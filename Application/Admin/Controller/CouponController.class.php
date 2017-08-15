<?php
namespace Admin\Controller;
use Think\AjaxPage;

class CouponController extends BaseController {
    /**----------------------------------------------*/
     /*                优惠券控制器                  */
    /**----------------------------------------------*/
    /*
     * 优惠券类型列表
     */
    public function index(){
        //获取优惠券列表,添加筛选条件 2017-8-14 李则云
        $where=[];
        if(I("get.name")){
            $where["name"]=I("get.name");
        }
        if(I("get.store_name")){
            if(I("get.store_name")=="平台"){
                $where['tp_coupon.store_id']=0;
            }else{
                $where['tp_merchant.store_name']=I("get.store_name");
            }
        }
        if(I("get.money")){
            $where["tp_coupon.money"]=I("get.money");
        }
        if(I("get.add_time")){
            $tmp=explode("-",I("get.add_time"));
            $starttime=strtotime(trim(str_replace(".","",$tmp[0])));
            $endtime=strtotime(trim(str_replace(".","",$tmp[1])));
            $where["tp_coupon.add_time"]=["EXP","BETWEEN $starttime AND ".($endtime+24*3600-1)];
        }
    	$count =  M('coupon')->where($where)->count();
    	$Page = new \Think\Page($count,10);        
        $show = $Page->show();
        $lists = M('coupon')
	        ->join(array(" LEFT JOIN tp_merchant ON tp_coupon.store_id = tp_merchant.id "))
            ->order('add_time desc')
            ->where($where)
	        ->field('tp_coupon.*,tp_merchant.store_name')
	        ->limit($Page->firstRow.','.$Page->listRows)
	        ->select();
        //格式化优惠券序列号范围 2017-8-14 李则云
        foreach ($lists as $k=>$v){
            $lists[$k]["sn_range"]=$this->parseSn($v);
            $lists[$k]["category"]=$this->parseCategory($v["category1"]);
        }
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出   
//        $this->assign('coupons',C('COUPON_TYPE'));
        $this->display();
    }

    /*
     * 添加编辑一个优惠券类型
     */
    public function coupon_info(){
        if(IS_POST){
        	$data = I('post.');
            $data['send_start_time'] = strtotime($data['send_start_time']);
            $data['send_end_time'] = strtotime($data['send_end_time']);
            $data['use_end_time'] = strtotime($data['use_end_time']);
            $data['use_start_time'] = strtotime($data['use_start_time']);
            if($data['send_start_time'] > $data['send_end_time']){
                $this->error('发放日期填写有误');
            }
//            store_id=0时为全部门店 2017-8-14 李则云
//			if(empty($data['store_id']))
//			{
//				$this->error('请选择商户');
//			}
            //是否有使用条件 2017-8-14 李则云
            if($data["is_limit"]=="0"){
                $data["condition"]=0;
            }else{
                if(empty($data["condition"])){
                    $this->error("满减金额必须大于0");
                }
            }
            if(empty($data['id'])){
            	$data['add_time'] = time();
            	$row = M('coupon')->add($data);
            }else{
            	$row =  M('coupon')->where(array('id'=>$data['id']))->save($data);
            }
            if(!$row)
                $this->error('编辑代金券失败');
            $this->success('编辑代金券成功',U('Admin/Coupon/index'));
            exit;
        }

        $cid = I('get.id');
	    $store = M('merchant')->where('state=1')->field('id,store_name')->select();

        if($cid){
        	$coupon = M('coupon')->where(array('id'=>$cid))->find();
        	$this->assign('coupon',$coupon);
        	$this->assign('category',M("coupon_category")->where(["pid"=>$coupon["category1"]])->select());
        }else{
        	$def['send_start_time'] = strtotime("+1 day");
        	$def['send_end_time'] = strtotime("+1 month");
        	$def['use_start_time'] = strtotime("+1 day");
        	$def['use_end_time'] = strtotime("+2 month");
        	$this->assign('coupon',$def);
        }
	    $this->assign('store',$store);
        $this->display();
    }

    /*
    * 优惠券发放
    */
    public function make_coupon(){
        //获取优惠券ID
        $cid = I('get.id');
        $type = I('get.type');
        //查询是否存在优惠券
        $data = M('coupon')->where(array('id'=>$cid))->find();
        $remain = $data['createnum'] - $data['send_num'];//剩余派发量
    	if($remain<=0) $this->error($data['name'].'已经发放完了');
        if(!$data) $this->error("优惠券类型不存在");
        if($type != 4) $this->error("该优惠券类型不支持发放");
        if(IS_POST){
            $num  = I('post.num');
            if($num>$remain) $this->error($data['name'].'发放量不够了');
            if(!$num > 0) $this->error("发放数量不能小于0");
            $add['cid'] = $cid;
            $add['type'] = $type;
            $add['send_time'] = time();
            for($i=0;$i<$num; $i++){
                do{
                    $code = get_rand_str(8,0,1);//获取随机8位字符串
                    $check_exist = M('coupon_list')->where(array('code'=>$code))->find();
                }while($check_exist);
                $add['code'] = $code;
                M('coupon_list')->add($add);
            }
            M('coupon')->where("id=$cid")->setInc('send_num',$num);
            adminLog("发放".$num.'张'.$data['name']);
            $this->success("发放成功",U('Admin/Coupon/index'));
            exit;
        }
        $this->assign('coupon',$data);
        $this->display();
    }
    
    public function ajax_get_user(){
    	//搜索条件
    	$condition = array();
    	I('mobile') ? $condition['mobile'] = I('mobile') : false;
    	I('email') ? $condition['email'] = I('email') : false;
    	$nickname = I('nickname');
    	if(!empty($nickname)){
    		$condition['nickname'] = array('like',"%$nickname%");
    	}
    	$model = M('users');
    	$count = $model->where($condition)->count();
    	$Page  = new AjaxPage($count,10);
    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$show = $Page->show();
    	$userList = $model->where($condition)->order("user_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $user_level = M('user_level')->getField('level_id,level_name',true);       
        $this->assign('user_level',$user_level);
    	$this->assign('userList',$userList);
    	$this->assign('page',$show);
    	$this->display();
    }
    
    public function send_coupon(){
    	$cid = I('cid');    	
    	if(IS_POST){
    		$level_id = I('level_id');
    		$user_id = I('user_id');
    		$insert = '';
    		$coupon = M('coupon')->where("id=$cid")->find();
    		if($coupon['createnum']>0){
    			$remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
    			if($remain<=0) $this->error($coupon['name'].'已经发放完了');
    		}
    		
    		if(empty($user_id) && $level_id>=0){
    			if($level_id==0){
    				$user = M('users')->where("is_lock=0")->select();
    			}else{
    				$user = M('users')->where("is_lock=0 and level_id=$level_id")->select();
    			}
    			if($user){
    				$able = count($user);//本次发送量
    				if($coupon['createnum']>0 && $remain<$able){
    					$this->error($coupon['name'].'派发量只剩'.$remain.'张');
    				}
    				foreach ($user as $k=>$val){
    					$user_id = $val['user_id'];
    					$time = time();
    					$gap = ($k+1) == $able ? '' : ',';
    					$insert .= "($cid,1,$user_id,$time)$gap";
    				}
    			}
    		}else{
    			$able = count($user_id);//本次发送量
    			if($coupon['createnum']>0 && $remain<$able){
    				$this->error($coupon['name'].'派发量只剩'.$remain.'张');
    			}
    			foreach ($user_id as $k=>$v){
    				$time = time();
    				$gap = ($k+1) == $able ? '' : ',';
    				$insert .= "($cid,1,$v,$time)$gap";
    			}
    		}
			$sql = "insert into __PREFIX__coupon_list (`cid`,`type`,`uid`,`send_time`) VALUES $insert";
			M()->execute($sql);
			M('coupon')->where("id=$cid")->setInc('send_num',$able);
			adminLog("发放".$able.'张'.$coupon['name']);
			$this->success("发放成功");
			exit;
    	}
    	$level = M('user_level')->select();
    	$this->assign('level',$level);
    	$this->assign('cid',$cid);
    	$this->display();
    }
    
    public function send_cancel(){
    	
    }

    /*
     * 删除优惠券类型
     */
    public function del_coupon(){
        //获取优惠券ID
        $cid = I('get.id');
        //查询是否存在优惠券
	    $result = M('coupon_list')->where('`cid`='.$cid.' `is_use`!=0')->count();
	    if($result){
		    $this->error("还有用户持有优惠券");
	    }
        $row = M('coupon')->where(array('id'=>$cid))->delete();
        if($row){
            //删除此类型下的优惠券
            M('coupon_list')->where(array('cid'=>$cid))->delete();
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }


    /*
     * 优惠券详细查看
     */
    public function coupon_list(){
        //获取优惠券ID
        $cid = I('get.id');
        //查询是否存在优惠券
        $check_coupon = M('coupon')->field('id,type')->where(array('id'=>$cid))->find();
        if(!$check_coupon['id'] > 0)
            $this->error('不存在该类型优惠券');
       
        //查询该优惠券的列表的数量
        $sql = "SELECT count(1) as c FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = ".$cid;    //联合用户表去查询用户名        
        
        $count = M()->query($sql);
        $count = $count[0]['c'];
    	$Page = new \Think\Page($count,10);
    	$show = $Page->show();
        
        //查询该优惠券的列表
        $sql = "SELECT l.*,c.name,o.order_sn,u.nickname FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = ".$cid.    //联合用户表去查询用户名
                " limit {$Page->firstRow} , {$Page->listRows}";
        $coupon_list = M()->query($sql);
        $this->assign('coupon_type',C('COUPON_TYPE'));
        $this->assign('type',$check_coupon['type']);       
        $this->assign('lists',$coupon_list);            	
    	$this->assign('page',$show);        
        $this->display();
    }
    
    /*
     * 删除一张优惠券
     */
    public function coupon_list_del(){
        //获取优惠券ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在优惠券
         $row = M('coupon_list')->where(array('id'=>$cid))->delete();
        if(!$row)
            $this->error('删除失败');
        $this->success('删除成功');
    }

    /**
     * 优惠券领取列表
     */
    public function coupon_get(){
        $where=[];
        $where["tp_coupon_list.is_use"]=0;//未使用
        //加入筛选条件
        if(I("get.uid")){
            $where["tp_coupon_list.uid"]=I("get.uid");
        }
        if(I("get.name")){
            $where["tp_coupon.name"]=I("get.name");
        }
        if(I("get.use_end_time")){
            $time=strtotime(str_replace(".","/",I("get.use_end_time")));
            $where["tp_coupon.use_end_time"]=["EXP","BWTWEEN $time AND ".($time+24*3600-1)];
        }
        if(I("get.store_name")){
            if(I("get.store_name")=="平台"){
                $where['tp_coupon.store_id']=0;
            }else{
                $where["tp_merchant.store_name"]=I("get.store_name");
            }
        }
        $count =  M('coupon_list')->join("tp_coupon ON tp_coupon.id=tp_coupon_list.id","left")
            ->join("tp_merchant ON tp_coupon.store_id = tp_merchant.id","LEFT")
            ->where($where)
            ->count();
        $Page = new \Think\Page($count,15);
        $show = $Page->show();
        $lists=M("coupon_list")
            ->join("tp_coupon ON tp_coupon.id=tp_coupon_list.id","left")
            ->join("tp_merchant ON tp_coupon.store_id = tp_merchant.id","LEFT")
            ->where($where)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->field("tp_coupon.name,tp_coupon_list.coupon_sn,tp_coupon.category1,tp_coupon.money,tp_coupon.condition,tp_coupon.use_end_time,tp_merchant.store_name,tp_coupon_list.uid,tp_coupon_list.send_time")
            ->select();
        foreach ($lists as $k=>$v){
            $lists[$k]["category"]=$this->parseCategory($v["category1"]);
        }
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /**
     * 优惠券使用列表
     */
    public function coupon_use(){
        $where=[];
        //加入筛选条件
        if(I("get.uid")){
            $where["tp_coupon_list.uid"]=I("get.uid");
        }
        if(I("get.order_sn")){
            $where["tp_order.order_sn"]=I("get.order_sn");
        }
        if(I("get.use_time")){
            $time=strtotime(str_replace(".","/",I("get.use_time")));
            $where["tp_coupon_list.use_time"]=["EXP","BETWEEN $time AND ".($time+24*3600-1)];
        }
        if(I("get.store_name")){
            if(I("get.store_name")=="平台"){
                $where['tp_coupon.store_id']=0;
            }else{
                $where["tp_merchant.store_name"]=I("get.store_name");
            }
        }
        $count =  M('coupon_list')
            ->join("tp_coupon ON tp_coupon.id=tp_coupon_list.id","left")
            ->join("tp_merchant ON tp_coupon.store_id = tp_merchant.id","LEFT")
            ->join("tp_order ON tp_order.order_id=tp_coupon_list.order_id","LEFT")
            ->where($where)
            ->count();
        $Page = new \Think\Page($count,15);
        $show = $Page->show();
        $lists=M("coupon_list")
            ->join("tp_coupon ON tp_coupon.id=tp_coupon_list.id","left")
            ->join("tp_merchant ON tp_coupon.store_id = tp_merchant.id","LEFT")
            ->join("tp_order ON tp_order.order_id=tp_coupon_list.order_id","LEFT")
            ->where($where)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->field("tp_coupon_list.order_id,tp_coupon.name,tp_coupon_list.coupon_sn,tp_coupon.category1,tp_coupon.money,tp_coupon.condition,tp_order.order_sn,tp_order.total_amount,tp_order.coupon_price,tp_merchant.store_name,tp_coupon_list.uid,tp_coupon_list.use_time,tp_coupon_list.is_use,tp_coupon.use_end_time")
            ->select();
        $goodsmodel=M("order_goods");
        foreach ($lists as $k=>$v){
            $lists[$k]["goods_name"]=$goodsmodel->where(["order_id"=>$v["order_id"]])->getField("goods_name");
            $lists[$k]["category"]=$this->parseCategory($v["category1"]);
            $lists[$k]["use_time_str"]=$this->parseUseTime($v);
        }
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /**
     * 统计对账(优惠券)
     */
    public function statistics(){
        $where=[];
        $where["tp_coupon_list.is_use"]=0;//未使用
        //加入筛选条件
        if(I("get.name")){
            $where["tp_coupon.name"]=I("get.name");
        }
        if(I("get.pickdate")){
            $tmp=explode("-",I("get.pickdate"));
            $starttime=strtotime(trim(str_replace(".","",$tmp[0])));
            $endtime=strtotime(trim(str_replace(".","",$tmp[1])));
            $where["tp_coupon.add_time"]=["EXP","BETWEEN $starttime AND ".($endtime+24*3600-1)];
        }
        if(I("get.store_name")){
            if(I("get.store_name")=="平台"){
                $where['tp_coupon.store_id']=0;
            }else{
                $where["tp_merchant.store_name"]=I("get.store_name");
            }
        }
        $count =  M('coupon_list')->join("tp_coupon ON tp_coupon.id=tp_coupon_list.id","left")
            ->join("tp_merchant ON tp_coupon.store_id = tp_merchant.id","LEFT")
            ->where($where)
            ->count();
        $Page = new \Think\Page($count,15);
        $show = $Page->show();
        $lists=M("coupon_list")
            ->join("tp_coupon ON tp_coupon.id=tp_coupon_list.id","left")
            ->join("tp_merchant ON tp_coupon.store_id = tp_merchant.id","LEFT")
            ->where($where)
            ->limit($Page->firstRow.','.$Page->listRows)
            ->field("tp_merchant.store_name,tp_coupon.name,tp_coupon.use_start_time,tp_coupon.use_end_time,tp_coupon.createnum,tp_coupon.money,tp_coupon.use_num,tp_coupon.id as coupon_id")
            ->select();
        $couponListModel=M("coupon_list");
        $now=time();
        foreach ($lists as $k=>$v){
//            查找未使用的数量coupon_id
            $lists[$k]["nouse_num"]=$couponListModel->where(["is_use"=>0])
                ->where("{$v['use_end_time']}>".$now)
                ->count();
        }
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /**
     * 优惠券分类列表
     */
    public function category_list(){
        $this->assign("res",M("coupon_category")->select());
        $this->display();
    }

    /**
     * 优惠券分类编辑
     */
    public function category_edit(){
        if(IS_POST){
            //修改或新增
            if(I("post.id")){
                $res=M("coupon_category")->data(I("post."))->save();
            }else{
                $res=M("coupon_category")->data(I("post."))->add();
            }
            if($res){
                $this->success("操作成功!");
            }else{
                $this->error("操作失败,请稍后重试");
            }
        }else{
            //展示
            if(I("get.id")){
                $this->assign("info",M("coupon_category")->where(["id"=>I("get.id")])->find());
            }
            $this->display();
        }
    }

    /**
     * 获取二级分类
     */
    public function get_category(){
       if(IS_AJAX){
           $this->ajaxReturn(M("coupon_category")->where(["pid"=>I("get.category1")])->select());
       }
    }

    /**
     * 格式化序列号
     */
    private function parseSn($v){
        $str=strval($v["category1"]);
        $str.=$v["condition"]==0?"1":"2";
        $str.=date("Ymd",$v["add_time"]);
        return [$str.str_pad(1,strlen($v["createnum"]),"0",STR_PAD_LEFT),$str.strval($v["createnum"])];
    }

    /**
     * 格式化分类
     * @param $v
     */
    private function parseCategory($v){
        switch ($v){
            case  1:
                return "平台优惠券";
                break;
            case 2:
                return "店铺优惠券";
                break;
            case 3:
                return "活动优惠券";
                break;
            case 4:
                return "商品优惠券";
                break;
            default:
                return "未定义";
                break;
        }
    }

    /**
     * 格式化使用时间
     */
    private function parseUseTime($v){
        if($v["is_use"]==1)
            return date("Y-m-d",$v["use_time"]);
        if($v["use_end_time"]>=time())
            return "未使用";
        return "已过期";
    }
}