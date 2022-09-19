<?php

/**
 * Index.php
 *
 * @date 2014-05-07
 * @author xiaoan 
 */

namespace web\controllers\guide;

use libs\web\Form;
use web\controllers\BaseAction;

class Index extends BaseAction {

    public function init() {
        
    }

    public function invoke() {
                
        if ($_REQUEST['action'] == 'introduction') {
            $page_id = 'product';
        } elseif ($_REQUEST['action'] == 'operation') {
            $page_id = 'operation';
        } else {
        	$page_id = 'aboutp2p';
        }
        
        $this->tpl->assign("url_aboutp2p", PRE_HTTP.APP_HOST."/guide");
        $this->tpl->assign("url_product", PRE_HTTP.APP_HOST."/guide/introduction");
        $this->tpl->assign("url_operation", PRE_HTTP.APP_HOST."/guide/operation");
        $this->tpl->assign("url_deal", PRE_HTTP.APP_HOST."/?from=$page_id");

        //SEO信息
        $this->tpl->assign('page_title', '【网信理财】投资用户新手操作指南,P2P产品介绍-Firstp2p.com');
        $this->tpl->assign('page_keyword', '网信理财,firstp2p,P2P产品,房贷,车贷,产融贷');
        $this->tpl->assign('page_description', '网信理财的投资标的均经过专业风控团队实地调查和严格审核，并由国内知名融资性担保公司或大型企业集团为投资人本金及收益提供全额担保，旨在为投资人提供低门槛、高收益的投资选择。');
        $this->tpl->assign('site_info', array('SHOP_TITLE' => '', 'SHOP_KEYWORD' => '', 'SHOP_DESCRIPTION' => ''));
        $this->tpl->display("web/views/guide/{$page_id}.html");
    }

}
