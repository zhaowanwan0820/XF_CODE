<?php
namespace api\controllers\payment;

/**
 * 跳转到银行页面 
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Transit extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required'),
            'site_id' => array('filter' => 'int'),
            'params' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL');
            $this->tpl->assign('error', $this->error);
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            $this->tpl->assign('error', $this->error);
            return false;
        }
        $site_id = $data['site_id'];
        $params = isset($data['params']) ? json_decode(stripslashes($data['params']), true) : [];
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $user['id'], 'data:' . var_export($data, true), 'Params:' . var_export($params, true))));
        if (empty($params['srv'])) {
            $this->setErr(-1, '缺少srv服务');
            $this->tpl->assign('error', $this->error);
            return false;
        }

        // 初始化普惠账户信息
        $accountId = \core\service\ncfph\AccountService::initAccount($user['id'], $user['user_purpose']);
        if (!$accountId) {
            $this->setErr(-1, '初始化账户失败');
            $this->tpl->assign('error', $this->error);
            return false;
        }

        $params = $this->rpc->local('SupervisionService\changeSrv', array($params, $user['id']));

        $srv = trim($params['srv']);
        unset($params['srv']);
        $params['mobileType'] = isset($params['mobileType']) ? intval($params['mobileType']) : 21;
        //$params['mobileType'] = '1' . $this->getOs();
        try {
            $result = $this->rpc->local(
                'SupervisionService\formFactory',
                array($srv, $accountId, $params, 'h5')
            );
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $user['id'], $accountId, 'Error:'.$e->getMessage())));
            $result = array();
        }

        if (empty($result['status'])) {
            $result['status'] = 0;
        }
        $res = array();
        $res['status'] = $result['status'];
        if ($result['status']) {
            $res['form'] = $result['form'];
            $res['formId'] = $result['formId'];
        } else {
            $msg = '网络错误，请重试';
            $res['msg'] = $msg;
        }
        $this->json_data = $res;
    }

}
