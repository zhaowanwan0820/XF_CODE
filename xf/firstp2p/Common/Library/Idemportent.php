<?php
/**
 * 统一幂等控制器
 */
namespace NCFGroup\Common\Library;

class Idemportent
{

    /**
     * 订单已存在
     */
    const EXISTS = 1;

    /**
     * 设置成功
     */
    const SET_SUCCESS = 0;

    /**
     * 订单不存在
     */
    const NOT_EXISTS = 0;

    /**
     * 查询幂等
     */
    public static function get($dblink, $type, $orderId, $status = 0)
    {
        $type = addslashes($type);
        if (empty($type)) {
            throw new \Exception('Idemportent::get type is empty');
        }

        $orderId = intval($orderId);
        $status = intval($status);

        $sql = "SELECT id FROM common_idemportent WHERE orderid='{$orderId}' AND type='{$type}' AND status='{$status}' LIMIT 1";
        $res = mysql_query($sql, $dblink);
        $result = mysql_fetch_assoc($res);

        if (empty($result['id'])) {
            return self::NOT_EXISTS;
        }

        return self::EXISTS;
    }

    /**
     * 写入幂等
     */
    public static function set($dblink, $type, $orderId, $status = 0)
    {
        $type = addslashes($type);
        if (empty($type)) {
            throw new \Exception('Idemportent::set type is empty');
        }

        $orderId = intval($orderId);
        $status = intval($status);
        $time = time();

        $sql = "INSERT INTO common_idemportent (`type`, `orderid`, `status`, `createtime`) VALUES ('{$type}', '{$orderId}', '{$status}', '{$time}')";
        $res = mysql_query($sql, $dblink);

        $errno = mysql_errno($dblink);

        //如果是ER_DUP_ENTRY返回订单存在
        if ($errno === 1062) {
            return self::EXISTS;
        }

        //其他错误抛异常
        if ($errno !== 0) {
            $error = mysql_error($dblink);
            throw new \Exception("Idemportent::set failed. errno:{$errno}, error:{$error}");
        }

        return self::SET_SUCCESS;
    }

}
