<?php
/**
 * 查看项目详情
 */
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\DealProjectModel;

class DealProjectShow extends BaseAction {
    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
                'project_id' => array('filter' => 'int'),
                'ajax' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $ajax = intval($data['ajax']);
        $project_id = intval($data['project_id']);
        $projectInfo = DealProjectModel::instance()->find($project_id);
        echo $projectInfo['intro'];
    }
}

