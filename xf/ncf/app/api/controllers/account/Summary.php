<?php

/**
 * 账户总览
 * @author wenyanlei@ucfgroup.com
 */

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\ABControl;
use libs\utils\Finance;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\service\user\BankService;
use core\service\supervision\SupervisionFinanceService;

class Summary extends AppBaseAction {
    public function init() {
        parent::init();

        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_GET_USER_FAIL'
            ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true)
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $site_id = isset($data['site_id']) ? $data['site_id'] : $this->defaultSiteId;
        $user_info = $this->user;

        // 这里需要完善普惠的数据获取逻辑
        $result = UserService::accountSummary($user_info['id'], $site_id);
        if ($result === false) {
            $this->setErr(UserService::getErrorData(), UserService::getErrorMsg());
            return false;
        }

        $bankcard = [];
        $accountId = AccountService::initAccount($user_info['id'], $user_info['user_purpose']);
        $result['svInfo'] = $this->rpc->local('SupervisionService\svInfo', array($accountId), 'supervision');
        if (!empty($result['svInfo']['isSvUser']) && $result['svInfo']['isActivated'] != 0) {
            $result['svUrl'] = sprintf(
                $this->getHost() . "/payment/Transit?params=%s",
                urlencode(json_encode(['srv' => 'info', 'return_url' => 'storemanager://api?type=closecgpages']))
            );
        } else {
            // 获取用户绑卡数据
            $bankcard = BankService::getNewCardByUserId($user_info['id']);
            // 普惠未绑卡用户去标准开户
            $srv = (empty($bankcard) && $site_id == $this->defaultSiteId) ? 'registerStandard' : 'register';
            $result['svUrl'] = sprintf(
                $this->getHost() . "/payment/Transit?params=%s",
                urlencode(json_encode(['srv' => $srv, 'return_url' => 'storemanager://api?type=closecgpages']))
            );
        }

        $result['wxUrl'] = sprintf(
            $this->getHost() . "/payment/Transit?params=%s",
            urlencode(json_encode(['srv' => 'superInfo', 'return_url' => 'storemanager://api?type=closecgpages']))
        );

        $result['isWxFreepayment'] = 1;

        $accont = AccountService::getAccountMoney($user_info['id'], $user_info['user_purpose']);
        $user_statics = AccountService::getUserSummary($user_info['id']);
        $result['p2p_principal'] = $user_statics['p2p_principal'];

        // 冻结中减掉智多鑫待投本金，资产中增加智多鑫待投本金
        $user_statics['corpus'] = bcadd($user_statics['corpus'], $user_statics['dt_norepay_principal'], 2);
        $accont['lockMoney'] = bcsub($accont['lockMoney'], $user_statics['dt_remain'], 2);

        $result['total'] = Finance::addition(array($accont['totalMoney'], $user_statics['corpus']), 2);
        $result['totalExt'] = Finance::addition(array($accont['totalMoney'], $user_statics['corpus']), 2);
        $result['frozen'] = format_price($accont['lockMoney'], false);
        $result['corpus'] = format_price($user_statics['corpus'], false);
        $result['income'] = format_price($user_statics['income'], false);
        $result['earning_all'] = format_price($user_statics['earning_all'], false);

        $userAssetInfo = array();
        $userAssetInfo['corpus'] = $result['corpus'];
        $userAssetInfo['income'] = $result['income'];
        $userAssetInfo['earning_all'] = $result['earning_all'];
        $userAssetInfo['total'] = $result['total'];
        $userAssetInfo['totalExt'] = $result['totalExt'];

        // 判断是否显示多投数据
        $result['isDuotou'] = 1;
        if (app_conf('DUOTOU_SWITCH') == '0' || !is_duotou_inner_user()) {
            $result['isDuotou'] = 0;
        }

        $result['remain'] = format_price($accont['money'], false);
        $result['frozen'] = format_price($accont['lockMoney'], false);
        // } END

        // 是否使用新h5充值
        $result['useH5Charge'] = intval(app_conf('APP_USE_H5_CHARGE')) == 0 ? intval(ABControl::getInstance()->hit('useH5Charge')) : 1;
        //网贷大额充值地址
        $supervisionFinanceObj = new SupervisionFinanceService();
        $result['p2pOfflineChargeUrl'] = $supervisionFinanceObj->getOfflineChargeApiUrl($user_info['id'], $bankcard);

        //账户授权管理开关
        $result['accountAuthManageSwitch'] = (int) app_conf('ACCOUNT_AUTH_MANAGE_SWITCH');

        //p2p在投
        $result['svCorpus'] = format_price($user_statics['cg_principal'], false);
        //p2p总资产
        $result['svAssets'] = Finance::addition(array($result['svInfo']['svMoney'], $user_statics['cg_principal']), 2);
        $result['svAssets'] = Finance::addition(array($result['svAssets'], $user_statics['dt_load_money']), 2);
        //智多鑫在投
        $result['zdxCorpus'] = format_price($user_statics['dt_norepay_principal'], false);

        // 判断用户是否首投
        $result['isBid'] = false;
        // 是否可使用红包
        $result['canUseBonus'] = $user_info['canUseBonus'];
        // 红包禁用开关
        $result['bonusDisabled'] = \core\service\bonus\BonusService::isBonusEnable() ? 0 : 1;
        $this->json_data = $result;
    }
}
