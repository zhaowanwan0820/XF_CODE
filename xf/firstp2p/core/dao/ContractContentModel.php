<?php
/**
 * ContractContentModel class file.
 *
 * @author wangyiming@ucfgroup.com
 */

namespace core\dao;
use libs\aerospike\AerospikeSaveObj;

/**
 * 合同内容类
 * 实际上是一个代理类，而不是真正意义上的model。此类隐藏了合同内容被拆分为多个表的事实，且这个类并不能用从基类里继承的ORM，如find,save等方法。
 *
 * @author wangyiming@ucfgroup.com
 */
class ContractContentModel extends BaseModel {
    // 暂时将内容表拆分为32个
    const NUM_OF_TABLE = 32;

    /**
     * 根据合同id获取内容所在表名
     * @param int $id 合同id
     * @return string
     */
    private function getTable($id) {
        return $this->tableName() . "_" . ($id % self::NUM_OF_TABLE);
    }

    /**
     * 根据合同id获取内容
     * @param int $id 合同id
     * @return string
     */
    public function find($id) {
        //双读代码。去掉注释即可。
        // 从aerospike集群中读合同
        $ret = $this->getFromAerospike($id);
        if(!empty($ret)){
            return $ret;
        }
        // 从写入失败表中读合同
        $bakData = $this->getFromBakTable($id);
        if(!empty($bakData)){
            \SiteApp::init()->aerospike->writeLog("get From Aerospike failed, success form bak_table [ id:$id ]");
            // 回写aerospike
            $this->setDataToAerospike($id,$bakData);
            return $bakData;
        }
        // 从数据库中获取数据
        $data = $this->findFromDB($id);
        if(!empty($data)){
            \SiteApp::init()->aerospike->writeLog("get From Aerospike,bak_table failed, success form table bak [ id:$id ]");
            // 回写aerospike
            $this->setDataToAerospike($id,$data);
            return $data;
        }
        \SiteApp::init()->aerospike->writeLog("get data failed [ id:$id ]");
        return false;
    }

    public function findFromDB($id) {
        $sql = sprintf("SELECT `content` FROM %s WHERE `id`='%d'", $this->getTable($id), $this->escape($id));
        $row = $this->findBySql($sql, array(), true);
        return $row['content'];
    }

    /**
     * 根据合同id数组获取内容
     * @param array $ids
     * @return array
     */
    public function mget($ids) {
        $res = array();
        foreach ($ids as $id) {
            $res[$id] = $this->find($id);
        }
        return $res;
    }

    /**
     * 新增合同内容
     * @param int $id 合同id
     * @param string $content 合同内容
     * @return bool
     */
    public function add($id, $content) {
        $aerospikeWrite = $this->setDataToAerospike($id,$content);
        if ( $aerospikeWrite == false){
            return $this->addToBakTable($id, $content);
        }
        return true;
        // 去掉双写代码。只写入aerospike中。
        //$sql = sprintf("INSERT INTO %s (`id`, `content`) VALUES ('%d', '%s')", $this->getTable($id), $this->escape($id), $this->escape($content));
        //return $this->execute($sql);
    }


    /**
     * 写入aerospike失败的时候向备份表中写入。防止丢失
     * @param int $id 合同id
     * @param string $content 合同内容
     * @return bool
     */
    private function addToBakTable($id, $content) {
        $sql = sprintf("INSERT INTO `firstp2p_contract_content_bak` (`contract_id`, `content`) VALUES ('%d', '%s')", $this->escape($id), $this->escape($content));
        return $this->execute($sql);
    }

     /**
     * 写入aerospike失败的时候向备份表中写入。防止丢失
     * @param int $id 合同id
     * @param string $content 合同内容
     * @return bool
     */
    private function getFromBakTable($id) {
        $sql = sprintf("SELECT * FROM `firstp2p_contract_content_bak` WHERE `contract_id`=%s", $this->escape($id));
        $row = $this->findBySql($sql, array(), true);
        return $row['content'];
    }   

    /**
     * 修改合同内容
     * @param int $id 合同id
     * @param string $content 合同内容
     * @return bool
     */
    public function update($id, $content) {
        
        // 从aerospike集群中读出来的合同，直读出来了。直接回写aerospike。
        $ret = $this->getFromAerospike($id);
        if(!empty($ret)){
            $aerospikeWrite = $this->setDataToAerospike($id,$content);
            if ( $aerospikeWrite == false){
                return $this->addToBakTable($id, $content);
            }
            return true;
        }
        // 从aerospike读取失败，就从写入失败表中读，如果读出来了，直接回写aerospike，
        $bakData = $this->getFromBakTable($id);
        if(!empty($bakData)){
            \SiteApp::init()->aerospike->writeLog("get From Aerospike failed, success form bak_table [ id:$id ]");
            // 回写aerospike
            $aerospikeWrite = $this->setDataToAerospike($id,$content);
            if ( $aerospikeWrite == false){
                return $this->addToBakTable($id, $bakData);
            }
            return true;
        }
        // 前面都没有读出来，只从原始数据库中读出来了。那么就更新原始数据库，同时回写aerospike
        $data = $this->findFromDB($id);
        if(!empty($data)){
            \SiteApp::init()->aerospike->writeLog("get From Aerospike,bak_table failed, success form table bak [ id:$id ]");
            // 回写DB
            $sql = sprintf("UPDATE %s SET `content`='%s' WHERE `id`='%d'", $this->getTable($id), $this->escape($content), $this->escape($id));
            $res = $this->execute($sql);
            // 回写aerospike
            $aerospikeWrite = $this->setDataToAerospike($id,$content);
            if ( $aerospikeWrite == false){
                return $this->addToBakTable($id, $bakData);
            }
            return true;
        }
        return $this->setDataToAerospike($id,$content);
    }

    /**
     * 删除一条合同内容
     * @param int $id 合同id
     * @return bool
     */
    public function remove($id) {
        $sql = sprintf("DELETE FROM %s WHERE `id`='%d'", $this->getTable($id), $this->escape($id));
        return $this->execute($sql);
    }

    /**
     * 根据id数组删除多条内容
     * @param array $ids
     * @return bool
     */
    public function mdel($ids) {
        $this->db->startTrans();
        try {
            foreach ($ids as $id) {
                if ($this->remove($id) === false) {
                    throw new \Exception("delete contract content error");
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /*
    *  从aerospike中读取
    */
    public function getFromAerospike($id){
        $key = \SiteApp::init()->aerospike->createKey(intval($id));
        if ( empty($key) ){
            return false;
        }
        $data = \SiteApp::init()->aerospike->get($key);
        if( empty($data) || empty($data['content']) ){
            return false;
        }
        $aeroContent = gzuncompress($data['content']->content);
        if ( empty($aeroContent) ){
            return false;
        }else{
            \SiteApp::init()->aerospike->writeLog("get contract success From Aerospike [ id:$id ]");
            return $aeroContent;
        }
    }

    /*
     * 向aerospike中写数据
     */
    public function setDataToAerospike($contractId,$content){
        $id = intval($contractId);
        $key = \SiteApp::init()->aerospike->createKey($id);
        $saveObj = new AerospikeSaveObj;
        $saveObj->content = gzcompress($content);
        $bins = array(
                "id"=>$id,
                "content"=>$saveObj,
                );
        $ret = \SiteApp::init()->aerospike->set($key,$bins);
        if( empty($ret) ){
            \SiteApp::init()->aerospike->writeLog("set Contract failed [ id:$id ]");
            return false;
        }else{
            \SiteApp::init()->aerospike->writeLog("set Contract success [ id:$id ]");
            return true;
        }
    }
} // END class ContractContentModel extends BaseModel
