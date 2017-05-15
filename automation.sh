#!/bin/bash  
  
step=10 #间隔的秒数，不能大于60

for (( i = 0; i < 60; i=(i+step) )); do
    $(curl 'http://119.23.58.21/api_2_0_0/user/automation/action/restart/sig/48b0e4a4e2c0c41ef904382eb462cbb8')
    sleep $step
done

exit 0
