<?php
namespace web\controllers\en;
use web\controllers\BaseAction;

class Honor extends BaseAction {

  public function init()
  {
    parent::init();
  }
  
  public function invoke()
  {
    $this->tpl->assign("title", "AWARDS");
    $this->template = 'web/views/v3/en/honor.html';
  }
}