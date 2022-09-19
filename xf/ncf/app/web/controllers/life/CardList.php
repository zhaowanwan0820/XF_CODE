<?php
namespace web\controllers\life;

/**
 * 卡中心-银行卡列表页面
 */
use web\controllers\BaseAction;
use libs\payment\supervision\Supervision;
use core\service\life\PaymentUserService;

class CardList extends BaseAction {
    public function init() {
        if (!$this->check_login()) parent::init();
    }

    public function invoke() {
        $userId = (int)$GLOBALS['user_info']['id'];
        $userInfo = $GLOBALS['user_info'];

        // 未实名认证不能进入银行卡列表页
        if(empty($userInfo['real_name']) || $userInfo['idcardpassed'] != 1) {
            showErr('请先填写身份证信息', 0, '/account/addbank');
            return;
        }

        // 获取用户银行卡列表
        $paymentUserObj = new PaymentUserService();
        $cardList = $paymentUserObj->getMyCardList($userId);
        $this->tpl->assign('list', $cardList);

        // 银行卡信息
        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($userId));
        $hasPassport = $this->rpc->local('AccountService\hasPassport', array($userId));
        $this->tpl->assign('hasPassport', $hasPassport);
        $this->tpl->assign('bankcard', $bankcard);
        $this->tpl->assign('is_audit', (int)$bankcard['is_audit']);

        // 存管相关参数
        $svData = ['isOpenAccount'=>0, 'quickBidAuth'=>0, 'isReserveValid'=>0, 'yxtRepayAuth'=>0, 'isYxtValid'=>0, 'isShowTransfer' => 0];
        // 存管开关是否开启
        $isSupervisionData = $this->rpc->local('SupervisionAccountService\isSupervision', [$userId]);
        $svData['isOpenSv'] = isset($isSupervisionData['isSvOpen']) ? (int)$isSupervisionData['isSvOpen'] : 0;
        if ($svData['isOpenSv'] === 1) {
            // 查询用户是否开启存管系统帐号
            $svData['isOpenAccount'] = isset($isSupervisionData['isSvUser']) ? (int)$isSupervisionData['isSvUser'] : 0;
        }
        // 存管降级
        $isSvDown = Supervision::isServiceDown();
        $this->tpl->assign('svData', $svData);
        $this->tpl->assign('isSvDown', $isSvDown);

        // 总资产是否为零
        $isZeroAssets = $this->rpc->local('SupervisionAccountService\isZeroUserAssets', array($userId));
        $canResetBank = $isZeroAssets && app_conf('WEB_APP_REMOVE_BANKCARD');
        $this->tpl->assign('canResetBank', $canResetBank);
        $this->tpl->assign('user_info', $userInfo);

        // 理财卡解绑、验证等form表单
        $formString = $this->rpc->local('PaymentService\getBindCardForm', [['token' => base64_encode(microtime(true))], true, false, 'bindCardForm']);
        $bankcardValidateForm = $this->rpc->local('PaymentService\getBankcardValidateForm', [['token' => base64_encode(microtime(true))], true, false, 'bankcardValidateForm']);
        $this->tpl->assign('formString', $formString);
        $this->tpl->assign('bankcardValidateForm', $bankcardValidateForm);
        $this->template = "web/views/life/card_list.html";
    }
}