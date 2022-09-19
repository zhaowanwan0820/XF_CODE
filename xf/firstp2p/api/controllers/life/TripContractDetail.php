<?php
/**
 * 网信出行-合同详情页
 *
 * @date 2017-12-22
 * @author weiwei12@ucfgroup.com
 */

namespace api\controllers\life;

use libs\web\Form;
use api\controllers\LifeBaseAction;

class TripContractDetail extends LifeBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'advid' => array('filter' => 'required', 'message' => 'advid is required'),
            'advtitle' => array('filter' => 'required', 'message' => 'advtitle is required'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        // 获取广告位ID
        $advId = !empty($data['advid']) ? addslashes($data['advid']) : 'trip_contract_detail';
        // 获取广告位标题
        $advTitle = !empty($data['advtitle']) ? addslashes($data['advtitle']) : '用户服务协议';

        $this->tpl->assign('advId', $advId);
        $this->tpl->assign('advTitle', $advTitle);
        return true;
    }
}