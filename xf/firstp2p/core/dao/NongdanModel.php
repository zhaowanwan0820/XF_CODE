<?php
/**
 * NongdanModel class file.
 **/

namespace core\dao;

use libs\utils\PaymentApi;
use NCFGroup\Commom\Library\Idworker;
/**
 * NongdanModel class
 * @author pengchanglu
 **/
class NongdanModel extends BaseModel {
    const STATUS_REFUSE     = -1; // 已拒绝
    const STATUS_WAIT_STEP1 = 1; // A 角色待审核
    const STATUS_WAIT_STEP2 = 2; // B 角色待审核
    const STATUS_AUDIT_PASS = 3; // 审核通过

    const REQ_STATUS_INIT       = 0; // 未发送
    const REQ_STATUS_SENDING    = 1; // 处理中
    const REQ_STATUS_SUCCESS    = 2; // 成功
    const REQ_STATUS_FAILURE    = 3; // 失败

    const TYPE_PROMOTION    = 1; // 营销补贴
    const TYPE_INTEREST     = 2; // 补息
    const TYPE_RETURNREPAY = 3; // 还代偿款

    static $typeDesc = [
        self::TYPE_PROMOTION    => '营销补贴',
        self::TYPE_INTEREST     => '补息',
        self::TYPE_RETURNREPAY => '还代偿款',
    ];

    /**
     *  批量读取需要执行的数据
     */
    public function pop($pidCount, $pidOffset)
    {
        // 动态提取三天前转账记录的id
        $startTime = microtime(true);
        $time = strtotime('-2 days') - 28800;
        $condition = sprintf(' create_time >= '.$time.' AND status=%d AND req_status=%d LIMIT 1', self::STATUS_AUDIT_PASS, self::REQ_STATUS_INIT);
        $sql = "SELECT * FROM firstp2p_nongdan WHERE id%{$pidCount}={$pidOffset} AND {$condition}";
        // 因为主从同步延时比较大，改走主库
        $result = $GLOBALS['db']->getRow($sql);
        if (empty($result))
        {
            //PaymentApi::log("NongdanWorker pop empty.");
            return array();
        }

        $data = $result;

        if ($this->setQueueStatus($data['id'], self::REQ_STATUS_SENDING) === false)
        {
            PaymentApi::log("NogndanWorker pop conflict. id:{$data['id']}, time:".(microtime(true) - $startTime));
            return false;
        }

        PaymentApi::log("NongdanWorker pop success. id:{$data['id']}, time:".(microtime(true) - $startTime));
        return $data;
    }

    private function setQueueStatus($id, $req_status)
    {
        $this->db->query("UPDATE firstp2p_nongdan SET req_status = {$req_status} WHERE id = '{$id}'");
        return $this->db->affected_rows() == 1 ? true : false;
    }


    public function updateRecord($data)
    {
        foreach ($data as $field => $val)
        {
            $this->{$field} = addslashes($val);
        }
        return $this->update();
    }

}
