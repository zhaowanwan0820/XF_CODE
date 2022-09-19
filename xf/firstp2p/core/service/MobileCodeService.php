<?php
/**
 *  获取验证码
 *
 * @author xiaoan <zhaoxiaoan@ucfgroup.com>
 */


namespace core\service;
use core\dao\MobileVcodeModel;
use libs\utils\Block;
use core\dao\UserModel;
use core\dao\EnterpriseContactModel;
use core\data\MobileCodeData;
use libs\utils\Logger;
use libs\sms\SmsServer;
use core\service\UserBindService;
/**
 *
 * @package core\service
 */
class MobileCodeService extends BaseService {
    const SUCESS = 1; // 发送成功
    const ERROR_CODE_PARAM = -1; // 参数错误
    const ERROR_CODE_DAY_LIMIT = -2; // 超过当天发送次限制
    const ERROR_CODE_SECOND_LIMIT = -3; // 超过当天分钟发送次限制
    const ERROR_CODE_SEND_FAILD = -4; // 发送失败
    const ERROR_CODE_PHONE_REPEAT = -5; // 手机号重复
    const ERROR_CODE_PHONE_NOT_EXIST = -6; // 手机号不存在
    const ERROR_CODE_PHONE_REPEAT_RESETPWD = -33; //手机号重复，引导修改密码

    const REGISTER = 1; // 注册
    const FORGETPASSWORD = 2;// 找回密码
    const WEBRELOGIN = 9; // web登录防套利
    const PC_MOBILE_CODE_EXPIRATION_TIME = 180; // pc端验证码过期时间
    const PHONE_MOBILE_CODE_EXPIRATION_TIME = 180; // 客户端验证码过期时间
    const ERROR_CODE_IP_FORBID = -8; //ip频度限制
    const OPEN_BE_DEV = 11; //成为网信开放平台开发者
    const MODIFY_PASSWORD_CODE = 12;//修改密码发送验证码
    const OPEN_BIND_WEIXIN = 13; //开放平台绑定微信
    const COMMON_CODE_WITHOUT_CAPTCHA = 14; //免图形验证码验证短信
    const RESET_BANK = 15; //解绑银行卡
    const SMS_LOGIN= 16; //手机验证码登录
    const ENTERPRISE_REGISTER = 17; // 企业用户注册
    const GOLD_DELIVER_CODE = 18;//提金发送验证码
    const BONUS_GROUP_CODE = 19;//红包组短信验证码
    const CANDY_WITHDRAW = 21;//buc提币发送验证码
    const SMS_ENTERPRISE_TRANSFER_VERIFY = 22; // 企业用户转账短信验证码

    const ENTERPRISE_FORGETPWD = 20;
    const UNKOWN = -7; // 未知
    /**
     * 新验证码入mysql库
     */
    public function addInfo($phone,$code)
    {
        if (empty($phone) || empty($code)){
            return false;
        }
        $model = new MobileVcodeModel();
        $model->mobile_phone = $phone;
        $model->mobile_vcode = $code;
        $model->create_time = get_gmtime();

        return $model->insert();
    }

    /**
     * 验证码存储redis中
     * @param int $phone
     * @param int $code
     * @param int $isPc 默认1 是pc端,0是手机端
     * @return bool true | false
     */
    public function newAddInfo($phone,$code,$isPc=1){
        if (!is_numeric($phone) || $phone < 0 || empty($code) || !in_array($isPc,array(1,0))){
            return false;
        }
        $mobileCodeData = new MobileCodeData();
        $expirationTime = self::PC_MOBILE_CODE_EXPIRATION_TIME; // 过期时间
        if ($isPc == 0){
            $expirationTime = self::PHONE_MOBILE_CODE_EXPIRATION_TIME;
        }
        return $mobileCodeData->setMobileCode($phone, $code,$expirationTime,$isPc);
    }
    /**
     * 获取验证码 180内秒
     * @param int $phone
     * @param int $seconds 如果是从redis读取，这个参数已无用
     */
   public function getMobilePhoneTimeVcode($phone,$seconds=180,$isPc=1){
       if (empty($phone) || !in_array($isPc,array(1,0))){
           return false;
       }

       // 从redis中读取
       $mobileCodeData = new MobileCodeData();
       return $mobileCodeData->getMobileCode($phone,$isPc);
   }

   /**
    * 检查手机号发送频率
    * @param int $phone
    * @param int $isPc 默认1 是pc端,0是移动端
    */
   public function frequencyCheck($phone,$isPc = 1, $checkIp = true){
       // 测试环境如非必须测试手机号发送频率,保留下面的if逻辑
       if (app_conf('ENV_FLAG') == 'test') {
           return self::SUCESS;
       }

       if (!is_numeric($phone) || empty($phone)){
           return self::ERROR_CODE_PARAM;
       }

        // 检查当天次数
       if (Block::check('REGISTER_USER_CODE_TODAY', $phone,true) === false){

           return self::ERROR_CODE_DAY_LIMIT;
       }
       // pc端
       if ($isPc == 1){
           // 检查当天分钟次数
           if (Block::check('REGISTER_USER_CODE_SECOND', $phone,true) === false){
               return self::ERROR_CODE_SECOND_LIMIT;
           }
       }
       //  客户端
       if ($isPc == 0){
           // 检查当天分钟次数
           if (Block::check('CLIENT_REGISTER_USER_CODE_SECOND', $phone,true) === false){
               return self::ERROR_CODE_SECOND_LIMIT;
           }
       }

       if ($checkIp) {
           $clientIp = get_client_ip();
           if (Block::check('SEND_SMS_IP_TODAY', $clientIp, true) === false){
               return self::ERROR_CODE_IP_FORBID;
           }
           if (Block::check('SEND_SMS_IP_MINUTE', $clientIp, true) === false){
               return self::ERROR_CODE_IP_FORBID;
           }
       }

       return self::SUCESS;
   }
   /**
    * 检查条件
    * @param int $phone
    * @param int $type
    * @param int $isPc  默认1是pc端，0是移动端
    */
   public function isSend($phone, $type, $isPc = 1, $checkIp = true, $isEnterprise = false){
       if (!is_numeric($phone) || empty($phone)){
           return self::ERROR_CODE_PARAM;
       }
       $frequencyCheck = $this->frequencyCheck($phone,$isPc, $checkIp);
       if ($frequencyCheck != self::SUCESS){
           return $frequencyCheck;
       }
       switch($type){
            case self::REGISTER:
                if (!$isEnterprise) {
                    // 用户是否存在
                    $ismobile = UserModel::instance()->isUserExistsByMobile($phone);
                    if ($ismobile){
                        //判断是否需要引导修改密码
                        $oUserBindService = new UserBindService();
                        $bIs = $oUserBindService->isUserCanResetPwdByMobile($phone);
                        if($bIs){
                            return self::ERROR_CODE_PHONE_REPEAT_RESETPWD;
                        }else{
                            return self::ERROR_CODE_PHONE_REPEAT;
                        }
                    }
                }
            break;
            case self::FORGETPASSWORD:
                $ismobile = UserModel::instance()->isUserExistsByMobile($phone);
                if ($ismobile==false){
                    //return self::ERROR_CODE_PHONE_NOT_EXIST;
                    //手机号不存在改为返回成功，以免被暴扫手机号
                    return self::SUCESS;
                }
            break;
            case self::WEBRELOGIN:
            case self::OPEN_BE_DEV:
            case self::OPEN_BIND_WEIXIN:
            case self::COMMON_CODE_WITHOUT_CAPTCHA:
            case self::RESET_BANK:
            case self::MODIFY_PASSWORD_CODE:
            case self::SMS_LOGIN:
            case self::ENTERPRISE_REGISTER:
            case self::GOLD_DELIVER_CODE:
            case self::BONUS_GROUP_CODE:
            case self::ENTERPRISE_FORGETPWD:
            case self::CANDY_WITHDRAW:
            case self::SMS_ENTERPRISE_TRANSFER_VERIFY:
            break;
            default:
                return self::UNKOWN;
            break;
       }
       return self::SUCESS;
   }

   /**
    * 获取错误信息
    * @param unknown $errorCode
    */
   public function getError($errorCode){
       if (empty($errorCode)){
           return array('code' => self::ERROR_CODE_PARAM,'message' => '参数错误');
       }
       $ret = array('code' => $errorCode,'message' => '未知');
       switch($errorCode){
        case self::ERROR_CODE_PARAM:
          $ret['message'] = '参数错误';
        break;
        case self::ERROR_CODE_DAY_LIMIT:
            $ret['message'] = '请不要频繁发送验证码';
        break;
        case self::ERROR_CODE_SECOND_LIMIT:
            $ret['message'] = '请不要频繁发送验证码';
        break;
        case self::ERROR_CODE_PHONE_REPEAT:
            $ret['message'] = '该手机号已经注册，如有疑问请联系客服';
            break;
        case self::ERROR_CODE_PHONE_NOT_EXIST:
            $ret['message'] = '手机号不存在';
        break;
        case self::ERROR_CODE_PHONE_REPEAT_RESETPWD:
            $ret['message'] = '该手机号已经注册，请修改密码';
            break;
        case self::ERROR_CODE_IP_FORBID:
            $ret['message'] = '请不要频繁发送验证码';
        break;
        default:
            $ret['message'] = '未知';
        break;
       }

       return $ret;
   }

   /**
    * 生成字符串验证码
    * @param int $num
    * @return int
    */
   public static function getVerifyCode($num=6){
       if (!is_numeric($num) || $num < 0 || $num > 15){
           return '';
       }
       $code = '';
       for($i=1;$i<=$num;$i++){
           $rand_string = rand(10000,99999);
           $rand_string_two = str_shuffle($rand_string);
           $rand_string_three = rand(0,strlen($rand_string)-1);
           $code .= $rand_string_two{$rand_string_three};
       }

       return $code;
   }

   /**
    * 发送验证码，是否直接输出json
    */
   public $isReturnJsonSendCode = false;

   /**
    * 发送验证码 验证码放入缓存中 //TODO 考虑是否加入队列
    * @param int $mobile
    * @param int $isPc 默认是1,0是客户端
    * @param int $isrsms 默认是false  true的话走短信猫
    * @param int $sms_teplate_type 1注册验证，2忘记密码，3修改原手机号，4修改新手机号,5设置收获地址 ，6，修改收获地址 7,设置密保问题 8, 修改密保问题9,web防套利身份验证12,修改密码发送验证码16,短信验证码登录
    * @param string $country_code $sms_template_type 等于1即注册时，其他情况从表中读取
    * @return
    */
   public function sendVerifyCode($mobile,$isPc=1,$isrsms=false,$sms_teplate_type=0, $country_code='cn', $idno=null){
       $reg = "/^1[3456789]\d{9}$/";
       if (!empty($country_code) && isset($GLOBALS['dict']['MOBILE_CODE'][$country_code]) && $GLOBALS['dict']['MOBILE_CODE'][$country_code]['is_show']){
           $reg = "/{$GLOBALS['dict']['MOBILE_CODE'][$country_code]['regex']}/";
       }

       if (!preg_match($reg, $mobile)) {
           $ret = json_encode(array('code' => -4,'message'=>'手机错误'));
           setLog(array('errno' => -4, 'errmsg' => '手机错误'));
           // 直接返回json
           if ($this->isReturnJsonSendCode) {
               return $ret;
           }
           if ($isPc == 1){
               echo $ret;
               return;
           }
           if ($isPc == 0){
               return $ret;
           }
       }

       if (!in_array($sms_teplate_type,array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,200))){
            $ret = json_encode(array('code' => -4,'message' => '参数错误'));
            setLog(array('errno' => -4, 'errmsg' => '参数错误'));
            // 直接返回json
            if ($this->isReturnJsonSendCode) {
                return $ret;
            }
            if ($isPc == 1){
                echo $ret;
                return;
            }
            if ($isPc == 0){
                return $ret;
            }
       }

       switch($sms_teplate_type){
            case self::REGISTER:
            case self::ENTERPRISE_REGISTER:
                $tplName = 'TPL_SMS_VERIFY_CODE';
                $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$country_code]['code'];
            break;
            case 2:
                $tplName = 'TPL_SMS_MODIFY_FORGETPASSWORD_CODE';
                $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$country_code]['code'];
            break;
            case 3:
                $tplName = 'TPL_SMS_MODIFY_OLD_PHONE_CODE';
            break;
            case 4:
                $tplName = 'TPL_SMS_MODIFY_NEW_PHONE_CODE';
                $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$country_code]['code'];
            break;
            case 5:
                $tplName = 'TPL_SMS_SET_SITE_CODE';
            break;
            case 6:
                $tplName = 'TPL_SMS_MODIFY_SITE_CODE';
            break;
            case 7:
                $tplName = 'TPL_SMS_SET_PROTION_CODE';
            break;
            case 8:
                $tplName = 'TPL_SMS_MODIFY_PROTION_CODE';
            break;
            case 9:
                $tplName = 'TPL_SMS_WEB_RELOGIN_CODE';
                break;
            case 10:
                $tplName = 'TPL_SMS_CHANGE_MOBILE_NEW';
                break;
            case 11:
            case 13:
            case 14:
                $tplName = 'TPL_SMS_OPEN_BEDEV';
                break;
            case 12:
                $tplName = 'TPL_SMS_MODIFY_PASSWORD_CODE';
                break;
            case 15:
                $tplName = 'TPL_SMS_RESET_BANK';
                break;
            case 16:
                $tplName = 'TPL_SMS_LOGIN_CODE';
                break;
            case 18:
                $tplName = 'TPL_SMS_GOLD_DELIVER_VERIFY';
                break;
            case 19:
                $tplName = 'TPL_SMS_BONUS_GROUP_CODE';
                break;
            case 20:
                $tplName = 'TPL_SMS_MODIFY_FORGETPASSWORD_CODE';
                $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$country_code]['code'];
                break;
            case 21:
               $tplName = 'TPL_SMS_VERIFY_CANDY_BUC_WITHDRAW';
               break;
            case 22:
                $tplName = 'SMS_ENTERPRISE_TRANSFER_VERIFY';
                break;
            case 200: //香港注册验证码
                $tplName = 'HK_TPL_SMS_LOGIN_CODE';
                $mobile_code = $GLOBALS['dict']['MOBILE_CODE'][$country_code]['code'];
                break;
            default:
            break;
       }
       $code = self::getVerifyCode();
       $ret = $this->newAddInfo($mobile, $code,$isPc);
       if ($ret){
           // 入库后发送
           $content = $code;
           // 发送短信走的短信模板
           $siteId = \libs\utils\Site::getId();
           //解决m站openapi页面取不到site_id问题
           if($siteId == 1){
                if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'site_id') !== false){
                        $refererUrl = parse_url(($_SERVER['HTTP_REFERER']));
                        if(isset($refererUrl['query'])){
                                parse_str($refererUrl['query']);
                                if(isset($site_id) && $site_id > 1){
                                        $siteId = $site_id;
                                }
                        }
                }
           }


           // 库里没有的情况
           $userInfo = [];
           if ($sms_teplate_type != 1 && $sms_teplate_type!=4 && $sms_teplate_type != 200) {
               $userInfo = UserModel::instance()->getUserByMobile($mobile, '*');
               if (!isset($mobile_code)) {
                   $mobile_code = !empty($userInfo['mobile_code']) ? $userInfo['mobile_code'] : 86;
               }
           }

           $send = false;
           if ($sms_teplate_type == self::FORGETPASSWORD) {
               //未注册用户，失效用户，删除用户不发验证码
               if (!empty($userInfo) && $userInfo['is_delete'] == 0 && $userInfo['is_effect'] == 1){
                   //未实名认证的用户发验证码
                   if ($userInfo['idcardpassed'] != 1) {
                       $send = true;
                   } else {
                       //实名认证的用户校验的证件号
                       $toCheckIdno = $userInfo['idno'];

                       //实名认证的用户需要输入正确的证件号才发验证码
                       if ($toCheckIdno == $idno) {
                           $send = true;
                       }
                   }
               }
           } else {
               $send = true;
           }

           if ($sms_teplate_type == self::ENTERPRISE_FORGETPWD) {
               $enterpriseContactModel = new EnterpriseContactModel();
               if ($enterpriseContactModel->checkUserByPhone($mobile)) {
                   $send = true;
               }
           }

           $res = ['code' => 0, 'message'=> '成功'];
           if ($send) {
               //台湾手机号去掉号码前面0
               if ($mobile_code == '886' && substr($mobile,0,1) == '0') {
                   $mobile = substr($mobile, 1);
               }

               if ($mobile_code != 86) {
                   $mobile = '00' . $mobile_code . $mobile;
               }
               $res = SmsServer::instance()->send($mobile, $tplName, [$code], null, $siteId);
           } else {
               Logger::info("Skip send sms. mobile:{$mobile}, content:{$content}, realIdno:{$toCheckIdno}, idno:{$idno}, idcardpassed:{$userInfo['idcardpassed']}");
           }

           if (isset($res['code']) && $res['code']==0){
               // 记录频率
               $clientIp = get_client_ip();
               @Block::check('REGISTER_USER_CODE_TODAY', $mobile);
               @Block::check('REGISTER_USER_CODE_SECOND', $mobile);
               @Block::check('CLIENT_REGISTER_USER_CODE_SECOND', $mobile);
               @BLock::check('SEND_SMS_IP_TODAY', $clientIp);
               @BLock::check('SEND_SMS_IP_MINUTE', $clientIp);
               $result = json_encode(array('code' => 1, 'message' => $res['message']));
           }else{
               $result =  json_encode(array('code' => -4,'message'=>$res['message']));
           }
       }else{
           $result = json_encode(array('code' => -4,'message' => '发送失败'));
       }

       $err = json_decode($result, 1);

       setLog(array('errno' => $err['code'], 'errmsg' => $err['message']));

       // 直接返回json
       if ($this->isReturnJsonSendCode) {
           return $result;
       }
       if ($isPc == 1){
           echo $result;
           return;
       }
       if ($isPc == 0){
           return $result;
       }

   }
   /**
    * 清除验证码
    * @param int $mobile
    * @param int $isPc
    */
   public function delMobileCode($mobile,$isPc=1){
      if (empty($mobile)) return false;

      $mobileCodeData = new MobileCodeData();

      return $mobileCodeData->delMobileCode($mobile,$isPc);
   }
}
