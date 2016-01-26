<?php

/**
 * Class codes
 */
class codes
{
    /**
    生成的码为11位32进制的码 = 11*5(32进制) = 55位（二进制）
    分配：
        27  numbering   134,217,727     唯一码生成数量
        14  index       16,383          索引ID最大值(int)
        14  garble      16,383          混淆的字符位数[泄露了算法，暴力可破解一组index/msg的尝试次数]
    ***/
    const NUMBERING_BIN_LENGTH  = 27;
    const INDEX_BIN_LENGTH      = 14;
    const GARBLE_BIN_LENGTH     = 14;
    private $_bin_index = '';
    private $_index = '';
    private $_bin_numbering = '';
    private $_numbering = '';
    private $_bin_garble = '';
    private $_salt = '';
    private $_code_map = 'NPQRS6789TUVWXYZ45ABCDEF123GHJKM';

    /**
     * @ 根据主键序号获取主键信息
     * @param $action verify/create
     * @return array
     */
    private $current = 10241024;
    private function getCodingMsgByIndex($action='verify') {
        // 根据$this->_index获取索引的信息
        $arrReturn = [
            'id'=> $this->_index,
            'min'=> 123,
            'max'=> 111110000,
            'current'=> $this->current,
            'salt'=> 'hG#cGzBW*PxVskFP$4*a', // A-Za-z0-9!@#$%^&*
        ];
        if(false) { // 不存在这个序号，返回false
            return false;
        }
        $this->_numbering = $arrReturn['current'];
        $this->_salt = $arrReturn['salt'];
        if($action == 'create') {
            // 生成唯一码的环节，current进行自增操作
            $this->current++;
        }
        return $arrReturn;
    }

    /**
     * @ 创建唯一码的入口
     * @param $index
     * @return array
     */
    public function encode($index) {
        $this->_index = $index;
        $indexMsg = $this->getCodingMsgByIndex('create');
        // 编号不在可用范围内
        if($this->_numbering < $indexMsg['min'] || $this->_numbering > $indexMsg['max']) {
            return $this->returnArray(false, 'Numbering in invalid range', [
                'number'=>$this->_numbering,
                'min'=>$indexMsg['min'],
                'max'=>$indexMsg['max'],
                'code'=>'',
            ]);
        }

        // 编号超出最大存储范围
        $this->_bin_numbering = $this->nuToBin($this->_numbering, self::NUMBERING_BIN_LENGTH);
        if(strlen($this->_bin_numbering) > self::NUMBERING_BIN_LENGTH) {
            return $this->returnArray(false, 'Numbering exceed the maximum', [
                'number'=>$this->_numbering,
                'min'=>$indexMsg['min'],
                'max'=>$indexMsg['max'],
                'code'=>'',
            ]);
        }

        // 索引ID超出最大存储范围
        $this->_bin_index = $this->nuToBin($this->_index, self::INDEX_BIN_LENGTH);
        if(strlen($this->_bin_index) > self::INDEX_BIN_LENGTH) {
            return $this->returnArray(false, 'Index exceed the maximum', [
                'number'=>$this->_numbering,
                'min'=>$indexMsg['min'],
                'max'=>$indexMsg['max'],
                'code'=>'',
            ]);
        }
        $this->_bin_garble = $this->getGarbleBin();
        // 混淆码和主键ID的混合二进制串
        $binMix = $this->implodeBins($this->_bin_garble, $this->_bin_index);
        // 混合二进制串进行乱序和numbering二进制串进行混合
        $endMix = $this->disruptOrderAndImplode($binMix);

        return $this->returnArray(true, 'success', [
            'number'=>$this->_numbering,
            'min'=>$indexMsg['min'],
            'max'=>$indexMsg['max'],
            'code'=>$this->binToStr($endMix),
        ]);
    }

    /**
     * @ 唯一码解码及验证
     * @param $code
     * @return array
     */
    public function decode($code) {
        $endMix = $this->strToBin($code);
        // 最终串还原成混合二进制串和numbering
        $binMix = $this->explodeAndRecoverOrder($endMix);
        // 混合二进制串还原成混淆码和主键ID
        list($this->_bin_garble, $this->_bin_index) = $this->explodeBins($binMix, [self::GARBLE_BIN_LENGTH, 0]);

        // 索引ID超出最大存储范围
        $this->_index = bindec($this->_bin_index);

        $indexMsg = $this->getCodingMsgByIndex();
        if($indexMsg === false){ // 不存在解析出来的index
            return $this->returnArray(false, 'No index', []);
        }
        // 编号不在可用范围内
        if($this->_numbering < $indexMsg['min'] || $this->_numbering > $indexMsg['max']) {
            return $this->returnArray(false, 'Numbering in invalid range', [
                'index'=>$this->_index,
                'number'=>$this->_numbering,
                'min'=>$indexMsg['min'],
                'max'=>$indexMsg['max'],
            ]);
        }

        // 编号超出最大存储范围（不可能发生）
        $this->_bin_numbering = $this->nuToBin($this->_numbering, self::NUMBERING_BIN_LENGTH);
        if(strlen($this->_bin_numbering) > self::NUMBERING_BIN_LENGTH) {
            return $this->returnArray(false, 'Numbering exceed the maximum', [
                'index'=>$this->_index,
                'number'=>$this->_numbering,
                'min'=>$indexMsg['min'],
                'max'=>$indexMsg['max'],
            ]);
        }

        // 索引ID超出最大存储范围（不可能发生）
        $this->_bin_index = $this->nuToBin($this->_index, self::INDEX_BIN_LENGTH);
        if(strlen($this->_bin_index) > self::INDEX_BIN_LENGTH) {
            return $this->returnArray(false, 'Index exceed the maximum', [
                'index'=>$this->_index,
                'number'=>$this->_numbering,
                'min'=>$indexMsg['min'],
                'max'=>$indexMsg['max'],
            ]);
        }

        return $this->returnArray(true, 'success', [
            'index'=>$this->_index,
            'number'=>$this->_numbering,
        ]);
    }

    /**
     * 根据numbering获取乱序的参数
     * @params $binLength
     **/
    private function getDisruptParams() {
        $_bin_length = self::INDEX_BIN_LENGTH+self::GARBLE_BIN_LENGTH;
        $i = $this->_numbering%(pow($_bin_length, 3));
        // 散列参数顺序：宽|长|位移
        $step = $i%$_bin_length;
        $length = ($i-$step)/$_bin_length%$_bin_length;
        $width = ($i-$step-$length)/pow($_bin_length,2)%$_bin_length;
        return ['step'=>$step, 'length'=>$length, 'width'=>$width];
    }

    /**
     * 根据numbering对混合二进制串进行乱序，并和numbering二进制进行混合
     **/
    private function disruptOrderAndImplode($bin) {
        $params = $this->getDisruptParams();
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

        return $this->implodeBins($this->_bin_numbering, $binMix);
    }

    /**
     * 根据numbering对混合二进制串进行乱序，并和numbering二进制进行混合
     * 对混合串拆分混合二进制串和numbering二进制串，并恢复原排序
     **/
    private function explodeAndRecoverOrder($endMix) {
        list($this->_bin_numbering, $hashBin) = $this->explodeBins($endMix, [self::NUMBERING_BIN_LENGTH, 0]);
        $this->_numbering = bindec($this->_bin_numbering);
        $params = $this->getDisruptParams();

        // 根据$params对乱序的混合二进制串恢复排序 ......
        $arrBin = [];
        $strLength = strlen($hashBin);
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
            $arrBin[$tH*$params['length']*$params['width']+$tW*$params['length']+$tL] = $hashBin{$i};
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
        $lengthMin = ($lengthBin1 > $lengthBin2)?$lengthBin2:$lengthBin1;
        $binReturn = '';
        for($i=0; $i<$lengthMin; $i++) {
            $binReturn .= $strBin1{$i}.$strBin2{$i};
        }
        $binReturn .= ($lengthBin1 > $lengthBin2)? substr($strBin1, $lengthMin):substr($strBin2, $lengthMin);
        return $binReturn;
    }

    /**
     * 对混合的两个二进制串，按照各自长度进行拆分
     **/
    private function explodeBins($binMix, $arrLength=[14, 14]) {
        $lengthBin1 = $arrLength[0];
        $lengthBin2 = strlen($binMix) - $arrLength[0];
        $strBin1 = '';
        $strBin2 = '';
        $lengthMin = ($lengthBin1 > $lengthBin2)?$lengthBin2:$lengthBin1;
        for($i=0; $i<$lengthMin; $i++) {
            $strBin1 .= $binMix{$i*2};
            $strBin2 .= $binMix{$i*2+1};
        }
        if($lengthBin1 > $lengthBin2) {
            $strBin1 .= substr($binMix, $lengthMin*2);
        } else {
            $strBin2 .= substr($binMix, $lengthMin*2);
        }
        return [strrev($strBin1), $strBin2];
    }

    /**
     * @ 根据salt和number进行散列，获取14位二进制不可逆混淆码
     * @return null
     */
    private function getGarbleBin() {
        $str = strrev($this->_salt.$this->_numbering);
        $strSha = sha1($str);
        $binReturn = '';
        for($i=0; $i<self::GARBLE_BIN_LENGTH; $i++) {
            $binReturn .= ord($strSha{$i}) % 2;
        }
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
