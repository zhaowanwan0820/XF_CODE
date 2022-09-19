<?php
/**
 * 随心约信息披露 标的列表api
 *
 * @date 2018-01-12
 * @author weiwei12@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\reserve\UserReservationService;

class ReserveDisclosureListApi extends ReserveBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'invest' => array('filter' => 'required', 'message' => 'invest is required'),
            'page' => array('filter' => 'int'),
            'page_size' => array('filter' => 'int'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }
        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $data = $this->form->data;
        $page = max(1, intval($data['page']));
        $pageSize = isset($data['page_size']) ? max(1, intval($data['page_size'])) : 20;
        if (false === strpos($data['invest'], '_')) {
            $this->setErr('ERR_MANUAL_REASON', '投资期限参数不合法');
            return false;
        }
        list($investDeadline, $investDeadlineUnit) = explode('_', $data['invest']);
        $userReservationService = new UserReservationService();
        $result = $userReservationService->getDisclosureDealListCacheByPage($investDeadline, $investDeadlineUnit, $page, $pageSize);
        $this->json_data = $result;
        return true;
    }
}
