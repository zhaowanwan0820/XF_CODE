<?php
/**
 * 未读消息数
 */
namespace api\controllers\message;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\user\UserService;
use core\service\o2o\DiscountService;
use core\service\bonus\BonusService;

class Count extends AppBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        // 请求间隔，最低30秒
        $interval = max(app_conf('MSG_BOX_COUNT_INTERVAL'), 30);
        if (!app_conf('MSG_BOX_ENABLE')) {
            $this->json_data = array(
                'noticeCount' => 0,
                'unreadCount' => 0,
                'bonusStatus' => 0,
                'discountStatus' => 0,
                'interval' => $interval
            );
            return;
        }

        $user = $this->user;
        try {
            $result = $this->rpc->local('MsgboxService\getUnreadCount', array($user['id']), 'msgbox');
            $notice = $this->rpc->local('NoticeService\getUserNoticeTips', array($user['id']), 'notice');
            $bonusStatus = 0;
            $discountStatus = 0;
            if (!app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
                $bonusStatus = BonusService::getIncomeStatus($user['id']);
                $discountStatus = DiscountService::checkUserMoments($user['id']);
            }
        } catch (\Exception $e) {
            Logger::error('MessageError:'.$e->getMessage());
            $result = $notice = 0;
        }

        $result = array(
            'noticeCount' => $notice,
            'unreadCount' => $result,
            'bonusStatus' => $bonusStatus ? 1 : 0,
            'discountStatus' => $discountStatus ? 1 : 0,
        );

        $result['interval'] = $interval;
        $this->json_data = $result;
    }
}
