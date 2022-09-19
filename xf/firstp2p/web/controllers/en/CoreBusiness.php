<?php
namespace web\controllers\en;
use web\controllers\BaseAction;

class CoreBusiness extends BaseAction {

  public function init()
  {
    parent::init();
  }
  
  public function invoke()
  {
    $this->tpl->assign("title", "Core Business");
    $this->template = 'web/views/v3/en/core_business.html';
  }
}