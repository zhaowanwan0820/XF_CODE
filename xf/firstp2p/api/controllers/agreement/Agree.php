<?php
/**
 * 通用协议授权确认
 *
 * 用户各类授权协议的同意确认，通用模块
 *
 * 1.type:自定义业务名称，(userid,type)唯一索引，以支持各业务的协议签署需求
 * 2.调用AgreementService::check()判断是否已签署协议，如未签署则跳转协议模板
 * 3.协议模板签署按钮ajax调用Action: agreement/agree?type=candy&token=xxx, 完成签署
 *
 * @date 2018-07-14
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\agreement;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\AgreementService;

class Agree extends AppBaseAction{

    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
                'token' => array('filter' => 'required', 'message' => 'ERR_PARAMS_VERIFY_FAIL'),
                'type' => array('filter' => 'required', 'message' => 'ERR_PARAMS_VERIFY_FAIL'),
                );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $agreementService = new AgreementService();
        if (!$agreementService->agree($user['id'], $data['type'])) {
            $this->setErr('ERR_MANUAL_REASON',$res['errMsg']);
            return false;
        }
        $this->json_data = array();
    }

}
