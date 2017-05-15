#!/bin/bash  
  
step=15 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
    #$(php '/data/wwwroot/default/chat/upfd.php')
    $(curl 'http://119.23.58.21/api_2_0_0/goods/getDetaile/refresh/true')
    sleep $step
done

exit 0
