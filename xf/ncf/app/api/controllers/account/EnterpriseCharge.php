<?php
/**
 * 企业用户充值
 */
namespace api\controllers\account;

use NCFGroup\Common\Library\Idworker;
use api\controllers\AppBaseAction;
use libs\web\Form;
use libs\utils\Logger;
use core\service\account\AccountService;
use core\service\user\UserService;
use core\service\supervision\SupervisionFinanceService;

/**
 * 企业用户充值
 */
class EnterpriseCharge extends AppBaseAction {
    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    protected $redirectWapUrl = '/account/enterpriseCharge';

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->user;

        // 检查是否企业用户
        if (!isset($userInfo['is_enterprise_user']) || !$userInfo['is_enterprise_user']) {
            $this->setErr('ERR_MANUAL_REASON', '仅支持企业用户充值');
        }

        $result = [];
        // 资产中心余额
        $balanceResult = AccountService::getAccountMoney($userInfo['id'], $userInfo['user_purpose']);
        $userInfo['svFreezeMoney'] = $balanceResult['lockMoney'];
        $userInfo['svTotalMoney'] = $balanceResult['totalMoney'];
        $userInfo['svCashMoney'] = $balanceResult['money'];
        $result['userInfo'] = $userInfo;

        $orderId = Idworker::instance()->getId();
        $result['orderId'] = $orderId;
        $result['tkSn'] = $data['token'];
        $result['returnUrl'] = 'storemanager://api?type=recharge&channel=cg';

        // 获取网贷大额充值账户名称
        $supervisionFinanceObj = new SupervisionFinanceService();
        $offlineChargeName = $supervisionFinanceObj->getOfflineChargeName();
        $result['offlineChargeName'] = $offlineChargeName;
        // 记录日志
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userInfo['id'], $data['token'])));
        $this->json_data = $result;
    }
}