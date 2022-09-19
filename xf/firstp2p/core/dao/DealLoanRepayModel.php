<?php
/**
 * DealLoanRepay class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\DealSiteModel;
use core\dao\UserModel;
use core\dao\JobsModel;
use core\dao\FinanceQueueModel;
use core\dao\DealRepayModel;
use core\dao\DealLoanTypeModel;

use core\service\CreditLoanService;
use core\service\DealService;
use core\service\jifu\JfTransferService;
use core\service\PartialRepayService;
use core\service\UserLoanRepayStatisticsService;
use core\service\DealLoanRepayCalendarService;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\oto\O2ODiscountRateService;
use core\service\MsgBoxService;
use core\service\DtDealService;

use core\event\DealLoanRepayMsgEvent;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

require_once APP_ROOT_PATH . 'system/libs/msgcenter.php';

/**
 * 还款记录,每当满标后进行放款时生成一系列回款记录，也即回款计划
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class DealLoanRepayModel extends BaseModel {
    const MONEY_PRINCIPAL = 1; // 本金
    const MONEY_INTREST = 2; // 利息
    const MONEY_PREPAY = 3; // 提前还款
    const MONEY_COMPENSATION = 4; // 提前还款补偿金
    const MONEY_IMPOSE = 5; // 逾期罚息
    const MONEY_MANAGE = 6; // 管理费
    const MONEY_PREPAY_INTREST = 7; // 提前还款利息
    const MONEY_COMPOUND_PRINCIPAL = 8; // 利滚利赎回本金
    const MONEY_COMPOUND_INTEREST = 9; // 利滚利赎回利息

    const STATUS_NOTPAYED = 0; // 未还
    const STATUS_ISPAYED = 1; // 已还
    const STATUS_CANCEL = 2; // 因提前还款而取消

    /**
     * 根据cate获取所有deal_loan_repay表中即将带来的总收益
     * @param int $site_id
     * @return float 总金额
     */
    public function getRepayEarnMoneyByCate($site_id = 0,$cate = 0){

        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `status` = 0 AND `type` = 2";
        $sql = sprintf($sql, $this->tableName());
        if (!empty($site_id)) {
            $sql1=sprintf("SELECT `deal_id` FROM %s WHERE `site_id` in(%s)",DealSiteModel::instance()->tableName(),$this->escape($site_id));
            $str='';
            foreach ($this->findAllBySql($sql1, true,null, true) as $v)
            {
                $str.=",".implode($v);
            }
            $result1=substr($str,1);
            $sql .= " AND `deal_id` IN ($result1)";
        }
        if(!empty($cate)){
            $sql1=sprintf("SELECT `id` FROM %s WHERE `type_id`=%d",DealModel::instance()->tableName(),$this->escape($cate));
            $str='';
            foreach ($this->findAllBySql($sql1, true,null, true) as $v)
            {
                $str.=",".implode($v);
            }
            $result1=substr($str,1);
            $sql .= " AND `deal_id` IN ($result1)";
        }
        $result = $this->findBySql($sql);

        return $result['sum'];


//         $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `status` = 0 AND `type` = 2";
//         $sql = sprintf($sql, $this->tableName());
//         if (!empty($site_id)) {
//             $sql .= sprintf(" AND `deal_id` IN (SELECT `deal_id` FROM %s WHERE `site_id` in(%s))", DealSiteModel::instance()->tableName(),$this->escape($site_id));
//         }
//         if(!empty($cate)){
//             $sql .= sprintf(" AND `deal_id` IN (SELECT `id` FROM %s WHERE `type_id`=%d)", DealModel::instance()->tableName(),$this->escape($cate));
//         }

//         $result = $this->findBySql($sql);
//         return $result['sum'];
    }

    /**
     * 获取所有deal_loan_repay表中即将带来的总收益
     * @param int $site_id
     * @return float 总金额
     */
    public function getRepayEarnMoney($site_id = 0){
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `status` = 0 AND `type` = 2";
        $sql = sprintf($sql, $this->tableName());
        if (!empty($site_id)) {

            $sql1=sprintf("SELECT `deal_id` FROM %s WHERE `site_id` in(%s)",DealSiteModel::instance()->tableName(),$this->escape($site_id));
            $str='';
            foreach ($this->findAllBySql($sql1, true,null, true) as $v)
            {
                $str.=",".implode($v);
            }
            $result1=substr($str,1);
            $sql .= " AND `deal_id` IN ($result1)";
           // $sql .= sprintf(" AND `deal_id` IN (SELECT `deal_id` FROM %s WHERE `site_id` in(%s))", DealSiteModel::instance()->tableName(),$this->escape($site_id));
        }
        $result = $this->findBySql($sql, array(), true);
        return $result['sum'];
    }

    /**
     * 根据cate获取所有收益表中的已获总收益
     * @param int $site_id
     * @return float
     */
    public function getPayedEarnMoneyByCate($site_id = 0,$cate = 0) {

        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `status` = 1 AND `type` IN (2,4,5,7)";
        $sql = sprintf($sql, $this->tableName());
        if (!empty($site_id)) { // 所有分站显示所有的
            $sql1=sprintf("SELECT `deal_id` FROM %s WHERE `site_id` in(%s)",DealSiteModel::instance()->tableName(),$this->escape($site_id));
            $str='';
            foreach ($this->findAllBySql($sql1, true,null, true) as $v)
            {
                $str.=",".implode($v);
            }
            $result1=substr($str,1);
            $sql .= " AND `deal_id` IN ($result1)";
        }
        if(!empty($cate)){
            $sql1=sprintf("SELECT `id` FROM %s WHERE `type_id`=%d",DealModel::instance()->tableName(),$this->escape($cate));
            $str='';
            foreach ($this->findAllBySql($sql1, true,null, true) as $v)
            {
                $str.=",".implode($v);
            }
            $result1=substr($str,1);
            $sql .= " AND `deal_id` IN ($result1)";
        }

        $result = $this->findBySql($sql, array(), true);

        return $result['sum'];


//         $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `status` = 1 AND `type` IN (2,4,5,7)";
//         $sql = sprintf($sql, $this->tableName());
//         if (!empty($site_id)) { // 所有分站显示所有的
//             $sql .= sprintf(" AND `deal_id` IN (SELECT `deal_id` FROM %s WHERE `site_id` in(%s))", DealSiteModel::instance()->tableName(),$this->escape($site_id));
//         }
//         if(!empty($cate)){
//             $sql .= sprintf(" AND `deal_id` IN (SELECT `id` FROM %s WHERE `type_id`=%d)", DealModel::instance()->tableName(),$this->escape($cate));
//         }

//         $result = $this->findBySql($sql, array(), true);
//         return $result['sum'];
    }

    /**
     * 根据dealId获取所有收益表中的已获总收益
     * @param int $dealId 标Id
     * @param int $status  状态
     * @return float
     */
    public function getPayedEarnMoneyByDealId($dealId , $status) {
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id` = %d AND `status` = %d AND `type` IN (2,4,5,7)";
        $sql = sprintf($sql, $this->tableName(), $dealId ,$status);
        $result = $this->findBySql($sql, array(), true);
        return $result['sum'];
    }

    /**
     * 获取所有收益表中的已获总收益
     * @param int $site_id
     * @return float
     */
    public function getPayedEarnMoney($site_id = 0) {

        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `status` = 1 AND `type` IN (2,4,5,7)";
        $sql = sprintf($sql, $this->tableName());
        if (!empty($site_id)) { // 所有分站显示所有的
            $sql1=sprintf("SELECT `deal_id` FROM %s WHERE `site_id` in(%s)",DealSiteModel::instance()->tableName(),$this->escape($site_id));
            $str='';
            foreach ($this->findAllBySql($sql1, true,null, true) as $v)
            {
                $str.=",".implode($v);
            }
            $result1=substr($str,1);
            $sql .= " AND `deal_id` IN ($result1)";
        }
        $result = $this->findBySql($sql, array(), true);
        return $result['sum'];

//         $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `status` = 1 AND `type` IN (2,4,5,7)";
//         $sql = sprintf($sql, $this->tableName());
//         if (!empty($site_id)) { // 所有分站显示所有的
//             $sql .= sprintf(" AND `deal_id` IN (SELECT `deal_id` FROM %s WHERE `site_id` in(%s))", DealSiteModel::instance()->tableName(),
// $this->escape($site_id));
//         }
//         $result = $this->findBySql($sql, array(), true);
//         return $result['sum'];
    }

    /**
     * 获取用户汇款列表 web api 回款列表
     * @param $user_id
     * @param $start_time
     * @param $end_time
     * @param $limit array(0,5)
     * @param string $type web  或者 api newapi creditloanapi 速贷api
     * @param null $money_type
     * @param null $repay_status
     * @param int | string $deal_type
     */
    public function getLoanList($user_id,$start_time,$end_time,$limit,$type='web',$money_type=null,$repay_status=null, $deal_type = false){
        $where = " (`type` in (2,3,4,5,7,8,9) or (`type` = 1 and money != 0 )) AND `time`!='0' AND ";
        if(!$user_id){return false;}
        $condition = sprintf(' loan_user_id = %d ',$user_id);
        if($start_time){
            $condition .= sprintf(' and time >= %d ',$start_time);
        }
        if($end_time){
            $condition .= sprintf(' and time <= %d ',$end_time);
        }
        if($money_type !== null){
            $condition .= sprintf(' and type = %d ',$money_type);
        }
        if($repay_status !== null){
            $condition .= sprintf(' and status = %d ',$repay_status);
        }
        if(false !== $deal_type) {
            $condition .= sprintf(' and deal_type in (%s) ',$deal_type);
        }

        // 过滤智多鑫标的回款计划
        $dt_tag = \core\dao\TagModel::instance()->getInfoByTagName(DtDealService::TAG_DT);
        if (!empty($dt_tag)) {
            $condition .= sprintf(" AND `deal_id` NOT IN (SELECT `deal_id` FROM %s WHERE `tag_id` = '%d')", \core\dao\DealTagModel::instance()->tableName(), intval($dt_tag['id']));
        }

        if($type == 'web'){
            //$order = " order by deal_loan_id desc,type asc,id desc ";
            $order = " ORDER BY `status` ASC, `real_time` DESC, `time` ASC, `id` ASC, `type` ASC ";
        }elseif($type == 'api'){//老接口
            if($start_time){
                $order = ' ORDER BY time ASC ';
            }else{
                $order = ' ORDER BY time DESC ';
            }
        }elseif($type == 'newapi'){
            if($repay_status){//已还
                $order = ' ORDER BY time DESC, id DESC ';
            }else{
                $order = ' ORDER BY time ASC, id ASC ';
            }
        } elseif($type == 'creditloanapi') {
            $order = ' ORDER BY time ASC ';
        }
        $limit_str = " LIMIT %d,%d ";
        $limit_str = sprintf($limit_str,$limit[0],$limit[1]);
        $where = $where.$condition.$order;
        $rs = $this->findAllViaSlave($where.$limit_str,TRUE);
        $counts = $this->countViaSlave($where);
        $rs['counts'] = $counts;
        return $rs;
    }

    /**
     * 根据投资id获取回款列表
     * @param $deal_loan_id int
     * @return array("count"=>$count, "list"=>$list)
     */
    public function getLoanRepayListByLoanId($deal_loan_id) {
        $deal_loan_id = intval($deal_loan_id);
        $condition = "`deal_loan_id`='%d' AND `time`!='0' ORDER BY `time`";
        $condition = sprintf($condition, $this->escape($deal_loan_id));
        $list = $this->findAll($condition);

        if (!$list) {
            return false;
        }
        foreach ($list as $key => &$item) {
            if ($item['type'] == self::MONEY_MANAGE || $item['money'] == 0) {
                unset($list[$key]);
                continue;
            }
            $item['money_type'] = self::getLoanRepayType($item['type']);
            $item['money_status'] = self::getLoanRepayStatus($item['status']);
            $item['is_delay'] = $item['real_time'] > 0 && to_date($item['real_time'], "Y-m-d") > to_date($item['time']);
        }
        return $list;
    }

    /**
     * 根据回款类型获取回款类型文案
     * @param $type int
     * @return $money_type string
     */
    public static function getLoanRepayType($type) {
        switch ($type) {
            case self::MONEY_PRINCIPAL: $money_type = "本金";break;
            case self::MONEY_INTREST: $money_type = "利息";break;
            case self::MONEY_PREPAY: $money_type = "提前还款本金";break;
            case self::MONEY_COMPENSATION: $money_type = "提前还款补偿金";break;
            case self::MONEY_IMPOSE: $money_type = "逾期罚息";break;
            case self::MONEY_MANAGE: $money_type = "投资管理费";break;
            case self::MONEY_PREPAY_INTREST : $money_type = "提前还款利息";break;
            case self::MONEY_COMPOUND_PRINCIPAL : $money_type = "本金";break;
            case self::MONEY_COMPOUND_INTEREST : $money_type = "利息";break;
            default : $money_type = false;
        }
        return $money_type;
    }

    /**
     * 根据回款状态获取回款状态文案
     * @param $status int
     * @return $money_status string
     */
    public static function getLoanRepayStatus($status) {
        switch ($status) {
            case self::STATUS_NOTPAYED: $money_status = "未还";break;
            case self::STATUS_ISPAYED: $money_status = "已还";break;
            case self::STATUS_CANCEL: $money_status = "因提前还款而取消";break;
            default : $money_status = false;
        }
        return $money_status;
    }

    /**
     * add on 20141125 by wangyiming
     * 此函数有效期为 20151125
     * 为解决20141121投资未冻结资金的情况，在偿还本金时将用户资金冻结，并增加一条系统修正的资金记录
     * @param int $deal_loan_id 由此判断是否属于配置中的投资未冻结id
     * @param float $moeny 本金金额
     * @param int $user_id 回款用户id
     */
    public function repairMoneyOnrepay($deal_loan_id, $money, $user_id) {
        $deal_loan_id_arr = explode(",", app_conf('REPAIR_MONEY_ONREPAY'));
        $arr_tmp_141230 = explode(",", app_conf('REPAIR_MONEY_ONREPAY_141230')); // 解决超出充值的问题
        $deal_loan_id_arr_141230 = array();
        foreach ($arr_tmp_141230 as $str_tmp) {
            $arr = explode(":", $str_tmp);
            $deal_loan_id_arr_141230[$arr[0]] = $arr[1];
        }

        if (in_array($deal_loan_id, $deal_loan_id_arr)) {
            $user_model = new UserModel();
            $user = $user_model->find($user_id);
            $msg = "系统冻结余额修正";
            return $user->changeMoney($money, "系统冻结余额修正", $msg, 0, 0, 1);
        } elseif (isset($deal_loan_id_arr_141230[$deal_loan_id]) && $deal_loan_id_arr_141230[$deal_loan_id] > 0) {
            $user_model = new UserModel();
            $user = $user_model->find($user_id);
            $money = $deal_loan_id_arr_141230[$deal_loan_id];
            $msg = "系统余额修正";
            return $user->changeMoney(-$money, $msg, $msg);
        } else {
            return true;
        }
    }



    /**
     * 获取借款人逾期罚息
     * @param $deal_repay objects
     */
    public function getImposeBorrower($deal_repay){
        $deal_repay_id = intval($deal_repay->id);
        $deal_id = intval($deal_repay->deal_id);
        $deal = DealModel::instance()->find($deal_id,'borrow_amount');
        $deal_loan_model = new DealLoadModel();
        $deal_loan_list  = $deal_loan_model->getDealLoanList($deal_id);
        $total_overdue = 0;
        foreach ($deal_loan_list as $deal_loan) {
            $fee_of_overdue = $deal_loan->money / $deal["borrow_amount"] * $deal_repay->impose_money;
            // 逾期罚息进行舍余
            $total_overdue += $deal->floorfix($fee_of_overdue);
        }
        return $total_overdue;
    }
    /**
     * 根据还款id执行正常还款操作
     * @param $deal_repay object
     * @param $next_repay_id int|bool 下次还款id false-若为最后一期
     * @param $next_repay_id int|bool 下次还款id false-若为最后一期
     * @return array("total_overdue"=>$totla_overdue) 返回扩展数据，目前只返回逾期罚息金额
     */
    public function repayDealLoan($deal_repay_id, $next_repay_id, $ignore_impose_money = false, $repay_user_id = false){
        $deal_repay_model = new DealRepayModel();
        $dealService = new DealService();
        $deal_repay = $deal_repay_model->find($deal_repay_id);
        $deal_repay_id = intval($deal_repay->id);
        $deal_id = intval($deal_repay->deal_id);
        $deal = DealModel::instance()->find($deal_id);

        $deal_service = new DealService();
        $is_dtv3 = $deal_service->isDealDTV3($deal_id);

        $isYJ175 = $dealService->isDealYJ175($deal_id);

        $GLOBALS['db']->startTrans();
        try {
            if ($is_dtv3 === true) {
                $ydt_user_id = app_conf('DT_YDT');
                $jobs_model = new JobsModel();
                $function = '\core\dao\DealLoanRepayModel::repayDealLoanOne';
                $param = array(
                    'deal_repay_id' => $deal_repay_id,
                    'deal_loan_money' => $deal['borrow_amount'],
                    'deal_loan_id' => 0,
                    'deal_loan_user_id' => $ydt_user_id,
                    'ignore_impose_money' => $ignore_impose_money,
                    'next_repay_id' => $next_repay_id,
                );
                $jobs_model->priority = 85;
                $r = $jobs_model->addJob($function, array('param' => $param));
                if ($r === false) {
                    throw new \Exception("add prepay by loan id jobs error");
                }
            } else {
                // 变更出借人账户
                $deal_loan_model = new DealLoadModel();
                //根据借款ID获取所有投标记录信息
                $deal_loan_list = $deal_loan_model->getDealLoanList($deal_id);
                $total_overdue = 0;
                foreach ($deal_loan_list as $deal_loan) {
                    //插入队列执行
                    $jobs_model = new JobsModel();
                    $function = '\core\dao\DealLoanRepayModel::repayDealLoanOne';
                    $param = array(
                        'deal_repay_id' => $deal_repay_id,
                        'deal_loan_money' => $deal_loan->money,
                        'deal_loan_id' => $deal_loan->id,
                        'deal_loan_user_id' => $deal_loan->user_id,
                        'ignore_impose_money' => $ignore_impose_money,
                        'next_repay_id' => $next_repay_id,
                    );
                    $jobs_model->priority = 85;
                    $r = $jobs_model->addJob($function, array('param' => $param));
                    if ($r === false) {
                        throw new \Exception("add prepay by loan id jobs error");
                    }

                    $deal_loan_money = $deal_loan->money;
                    if ($deal_repay->status == 2 && !$ignore_impose_money) {
                        $fee_of_overdue = $deal_loan_money / $deal["borrow_amount"] * $deal_repay->impose_money;
                        // 逾期罚息进行舍余
                        $fee_of_overdue = $deal->floorfix($fee_of_overdue);
                        $total_overdue += $fee_of_overdue;
                    }
                }
            }

            $result['total_overdue'] = $total_overdue;
            // 同步还款记录
            if ($isYJ175 === false && $this->repayment($deal_repay_id, $deal->id, $repay_user_id) === false) {
                throw new \Exception("还款投资人失败");
            }
            $rs = $GLOBALS['db']->commit();
        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            throw new \Exception($e->getMessage());
        }
        if ($rs === false) {
            throw new \Exception("事务提交失败");
        }
        return $result;
    }

    /**
     * 还款
     * @param $deal_repay_id 还款计划id
     * @param $deal_id 标的id
     */
    public function repayment($deal_repay_id, $deal_id, $repay_user_id){
        $deal_service = new DealService();
        $deal_id = intval($deal_id);

        // 报备标的不走支付队列
        if($deal_service->isP2pPath($deal_id)){
            return true;
        }

        $condition = "`deal_repay_id` = ':deal_repay_id' AND `deal_id` = ':deal_id' ORDER BY id ASC";

        // 智多鑫三期清盘标的
        $is_dtv3 = $deal_service->isDealDTV3($deal_id);
        if ($is_dtv3 === true) {
            $condition = "`deal_repay_id` = ':deal_repay_id' AND `deal_id` = ':deal_id' AND `status`='0' ORDER BY id ASC";
        }

        $dealLoanRepayList = $this->findAll($condition, false, '*', array(':deal_repay_id' => $deal_repay_id, ':deal_id' => $deal_id));

        $loanRepayParam['orders'] = array();

        //获取是否为哈哈农庄化肥标
        $isDealHF = $deal_service->isDealHF($deal_id);

        // 是否为智多鑫标的
        $isDealDT = $deal_service->isDealDT($deal_id);

        foreach($dealLoanRepayList as $val){
            // 跳过金额为0.00的转账记录
            if (bccomp($val['money'], '0.00', 2) <= 0) {
                continue;
            }
            $temp = array();
            $temp['outOrderId'] = 'DEALLOANREPAY|' . $val['id'];
            $temp['payerId'] = $repay_user_id === false ? $val['borrow_user_id'] : $repay_user_id;//付款人ID
            //如果为管理费，需要将收款人调整为平台ID
            if($val['type'] == 6){
                $temp['receiverId'] = app_conf('MANAGE_FEE_USER_ID');
            } elseif ($val['type'] == 2 && $isDealDT === true) {
                $temp['receiverId'] = app_conf('AGENCY_ID_DT_INTEREST');//多投宝利息账户
            } elseif ($val['type'] == 5 && $isDealDT === true) {
                $temp['receiverId'] = app_conf('AGENCY_ID_DT_INTEREST');//多投宝利息账户
            }else{
                if ($isDealDT === true) {
                    continue;
                }
                $temp['receiverId'] = $val['loan_user_id'];//收款人ID
            }
            $temp['repaymentAmount'] = $val['money']*100;//还款金额，单位为分

            $temp['curType'] = 'CNY';//币别，默认CNY
            $temp['bizType'] = 1;
            $temp['batchId'] = $deal_id;//币别，默认CNY

            $loanRepayParam['orders'][]=$temp;

            //哈哈农庄化肥专享本金转账
            if($val['type'] == 1){
                if($isDealHF){
                    $tempHF = array();
                    $tempHF['outOrderId'] = 'DEALLOANREPAYHF|' . $val['id'];
                    $tempHF['payerId'] = $val['loan_user_id'];
                    $tempHF['receiverId'] = app_conf('CLOUD_PIC_USER_ID');
                    $tempHF['repaymentAmount'] = $val['money']*100;
                    $tempHF['curType'] = $temp['curType'];
                    $tempHF['bizType'] = $temp['bizType'];
                    $tempHF['batchId'] = $temp['batchId'];

                    $loanRepayParam['orders'][] = $tempHF;
                }
            }
        }

        // $loanRepayParam['orders'] = json_encode($loanRepayParam['orders']);

        return FinanceQueueModel::instance()->push($loanRepayParam,'transfer', FinanceQueueModel::PRIORITY_HIGH);
    }

    /**
     * 逆向还款
     * @param $deal_repay_id 还款计划id
     * @param $deal_id

     */
    public function revert_repayment($deal_id){

        $condition = "deal_id = :deal_id AND `status`='2' ORDER BY id ASC";

        $dealLoanRepayList = $this->findAll($condition, false, '*', array(':deal_id' => $deal_id));

        $loanRepayParam['orders'] = array();

        foreach($dealLoanRepayList as $val){
            // 跳过金额为0.00的转账记录
            if (bccomp($val['money'], '0.00', 2) <= 0) {
                continue;
            }
            $temp = array();
            $temp['outOrderId'] = 'DEALLOANREPAY|' . $val['id'];
            $temp['payerId'] = $val['borrow_user_id'];//付款人ID
            //如果为管理费，需要将收款人调整为平台ID
            if($val['type'] == 6){
                $temp['receiverId'] = app_conf('MANAGE_FEE_USER_ID');
            }else{
                $temp['receiverId'] = $val['loan_user_id'];//收款人ID
            }
            $temp['repaymentAmount'] = $val['money']*100;//还款金额，单位为分

            $temp['curType'] = 'CNY';//币别，默认CNY
            $temp['bizType'] = 1;
            $temp['batchId'] = $deal_id;

            $loanRepayParam['orders'][]=$temp;
        }

        // $loanRepayParam['orders'] = json_encode($loanRepayParam['orders']);

        return FinanceQueueModel::instance()->push($loanRepayParam,'transfer', FinanceQueueModel::PRIORITY_HIGH);
    }


    /**
     * 根据还款id和用户id获取各项还款金额
     * @param $deal_repay_id int
     * @param $user_id int
     * @param $exclude_reservation boolen 是否排除前台预约投标
     * @param $deal_id int
     * @return array
     */
    public function getChangeMoneyByRepayId($deal_repay_id, $user_id, $exclude_reservation = false, $deal_id = 0) {
        $moneyInfo = array(
                        'principal' => '0元', //本金
                        'intrest' => '0元', //利息
                        'prepay' => '0元', //提前还款
                        'compensation' => '0元', //提前还款补偿金
                        'impose' => '0元', //逾期罚息
                        'prepayIntrest' => '0元', //提前还款利息
                    );
        $deal_repay_id = intval($deal_repay_id);
        $user_id = intval($user_id);
        $condition = sprintf('`deal_repay_id`=  %d  AND `loan_user_id`= %d ', $this->escape($deal_repay_id), $this->escape($user_id));
        if ($exclude_reservation) {
            $deal_load_id_arr = DealLoadModel::instance()->getReserveDealLoadIdsByDealId($deal_id);
            $condition .= empty($deal_load_id_arr) ? '' : sprintf(' AND deal_loan_id NOT IN (%s) ', implode(',', $deal_load_id_arr));
        }

        $loan_repay_list = $this->findAll($condition);
        $money_total     = 0;
        $arr_money       = array();
        foreach ($loan_repay_list as $val) {
            $arr_money[$val['type']] += $val['money'];
            $arr_money['time']       = $val['time'];
            if ($val['type'] != self::MONEY_MANAGE) {
                $money_total += $val['money'];
            }
            switch ($val['type']) {
                case self::MONEY_PRINCIPAL:
                    $moneyInfo['principal'] += $val['money'];
                    break;
                case self::MONEY_INTREST:
                    $moneyInfo['intrest'] += $val['money'];
                    break;
                case self::MONEY_PREPAY:
                    $moneyInfo['prepay'] += $val['money'];
                    break;
                case self::MONEY_COMPENSATION:
                    $moneyInfo['compensation'] += $val['money'];
                    break;
                case self::MONEY_IMPOSE:
                    $moneyInfo['impose'] += $val['money'];
                    break;
                case self::MONEY_PREPAY_INTREST:
                    $moneyInfo['prepayIntrest'] += $val['money'];
                    break;
                default:
                    break;
            }
        }
        $arr_money['total'] = $money_total;
        $arr_money['moneyInfo'] = $moneyInfo;
        return $arr_money;
    }

    /**
     * 向出借人发送站内信和邮件
     * @param $deal object
     * @param $user object
     * @param $repay_id int
     * @param $next_repay_id int
     */
    public function sendMsg($deal, $user, $repay_id, $next_repay_id) {
        $arr_change_money = $this->getChangeMoneyByRepayId($repay_id, $user->id, true, $deal['id']);
        if ($next_repay_id) {
            $is_last         = 0;
            $arr_money_extra = $this->getChangeMoneyByRepayId($next_repay_id, $user->id, true, $deal['id']);
        } else {
            $is_last         = 1;
            $arr_money_extra = array(
                "all_repay_money"  => number_format($this->getTotalRepayMoney($deal->id, $user->id), 2),
                "all_impose_money" => number_format($this->getTotalMoneyOfUserByDealId($deal->id, $user->id, self::MONEY_IMPOSE, self::STATUS_ISPAYED), 2),
                "all_income_money" => number_format($this->getTotalMoneyOfUserByDealId($deal->id, $user->id, self::MONEY_INTREST, self::STATUS_ISPAYED), 2),
            );
        }

        // 向出借人发送回款站内信
        $this->sendMessage($deal, $user, $arr_change_money, $is_last, $arr_money_extra);

        // 向出借人发送回款邮件
        $this->sendEmail($user, $deal, $arr_change_money, $is_last, $arr_money_extra);
    }

    /**
     * 向出借人发回款邮件
     * @param $user object
     * @param $deal array
     * @param $arr_money array
     * @param $is_last int
     * @param $arr_money_extra array
     */
    private function sendEmail($user, $deal, $arr_money, $is_last, $arr_money_extra) {
        if (isset($user['user_type']) && (int)$user['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
            $userName =$user->user_name;
        }else{
            $userName =get_deal_username($user->id);
        }
        $notice = array(
            "user_name"   => $userName,
            "deal_name"   => $deal['name'],
            "deal_url"    => $deal['share_url'],
            "site_name"   => app_conf("SHOP_TITLE"),
            "help_url"    => get_deal_domain($deal['id']) . '/helpcenter',
            "repay_money" => $arr_money['total'],
        );

        $msgcenter = new \Msgcenter();
        if ($is_last) {
            $notice['all_repay_money']  = $arr_money_extra['all_repay_money'];
            $notice['impose_money']     = $arr_money_extra['all_impose_money'] > 0 ? "其中违约金为:{$arr_money_extra['all_impose_money']}元," : "";
            $notice['all_income_money'] = $arr_money_extra['all_income_money'];

            $msgcenter->setMsg($user->email, $user->id, $notice, 'TPL_DEAL_LOAD_REPAY_EMAIL_LAST', "“{$deal['name']}”回款通知");
        } else {
            $notice['next_repay_time']  = to_date($arr_money_extra['time'], "Y年m月d日");
            $notice['next_repay_money'] = number_format($arr_money_extra['total'], 2);

            $msgcenter->setMsg($user->email, $user->id, $notice, 'TPL_DEAL_LOAD_REPAY_EMAIL', "“{$deal['name']}”回款通知");
        }
        $msgcenter->save();
    }

    /**
     * 向出借人发送回款站内信
     * @param $deal object 订单信息
     * @param $loan_user object
     * @param $arr_money array 回款金额信息
     * @param $is_last boolean 是否是最后一次回款
     * @param $arr_money_extra boolean 下次回款金额信息
     */
    private function sendMessage($deal, $loan_user, $arr_money, $is_last, $arr_money_extra) {
        $dealService = new DealService();
        $isHF = $dealService->isDealHF($deal['id']);

        $repay_money = $arr_money[self::MONEY_PRINCIPAL] + $arr_money[self::MONEY_INTREST] + $arr_money[self::MONEY_MANAGE] + $arr_money[self::MONEY_IMPOSE];
        $repay_money_format = format_price($repay_money);
        $principal_format = format_price($arr_money[self::MONEY_PRINCIPAL]);
        $interest_format = format_price($arr_money[self::MONEY_INTREST]);
        $compensation_format = format_price($arr_money[self::MONEY_COMPENSATION]);
        $impose_format = format_price($arr_money[self::MONEY_IMPOSE]);

        if (!$is_last) {
            $next_repay_date = to_date($arr_money_extra['time'], 'Y年m月d日');
            $next_repay_money_format = format_price($arr_money_extra[self::MONEY_PRINCIPAL] + $arr_money_extra[self::MONEY_INTREST] + $arr_money_extra[self::MONEY_MANAGE]);

            $content = sprintf('您投资的 “%s”成功回款%s。本笔投资的下个回款日为%s，需还本息%s。', $deal['name'], $repay_money_format, $next_repay_date, $next_repay_money_format);
        } else {
            $next_repay_date = 0;
            $next_repay_money_format = 0;

            $content = sprintf('您投资的“%s”成功回款%s，本次投资共回款%s，收益:%s。本次投资已回款完毕。', $deal['name'], $repay_money_format, $repay_money_format, $interest_format);
        }

        if($isHF){
            $content.= "本金已根据您的授权转入云图控股账户，详情查询您的账户中合同协议，如有问题咨询400-110-0025";
        }

        $load_counts = DealLoadModel::instance()->getDealLoadCountsByUserId($deal['id'], $loan_user->id, true);
        $structured_content = array(
            'money' => sprintf('+%s', number_format($repay_money, 2)),
            'repay_periods' => $is_last ? '已完成' : sprintf('%s/%s期', $deal['repay_periods_order'], $deal['repay_periods_sum']), // 期数
            'main_content' => rtrim(sprintf("%s%s%s%s%s%s%s",
                                            sprintf("项目：%s（%s笔）\n", $deal['name'], $load_counts),
                                            empty($arr_money[self::MONEY_PRINCIPAL]) ? '' : sprintf("本金：%s\n", $principal_format),
                                            empty($arr_money[self::MONEY_INTREST]) ? '' : sprintf("收益：%s\n", $interest_format),
                                            empty($arr_money[self::MONEY_COMPENSATION]) ? '' : sprintf("提前还款补偿金：%s\n", $compensation_format),
                                            empty($next_repay_date) ? '' : sprintf("下次回款日：%s\n", to_date($arr_money_extra['time'], 'Y-m-d')),
                                            empty($next_repay_money_format) ? '' : sprintf("下次回款额：%s\n", $next_repay_money_format),
                                            empty($arr_money[self::MONEY_IMPOSE]) ? '' : sprintf("逾期利息：%s\n", $impose_format)
                                            )),
            'is_last' => $is_last,
            'prepay_tips' => '',
            'turn_type' => $is_last ? MsgBoxEnum::TURN_TYPE_CONTINUE_INVEST : MsgBoxEnum::TURN_TYPE_REPAY_CALENDAR, // app 跳转类型标识
        );

        $msgbox = new MsgBoxService();
        $msgbox->create($loan_user->id, 9, '回款', $content, $structured_content);
    }

    /**
     * 获取单笔投资回款总额
     * @param $deal_id int
     * @param $loan_user_id int
     * @return float 回款总额
     */
    public function getTotalRepayMoney($deal_id, $loan_user_id) {
        $sql = sprintf('SELECT SUM(`money`) AS `m`,`type` FROM %s WHERE `deal_id`= %d AND `loan_user_id`= %d AND `status`= %d GROUP BY `type`', $this->tableName(), $deal_id, $loan_user_id, self::STATUS_ISPAYED);
        $result = $this->findAllBySql($sql);
        $total_money = 0;
        foreach ($result as $val) {
            $total_money += $val['m'];
        }
        return $total_money;
    }

    /**
     * 根据投资id获取回款总额
     * @param array $arr_deal_loan_id
     * @return float
     */
    public function getTotalRepayMoneyByDealLoanIds($arr_deal_loan_id) {
        $str = implode(",", $arr_deal_loan_id);
        $sql = sprintf("SELECT SUM(`money`) AS `m` FROM %s WHERE `deal_loan_id` IN (%s)", $this->tableName(), $this->escape($str));
        $res = $this->findBySql($sql);
        return floatval($res['m']);
    }

    /**
     * 根据投资id和金额类别获取总金额
     * @param $deal_loan_id int 投资id
     * @param $type int 金额类别
     * @param $is_payed int 是否已还
     * @return float 总金额
     */
    public function getTotalMoneyByTypeLoanId($deal_loan_id, $type, $is_payed = 1) {
        $deal_loan_id = intval($deal_loan_id);
        $type = intval($type);
        $is_payed = intval($is_payed);
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_loan_id`='%d' AND `type`='%d'";
        $sql = sprintf($sql, $this->tableName(), $deal_loan_id, $type);
        if ($is_payed == 1) {
            $sql .= sprintf(" AND `status`='%d'", self::STATUS_ISPAYED);
        }
        $res = $this->findBySql($sql, array(), true);
        return $res['sum'];
    }

    public function getTotalMoneyByTypeStatusLoanId($deal_loan_id, $type, $status,$deal_id=0) {
        $deal_loan_id = intval($deal_loan_id);
        $type = intval($type);

        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_loan_id`='%d' AND `type`='%d' AND `status`='%d'";
        if($deal_id){
            $sql.=" AND deal_id=".$deal_id;
        }
        $sql = sprintf($sql, $this->tableName(), $deal_loan_id, $type,$status);
        $res = $this->findBySql($sql, array(), true);
        return $res['sum'];
    }

    /**
     * 根据标的id和金额类别获取总金额
     * @param $deal_id int 标的id
     * @param $types array 金额类别
     * @param $is_payed int 是否已还
     * @return float 总金额
     */
    public function getTotalMoneyByTypeDealId($deal_id, $types, $is_payed = false) {
        $deal_id = intval($deal_id);
        if(!is_array($types)) {
            $types = array($types);
        }
        $type_str = implode(',', $types);
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id`='%d' AND `type` IN (%s) AND `time`!='0'";
        $sql = sprintf($sql, $this->tableName(), $deal_id, $type_str);
        if ($is_payed !== false) {
            $sql .= sprintf(" AND `status`='%d'", intval($is_payed));
        }
        $res = $this->findBySql($sql, array(), true);
        return $res['sum'];
    }


    /**
     * 根据项目id和金额类别获取总金额
     * @param $project_id int 项目id
     * @param $type int 金额类别
     * @param $is_payed int 是否已还
     * @return float 总金额
     */
    public function getTotalMoneyByTypeProjectId($project_id, $type, $is_payed = false) {
        $project_id = intval($project_id);
        $type = intval($type);
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `type`='%d' AND `time`!='0' AND `deal_id` IN (SELECT `id` FROM %s WHERE `project_id`='%d')";
        $sql = sprintf($sql, $this->tableName(), $type, DealModel::instance()->tableName(), $project_id);
        if ($is_payed !== false) {
            $sql .= sprintf(" AND `status`='%d'", intval($is_payed));
        }
        $res = $this->findBySql($sql, array(), true);
        return $res['sum'];
    }

    /**
     * 获取项目下所有标某日该还
     * @param intval $project_id
     * @param intval $time
     * @param intval $status 默认未还
     */
    public function getLglTotalMoneyByProjectId($project_id, $time, $status = 0){
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `type` IN (8,9) AND `time` = '%d' AND `status` = '%d' AND `deal_id` IN (SELECT `id` FROM %s WHERE `project_id`='%d')";
        $sql = sprintf($sql, $this->tableName(), $time, $status, DealModel::instance()->tableName(), $project_id);
        $res = $this->findBySql($sql, array(), true);
        return floatval($res['sum']);
    }

    /**
     * getLGLDelayList
     * 获得利滚利逾期列表
     *
     * @access public
     * @return void
     */
    public function getLGLDelayList()
    {
//        $sql = "SELECT r.`deal_id` , r.`borrow_user_id`,d.`name`,d.`borrow_amount`,d.`rate`,d.`repay_time`,d.`loantype`, d.`is_during_repay`,
// u.`user_name`,u.`real_name`,u.`mobile`,u.`money`, sum(r.`money`) as need_repay FROM :table_name r, :table_deal d ,:table_user u WHERE r.`time` < :time AND r.`time`!='0' AND r.`status` = 0 AND r.`type` IN (:principal, :interest) AND r.deal_id = d.id AND r.`borrow_user_id` = u.`id` group by deal_id";

        $sql = "SELECT r.`deal_id`, r.`borrow_user_id`, SUM(r.`money`) AS need_repay FROM :table_name as r WHERE r.`deal_id` in (SELECT d.`id` FROM :table_deal AS d WHERE d.`deal_type` = 1 AND d.`deal_status` = 4) AND r.`type` IN (:principal, :interest) AND r.`time` <= :time AND r.`time` != '0' AND r.`status` = '0' GROUP BY r.`deal_id`";

        $param = array (
            ':table_name' => $this->tableName(),
            ':table_deal' => DealModel::instance()->tableName(),
            //':table_user' => UserModel::instance()->tableName(),
            ':time' => get_gmtime(),
            ':principal' => self::MONEY_COMPOUND_PRINCIPAL,
            ':interest' => self::MONEY_COMPOUND_INTEREST,
        );
        $deal_loan_repays = $this->findAllBySql($sql,true, $param, true);

        $i = 0;
        $rs = array();
        foreach($deal_loan_repays as $k=> $v){
            $rs[$i]['deal_id'] = $v['deal_id'];
            $rs[$i]['borrow_user_id'] = $v['borrow_user_id'];
            $rs[$i]['need_repay'] = $v['need_repay'];

            //获取deal表中的信息
            $deal_info = DealModel::instance()->find($v['deal_id'],'name,borrow_amount,rate,repay_time,loantype,is_during_repay');
            if($deal_info != null){
                $rs[$i]['name'] = $deal_info['name'];
                $rs[$i]['borrow_amount'] = $deal_info['borrow_amount'];
                $rs[$i]['rate'] = $deal_info['rate'];
                $rs[$i]['repay_time'] = $deal_info['repay_time'];
                $rs[$i]['loantype'] = $deal_info['loantype'];
                $rs[$i]['is_during_repay'] = $deal_info['is_during_repay'];
            }

            //获取用户信息
            $user_info = UserModel::instance()->find($v['borrow_user_id'],'user_name,real_name,mobile,money');
            if($user_info != null){
                $rs[$i]['user_name'] = $user_info['user_name'];
                $rs[$i]['real_name'] = $user_info['real_name'];
                $rs[$i]['mobile'] = $user_info['mobile'];
                $rs[$i]['money'] = $user_info['money'];
            }

            $i++;
        }

        return $rs;
    }


    public function getLGLDelayCount()
    {
        $sql = "SELECT count(`deal_id`) from (SELECT r.`deal_id` , r.`borrow_user_id`,d.`name`,d.`borrow_amount`,d.`rate`,d.`repay_time`,d.`loantype`, d.`is_during_repay`,
 u.`user_name`,u.`real_name`,u.`mobile`,u.`money`, sum(r.`money`) as need_repay FROM :table_name r, :table_deal d ,:table_user u WHERE r.`time` < :time AND r.`time`!='0' AND r.`status` = 0 AND r.`type` IN (:principal, :interest) AND r.deal_id = d.id AND r.`borrow_user_id` = u.`id` group by r.`deal_id`) as tmp";

        $param = array (
            ':table_name' => $this->tableName(),
            ':table_deal' => DealModel::instance()->tableName(),
            ':table_user' => UserModel::instance()->tableName(),
            ':time' => get_gmtime(),
            ':principal' => self::MONEY_COMPOUND_PRINCIPAL,
            ':interest' => self::MONEY_COMPOUND_INTEREST,
        );
        $cnt = $this->countBySql($sql, $param, true);
        return $cnt;

    }

    /**
     * 获取用户利滚利 回款列表
     * @param $user_id
     * @param $deal_id
     */
    public function getUserCompoundLoanByDealID($deal_id ,$user_id = 0){
        $user_id = ($user_id == 0 && !empty($GLOBALS['user_info'])) ? $GLOBALS['user_info']['id'] : intval($user_id);
        if($user_id <= 0){
            return false;
        }
        $condition = " `loan_user_id` = ':loan_user_id' AND  `deal_id` = ':deal_id' ";
        $param = array (
            ':loan_user_id' => $user_id,
            ':deal_id' => $deal_id,
        );
        return $this->findByViaSlave($condition, ' `type` , `real_time` , `status`' ,$param);
    }

    /**
     * 根据deal_loan_id更新回款状态
     * @param array $arr_deal_loan_id
     * @param int $time
     * @return bool
     */
    public function repayCompoundByDealLoanId($arr_deal_loan_id, $time=0) {
        $str = implode(',', $arr_deal_loan_id);
        $time = $time == 0 ? get_gmtime() : $time;
        $sql = "UPDATE " . $this->tableName() . " SET `real_time`='" . $time . "', `status`='1', `update_time`='" . get_gmtime() . "' WHERE `deal_loan_id` IN ({$str})";
        return $this->execute($sql);
    }

    /**
     * 提前还款时，将未还的还款计划设置为取消
     * @param int $deal_loan_id
     * @return bool
     */
    public function cancelDealLoanRepay($deal_loan_id) {
        $params = array(
            ":deal_loan_id" => $deal_loan_id,
            ":status" => self::STATUS_NOTPAYED,
        );

        $list = $this->findAll("`deal_loan_id`=':deal_loan_id' AND `status`=':status'", false, "*", $params);

        if (!$list) {
            return false;
        }
        $dealService = new DealService();
        $isDT = $dealService->isDealDT($list[0]['deal_id']);

        $money_interest = 0;
        $calInfo = array();
        $loan_user_id = 0;

        foreach ($list as $v) {
            $v['status'] = self::STATUS_CANCEL;
            $v['update_time'] = get_gmtime();
            if ($v->save() === false) {
                throw new \Exception("update deal loan repay fail");
            }
            $calcTime = strtotime(to_date($v['time'],'Y-m-d'));
            $loan_user_id = $v['loan_user_id'];

            if(!isset($calInfo[$calcTime])) {
                $calInfo[$calcTime] = array();
            }

            if ($v['type'] == self::MONEY_INTREST) {
                $money_interest = bcadd($money_interest, $v['money'], 2);
                $calInfo[$calcTime][DealLoanRepayCalendarService::NOREPAY_INTEREST]+= -$v['money'];
            }
            if ($v['type'] == self::MONEY_PRINCIPAL) {
                $calInfo[$calcTime][DealLoanRepayCalendarService::NOREPAY_PRINCIPAL]+= -$v['money'];
            }
        }

        if(!$isDT){
            foreach($calInfo as $time=>$val) {
                DealLoanRepayCalendarService::collect($loan_user_id,$time,$val,$time);
            }
        }
        return $money_interest;
    }

    /**
     * 根据用户查询回款计划总额
     * @param int $user_id
     * @param int $type
     * @param int $status
     * @return float
     */
    public function getSumByUserId($user_id, $type=false, $status=false) {
        $sql = sprintf("SELECT SUM(`money`) AS `m` FROM " . $this->tableName() . " WHERE `loan_user_id`='%d'", intval($user_id));
        if ($type !== false) {
            if (is_array($type)) {
                $str = implode("','", $type);
                $sql .= sprintf(" AND `type` IN ('%s')", $str);
            } else {
                $sql .= sprintf(" AND `type`='%d'", intval($type));
            }
        }
        if ($status !== false) {
            if (is_array($status)) {
                $str = implode("','", $status);
                $sql .= sprintf(" AND `status` IN ('%s')", $str);
            } else {
                $sql .= sprintf(" AND `status`='%d'", intval($status));
            }
        }

        $res = $this->findBySqlViaSlave($sql);
        return floatval($res['m']);
    }


    /**
     * @param $deal_repay_id 还款id
     * @param $deal_loan_money 还款金额
     * @param $deal_loan_id  还款ID
     * @param $deal_loan_user_id 还款用户ID
     * @param bool $ignore_impose_money
     * @return float
     * @throws \Exception
     */
    public function repayDealLoanOne($param)
    {

        $deal_repay_id = $param['deal_repay_id'];
        $deal_loan_money = $param['deal_loan_money'];
        $deal_loan_id = $param['deal_loan_id'];
        $deal_loan_user_id = $param['deal_loan_user_id'];
        $ignore_impose_money = $param['ignore_impose_money'];
        $next_repay_id = $param['next_repay_id'];

        $deal_service = new DealService();
        $moneyInfo = array();
        $calInfo = array();

        $realTime = to_timespan(to_date(get_gmtime(),'Y-m-d'));// 提前还款的真实时间是 今日零点



        $deal_repay_model = new DealRepayModel();
        $deal_repay = $deal_repay_model->find($deal_repay_id);
        $deal_id = intval($deal_repay->deal_id);
        $deal_repay_id = intval($deal_repay->id);
        $deal = DealModel::instance()->find($deal_id);

        $isYJ175 = $deal_service->isDealYJ175($deal_id);


        if($deal_id == 5438830 && $deal_repay_id == 8921529){
            $realTime = 1530777600;
        }



        // 如果标的属于智多鑫，只更改本金还款状态，不操作账户变更，不变更还款日历，不变更资产总额；利息回款到利息账户
        $isDT = $deal_service->isDealDT($deal_id);
        $isDealXH = $deal_service->isDealYtsh($deal_id);

        $isDealExchange = ($deal['deal_type'] == DealModel::DEAL_TYPE_EXCHANGE) ? true : false;//是不是大金所标的
        $isDealCG = $deal_service->isP2pPath($deal);

        $isND = $deal_service->isDealND($deal_id);

        $user_model = new UserModel();
        $user = $user_model->find($deal_loan_user_id);

        $credit_loan_service = new CreditLoanService();

        if(bccomp($deal_repay->principal,'0.00',2) > 0){
            $isNeedFreeze = $credit_loan_service->isNeedFreeze($deal,$deal_loan_user_id,$deal_repay_id,1);
        }else{
            $isNeedFreeze = false; // 还款本金为0时不请求速贷
        }

        $condition = "`deal_repay_id`= '%d' AND `deal_loan_id` = '%d' AND `loan_user_id`= '%d' AND status = 0";
        $condition = sprintf($condition, $this->escape($deal_repay_id), $this->escape($deal_loan_id), $this->escape($user->id));
        //根据还款记录ID，投标记录ID，投资人ID
        $loan_repay_list = $this->findAll($condition);
        if(empty($loan_repay_list)){
            return true;
        }

        $partRepayModel = new DealLoanPartRepayModel();


        // 开始给一个用户还款
        $GLOBALS['db']->startTrans();
        try {
            $user->changeMoneyDealType = $deal_service->getDealType($deal);
            $repayMoney = 0;

            //发生逾期还款
            if ($deal_repay->status == 2 && !$ignore_impose_money) {
                $fee_of_overdue = $deal_loan_money / $deal["borrow_amount"] * $deal_repay->impose_money;
                // 逾期罚息进行舍余
                $fee_of_overdue = $deal->floorfix($fee_of_overdue);
                // TODO finance 逾期罚息 | Repayment
                $loan_repay = new DealLoanRepayModel();
                $loan_repay->deal_id = $deal_id;
                $loan_repay->deal_repay_id = $deal_repay->id;
                $loan_repay->deal_loan_id = $deal_loan_id;
                $loan_repay->loan_user_id = $deal_loan_user_id;
                $loan_repay->borrow_user_id = $deal["user_id"];
                $loan_repay->money = $fee_of_overdue;
                $loan_repay->type = self::MONEY_IMPOSE;//逾期罚息
                $loan_repay->time = $deal_repay->repay_time;
                $loan_repay->real_time = $realTime;//get_gmtime();
                $loan_repay->status = self::STATUS_ISPAYED;//已还
                $loan_repay->create_time = get_gmtime();
                $loan_repay->update_time = get_gmtime();
                $loan_repay->deal_type = $deal_repay->deal_type;
                $query_insert = $loan_repay->insert();
                if ($query_insert === false) {
                    throw new \Exception("逾期罚息编号{$deal_id} {$deal['name']}");
                }

                if ($isDT === true) {
                      //不再转给利息账户，由智多鑫计算产生
//                    $interest_user = $user_model->find(app_conf('AGENCY_ID_DT_INTEREST'));
//                    $interest_user->changeMoneyDealType = $deal_service->getDealType($deal);
//                    $interest_user->changeMoney($fee_of_overdue, "逾期罚息", "编号{$deal_id} {$deal['name']}");
                } elseif ($isND == true) {

                } else {
                    $user->isDoNothing = $isYJ175 ? true : false;
                    $bizToken = [
                        'dealId' => $deal_id,
                        'dealRepayId' => $deal_repay->id,
                        'dealLoadId' => $deal_loan_id,
                    ];
                    $user->changeMoney($fee_of_overdue, "逾期罚息", "编号{$deal_id} {$deal['name']}", 0, 0, 0, 0, $bizToken);
                    $repayMoney+=$fee_of_overdue;
                    $moneyInfo[UserLoanRepayStatisticsService::LOAD_YQ_IMPOSE] = $fee_of_overdue;

                    $calInfo[$realTime][DealLoanRepayCalendarService::REPAY_INTEREST]+= $fee_of_overdue;
                }
            }

            foreach ($loan_repay_list as $loan_repay) {
                // 逐条变更回款记录状态
                //$real_time =  to_timespan(to_date(get_gmtime(),'Y-m-d'));//get_gmtime();
                $loan_repay->real_time = $realTime;
                $loan_repay->update_time = get_gmtime();
                $loan_repay->status = self::STATUS_ISPAYED;
                if ($loan_repay->save() === false) {
                    throw new \Exception("变更{$loan_repay->id}回款记录状态失败");
                }

                switch ($loan_repay['type']) {
                    //本金
                    case self::MONEY_PRINCIPAL :
                        if ($loan_repay['money'] != 0) {
                            if ($isDT === true) {
                                break;
                            }
                            $bizToken = [
                                'dealId' => $deal_id,
                                'dealRepayId' => $deal_repay_id,
                                'dealLoadId' => $loan_repay['deal_loan_id'],
                            ];
                            if($isND === true) {
                               $this->addNDRepayMoneyLog($deal_repay_id,$loan_repay['deal_loan_id'],"还本","编号{$deal_id} {$deal['name']}",$user,PartialRepayService::FEE_TYPE_PRINCIPAL,$bizToken);
                            } else {
                                // TODO finance 偿还本金 | Repayment
                                $user->isDoNothing = $isYJ175 ? true : false;

                                $user->changeMoney($loan_repay['money'], "还本", "编号{$deal_id} {$deal['name']}", 0, 0, 0, 0, $bizToken);
                            }
                            $this->repairMoneyOnrepay($loan_repay['deal_loan_id'], $loan_repay['money'], $user->id);
                            $repayMoney+=$loan_repay['money'];

                            $calInfo[$realTime][DealLoanRepayCalendarService::REPAY_PRINCIPAL] = $loan_repay['money']; // 真实还款日期本金增加
                            $calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_PRINCIPAL]-=$loan_repay['money']; // 原有日期本金减少

                            //哈哈农庄化肥标逻辑（将投资用户本金转入云图控股用户账户)
                            if($deal_service->isDealHF($deal_id)){
                                $userHF = $user_model->find(app_conf('CLOUD_PIC_USER_ID'));
                                $bizToken = [
                                    'dealId' => $deal_id,
                                    'dealRepayId' => $deal_repay_id,
                                    'dealLoadId' => $loan_repay['deal_loan_id'],
                                ];
                                $user->changeMoney(-$loan_repay['money'], "授权转出", "编号{$deal_id}, 标的名称{$deal['name']},本金授权转出", 0, 0, 0, 0, $bizToken);
                                $userHF->changeMoney($loan_repay['money'], "授权转入", "编号{$deal_id} 标的名称{$deal['name']},{$user->real_name}(".moblieFormat($user->mobile).")授权转入", 0, 0, 0, 0, $bizToken);
                            }elseif($credit_loan_service->isCreditingUser($user->id,$deal_id)){
                                $user->isDoNothing = $isYJ175 ? true : false;
                                $bizToken = [
                                    'dealId' => $deal_id,
                                ];
                                $user->changeMoney($loan_repay['money'], '贷款冻结', '冻结 "' . $deal['name'] .'" 投资本金',0,0,UserModel::TYPE_LOCK_MONEY,0,$bizToken);
                            }elseif($isNeedFreeze === true){
                                /** 如果用户发生过借款 冻结用户本金  $credit_loan_service */
                                $user->isDoNothing = $isYJ175 ? true : false;
                                $bizToken = [
                                    'dealId' => $deal_id,
                                ];
                                $user->changeMoney($loan_repay['money'], '网信速贷还款冻结', '冻结 "' . $deal['name'] .'" 投资本金',0,0,UserModel::TYPE_LOCK_MONEY,0,$bizToken);
                                $credit_loan_service->freezeNotifyCreditloan($user->id,$deal_id,$deal_repay_id,1);
                            }



                            if(!isset($moneyInfo[UserLoanRepayStatisticsService::NOREPAY_PRINCIPAL])) {
                                $moneyInfo[UserLoanRepayStatisticsService::NOREPAY_PRINCIPAL] = 0;
                            }
                            if(!isset($calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_PRINCIPAL])) {
                                $calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_PRINCIPAL] = 0;
                            }

                            $moneyInfo[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY] = $loan_repay['money'];
                            $moneyInfo[UserLoanRepayStatisticsService::NOREPAY_PRINCIPAL] = -$loan_repay['money'];

                            if($isDealExchange) {//大金所收集
                                //大金所待回本金
                                if(!isset($moneyInfo[UserLoanRepayStatisticsService::JS_NOREPAY_PRINCIPAL])) {
                                    $moneyInfo[UserLoanRepayStatisticsService::JS_NOREPAY_PRINCIPAL] = 0;
                                }
                                $moneyInfo[UserLoanRepayStatisticsService::JS_NOREPAY_PRINCIPAL] = -$loan_repay['money'];
                            }

                            if($isDealCG) {// 存管网贷
                                if(!isset($moneyInfo[UserLoanRepayStatisticsService::CG_NOREPAY_PRINCIPAL])) {
                                    $moneyInfo[UserLoanRepayStatisticsService::CG_NOREPAY_PRINCIPAL] = 0;
                                }
                                $moneyInfo[UserLoanRepayStatisticsService::CG_NOREPAY_PRINCIPAL] = -$loan_repay['money'];
                            }
                        }
                        break;
                    //利息
                    case self::MONEY_INTREST :
                        if ($isDT === true) {
                            //智多鑫还款依赖智多鑫计算，此处不处理
//                            $interest_user = $user_model->find(app_conf('AGENCY_ID_DT_INTEREST'));
//                            $interest_user->changeMoneyDealType = $deal_service->getDealType($deal);
//                            $interest_user->changeMoney($loan_repay['money'], "付息", "编号{$deal_id} {$deal['name']}");
                            break;
                        } else {
                            $bizToken = [
                                'dealId' => $deal_id,
                                'dealRepayId' => $deal_repay_id,
                                'dealLoadId' => $loan_repay['deal_loan_id'],
                            ];
                            if($isND === true) {
                                $this->addNDRepayMoneyLog($deal_repay_id,$loan_repay['deal_loan_id'],"付息","编号{$deal_id} {$deal['name']}",$user,PartialRepayService::FEE_TYPE_INTEREST,$bizToken);
                            } else {
                                // TODO finance 偿还利息 | Repayment
                                $user->isDoNothing = $isYJ175 ? true : false;
                                $user->changeMoney($loan_repay['money'], "付息", "编号{$deal_id} {$deal['name']}", 0, 0, 0, 0, $bizToken);
                            }

                            // 智多鑫标的不变更回款日历
                            if(!isset($calInfo[$realTime][DealLoanRepayCalendarService::REPAY_INTEREST])) {
                                $calInfo[$realTime][DealLoanRepayCalendarService::REPAY_INTEREST] = 0;
                            }
                            if(!isset($calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_INTEREST])) {
                                $calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_INTEREST] = 0;
                            }
                            $calInfo[$realTime][DealLoanRepayCalendarService::REPAY_INTEREST] += $loan_repay['money']; // 真实还款日期利息增加
                            $calInfo[$loan_repay->time][DealLoanRepayCalendarService::NOREPAY_INTEREST]-=$loan_repay['money']; // 原还款日期本金减少
                        }
                        $repayMoney+=$loan_repay['money'];

                        $moneyInfo[UserLoanRepayStatisticsService::LOAD_EARNINGS] = $loan_repay['money'];
                        $moneyInfo[UserLoanRepayStatisticsService::NOREPAY_INTEREST] = -$loan_repay['money'];

                        if(!isset($moneyInfo[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY])) {
                            $moneyInfo[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY] = 0;
                        }
                        $moneyInfo[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY] += $loan_repay['money'];

                        if($isDealExchange) {//大金所收集
                            //大金所待收收益
                            if(!isset($moneyInfo[UserLoanRepayStatisticsService::JS_NOREPAY_EARNINGS])) {
                                $moneyInfo[UserLoanRepayStatisticsService::JS_NOREPAY_EARNINGS] = 0;
                            }
                            $moneyInfo[UserLoanRepayStatisticsService::JS_NOREPAY_EARNINGS] = -$loan_repay['money'];
                            //大金所累计收益
                            if(!isset($moneyInfo[UserLoanRepayStatisticsService::JS_TOTAL_EARNINGS])) {
                                $moneyInfo[UserLoanRepayStatisticsService::JS_TOTAL_EARNINGS] = 0;
                            }
                            $moneyInfo[UserLoanRepayStatisticsService::JS_TOTAL_EARNINGS] = + $loan_repay['money'];
                        }
                        if($isDealCG) {// 存管网贷
                            if(!isset($moneyInfo[UserLoanRepayStatisticsService::CG_NOREPAY_EARNINGS])) {
                                $moneyInfo[UserLoanRepayStatisticsService::CG_NOREPAY_EARNINGS] = 0;
                            }
                            $moneyInfo[UserLoanRepayStatisticsService::CG_NOREPAY_EARNINGS] = -$loan_repay['money'];
                            if(!isset($moneyInfo[UserLoanRepayStatisticsService::CG_TOTAL_EARNINGS])) {
                                $moneyInfo[UserLoanRepayStatisticsService::CG_TOTAL_EARNINGS] = 0;
                            }
                            $moneyInfo[UserLoanRepayStatisticsService::CG_TOTAL_EARNINGS] = + $loan_repay['money'];
                        }


                        break;
                    //管理费
                    case self::MONEY_MANAGE :
                        if($isND === true) {
                            break;
                        }
                        // 出借人平台管理费转入平台账户
                        $platform_user_id = app_conf('MANAGE_FEE_USER_ID');
                        $platform_user = $user_model->find($platform_user_id);
                        if (!empty($platform_user) && $loan_repay['money'] != 0) {
                            $log_note = "编号{$deal_id} {$deal['name']} 投资记录ID{$loan_repay['deal_loan_id']}";
                            // TODO finance 交纳平台管理费 | Repayment
                            $platform_user->isDoNothing = $isYJ175 ? true : false;
                            $platform_user->changeMoneyDealType = $deal_service->getDealType($deal);
                            $bizToken = [
                                'dealId' => $deal_id,
                                'dealRepayId' => $deal_repay_id,
                                'dealLoadId' => $loan_repay['deal_loan_id'],
                            ];
                            $platform_user->changeMoney($loan_repay['money'], "平台管理费", $log_note, 0, 0, 0, 0, $bizToken);
                        }
                        break;
                    default:
                        continue;
                }
            }

            if(!empty($moneyInfo)) {
                if (UserLoanRepayStatisticsService::updateUserAssets($deal_loan_user_id,$moneyInfo) === false) {
                    throw new \Exception("user loan repay statistics error");
                }
            }

            if (!empty($calInfo)) {
                foreach($calInfo as $key=>$cinfo) {
                    $time = strtotime(to_date($key)); // 转为无差别时间
                    if (DealLoanRepayCalendarService::collect($deal_loan_user_id,$time,$cinfo,$time) === false) {
                        throw new \Exception("collect calendar error");
                    }
                }
            }

            if(!JfTransferService::instance()->repayTransferToJf($user,$deal_id,$deal_loan_id,$repayMoney)) {
                throw new \Exception("JfTransferService error");
            }

            if($isDealXH){
                $bizToken = [
                    'dealId' => $deal_id,
                ];
                $user->changeMoney($repayMoney, '享花还款冻结', '冻结 "' . $deal['name'] .'" 投资本息',0,0,UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
            }

            $jobs_model = new JobsModel();
            //判断是否使用了加息券
            $o2oDiscountRateService = new O2ODiscountRateService();
            if($o2oDiscountRateService->isNeedDiscountRate($deal_loan_id)) {
                $function = '\core\service\oto\O2ODiscountRateService::useDiscountRate';
                $token = \libs\utils\Token::genToken();
                $param = array(
                    'token' => $token,
                    'dealLoanId' => $deal_loan_id,
                );
                $jobs_model->priority = 85;
                $r = $jobs_model->addJob($function, $param, false, 90);
                if ($r === false) {
                    throw new \Exception("add O2ODiscountRateService useDiscountRate jobs error");
                }
            }

            // 回改partRepay 表中还款状态
            $partRes = $partRepayModel->updateLoanRepayStatus($deal_repay_id,$deal_loan_id);
            if(!$partRes){
                throw new \Exception("partRepay状态回改失败");
            }
            $rs = $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw new \Exception($e->getMessage());
        }
        if ($rs === false) {
            throw new \Exception("事务提交失败");
        }
        return true;
    }

    /**
     * 记录农担贷资金记录
     * @param $loanUser
     * @param $borrower
     * @param $compo
     */
    public function addNDRepayMoneyLog($repayId,$dealLoanId,$logType,$note,$loanUser,$feeTypes,$bizToken) {
        $partialRepayModel = new PartialRepayModel();
        //支付借款人的钱
        $borrowerRepayMoney = $partialRepayModel->getMoneyByLoanId($repayId,$dealLoanId,PartialRepayModel::REPAY_TYPE_BORROWER,$feeTypes);
        if(bccomp($borrowerRepayMoney,'0.00',2) == 1) { //借款人还款大于0
            if ($loanUser->changeMoney($borrowerRepayMoney, $logType, $note, 0, 0, 0, 0, $bizToken) === false) {
                throw new \Exception("还款支付{$logType}失败");
            }
        }

        //支付代偿机构的钱
        $compensatoryRepayMoney = $partialRepayModel->getMoneyByLoanId($repayId,$dealLoanId,PartialRepayModel::REPAY_TYPE_COMPENSATORY,$feeTypes);
        if(bccomp($compensatoryRepayMoney,'0.00',2) == 1) { //代偿还款大于0
            if ($loanUser->changeMoney($compensatoryRepayMoney, $logType, $note, 0, 0, 0, 0, $bizToken) === false) {
                throw new \Exception("还款支付{$logType}失败");
            }
        }
        return true;
    }


    public function getRepayCountByDealRepayId($deal_repay_id)
    {
        $sql = sprintf("SELECT count(*) AS `all` FROM %s WHERE `deal_repay_id` = '%d' AND `status`=0", $this->tableName(), $this->escape($deal_repay_id));
        $query_ret = $this->db->getOne($sql);
        if ($query_ret === false) {
            throw new \Exception("获取还款数量失败");
        }
        return $query_ret;
    }

    public function getRepayCountByDeal($deal_id)
    {
        $sql = sprintf("SELECT count(*) AS `all` FROM %s WHERE `deal_id` = '%d' AND `status`=0", $this->tableName(), $this->escape($deal_id));
        $query_ret = $this->db->getOne($sql);
        if ($query_ret === false) {
            throw new \Exception("获取还款数量失败");
        }
        return $query_ret;
    }


    /**
     * 取得用户某个通知贷款的回款记录
     * @param integer $deal_id
     * @param timestamp $time
     */
    public function getUserCompoundRecord($deal_id,$real_time) {
        $sql = "SELECT `loan_user_id`,`deal_loan_id`,`money`,`real_time`,`type` FROM ".$this->tableName();
        $sql.=" WHERE `deal_id`=':deal_id' AND `status` = 1 and `real_time`=':real_time' AND `type` in(8,9)";

        $params = array(
            ':deal_id' => $deal_id,
            ':real_time' => $real_time,
        );
        $res = $this->findAllBySql($sql,true,$params);
        return $res;
    }

    /**
     * 取得用户某个提前还款的回款记录
     * @param integer $deal_id
     * @param integer $repayId
     */
    public function getUserPrepayRecord($deal_id,$repayId) {
        $sql = "SELECT `loan_user_id`,`deal_loan_id`,`money`,`real_time`,`type` FROM ".$this->tableName();
        $sql.=" WHERE `deal_id`=':deal_id' AND `deal_repay_id`=':deal_repay_id' AND `status` = 1 and `type` in(3,4,7)";

        $params = array(
            ':deal_id' => $deal_id,
            ':deal_repay_id' => $repayId,
        );
        $res = $this->findAllBySql($sql,true,$params);
        return $res;
    }

    /**
     * 取得某个标某次回款的回款记录
     * @param integer $deal_id
     * @param integer $repayId
     */
    public function getUserNormalRecord($deal_id,$repayId) {
        //$sql = "SELECT `loan_user_id`,deal_loan_id,sum(money) as `money`,`real_time` FROM ".$this->tableName();
        //$sql.=" where `deal_id`=':deal_id' and deal_repay_id=':deal_repay_id' and `status` = 1 and  type in(1,2,5) group by loan_user_id";

        $sql = "SELECT `loan_user_id`,`deal_loan_id`,`money`,`real_time`,`type` FROM ".$this->tableName();
        $sql.=" WHERE `deal_id`=':deal_id' AND `deal_repay_id`=':deal_repay_id' AND `status` = 1 AND `type` in(1,2,5)";

        $params = array(
            ':deal_id' => $deal_id,
            ':deal_repay_id' => $repayId,
        );
        $res = $this->findAllBySql($sql,true,$params);
        return $res;
    }

    /**
     * 获取用户的回款计划总额
     * @param int $user_id
     * @return array
     */
    public function getUserSummary($user_id) {
        $sql = "SELECT SUM(`money`) AS `m`, `type`, `status` FROM " . $this->tableName() . " WHERE `loan_user_id`=':user_id' GROUP BY `type`, `status`";
        $params = array(
            ':user_id' => $user_id,
        );
        $res = $this->findAllBySql($sql, true, $params, true);
        return $res;
    }

    public function getUserNoRepaySummaryByTime($user_id,$beginTime,$endTime) {
        $sql = "SELECT SUM(`money`) AS `m`, `type`, `status`,`time`,`real_time`  FROM " . $this->tableName();
        $sql.=" WHERE `loan_user_id`=':user_id' AND `status`=0 AND `real_time`=0 AND `time` BETWEEN ".$beginTime." AND ".$endTime." GROUP BY `time`,`type`, `status`";
        $params = array(
            ':user_id' => $user_id,
        );
        $res = $this->findAllBySql($sql, true, $params, true);
        return $res;
    }

    public function getUserRepaySummaryByTime($user_id,$beginTime,$endTime) {
        $sql = "SELECT SUM(`money`) AS `m`, `type`, `status`,`time`,`real_time`  FROM " . $this->tableName();
        $sql.=" WHERE `loan_user_id`=':user_id' AND `status`=1 AND real_time !=0 AND `real_time` BETWEEN ".$beginTime." AND ".$endTime." GROUP BY `real_time`,`type`, `status`";
        $params = array(
            ':user_id' => $user_id,
        );
        $res = $this->findAllBySql($sql, true, $params, true);
        return $res;
    }

    /*
     * 根据user_id整合回款记录
     * @param int $deal_id
     * @param int $deal_repay_id
     * @return array
     */
    public function getListByDealId($deal_id, $deal_repay_id) {
        $deal_id = intval($deal_id);
        $deal_repay_id = intval($deal_repay_id);
        $sql = "SELECT SUM(`money`) AS `m`, `loan_user_id`, `type`, `deal_loan_id`, COUNT(DISTINCT(`deal_loan_id`)) AS `c` FROM " . $this->tableName() . " WHERE `deal_id` = '{$deal_id}' AND `deal_repay_id` = '{$deal_repay_id}' AND `status`='" . self::STATUS_ISPAYED . "' GROUP BY `loan_user_id`, `type`";
        $result = $this->findAllBySql($sql, true, array());

        $list = array();
        foreach ($result as $val) {
            $list[$val['loan_user_id']]['deal_loan_id'] = $val['deal_loan_id'];
            $list[$val['loan_user_id']]['cnt'] = $val['c'];
            switch($val['type']) {
                case self::MONEY_PRINCIPAL:
                    $list[$val['loan_user_id']]['principal'] = $val['m'];break;
                case self::MONEY_INTREST:
                    $list[$val['loan_user_id']]['intrest'] = $val['m'];break;
                case self::MONEY_PREPAY:
                    $list[$val['loan_user_id']]['prepay'] = $val['m'];break;
                case self::MONEY_COMPENSATION:
                    $list[$val['loan_user_id']]['compensation'] = $val['m'];break;
                case self::MONEY_IMPOSE:
                    $list[$val['loan_user_id']]['impose'] = $val['m'];break;
                case self::MONEY_PREPAY_INTREST:
                    $list[$val['loan_user_id']]['prepayIntrest'] = $val['m'];break;
            }
        }

        return $list;
    }

    /**
     * 根据投资记录ID整合回款记录
     * @param $deal_id
     * @param $deal_repay_id
     * @return array
     */
    public function getListByRepayId($deal_id,$deal_repay_id){
        $deal_id = intval($deal_id);
        $deal_repay_id = intval($deal_repay_id);
        $sql = "SELECT SUM(`money`) AS `m`, `loan_user_id`, `type`, `deal_loan_id`,real_time FROM " . $this->tableName() . " WHERE `deal_id` = '{$deal_id}' AND `deal_repay_id` = '{$deal_repay_id}' AND `status`='" . self::STATUS_ISPAYED . "' GROUP BY `deal_loan_id`, `type`";
        $result = $this->findAllBySql($sql, true, array());

        $list = array();
        foreach ($result as $val) {
            $list[$val['deal_loan_id']]['loan_user_id'] = $val['loan_user_id'];
            $list[$val['deal_loan_id']]['real_time'] = $val['real_time'];
            switch($val['type']) {
                case self::MONEY_PRINCIPAL:
                    $list[$val['deal_loan_id']]['principal'] = $val['m'];break;
                case self::MONEY_INTREST:
                    $list[$val['deal_loan_id']]['intrest'] = $val['m'];break;
                case self::MONEY_PREPAY:
                    $list[$val['deal_loan_id']]['prepay'] = $val['m'];break;
                case self::MONEY_COMPENSATION:
                    $list[$val['deal_loan_id']]['compensation'] = $val['m'];break;
                case self::MONEY_IMPOSE:
                    $list[$val['deal_loan_id']]['impose'] = $val['m'];break;
                case self::MONEY_PREPAY_INTREST:
                    $list[$val['deal_loan_id']]['prepayIntrest'] = $val['m'];break;
            }
        }
        return $list;
    }

    public function getOneByUserId($deal_id,$deal_repay_id,$loan_user_id){
        $sql = "SELECT SUM(`money`) AS `money`,`type`, `loan_user_id`, `deal_loan_id`,real_time FROM " . $this->tableName() . " WHERE `deal_id` = '{$deal_id}' AND `deal_repay_id` = '{$deal_repay_id}' AND loan_user_id='{$loan_user_id}' AND `status`=" . self::STATUS_ISPAYED ." GROUP BY `type`";

        $result = $this->findAllBySql($sql, true, array());
        $list = array();
        foreach ($result as $val) {
            switch($val['type']) {
                case self::MONEY_PRINCIPAL:
                    $list['principal'] = $val['money'];break;
                case self::MONEY_INTREST:
                    $list['intrest'] = $val['money'];break;
                case self::MONEY_PREPAY:
                    $list['prepay'] = $val['money'];break;
                case self::MONEY_COMPENSATION:
                    $list['compensation'] = $val['money'];break;
                case self::MONEY_IMPOSE:
                    $list['impose'] = $val['money'];break;
                case self::MONEY_PREPAY_INTREST:
                    $list['prepayIntrest'] = $val['money'];break;
            }
        }
        return $list;
    }

    /*
     * 根据user_id整合回款记录，排除预约投资
     * @param int $deal_id
     * @param int $deal_repay_id
     * @return array
     */
    public function getNonReserveListByDealId($deal_id, $deal_repay_id) {
        $deal_id = intval($deal_id);
        $deal_repay_id = intval($deal_repay_id);
        $sql = "SELECT SUM(dlr.`money`) AS `m`, dlr.`loan_user_id`, dlr.`type`, dlr.`deal_loan_id`, COUNT(DISTINCT(dlr.`deal_loan_id`)) AS `c` FROM " . $this->tableName() . " AS dlr
                LEFT JOIN `firstp2p_deal_load` AS dl ON dlr.deal_loan_id = dl.id
                WHERE dlr.`deal_id` = '{$deal_id}' AND dlr.`deal_repay_id` = '{$deal_repay_id}' AND dlr.`status`='" . self::STATUS_ISPAYED . "' AND dl.`deal_id` = '{$deal_id}' AND dl.`source_type` != '" . DealLoadModel::$SOURCE_TYPE['reservation'] . "'
                GROUP BY dlr.`loan_user_id`, dlr.`type`";
        $result = $this->findAllBySql($sql, true, array());

        $list = array();
        foreach ($result as $val) {
            $list[$val['loan_user_id']]['deal_loan_id'] = $val['deal_loan_id'];
            $list[$val['loan_user_id']]['cnt'] = $val['c'];
            switch($val['type']) {
                case self::MONEY_PRINCIPAL:
                    $list[$val['loan_user_id']]['principal'] = $val['m'];break;
                case self::MONEY_INTREST:
                    $list[$val['loan_user_id']]['intrest'] = $val['m'];break;
                case self::MONEY_PREPAY:
                    $list[$val['loan_user_id']]['prepay'] = $val['m'];break;
                case self::MONEY_COMPENSATION:
                    $list[$val['loan_user_id']]['compensation'] = $val['m'];break;
                case self::MONEY_IMPOSE:
                    $list[$val['loan_user_id']]['impose'] = $val['m'];break;
                case self::MONEY_PREPAY_INTREST:
                    $list[$val['loan_user_id']]['prepayIntrest'] = $val['m'];break;
            }
        }

        return $list;
    }

    /**
     * 获取预约回款用户ID
     * @return array
     */
    public function getReserveDealRepayUserIds($startTime, $endTime) {
        $sql = sprintf("SELECT dlr.loan_user_id AS user_id FROM firstp2p_deal_loan_repay AS dlr
                INNER JOIN `firstp2p_reservation_deal_load` AS rdl ON dlr.deal_loan_id = rdl.load_id
                WHERE dlr.status = 1 AND dlr.real_time >= %s AND dlr.real_time <= %s GROUP BY dlr.loan_user_id", $startTime, $endTime);
        $result = $this->findAllBySqlViaSlave($sql, true);
        return $result;
    }

    /*
     * 根据userId获取预约回款记录
     * @param int $userId
     * @return array
     */
    public function getReserveDealRepaySumByUserId($userId, $startTime, $endTime) {
        $sql = sprintf("SELECT dlr.loan_user_id, dlr.type, SUM(dlr.money) AS money, dlr.deal_loan_id FROM firstp2p_deal_loan_repay AS dlr
                INNER JOIN `firstp2p_reservation_deal_load` AS rdl ON dlr.deal_loan_id = rdl.load_id
                WHERE dlr.status = 1 AND dlr.loan_user_id = %d AND dlr.real_time >= %d AND dlr.real_time <= %d GROUP BY dlr.type, dlr.deal_loan_id", $userId, $startTime, $endTime);
        $result = $this->findAllBySqlViaSlave($sql, true);

        $list = array(
            'cnt' => 0,
            'principal' => 0,
            'intrest' => 0,
            'prepay' => 0,
            'compensation' => 0,
            'impose' => 0,
            'prepayIntrest' => 0,
        );
        $dealLoanIdArr = array();
        foreach ($result as $val) {
            if (!isset($dealLoanIdArr[$val['deal_loan_id']])) {
                $list['cnt'] += 1;
                $dealLoanIdArr[$val['deal_loan_id']] = 1;
            }
            switch($val['type']) {
                case self::MONEY_PRINCIPAL:
                    $list['principal'] += $val['money'];
                    break;
                case self::MONEY_INTREST:
                    $list['intrest'] += $val['money'];
                    break;
                case self::MONEY_PREPAY:
                    $list['prepay'] += $val['money'];
                    break;
                case self::MONEY_COMPENSATION:
                    $list['compensation'] += $val['money'];
                    break;
                case self::MONEY_IMPOSE:
                    $list['impose'] += $val['money'];
                    break;
                case self::MONEY_PREPAY_INTREST:
                    $list['prepayIntrest'] += $val['money'];
                    break;
            }
        }

        return !empty($list['cnt']) ? $list : array();
    }

    /**
     * 获取等额本息还款方式的最后一期本金
     * @param array $deal_loan
     * @param object $deal
     * @return false|float
     */
    public function getFixPrincipalByLoanId($deal_loan, $deal) {
        $repay_times = $deal->getRepayTimes();
        $sql = "SELECT COUNT(`id`) AS `c`, SUM(`money`) AS `m` FROM %s WHERE `deal_loan_id` = '%d' AND `type`='%d'";
        $sql = sprintf($sql, $this->tableName(), $deal_loan['id'], self::MONEY_PRINCIPAL);
        $row = $this->findBySql($sql);
        $cnt = $row['c'];
        if ($repay_times != $cnt+1) {
            return false;
        } else {
            return bcsub($deal_loan['money'], $row['m'], 2);
        }
    }

    /**
     * 取得某用户已还或待还的金额按照标的汇总
     * @param $uid
     * @param $time
     * @param $type
     */
    public function getRepayDealSumaryByTime($uid,$time) {
        $sql = "SELECT SUM(`money`) AS `m`,`type`,`deal_id`,`time`,`real_time`,`status`,`deal_type` FROM firstp2p_deal_loan_repay WHERE loan_user_id={$uid} ";
        $sql.=" AND status !=2 AND (`real_time` = ".$time . " or (`time`=".$time." AND real_time =0))";
        $sql.=" GROUP by deal_id,type";
        return $this->findAllBySql($sql, true, array(),true);
    }

    /**
     * 取得因提前还款而取消的最大的预期还款时间
     * @param $deal_id
     * @return mixed
     */
    public function getMaxPrepayTimeByDealId($deal_id) {
        $sql = "SELECT MAX(time) as maxtime  FROM firstp2p_deal_loan_repay where deal_id={$deal_id} AND `status`=2 ";
        $res =  $this->findBySql($sql,array(),true);
        return $res->maxtime;
    }

    /*
     * 获取单个标的生成的利息回款计划的条数
     * @param int $deal_id
     * @param int $type
     * @return int
     */
    public function getCountByDealId($deal_id, $type) {
        $condition = "`deal_id`=':deal_id' AND `type`=':type'";
        $params = array(
            ':deal_id' => $deal_id,
            ':type' => $type,
        );
        return $this->count($condition, $params);
    }

    /**
     * 根据标的id获取回款计划的总和
     * @param int $deal_id
     * @return array
     */
    public function getSumByDealId($deal_id) {
        $sql = "SELECT SUM(`money`) AS `m`, `type`, `deal_repay_id` FROM " . $this->tableName() . " WHERE `deal_id`=':deal_id' GROUP BY `type`, `deal_repay_id`";
        $param = array(
            ':deal_id' => $deal_id,
        );
        $sum = $this->findAllBySql($sql, true, $param);

        $result = array();

        foreach ($sum as $k => $v) {
            if ($v['type'] == self::MONEY_PRINCIPAL) {
                $result[$v['deal_repay_id']]['principal'] += $v['m'];
            } elseif ($v['type'] == self::MONEY_INTREST) {
                $result[$v['deal_repay_id']]['interest'] += $v['m'];
            }
        }

        return $result;
    }

    /**
     * 根据标的和用户 id 获取用户回款金额
     * @param int $deal_id
     * @param int $loan_user_id
     * @return float
     */
    public function getDealTotalRepayMoneyByLoanUserId($deal_id, $loan_user_id)
    {
        $deal_loan_id_str = implode(',', $arr_deal_loan_id);
        $sql = sprintf('SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id` = %d AND `loan_user_id` = %d ', $this->tableName(), $deal_id, $loan_user_id);
        $res = $this->findBySql($sql);
        return floatval($res['sum']);
    }

    /**
     * 获取用户在此标中指定类型和状态的总资金
     * @param int $deal_id
     * @param int $user_id
     * @param int $type 金额类别
     * @param boolean | int $status
     * @param boolean $exclude_reservation 是否排除前台预约投标
     * @return float 总金额
     */
    public function getTotalMoneyOfUserByDealId($deal_id, $user_id, $type, $status = false, $exclude_reservation = false) {
        $sql = sprintf(' SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id`= %d  AND `loan_user_id`= %d  AND `type`= %d ', $this->tableName(), $deal_id, $user_id, $type);
        $sql .= (false === $status) ? '' : sprintf(' AND `status`= %d ', $status);
        if ($exclude_reservation) {
            $deal_load_id_arr = DealLoadModel::instance()->getReserveDealLoadIdsByDealId($deal_id);
            $sql .= empty($deal_load_id_arr) ? '' : sprintf(' AND deal_loan_id NOT IN (%s) ', implode(',', $deal_load_id_arr));
        }
        $res = $this->findBySql($sql);
        return $res['sum'];
    }

    public function getSumMoneyOfUserByDealIdRepayId($dealId, $userId,$repayId,$type){
        $sql = sprintf(' SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id`= %d  AND `loan_user_id`= %d  AND `deal_repay_id`=%d AND `type` = %d', $this->tableName(), $dealId, $userId, $repayId,$type);
        $res = $this->findBySql($sql);
        return $res['sum'];
    }

    /**
     * 获得用户最近已回款的几笔回款
     */
    public function getUserLastRepay($userId, $limit = 10) {
        $userId = intval($userId);
        $limit = intval($limit);
        $condition = "loan_user_id = {$userId} and status <> " . self::STATUS_NOTPAYED;
        $condition .= " ORDER BY `id` DESC ";
        $condition .= " LIMIT {$limit}";

        return $this->findAllViaSlave($condition, true, '*');
    }

    /**
     *获取原始回款计划汇总数据
     * @param $deal_repay_id
     * @return array
     */
    public function getOriginLoanRepayInfos($deal_repay_id) {
        $condition = "`deal_repay_id`= '%d' and  status in (0,1) ORDER BY loan_user_id ASC";
        $condition = sprintf($condition,$deal_repay_id);
        $loan_repay_list = $this->findAll($condition);
        if(empty($loan_repay_list)){
            return array();
        }
        $list = array();
        foreach ($loan_repay_list as $loan_repay) {
            $deal_loan_id = $loan_repay['deal_loan_id'];
            if(empty($list[$deal_loan_id])) {
                $user = UserModel::instance()->find($loan_repay['loan_user_id']);
                if (!empty($user)) {
                    $user['user_type_name'] = getUserTypeName($user['id']);
                    // 获取用户企业名称，若不是企业用户，则企业名称为用户真实名称
                    if (UserModel::USER_TYPE_ENTERPRISE == $user['user_type']) {
                        $user['real_name'] = getUserFieldUrl($user, EnterpriseModel::TABLE_FIELD_COMPANY_NAME);
                    } else {
                        $user['real_name'] = getUserFieldUrl($user, UserModel::TABLE_FIELD_REAL_NAME);
                    }
                }

                $list[$deal_loan_id] = array(
                    'deal_loan_id'=>$deal_loan_id,
                    'loan_user_id'=>$loan_repay['loan_user_id'],
                    'deal_id'=>$loan_repay['deal_id'],
                    'time'=>$loan_repay['time'],
                    'update_time'=>$loan_repay['update_time'],
                    'real_time'=>$loan_repay['real_time'],
                    'borrow_user_id'=>$loan_repay['borrow_user_id'],
                    'deal_type'=>$loan_repay['deal_type'],
                    'user_name'=>getUserFieldUrl($user),
                    'real_name'=>$user['real_name'],
                    'repay_money'=>0,
                    'principal'=>0,
                    'interest'=>0,
                    'status'=> $loan_repay['status'], // DealLoanPartRepay 表的初始状态
                );
            }
            switch ($loan_repay['type']) {
                //本金
                case DealLoanRepayModel::MONEY_PRINCIPAL :
                    $money = $list[$deal_loan_id]['principal'];
                    $money = bcadd($money, $loan_repay['money'], 2);
                    $list[$deal_loan_id]['principal'] = $money;

                    $repay_money = $list[$deal_loan_id]['repay_money'];
                    $repay_money = bcadd($repay_money, $loan_repay['money'], 2);
                    $list[$deal_loan_id]['repay_money'] = $repay_money;
                    break;
                //利息
                case DealLoanRepayModel::MONEY_INTREST :
                    $money = $list[$deal_loan_id]['interest'];
                    $money = bcadd($money, $loan_repay['money'], 2);
                    $list[$deal_loan_id]['interest'] = $money;

                    $repay_money = $list[$deal_loan_id]['repay_money'];
                    $repay_money = bcadd($repay_money, $loan_repay['money'], 2);
                    $list[$deal_loan_id]['repay_money'] = $repay_money;
                    break;
            }
        }

        return $list;
    }
} // END class DealLoanRepay extends BaseModel
