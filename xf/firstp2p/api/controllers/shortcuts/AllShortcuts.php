<?php
namespace api\controllers\shortcuts;

use libs\web\Form;
use libs\utils\Logger;
use libs\utils\Monitor;
use api\controllers\AppBaseAction;


class AllShortcuts extends AppBaseAction
{


    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'type' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $shortcuts = $this->rpc->local("ShortcutsService\getAllShortcuts",array('type' => $data['type'],'version'=> $this->app_version));
        $this->json_data = !$shortcuts ? [] : $shortcuts;
        return true;
    }
}
