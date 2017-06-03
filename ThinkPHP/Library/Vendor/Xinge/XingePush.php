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

const ANDROID_ACCESS_ID = '2100231415';
const ANDROID_SERERKEY = 'c1909e4753e1b23bd75e9ec0c9b43d63';

const IOS_ACCESS_ID = '2200231416';
const IOS_SECRETKEY = 'dec2efb82a46bfe7c8a83cf1dc3ecd9d';



class XingePush
{

	public function pushToUID($uid,$content='')
	{
		$ret = XingeApp::PushAccountAndroid(ANDROID_ACCESS_ID,ANDROID_SERERKEY,$content,$content,(string)$uid);

		$ret2 =  XingeApp::PushAccountIos(IOS_ACCESS_ID,IOS_SECRETKEY,$content,(string)$uid,XingeApp::IOSENV_PROD);

		return array($ret,$ret2);


	}
}