<?php
/**
 * 消息列表
 */
namespace api\controllers\message;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Index extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            //截止时间
            'endtime' => array('filter' => 'int'),
            //页码
            'page' => array('filter' => 'int'),
            //状态(可选)
            'status' => array('filter' => 'int'),
            //消息类型(可选)
            'type' => array('filter' => 'int'),
            //每页数量(可选)
            'pageSize' => array('filter' => 'int'),
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

        if (empty($data['pageSize'])) {
            $data['pageSize'] = 10;
        }

        $data['pageSize'] = $data['pageSize'] > 100 ? 100 : $data['pageSize'];

        if (empty($data['page'])) {
            $data['page'] = 1;
        }

        $result = $this->rpc->local('MsgBoxService\getAppMsgList', array($user['id'], $data['endtime'], $data['status'], $data['type'], $data['page'], $data['pageSize']));

        $this->json_data = $result;
    }

}
