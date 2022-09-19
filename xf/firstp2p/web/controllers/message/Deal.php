<?php
/**
 * 消息
 * @author caolong<caolong@ucfgroup.com>
 **/

namespace web\controllers\message;

use web\controllers\BaseAction;
use core\dao\MsgBoxModel;
use libs\web\Form;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

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
        //$row = $this->rpc->local('MsgBoxService\getMsgByUserId', array($user_id, $group_key));
        $row = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('MsgBoxService\getMsgByUserId', array($user_id, $group_key)), 60);
        if(!empty($row['count'])) {
            //分页
            $page = intval($data['p']);
            if($page==0) {
                $page = 1;
            }
            //$r = $this->rpc->local('MsgBoxService\getMsgList', array($row['is_notice'], $row['system_msg_id'], $GLOBALS['user_info']['id'], $page));
            $r = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('MsgBoxService\getMsgList',  array($row['is_notice'], $row['system_msg_id'], $GLOBALS['user_info']['id'], $page)), 60);
            $list = $r['list'];
            //替换msg 中的绝对路径为相对路径
            foreach($list as &$item){
                $item['content'] = str_replace('http://www.firstp2p.com', '', $item['content']);
            }
            $total = $r['count'];
            $page = new \Page($total,app_conf("PAGE_SIZE"));   //初始化分页对象
            $p  =  $page->show();

            $this->rpc->local('MsgBoxService\updateMsgIsReadByUserIdAndSystemMsgId', array($row['is_notice'], $user_id, $row['system_msg_id']));
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
