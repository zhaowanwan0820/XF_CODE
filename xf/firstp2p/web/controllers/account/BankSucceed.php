<?php

/**
 * Index.php
 *
 * @date 2014年4月8日14:52:33
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\utils\Finance;

class BankSucceed extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $user_info = $GLOBALS ['user_info'];
        $bonus = $this->rpc->local('BonusService\get_useable_money', array($user_info['id']));
        $user_info = $this->rpc->local('UserService\getUser', array($user_info['id']));
        $user_info['total_money'] = Finance::addition(array($user_info['money'], $bonus['money']), 2);
        $this->tpl->assign('user_info', $user_info);
        $this->tpl->assign('bonus', $bonus['money']);

        $this->template = 'web/views/account/bank_succeed.html';
        return;
    }

}
