<?php
namespace web\controllers\en;
use web\controllers\BaseAction;

class CompetitiveAdvantages extends BaseAction {

  public function init()
  {
    parent::init();
  }
  
  public function invoke()
  {
    $this->tpl->assign("title", "Competitive Advantages");
    $this->template = 'web/views/v3/en/competitive_advantages.html';
  }
}