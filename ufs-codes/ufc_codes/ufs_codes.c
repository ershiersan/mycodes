// ufs_codes.c
#include <string.h>
#include <stdio.h>
#include <math.h>
#include "php_ufs_codes.h"
//#include "functions.c"
#include "md5.c"

// 二进制字符串转十进制
int bindec(char* strBin) {
    int strBinLength = strlen(strBin);
    int i;
    int returnValue = 0;
    for(i=0; i<strBinLength; i++) {
        returnValue = returnValue * 2;
        char p[2] = {strBin[i]};
        returnValue = returnValue + atoi(p);
    }
    return returnValue;
}

// 获取二进制所有位数的和
int getBinSum(char* bin) {
    int sum = 0;
    int i;
    for(i=0; i<strlen(bin); i++) {
        char p[2] = {bin[i]};
        sum += atoi(p);
    }
    return sum;
}

// 字符串反转
char* strrev(char* s)
{
    /* h指向s的头部 */
    char* h = s;
    char* t = s;
    char ch;
    /* t指向s的尾部 */
    while(*t++){};
    t--;    /* 与t++抵消 */
    t--;    /* 回跳过结束符'\0' */
    /* 当h和t未重合时，交换它们所指向的字符 */
    while(h < t)
    {
        ch = *h;
        *h++ = *t;    /* h向尾部移动 */
        *t-- = ch;    /* t向头部移动 */
    }
    return(s);
}

// substr
char* my_substr(char * string, int start, int length, char* my_substr_return) {
    char stringReturn[90] = "";
    int i;
    for(i=0; i<length; i++) {
        char p[2] = {string[start+i]};
        strcat(stringReturn, p);
    }
    my_substr_return = (char *)stringReturn;
    return my_substr_return;
}

// 根据salt和number进行散列，获取14位二进制不可逆混淆码
char* getGarbleBin(char *_salt, char *_numbering, char *_garble_bin_length, char *bin_garble) {
    int garble_bin_length;
    garble_bin_length = atoi(_garble_bin_length);
    char concat[90];
    strcpy(concat, _salt);
    strcat(concat, _numbering);
    char *reverse = strrev(concat);
    int i;
    unsigned char decrypt[16];
    MD5_CTX md5;
    MD5Init(&md5);
    MD5Update(&md5, concat, strlen((char *)concat));
    MD5Final(&md5,decrypt);
    char strmd5[90] = "";
    for(i=0;i<16;i++)
    {
        char s[10];
        sprintf(s, "%02x",decrypt[i]);
        strcat(strmd5, s);
    }

    char* h = strmd5;
    char ch;
    char binReturn[90] = "";
    int index = 0;
    while(index++ < garble_bin_length)
    {
        ch = *h;
        int ascii = ch;
        ascii = ascii % 2;
        char modvalue[3];
        sprintf(modvalue, "%d", ascii);
        strcat(binReturn, modvalue);
        *h++;
    }
    bin_garble = (char *)binReturn;
    return bin_garble;
/** php:
        $str = strrev($salt.$number);
        $strSha = md5($str);
        $binReturn = '';
        for($i=0; $i<$garble_bin_length; $i++) {
            $binReturn .= ord($strSha{$i}) % 2;
        }
        return $binReturn;*/
}


// 对混合的两个二进制串，按照各自长度进行拆分
void explodeBins(char* binMix, int intLength1, char* bin_garble_to_verified, char* bin_numbering) {
    int lengthSum = strlen(binMix);
    int halfSum = lengthSum - lengthSum/4;
    char binMixStep[100] = "";
    char* my_substr_return;
    strcat(binMixStep, my_substr(binMix, halfSum, lengthSum-halfSum, my_substr_return));
    strcat(binMixStep, my_substr(binMix, 0, halfSum, my_substr_return));
    int lengthBin1 = intLength1;
    int lengthBin2 = lengthSum - intLength1;
    char strBin1[90] = "", strBin2[90] = "";
    int tempSum1 = 0, tempSum2 = 0;

    int i;
    for(i=0; i<lengthSum; i++) {
        char p[2] = {binMixStep[i]};
        if(tempSum1 >= tempSum2) {
            strcat(bin_garble_to_verified, p);
            tempSum2 += lengthBin2;
        } else {
            strcat(bin_numbering, p);
            tempSum1 += lengthBin1;
        }
    }
    bin_garble_to_verified = strrev(bin_garble_to_verified);

/*        $lengthSum = strlen($binMix);
        $halfSum = $lengthSum - floor($lengthSum/4);
        $binMix = substr($binMix, $halfSum).substr($binMix, 0, $halfSum);

        $lengthBin1 = $intLength1;
        $lengthBin2 = $lengthSum - $intLength1;
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

        return [strrev($strBin1), $strBin2];*/
}

// 两个二进制串混合
char* implodeBins(char *strBin1, char *strBin2, char implodeBin41[]) {
    strBin1 = strrev(strBin1);
    int lengthBin1 = strlen(strBin1);
    int lengthBin2 = strlen(strBin2);
    int lengthSum = lengthBin1 + lengthBin2;

    int tempSum1 = 0;
    int tempSum2 = 0;
    int tempindex1 = 0;
    int tempindex2 = 0;
    char binReturn[90] = "";
    int i;
    for(i=0; i<lengthSum; i++) {
        if(tempSum1 >= tempSum2) {
            char p[2] = {strBin1[tempindex1++]};
            strcat(binReturn, p);
            tempSum2 += lengthBin2;
        } else {
            char p[2] = {strBin2[tempindex2++]};
            strcat(binReturn, p);
            tempSum1 += lengthBin1;
        }
    }
    int halfSum = (int)(lengthSum/4);

    char* my_substr_return;
    strcat(implodeBin41, my_substr(binReturn, halfSum, lengthSum-halfSum, my_substr_return));
    strcat(implodeBin41, my_substr(binReturn, 0, halfSum, my_substr_return));
    return (char *)implodeBin41;

        /*$strBin1 = strrev($strBin1);
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
        return (substr($binReturn, $halfSum).substr($binReturn, 0, $halfSum));*/
}


// 根据interference获取乱序的参数
void getDisruptParams(int garble_bin_length, int numbering_bin_length, int interference, int arrDisruptParams[]) {
    int _bin_length = garble_bin_length + numbering_bin_length;
    int pow_bin_length = pow(_bin_length, 3);
    int i = interference % pow_bin_length;
    // 散列参数顺序：宽|长|位移
    int step = i % _bin_length;
    int length = (i-step)/_bin_length%_bin_length;
    int temp = (i-step-length)/pow(_bin_length,2);
    int width = temp%_bin_length;
    arrDisruptParams[0] = step;
    arrDisruptParams[1] = length;
    arrDisruptParams[2] = width;

/*    $_bin_length = $garble_bin_length + $numbering_bin_length;
    $i = $interference%(pow($_bin_length, 3));
    // 散列参数顺序：宽|长|位移
    $step = $i%$_bin_length;
    $length = ($i-$step)/$_bin_length%$_bin_length;
    $width = ($i-$step-$length)/pow($_bin_length,2)%$_bin_length;
    return ['step'=>$step, 'length'=>$length, 'width'=>$width];*/
}

// 获取原顺序的（混合签名和编号二进制）
char* recoverOrder(char* disruptedBin41, int params[], char disruptedBin41temp[]) {
    char arrBin[90] = "";
    int strLength = strlen(disruptedBin41);
    int tH = 0, tW = 0, tL = 0;
    params[1] ++;
    params[2] ++;
    int i;
    for(i=0; i<strLength; i++) {
        if(tH*params[1]*params[2]+tW*params[1]+tL >= strLength) {
            tH = 0;
            tL = tL + 1;
        }
        if(tL >= params[1]) {
            tL = 0;
            tW = tW + 1;
        }
        arrBin[tH*params[1]*params[2]+tW*params[1]+tL] = disruptedBin41[i];
        tH++;
    }

    char* my_substr_return1;
    char* my_substr_return2;
    my_substr_return1 = my_substr(arrBin, strlen(arrBin)-params[0], params[0], my_substr_return1);
    if(strlen(my_substr_return1) > 0) {
        strcat(disruptedBin41temp, my_substr_return1);
    }

    my_substr_return2 = my_substr(arrBin, 0, strlen(arrBin)-params[0], my_substr_return2);
    if(strlen(my_substr_return2) > 0) {
        strcat(disruptedBin41temp, my_substr_return2);
    }
    return (char*)disruptedBin41temp;


    /* php 代码
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
    */
}

// 根据$params对混合二进制串进行乱序 ......
char* disruptOrder(char* bin, int params[], char disruptedBin41[]) {

    char binBegin[90] = "";
    if(params[0] != 0) {
        char* my_substr_return;
        strcat(binBegin, my_substr(bin, params[0], strlen(bin)-params[0], my_substr_return));
        strcat(binBegin, my_substr(bin, 0, params[0], my_substr_return));
        bin = (char*)binBegin;
    }

    int strLength = strlen(bin);
    int tH=0, tW = 0, tL = 0;
    params[1]++;
    params[2]++;
    int i;
    for(i=0; i< strLength; i++) {
        if(tH*params[1]*params[2]+tW*params[1]+tL >= strLength) {
            tH = 0;
            tL = tL + 1;
        }
        if(tL >= params[1]) {
            tL = 0;
            tW = tW + 1;
        }
//        disruptedBin41 .= bin{tH*params[1]*params[2]+tW*params[1]+tL};
        char p[2] = {bin[tH*params[1]*params[2]+tW*params[1]+tL]};
        strcat(disruptedBin41, p);
        tH++;
    }
    return (char*)disruptedBin41;


    /*
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

    return $binMix;*/
}


ZEND_FUNCTION(c_makeSignAndMixWithNumber)
{
/*
        makeSignAndMixWithNumber(
            $this->_salt,
            $this->_numbering,
            $this->_bin_numbering,
            $this->_numbering_bin_length,
            $this->_garble_bin_length,
            $this->_interference

        );
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

        return $disruptedBin41;
*/
    char *_salt;
    int _salt_len;
    char *_numbering;
    int _numbering_len;
    char *_bin_numbering;
    int _bin_numbering_len;
    char *_numbering_bin_length;
    int _numbering_bin_length_len;
    char *_garble_bin_length;
    int _garble_bin_length_len;
    char *_interference;
    int _interference_len;
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ssssss",&_salt, &_salt_len, &_numbering, &_numbering_len, &_bin_numbering, &_bin_numbering_len, &_numbering_bin_length, &_numbering_bin_length_len, &_garble_bin_length, &_garble_bin_length_len, &_interference, &_interference_len) == FAILURE) {
        RETURN_NULL();
    }
    // 签名二进制
    char *bin_garble = "";
    bin_garble = getGarbleBin(_salt, _numbering, _garble_bin_length, bin_garble);
    // 混合签名和编号二进制
    char implodeBin41temp[90] = "";
    char *implodeBin41 = "";
    implodeBin41 = (char *)implodeBins(bin_garble, _bin_numbering, implodeBin41temp);
    // 获取乱序的参数
    int interference = atoi(_interference) + pow(getBinSum(implodeBin41), 3);
    int arrDisruptParams[3];
    getDisruptParams(atoi(_garble_bin_length), atoi(_numbering_bin_length), interference, arrDisruptParams);
    // 获取乱序的（混合签名和编号二进制）
    char disruptedBin41temp[90] = "";
    char *disruptedBin41 = "";
    disruptedBin41 = (char *)disruptOrder(implodeBin41, arrDisruptParams, disruptedBin41temp);
    ZVAL_STRING(return_value, disruptedBin41, 1);
    return;
}

ZEND_FUNCTION(c_splitMixedAndVerified)
{
    char *disruptedBin41;
    int disruptedBin41_len;
    char *_salt;
    int _salt_len;
    char *_interference;
    int _interference_len;
    char *_garble_bin_length;
    int _garble_bin_length_len;
    char *_numbering_bin_length;
    int _numbering_bin_length_len;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sssss",&disruptedBin41, &disruptedBin41_len ,&_salt, &_salt_len, &_interference, &_interference_len, &_garble_bin_length, &_garble_bin_length_len, &_numbering_bin_length, &_numbering_bin_length_len) == FAILURE) {
        RETURN_NULL();
    }

    // 获取乱序的参数
    int interference = atoi(_interference) + pow(getBinSum(disruptedBin41), 3);
    int arrDisruptParams[3];
    getDisruptParams(atoi(_garble_bin_length), atoi(_numbering_bin_length), interference, arrDisruptParams);
    // 获取原顺序的（混合签名和编号二进制）
    char implodeBin41[90] = "";
    recoverOrder(disruptedBin41, arrDisruptParams, implodeBin41);
    // 拆分二进制
    char bin_garble_to_verified[90] = "";
    char bin_numbering[90] = "";
    explodeBins(implodeBin41, atoi(_garble_bin_length), bin_garble_to_verified, bin_numbering);
//    php_printf(implodeBin41);
//    php_printf("\t");
//    php_printf(bin_garble_to_verified);
//    php_printf("\t");
//    php_printf(bin_numbering);
//    php_printf("\t");
    // 十进制numbering
    int numbering = bindec(bin_numbering);
    // 获取实际的签名
    char *bin_garble = "";
    char str_numbering[11];
    sprintf(str_numbering, "%d", numbering);

    bin_garble = getGarbleBin(_salt, str_numbering, _garble_bin_length, bin_garble);

    zval *subarray;
    array_init(return_value);
    if(strcmp(bin_garble_to_verified, bin_garble) == 0) {
        add_assoc_bool(return_value, "verify", 1);
    } else {
        add_assoc_bool(return_value, "verify", 0);
    }
    add_assoc_long(return_value, "_numbering", numbering);
    add_assoc_string(return_value, "_bin_numbering", bin_numbering, 1);
    return;


    /*
    // 获取乱序的参数
                $arrDisruptParams = $this->getDisruptParams($this->_garble_bin_length, $this->_numbering_bin_length, $this->_interference+pow($this->getBinSum($disruptedBin41), 3));
                // 获取原顺序的（混合签名和编号二进制）
                $implodeBin41 = $this->recoverOrder($disruptedBin41, $arrDisruptParams);
                list($bin_garble_to_verified, $bin_numbering) = $this->explodeBins($implodeBin41, [$this->_garble_bin_length, 0]);
                $numbering = bindec($bin_numbering);
                $bin_garble = $this->getGarbleBin($this->_salt, $numbering, $this->_garble_bin_length);
    */

}

ZEND_FUNCTION(c_explodeBins)
{
    char *binMix;
    int binMix_len;
    char *_length1;
    int _length1_len;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss",&binMix, &binMix_len ,&_length1, &_length1_len) == FAILURE) {
        RETURN_NULL();
    }

    // 拆分二进制
    char binexplode1[100] = "";
    char binexplode2[100] = "";
    explodeBins(binMix, atoi(_length1), binexplode1, binexplode2);

    zval *subarray;
    array_init(return_value);
    add_index_string(return_value, 0, binexplode1, 1);
    add_index_string(return_value, 1, binexplode2, 1);
    return;
}

ZEND_FUNCTION(c_implodeBins)
{
    char *strBin1;
    int strBin1_len;
    char *strBin2;
    int strBin2_len;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss",&strBin1, &strBin1_len ,&strBin2, &strBin2_len) == FAILURE) {
        RETURN_NULL();
    }

    char binMixtemp[100] = "";
    char *binMix = "";
    binMix = (char *)implodeBins(strBin1, strBin2, binMixtemp);

    ZVAL_STRING(return_value, binMix, 1);
    return;
}

ZEND_FUNCTION(c_getCodeMap)
{
    ZVAL_STRING(return_value, "FXJ8HVD2ZW7CM6KLR4GT5PUE3AQNB9YS", 1);
    return;
}

ZEND_FUNCTION(c_getLengthConfig)
{
    array_init(return_value);

    /** 11 位码为例：
    生成的码为11位32进制的码 = 11*5(32进制) = 55位（二进制）
    分配：
    27  numbering   134,217,727     唯一码生成数量
    14  index       16,383          索引ID最大值(int)
    14  garble      16,383          混淆的字符位数
     ***/
    zval *subarray10;
    MAKE_STD_ZVAL(subarray10);
    array_init(subarray10);
    add_assoc_long(subarray10, "numbering", 27);
    add_assoc_long(subarray10, "index", 14);
    add_assoc_long(subarray10, "garble", 9);
    add_index_zval(return_value, 10, subarray10);

    zval *subarray11;
    MAKE_STD_ZVAL(subarray11);
    array_init(subarray11);
    add_assoc_long(subarray11, "numbering", 27);
    add_assoc_long(subarray11, "index", 14);
    add_assoc_long(subarray11, "garble", 14);
    add_index_zval(return_value, 11, subarray11);

    zval *subarray12;
    MAKE_STD_ZVAL(subarray12);
    array_init(subarray12);
    add_assoc_long(subarray12, "numbering", 27);
    add_assoc_long(subarray12, "index", 14);
    add_assoc_long(subarray12, "garble", 19);
    add_index_zval(return_value, 12, subarray12);

    zval *subarray13;
    MAKE_STD_ZVAL(subarray13);
    array_init(subarray13);
    add_assoc_long(subarray13, "numbering", 27);
    add_assoc_long(subarray13, "index", 14);
    add_assoc_long(subarray13, "garble", 24);
    add_index_zval(return_value, 13, subarray13);

    zval *subarray14;
    MAKE_STD_ZVAL(subarray14);
    array_init(subarray14);
    add_assoc_long(subarray14, "numbering", 27);
    add_assoc_long(subarray14, "index", 19);
    add_assoc_long(subarray14, "garble", 24);
    add_index_zval(return_value, 14, subarray14);

    zval *subarray15;
    MAKE_STD_ZVAL(subarray15);
    array_init(subarray15);
    add_assoc_long(subarray15, "numbering", 27);
    add_assoc_long(subarray15, "index", 19);
    add_assoc_long(subarray15, "garble", 29);
    add_index_zval(return_value, 15, subarray15);

    return;
/*
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
*/
}

static zend_function_entry ufs_functions[] = {
    ZEND_FE(c_makeSignAndMixWithNumber,        NULL)
    ZEND_FE(c_splitMixedAndVerified,        NULL)
    ZEND_FE(c_explodeBins,        NULL)
    ZEND_FE(c_implodeBins,        NULL)
    ZEND_FE(c_getCodeMap,        NULL)
    ZEND_FE(c_getLengthConfig,        NULL)
    { NULL, NULL, NULL }
};


// module entry
zend_module_entry ufs_codes_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
    "ufs_codes", //这个地方是扩展名称，往往我们会在这个地方使用一个宏。
    ufs_functions,
    NULL, /* MINIT */
    NULL, /* MSHUTDOWN */
    NULL, /* RINIT */
    NULL, /* RSHUTDOWN */
    NULL, /* MINFO */
#if ZEND_MODULE_API_NO >= 20010901
	"2.1", //这个地方是我们扩展的版本
#endif
	STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_UFS_CODES
	ZEND_GET_MODULE(ufs_codes)
#endif
