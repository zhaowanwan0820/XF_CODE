<?php
namespace web\controllers\en;
use web\controllers\BaseAction;

class ServiceGroups extends BaseAction {

  public function init()
  {
    parent::init();
  }
  
  public function invoke()
  {
    $this->tpl->assign("title", "Service Groups");

    $this->template = 'web/views/v3/en/service_groups.html';
  }
}