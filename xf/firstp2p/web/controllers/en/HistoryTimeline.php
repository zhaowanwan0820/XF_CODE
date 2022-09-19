<?php
namespace web\controllers\en;
use web\controllers\BaseAction;

class HistoryTimeline extends BaseAction {

  public function init()
  {
    parent::init();
  }
  
  public function invoke()
  {
    $this->tpl->assign("title", "Milestone");
    $this->template = 'web/views/v3/en/history_timeline.html';
  }
}