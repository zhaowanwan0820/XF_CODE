<?php

/**
 * 多投宝信息披露
 * Publish.php
 * @author wangchuanlu@ucfgroup.com
 */
namespace web\controllers\finplan;

use libs\web\Form;
use web\controllers\BaseAction;

class Publish extends BaseAction {
    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'page_num' => array('filter' => 'int'), // 第几页
            'page_size' => array('filter' => 'int'), //每页条数
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $pageNum = intval($data['page_num'])==0 ? 1 : intval($data['page_num']);
        $pageSize = intval($data['page_size'])==0 ? 10 : intval($data['page_size']);

        //强调网信普惠
        if(!$this->is_firstp2p){
            header(sprintf('location://%s%s', app_conf('FIRSTP2P_CN_DOMAIN'), $_SERVER['REQUEST_URI']));
            exit;
        }

        //没有登录，跳转到登录页面
        if(!$this->check_login()) {
            return false;
        }

        $user = $GLOBALS['user_info'];
        $userId = $user['id'];
        $res = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DtPublishService\getPublishP2pDeals', array($userId,$pageNum,$pageSize),'duotou'), 30);
        $p2pDeals = $res['list'] ;
        $canCancel = $this->rpc->local('DtPublishService\getCanCancelToday', array($userId),'duotou');

        $this->tpl->assign("canCancel", intval($canCancel));
        $this->tpl->assign("p2pDeals", $p2pDeals);
        $this->tpl->assign("pages", $res['totalPage']);
        $this->tpl->assign("totalNum", $res['totalNum']);
        $this->tpl->assign("current_page", ($pageNum == 0) ? 1 : $pageNum);
        $pageUrlStr = 'page_size='.$pageSize.'&page_num=';
        $this->tpl->assign("pagination",pagination(($pageNum == 0) ? 1 : $pageNum, ceil($res['totalNum'] / $pageSize), $pageSize, $pageUrlStr));

    }
}
