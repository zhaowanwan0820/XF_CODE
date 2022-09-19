<?php
/**
 * 新注册用户首页
 * @author jinhaidong
 * @date 2017-6-11 13:48:29
 */
namespace core\service\user;

use core\dao\NewUserPageModel;
use core\dao\AttachmentModel;
use core\dao\DealModel;
use \libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use libs\web\Open;
use core\service\BaseService;
use core\service\coupon\CouponService;
use core\service\dealload\DealLoadService;


class NewUserPageService extends BaseService {

    /**
     * 获取用户进度
     * @return int 0-注册,1-已投资,2-已邀请好友
     */
    public function getNewUserProgress($userId){
        $isRegister = !empty($userId) ? 1 : 0;

        $invite = CouponService::getUserFriendCount($userId);
        $isInvite =  $invite > 0 ? 1 : 0;

        $investService = new DealLoadService();
        $invest = $investService->getTotalLoanMoneyByUserId($userId,false,false,array(1,2,4,5));
        $isInvest =  $invest >= 500 ? 1 : 0;

        $progress = array('isRegister' =>$isRegister, 'isInvest' => $isInvest, 'isInvite' =>$isInvite );
        return $progress;
    }

    /**
     *
     * 新手专区的开关
     */
    public function isNewUserSwitchOpen(){
        $isNewUserSwitchOpen = app_conf('NEWUSER_CENTER_SWITCH');
        return $isNewUserSwitchOpen == 1 ? 1 : 0;
    }

    /**
     *
     * 用户是否是新手（注册30天以内，未完成投资或未完成邀请好友任务）
     */
    public function isNewUser($userId,$regTime){
        if($this->isNewUserSwitchOpen() !=1){
            return false;
        }
        if(empty($userId)){
            return true;
        }
        $regDays = ceil((time() - $regTime - 28800) / 86400);
        $isNewUser = false;
        if($regDays <= 30){//注册30天以内的用户并且未完成新手任务 需要显示新手专区
            $userProgress = $this->getNewUserProgress($userId);
            $isNewUser = ($userProgress['isInvest'] == 1 && $userProgress['isInvite'] == 1) ? false : true;
        }
        return $isNewUser;
    }

}
