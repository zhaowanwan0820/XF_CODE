<?php
/**
 * 读取用户短信设置
 * @author longbo
 */
namespace api\controllers\account;

use api\controllers\AppBaseAction;
use libs\web\Form;
use libs\utils\Logger;
use core\service\MsgConfigService;

class GetSmsConfig extends AppBaseAction
{

    public static $static_config = array(
                '密码变更',
                '手机号变更',
                '银行卡变更',
                '提现申请',
            );
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
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
        $msgConfig = new MsgConfigService();
        $sms_config = $msgConfig::$sms_config;

        $user_config = $this->rpc->local('MsgConfigService\getUserConfig', array($user['id'], 'sms_switches'));
        $new_config = array();
        foreach ($sms_config as $config_v) {
            if (empty($config_v)) {
                continue;
            }
            foreach ($config_v as $key => $con_v) {
                $new_conf['type_id'] = strval($key);
                $new_conf['title'] = $con_v;
                $new_conf['status'] = isset($user_config[$key]) ? $user_config[$key] :$msgConfig::$default_checked;
                $new_config[] = $new_conf;
            }
        }

        /*if (!empty($user_config)) {
            $new_config = array_map(function ($v) use ($user_config) {
                    $v['status'] = $user_config[$v['type_id']];
                    return $v;
                }, $new_config);
        }*/
        $res['options'] = array_values($new_config);
        $res['static_conf'] = self::$static_config;
        $this->json_data = $res;
    }

}
