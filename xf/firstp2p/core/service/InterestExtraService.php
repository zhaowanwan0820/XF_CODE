<?php
/**
 * InterestExtraService.php
 * 投资贴息service
 * @date 2015-10-29
 * @author wangzhen <wangzhen@ucfgroup.com>
 */

namespace core\service;
use core\dao\InterestExtraModel;
use core\dao\InterestExtraLogModel;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\JobsModel;
use core\dao\FinanceQueueModel;
use libs\utils\Logger;
use libs\lock\LockFactory;


/**
 * 贴息service
 */
class InterestExtraService {

    /**
     * 收益类型
     */
    const INCOME_TYPE_INTEREST  = 0; //贴息收益
    const INCOME_TYPE_EXCESS    = 1; //超额收益

    /**
     * 贴息类型
     */
    const INTEREST_TYPE_0 = 0; //T+0 贴息
    const INTEREST_TYPE_1 = 1; //T+1 贴息

    /**
     * 标的贴息状态
     */
    const INTEREST_STATUS_N2 = -2;//删除无效
    const INTEREST_STATUS_N1 = -1;//审核拒绝
    const INTEREST_STATUS_0  = 0;//审核中
    const INTEREST_STATUS_1  = 1;//待结算
    const INTEREST_STATUS_2  = 2;//已结算
    const INTEREST_STATUS_3  = 3;//审核通过

    /**
     * 贴息资金模板
     */
    const NOTE_TPL_0 = "%d根据平台贴息活动，对您投资的：%s补息%d天";//平台投资用户贴息资金备注模板:10107863（投资记录ID）根据平台贴息活动，对您投资的：100起投，长兴1号129-11（借款标题）补息2（补息天数）天
    const NOTE_TPL_1 = "平台贴息，%s";//东方联合扣款账号资金备注模板:平台贴息，批次编号

    /**
     * 超额收益资金模板
     */
    const INCOMEEXCESS_NOTE_TPL_0 = "编号%d %s";//编号10107863（投资记录ID） 100起投，长兴1号129-11（借款标题）
    const INCOMEEXCESS_NOTE_TPL_1 = "超额收益 ，%d %s";//用户贴息，超额收益 备注模板:平台贴息，批次编号

    private $max_num; //贴息标数量

    public static $INTEREST_TYPE_MAP = array(
            self::INTEREST_TYPE_0 => 'T+0贴息',
            self::INTEREST_TYPE_1 => 'T+1贴息'
    );

    public static $INTEREST_STATUS_MAP = array(
            self::INTEREST_STATUS_N2 => '删除无效',
            self::INTEREST_STATUS_N1 => '审核拒绝',
            self::INTEREST_STATUS_0 => '审核中',
            self::INTEREST_STATUS_1 => '待结算',
            self::INTEREST_STATUS_2 => '已结算',
            self::INTEREST_STATUS_3 => '审核通过',
    );

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->dealModel = new DealModel();
        $this->interestExtraModel = new InterestExtraModel();
        $this->interestExtraLogModel = new InterestExtraLogModel();
        $this->max_num = intval(app_conf('INTEREST_DEAL_MAX_NUM'));
    }

    /**
     * 贴息进程
     */
    public function interestExtraprocess(){
        //获取要贴息的数据列表
        $interestExtraList = $this->getByCount($this->max_num);
        if(!empty($interestExtraList))
        {
            foreach($interestExtraList as $interestExtra){
                $jobsModel = new JobsModel();
                $function = '\core\service\InterestExtraService::payInterestExtraByDealId';
                $jobsModel->priority = 40;
                $param = array('deal_id'=>$interestExtra['deal_id'],'income_type'=>$interestExtra['income_type']);
                $ret = $jobsModel->addJob($function, $param); //不重试
                if ($ret === false) {
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "添加jobs失败",json_encode($param))));
                    \libs\utils\Alarm::push('interest_extra', 'deal_id:'.$interestExtra['deal_id'].',贴息添加jobs失败',json_encode($param));
                }
            }
        }
    }

    /**
     * 根据标id进行贴息
     * @param int $deal_id
     * @param int $income_type 收益类型
     */
    public function payInterestExtraByDealId($deal_id,$income_type=0)
    {
        if ($income_type == self::INCOME_TYPE_INTEREST) {
            $out_user_id = app_conf('INTEREST_EXTRA_OUT_USER_ID');
        } else {//超额收益，扣除借款人的钱
            //查找借款人信息
            $dealService = new DealService();
            $deal = $dealService->getDeal($deal_id,true,false);
            $out_user_id = $deal['user_id'];
        }
        $userNames = $this->userModel->getUserNamesByIds($out_user_id);

        $dealLoads = $this->interestExtraModel->getDealLoadByDealId($deal_id);
        $interestExtra = $this->interestExtraModel->getByDealId($deal_id,$income_type);
        $dealInfo = $this->dealModel->findViaSlave($deal_id,'name');
        $deal_name = $dealInfo['name'];
        $GLOBALS['db']->startTrans();
        try {
            $lockKey = __CLASS__ . "-" . __FUNCTION__ . "-" . $deal_id;
            $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
            if (!$lock->getLock($lockKey, 1800)) {//加锁30分钟
                throw new \Exception('加锁失败');
            }

            if(empty($interestExtra))
            {
                throw new \Exception('标贴息记录不存在');
            }

            if($interestExtra['status'] != self::INTEREST_STATUS_1)
            {
                throw new \Exception('标贴息状态不正确');
            }

            if(!isset($userNames[$out_user_id]))
            {
                throw new \Exception('贴息付款账号配置错误');
            }

            $pay_date = date("Y-m-d");
            $time = get_gmtime();
            $interest_amount = 0;

            // 剔除贴息天数和利率小于等于0的记录
            if ($interestExtra['rate'] <= 0 || $interestExtra['interest_days'] <= 0) {
                $res = $this->interestExtraModel->update(array('status' => self::INTEREST_STATUS_0, 'update_time' => $time), "deal_id=" . $deal_id . " and status=" . self::INTEREST_STATUS_1);
                throw new \Exception('贴息天数或者利率不能小于等于0,从贴息列表中移除');
            }

            foreach($dealLoads as $key => $dealLoad)
            {
                $data = array(
                        'deal_id' => $dealLoad['deal_id'],
                        'deal_load_id'=>$dealLoad['id'],
                        'user_id'=>$dealLoad['user_id'],
                        'user_name'=>$dealLoad['user_name'],
                        'money' => $dealLoad['money'],
                        'pay_date' => $pay_date,
                        'create_time' => $time,
                        'update_time' => $time,
                        'out_user_id' => $out_user_id,
                        'income_type' => $income_type,
                        'out_user_name' => $userNames[$out_user_id]
                );

                $data['interest'] = $this->set_interest_amount($dealLoad['money'],$interestExtra['rate'],$interestExtra['interest_days']);//计算贴息金额

                $logId = $this->interestExtraLogModel->insert($data);//写入贴息记录表
                if(!$logId)
                {
                    throw new \Exception('数据插入失败');
                }
                unset($dealLoads[$key]);

                $interest_amount = bcadd($interest_amount, $data['interest'], 2);//贴息累计金额

                /**
                 * 贴息付款扣款
                 */
                if ($income_type == self::INCOME_TYPE_INTEREST) {
                    $this->pay($data['interest'],$dealLoad['user_id'],$out_user_id,$logId,sprintf(self::NOTE_TPL_0,$deal_id,$deal_name,$interestExtra['interest_days']),sprintf(self::NOTE_TPL_1,$pay_date),self::INCOME_TYPE_INTEREST,$dealLoad);
                } else {
                    $this->pay($data['interest'],$dealLoad['user_id'],$out_user_id,$logId,
                        sprintf(self::INCOMEEXCESS_NOTE_TPL_0,$deal_id,$deal_name),
                        sprintf(self::INCOMEEXCESS_NOTE_TPL_1,$deal_id,$deal_name),
                        $income_type,$dealLoad
                    );
                }
            }

            //贴息成功，更新贴息状态
            $res=$this->interestExtraModel->update(array('pay_date'=>$pay_date,'status'=>self::INTEREST_STATUS_2 ,'update_time'=>$time ,'interest_amount'=>$interest_amount), "deal_id=".$deal_id ." AND income_type=".$income_type." and status=".self::INTEREST_STATUS_1);
            if(!$res)
            {
                throw new \Exception('数据更新失败');
            }

            $GLOBALS['db']->commit();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"income_type:".$income_type, "deal_id:" .$deal_id,"rate:".$interestExtra['rate'],"interest_days:".$interestExtra['interest_days'],"pay_date:".$pay_date,"interest_amount:".$interest_amount,"贴息成功")));
            $lock->releaseLock($lockKey); // 解锁
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"income_type:".$income_type, "deal_id:" .$deal_id,"rate:".$interestExtra['rate'],"interest_days:".$interestExtra['interest_days'],"pay_date:".$pay_date,"interest_amount:".$interest_amount,"贴息失败", "error:" . $e->getMessage())));
            \libs\utils\Alarm::push('interest_extra', 'income_type:'.$income_type.',deal_id:'.$deal_id.',贴息失败', "income_type:".$income_type.",deal_id:" .$deal_id.",rate:".$interestExtra['rate'].",interest_days:".$interestExtra['interest_days'] .",pay_date:".$pay_date.",interest_amount:".$interest_amount.",贴息失败,error:" . $e->getMessage());
            $lock->releaseLock($lockKey); // 解锁
            return false;
        }
        return true;
    }

    /**
     * 保存标贴息记录信息
     * @param array $deal_ids
     * @param array $status 贴息状态
     * @param int $interest_type 贴息类型
     * @return boolean
     */
    public function saveInterestExtraByDealIds($deal_ids,$status = self::INTEREST_STATUS_N2,$interest_type = self::INTEREST_TYPE_0, $admin_id = 0)
    {
        $interestExtraDeals = $this->getInterestExtraDealsByDealIds($deal_ids,$interest_type);
        if(!empty($interestExtraDeals))
        {
            $GLOBALS['db']->startTrans();
            try {
                foreach ($interestExtraDeals as $interestExtraDeal)
                {
                    $interestExtra = array();
                    $interestExtra['status']           = $status;
                    $interestExtra['admin_id']         = $admin_id;
                    $interestExtra['type']             = $interest_type;
                    $interestExtra['create_time']      = get_gmtime();
                    $interestExtra['rate']             = $interestExtraDeal['rate'];
                    $interestExtra['deal_id']          = $interestExtraDeal['id'];
                    $interestExtra['success_time']     = $interestExtraDeal['success_time'];
                    $interestExtra['repay_start_time'] = $interestExtraDeal['repay_start_time'];
                    $interestExtra['interest_days']    = $this->set_interest_days($interest_type,$interestExtraDeal['success_time'],$interestExtraDeal['repay_start_time']);//贴息天数
                    $res= $this->interestExtraModel->insert($interestExtra);
                    if(!$res)
                    {
                        throw new \Exception('数据插入失败');
                    }
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                return false;
            }
        }
        return true;
    }

    /**
     * 获取符合贴息条件的标数量
     * @param array $param
     * @return integer
     */
    public function getInterestExtraDealsCount($param)
    {
        $param['interest_time'] = $this->set_interest_time($param['interest_type']);
        return $this->interestExtraModel->getInterestExtraDealsCount($param);
    }

    /**
     * 获取符合贴息条件的标列表
     * @param array $param
     * @return array
     */
    public function getInterestExtraDealsList($param)
    {
        $param['interest_time'] = $this->set_interest_time($param['interest_type']);
        return $this->interestExtraModel->getInterestExtraDealsList($param);
    }

    /**
     * 通过标id批量获取要符合条件标
     * @param array $deal_ids
     * @return array
     */
    public function getInterestExtraDealsByDealIds($deal_ids ,$interest_type){
        $interest_time = $this->set_interest_time($interest_type);
        return $this->interestExtraModel->getInterestExtraDealsByDealIds($deal_ids ,$interest_time);
    }

    /**
     * 根据贴息类型设置贴息时间，贴息时间=放款时间-满标时间
     * @param int $interest_type
     * @return number
     */
    private function set_interest_time($interest_type = 0){
        $interest_type = intval($interest_type);
        return intval($interest_type)*86400;
    }

    /**
     * 根据贴息类型设置贴息时间，贴息天数=（放款时间-满标时间）/86400 向上取整
     * @param int $interest_type
     * @return number
     */
    private function set_interest_days($interest_type = 0,$success_time,$repay_start_time){
        return ceil(($repay_start_time - $success_time - (86400 * intval($interest_type) ))/86400);
    }

    /**
     * 计算贴息金额
     * @param float $amount
     * @param float $rate
     * @param int $interest_days
     * @throws \Exception
     * @return float
     */
    private function set_interest_amount($amount,$rate,$interest_days){
        if(bccomp($amount,0,2) <= 0){
            throw new \Exception('投资金额不能小于等于0');
        }
        if($interest_days <= 0)
        {
            throw new \Exception('贴息天数不能小于等于0');
        }
        if(bccomp($rate,0,5) <= 0)
        {
            throw new \Exception('年利率不能小于等于0');
        }

        //贴息的计算方式  = (投资金额（100元）*年化收益（8.8%）* 贴息天数（2天）)/360(天)
        $interest_amount = floatval(floor($amount*$rate*$interest_days/360)/100);

        return $interest_amount;
    }

    /**
     * 对投资人进行贴息，补钱账号进行扣款
     * @param float $money
     * @param int $receiverId
     * @param int $payerId
     * @param int $logId
     * @param string $receiverNote
     * @param string $payerNote
     * @param string $incomeType 收益类型
     * @throws \Exception
     * @return boolean
     */
    private function pay($money,$receiverId,$payerId,$logId,$receiverNote,$payerNote,$incomeType = 0,$dealLoad = array())
    {
        $res = $this->payMoneyByUserId($money,$receiverId,$receiverNote,$incomeType,$dealLoad);
        if(empty($res))
        {
            throw new \Exception('[receiverId:'.$receiverId.',money:'.$money.',incomeType:'.$incomeType.",".$receiverNote.']贴息打款失败');
        }

        $res = $this->chargeMoneyByUserId($money,$payerId,$payerNote,$incomeType,$dealLoad);
        if(empty($res))
        {
            throw new \Exception('[payerId:'.$payerId.',money:'.$money.',incomeType:'.$incomeType.','.$payerNote.']贴息扣款失败');
        }

        if(bccomp($money,0,2) > 0)
        {
            $syncRemoteData[] = array(
                    'outOrderId' => 'INTEREST_EXTRA|' . $logId,
                    'payerId' => $payerId,
                    'receiverId' => $receiverId,
                    'repaymentAmount' => bcmul($money, 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 3,
                    'batchId' => '',
            );
            $res = $this->syncRemote($syncRemoteData);
            if (empty($res)) {
                throw new \Exception("同步资金平台入队列失败");
            }
        }

        return true;

    }

    /**
     * 资金托管平台同步
     * @param array $syncRemoteData
     * @return boolean
     */
    function syncRemote($syncRemoteData){
        if (!empty($syncRemoteData)) {
            $res = FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__ ,json_encode($syncRemoteData),$res?'资金托管平台同步成功':'资金托管平台同步失败')));
            return $res;
        }
        return false;
    }
    /**
     * 通过用户id给用户贴息
     * @param int $money
     * @param int $user_id
     * @param string $incomeType 收益类型
     * @return boolean
     */
    private function payMoneyByUserId($money,$user_id ,$note,$incomeType=0,$dealLoad = array())
    {
        if(bccomp($money,0,2) > 0)
        {
            $user = $this->userModel->find($user_id,'id');
            $user->changeMoneyAsyn = true;

            $log_info = '平台贴息';
            if($incomeType == self::INCOME_TYPE_EXCESS) {
                $log_info = '超额收益';
            }

            $bizToken = [
                'dealId' => $dealLoad['deal_id'],
                'dealLoadId' => $dealLoad['id'],
            ];

            $res = $user->changeMoney($money, $log_info, $note, 0, 0, UserModel::TYPE_MONEY, 0, $bizToken);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__ , 'money:'.$money,'user_id:'.$user_id,'note:'.$note ,$res?$log_info.'打款成功':$log_info.'打款失败')));
            return $res;
        }
        return true;
    }

   /**
     * 通过东方联合账户用户id给用户扣款
     * @param int $money
     * @param int $user_id
     * @param string $incomeType 收益类型
     * @return boolean
    */
    private function chargeMoneyByUserId($money,$user_id,$note,$incomeType=0,$dealLoad=array())
    {
        if(bccomp($money,0,2) > 0)
        {
            $user = $this->userModel->find($user_id,'id');
            $user->changeMoneyAsyn = true;

            $log_info = '平台贴息';
            if($incomeType == self::INCOME_TYPE_EXCESS) {
                $log_info = '超额收益';
            }

            $bizToken = [
                'dealId' => $dealLoad['deal_id'],
                'dealLoadId' => $dealLoad['id'],
            ];

            $res = $user->changeMoney(-$money, $log_info, $note, 0, 0, UserModel::TYPE_MONEY, 0, $bizToken);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__ , 'money:'.$money,'user_id:'.$user_id,'note:'.$note ,$res?$log_info.'扣款成功':$log_info.'扣款失败')));
            return $res;
        }
        return true;
    }

    /**
     * 获取一定数量的贴息标
     * @param int $num
     */
    public function getByCount($num){
        if($num <= 0) return false;
        return $this->interestExtraModel->getData(array('status' => self::INTEREST_STATUS_1) ,"deal_id,income_type" ,'','0,'.$num);
    }

}
