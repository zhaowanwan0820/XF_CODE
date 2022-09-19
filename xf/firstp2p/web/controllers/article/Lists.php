<?php

namespace web\controllers\article;

use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class Lists extends BaseAction {

    public function init() {
        $this->form = new Form();

        $this->form->rules = array(
            'type' => array('filter' => 'reg', 'option' => array('regexp' => '/^\d{1,}$/', 'optional' => true)),
            'page' => array('filter' => 'reg', 'option' => array('regexp' => '/^\d{1,}$/', 'optional' => true)),
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
        $data['size'] = 10;

        if (!isset($data['type'])) {
            $data['type'] = 1;
        }

        if (!isset($data['page'])) {
            $data['page'] = 1;
        }

        $appId = $this->appInfo['id'];
        $request = new SimpleRequestBase();
        $request->setParamArray(array('siteId' => $appId, 'page' => $data['page'], 'size' => $data['size'], 'type' => $data['type']));
        $response = $GLOBALS['openbackRpc']->callByObject(array(
            'service' => 'NCFGroup\Open\Services\OpenArticle',
            'method' => 'getArticleList',
            'args' => $request,
        ));

        $lists = $response->getList();
        $this->tpl->assign("lists", $this->getPageList($lists));

        $pages = $response->getPage();
        $this->tpl->assign("pages", $pages);
        $this->tpl->assign("numbers",  $this->getPageNumbers($pages));

        $this->tpl->assign("type",  $data['type']);
        $this->tpl->assign("page",  $data['page']);
        $this->tpl->assign("size",  $data['size']);

        $this->template = 'web/views/article/lists.html';

        return true;
    }

    private function getPageList($lists) {
        foreach ($lists as $key => $item) {
            $item['content'] = msubstr(strip_tags($item['content']), 0, 120);
            $lists[$key] = $item;
        }
        return $lists;
    }

    private function getPageNumbers($pages) {
        $numbers = array();
        $loop = $start = $pages['pageNo'] > 3 ? $pages['pageNo'] - 2 : 1;
        while ($loop - $start < 5 && $loop <= $pages['totalPage']) {
            $numbers[] = $loop ++;
        }

        $complete = min(5 - count($numbers), $start - 1);
        while ($complete > 0) {
            array_unshift($numbers, $complete --);
        }

        return $numbers;
    }

}
