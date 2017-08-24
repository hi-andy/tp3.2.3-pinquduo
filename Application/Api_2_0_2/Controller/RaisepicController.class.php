<?php
/**
 * Created by PhpStorm.
 * User: mengzhuowei
 * Date: 2017/5/22
 * Time: 下午3:45
 */

namespace Api_2_0_2\Controller;

use OSS\Core\OssException;

use Admin\Logic\OrderLogic;
/*
 * 用于生产为我点赞分享图
 * 写于 17_8_4
 * 作者  吴银海
 * */
class RaisepicController extends BaseController
{
    function raise_pic(){
        $prom_id = I('prom_id');
        $Qr_code = I('Qr_code');
        $accessKeyId = C('OSSKEYID');//去阿里云后台获取秘钥
        $accessKeySecret = C("OSSKEYSECRET");//去阿里云后台获取秘钥
        $endpoint = C('OSSENDPOINT');//你的阿里云OSS地址
        $bucket= C('OSSBUCKET');//oss中的文件上传空间
        if(empty($prom_id)){
            $json = array('status'=>-1,'msg'=>'参数不能为空');
            $this->getJsonp($json);
        }

        $prom_info = M('group_buy')->where('id = '.$prom_id)->field('goods_id,user_id')->find();
        $user_pic = M('users')->where('user_id = '.$prom_info['user_id'])->field('head_pic,nickname,mobile')->find();
        if(empty($prom_info)){
            $json = array('status'=>-1,'msg'=>'该团不存在');
            $this->getJsonp($json);
        }
//http://pqd.oss-cn-shenzhen.aliyuncs.com/Public/upload/raise/goods_19279.jpg
        $bigImgPath =  "http://{$bucket}.{$endpoint}". "/Public/upload/raise/goods_". $prom_info['goods_id'] .'.jpg';
        $img = imagecreatefromstring(curl_file_get_contents($bigImgPath));
        if(empty($img)){
            M('admin_log')->data(array('admin_id'=>1,'log_info'=>'为我助力1','log_ip'=>'123','log_url'=>1))->add();
            $goods_info = M('goods')->where('goods_id = '.$prom_info['goods_id'])->field('goods_name,market_price')->find();
            $goods_image = M('goods_images')->where("goods_id = '{$prom_info['goods_id']}'")->field('image_url')->find();
            $url = get_raise_pic($prom_info['goods_id'],$goods_image['image_url'],$goods_info['goods_name'],$goods_info['market_price']);
            $img = imagecreatefromstring(curl_file_get_contents($url));
        }

        $font = 'Public/images/yahei.ttf';//字体
        $bigImg=  "https://{$bucket}.{$endpoint}/Public/upload/raise-prom/userid_". $prom_info['user_id'] .'_promid_'.$prom_id.'.jpg';
//        $getimgcontent = file_get_contents($bigImg);
        $img_t = imagecreatefromstring(curl_file_get_contents($bigImg));
        if(empty($img_t)) {
            //获取图片文件的内容
            $pic_path = curl_file_get_contents($Qr_code);
            //创建图片资源
            $resource = imagecreatefromstring($pic_path);
            //图片合并
            imagecopyresized($img, $resource, 390, 476, 0, 0, 200, 200, imagesx($resource), imagesy($resource));

            //获取图片文件的内容
            $pic_path = curl_file_get_contents($user_pic['head_pic']);

            //创建图片资源
            $resource = imagecreatefromstring($pic_path);
            //图片合并
            imagecopyresized($img, $resource, 20, 395, 0, 0, 60, 60, imagesx($resource), imagesy($resource));
            //用户头像遮罩
            $head_pic = 'Public/images/square_head@2x.png';
            //获取图片文件的内容
            $pic_path = curl_file_get_contents($head_pic);
            //创建图片资源
            $resource = imagecreatefromstring($pic_path);
            //图片合并
            imagecopyresized($img, $resource, 20, 395, 0, 0, 60, 60, imagesx($resource), imagesy($resource));
            //用户名称
            if (!empty($user_pic['mobile'])) {
                $user_name = substr_replace($user_pic['mobile'], '****', 3, 4);
            } else {
                $user_name = $user_pic['nickname'];
            }
//            var_dump("user_name".$user_name);
            if (strlen($user_name) > 18) {
                $user_name = msubstr($user_name, 0, 6) . '...';
                imagettftext($img, 20, 0, 100, 440, imagecolorallocate($img, 153, 153, 153), $font, $user_name);
            } else {
                imagettftext($img, 20, 0, 100, 440, imagecolorallocate($img, 153, 153, 153), $font, $user_name);
            }

            $path = "Public/upload/raise-prom";
            if (!file_exists($path)) {
                mkdir($path);
            }
            //拉图片传到阿里云
            $path1 = "Public/upload/raise-prom/userid_" . $prom_info['user_id'] . '_promid_' . $prom_id . '.jpg';
            imagejpeg($img, $path1);

//          $path = gethostbyname($_SERVER['SERVER_NAME']).'/'. $path1;

            vendor('aliyun.autoload');

            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $object = "Public/upload/raise-prom/userid_" . $prom_info['user_id'] . '_promid_' . $prom_id . '.jpg';//想要保存文件的名称
            $file = $path1;//文件路径，必须是本地的。

            try{
                $ossClient->uploadFile($bucket,$object,$file);
                $url =  "https://{$bucket}.{$endpoint}/".$object;
                $p = imagecreatefromstring(curl_file_get_contents($url));
                if(!empty($p)){
                    unlink($path1);
                }
//                var_dump($url);
                $json = array('status'=>1,'msg'=>'获取成功','result'=>array('url'=>$url));
                $this->getJsonp($json);
            } catch(OssException $e) {
                print $e->getMessage();
            }
        }else{
            $url = "https://{$bucket}.{$endpoint}/Public/upload/raise-prom/userid_". $prom_info['user_id'] .'_promid_'.$prom_id.'.jpg';
        }
        $json = array('status'=>1,'msg'=>'获取成功','result'=>array('url'=>$url));
        $this->getJsonp($json);
    }


}