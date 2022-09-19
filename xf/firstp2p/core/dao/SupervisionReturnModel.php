<?php
/**
 * 存管回单表
 * SupervisionReturnModel class file.
 * @author 魏巍 <weiwei12@ucfgroup.com>
 **/

namespace core\dao;

/**
 * SupervisionReturnModel class
 * @author 魏巍 <weiwei12@ucfgroup.com>
 **/
class SupervisionReturnModel extends BaseModel {

    public static $typeMap = [
        'recharge' => 1,
        'withdraw' => 2,
        'transaction' => 3,
    ];

    /**
     * 连firstp2p_payment库
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
    }

    /**
     * 添加回单
     */
    public function addReturn($params) {
        $data = [
            'type'  => intval($params['type']),
            'out_order_id'  => addslashes($params['out_order_id']),
            'sv_order_id'  => addslashes($params['sv_order_id']),
            'trade_code'  => addslashes($params['trade_code']),
            'amount'  => intval($params['amount']),
            'order_status'  => intval($params['order_status']),
            'finish_time'  => intval($params['finish_time']),
            'date'  => addslashes($params['date']),
            'remark'  => !empty($params['remark']) ? addslashes($params['remark']): '',
            'pay_code'  => !empty($params['pay_code']) ? addslashes($params['pay_code']): '',
            'source'  => !empty($params['source']) ? intval($params['source']): '',
            'deal_id'  => !empty($params['deal_id']) ? intval($params['deal_id']): 0,
            'pay_user_id'  => !empty($params['pay_user_id']) ? intval($params['pay_user_id']) : 0,
            'receive_user_id'  => !empty($params['receive_user_id']) ? intval($params['receive_user_id']) : 0,
            'create_time'  => time(),
            'update_time'  => time(),
        ];
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 清理回单
     */
    public function clearReturn($date) {
        if (empty($date)) {
            return false;
        }
        $sql = sprintf("DELETE FROM %s WHERE `date` = '%s'", $this->tableName(), $date);
        return $this->execute($sql);
    }

    /**
     * 更新回单
     */
    public function updateReturn($outOrderId, $updateData) {
        $condition = sprintf("`out_order_id` = '%s'", addslashes($outOrderId));
        $params = array(
            'errno'  => 1,
            'update_time'   => time(),
        );
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 通过外部订单号查询
     * @param string $outOrderId 外部订单号
     * @return mix
     */
    public function getInfoByOutOrderId($outOrderId) {
        $condition = sprintf("`out_order_id` = '%s'", addslashes($outOrderId));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 获取回单列表
     */
    public function getReturnList($date, $offset = 0, $pageSize = 20) {
        $orderBy = ' ORDER BY `id` ASC ';
        $limit = sprintf(' LIMIT %d, %d ', $offset, $pageSize);
        $whereParams = " `date` = ':date' ";
        $whereValues = array(':date'=>addslashes($date));
        return $this->findAll($whereParams . $orderBy . $limit, true, '*', $whereValues);
    }

}
