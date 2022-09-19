<?php

/**
 * 我的页面中，未使用红包、礼券、投资券和风险评估的信息
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Common\Library\ApiService;

class Count extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "oauth_token is required")
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");

            return false;
        }
    }

    public function invoke()
    {
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');

            return false;
        }
        $userId = $userInfo['id'];

        $usableInfo = $this->rpc->local('BonusService\get_useable_money', array($userId));
        $data = array();
        $data['giftValidCount']     = intval($this->rpc->local('O2OService\getUnpickCount', array($userId)));

        //增加未领取和新到标志
        $hasUnpick = false;
        $data['desc'] = '';
        $data['giftType'] = 0;
        if(($data['giftValidCount'] > 0)){
            $data['desc'] = $data['giftValidCount'].'张未领取';
            $data['giftType'] = 1;
            $hasUnpick = true;
        }
        //没有未领取，检查是否有新到的券
        if (!$hasUnpick) {
            $newCount = ApiService::rpc("o2o", "coupon/getUserNewCouponCount", ['userId' => $userId]);
            if ($newCount) {
                $data['desc'] = $newCount.'张新到';
                $data['giftType'] = 2;
            }
        }

        /*$data['bonusValidCount']    = intval($this->rpc->local('BonusService\getUnSendCount', array($userId)));
        $data['bonusValidMoney']    = $usableInfo['money'];
        $data['discountValidCount'] = intval($this->rpc->local('O2OService\getUserUnusedDiscountCount', array($userId)));
            //获取24小时内将过期的红包金额
        $args = array('userId' => $userId,'status' => 1,'endExpireTime' => (time() + 24*3600));
        $data['willExpireBonusMoney']    = intval($this->rpc->local('BonusService\getUserSumMoney', array($args)));

        $data['willExpireDsicountCount'] = intval($this->rpc->local('O2OService\getUserWillExpireDiscountCount', array($userId)));//24小时即将过期的投资券数量
        $data['willExpireCouponCount']   = intval($this->rpc->local('O2OService\getUserWillExpireCouponCount', array($userId)));//24小时即将过期的礼券数量*/


        //mock start
        $data['bonusValidCount']         = 0;
        $data['bonusValidMoney']         = 0;
        $data['discountValidCount']      = 0;
        $data['willExpireBonusMoney']    = 0;
        $data['willExpireDsicountCount'] = 0;
        $data['willExpireCouponCount']   = 0;
        //mock end

        //风险评估
        $request = new ProtoUser();
        $request->setUserId(intval($userId));
        $riskData = array();
        $riskRes = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpRiskAssessment',
            'method' => 'getUserRiskData',
            'args' => $request
        ));

        //红包
        $data['new_bonus_title'] = app_conf('NEW_BONUS_TITLE');
        $data['new_bonus_unit'] = app_conf('NEW_BONUS_UNIT');

        if(!empty($riskRes)){
            $data['riskData'] = $riskRes->toArray();
        }
        $this->json_data = $data;

        return true;
    }
}
