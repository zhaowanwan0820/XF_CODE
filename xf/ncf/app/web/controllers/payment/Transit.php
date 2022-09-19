<?php
/**
 * 存管页面跳转
 * @author weiwei12<weiwei12@ucfgroup.com>
 */

namespace web\controllers\payment;
use libs\utils\Risk;
use libs\web\Form;
use libs\utils\Logger;
use web\controllers\BaseAction;
use core\service\account\AccountService;

use core\service\supervision\SupervisionTransitService;
use core\enum\DealEnum;
use core\service\bonus\BonusService;

class Transit extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
    }


    public function invoke() {
        $userInfo = $GLOBALS['user_info'];
        $srv = isset($_GET['srv']) ? addslashes($_GET['srv']) : '';
        if (empty($srv)) {
            return $this->show_error('非法操作', '操作失败', 0, 0, '/', 3);
        }

        $_GET['srv'] = $srv;

        try {
            $transitService = new SupervisionTransitService();
            $accountType = isset($_GET['accountType']) ? (int) $_GET['accountType'] : $userInfo['user_purpose'];
            $accountId = AccountService::initAccount($userInfo['id'], $accountType);
            $params = $transitService->changeSrv($_GET, $accountId);
            $params['canUseBonus'] = isset($userInfo['canUseBonus']) ? $userInfo['canUseBonus'] : DealEnum::CAN_USE_BONUS ;
            $params['fingerprint'] = isset($userInfo['fingerprint']) ? $userInfo['fingerprint'] : Risk::getFinger();
            // 红包使用总开关
            $isBonusEnable = BonusService::isBonusEnable();
            if (empty($isBonusEnable)){
                Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.__LINE__.' canUseBonus '.$isBonusEnable. ' ' .$userInfo['canUseBonus']);
                $params['canUseBonus'] = false;
            }
            $srv = trim($params['srv']);
            unset($params['srv']);
            $result = $transitService->formFactory($srv, $accountId, $params, 'pc');
        } catch (\Exception $e) {
            Logger::error('Error:'.$e->getMessage());
        }

        if (empty($result['status'])) {
            $result['status'] = 0;
        }

        $this->tpl->assign('status', $result['status']);
        if ($result['status']) {
            $this->tpl->assign('form', $result['form']);
            $this->tpl->assign('formId', $result['formId']);
            $this->tpl->assign("title",'正在跳转到存管银行页面');
            $this->tpl->assign("msg",'正在跳转到存管银行页面，请稍等...');
        } else {
            $msg = '网络错误，请重试';
            $title = $msg;
            $this->tpl->assign('title', $title);
            $this->tpl->assign('msg', $msg);
        }
    }
}
