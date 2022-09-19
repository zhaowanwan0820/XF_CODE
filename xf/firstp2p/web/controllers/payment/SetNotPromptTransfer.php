<?php
/**
 * 设置不在提示划转
 */

namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\common\WXException;
use libs\web\Form;
use libs\utils\Logger;

class SetNotPromptTransfer extends BaseAction {
    /**
     * 是否页面
     * @var boolean
     */
    const IS_H5 = false;

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
        );
        if (!$this->form->validate()) {
            $this->setErr(0, $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        try {
            if (!$this->ajax_checklogin()) {
                throw new WXException('ERR_USER_NOLOGIN');
            }
            $userId = $GLOBALS['user_info']['id'];
            $result = (int)$this->rpc->local('SupervisionFinanceService\SetNotPromptTransfer', [$userId]);
            $this->json_data = ['status'=>$result];
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION', sprintf('设置不在提示划转成功, userId:%d', $userId))));
            return true;
        } catch(\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION', sprintf('设置不在提示划转失败, userId:%d, ExceptionCode:%s, ExceptionMsg:%s', isset($userId) ? $userId : '', $e->getCode(), $e->getMessage()))));
            $this->setErr($e->getCode(), $e->getMessage());
        }
    }
}
