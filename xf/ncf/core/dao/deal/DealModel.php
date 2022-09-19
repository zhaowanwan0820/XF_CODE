<?php

namespace core\dao\deal;

use core\dao\BaseModel;
use core\dao\repay\DealLoanRepayModel;
use core\enum\DealExtEnum;
use core\enum\DealLoanTypeEnum;
use core\service\user\UserService;
use libs\db\Db;
use core\enum\DealEnum;
use core\enum\DealAgencyEnum;
use core\enum\UserEnum;
use NCFGroup\Common\Library\Idworker;
use libs\web\Url;
use libs\utils\Aes;
use libs\utils\Finance;
use core\dao\project\DealProjectModel;
use core\dao\repay\DealRepayModel;
use core\dao\repay\DealPrepayModel;
use core\service\contract\CategoryService;
use core\service\account\AccountService;
use core\service\deal\EarningService;
use core\service\bonus\BonusService;
use core\dao\deal\DealCateModel;
use libs\utils\XDateTime;
use core\service\project\ProjectService;
use core\dao\deal\DealGuarantorModel;
use core\service\deal\DealService;
use core\enum\UserAccountEnum;
use core\service\deal\state\FailState;
use core\dao\tag\TagModel;
use core\enum\AccountEnum;
use core\dao\jobs\JobsModel;
use core\service\bwlist\BwlistService;
use core\service\coupon\CouponService;
use libs\utils\Logger;

class DealModel extends BaseModel {

    public function getDealInfo($id,$isSlave=false){
        if($isSlave){
            $row = $this->findViaSlave($id);
        }else{
            $row = $this->find($id);
        }
// 目前不迁移已还清数据，暂时不查询move库
//        if(!$row){
//            $row = $this->getDealInfoFromMoved($id, $isSlave);
//        }
        return $row;
    }

    /**
     * 读取从库
     * @param $id
     * @param string $fields
     * @return \libs\db\Model
     */
    public function getDealInfoViaSlave($id,$fields = '*'){
        $row = $this->findViaSlave($id,$fields);
        if(!$row){
            $row = $this->getDealInfoFromMoved($id,true);
        }
        return $row;
    }


    public function getDealInfoFromMoved($id, $isSlave = false){

        $type = 'master';
        if ($isSlave){
            $type = 'slave';
        }
        $this->db = Db::getInstance(DealEnum::DEAL_NORMAL_DB_NAME,$type);
        $ret = $this->find($id);
        $this->db = $GLOBALS['db'];
        return $ret;
    }

    public function addDeal($projectId,$data){
        $data['project_id'] = $projectId;
        $this->saveDeal($data);
        return $this->id;
    }

    public function saveDeal($data){
        foreach($data as $k=>$v){
            $this->{$k} = $v;
        }
        $this->update_time = get_gmtime();
        return $this->save();
    }


    public function getProByApproveNum($approve_number,$is_slave = true){
        return $this->findBy("approve_number='{$approve_number}'",'*',array(),$is_slave);
    }

    /**
     *
     * 检查是否有借款类型
     * @param int  $type_id
     * @return bool
     */
    public function checkLoanTypeExist($type_id){
        if (!is_numeric($type_id)){
            return true;
        }
        $condition = "type_id=':type_id'";
        $param = array(
            ':type_id' => $type_id,
        );

        $ret = $this->findByViaSlave($condition,'id',$param);

        if ($ret == null){
            return false;
        }
        // 数据返回异常
        if (!is_object($ret)){
            return true;
        }
        $info = $ret->getRow();

        if (!empty($info['id'])) return true;

        $ret = $this->checkMoveDbLoanTypeExist($type_id);

        return $ret;
    }

    /**
     * 从备份库中查询
     * @param $type_id
     * @return bool
     * @throws \Exception
     */
    public function checkMoveDbLoanTypeExist($type_id){

        if (!is_numeric($type_id)){
            return true;
        }
        $condition = "type_id=':type_id'";
        $param = array(
            ':type_id' => $type_id,
        );
        // 从备份库中查
        $this->db = Db::getInstance(DealEnum::DEAL_MOVED_DB_NAME, 'slave');
        $ret = $this->findByViaSlave($condition,'id',$param);
        if ($ret == null){
            return false;
        }
        if (!is_object($ret)){
            return true;
        }
        $info = $ret->getRow();
        // 恢复到正常库
        $this->db = $GLOBALS['db'];
        if (empty($info['id'])){
            return false;
        }
        return true;
    }

    /**
     * 截标操作，将标的借款金额改为当前投资总额
     * @param int $deal_id
     * @return bool
     */
    public function updateMoney($deal_id) {
        $sql = "UPDATE " . $this->tableName() . " SET `borrow_amount`=`load_money`, `point_percent`='1', `deal_status`='2', `success_time`='" . get_gmtime() . "', `update_time`='" . get_gmtime() . "' WHERE `id`='{$deal_id}'";
        return $this->execute($sql);
    }


    /**
     * 根据项目id 及子标状态 查找子标集合
     * @param int $pro_id
     * @param array|int $deal_status
     * @param boolean $is_array 返回结构是否为数组
     * @return array
     *
     */
    public function getDealByProId($pro_id,$deal_status=array(), $is_array = true) {
        $sql = "SELECT * FROM %s  WHERE project_id = ':pro_id' AND `is_delete` = 0 AND `publish_wait` = 0";
        $param = array(':pro_id'=>$pro_id);

        if(is_array($deal_status) && count($deal_status) > 0) {
            $sql .= " AND `deal_status` IN (".implode(',', $deal_status).")";
        }elseif(is_numeric($deal_status)) {
            $sql .= " AND `deal_status` = ':deal_status'";
            $param = array(':deal_status'=>$deal_status);
        }

        $sql = sprintf($sql, $this->tableName() );
        $result = $this->findAllBySql($sql, $is_array,$param);
        if(!$result){
            return array();
        }
        return $result;
    }

    /**
     * @param int $project_id
     * @return floatval
     */
    public function getFullDealsMoneySumByProjectId($project_id)
    {
        $sql = sprintf('SELECT SUM(`borrow_amount`) AS `full_money` FROM %s WHERE `project_id` = %d AND `deal_status` = %d', $this->tableName(), $project_id, DealEnum::DEAL_STATUS_FULL);
        $res = DealModel::instance()->findBySql($sql);
        return empty($res) ? 0 : floorfix($res['full_money']);
    }

    /**
     * 计算需要拆分为多少期进行还款
     *
     * @return integer
     **/
    public function getRepayTimes() {
        $repay_times = 0;

        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
            $repay_times = $this->repay_time / 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
            $repay_times = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $repay_times = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $repay_times = $this->repay_time / 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {//等额本息固定日还款
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']) {
            $repay_times = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']) {
            $repay_times = $this->repay_time / 3;
        }
        return $repay_times;
    }

    /**
     * 获取加息返利年化折算系数
     * @param int $loantyp 还款方式
     * @return float
     */
    public function getRebateRate($loantype) {
        switch($loantype) {
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'] :
                $r = app_conf('COUPON_RABATE_RATIO_FACTOR_ANJI');
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH'] :
                $r = app_conf('COUPON_RABATE_RATIO_FACTOR_ANYUE');
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE'] :
                $r = app_conf('COUPON_RABATE_RATIO_FACTOR_XFFQ');
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH'] :
                $r = app_conf('COUPON_RABATE_RATIO_FACTOR_ANYUEBJ');
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH'] :
                $r = app_conf('COUPON_RABATE_RATIO_FACTOR_ANJIBJ');
                break;
            default:
                $r = 1;
                break;
        }

        return $r ? $r : 1;
    }

    /**
     * 计算两次还款的间隔周期, 根据不同的还款方式，结果可能是月份或者天数
     *
     * @return integer
     **/
    public function getRepayCycle()
    {
        $repay_cycle= 0;
        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
            $repay_cycle = 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
            $repay_cycle = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
            $repay_cycle = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $repay_cycle = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $repay_cycle = $this->repay_time;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $repay_cycle = 3;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
            $repay_cycle = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']) {
            $repay_cycle = 1;
        }else if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']) {
            $repay_cycle = 3;
        }
        return $repay_cycle;
    }


    /**
     * JIRA#1062 金额改为舍余处理
     * @param float $value
     * @param int $precision 小树位数
     * @return float
     */
    public function floorfix($value, $precision = 2) {
        $t = pow(10, $precision);
        if (!$t) {
            return 0;
        }
        $value = round($value*$t, 5);
        return (float)floor($value) / $t;
    }

    /**
     * 获得传入id中可以删除的 id
     *  没有投标或者 流标的可以删除
     *
     * @param array/int $deal_ids
     * @access public
     * @return array
     */
    public function getCanDeleteByIds($deal_ids) {
        $ids = '';
        if (is_array($deal_ids)) {
            $ids = implode(',', $deal_ids);
        } else {
            $ids = $deal_ids;
        }
        $sql = "select id from firstp2p_deal where id in ($ids) and load_money =0 or id in ({$ids}) and deal_status = 3";
        $rs =  $this->findAllBySql($sql,true);
        foreach ($rs as $row) {
            $rids[] = $row['id'];
        }
        return $rids;
    }

    /**
     * 插入一条借款数据
     * @param $data array 数据数组
     * @return float
     */
    public function insertDealData($data){

        if(empty($data)){
            return false;
        }
        //增加事物，确保合同服务与P2P复制标的数据一致性
        $this->db->startTrans();

        $this->setRow($data->getRow());
        $this->create_time = get_gmtime();
        $this->update_time = get_gmtime();
        $this->buy_count = 0;
        $this->load_money = 0;
        $this->repay_money = 0;
        $this->start_time = 0;
        $this->success_time = 0;
        $this->repay_start_time = 0;
        $this->last_start_time = 0;
        $this->next_repay_time = 0;
        $this->sort = 0;
        $this->is_has_loans = 0;
        $this->is_send_half_msg = 0;
        $this->is_during_repay = 0;
        $this->point_percent = 0;
        $this->approve_number = '';
        $this->deal_status = 0;
        $this->publish_wait = 1;
        $this->parent_id = -1;
        $this->is_effect = $data['is_effect']; //有效无效和原标保持一致
        $this->deal_type = $data['deal_type']; //标的类型和原标保持一致
        $this->report_type = $data['report_type']; //标的类型和原标保持一致
        $this->report_status = 0; //标的类型和原标保持一致

        if($this->insert()){
            $dealId = $this->db->insert_id();
            //如果是合同服务的标的,复制需要插入标的对应的合同分类
            if(is_numeric($data['contract_tpl_type'])){
                //合同服务设置标的模板分类ID
                $contractResponse = CategoryService::setDealCId(intval($dealId),intval($data['contract_tpl_type']));
                if($contractResponse != true){
                    $this->db->rollback();
                    throw new \Exception("合同服务调用失败");
                    return false;
                }
            }

            $this->db->commit();
            return $dealId;
        }else{
            $this->db->rollback();
            return false;
        }
    }

    public function updateReportStatus($dealId,$status){
        $this->db->query("UPDATE " . $this->tableName() . " SET  `report_status`={$status} WHERE `id` ='{$dealId}'");
        return $this->db->affected_rows();
    }

    /* 通过多个uid获取这些用户p2p未还款金额,只统计需要报备的p2p忽略以前的p2p标的借款
     * @param array $user_ids
     * @return float
     */
    public function getUnrepayP2pMoneyByUids(array $user_ids) {
        if(empty($user_ids)) {
            return 0;
        }

        $sql = sprintf("SELECT SUM(`borrow_amount` - `repay_money`) AS `m` FROM " . $this->tableName() .
            " WHERE `user_id` IN (%s) AND deal_type = 0 AND `is_delete`='0' AND deal_status IN (0,1,2,4,6)",
            implode(',',$user_ids));
        $res = $this->findBySql($sql);
        return $this->floorfix($res['m']);
    }

    /**
     * 将年利率转为日利率
     * @param float $rate_year
     * @param int $redemption_period 赎回周期
     * @return float
     */
    public function convertRateYearToDay($rate_year, $redemption_period){
        $rate_year = (float)$rate_year / 100;
        $rate_day = pow(1+$rate_year/360*$redemption_period, 1/$redemption_period) - 1;
        //$rate_day = pow(1+$rate_year, 1/360) - 1;
        return $this->floorfix($rate_day, 7);
    }

    /**
     * 根据还款方式，借款期限，计算需要拆分为多少期进行还款
     * @param int $loantype 还款方式
     * @param int $repay_time 借款期限
     * @return integer
     **/
    public static function getRepayTimesByLoantypeAndRepaytime($loantype, $repay_time)
    {
        $deal_model = new self();
        $deal_model->loantype = $loantype;
        $deal_model->repay_time = $repay_time;
        return $deal_model->getRepayTimes();
    }

    /**
     * 获取投资列表
     * @param $type string 类型分类（tab标签）
     * @param $sort array() 排序规则
     * @param $page int
     * @param $page_size int
     * @param $is_all_site bool
     * @param $is_display bool
     * @param bool $is_real_site 是true的话，site_id 不读配置，只读传过来的site_id,false 不做任何处理，默认是false
     * @param bool $count_only 默认为false，为减少数据访问以节省流量，count_only为true时仅获取count数据
     * @param bool $is_bxt 默认为false，如果为False的话，原先的逻辑，如果为true只显示变现通标的
     * @param bool $need_count 默认为true 为false的话不需要进行count统计
     * @return array("count"=>xx, "list"=>array(***))
     */
    public function getList($type, $sort, $page, $page_size, $is_all_site=false, $is_display=true, $site_id=0, $option=array(),$is_real_site=false, $count_only=false, $is_bxt=false,$need_count=true) {

        $sql = "SELECT `id`, `user_id`, `name`, `cate_id`, `type_id`, `agency_id`, `borrow_amount`, `min_loan_money`"
            .", `rate`, `start_time`, `enddate`, `deal_status`, `load_money`, `bad_time`, `success_time`, `is_update`"
            .", `loantype`, `manage_fee_rate`, `repay_time`, `income_fee_rate`, `deal_crowd`, `income_fee_rate`"
            .", `warrant`, `max_loan_money`, `success_time`, `deal_tag_name`, `type_match_row`, `min_loan_total_count`"
            .", `min_loan_total_amount`, `deal_tag_desc`,`deal_type`, `project_id`,`product_class_type`,`holiday_repay_type` FROM " .$this->tableName()
        ;

        $arr = $this->buildCondQuery($type, $sort, $is_all_site, $is_display, $site_id, $option, $is_real_site, $is_bxt);

        if ($arr === false) {
            return array('count' => 0, 'list' => array());
        }

        $condition = $arr['cond'];
        $params = $arr['param'];
        $order = " ORDER BY `id` DESC,`deal_status` DESC";

        // 展示顺序12045
        $arr_deal_status = array(
            DealEnum::DEAL_STATUS_PROCESSING,
            DealEnum::DEAL_STATUS_FULL,
            DealEnum::DEAL_STATS_WAITING,
            DealEnum::DEAL_STATUS_REPAY,
            //DealEnum::DEAL_STATUS_REPAID,
        );

        $start = ($page - 1) * $page_size;
        $end = $page * $page_size;
        $count = 0;
        $result = array();

        //逐个状态进行读取，满足条数则返回结果
        foreach ($arr_deal_status as $deal_status) {
            $cond = "`deal_status` = '{$deal_status}'" . $condition;
            $sql_cnt = "SELECT count(`id`) AS 'c' FROM " . $this->tableName() . " WHERE `deal_status` = '{$deal_status}' " . $condition;
            $r = $this->findBySql($sql_cnt, $params, true);
            $cnt = $r['c'];

            // 如果当前状态没有标的，直接检测下一状态
            if ($cnt <= 0) {
                continue;
            }

            $total = $count;
            $count += $cnt;

            // 标的列表排序，仅进行中的标的依次按产品分类('供应链','企业经营贷','消费贷','个体经营贷')、期限(短到长)、id;
            // DEAL_LIST_SORT_PRODUCTCLASSTYPE: 配置product_class_type id 排序序列; （field倒序，因match不到会赋值0排最前面); 生产typeid顺序：232,5,316,315,223
            // ID查询SQL:select id, name from firstp2p_deal_type_grade where name in ('个体经营贷','消费贷','企业经营贷','供应链') order by field(name,'个体经营贷','消费贷','企业经营贷','供应链')
            if ($deal_status == DealEnum::$DEAL_STATUS['progressing']) {
                $conf_sort_product_class_type = app_conf('DEAL_LIST_SORT_PRODUCTCLASSTYPE');
                if (!empty($conf_sort_product_class_type)) {
                    $order = " ORDER BY field(`product_class_type`,{$conf_sort_product_class_type}) DESC, if(`loantype`=5, `repay_time`, `repay_time`*30), `id` DESC ";
                }
            }

            if ($start < $count && $end > $total) {
                $size = $end - $total;
                $size = $size > $page_size ? $page_size : $size;

                $start_tmp = $start > $total ? $start - $total : 0;

                $limit = " LIMIT {$start_tmp}, {$size}";
                $sql_tmp = $sql . " WHERE " . $cond . $order . $limit;

                $data = $this->findAllBySqlViaSlave($sql_tmp, true, $params);
                $c = count($data);

                foreach($data as $v) {
                    $result[] = $v;
                }

                // 剩余查询量减少本次查询量
                $page_size -= $c;
                if ($page_size <= 0) {
                    // 剩余查询量为0时，如果需要计数，继续循环，但只执行计数统计；否则直接退出循环返回结果
                    if ($need_count) {
                        continue;
                    } else {
                        break;
                    }
                }
            }
        }

        if ($count_only === false) {
            return array("count"=>$count, "list"=>$result);
        } else {
            return array("count"=>$count);
        }
    }

    /**
     * 获取进行中的投资列表
     * @param $sort array() 排序规则 可以安装剩余金额排序 字段:needMoney
     * @param $page int
     * @param $page_size int
     * @return array
     */
    public function getDealListForProcessing($sort, $page, $pageSize) {

        $sql = "SELECT `id`, `user_id`, `name`, `cate_id`, `type_id`, `agency_id`, `borrow_amount`, `min_loan_money`"
            .", `rate`, `start_time`, `enddate`, `deal_status`, `load_money`, `bad_time`, `success_time`, `is_update`"
            .", `loantype`, `manage_fee_rate`, `repay_time`, `income_fee_rate`, `deal_crowd`, `income_fee_rate`"
            .", `warrant`, `max_loan_money`, `success_time`, `deal_tag_name`, `type_match_row`, `min_loan_total_count`"
            .", `min_loan_total_amount`, `deal_tag_desc`,`deal_type`, `project_id` , `holiday_repay_type`, `product_class_type`, `borrow_amount`-`load_money` as`needMoney` FROM " .$this->tableName()
        ;

        // sort 传了也没用，底层没处理
        $arr = $this->buildCondQuery(false, false);

        if ($arr === false) {
            return array();
        }

        $condition = $arr['cond'];
        $params = $arr['param'];
        $condition = '`deal_status` = '.DealEnum::DEAL_STATUS_PROCESSING.$condition;
        $order = '';
        if ($sort) {
            $order = $sort;
        }

        $start = ($page - 1) * $pageSize;

        $limit = " LIMIT {$start}, {$pageSize}";
        $sql = $sql. " WHERE {$condition} {$order} {$limit}";
        return $this->findAllBySqlViaSlave($sql, true, $params);

    }

    /**
     * 根据参数拼装查询条件
     * @param $type string 类型分类（tab标签）
     * @param $sort array() 排序规则
     * @param $is_all_site bool
     * @param $is_display bool
     * @param $site_id int
     * @param $option array() 排序规则
     * @param bool $is_real_site 是true的话，site_id 不读配置，只读传过来的site_id,false 不做任何处理，默认是false
     * @param bool $is_bxt 默认为false，如果为False的话，原先的逻辑，如果为true只显示变现通标的
     * @return array
     */
    protected function buildCondQuery($type, $sort, $is_all_site=false, $is_display=false, $site_id=0, $option=array(), $is_real_site=false, $is_bxt=false) {
        $condition = " AND `is_effect`='1' AND `is_delete`='0' AND `publish_wait` = 0";

        if ($is_display == true) {
            $condition .= " AND `is_visible`='1'";
        }

        if ($type) {
            $condition .= " AND `type_id` IN (:type)";
        }

        if(isset($option['show_crowd_specific']) && $option['show_crowd_specific'] == 0){
            $condition .= " AND `deal_crowd` != 2";
        }

        // 产品类型二级分类ID
        if(isset($option['product_class_type']) && $option['product_class_type'] != 0){
            $condition .= " AND `product_class_type` = ".intval($option['product_class_type']);
        }

        // 借款客群
        if(isset($option['loan_user_customer_type']) && $option['loan_user_customer_type'] != 0){
            $condition .= " AND `loan_user_customer_type` = ".intval($option['loan_user_customer_type']);
        }

        if ($is_real_site === false && $is_all_site === false) {
            $site_id = formatConf(app_conf('DEAL_SITE_ALLOW'));
            $site_id = $site_id==0 ? 0 : $site_id;
        }

        // JIRA#2994 特定标的不展示
        if (app_conf('DEAL_ID_FORBIDDEN_LIST')) {
            $ids_forbidden = explode(',', app_conf('DEAL_ID_FORBIDDEN_LIST'));
            $condition .= " AND `id` NOT IN (" . implode(",", $ids_forbidden) . ")";
        }


        $dealCustomStr = $this->getListBatchImportByDealCrowd(2);

        if (!empty($dealCustomStr)) {
            // 是否读取定制标
            if (isset($option['is_read_deal_custom_user']) && $option['is_read_deal_custom_user'] == true) {
                $condition .= " AND `id` IN ($dealCustomStr)";
            } else {
                $condition .= " AND `id` NOT IN ($dealCustomStr)";
            }
        }
        // deal_tag_name,根据标签类型获取
        if (isset($option['deal_tag_name']) && is_array($option['deal_tag_name']) && !empty($option['deal_tag_name'])){
            $tmp = array();
            foreach( $option['deal_tag_name'] as $oneTag){
                $tmp[] = "deal_tag_name='{$oneTag}'";
            }
            $condition .= sprintf(' AND (%s)',implode(' OR ',$tmp));
        }

        $params = array(
            ":siteIds" => $site_id,
            //":bxtTypeId" => $bxtTypeId,
            ":type" => $type,
            //":prev_page" => ($page - 1) * $page_size,
            //":curr_page" => $page_size
        );

        // 机构相关的字段有不同key，对应查询
        foreach (DealEnum::$agencyKey as $keyName) {
            if (!empty($option[$keyName])) {
                $params[":{$keyName}"] = $option[$keyName];
                $condition .= " AND `{$keyName}` = :{$keyName}";
            }
        }

        if ($is_real_site === true || ($is_real_site === false && $is_all_site === false)){
            // 如若需要匹配站点但站点为空，数据为空，没意义,直接返回
            if ($site_id == 0) {
                return false;
                //return array('count' => 0, 'list' => array());
            }
            $condition .= " AND site_id IN (:siteIds) ";
        }

        return array('cond'=>$condition, 'param'=>$params);
    }

    /**
     * 获取 投资限定条件等于34
     * @param $return_type 1 返回数组，2返回以逗号隔开的字符串
     */
    public function getListBatchImportByDealCrowd($return_type = 1){

        $sql = "SELECT id FROM firstp2p_deal d WHERE d.deal_status IN (1,2) AND d.`is_effect`='1' AND d.`is_delete`='0' AND d.`publish_wait` = 0
            AND d.`is_visible`='1' AND d.deal_crowd=".DealEnum::DEAL_CROWD_CUSTORM;

        $result = $this->findAllBySqlViaSlave($sql, true);
        if ($return_type == 2){
            $str = '';
            if (!empty($result)){
                $dealIds = array();
                foreach($result as $v){
                    $dealIds[$v['id']] = $v['id'];
                }

                if (!empty($dealIds)){
                    $str = implode(',',$dealIds);
                }
            }
            return $str;
        }
        return $result;
    }

    public function handleDealNew($deal, $data_type=0) {
        $deal['old_name'] = $deal['name'];
        $deal['name'] = msubstr($deal['name'], 0, 40); //坑
        $deal['url'] = Url::gene("d", "", Aes::encryptForDeal($deal['id']), true);
        $deal['deal_tag'] = explode(',', $deal['deal_tag_name']) ;
        $i_tag =0;
        foreach($deal['deal_tag'] as $tag){
            if($i_tag){
                $deal['deal_tag_name'.$i_tag] = $tag;
            }else{
                $deal['deal_tag_name'] = $tag;
            }
            $i_tag++;
        }
        unset($deal['deal_tag']);
        // 格式化借款数据
        $deal['borrow_amount_format'] = format_price($deal['borrow_amount']);
        $deal['borrow_amount_origin'] = $deal['borrow_amount'];
        $deal['borrow_amount_format_detail'] = format_price($deal['borrow_amount'] / 10000,false);
        $deal['borrow_amount_wan_int'] = format_price($deal['borrow_amount'] / 10000, false)."万";
        $deal['rate_foramt'] = number_format($deal['rate'],2);

        $deal['remain_time'] = $deal['start_time'] + $deal['enddate'] * 24 * 3600 - get_gmtime();

        //投标剩余时间
        if ($deal['deal_status'] != 1 || $deal['remain_time'] <= 0) {
            $deal['remain_time_format'] = "0" . DealEnum::DAY . "0" . DealEnum::HOUR . "0" . DealEnum::MINUTE;
        } else {
            $d = intval($deal['remain_time'] / 86400);
            $h = floor($deal['remain_time'] % 86400 / 3600);
            $m = floor($deal['remain_time'] % 3600 / 60);
            $deal['remain_time_format'] = $d . DealEnum::DAY . $h . DealEnum::HOUR . $m . DealEnum::MINUTE;
        }

        //还需多少钱
        $deal['need_money_decimal'] = round($deal['borrow_amount'] - $deal['load_money'], 2);
        //起投金额大于剩余投资金额，起投金额等于最低起投金额
        if(bccomp($deal['need_money_decimal'], $deal['min_loan_money'], 2) == -1){
            $deal['min_loan_money'] = $deal['need_money_decimal'];
        }

        $deal['need_money'] = format_price($deal['need_money_decimal']);
        $deal['need_money_detail'] = format_price($deal['need_money_decimal'], false);
        $deal['need_money_origin'] = $deal['need_money_decimal'];
        $deal['min_loan_money_format'] = $deal['min_loan_money'] >= 10000 ?
            format_price($deal['min_loan_money'] / 10000, false)."万" : format_price($deal['min_loan_money'], false);
        $deal['min_loan_money_format_yuan'] = format_price($deal['min_loan_money']);

        // 流标时间
        if (!empty($deal['bad_time'])) {
            if (date("Y", $deal['bad_time']) != date("Y", get_gmtime())) {
                $bad_time_format = "Y年m月d日";
            } else {
                $bad_time_format = "m月d日";
            }
            $deal['flow_standard_time'] = to_date($deal['bad_time'], $bad_time_format);
        } else {
            $deal['flow_standard_time'] = "-";
        }
        // 满标时间
        if (!empty($deal['success_time'])) {
            if (date("Y", get_gmtime()) != date("Y", $deal['success_time'])) {
                $su_time_format = "Y年m月d日";
            } else {
                $su_time_format = "m月d日";
            }
            $deal['full_scale_time'] = to_date($deal['success_time'], $su_time_format);
        } else {
            $deal['full_scale_time'] = "-";
        }

        $deal['loantype_name'] = str_replace('收益', '利息', $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']]);
        //修改此处为借款用途的图标
        if (isset($deal['type_info']['icon']) && $deal['type_info']['icon']) {
            $deal['icon'] = str_replace("./public/images/dealtype/","./static/img/dealtype/",$deal['type_info']['icon']);
        }

        //后台填的年利率
        $deal['int_rate'] = $deal['rate'];

        if($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']){
            $period_income_rate = (1 + $deal['int_rate']/12/100 * $deal['repay_time']) * (1 - $deal['manage_fee_rate'] /12/100 * $deal['repay_time']) -1;
            $deal['rate'] = round($period_income_rate * 12 / $deal['repay_time']*100, 2);
            $deal['rate'] = number_format($deal['rate'], 2) . "%";
        } else {
            //出借人年化收益率
            $deal['rate'] = ($deal['income_fee_rate'] > 0) ? $deal['income_fee_rate']:$this->get_invest_rate_data($deal['loantype'], $deal['repay_time']);
            $deal['rate'] = number_format($deal['rate'], 2) . "%"; // 把后台各项费率小数位数位数放开到5位，前端显示放2位，四舍五入 --20140102
        }
        $deal['rate_show'] = number_format( (float)$deal['rate'], 2);
        $deal['repay_time_array'] = $this->numberToArrayForPic($deal['repay_time']);

        //获取此标的投资人群
        $deal['crowd_str'] = $GLOBALS['dict']['DEAL_CROWD'][$deal['deal_crowd']];
        //后台修改的借款年利率
        $deal['deal_rate'] = number_format($deal['int_rate'], 2) .'%';

        $deal['show_focus'] = 1;

        // 订单附加信息
        //$deal_ext = DealExtModel::instance()->findByViaSlave("`deal_id`='{$this->escape($deal['id'])}'");
        $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);

        if (!empty($deal_ext)) {
            $ext_info = $deal_ext->getRow();
            foreach ($ext_info as $k => $v) {
                if (!isset($deal[$k])) {
                    $deal[$k] = $v;
                }
            }
        }

        $deal['income_ext_rate'] = number_format($deal['income_float_rate']+$deal['income_subsidy_rate'], 2, ".", "");
        $deal['income_base_rate'] = number_format($deal['income_base_rate'], 2, ".", "");
        $deal['income_float_rate'] = number_format($deal['income_float_rate'], 2, ".", "");
        $deal['income_subsidy_rate'] = number_format($deal['income_subsidy_rate'], 2, ".", "");
        $deal['income_fee_rate_format'] = number_format($deal['income_fee_rate'], 2);
        $deal['income_total_show_rate'] = number_format($deal['rate'] + $deal['income_subsidy_rate'], 2, ".", "");
        $deal['rate_show_array'] = $this->numberToArrayForPic($deal['income_total_show_rate']);
        $deal['max_rate'] = number_format( (float)$deal['rate'], 2);
        //订单状态文字
        $deal['deal_status_text'] = $this->getDealStatusText($deal);

        if ($data_type != 1) {
            $deal = $this->getEarningsInfo($deal);
        }

        // 开标时间，如果开标时间未到，则赋值给格式化后的开标时间，模板根据格式化后的开标时间判断是否显示开标时间
        if (!empty($deal['start_loan_time']) && $deal['start_loan_time']>get_gmtime() && $deal['deal_status']==0) {
            if (date("Y", get_gmtime()) != date("Y", $deal['start_loan_time'])) {
                $st_time_format = "Y-m-d H:i";
            } else {
                $st_time_format = "m-d H:i";
            }
            $deal['start_loan_time_format'] = to_date($deal['start_loan_time'], $st_time_format);
            //} else {
            //    $deal['start_loan_time_format'] = "-";
        }

        $deal = $this->UserDealStatusSwitch($deal);
        $deal['is_crowdfunding'] = ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) ? 1 : 0;

        // JIRA#3844 获取项目相关信息 by fanjingwen
        $project_obj =  DealProjectModel::instance()->findViaSlave($deal['project_id']);
        if (!empty($project_obj)) {
            $project_info = $project_obj->getRow();
            $deal['project_name']   =  $project_info['name'];
            $deal['product_name']   =  $project_info['product_name'];
            $deal['product_class']  =  $project_info['product_class'];
        }

        \libs\utils\Logger::info("dealModel deal handleDealNew  deal:" . json_encode($deal));
        return $deal;
    }

    public function handleDealForDiscount($deal) {
        $deal['old_name'] = $deal['name'];
        $deal['name'] = msubstr($deal['name'], 0, 40); //坑
        // 格式化借款数据
        $deal['borrow_amount_wan_int'] = format_price($deal['borrow_amount'] / 10000, false)."万";
        $deal['rate_foramt'] = number_format($deal['rate'],2);

        //还需多少钱
        $deal['need_money_decimal'] = round($deal['borrow_amount'] - $deal['load_money'], 2);
        //起投金额大于剩余投资金额，起投金额等于最低起投金额
        if(bccomp($deal['need_money_decimal'], $deal['min_loan_money'], 2) == -1){
            $deal['min_loan_money'] = $deal['need_money_decimal'];
        }

        $deal['need_money_detail'] = format_price($deal['need_money_decimal'], false);
        $deal['min_loan_money_format'] = $deal['min_loan_money'] >= 10000 ?
            format_price($deal['min_loan_money'] / 10000, false)."万" : format_price($deal['min_loan_money'], false);

        $deal['loantype_name'] = str_replace('收益', '利息', $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']]);

        $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);

        if (!empty($deal_ext)) {
            $ext_info = $deal_ext->getRow();
            foreach ($ext_info as $k => $v) {
                if (!isset($deal[$k])) {
                    $deal[$k] = $v;
                }
            }
        }

        $deal['income_ext_rate'] = number_format($deal['income_float_rate']+$deal['income_subsidy_rate'], 2, ".", "");
        $deal['income_base_rate'] = number_format($deal['income_base_rate'], 2, ".", "");
        $deal['income_total_show_rate'] = number_format($deal['rate'] + $deal['income_subsidy_rate'], 2, ".", "");

        // 开标时间，如果开标时间未到，则赋值给格式化后的开标时间，模板根据格式化后的开标时间判断是否显示开标时间
        if (!empty($deal['start_loan_time']) && $deal['start_loan_time']>get_gmtime() && $deal['deal_status']==0) {
            if (date("Y", get_gmtime()) != date("Y", $deal['start_loan_time'])) {
                $st_time_format = "Y-m-d H:i";
            } else {
                $st_time_format = "m-d H:i";
            }
            $deal['start_loan_time_format'] = to_date($deal['start_loan_time'], $st_time_format);
            //} else {
            //    $deal['start_loan_time_format'] = "-";
        }

        // JIRA#3844 获取项目相关信息 by fanjingwen
        $project_obj =  DealProjectModel::instance()->findViaSlave($deal['project_id']);
        if (!empty($project_obj)) {
            $project_info = $project_obj->getRow();
            $deal['project_name']   =  $project_info['name'];
            $deal['product_name']   =  $project_info['product_name'];
            $deal['product_class']  =  $project_info['product_class'];
        }

        \libs\utils\Logger::info("dealModel deal handleDealForDiscount  deal:" . json_encode($deal));
        return $deal;
    }


    /**
     * 把利率金额数字转成数组，供数字图片化使用
     *
     * @param $str 利率金额数字字符串
     * @return bool|mixed
     */
    public function numberToArrayForPic($str) {
        if (empty($str)) {
            return false;
        }
        $result = str_split($str);
        $search = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', ',');
        $replace = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'point', 'comma');
        return str_replace($search, $replace, $result);
    }

    /**
     * 获取投资年利率（收益率，面向投资人）
     */
    public   function get_invest_rate_data($repay_mode, $repay_period) {

        if($repay_mode == 5)
        {
            return $GLOBALS['dict']['DAY_ONCE_RATE'];
        }
        else
        {
            $repay_mode = $GLOBALS['dict']['INTEREST_REPAY_MODE'][$repay_mode];
            $repay_period = $GLOBALS['dict']['REPAY_PERIOD'][$repay_period];
            if($repay_mode && $repay_period){
                $sql ="SELECT ". $repay_period . " FROM " . DB_PREFIX . "deploy WHERE process='" . $repay_mode . "'";
                $res = $GLOBALS['db']->get_slave()->getRow($sql);
                return $res[$repay_period];
            }
            return 0;
        }
    }

    /**
     * 获取订单状态
     *
     * @param $deal
     * @return bool|string
     */
    public function getDealStatusText($deal) {
        if (empty($deal)) {
            return false;
        }
        /*if (!isset($deal['guarantor_status'])) {
            $deal['guarantor_status'] = DealGuarantorModel::instance()->checkDealGuarantorStatus(intval($deal['id']));
        }*/
        $result = "";
        //if ($deal['is_update'] == 1 || $deal['deal_status'] == 0 || $deal['guarantor_status'] != 2) {
        if ($deal['is_update'] == 1 || $deal['deal_status'] == 0) {
            $result = "等待确认";
        } elseif ($deal['deal_status'] == 2) {
            $result = "满标";
        } elseif ($deal['deal_status'] == 3) {
            $result = "流标";
        } elseif ($deal['deal_status'] == 4) {
            $result = "还款中";
        } elseif ($deal['deal_status'] == 5) {
            $result = "已还清";
        } else {
            $result = "进行中";
        }
        return $result;
    }

    /**
     * 检查是否已经满标
     *
     * @return bool
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function isFull()
    {
        $deal = $this->find($this->id, 'deal_status');
        if ($deal['deal_status'] == 2) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 修改首页 列表页面 缓存引起标的状态有问题的情况
     * @param $deal
     * @return mixed
     */
    public function UserDealStatusSwitch($deal){
        //PaymentApi::log('DealModel.UserDealStatusSwitch.1:'.$deal['id'].'-'.$deal['deal_status'].'-'.$deal['deal_type']);
        //处理首页和列表页 满标后的列表显示，jira 1156
        $deal['bid_flag'] = 1;//主要是控制链接是否出现的
//        $user_bid_deals = DealLoadModel::instance()->getUserLoadDealId();
        //默认未投

        $userId = (!empty($GLOBALS['user_info'])) ? $GLOBALS['user_info']['id'] : 0;
        $deal['have_bid_deal'] = $this->haveBidDeal($userId, $deal['id']) ? 1 : 0;
//        if($user_bid_deals && in_array($deal['id'], explode(',', $user_bid_deals))){
//            //当前登录用户 已投该标
//            $deal['have_bid_deal'] = 1;
//        }
        //PaymentApi::log('DealModel.UserDealStatusSwitch.1_1:'.$deal['have_bid_deal']);
        $deal['deal_compound_status'] = 0;//利滚利 未投资状态
        if(in_array($deal['deal_status'], array(2,4,5))){
            $deal['bid_flag'] = 0;
            if($deal['have_bid_deal'] == 1){
                $deal['bid_flag'] = 1;
                $deal['deal_compound_status'] = 1;
            }
        }

        return $deal;
    }

    /**
     * 判断用户是否投资过某标的
     * @param $userId int 用户id
     * @param $dealId int 标id
     * @return bool
     */
    public function haveBidDeal($userId, $dealId) {
        if (empty($userId) || $dealId) {
            return false;
        }

        $count = DealLoadModel::instance()->countViaSlave("`user_id`='{$userId}' AND `deal_id`='{$dealId}'");
        return $count > 0 ? true : false;
    }

    /**
     * 获取定制的标
     * @param bool $is_all_site
     * @param bool $is_display
     * @param int $site_id
     * @param array $option
     * @param bool $is_real_site
     */
    public function getDealCustomUserList($is_all_site=false, $is_display=false, $site_id=0, $option=array(),$is_real_site=false){

        $sql = "SELECT `id`, `user_id`, `name`, `cate_id`, `type_id`, `agency_id`, `borrow_amount`, `min_loan_money`"
            .", `rate`, `start_time`, `enddate`, `deal_status`, `load_money`, `bad_time`, `success_time`, `is_update`"
            .", `loantype`, `manage_fee_rate`, `repay_time`, `income_fee_rate`, `deal_crowd`, `income_fee_rate`"
            .", `warrant`, `max_loan_money`, `success_time`, `deal_tag_name`, `type_match_row`, `min_loan_total_count`"
            .", `min_loan_total_amount`, `deal_tag_desc`,`deal_type`, `project_id` FROM " .$this->tableName()
        ;

        $arr = $this->buildCondQuery(false, false, $is_all_site, $is_display, $site_id, $option, $is_real_site, false);

        if ($arr === false) {
            return array();
        }

        $cond = $arr['cond'];
        $params = $arr['param'];
        $result = array();
        $sql_tmp = $sql . " WHERE 1=1 " .$cond;

        $data = $this->findAllBySqlViaSlave($sql_tmp, true, $params);

        foreach($data as $v) {
            $result[] = $v;
        }

        return $result;

    }

    /**
     * 更新标的放款中间状态
     * @param unknown $deal_id
     * @param unknown $status
     * @return boolean|Ambigous <number, boolean>
     */
    public function changeLoansStatus($deal_id, $status){
        if(!in_array($status, array(DealEnum::DEAL_IS_DOING_YES, DealEnum::DEAL_IS_HAS_LOANS_ING))){
            return false;
        }
        $old_status = ($status == DealEnum::DEAL_IS_DOING_YES) ? DealEnum::DEAL_IS_HAS_LOANS_ING : 0;
        $sql = "UPDATE `%s` SET `is_has_loans`='%d',`update_time`='%s' WHERE `id`='%d' AND `is_has_loans` = '%d'";
        $sql = sprintf($sql, $this->tableName(), $this->escape($status), get_gmtime(), $this->escape($deal_id), $old_status);
        return $this->updateRows($sql);
    }

    /**
     * 更新标的还款中状态
     * @param int $deal_id
     * @return boolean|Ambigous <number, boolean>
     */
    public function changeDealStatus($deal_id){
        $old_status = DealEnum::DEAL_STATUS_FULL;
        $status = DealEnum::DEAL_STATUS_REPAY;
        $sql = "UPDATE `%s` SET `deal_status`='%d',`repay_start_time`='%s' WHERE `id`='%d' AND `deal_status` = '%d'";
        $sql = sprintf($sql, $this->tableName(), $this->escape($status), to_timespan(date("Y-m-d")), $this->escape($deal_id), $old_status);
        return $this->updateRows($sql);
    }

    /**
     * 更新标的开始还款时间
     * @param int $deal_id
     * @param int $time
     * @return boolean
     */
    public function changeRepayStartTime($dealID, $time){
        if (empty($time)) {
            return false;
        }
        $data = array(
            'repay_start_time' => intval($time),
        );
        $conditon = " `id` = " . intval($dealID);
        return $this->updateBy($data, $conditon);
    }

    /**
     * 根据投资金额计算预期收益
     * @param $principal float 本金
     * @param $is_preview bool 是否是预览收益
     * @return float 收益
     */
    public function getEarningMoney($principal) {
        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) { //公益募捐
            return 0;
        }
        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) { //按月付息
            $earning = $principal * $this->income_fee_rate / 100 / 12 * $this->getRepayTimes();
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) { //按季付息
            $earning = $principal * $this->income_fee_rate / 100 / 4 * $this->getRepayTimes();
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) { //等额本息固定日还款
            $earning =  0;
            $repay_time = $this->repay_start_time; //中间变量，保存各期还款时间
            if(intval($repay_time) <=0) {
                $repay_time = to_timespan(date("Y-m-d"));
            }
            $deal_ext = \core\dao\deal\DealExtModel::instance()->getInfoByDeal($this->id, false);
            $first_repay_day = $deal_ext['first_repay_interest_day'];
            $left_need_repay_principal = $principal;
            $rate = $this->income_fee_rate / 100;
            $month_rate =  $rate / 12;
            $repay_times = $this->getRepayTimes();
            for ($i = 1; $i <= $repay_times; $i++) {
                $repay_principal = installmentPMT($i,$repay_times,$month_rate,$principal);
                if ($i == 1) {
                    $interest_day = ($first_repay_day - $repay_time) / 86400;
                    $interest = $principal * $rate * $interest_day / 360;
                } else {
                    $interest = $left_need_repay_principal * $month_rate;
                }
                $earning += $this->floorfix($interest,2);
                $left_need_repay_principal -= $this->floorfix($repay_principal, 2);
            }
        }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']){
            $repay_times = $this->getRepayTimes();
            $earning = 0;
            for($i=1;$i<=$repay_times;$i++) {
                $earning += $this->floorfix(($principal- $principal/$repay_times * ($i - 1)) * $this->income_fee_rate / 100 / 12);
            }
        }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']){
            $repay_times = $this->getRepayTimes();
            $earning = 0;
            for($i=1;$i<=$repay_times;$i++) {
                $earning += $this->floorfix(($principal- $principal/$repay_times * ($i - 1)) * $this->income_fee_rate / 100 / 4);
            }
        } else {
            $pmt = $this->getPmtByDeal($this);
            $earning = $principal / $this->borrow_amount * $pmt['income_fee'];
        }
        return $earning;
    }


    /**
     * 根据标的信息获取PMT信息方法
     * @param $deal array
     * @return array|bool
     */
    public function getPmtByDeal($item) {
        if (!$item) {
            return false;
        }

        $data = array();

        $data['loantype'] = $item['loantype'];
        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $data['desc'] = $item['repay_time'] . '天' . $GLOBALS['dict']['LOAN_TYPE']["{$item['loantype']}"];
        } else {
            $data['desc'] = $item['repay_time'] . '月' . $GLOBALS['dict']['LOAN_TYPE']["{$item['loantype']}"];
        }

        $data['borrow_sum'] = isset($item['borrow_sum']) ? $item['borrow_sum'] : 0; // 借款总额度
        $data['borrow_amount'] = $item['borrow_amount'];  // 借款分配额度
        $data['repay_time'] = $item['repay_time']; // 借款期限
        $data['repay_interval'] = $this->get_delta_month_time($item['loantype'], $item['repay_time']); // 还款间隔月数

        $data['rate'] = $item['rate'] / 100;  // 年华借款利率

        // 如果是按天一次性
        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            if($item['id'] <= $GLOBALS['dict']['OLD_DEAL_DAY_ID']){
                $data['repay_fee_rate'] = $data['rate'] / 365 * $data['repay_interval']; // 借款期间利率
            }else{
                $data['repay_fee_rate'] = $data['rate'] / DealEnum::DAY_OF_YEAR * $data['repay_interval']; // 借款期间利率
            }
        } else {
            $data['repay_fee_rate'] = $data['rate'] / DealEnum::MONTH_OF_YEAR * $data['repay_interval']; // 借款期间利率
        }

        $data['repay_num'] = $data['repay_time'] / $data['repay_interval']; // 还款次数

        $data['borrow_rate'] = $data['borrow_sum'] ? $data['borrow_amount'] / $data['borrow_sum'] : 0; // 借款分配比例
        $data['fv'] = 0; // Fv为未来值（余值），或在最后一次付款后希望得到的现金余额，如果省略Fv，则假设其值为零，也就是一笔贷款的未来值为零。
        $data['type'] = 0; // Type数字0或1，用以指定各期的付款时间是在期初还是期末。1代表期初（先付：每期的第一天付），不输入或输入0代表期末（后付：每期的最后一天付）。
        $data['pmt'] = self::getPmtMoney($data['repay_fee_rate'], $data['repay_num'], $data['borrow_amount']); //借款人每期还款额
        $data['manage_fee_rate'] = $item['manage_fee_rate'] / 100; // 账户管理费率年化
        $data['interest'] = $data['pmt'] * $data['repay_num'] - $data['borrow_amount']; // 总利息

        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $data['manage_fee'] = $data['pmt'] * $data['manage_fee_rate'] / DealEnum::DAY_OF_YEAR * $data['repay_time']; // 管理费
        } else {
            $data['manage_fee'] = $data['pmt'] * $data['manage_fee_rate'] / DealEnum::MONTH_OF_YEAR * $data['repay_time']; // 管理费
        }

        $data['manage_rate'] = $data['manage_fee'] / $data['pmt']; // 管理费收取比例
        $data['income_fee'] = $data['interest'] - $data['manage_fee'];  // 理财总收益
        $data['real_repay_fee_rate'] = $data['interest'] / $data['borrow_amount']; // 实际借款利率
        $data['income_fee_rate'] = $data['income_fee'] / $data['borrow_amount']; // 实际理财收益率

        if($data['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $data['period_income_rate'] = (1 + $data['rate'] /DealEnum::DAY_OF_YEAR * $data['repay_time']) * (1 - $data['manage_fee_rate'] /DealEnum::DAY_OF_YEAR * $data['repay_time']) -1;   // 理财期间收益率
            $data['simple_interest'] = $data['period_income_rate'] * DealEnum::DAY_OF_YEAR / $data['repay_time']; // 理财年化收益率（单利）
            $data['compound_interest'] = pow( (1 + $data['period_income_rate']), (DealEnum::DAY_OF_YEAR / $data['repay_time'])) -1;  // 理财年化收益率（复利）
        } else {
            $data['period_income_rate'] = (1 + $data['rate'] / DealEnum::MONTH_OF_YEAR * $data['repay_time']) * (1 - $data['manage_fee_rate'] /DealEnum::MONTH_OF_YEAR * $data['repay_time']) -1;   // 理财期间收益率
            $data['simple_interest'] = $data['period_income_rate'] * DealEnum::MONTH_OF_YEAR / $data['repay_time']; // 理财年化收益率（单利）
            $data['compound_interest'] = pow( (1 + $data['period_income_rate']), (DealEnum::MONTH_OF_YEAR / $data['repay_time'])) -1;  // 理财年化收益率（复利）
        }

        return $data;
    }

    /**
     * 获取订单详情的收益信息
     *
     * @param $deal 订单信息
     * @return mixed
     */
    public function getEarningsInfo($deal) {
        $deal['min_loan'] = number_format(bcdiv($deal['min_loan_money'] , 10000,5),2);
        //$deal['borrow_amount_format'] = intval($deal['borrow_amount'] / 10000);
        //$deal['borrow_amount_format_detail'] = intval($deal['borrow_amount_format_detail']);
        $deal['loan_rate'] = round((1 - $deal['need_money_decimal'] / $deal['borrow_amount']) * 100, 2);
        //$deal['income_fee_rate_format'] = number_format($deal['income_fee_rate'], 2);
        //$deal['need_money_format'] = number_format($deal['need_money_decimal'], 2);
        if (isset($GLOBALS['user_info'])&& $GLOBALS['user_info']) {
            //if ($deal['deal_crowd'] == DealEnum::DEAL_CROWD_NEW) {
            //    $total_money = $GLOBALS['user_info']['money'];
            //    $deal['bonus_money'] = 0;
            //} else {
            if(!isset($GLOBALS['user_info']['cache_bonus'])) {
                $GLOBALS['user_info']['cache_bonus'] = BonusService::getUsableBonus($GLOBALS['user_info']['id'], false, 0, false, $GLOBALS['user_info']['is_enterprise_user']);
            }
            $bonus = $GLOBALS['user_info']['cache_bonus'];

            $total_money = bcadd($bonus['money'], !empty($GLOBALS['user_info']['money']) ? $GLOBALS['user_info']['money'] : '0.00', 2);

            $balanceResult = AccountService::getAccountMoney($GLOBALS['user_info']['id'], UserAccountEnum::ACCOUNT_INVESTMENT);
            if($balanceResult && isset($balanceResult['money'])) {
                $total_money = bcadd($total_money,$balanceResult['money'], 2);
            }

            //$total_money = $GLOBALS['user_info']['money'];
            $deal['bonus_money'] = $bonus['money'];
            //}
            $max_loan = $total_money > $deal['need_money_decimal'] ? $deal['need_money_decimal'] : $total_money;
        } else {
            $max_loan = $deal['need_money_decimal'];
        }

        $max_loan = number_format($max_loan, 2, ".", "");

        $earning = new EarningService();
        if(in_array($deal['deal_crowd'], array(1,8)))//新手专享 || 手机新手专享
        {
            if($max_loan > $deal['min_loan_money'])
            {
                $crowd_min_loan = number_format($deal['min_loan_money'],2,'.','');
            }
            else
            {
                $crowd_min_loan = $max_loan;
            }
            $deal['crowd_min_loan'] = $crowd_min_loan;
            if($max_loan > $deal['max_loan_money'] && $deal['max_loan_money']>0)
            {
                $max_loan = $deal['max_loan_money'];
            }
            if ($deal['need_money_decimal'] < (2*$deal['min_loan_money'])) {
                $max_loan = $deal['need_money_decimal'];
            }
            $money_earning = $earning->getEarningMoney($deal['id'], $deal['crowd_min_loan'], true);
            $expire_rate = $earning->getEarningRate($deal['id'], true);
        }
        else
        {
            $money_earning = $earning->getEarningMoney($deal['id'], $max_loan, true);
            if ($max_loan > 0) {
                $expire_rate = $money_earning / $max_loan * 100;
            } else {
                $expire_rate = $earning->getEarningRate($deal['id'], true);
            }
            if($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) { //等额本息固定日还款(信分期单独处理)
                $expire_rate = $earning->getEarningRate($deal['id']);
            }
        }

        $max_loan = $deal['deal_status'] != 1 ? 0 : $max_loan;
        $deal['max_loan'] = $max_loan;
        $deal['expire_rate'] = $expire_rate;
        $deal['money_earning'] = $money_earning;
        return $deal;
    }

    /**
     * 根据贷款类型，获得每两次还款的间隔时间，单位为“月”
     */
    public function get_delta_month_time($loantype, $repay_time) {
        if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']) {
            $delta_month_time = 3;
        } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']) {
            $delta_month_time = 1;
        } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_ONCE_TIME']) {
            $delta_month_time = $repay_time;
        } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) {
            $delta_month_time = 1;
        } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
            $delta_month_time = 3;
        } else if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {//等额本息固定日还款
            $delta_month_time = 1;
        }
        else if($loantype == 5)
        {
            $delta_month_time = $repay_time;
        }

        return $delta_month_time;
    }

    /**
     * PMT年金计算方法
     * @param $i float 期间收益率
     * @param $n int 期数
     * @param $p float 本金
     * @return float 每期应还金额
     */
    public static function getPmtMoney($i, $n, $p) {
        return $p * $i * pow((1 + $i), $n) / ( pow((1 + $i), $n) -1);
    }

    /**
     * 获取平台补贴金额
     * @param string $principal 投资本金
     * @return float
     */
    public function getSubsidyMoney($principal) {
        //$deal_ext = DealExtModel::instance()->findByViaSlave("`deal_id`='{$this->id}'");
        $deal_ext = DealExtModel::instance()->getDealExtByDealId($this->id);
        if (!$deal_ext || !$deal_ext['income_subsidy_rate']) {
            return 0;
        }
        $rate = $deal_ext['income_subsidy_rate'];

        if ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            $money = $principal * $rate / 100 * $this->repay_time / 360;
        } else {
            $money = $principal * $rate / 100 * $this->repay_time / 12;
        }
        return $money;
    }

    /**
     * 计算并更新进度和余额
     *
     * @return int
     **/
    public function doBid($money, $user_id, $user_name, $ip, $source_type = 0, $site_id = null, $short_alias = null) {
        $site_id = $site_id ?$site_id: app_conf("TEMPLATE_ID");
        $point_percent = bcadd($this->load_money, $money, 2) / $this->borrow_amount;

        //更新标的信息的金额限定条件
        // 只有专享和交易所标的并且设置了浮动起投才会计算
        $updateMoneyCond = " AND (`borrow_amount`= ROUND(`load_money`+'{$money}', 2) OR ROUND(`borrow_amount`-`load_money`-'{$money}', 2) >= `min_loan_money` ) ";
        // Do update
        $r = $this->db->query("UPDATE ".$this->tableName()." SET `load_money` = ROUND(`load_money`+'{$money}', 2), `point_percent`='{$point_percent}', `buy_count`=`buy_count`+1 WHERE `id` ='{$this->id}' {$updateMoneyCond} AND `deal_status` IN (0,1,6) AND `is_effect`='1'");
        if ($r === false || !$this->db->affected_rows()) {
            throw new \Exception('更新标的信息失败');
        }

        $deal = $this->find($this->id);
        $deal->update_time = get_gmtime();
        $deal->is_send_half_msg = 1;
        if (bccomp($deal['load_money'], $deal['borrow_amount'], 2) != -1) {
            $deal->deal_status = 2;
            $deal->success_time = get_gmtime();
        }


        if ($deal->save() === false) {
            throw new \Exception('更新标的状态失败');
        }

        //写进 deal_load的log，记录下来投普通单的情况
        $data['money'] = $money;
        $data['user_id'] = $user_id;
        $data['user_name'] = $user_name;
        $data['user_deal_name'] = get_deal_username($user_id); //添加投标列表显示的用户名
        $data['create_time'] = get_gmtime();
        $data['from_deal_id'] = 0;
        $data['deal_id'] = $this->id;
        $data['source_type'] = $source_type;
        $data['deal_parent_id'] = -1;
        $data['site_id'] = $site_id;
        $data['ip'] = $ip;
        $data['deal_type'] = $this->deal_type;
        $data['short_alias'] = strtoupper($short_alias);
        if ($this->db->autoExecute(DB_PREFIX."deal_load",$data,"INSERT") === false) {
            throw new \Exception('插入投资记录失败');
        }
        return $this->db->insert_id();
    }

    /**
     * 根据放款时间和期数计算还款时间
     * @param int $time 放款时间
     * @param int $repay_cycle 还款周期，月数/天数
     * @param int $loantype 还款方式
     * @param int $i 期数
     * @return int 还款时间
     */
    public function getRepayDay($repay_start_time, $repay_cycle, $loantype, $i=1) {
        $y = to_date($repay_start_time,"Y");
        $m = to_date($repay_start_time,"m");
        $d = to_date($repay_start_time,"d");

        if($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            return to_timespan($y."-".$m."-".$d,"Y-m-d") + $repay_cycle*24*60*60;
        }elseif($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {
            $add_month_num = $i*$repay_cycle;
            return to_timespan(date("Y-m-d H:i:s", strtotime("+ {$add_month_num} months", strtotime(to_date($repay_start_time)))));
        }else{
            $target_m = $m + $repay_cycle * $i;

            $year = floor($target_m / 12);
            $y += $year;

            $m = $target_m % 12;
            if ($m == 0) {
                $m = 12;
                $y--; // 当target_m=24时，$year=2，但是实际上并没发生跨年，于是在这里$y--;
            }

            $target = to_timespan($y."-".$m."-".$d,"Y-m-d");
            if ($d != to_date($target, 'd')) {
                $target = to_timespan(to_date($target, 'Y') . '-' . to_date($target, 'm') . '-1', 'Y-m-d');
            }
            return $target;
        }
    }


    /**
     * 计算每期还款本金和利息以及总额
     *
     * @param boolen $is_last 是否最后一期
     * @param flaot $total_principal 本金总额
     * @param int $is_loan 0还款 1回款
     * @param int $interest_day 计息天数，仅按月付息、按季付息有效
     * @param int $periods_index 期数
     *
     * @return array 示例:array('total'=>111,'interest'=>222, 'principal'=>333)
     * total: 本期总还款额 interest: 本期利息  principal: 本期本金
     **/
    public function getRepayMoney($total_principal, $is_last = false, $is_loan = false, $interest_day = false, $periods_index=0) {
        $rate = $is_loan ? $this->income_fee_rate : $this->rate;
        $result = array();
        $repay_times = $this->getRepayTimes();
        $result['principal'] = $repay_times == 1 ? $total_principal : $total_principal / $repay_times;  // 计算每期本金
        if($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']) { //按月付息
            if ($interest_day !== false) {
                $result['interest'] = $this->floorfix($total_principal * $interest_day * ($rate / 100 / Finance::DAY_OF_YEAR));
            } else {
                $result['interest'] = $this->floorfix($result['principal'] * ($rate / 100 /12 * $repay_times)); //每期应还利息
            }
            if($is_last) {
                $result['principal'] = $total_principal;
                $result['total'] = $result['interest'] + $result['principal'];
            }else{
                $result['principal'] = 0;
                $result['total'] = $result['interest'];
            }
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) { //按季付息
            if ($interest_day !== false) {
                $result['interest'] = $this->floorfix($total_principal * $interest_day * ($rate / 100 / Finance::DAY_OF_YEAR));
            } else {
                $result['interest'] = $this->floorfix($result['principal'] * ($rate / 100 / 4 * $repay_times));
            }
            if ($is_last) {
                $result['principal'] = $total_principal;
                $result['total'] = $result['principal'] + $result['interest'];
            } else {
                $result['principal'] = 0;
                $result['total'] = $result['interest'];
            }
        } elseif ($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) { //等额本息固定日还款
            $left_need_repay_principal = $total_principal;
            $month_rate = $rate / 12 /100;
            for ($i = 1; $i <= $repay_times; $i++) {
                $repay_principal = installmentPMT($i,$repay_times,$month_rate,$total_principal);
                if ($periods_index == $i) {
                    if ($i == 1) {
                        $interest = $total_principal * $rate /100 * $interest_day / 360;
                    } else {
                        $interest = $left_need_repay_principal * $month_rate;
                    }
                    $repay_money['principal'] = $this->floorfix($repay_principal, 2);
                    $repay_money['interest'] = $this->floorfix($interest, 2);
                    $repay_money['total'] = bcadd($repay_money['principal'],$repay_money['interest'],2);
                    return $repay_money;
                }
                $left_need_repay_principal -=  $this->floorfix($repay_principal, 2);
            }
        }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']){

            $avgPrincipal = $this->floorfix($total_principal/$repay_times,2);
            if($is_last) {
                $result['principal']  = $total_principal - $avgPrincipal * ($repay_times - 1);
            }else{
                $result['principal'] = $avgPrincipal;
            }

            // 【借款本金-借款本金÷借款总期数×（期数-1）】×（年化利率÷12）
            $result['interest'] = $this->floorfix(($total_principal - $total_principal/$repay_times * ($periods_index - 1)) * $rate / 100 /12);
            $result['total'] = bcadd($result['principal'],$result['interest'],2);
        }elseif($this->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']){
            $avgPrincipal = $this->floorfix($total_principal/$repay_times,2);
            if($is_last) {
                $result['principal']  = $total_principal - $avgPrincipal * ($repay_times - 1);
            }else{
                $result['principal'] = $avgPrincipal;
            }

            // 【借款本金-借款本金÷借款总期数×（期数-1）】×（年化利率÷4）
            $result['interest'] = $this->floorfix(($total_principal - $total_principal/$repay_times * ($periods_index-1)) * $rate / 100 /4);
            $result['total'] = bcadd($result['principal'],$result['interest'],2);
        } else { //按月付息之外的其他新借款
            $finance = new Finance();
            $pmt = $finance->getPmtByDealId($this->id, $periods_index, $total_principal);
            if($pmt !== false){
                if (!$periods_index) {
                    $interest = $is_loan ? $pmt['income_fee'] : $pmt['interest'];
                    $result['interest'] = $this->floorfix($interest * $total_principal / $this->borrow_amount / $repay_times);
                    $result['total'] = $result['interest'] + $result['principal'];
                } else {
                    $result['principal'] = $pmt['pmt_principal'];
                    $result['interest'] = $this->floorfix($pmt['pmt'] - $pmt['pmt_principal']);
                    $result['total'] = $pmt['pmt'];
                }
            }
        }

        $result['principal'] = $this->floorfix($result['principal']); //计算每期正常情况下应还本金
        $result['total'] = $this->floorfix($result['total']);
        return $result;
    }

    /**
     * 处理deal数据
     * @param $deal
     * @param int $data_type 0-全部deal数据 1-首页访问，减少不需要的数据库访问
     * $is_user_status  标对应用户的状态 要不要显示 默认显示
     * @return array()
     */
    public function handleDeal($deal, $data_type=0, $needSwitch = true) {
        $deal['old_name'] = $deal['name'];
        $deal['name'] = msubstr($deal['name'], 0, 40); //坑
        $deal['url'] = Url::gene("d", "", Aes::encryptForDeal($deal['id']), true);

        if ($data_type!=1) {
            // 获取扩展信息
            if ($deal['cate_id'] > 0) {
                $sql = "`is_effect`='1' AND `is_delete`='0' AND `id`='{$this->escape($deal['cate_id'])}'";
                //只走从库
                $cateInfo = DealCateModel::instance()->findByViaSlave($sql);
                $deal['cate_info'] = !empty($cateInfo) ? $cateInfo->getRow() : array();
            }
            if ($deal['type_id'] > 0) {
                //只走从库
                $typeInfo = DealLoanTypeModel::instance()->findByViaSlave("`is_effect`='1' AND `is_delete`='0' AND `id`='{$this->escape($deal['type_id'])}'");
                $deal['type_info'] = !empty($typeInfo) ? $typeInfo->getRow() : array();
            }
        }

        if ($deal['agency_id'] > 0) {
            //只走从库
            $agencyInfo = DealAgencyModel::instance()->findByViaSlave("`is_effect`='1' AND `id`='{$this->escape($deal['agency_id'])}'");
            $deal['agency_info'] = !empty($agencyInfo) ? $agencyInfo->getRow() : array();
        }

        // 获取资产推荐方信息
        if (isset($deal['advisory_id']) && $deal['advisory_id'] > 0) {
            $advisoryInfo = DealAgencyModel::instance()->findByViaSlave("`is_effect`='1' AND `id`='{$this->escape($deal['advisory_id'])}'");
            $deal['advisory_info'] = !empty($advisoryInfo) ? $advisoryInfo->getRow() : array();
        }

        // 格式化借款数据
        $deal['borrow_amount_format'] = format_price($deal['borrow_amount']);
        $deal['borrow_amount_format_detail'] = format_price($deal['borrow_amount'] / 10000,false);
        $deal['borrow_amount_wan_int'] = format_price($deal['borrow_amount'] / 10000, false)."万";
        $deal['rate_foramt'] = number_format($deal['rate'],2);

        $deal['remain_time'] = $deal['start_time'] + $deal['enddate'] * 24 * 3600 - get_gmtime();
        $deal['deal_tag'] = explode(',', $deal['deal_tag_name']) ;
        $i_tag =0;
        foreach($deal['deal_tag'] as $tag){
            if($i_tag){
                $deal['deal_tag_name'.$i_tag] = $tag;
            }else{
                $deal['deal_tag_name'] = $tag;
            }
            $i_tag++;
        }
        unset($deal['deal_tag']);
        //投标剩余时间
        if ($deal['deal_status'] != 1 || $deal['remain_time'] <= 0) {
            $deal['remain_time_format'] = "0" . DealEnum::DAY . "0" . DealEnum::HOUR . "0" . DealEnum::MINUTE;
        } else {
            $d = intval($deal['remain_time'] / 86400);
            $h = floor($deal['remain_time'] % 86400 / 3600);
            $m = floor($deal['remain_time'] % 3600 / 60);
            $deal['remain_time_format'] = $d . DealEnum::DAY . $h . DealEnum::HOUR . $m . DealEnum::MINUTE;
        }

        //还需多少钱
        $deal['need_money_decimal'] = round($deal['borrow_amount'] - $deal['load_money'], 2);
        //起投金额大于剩余投资金额，起投金额等于最低起投金额
        if(bccomp($deal['need_money_decimal'], $deal['min_loan_money'], 2) == -1){
            $deal['min_loan_money'] = $deal['need_money_decimal'];
        }
        $deal['need_money'] = format_price($deal['need_money_decimal']);
        $deal['need_money_detail'] = format_price($deal['need_money_decimal'], false);

        $deal['min_loan_money_format'] = $deal['min_loan_money'] >= 10000 ?
            format_price($deal['min_loan_money'] / 10000, false)."万" : format_price($deal['min_loan_money'], false);
        $deal['min_loan_money_format_yuan'] = format_price($deal['min_loan_money']);

        // 流标时间
        if (!empty($deal['bad_time'])) {
            if (date("Y", $deal['bad_time']) != date("Y", get_gmtime())) {
                $bad_time_format = "Y年m月d日";
            } else {
                $bad_time_format = "m月d日";
            }
            $deal['flow_standard_time'] = to_date($deal['bad_time'], $bad_time_format);
        } else {
            $deal['flow_standard_time'] = "-";
        }
        // 满标时间
        if (!empty($deal['success_time'])) {
            if (date("Y", get_gmtime()) != date("Y", $deal['success_time'])) {
                $su_time_format = "Y年m月d日";
            } else {
                $su_time_format = "m月d日";
            }
            $deal['full_scale_time'] = to_date($deal['success_time'], $su_time_format);
        } else {
            $deal['full_scale_time'] = "-";
        }

        $deal['loantype_name'] = str_replace('收益', '利息', $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']]);
        //修改此处为借款用途的图标
        if (isset($deal['type_info']['icon']) && $deal['type_info']['icon']) {
            $deal['icon'] = str_replace("./public/images/dealtype/","./static/img/dealtype/",$deal['type_info']['icon']);
        }

        if ($data_type != 1) {
            $deal['user_deal_name'] = UserService::getFormatUserName($deal['user_id']);

            //还款计划相关的内容
            $deal_repay = DealRepayModel::instance()->getNextRepayByDealId($deal['id']);
            if ($deal_repay) {
                $deal['month_repay_money'] = $deal_repay->repay_money;
            } else {
                $pmt_info = $this->getPmtByDeal($deal);
                $deal['month_repay_money'] = $pmt_info['pmt'];
            }

            $deal['true_month_repay_money'] = ceilfix($deal['month_repay_money']);
        }

        //后台填的年利率
        $deal['int_rate'] = $deal['rate'];

        if($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY']){
            $period_income_rate = (1 + $deal['int_rate']/12/100 * $deal['repay_time']) * (1 - $deal['manage_fee_rate'] /12/100 * $deal['repay_time']) -1;
            $deal['rate'] = round($period_income_rate * 12 / $deal['repay_time']*100, 2);
            $deal['rate'] = number_format($deal['rate'], 2) . "%";
            /*} elseif ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']) {
                $period_income_rate = (1 + $deal['int_rate']/4/100 * $deal['repay_time']) * (1 - $deal['manage_fee_rate'] /4/100 * $deal['repay_time']) -1;
                $deal['rate'] = round($period_income_rate * 4 / $deal['repay_time']*100, 2)."%";*/
        } else {
            //出借人年化收益率
            $deal['rate'] = ($deal['income_fee_rate'] > 0) ? $deal['income_fee_rate']: $this->get_invest_rate_data($deal['loantype'], $deal['repay_time']);
            $deal['rate'] = number_format($deal['rate'], 2) . "%"; // 把后台各项费率小数位数位数放开到5位，前端显示放2位，四舍五入 --20140102
        }
        $deal['rate_show'] = number_format( (float)$deal['rate'], 2);
        $deal['repay_time_array'] = $this->numberToArrayForPic($deal['repay_time']);

        //获取此标的投资人群
        $deal['crowd_str'] = $GLOBALS['dict']['DEAL_CROWD'][$deal['deal_crowd']];
        //后台修改的借款年利率
        $deal['deal_rate'] = number_format($deal['int_rate'], 2) .'%';

        $deal['show_focus'] = 1;
        //获取此标的担保人状态
        $deal['guarantor_status'] = DealGuarantorModel::instance()->checkDealGuarantorStatus(intval($deal['id']));

        // 订单附加信息
        $deal_ext = DealExtModel::instance()->findByViaSlave("`deal_id`='{$this->escape($deal['id'])}'");

        if (!empty($deal_ext)) {
            $ext_info = $deal_ext->getRow();
            foreach ($ext_info as $k => $v) {
                if (!isset($deal[$k])) {
                    $deal[$k] = $v;
                }
            }
        }

        $deal['income_ext_rate'] = number_format($deal['income_float_rate']+$deal['income_subsidy_rate'], 2, ".", "");
        $deal['income_base_rate'] = number_format($deal['income_base_rate'], 2, ".", "");
        $deal['income_float_rate'] = number_format($deal['income_float_rate'], 2, ".", "");
        $deal['income_subsidy_rate'] = number_format($deal['income_subsidy_rate'], 2, ".", "");
        $deal['income_fee_rate_format'] = number_format($deal['income_fee_rate'], 2);
        $deal['income_total_show_rate'] = number_format($deal['rate'] + $deal['income_subsidy_rate'], 2, ".", "");
        $deal['rate_show_array'] = $this->numberToArrayForPic($deal['income_total_show_rate']);
        $deal['max_rate'] = number_format( (float)$deal['rate'], 2);
        //订单状态文字
        $deal['deal_status_text'] = $this->getDealStatusText($deal);

        if ($data_type != 1) {
            $deal = $this->getEarningsInfo($deal);
        }

        // 开标时间，如果开标时间未到，则赋值给格式化后的开标时间，模板根据格式化后的开标时间判断是否显示开标时间
        if (!empty($deal['start_loan_time']) && $deal['start_loan_time']>get_gmtime() && $deal['deal_status']==0) {
            if (date("Y", get_gmtime()) != date("Y", $deal['start_loan_time'])) {
                $st_time_format = "Y-m-d H:i";
            } else {
                $st_time_format = "m-d H:i";
            }
            $deal['start_loan_time_format'] = to_date($deal['start_loan_time'], $st_time_format);
            //} else {
            //    $deal['start_loan_time_format'] = "-";
        }

        $project_obj =  DealProjectModel::instance()->findViaSlave($deal['project_id']);
        $project_info = !empty($project_obj) ? $project_obj->getRow() : array();
        if (!empty($project_obj)) {
            $project_info = $project_obj->getRow();

            $pro_service = new ProjectService();
            $deal['is_entrust_zx']  =  false; // 是否为受托专享
            $deal['is_deal_zx'] = false;

            $fixed_date_obj = XDateTime::valueOfTime(timestamp_to_conf_zone($project_info['fixed_value_date']) + 86399); // 因为目前固定起息日存的是当天 0 点,现在要显示成当天 23:59:59
            $start_date_obj = XDateTime::valueOfTime(timestamp_to_conf_zone($deal['start_time']));
            $end_date_obj = XDateTime::valueOfTime(timestamp_to_conf_zone($deal['start_time'] + $deal['enddate'] * 24 * 3600));

            $deal['formated_fixed_value_date']  =  $fixed_date_obj->getDateTime(); // 固定起息日
            // 标的发布时间
            $deal['formated_start_time']  =  $start_date_obj->getDateTime();
            // 标的截止时间
            $deal['formated_end_time']  =  $end_date_obj->getDateTime();
            $now_date_obj = XDateTime::now();
            if ($fixed_date_obj->getTime() <= $now_date_obj->getTime()) {
                $deal['formated_diff_time'] = array("day" => 0, "hour" => 0,"min" => 0, "sec" => 0);  // 过了固定起息日，就显示 0
            } else {
                $deal['formated_diff_time'] = XDateTime::getDiffInfo($now_date_obj, $fixed_date_obj);
            }
        }

        $deal['repay_start_time_name'] = '计息日';
        $deal['formated_repay_start_time'] = '--';
        if (in_array($deal['deal_status'], array(DealEnum::DEAL_STATS_WAITING, DealEnum::DEAL_STATUS_PROCESSING, DealEnum::DEAL_STATUS_FULL))) {
            if ($deal['is_deal_zx']) {
                // JIRA#5410 这里区分专享1.5和1.75,显示不同文案
                $deal['formated_repay_start_time'] = !empty($project_info['fixed_value_date']) ? to_date($project_info['fixed_value_date'], "Y-m-d") : '放款后开始起算收益';
                $deal['repay_start_time_name'] =  !empty($project_info['fixed_value_date']) ? '预计收益起算日' : '收益起算日';

                // 针对投资确认页 的预计起息日显示
                $deal['expected_repay_start_time'] = sprintf('%s%s', $deal['repay_start_time_name'], $deal['formated_repay_start_time']);
            } else {
                $deal['formated_repay_start_time'] = '放款后开始计息';
            }
        } elseif (in_array($deal['deal_status'], array(DealEnum::DEAL_STATUS_REPAY, DealEnum::DEAL_STATUS_REPAID))) {
            $deal['repay_start_time_name'] = $deal['is_deal_zx'] ?'收益起算日' : $deal['repay_start_time_name'];
            $deal['formated_repay_start_time'] = to_date($deal['repay_start_time'], 'Y-m-d');
        }


        $deal = $this->UserDealStatusSwitch($deal);
        if ($needSwitch === true) {
            $deal = $this->UserDealStatusSwitch($deal);
        }
        $deal['is_crowdfunding'] = ($deal['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) ? 1 : 0;
        return $deal;
    }

    /**
     * 流标
     */
    public function failDeal($dealId){
        //开启事务
        $this->db->startTrans();
        try{
            // 先修改订单状态
            $deal_dao = DealModel::instance()->find($dealId);
            $deal=$deal_dao;
            $deal_dao->deal_status = 3;
            $deal_dao->is_doing = 0;    // 表示流标操作结束
            $bad_time = ($deal_dao->deal_type == DealEnum::DEAL_TYPE_GENERAL) ? $deal_dao->bad_time : ($deal['start_time'] + $deal['enddate'] * 24 * 3600);

            if ($deal['bad_time'] != $bad_time) {
                $deal_dao->bad_time = $bad_time;
            }

            if ($deal_dao->save() === false) {
                throw new \Exception("fail deal error");
            }

            // 流标向用户返还金额
            $load_list = DealLoadModel::instance()->findAll("`is_repay`='0' AND `deal_id`='{$deal['id']}' AND `from_deal_id`='0'");

            $deal_service = new DealService();

            $isDT = $deal_service->isDealDT($deal['id']);
            $dt_service = new \core\service\duotou\DtTransferService();

            if ($load_list) {
                foreach ($load_list as $v) {
                    $note = '编号' . $deal['id'] .' ' . $deal['name'] . '，单号' . $v['id'];
                    // TODO finance? 取消投标
                    $userIdAccountId = AccountService::getUserAccountId($v['user_id'],UserAccountEnum::ACCOUNT_INVESTMENT);
                    if (!$userIdAccountId) {
                        throw new \Exception("未开通投资户");
                    }
                    //$user->changeMoneyDealType = $deal_service->getDealType($deal_dao);
                    $bizToken = array('dealId' => $deal['id'],'dealLoadId' => $v['id']);
                    $chg_rs =AccountService::changeMoney($userIdAccountId,$v['money'], '取消投标',$note, AccountEnum::MONEY_TYPE_UNLOCK, false, true, 0, $bizToken);
                    //$chg_rs = $user->changeMoney(-$v['money'], "取消投标", $note, 0, 0, 1);

                    $dt_rs = true;
                    if ($isDT === true) {
                        $dt_rs = $dt_service->transferFailDT($userIdAccountId, $v['money'], $v['id'],$deal['id']);
                    }

                    if ($chg_rs === false || $dt_rs === false) {
                        throw new \Exception("change_money error {$note}\n");
                    }
                }

                // 统一将投资记录设置为已还
                $deal_load_model = new DealLoadModel();
                $deal_load_model->setIsrepayByDealId($deal['id']);

                // 处理多投宝逻辑
                if ($isDT === true) {
                    $jobs_model = new JobsModel();
                    $jobs_model->priority = 84;
                    $param = array(
                        'deal_id' => $deal['id'],
                    );
                    $r = $jobs_model->addJob('\core\service\duotou\DtDealService::failDeal', $param);
                    if ($r === false) {
                        throw new \Exception("Add DT Jobs Fail");
                    }
                }
            }

            // 将删除合同等 后续操作放到 事务中
            FailState::afterMoney($deal);

            $this->db->commit();

            return true;
        } catch (\Exception $e) {
            // 出现异常则回滚
            $this->db->rollback();

            $log = array(
                "type" => "deal",
                "act" => "fail",
                "is_succ" => 0,
                "id" => $deal['id'],
                "name" => $deal['name'],
                "err" => $e->getMessage(),
            );
            \libs\utils\Logger::error(implode(" | ", $log));

            return false;
        }
    }

    public function getListV2($type, $sort, $page, $page_size, $is_all_site=false, $is_display=true, $site_id=0, $option=array(),$is_real_site=false, $count_only=false, $is_bxt=false,$need_count=true) {

        $sql = "SELECT `id`, `user_id`, `name`, `cate_id`, `type_id`, `agency_id`, `borrow_amount`, `min_loan_money`"
            .", `rate`, `start_time`, `enddate`, `deal_status`, `load_money`, `bad_time`, `success_time`, `is_update`"
            .", `loantype`, `manage_fee_rate`, `repay_time`, `income_fee_rate`, `deal_crowd`, `income_fee_rate`"
            .", `warrant`, `max_loan_money`, `success_time`, `deal_tag_name`, `type_match_row`, `min_loan_total_count`"
            .", `min_loan_total_amount`, `deal_tag_desc`,`deal_type`, `project_id`,`holiday_repay_type` FROM " .$this->tableName()
            ;

        $arr = $this->buildCondQuery($type, $sort, $is_all_site, $is_display, $site_id, $option, $is_real_site, $is_bxt);

        if ($arr === false) {
            return array('count' => 0, 'list' => array());
        }

        $condition = $arr['cond'];
        $params = $arr['param'];
        $order = " ORDER BY `id` DESC";
        $result = array();
        $count = 0;

        // 展示顺序12045
        $arr_deal_status1 = array(
            DealEnum::$DEAL_STATUS['progressing'],
            DealEnum::$DEAL_STATUS['full'],
            DealEnum::$DEAL_STATUS['waiting'],
        );

        $arr_deal_status2 = array(
            DealEnum::$DEAL_STATUS['repaying'],
            DealEnum::$DEAL_STATUS['repaid'],
        );

        // 第一分页展示进行中、满标、等待确认标的
        if ($page <= 1) {
            foreach ($arr_deal_status1 as $deal_status) {
                $cond = "`deal_status` = '{$deal_status}'" . $condition;
                $sql_tmp = $sql . " WHERE " . $cond . $order;

                $sqltmp2 = $sql_tmp. " LIMIT 0,".$page_size;
                $data = $this->findAllBySqlViaSlave($sqltmp2, true, $params);

                foreach($data as $v) {
                    $result[] = $v;
                }
                if(count($result) >= $page_size) break;
            }

        } else {
            $page--;

            $start = ($page - 1) * $page_size;
            $end = $page * $page_size;


            //逐个状态进行读取，满足条数则返回结果
            foreach ($arr_deal_status2 as $deal_status) {
                $sql_cnt = "SELECT count(*) FROM " .$this->tableName();
                $cond = "`deal_status` = '{$deal_status}'" . $condition;
                $cnt = $this->countBySql($sql_cnt . " WHERE " . $cond, $params, true);

                // 如果当前状态没有标的，直接检测下一状态
                if ($cnt <= 0) {
                    continue;
                }

                $total = $count;
                $count += $cnt;

                if ($start < $count && $end > $total) {
                    $size = $end - $total;
                    $size = $size > $page_size ? $page_size : $size;

                    $start_tmp = $start > $total ? $start - $total : 0;

                    $limit = " LIMIT {$start_tmp}, {$size}";
                    $sql_tmp = $sql . " WHERE " . $cond . $order . $limit;

                    $data = $this->findAllBySqlViaSlave($sql_tmp, true, $params);
                    $c = count($data);

                    foreach($data as $v) {
                        $result[] = $v;
                    }

                    // 剩余查询量减少本次查询量
                    $page_size -= $c;
                    if ($page_size <= 0) {
                        // 剩余查询量为0时，如果需要计数，继续循环，但只执行计数统计；否则直接退出循环返回结果
                        if ($need_count) {
                            continue;
                        } else {
                            break;
                        }
                    }
                }
            }
        }
        if ($count_only === false) {
            return array("count"=>$count, "list"=>$result);
        } else {
            return array("count"=>$count);
        }
    }

    /**
     * 改变标的还款状态
     * @param unknown $status
     * @return boolean
     */
    public function changeRepayStatus($status){
        $this->is_during_repay = $status;
        $affect_row = 0;
        return $this->save();
    }

    public function changeDuringRepay($dealId){
        $data = array('is_during_repay' => DealEnum::DEAL_DURING_REPAY);
        $cond = 'id='.$dealId. ' AND is_during_repay='.DealEnum::DEAL_NOT_DURING_REPAY;
        return $this->updateBy($data,$cond);
    }

    /**
     * 根据借款金额计算预期还款总额
     * @return $repay_money float 还款总额
     */
    public function getAllRepayMoney() {
        $repay_time = $this->repay_start_time; //中间变量，保存各期还款时间
        $repay_cycle = $this->getRepayCycle();
        $repay_times = $this->getRepayTimes();

        $repay_money = 0;
        for($i = 0; $i < $repay_times; $i++) {
            $repay_time = $this->nextRepayDay($repay_time, $repay_cycle, $this->loantype);
            $is_last = (($i + 1) == $repay_times);
            $repay_info = $this->getRepayMoney($this->borrow_amount, $is_last);
            $repay_money += $repay_info['total'];
        }

        return $repay_money;
    }

    /**
     * 根据给定的还款时间以及还款周期计算下次还款时间
     *
     * @param integer $time 本次还款时间或者开始还款时间
     * @param integer $repay_cycle 还款周期，可能是月数或者天数
     * @param integer $loantype 还款方式
     *
     * @return integer unix time
     **/
    public function nextRepayDay($time, $repay_cycle, $loantype)
    {
        $y = to_date($time, "Y");
        $m = to_date($time, "m");
        $d = to_date($time, "d");

        if ($loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']) {
            return to_timespan($y . "-" . $m . "-" . $d, "Y-m-d") + $repay_cycle * 24 * 60 * 60;
        } else {
            $target_m = $m + $repay_cycle;
            if ($target_m > 12) {
                ++$y;
            }
            $m = $target_m % 12;
            if ($m == 0) {
                $m = 12;
            }

            return to_timespan($y . "-" . $m . "-" . $d, "Y-m-d");
        }
    }

    /**
     * 还款完成时的相关处理
     *
     * @return void
     **/
    public function repayCompleted($is_force_repay=false) {
        // 如果还有未完成还款 不能改为已还清状态
        if($is_force_repay){
            $condition = sprintf("`deal_id`=$this->id AND status=0");
            $count = DealRepayModel::instance()->count($condition);
            if($count > 0){
                \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",不能更改标的为已还清状态 因为还有未完成的还款 dealId:{$this->id}");
                return false;
            }
        }

        $this->deal_status = DealEnum::$DEAL_STATUS['repaid'];
        $this->update_time = get_gmtime();
        return $this->save();
    }

    /**
     * 剩余未还本金
     * 按月等额类型为剩余本金，其他均为借款总额
     *
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/

    public function getRemainPrincipal(){
        if(in_array($this->loantype,array(
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE'],
        ))) {
            $deal_loan_repay = new DealLoanRepayModel();
            $has_repay = $deal_loan_repay->getTotalPrincipalMoney($this->id);
            return $this->borrow_amount - $has_repay;
        }else{
            return $this->borrow_amount;
        }
    }


    /**
     * 检查一个用户是否是借款人
     * @param int $user_id
     * @return bool
     */
    public function isBorrowUser($user_id) {
        $params = array(
            ':user_id' => $user_id,
        );
        $cnt = $this->countViaSlave("`user_id`=':user_id' AND `is_effect`='1' AND `is_delete`='0'", $params);
        if ($cnt == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取处于还款中正在还款的deal数量
     * @param int $is_login 0 未登录，1已登录
     */
    public function getDuringRepayCount($user_id, $is_during_repay = 1) {
        $condition =sprintf("`user_id`= '%d' AND `is_during_repay`= '%d'", $user_id, $is_during_repay );
        return $this->count($condition);
    }


    /**
     * 通过uid 获取列表
     * @param $uid
     * @param $status
     * @param int | string $deal_type
     * @param $limits
     */
    public function getListByUid($uid,$status,$limits, $deal_type = false){
        $sql = " deal_status = %d AND borrow_amount>0 AND user_id=%d AND is_visible = 1 ";
        $sql = sprintf($sql,$status,$uid);

        if(false !== $deal_type) {
            $sql .= sprintf(' and deal_type in (%s) ',$deal_type);
        }

        $limit = " LIMIT %d,%d ";
        $limit = sprintf($limit,$limits[0],$limits[1]);
        $order = " ORDER BY id DESC";
        $list = $this->findAll($sql . $order . $limit,true);
        $count = $this->count($sql . $order);
        if(!$list){
            return array('list'=>array(),'count'=>0);
        }
        $rs = array();
        foreach($list as $k => $deal){
            $rs[$k] = $this->handleDeal($deal);
        }
        return array('list'=>$rs,'count'=>$count);
    }

    /**
     * 借款人账户余额是否负担得起标的的手续费
     * @params int $deal_id
     * @return boolean true-can
     */
    public function canUserAffordDealFee($deal_id)
    {
        $deal_info = DealModel::instance()->findViaSlave(intval($deal_id));
        $user_info = UserService::getUserByCondition("id={$deal_info->user_id}",'id,money');
        $fee = $this->getAllFee($deal_id);
        return (bccomp($user_info->money, Finance::addition(array($fee['loan_fee'], $fee['consult_fee'], $fee['guarantee_fee'], $fee['pay_fee'], $fee['manage_fee']))) >= 0);
    }

    /**
     * 根据标id 获取标的的各项费用金额
     * @params int $deal_id
     * @return array ['loan_fee', 'consult_fee', 'guarantee_fee', 'pay_fee', 'manage_fee']
     */
    public function getAllFee($deal_id)
    {
        $deal_info = DealModel::instance()->findViaSlave(intval($deal_id));
        $deal_ext_info = DealExtModel::instance()->getDealExtByDealId(intval($deal_id));
        $fee = array();

        $fee['loan_fee'] = $this->getOneFee($deal_info, $deal_ext_info, 'loan_fee');
        $fee['consult_fee'] = $this->getOneFee($deal_info, $deal_ext_info, 'consult_fee');
        $fee['guarantee_fee'] = $this->getOneFee($deal_info, $deal_ext_info, 'guarantee_fee');
        $fee['pay_fee'] = $this->getOneFee($deal_info, $deal_ext_info, 'pay_fee');
        $deal_service = new DealService();
        $fee['manage_fee'] = $deal_service->isDealDT($deal_id) ? $this->getOneFee($deal_info, $deal_ext_info, 'manage_fee') : 0;

        return $fee;
    }

    /**
     * 此项手续费应收金额
     * @params object $deal 对应 deal 表
     * @params object $deal_ext 对应 deal_ext 表
     * @params string $fee_name eg. loan_fee consult_fee ..
     * @return float $fee
     */
    public function getOneFee($deal, $deal_ext, $fee_name)
    {
        // 获取各字段名
        $fee_rate_field = sprintf('%s_rate', $fee_name);
        $fee_rate_type_field = sprintf('%s_rate_type', $fee_name);
        $fee_ext_field = sprintf('%s_ext', $fee_name);

        // 计算费用
        if ($deal_ext->loan_type == DealExtEnum::LOAN_AFTER_CHARGE || !$deal_ext->$fee_ext_field) { // 收费后放款，统一按前收处理
//            if (DealExtEnum::FEE_RATE_TYPE_FIXED_BEFORE == $deal_ext->$fee_rate_type_field) { // 固定比例收取费用
//                $fee_rate = $deal->$fee_rate_field;
//            } else {
            $fee_rate = Finance::convertToPeriodRate($deal->loantype, $deal->$fee_rate_field, $deal->repay_time, false);
//            }
            $fee = $this->floorfix($deal->borrow_amount * $fee_rate / 100.0);
        } else {
            $fee_arr = json_decode($deal_ext->$fee_ext_field, true);
            $fee = $fee_arr[0];
        }
        return $fee;
    }

    /*
     * 账户总览 -- 用户投资概况
     *
     * @param $user_id
     * @return array
     */
    public function getInvestOverview($user_id){

        $data = array();
        $user_id = intval($user_id);

        if($user_id > 0){
            //回款中
            $returning = $this->InvestOverview($user_id, '4');
            $returning['text'] = '回款中';
            $data[0] = $returning;
            //投标中
            $biding = $this->InvestOverview($user_id, array(1,2));
            $biding['text'] = '投标中';
            $data[1] = $biding;

            //已回款
            $returned = $this->InvestOverview($user_id, '5');
            $returned['text'] = '已回款';
            $data[2] = $returned;

            //总额
            $all['text'] = '总计';
            $all['counts'] = $returning['counts'] + $biding['counts'] + $returned['counts'];
            $all['money'] = $returning['money'] + $biding['money'] + $returned['money'];
            $data[3] = $all;
        }

        return $data;
    }


    /**
     * 根据项目 id，获取所有的标 id
     *
     * @params  int $project_id
     * @return array
     */
    public function getDealIdsByProjectId($project_id)
    {
        $sql = "SELECT id FROM firstp2p_deal WHERE project_id = {$project_id} ";
        $rs =  $this->findAllBySql($sql,true);
        $deal_id_arr = array();
        foreach ($rs as $row) {
            $deal_id_arr[] = $row['id'];
        }
        return $deal_id_arr;
    }

    /**
     * 检查是否已经申请或已完成提前还款
     * @return boolean
     **/
    public function isAppliedPrepay() {
        $deal_prepay = new DealPrepayModel();
        $condition = "`deal_id` = '%d' and (`status` ='0' or `status` = '1')";
        $condition = sprintf($condition, $this->escape((int)$this->id));
        $count = $deal_prepay->count($condition);
        return $count > 0;
    }

    /**
     * 检查是否已经逾期
     * @return boolean
     **/
    public function isOverdue() {
        return $this->next_repay_time + 24*3600 < get_gmtime();
    }

    /**
     * 检查是否已经可以进行提前还款
     * @return boolean
     **/
    public function canPrepay() {
        $gone_days = (get_gmtime() - $this->repay_start_time)/(24*60*60);
        return $gone_days >= $this->prepay_days_limit;
    }

    /**
     * 待还金额
     * @return float
     **/
    public function remainRepayMoney() {
        return $this->totalRepayMoney() - $this->repay_money;
    }

    /**
     * 计算总计需还款金额
     * @return float
     **/
    public function totalRepayMoney() {
        $deal_repay = new DealRepayModel();
        $sql = "SELECT sum(repay_money) AS `sum` FROM %s WHERE `deal_id`='%d'";
        $sql = sprintf($sql, $deal_repay->tableName(), $this->escape((int)$this->id));
        $res = $this->findBySql($sql);
        return $res['sum'];
    }


    /**
     * 获取借款类型名称
     *
     * @return string
     **/
    public function getLoantypeName() {
        return $GLOBALS['dict']['LOAN_TYPE'][$this->loantype];
    }
    function get_remain_principal($deal){
        if(in_array($deal['loantype'],array(
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE'],
        ))) {
            $deal_loan_repay = new \core\dao\repay\DealLoanRepayModel();
            $has_repay = $deal_loan_repay->getTotalPrincipalMoney($deal['id']);
            return $deal['borrow_amount'] - $has_repay;
        }else{
            return $deal['borrow_amount'];
        }
    }

    /**
     * 提前还款利息部分计算
     *
     * @return void
     **/
    function prepay_money_intrest($remain_principal, $remain_days, $rate) {
        return $remain_principal * ((($remain_days) / 360) * ($rate / 100));
    }

    /**
     * 获取智多鑫进行中的标的列表
     * @return array
     */
    public function getZDXProgressDealList() {
        $list = array();
        // 获取deal_status[进行中]的标的列表
        $sql = sprintf('SELECT `id`, `name`, `deal_status`, `type_id`, `advisory_id`, `project_id`, `loantype`, `repay_time`, `borrow_amount`, `load_money`, `deal_type`, `create_time`, `min_loan_money` FROM `%s` WHERE deal_status = %d AND publish_wait = 0 AND is_delete = 0 AND is_effect = 1 AND report_status = 1 ORDER BY `id` ASC', $this->tableName(),DealEnum::$DEAL_STATUS['progressing']);
        $dealListDb = $this->findAllBySqlViaSlave($sql, true);
        if (empty($dealListDb)) {
            return $list;
        }

        $dealList = $dealIds = array();
        foreach ($dealListDb as $item) {
            $dealList[$item['id']] = $item;
        }

        $tagInfo = TagModel::instance()->getInfoByTagName(\core\service\duotou\DtDealService::TAG_DT);
        if (empty($tagInfo)) {
            return $list;
        }

        // 获取智多鑫标对应TAG的标的信息
        $sql = sprintf('SELECT deal_id,GROUP_CONCAT(tag_id) AS tag_id_group FROM `firstp2p_deal_tag` WHERE deal_id IN (%s) AND tag_id = %d GROUP BY deal_id', join(',', array_keys($dealList)), $tagInfo['id']);
        $dealTagList = $this->findAllBySqlViaSlave($sql, true);
        if (empty($dealTagList)) {
            return $list;
        }

        // 整理标的列表
        foreach ($dealTagList as $dt) {
            if(!empty($dealList[$dt['deal_id']])) {
                $list[] = $dealList[$dt['deal_id']];
            }
        }
        return $list;
    }
    /*
    * 账户总览 -- 用户投资概况
    *
    * @param $user_id int 用户id
    * @param $status int|array 借款状态
    * @return array counts & money
    */
    public function InvestOverview($user_id, $status){

        if(is_array($status)){
            foreach($status as &$item){
                $item = $this->escape($item);
            }
            $status_condition = ' in ('.implode(',', $status).')';
        }else{
            $status_condition = ' = '.intval($status);
        }

        $tag_sql = 'SELECT  id  from firstp2p_tag where tag_name = "DEAL_DUOTOU" ';
        $tag_rs = $this->findBySql($tag_sql,array(), true);
        if(!empty($tag_rs)) {
            $tag_condition = 'and (d_t.tag_id is null or d_t.tag_id   != '.intval($tag_rs['id']).')';
        }
        $sql=" SELECT COUNT(*) AS counts,SUM(l.money) AS money from firstp2p_deal_load as l where  deal_id in(
            select  d_l.deal_id  FROM `firstp2p_deal` AS d LEFT JOIN `firstp2p_deal_load` AS d_l
  on  d_l.deal_id = d.id   LEFT JOIN `firstp2p_deal_tag` AS d_t  on  d.id = d_t.deal_id
 where  d.deal_status  ".$status_condition."  AND d.is_delete = 0 AND parent_id != 0  ".$tag_condition." AND d_l.user_id  = :user_id ) and user_id = :user_id"  ;
 
        $rs = $this->findBySql($sql,array(":user_id" => $user_id), true);
        return $rs;
    }

    /**
     * getDealRepayOverviewByTime
     * 账户总览 -- 回款计划
     *
     * @param mixed $user_id
     * @param mixed $begin
     * @param mixed $end
     * @access public
     * @return void
     */
//    public function getDealRepayOverviewByTime($user_id, $begin = null, $end = null) {
//        $sql = "SELECT COUNT(*) AS counts ,SUM(money) AS money FROM `firstp2p_deal_loan_repay`"
//            ." WHERE type IN (1,2,3,4,5,7,8,9) AND money!=0 AND loan_user_id = :user_id AND `status` != '2' AND `time`!='0'";
//        if (!empty($begin)) {
//            $sql .= " AND time >= :begin";
//        }
//        if (!empty($end)) {
//            $sql .= " AND time <= :end";
//        }
//        return $this->findBySql($sql, array(
//                ':user_id' => $user_id,
//                ':begin' => $begin,
//                ':end' => $end,
//            )
//            , true
//        );
//    }

    /**
     * 获取借款用户在途未还清的标的数量
     * @param int $userId
     * @return int $result
     */
    public function getUserInTheLoanCount($userId){
        if(empty($userId)){
            return false;
        }
        $forbidStatus = DealEnum::DEAL_STATUS_REPAID . "," . DealEnum::DEAL_STATUS_FAIL;

        $countSql = "SELECT count(*) FROM ".DealModel::instance()->tableName()." WHERE user_id = ".intval($userId)."  AND deal_status  not in ($forbidStatus)";
        $result = $this->countBySql($countSql,null,true);

        return intval($result);
    }

    /**
     * 根据状态类型、时间区间和审批单号获得标的列表
     * 走从库
     * @param int $type deal_type
     * @param int $status deal_status
     * @param int $start_time
     * @param int $end_time
     * @param string $approve_number
     * @access public
     * @return array()
     */
    public function getDealListByStatusTypeTime($type, $status, $start_time, $end_time, $approve_number, $fields = "*", $page_num = 1, $page_size = 100) {
        $limit = " LIMIT :prev_page , :curr_page";
        $params = array(
            ":prev_page" => ($page_num - 1) * $page_size,
            ":curr_page" => $page_size
        );

        $condition = sprintf(" `deal_type` = '%d' AND `deal_status`='%d' AND `is_delete` = '0' AND `publish_wait` = '0' AND `is_effect` = '1' ", intval($type), intval($status));
        if (!empty($start_time)) {
            $condition .= " AND `last_repay_time` >= '{$start_time}'";
        }
        if (!empty($end_time)) {
            $condition .= " AND `last_repay_time` <= '{$end_time}'";
        }
        if (!empty($approve_number)) {
            $condition .= " AND `approve_number` like '{$approve_number}'";
        }

        $db = $this->db->get_slave();
        /*
         * 暂时不迁移
         * if($status == DealEnum::DEAL_STATUS_REPAID) {
            $db = Db::getInstance(DealEnum::DEAL_MOVED_DB_NAME, 'slave');
        }*/

        $condition = $this->bindParams($condition, $params);
        $limit = $this->bindParams($limit, $params);

        $count_sql = "SELECT count(*) FROM ".$this->tableName()." WHERE " . $condition;
        $count = $db->getOne($count_sql);

        $find_sql = "SELECT {$fields} FROM ".$this->tableName() ." WHERE " . $condition .$limit;
        $list = $db->getAll($find_sql);

        $res['total_page'] = ceil(bcdiv($count,$page_size,2));
        $res['total_size'] = intval($count);
        $res['res_list'] = $list;
        return $res;
    }

    /**
     * getNoticeRepay
     * 查询出所有的指定天数之内需要还款的标
     * @author zhanglei5 <zhanglei5@group.com>
     *
     * @date 2014-10-14
     * @param int $warn_day_start
     * @param int $warn_day_end
     * @access public
     * @return array
     */
    public function getNoticeRepay($warn_day_start, $warn_day_end){
        $type_id_xffq = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeEnum::TYPE_XFFQ);
        $sql = "select d.*,e.need_repay_notice from firstp2p_deal as d LEFT JOIN firstp2p_deal_ext as e ON d.id = e.deal_id WHERE d.`type_id` NOT IN (".$type_id_xffq.") AND d.`deal_status` = 4 AND d.`parent_id` != 0 AND e.need_repay_notice = 1 AND (next_repay_time - ".get_gmtime().") /24/3600 between ".$warn_day_start." AND ".$warn_day_end;
        return $this->findAllBySqlViaSlave($sql);
    }

    /**
     * searchDealById
     * 更具标ID获取标详情
     *
     * @param mixed $dealId
     * @access public
     * @return void
     */
    public function searchDealById($dealId)
    {
        $count = 1;
        $data = $this->find($dealId);
        return array("count" => $count, "list" => array($data));
    }


    /**
    * 根据标ids获取标信息
    */
    public function getDealsInfoByIds($deal_ids){
        $ids = '';
        $result = array();
        if (is_array($deal_ids)) {
            $ids = implode(',', $deal_ids);
        } else {
            $ids = $deal_ids;
        }
        $sql = "select id,name,borrow_amount,rate,repay_time,user_id from firstp2p_deal where id in ($ids) ";
        $rs =  $this->findAllBySql($sql,true);
        foreach ($rs as $row) {
            $result[$row['id']] = $row;
        }
        return $result;
    }


    /**
     * 获取标的数量
     */
    public function getDealCategoryNum() {
        $common_condition = " `deal_status` = 1 AND `is_effect`='1' AND `is_delete`='0' AND `is_visible`='1' AND `publish_wait` = 0 ";
        return $this->count($common_condition);
    }

     /**
     * 获取首页新手标
     * @param type $uid
     */
    public function getIndexNewUserList(){
         $sql = "SELECT `id`, `user_id`, `name`, `cate_id`, `type_id`, `agency_id`, `borrow_amount`, `min_loan_money`"
            .", `rate`, `start_time`, `enddate`, `deal_status`, `load_money`, `bad_time`, `success_time`, `is_update`"
            .", `loantype`, `manage_fee_rate`, `repay_time`, `income_fee_rate`, `deal_crowd`, `income_fee_rate`"
            .", `warrant`, `max_loan_money`, `success_time`, `deal_tag_name`, `type_match_row`, `min_loan_total_count`"
            .", `min_loan_total_amount`, `deal_tag_desc`,`deal_type`, `project_id`,`product_class_type` ,`holiday_repay_type` FROM " .$this->tableName();
         $condition = " `deal_status` = '1' AND `is_effect`='1' AND `is_delete`='0' AND `publish_wait` = 0 AND `is_visible`='1' AND `publish_wait` = 0";
         $condition.= " AND `deal_crowd` = '1'";
         $sql_tmp = $sql . " WHERE " . $condition;

         $data = $this->findAllBySqlViaSlave($sql_tmp, true);
         foreach($data as $v) {
              $result[] = $v;
          }
         return array("list"=>$result);
    }
}
