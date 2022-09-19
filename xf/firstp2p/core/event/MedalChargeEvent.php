<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/5/4
 * Time: 16:18
 */

namespace core\event;


use core\service\CouponBindService;
use core\service\CouponService;
use NCFGroup\Protos\Medal\RequestMedalChargeStat;
use core\service\UserTagService;

class MedalChargeEvent extends  BaseEvent{

    public function __construct($userId,$chargeMoney, $chargeSequence, $chargeTime, $siteId = 1) {
        $this->_userId = intval($userId);
        $this->_chargeSequence = $chargeSequence;
        $this->_chargeMoney = intval($chargeMoney);
        $this->_chargeTime = $chargeTime;
        $this->_siteId = intval($siteId);
    }

    public function execute() {
        $medalService = new \core\service\MedalService();
        $request = new RequestMedalChargeStat();
        $request->setUserId($this->_userId);
        $request->setChargeMoney($this->_chargeMoney);
        $request->setChargeSequence($this->_chargeSequence);
        $request->setChargeTime($this->_chargeTime);
        $request->setSiteId($this->_siteId);

        $couponBindService = new CouponBindService();
        $bindResult = $couponBindService->getByUserId($this->_userId);
        $inviterId = isset($bindResult['refer_user_id']) ? intval($bindResult['refer_user_id']) : 0;
        $userTagService = new UserTagService();
        $userTagData = $userTagService->getTags($this->_userId);
        $userTags = array();
        if($userTagData) {
            foreach($userTagData as $userTag) {
                $userTags[] = $userTag['const_name'];
            }
        }
        $inviterTags = array();
        if($inviterId > 0) {
            $inviterTagData = $userTagService->getTags($inviterId);
            foreach($inviterTagData as $inviterTag) {
                $inviterTags[] = $inviterTag['const_name'];
            }
        }
        $request->setUserTag($userTags);
        $request->setInviterTag($inviterTags);

        $service = "\\NCFGroup\\Medal\\Services\\MedalStat";
        $method = "dispatchEvent";
        $result = $medalService->requestMedal($service, $method, $request);
        return $result;
    }

    public function alertMails() {
        return array(
            "dengyi@ucfgroup.com", "luzhengshuai@ucfgroup.com"
        );
    }
}
