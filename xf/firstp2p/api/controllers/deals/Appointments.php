<?php
/**
 * 预约列表
 */

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\ncfph\ReserveEntraService as PhReserveEntraService;
use core\service\ReservationEntraService;
use core\dao\UserReservationModel;
use core\dao\ReservationEntraModel;
use core\dao\DealModel;

class Appointments extends AppBaseAction
{

    public static $rateText = array(
            0 => '年化借款利率',
            1 => '预期年化',
            2 => '预期年化',
            3 => '预期年化',
        );

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string'),
            'isMore' => array('filter' => 'int'),
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
            "product_type" => array("filter" => "int", "message" => "product_type is error", "option" => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        $userId = !empty($userInfo['id']) ? $userInfo['id'] : 0;

        $params['offset'] = empty($data['offset']) ? 0 : intval($data['offset']);
        $params['count'] = empty($data['count']) ? 2 : intval($data['count']);
        $params['product_type'] = isset($data['product_type']) ? intval($data['product_type']) : 0; //默认全部  0全部  1网贷  2尊享

        $dealTypeList = $this->rpc->local("UserReservationService\getDealTypeListByProduct", array($params['product_type'], $userId));
        if (empty($dealTypeList)) {
            $this->json_data = [];
            return true;
        }

        //拆分成网贷和专享类型列表
        $p2pDealTypeList = array_intersect([DealModel::DEAL_TYPE_GENERAL], $dealTypeList);
        $exclusiveDealTypeList = array_diff($dealTypeList, [DealModel::DEAL_TYPE_GENERAL]);

        $p2pCards = $exclusiveCards = [];
        //请求网贷接口
        if ($p2pDealTypeList) {
            $phReserveEntraService = new PhReserveEntraService();
            $result = $phReserveEntraService->getReserveEntraList($params['count'], $params['offset'], $userId);
            $p2pCards = !empty($result['list']) ? $result['list'] : [];
        }

        if ($exclusiveDealTypeList) {
            $entraService = new ReservationEntraService();
            $result = $entraService->getReserveEntraDetailList(ReservationEntraModel::STATUS_VALID, $params['count'], $params['offset'], $userInfo);
            $exclusiveCards = !empty($result['list']) ? $result['list'] : [];
        }

        //聚合结果
        $cards = array_merge($p2pCards, $exclusiveCards);

        if (!empty($cards)) {
            $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
            foreach ($cards as &$val) {
                $productType = $this->rpc->local('UserReservationService\getProductByDealType', [$val['dealType']]);
                $url = $productType == UserReservationModel::PRODUCT_TYPE_P2P ? app_conf('NCFPH_WAP_HOST') : $http . $_SERVER['HTTP_HOST'];
                $val['appointUrl'] = sprintf($url."/deal/reserve?investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s", $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
                $val['detailUrl'] = sprintf($url."/deal/reserveDetail?line_unit=%s_%s&deal_type=%s&loantype=%s&rate=%s", $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
                if ($productType == UserReservationModel::PRODUCT_TYPE_P2P) {
                    if (empty($data['token'])) {
                        // 如果token为空，这里通过网信跳转
                        $url = $http . $_SERVER['HTTP_HOST'];
                        $val['appointUrl'] = sprintf($url."/ncfph/dealReserve?investLine=%s&investUnit=%s&deal_type=%s&loantype=%s&rate=%s", $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
                        $val['detailUrl'] = sprintf($url."/ncfph/dealReserveDetail?line_unit=%s_%s&deal_type=%s&loantype=%s&rate=%s", $val['investLine'], $val['unitType'], $val['dealType'], $val['loantype'], $val['investRate']);
                    } else {
                        $val['appointUrl'] .= '&token='.$data['token'];
                        $val['detailUrl'] .= '&token='.$data['token'];
                    }
                }

                $val['rateText'] = self::$rateText[$val['dealType']] ?: self::$rateText[2];
                $val['productType'] = $productType;
            }
        } else {
            $cards = array();
        }
        $this->json_data = $cards;
    }

}
