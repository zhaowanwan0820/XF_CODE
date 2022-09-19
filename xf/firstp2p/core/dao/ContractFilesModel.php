<?php
/**
 * @author <wangfei5@ucfgroup.com>
 * 用于合同的版本管理
 **/

namespace core\dao;

/**
 **/
class ContractFilesModel extends BaseModel {

    /**
    * 获取实际的表名称
    */
    private function getTableName($contractId){
        $tableSurfix = $contractId % 32;
        $tableName = sprintf('firstp2p_contract_files_%d',$tableSurfix);
        return $tableName;
    }

    /**
    * 添加一条纪录
    */
    public function addNewRecord($contractId,$groupId,$path){
        //$db = new \libs\db\MysqlDb(app_conf('DB_CONTRACT_PDF_HOST').":".app_conf('DB_CONTRACT_PDF_PORT'), app_conf('DB_CONTRACT_PDF_USER'),app_conf('DB_CONTRACT_PDF_PWD'),app_conf('DB_CONTRACT_PDF_NAME'),'utf8');
        $db = \libs\db\MysqlDb::getInstance('contract');
        $tableName = $this->getTableName($contractId);
        $sql = sprintf("INSERT INTO %s SET `contract_id`='%s', `group_id`='%s',`path`='%s',`create_time`='%s'",
                    $tableName, $this->escape($contractId), $this->escape($groupId), $this->escape($path), time());
        return $db->query($sql);
    }

    /**
    * 获取某个合同的id锁对应的所有fds中存在的文件信息,按照时间降排
    */
    public function getAllByContractId($contractId){
        //$db = new \libs\db\MysqlDb(app_conf('DB_CONTRACT_PDF_HOST').":".app_conf('DB_CONTRACT_PDF_PORT'), app_conf('DB_CONTRACT_PDF_USER'),app_conf('DB_CONTRACT_PDF_PWD'),app_conf('DB_CONTRACT_PDF_NAME'),'utf8');
        $db = \libs\db\MysqlDb::getInstance('contract');
        $tableName = $this->getTableName($contractId);
        $sql = sprintf("SELECT * FROM %s WHERE `contract_id`= %d ORDER BY create_time DESC",$tableName,$this->escape($contractId));
        $data = $db->getAll($sql);
        if(empty($data)){
            $data = array();
        }
        return $data;
    }
}
