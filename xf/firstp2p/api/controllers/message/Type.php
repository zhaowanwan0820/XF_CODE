<?php
/**
 * 消息类型列表
 */
namespace api\controllers\message;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

class Type extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'wxb' => array('filter' => 'string', 'option' => array('optional' => true)),
            'isNewVersion' => array('filter' => 'int', 'option' => array('optional' => true)),//新版本标识
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

        $showWxb = isset($data['wxb']) && $data['wxb'] == 'true' ? true : false ;
        if(isset($data['isNewVersion']) && intval($data['isNewVersion']) == 1){
            $result['typeList'] = $this->rpc->local('MsgBoxService\getStructAppTypeList', array($showWxb));
            $result['text'] = '仅显示最近3个月的消息';
        } else{
            $result = $this->rpc->local('MsgBoxService\getAppTypeList', array());
            if ($showWxb) {
                foreach ($result as $key => $value) {
                    if ($value['type'] == 30) {
                        $result[$key]['title'] = app_conf('NEW_BONUS_TITLE');
                        break;
                    }
                }
            }
        }
        return $this->json_data = $result;
    }

}
