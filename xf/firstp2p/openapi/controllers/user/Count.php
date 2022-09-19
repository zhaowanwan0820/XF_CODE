<?php

/**
 * @abstract 获取未使用个数信息
 *
 * @author wangshijie<wangshijie@ucfgroup.com>
 * @date 2016年 03月 15日 星期二 20:15:28 CST
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoUser;

class Count extends BaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "type"        => array("filter" => "string", "option" => array("optional" => true)),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");

            return false;
        }
    }

    public function invoke()
    {
        $userInfo = $this->getUserByAccessToken();
        if (empty($userInfo) || $userInfo->userId <= 0) {
            $this->setErr('ERR_GET_USER_FAIL');

            return false;
        }
        $userId = $userInfo->userId;

        $usableInfo = $this->rpc->local('BonusService\get_useable_money', array($userId));
        $data = array();
        $type = $this->form->data['type'];
        if ($type == '1') {
            $data['bonusValidCount']    = intval($this->rpc->local('BonusService\getUnSendCount', array($userId)));
        } else {
            $data['giftValidCount']     = intval($this->rpc->local('O2OService\getUnpickCount', array($userId)));
            $data['bonusValidCount']    = intval($this->rpc->local('BonusService\getUnSendCount', array($userId)));
            $data['bonusValidMoney']    = $usableInfo['money'];
            $data['discountValidCount'] = intval($this->rpc->local('O2OService\getUserUnusedDiscountCount', array($userId)));
            //获取24小时内将过期的红包金额
            $args = array('userId' => $userId,'status' => 1,'endExpireTime' => (time() + 24*3600));
            $data['willExpireBonusMoney']    = intval($this->rpc->local('BonusService\getUserSumMoney', array($args)));

            $data['willExpireDsicountCount'] = intval($this->rpc->local('O2OService\getUserWillExpireDiscountCount', array($userId)));//24小时即将过期的投资券数量
            $data['willExpireCouponCount']   = intval($this->rpc->local('O2OService\getUserWillExpireCouponCount', array($userId)));//24小时即将过期的礼券数量

            //风险评估
            $request = new ProtoUser();
            $request->setUserId(intval($userId));
            $riskData = array();
            $riskRes = $GLOBALS['rpc']->callByObject(array(
                    'service' => 'NCFGroup\Ptp\services\PtpRiskAssessment',
                    'method' => 'getUserRiskData',
                    'args' => $request
            ));
        }

        if(!empty($riskRes)){
            $data['riskData'] = $riskRes->toArray();
        }
        $this->json_data = $data;

        return true;
    }
}
