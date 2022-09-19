<?php
/**
 * 存管系统-开通快捷投资服务
 *
 * @date 2017-03-20
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\SupervisionAccountService;

class OpenFree extends ReserveBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array();
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }

        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userRedisInfo = $this->getUserRedisInfo();
        if (empty($userRedisInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $data = $this->form->data;
        // 检查用户是否开通存管账户
        $supervisionAccountObj = new SupervisionAccountService();
        $isSupervisionData = $supervisionAccountObj->isSupervision($userInfo['id']);
        $isOpenAccount = isset($isSupervisionData['isSvUser']) ? (int)$isSupervisionData['isSvUser'] : 0;
        if ($isOpenAccount === 0) {
            $this->setErr('ERR_RESERVE_SUPERVISION_NOACCOUNT');
            return false;
        }
        // 检查用户是否开通快捷投资服务
        $isQuickBidAuth = (int)$supervisionAccountObj->isQuickBidAuthorization($userInfo['id']);
        if ($isQuickBidAuth === 1) {
            $this->setErr('ERR_RESERVE_QUICK_BID_OPEN');
            return false;
        }
        $this->tpl->assign('token', $userRedisInfo['token']);
        $this->tpl->assign('userClientKey', (!empty($data['userClientKey']) ? $data['userClientKey'] : ''));
        return true;
    }
}