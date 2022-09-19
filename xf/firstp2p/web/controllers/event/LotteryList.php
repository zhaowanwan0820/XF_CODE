<?php

/**
 * iphone6 活动用户清单页
 *
 * @author yutao <yutao@ucfgroup.com>
 */

namespace web\controllers\event;

use libs\web\Form;
use web\controllers\BaseAction;

class LotteryList extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'timestamp' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $time = trim($data['timestamp']);
        //$userList = $this->rpc->local('ActivityIphoneService\getIphoneUserList', array($time));
        $userList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ActivityIphoneService\getIphoneUserList', array($time)), 60);
        $lotteryDate = date('m月d日', $time);

        $this->tpl->assign('lotteryDate', $lotteryDate);
        $this->tpl->assign('userList', $userList);
        $this->tpl->display("web/views/event/lottery_list.html");
    }

}
