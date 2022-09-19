<?php

namespace web\controllers\account;

use web\controllers\BaseAction;

require_once dirname(__FILE__).'/../../../app/Lib/page.php';

class Invitedaward extends BaseAction
{
    public function init()
    {
        if (!$this->check_login()) {
            return false;
        }
    }

    public function invoke()
    {
        $this->template = 'web/views/account/invited_award.html';
    }
}
