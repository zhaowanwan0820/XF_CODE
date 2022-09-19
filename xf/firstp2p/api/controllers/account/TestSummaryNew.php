<?php

/**
 * 账户总览
 * */

namespace api\controllers\account;

use libs\utils\ABControl;
use libs\web\Form;
use api\controllers\BaseAction;
use libs\utils\Finance;
use libs\utils\Logger;
use NCFGroup\Protos\Gold\RequestCommon;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\service\UserService;
use core\service\life\UserTripService;
use core\dao\vip\VipAccountModel;
use core\service\BwlistService;

class TestSummaryNew extends BaseAction
{
    static $token = '6d298872e4506f8b822ca4a9c7a2abe9';
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => 'token不能为空'),
                'user_id' => array('filter' => 'required', 'message' => 'userid不能为空'),
                );
        $this->form->validate();

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        if ($data['token'] != self::$token) {
            $this->setErr('error token');
            return false;
        }
        //$info = $this->getUserByToken();
        $info = (new UserService())->getUserViaSlave($data['user_id']);
        if (empty($info)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            return false;
        } else {
            $user_info = $info;

            $user_statics = $this->rpc->local('AccountService\getUserSummary', array($user_info['id']));
            $p2p_user_statics = (new \core\service\ncfph\AccountService())->getSummary($user_info['id']);
            $user_statics = $this->mergeP2pData($user_statics, $p2p_user_statics);

            if (!app_conf('BONUS_DISCOUNT_MOMENTS_DISABLE')) {
                $bonus = $this->rpc->local('BonusService\get_useable_money', array($user_info['id']));
            } else {
                $bonus['money'] = 0;
            }

            $result['name'] = $user_info['real_name'] ? $user_info['real_name'] : "无";
            $result['mobile'] = $user_info['mobile'] ? moblieFormat($user_info['mobile'],$user_info['mobile_code']) : "无";

            $result['remain'] = format_price($user_info['money'], false);
            $result['frozen'] = format_price($user_info['lock_money'], false);
            $result['p2p_principal'] = $user_statics['p2p_principal'];

            //专享在投
            $result['zxCorpus'] = format_price(bcsub($user_statics['corpus'], $user_statics['cg_principal'], 2), false);

            $userAssetInfo = array();
            // 冻结中减掉智多鑫待投本金，资产中增加智多鑫待投本金
            $user_statics['corpus'] = bcadd($user_statics['corpus'], $user_statics['dt_norepay_principal'], 2);
            $user_info['lock_money'] = bcsub($user_info['lock_money'], $user_statics['dt_remain'], 2);

            $result['frozen'] = format_price($user_info['lock_money'], false);
            $result['corpus'] = format_price($user_statics['corpus'], false);
            $result['income'] = format_price($user_statics['income'], false);
            $result['earning_all'] = format_price($user_statics['earning_all'], false);
            $result['total'] = Finance::addition(array($user_info['money'], $user_info['lock_money'], $user_statics['corpus']), 2);
            $result['totalExt'] = Finance::addition(array($user_info['money'], $user_info['lock_money'], $user_statics['corpus']), 2);

            $userAssetInfo['corpus'] = $result['corpus'];
            $userAssetInfo['income'] = $result['income'];
            $userAssetInfo['earning_all'] = $result['earning_all'];
            $userAssetInfo['total'] = $result['total'];
            $userAssetInfo['totalExt'] = $result['totalExt'];

            $result['bonus'] = format_price($bonus['money'], false);

            //存管相关

            //专享总资产
            $result['wxAssets'] = format_price(bcsub($result['totalExt'], $user_statics['cg_principal'], 2), false);
            //专享在投
            $result['wxCorpus'] = format_price(bcsub($user_statics['corpus'], $user_statics['cg_principal'], 2), false);
            //p2p在投
            $result['svCorpus'] = format_price($user_statics['cg_principal'], false);
            //智多鑫在投
            $result['zdxCorpus'] = format_price($user_statics['dt_norepay_principal'], false);
            $result['svInfo'] = $this->rpc->local('SupervisionService\svInfo', array($user_info['id']));
            $result['svInfo']['isSvUser'] = $result['svInfo']['isSvUser'] ? 1:0;

            if ($result['svInfo']['isSvUser']) {
                $result['svBalance'] = $result['svInfo']['svBalance'] ?: '';
                $result['svFreeze'] = $result['svInfo']['svFreeze'] ?: '';
                $result['svMoney'] = $result['svInfo']['svMoney'] ?: '';
            }

            //p2p总资产
            $result['svAssets'] = Finance::addition(array($result['svInfo']['svMoney'], $user_statics['cg_principal']), 2);

            unset($result['svInfo']);
            $this->json_data = $result;
        }
    }

    private function mergeP2pData($wxData, $p2pData)
    {
        $fileds = [
            'corpus',
            'income',
            'earning_all',
            'compound_interest',
            'js_norepay_principal',
            'js_norepay_earnings',
            'js_total_earnings',
            'p2p_principal',
            'cg_principal',
            'cg_income',
            'cg_earnings',
            'dt_norepay_principal',
            'dt_load_money',
            'dt_remain'
        ];

        $data = [];
        foreach ($fileds as $filed) {
            $data[$filed] = bcadd($wxData[$filed], $p2pData[$filed], 2);
        }

        return $data;
    }
}
