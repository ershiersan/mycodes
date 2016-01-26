<?php

/**
 * Class codes
 */
class codes
{
    private $_numbering_bin_length  = null;
    private $_index_bin_length      = null;
    private $_garble_bin_length     = null;

    private $_bin_index = null;
    private $_index = null;
    private $_bin_numbering = null;
    private $_numbering = null;
    private $_bin_garble = null;

    private $_salt = null;
    private $_interference = null;
    private $_code_map = 'FXJ8HVD2ZW7CM6KLR4GT5PUE3AQNB9YS';

    function __construct() {

    }

    /**
    生成的码为11位32进制的码 = 11*5(32进制) = 55位（二进制）
    分配：
    27  numbering   134,217,727     唯一码生成数量
    14  index       16,383          索引ID最大值(int)
    14  garble      16,383          混淆的字符位数[泄露了算法，暴力可破解一组index/msg的尝试次数]
     ***/
    private $_length_config = [
        // 生成码的位数对应的位数分配
        // 生成码后，配置即不可修改
        '10' => [
            'numbering' => 27, // 1-134,217,727
            'index' => 14,     // 1-16,383
            'garble' => 9,
        ],
        '11' => [
            'numbering' => 27, // 1-134,217,727
            'index' => 14,     // 1-16,383
            'garble' => 14,
        ],
        '12' => [
            'numbering' => 27, // 1-134,217,727
            'index' => 14,     // 1-16,383
            'garble' => 19,
        ],
        '13' => [
            'numbering' => 27, // 1-134,217,727
            'index' => 14,     // 1-16,383
            'garble' => 24,
        ],
        '14' => [
            'numbering' => 27, // 1-134,217,727
            'index' => 19,     // 1-524,288
            'garble' => 24,
        ],
        '15' => [
            'numbering' => 27, // 1-134,217,727
            'index' => 19,     // 1-524,288
            'garble' => 29,
        ],
    ];
    /**
     * @ 根据主键序号获取主键信息
     * @param $action verify/create
     * @return array
     */
    private $current = 10000;
    private function getCodingMsgByIndex($action='verify') {
        // 根据$this->_index获取索引的信息
        $arrReturn = [
            'id'=> $this->_index,
            'current'=> $this->current,
            'salt'=> 'hG#cGzBW*PxVskFP$4*a', // A-Za-z0-9!@#$%^&*
            'interference'=> 14162,  // (numbering+garble)^3=68921种组合可能，可取1-100，000范围的随机数
        ];
        if(false) { // 不存在这个序号，返回false
            return false;
        }
        $this->_numbering = $arrReturn['current'];
        $this->_interference = $arrReturn['interference'];
        $this->_salt = $arrReturn['salt'];
        if($action == 'create') {
            // 生成唯一码的环节，current进行自增操作
            $this->current++;
        }
        return true;
//        return $arrReturn;
    }

    /**
     * @ 创建唯一码的入口
     * @param $index
     * @param $code_length // 生成码的位数
     * @return array
     */
    public function encode($index, $code_length=11) {
        if(!$this->initLength($code_length)) {
            return $this->returnArray(false,
                "Can not handle {$code_length}-character codes",
                [
            ]);
        }
        $this->_index = $index;
        $indexMsg = $this->getCodingMsgByIndex('create');
        if($indexMsg === false){ // 不存在解析出来的index
            return $this->returnArray(false, 'No such index', []);
        }

        // 编号超出最大存储范围
        $this->_bin_numbering = $this->nuToBin($this->_numbering, $this->_numbering_bin_length);
        if(strlen($this->_bin_numbering) > $this->_numbering_bin_length) {
            return $this->returnArray(false, 'Numbering exceed the maximum', [
                'number'=>$this->_numbering,
                'code'=>'',
            ]);
        }

        // 索引ID超出最大存储范围
        $this->_bin_index = $this->nuToBin($this->_index, $this->_index_bin_length);
        if(strlen($this->_bin_index) > $this->_index_bin_length) {
            return $this->returnArray(false, 'Index exceed the maximum', [
                'number'=>$this->_numbering,
                'code'=>'',
            ]);
        }

        $disruptedBin41 = $this->makeSignAndMixWithNumber();
        $endMix = $this->implodeBins($this->binNot($this->_bin_index), $disruptedBin41);
//echo $disruptedBin41."\t";
        return $this->returnArray(true, 'success', [
            'number'=>$this->_numbering,
            'code'=>$this->binToStr($endMix),
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
        list($bin_index, $disruptedBin41) = $this->explodeBins($endMix, [$this->_index_bin_length, 0]);
//        list($bin_index, $disruptedBin41) =  (c_explodeBins($endMix, $this->_index_bin_length));
        $this->_bin_index = $this->binNot($bin_index);

        // 索引ID
        $this->_index = bindec($this->_bin_index);

        $indexMsg = $this->getCodingMsgByIndex();
        if($indexMsg === false){ // 不存在解析出来的index
            return $this->returnArray(false, 'No such index', []);
        }

        $verifiedResult = $this->splitMixedAndVerified($disruptedBin41);

        if(!$verifiedResult) {
            return $this->returnArray(false, 'Verification failed', [
//                'index'=>$this->_index,
//                'number'=>$this->_numbering,
            ]);
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
    private function makeSignAndMixWithNumber() {
        /* prefer to C code
        input:
            $this->_salt
            $this->_numbering
            $this->_garble_bin_length
            $this->_bin_numbering
            $this->_numbering_bin_length
            $this->_interference

        output:
            $disruptedBin41
        */
        {
            // 签名二进制
            $bin_garble = $this->getGarbleBin($this->_salt, $this->_numbering, $this->_garble_bin_length);
            // 混合签名和编号二进制
            $implodeBin41 = $this->implodeBins($bin_garble, $this->_bin_numbering);
            // 获取乱序的参数
            $arrDisruptParams = $this->getDisruptParams($this->_garble_bin_length, $this->_numbering_bin_length, $this->_interference+pow($this->getBinSum($implodeBin41), 3));
            // 获取乱序的（混合签名和编号二进制）
            $disruptedBin41 = $this->disruptOrder($implodeBin41, $arrDisruptParams);
        }

//        echo json_encode($arrDisruptParams)."\t";

        return $disruptedBin41;


        /*return (c_makeSignAndMixWithNumber(
            $this->_salt,
            $this->_numbering,
            $this->_bin_numbering,
            $this->_numbering_bin_length,
            $this->_garble_bin_length,
            $this->_interference
        ));*/

    }

    /**
     * @乱序恢复，拆分出序号二进制和签名二进制并校验
     * @return boolean
     **/
    private function splitMixedAndVerified($disruptedBin41) {
        /* prefer to C code
        input:
            $this->_garble_bin_length
            $this->_numbering_bin_length
            $this->_interference
            $this->_salt

        output:
            $this->_numbering
            $this->_bin_numbering
        */
        {
            // 获取乱序的参数
            $arrDisruptParams = $this->getDisruptParams($this->_garble_bin_length, $this->_numbering_bin_length, $this->_interference+pow($this->getBinSum($disruptedBin41), 3));
            // 获取原顺序的（混合签名和编号二进制）
            $implodeBin41 = $this->recoverOrder($disruptedBin41, $arrDisruptParams);
            list($bin_garble_to_verified, $bin_numbering) = $this->explodeBins($implodeBin41, [$this->_garble_bin_length, 0]);
//            echo $bin_garble_to_verified."\t".$bin_numbering."\n";
            $numbering = bindec($bin_numbering);
            $bin_garble = $this->getGarbleBin($this->_salt, $numbering, $this->_garble_bin_length);
        }
        $this->_bin_numbering = $bin_numbering;
        $this->_numbering = $numbering;

//        echo "\n".json_encode($arrDisruptParams)."\t";

        if($bin_garble == $bin_garble_to_verified) {
            return true;
        } else {
            return false;
        }


        /*
        array(3) {
          ["verify"]=>
          bool(true)
          ["_numbering"]=>
          int(10000)
          ["_bin_numbering"]=>
          string(27) "000000000000010011100010000"
        }
         */

        /*$valifyResult = c_splitMixedAndVerified(
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
        }*/
    }

    /**
     * @根据二进制获取所有字节的和
     * @param $bin
     * @return int
     * */
    private function getBinSum($bin) {
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
    private function getDisruptParams($garble_bin_length, $numbering_bin_length, $interference) {
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
     * @使用参数对二进制串进行乱序
     * @param $bin
     * @param $params
     * @return string
     **/
    private function disruptOrder($bin, $params) {
        // 根据$params对混合二进制串进行乱序 ......
        if($params['step'] != 0) {
            $bin = substr($bin, $params['step']).substr($bin, 0, $params['step']);
        }
        $binMix = '';
        $strLength = strlen($bin);

        $tH = 0;
        $tW = 0;
        $tL = 0;
        $params['width'] ++;
        $params['length'] ++;
        for($i=0; $i<$strLength; $i++) {
            if($tH*$params['length']*$params['width']+$tW*$params['length']+$tL >= $strLength) {
                $tH = 0;
                $tL++;
            }
            if($tL >= $params['length']) {
                $tL = 0;
                $tW++;
            }
            $binMix .= $bin{$tH*$params['length']*$params['width']+$tW*$params['length']+$tL};
            $tH++;
        }

        return $binMix;
    }

    /**
     * 使用参数对二进制串恢复原顺序
     * @param $endMix
     * @param $params
     * @return string
     **/
    private function recoverOrder($endMix, $params) {
        // 根据$params对乱序的混合二进制串恢复排序 ......
        $arrBin = [];
        $strLength = strlen($endMix);
        $tH = 0;
        $tW = 0;
        $tL = 0;
        $params['width'] ++;
        $params['length'] ++;
        for($i=0; $i<$strLength; $i++) {
            if($tH*$params['length']*$params['width']+$tW*$params['length']+$tL >= $strLength) {
                $tH = 0;
                $tL++;
            }
            if($tL >= $params['length']) {
                $tL = 0;
                $tW++;
            }
            $arrBin[$tH*$params['length']*$params['width']+$tW*$params['length']+$tL] = $endMix{$i};
            $tH++;
        }
        ksort($arrBin);
        $returnBin = implode($arrBin);
        $returnBin = substr($returnBin, $strLength-$params['step']).substr($returnBin, 0, $strLength-$params['step']);
        return $returnBin;
    }

    /**
     * 两个二进制串混合
     **/
    private function implodeBins($strBin1, $strBin2) {
        $strBin1 = strrev($strBin1);
        $lengthBin1 = strlen($strBin1);
        $lengthBin2 = strlen($strBin2);
        $lengthSum = $lengthBin1+$lengthBin2;

        $tempSum1 = 0;
        $tempSum2 = 0;
        $tempindex1 = 0;
        $tempindex2 = 0;
        $binReturn = '';
        for($i=0; $i<$lengthSum; $i++) {
            if($tempSum1 >= $tempSum2) {
                $binReturn .= $strBin1{$tempindex1++};
                $tempSum2 += $lengthBin2;
            } else {
                $binReturn .= $strBin2{$tempindex2++};
                $tempSum1 += $lengthBin1;
            }
        }
        $halfSum = floor($lengthSum/4);
//        echo substr($binReturn, $halfSum)."|".substr($binReturn, 0, $halfSum)."\t";
        return (substr($binReturn, $halfSum).substr($binReturn, 0, $halfSum));
//        return strrev($binReturn);
    }

    /**
     * 对混合的两个二进制串，按照各自长度进行拆分
     **/
    private function explodeBins($binMix, $arrLength=[14, 14]) {
        $lengthSum = strlen($binMix);
        $halfSum = $lengthSum - floor($lengthSum/4);
        $binMix = substr($binMix, $halfSum).substr($binMix, 0, $halfSum);

        $lengthBin1 = $arrLength[0];
        $lengthBin2 = $lengthSum - $arrLength[0];
        $strBin1 = '';
        $strBin2 = '';
        $tempSum1 = 0;
        $tempSum2 = 0;
        for($i=0; $i<$lengthSum; $i++) {
            if($tempSum1 >= $tempSum2) {
                $strBin1 .= $binMix{$i};
                $tempSum2 += $lengthBin2;
            } else {
                $strBin2 .= $binMix{$i};
                $tempSum1 += $lengthBin1;
            }
        }

        return [strrev($strBin1), $strBin2];
    }

    /**
     * @ 根据salt和number进行散列，获取14位二进制不可逆混淆码
     * @params $salt
     * @params $number
     * @params $garble_bin_length
     * @return null
     */
    private function getGarbleBin($salt, $number, $garble_bin_length) {
        $str = strrev($salt.$number);
        $strSha = md5($str);
        $binReturn = '';
        for($i=0; $i<$garble_bin_length; $i++) {
            $binReturn .= ord($strSha{$i}) % 2;
        }
//        echo $binReturn."\t";
        return $binReturn;
    }

    /**
     * 十进制转二进制并补齐最小位数
     **/
    private function nuToBin($nu, $mixLength) {
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
    private function binToStr($bin) {
        $strReturn = '';
        for($i=0; $i<ceil(strlen($bin)/5); $i++) {
            $strReturn .= $this->_code_map{bindec(substr($bin, $i*5, 5))};
        }
        return $strReturn;
    }

    /**
     * 32进制字符串转二进制串
     **/
    private function strToBin($str) {
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
    private function initLength($code_length) {
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
    private function binNot($bin) {
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
    private function returnArray($issuccess, $msg, $data) {
        return [
            'success'=> $issuccess,
            'msg'=> $msg,
            'data'=> $data,
        ];
    }
}
