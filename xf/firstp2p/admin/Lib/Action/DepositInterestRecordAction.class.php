<?php
/**
 *
 * DepositInterestRecord.class.php
 *
 * @date 2014-02-13 14:30
 * @author liangqiang@ucfgroup.com
 */
use app\models\service\Discount;

class DepositInterestRecordAction extends CommonAction {

    protected static $type_list = array('1' => '账户余额贴息', '2' => '投标贴息');
    protected static $status_list = array('0' => '未确认', '1' => '自动确认', '2' => '手工确认');

    /**
     * 搜索条件
     */
    protected function _search($name = '') {
        $map = parent::_search();
        if (!empty($map['time'])) {
            $start = to_timespan($map['time'] . " 00:00:00");
            $end = to_timespan($map['time'] . " 23:59:59");
            $map['time'] = array('between', "{$start},{$end}");
        }
        return $map;
    }

    /**
     * 处理列表数据显示
     */
    protected function form_index_list(&$list) {
        foreach ($list as $k => $item) {
            $item['user_name'] = get_user_name($item['user_id']);
            $item['time'] = to_date($item['time'], 'Y-m-d');
            $item['money'] = format_price($item['money']);
            if (!empty($item['deal_loan_id'])) {
                $sql_deal = "select d.name from " . DB_PREFIX . "deal_load l left join " . DB_PREFIX . "deal d on l.deal_id=d.id where l.id=" . $item['deal_loan_id'];
                $deal_name = $GLOBALS['db']->getOne($sql_deal);
                $item['deal_name'] = "<a href='" . u("DealLoad/index", array('id' => $item['deal_loan_id'])) . "' target='_blank'>" . $deal_name . "</a>";
            } else {
                $item['deal_loan_id'] = '-';
                $item['deal_name'] = '-';
            }
            if ($item['status'] == 0) {
                $item['opt_pay'] = "<a href='#' onclick='pay(" . $item['id'] . ");'>确认</a>";
            } else {
                $item['opt_pay'] = '';
            }
            $item['type'] = self::$type_list[$item['type']];
            $item['status'] = self::$status_list[$item['status']];
            $list[$k] = $item;
        }
    }

    /**
     * 后台手工确认
     */
    public function pay() {
        $id_list = $this->get_id_list();
        $log_info = "确认贴息:" . implode(',', $id_list);
        if (empty($id_list)) {
            save_log($log_info . L("LOG_STATUS_0"), 0);
            $this->error('操作失败');
        }
        $discount = new Discount();
        $discount->pay($id_list, 2);
        $this->display_success('操作成功');
        save_log($log_info . L("LOG_STATUS_1"), 1);
    }

}