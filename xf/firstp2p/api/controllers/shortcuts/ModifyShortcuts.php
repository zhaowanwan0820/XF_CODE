<?php
namespace api\controllers\shortcuts;

use libs\web\Form;
use libs\utils\Logger;
use libs\utils\Monitor;
use api\controllers\AppBaseAction;


class ModifyShortcuts extends AppBaseAction
{


    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'config' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if ( strcmp('clear_config', $data['config']) && empty($data['config'])) {
            return $this->setErr('ERR_PARAMS_ERROR',"配置ID错误");
        }
        $config = !strcmp('clear_config', $data['config']) ? '' : $data['config'];
        $result = $this->rpc->local("ShortcutsService\modifyUserShortcuts",array('userId' => $userInfo['id'], 'config' => $config));
        $this->json_data = $result;
        return true;
    }
}
