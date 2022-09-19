<?php
namespace web\controllers\en;
use web\controllers\BaseAction;

class CompanyProfile extends BaseAction {

  public function init()
  {
    parent::init();
  }
  
  public function invoke()
  {
    $this->tpl->assign("title", "CompanyProfile");
    $this->template = 'web/views/v3/en/company_profile.html';
  }
}