<?php
/**
 * 用户推送配置设置
 */
namespace api\controllers\message;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class ConfigSet extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            //消息类型，多个用逗号隔开
            'type' => array('filter' => 'required', 'message' => 'type不能为空'),
            //状态(0, 1)，多个用逗号隔开
            'status' => array('filter' => 'required', 'message' => 'status不能为空'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->user;

        $typeArray = explode(',', $data['type']);
        $statusArray = explode(',', $data['status']);
        if (count($typeArray) === 0 || count($typeArray) !== count($statusArray)) {
            return $this->setErr('ERR_SYSTEM', '参数不匹配');
        }

        $switches = array();
        foreach ($typeArray as $key => $value) {
            $switches[$value] = $statusArray[$key];
        }

        if (!$this->rpc->local('MsgBoxService\userConfigSet', array($user['id'], $switches))) {
            return $this->setErr('ERR_SYSTEM', '数据保存失败');
        }

        return $this->json_data = array();
    }

}
