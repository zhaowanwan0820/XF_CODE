<?php

namespace web\controllers\message;

use web\controllers\BaseAction;
use core\dao\msgbox\MsgBoxModel;


class Index extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $user_id = intval($GLOBALS['user_info']['id']);
        $list = \SiteApp::init()->dataCache->call(MsgBoxModel::instance(array('to_user_id' => $user_id)), 'getMsgBoxList', array($user_id, $this->is_firstp2p), 60);

        //取消息
        foreach($list as $k=>$v) {
            $list[$k] = \SiteApp::init()->dataCache->call(MsgBoxModel::instance(array('to_user_id' => $user_id)), 'getMsgBoxRow', array($v['group_key'],$user_id), 60);
            $list[$k]['total'] = $v['total'];
        }
        $mylist = array();
        //给消息分类
        foreach ($list as $key=>$val) {
            if($val['is_notice'] == 18) {             //投标完成
                $mylist['b'][] = $list[$key];
            } elseif ($val['is_notice'] == 16) {       //项目满标
                $mylist['c'][] = $list[$key];
            } elseif ($val['is_notice'] == 19) {        //投标放款
                $mylist['d'][] = $list[$key];
            } elseif ($val['is_notice'] == 9) {        //投标取消
                $mylist['e'][] = $list[$key];
            } elseif ($val['is_notice'] == 10 || $val['is_notice'] == 11) {    //项目回款
                $mylist['f'][] = $list[$key];
            } elseif ($val['is_notice'] == 20) {        //平台贴利率
                $mylist['g'][] = $list[$key];
            } elseif ($val['is_notice'] == 37) {        //多投宝
                $mylist['h'][] = $list[$key];
            } elseif ($val['is_notice'] == 55) {        //债权转让通知
                $mylist['i'][] = $list[$key];
            } else {                                    //系统消息
                $mylist['a'][] = $list[$key];
            }
        }
        ksort($mylist);
        $notice_title_config = array(
                'a'=>'系统消息',
                'b'=>'投标完成',
                'c'=>'项目满标',
                'd'=>'投标放款',
                'e'=>'项目回款',
                'f'=>'投标取消',
                'g'=>'平台返利率完成',
                'h'=>'智多新',
                'i'=>'债权转让',
        );
        if (!$this->is_firstp2p) {
            $systemNum = \SiteApp::init()->dataCache->call(MsgBoxModel::instance(array('to_user_id' => $user_id)), 'getuserMsgCount', array($user_id), 60);
            $mylist['a'][0]['total'] = $systemNum;
        } else {
            unset($mylist['a']);
        }
        $this->tpl->assign("msg_list",$mylist);
        $this->tpl->assign("notice_title", $notice_title_config);
        $this->tpl->assign("page_title",$GLOBALS['lang']['UC_NOTICE']);
        $this->tpl->assign("post_title",$GLOBALS['lang']['UC_NOTICE']);
    }
}
