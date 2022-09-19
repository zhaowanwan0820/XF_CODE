<?php

namespace api\controllers\activity;

use libs\web\Form;
use api\controllers\BaseAction;

class Preview extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'key'    => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL', "参数错误");
        }
    }

    public function invoke() {
        $data = $this->form->data;
        if (empty($data['key'])) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL', "参数错误");
        }

        $activity = \core\service\OpenService::getPreviewActivity($data['key']);
        if (empty($activity)) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL', "数据已过期, 请重新预览");
        }

        $this->json_data = $activity;
    }

}
