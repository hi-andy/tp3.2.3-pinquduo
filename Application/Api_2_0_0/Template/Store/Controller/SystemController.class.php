<?php

namespace Store\Controller;
class SystemController extends BaseController{
	
	/*
	 * 配置入口
	 */

        
        /**
         * 清空系统缓存
         */
        public function cleanCache(){              
             //$img_arr = glob('./Public/upload/goods/thumb/*'); //$aa = scandir('./Public/upload/goods/thumb/');
             //foreach($img_arr as $key => $val)
             //   unlink ($val);// 删除缩略图
             delFile('./Public/upload/goods/thumb');// 删除缩略图
             if(delFile(RUNTIME_PATH))// 删除缓存 删除 \Application\Runtime 下面的所有文件
                $this->success("清除成功!!!");
             else
                $this->error("操作完成!!");
        }
	
}