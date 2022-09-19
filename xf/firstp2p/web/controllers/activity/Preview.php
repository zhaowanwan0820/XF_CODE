<?php

namespace web\controllers\activity;

use libs\web\Form;
use web\controllers\BaseAction;

class Preview extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'key' => array('filter' => 'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        if (empty($data['key'])) {
            return $this->show_error("参数错误");
        }

        $activity = \core\service\OpenService::getPreviewActivity($data['key']);
        if (empty($activity)) {
            return $this->show_error("数据已过期, 请重新预览");
        }
        if($activity["level"] == 2){
            $activity["content"] = explode(',',$activity["content"]);
        }
        // 可投资标的列表
        $siteId = $this->getSiteId();
        $newUserDealsList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('NewUserPageService\getNewUserDeals', array($siteId)), 30);
        $this->tpl->assign("newUserDealsList", $newUserDealsList);
        $this->tpl->assign('activity', $activity);
    }

}
