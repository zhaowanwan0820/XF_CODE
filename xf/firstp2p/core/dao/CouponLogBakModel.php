<?php
/**
 * 消费卷删除备份
 *
 * @date 2014-11-04
 * @author xiaoan zhaoxiaoan@ucfgroup.com
 */

namespace core\dao;


/**
 * 优惠券消费记录dao
 * @package core/dao
 */
class CouponLogBakModel extends BaseModel {

    public function backupForDetele($id){
        
        $sql = 'REPLACE into '.DB_PREFIX. 'coupon_log_bak select * from '. DB_PREFIX ."coupon_log where id=%d";
       $sql = sprintf($sql,$this->escape($id));
       return $this->db->query($sql);
    }
}
