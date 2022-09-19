<?php
/**
 * 网贷p2p大额充值-APP
 * @date 2018-05-28
 * @author weiwei12 <weiwei12@ucfgroup.com>
 */

namespace api\controllers\account;

use NCFGroup\Common\Library\Idworker;
use api\controllers\AppBaseAction;
use libs\web\Form;
use libs\utils\Logger;
use core\service\SupervisionFinanceService;
use api\conf\ConstDefine;

/**
 * 网贷大额充值
 */
class P2pOfflineCharge extends AppBaseAction {
    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            'money' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data   = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        // 充值金额，单位分
        $moneyCent = isset($data['money']) ? (int)$data['money'] : 0;
        // 充值金额，单位元
        $money = $moneyCent > 0 ? bcdiv($moneyCent, 100, 2) : '';
        $this->tpl->assign('money', $money);

        //资产中心余额
        $balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array($userInfo['id']));
        $userInfo['svFreezeMoney'] = $balanceResult['supervisionLockMoney'];
        $userInfo['svTotalMoney'] = $balanceResult['supervisionMoney'];
        $userInfo['svCashMoney'] = $balanceResult['supervisionBalance'];
        $this->tpl->assign('userInfo', $userInfo);

        $orderId = Idworker::instance()->getId();
        $this->tpl->assign('orderId', $orderId);
        $this->tpl->assign('tkSn', $data['token']);
        $this->tpl->assign('returnUrl', 'storemanager://api?type=recharge');
        $this->tpl->assign('wxVersion', $this->getAppVersion());
        // 获取网贷大额充值账户名称
        $offlineChargeName = $this->rpc->local('SupervisionFinanceService\getOfflineChargeName', array());
        $this->tpl->assign('offlineChargeName', $offlineChargeName);
        // 记录日志
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $data['token'])));
        $this->template = $this->getTemplate('p2p_offline_charge');
        return true;
    }
}
