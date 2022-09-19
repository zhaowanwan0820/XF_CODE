<?php
/**
 * 我的投资劵
 **/
namespace web\controllers\account;

use libs\utils\Site;
use libs\web\Form;
use web\controllers\BaseAction;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

class DiscountList extends BaseAction
{
    const DATE_FORMAT = 'Y-m-d';
    const PAGE_SIZE = 9;

    public function init()
    {
        $this->check_login();

        $this->form = new Form();
        $this->form->rules = array(
//            'type' => array('filter' => 'int'),
            'page' => array('filter' => 'int'),
            'useStatus' =>  array('filter' => 'int'),
            'consume_type' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $ret = ['error' => 2000, 'msg' => $this->form->getErrorMsg()];
            return ajax_return($ret);
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $page = intval($data['page']) ?: 1;
        $siteId = Site::getId();
        $uid = $GLOBALS['user_info']['id'];
        $useStatus = isset($data['useStatus']) ? intval($data['useStatus']) : 0;
        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : 0;
        $rpcParams = array($uid, 0, $page, self::PAGE_SIZE, 0, $siteId, $consumeType, $useStatus);

        $ret = $this->rpc->local('DiscountService\mine', $rpcParams);

        $list = $ret['list'] ?: [];
        $total = $ret['total'] ?: 0;
        $totalPage = $ret['totalPage'] ?: 0;
        if ($total > 0) {
            $list = array_map([$this, 'format'], $list);
        }

        $cashbackcount = $raiseratescount = $goldCount = $usedCount = 0;
        if ($consumeType == 0 || $consumeType == CouponGroupEnum::CONSUME_TYPE_P2P) {
            $cashbackParams = array($uid, CouponGroupEnum::DISCOUNT_TYPE_CASHBACK);
            $cashbackcount = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('O2OService\getUserUnusedDiscountCount', $cashbackParams), 60);
            $raiseratesParams = array($uid, CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES);
            $raiseratescount = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('O2OService\getUserUnusedDiscountCount', $raiseratesParams), 60);
            $params = array($uid);
            $countRes = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('O2OService\getMineUnusedDiscountCount', $params), 60);
            $usedCount = $countRes['used'];
        }

        if ($consumeType == 0 || $consumeType == CouponGroupEnum::CONSUME_TYPE_GOLD) {
            $goldParams = array($uid, CouponGroupEnum::DISCOUNT_TYPE_GOLD);
            $goldCount = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('O2OService\getUserUnusedDiscountCount', $goldParams), 60);
        }

        //判断是否为黄金白名单
        $isWhite = $this->rpc->local('GoldService\isWhite',array($uid));

        $ret = [
            'error' => 0,
            'msg' => 'success',
            'pagecount' => intval($totalPage),
            'page' => intval($page),
            'count' => intval($total),
            'cashbackcount' => intval($cashbackcount),
            'raiseratescount' => intval($raiseratescount),
            'goldCount' => intval($goldCount),
            'usedCount' => intval($usedCount),
//            'type' => $type,
            'list' => $list,
            'isWhite' => $isWhite,
        ];

        ajax_return($ret);
    }

    protected function format($item)
    {
        $ret = array();
        $ret['id'] = $item['id'];
        $ret['pay_money'] = str_replace('.00', '', number_format($item['goodsPrice'], 2));
        if ($item['type'] == CouponGroupEnum::DISCOUNT_TYPE_GOLD) {
            $ret['deal_money'] = "满" . $item['bidAmount'] . "克可用";
        } else {
            $ret['deal_money'] = "满" . $item['bidAmount'] . "元可用";
        }

        $ret['deal_term'] = "满" . $item['bidDayLimit'] . "天可用";
        $ret['effective_start_time'] = date(self::DATE_FORMAT, $item['useStartTime']);
        $ret['effective_end_time'] = date(self::DATE_FORMAT, $item['useEndTime']);
        $ret['resource'] = $item['name'];
        $ret['note'] = $item['useInfo'];
        $ret['status'] = $item['status'];
        $ret['type'] = $item['type'];
        return $ret;
    }
}
