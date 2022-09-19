<?php

class CrawlerRateAction extends CommonAction{

    public function index()
    {
        if(trim($_REQUEST['ratetime'])!='')
        {
            $condition['ratetime'] = $_REQUEST['ratetime'];
            $this->assign("ratetime",$_REQUEST['ratetime']);		
        }
        $this->assign("default_map",$condition);
        parent::index();
    }
}	