<?php

namespace web\controllers\message;

use web\controllers\BaseAction;
use libs\web\Form;
use core\enum\MsgBoxEnum;

class Deal extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("get");
        $this->form->rules = array(
                "id" => array("filter"=>"string"),
                "p" => array("filter"=>"int"),
        );
        if(!$this->form->validate()) {
            return app_redirect(url("index",'index'));
        }
    }
    
   
    public function invoke() {
        
        $data = $this->form->data;
        $group_key = addslashes(trim($data['id']));
        $user_id = intval($GLOBALS['user_info']['id']);
        //网信普惠只显示债权转让类型
        if ($this->is_firstp2p && $group_key != "0_{$user_id}_" . MsgBoxEnum::TYPE_DUOTOU_LOAN_USER_CHANGED) {
            return;
        }
        $row = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('MsgboxService\getMsgByUserId', array($user_id, $group_key),'msgbox'), 60);
        if(!empty($row['count'])) {
            //分页
            $page = intval($data['p']);
            if($page==0) {
                $page = 1;
            }
            $r = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('MsgboxService\getMsgList',  array($row['is_notice'], $row['system_msg_id'], $GLOBALS['user_info']['id'], $page),'msgbox'), 60);
            $list = $r['list'];
            //替换msg 中的绝对路径为相对路径
            foreach($list as &$item){
                $item['content'] = str_replace('http://www.firstp2p.com', '', $item['content']);
            }
            $total = $r['count'];
            $page = new \libs\utils\Page($total,app_conf("PAGE_SIZE"));   //初始化分页对象
            $p  =  $page->show();

            $this->rpc->local('MsgboxService\updateMsgIsReadByUserIdAndSystemMsgId', array($row['is_notice'], $user_id, $row['system_msg_id']),'msgbox');
            $this->tpl->assign("notice_title", $GLOBALS['dict']['MSG_NOTICE_TITLE']);
            $this->tpl->assign("list",$list);
            $this->tpl->assign("page_title",$GLOBALS['lang']['SYSTEM_PM']);
            $this->tpl->assign('pages',$p);
        }
//        else{ //指到首页
//            
//            return app_redirect(url("index",'index'));
//        }
    }
}
