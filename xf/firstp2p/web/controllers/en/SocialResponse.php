<?php
namespace web\controllers\en;
use web\controllers\BaseAction;

class SocialResponse extends BaseAction {

  public function init()
  {
    parent::init();
  }
  
  public function invoke()
  {
    $this->tpl->assign("title", "SOCIAL RESPONSIBILITY");

    $this->template = 'web/views/v3/en/social_response.html';
  }
}