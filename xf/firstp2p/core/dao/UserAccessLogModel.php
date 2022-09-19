<?php
/**
 * 用户访问日志记录表
 * @author 魏巍 <weiwei12@ucfgroup.com>
 **/

namespace core\dao;

use libs\db\Db;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;

class UserAccessLogModel extends BaseModel {

    /**
     * 连firstp2p_payment库
     */
    public function __construct()
    {
        $this->db = Db::getInstance('firstp2p_payment');
        parent::__construct();
    }

    /**
     * 记录日志
     * @param $log 用户访问日志
     */
    public function addLog($log) {
        if (empty($log)) {
            return false;
        }
        $data = array(
            'order_id'      => (int) $log['order_id'],
            'user_id'       => (int) $log['user_id'],
            'log_type'      => (int) $log['log_type'],
            'log_info'      => $log['log_info'],
            'log_time'      => $log['log_time'],
            'log_status'    => $log['log_status'],
            'log_id'        => $log['log_id'],
            'platform'      => $log['platform'],
            'client_ip'     => $log['client_ip'],
            'device'        => $log['device'],
            'site_id'       => $log['site_id'],
            'app_version'   => $log['app_version'],
            'extra_info'    => $log['extra_info'],
            'new_info'      => $log['new_info'],
        );
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
   }

   /**
    * 是否存在日志
    */
   public function logExist($orderId) {
        $condition = sprintf("`order_id` = %d", intval($orderId));
        $ret = $this->findBy($condition);
        if (empty($ret)) {
            return false;
        }
        return true;
   }

} // END class UserAccessLogModel extends BaseModel
