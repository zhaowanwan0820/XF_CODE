<?php
namespace web\controllers\en;
use web\controllers\BaseAction;

class FinanceProcess extends BaseAction {

  public function init()
  {
    parent::init();
  }
  
  public function invoke()
  {
    $this->tpl->assign("title", "Finance Process");
    $this->template = 'web/views/v3/en/finance_process.html';
  }
}