<?php
namespace web\controllers\en;
use web\controllers\BaseAction;

class RiskControl extends BaseAction {

  public function init()
  {
    parent::init();
  }
  
  public function invoke()
  {
    $this->tpl->assign("title", "Risk Control");
    $this->template = 'web/views/v3/en/risk_control.html';
  }
}