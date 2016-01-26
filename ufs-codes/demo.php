<?php
//include_once('class/codes.c.class.php');
include_once('class/codes.php.class.php');

// 继承codes基类并重写getCodingMsgByIndex方法
class myCodes extends codes {
    /**
     * @ 根据主键序号获取主键信息，需要继承重载此方法
     * @param $index            根据$index获取信息
     * @param $autoincreament   是否需要current自增，生成码时传true
     * @param $count            生成码的数量
     * @return array
     */
    public function getCodingMsgByIndex($index, $autoincreament=false, $count=1) {
        /**
         * 根据$index获取生成码的参数，并返回:
         *     [
         *         (int)    'current'=> '当前已创建码的个数，自增',
         *         (string) 'salt'=> '秘钥',
         *         (int)    'interference'=> '参与计算的乱序随机数',
         *     ]
         */
        $current = 888;
//        $arrReturn = objIndex->findOne(['id'=> $index]);
        $arrReturn = [
            'current'=> $current,
            'salt'=> 'hG#cGzBW*PxVskFP$4*a', // A-Za-z0-9!@#$%^&*
            'interference'=> '14162',  // 参加计算的随机数
        ];
        // * 如果$autoincreament为true，current的值加1存库
        if($autoincreament) {
//            $arrReturn->current += $count;
//            $arrReturn->save();
            $current += $count;
        }
        return $arrReturn;
    }
}

// 生成码的对象
$objCodesEncode = new myCodes();

$index = 10000;
/**
 * $code_length 码位数
 * $count       生成数量
 * */
$arrEncode = $objCodesEncode->encode($index, $code_length=11, $count=11120);
echo "生成成功：\n";
var_dump($arrEncode);

$arrEncode = $objCodesEncode->encode($index, $code_length=15);
echo "支持生成10-15位码：\n";
var_dump($arrEncode);

$index = 20000;
$arrEncode = $objCodesEncode->encode($index, 11);
echo "index值溢出：\n";
var_dump($arrEncode);

$index = 10000;
$arrEncode = $objCodesEncode->encode($index, 16);
echo "不支持16位的码：\n";
var_dump($arrEncode);

echo "\n\n\n";
// ...



// 校验码的对象
$objCodesDecode = new myCodes();

$arrDecode = $objCodesDecode->decode("H6YRZR75K7T");
echo "验证成功：\n";
var_dump($arrDecode);

$arrDecode = $objCodesDecode->decode("UCYVQARJM6P57NF");
echo "验证成功：\n";
var_dump($arrDecode);

$arrDecode = $objCodesDecode->decode("FHM3RJZRHZF");
echo "验证失败：\n";
var_dump($arrDecode);

$arrDecode = $objCodesDecode->decode("FHM3RJZRHZFRHZFF");
echo "不支持16位：\n";
var_dump($arrDecode);