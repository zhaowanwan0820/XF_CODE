<?php
/**
 * 用户短信发送设置
 * @author longbo
 */
namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class SetSmsConfig extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'type_id' => array('filter' => 'required', 'message' => 'type不能为空'),
            'status' => array('filter' => 'required', 'message' => 'status不能为空'),
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
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        $type = explode(',', $data['type_id']);
        $status = explode(',', $data['status']);
        if (count($type) != count($status)) {
            $this->setErr('ERR_SYSTEM', '数据不整齐');
            return false;
        }

        $config_data = array();
        array_map(function ($v1, $v2) use (&$config_data) {
            $config_data[$v1] = strval($v2);
        }, $type, $status);

        if (!empty($config_data)) {
            $ret = $this->rpc->local('MsgConfigService\setSwitches',
                        array($user['id'], 'sms_switches', $config_data)
                    );
            if (empty($ret)) {
                Logger::info("SET_SMS_CONFIG_FAILED:".json_encode($config_data));
                $this->setErr('ERR_SYSTEM', '数据设置失败');
                return false;
            }
        } else {
            $this->setErr('ERR_SYSTEM', '数据不正确');
            return false;
        }
        $this->json_data = array();
        return;
    }

}
