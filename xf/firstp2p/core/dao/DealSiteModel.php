<?php
/**
 * DealSite class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

/**
 * DealSite class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class DealSiteModel extends BaseModel {
    
    /**
     * 插入一条deal_site数据
     * @param $data array 数据数组
     * @return float
     */
    public function insertDealSite($deal_id, $site_id){
         
        $deal_id = intval($deal_id);
        $site_id = intval($site_id);
        
        if($site_id <= 0 || $deal_id <= 0){
            return false;
        }
         
        $this->deal_id = $deal_id;
        $this->site_id = $site_id;
    
        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    public function getSiteByDeal($deal_id) {
        $condition = "deal_id=:deal_id";
        //slave db
        return $this->findBy($condition, '*', array(':deal_id' => $deal_id), true);
    }

    public function getAllSitesByDeal($deal_id) {
        $condition = "deal_id=:deal_id";
        return $this->findAllViaSlave($condition, true, '*', array(':deal_id' => $deal_id));
    }

    /**
     * 根据site_id获取订单id
     * @param int $site_id
     * @param bool $is_arr true-array false-string
     * @return string|array
     */
    public function getDealBySiteId($site_id, $is_arr=true) {
        $site_id = intval($site_id);
        $sql = "SELECT `deal_id` FROM %s WHERE `site_id`='%d'";
        $sql = sprintf($sql, $this->tableName(), $this->escape($site_id));
        $result = $this->findAllBySql($sql, $is_arr, null, true);
        if ($is_arr === true) {
            return $result;
        } else {
            return implode(",", $result);
        }
    }

    /**
     * getDealIdsBySites 
     * 获取制定分站下的标ID
     * 
     * @param mixed $siteStr 
     * @param mixed $flag 
     * @access public
     * @return void
     */
    public function getDealIdsBySites($sites, $flag = true)
    {
        if ($flag) {
	        $condition = "`site_id` IN (:site_id)";
        } else {
	        $condition = "`site_id` NOT IN (:site_id)";
        }
        $params = array(':site_id' => $sites);
        $rs = $this->findAllViaSlave($condition, true, 'deal_id', $params);
        $dealIds = array();
        foreach ($rs as $r) {
            $dealIds[] = $r['deal_id'];
        }
        return $dealIds;
    }

    /**
     * 判断一个标的是否属于某个分站
     * @param int $deal_id
     * @param int $site_id 
     * @return bool
     */
    public function isDealSiteExists($deal_id, $site_id) {
        $condition = sprintf("`site_id` = '%d' AND `deal_id` = '%d'", intval($site_id), intval($deal_id));
        $row = $this->findByViaSlave($condition);
        return empty($row) ? false : true;
    }
    
    /**
     * 通过标site_id 过滤标
     * @param string $deal_ids 含有的标
     * @param int $site_id
     * @param array $notIndealIds 不包括这些标
     * @return bool
     */
    public function filterDealIdsBySiteIdNotInDealIds($deal_ids = array(), $site_id = 0, $notIndealIds = array()) {
        
        $dealIds = array();
        
        $deal_ids = array_map('intval',$deal_ids);
        $site_id  = intval($site_id);
        
        $condition = "";
        if(!empty($site_id)){
            $condition = sprintf("`site_id` = '%d'", intval($site_id));
        }
        if(!empty($deal_ids)){
            $condition .= !empty($condition)? sprintf(" AND `deal_id` in(%s)", implode(',', $deal_ids)) : sprintf("`deal_id` in(%s)", implode(',', $deal_ids));
        }
        
        if(!empty($notIndealIds)){
            $condition .= !empty($condition)? sprintf(" AND `deal_id` not in(%s)",  implode(',', $notIndealIds)):sprintf("`deal_id` not in(%s)",  implode(',', $notIndealIds));
        }
        $result = $this->findAllViaSlave($condition ,true,'deal_id');
        
        if(!empty($result)){
            foreach ($result as $val){
                $dealIds[]= $val['deal_id'];
            }
        }
        
        return $dealIds;
    }

    public function getDealIdsBySiteId($siteId)
    {

        $condition = "`site_id` = (:site_id)";
        $params = array(':site_id' => $siteId);
        $rs = $this->findAllViaSlave($condition, true, 'deal_id', $params);
        $dealIds = array();
        foreach ($rs as $r) {
            $dealIds[] = $r['deal_id'];
        }
        return $dealIds;
    }
}
