<?php
/**
 * 存管对账错误记录表
 * SupervisionCheckErrorModel class file.
 * @author 魏巍 <weiwei12@ucfgroup.com>
 **/

namespace core\dao;

/**
 * SupervisionCheckErrorModel class
 * @author 魏巍 <weiwei12@ucfgroup.com>
 **/
class SupervisionCheckErrorModel extends BaseModel {

    const ERR_PENDING = 1;//挂账：我方有存管无
    const ERR_NO_TRADE = 2;//无交易单：存管有我方无
    const ERR_MONEY = 3;//错账：金额错误
    const ERR_STATUS = 4;//状态错误：订单状态不一致

    const ERR_CANCEL = 5;//消账

    const STATUS_NORMAL = 0;//未处理
    const STATUS_CANCEL = 1;//销账

    /**
     * 连firstp2p_payment库
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
    }

    /**
     * 清理错误记录
     */
    public function clearError($date) {
        if (empty($date)) {
            return false;
        }
        $sql = sprintf("DELETE FROM %s WHERE `date` = '%s'", $this->tableName(), $date);
        return $this->execute($sql);
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
     * 添加错误记录
     */
    public function addError($params) {
        $data = [
            'out_order_id'  => addslashes($params['out_order_id']),
            'date'  => addslashes($params['date']),
            'errno'  => intval($params['errno']),
            'return_id'  => isset($params['return_id']) ? intval($params['return_id']) : 0,
            'status'  => isset($params['status']) ? intval($params['status']) : 0,
            'create_time'  => time(),
            'update_time'  => time(),
            'is_proc'  => isset($params['is_proc']) ? intval($params['is_proc']) : 0,
            'proc_time'  => isset($params['proc_time']) ? intval($params['proc_time']) : 0,
            'proc_uid'  => isset($params['proc_uid']) ? intval($params['proc_uid']) : 0,
            'comment'  => isset($params['comment']) ? addslashes($params['comment']) : '',
        ];
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 勾消挂账
     */
    public function cancelErrPending($outOrderId, $updateParams) {
        $condition = sprintf("`out_order_id` = '%s' AND `status` = '%d' AND `errno` = '%d'", addslashes($outOrderId), self::STATUS_NORMAL, self::ERR_PENDING);
        $params = [
            'status'        => self::STATUS_CANCEL,
            'update_time'   => time(),
        ];
        $params += $updateParams;
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

}
