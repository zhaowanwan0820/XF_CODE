<?php

namespace web\controllers\article;

use core\service\OpenService;
use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;

//文章调用标签
class Articletag extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            "type" => array("filter" => "int", 'optional' => true),
            "cnt" => array("filter" => "int", 'optional' => false),
            "title" => array("filter" => "int", 'optional' => false),
         );

        if (!$this->form->validate()) {
            return app_redirect(url('index', 'index'));
        }
    }

    public function invoke() {
        if (empty($this->appInfo)) {
            return app_redirect(url('index', 'index'));
        }

        $data  = $this->form->data;
        $type = intval($data['type']);
        $cnt = intval($data['cnt']);
        $title = intval($data['title']);
        //默认10条
        $cnt <= 0 ? $cnt = 10 : $cnt;
        $title <= 0 ? $title = 50 : $title;

        $appId = $this->appInfo['id'];
        $srv = new OpenService();
        $response = $srv->getArticletag($appId, $type, $cnt, $title);
        if($response == false){
            echo json_encode(array('errorCode' => 1, 'errorMsg' => '系统繁忙，请稍后再试', 'data' => ''), JSON_UNESCAPED_UNICODE);
        }
        $article = $response->data;
        echo json_encode(array('errorCode' => 0, 'errorMsg' => '', 'data' => $article), JSON_UNESCAPED_UNICODE);
    }

}
