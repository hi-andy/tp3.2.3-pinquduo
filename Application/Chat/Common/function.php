<?php
//将加密串前5个字符和最后5个字符调转位置
function transposition($str){
    $head = substr($str, 0, 5);
    $tail = substr($str, -5);
    $str = substr($str,5);
    $str = substr($str, 0, -5);
    $result = $tail.$str.$head;
    return $result;
}