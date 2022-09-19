<?php
namespace core\service\mq;

use libs\utils\Rpc;
use libs\utils\Logger;
use libs\utils\Finance;
use core\dao\deal\DealModel;
use core\enum\DealExtEnum;
use core\dao\deal\DealAgencyModel;
use core\service\BaseService;
use core\dao\repay\DealRepayModel;
use core\dao\repay\DealPrepayModel;
use core\enum\DealRepayEnum;
use core\service\msgbus\MsgbusService;
use core\enum\MsgbusEnum;

/**
 * MqService.php
 * 消息队列服务
 */
class MqService extends BaseService {

    const LOAN_FEE          = 'loan_fee';       //平台手续费
    const CONSULT_FEE       = 'consult_fee';    //咨询费
    const GUARANTEE_FEE     = 'guarantee_fee';  //担保费
    const PAY_FEE           = 'pay_fee';        //支付服务费
    const MANAGEMENT_FEE    = 'management_fee'; //管理服务费

    const FORMAT_TIME_TYPE_DEAL         = 1;
    const FORMAT_TIME_TYPE_DEAL_REPAY   = 2;
    const FORMAT_TIME_TYPE_DEAL_PREPAY  = 3;

    /**
     * 业务系统收费配置
     */
    static $feeConfigs = array(
            self::LOAN_FEE => array(
                'type'=>'loan_fee_rate_type',
                'rate'=>'loan_fee_rate',
                'ext'=>'loan_fee_ext',
                ),
            self::CONSULT_FEE => array(
                'type'=>'consult_fee_rate_type',
                'rate'=>'consult_fee_rate',
                'ext'=>'consult_fee_ext',
                ),
            self::GUARANTEE_FEE => array(
                'type'=>'guarantee_fee_rate_type',
                'rate'=>'guarantee_fee_rate',
                'ext'=>'guarantee_fee_ext',
                ),
            self::PAY_FEE => array(
                'type'=>'pay_fee_rate_type',
                'rate'=>'pay_fee_rate',
                'ext'=>'pay_fee_ext',
                ),
            self::MANAGEMENT_FEE => array(
                'type'=>'management_fee_rate_type',
                'rate'=>'management_fee_rate',
                'ext'=>'management_fee_ext',
                )
            );

    /**
     * 放款
     * @param $dealId 标的Id
     * @return bool
     */
    public function loan($param) {
        $dealId = $param['dealId'];
        $deal = $this->_getDeal($dealId);
        //获取还款计划
        $loanList = DealRepayModel::instance()->findAll("deal_id =".$dealId." ORDER BY id ASC");
        $formatLoanList = array();
        foreach ($loanList as $item) {
            $repayInfo =$item->getRow();
            $this->fixTimezoneOffset($repayInfo,self::FORMAT_TIME_TYPE_DEAL_REPAY);
            $formatLoanList[] = $repayInfo ;
        }
        $receiptInfo = $this->_getBeforeFees($deal);
        $params = array(
                'reportType'=>'loan',
                'dealId'=>$deal['id'],
                'dealInfo'=>$deal,
                'loanList'=>$formatLoanList,
                'receiptInfo'=>$receiptInfo,
                );
        return $this->sendMsg($params);
    }

    /**
     * 正常还款
     * @param $repayId 正常还款id
     * @return bool
     */
    public function repay($param) {
        $repayId = $param['repayId'];
        $repayInfo = DealRepayModel::instance()->find($repayId)->getRow();
        $isLastRepay = DealRepayModel::instance()->getNextRepayByRepayId($repayInfo['deal_id'],$repayId);
        $isLast = isset($isLastRepay['id']) ? 0 : 1;//是否最后一次还款
        $this->fixTimezoneOffset($repayInfo,self::FORMAT_TIME_TYPE_DEAL_REPAY);
        $params = array(
                'reportType'=>'repay',
                'isLast'=>$isLast,
                'repayInfo'=>$repayInfo,
                );
        return $this->sendMsg($params);
    }

    /**
     * 提前还款
     * @param $prepayId 提前还款id
     * @return bool
     */
    public function prepay($param) {
        $prepayId = $param['prepayId'];
        $prepayInfo = DealPrepayModel::instance()->find($prepayId)->getRow();

        //因提前还款作废还款信息
        $cancelRepayIds = array();
        $params = array(":deal_id" => $prepayInfo['deal_id'],":status" => DealRepayEnum::STATUS_PREPAID);
        $list = DealRepayModel::instance()->findAll("`deal_id`=':deal_id' AND `status`=':status'", false, "*", $params);
        if ($list) {
            foreach ($list as $v) {
                $cancelRepayIds[] = $v['id'];
            }
        }

        $this->fixTimezoneOffset($prepayInfo,self::FORMAT_TIME_TYPE_DEAL_PREPAY);
        $params = array(
                'reportType'=>'prepay',
                'prepayInfo'=>$prepayInfo,
                'cancelRepayIds'=>$cancelRepayIds,
                );
        return $this->sendMsg($params);
    }

    /**
     * 部分提前还款
     * @param $prepayId 部分提前还款id
     * @return bool
     */
    public function partPrepay($param) {
        $prepayId = $param['prepayId'];
        $prepayInfo = DealPrepayModel::instance()->find($prepayId)->getRow();
        $this->fixTimezoneOffset($prepayInfo,self::FORMAT_TIME_TYPE_DEAL_PREPAY);
        $params = array(
                'reportType'=>'partPrepay',
                'prepayInfo'=>$prepayInfo,
                );
        return $this->sendMsg($params);
    }

    /**
     * 发送消息
     * @param $msg 消息体
     */
    public function sendMsg($params) {
        try{
            MsgbusService::produce(MsgbusEnum::TOPIC_HANDLE_DEAL_TRIGGER_FINANCE,$params);
        }catch(\Exception $e){
            Logger::error(__CLASS__."|".__FUNCTION__."|插入业财消息jobs失败| params:".json_encode($params)." | topic : ".$topic." |errMsg:".$e->getMessage());
            return false;
        }
        Logger::info(__CLASS__."|".__FUNCTION__."|插入业财消息jobs成功| params:".json_encode($params)." | topic : ".$topic);
        return true;
    }

    /**
     * 获取格式化标的信息，返回纯数组格式标的
     * @param $dealId 标的id
     * @return array
     */
    private function _getDeal($dealId) {
        $deal_model = new DealModel();
        $deal = $deal_model->find($dealId)->getRow();
        $deal = $deal_model->handleDealNew($deal,1);
        $loanAgency = DealAgencyModel::instance()->getLoanAgencyByDealId($deal['id']);//平台费收费机构信息
        if(empty($loanAgency)) {
            $loanAgency     = array(
                    'id' => 0,
                    'user_id' => app_conf('LOAN_FEE_USER_ID'),
                    );
        }

        $deal['nc_loan_user_id'] = $loanAgency['user_id'];//平台费收费机构用户id
        $deal['nc_loan_agency_id'] = $loanAgency['id'];//平台费收费机构id
        $deal['loan_fee_discount_rate'] = $deal['discount_rate']/100;//平台费折扣率
        $this->fixTimezoneOffset ($deal,self::FORMAT_TIME_TYPE_DEAL);
        return $deal;
    }

    /**
     * 计算前收费用
     * @param $deal 标的信息
     * @return array
     */
    private function _getBeforeFees($deal) {
        $deal_model = new DealModel();
        $beforeFees = array();
        foreach (self::$feeConfigs as $feeType => $feeConfig) {
            if (empty($deal[$feeConfig['ext']])) {
                // 年化收 还是 固定比例收
                if (in_array($deal[$feeConfig['type']], array(DealExtEnum::FEE_RATE_TYPE_FIXED_BEFORE, DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND, DealExtEnum::FEE_RATE_TYPE_FIXED_PERIOD))) {
                    $loan_fee_rate = $deal[$feeConfig['rate']];
                } else {
                    $loan_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal[$feeConfig['rate']], $deal['repay_time'], false);
                }
                $fee = $deal_model->floorfix($deal['borrow_amount'] * $loan_fee_rate / 100.0);
            } else {
                $feeArr = json_decode($deal[$feeConfig['ext']], true);
                $fee = $feeArr[0];
            }
            $feeInfo = array(
                    'fee_type' => $feeType,//费用类型
                    'fee_rate_type' => $deal[$feeConfig['type']],//费用收取方式
                    'fee_amount' => $fee,//金额
                    );
            $beforeFees[$feeType] = $feeInfo;
        }
        return $beforeFees;
    }

    /**
     * 修正8小时误差，保证输出外包系统的时间都为标准时间
     * @param $data 待格式化数据
     * @param $type 格式化类型
     */
    private function fixTimezoneOffset (&$data,$type){
        switch ($type) {
            case self::FORMAT_TIME_TYPE_DEAL :
                $timeKeys = array('start_time','create_time','update_time','success_time','repay_start_time','last_repay_time','next_repay_time','publish_time','start_loan_time');
                break;
            case self::FORMAT_TIME_TYPE_DEAL_REPAY :
                $timeKeys = array('repay_time','create_time','update_time');
                break;
            case self::FORMAT_TIME_TYPE_DEAL_PREPAY :
                $timeKeys = array('prepay_time');
                break;
            default:
                $timeKeys = array();
                break;
        }
        $offsetSeconds = date('Z');
        foreach ($timeKeys as $timeKey) {
            $data[$timeKey] += $offsetSeconds;
        }
    }

}

