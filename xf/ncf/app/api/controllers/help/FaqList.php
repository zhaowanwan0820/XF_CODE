<?php
/**
 * FaqList.php
 *
 * @date 2014-04-11
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\help;


use libs\web\Form;
use core\service\article\ArticleService;
use api\controllers\AppBaseAction;

/**
 * 常见问题列表
 *
 * Class FaqList
 * @package api\controllers\help
 */
class FaqList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "cid" => array("filter" => "int", "message" => "cid is error"),
            "site_id" => array("filter" => "int", "message" => "site_id is error"),
            'token' => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['cid'])) {
            $this->setErr("ERR_PARAMS_ERROR", "cid is error");
            $this->return_error();
        }
    }

    public function invoke() {
        $cate_id = $this->form->data['cid'];
        $site_id = (!empty($this->form->data['site_id'])) ? $this->form->data['site_id'] : $this->defaultSiteId;
        $site = array_search($site_id,$GLOBALS['sys_config']['TEMPLATE_LIST']);

        $cate = (new ArticleService())->getArticleCate($cate_id);
        $list = (new ArticleService())->getArticleListByCateId($cate_id);

        // 分站文章字符替换
        if($site && $site_id != 1){
            $query_site = "&site_id={$site_id}";
            $firstp2p_title = $GLOBALS['sys_config']['SITE_LIST_TITLE']['firstp2p'];
            $firstp2p_domain = $GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p'];

            $site_title = $GLOBALS['sys_config']['SITE_LIST_TITLE'][$site];
            $site_domain = $GLOBALS['sys_config']['SITE_DOMAIN'][$site];
            if($cate){
                $cate['title'] = str_replace(array($firstp2p_title,$firstp2p_domain),array($site_title,$site_domain),$cate['title']);
            }
            if($list){
                foreach($list as &$value){
                    $value['title'] = str_replace(array($firstp2p_title,$firstp2p_domain),array($site_title,$site_domain),$value['title']);
                }
            }
        }else{
            $query_site = "";
        }

        $resultJson = array(
            'query_site' => $query_site,
            'cate' => $cate,
            'list' => $list,
            'token' => $this->form->data['token'],
            'site' => $_SERVER['HTTP_HOST'],
        );
        $this->json_data = $resultJson;
    }
}
