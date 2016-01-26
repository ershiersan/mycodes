<?php
/**
 * Created by PhpStorm.
 * User: Root
 * Date: 2016/1/25
 * Time: 14:50
 * 排序
*/
// 快速排序1
function fast($arrSort) {
    if(is_array($arrSort) && count($arrSort) > 0) {
        if(count($arrSort) == 1) {
            return $arrSort;
        } else {
            $arrMiddle = [];
            $arrLesser = [];
            $arrHigher = [];
            $arrMiddle[] = $arrSort[0];
            for($i=1; $i<count($arrSort); $i++) {
                if($arrSort[$i] > $arrMiddle[0]) {
                    $arrHigher[] = $arrSort[$i];
                } else {
                    $arrLesser[] = $arrSort[$i];
                }
            }
            return array_merge(fast($arrLesser), $arrMiddle, fast($arrHigher));
        }
    }
    return [];
}

// 快速排序2
function fast2(&$arrSort, $from=0, $to=0) {
    if($from==0 && $to==0) {
        $from = 0;
        $to = count($arrSort) - 1;
    }
    $middle = $from;
    $isLeft = true;
    for ($i=$from,$j=$to; $i<=$j; ) {
        if($isLeft) {
            if($arrSort[$middle] > $arrSort[$j]) {
                $temp = $arrSort[$middle];
                $arrSort[$middle] = $arrSort[$j];
                $arrSort[$j] = $temp;
                $middle = $j;
                $i++;
                $isLeft = false;
            } else {
                $j--;
            }
        } else {
            if($arrSort[$middle] < $arrSort[$i]) {
                $temp = $arrSort[$middle];
                $arrSort[$middle] = $arrSort[$i];
                $arrSort[$i] = $temp;
                $middle = $i;
                $j--;
                $isLeft = true;
            } else {
                $i++;
            }
        }
    }
    if($middle-1 > $from) {
        fast2($arrSort, $from, $middle-1);
    }
    if($middle+1 < $to) {
        fast2($arrSort, $middle+1, $to);
    }
}

// 归并排序
function guibing($arrSort) {
    if(is_array($arrSort) && count($arrSort) > 1) {
        $middle = count($arrSort) / 2;
        $arr1 = [];
        $arr2 = [];
        for($i=0; $i<count($arrSort); $i++) {
            if($i < $middle) {
                $arr1[] = $arrSort[$i];
            } else {
                $arr2[] = $arrSort[$i];
            }
        }
        $arrSorted1 = guibing($arr1);
        $arrSorted2 = guibing($arr2);
        $arrMerged = [];
        for($i=0, $j=0; $i<count($arrSorted1) || $j<count($arrSorted2);) {
            if($i >= count($arrSorted1) || (array_key_exists($j, $arrSorted2) && $arrSorted2[$j] < $arrSorted1[$i])) {
                $arrMerged[] = $arrSorted2[$j++];
            } else if ($j >= count($arrSorted2) || $arrSorted2[$j] >= $arrSorted1[$i]) {
                $arrMerged[] = $arrSorted1[$i++];
            }
        }
        return $arrMerged;
    }
    return $arrSort;
}

// 计数排序
function countingSort($arrSort) {
    $arrCounting = [];
    foreach($arrSort as $sortValue) {
        if(!array_key_exists($sortValue, $arrCounting)) {
            $arrCounting[$sortValue] = 0;
        }
        $arrCounting[$sortValue] ++;
    }
    $arrSorted = [];
    ksort($arrCounting);
    foreach($arrCounting as $keyCounting => $valueCounting) {
        for($i=0; $i<$valueCounting; $i++) {
            $arrSorted[] = $keyCounting;
        }
    }
    return $arrSorted;
}


$arrExp = [45, 8, 61, 65, 4, 84, 6, 51, 63, 48, 4, 65, 41, 65, 9, 85, 7, 89, 79, 3];
echo implode(', ', $arrExp)."\n";
echo implode(', ', fast($arrExp))."\n";
echo implode(', ', guibing($arrExp))."\n";
echo implode(', ', countingSort($arrExp))."\n";
fast2($arrExp);
echo implode(', ', $arrExp)."\n";

