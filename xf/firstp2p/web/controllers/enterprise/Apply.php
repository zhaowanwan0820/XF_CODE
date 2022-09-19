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
class Apply extends BaseAction {
    public function init() {
        $this->check_login();
    }

    public function invoke() {
        // 查询用户开户信息 如果有开户信息则返回
        $userId = $GLOBALS['user_info']['id'];
        $enterpriseAccountInfo = $this->rpc->local('EnterpriseService\getInfo', array($userId));
        if ($enterpriseAccountInfo['register']['verify_status'] != EnterpriseRegisterModel::VERIFY_STATUS_NO_INFO) {
            return app_redirect(url('enterprise/ConfirmApply'));
        }
        $bankList = $this->rpc->local('BankService\bankList', array());

        // 格式化参数
        $induCate = $enterpriseAccountInfo['base']['indu_cate'];
        $enterpriseAccountInfo['base']['indu_cate_dec'] = UserAccountEnum::$inducateTypes[$induCate];
        $credentials_type = UserAccountEnum::$inducateTypes;
        // 企业注册资金格式化
        $enterpriseAccountInfo['base']['reg_amt'] = bcdiv($enterpriseAccountInfo['base']['reg_amt'], 10000, 4);
        $legalbodyCredentialsType = $enterpriseAccountInfo['base']['legalbody_credentials_type'];
        $enterpriseAccountInfo['base']['legalbody_credentials_type_dec'] = $GLOBALS['dict']['ID_TYPE'][$legalbodyCredentialsType];
        $bankId = $enterpriseAccountInfo['bank']['bank_id'];
        $enterpriseAccountInfo['bank']['bank_id_dec'] = '';
        foreach ($bankList as $item) {
            if ($item['id'] == $bankId) {
                $enterpriseAccountInfo['bank']['bank_id_dec'] = $item['name'];
                break;
            }
        }

        $majorCredentialsType = $enterpriseAccountInfo['bank']['major_condentials_type'];
        $enterpriseAccountInfo['bank']['major_condentials_type_dec'] = $GLOBALS['dict']['ID_TYPE'][$majorCredentialsType];
        // 开户行所在地格式化
        $region2 = $enterpriseAccountInfo['bank']['region_lv2'];
        $regionInfo2 = $this->rpc->local('DeliveryRegionService\getRegion', array($region2));
        $enterpriseAccountInfo['bank']['region_lv2_dec'] =$regionInfo2->name;
        $region3 = $enterpriseAccountInfo['bank']['region_lv3'];
        $regionInfo3 = $this->rpc->local('DeliveryRegionService\getRegion', array($region3));
        $enterpriseAccountInfo['bank']['region_lv3_dec'] =$regionInfo3->name;
        $region4 = $enterpriseAccountInfo['bank']['region_lv4'];
        $regionInfo4 = $this->rpc->local('DeliveryRegionService\getRegion', array($region4));
        $enterpriseAccountInfo['bank']['region_lv4_dec'] =$regionInfo4->name;

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
        $this->template = "web/views/v3/user/applycompany.html";
    }
}
