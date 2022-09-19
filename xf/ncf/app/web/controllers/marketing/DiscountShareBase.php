<?php
/**
 * DiscountShareBase.php
 *
 * @date 2016-07-11
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace web\controllers\marketing;

use web\controllers\BaseAction;
use core\service\DiscountShareService;
use core\service\UserService;
use core\service\CouponService;
use core\service\UserTagService;
use libs\utils\Logger;
use libs\weixin\Weixin;
// use core\service\BonusService;
use libs\utils\Aes;


class DiscountShareBase extends BaseAction {
    /**
     * 活动状态
     */
    protected  $status;
    /**
     * 活动详情
     */
    public  $discountShareInfo;

    protected  $ajax = 0;

   //加密ID
    protected  $ec_id ;

    protected  $jump_url = '';

    protected  $error_code = 0;

    protected  $userInfo = array();

    protected  $mobile;
    // 微信公众号唯一标识
    protected  $weixin_appId = 0;
    // 微信公众号 加密串
    protected  $weixin_secret = '';

    protected $weixin_host = '';

    protected $cn = '';

    protected $sharinfo_private_key = '879257a19bb211a153.79780751';


    // 是否投資兩次或者兩次以上
    protected $isBidMore = false;

    static public $error_msg  = array(
            0=>'操作成功',
            1=>'活动错误',
            2=>'手机号或验证码不正确',
            3=>'验证码不正确',
            4=>'本活动仅适用未注册用户',
            5=>'本活动仅适用注册用户',
            6=>'领券失败',
            7=>'邀请码无效',
            8=>'微信授权跳转',
            9 => '领取错误',
            10 => '表单令牌错误请刷新页面重试',
            11 => '获取微信信息失败',
            12 => '没有绑定手机号',
            13 => '更新失败',
            14 => '手机号码格式不正确',
            15 => '手机号码不能为空',
            16 => '请用微信浏览器打开',
    );


    public function init() {

        $ec_id = $_REQUEST['id'];
        $id = Aes::decryptForDeal($ec_id);
        $this->ec_id = $ec_id;
        if (!empty($_REQUEST['cn'])){
            $this->cn = addslashes($_REQUEST['cn']);
        }else{
            $this->cn = '';
        }
        $discountShareService = new DiscountShareService();
        $data = $discountShareService->checkDisCountShare($id);
        $this->discountShareInfo = $data['disCountShareInfo'];
        $this->status = $data['status'];
        $this->weixin_appId = app_conf('WEIXIN_APPID');
        $this->weixin_secret = app_conf('WEIXIN_SECRET');
        $this->weixin_host = app_conf('ACTIVITY_WEIXIN_HOST');
        $this->setUserInfo();
        $this->assginAll();
    }

    /**
     * 参数验证
     * @return boolean
     */
    protected function autoCheck(){
        if($this->status != DiscountShareService::STATUS_START){
            $this->error_code = 1;
            return false;
        }

        if(!$this->checkCn()){
            $this->error_code = 7;
            return false;
        }

        return true;
    }

    protected function assginAll(){
        if(!empty($this->discountShareInfo)){
            foreach($this->discountShareInfo as $key => $val){
                $this->tpl->assign($key, $val);
            }
        }
        // 微信分享綁定的域名
        $this->tpl->assign('weixinHost', $this->weixin_host);
        $this->tpl->assign('ec_id', $this->ec_id);
    }

    /**
     * 错误输出页面
     */
    protected function error(){
        Logger::info(implode(" | ",array(__CLASS__, __FUNCTION__,'code:'.$this->error_code,'msg:'.self::$error_msg[$this->error_code],'eventId:'.$this->id,'event_status:'.DiscountShareService::$STATUS_NAMES[$this->status],'data:'.json_encode($this->userInfo))));
        if($this->ajax){
            header("Content-type: application/json; charset=utf-8");
            if(!empty($this->jump_url)){
                $this->jump_url .= '&cn='.$this->cn.'&from_platform=' . $this->fromPlatform;
            }
            echo json_encode(array('code'=>$this->error_code,'msg'=>self::$error_msg[$this->error_code],'jumpUrl'=>$this->jump_url));
        }else{
            if($this->error_code == 7){
                $this->template = "web/views/marketing/error.html";
            }else{
                $this->setDownload();
                $this->template = "web/views/marketing/discount_no.html";
            }
        }
    }

    /**
     * 成功输出页面
     * @param array $data 其他变量 一维
     */
    protected function success($data = array()){
        if($this->ajax){
            header("Content-type: application/json; charset=utf-8");
            if(!empty($this->jump_url)){
                $this->jump_url .= '&cn='.$this->cn.'&from_platform=' . $this->fromPlatform;
            }
            $ret = array('code'=>0,'msg'=>self::$error_msg[0],'jumpUrl'=>$this->jump_url);
            if (!empty($data)){
                $ret = array_merge($ret,$data);
            }
            echo json_encode($ret);
        }
    }

    /**
     * 获取活动信息
     * @param string $key
     * @return boolean
     */
    public function __get($key){
         if(isset($this->discountShareInfo[$key])){
             return $this->discountShareInfo[$key];
         }
         return false;
    }


    /**
     * 获取最近领取记录
     */
    protected function getDisCountList(){
        //获取最近领取列表
        $discountShareService = new DiscountShareService();
        $discountList = $discountShareService->getDiscountListById($this->id);
        $this->tpl->assign('discountList',$discountList);
    }

    /**
     * 获取用户最近领取记录
     */
    protected function getDisCountListByMobile(){
        //获取最近领取列表
        $discountShareService = new DiscountShareService();
        $userDiscountList = $discountShareService->getDiscountListByIdAndMobile($this->id,$this->userInfo['mobile']);
        $this->tpl->assign('userDiscountList',$userDiscountList);
    }

    /**
     * 设置客户端下载链接
     */
    protected function setDownload(){
        // 根据各分站配置读取对应的h5下载链接
        $site_id = $this->tplSiteId;
        if ($site_id != 1 && get_config_db('APP_DOWNLOAD_H5_URL', $site_id)) { //分站
            $downloadUrl = get_config_db('APP_DOWNLOAD_H5_URL', $site_id);
            $downloadDesc = '下载客户端';
        } else if ($this->userInfo['user_id']) { // 主站 & 老用户
            $downloadUrl = 'http://m.firstp2p.com/?from_platform=' . $this->fromPlatform;
            $downloadDesc = '前往网信';
        } else { //主站 & 新用户
            $downloadUrl = '//www.firstp2p.com/user/register?type=h5&cn=' . $this->cn . '&mobile='.$this->mobile. '&from_platform=' . $this->fromPlatform;
            $downloadDesc = '注册网信';
        }
        $this->tpl->assign('downloadUrl', $downloadUrl);
        $this->tpl->assign('downloadDesc', $downloadDesc);
    }

    /**
     * 设置用户信息
     * @return boolean
     */
    protected function setUserInfo(){
        if(empty($this->mobile)){
            return false;
        }

        $this->userInfo['mobile'] = addslashes($this->mobile);

        //获取用户id
        $userService = new UserService();
        $userId = $userService->getUserIdByMobile($this->userInfo['mobile']);
        if(!empty($userId)){
            $this->userInfo['user_id'] = $userId;
        }

        $this->userInfo['isOldUser'] = false;
        if(!empty($this->userInfo['user_id'])){
            $tag_service = new UserTagService();
            $this->userInfo['isOldUser'] = $tag_service->getTagByConstNameUserId('BID_MORE', $this->userInfo['user_id']);
        }

        if($this->coupon == ''){
            $this->userInfo['coupon'] = '';
        }elseif($this->coupon == CouponService::SHORT_ALIAS_DEFAULT){
            if(!empty($this->userInfo['user_id'])){
                $couponService = new CouponService();
                $shortAliasInfo = $couponService->getOneUserCoupon($this->userInfo['user_id']);
                $this->userInfo['coupon'] = $shortAliasInfo['short_alias'];
            }
        }else{
            $this->userInfo['coupon'] = $this->coupon;
        }

        $this->tpl->assign('userInfo', $this->userInfo);
    }


    protected function setInviteButton(){

        $invaiteUrl = '';
        $this->tpl->assign('canNotInvaite',false);
        if($this->coupon == CouponService::SHORT_ALIAS_DEFAULT){//使用用户邀请码
            if(!$this->userInfo['user_id']){
                $invaiteUrl = '/marketing/DiscountShareInfo?id='.$this->ec_id.'&cn='.$this->cn;
                $invaiteDesc = '帮TA邀请';
            }elseif($this->userInfo['isOldUser']){
                $invaiteUrl = '/marketing/DiscountShareInfo?id='.$this->ec_id.'&cn='.$this->userInfo['coupon'];
                $invaiteDesc = '邀请好友';
            }else{
                $this->tpl->assign('canNotInvaite',true);
                // 非法微信分享
                $invaiteUrl = '/marketing/DiscountShareInfo?id='.$this->ec_id;
                $invaiteDesc = '邀请好友';
            }
        }elseif($this->coupon == ''){//不使用邀请码
            $invaiteUrl = '/marketing/DiscountShareInfo?id='.$this->ec_id;
            $invaiteDesc = '分享给好友';
        }else{//使用固定邀请码
            $invaiteUrl = '/marketing/DiscountShareInfo?id='.$this->ec_id.'&cn='.$this->coupon;
            $invaiteDesc = '分享给好友';
        }

        $this->weiXinShare();

        $invaiteUrl = $this->weixin_host . $invaiteUrl;
        // 如果只有host 后面紧跟这些参数，首页会报错
        $invaiteUrl .= '&from_platform=' . $this->fromPlatform;

        $this->tpl->assign('invaiteDesc', $invaiteDesc);
        $this->tpl->assign('shareLink', $invaiteUrl);
    }

    /**
     * 验证邀请码有效性
     * @return boolean
     */
    protected function checkCn(){
        if(!empty($this->cn)){
            $couponService = new CouponService();
            $coupon = $couponService->checkCoupon($this->cn);
            if(empty($coupon)){
                return false;
            }

            //使用用户邀请码的情况判断邀请码对应的人邀请是否具有邀请资格
            if($this->coupon == CouponService::SHORT_ALIAS_DEFAULT){
                $refer_user_id = $coupon['refer_user_id'];
                $tag_service = new UserTagService();
                $isOldUser = $tag_service->getTagByConstNameUserId('BID_MORE', $refer_user_id);
                if(empty($isOldUser)){
                    return false;
                }
            }
        }

        //不使用邀请码，则把传过来的码置空
        if($this->coupon == ''){
            $this->cn = '';
        }elseif($this->coupon != CouponService::SHORT_ALIAS_DEFAULT){//使用固定邀请码，则使用活动邀请码
            $this->cn = $this->coupon;
        }

        $this->tpl->assign('cn', $this->cn);
        return true;
    }

    /**
     * 是否投資或者兩次以上
     * @param int $user_id
     * @return bool
     */
    protected function checkBidMore(){
        if (empty($this->userInfo['user_id'])){
            return false;
        }
        $tag_service = new UserTagService();
        $this->isBidMore = $tag_service->getTagByConstNameUserId('BID_MORE', $this->userInfo['user_id']);
    }


    /**
     * wx js api  Signature （等到做授权的时候这块可能会去掉一篇调用统一）
     * @param string appid
     * @param string secret
     * @return value
     */
    protected   function getJsApiSignature($options) {
        $weObj = new Weixin($options);
        $url = $this->weixin_host . $_SERVER['REQUEST_URI'];
        $nonceStr = md5(time());
        $timeStamp = time();
        $signature = $weObj->getJsSign($url, $timeStamp, $nonceStr);

        $this->tpl->assign('appid', $options['appid']);
        $this->tpl->assign('timeStamp', $timeStamp);
        $this->tpl->assign('nonceStr', $nonceStr);
        $this->tpl->assign('signature', $signature);
    }

    /**
     * 微信分享
     */
    protected function weiXinShare(){
        $source = $this->getUserAgent();
        if ($source['from'] == 'weixin' && $this->weixin_appId && $this->weixin_secret){
            $options = array(
                    'appid' => $this->weixin_appId,
                    'appsecret' => $this->weixin_secret,
            );
            $this->getJsApiSignature($options);
        }else{
            $this->tpl->assign('appid','');
            $this->tpl->assign('timeStamp', '');
            $this->tpl->assign('nonceStr', '');
            $this->tpl->assign('signature', '');
        }
    }

    /**
     * 加密用红包的加密方法
     * @param string $value
     * @return string
     */
    static public function encode($value){
        return false;
        // $bonusService = new BonusService();
        // return $bonusService->encrypt($value ,'E');
    }

    /**
     * 解密用红包的加密方法
     * @param string $value
     * @return string
     */
    static public function decode($value){
        return false;
        // $bonusService = new BonusService();
        // return $bonusService->encrypt($value ,'D');
    }

}
