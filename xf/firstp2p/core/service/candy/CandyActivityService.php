<?php

namespace core\service\candy;

use core\service\UserService;
use core\service\AccountService;
use core\service\candy\CandyService;
use core\service\vip\VipService;
use core\service\BwlistService;
use libs\db\Db;
use libs\utils\ABControl;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\VipEnum;

/**
 * 信力
 */
class CandyActivityService
{

    // 信力来源：抽奖
    const SOURCE_TYPE_LOTTERY = 20;
    // 信力来源：红包
    const SOURCE_TYPE_BOUNS = 21;
    // 信力来源：游戏(红包雨)
    const SOURCE_TYPE_GAME = 22;
    // 信力来源：余额产生信力
    const SOURCE_TYPE_BALANCE = 23;
    // 信力来源：分享文章
    const SOURCE_TYPE_SHARE = 24;
    // 信力来源：P2P
    const SOURCE_TYPE_P2P = 51;
    // 信力来源：专享
    const SOURCE_TYPE_ZHUANXIANG = 52;
    // 信力来源：邀请
    const SOURCE_TYPE_INVITE = 54;
    // 信力来源：签到
    const SOURCE_TYPE_CHECKIN = 55;
    // 信力来源：智多新
    const SOURCE_TYPE_DT = 59;

    const ERR_CODE_TOKEN = 100001; // token重复

    // 2018七夕VIP活动配置
    public static $qixiVipConf = [
        'activity' => 5000,
        'startDate' => '20180816',
        'endDate' => '20180820'
    ];

    public static $sourceTypeConf = array(
        self::SOURCE_TYPE_P2P => array(
            'key' => 'P2P',
            'value' => 3000,
            'desc' => '单笔年化金额每万元获<span class="color_red">3000</span>信力<br>若单笔投资金额满5万元（含）以上，信力x1.2倍；满10万元（含）以上，信力x1.5倍；满20万元（含）以上，信力x2倍',
        ),
        self::SOURCE_TYPE_ZHUANXIANG => array(
            'key' => 'ZHUANXIANG',
            'value' => 2000,
            'desc' => '单笔年化金额每万元获<span class="color_red">2000</span>信力<br>若单笔投资金额满5万元（含）以上，信力x1.2倍；满10万元（含）以上，信力x1.5倍；满20万元（含）以上，信力x2倍',
        ),
        self::SOURCE_TYPE_INVITE => array(
            'key' => 'INVITE',
            'value' => '500,3500',
            'desc' => '成功邀请好友首投立获<span class="color_red">500</span>信力，另外根据好友首投额度还可额外获得<span class="color_red">3500</span>信力/万元年化。<br>友情提示:投资智多新30天及以上才能获取信力，投资其他产品无限制（网贷产品仅限网贷-网信普惠）',
        ),
        self::SOURCE_TYPE_CHECKIN => array(
            'key' => 'CHECKIN',
            'value' => 30,
            'desc' => '每日签到1次获<span class="color_red">30</span>信力<br>需投资一次签到才能获得信力（网贷产品仅限网贷-网信普惠）',
        ),
        self::SOURCE_TYPE_DT => array(
            'key' => 'DT',
            'value' => 3000,
            'desc' => '成功加入智多新（30天以上），单笔年化金额每万元获<span class="color_red">3000</span>信力<br>若单笔成功加入金额满5万元（含）以上，信力x1.2倍；满10万元（含）以上，信力x1.5倍；满20万元（含）以上，信力x2倍',
        ),
        self::SOURCE_TYPE_LOTTERY => array(
            'key' => 'LOTTERY',
            'value' => '50,200',
            'desc' => '当日投资一次（网贷产品仅限网贷-网信普惠）即可获得1次信力抽奖机会,最多可获<span class="color_red">500</span>信力<br>友情提示：加入智多新30天及以上且在<span class="color_red">%s</span>-<span class="color_red">%s</span>期间未取消，即可在当日<span class="color_red">%s</span>以后获得一次抽奖机会，投资其他产品无限制',
        ),
        self::SOURCE_TYPE_BOUNS => array(
            'key' => 'BONUS',
            'value' => '',
            'desc' => '需投资一次才能分享能量包获信力（网贷产品仅限网贷-网信普惠）',
        ),
        self::SOURCE_TYPE_BALANCE => array(
            'key' => 'BALANCE',
            'value' => 4000,
            'desc' => '网贷-网信普惠和网信账户中任一账户余额满4000元即可每2小时结算1信力（不含红包）',
        ),
        self::SOURCE_TYPE_SHARE => array(
            'key' => 'SHARE',
            'value' => 50,
            'desc' => '成功参加网信市场营销活动获得的相关信力奖励，具体奖励以平台线上发布的活动规则为准<br>活动1：发现频道转发文章送<span class="color_red">50</span>信力，仅限投资过的用户，每天最多领取50信力',
        ),
        self::SOURCE_TYPE_GAME => array(
            'key' => 'GAME',
            'value' => '',
            'desc' => '',
        ),
    );

    /**
     * 获取用户当天活跃度
     */
    public function getUserActivityToday($userId)
    {
        return $this->getUserActivity($userId, strtotime(date('Ymd')), strtotime(date('Ymd')) + 86400);
    }

    /**
     * 获取用户活跃度信息
     */
    public function getUserActivity($userId, $startTime, $endTime)
    {
        // 信力记录
        $sql = "SELECT source_type, sum(activity) value FROM activity_log WHERE user_id='{$userId}' AND create_time>={$startTime} AND create_time<{$endTime} GROUP BY source_type";
        $rows = Db::getInstance('candy')->getAll($sql);

        $result = array();
        foreach (self::$sourceTypeConf as $item) {
            $result[$item['key']] = 0;
        }

        foreach ($rows as $item) {
            $key = isset(self::$sourceTypeConf[$item['source_type']]['key']) ? self::$sourceTypeConf[$item['source_type']]['key'] : 'OTHER';
            $result[$key] += intval($item['value']);
        }

        return $result;
    }

    const HASH_KEY_ACTIVITY_TOTAL = 'candy_activity_total';

    /**
     * 信力池，从cache读取
     */
    public function getAllActivityTotalTodayFromCache()
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        return $redis->HGET(self::HASH_KEY_ACTIVITY_TOTAL, 'total');
    }

    /**
     * 信力池，写入cache
     */
    public function getAllActivityTotalTodayToCache()
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $total = $this->getAllActivityTotalToday();

        $redis->HSET(self::HASH_KEY_ACTIVITY_TOTAL, 'total', $total);
        $redis->HSET(self::HASH_KEY_ACTIVITY_TOTAL, 'create_time', time());

        return $total;
    }

    /**
     * 获取当天信力池总数 (总信力池要乘一定系数)
     */
    public function getAllActivityTotalToday()
    {
        $total = $this->getAllActivityTotal(strtotime(date('Ymd')), strtotime(date('Ymd')) + 86400);
        return intval($total * max(1, app_conf('CANDY_ACTIVITY_POOL_RATE')));
    }

    /**
     * 获取一段时间的信力总值
     */
    public function getAllActivityTotal($startTime, $endTime)
    {
        $sql = "SELECT sum(activity) FROM activity_log WHERE create_time>='{$startTime}' AND create_time<'{$endTime}'";
        return Db::getInstance('candy')->getOne($sql);
    }

    /**
     * 获取所有活跃度数据
     */
    public function getAllUserActivity($startTime, $endTime)
    {
        // 信力记录
        $sql = "SELECT user_id, sum(activity) value FROM activity_log WHERE create_time>={$startTime} AND create_time<{$endTime} GROUP BY user_id";
        $rows = Db::getInstance('candy')->getAll($sql);

        $result = array();
        foreach ($rows as $item) {
            $result[$item['user_id']] = $item['value'];
        }

        return $result;
    }

    /**
     * 增加算力值
     */
    public function addActivity($token, $userId, $activity, $sourceType, $note)
    {
        $data = array(
            'token' => $token,
            'user_id' => $userId,
            'source_type' => $sourceType,
            'activity' => $activity,
            'note' => $note,
            'create_time' => time(),
        );

        \libs\utils\Monitor::add('CANDY_ACTIVITY_ADD', $activity);
        return Db::getInstance('candy')->insert('activity_log', $data);
    }

    /**
     * 算力抽奖
     */
    public function activityLottery($userId)
    {
        // 投资才能抽奖
        if ((new CandyAccountService())->isLotteryLimited($userId)) {
            throw new \Exception('当日投资即可获得1次抽奖机会，加入智多新30天及以上需等到'.app_conf('DUOTOU_CANCEL_END_TIME').'以后' . '（网贷产品仅限网贷-网信普惠）');
        }

        // 一天一次
        $token = sprintf('lottery-%s-%s', $userId, date('Ymd'));

        $min = intval(app_conf('CANDY_LOTTERY_MIN'));
        $max = intval(app_conf('CANDY_LOTTERY_MAX'));
        $activity = mt_rand($min, $max);

        return $this->activityCreateByToken($token, $userId, $activity, self::SOURCE_TYPE_LOTTERY, '抽奖');
    }

    /**
     * 根据类型增加信力值
     */
    public function activityCreateByType($sourceType, $token, $userId, $sourceValue, $sourceValueExtra = 0)
    {
        // todo 信力相关将要删除，直接进行信宝相关结算，故此处调用信宝结算。临时调用，待o2o系统上线调用信宝服务后删除整个文件
        return CandyService::changeAmountByActivity($sourceType, $token, $userId, $sourceValue, $sourceValueExtra);

        switch ($sourceType) {
            // 余额加信力
            case self::SOURCE_TYPE_BALANCE:
                $value = intval($sourceValue / self::$sourceTypeConf[$sourceType]['value']) * 1;
                break;
            // 投资
            case self::SOURCE_TYPE_P2P:
            case self::SOURCE_TYPE_ZHUANXIANG:
                // $sourceValueExtra为实际投资额
                $ratio = $this->calcActivityRatio($sourceValueExtra);
                // $sourceValue为年化投资额
                $value = intval($ratio * $sourceValue * self::$sourceTypeConf[$sourceType]['value'] / 10000);
                break;
            // 智多新
            case self::SOURCE_TYPE_DT:
                $days = $sourceValueExtra;
                // 小于30天不给信力
                if ($days < 30) {
                    return 0;
                }

                // $sourceValue为实际投资额
                $ratio = $this->calcActivityRatio($sourceValue);
                // $sourceValueExtra为锁定期天数
                $annualizedAmount = $sourceValue * $sourceValueExtra / 360;

                $value = intval($ratio * $annualizedAmount * self::$sourceTypeConf[$sourceType]['value'] / 10000);
                break;
            // 分享文章增加信力
            case self::SOURCE_TYPE_SHARE:
                $value = self::$sourceTypeConf[$sourceType]['value'];
                break;
            default:
                throw new \Exception('不支持的sourceType:'.$sourceType);
        }

        $this->activityCreateByToken($token, $userId, $value, $sourceType, "source value:{$sourceValue}, extra:{$sourceValueExtra}");
        return $value;
    }

    /**
     * 根据投资额计算信力系数
     */
    public function calcActivityRatio($money)
    {
        if ($money < 50000) {
            return 1;
        }

        if ($money < 100000) {
            return 1.2;
        }

        if ($money < 200000) {
            return 1.5;
        }

        return 2;
    }

    /**
     * 从Vip经验值增加信力
     */
    public function activityCreateByVip($token, $userId, $point, $vipSourceType, $sourceAmount)
    {
        switch ($vipSourceType) {
            // 签到
            case VipEnum::VIP_SOURCE_VALUE_CHECKIN:
                if (!(new UserService())->hasLoan($userId)) {
                    Logger::info(__FUNCTION__. ' NO_DEAL_CHECKIN:'. implode(',',func_get_args()));
                    return;
                }
                $sourceType = self::SOURCE_TYPE_CHECKIN;
                $value = self::$sourceTypeConf[$sourceType]['value'];
                break;
            // 邀请好友
            case VipEnum::VIP_SOURCE_VALUE_INVITE:
                if ($this->inInviteBlack($userId)) {
                    return 0;
                }
                $sourceType = self::SOURCE_TYPE_INVITE;
                $conf = self::$sourceTypeConf[$sourceType]['value'];
                $confArray = explode(',', $conf);
                $value = intval($confArray[0]) + intval($sourceAmount * $confArray[1] / 10000);
                break;
            default:
                return;
        }

        $this->activityCreateByToken($token, $userId, $value, $sourceType, "vip source amount:{$sourceAmount}");

        return $value;
    }

    /**
     * 增加算力，使用token做唯一索引
     */
    public function activityCreateByToken($token, $userId, $activity, $sourceType, $note = '')
    {
        $id = Db::getInstance('candy')->getOne("SELECT id FROM activity_log WHERE user_id='{$userId}' AND token='{$token}'");
        if (!empty($id)) {
            throw new \Exception('token已使用', self::ERR_CODE_TOKEN);
        }

        $this->addActivity($token, $userId, $activity, $sourceType, $note);
        Logger::info("activity create success. userId:{$userId}, token:{$token}, source:{$sourceType}, activity:{$activity}");

        return $activity;
    }

    public function getActivityKeyConf() {
        $conf = [];
        foreach (self::$sourceTypeConf as $value) {
            if ($value['key'] == 'LOTTERY') {
                $value['desc'] = sprintf($value['desc'], app_conf('DUOTOU_CANCEL_START_TIME'), app_conf('DUOTOU_CANCEL_END_TIME'), app_conf('DUOTOU_CANCEL_END_TIME'));
            }

            if ($value['key'] == 'INVITE') {
                // 七夕节活动临时增加
                $currentTime = time();
                if ($currentTime >= strtotime(self::$qixiVipConf['startDate']) && $currentTime < strtotime(self::$qixiVipConf['endDate']) + 86400) {
                   $value['desc'] .=  '<br>七夕信力大放送：2018.08.16—2018.08.20，<span class="red">VIP用户</span>邀请够2位好友完成首投，可额外获得<span class="red">5000</span>信力值';
                }

            }
            $conf[$value['key']] = $value;
        }
        return $conf;
    }

    public function inInviteBlack($userId) {
        $blackGroups = app_conf('CANDY_INVITE_BLACK_GROUP');
        if (!empty($blackGroups)) {
            $blackGroups = explode(',', $blackGroups);
            $groupid = (new UserService())->getUser($userId, false, false, true)['group_id'];
            if (in_array($groupid, $blackGroups)) {
                Logger::info("inInviteBlack. userid:{$userId}, groupid:{$groupid}");
                return true;
            }
        }
        return false;
    }

    /**
     * 信力红包开关
     */
    public function inBonus($userId) {
        $bonusOn = app_conf('CANDY_BONUS_ON');
        // WHITE:仅白名单可见 ALL:所有用户可见
        if (empty($bonusOn)) {
            return false;
        }
        // 全量打开
        if ($bonusOn == 'ALL') {
            return true;
        }
        // ABTesting名单
        if (ABControl::getInstance()->hit('candy_bonus')) {
            return true;
        }
        // 通用黑白名单
        $bwlistService = new BwlistService();
        if ($bwlistService->inList('CANDY_BONUS_WHITE', $userId)) {
            return true;
        }
        // Vip用户
        $vipService = new VipService();
        $vipInfo = $vipService->getVipInfo($userId);
        if (!empty($vipInfo) && $vipInfo['service_grade'] > 0) {
            return true;
        }

        // 有在途或余额
        //$accountService = new AccountService();
        //if ($accountService->isUserHasAssets($userId)) {
        //    return true;
        //}
        return false;
    }
}
