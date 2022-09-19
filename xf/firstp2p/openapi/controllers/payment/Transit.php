<?php
namespace openapi\controllers\payment;

/**
 * 跳转到银行页面
 * @author longbo
 */
use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Logger;

class Transit extends BaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required'),
            'params' => array('filter' => 'string'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByAccessToken();
        if (empty($user)) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        $params = isset($data['params']) ? json_decode(urldecode($data['params']), true) : [];
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $user->userId, 'data:' . var_export($data, true), 'Params:' . var_export($params, true))));
        if (empty($params['srv'])) {
            $this->setErr('ERR_SYSTEM', '缺少srv服务');
            return false;
        }
        $srv = trim($params['srv']);
        unset($params['srv']);
        $params['mobileType'] = isset($params['mobileType']) ? intval($params['mobileType']) : 21;
        try {
            // 初始化普惠账户信息
            $accountId = \core\service\ncfph\AccountService::initAccount($user->userId, $user->userPurpose);
            if (!$accountId) {
                throw new \Exception('初始化账户失败');
            }

            $result = $this->rpc->local(
                'SupervisionService\formFactory',
                array($srv, $accountId, $params, 'h5')
            );
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $user->userId, $accountId, 'Error:'.$e->getMessage())));
            $this->setErr("ERR_SYSTEM", $e->getMessage());
            return false;
        }

        if (empty($result['status'])) {
            $this->setErr('ERR_SYSTEM', $result['msg']);
            return false;
        }

        $this->json_data = $result;
        return true;
    }
}