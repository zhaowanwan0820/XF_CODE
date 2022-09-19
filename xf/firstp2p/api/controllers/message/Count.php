<?php
/**
 * 未读消息数
 */
namespace api\controllers\message;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\SparowService;

class Count extends AppBaseAction
{

    public function init()
    {
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

    public function invoke()
    {
        //请求间隔，最低30秒
        $interval = max(app_conf('MSG_BOX_COUNT_INTERVAL'), 30);

        if (!app_conf('MSG_BOX_ENABLE')) {
            $this->json_data = array('unreadCount' => 0, 'interval' => $interval);
            return;
        }

        $data = $this->form->data;

        $user = $this->getUserByToken();
        if (empty($user)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }

        try {
            $result = $this->rpc->local('MsgBoxService\getUnreadCount', array($user['id']));
            $notice = $this->rpc->local('NoticeService\getUserNoticeTips', array($user['id']));
            $bonusStatus = 0;
            $bonusStatus = 0;
            if (!app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
                $bonusStatus = $this->rpc->local('WXBonusService\getIncomeStatus', array($user['id']));
                $taskStatus = $this->rpc->local('SparowService\getIncomeStatus', array($user['id']));
                $discountStatus = $this->rpc->local('O2OService\checkUserMoments', array($user['id']));
            }
        } catch (\Exception $e) {
            Logger::error('MessageError:'.$e->getMessage());
            $result = $notice = 0;
        }
        $this->json_data = array(
            'noticeCount' => $notice,
            'unreadCount' => $result,
            'bonusStatus' => $bonusStatus ? 1 : 0,
            'discountStatus' => $discountStatus ? 1 : 0,
            'interval' => $interval,
            'taskStatus' => $taskStatus,
        );
    }

}
