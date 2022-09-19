<?php
namespace api\controllers\shortcuts;

use libs\web\Form;
use libs\utils\Logger;
use libs\utils\Monitor;
use api\controllers\AppBaseAction;


class UserShortcuts extends AppBaseAction
{


    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $userInfo = $this->getUserByToken(false);
        $stickShortcuts = $this->rpc->local("ShortcutsService\getStickShortcuts");
        if (!$userInfo) {
            $shortcuts = $this->rpc->local( "ShortcutsService\getMineShortcuts");
        }else{
            $shortcuts = $this->rpc->local( "ShortcutsService\getUserShortcuts", array('userId' => $userInfo['id']));
            if (!$shortcuts) {
                $shortcuts = $this->rpc->local( "ShortcutsService\getMineShortcuts");
            }
            $this->rpc->local( "ShortcutsService\dupRemove", array('userShortcuts' => &$shortcuts,'stickShortcuts' => $stickShortcuts));//去除重复快捷入口（用户登录情况下）
        }

        $result = $userInfo ? array_merge($stickShortcuts,$shortcuts) : $shortcuts;
        $this->json_data = $result;
        return true;
    }
}
