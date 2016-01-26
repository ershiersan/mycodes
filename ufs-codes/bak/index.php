<?php
include_once('class/codes.class.php');

class myCodes extends codes {
    private $current = 10000;
    public function getCodingMsgByIndex($index, $autoincreament=false) {
        /**
         * 根据$index获取生成码的参数，并返回:
         *     [
         *         (int)    'current'=> '当前创建码的个数',
         *         (string) 'salt'=> '秘钥',
         *         (int)    'interference'=> '参与计算的乱序随机数',
         *     ]
         */
        $arrReturn = [
            'current'=> $this->current,
            'salt'=> 'hG#cGzBW*PxVskFP$4*a', // A-Za-z0-9!@#$%^&*
            'interference'=> '14162',  // 参加计算的随机数
        ];
        // * 如果$autoincreament为true，current的值加1存库
        if($autoincreament) {
            $this->current++;
        }
        return $arrReturn;
    }
}

$objCodesEncode = new myCodes();
$objCodesDecode = new myCodes();
$index = 1;
echo "index:{$index}\n";
$t_s = microtime(true);

for($i=0;$i<10;$i++){
    $t_st = microtime(true);
    $arrEncode = $objCodesEncode->encode($index, 11);
    if(!$arrEncode['success']){
        echo $arrEncode['msg']."\n";
        continue;
    }
    $t_mt = microtime(true);
    // echo $arrEncode['data']['code']."\t";
    $arrDecode = $objCodesDecode->decode(/*"J74K2NF2VT7"*/$arrEncode['data']['code']);
    $t_et = microtime(true);
    // echo $arrDecode['data']['index']."\t";
    echo
        $arrEncode['data']['code']."\t".
        number_format($t_mt-$t_st, 7)."\t| ".
        ($arrDecode['success']?$arrDecode['data']['index'].'-'.$arrDecode['data']['number']:$arrDecode['msg'])."\t".
        number_format($t_et-$t_mt, 7)."\n";
}
echo "total:{$i}, time:".number_format($t_et-$t_s, 7)."\n";
