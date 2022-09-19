<?php
namespace web\controllers\activity;

use web\controllers\BaseAction;
use libs\web\Form;
use NCFGroup\Common\Library\ApiService;

class SpringFestival2019Like extends BaseAction
{

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = [
            'c' => ['filter' => 'string', 'option' => ['optional' => true]],
            'token' => ['filter' => 'string', 'option' => ['optional' => true]],
        ];
        if (!$this->form->validate()) {
            echo json_encode(['code' => 10000, 'message' => $this->form->getErrorMsg()]);
            return false;
        }
    }

    public function invoke()
    {
        $params = $this->form->data;

        $code = $params['c'] ?: '';

        $token = $params['token'];
        if (!empty($token)) {
            $token_info = $this->rpc->local('UserService\getUserByCode', [$token]);
            if (empty($token_info['code'])) {
                $GLOBALS['user_info'] = $token_info['user'];
            }
        }

        $currentUid = $GLOBALS['user_info']['id'] ?: 0;

        $data = ApiService::rpc('marketing', 'DealAssistance/like', [
            'code' => $code,
            'currentUid' => $currentUid,
        ]);
        if (ApiService::hasError()) {
            $errorData = ApiService::getErrorData();
            echo json_encode(['code' => $errorData['applicationCode'], 'msg' => $errorData['devMessage']]);
            return false;
        }
        echo json_encode(['code' => 0, 'msg' => 'ok', 'data' => $data]);
    }

}
