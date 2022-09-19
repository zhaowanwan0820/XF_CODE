<?php
/**
 * InterestExtraModel.class.php
 * 投资贴息表记录模块
 * @date 2015-10-29
 * @author wangzhen3 <wangzhen3@ucfgroup.com>
 */

namespace core\dao;


class InterestExtraModel extends BaseModel {

    public function insert($data)
    {
        $this->db->autoExecute($this->tableName(), $data, "INSERT");
        return $this->db->insert_id();
    }

    public function update($params,$where)
    {
        return $this->db->autoExecute($this->tableName(), $params, 'UPDATE', $where);
    }
    /**
     * 获取要符合贴息条件标数量
     * @param array $param
     */
    public function getInterestExtraDealsCount($param){
        $sql_where = $this->_sql_where($param);
        $sql_count = "select count(distinct fd.id) from firstp2p_deal fd left join firstp2p_deal_site fds on fd.id = fds.deal_id ".$sql_where;
        return $this->countBySql($sql_count ,false ,true);
    }

    /**
     * 获取要符合贴息条件标
     * @param array $param
     */
    public function getInterestExtraDealsList($param){
        $sql_where = $this->_sql_where($param);
        $sql_data = "select distinct fd.id,rate,fd.success_time,fd.repay_start_time,fds.site_id," . intval($param['interest_type']) . " as interest_type from firstp2p_deal fd left join firstp2p_deal_site fds on fd.id = fds.deal_id " .$sql_where;
        if(isset($param['_order']) && !empty($param['_order'])){
            $sql_data .= " order by " .$param['_order'] . " " . (empty($param['_sort']) ? 'asc' : 'desc');
        }
        if(isset($param['firstRow']) && $param['listRows'])
        {
            $sql_data .= " limit " .$param['firstRow'] .",".$param['listRows'];
        }

        return $this->findAllBySqlViaSlave($sql_data ,true);
    }

    /**
     * 通过标id批量获取要符合条件标
     * @param array $deal_ids
     */
    public function getInterestExtraDealsByDealIds($deal_ids ,$interest_time = 0){
        if(!empty($deal_ids))
        {
            $sql = "select id,rate,success_time,repay_start_time from firstp2p_deal where id in(".implode(',',$deal_ids).")" . " and (repay_start_time-success_time) >= '".intval($interest_time)."'" . " and is_effect = 1 and is_delete = 0 and deal_status=4 ";
            $sql .= " and  not exists (select deal_id from ".$this->tableName()." where firstp2p_deal.id = deal_id and income_type = 0)";//排除已经提交的标

            return $this->findAllBySqlViaSlave($sql ,true);
        }
        return false;
    }

    /**
     * 设置where条件
     * @param array $param
     * @return string
     */
    private function _sql_where($param)
    {
        $sql = " where is_effect = 1 and is_delete = 0 and deal_status=4 and rate>0 ";

        if(isset($param['deal_id']) && !empty($param['deal_id'])) // 标id
        {
            $sql .= " and fd.id = " . intval($param['deal_id']);
        }

        $sql .= " and  not exists (select deal_id from ".$this->tableName()." where fd.id = deal_id and income_type = 0)";//排除已经提交的标

        if(isset($param['start_success_time']) && !empty($param['start_success_time'])) //满标起始时间
        {
            $sql .= " and fd.success_time >= ".intval($param['start_success_time']);
        }

        if(isset($param['end_success_time']) && !empty($param['end_success_time'])) //满标结束时间
        {
            $sql .= " and fd.success_time <= ".intval($param['end_success_time']);
        }

        //贴息条件计算  (放款时间(repay_start_time) - 满标时间(success_time)) > 贴息时间
        if(isset($param['interest_time']))
        {
            $sql .= " and (fd.repay_start_time-fd.success_time) >= '".intval($param['interest_time'])."'";
        }

        if(isset($param['site_id']) && !empty($param['site_id'])) // 站点id
        {
            $sql .= " and fds.site_id = ".intval($param['site_id']);
        }

        return $sql;
    }

    /**
     * 通过条件获取数据
     * @param array $condition
     * @param string $field 列
     */
    public function getData($condition = "",$field = "*",$order = "",$limit ="")
    {
        $sql = "select ".$field." from ".$this->tableName();
        if(!empty($condition))
        {
            $where = "";
            foreach($condition as $key => $val)
            {
                $where .= $where? " and `" .$key . "`='" . $val . "'" : "`" .$key . "`='" . $val . "'";
            }
            $sql .=  " where " . $where;
        }
        if(!empty($order))
        {
            $sql .= $order;
        }

        if(!empty($limit))
        {
            $sql .= " limit " .$limit;
        }

        return $this->findAllBySqlViaSlave($sql ,true);
    }

    /**
     * 通过标id获取投资记录
     * @param int $deal_id
     * @return array
     */
    public function getDealLoadByDealId($deal_id)
    {
        $sql = "select id,deal_id,user_id,user_name,money from firstp2p_deal_load where deal_id=".intval($deal_id);
        return $this->findAllBySqlViaSlave($sql ,true);
    }

    /**
     * 通过deal_id获取贴息标
     * @param int $deal_id
     * @return array
     */
    public function getByDealId($deal_id,$income_type){
        $sql = "select rate,interest_days,status from ".$this->tableName()." where deal_id=" .$deal_id . " and income_type = ".intval($income_type);
        return $this->findBySqlViaSlave($sql);
    }
}
