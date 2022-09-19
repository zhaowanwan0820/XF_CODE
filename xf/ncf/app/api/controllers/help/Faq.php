<?php
/**
 * Faq.php
 *
 * @date 2014-04-11
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\help;


use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\article\ArticleService;

/**
 * 常见问题答案详情
 *
 * Class Faq
 * @package api\controllers\help
 */
class Faq extends AppBaseAction {

    // 此接口不需要用户登录
    protected $needAuth = false;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter" => "int", "message" => "id is error"),
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return false;
        }
    }

    public function invoke() {
        $id = $this->form->data['id'];
        $site_id = (!empty($this->form->data['site_id'])) ? $this->form->data['site_id'] : $this->defaultSiteId;

        $article = (new ArticleService())->getArticle($id);
        if($article){
            if($site_id != '1'){
                $query_site = "&site_id={$site_id}";
                $site = array_search($site_id,$GLOBALS['sys_config']['TEMPLATE_LIST']);
                if(!empty($site)){
                    $firstp2p_title = $GLOBALS['sys_config']['SITE_LIST_TITLE']['firstp2p'];
                    $firstp2p_domain = $GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p'];

                    $site_title = $GLOBALS['sys_config']['SITE_LIST_TITLE'][$site];
                    $site_domain = $GLOBALS['sys_config']['SITE_DOMAIN'][$site];

                    $article['title'] = str_replace(array($firstp2p_title,$firstp2p_domain),array($site_title,$site_domain),$article['title']);
                    $article['content'] = str_replace(array($firstp2p_title,$firstp2p_domain),array($site_title,$site_domain),$article['content']);

                    //商户通 公众号替换
                    if($site_id==51){
                        $article['content'] = str_replace('Firstp2p', 'shtcapital' ,$article['content']);
                    }

                }
            } else {
                $query_site = "";
            }
        }
        $resultJson = array(
            'article' => $article,
            'query_site' => $query_site,
            'site' => $_SERVER['HTTP_HOST'],
        );
        $this->json_data = $resultJson;
    }
}
