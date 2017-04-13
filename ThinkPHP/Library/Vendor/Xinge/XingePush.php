<?php
/**
 * User: SBMaoPi
 */

namespace Api\Controller;

use Api\Controller\XingeApp;


const PUSH_TYPE_EVENT = 1;
const PUSH_TYPE_PROJECT = 2;
const PUSH_TYPE_MERCHANT = 3;
const  PUSH_TYPE_SYSTEM = 4;

const ANDROID_ACCESS_ID = '2200208163';
const ANDROID_SERERKEY = 'abee777d8c19c1a9e88e8cf8e0094638';

const IOS_ACCESS_ID = '2200208163';
const IOS_SECRETKEY = 'abee777d8c19c1a9e88e8cf8e0094638';



class XingePush
{

	public function pushToUID($uid,$content='')
	{
		$ret = XingeApp::PushAccountAndroid(ANDROID_ACCESS_ID,ANDROID_SERERKEY,$content,$content,(string)$uid);

		$ret2 =  XingeApp::PushAccountIos(IOS_ACCESS_ID,IOS_SECRETKEY,$content,(string)$uid,XingeApp::IOSENV_PROD);

		return array($ret,$ret2);


	}
}