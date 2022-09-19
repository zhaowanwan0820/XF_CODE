<?php
namespace core\service;

/**
 * 用户签到的Service
 * @author longbo
 */
use core\dao\UserCheckinModel;
use core\dao\ApiConfModel;
use libs\utils\Logger;
use core\service\O2OService;
use core\service\vip\VipService;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use NCFGroup\Protos\O2O\Enum\GameEnum;
use core\event\O2ORetryEvent;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Common\Library\Msgbus;

class CheckinService extends BaseService
{
    const CONFIG_KEY = 'user_checkin_config'; //api_conf配置名

    const AWARD_TYPE_DISCOUNT = 1; //投资券奖励
    const AWARD_TYPE_COUPON = 2; //礼券奖励
    const AWARD_TYPE_RULE = 3; //根据规则奖励

    private $config = array();
    private $checkedInfo = array(
            'first_time' => 0,
            'recent_time' => 0,
            'round_data' => [],
            'current_count' => 0,
        );

    private $data = array(
            'roundDay' => 0,
            'roundCount' => 0,
            'roundNodes' => [],
            'bgImg' => '',
            'remainDay' => 0,
            'checkedStatus' => 0,
            'checkedCount' => 0,
            'firstTime' => 0,
            //vip经验值
            'vipPoint' => '',
        );


    /**
     * 初始化签到信息
     */
    private function init($userId)
    {
        $this->userId = intval($userId);
        $condition = "is_effect=1 and name='".self::CONFIG_KEY."'";
        if ($checkinConfObj = ApiConfModel::instance()->findByViaSlave($condition)) {
            $checkinConf = $checkinConfObj->getRow();
        }
        $this->config = json_decode($checkinConf['value'], true);
        if (empty($this->config) || empty($this->userId)) {
            throw new \Exception('数据错误');
        }
        $this->setCheckedInfo();
    }

    private function handle()
    {
        $this->data['checkedStatus'] = 0;
        $this->data['roundCount'] = intval($this->config['roundCount']) ?: 0;
        $this->data['roundDay'] = intval($this->config['roundDay']) ?: 0;
        $this->data['bgImg'] = strval($this->config['bgImg']) ?: '';
        $this->data['checkedCount'] = intval($this->checkedInfo['current_count']) ?: 0;
        $this->data['firstTime'] = intval($this->checkedInfo['first_time']) ?: 0;
        $roundNodes = array();
        foreach($this->config['roundData'] as $k => $v) {
            $roundNodes[] = array('node' => $k, 'prize' => $v['prize']);
        }
        $this->data['roundNodes'] = $roundNodes;
        $this->data['nextAwardDays'] = $this->getNextAwardDays();

        if (self::isChecked($this->checkedInfo['recent_time'])) {
            $this->data['checkedStatus'] = 1;
        }
        if ($this->data['checkedCount'] == $this->config['roundCount']) {
            $this->data['remainDay'] = 0;
        } else {
            $checkedDay = self::getCheckedDay($this->checkedInfo['first_time']);
            $this->data['remainDay'] = intval($this->config['roundDay'] - $checkedDay);
        }
        //显示vip经验值
        $vipService = new VipService();
        $sourceType = VipEnum::VIP_SOURCE_CHECKIN;
        $sourceAmount = 1;//签到一次
        $isShowVip = $vipService->isShowVip($this->userId);
        $this->data['vipPoint'] = $isShowVip ? $vipService->computeVipPoint($sourceType, $sourceAmount) : '';
    }

    /**
     * 签到
     */
    public function checkin($userId)
    {
        $this->init($userId);
        $checkAward = false;
        do {
            if (empty($this->checkedInfo['current_count'])) {
                if (UserCheckinModel::instance()->add($this->userId)) {
                    $checkAward = true;
                }
                break;
            }
            if (self::isChecked($this->checkedInfo['recent_time'])) {
                break;
            }
            if ($this->isFull()) {
                if ($this->nextRound()) {
                    $checkAward = true;
                }
                break;
            }
            $roundData = json_decode($this->checkedInfo['round_data'], true) ?: array();
            array_push($roundData, time());
            $data = array(
                'recent_time' => time(),
                'round_data' => json_encode($roundData, JSON_UNESCAPED_UNICODE),
                'current_count' => array('current_count+1'),
                'sum' => array('sum+1'),
            );
            if (UserCheckinModel::instance()->updateData($this->userId, $data)) {
                $checkAward = true;
                Logger::info('User_Checkin_Log:userid_'.$this->userId.'|data:'.json_encode($data));
            }
        } while (false);

        //增加vip经验值
        try {
            $vipService = new VipService();
            $sourceAmount = 1;
            $sourceType = VipEnum::VIP_SOURCE_CHECKIN;
            $token = $sourceType.'_'.$userId.'_'.self::midnight(time());
            $info = date('Y-m-d',self::midnight(time())).'签到奖励';
            $vipService->updateVipPoint($userId, $sourceAmount, $sourceType, $token, $info);
            Logger::info('User_Checkin_Log:add vip point|userId|'.$userId.'|token|'.$token);
        } catch (\Exception $ex) {
            Logger::info('User_Checkin_Log:add vip point err|userId|'.$userId.'|token|'.$token.'|errMsg|'.$ex->getMessage());
        }

        //世界杯签到赠积分
        if (time() >= strtotime(GameEnum::GUESS_CHECKIN_START_TIME) && time() <= strtotime(GameEnum::GUESS_POINTS_END_TIME)) {
            try {
                $token = GameEnum::GUESS_SOURCE_CHECKIN. $userId.'_'.date('Ymd');
                $note = '签到积分';
                $points = GameEnum::GUESS_CHECKIN_POINTS;
                $sourceType = GameEnum::SOURCE_TYPE_CHECKIN;
                $sourceValue = '';
                Logger::info("GameService.addCheckinScore, userId: ".$userId.',date:'. date('Ymd').',points'.$points);
                $taskObj = new GTaskService();
                $event = new O2ORetryEvent('changeUserGamePoints', array($userId, $token, $points, $sourceType, $sourceValue, $note));
                $taskObj->doBackground($event, 3);
            } catch(\Exception $ex) {
                Logger::info('GameService.addCheckinScore:add worldcup point err|userId|'.$userId.'|token|'.$token.'|errMsg|'.$ex->getMessage());
            }
        }

        $this->setCheckedInfo(false);
        if ($checkAward && $getAwards = $this->getAwards()) {
            $this->data['awards'] = $getAwards;
            $this->recordAwards($getAwards);
        }
        $this->handle();
        // 触发埋点
        $message = [
            'userId' => $userId,
            'createTime' => time(),
        ];
        $message = json_encode($message);
        $result = Msgbus::instance()->produce('checkin', $message);
        Logger::info(implode('|', [__METHOD__, 'to msgbus', $userId, json_encode($result)]));

        return $this->data;
    }

    /**
     * 获取签到信息
     **/
    public function getCheckedInfo($userId)
    {
        $this->init($userId);
        $this->handle();
        if (!$this->data['checkedStatus'] && $this->isFull()) {
            $this->data['checkedCount'] = 0;
            $this->data['remainDay'] = $this->config['roundDay'];
            $this->data['firstTime'] = 0;
        }
        return $this->data;
    }

    private function recordAwards($awards = array())
    {
        $roundData = json_decode($this->checkedInfo['round_data'], true);
        $awards['time'] = array_pop($roundData);
        array_push($roundData, $awards);
        $data = array('round_data' => json_encode($roundData, JSON_UNESCAPED_UNICODE));
        try {
            UserCheckinModel::instance()->updateData($this->userId, $data);
        } catch (\Exception $e) {
            Logger::error('UpdateAwardRecordErr:'.$e->getMessage());
        }
    }

    private function setCheckedInfo($isSlave = true)
    {
        if (!$this->userId) {
            return;
        }
        $cond = 'user_id='.$this->userId;
        $checkedInfoObj = $isSlave ? UserCheckinModel::instance()->findByViaSlave($cond)
            : UserCheckinModel::instance()->findBy($cond);
        if ($checkedInfoObj){
            $this->checkedInfo = $checkedInfoObj->getRow();
        }
    }

    static private function getCheckedDay($firstTime)
    {
        if (empty($firstTime))return 0;
        $hasCheckedTime = self::midnight(time()) - self::midnight($firstTime);
        return intval($hasCheckedTime / (3600*24)) + 1;
    }

    static private function midnight($timestamp)
    {
        return strtotime(date('Y-m-d', $timestamp));
    }

    static private function isChecked($recentTime)
    {
        if (!empty($recentTime) && self::midnight($recentTime) == self::midnight(time())) {
            return true;
        }
        return false;
    }

    /*距下一节点还有几次*/
    private function getNextAwardDays()
    {
        $count = intval($this->data['checkedCount']);
        $daysList = array_keys($this->config['roundData']);
        rsort($daysList, SORT_NUMERIC);
        if ($count >= $daysList[0]) {
            return 0;
        }
        while (list($k, $v) = each($daysList)) {
            if ($count >= $v) {
                return $daysList[$k-1] - $count;
            }
        }
        return array_pop($daysList) - $count;
    }

    /*是否签满*/
    private function isFull()
    {
        return ($this->checkedInfo['current_count'] >= $this->config['roundCount'])
            || (self::getCheckedDay($this->checkedInfo['first_time']) > $this->config['roundDay']);
    }

    /*开启下一轮回*/
    private function nextRound() {
        if (!($past_arr = json_decode($this->checkedInfo['round_data_past'], true))) {
            $past_arr = array();
        }
        if (count($past_arr) > 10) {
            array_pop($past_arr);
        }
        array_unshift($past_arr, json_decode($this->checkedInfo['round_data'], true));
        $data = array(
            'first_time' => time(),
            'recent_time' => time(),
            'round_data' => json_encode([time()]),
            'round_data_past' => json_encode($past_arr, JSON_UNESCAPED_UNICODE),
            'current_count' => '1',
            'sum' => array('sum+1'),
        );
        return UserCheckinModel::instance()->updateData($this->userId, $data);
    }

    private function getAwards() {
        $count = intval($this->checkedInfo['current_count']);
        if (!empty($this->config['roundData'])) {
            foreach ($this->config['roundData'] as $times => $awards) {
                if (intval($count) ==  intval($times)) {
                    return $this->award($awards, $times);
                }
            }
        }
        return false;
    }

    private function award($awards, $times) {
        $token = 'checkin_'.$this->userId.'_'.self::midnight(time());
        $o2oService = new O2OService();
        $awRes = false;
        $returnAwards = array();
        switch (intval($awards['awardType'])) {
            case self::AWARD_TYPE_DISCOUNT:
                $awRes = $o2oService->acquireDiscounts($this->userId, intval($awards['awards']), $token, 0, '用户签到'.$times.'次奖励', true);
                if ($awRes !== false) {
                    Logger::info('GetDiscount:'.json_encode($awRes, JSON_UNESCAPED_UNICODE));
                    $returnAwards['awardName'] = $awRes[$awards['awards']]['name'] ?: '';
                }
                break;
            case self::AWARD_TYPE_COUPON:
                $awRes = $o2oService->acquireCoupons($this->userId, strval($awards['awards']), $token, '', 0, true);
                if ($awRes !== false) {
                    Logger::info('GetCoupon:'.json_encode($awRes, JSON_UNESCAPED_UNICODE));
                    $returnAwards['awardName'] = $awRes[$awards['awards']]['product']['productName'] ?: '';
                }
                break;
            case self::AWARD_TYPE_RULE:
                $awRes = $o2oService->acquireRuleDiscount($this->userId, $awards['awards'], $token, 0, 0);
                if ($awRes !== false) {
                    Logger::info('GetRule:'.json_encode($awRes, JSON_UNESCAPED_UNICODE));
                    $returnAwards['awardName'] = $awRes['name'] ?: '';
                }
                break;
        }
        if ($awRes == false) {
            Logger::error("AwardFailed. code:".$o2oService->getErrorCode().', msg:'.$o2oService->getErrorMsg());
            return false;
        }
        $returnAwards['awardRemark'] = $awards['remark'];
        $returnAwards['prize'] = $awards['prize'];
        $returnAwards['id'] = $awards['awards'];
        $returnAwards['type'] = $awards['awardType'];
        Logger::info($token.',CheckinTimes:'.$times.',GetAwards:'.json_encode($awards, JSON_UNESCAPED_UNICODE));
        return $returnAwards;
    }

}

