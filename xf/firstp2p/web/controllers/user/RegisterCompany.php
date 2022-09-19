<?php

/**
 * 企业用户注册页面
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\H5UnionService;
use core\service\RegisterService;
use core\dao\EnterpriseRegisterModel;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;

class RegisterCompany extends BaseAction {
    public function init()
    {
        $this->form = new Form('get');
        $this->form->rules = array(
            'is_apply' => array('filter' => 'int'),
        );

        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 1, 1);
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userId = $GLOBALS['user_info']['id'];
        // 是否是从开户页跳转过来，给前端区分
        $isAccount = empty($data['is_apply']) ? 0 : 1;
        // 获取企业用户开户等的模版
        $enterpriseTemplateListJson = app_conf('ENTERPRISE_TEMPLATE_LIST');
        if (!empty($enterpriseTemplateListJson)) {
            $enterpriseTemplateList = json_decode($enterpriseTemplateListJson, true);
            if (!empty($enterpriseTemplateList)) {
                foreach ($enterpriseTemplateList as $item) {
                    if (empty($item['tplName']) || empty($item['tplUrl'])) {
                        continue;
                    }
                    $tplUrl = PRE_HTTP . ltrim(app_conf('STATIC_HOST'), '//') . $item['tplUrl'];
                    $this->tpl->assign('templateUrl_' . $item['tplName'], $tplUrl);
                }
            }
        }
        // is_apply 用来标识本接口的调用者  1 开户成功 0或者不存在 注册成功
        if (isset($data['is_apply']) && !empty($data['is_apply'])) {
            // 更新用户开户信息审核状态
            $param = ['verify_status' => EnterpriseRegisterModel::VERIFY_STATUS_HAS_INFO];
            $resultEditStatus = $this->rpc->local('EnterpriseService\updateRegisterByUid', array($userId, $param));
        }

        $isFinance = $GLOBALS['user_info']['user_purpose'] == UserAccountEnum::ACCOUNT_FINANCE ? true : false;
        $this->tpl->assign('isFinance', $isFinance);
        $this->tpl->assign('isAccount', $isAccount);
        $this->template = 'web/views/v3/user/registercompany_suc.html';
    }
}
