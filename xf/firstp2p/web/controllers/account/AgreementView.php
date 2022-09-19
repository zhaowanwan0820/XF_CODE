<?php

/**
 * agreementView.php
 * PC 授权协议查看列表页面
 *
 * @date 2018-01-03
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
class AgreementView extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $article = \core\dao\ArticleModel::instance()->getArticleById(intval($_GET['id']));
        $this->tpl->assign('agreement', $article);
        $this->tpl->assign('inc_file', 'web/views/v3/account/agreementview.html');
        $this->template = "web/views/account/frame.html";
    }

}
