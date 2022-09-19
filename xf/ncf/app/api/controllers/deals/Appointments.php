<?php
/**
 * 预约列表
 */

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\enum\DealEnum;
use core\service\reserve\ReservationEntraService;
use core\enum\ReserveEntraEnum;

class Appointments extends AppBaseAction {
    // 是否需要授权
    protected $needAuth = false;

    public static $rateText = array(
        0 => '年化借款利率',
        1 => '预期年化',
        2 => '预期年化',
        3 => '预期年化',
    );

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'isMore' => array('filter' => 'int'),
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $params['offset'] = empty($data['offset']) ? 0 : intval($data['offset']);
        $params['count'] = empty($data['count']) ? 2 : intval($data['count']);
        $userInfo = $this->getUserByToken();
        $result = array();
        $entraService = new ReservationEntraService();
        $result = $entraService->getReserveEntraDetailList(ReserveEntraEnum::STATUS_VALID, $params['count'], $params['offset'], $userInfo);
        $cards = $result['list'];
        if (!empty($cards)) {
            $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
            $url = $http . $_SERVER['HTTP_HOST'];
            foreach ($cards as &$val) {
                $val['appointUrl'] = sprintf($url."/deal/reserve?investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s", $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
                $val['detailUrl'] = sprintf($url."/deal/reserveDetail?line_unit=%s_%s&deal_type=%s&loantype=%s&rate=%s", $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
                $val['rateText'] = self::$rateText[$val['dealType']] ?: self::$rateText[2];
            }
        } else {
            $cards = array();
        }

        $this->json_data = $cards;
    }
}
