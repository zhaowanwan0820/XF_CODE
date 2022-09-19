<?php


namespace core\dao\deal;


use core\dao\BaseModel;
use core\dao\deal\DealModel;

class DealExtModel extends BaseModel
{

    public function addDealExt($dealId, $data)
    {
        $res = $this->saveDealExt($dealId,$data);
        return $res ? $this->deal_id : 0;
    }

    public function saveDealExt($dealId, $data)
    {
        foreach ($data as $k => $v) {
            $this->{$k} = $v;
        }
        $this->update_time = get_gmtime();
        $this->deal_id = $dealId;
        return $this->save();
    }

    /**
     * 根据deal_id获取订单扩展信息
     * @param int $deal_id
     * @return object
     */
    public function getDealExtByDealId($deal_id,$isSlave=true)
    {
        static $deal_ext;
        if (!isset($deal_ext[$deal_id])) {
            $deal_id = intval($deal_id);
            $condition = "`deal_id`='%d'";
            $condition = sprintf($condition, $this->escape($deal_id));
            $deal_ext[$deal_id] = $this->findBy($condition,'*',array(),$isSlave);
        }
        return $deal_ext[$deal_id];
    }

    /*
    * @author : fanjingwen@ucfgroup.com
    * @function :  根据标id、标名前缀
    * @param : $dealID
    * @param : $prefixTitle 标名前缀
    */
    public function updateDealNamePrefix($dealID, $dealNamePrefix)
    {
        $date = ['deal_name_prefix' => $dealNamePrefix];
        $cond = "`deal_id` = '{$dealID}'";
        $rs = $this->updateBy($date, $cond);
        if (empty($rs)) {
            throw new \Exception("标的名称前缀更新失败" . $dealID);
        }
        return $rs;
    }
    /**
     * 插入一条deal_ext数据
     * @param $data array 数据数组
     * @return floatoverdue_break_days
     */
    public function insertDealExt($data){

        if(empty($data)){
            return false;
        }

        $this->deal_id = $data['deal_id'];
        $this->use_info = $data['use_info'];
        $this->house_address = $data['house_address'];
        $this->house_sn = $data['house_sn'];
        $this->leasing_contract_num = $data['leasing_contract_num'];
        $this->lessee_real_name = $data['lessee_real_name'];
        $this->leasing_money = $data['leasing_money'];
        $this->income_base_rate = $data['income_base_rate'];
        $this->income_float_rate = $data['income_float_rate'];
        $this->income_subsidy_rate = $data['income_subsidy_rate'];
        $this->prepay_manage_fee_rate = $data['prepay_manage_fee_rate'];
        $this->overdue_break_days = $data['overdue_break_days'];

        $this->entrusted_loan_entrusted_contract_num = $data['entrusted_loan_entrusted_contract_num'];
        $this->entrusted_loan_borrow_contract_num = $data['entrusted_loan_borrow_contract_num'];
        $this->base_contract_repay_time = $data['base_contract_repay_time'];
        $this->must_coupon = $data['must_coupon'];
        $this->need_repay_notice = $data['need_repay_notice'];
        $this->coupon_pay_type = $data['coupon_pay_type'];
        $this->is_auto_withdrawal = $data['is_auto_withdrawal'];
        $this->is_bid_new = $data['is_bid_new'];
        $this->contract_transfer_type = $data['contract_transfer_type'];
        $this->loan_fee_rate_type = $data['loan_fee_rate_type'];
        $this->consult_fee_rate_type = $data['consult_fee_rate_type'];
        $this->guarantee_fee_rate = $data['guarantee_fee_rate'];
        $this->pay_fee_rate_type = $data['pay_fee_rate_type'];
        $this->management_fee_rate_type = $data['management_fee_rate_type'];
        $this->guarantee_fee_rate_type = $data['guarantee_fee_rate_type'];
        $this->canal_fee_rate_type = $data['canal_fee_rate_type'];
        $this->leasing_contract_title = $data['leasing_contract_title'];
        $this->loan_application_type = $data['loan_application_type'];

        $this->max_rate = $data['max_rate'];
        $this->line_site_id = $data['line_site_id'];
        $this->line_site_name = $data['line_site_name'];

        // JIRA#3271 平台产品名称定义 2016-03-30 <fanjingwen@ucfgroup.com>
        $this->deal_name_prefix = $data['deal_name_prefix'];

        // JIRA#4080
        $this->deal_specify_uid = $data['deal_specify_uid'];
        $this->loan_fee_ext = $data['loan_fee_ext'];

        $this->loan_type = $data['loan_type'];
        // JIRA#5361
        $this->discount_rate = $data['discount_rate'];

        return $this->insert();
    }

    /**
     * 修改分期服务费
     * @param int $deal_id 标的ID
     * @param array $loan_fee_arr 借款平台手续费
     * @param array $consult_fee_arr 借款咨询费
     * @param array $guarantee_fee_arr 借款担保费
     * @param array $pay_fee_arr 支付服务费
     * @param string $management_fee_arr 管理服务费
     * todo 修改model增加where参数
     * @return bool
     */
    public function saveDealExtServicefee($deal_id, $loan_fee_arr, $consult_fee_arr, $guarantee_fee_arr,$pay_fee_arr,$canal_fee_arr = null,$management_fee_arr = null) {
        $sql = "UPDATE %s SET `update_time`='%d' WHERE `id`='%d'";
        $sql = sprintf($sql, DealModel::instance()->tableName(), get_gmtime(), $this->escape($deal_id));
        $this->execute($sql);

        $loan_fee_ext = $loan_fee_arr ? json_encode($loan_fee_arr) : "";
        $consult_fee_ext = $consult_fee_arr ? json_encode($consult_fee_arr) : "";
        $guarantee_fee_ext = $guarantee_fee_arr ? json_encode($guarantee_fee_arr) : "";
        $pay_fee_ext = $pay_fee_arr ? json_encode($pay_fee_arr) : "";
        $canal_fee_ext = $canal_fee_arr ? json_encode($canal_fee_arr) : "";

        $management_fee_ext_update = "";//是否需要修改管理服务费
        if (null != $management_fee_arr) {
            $management_fee_ext = $management_fee_arr ? json_encode($management_fee_arr) : "";
            $management_fee_ext_update = ",`management_fee_ext` =' ". $management_fee_ext ."'";
        }

        $canal_fee_ext_update = "";//是否需要修改渠道费
        if (null != $canal_fee_arr) {
            $canal_fee_ext = $canal_fee_arr ? json_encode($canal_fee_arr) : "";
            $canal_fee_ext_update = ",`canal_fee_ext` =' ". $canal_fee_ext ."'";
        }

        $sql = "UPDATE %s SET `loan_fee_ext`='%s', `consult_fee_ext`='%s', `guarantee_fee_ext`='%s', `pay_fee_ext`='%s' %s %s WHERE `deal_id`='%d'";
        $sql = sprintf($sql, $this->tableName(), $loan_fee_ext, $consult_fee_ext, $guarantee_fee_ext, $pay_fee_ext, $management_fee_ext_update, $canal_fee_ext_update, $this->escape($deal_id));
        return $this->execute($sql);
    }


    public function getInfoByDeal($deal_id, $is_slave=true) {
        $condition = "deal_id=:deal_id";
        return $this->findBy($condition, '*', array(':deal_id' => $deal_id), $is_slave);
    }

    /**
     * 根据标的id 获取放款类型
     * @param  int $deal_id
     * @return int $loan_type | false
     */
    public function getDealExtLoanType($deal_id)
    {
        static $loan_type_arr = array();
        if (isset($loan_type_arr[$deal_id])) {
            return $loan_type_arr[$deal_id];
        } else {
            $condition = sprintf('`deal_id` = %d', $deal_id);
            $deal_ext_info = $this->findBy($condition, 'loan_type');
            if (empty($deal_ext_info)) {
                return false;
            } else {
                $loan_type_arr[$deal_id] = intval($deal_ext_info->loan_type);
                return $loan_type_arr[$deal_id];
            }
        }
    }

    /**
     * 根据 model 实例中的 deal_id 更新表信息
     *
     * @return boolean
     **/
    public function updateByDealId()
    {
        $row = $this->getRow();
        return $this->updateBy($row, sprintf('deal_id = %d', $row['deal_id']));
    }

}