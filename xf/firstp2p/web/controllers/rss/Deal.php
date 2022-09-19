<?php
/**
 * Index.php
 * 新闻类rss基本 控制器入口
 * 
 * @date 2014-04-01
 * @author yangqing <yangqing@ucfgroup.com>
 */

namespace web\controllers\rss;

use libs\web\Form;
use web\controllers\BaseAction;

class Deal extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return false;
        }
    }

    public function invoke() {
        $id = $this->form->data['id'];
        //$deal = $this->rpc->local('RssService\getDealInfo', array($id));
        $deal = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('RssService\getDealInfo', array($id)), 30);
        if (empty($deal)) {
            return app_redirect(url("index"));
        }else{
            header("Content-Type:text/xml;charset=utf-8");
            echo $deal;
        }

    }

}
