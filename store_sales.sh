#!/bin/bash  
  
step=1 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
    $(curl 'http://api.hn.pinquduo.cn/api_2_0_0/store/store_sales')
    sleep $step
done

exit 0
