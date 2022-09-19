<?php
namespace core\service\payment;

use libs\db\Db;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\utils\Alarm;
use libs\utils\Aes;
use libs\utils\Curl;
use libs\utils\Monitor;
use core\enum\PaymentEnum;
use core\service\BaseService;
use core\service\user\UserService;
use core\service\o2o\CouponService;
use core\dao\user\UserIdentityModifyLogModel;
use core\dao\supervision\SupervisionChargeModel;
use core\dao\supervision\SupervisionWithdrawModel;

class PaymentService extends BaseService {
    public static function getUserType($idType) {
        $userType = '01';
        if(!empty($idType)) {
            if ($idType == 1) {
                 $userType = '01';
            }
            else if ($idType == 2) {
                 $userType = '04';
            }
            else if ($idType == 3) {
                 $userType = '03';
            }
            else if ($idType >= 4 && $idType <= 6) {
                 $userType = '02';
            }
            else if ($idType == 99 ) {
                 $userType = '04';
            }
        }
        return $userType;
    }

    /**
     * 是否在支付开户
     */
    public static function hasRegister($userId)
    {
        // 获取用户信息
        $userInfo = UserService::getUserById($userId, 'payment_user_id');
        if (empty($userInfo['payment_user_id'])) {
            return false;
        }
        return true;
    }

    /**
     * 充值结果信息查询
     * @param $paymentNoticeSn 充值单编号
     * return array
     */
    public static function chargeResultInfoQuery($paymentNoticeSn, $businessType = ''){
        if(empty($paymentNoticeSn)){
          throw new Exception("payment_notice_sn is empty");
        }
        $bType = '';
        switch ($businessType)
        {
            case PaymentEnum::PLATFORM_H5_NEW_CHARGE:
                // 如果是h5newcharge 则改变值为newcharge
                $bType = 'new_recharge';
                break;
            default:
                //支付默认 businessType,10为充值
                $bType = '10';
        }
        return PaymentApi::instance()->request("searchonetrade",array("businessType"=>$bType, "outOrderId"=>$paymentNoticeSn));
    }

    /**
     * 充值结果信息查询
     * @param $paymentNoticeSn 充值单号
     * return status
     */
    public function chargeStatusByPaymentNoticeNo($paymentNoticeSn) {
       $chargeResult = self::chargeResultInfoQuery($paymentNoticeSn);
       return self::chargeStatus($chargeResult);
    }

    private static function checkPaymentApiError($result) {
       $flag = true;
       if(empty($result)){
          $flag = false;
       }
       if(!isset($result['status'])||$result['status'] == '02'){
          $flag = false;
       }
       return $flag;
    }

    /**
     * 充值结果状态查询
     * @param $chargeResultInfo 充值结果状态
     * return status
     */
    public static function chargeStatus($chargeResultInfo) {
        if(!self::checkPaymentApiError($chargeResultInfo)) {
            $result = "充值结果状态查询返回参数错误：" . print_r($chargeResultInfo,1);
            Alarm::push('payment', 'chargeStatus', $result);
            return PaymentEnum::ERROR_PAYMENT_API;
        }

        if($chargeResultInfo['status'] == '30004') {
            return PaymentEnum::ERROR_PAYMENT_ORDER_NOTEXITS;
        }

        if($chargeResultInfo['respCode']=='00') {
            if($chargeResultInfo['orderStatus'] == '00'){
                return PaymentEnum::CHARGE_SUCCESS;
            }
            else if($chargeResultInfo['orderStatus'] == '02'){
                return PaymentEnum::CHARGE_PENDING;
            }
            return PaymentEnum::CHARGE_FAILURE;
        }
        else{
            $result = '充值结果状态查询返回错误：' . print_r($chargeResultInfo,1);
            Alarm::push('payment', 'chargeStatus', $result);
            return PaymentEnum::ERROR_PAYMENT_API;
        }
    }

    /**
     * 设置充值状态缓存
     */
    public function setChargeStatusCache($userId, $orderSn)
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis) {
            return $redis->setex("CHARGE_{$userId}_{$orderSn}", 86400, 1);
        }
    }

    /**
     * 获取充值状态
     */
    public function getChargeStatusCache($userId, $orderSn)
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis) {
            return $redis->get("CHARGE_{$userId}_{$orderSn}");
        }
    }

    /**
     * 日志记录
     */
    public static function log($body, $level = Logger::INFO)
    {
        $destination = ROOT_PATH . 'storage/log/logger/PaymentService.'.date('y_m').'.log';
        Logger::wLog($body, $level, Logger::FILE, $destination);
    }

    /**
     * check
     * 检查订单支付结果
     *
     * @param mixed $order_id
     * @param mixed $merchant
     * @param mixed $businessType
     * @access public
     * @return void
     */
    public static function check($order_id, $merchant, $businessType) {
        $params = array(
            'outOrderId' => $order_id,
            'merchantId' => $merchant,
            'businessType' => $businessType,
        );
        $query_string = Aes::buildString($params);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $params['sign'] = $signature;

        $api = $GLOBALS['sys_config']['XFZF_PAY_CHECK'];
        $aesData = Aes::encode($query_string."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $retStr = Curl::post($api, array('data'=>$aesData));
        $ret = json_decode($retStr, true);

        $rs = Aes::decode($ret['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        parse_str($rs, $datas);
        // 记录日志文件
        $log = array(
            'type' => 'PaymentService',
            'order_id' => $order_id,
            'function' => 'check',
            'msg' => '调用支付端查询订单接口',
            'api' => $api,
            'request' => $aesData,
            'response' => $retStr,
            'response_decode' => $datas,
        );
        logger::wLog($log);
        if (Aes::validate($datas)) {
            PaymentApi::log("PaymentService.check:".json_encode($log), Logger::INFO);
            // 验证成功
            return $datas;
        } else {
            PaymentApi::log("PaymentService.check:".json_decode($log), Logger::WARN);
            return false;
        }
    }

    /**
     * 跳转到四要素认证页面
     * @param integer userId
     */
    public static function gotoFactorAuthPage($params) {
        $target = $targetNew ? "target='blank'" : '';
        $formId = 'factorAuthForm';
        $config = PaymentApi::instance()->getGateway()->getConfig('factorAuth');

        $html = "<form action='{$config['url']}' id='$formId' $target style='display:none;' method='post'>\n";
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='$key' value='$value' />\n";
        }
        $html .= "</form>\n";

        PaymentApi::log('Ucfpay getForm factorAuth, params:'.json_encode($params));
        echo $html;
        echo '正在跳向支付页面....<br/><script>document.forms[0].submit();</script>';
        return true;
    }

    /**
     * H5 获取四要素验证表单
     */
    public static function getAuthCardForm($params) {
        $formId = 'h5authCardForm';
        $form = PaymentApi::instance()->getGateway()->getForm('h5authCard', $params,$formId, false);
        $formData = '';
        $result = ['respCode' => '00', 'respMsg' => ''];
        if (!empty($form)) {
            $formData = [
                    'form' => $form,
                    'formId' => $formId,
            ];
        } else {
            $result['respCode'] = '01';
        }
        $result['data'] = $formData;
        $result['status'] = 'S';
        return $result;
    }

    /**
     * 线下充值接口
     */
    public static function getOfflineChargeForm($params) {
        $formId = 'offlineChargeForm';
        $form = PaymentApi::instance()->getGateway()->getForm('offlineCharge', $params, $formId, false);
        $formData = '';
        $result = ['respCode' => '00', 'respMsg' => ''];
        if (!empty($form)) {
            $formData = [
                'form' => $form,
                'formId' => $formId,
            ];
        } else {
            $result['respCode'] = '01';
        }
        $result['data'] = $formData;
        $result['status'] = 'S';
        return $result;
    }

    /**
     * 更新用户实名信息
     * @param array $params
     *  user_id 用户id
     *  order_id 请求订单id
     *  status 订单状态
     *  fail_reason 错误原因
     */
    public static function updateUserIdentityByLog($params) {
        if (empty($params)) {
            return false;
        }
        $modifyLogModel = UserIdentityModifyLogModel::instance();
        $modifyLog = $modifyLogModel->getLogByOrderId($params['order_id']);
        if ($modifyLog['status'] == $params['status']) {
            return true;
        }
        $db = Db::getInstance('firstp2p');
        try {
            $db->startTrans();
            // 更新日志
            $res = $modifyLog->updateLog($params);
            if (!$res) {
                throw new \Exception('更新用户实名信息日志失败');
            }

            // 成功才更新用户信息
            if ($params['status'] == UserIdentityModifyLogModel::STATUS_SUCCESS) {
                // 更新用户实名信息
                $res = UserService::updateUserIdentityInfo($modifyLog['user_id'], $modifyLog['real_name'], $modifyLog['id_type'], $modifyLog['idno']);
                if (!$res) {
                    throw new \Exception('更新用户实名信息和开户名失败');
                }
            }

            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('params:%s, errMsg:%s', json_encode($params), $e->getMessage()))));
            return false;
        }
    }

    /**
     * 先锋注册用户，复写web端注册用户接口
     * @param userId 用户ID
     * @return string
     **/
    public function mobileRegister($userId, $userData = array()){
        $merchant = $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'];
        $userId = trim($userId);
        if(empty($userId)){
            return PaymentEnum::REGISTER_FAILURE;
        }
        $user = $userService::getUserById($userId);
        if(!empty($user['payment_user_id'])){
            return self::REGISTER_HASREGISTER;
        }
        if (($user['real_name'] == '' || $user['idno'] == '') && (empty($userData['cardNo']) || empty($userData['realName']))) {
            return PaymentEnum::REGISTER_FAILURE;
        }
        $registerParam['merchantId'] = $merchant;
        $registerParam['userId'] = $user['id'];
        //真实姓名
        $registerParam['realName'] = $user['real_name'];
        if (empty($registerParam['realName']) && !empty($userData['realName'])) {
            $registerParam['realName'] = $userData['realName'];
        }
        $idinfo = $userService->getIdnoAndType($userId);
        $user_type = '01';
        if(is_array($idinfo)) {
            if ($idinfo['id_type'] == 1) {
                $user_type = '01';
            }
            else if ($idinfo['id_type'] == 2) {
                $user_type = '04';
            }
            else if ($idinfo['id_type'] == 3) {
                $user_type = '03';
            }
            else if ($idinfo['id_type'] >= 4 && $idinfo['id_type'] <= 6) {
                $user_type = '02';
            }
            else if ($idinfo['id_type'] == 99 ) {
                $user_type = '99';
            }
        }
        // 证件类型
        $registerParam['cardType'] = $user_type; //01-身份证,//02-港澳台
        //证件号
        $registerParam['cardNo'] = $idinfo['idno'];
        if (empty($registerParam['cardNo']) && !empty($userData['cardNo'])) {
            $registerParam['cardNo'] = $userData['cardNo'];
        }
        $registerParam['mobileNo'] = $user['mobile'];
        $query_string = \libs\utils\Aes::buildString($registerParam);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $registerParam['sign'] = $signature;

        $api = $GLOBALS['sys_config']['XFZF_USER_REGISTER'];
        $aesData = \libs\utils\Aes::encode($query_string."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $retSrc = Curl::post($api, array('data'=>$aesData));
        $ret = json_decode($retSrc, true);
        $rs = \libs\utils\Aes::decode($ret['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        parse_str($rs, $datas);
        // 记录日志文件
        $log = array(
            'type' => 'PaymentService',
            'function' => 'mobileRegister',
            'msg' => '手机注册用户',
            'api' => $api,
            'request' => $registerParam,
            'response' => $retSrc,
            'response_decode' => $datas,
        );
        logger::wLog($log);

        if (\libs\utils\Aes::validate($datas)) {
            PaymentApi::log("PaymentService.mobileRegister:".json_encode($log), Logger::INFO);
            // 验证成功
            if (!empty($datas['userId']) && $datas['respCode'] == '00' && ($datas['status'] == '00' || $datas['status'] == '05')) {
                $user->payment_user_id=$datas['userId'];
                $user->save();
                $GLOBALS['user_info']['payment_user_id'] = $datas['userId'];
                return PaymentEnum::REGISTER_SUCCESS;
            } else {
                return PaymentEnum::REGISTER_FAILURE;
            }
       } else {
            PaymentApi::log("PaymentService.mobileRegister:".json_encode($log), Logger::WARN);
            return PaymentEnum::REGISTER_FAILURE;
       }
    }

    /**
     * apply
     * 调用支付端创建订单
     *
     * @param mixed $notice_id
     * @param mixed $merchant
     * @param mixed $uid
     * @param mixed $amount
     * @param mixed $cur_type
     * @param mixed $real_name
     * @param mixed $card_type
     * @param mixed $idno
     * @param mixed $mobile
     * @access public
     * @return void
     */
    public function apply($notice_id, $merchant, $uid, $amount, $cur_type, $real_name, $card_type, $idno, $mobile) {
        $payment_notice = PaymentNoticeModel::instance()->find($notice_id);
        if (empty($payment_notice)) {
            $msg = '没有对应订单号！';
            PaymentApi::log("PaymentService.apply:".$notice_id.$msg, Logger::WARN);
            \libs\utils\Alarm::push('payment', "PaymentService.apply", $notice_id.$msg);
            return false;
        }
        $params = array(
            'outOrderId' => $payment_notice['notice_sn'],
            'merchantId' => $merchant,
            'userId' => $uid,
            'amount' => $amount * 100,
            'curType' => $cur_type,
            //'notifyUrl' => $notify,
            //'realName' => $real_name,
            //'cardType' => $card_type,
            //'cardNo' => $idno,
            //'mobileNo' => $mobile,
        );
        $query_string = \libs\utils\Aes::buildString($params);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $params['sign'] = $signature;

        $api = $GLOBALS['sys_config']['XFZF_PAY_CREATE'];
        $aesData = \libs\utils\Aes::encode($query_string."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $retSrc = Curl::post($api, array('data'=>$aesData));
        $ret = json_decode($retSrc, true);
        $rs = \libs\utils\Aes::decode($ret['data'], base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        parse_str($rs, $datas);
        // 记录日志文件
        $log = array(
            'type' => 'PaymentService',
            'money' => $amount,
            'function' => 'apply',
            'msg' => '调用支付端创建订单接口',
            'api' => $api,
            'request' => $params,
            'response' => $retSrc,
            'response_decode' => $datas,
        );
        logger::wLog($log);

        if (Aes::validate($datas)) {
            PaymentApi::log("PaymentService.apply:".json_encode($log), Logger::INFO);
            // 验证成功
            if ($datas['status'] == '00') {
                // 支付创建订单成功
                Monitor::add('MOBILE_PAY_CREATE_SUCCESS');
                // 需要将p2p后台的订单状态设置为处理中
                $payment_notice->is_paid = 2;
                $payment_notice->save();
                // app端需要的数据是给移动端进行签名使用的
                return $params;
            } else {
                // 支付创建订单未返回成功
                Monitor::add('MOBILE_PAY_CREATE_FAIL');
                return false;
            }
        } else {
            PaymentApi::log("PaymentService.apply:".json_encode($log), Logger::WARN);
            $msg = "返回结果数据验证失败。";
            Alarm::push('payment', "PaymentService.apply", $msg);
            // 创建订单失败
            Monitor::add('MOBILE_PAY_CREATE_FAIL');
            return false;
        }
    }

    /**
     * 跳转到本地页面绑再跳转到支付
     */
    public function getBankcardValidateForm($params, $methodPost = false, $targetNew = false, $formId = 'bankcardValidateForm')
    {
        $method = $methodPost === true ? 'post' : 'get';
        $url = '/payment/goValidateCard';
        $target = $targetNew ? "target='blank'" : '';
        $html = "<form action='$url' id='$formId' $target style='display:none;' method='{$method}'>\n";
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='$key' value='$value' />\n";
        }
        $html .= "</form>\n";
        return $html;
    }

    /**
     * 跳转到绑卡界面再跳转到支付
     */
    public function getBindCardForm($params, $methodPost = false, $targetNew = false, $formId = 'getBindCardForm')
    {
        $method = $methodPost === true ? 'post' : 'get';
        $url = '/payment/goBindCard';
        $target = $targetNew ? "target='blank'" : '';
        $html = "<form action='$url' id='$formId' $target style='display:none;' method='{$method}'>\n";
        foreach ($params as $key => $value)
        {
            $html .= "    <input type='hidden' name='$key' value='$value' />\n";
        }
        $html .= "</form>\n";
        return $html;
    }

    public function filterXss($data) {
        if (empty($data['realName'])) {
            throw new \Exception('姓名长度不合法');
        }
        if (empty($data['cardNo'])) {
            throw new \Exception('请填写身份证号');
        }
        $data['realName'] = htmlspecialchars(trim($data['realName']));
        $data['cardNo'] = htmlspecialchars(trim($data['cardNo']));
        if(strlen($data['cardNo']) == 15) {
            throw new \Exception('仅支持二代身份证');
        }
        if (strlen($data['cardNo']) != 18) {
            throw new \Exception('身份证长度不正确');
        }
        return $data;
    }

    /**
     * 判断用户是否已经设置交易密码
     */
    public static function usedQuickPay($userId) {
        $params = array(
            'source' => 1,
            'userId' => $userId,
        );
        $userInfo = PaymentApi::instance()->request('searchuserinfo', $params);
        if (isset($userInfo['isSetTransPWD']) && $userInfo['isSetTransPWD'] == '1') {
            return true;
        }
        return false;
    }

    /**
     * 整理充值参数并发送请求到O2O领券
     * @param array $chargeOrder 充值记录
     */
    public function chargeTriggerO2O($chargeOrder) {
        // 用户ID
        $chargeOrder['user_id'] = intval($chargeOrder['user_id']);
        // 存管回调的金额是amount字段，单位是分
        $chargeOrder['money'] = bcmul($chargeOrder['amount'] , 0.01, 2);

        // 获取用户最近一条提现成功的记录
        $withdrawOrderInfo = SupervisionWithdrawModel::instance()->getLastWithdrawInfo($chargeOrder['user_id']);
        $withdrawTime = !empty($withdrawOrderInfo['withdraw_time']) ? (int)$withdrawOrderInfo['withdraw_time'] : 0;
        // 快捷充值（线上）19，大额充值（线下）20
        if (in_array($chargeOrder['platform'], PaymentEnum::$offlinePlatform)) {
            $action = PaymentEnum::TRIGGER_CHARGE_OFFLINE;
        }else{
            $action = PaymentEnum::TRIGGER_CHARGE_ONLINE;
        }
        // 站点ID
        $chargeSiteId = !empty($GLOBALS['sys_config']['TEMPLATE_LIST']['firstp2pcn']) ? $GLOBALS['sys_config']['TEMPLATE_LIST']['firstp2pcn'] : 100;

        // 记到日志里的参数
        $logParams = array($chargeOrder['user_id'], $action, $chargeOrder['id'], $chargeOrder['money'], $chargeSiteId, $withdrawTime);
        // 调用O2O接口
        CouponService::chargeTriggerO2O($chargeOrder['user_id'], $action, $chargeOrder['id'], $chargeOrder['money'], $chargeSiteId, $withdrawTime);
        if (CouponService::hasError()) {
            PaymentApi::log(sprintf('PaymentService::chargeTriggerO2O is failed, chargeTriggerO2OParams:%s, errorMsg:%s', json_encode($logParams), CouponService::getErrorMsg()), Logger::ERR);
            return false;
        }
        PaymentApi::log(sprintf('PaymentService::chargeTriggerO2O is success, userId:%d, outOrderId:%s, chargeTriggerO2OParams:%s', $chargeOrder['user_id'], $chargeOrder['out_order_id'], json_encode($logParams)));
        return true;
    }
}