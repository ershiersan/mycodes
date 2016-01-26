<?php

/**
 * Class codes
 */
class codes
{
    protected $_numbering_bin_length  = null;
    protected $_index_bin_length      = null;
    protected $_garble_bin_length     = null;

    protected $_bin_index = null;
    protected $_index = null;
    protected $_bin_numbering = null;
    protected $_numbering = null;
    protected $_bin_garble = null;

    protected $_salt = null;
    protected $_interference = null;
    protected $_code_map = null;
    protected $_length_config = [];

    function __construct() {
        // 32进制代码串，通过c接口获取
        $this->_code_map = c_getCodeMap();
        // code位数对应各部分组个位数的数组
        $this->_length_config = c_getLengthConfig();
    }


    /**
     * @ 根据主键序号获取主键信息，需要继承重载此方法
     * @param $index            根据$index获取信息
     * @param $autoincreament   是否需要current自增，生成码时传true
     * @param $count            生成码的数量
     * @return array
     */
    protected function getCodingMsgByIndex($index, $autoincreament=false, $count=1) {
        /**
         * 根据$index获取该索引的信息，并返回:
         *     [
         *         (int)    'current'=> '当前创建码的起始数',
         *         (string) 'salt'=> '秘钥',
         *         (int)    'interference'=> '参与计算的乱序随机数',
         *     ]
         * 如果$autoincreament为true，current的值加$count保存
         */
        return false;
    }

    // getCodingMsgByIndex调用方法
    private function callCoding($action='verify', $count=1) {
        try{
            $arrReturn = $this->getCodingMsgByIndex($this->_index, $action=="create", $count);
        } catch (Exception $e) {
            return false;
        }
        if(is_array($arrReturn)
            && array_key_exists('current', $arrReturn)
            && preg_match("/^\d+$/", $arrReturn['current'])
            && array_key_exists('interference', $arrReturn)
            && preg_match("/^\d+$/", $arrReturn['interference'])
            && array_key_exists('salt', $arrReturn)
        ) {
            $this->_numbering = $arrReturn['current'];
            $this->_interference = $arrReturn['interference'];
            $this->_salt = $arrReturn['salt'];
            return true;
        } else {
            return false;
        }
    }

    /**
     * @ 创建唯一码的入口
     * @param $index
     * @param $code_length // 生成码的位数
     * @return array
     */
    public function encode($index, $code_length=11, $count = 1) {
        if(!$this->initLength($code_length)) {
            return $this->returnArray(false,
                "Can not handle {$code_length}-character codes",
                [
            ]);
        }
        $this->_index = $index;
        $indexMsg = $this->callCoding('create', $count);

        $arrCodings = [];
        for($i=0; $i<$count; $i++) {
            if ($indexMsg === false) { // 不存在解析出来的index
                return $this->returnArray(false, 'Index message id not exist or not correct', []);
            }
            // 编号超出最大存储范围
            $this->_bin_numbering = $this->nuToBin($this->_numbering, $this->_numbering_bin_length);
            if (strlen($this->_bin_numbering) > $this->_numbering_bin_length) {
                return $this->returnArray(false, 'Numbering exceed the maximum', []);
            }

            // 索引ID超出最大存储范围
            $this->_bin_index = $this->nuToBin($this->_index, $this->_index_bin_length);
            if (strlen($this->_bin_index) > $this->_index_bin_length) {
                return $this->returnArray(false, 'Index exceed the maximum', []);
            }

            $disruptedBin41 = $this->makeSignAndMixWithNumber();
            $endMix = c_implodeBins($this->binNot($this->_bin_index), $disruptedBin41);
            $arrCodings[] = $this->binToStr($endMix);
            $this->_numbering++;
        }
        return $this->returnArray(true, 'success', [
            'number'=>['start'=>$this->_numbering-$count, 'end'=>$this->_numbering-1],
            'code'=>$arrCodings,
        ]);
    }

    /**
     * @ 唯一码解码及验证
     * @param $code
     * @return array
     */
    public function decode($code) {
        $code = trim($code);
        $code_length = strlen($code);
        if(!$this->initLength($code_length)) {
            return $this->returnArray(false,
                "Can not handle {$code_length}-character codes",
                [
                ]);
        }
        $endMix = $this->strToBin($code);
        // 混合二进制串还原成混淆码和主键ID
        list($bin_index, $disruptedBin41) =  (c_explodeBins($endMix, $this->_index_bin_length));
        $this->_bin_index = $this->binNot($bin_index);

        // 索引ID
        $this->_index = bindec($this->_bin_index);

        $indexMsg = $this->callCoding();
        if($indexMsg === false){ // 不存在解析出来的index
            return $this->returnArray(false, 'Index message id not exist or not correct', []);
        }

        $verifiedResult = $this->splitMixedAndVerified($disruptedBin41);

        if(!$verifiedResult) {
            return $this->returnArray(false, 'Verification failed', []);
        }

        return $this->returnArray(true, 'success', [
            'index'=>$this->_index,
            'number'=>$this->_numbering,
        ]);
    }

    /**
     * @生成乱序的签名二进制，和序号二进制拼接并使用干扰码进行乱序
     * @return bin41
     **/
    protected function makeSignAndMixWithNumber() {


        return (c_makeSignAndMixWithNumber(
            $this->_salt,
            $this->_numbering,
            $this->_bin_numbering,
            $this->_numbering_bin_length,
            $this->_garble_bin_length,
            $this->_interference
        ));

    }

    /**
     * @乱序恢复，拆分出序号二进制和签名二进制并校验
     * @return boolean
     **/
    protected function splitMixedAndVerified($disruptedBin41) {
        $valifyResult = c_splitMixedAndVerified(
            $disruptedBin41,
            $this->_salt,
            $this->_interference,
            $this->_garble_bin_length,
            $this->_numbering_bin_length
        );
        if($valifyResult["verify"]) {
            $this->_bin_numbering = $valifyResult["_bin_numbering"];
            $this->_numbering = $valifyResult["_numbering"];
            return true;
        } else {
            return false;
        }
    }

    /**
     * @根据二进制获取所有字节的和
     * @param $bin
     * @return int
     * */
    protected function getBinSum($bin) {
        $sum = 0;
        for($i=0; $i<strlen($bin); $i++) {
            $sum += $bin{$i};
        }
        return $sum;
    }

    /**
     * 根据interference获取乱序的参数
     * @params $garble_bin_length
     * @params $numbering_bin_length
     * @params $interference
     * @return bin_string
     **/
    protected function getDisruptParams($garble_bin_length, $numbering_bin_length, $interference) {
        $_bin_length = $garble_bin_length + $numbering_bin_length;
        $i = $interference%(pow($_bin_length, 3));
        // 散列参数顺序：宽|长|位移
        $step = $i%$_bin_length;
        $length = ($i-$step)/$_bin_length%$_bin_length;
        $width = ($i-$step-$length)/pow($_bin_length,2)%$_bin_length;
//        echo $interference."|".$step."|".$length."|".$width."\t";
        return ['step'=>$step, 'length'=>$length, 'width'=>$width];
    }

    /**
     * 十进制转二进制并补齐最小位数
     **/
    protected function nuToBin($nu, $mixLength) {
        $bin = decbin($nu);
        $lengthBin = strlen($bin);
        for($i=0; $i<$mixLength-$lengthBin; $i++) {
            $bin = '0'.$bin;
        }
        return $bin;
    }

    /**
     * 二进制串转32进制字符串
     **/
    protected function binToStr($bin) {
        $strReturn = '';
        for($i=0; $i<ceil(strlen($bin)/5); $i++) {
            $strReturn .= $this->_code_map{bindec(substr($bin, $i*5, 5))};
        }
        return $strReturn;
    }

    /**
     * 32进制字符串转二进制串
     **/
    protected function strToBin($str) {
        $str = trim($str);
        $binReturn = '';
        for($i=0; $i<strlen($str); $i++) {
            $position = strpos($this->_code_map, $str{$i});
            if($position === false) {
                return false;
            }
            $binReturn .= $this->nuToBin($position, 5);
        }
        return $binReturn;
    }

    /**
     * 根据码的位数，初始化各数值
     **/
    protected function initLength($code_length) {
        if(!array_key_exists($code_length, $this->_length_config)) {
            return false;
        }
        $this->_numbering_bin_length  = $this->_length_config[$code_length]['numbering'];
        $this->_index_bin_length      = $this->_length_config[$code_length]['index'];
        $this->_garble_bin_length     = $this->_length_config[$code_length]['garble'];
        return true;
    }

    /**
     * 按位取反
     **/
    protected function binNot($bin) {
        $binReturn = '';
        $binLength = strlen($bin);
        for($i=0; $i<$binLength; $i++) {
            $binReturn .= 1-$bin{$i};
        }
        return $binReturn;
    }

    /**
     * encode、decode返回的数组结构
     **/
    protected function returnArray($issuccess, $msg, $data) {
        return [
            'success'=> $issuccess,
            'msg'=> $msg,
            'data'=> $data,
        ];
    }

}
