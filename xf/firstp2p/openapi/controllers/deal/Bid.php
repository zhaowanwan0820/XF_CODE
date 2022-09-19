<?php

/**
 *
 * @abstract   openapi投资接口
 * @date 2015-06-01
 * @author xiaoan <xiaoan@ucfgroup.com>
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\RequestDealBid;
use libs\utils\Aes;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\risk\RiskServiceFactory;
use core\dao\DealModel;
use core\dao\EnterpriseModel;
use libs\utils\Risk;
/**
 * 投资接口
 *
 * Class Detail
 * @package openapi\controllers\deals
 */
class Bid extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'id' => array(
                'filter' => 'int',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'ecid' => array(
                'filter' => 'string',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'money' => array(
                'filter' => 'reg',
                'message' => '投资金额格式错误，小数点两位',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                ),
            ),
            'coupon' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
            'source_type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'order_id' => array(
                'filter' => 'string',
                'message' => 'order id is error',
            ),
            'discount_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'discount id is error',
            ),
            'discount_group_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'discount group id is error',
            ),
            'discount_sign' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'discount sign is errror',
            ),
            'discount_goodprice' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'discount good price is error',
            ),
            'discount_title' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'discount title is error',
            ),
            'discount_type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'discount type is error',
            ),
            'track_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'track_id is error',
            ),
             'euid' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'euid is error',
            ),

        );
        /*
         * 与父类系统鉴权验证规则合并
         */
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        if (isset($data['ecid']) && $data['ecid'] != "") {//有加密数据传入
            $data['id'] = Aes::decryptForDeal($data['ecid']);
        } else {
            $data['id'] = intval($data['id']);
        }

        if (!deal_belong_current_site($data['id'])) {
            $this->setErr('2005', '站点来源错误');
            return false;
        }

        $user_info = $this->getUserByAccessToken();
        if (!$user_info) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        RiskServiceFactory::instance(Risk::BC_BID,Risk::PF_OPEN_API,$this->device)->check(array('id'=>$user_info->userId,'user_name'=>$user_info->userName,'mobile'=>$user_info->mobile,'money'=>$data['money']),Risk::ASYNC,$data);
        if ($user_info->idcardPassed == 3) {
            $this->setErr('ERR_IDENTITY_NO_VERIFY', '认证信息提交成功，网信理财将在3个工作日内完成信息审核。审核结果将以短信、站内信或者电子邮件等方式通知您。');
            return false;
        }
        //如果未绑定手机
        if (intval($user_info->idcardPassed) == 0 || intval($user_info->idcardPassed) != 1 || !$user_info->realName) {
            $this->setErr('ERR_IDENTITY_NO_VERIFY', "投资前需要验证身份，请先登录www.wangxinlicai.com完成身份验证");
            return false;
        }

//        $deal = DealModel::instance()->find($data['id']);
//
//        if($this->rpc->local('DealService\isP2pPath', array($deal))){
//            if(!$this->rpc->local('UserService\allowAccountLoan', array($user_info['user_purpose']))){
//                $this->setErr('ERR_INVESTMENT_USER_CAN_BID', $GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID']);
//                return false;
//            }
//        }
//
        $request = new RequestDealBid();
        $request->setId($data['id']);

        // 直接获取用户绑定优惠码
        $couponLatest = $this->rpc->local('CouponService\getCouponLatest', array($user_info->userId));
        if ($couponLatest['short_alias']) {
            $data['coupon'] = $couponLatest['short_alias'];
        } elseif (!$data['coupon'] && isset($this->clientConf['default_coupon'])) {
            $data['coupon'] = app_conf($this->clientConf['default_coupon']);
        }
        $request->setCoupon((string) $data['coupon']);
        if (empty($data['site_id'])) {
            $data['site_id'] = 1;
        } elseif (!isset($GLOBALS['sys_config']['TPL_SITE_LIST'][$data['site_id']])) {
            $this->setErr('2005', '站点来源错误');
            return false;
        }
        $request->setSite_id(intval($data['site_id']));

        //不加 存管的分站 不走存管的划转逻辑
        $arrayClientID = json_decode(app_conf('NO_SUPERVISION_CLIENT'), true);
        if(in_array($this->_client_id, $arrayClientID)){
            $remain = bcadd(str_replace(',', '',$user_info->money),str_replace(',', '',$user_info->bonus),2);
            if(bccomp($remain, $data['money'], 2) < 0){
                $this->setErr('ERR_USER_MONEY_FAILED','余额不足，请先进行充值');
                return false;
            }
        }

        //基类获得端标识参数
        $data['source_type'] = $this->device;
        if ($this->device == DeviceEnum::DEVICE_UNKNOWN) {
            $data['source_type'] = DeviceEnum::DEVICE_WAP;
        }
        //投资劵参数
        $request->setDiscountId(intval($data['discount_id']));
        $request->setDiscountGroupId(intval($data['discount_group_id']));
        $request->setDiscountSign($data['discount_sign']);
        $request->setDiscountGoodprice($data['discount_goodprice']);
        $request->setDiscountType($data['discount_type']);

        $request->setSource_type(intval($data['source_type']));
        $request->setMoney($data['money']);
        $request->setUserId($user_info->userId);
        $request->setUserInfo(array('idcardpassed' => $user_info->idcardpassed));
        $request->setTrackId(intval($data['track_id']));
        if(!empty($data['euid'])){
            $request->setEuid($data['euid']);
        }

        if ($data['order_id']) {
            $order_res = $this->rpc->local(
                    'ThirdpartyOrderService\getOrderByOrderId', array($data['order_id'])
            );
            if ($order_res['errno'] == 0) {
                $return_data['order_id'] = $data['order_id'];
                $return_data['load_id'] = $order_res['data']['deal_loan_id'];
                $this->json_data = $return_data;
                return true;
            }
            $request->setOrderId($data['order_id']);
        }

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDeal',
            'method' => 'bid',
            'args' => $request
        ));
        if (!empty($response->errorCode) && !empty($response->errorMsg)) {

            $this->errorCode = $response->errorCode;
            $this->errorMsg = $response->errorMsg;
            // 项目风险承受能力和个人评估结果不一致时弹窗
            if (isset($response->dealProjectRisk['remaining_assess_num'])){
                $this->json_data_err = $response->dealProjectRisk;
            }
            unset($response->errorCode, $response->errorMsg);
            return false;
        }

        $return_data = $response->toArray();
        $return_data['order_id'] = $data['order_id'];
        $access_token = $this->getCouponAccessToken($oauth_token, $user_info->userId);
        $return_data['access_token'] = $access_token;

        //存管相关
        $svInfo = $this->rpc->local('SupervisionService\svInfo', array($user_info->userId));
        if (!empty($svInfo['status']) && $svInfo['isSvUser'] == 1  && !$svInfo['isFreePayment']) {
            if ($isShowBankAlert = $this->rpc->local('SupervisionDealService\setQuickBidAuthCount', array($user_info->userId))) {
                $return_data['srv'] = 'freePaymentQuickBid';
            }
        }

        $this->json_data = $return_data;
        RiskServiceFactory::instance(Risk::BC_BID,Risk::PF_OPEN_API)->notify();
        return true;
    }

}
