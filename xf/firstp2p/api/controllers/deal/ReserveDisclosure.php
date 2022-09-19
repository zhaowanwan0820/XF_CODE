<?php
/**
 * 随心约信息披露 首页
 * 预约期限
 *
 * @date 2018-01-12
 * @author weiwei12@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\dao\UserReservationModel;
use core\dao\ReservationConfModel;
use core\service\UserReservationService;

class ReserveDisclosure extends ReserveBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
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
            return false;
        }
        $data = $this->form->data;

        $result = ['list' => [], 'count' => 0];
        $userReservationService = new UserReservationService();
        //获取用户有效预约记录，生成期限数组
        $reserveList = $userReservationService->getUserValidReserveList($userInfo['id']);
        $reserveDeadline = [];
        foreach ($reserveList['userReserveList'] as $reserve) {
            $reserveDeadline[$reserve['invest_deadline'] . '_' . $reserve['invest_deadline_unit']] = 1;
        }

        $deadlineList = $userReservationService->getDisclosureDeadlineList();
        foreach ($deadlineList as $deadlineStr) {
            //用户没有预约期限，则不显示
            if (empty($reserveDeadline[$deadlineStr])) {
                continue;
            }
            list($deadline, $deadlineUnit) = explode('_', $deadlineStr);
            $value['deadline'] = $deadline;
            $value['deadline_unit'] = $deadlineUnit;
            $value['invest'] = $value['deadline'] . '_' . $value['deadline_unit'];
            $value['deadline_unit_string'] = $deadlineUnit == UserReservationModel::INVEST_DEADLINE_UNIT_MONTH ? '个' . UserReservationModel::$investDeadLineUnitConfig[$deadlineUnit] : UserReservationModel::$investDeadLineUnitConfig[$deadlineUnit];
            $value['desc'] = '标的期限为' . $deadline . $value['deadline_unit_string'] . '的随心约产品信息';
            $result['list'][] = $value;
        }
        $result['total'] = count($result['list']);

        $this->tpl->assign("list", $result);
        $this->tpl->assign('userClientKey', $data['userClientKey']);
    }
}
