<?php
/**
 * @des 获取用户四要素
 */

namespace openapi\controllers\user;

use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\lib\Tools;
use core\service\partner\RequestService;
use core\service\UserTagService;

class GetFourElement extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "open_id" => array("filter" => "required", "message" => "open_id is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke()
    {
        $userObj = $this->getUserByAccessToken(true);
        $openId = Tools::getOpenID($userObj->userId);

        try {
            $post = ['open_id' => $userObj->userId];
            $xhRes = RequestService::init('xianghua')
                ->setApi('user.auth')
                ->setPost($post)
                ->request();
        } catch (\Exception $e) {
            $this->setErr('ERR_MANUAL_REASON', $e->getMessage());
            return false;
        }
        if (empty($xhRes['data']['auth_status'])) {
            $this->setErr('ERR_MANUAL_REASON', '该用户未授权');
            return false;
        }
        (new UserTagService())->addUserTagsByConstName($userObj->userId, 'FROM_XIANGHUA');

        $userInfo['open_id'] = $openId;
        $userInfo['mobile'] = $userObj->mobile;
        $userInfo['real_name'] = $userObj->realName;
        $userInfo['idno'] = $userObj->idno;
        $userInfo['bankcard'] = $userObj->bankNo;
        $userInfo['bank_name'] = $userObj->bank;
        $userInfo['verify_type'] = $userObj->cardVerify;
        $this->json_data = $userInfo;
    }

}
