<?php

/**
 * 优惠券筛选投资列表
 * @date 2017-07-19
 * @author yanjun <yanjun5@ucfgroup.com>
 * */
namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\bonus\BonusService;


class DealListByDiscount extends AppBaseAction {


    public function init() {
        parent::init ();
        $this->form = new Form ();
        $this->form->rules = array (
                'token' => array('filter' => 'required', 'message' => 'token is required'),
                "discountId" => array ("filter" => "required", "message" => "discountId is required"),
                "pageNum" => array ("filter" => "int"),
                "pageSize" => array ("filter" => "int"),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }
    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->user;

        //获取优惠券信息
        $discount = $this->rpc->local('DealListService\getDiscountInfo', array(intval($data['discountId'])));
        if(empty($discount) || $discount['ownerUserId'] != $userInfo['id']){
            $this->setErr ('ERR_PARAMS_ERROR', '优惠券不存在');
            return false;
        }
        if($discount['status'] != 1 || $discount['useStartTime'] > time() || $discount['useEndTime'] < time()){
            $this->setErr ('ERR_PARAMS_ERROR', '优惠券已过期或者已使用');
            return false;
        }

        $bidAmount = $discount['bidAmount'];//优惠券价格
        $bidDayLimit = $discount['bidDayLimit'];//优惠券期限
        $discountType = $discount['discountType'];
        $discountGroupId = $discount['discountGroupId'];


        //账户余额
        $bonus = BonusService::getUsableBonus($userInfo['id']);//红包金额
        $userMoney = bcadd($userInfo['money'], $bonus['money'],2);
        //存管账户
        $supervisionService = new \core\service\SupervisionService();
        $svInfo = $supervisionService->svInfo($userInfo['id']);
        if (isset($svInfo['isSvUser']) && $svInfo['isSvUser']) {
            $userMoney  = bcadd($userInfo['money'], $svInfo['svBalance'],2);
        }

        //$dealsList = $this->rpc->local('DealListService\getDealListByDiscount', array($discount['discountGroupId'], $discountType, $bidAmount, $bidDayLimit, $userMoney));
        $dealsList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealListService\getDealListByDiscount', array($bidAmount, $bidDayLimit,$discountGroupId,$discountType, $userMoney, $this->app_version, $userInfo['id'])), 60);
        $dealsList['discount'] = $discount;

        $this->json_data = $dealsList ;
    }
}
