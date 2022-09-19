<?php
/**
 * @author <wangfei5@ucfgroup.com>
 * 用于合同的版本管理,存储时增加合同编号的存储。
 **/

namespace core\dao\contract;

use core\dao\BaseModel;

/**
 **/
class ContractFilesWithNumModel extends BaseModel {

    // 未打
    const TSA_STATUS_START = 0;
    // 打了
    const TSA_STATUS_DONE = 1;
    // fdfs默认值
    const FDFS_DEFAULT = '';


    /**
    * 获取实际的表名称
    */
    private function getTableName($contractNum){
        // 简单hash crc32 后对64取余
        $crc = intval(abs(crc32($contractNum)));
        $tableSurfix = $crc % 64;
        $tableName = sprintf('firstp2p_contract_files_with_num_%s',$tableSurfix);
        return $tableName;
    }

    /**
    * 添加一条纪录
    * ext1,ext2,为冗余扩展字段，ext1为int 11 / ext2 为vchar(255)
    */
    public function addNewRecord($contractId,$contractNum,$groupId,$path,$ext1=null,$ext2=null,$sourceType=0){
        $this->db = \libs\db\Db::getInstance('contract');
        $tableName = $this->getTableName($contractNum);
        $sql = sprintf("INSERT INTO %s SET `contract_id`='%s', `contract_number`='%s',`group_id`='%s',`path`='%s',`create_time`='%s',`status`=%s,`source_type`=%d",
                    $tableName, $this->escape($contractId),$this->escape($contractNum), $this->escape($groupId), $this->escape($path), time(),self::TSA_STATUS_START,intval($sourceType));
        if(!empty($ext1)){
            $sql .= ",`ext1`=".$this->escape($ext1);
        }
        if(!empty($ext2)){
            $sql .= ",`ext2`=".$this->escape($ext2);
        }
        return $this->db->query($sql);
    }

    /**
    * 更新一条记录，带状态
    * ext1,ext2,为冗余扩展字段，ext1为int 11 / ext2 为vchar(255)
    */
    public function updatePathByContractId($contractId,$contractNum,$groupId,$path,$ext1=null,$ext2=null,$sourceType=0){
        $this->db = \libs\db\Db::getInstance('contract');
        $tableName = $this->getTableName($contractNum);
        $sql = sprintf("UPDATE  %s SET `contract_number`='%s',`group_id`='%s',`path`='%s',`update_time`='%s',`status`=%s",
                    $tableName, $this->escape($contractNum), $this->escape($groupId), $this->escape($path), time(), self::TSA_STATUS_DONE);
        if(!empty($ext1)){
            $sql .= ",`ext1`=".$this->escape($ext1);
        }
        if(!empty($ext2)){
            $sql .= ",`ext2`=".$this->escape($ext2);
        }
        $sql .= sprintf(" WHERE `contract_number`='%s' AND `status`=%s AND `source_type`=%d",$this->escape($contractNum),self::TSA_STATUS_START,intval($sourceType));
        return $this->updateRows($sql);
    }

    /**
    * 获取某个合同的id锁对应的所有fds中存在的文件信息,按照时间降排,重点在于是否已经入队
    */
    public function getAllByContractNum($contractNum,$contractId,$sourceType=0){
        $this->db = \libs\db\Db::getInstance('contract');
        $tableName = $this->getTableName($contractNum);
        $sql = sprintf("SELECT * FROM %s WHERE `contract_number`= '%s' AND `contract_id` = '%s' AND `source_type`=%d  ORDER BY create_time ASC",$tableName,$this->escape($contractNum),$this->escape($contractId),intval($sourceType));
        $data = $this->db->getAll($sql);
        if(empty($data)){
            $data = array();
        }
        return $data;
    }

    /**
     * 获取某个合同的id锁对应的所有fds中存在的文件信息,按照时间降排,重点在于是否打完
     */
    public function getSignedByContractNum($contractNum,$sourceType=0){
        $this->db = \libs\db\Db::getInstance('contract');
        $tableName = $this->getTableName($contractNum);
        $sql = sprintf("SELECT * FROM %s WHERE `contract_number`= '%s' AND `status`=%s AND `source_type`=%d   ORDER BY create_time ASC",$tableName,$this->escape($contractNum),self::TSA_STATUS_DONE,intval($sourceType));
        $data = $this->db->getAll($sql);
        if(empty($data)){
            $data = array();
        }
        return $data;
    }
}
