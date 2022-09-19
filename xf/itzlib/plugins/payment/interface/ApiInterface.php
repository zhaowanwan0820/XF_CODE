<?php
//api方式的通道需要实现此接口
/**
 * 此类返回一个数组
 * $arr=array('code'=>'','msg'=>'');
 * code=1代表成功,0代表失败
 * msg=返回的消息
 */
interface ApiInterface{
    public function bindAndRecharge($data,$safeCard,$userInfo);//绑卡并充值
    public function recharge($data,$safeCard,$userInfo);//充值
    
    public function bindAndRechargeVerify($data,$safeCard,$userInfo);//绑卡并充值确认
    public function rechargeVerify($data,$safeCard,$userInfo);//充值确认
    
    public function bindAndRechargeSms($data,$safeCard,$userInfo);//绑卡并充值_重新发送验证码
    public function rechargeSms($data,$safeCard,$userInfo);//充值_重新发送验证码
}

?>