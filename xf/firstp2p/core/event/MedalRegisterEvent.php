<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/6/23
 * Time: 17:34
 */

namespace core\event;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;


class MedalRegisterEvent extends BaseEvent{

    private $userId;

    public function __construct($userId) {
        $this->userId = $userId;
    }

    public function execute() {
        $medalService = new \core\service\MedalService();
        $request = $medalService->createUserMedalRequestParameter($this->userId);
        $medalBubbleInfo = $medalService->getMedalProgress($request);
        if(isset($medalBubbleInfo['errMsg']) && !empty($medalBubbleInfo['errMsg'])) {
            return false;
        }
        if(!$medalBubbleInfo['isBeginner']) {
            return true;
        }
        $service = "\\NCFGroup\\Medal\\Services\\MedalTag";
        $method = "getBeginnerCouponIds";
        $request = new SimpleRequestBase();
        $couponIds = $medalService->requestMedal($service, $method, $request);
        if(empty($couponIds)) {
            return true;
        }
        $o2oService = new \core\service\O2OService();
        foreach($couponIds as $key => $couponId) {
            $token = "OfferDiscountAfterRegister_{$this->userId}_{$key}_{$couponId}";
            $token = md5($token);
            $ret = $o2oService->acquireDiscount($this->userId, $couponId, $token);
            if($ret === false) {
                return false;
            }
        }
        return true;
    }

    public function alertMails() {
        return array("dengyi@ucfgroup.com", "luzhengshuai@ucfgroup.com");
    }

}
