<?php

/**
 * Index.php
 *
 * @date 2014-04-17
 * @author 杨庆 <yangqing@ucfgroup.com>
 */

namespace web\controllers\help;

use libs\web\Form;
use web\controllers\BaseAction;

class RegisterTerms extends BaseAction {

    public function init() {

    }

    public function invoke() {

        // help 没有首页

        //$adv = is_qiye_site() ? 'qy_regist_protocol' : (is_firstp2p() ? 'regist_protocol' : '客户端用户协议');
        $adv = is_qiye_site() ? 'qy_regist_protocol' : '客户端用户协议';

        $this->tpl->assign('adv', $adv);

        $this->template = 'web/views/help/register_terms.html';
    }

}
