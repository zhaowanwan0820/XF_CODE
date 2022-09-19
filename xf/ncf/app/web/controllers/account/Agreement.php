<?php
/**
 * Agreement.php
 * PC 授权协议列表页面
 *
 * @date 2018-01-03
 */
namespace web\controllers\account;

use web\controllers\BaseAction;
use core\dao\article\ArticleCateModel;
use core\dao\article\ArticleModel;
use core\enum\ArticleCateEnum;

class Agreement extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        $articleList = ArticleModel::instance()->getListByCateId(ArticleCateModel::instance()->getPlatformAgreementCateId(ArticleCateEnum::PLATFORM_WXPC));
        $agreements = [];
        foreach ($articleList as $article ) {
            $agreement = $article->getRow();
            $agreement['viewUrl'] = '/account/agreementView?id='.$agreement['id'];
            $agreements[] = $agreement;
        }
        $this->tpl->assign('agreements', $agreements);
        $this->tpl->assign('inc_file', 'web/views/account/agreement.html');
        $this->template = "web/views/account/frame.html";
    }
}