<?php

namespace web\controllers\enterprise;

use web\controllers\BaseAction;
use libs\web\Form;
use core\dao\EnterpriseRegisterModel;
use core\dao\EnterpriseModel;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

/**
 * 企业用户开户申请
 */
class ConfirmApply extends BaseAction {
    public function init() {
        $this->check_login();
    }

    public function invoke() {
        // 查询用户开户信息 如果有开户信息则返回
        $userId = $GLOBALS['user_info']['id'];
        $enterpriseAccountInfo = $this->rpc->local('EnterpriseService\getInfo', array($userId));
        $induCate = $enterpriseAccountInfo['base']['indu_cate'];
        $enterpriseAccountInfo['base']['indu_cate_dec'] = UserAccountEnum::$inducateTypes[$induCate];
        $legalbodyCredentialsType = $enterpriseAccountInfo['base']['legalbody_credentials_type'];
        $enterpriseAccountInfo['base']['legalbody_credentials_type_dec'] = $GLOBALS['dict']['ID_TYPE'][$legalbodyCredentialsType];
        // 开户行名称
        $bankList = $this->rpc->local('BankService\bankList', array());
        $bankId = $enterpriseAccountInfo['bank']['bank_id'];
        $enterpriseAccountInfo['bank']['bank_id_dec'] = '';
        foreach ($bankList as $item) {
            if ($item['id'] == $bankId) {
                $enterpriseAccountInfo['bank']['bank_id_dec'] = $item['name'];
                break;
            }
        }

        // 开户行所在地格式化
        $bankRegion2 = $enterpriseAccountInfo['bank']['region_lv2'];
        $bankRegion3 = $enterpriseAccountInfo['bank']['region_lv3'];
        $bankRegion4 = $enterpriseAccountInfo['bank']['region_lv4'];
        $regions2 = $this->rpc->local('DeliveryRegionService\getRegion', array($bankRegion2));
        $enterpriseAccountInfo['bank']['region_lv2_dec'] = $regions2->name;
        $regions3 = $this->rpc->local('DeliveryRegionService\getRegion', array($bankRegion3));
        $enterpriseAccountInfo['bank']['region_lv3_dec'] = $regions3->name;
        $regions4 = $this->rpc->local('DeliveryRegionService\getRegion', array($bankRegion4));
        $enterpriseAccountInfo['bank']['region_lv4_dec'] = $regions4->name;

        // 企业注册资金格式化
        $enterpriseAccountInfo['base']['reg_amt'] = bcdiv($enterpriseAccountInfo['base']['reg_amt'], 10000, 4);

        // 代理人证件类别
        $majorCondentialsType = $enterpriseAccountInfo['contact']['major_condentials_type'];
        $enterpriseAccountInfo['contact']['major_condentials_type_dec'] = $GLOBALS['dict']['ID_TYPE'][$majorCondentialsType];

        $credentials_type = UserAccountEnum::$inducateTypes;
        // 手机号码区号
        $this->tpl->assign('mobile_codes', $GLOBALS['dict']['MOBILE_CODE']);
        // 开户行名称列表
        $this->tpl->assign('bank_list', $bankList);
        // 法定代表人证件类别
        $this->tpl->assign('idTypes', $GLOBALS['dict']['ID_TYPE']);
        // 企业信息回显
        $this->tpl->assign('enterpriseInfo', $enterpriseAccountInfo);
        // 企业行业分类
        $this->tpl->assign('credentials_type', $credentials_type);
        $this->tpl->assign('user_purpose', $GLOBALS['user_info']['user_purpose']);
        $this->template = "web/views/v3/user/confirmcompany.html";
    }
}
