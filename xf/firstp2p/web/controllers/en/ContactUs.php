<?php
namespace web\controllers\en;
use web\controllers\BaseAction;

class ContactUs extends BaseAction {

  public function init()
  {
    parent::init();
  }
  
  public function invoke()
  {
    $this->tpl->assign("title", "Contact Us");
    $this->template = 'web/views/v3/en/contact_us.html';
  }
}