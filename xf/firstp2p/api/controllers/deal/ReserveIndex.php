<?php
/**
 * 随心约首页 新
 * 入口列表页
 * @date 2019-02-27
 * @author weiwei12@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\ReservationEntraService;
use core\service\ReservationConfService;
use core\dao\ReservationEntraModel;
use core\dao\ReservationConfModel;
use libs\utils\Logger;

class ReserveIndex extends ReserveBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );

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
        if (empty($userInfo))
        {
            // 登录token失效，跳到App登录页
            header('Location:' . $this->getAppScheme('native', array('name'=>'login')));
            return false;
        }
        $data = $this->form->data;
        $token = $data['token'];

        $entraService = new ReservationEntraService();
        $detailList = $entraService->getReserveEntraDetailList(ReservationEntraModel::STATUS_VALID, 100, 0, $userInfo);
        $list = !empty($detailList['list']) ? $detailList['list'] : [];

        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        $url = $http . $_SERVER['HTTP_HOST'];
        foreach ($list as $key => $val) {
            $list[$key]['appointUrl'] = sprintf("/deal/reserve?token=%s&investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s", $token, $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
            $list[$key]['detailUrl'] = sprintf("/deal/reserveDetail?token=%s&line_unit=%s_%s&deal_type=%s&loantype=%s&rate=%s", $token, $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
        }

        $reservationConfService = new ReservationConfService();
        $reserveNotice = $reservationConfService->getReserveInfoByType(ReservationConfModel::TYPE_NOTICE);

        $userClientKey = parent::genUserClientKey($data['token'], $userInfo['id']);
        $reserveListUrl = '/deal/reserveMy?userClientKey=' . $userClientKey;

        $this->tpl->assign('token', $token);
        $this->tpl->assign('list', $list);
        $this->tpl->assign('bannerUrl', (!empty($reserveNotice['banner_uri']) ? $reserveNotice['banner_uri'] : ''));
        $this->tpl->assign('reserveListUrl', $reserveListUrl);

        return true;
    }
}
