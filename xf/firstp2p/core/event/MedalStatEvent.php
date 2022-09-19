<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2015/12/25
 * Time: 16:44
 */

namespace core\event;

use NCFGroup\Protos\Medal\RequestMedalInvestStat;
use core\dao\DealLoadModel;
use core\service\CouponService;
use core\dao\DealModel;
use core\service\DealTagService;
use core\service\UserTagService;
use core\service\MedalService;

class MedalStatEvent extends  BaseEvent{

    public function __construct($userId, $couponId, $dealId, $dealLoadId, $investMoney, $investTime, $bonus, $siteId) {
        $this->_userId = intval($userId);
        $this->_couponId = $couponId;
        $this->_dealId = intval($dealId);
        $this->_investId = intval($dealLoadId);
        $this->_investMoney = $investMoney;
        $this->_investTime = intval($investTime);
        $this->_bonus = isset($bonus) ? $bonus : 0;
        $this->_siteId = intval($siteId);
    }

    public function execute() {
        $medalService = new MedalService();
        $request = new RequestMedalInvestStat();

        $request->setUserId($this->_userId);
        $request->setInvestId($this->_investId);
        $dealModel = new DealModel();
        $deal = $dealModel->find($this->_dealId);
        if($deal['loantype'] == 7) {
            $request->setIsCharity(true);
        } else {
            $request->setIsCharity(false);
        }

        $dealHorizon = 0;
        if ($deal['deal_type'] != DealModel::DEAL_TYPE_COMPOUND) {
            $dealHorizon = $deal['repay_time'];
            if ($deal['loantype'] != 5) {
                $dealHorizon = $deal['repay_time'] * DealModel::DAY_OF_MONTH;
            }
        }
        $request->setDealHorizon($dealHorizon);

        $dealLoadModel = new DealLoadModel();
        $firstLoad = $dealLoadModel->getFirstDealByUser($this->_userId);
        if($firstLoad['id'] == $this->_investId) {
            $request->setIsFirstInvest(true);
        } else {
            $request->setIsFirstInvest(false);
        }
        $request->setInvestMoney(intval(bcmul($this->_investMoney, 100)));
        $request->setInvestTime($this->_investTime);
        $request->setBonus(intval(bcmul($this->_bonus, 100)));

        $coupon = new CouponService();
        $inviterId = $coupon->getReferUserId($this->_couponId);
        $request->setInviterId($inviterId);

        $currentDealLoad = $dealLoadModel->find($this->_investId, "source_type");
        if($currentDealLoad['source_type'] == 0 || $currentDealLoad['source_type'] == 1 || $currentDealLoad['source_type'] == 8) {
            $request->setPlatform("web");
        } else {
            $request->setPlatform("mobileapp");
        }
        $dealTagService = new DealTagService();
        $dealTag = $dealTagService->getTagByDealId($this->_dealId);
        $request->setDealTag($dealTag);

        $request->setSiteId($this->_siteId);

        //设置用户Tag
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
