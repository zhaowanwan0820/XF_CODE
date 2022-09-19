<?php
namespace core\service\gateway;

use libs\utils\PaymentApi;
use libs\common\ErrCode;
/**
 * 批量查单接口服务
 */
class BatchOrderSearchService {

    private static $typeMap = array(
        1 => 'searchChargeOrder',
        2 => 'searchWithdrawOrder',
        3 => 'searchOfflineOrder',
    );

    public static function execute() {
        $params = func_get_args();
        $params = $params[0];
        $orderIds = isset($params['orderIds']) ? addslashes(trim($params['orderIds'])) : self::exception('ERR_PARAM');
        $orderType = isset($params['orderType']) ? intval($params['orderType']) : self::exception('ERR_PARAM');
        $orderIds = explode(',', $orderIds);
        if (empty($orderIds) || !isset(self::$typeMap[$orderType])) {
             self::exception('ERR_PARAM');
        }
        if (count($orderIds) > 1000) {
            self::exception('ERR_ORDER_NUM_MAX_LIMIT');
        }
        $result = call_user_func_array(['self', self::$typeMap[$orderType]], [$orderIds]);
        if (empty($result)) {
            self::exception('ERR_ORDER_NOT_EXIST');
        }
        return $result;
    }

    public static function exception($key) {
        throw new \Exception(ErrCode::getMsg($key), ErrCode::getCode($key));
    }

    /**
     * 查询充值单号
     */
    private static function searchChargeOrder($orderIds) {
        $sql = "SELECT * FROM firstp2p_payment_notice WHERE notice_sn in ('" . implode("','", $orderIds) . "')";
        $result = \libs\db\Db::getInstance('firstp2p', 'slave')->getAll($sql);
        $statusMap = array(
            0 => '初始状态',
            1 => '成功',
            2 => '处理中',
            3 => '失败',
        );
        $orders = array();
        foreach ($result as $item)
        {
            $orders[] = array(
                'type' => '充值',
                'amount' => round($item['money'] * 100),
                'orderId' => $item['notice_sn'],
                'time' => $item['create_time'],
                'dealtime' => $item['pay_time'],
                'status' => $statusMap[$item['is_paid']],
                'note' => "设备:{$item['platform']}(1Web,2IOS,3Android,8H5)",
            );
        }
        return $orders;
    }

    /**
     * 查询提现单号
     */
    private static function searchWithdrawOrder($orderIds) {
        $sql = "SELECT * FROM firstp2p_user_carry WHERE id in ('" . implode("','", $orderIds) . "')";
        $result = \libs\db\Db::getInstance('firstp2p', 'slave')->getAll($sql);
        $statusMap = array(
            0 => '初始状态',
            1 => '成功',
            2 => '失败',
            3 => '处理中',
        );
        $orders = array();
        foreach ($result as $item)
        {
            $orders[] = array(
                'type' => '提现',
                'amount' => round($item['money'] * 100),
                'orderId' => $item['id'],
                'time' => $item['create_time'],
                'dealtime' => $item['withdraw_time'],
                'status' => $statusMap[$item['withdraw_status']],
                'note' => $item['withdraw_msg'],
            );
        }
        return $orders;
    }

    /**
     * 查询线下调帐单号
     */
    private static function searchOfflineOrder($orderIds) {
        $sql = "SELECT * FROM firstp2p_money_apply WHERE id in ('" . implode("','", $orderIds) . "')";
        $result = \libs\db\Db::getInstance('firstp2p', 'slave')->getAll($sql);
        $statusMap = array(
            1 => '处理中',
            2 => '成功',
        );
        $orders = array();
        foreach ($result as $item)
        {
            $orders[] = array(
                'type' => '线下调账',
                'amount' => round($item['money'] * 100),
                'orderId' => $item['id'],
                'time' => $item['time'],
                'dealtime' => $item['time'],
                'status' => $statusMap[$item['type']],
                'note' => $item['note'],
            );
        }
        return $orders;
    }
}
