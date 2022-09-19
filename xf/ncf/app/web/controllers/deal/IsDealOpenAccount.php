<?php
/**
 * 存管系统-查询标的是否是P2P的标、是否在存管行开户
 */

namespace web\controllers\deal;

use web\controllers\BaseAction;
use libs\common\WXException;
use libs\web\Form;
use libs\utils\Aes;
use libs\utils\Logger;
use core\service\account\AccountService;
use core\service\user\UserBindService;
use core\service\supervision\SupervisionAccountService;

class IsDealOpenAccount extends BaseAction {
    /**
     * 是否页面
     * @var boolean
     */
    const IS_H5 = false;

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'required', 'message' =>'借款不存在'),
        );
        if (!$this->form->validate()) {
            $this->setErr(0, $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        try{
            if (!$this->ajax_checklogin()) {
                throw new WXException('ERR_USER_NOLOGIN');
            }
            $data = $this->form->data;
            if (empty($data['id'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $userId = $GLOBALS['user_info']['id'];
            // 获取标的ID
            $dealId = Aes::decryptForDeal($data['id']);
            // 是否弹出开通存管户的窗口(0:弹窗1:不弹窗)
            $isAlert = 1;
            // 检查是否实名、绑卡
            $checkBindCardRet = UserBindService::isBindBankCard($userId);
            if (true == $checkBindCardRet['ret'] && $this->isSvOpen) {
                // 获取用户账户ID
                $accountId = AccountService::getUserAccountId($userId, $GLOBALS['user_info']['user_purpose']);
                $supervisionAccountObj = new SupervisionAccountService();
                $isSvUser = $supervisionAccountObj->isSupervisionUser($accountId);
                // 尚未在存管系统开户
                if (false === $isSvUser) {
                    // 判断标的是否走p2p存管流程
                    // $isReport = (int)$this->rpc->local('DealService\isP2pPath', [$dealId]);
                    // $isReport && $isAlert = 0;
                    $isAlert = 0;
                }
            }
            $this->json_data = ['status'=>$isAlert];
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION', sprintf('标的相关页面弹出开户窗口成功,userId:%d,aesDealId:%s,dealId:%s', $userId, $data['id'], $dealId))));
            return true;
        }catch(\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'SUPERVISION', sprintf('标的相关页面弹出开户窗口失败,userId:%d,aesDealId:%s,dealId:%s,ExceptionCode:%s,ExceptionMsg:%s', $userId, $data['id'], $dealId, $e->getCode(), $e->getMessage()))));
            $this->setErr($e->getCode(), $e->getMessage());
        }
    }
}