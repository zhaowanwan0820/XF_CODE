<?php
/**
 * 网信出行-授权协议详情页
 *
 * @date 2018-03-03
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\life;

use libs\web\Form;
use api\controllers\LifeBaseAction;
use NCFGroup\Protos\Life\Enum\TripEnum;

class TripAuthProtocol extends LifeBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'advid' => array('filter' => 'required', 'message' => 'advid is required'),
            'advtitle' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        // 获取广告位ID
        $advId = !empty($data['advid']) ? addslashes($data['advid']) : TripEnum::ADV_TRIP_AUTH_PROTOCOL;
        // 获取广告位标题
        $advTitle = !empty($data['advtitle']) ? addslashes($data['advtitle']) : '用户服务协议';

        $this->tpl->assign('advId', $advId);
        $this->tpl->assign('advTitle', $advTitle);
        return true;
    }
}