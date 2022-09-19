<?php

namespace web\controllers\activity;

use libs\web\Form;
use libs\web\Url;
use libs\utils\Aes;
use libs\utils\Logger;

use core\dao\DealModel;
use web\controllers\BaseAction;


/**
 * 新首页PC一入口
 */
class Deals extends BaseAction {

    public function invoke() {
        $siteId = $this->getSiteId();

        //智多鑫
        $duotouList = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DtDealService\getActivityIndexDeals', [$siteId]), 60);
        $duotouList = $this->_getShowFields($duotouList, ['id', 'name', 'lock_day', 'min_rate', 'max_rate', 'status']);

        //条件
        $page = intval(@$REQUEST['p']) > 0 ?: 1;
        if((int)app_conf('SUPERVISION_SWITCH') === 1 || $this->isSvOpen) {
            $option['isHitSupervision'] = true;
        }

        $zxp2pFields = ['repay_time', 'loantype', 'need_money_decimal', 'name', 'income_base_rate', 'url', 'deal_status', 'deal_status_text'];

        //专享
        $option['deal_type'] = DealModel::DEAL_TYPE_EXCLUSIVE . ",".DealModel::DEAL_TYPE_EXCHANGE;
        $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealsList', array(null, $page, 0, false, 0, $option)), 30, false, false);
        $deals['list']['list'] = $this->rpc->local('DealService\UserDealStatusSwitch', array($deals['list']['list']));
        $deals['list']['list'] = $this->rpc->local('DealService\EncryptDealIds', array($deals['list']['list']));
        $zxList = $deals['list']['list'];
        $zxList = $this->_getShowFields($zxList, $zxp2pFields);

        //p2p
        $option['deal_type'] = DealModel::DEAL_TYPE_ALL_P2P;
        $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealsList', array(null, $page, 0, false, 0, $option)), 30, false, false);
        $deals['list']['list'] = $this->rpc->local('DealService\UserDealStatusSwitch', array($deals['list']['list']));
        $deals['list']['list'] = $this->rpc->local('DealService\EncryptDealIds', array($deals['list']['list']));
        $p2pList = $deals['list']['list'];
        $p2pList = $this->_getShowFields($p2pList, $zxp2pFields);

       //输出
        $return = ['duotou' => $duotouList, 'zx' => $zxList, 'p2p' => $p2pList];
        echo json_encode(array(
          'errorCode' => 0,
          'data' => $return,
        ));
    }

    private function _getShowFields($deals, $fields) {
        $showDeals = [];
        foreach ($deals as $item) {
            foreach ($fields as $field) {
                $tmp[$field] = $item[$field];
            }
            $showDeals[] = $tmp;
        }
        return $showDeals;
    }

}
