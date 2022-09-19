<?php
/**
 * 我的投资劵
 **/
namespace web\controllers\account;

use libs\utils\Site;
use libs\web\Form;
use web\controllers\BaseAction;
use core\service\o2o\DiscountService;

class DiscountList extends BaseAction
{
    const DATE_FORMAT = 'Y-m-d';
    const PAGE_SIZE = 9;

    public function init()
    {
        $this->check_login();

        $this->form = new Form();
        $this->form->rules = array(
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
        $uid = $GLOBALS['user_info']['id'];
        $useStatus = isset($data['useStatus']) ? intval($data['useStatus']) : 0;
        $consumeType = isset($data['consume_type']) ? $data['consume_type'] : 0;
        $countRes = DiscountService::getMineUnusedDiscountCount($uid);

        $ret = DiscountService::getUserDiscountList($uid, 0, $page, self::PAGE_SIZE, 0, $consumeType, $useStatus, 1);
        $list = $ret['data'] ?: [];
        $total = $ret['total'] ?: 0;
        $totalPage = $ret['totalPage'] ?: 0;
        if ($list) {
            $list = array_map([$this, 'format'], $list);
        }

        $cashbackcount = $raiseratescount = $goldCount = $usedCount = 0;
        $cashbackcount = $countRes['cashBack'];
        $raiseratescount = $countRes['raiseInterest'];
        $usedCount = $countRes['used'];

        $ret = [
            'error' => 0,
            'msg' => 'success',
            'pagecount' => intval($totalPage),
            'page' => intval($page),
            'count' => intval($total),
            'cashbackcount' => intval($cashbackcount),
            'raiseratescount' => intval($raiseratescount),
            'usedCount' => intval($usedCount),
            'list' => $list,
        ];

        ajax_return($ret);
    }

    protected function format($item)
    {
        $ret = array();
        $ret['id'] = $item['id'];
        $ret['pay_money'] = str_replace('.00', '', number_format($item['goodsPrice'], 2));
        $ret['deal_money'] = "满" . $item['bidAmount'] . "元可用";

        $ret['deal_term'] = "满" . $item['bidDayLimit'] . "天可用";
        $ret['effective_start_time'] = date(self::DATE_FORMAT, $item['useStartTime']);
        $ret['effective_end_time'] = date(self::DATE_FORMAT, $item['useEndTime']);
        $ret['resource'] = $item['name'];
        $ret['note'] = $item['useInfo'] ?: '';
        $ret['status'] = $item['status'];
        $ret['type'] = $item['type'];
        return $ret;
    }
}
