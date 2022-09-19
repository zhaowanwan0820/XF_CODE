<?php
namespace openapi\controllers\data;

use libs\web\Form;
use openapi\controllers\DataBaseAction;
use NCFGroup\Protos\Ptp\RequestUser;
use NCFGroup\Protos\Ptp\RPCErrorCode;

/**
 * @abstract 获取用户信息
 * @author longbo
 */
class UserInfo extends DataBaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "userXid" => array("filter" => "required", "message" => "userXid is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userXid = trim($data['userXid']);
        $userId = $this->decodeId($userXid);
        if (empty($userId)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $request = new RequestUser();
        $request->setUserId(intval($userId));
        $request->setIsDesensitize(0);
        $userResponse = $GLOBALS['rpc']->callByObject(array(
                'service' => 'NCFGroup\Ptp\services\PtpUser',
                'method' => 'getUserById',
                'args' => $request
            ));
        if ($userResponse->resCode === RPCErrorCode::SUCCESS) {
            $userInfo = $userResponse;
        }
        $basicInfo = $userInfo->toArray();
        $userService = new \core\service\UserService();
        $userService->getBankCodeByUid($userInfo->userId);
        $basicInfo['bankCode'] = $userService->getBankCodeByUid($userInfo->userId);
        $this->json_data = $basicInfo;
        return true;
    }

}
