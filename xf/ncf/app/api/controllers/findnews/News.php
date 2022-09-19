<?php
/**
 * 发现
 */
namespace api\controllers\findnews;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Curl;

class News extends AppBaseAction {

    protected $needAuth = false;

    private $tabs = array('keywords' => 1501, 'hotnews' => 1502, 'industry' => 1503);

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "tab" => array("filter" => "required", 'message' => 'tab cannot be null'),
            "page" => array("filter" => "int", 'option' => array('optional' => true)),
            "pagesize" => array("filter" => "int", 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $params = $this->form->data;

        $cateId = $params['tab'];
        if (!array_key_exists($cateId, $this->tabs)) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
        $page = $params['page'] > 1 ? $params['page'] : 1;
        $pageSize = $params['pagesize'] >= 1 ? $params['pagesize'] : 10;

        $list = $this->rpc->local('ArticleService\getArticleListByCateId', array($this->tabs[$cateId], false, true, $page, $pageSize, 'update_time', 'desc'), 'article');
        $results = array();

        foreach ($list as $article) {
            $result['id'] = $article['id'];
            $result['title'] = $article['title'];
            $result['content'] = $article['brief'] ?: '';
            $result['h5'] = sprintf("%s/found/article?id=%d", app_conf('NCFPH_WAP_URL'), $article['id']);
            $result['image'] = $article['image_url'];
            $result['date'] = date('Y-m-d H:i:s', ($article['update_time'] + date('Z')));
            $results[] = $result;
        }

        $this->json_data = $results;
    }
}

