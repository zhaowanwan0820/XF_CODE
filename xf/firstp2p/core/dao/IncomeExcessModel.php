<?php
namespace core\dao;

use core\service\InterestExtraService;
/**
 * 超额收益模块
 * @author 王传路 <wangchuanlu@ucfgroup.com>
 * Date: 2015-12-29
 */
class IncomeExcessModel  extends BaseModel {

    private $_tableName = '';//数据库表名

    public function __construct() {
        $this->_tableName = (new InterestExtraModel())->tableName();
        parent::__construct();
    }
    public function insert($data) {
        $this->db->autoExecute($this->_tableName, $data, "INSERT");
        return $this->db->insert_id();
    }

    public function update($data,$where) {
        return $this->db->autoExecute($this->_tableName, $data, 'UPDATE', $where);
    }

    /**
     * 获取要符合超额收益条件标
     * @param array $conds
     */
    public function getIncomeExcessDealsList($conds){

        //标的需要满足条件： 1、有效的标 2、还款中的标 3、普通标 4、定向委托投资
        $sql = " WHERE is_effect = 1 AND is_delete = 0 AND deal_status = 4 AND deal_type = 0 AND type_id = ". DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT);

        if(isset($conds['deal_id']) && !empty($conds['deal_id'])) {// 标id
            $sql .= " AND fd.id = " . intval($conds['deal_id']);
        }

        if(isset($conds['start_success_time']) && !empty($conds['start_success_time'])) {//满标起始时间
            $sql .= " AND fd.success_time >= ".intval($conds['start_success_time']);
        }

        if(isset($conds['end_success_time']) && !empty($conds['end_success_time'])) {//满标结束时间
            $sql .= " AND fd.success_time <= ".intval($conds['end_success_time']);
        }

        if(isset($conds['site_id']) && !empty($conds['site_id'])) {// 站点id
            $sql .= " AND fds.site_id = ".intval($conds['site_id']);
        }

        $sql .= " ) t1 WHERE t1.status IN (". implode(",", array(InterestExtraService::INTEREST_STATUS_N1,InterestExtraService::INTEREST_STATUS_0,100)) . ")"; //排除已经进入超额收益流程的标

        $sql_count  = "SELECT COUNT(*) FROM ( SELECT DISTINCT fd.id ,IFNULL(fie.status,100) as status, fie.income_type FROM firstp2p_deal fd LEFT JOIN firstp2p_deal_site fds ON fd.id = fds.deal_id LEFT JOIN ".
        " (SELECT * FROM ".$this->_tableName." WHERE income_type=". InterestExtraService::INCOME_TYPE_EXCESS .") fie ON fd.id = fie.deal_id".$sql;
        $totalNum = $this->countBySql($sql_count ,false ,true);

        $list = array();
        if ($totalNum > 0) {
            $sql_data = "SELECT * FROM ( SELECT DISTINCT fd.id,fd.rate,fd.success_time,fd.repay_start_time,fds.site_id,IFNULL(fie.status,100) as status, fie.income_type from firstp2p_deal fd LEFT JOIN firstp2p_deal_site fds ON fd.id = fds.deal_id LEFT JOIN ".
            " (SELECT * FROM ".$this->_tableName." WHERE income_type=". InterestExtraService::INCOME_TYPE_EXCESS.") fie ON fd.id = fie.deal_id" .$sql;
            if(isset($conds['_order']) && !empty($conds['_order'])) {
                $sql_data .= " ORDER BY " .$conds['_order'] . " " . (empty($conds['_sort']) ? 'ASC' : 'DESC');
            }
            $sql_data .= " LIMIT " . ($conds['page_num'] - 1) *$conds['page_size']  .",".$conds['page_size'];

            $list = $this->findAllBySqlViaSlave($sql_data ,true);
        }

        $return = array(
            'totalNum' => $totalNum,
            'list' => $list,
        );
        return $return;
    }

    /**
     * 获取要符合超额收益条件标
     * @param array $conds
     */
    public function getIncomeExcessAuditList($conds){
        //收益类型为超额收益 并且状态为待审核
        $sql = " WHERE  %s ie.income_type = ".InterestExtraService::INCOME_TYPE_EXCESS." AND ie.status = ".InterestExtraService::INTEREST_STATUS_0;
        $dealIdCond = "";

        if(isset($conds['deal_id']) && !empty($conds['deal_id'])) {// 标id
            $dealIdCond .= "deal_id = " . intval($conds['deal_id']) ." AND ";
        }
        $sql = sprintf($sql,$dealIdCond);

        $sql_count  = "SELECT COUNT(*) FROM ".$this->_tableName ." ie ". $sql;
        $totalNum = $this->countBySql($sql_count ,false ,true);

        $list = array();
        if ($totalNum > 0) {
            $sql_data = "SELECT ie.*,fds.site_id FROM ".$this->_tableName ." ie LEFT JOIN firstp2p_deal_site fds ON ie.deal_id = fds.deal_id ".$sql;
            if(isset($conds['_order']) && !empty($conds['_order'])){
                $sql_data .= " ORDER BY " .$conds['_order'] . " " . (empty($conds['_sort']) ? 'ASC' : 'DESC');
            }

            $sql_data .= " LIMIT " . ($conds['page_num'] - 1) *$conds['page_size']  .",".$conds['page_size'];
            $list = $this->findAllBySqlViaSlave($sql_data ,true);
        }

        $return = array(
            'totalNum' => $totalNum,
            'list' => $list,
        );
        return $return;
    }

    /**
     * 获取超额收益已支付历史
     * @param array $conds
     */
    public function getIncomeExcessHistory($conds) {

        //收益类型为超额收益 并且状态为待审核
        $sql = " WHERE  %s income_type = ".InterestExtraService::INCOME_TYPE_EXCESS." AND status IN ("
                . implode(",", array(InterestExtraService::INTEREST_STATUS_1,InterestExtraService::INTEREST_STATUS_2,InterestExtraService::INTEREST_STATUS_3)) . ")";
        $dealIdCond = "";
        if(isset($conds['deal_id']) && !empty($conds['deal_id'])) // 标id
        {
            $dealIdCond .= "deal_id = " . intval($conds['deal_id'])." AND ";
        }
        $sql = sprintf($sql,$dealIdCond);

        $sql_count  = "SELECT COUNT(*) FROM ".$this->_tableName . $sql;
        $totalNum = $this->countBySql($sql_count ,false ,true);

        $list = array();
        if ($totalNum > 0) {
            $sql_data = "SELECT * FROM ".$this->_tableName .$sql ." ORDER BY audit_time DESC";
            $sql_data .= " LIMIT " . ($conds['page_num'] - 1) *$conds['page_size']  .",".$conds['page_size'];
            $list = $this->findAllBySqlViaSlave($sql_data ,true);
        }

        $return = array(
                'totalNum' => $totalNum,
                'list' => $list,
        );

        return $return;
    }

    /**
     * 通过标id获取符合条件标
     * @param  $deal_id
     */
    public function getIncomeExcessInfoByDealId($deal_id ) {
        $sql = "SELECT * FROM ".$this->_tableName ." WHERE deal_id = ".$deal_id." AND income_type = ".InterestExtraService::INCOME_TYPE_EXCESS;
        $dealInfo = $this->findBySqlViaSlave($sql);
        return $dealInfo;
    }

    /**
     * 检查标的是否有状态为审核中的
     * @param array $dealIds
     * @return array
     */
    public function checkIsDealInAudit($dealIds) {
        $sql_count  = "SELECT COUNT(*) FROM ".$this->_tableName . " WHERE status = ". InterestExtraService::INTEREST_STATUS_0. " AND deal_id  IN (".implode(",",$dealIds).")";
        $totalNum = $this->countBySql($sql_count ,false ,true);
        return $totalNum > 0 ? true : false;
    }
}
