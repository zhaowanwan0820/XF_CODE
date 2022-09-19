<?php
/**
 * DealProjectModel.php
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;

use core\dao\DealModel;
use NCFGroup\Common\Extensions\Varz\VarzAdapter;

class DealProjectModel extends BaseModel {

    // 放款方式
    const LOAN_MONEY_TYPE_ENTRUST = 3; // 受托支付

    /**
     * 项目业务状态
     *
     * @var string
     **/
    public static $PROJECT_BUSINESS_STATUS = array(
        'cancel_loan' => -1, // 取消放款
        'waitting' => 0, //待上线
        'process' => 1, //募集中
        'full_audit' => 2, //满标待审核
        'transfer_sign' => 3, //转让签署中
        'transfer_loans_audit' => 4, //转让放款待审核
        'repaying' => 5, //还款中
        'during_repay' => 6, //正在还款
        'repaid' => 7, //已还款
    );

    public static $PROJECT_BUSINESS_STATUS_MAP = array(
        -1 => '取消放款',
        0 => '待上线',
        1 => '募集中',
        2 => '满标待审核',
        3 => '转让签署中',
        4 => '转让放款待审核',
        5 => '还款中',
        6 => '正在还款',
        7 => '已还款',
    );

    /**
     * 获取项目的已上标金额
     * @param $id
     * @return float
     */
    public function getProBorrowed($id){

        $sql = "SELECT SUM(`borrow_amount`) AS sum_money FROM %s WHERE project_id = ':project_id'
                AND is_delete = 0 AND deal_status != 3 AND publish_wait = 0 AND `parent_id` != 0";
        $sql = sprintf($sql, DealModel::instance()->tableName());

        $param = array(':project_id' => $id);
        $result = $this->findBySql($sql,$param);
        return $result['sum_money'];
    }

    /**
     * 获取项目的已投资金额
     * @param $id
     * @return float
     */
    public function getProLoaned($id){

        $sql = "SELECT sum(dl.money) AS sum_money FROM %s d left join %s dl on d.id = dl.deal_id
                where d.project_id = :project_id and d.is_delete = 0 and d.deal_status != 3 and d.parent_id != 0";
        $sql = sprintf($sql, DealModel::instance()->tableName(), \core\dao\DealLoadModel::instance()->tableName());

        $param = array(':project_id' => $id);
        $result = $this->findBySql($sql,$param);

        return $result['sum_money'];
    }

    /**
     * @param $deal_project_id
     * @return array
     */
    public function getFirstDealByProjectId($deal_project_id,$deal_status = null) {
        $cond = "`project_id`='".intval($deal_project_id)."' AND `is_delete`='0'";
        if($deal_status != null){
            $cond .= " AND `deal_status`='".intval($deal_status)."' ";
        }
        $row = DealModel::instance()->findBy($cond." ORDER BY `id` LIMIT 1", "*", array(), true);
        return $row;
    }

    /**
     * 根据项目名称模糊获取项目id
     * @author <fanjingwen@ucfgroup.com>
     * @param  string  $name
     * @return array
     */
    public function getProjectIdsByName($name)
    {
        $param = array(
            ':name' => $name,
        );
        $res = $this->findAllViaSlave("`name` like '%:name%'", true, 'id', $param);
        $id_arr_tmp = empty($res) ? array() : $res;
        $id_arr = array();
        foreach ($id_arr_tmp as $id_tmp) {
            $id_arr[] = intval($id_tmp['id']);
        }
        return $id_arr;
    }

    /**
     * 后台交易平台用款预警使用
     * 获取deal表的最大id
     * @param
     * @return int
     */
    public function getDealMaxid()
    {
        $sql = sprintf("select max(id) as max_id from %s",DealModel::instance()->tableName());
        return $this->findAllBySqlViaSlave($sql,true);
    }
    /**
     * 后台交易平台用款预警使用
     * 获取项目对应咨询机构下，状态为“待确认”、“进行中”、“满标”的所有标的借款金额，以及状态为“还款中”的所有标的的未还金额、项目层未上标金额
     * @param  int $advisory_name,$max_id,$min_id
     * @return float
     */
    public function getPlatManagement($advisory_id,$max_id,$min_id)
    {
        $advisory_id = intval($advisory_id);
        $max_id = intval($max_id);
        $min_id = intval($min_id);
        if (empty($advisory_id) || $max_id < 0 || $min_id < 0 || $max_id < $min_id) {
            return false;
        }
        //查询在advisory_id中的状态为“待确认”、“进行中”、“满标”的所有标的借款金额
        $deal_sql = sprintf("select SUM(borrow_amount) AS borrow_amount from %s
                where `deal_status` IN (0,1,2,4)  AND is_delete = 0 AND advisory_id = %d AND id < %d AND id >= %d",
                DealModel::instance()->tableName(),$advisory_id,$max_id,$min_id);
        $deal_res = $this->findAllBySqlViaSlave($deal_sql,true);

        //状态不为“还款中”的所有标的的未还金额

        $deal_repay_sql = sprintf("select SUM(principal) AS principal  FROM %s dr
                                   LEFT JOIN %s d on dr.deal_id = d.id where d.advisory_id = %d AND dr.`status`!=0 AND d.deal_status =4 AND d.id < %d AND d.id >= %d",
                                   DealRepayModel::instance()->tableName(),DealModel::instance()->tableName(),$advisory_id,$max_id,$min_id);
        $deal_repay_res = $this->findAllBySqlViaSlave($deal_repay_sql,true);

        //项目层未上标金额 项目金额-已上标金额
        $deal_pro_sql = sprintf("SELECT SUM(borrow_amount) AS borrowed_money
                                 from %s dp RIGHT  JOIN (SELECT DISTINCT(project_id) from %s
                                 where advisory_id = %d AND id < %d AND id > %d) d ON d.project_id = dp.id ",
                                 $this->tableName(),DealModel::instance()->tableName(),$advisory_id,$max_id,$min_id);
        $deal_pro_res = $this->findAllBySqlViaSlave($deal_pro_sql,true);

        $deal_bro_sql = sprintf("SELECT SUM(`borrow_amount`) AS sum_money FROM %s WHERE advisory_id = %d 
                                AND is_delete = 0 AND deal_status != 3 AND publish_wait = 0 AND `parent_id` != 0 AND id < %d AND id > %d",
                                DealModel::instance()->tableName(),$advisory_id,$max_id,$min_id);
        $deal_bro_res = $this->findAllBySqlViaSlave($deal_bro_sql,true);
        //计算三者之和
        $money = $deal_res['0']['borrow_amount'] - $deal_repay_res['0']['principal'] + ($deal_pro_res['0']['borrowed_money'] - $deal_bro_res['0']['sum_money']);
        return $money;
    }
    /**
     * 后台交易平台用款预警使用
     * 获取对应产品名称下，状态为“待确认”、“进行中”、“满标”的所有标的借款金额，以及状态为“还款中”的所有标的的未还金额、项目层未上标金额
     * @param  string $product_name
     * @return float
     */
    public function getProductManagement($product_name)
    {
        $product_name = addslashes($product_name);
        //项目金额
        $deal_pro_sql = sprintf("SELECT SUM(borrow_amount) AS borrowed_money FROM %s
                                 WHERE `product_name` = '%s'",$this->tableName(),$product_name);
        $deal_pro_res = $this->findAllBySqlViaSlave($deal_pro_sql,true);
        //已上标金额
        $deal_bro_sql = sprintf("SELECT SUM(d.`borrow_amount`) AS sum_money FROM %s d
                                 LEFT JOIN %s dp ON d.project_id = dp.id
                                WHERE dp.`product_name` = '%s' AND d.is_delete = 0 AND d.deal_status != 3
                                AND d.publish_wait = 0 AND d.`parent_id` != 0; ",DealModel::instance()->tableName(),$this->tableName(),$product_name);
        $deal_bro_res = $this->findAllBySqlViaSlave($deal_bro_sql,true);

        //状态为“待确认”、“进行中”、“满标”的所有标的借款金额
        $deal_sql = sprintf("select SUM(d.borrow_amount) AS borrow_amount from %s d LEFT JOIN %s dp on d.project_id = dp.id
                             where d.`deal_status` IN (0,1,2,4)  AND d.is_delete = 0 AND dp.`product_name` = '%s'",
                             DealModel::instance()->tableName(),$this->tableName(),$product_name);
        $deal_res = $this->findAllBySqlViaSlave($deal_sql,true);
        //状态为“还款中”的所有标的的未还金额
        $deal_repay_sql = sprintf("select SUM(dr.principal) AS principal from %s dr LEFT JOIN %s d  on dr.deal_id = d.id
                                   LEFT JOIN %s dp ON d.project_id = dp.id
                                   where dr.`status`!= 0 AND d.deal_status = 4 AND dp.`product_name` = '%s'",
                                   DealRepayModel::instance()->tableName(),DealModel::instance()->tableName(),$this->tableName(),$product_name);
        $deal_repay_res = $this->findAllBySqlViaSlave($deal_repay_sql,true);
        //计算总和

        $money = ($deal_pro_res['0']['borrowed_money'] - $deal_bro_res['0']['sum_money']) + $deal_res['0']['borrow_amount'] - $deal_repay_res['0']['principal'];
        return $money;
    }

    /**
     * 更改项目状态
     * @author <wangjiantong@ucfgroup.com>
     * @param  int  $projectId
     * @param  int  $status

     * @return boolean
     */

    public function changeProjectStatus($projectId,$status){
        if(!in_array($status, array(-2, -1, 0, 1, 2, 3, 4, 5, 6, 7, 8))){
            return false;
        }

        $sql = "UPDATE `%s` SET `business_status`='%d'  WHERE `id`='%d'";
        $sql = sprintf($sql, $this->tableName(), $this->escape($status), $this->escape($projectId));
        return $this->updateRows($sql);
    }

    /**
     * 根据借款人id 获取专享项目列表
     * @param  int $user_id
     * @param  int | boolen $business_status_arr
     * @param  int $limit_start
     * @param  int $limit_end
     * @return array [$project_list, $project_count]
     */
    public function getEntrustProjectListByUserId($user_id, $business_status_arr = array(), $limit_start = 0, $limit_end = 0)
    {
        $cond = sprintf(' fixed_value_date > 0 AND deal_type = %d AND user_id = %d ', DealModel::DEAL_TYPE_EXCLUSIVE, $user_id);
        if (!empty($business_status_arr)) {
            $cond .= sprintf(' AND business_status IN(%s) ', implode(',', array_map('intval', $business_status_arr)));
        }

        if (!empty($limit_start) && !empty($limit_end)) {
            $cond .= sprintf(' LIMIT %d, %d ',$limit_start, $limit_end);
        }

        $count = $this->countViaSlave($cond);
        $cond .= ' ORDER BY id DESC ';
        $list = empty($count) ? array() : $this->findAllViaSlave($cond);

        return array($list, $count);
    }

    /**
     * 获取项目满标时间 即：此项目下 标的的最大满标时间
     * @param  int $project_id
     * @return int timespan
     */
    public function getProjectSuccessTime($project_id)
    {
        $sql = sprintf(' SELECT MAX(`success_time`) AS `success_time` FROM %s WHERE `project_id` = %d ', DealModel::instance()->tableName(), $project_id);
        $res = $this->findBySqlViaSlave($sql);

        return !empty($res['success_time']) ? $res['success_time'] : 0;
    }

    /**
     * 更新项目还款列表状态
     * @param  int $project_id
     * @param  array $repay_ids
     */
    public function changeProjectRepayList($project_id,$repay_ids = array()){
        if(count($repay_ids) > 0){
            foreach($repay_ids as $repay_id){
                $repayDeal = DealRepayModel::instance()->find($repay_id);
                $sql = "UPDATE `%s` SET `status`='%d'  WHERE `project_id`='%d' AND `repay_time` = '%d'";
                $sql = sprintf($sql, ProjectRepayListModel::instance()->tableName(), $repayDeal['status'], intval($project_id),$repayDeal['repay_time']);
                break;
            }
        }else{
            $sql = "UPDATE `%s` SET `status` = 4  WHERE `project_id`='%d' AND `status` = 0";
            $sql = sprintf($sql, ProjectRepayListModel::instance()->tableName(), intval($project_id));
        }

        return $this->updateRows($sql);
    }

    /**
     * 通过标的id获取项目信息，判断该标的类型是否是交易所、产品大类是否是盈益
     */
    public function isJysAndYy($id)
    {
        if (empty($id)) {
            return false;
        }
        $project = $this->findViaSlave($id);
        if (empty($project)) {
            return false;
        }

        return ($project['deal_type'] == DealModel::DEAL_TYPE_EXCHANGE && $project['product_class'] == '盈益');
    }
}
