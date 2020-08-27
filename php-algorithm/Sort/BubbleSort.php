<?php

/**
 * 冒泡排序
 *
 * -------------------------------------------------------------
 * 思路分析：就是像冒泡一样，每次从数组当中 冒一个最大的数出来。 
 * -------------------------------------------------------------
 * 你可以这样理解：（从小到大排序）存在10个不同大小的气泡，
 * 由底至上的把较少的气泡逐步地向上升，这样经过遍历一次后最小的气泡就会被上升到顶（下标为0）
 * 然后再从底至上地这样升，循环直至十个气泡大小有序。
 * 在冒泡排序中，最重要的思想是两两比较，将两者较少的升上去
 *
 */

function BubbleSort(array $container)
{
    $count = count($container);
    for ($i = 1; $i < $count; $i++) {
        $sortedFlag = true;
        for ($j = 0; $j < $count - $i; $j++) {
            if ($container[$j] > $container[$j + 1]) {
                $temp = $container[$j];
                $container[$j] = $container[$j + 1];
                $container[$j + 1] = $temp;
                $sortedFlag = false;
            }
        }
        if ($sortedFlag === true) { //后面的数据已成顺序结构, 无需再遍历 (可以减少顺序化后的遍历次数)
            break;
        }
    }
    return $container;
}

$container = [3, 2, 1, 5, 6, 7, 9];
var_dump(BubbleSort($container));

/**
array(7) {
[0] =>
int(1)
[1] =>
int(2)
[2] =>
int(3)
[3] =>
int(5)
[4] =>
int(6)
[5] =>
int(7)
[6] =>
int(9)
}
 */