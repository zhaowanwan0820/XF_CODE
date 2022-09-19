<?php
/**
 * 广告位详情页
 *
 * @date 2018-05-15
 * @author weiwei12@ucfgroup.com
 */

namespace api\controllers\common;

use libs\web\Form;
use api\controllers\AppBaseAction;

class AdvH5 extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'required', 'message' => 'id is required'),
            'title' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        // 获取广告位ID
        $id = !empty($data['id']) ? addslashes($data['id']) : '';
        // 获取广告位标题
        $title = !empty($data['title']) ? addslashes($data['title']) : '广告位';

        $this->tpl->assign('id', $id);
        $this->tpl->assign('title', $title);
        return true;
    }
}
