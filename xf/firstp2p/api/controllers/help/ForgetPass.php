<?php

/**
 * ForgetPass.php
 *
 * @date 2014-04-11
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\help;

use libs\web\Form;

class ForgetPass extends Faq {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->return_error();
        }
    }

    public function invoke() {
        // 获取基类
//        $grandParent = self::getRoot();
//        $grandParent::init();
//        return app_redirect(url("help", "faq/" . app_conf('APP_HELP_ID_FORGET_PASS') . "?site_id=" . $site_id));
        $id = app_conf('APP_HELP_ID_FORGET_PASS');
        $site_id = (!empty($this->form->data['site_id'])) ? $this->form->data['site_id'] : 1;
        $article = $this->rpc->local('ArticleService\getArticle', array($id));
        if ($article) {
            if ($site_id != '1') {
                $query_site = "&site_id={$site_id}";
                $site = array_search($site_id, $GLOBALS['sys_config']['TEMPLATE_LIST']);
                if (!empty($site)) {
                    $firstp2p_title = $GLOBALS['sys_config']['SITE_LIST_TITLE']['firstp2p'];
                    $firstp2p_domain = $GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p'];

                    $site_title = $GLOBALS['sys_config']['SITE_LIST_TITLE'][$site];
                    $site_domain = $GLOBALS['sys_config']['SITE_DOMAIN'][$site];

                    $article['title'] = str_replace(array($firstp2p_title, $firstp2p_domain), array($site_title, $site_domain), $article['title']);
                    $article['content'] = str_replace(array($firstp2p_title, $firstp2p_domain), array($site_title, $site_domain), $article['content']);
                }
            } else {
                $query_site = "";
            }
        }
        $this->tpl->assign("article", $article);
        $this->tpl->assign("query_site", $query_site);
        $this->tpl->display("api/views/_v10/help/faq.html");
    }

}
