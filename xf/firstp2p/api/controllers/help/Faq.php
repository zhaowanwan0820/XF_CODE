<?php
/**
 * Faq.php
 *
 * @date 2014-04-11
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\help;


use api\controllers\BaseAction;
use libs\web\Form;

/**
 * 常见问题答案详情
 *
 * Class Faq
 * @package api\controllers\help
 */
class Faq extends FaqIndex {

    const IS_H5 = true;
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "id" => array("filter" => "int", "message" => "id is error"),
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
            "is_useful" => array("filter" => "int", 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->return_error();
        }
        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            $this->return_error();
        }
    }

    public function invoke() {
        $id = $this->form->data['id'];
        $site_id = (!empty($this->form->data['site_id']))?$this->form->data['site_id']:1;

        if (isset($this->form->data['is_useful'])) {
            $isUseful = $this->form->data['is_useful'];
            if ($isUseful == 1) {
                $field = 'useful_count';
            } elseif ($isUseful == 0) {
                $field = 'useless_count';
            } else {
                return false;
            }
            $res = $this->rpc->local('ArticleService\increaseCount', array($id, $field));
            return $res;
        }

        $article = $this->rpc->local('ArticleService\getArticle', array($id));
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
        $this->tpl->assign("article", $article);
        $this->tpl->assign("query_site", $query_site);
    }
}
