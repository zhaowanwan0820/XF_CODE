<?php
/**
 * BonusService.php
 * @date 2014-10-28
 * @author wangshijie@ucfgroup.com
 */

namespace core\service;

use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\dao\BonusActivityModel;
use core\dao\BonusBindModel;
use core\dao\BonusBuyOrderModel;
use core\dao\BonusConfModel;
use core\dao\BonusConsumeModel;
use core\dao\BonusDispatchConfigModel;
use core\dao\BonusGroupModel;
use core\dao\BonusModel;
use core\dao\BonusSuperModel;
use core\dao\BonusTempleteModel;
use core\dao\BonusUsedModel;
use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\OtoAcquireLogModel;
use core\dao\OtoBonusAccountModel;
use core\dao\UserModel;
use core\event\TestExampleEvent;
use core\service\BonusJobsService;
use core\service\CouponService;
use core\service\TransferService;
use core\service\UserTagService;
use core\service\WeixinInfoService;
use libs\lock\LockFactory;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\event\Bonus\AcquireBonusEvent;
use core\event\Bonus\SyncGroupStatusEvent;
use core\event\Bonus\BindBonusEvent;
use core\service\bonus\BonusUser;
use core\service\bonus\RpcService;
use core\event\Bonus\AcquireBonusGroupEvent;

/**
 * Class BonusService
 * @package core\service
 */
class BonusService extends BaseService {

    private $config = array();
    private $key = 'flIflSinLFIDADFsfasdfl';
    public $batch_id = 0;//批量发送规则id

    //红包来源
    public $source = array(
        '1' => '平台奖励',
        '2' => '活动奖励'
    );

    /**
     * 拜年红包，来源手机号加密KEY
     */
    const HONGBAO_AES_KEY = "aGpocyYqNzMqKEAqI0BRKQ==";

    // 获取指定红包组下红包的列表
    const SCOPE_ALL = 1; //全部
    const SCOPE_BIND = 2; // 获取已绑定了的
    const SCOPE_UNBIND = 3; // 获取已绑定了的
    const SCOPE_USED = 4; // 获取已使用的

    /**
     * 红包离散深度
     */
    private $rand_deep = 10;

    /**
     * 产生红包的最小金额
     */
    private $subsidy_least_value = 0.5;

    /**
     * 补贴系数
     */
    private $subsidy_ratio = 1;

    /**
     *红包类型
     */
    const TYPE_DEAL             = 0; //投资产生的正常的红包
    const TYPE_BATCH            = 1; //批量发送的红包
    const TYPE_ACTIVITY         = 2; // 红包组类型为活动发出的
    const TYPE_XQL              = 3; //超级红包
    const TYPE_NEW_USER_DEAL    = 4; //红包组类型为新手标红包
    const TYPE_FIRST_DEAL_FOR_DEAL   = 5;
    const TYPE_FIRST_DEAL_FOR_INVITE = 6;
    const TYPE_REGISTER_FOR_NEW   = 7;
    const TYPE_REGISTER_FOR_INVITE   = 8;
    const TYPE_BINDCARD_FOR_NEW = 9;
    const TYPE_BINDCARD_FOR_INVITE = 10;
    const TYPE_CASH_FOR_NEW = 11;
    const TYPE_CASH_FOR_INVITE = 12;
    const TYPE_CASH_NORMAL_FOR_NEW = 13;
    const TYPE_O2O_CONFIRM = 14;
    const TYPE_O2O_ACQUIRE_FOR_INVITER = 15;
    const TYPE_O2O_ACQUIRE_FOR_USER = 16;
    const TYPE_LCS_BUY_RANDOM = 17; // 理财师随机红包
    const TYPE_LCS_BUY_AVERAGE = 18; // 理财师等额红包
    const TYPE_LCS_BUY_RANDOM_CHECK = 19; // 理财师随机红包（检查所属关系）
    const TYPE_LCS_BUY_AVERAGE_CHECK = 20; // 理财师等额红包（检查所属关系）
    const TYPE_LCS_BUY_BIRTHDAY = 21; // 理财师购买生日红包

    public $loan_money = 0; //生成红包组的投资金额

    //
    //cache key
    const CACHE_PREFIX_BONUS_NEW_USER_REBATE = 'BONUS_NEW_USER_REBATE_';
    const CACHE_PREFIX_BONUS_GROUP           = 'BONUS_SERVICE_GROUP_';
    const CACHE_PREFIX__BONUS_TEMPLETE       = 'BONUS_SERVICE_TEMPLETE_';

    // 同步红包组状态
    const STATUS_GRABING = 11;
    const STATUS_GRABED = 12;
    const STATUS_EXPIRED = 13;

    // O2O红包组类型
    public static $o2oBonusGroupType = [
        self::TYPE_O2O_CONFIRM,
        self::TYPE_O2O_ACQUIRE_FOR_INVITER,
        self::TYPE_O2O_ACQUIRE_FOR_USER
    ];

    public function __construct($batch_id = 0) {
        $this->batch_id = $batch_id;
        $this->config = $this->get_config();
    }

    public function get_config($key = '') {
        $data = array('rate' => 0.002, 'size' => 10, 'min_count' => 10, 'max_size' => 50, 'times' => 5, 'get_limit_days' => 1, 'use_limit_days' => 1, 'max_total_money' => 2000, 'min_total_money' => 8);
        if (!empty($key) && isset($data[$key])) {
            return $data[$key];
        }
        return $data;
    }

    /**
     * 生成红包
     * @param int $uid 投资用户uid
     * @param int $deal_load_id 投资id
     * @param float $deal_load_money 投资金额
     * @param float $yield 年化比率
     */
    public function generation($uid, $deal_load_id, $deal_load_money, $yield = 0.25, $deal_id = '', $bonus_type_id = '', $total_money = 0, $bonus_count = 0, $send_limit_days = 0) {
        \libs\utils\Monitor::add('BONUS_GENERATION');

        if ($deal_load_id) {
            $result = BonusGroupModel::instance()->findBy('deal_load_id=:deal_load_id', 'id', array(':deal_load_id' => $deal_load_id));
            if (!empty($result['id'])) {
                return $this->encrypt($result['id'], 'E');
            }
        }
        if ($deal_load_money < 5000 && $deal_load_id != 0 && $bonus_type_id != 3) {
            if (!$this->generation_bonus_limit($uid, $deal_load_money)) {
                return false;
            }
        }
        extract($this->get_config()); //获取红包配置信息
        if ($total_money > 0) { //兼容脚本
            $min_total_money = $total_money;
        }
        //-------以下这段代码是为了分站支持O2O年化千五的活动而写的。分分钟会被删除。删除注意添加commit
        if (\libs\utils\Site::getId() != 1 && time() >= strtotime('2015-12-14')) {
            $dealTagService = new \core\service\DealTagService();
            $deal_tags = $dealTagService->getTagByDealId($deal_id);
            if ($deal_load_money * $yield >= 10000 && in_array('O2O_90DAYS', $deal_tags)) {
                $rate = 0.005;
            }
        }
        //-------待删除代码END
        if ($bonus_count > 0) { //兼容脚本
            $min_count = $bonus_count;
        }
        if ($bonus_type_id == self::TYPE_ACTIVITY) {
            $money = 0;
            $count = $bonus_count;
            $bonuses = array();
        } else {
            extract($this->bonus_mechanism($rate, $size, $max_size, $times, $deal_load_money, $min_count, $max_total_money, $min_total_money, $yield, $bonus_type_id));
            if ($money <= 0) {
                return false;
            }
        }
        $created_at = time();
        if ($send_limit_days > 0) {//定制发送限制
            $get_limit_days = $send_limit_days;
        }
        $expired_at = strtotime("+{$get_limit_days} days");//, mktime(0, 0, 0));//精确时间
        try {
            $GLOBALS['db']->startTrans();
            $data = array('user_id' => $uid, 'deal_load_id' => $deal_load_id, 'money' => ($bonus_type_id == 2 ? 0 : $money), 'deal_load_money' => $deal_load_money, 'count' => $count, 'deal_id' => $deal_id, 'bonus_type_id' => intval($bonus_type_id));
            $data['created_at'] = $created_at;
            $data['expired_at'] = $expired_at;
            $data['batch_id'] = intval($this->batch_id);
            $group_id = BonusGroupModel::instance()->add_record($data);
            if ($bonus_type_id == 0 || $bonus_type_id == 3) {
                $result = BonusModel::instance()->insert_batch($uid, $group_id, $bonuses);
            }
            $GLOBALS['db']->commit();
        } catch(\Exception $e) {
            Logger::wLog(__FUNCTION__.print_r($e, true));
            $GLOBALS['db']->rollback();
            return false;
        }
        if ($bonus_type_id == self::TYPE_ACTIVITY) {
            return $group_id;
        } else {
            return $this->encrypt($group_id, 'E');
        }
    }

    /**
     * 理财师买红包
     * @param  integer $uid
     * @param  float $totalPrice 红包总金额
     * @param  integer $count 红包个数
     */
    public function generationLCS($uid, $totalPrice, $count, $orderID, $isRandom = false, $receiveMode = 0, $showLCS = 0)
    {
        \libs\utils\Monitor::add('BONUS_GENERATION_LCS');

        extract($this->get_config()); //获取红包配置信息
        $get_limit_days = intval(BonusConfModel::get('BUY_BONUS_GROUP_LIMIT_DAYS')) ?: 30 * 6;
        $created_at = time();
        $expired_at = strtotime("+{$get_limit_days} days");//, mktime(0, 0, 0));//精确时间

        if ($isRandom) {
            list($_, $_, $bonuses) = array_values($this->bonus_mechanism(0, 0, 0, $times, 0, $count, 0, $totalPrice, 0, self::TYPE_LCS_BUY_RANDOM));
            if ($receiveMode == 0) {
                $bonusTypeId = self::TYPE_LCS_BUY_RANDOM;
            } elseif ($receiveMode == 1) {
                $bonusTypeId = self::TYPE_LCS_BUY_RANDOM_CHECK;
            }
            if ($showLCS) {
                $bonusType = BonusModel::BONUS_LCS_RANDOM_LCS;
            } else {
                $bonusType = BonusModel::BONUS_LCS_RANDOM;
            }

        } else {
            if ($receiveMode == 0) {
                $bonusTypeId = self::TYPE_LCS_BUY_AVERAGE;
            } elseif ($receiveMode == 1) {
                $bonusTypeId = self::TYPE_LCS_BUY_AVERAGE_CHECK;
            }
            $bonuses = array_fill(0, $count, bcdiv($totalPrice, $count, 2));
            if ($showLCS) {
                $bonusType = BonusModel::BONUS_LCS_AVERAGE_LCS;
            } else {
                $bonusType = BonusModel::BONUS_LCS_AVERAGE;
            }
        }

        if ($res = BonusBuyOrderModel::instance()->findBy('order_id = ":orderID"', 'group_id', [':orderID' => $orderID])) {
            return $this->encrypt($res['group_id'], 'E');
        }

        try {

            $GLOBALS['db']->startTrans();
            $data = [
                'user_id' => $uid,
                'money' => $totalPrice,
                'count' => $count,
                'bonus_type_id' => $bonusTypeId,
            ];
            $data['created_at'] = $created_at;
            $data['expired_at'] = $expired_at;
            $data['batch_id'] = intval($this->batch_id);
            $group_id = BonusGroupModel::instance()->add_record($data);
            BonusBuyOrderModel::instance()->newOrder($orderID, $group_id);
            $result = BonusModel::instance()->insert_batch($uid, $group_id, $bonuses, $bonusType);
            $GLOBALS['db']->commit();

        } catch(\Exception $e) {
            Logger::wLog(implode('|', [__CLASS__, __METHOD__, $e->getMessage(), json_encode(func_get_args())]));
            $GLOBALS['db']->rollback();
            return false;
        }
        return $this->encrypt($group_id, 'E');
    }

    /**
     * 理财师买直推红包
     * @param  string  $orderID 订单ID
     * @param  int  $senderID   购买人ID
     * @param  array  $ownerIDs   领取人ID
     * @param  float  $totalMoney 总钱数
     * @param  int  $expireDay  单个红包过期天数
     * @param  int  $type       单个红包类型
     * @param  boolean $isSend     首付发短信
     */
    public function generationLCSDirectPush($orderID, $senderID, $ownerIDs, $totalMoney, $expireDay, $type, $isSend = TRUE)
    {
        $params = func_get_args();
        foreach ($params as $val) {
            if (is_null($val)) return [false, "params error"];
        }
        \libs\utils\Monitor::add('BONUS_GENERATION_LCS_DP');

        extract($this->get_config()); //获取红包配置信息
        $get_limit_days = 1; // 直接发送，此处有效期固定
        $created_at = time();
        $expired_at = strtotime("+{$get_limit_days} days");//, mktime(0, 0, 0));//精确时间

        switch ($type) {
            case BonusModel::BONUS_BIRTHDAY_LCS:
            case BonusModel::BONUS_BIRTHDAY:
                $groupType = self::TYPE_LCS_BUY_BIRTHDAY;
                break;

            default:
                return [false, "type error"];
        }

        if ($res = BonusBuyOrderModel::instance()->findBy('order_id = ":orderID"', 'group_id', [':orderID' => $orderID])) {
            return [true, $this->encrypt($res['group_id'], 'E')];
        }

        $count = count($ownerIDs);
        if ($count == 0) return [false, "count is 0"];

        if ($totalMoney <= 0) return [flase, "moeny lt 0"];
        $money = bcdiv($totalMoney, $count, 2);

        try {

            $GLOBALS['db']->startTrans();
            $data = [
                'user_id' => $senderID,
                'money' => $totalMoney,
                'count' => $count,
                'bonus_type_id' => $groupType,
            ];
            $data['created_at'] = $created_at;
            $data['expired_at'] = $expired_at;
            $data['batch_id'] = intval($this->batch_id);
            // 插入红包组
            $group_id = BonusGroupModel::instance()->add_record($data);
            // 插入订单
            BonusBuyOrderModel::instance()->newOrder($orderID, $group_id);
            // 发红包
            $info = [];
            $mobiles = UserModel::instance()->getMobileByIds(implode(', ', $ownerIDs));
            if (count($mobiles) != $count) {
                throw new \Exception('some uid cannot find mobile');
            }
            $createdAt = time();
            $expiredAt = $createdAt + 86400 * $expireDay;
            $bonusIDs = [];
            foreach ($mobiles as $item) {
                $bonusIDs[] = BonusModel::instance()->single_bonus($group_id, $senderID, $item['id'], $item['mobile'], 1, $money, $createdAt, $expiredAt, '', '', $type, 0, 0, false);
            }
            $GLOBALS['db']->commit();

            // 全部成功后异步创建
            foreach ($bonusIDs as $bid) {
                if (!$bid) continue;
                $taskId = (new GTaskService())->doBackground((new AcquireBonusEvent($bid)), 20);
                Logger::info("BonusDataToNewService:BonusService::generationLCSDirectPush:bonusId=$bid:taskId=$taskId");
            }

            // 发短信
            if ($isSend) {
                require_once(APP_ROOT_PATH . 'system/libs/msgcenter.php');
                $msgCenter = new \Msgcenter();
                foreach ($info as $item) {
                    if (empty($item['mobile'])) continue;
                    $msgCenter->setMsg($item['mobile'], $item['ownerID'], ['expiredDay' => $item['expireDay']], 'TPL_BONUS_LCS_BIRTHDAY');
                }
                $res = $msgCenter->save();
            }

        } catch(\Exception $e) {
            Logger::wLog(implode('|', [__CLASS__, __METHOD__, $e->getMessage(), json_encode($params)]));
            $GLOBALS['db']->rollback();
            return [false, $e->getMessage()];
        }
        return [true, $this->encrypt($group_id, 'E')];
    }


    /**
     * 按照红包组信息生成红包详情信息
     */
    public function generation_bonus_item($uid, $group_id, $bonus_type_id = 0, $min_count = 8, $min_total_money = 10, $deal_load_money = 0, $yield = 0.25) {
        if ($bonus_type_id == 0) {
            return true;
        }
        if ($bonus_type_id != self::TYPE_DEAL && $bonus_type_id != self::TYPE_ACTIVITY && $bonus_type_id != self::TYPE_XQL) {
            $group_id = intval($group_id);
            // 悲观锁，以group_id为锁的键名，防止重复生成
            $lockKey = "BonusService-gen-item".$group_id;
            $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
            if (!$lock->getLock($lockKey, 120)) {
                return false;
            }
            $bonus_count = BonusModel::instance()->count('group_id=:group_id', array(':group_id' => $group_id));
            if ($bonus_count <= 0) {
                extract($this->get_config(), EXTR_SKIP);
                extract($this->bonus_mechanism($rate, $size, $max_size, $times, $deal_load_money, $min_count, $max_total_money, $min_total_money, $yield, $bonus_type_id));
                $result = BonusModel::instance()->insert_batch($uid, $group_id, $bonuses);
                if ($result == false) {
                    $lock->releaseLock($lockKey);//解锁
                    return $result;
                }
            }
            $lock->releaseLock($lockKey);//解锁
            return $this->encrypt($group_id, 'E');
        }
        return true;
    }

    /**
     * 判断红包产生的条件
     */
    private function generation_bonus_limit($uid, $deal_load_money, $min = 500, $max = 5000) {
        return true;
        if ($deal_load_money < $min) {
            return false;
        }
        if ($deal_load_money >= $max) {
            return true;
        }
        $limit_count = $money_start = $money_end = 0;
        $limits = array('500,1000,1', '1000,5000,2');
        foreach ($limits as $item) {
            list($start, $end, $times) = explode(',', $item);
            if ($deal_load_money >= $start && $deal_load_money < $end) {
                $money_start = $start;
                $money_end = $end;
                $limit_count = $times;
                break;
            }
        }
        $condition = "user_id=:user_id AND deal_load_money >= :money_start AND deal_load_money < :money_end AND created_at BETWEEN :today_start AND :today_end";
        $params = array(':user_id' => $uid, ':money_start' => $money_start, ':money_end' => $money_end, ':today_start' => mktime(0, 0, 0), ':today_end' => mktime(23, 59, 59));
        $today_count = BonusGroupModel::instance()->count($condition, $params);
        if ($today_count < $limit_count) {
            return true;
        }
        return false;
    }

    /**
     * 抢红用户是否已经抢过红包了
     * @param string $sn 红包组加密序号
     * @param string $mobile 手机号
     * @return array $result 返回抢红包的状态
     */
    public function collection($sn, $mobile, $openid = '', $referMobile = '', $replaceBonus = false) {
        \libs\utils\Monitor::add('BONUS_COLLECTION');
        $group_id = $this->encrypt($sn, 'D');
        if (!$group_id) {
            return false;
        }

        // 悲观锁，以group_id与手机号为锁的键名
        $lockKey = "BonusService-" . "{$group_id}-{$mobile}";
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 3600)) {
            return false;
        }
        $condition = '`group_id`=:group_id AND `mobile`=":mobile"';
        $params = array(':group_id' => $group_id, ':mobile' => $mobile);
        $result = BonusModel::instance()->findBy($condition, 'id, money, created_at, expired_at', $params);
        if (!empty($result['id'])) {
            $lock->releaseLock($lockKey);//解锁
            return array(
                'status' => 2, 'money' => $result['money'], 'sn' => $this->encrypt($result['id'], 'E'),
                'created_at' => $result['created_at'], 'expired_at' => $result['expired_at'], 'id' => $result['id'],
            );
        }
        $created_at = time();
        $owner = UserModel::instance()->findByViaSlave('mobile=":mobile"', 'id', array(':mobile' => $mobile));

        //红包有效期天数
        //$group_info = BonusGroupModel::instance()->find($group_id);
        $group_info = $this->getGroupByIdUseCache($group_id);
        if ($group_info['bonus_type_id'] == self::TYPE_ACTIVITY && !empty($owner)) {
            $active = $this->getActivityByGroupId($group_id);
            if ($active['is_diff_new_old_user'] == 1) {
                $lock->releaseLock($lockKey);//解锁
                return array('status' => 3);//老用户不能领用
            }
        }

        // 迎春红包
        $hnySn = app_conf('BONUS_HAPPY_NEW_YEAR');
        if ($group_id == $this->encrypt($hnySn)) {
            $cardGroupIds = BonusConfModel::get('CARD_BONUS_GROUP_IDS');
            $conditionCard = '`group_id` in (' .$cardGroupIds. ') AND `mobile`= "' .$mobile. '"';
            $result = BonusModel::instance()->findBy($conditionCard, 'id, money, created_at, expired_at');
            if (!empty($result['id'])) {
                $lock->releaseLock($lockKey);//解锁
                return array('status' => 4);//二维码用户不能领
            }
        }

        if($group_info['batch_id'] > 0){
            if ($group_info['bonus_type_id'] == self::TYPE_BATCH) {
                $bonus_jobs_obj = new BonusJobsService();
                $bonus_jobs_info = $bonus_jobs_obj->getJobById($group_info['batch_id']);
                $use_limit_days = intval($bonus_jobs_info['bonus_validity']);
            } else {
                $bonus_new_user_rebate = BonusDispatchConfigModel::instance()->find($group_info['batch_id'], 'use_limit_day', true);
                $use_limit_days = intval($bonus_new_user_rebate['use_limit_day']);
                if ($use_limit_days <=0) {
                    $use_limit_days = 1;
                }
            }
        }else{
            if ($group_info['bonus_type_id'] == 3) {
                $use_limit_days = app_conf('BONUS_XQL_GET_LIMIT_DAYS');
                $use_limit_days = \SiteApp::init()->cache->get('bonus_xql_use_limit_day_'.$group_id);
                if ($use_limit_days <= 0) {
                    $use_limit_days = $this->get_config('get_limit_days');
                }
            } else {
                $use_limit_days = $this->get_config('use_limit_days');
            }
        }

        if (in_array($group_info['bonus_type_id'], [self::TYPE_LCS_BUY_RANDOM, self::TYPE_LCS_BUY_AVERAGE,
            self::TYPE_LCS_BUY_AVERAGE_CHECK, self::TYPE_LCS_BUY_RANDOM_CHECK]))
        {
            // $use_limit_days = 30;
            $use_limit_days = intval(BonusConfModel::get('BUY_BONUS_LIMIT_DAYS')) ?: 30;
        }

        $expired_at = strtotime("+{$use_limit_days} days");//, mktime(0, 0, 0));精确时间

        // TODO 这里mark下，o2o的红包组task_id实际为券组id，为了数据分析做的兼容, 这里取task信息没有意义
        if ($group_info['task_id'] > 0 && !in_array($group_info['bonus_type_id'], self::$o2oBonusGroupType)) {
            $task_info = $this->getTaskById($group_info['task_id'], $group_info['created_at']);
            if (isset($task_info['use_limit_day']) && $task_info['use_limit_day'] > 0) {
                $use_limit_days = $task_info['use_limit_day'];
                $expired_at = $created_at + $task_info['use_limit_day'] * 86400;
            }
        }

        if ($group_info['bonus_type_id'] == 2) {
            list($use_limit_days, $money) = $this->get_event_rand_money_and_days($group_id);
            $expired_at = strtotime("+{$use_limit_days} days");//, mktime(0, 0, 0));精确时间
            if (!$money) {
                $lock->releaseLock($lockKey);//解锁
                return false;
            }

            // 红包互斥检验
            if (empty($active)) $active = $this->getActivityByGroupId($group_id);
            $rBonus = $this->checkMutex($mobile, $active['name']);
            if ($rBonus !== true && !$replaceBonus) {
                $lock->releaseLock($lockKey);//解锁
                return array('status' => 5, 'bonus' => $rBonus);//互斥不能领取
            }

            $replaceBonusID = 0;
            if ($replaceBonus && $rBonus['id'] > 0) {
                $replaceBonusID = $rBonus['id'];
            }
            $result = BonusModel::instance()->single_bonus($group_id, $group_info['user_id'], intval($owner['id']), $mobile, 1, $money, $created_at, $expired_at, $openid, $referMobile, BonusModel::BONUS_NORMAL, $replaceBonusID);
        } else {
            $result = BonusModel::instance()->collection($group_id, $mobile, (isset($owner['id']) ? intval($owner['id']) : 0), $created_at, $expired_at, $openid, $referMobile);

            if ($result) {
                BonusGroupModel::instance()->updateCount($group_id, 'get');
            }
        }
        $lock->releaseLock($lockKey);//解锁
        if ($result) {
            $result = BonusModel::instance()->findBy($condition, 'id, owner_uid, money, created_at, expired_at', array(':group_id' => $group_id, ':mobile' => $mobile));
            if (empty($result)) {
                return false;
            }
            if ($group_info['bonus_type_id'] != 2 && $result['owner_uid'] > 0) {
                $taskId = (new GTaskService())->doBackground((new AcquireBonusEvent($result['id'])), 20);
                Logger::info("BonusDataToNewService:BonusService::collection:bonusId={$result['id']}:taskId=$taskId");
            }
            if (RpcService::getGroupSwitch(RpcService::GROUP_SWITCH_WRITE)) {
                $taskId = (new GTaskService())->doBackground(new SyncGroupStatusEvent($sn, self::STATUS_GRABED));
                Logger::info(implode('|', [__METHOD__, 'sync status grabed', $group_id, $taskId]));
            }

            return array(
                'status' => 1, 'money' => $result['money'], 'sn' => $this->encrypt($result['id'], 'E'),
                'created_at' => $result['created_at'], 'expired_at' => $result['expired_at'], 'id' => $result['id'],
            );
        }
        return false;
    }

    /**
     * 消费红包
     * @param array $ids 红包id数组
     * @param int $deal_load_id 投标ID
     * @param int $deal_id 标ID
     * @return boolean $result 使用红包结果
     */
    public function consume($ids, $deal_load_id, $deal_id, $user_id = '', $consume_id = 0) {
        \libs\utils\Monitor::add('BONUS_CONSUME', count($ids));

        $time = time();
        try {
            $GLOBALS['db']->startTrans();
            $result = BonusModel::instance()->update_record($ids);
            if (!$result || $result != count($ids)) {
                throw new \Exception('更新状态失败', '10001');
            }
            $result = BonusUsedModel::instance()->insert_batch($ids, $deal_load_id, $deal_id, $time, $consume_id);
            if (!$result) {
                throw new \Exception('插入记录失败', '10002');
            }
            $collection = '`id` IN (' . implode(',', $ids)  . ')';
            $bonusInfo = BonusModel::instance()->findAll($collection, true);
            $groupIDs = [];
            foreach ($bonusInfo as $item) {
                if ($item['group_id'] > 0) {
                    $groupIDs[] = $item['group_id'];
                }
            }
            $groupIDs = array_unique($groupIDs);
            if ($groupIDs) {
                $result = BonusGroupModel::instance()->updateCount($groupIDs, 'used');
                if (!$result) {
                    throw new \Exception("更新红包组失败", '10003');
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            Logger::wLog(__FUNCTION__.print_r($e, true));
            $GLOBALS['db']->rollback();
            return false;
        }
        return $result;
    }

    /**
     * 用户注册的时候绑定用户的id
     */
    public function bind($user_id, $mobile) {
        if (empty($user_id) || empty($mobile)) {
            return false;
        }
        //$result = BonusModel::instance()->updateAll(array('owner_uid' => $user_id), " `mobile`='$mobile' && `owner_uid`=0 && status = 1 && `expired_at` > " . time(), true);
        $result = BonusModel::instance()->findAllViaSlave(" `mobile`='$mobile' && `owner_uid`=0 && status = 1 && `expired_at` > " . time(), true, 'id');
        $successCount = 0;
        foreach ($result as $row) {
            $updateRes = BonusModel::instance()->updateAll(array('owner_uid' => $user_id), " `id`='{$row['id']}' ", true);
            if ($updateRes) {
                $successCount++;
                $taskId = (new GTaskService())->doBackground((new AcquireBonusEvent($row['id'])), 20);
                Logger::info("BonusDataToNewService:BonusService::bind:dealLoadId={$row['id']}:taskId=$taskId");
            }
        }
        $bindCnt = (new WXBonusService)->findBonusForUnregist($mobile);
        if ($bindCnt) {
            $taskId = (new GTaskService())->doBackground((new BindBonusEvent($user_id, $mobile)), 20);
            Logger::info("BonusDataToNewService:BonusService::bind:mobile={$mobile}:taskId=$taskId");
        }
        $successCount += $bindCnt;
        if ($successCount > 0) {
            $user_tag_service = new UserTagService();
            $user_tag_service->addUserTagsByConstName($user_id, 'BONUS_NEW_USER_REGISTER');
        }
        try {
            $event = new \core\event\DiscountBindEvent($user_id, $mobile);
            $task_obj = new GTaskService();
            $task_id = $task_obj->doBackground($event, 20);
            if (!$task_id) {
                Logger::wLog('同步marketing数据失败|' .$user_id. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
            }
        } catch (\Exception $e) {
            Logger::wLog('同步marketing数据失败|' .$user_id. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
        }
        return $successCount;
    }

    /**
     * 获取可以使用的红包金额
     * @param int $user_id 用户所有者的ID
     * @param float $money 投资的金额
     * @param int 投资的标ID
     * @param int 优惠码
     * @return float $result 可以使用的金额
     */
    public function get_useable_money($user_id, $money = 0, $is_detail = false, $deal_id = '', $coupon = '', $checkSwitch = false) {

        if ($money > 0) {
            $bonus_use_limit = intval(app_conf('BONUS_USE_LIMIT'));
            if ($bonus_use_limit > 0 && bccomp($money, $bonus_use_limit, 2) == -1) {
                return 0;
            }
        }

        return $this->getUsableBonus($user_id, $is_detail, $money);

        if ($checkSwitch) {
            $switch = (bool)BonusConfModel::get('GET_BONUS_DATA_FROM_NEW');
            if ($switch) {
                $bonusInfo = $this->getUsableBonus($user_id, $is_detail, $money);
                foreach ($bonusInfo['bonuses'] as $key => $item) {
                    $bonusInfo['bonuses'][$key]['id'] = $item['token'];
                }
                return $bonusInfo;
            }
        } else {
            return $this->getUsableBonus($user_id, $is_detail, $money);
        }

        //获取规则，根据规则生成
        $list = $this->get_list($user_id, 1, false);
        //array_multisort(array_map('array_pop', $list), SORT_ASC, $list);
        //$rate = $this->get_config('use_rate');
        //$useable_money = bcmul($money, $rate, 2);
        $sum = 0.00;
        $useable_bonuses = array();
        foreach ($list as $bonus) {
            $sum = bcadd($sum, $bonus['money'], 2);
            $useable_bonuses[] = $bonus;
            if ($money > 0 && bccomp($money, $sum, 2) == -1) {
                $tmp = array_pop($useable_bonuses);
                $sum = bcsub($sum, $tmp['money'], 2);
                break;
            }
        }
        $result = array('money' => number_format($sum, 2, '.', ''));
        if ($is_detail) {
            $result['bonuses'] = $useable_bonuses;
        }
        return $result;
    }

    /**
     * 获取用户发出的红包
     * @param int $user_id
     * @param int $make_page
     * @param int $page
     * @param int $size
     */
    public function get_group_list($user_id, $make_page = true, $page = 0, $page_size = 10)
    {
        if (RpcService::getGroupSwitch(RpcService::GROUP_SWITCH_READLIST)) {
            return (new RpcService)->getGroupList($user_id, $page, $page_size);
        }

        $page_data = array(
                'page' => ($page <= 0) ? 1 : $page,
                'page_size' => ($page_size <= 0) ? app_conf("PAGE_SIZE") : $page_size,
                'make_page' => $make_page,
        );
        $list = array();
        $valid_groups = BonusGroupModel::instance()->get_valid_group($user_id);
        $count_valid_group = count($valid_groups);
        if ($count_valid_group <= 0) {
            $list = BonusGroupModel::instance()->get_invalid_group($user_id, ($page_data['page'] - 1) * $page_data['page_size'], $page_data['page_size']);
        } else {
            if ($page_data['page'] * $page_data['page_size'] <= $count_valid_group) {
                $list = array_slice($valid_groups, ($page_data['page'] - 1) * $page_data['page_size'], $page_data['page_size']);
            } else {
                $valid_ids = array_map('array_shift', $valid_groups);
                $start = 0;
                if ($page_data['page'] == ceil($count_valid_group / $page_data['page_size'])) {
                    $valid_groups = array_pop(array_chunk($valid_groups, $page_data['page_size']));
                    $limit = $page_data['page_size'] - count($valid_groups);
                } else {
                    $valid_groups = array();
                    $limit = $page_data['page_size'];
                    $start = ($page_data['page'] - 1) * $page_data['page_size'] - $count_valid_group;
                }
                $list = array_merge($valid_groups, BonusGroupModel::instance()->get_invalid_group($user_id, $start, $limit, $valid_ids));
            }
        }
        $res = array('count' => BonusGroupModel::instance()->countViaSlave('user_id=":id" && created_at >= :created_at', array(':id' => $user_id, ':created_at' => strtotime(date('Y-m-d', strtotime('-30 days'))))), 'list' => $list);
        unset($valid_groups, $valid_ids, $list);
        //$res = BonusGroupModel::instance()->get_list($user_id, $page_data);

        $bonus_obj = BonusModel::instance();
        foreach($res['list'] as &$bonus){
            $bonus['bonus_from'] = '';
            $bonus['bonus_name'] = '';
            if($bonus['deal_id']){
                $deal_info = DealModel::instance()->find($bonus['deal_id'], 'name', true);
                $bonus['bonus_from'] = '投资奖励';
                $bonus['bonus_name'] = ($bonus['bonus_type_id'] == 3) ? intval($bonus['money']).'超级大红包' : $deal_info['name'];
            }elseif($bonus['bonus_type_id'] == 1){
                $bonus['bonus_from'] = '平台奖励';
                $bonus['bonus_name'] = '老用户反馈';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_NEW_USER_DEAL) {
                $bonus['bonus_from'] = '平台奖励';
                $bonus['bonus_name'] = '新手标红包';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_FIRST_DEAL_FOR_DEAL) {
                $bonus['bonus_from'] = '首投奖励';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_FIRST_DEAL_FOR_INVITE) {
                $bonus['bonus_from'] = '邀请投资奖励';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_REGISTER_FOR_NEW) {
                $bonus['bonus_from'] = '注册奖励';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_REGISTER_FOR_INVITE) {
                $bonus['bonus_from'] = '邀请注册奖励';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_BINDCARD_FOR_NEW) {
                $bonus['bonus_from'] = '绑卡奖励';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_BINDCARD_FOR_INVITE) {
                $bonus['bonus_from'] = '邀请绑卡奖励';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_CASH_FOR_NEW || $bonus['bonus_type_id'] == self::TYPE_CASH_NORMAL_FOR_NEW) {
                $bonus['bonus_from'] = '新手注册奖励';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_CASH_FOR_INVITE) {
                $bonus['bonus_from'] = '邀请注册奖励';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_LCS_BUY_RANDOM ||
                $bonus['bonus_type_id'] == self::TYPE_LCS_BUY_AVERAGE ||
                $bonus['bonus_type_id'] == self::TYPE_LCS_BUY_RANDOM_CHECK ||
                $bonus['bonus_type_id'] == self::TYPE_LCS_BUY_AVERAGE_CHECK)
            {
                $bonus['bonus_from'] = '网信红包';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_LCS_BUY_BIRTHDAY) {
                $bonus['bonus_from'] = '生日红包';
            }

            if ($bonus['bonus_type_id'] == self::TYPE_O2O_CONFIRM) {
                $bonus['bonus_from'] = '礼券奖励';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_O2O_ACQUIRE_FOR_INVITER) {
                $bonus['bonus_from'] = '被邀请人领券返利';
            } elseif ($bonus['bonus_type_id'] == self::TYPE_O2O_ACQUIRE_FOR_USER) {
                $bonus['bonus_from'] = '领券返利';
            }

            $num_params = array(':group_id' => $bonus['id']);
            $till_group_id = intval(\SiteApp::init()->cache->get('bonus_group_sync_till_id'));
            if ($bonus['id'] <= $till_group_id || $bonus['get_count'] > 0 || $bonus['used_count'] > 0) {
                $bonus['send_num'] = $bonus['get_count'];
                $bonus['use_num'] = $bonus['used_count'];
            } else {
                // $bonus['send_num'] = $bonus_obj->countViaSlave("`status` != 0 AND `group_id` = :group_id", $num_params);
                //$bonus['send_sum'] = $bonus_obj->get_sum_money($bonus['id'], 1);
                // $bonus['use_num'] = $bonus_obj->countViaSlave("`status` = 2 AND `group_id` = :group_id", $num_params);
                $bonus['send_num'] = $bonus['get_count'];
                $bonus['use_num'] = $bonus['used_count'];
            }

            $bonus['created_at_time'] = format_date($bonus['created_at'], 'Y-m-d H:i:s');
            $bonus['expired_at_time'] = format_date($bonus['expired_at'] - 1, 'Y-m-d H:i:s');
            $bonus['id_encrypt'] = $this->encrypt($bonus['id'], 'E');
            $bonus['can_send_again'] = false;
            if($bonus['send_num'] < $bonus['count'] && $bonus['expired_at'] > time()){
                $bonus['can_send_again'] = true;
            }
            if($bonus['can_send_again']) {//可发送
                $bonus['send_status'] = 1;
            } else if($bonus['send_num'] >= $bonus['count']){//发光了
                $bonus['send_status'] = 2;
            } else {//过期了
                $bonus['send_status'] = 3;
            }
        }
        return $res;
    }

    /**
     * 获取红包列表(已使用、未使用、已过期)
     * @param string $mobile 手机号码
     *
     * @param int $status 红包状态(1,已领取、2,已使用、3,已过期)
     * @param int $time 当前时间
     * @return array 红包列表
     */
    public function get_list($user_id, $status = 1, $make_page = true, $page = 0, $page_size = 10, $is_slave = true, $is_client = false) {
        if ($user_id <= 0) {
            return array();
        }
        $page_data = array(
                'page' => ($page <= 0) ? 1 : $page,
                'page_size' => ($page_size <= 0) ? app_conf("PAGE_SIZE") : $page_size,
                'make_page' => $make_page,
        );
        if ($status == 0) {
            $switch = (bool)BonusConfModel::get('GET_BONUS_DATA_FROM_NEW');
            if ($switch) {
                $bonus_res = $this->getBonusListNew($user_id, $page_data['page'], $page_data['page_size']);
            } else {
                $bonus_res = $this->get_bonus_list($user_id, $page_data['page'], $page_data['page_size']);
            }
        } else {
            $bonus_res = BonusModel::instance()->get_list($user_id, $status, BonusModel::TYPE_GET, $page_data, $is_slave);
        }

        $list = isset($bonus_res['count']) ? $bonus_res['list'] : $bonus_res;
        $date_format_string = $is_client ? 'm-d H:i:s' : 'Y-m-d H:i:s';
        $bonus_hny_group_id = intval($this->encrypt(app_conf('BONUS_HAPPY_NEW_YEAR')));
        foreach ($list as &$row){
            //$row['bonus_type'] = '限投资使用';
            switch ($row['type']) {
                case BonusModel::BONUS_CASH_FOR_NEW :
                    $row['bonus_type'] = '现金红包';
                    break;
                default :
                    $row['bonus_type'] = '限投资使用';

            }
            $row['created_format'] = format_date($row['created_at'], $date_format_string);
            $row['expired_format'] = format_date($row['expired_at'] - 1, $date_format_string);
            // $row['from_type'] = ($row['group_id'] == 0 && $row['sender_uid'] == 0) ? '投资奖励' : '好友邀请';
            $row['from_type'] = '投资奖励';
            $row['from_detail'] = '';
            // 首投奖励信息
            if ($row['type'] == BonusModel::BONUS_FIRST_DEAL_FOR_DEAL) {
                $row['from_type'] = '首投奖励';
            }
            if ($row['type'] == BonusModel::BONUS_FIRST_DEAL_FOR_INVITE) {
                $row['from_type'] = '邀请投资奖励';
            }
            if ($row['type'] == BonusModel::BONUS_REGISTER_FOR_NEW) {
                $row['from_type'] = '注册奖励';
            }
            if ($row['type'] == BonusModel::BONUS_REGISTER_FOR_INVITE) {
                $row['from_type'] = '邀请注册奖励';
            }
            if ($row['type'] == BonusModel::BONUS_BINDCARD_FOR_NEW) {
                $row['from_type'] = '绑卡奖励';
            }
            if ($row['type'] == BonusModel::BONUS_BINDCARD_FOR_INVITE) {
                $row['from_type'] = '邀请绑卡奖励';
            }
            if ($row['type'] == BonusModel::BONUS_CASH_FOR_NEW || $row['type'] == BonusModel::BONUS_CASH_NORMAL_FOR_NEW) {
                $row['from_type'] = '新手注册奖励';
            }
            if ($row['type'] == BonusModel::BONUS_CASH_FOR_INVITE) {
                $row['from_type'] = '邀请注册奖励';
            }
            if ($bonus_hny_group_id > 0 && $bonus_hny_group_id == $row['group_id']) {
                $row['from_type'] = '拜年红包';
            }
            if ($row['type'] == BonusModel::BONUS_PLATFORM_AWARD || $row['type'] == BonusModel::BONUS_STOCK) {
                $row['from_type'] = '平台奖励';
            }
            if ($row['type'] == BonusModel::BONUS_LCS_AVERAGE || $row['type'] == BonusModel::BONUS_LCS_RANDOM) {
                $row['from_type'] = '网信红包';
                $row['from_detail'] = '网信向您发放了一个红包';
            }
            if ($row['type'] == BonusModel::BONUS_LCS_AVERAGE_LCS || $row['type'] == BonusModel::BONUS_LCS_RANDOM_LCS) {
                $row['from_type'] = '网信红包';
                $userInfo = UserModel::instance()->find($row['sender_uid']);
                $row['from_detail'] = "您的好友{$userInfo['real_name']}赠送您一个红包";
            }
            if ($row['type'] == BonusModel::BONUS_REFUND) {
                $row['from_type'] = '红包退回';
            }
            if ($row['type'] == BonusModel::BONUS_COUPON) {
                $row['from_type'] = '邀请红包奖励';
                $row['from_detail'] = "邀请好友投资奖励红包";
            }
            if ($row['type'] == BonusModel::BONUS_BIRTHDAY) {
                $row['from_type'] = '生日红包';
                $row['from_detail'] = '网信向您发放了一个生日红包';
            }
            if ($row['type'] == BonusModel::BONUS_BIRTHDAY_LCS) {
                $row['from_type'] = '生日红包';
                $userInfo = UserModel::instance()->find($row['sender_uid']);
                $row['from_detail'] = "您的好友{$userInfo['real_name']}赠送您一个生日红包";
            }
            if ($row['type'] == BonusModel::BONUS_EVENT_AWARD || $row['type'] == BonusModel::BONUS_HYLAIWU
                || $row['type'] == BonusModel::BONUS_YAOYIYAO || $row['type'] == BonusModel::BONUS_GAME_CIRCLE
                || $row['type'] == BonusModel::BONUS_ACTIVITY || $row['type'] == BonusModel::BONUS_GOLD_COIN
                || $row['type'] == BonusModel::BONUS_DAYDAYYAO
            ) {
                $row['from_type'] = '活动奖励';
            }

            if ($row['type'] == BonusModel::BONUS_O2O_ACQUIRE_FOR_INVITER) {
                $row['from_type'] = '被邀请人领券返利';
            }

            if ($row['type'] == BonusModel::BONUS_O2O_ACQUIRE_FOR_USER) {
                $row['from_type'] = '领券返利';
            }

            if ($row['type'] == BonusModel::BONUS_O2O_CONFIRMED_REBATE) {
                $row['from_type'] = '礼券奖励';
            }

            if (isset(BonusModel::$typeConfig[$row['type']])) {
                $row['from_type'] = BonusModel::$typeConfig[$row['type']];
            }

            if ($row['task_id']) {
                $result = $this->getTaskById($row['task_id'], $row['created_at']);
                if (isset($result['from'])) {
                    $row['from_type'] = $result['from'];
                } else if (isset($this->source[$result['source']])) {
                    $row['from_type'] = $this->source[$result['source']];
                }
            }

            $row['bid_limit'] = '无';

            $row['deal_name'] = '';
            $row['use_time_format'] = $row['status'] == 2 ? format_date($row['update_time'], $date_format_string) : '';
            if($row['status'] == 2){
                $used_info = BonusUsedModel::instance()->getBonusUsedByid($row['id']);
                if($used_info){
                    if ($used_info['deal_id'] > 0) {
                        $deal_info = DealModel::instance()->find($used_info['deal_id'], 'name', true);
                        $row['deal_name'] = $deal_info['name'];
                    } else if ($used_info['consume_id'] > 0) {
                        $consume_info = $this->getConsume($used_info['consume_id']);
                        $row['deal_name'] = $consume_info['info'];
                    }
                    $row['use_time_format'] = format_date($used_info['used_at'], $date_format_string);
                }
            }
        }
        return isset($bonus_res['count']) ? array('count' => $bonus_res['count'], 'list' => $list) : $list;
    }

    /**
     * 获取发送的红包列表
     * @param string $mobile 手机号
     * @param int $status 红包状态(1,已领取、2,已使用、3,已过期)
     * @param int $time 当前时间
     * @return array $result 红包列表
     */
    public function send_list($uid, $status = 1, $make_page = true, $page = 0, $page_size = 10) {
        $page_data = array(
                'page' => ($page <= 0) ? 1 : $page,
                'page_size' => ($page_size <= 0) ? app_conf("PAGE_SIZE") : $page_size,
                'make_page' => $make_page,
        );
        return BonusModel::instance()->get_list($uid, $status, BonusModel::TYPE_SEND, $page_data, true);
    }

    /**
     * 红包发放机制
     * @param float $rate 红包发放比率
     * @param float $size 红包平均大小
     * @param int   $times 红包最大差额比率
     * @param float $money 投资金额
     * @param float $yield 年化收益率
     */
    private function bonus_mechanism($rate, $size, $max_size, $times, $money, $min_count, $max_total_money, $min_total_money, $yield, $type=self::TYPE_DEAL) {
        if ($type == self::TYPE_DEAL) { // 投资产生的正常红包，通过计算得出
            $total_money = bcmul(bcmul($rate, $money, 2), $yield, 2);//红包总金额
            //$total_money = $total_money < $min_total_money ? $min_total_money : $total_money;
            //$total_money = $total_money > $max_total_money ? $max_total_money : $total_money;//限制最大金额
            if (bccomp($total_money, $min_total_money, 2) == -1) {//最小金额处理
                $total_money = $total_money * $this->subsidy_ratio;
                if (bccomp($total_money, $this->subsidy_least_value, 2) == -1) {//当计算出来的金额乘以系数小于固定值时，不生成红包
                    return array('money' => 0, 'bonuses' => array());
                }
                if (bccomp($total_money, $min_total_money, 2) == 1) {
                    $total_money = $min_total_money;
                }
                $total_money = bcmul($total_money, rand(50, 150) / 100, 2);
                $subsidy_count = ceil($total_money) + 1;
                if ($subsidy_count < $min_count) {
                    $min_count = $subsidy_count;
                }
            }
            if (bccomp($total_money, $max_total_money, 2) == 1) {//最大金额不超过max_total_money
                $total_money = $max_total_money;
            }
            $range = floor($total_money / 100) - 5; //平均值递增
            if ($range > 0) {
                $size += $range;
            }
            //$size = $max_size < $size ? $max_size : $size;
            $count = ceil($total_money / $size);//红包数量，可能会根据金额线性变化，需要相关函数
            $count = $count < $min_count ? $min_count : $count;//限制最小个数
        } else {
            $count = $min_count;
            $total_money = $min_total_money;
        }
        $rand_arr = array();
        $sum = 0;
        $rand_start = 1 * $this->rand_deep;
        $rand_end = $times * $this->rand_deep;
        for ($i = 0; $i < $count; $i++) {
            $rand_arr[] = rand($rand_start, $rand_end);
        }
        $bonuses = $tmp_arr = array();
        $sum = array_sum($rand_arr);
        array_pop($rand_arr);
        foreach ($rand_arr as $val) {
            $tmp_arr[] = bcdiv(bcmul($val, $total_money, 2), $sum, 2);
        }
        $tmp_arr[] = $total_money - array_sum($tmp_arr);
        foreach ($tmp_arr as $bonus) { //去掉生成为零的红包
            if ($bonus <= 0.00) {
                continue;
            }
            $bonuses[] = $bonus;
        }
        return array('money' => $total_money, 'count' => $count, 'bonuses' => $bonuses);
    }

    /**
     * 对红包ID进行加密解密的函数
     * @param string $string 需要加密的字符串
     * @param string (D or E) 加密还是解密
     * @return string 加密or解密结果
     */
    public function encrypt($string, $operation = 'D') {
        $key = md5($this->key);
        $key_length = strlen($key);
        if (strpos($string, '%') !== false) {
            $string = urldecode($string);
        }
        $string = str_replace(' ', '+', $string);
        $string  =  $operation  ==  'D' ? base64_decode($string) : substr(md5($string.$key), 0, 8) . $string;
        $string_length = strlen($string);
        $rndkey = $box = array();
        $result = '';
        for($i = 0; $i<= 255; $i++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a+1) % 256;
            $j = ($j+$box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result.= chr(ord($string[$i])^($box[($box[$a] + $box[$j]) % 256]));
        }
        if($operation == 'D') {
            if(substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
                return substr($result, 8);
            } else {
                return '';
            }
        } else {
            return urlencode(str_replace('=', '', base64_encode($result)));
        }
    }

    /**
     * getListBySn
     *
     * @param string $sn
     * @param string $fields
     * @param boolean $getALl true getall false getBind
     * @access public
     * @return void
     */
    public function get_list_by_sn($sn, $scope = self::SCOPE_ALL, $fields = '*', $page = 1, $count = 500, $orderBy = 'DESC') {
        if (!$sn) {
            return false;
        }
        $group_id = $this->encrypt($sn, 'D');
        if (!$group_id) {
            return false;
        }
        $condition = "group_id = $group_id";

        if ($scope == self::SCOPE_BIND) {
            $condition .= " AND status > 0";
        }

        if ($scope == self::SCOPE_UNBIND) {
            $condition .= " AND status = 0";
        }

        $order = " ORDER BY created_at $orderBy";
        $limit = ' LIMIT '. ($page - 1) * $count . ",$count";
        return array('count' => BonusModel::instance()->count($condition), 'list' => BonusModel::instance()->findAll($condition.$order.$limit, false, $fields));
        //return BonusModel::instance()->findAll($condition, false, $fields);
    }

    /**
     * getGroupInfoBySn
     *
     * @param string $sn
     * @access public
     * @return void
     */
    public function get_group_info_by_sn($sn) {
        if (!$sn) {
            return false;
        }
        $group_id = $this->encrypt($sn, 'D');
        if ($group_id) {
            //$result = BonusGroupModel::instance()->find($group_id);
            $result = $this->getGroupByIdUseCache($group_id);
            $this->generation_bonus_item($result['user_id'], $result['id'], $result['bonus_type_id'], $result['count'], $result['money']);
            $result['active_config'] = ($result['bonus_type_id'] == self::TYPE_ACTIVITY) ? $this->getActivityByGroupId($group_id) : array();
            if ($result['bonus_type_id'] == self::TYPE_XQL) {
                $super_id = \SiteApp::init()->cache->get('bonus_xql_super_id_'.$group_id);
                $super_conf = $this->getSuperConfById($super_id);
                if (!empty($super_conf)) {
                    if ($super_conf['retweet_title'] != '' && $super_conf['retweet_icon'] != '' && $super_conf['retweet_desc'] != '') {
                        $active_config = array();
                        $static_host = app_conf('STATIC_HOST');
                        $active_config['icon'] = (substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/'.$super_conf['retweet_icon'];
                        $active_config['name'] = $super_conf['retweet_title'];
                        $active_config['desc'] = $super_conf['retweet_desc'];
                        $result['active_config'] = $active_config;
                    }
                }
            }

            return $result;
        }
        return false;

    }

    /**
     * 获取投资使用的红包金额以及list
     * @param int $deal_load_id 投标ID
     * @return array
     */
    public function get_used($deal_load_id) {
        if (empty($deal_load_id)) {
            return array();
        }
        $condition = 'deal_load_id=:deal_load_id';
        $params = array(':deal_load_id' => $deal_load_id);
        $used_bonus = BonusUsedModel::instance()->findAll($condition, true, 'bonus_id', $params);
        if (empty($used_bonus)) {
            return array();
        }
        $ids = $list = $data = array();
        foreach ($used_bonus as $row) {
            $ids[] = $row['bonus_id'];
            $list[$row['bonus_id']] = $row;
        }

        $sum = 0.00;
        $bonuses = BonusModel::instance()->findAll('id in(:ids)', true, 'id, money, owner_uid, mobile', array(':ids' => implode(',',$ids)));
        foreach ($bonuses as $row) {
            $sum += $row['money'];
            $data[] = array('bonus_id' => $row['id'], 'money' => $row['money'], 'mobile' => $row['mobile'], 'owner_uid' => $row['owner_uid']);
        }
        return array('money' => $sum, 'list' => $data);
    }

    public function get_bonus_group($load_id){
        if (intval($load_id) <= 0) {
            return false;
        }
        $res = BonusGroupModel::instance()->findBy('deal_load_id=":deal_load_id"', '*', array(':deal_load_id' => intval($load_id)));
        if (!empty($res)) {
            $res['id_encrypt'] = $this->encrypt($res['id'], 'E');
        }
        if ($res['bonus_type_id'] == self::TYPE_XQL) {
            $res['money'] = number_format($res['money'], 0, '', '');
        }
        return $res;
    }

    // 获取红包组信息
    public function getGroupInfoById($id)
    {
        //$groupObj = BonusGroupModel::instance()->find($id);
        $groupObj = $this->getGroupByIdUseCache($id);
        if (empty($groupObj)) {
            return false;
        }
        $group = $groupObj->getRow();
        $bonusInfo = '';
        $bonusTitle = '';
        if ($group['deal_id']){
            $dealInfo = DealModel::instance()->find($group['deal_id'], 'name', true);
            $bonusInfo = "投资“".$dealInfo['name']."”获得";
        } elseif ($group['bonus_type_id'] == 1){
            $bonusInfo = "活动奖励";
        } elseif ($group['bonus_type_id'] == self::TYPE_FIRST_DEAL_FOR_DEAL) {
            $bonusInfo = '首投奖励';
        } elseif ($group['bonus_type_id'] == self::TYPE_FIRST_DEAL_FOR_INVITE) {
            $bonusInfo = '邀请投资奖励';
        } elseif ($group['bonus_type_id'] == self::TYPE_REGISTER_FOR_NEW) {
            $bonusInfo = '注册奖励';
        } elseif ($group['bonus_type_id'] == self::TYPE_REGISTER_FOR_INVITE) {
            $bonusInfo = '邀请注册奖励';
        } elseif ($group['bonus_type_id'] == self::TYPE_BINDCARD_FOR_NEW) {
            $bonusInfo = '绑卡奖励';
        } elseif ($group['bonus_type_id'] == self::TYPE_BINDCARD_FOR_INVITE) {
            $bonusInfo = '邀请绑卡奖励';
        }

        if ($group['bonus_type_id'] == self::TYPE_O2O_CONFIRM) {
            $bonusInfo = '礼券奖励';
        } elseif ($group['bonus_type_id'] == self::TYPE_O2O_ACQUIRE_FOR_INVITER) {
            $bonusInfo = '被邀请人领券返利';
        } elseif ($group['bonus_type_id'] == self::TYPE_O2O_ACQUIRE_FOR_USER) {
            $bonusInfo = '领券返利';
        } elseif ($group['bonus_type_id'] == self::TYPE_LCS_BUY_RANDOM ||
            $group['bonus_type_id'] == self::TYPE_LCS_BUY_AVERAGE ||
            $group['bonus_type_id'] == self::TYPE_LCS_BUY_AVERAGE_CHECK ||
            $group['bonus_type_id'] == self::TYPE_LCS_BUY_RANDOM_CHECK)
        {
            $bonusInfo = '红包购买';
            $bonusTitle = '购买时间';
        } elseif ($group['bonus_type_id'] == self::TYPE_LCS_BUY_BIRTHDAY) {
            $bonusInfo = '生日红包';
            $bonusTitle = '购买时间';
        }

        $params = array(':group_id' => $group['id']);
        $group['bonusInfo'] = $bonusInfo;
        $group['bonusTitle'] = $bonusTitle;
        $group['dealTitle'] = $dealInfo['name'];
        $group['sendNum'] = BonusModel::instance()->countViaSlave("`status` != 0 AND `group_id` = :group_id", $params);
        $group['useNum'] = BonusModel::instance()->countViaSlave("`status` = 2 AND `group_id` = :group_id", $params);
        $group['leftNum'] = $group['count'] - $group['sendNum'];
        $group['createdAt'] = format_date($group['created_at'], 'Y-m-d H:i:s');
        $group['expiredAt'] = format_date($group['expired_at'] - 1, 'Y-m-d H:i:s');
        $group['encrypt'] = $this->encrypt($group['id'], 'E');

        return $group;
    }

    // 获取红包详情
    public function getBonusInfoById($id)
    {
        $switch = (bool)BonusConfModel::get('GET_BONUS_DATA_FROM_NEW');
        if ($switch) {
            $bonusObj = (new RpcService)->getBonusInfoOldPage($id);
        } else {
            $bonusObj = BonusModel::instance()->find($id);
        }
        if (empty($bonusObj)) {
            return false;
        }
        $bonus = is_array($bonusObj) ? $bonusObj : $bonusObj->getRow();
        $bonus['bonusType'] = '限投资使用';
        $bonus['createdAt'] = format_date($bonus['created_at'], 'Y-m-d H:i:s');
        $bonus['expiredAt'] = format_date($bonus['expired_at'] - 1, 'Y-m-d H:i:s');
        $bonus['fromType'] = '好友邀请';
        $bonus['fromType'] = ($bonus['group_id'] == 0 && $bonus['sender_uid'] == 0) ? '投资奖励' : '好友邀请';
        $bonus['fromDetail'] = '';
        $bonus_hny_group_id = intval($this->encrypt(app_conf('BONUS_HAPPY_NEW_YEAR')));
        if ($bonus['type'] == BonusModel::BONUS_FIRST_DEAL_FOR_DEAL) {
            $bonus['fromType'] = '首投奖励';
        }
        if ($bonus['type'] == BonusModel::BONUS_FIRST_DEAL_FOR_INVITE) {
            $bonus['fromType'] = '邀请投资奖励';
        }
        if ($bonus['type'] == BonusModel::BONUS_REGISTER_FOR_NEW) {
            $bonus['fromType'] = '注册奖励';
        }
        if ($bonus['type'] == BonusModel::BONUS_REGISTER_FOR_INVITE) {
            $bonus['fromType'] = '邀请注册奖励';
        }
        if ($bonus['type'] == BonusModel::BONUS_BINDCARD_FOR_NEW) {
            $bonus['fromType'] = '绑卡奖励';
        }
        if ($bonus['type'] == BonusModel::BONUS_BINDCARD_FOR_INVITE) {
            $bonus['fromType'] = '邀请绑卡奖励';
        }
        if ($bonus['type'] == BonusModel::BONUS_CASH_FOR_NEW || $bonus['type'] == BonusModel::BONUS_CASH_NORMAL_FOR_NEW) {
            $bonus['fromType'] = '新手注册奖励';
        }
        if ($bonus['type'] == BonusModel::BONUS_CASH_FOR_INVITE) {
            $bonus['fromType'] = '邀请注册奖励';
        }
        if ($bonus_hny_group_id > 0 && $bonus_hny_group_id == $bonus['group_id']) {
            $bonus['fromType'] = '拜年红包';
        }
        if ($bonus['type'] == BonusModel::BONUS_PLATFORM_AWARD || $bonus['type'] == BonusModel::BONUS_STOCK) {
            $bonus['fromType'] = '平台奖励';
        }
        if ($bonus['type'] == BonusModel::BONUS_LCS_AVERAGE || $bonus['type'] == BonusModel::BONUS_LCS_RANDOM) {
            $bonus['fromType'] = '网信红包';
            $bonus['fromDetail'] = '网信向您发放了一个红包';
        }
        if ($bonus['type'] == BonusModel::BONUS_LCS_AVERAGE_LCS || $bonus['type'] == BonusModel::BONUS_LCS_RANDOM_LCS) {
            $bonus['fromType'] = '网信红包';
            $userInfo = UserModel::instance()->find($bonus['sender_uid']);
            $bonus['fromDetail'] = "您的朋友{$userInfo['real_name']}赠送您一个红包";
        }
        if ($bonus['type'] == BonusModel::BONUS_REFUND) {
            $bonus['fromType'] = '红包退回';
            $bonus['fromDetail'] = '您充值的网信红包，由于未被领取，退回到您的红包账户中，可用于投资使用。';
        }
        if ($bonus['type'] == BonusModel::BONUS_COUPON) {
            $bonus['fromType'] = '邀请红包奖励';
            $bonus['fromDetail'] = '邀请好友投资奖励红包';
        }
        if ($bonus['type'] == BonusModel::BONUS_BIRTHDAY_LCS) {
            $bonus['fromType'] = '生日红包';
            $userInfo = UserModel::instance()->find($bonus['sender_uid']);
            $bonus['fromDetail'] = "您的朋友{$userInfo['real_name']}赠送您一个生日红包";
        }
        if ($bonus['type'] == BonusModel::BONUS_BIRTHDAY) {
            $bonus['fromType'] = '生日红包';
            $bonus['fromDetail'] = '网信向您发放了一个生日红包';
        }
        if ($bonus['type'] == BonusModel::BONUS_EVENT_AWARD || $bonus['type'] == BonusModel::BONUS_HYLAIWU
            || $bonus['type'] == BonusModel::BONUS_YAOYIYAO || $bonus['type'] == BonusModel::BONUS_GAME_CIRCLE
            || $bonus['type'] == BonusModel::BONUS_ACTIVITY || $bonus['type'] == BonusModel::BONUS_GOLD_COIN
            || $bonus['type'] == BonusModel::BONUS_DAYDAYYAO
        ) {
            $bonus['fromType'] = '活动奖励';
        }

        if ($bonus['task_id']) {
            $result = $this->getTaskById($bonus['task_id']);
            if (isset($this->source[$result['source']])) {
                $bonus['fromType'] = $this->source[$result['source']];
            }
        }

        if ($bonus['type'] == BonusModel::BONUS_O2O_ACQUIRE_FOR_INVITER) {
            $bonus['fromType'] = '被邀请人领券返利';
        }

        if ($bonus['type'] == BonusModel::BONUS_O2O_ACQUIRE_FOR_USER) {
            $bonus['fromType'] = '领券返利';
        }

        if ($bonus['type'] == BonusModel::BONUS_O2O_CONFIRMED_REBATE) {
            $bonus['fromType'] = '礼券奖励';
        }

        if (isset(BonusModel::$typeConfig[$bonus['type']])) {
            $bonus['fromType'] = BonusModel::$typeConfig[$bonus['type']];
        }

        $bonus['bidLimit'] = '无';

        $used = BonusUsedModel::instance()->findByViaSlave("bonus_id=:id", '*', array(':id' => $id));
        if ($used) {
            $dealInfo = DealModel::instance()->find($used['deal_id'], 'name', true);
            $bonus['usedDealTitle'] = $dealInfo['name'];
            $bonus['usedTime'] = format_date($used['used_at'], 'Y-m-d H:i');
        } else {
            $bonus['usedDealTitle'] = '';
            $bonus['usedTime'] = '';
        }

        return $bonus;
    }

    public function getBonusByOpenid($sn, $openid) {
        $group_id = $this->encrypt($sn, 'D');

        $condition = 'group_id = ' . $group_id . ' AND openid = "' . $openid . '"';
        if ($result = BonusModel::instance()->findByViaSlave($condition, '*')) {
            return array(
                'status' => 2, 'money' => $result['money'], 'sn' => $this->encrypt($result['id'], 'E'), 'mobile' => $result['mobile'],
                'created_at' => $result['created_at'], 'expired_at' => $result['expired_at'], 'id' => $result['id'],
            );
        }

        return false;
    }

    public function getCurrentBonusCount($sn) {
        $group_id = $this->encrypt($sn, 'D');

        $condition = 'group_id = ' .$group_id. ' AND status > 0';
        return BonusModel::instance()->count($condition);
    }

    public function checkOwener($sn, $mobile) {

        if (!$sn || !$mobile) {
            return false;
        }

        $groupId = $this->encrypt($sn, 'D');
        //$groupInfo = BonusGroupModel::instance()->find($groupId);
        $groupInfo = $this->getGroupByIdUseCache($groupId);
        $user = UserModel::instance()->findBy('mobile=":mobile"', 'id', array(':mobile' => $mobile));
        if (!empty($user) && !empty($groupInfo)) {
            if ($user['id'] === $groupInfo['user_id']) {
                return true;
            }
        }
        return false;
    }

    /**
     * getBonusTemplates
     *
     * @access public
     * @return array
     */
    public function getBonusTemplates() {
        $tplConf = require APP_ROOT_PATH . 'web/controllers/hongbao/TemplateConf.php';
        return $tplConf;
    }


    /**
     * getActivityByGroupId
     * 根据group_id获得活动信息
     *
     * @param mixed $group_id
     * @access public
     * @return mix
     */
    public function getActivityByGroupId($group_id) {
        $data = new \core\data\BonusData();
        $rs = $data->getActivityByGroupId($group_id);
        if (!$rs) {
            $activity_model = new \core\dao\BonusActivityModel();
            $rs = $activity_model->getByGroupId($group_id);
            $data->setActivity($group_id, $rs);
        }

        if (!empty($rs)) {
            //if ($rs['link_invalid_date'] < get_gmtime()) {  //活动已经失效
               // return false;
            //}
            return $rs;
        } else {
            return false;
        }

    }

    /**
     * 获取大号发送的红包随机金额
     */
    private function get_event_rand_money_and_days($group_id) {
        $active = $this->getActivityByGroupId($group_id);
        $money = 0.00;
        if ($active != false) {
            if ($active['is_fixed']) {
                $data = explode(',', $active['multiple_money']);
                shuffle($data);
                $money = array_shift($data);
            } else {
                $money = bcdiv(rand($active['range_money_start'] * 100, $active['range_money_end'] * 100), 100, 2);
            }
        }
        return array($active['valid_day'], $money);
    }

    /**
     * getUserSumMoney
     *
     * @param array $args
     * @access public
     * @return float
     */
    public function getUserSumMoney($args = array(), $notExpired = true) {
        if(!empty($args['endExpireTime'])){//判断24小时内 是否会过期
            return BonusModel::instance()->get_user_sum_money($args, $notExpired);
        }
        if (!$args['mobile'] && !$args['userId'] && !$args['openid'] && !$args['type']) {
            return false;
        }
        return BonusModel::instance()->get_user_sum_money($args, $notExpired);
    }

    /**
     * checkXql
     * 判断是否符合888红包规则
     *
     * @param mixed $loan_money
     * @access public
     * @return void
     */
    public function checkXql() {
        $range_date = app_conf('BONUS_XQL_RANGE_DATE');
        $range_hours = app_conf('BONUS_XQL_RANGE_HOURS');   // 每日活动区间
        $range_times = app_conf('BONUS_XQL_TIMES'); // 频率 几小时一次

        $range_date = explode('|', $range_date);
        $range_hours = explode('|', $range_hours);

        $now = time();
        $start_day = $range_date[0];
        $end_day = $range_date[1];
        $current_day = date("Y-m-d", $now);
        $current_hour = intval(date("H", $now));  //  当前小时
        $current_md = date("m_d", $now);      // 当前月日

        if ($current_day >= $start_day && $current_day <= $end_day) {   // 判断起始日期 和 结束日期
            if ($current_hour >= $range_hours[0] && $current_hour <= $range_hours[1]) { // 判断起始小时  和 结束小时
                if ($this->loan_money >= app_conf('BONUS_XQL_BID_MIN_MONEY')) {   // 判断投资金额
                    $arr = $this->getRangeHour($range_hours[0], $range_hours[1], $current_hour, $range_times);
                    if (count( $arr )) {
                        $day_key = implode('_', $arr);
                    } else {
                        return false;   // 获取数据不正确
                    }
                    $key = "BONUS_XQL_{$current_md}_{$day_key}";
                    //  echo $key;  die;

                    $xql_time = \SiteApp::init()->cache->get($key);
                    if ($xql_time === false) {
                        return false;
                    } else {
                        if ($now > $xql_time) { // 当前投资时间比888红包时间大。
                            // 悲观锁，以小时 时间段为键名
                            $lockKey = "BonusXQL_{$day_key}";
                            $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
                            if (!$lock->getLock($lockKey, 3600)) {
                                return false;
                            }

                            return \SiteApp::init()->cache->delete($key);   //删除该时间段key
                        }
                    }
                }

            }
        }
        return false;
    }

    public function getRangeHour($start, $end, $cur, $times) {

        $tmp = $start;
        $mod_time = 0;

        for( ; $tmp <= $cur; $tmp++) {
            if ( ($tmp - $start) % $times == 0 ) {
                $mod_time++;
            }
            $arr[$tmp] = array($start + $times * ($mod_time - 1), $start + $times * $mod_time);
        }
        return $arr[$cur];
    }

    /**
     * 获取超级红包配置
     *
     * @param mixed $loan_money
     * @access public
     * @return false or array
     */
    public function getSuperConf($loan_money = 500, $deal_id = 0) {

        $now = time();
        $xqls = $this->getSuperConfList();
        if (empty($xqls)) {
            return false;
        }
        $generation_money = 0;
        $deal_info = DealModel::instance()->find($deal_id, '`loantype`, `repay_time`');
        if($deal_info) {
            $rate =($deal_info['loantype'] == 5) ? ($deal_info['repay_time'] / 360) : ($deal_info['repay_time'] / 12);
            $generation_money = bcmul(bcmul($this->get_config('rate'), $loan_money, 2), $rate, 2);
        }

        foreach($xqls as $item) {
            //return array($item['id'], $item['group_money'], $item['group_count'], $item['bonus_count'], $item['send_limit_day'], $item['use_limit_day']);
            if ($now < $item['start_time'] || $now > $item['end_time']) {//限制时间
                continue;
            }
            if ($loan_money < $item['trigger_money'] || $now < $item['start_time'] || $now > $item['end_time']) {
                continue;
            }
            if (bccomp($item['group_money'], $generation_money) == -1) {
                continue;
            }
            list($hour_start, $hour_end) = explode("|", $item['hour_section']);
            $key = $this->getRangeKey($hour_start, $hour_end, $item['frequency'], $item['id'], 'BONUS_XQL');
            if ($key == false) {
                continue;
            }
            $xql_time = \SiteApp::init()->cache->get($key);
            if ($xql_time === false) {
                continue;
            } else {
                if ($now > $xql_time) { // 当前投资时间比888红包时间大。
                    // 悲观锁，以小时 时间段为键名
                    $lockKey = "BonusXQL_lock_{$item['id']}_{$xql_time}";
                    $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
                    if (!$lock->getLock($lockKey, 3600)) {
                        return false;
                    }
                    $result = \SiteApp::init()->cache->delete($key);   //删除该时间段key
                    if ($result) {
                        return array($item['id'], $item['group_money'], $item['group_count'], $item['bonus_count'], $item['send_limit_day'], $item['use_limit_day']);
                    }
                    $lock->releaseLock($lockKey);//解锁
                    return false;
                }
            }
        }
        return false;
    }

    /**
     * 根据配置条件，获取该时间范围内超级红包产生时间的KEY
     * @param int $hour_sart 当天活动开始的时间
     * @param int $hour_end 当天活动结束的时间
     * @param int $frequency 频次
     * @param int $rule_id 超级红包配置ID
     * @param string $prefix 超级红包产生时间KEY前缀
     *
     * @return fase or string
     */
    public function getRangeKey($hour_start = 0, $hour_end = 24, $frequency = 1, $rule_id = 1, $prefix = 'BONUS_XQL') {
        $now = time();
        $start_time = mktime($hour_start, 0, 0);
        $end_time = mktime($hour_end, 0, 0);
        if ($now < $start_time || $now > $end_time) {
            return false;
        }
        $times = ceil(($hour_end - $hour_start) * 60 / $frequency);
        $frequency = $frequency * 60;//间隔的秒数
        for($i = 0 ; $i < $times; $i++) {
            $section_start = $start_time + $frequency * $i;
            $section_end   = $start_time + $frequency * ($i + 1);
            if ($now >= $section_start && $now < $section_end) {
                return sprintf("%s_%s_%s_%s", $prefix, $rule_id, $section_start, $section_end);
            }
        }
        return false;
    }

    /**
     * 获取超级红包的配置
     */
    public function getSuperConfList($loan_money = 500) {

        $result = \SiteApp::init()->cache->get('BONUS_XQL_CONFIG_LIST');
        if (empty($result)) {
            $condition = "`status` = 1 ORDER BY `group_money` DESC";
            $result = BonusSuperModel::instance()->findAll($condition, false, '*');
            \SiteApp::init()->cache->set('BONUS_XQL_CONFIG_LIST', $result, 86400);
        }
        return $result;
    }

    /**
     * 获取单个配置
     */
    public function getSuperConfById($super_id) {
        $result = $this->getSuperConfList();
        if (!empty($result)) {
            foreach ($result as $item) {
                if ($super_id == $item['id']) {
                    return $item;
                }
            }
        }
        return array();
    }

    /**
     * 支付消耗红包
     */
    public function payConsume($userId, $amount, $outOrderId, $channel, $desc)
    {
        \libs\utils\Monitor::add('BONUS_PAY_CONSUME');

        $amount = intval($amount);
        if ($amount <= 0) {
            throw new \Exception('红包金额参数错误');
        }

        try {
            $GLOBALS['db']->startTrans();

            $consumeId = $this->bonusConsumeAdd($userId, $amount, $outOrderId, $channel, $desc);

            //消费红包
            $bonusIds = array();
            $sum = 0;
            $list = $this->get_list($userId, 1, false);
            foreach ($list as $item) {
                $bonusIds[] = $item['id'];
                $sum += intval(round($item['money']*100));
                if ($sum === $amount) {
                    break;
                }
            }
            if ($sum !== $amount) {
                throw new \Exception('红包余额与消耗金额不相等');
            }

            $ret = $this->consume($bonusIds, 0, 0, $userId, $consumeId);
            if (!$ret) {
                throw new \Exception('红包消费逻辑失败');
            }

            //转账 (红泽利 => 支付)
            $transferService = new TransferService();
            $payerId = app_conf('BONUS_BID_PAY_USER_ID');
            $receiverId = app_conf('BONUS_UCFPAY_USER_ID');
            $note = "用户{$userId}订单{$outOrderId}消费红包";
            $transferAmount = bcdiv($amount, 100, 2);
            $transferService->payerChangeMoneyAsyn = true;
            $transferService->transferById($payerId, $receiverId, $transferAmount, '红包消费', $note, '红包消费', $note, "BOUNSPAY|{$consumeId}");

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 增加消费批次
     */
    public function bonusConsumeAdd($userId, $amount, $outOrderId, $channel, $info)
    {
        $data['user_id'] = $userId;
        $data['amount'] = bcdiv($amount, 100, 2);
        $data['out_order_id'] = $outOrderId;
        $data['channel'] = $channel;
        $data['status'] = BonusConsumeModel::STATUS_CONSUME;
        $data['create_time'] = time();
        $data['info'] = $info;
        return BonusConsumeModel::instance()->add($data);
    }

    /**
     * bonusConsumeFinish
     *
     * @param integer $id
     * @param integer $status
     * @access public
     * @return void
     */
    public function bonusConsumeFinish($id, $consumeLog = array(), $status = BonusConsumeModel::STATUS_SUCCESS) {

        $id = intval($id);
        if ($id <= 0 || $status != BonusConsumeModel::STATUS_SUCCESS) {
            return false;
        }

        if (empty($consumeLog)) {
            $consumeLog = $this->getConsume($id);
        }

        if (empty($consumeLog)) {
            return false;
        }

        if ($consumeLog['amount'] <= 0) {
            return false;
        }

        $data = array('id' => $id, 'status' => $status);
        $data['notify_time'] = time();

        $GLOBALS['db']->startTrans();
        try {
            $logResult = BonusConsumeModel::instance()->update($data);
            if ($logResult === 0) {
                throw new \Exception('Duplicate entry', -100);
            }
            if ($logResult === false) {
                throw new \Exception('更新失败');
            }
            $userModel = new UserModel();
            // TODO 先锋支付账户获取
            $userModel->id = app_conf('BONUS_UCFPAY_USER_ID');
            $logInfo = '红包消费成功';
            $note = "订单：{$consumeLog['out_order_id']}，渠道:{$consumeLog['channel']}";
            $result = $userModel->changeMoney(-$consumeLog['amount'], $logInfo, $note, 0, 0, UserModel::TYPE_MONEY);
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * getConsumeByOutOid
     *
     * @param string $outOrderId
     * @access public
     * @return void
     */
    public function getConsumeByOutOid($outOrderId) {

        if (empty($outOrderId)) {
            return false;
        }

        $conditions = array('out_order_id' => $outOrderId);
        return BonusConsumeModel::instance()->getOneByConditions($conditions);
    }

    /**
     * getConsume
     *
     * @param integer $id
     * @access public
     * @return array
     */
    public function getConsume($id) {

        $id = intval($id);
        if ($id <= 0) {
            return false;
        }
        $conditions = array('id' => $id);
        return BonusConsumeModel::instance()->getOneByConditions($conditions);
    }

    public function getWeixinInfoByMobile($mobile) {

        if (!$mobile) {
            return false;
        }

        $conditions = array('mobile' => $mobile);
        $data = BonusBindModel::instance()->getByConditions($conditions);

        if (!$data['openid']) {
            return false;
        }
        $weixinInfoService = new WeixinInfoService();
        $wxInfo = $weixinInfoService->getWeixinInfo($data['openid']);
        return $wxInfo;
    }

    /**
     * getFirstDealRebateBonusCount
     * 获取用户当天获取的首投返利红包
     *
     * @param integer $userId  用户Id
     * @access public
     * @return int
     */
    public function getRebateBonusCount($userId, $groupType, $bonusType, $dealTime = 0) {

        if (!$userId) {
            return false;
        }

        if (!$bonusType) {
            return false;
        }

        if (!$groupType) {
            return false;
        }

        $countGroup = BonusGroupModel::instance()->getRebateBonusCount($userId, $groupType);
        $countBonus = BonusModel::instance()->getRebateBonusCount($userId, $bonusType, $dealTime);

        return $countGroup+$countBonus;
    }

    public function getBonusNewUserRebate($const_name) {

        $data = \SiteApp::init()->cache->get(self::CACHE_PREFIX_BONUS_NEW_USER_REBATE.$const_name);
        if (empty($data)) {
            $result = BonusDispatchConfigModel::instance()->findBy('const_name=":const_name"', '*', array(':const_name' => $const_name));
            if (empty($result)) {
                return array();
            }
            $time = time();
            if ($result['status'] == false || $result['start_time'] > $time || $result['end_time'] < $time) {
                return array();
            }
            $inviter = BonusDispatchConfigModel::instance()->findBy('const_name=":const_name"', '*', array(':const_name' => $const_name."_INVITER"));
            $fields = array('is_group', 'count', 'money', 'send_limit_day', 'use_limit_day', 'id', 'consume_type');
            $forNew = array();
            $forInvite = array();
            foreach ($fields as $field) {
                $forNew[$field] = $result[$field];
                $forInvite[$field] = $inviter[$field];
            }
            $data = array('forNew' => $forNew, 'forInvite' => $forInvite);
            \SiteApp::init()->cache->set(self::CACHE_PREFIX_BONUS_NEW_USER_REBATE.$const_name, $data, 86400);
        }
        return $data;

    }
    /**
     * getListByType
     *
     * @param string $sn
     * @param string $fields
     * @param boolean $getALl true getall false getBind
     * @access public
     * @return void
     */
    public function get_list_by_type($type, $ownerUid = '', $referUid = '', $scope = self::SCOPE_ALL, $fields = '*', $page = 1, $count = 100, $orderBy = 'ASC') {
        if (!$type) {
            return false;
        }

        if ($type == BonusModel::BONUS_CASH_FOR_NEW) {
            $condition = 'type IN ('.BonusModel::BONUS_CASH_FOR_NEW.','.BonusModel::BONUS_CASH_NORMAL_FOR_NEW.')';
        } else {
            $condition = "type = $type";
        }

        if ($scope == self::SCOPE_BIND) {
            $condition .= " AND status > 0";
        }

        if ($scope == self::SCOPE_UNBIND) {
            $condition .= " AND status = 0";
        }

        if ($scope == self::SCOPE_USED) {
            $condition .= " AND status = 2";
        }

        if ($referUid) {
            $condition .= ' AND refer_mobile = "'.$referUid.'"';
        }

        if ($ownerUid) {
            $condition .= ' AND owner_uid = '.$ownerUid;
        }

        $order = " ORDER BY id $orderBy";
        $limit = ' LIMIT '. ($page - 1) * $count . ",$count";
        return BonusModel::instance()->findAllViaSlave($condition.$order.$limit, false, $fields);
    }

    // 发送现金红包
    // $replaceBonus 为红包互斥需求添加，是否需要替换之前互斥的红包 add 2015/11/5
    public function sendCashBonus($mobile, $referUid, $ruleName = 'CASH_BONUS_RULE', $replaceBonus = false) {

        if (!$mobile || !$ruleName) {
            return false;
        }

        $lockKey = "ACTION-BONUS-$ruleName-$mobile";
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 3600)) {
            return false;
        }

        $condition = 'type IN (:type1, :type2) AND `mobile`=":mobile"';
        $params = array(':type1' => BonusModel::BONUS_CASH_FOR_NEW, ':type2' => BonusModel::BONUS_CASH_NORMAL_FOR_NEW, ':mobile' => $mobile);
        $result = BonusModel::instance()->findBy($condition, 'id, money, created_at, expired_at', $params);
        if (!empty($result['id'])) {
            $lock->releaseLock($lockKey);//解锁
            return array(
                'status' => 2, 'money' => $result['money'],
                'created_at' => $result['created_at'], 'expired_at' => $result['expired_at']
            );
        }

        $rebateRule = $this->getBonusNewUserRebate($ruleName);
        if (empty($rebateRule)) {
            $lock->releaseLock($lockKey);//解锁
            return array('status' => 3);
        }
        $bonusType = ($rebateRule['forNew']['consume_type'] == 2) ? BonusModel::BONUS_CASH_FOR_NEW : BonusModel::BONUS_CASH_NORMAL_FOR_NEW;
        $this->batch_id = $rule['id'];
        $currentTime = time();
        $expiredTime = $currentTime + $rebateRule['forNew']['use_limit_day'] * 3600 * 24;

        $rBonus = $this->checkMutex($mobile, $bonusType);
        if ($rBonus !== true && !$replaceBonus) {
            $lock->releaseLock($lockKey);//解锁
            return array('status' => 4, 'bonus' => $rBonus);
        }

        $replaceBonusID = 0;
        if ($replaceBonus && $rBonus['id'] > 0) {
            $replaceBonusID = $rBonus['id'];
        }

        BonusModel::instance()->single_bonus(0, 0, 0, $mobile, 1, $rebateRule['forNew']['money'], $currentTime, $expiredTime, NULL, $referUid, $bonusType, $replaceBonusID);
        $lock->releaseLock($lockKey);//解锁

        return array(
            'status' => 1, 'money' => $rebateRule['forNew']['money'],
            'created_at' => $currentTime, 'expired_at' => $expiredTime
        );
    }

    public function rebateCashBonus($userId, $ruleName = 'CASH_BONUS_RULE') {

        $userId = intval($userId);
        $condition = ' type IN (' . BonusModel::BONUS_CASH_FOR_NEW .','.BonusModel::BONUS_CASH_NORMAL_FOR_NEW.') AND owner_uid = '. $userId. ' AND status = 2';
        $bonus = BonusModel::instance()->findBy($condition, 'id, money, created_at, expired_at, refer_mobile, mobile');
        if (empty($bonus)) {
            return true;
        }

        $inviteUserId = $bonus['refer_mobile'];
        $referUserMobile = $bonus['mobile'];

        $condition = ' type = ' .BonusModel::BONUS_CASH_FOR_INVITE. ' AND owner_uid= '.$inviteUserId. ' AND refer_mobile = ' .$referUserMobile;
        $bonus = BonusModel::instance()->findBy($condition, 'id, money, created_at, expired_at, refer_mobile, mobile');
        if (!empty($bonus)) {
            return true;
        }

        $user = UserModel::instance()->find($inviteUserId, 'id,mobile,coupon_level_id,is_delete,is_effect', true);
        if (empty($user) || $user['is_delete'] || empty($user['is_effect'])) {
            return true;
        }

        if (!$this->isCashBonusSender($inviteUserId)) {
            return true;
        }
        $count_sql = sprintf("SELECT COUNT(*) FROM firstp2p_bonus WHERE owner_uid=%s && type=%s && created_at BETWEEN %s AND %s", $user['id'], BonusModel::BONUS_CASH_FOR_INVITE, mktime(0, 0, 0), mktime(23, 59, 59));
        $bonus_count =  intval(BonusModel::instance()->countBySql($count_sql, array(), true));
        $limit_cash_bonus_count = intval(BonusConfModel::get('CASH_SEND_LIMIT_DAY_COUNT'));
        if ($bonus_count > $limit_cash_bonus_count) {
            Logger::wLog('邀请人数超过限制|CASH_SEND_LIMIT_DAY_COUNT|' .$user['id']. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
            return true;
        }

        $rebateRule = $this->getBonusNewUserRebate($ruleName);
        $this->batch_id = $rebateRule['id'];
        if ($rebateRule['forInvite']['is_group'] == 1) {
            $groupType = self::TYPE_CASH_FOR_INVITE;
            $res = $this->generation($user['id'], 0, 0, 0.25, 0, $groupType, $rebateRule['forInvite']['money'], $rebateRule['forInvite']['count'], $rebateRule['forInvite']['send_limit_day']);
        } else {
            $currentTime = time();
            $expiredTime = $currentTime + $rebateRule['forInvite']['use_limit_day'] * 3600 * 24;
            $bonusType = BonusModel::BONUS_CASH_FOR_INVITE;
            $res = BonusModel::instance()->single_bonus(0, 0, $user['id'], $user['mobile'], 1, $rebateRule['forInvite']['money'], $currentTime, $expiredTime, NULL, $referUserMobile, $bonusType);
        }

        return $res;
    }

    public function transCashBonus($userId) {

        $condition = ' type = ' . BonusModel::BONUS_CASH_FOR_NEW .' AND owner_uid = '. $userId. ' AND status = 1 AND expired_at >' . time();
        $bonus = BonusModel::instance()->findBy($condition, 'id, money, created_at, expired_at, refer_mobile, mobile');
        if (empty($bonus)) {
            return true;
        }


        $GLOBALS['db']->startTrans();
        try {
            //处理红包的转账
            $handle_bonus = ($bonus['money'] > 0) ? true : false;
            if($handle_bonus){
                $transferService = new TransferService();
                $payerId = app_conf('BONUS_BID_PAY_USER_ID');
                $notePay = "用户{$userId}注册返利现金红包返利{$bonus['id']}";
                $noteReceiver  = "新手注册奖励";
                $transferService->payerChangeMoneyAsyn = true;
                $transRes = $transferService->transferById($payerId, $userId, $bonus['money'], '注册返利', $notePay, '注册返利', $noteReceiver, "CASHBOUNSPAY|{$bonus['id']}");
                if (!$transRes) {
                    throw new \Exception('红包转账失败');
                }
                if (!$this->consume(array($bonus['id']), 0, 0, $userId)) {
                    throw new \Exception('红包消费失败');
                }
            }

            //给老用户返利
            //if ($this->rebateCashBonus($bonus['refer_mobile'], $bonus['mobile']) === false) {
            //     throw new \Exception('红包消费失败');
            //}
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            \libs\utils\PaymentApi::log("CashBonusTransError." . json_encode($bonus) . $e->getMessage());
            $GLOBALS['db']->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * 获取用户是否符合现金红包发送
     */
    public function isCashBonusSender($uid, $site_id = 1, $inviteCode = '') {

        // 分站禁掉现金红包活动
        if (APP_SITE !== 'firstp2p' || $site_id != 1) {
            return false;
        }

        if (!$inviteCode) {
            $couponService = new CouponService();
            $coupon = $couponService->getOneUserCoupon($uid);
            $inviteCode = $coupon['short_alias'];
        }

        // 判断是否在黑名单中
        if ($inviteCodeBlack = BonusConfModel::get('BONUS_BLACK_LIST')) {
            $blackList = explode(',', $inviteCodeBlack);
        }

        if ($inviteCode && !empty($blackList) && in_array($inviteCode, $blackList)) {
            return false;
        }

        $uid = intval($uid);
        if ($uid <= 0) {
            return false;
        }

        $user_info = UserModel::instance()->findBy("id='$uid'", 'create_time, group_id', array(), true);
        //if (!isset($user_info['create_time']) || intval($user_info['create_time']) >= strtotime('2015-03-31 16:00:00')) {//2015-04-01号之前注册才有资格
        //    return false;
        //}
        // 判断用户是否在组黑名单中
        // 3.0黑名单
        $groupBlackList = BonusConfModel::get('BONUS_GROUP_BLACK_LIST');
        if ($groupBlackList) {
            $groupBlackList = explode(',', $groupBlackList);
            if (!empty($groupBlackList) && in_array($user_info['group_id'], $groupBlackList)) {
                return false;
            }
        }

        // 邀请人黑名单
        $groupRebateBlackList = BonusConfModel::get('REBATE_GROUP_BLACK_LIST');
        if ($groupRebateBlackList) {
            $groupRebateBlackList = explode(',', $groupRebateBlackList);
            if (!empty($groupRebateBlackList) && in_array($user_info['group_id'], $groupRebateBlackList)) {
                return false;
            }
        }

        $tags = BonusConfModel::get('CASH_SEND_LIMIT_TAGS');//获取配置标签
        if (empty($tags)) {
            return false;
        }
        $tags = explode(',', $tags);
        $tags_service = new UserTagService;
        $user_tags = $tags_service->getTags($uid);//获取用户标签
        $const_names = array();
        foreach ($user_tags as $tag) {
            $const_names[$tag['const_name']] = $tag['const_name'];
        }
        foreach ($tags as $tag) {
            if (!isset($const_names[$tag])) {
                return false;
            }
        }
        return true;
    }

    public function getTaskById($id, $group_create_time) {
        // $key = 'task_bonus_item_'.$id;
        $key = 'new_task_bonus_item_'.$id;
        $result = \SiteApp::init()->cache->get($key);
        if (!$result) {
            $new_task_online_time = 1510099200; // 新版任务上线时间 2017-11-08
            //if ($group_create_time > $new_task_online_time) {
            if ($id < 3000) {
                $res = \core\service\marketing\MarketingService::getBonusTaskInfo($id);
                $result['use_limit_day'] = $res['groupBonusExpireDay'];
                $result['from'] = $res['from'];
            } else {
                $result = \core\dao\BonusTaskModel::instance()->find($id, '*', true);
            }
            \SiteApp::init()->cache->set($key, $result, 86400);
        }
        return $result;

    }

    /**
     * 生成消费红包
     * @param  int   $owner_uid
     * @param  float $money
     * @param  int   $use_limit_day
     * @return int
     */
    public function generateConsumeBonus($owner_uid, $money, $use_limit_day, $type = BonusModel::BONUS_HYLAIWU) {
        return \core\dao\BonusModel::instance()->insert_one($owner_uid, $money, $use_limit_day, $type);
    }

    /**
     * 生成消费红包
     * @param  int   $owner_uid
     * @param  float $money
     * @param  int   $use_limit_day
     * @return int
     */
    public function generateXslb($mobile, $money, $use_limit_day, $type = BonusModel::BONUS_YAOYIYAO) {
        $owner = UserModel::instance()->findBy('mobile=":mobile"', 'id', array(':mobile' => $mobile));
        return \core\dao\BonusModel::instance()->insert_one(intval($owner['id']), $money, $use_limit_day, $type, $mobile);
    }

    public function firstDealRebate($userId, $inviteCode, $dealLoadId, $money, $redeemDeal = false, $dealTime = 0) {

        // 通知贷投资不返
        if ($redeemDeal) {
            return true;
        }
        \libs\utils\Monitor::add('BONUS_FIRST_DEAL');

        $isInWhiteList = $this->isInFirstDealWhiteList($userId, $inviteCode);
        if ($isInWhiteList == false) {
            Logger::wLog('该投资用户不在白名单中|'.$userId."|".$inviteCode.PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
            return true;
        }

        // 检查邀请码
        $couponService = new CouponService();
        $couponInfo = $couponService->checkCoupon($inviteCode);
        // 邀请码黑名单
        $inviteCodeBlack = BonusConfModel::get('BONUS_BLACK_LIST');
        $blackList = explode(',', $inviteCodeBlack);
        if ($inviteCode && !empty($blackList) && in_array($inviteCode, $blackList)) {
            Logger::wLog('邀请人邀请码在黑名单|' .$userId. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
            return true;
        }
        // 组黑名单
        $groupIdBlack = BonusConfModel::get('BONUS_GROUP_BLACK_LIST');
        if ($groupIdBlack) {
            $groupIdBlack = explode(',', $groupIdBlack);
            if (!empty($groupIdBlack) && in_array($couponInfo['group_id'], $groupIdBlack)) {
                Logger::wLog('邀请人组在黑名单|' .$userId. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
                return true;
            }
        }

        $event = new \core\event\BonusEvent('deal', $userId, $inviteCode, array('money' => $money, 'dealTime' => $dealTime));
        $task_obj = new GTaskService();
        $task_id = $task_obj->doBackground($event, 20);
        if (!$task_id) {
            Logger::wLog('首投添加返利失败|' .$userId. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
        }
        return $task_id;
    }

    /**
     * 获取红包列表，所有红包，按照未使用-》已使用
     */
    public function get_bonus_list($user_id, $page = 0, $page_size = 10)
    {
        $page_data = array(
                'page' => ($page <= 0) ? 1 : $page,
                'page_size' => ($page_size <= 0) ? app_conf("PAGE_SIZE") : $page_size,
        );
        $list = array();
        $valid_bonus = BonusModel::instance()->get_valid_bonus($user_id);
        $count_valid_bonus = count($valid_bonus);
        if ($count_valid_bonus <= 0) {
            $list = BonusModel::instance()->get_invalid_bonus($user_id, ($page_data['page'] - 1) * $page_data['page_size'], $page_data['page_size']);
        } else {
            if ($page_data['page'] * $page_data['page_size'] <= $count_valid_bonus) {
                $list = array_slice($valid_bonus, ($page_data['page'] - 1) * $page_data['page_size'], $page_data['page_size']);
            } else {
                $valid_ids = array_map('array_shift', $valid_bonus);
                $start = 0;
                if ($page_data['page'] == ceil($count_valid_bonus / $page_data['page_size'])) {
                    $valid_bonus = array_pop(array_chunk($valid_bonus, $page_data['page_size']));
                    $limit = $page_data['page_size'] - count($valid_bonus);
                } else {
                    $valid_bonus = array();
                    $limit = $page_data['page_size'];
                    $start = ($page_data['page'] - 1) * $page_data['page_size'] - $count_valid_bonus;
                }
                $list = array_merge($valid_bonus, BonusModel::instance()->get_invalid_bonus($user_id, $start, $limit, $valid_ids));
            }
        }
        $res = array('count' => BonusModel::instance()->countViaSlave('owner_uid=":id" && created_at >= :created_at', array(':id' => $user_id, ':created_at' => strtotime(date('Y-m-d', strtotime('-30 days'))))), 'list' => $list);

        return $res;
    }

    public function getBonusListNew($user_id, $page=0, $page_size=10)
    {
        $page = ($page <= 0) ? 1 : $page;
        $size = ($page_size <= 0) ? app_conf("PAGE_SIZE") : $page_size;

        $rpc = new RpcService;
        $usableList = $rpc->getUsableListOldPage($user_id);
        $countUsable = count($usableList);

        if ($countUsable <= 0) {
            $start = ($page - 1) * $size;
            $list = $rpc->getListOldPage($user_id, $start, $size);
        } else {
            if ($page * $size <= $countUsable) {
                $list = array_slice($usableList, ($page - 1) * $size, $size);
            } else {
                $start = 0;
                if ($page == ceil($countUsable / $size)) {
                    $usableList = array_pop(array_chunk($usableList, $size));
                    $limit = $size - count($usableList);
                } else {
                    $usableList = [];
                    $limit = $size;
                    $start = ($page - 1) * $size - $countUsable;
                }
                $list = array_merge($usableList, $rpc->getListOldPage($user_id, $start, $limit));
            }
        }
        $cnt = $rpc->getCountOldPage($user_id);
        $res = array('count' => $cnt, 'list' => $list);
        return $res;
    }

    /**
     * 红包组信息缓存
     */
    public function getGroupByIdUseCache($group_id, $expire_time = 86400, $forceRefresh = false) {
        if ($group_id <= 0) {
            return false;
        }
        $key = self::CACHE_PREFIX_BONUS_GROUP . $group_id;
        $group_info = \SiteApp::init()->cache->get($key);
        if (!$group_info || $forceRefresh) {
            $group_info = BonusGroupModel::instance()->find($group_id);
            \SiteApp::init()->cache->set($key, $group_info, $expire_time);
        }

        return $group_info;
    }


    /**
     * 根据总金额和个数生成红包
     */
    public function generateO2OBonus($userId, $bonusMoney, $bonusCount, $sendLimit = 0, $bonusAccountInfo = array(),
                                     $couponId = 0, $bonusType = self::TYPE_O2O_CONFIRM, $taskId = 0) {

        $dealLoadId = 0;
        if ($couponId) {
            $acquireLog = OtoAcquireLogModel::instance()->getByGiftId($couponId, true);
        }

        if (isset($acquireLog) && $acquireLog['deal_load_id'] && in_array($acquireLog['trigger_mode'], CouponGroupEnum::$TRIGGER_DEAL_MODES)) {
            $dealLoadId = $acquireLog['deal_load_id'];
            $dealLoadInfo = DealLoadModel::instance()->find($dealLoadId, 'id,deal_id,money');
            if (empty($dealLoadInfo)) {
                throw new \Exception('投标记录不存在');
            }
        }
        // 这是红包的配置，沿用之前的return array('rate' => 0.002, 'size' => 10, 'min_count' => 10, 'max_size' => 50, 'times' => 5, 'get_limit_days' => 1, 'use_limit_days' => 1, 'max_total_money' => 2000, 'min_total_money' => 10);
        extract($this->get_config()); //获取红包配置信息
        $min_total_money = $bonusMoney;
        $min_count = $bonusCount;

        //这是红包的算法，也一样沿用return array('money' => $total_money, 'count' => $count, 'bonuses' => $bonuses);
        extract($this->bonus_mechanism($rate, $size, $max_size, $times, 0, $min_count, $max_total_money, $min_total_money, $yield, self::TYPE_O2O_CONFIRM));
        if ($money <= 0) {
            throw new \Exception('红包金额不满足条件');
        }
        $createTime = time();
        $expiredTime = $createTime + $sendLimit;
        $GLOBALS['db']->startTrans();
        try {
            $data = array(
                'user_id' => $userId,
                'deal_load_id' => $dealLoadId,
                'money' => ($bonusType == 2 ? 0 : $money),
                'deal_load_money' => $dealLoadId ? $dealLoadInfo['money'] : 0,
                'count' => $count,
                'deal_id' => $dealLoadId ? $dealLoadInfo['deal_id'] : 0,
                'bonus_type_id' => intval($bonusType),
                'task_id' => $taskId
            );

            $data['created_at'] = $createTime;
            $data['expired_at'] = $expiredTime;
            $groupId = BonusGroupModel::instance()->add_record($data, false);
            $result = BonusModel::instance()->insert_batch($userId, $groupId, $bonuses, 0, $taskId);
            $bonusAccountInfo['bonus_group_id'] = $groupId;
            $bonusAccountInfo['bonus_id'] = 0;
            $res = OtoBonusAccountModel::instance()->saveData($bonusAccountInfo);
            if (!$res) {
                throw new \Exception('O2O给邀请人返红包, 记录红包记录失败');
            }
            $GLOBALS['db']->commit();
        } catch(\Exception $e) {
            $GLOBALS['db']->rollback();
            throw new \Exception('发送红包失败');
        }

        if ($groupId && RpcService::getGroupSwitch(RpcService::GROUP_SWITCH_WRITE)) {
            $taskId = (new GTaskService())->doBackground((new AcquireBonusGroupEvent($groupId)), 20);
            Logger::info(implode('|', [__METHOD__, 'to gearman', $groupId, $taskId]));
        }
        return $groupId;
    }

    /**
     * 获取没有发送完的有效分享红包个数
     *
     * @param mixed $user_id
     * @access public
     * @return int
     */
    public function getUnsendCount($user_id)
    {
        if (RpcService::getGroupSwitch(RpcService::GROUP_SWITCH_READLIST)) {
            $cnt = RpcService::getGrabingCnt($user_id);
        } else {
            $result = BonusGroupModel::instance()->get_valid_group($user_id);
            $cnt = count($result);
        }
        return $cnt;
    }

    /*
     * 获取红包模板
     *
     * @param int $site_id
     * @access public
     * @return mix
     */
    public function getBonusTempleteBySiteId($site_id = 1)
    {
        $site_id = intval($site_id);
        $valid_site_ids = explode(',', BonusConfModel::get('BONUS_TEMPLETE_VALID_SITE_IDS'));
        if (empty($valid_site_ids) || !in_array($site_id, $valid_site_ids)) {
            return array();
        }
        $key = self::CACHE_PREFIX__BONUS_TEMPLETE.$site_id;
        $site_ids = '1';
        if ($site_id > 1) {
            $site_ids .= ",$site_id";
        }
        $now = time();
        $result = \SiteApp::init()->cache->get($key);
        if (empty($result) || (isset($result['end_time']) && $result['end_time'] < $now)) {
            $condition = sprintf("`site_id` IN (%s) && `status` = 1 && `start_time` < %s && `end_time` > %s ORDER BY `site_id` DESC LIMIT 1", $site_ids, $now, $now);
            $result = BonusTempleteModel::instance()->findByViaSlave($condition, '*');
            if ($result) {
                $static_host = app_conf('STATIC_HOST');
                $result['bg_image'] = (substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/'.$result['bg_image'];
                $result['share_icon'] = (substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/'.$result['share_icon'];
            }
            \SiteApp::init()->cache->set($key, $result, 86400);
        }
        return $result;
    }

    /**
     * 根据手机号检查互斥规则
     * @param  int $mobile 手机号
     * @return mix
     */
    public function checkMutex($mobile, $activityKey='')
    {
        if (empty($mobile)) return false;

        $condition = '`mobile`=":mobile" AND `status` = :status AND `expired_at`> :now ';
        $params = array(':mobile' => $mobile, ':status' => 1, ':now' => time());
        $res = BonusModel::instance()->findAll($condition, true, '`id`, `type`, `group_id`,`money`, `refer_mobile`, `expired_at`', $params);
        if (count($res) <= 0) return true; // 用户没有红包 可以领取
        // 互斥规则配置
        $mutexConf = BonusConfModel::getMutexConf();

        // 判断活动是否在互斥列表
        if (!empty($activityKey)) {
            $needCheck = false;
            foreach ($mutexConf as $keys) {
                if (in_array($activityKey, $keys)) {
                    $needCheck = true;
                    break;
                }
            }
            if (!$needCheck) return true;
        }

        foreach ($res as $bonus) {
            $groupID = $bonus['group_id'];
            $type = $bonus['type'];
            $bonusID = $bonus['id'];
            // 检查类型
            if (!$this->checkMutexType($type, $mutexConf['MUTEX_RULE_TYPE'])) {
                Logger::wLog("MUTEX_RULE_TYPE|{$mobile}|{$bonusID}" . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."bonus_mutex_" . date('Ymd') .'.log');
                $bonus['name'] = BonusModel::$nameConfig[$type];
                return $bonus;
            }
            // 检查groupID
            if (!$this->checkMutexGroupID($groupID, $mutexConf['MUTEX_RULE_GROUPID'], $mutexConf['MUTEX_RULE_OUT_GROUPID'])) {
                Logger::wLog("MUTEX_RULE_GROUP|{$mobile}|{$bonusID}" . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."bonus_mutex_" . date('Ymd') .'.log');
                $bonus['name'] = BonusModel::$nameConfig[$type];
                return $bonus;
            }
            // 检查规则名
            $res = $this->checkMutexName($groupID, $mutexConf['MUTEX_RULE_NAME']);
            if ($res !== true) {
                Logger::wLog("MUTEX_RULE_NAME|{$mobile}|{$bonusID}" . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."bonus_mutex_" . date('Ymd') .'.log');
                $bonus['name'] = $res;
                return $bonus;
            }
        }
        return true;

    }

    /**
     * 检查类型互斥
     * @param  int $bonusType 单个红包类型
     * @param  array $conf 检索类型互斥配置
     * @return boolean
     */
    private function checkMutexType($bonusType, $conf)
    {
        return !in_array($bonusType, $conf);
    }

    /**
     * 检验groupID互斥条件
     * @param  int $groupID
     * @param  array $conf 互斥的groupID
     * @param  array $outConf 排除互斥的groupID
     * @return boolean
     */
    private function checkMutexGroupID($groupID, $conf, $outConf)
    {
        if ($groupID == 0) return true;
        if (in_array($groupID, $conf)) return false;
        if (in_array($groupID, $outConf)) return true;
        return true;
    }

    /**
     * 检查规则名互斥条件
     * @param  int $groupID
     * @param  array $conf 规则名互斥条件
     * @return boolean
     */
    private $_mutexNameGroupIDs = null;
    private function checkMutexName($groupID, $conf, $isNew=true)
    {
        if ($groupID == 0 || count($conf) == 0) return true;
        if ($this->_mutexNameGroupIDs === null) {
            $condition = '`name` in ("' . implode('","', $conf) . '")';
            $res = BonusActivityModel::instance()->findAll($condition, true, '`group_id`, `name`');
            foreach ($res as $item) {
                $this->_mutexNameGroupIDs[$item['group_id']] = $item['name'];
            }
        }
        if (count($this->_mutexNameGroupIDs) > 0 && in_array($groupID, array_keys($this->_mutexNameGroupIDs)))
            return $this->_mutexNameGroupIDs[$groupID];
        return true;
    }

    /**
     * 获取用户领取红包的邀请码
     * @param  string $mobile
     * @return string
     */
    public function getReferCN($mobile)
    {
        if (empty($mobile)) return '';
        $condition = '`mobile`=":mobile" AND `refer_mobile` <> "" LIMIT 1';
        $params = array(':mobile' => $mobile);
        $res = BonusModel::instance()->findByViaSlave($condition, '`id`, `refer_mobile` AS `referUid`', $params);
        if (!$referUid = $res['referUid']) return '';
        $couponService = new CouponService();
        $coupon = $couponService->getOneUserCoupon($referUid);
        $inviteCode = $coupon['short_alias'];
        return $inviteCode;
    }

    public function isBlackSite($siteId = 1) {
        $list = explode(',', BonusConfModel::get('BONUS_FIRST_DEAL_BLACK_SITES'));
        Logger::wLog('首投黑名单站点|' .$siteId."|". var_export($list, true) .PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
        if ($siteId > 0 && in_array($siteId, $list)) {
            return true;
        }
        return false;
    }

    public function isDiscountBanner()
    {
        return BonusConfModel::get('DISCOUNT_BANNER_IS_SHOW');
    }

    public function isRepeatDealBlackSite($siteId = 1) {
        $list = explode(',', BonusConfModel::get('BONUS_REPEAT_DEAL_BLACK_SITES'));
        if ($siteId > 0 && in_array($siteId, $list)) {
            return true;
        }
        return false;
    }

    public function isInFirstDealWhiteList($userId, $inviteCode)
    {
        $groupIds = explode(',', BonusConfModel::get('FIRST_DEAL_WHITE_LIST_GROUPS'));
        $user = UserModel::instance()->find($userId, 'group_id', true);
        //$inviteUserId = \core\service\CouponService::hexToUserId($inviteCode);
        $couponService = new CouponService();
        $inviteUser = $couponService->checkCoupon($inviteCode);
        if (empty($inviteUser) || empty($groupIds)) {
            Logger::wLog('该投资用户不在白名单中A|'.$userId."|".$inviteCode."|".json_encode($groupIds).PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
            return false;
        }

        //$inviteUser = UserModel::instance()->find($inviteUserId, 'group_id', true);
        if ($user['group_id'] > 0 && in_array($user['group_id'], $groupIds) &&
            $inviteUser['group_id'] > 0 && in_array($inviteUser['group_id'], $groupIds)) {
            return true;
        }

        $inviteCodes = explode(',', BonusConfModel::get('FIRST_DEAL_WHITE_LIST_COUPONS'));
        if (!empty($inviteCode) && !empty($inviteCodes) && in_array($inviteCode, $inviteCodes)) {
            return true;
        }

        Logger::wLog('该投资用户不在白名单中B|'.$userId."|".$inviteCode."|".json_encode($groupIds)."|".$user['group_id']."|".$inviteUser['group_id']."|".json_encode($inviteCodes).PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
        return false;
    }

    /**
     * 获取订单使用概要
     * 返回订单对应红包剩余个数
     * @param  array  $orders 订单号数组
     * @return array
     */
    public function getUsedByOrder($orders = [])
    {
        if (empty($orders)) return [];
        array_walk($orders, 'addslashes');
        $inSQL = '"' . implode('","', $orders) . '"';
        $sql = "SELECT o.`order_id`, g.count FROM `firstp2p_bonus_buy_order` as o, `firstp2p_bonus_group` as g WHERE o.group_id = g.id AND  o.order_id IN ({$inSQL});";
        $groupInfo = $GLOBALS['db']->getAll($sql);

        $sql = "SELECT o.`order_id`, count(b.id) as cnt FROM `firstp2p_bonus_buy_order` as o, `firstp2p_bonus` as b WHERE o.group_id = b.group_id AND b.status > 0 AND o.order_id IN ({$inSQL}) GROUP BY o.`order_id`;";
        $bonusInfo = $GLOBALS['db']->getAll($sql);
        $usedBonus = [];
        foreach ($bonusInfo as $item) {
            $usedBonus[$item['order_id']] = $item['cnt'];
        }

        $res = [];
        foreach ($groupInfo as $item) {
            $used = $usedBonus[$item['order_id']] ?: 0;
            $res[$item['order_id']] = $item['count'] - $used;
        }
        return $res;
    }

    /**
     * 获取订单对应使用详情
     * @param  integer $orderID [description]
     * @return [type]           [description]
     */
    public function getUsedDetailByOrder($orderID)
    {
        if (empty($orderID)) return false;
        $orderID = addslashes($orderID);
        $sql = "SELECT b.`owner_uid`, b.`mobile` FROM `firstp2p_bonus_buy_order` as o, `firstp2p_bonus` as b WHERE o.group_id = b.group_id AND b.status > 0 AND o.order_id = '{$orderID}'";

        return $GLOBALS['db']->getAll($sql);
    }

    public function getGroupIDByOrder($orderID)
    {
        if (empty($orderID)) return false;
        $orderID = addslashes($orderID);
        $sql = "SELECT `group_id` FROM `firstp2p_bonus_buy_order` WHERE order_id = '{$orderID}'";

        $res = $GLOBALS['db']->getAll($sql);
        if (!empty($res)) return $res[0]['group_id'];
        return false;
    }

    /**
     * 更新红包
     * @param  [type] $orderID    [description]
     * @param  [type] $expireTime [description]
     * @return [type]             [description]
     */
    public function updateExpireTime($orderID, $expireTime)
    {
        if (empty($orderID)) return false;
        if (empty($expireTime)) $expireTime = time();
        return (new BonusGroupModel)->updateExpireTime($orderID, $expireTime);
    }

    /**
     * 买红包退款
     */
    public function refund($orderID)
    {

        if (empty($orderID)) return ['error' => true, 'msg' => 'param error'];
        $expireDays = intval(BonusConfModel::get('BUY_BONUS_REFUND_LIMIT_DAYS')) ?: 30 * 6;
        $db = $GLOBALS['db'];

        try {


            // 检查当前订单状态
            $sql = "SELECT * FROM `firstp2p_bonus_buy_order` WHERE `order_id` = '{$orderID}'";
            $orderInfo = $db->getAll($sql);
            $orderInfo = current($orderInfo);

            if ($orderInfo['status'] == 2) { // 已退款
                $bonus = BonusModel::instance()->find($orderInfo['refund_bonus_id']);
                return ['error' => false, 'data' => ['uid' => $bonus['owner_uid'], 'money' => $bonus['money']]];

            } elseif ($orderInfo['status'] == 1) { // 全部领取，无需退款
                return ['error' => false, 'data' => ['uid' => 0, 'money' => 0]];
            }

            // 判断是否过期
            $bonuGroup = BonusGroupModel::instance()->find($orderInfo['group_id']);
            if ($bonuGroup['expired_at'] > time()) {
                return ['error' => true, 'msg' => '红包组未过期, 不能退款'];
            }

            // 获取未使用金额
            $sql = "SELECT SUM(`money`) AS money FROM `firstp2p_bonus` WHERE `group_id` = {$orderInfo['group_id']} AND `status` = 0";
            $res = $db->getAll($sql);
            $res = current($res);
            $unusedMoney = floatval($res['money']);

            $db->startTrans();

            // 未领取金额为0，更新订单状态
            if ($unusedMoney <= 0) {
                $sql = "UPDATE `firstp2p_bonus_buy_order` SET `status` = 1 WHERE `order_id` = '{$orderID}' AND `status` = 0";
                if (!$db->query($sql)) {
                    throw new \Exception("更新状态失败");
                }
                if (!$db->affected_rows()) {
                    throw new \Exception("更新状态失败");
                }
                $db->commit();
                return ['error' => false, 'data' => ['uid' => 0, 'money' => 0]];
            }

            // 插入退款红包
            $owner = UserModel::instance()->findByViaSlave('id=":id"', 'mobile', array(':id' => $bonuGroup['user_id']));
            $mobile = $owner['mobile'] ?: '';
            if (!BonusModel::instance()->insert_one($bonuGroup['user_id'], $unusedMoney, $expireDays, BonusModel::BONUS_REFUND, $mobile)) {
                throw new \Exception("插入退款红包失败");
            }
            $bonusID = BonusModel::instance()->id;

            // 更新退款状态
            $sql = "UPDATE `firstp2p_bonus_buy_order` SET `status` = 2, `refund_bonus_id` = {$bonusID} WHERE `order_id` = '{$orderID}' AND `status` = 0";
            if (!$db->query($sql)) {
                throw new \Exception("更新状态失败");
            }
            if (!$db->affected_rows()) {
                throw new \Exception("更新状态失败");
            }

            $db->commit();

        } catch (\Exception $e) {
            $db->rollback();
            return ['error' => true, 'msg' => $e->message()];
        }

        return ['error' => false, 'data' => ['uid' => $bonuGroup['user_id'], 'money' => $unusedMoney]];
    }

    public static function getFromTypeInfo($type) {
        switch ($type) {
            case 0:
                $fromType = '好友分享';
                break;
            case 1:
                $fromType = '首投奖励';
                break;
            case 2:
                $fromType = '邀请注册奖励';
                break;
            case 20:
                $fromType = '领券奖励';
                break;
            case 26:
            case 29:
                $fromType = '用券奖励';
                break;
            case 30:
                $fromType = '买红包退款';
                break;
            case 31:
            case 32:
                $fromType = '网信生日红包';
                break;
            case 27:
            case 28:
            case 33:
            case 34:
                $fromType = '网信红包';
                break;
            case 35:
                $fromType = '邀请奖励';
                break;
            case 36:
                $fromType = '补发红包';
                break;
            default:
                $fromType = '活动奖励';
        }
        return $fromType;
    }

    /**
     * 获取红包同步列表
     */
    public function getListForSync($condition, $data, $page = 1, $pagesize = 20)
    {
        if (empty($condition)) $condition = '1=1';
        $total = BonusModel::instance()->count($condition, $data);
        $start = ($page - 1) * $pagesize;
        $condition .= " LIMIT {$start}, {$pagesize}";
        $res = BonusModel::instance()->findAll($condition, true, '*', $data);
        foreach ($res as &$item) {
            list($token, $itemType, $itemId) = BonusModel::getAcquireItemInfo($item);
            $item['token'] = $token;
            $item['itemType'] = $itemType;
            $item['itemId'] = $itemId;
        }
        return [
            'total' => $total,
            'totalPage' => ceil($total / $pagesize),
            'data' => $res,
        ];
    }

    /**
     * 获取红包同步列表
     */
    public function getGroupListForSync($condition, $data, $page = 1, $pagesize = 20)
    {
        if (empty($condition)) $condition = '1=1';
        $total = BonusGroupModel::instance()->count($condition, $data);
        $start = ($page - 1) * $pagesize;
        $condition .= " LIMIT {$start}, {$pagesize}";
        $res = BonusGroupModel::instance()->findAll($condition, true, '*', $data);
        $list = [];
        foreach ($res as $item) {
            $list[] = $this->formatGroupItemForSync($item);
        }

        return [
            'total' => $total,
            'totalPage' => ceil($total / $pagesize),
            'data' => $list,
        ];
    }

    public function formatGroupItemForSync($item)
    {
        if($item['batch_id'] > 0){
            if ($item['bonus_type_id'] == self::TYPE_BATCH) {
                $bonus_jobs_obj = new BonusJobsService();
                $bonus_jobs_info = $bonus_jobs_obj->getJobById($item['batch_id']);
                $use_limit_days = intval($bonus_jobs_info['bonus_validity']);
            } else {
                $bonus_new_user_rebate = BonusDispatchConfigModel::instance()->find($item['batch_id'], 'use_limit_day', true);
                $use_limit_days = intval($bonus_new_user_rebate['use_limit_day']);
                if ($use_limit_days <=0) {
                    $use_limit_days = 1;
                }
            }
        }else{
            if ($item['bonus_type_id'] == 3) {
                $use_limit_days = app_conf('BONUS_XQL_GET_LIMIT_DAYS');
                $use_limit_days = \SiteApp::init()->cache->get('bonus_xql_use_limit_day_'.$group_id);
                if ($use_limit_days <= 0) {
                    $use_limit_days = $this->get_config('get_limit_days');
                }
            } else {
                $use_limit_days = $this->get_config('use_limit_days');
            }
        }

        if (in_array($item['bonus_type_id'], [self::TYPE_LCS_BUY_RANDOM, self::TYPE_LCS_BUY_AVERAGE,
            self::TYPE_LCS_BUY_AVERAGE_CHECK, self::TYPE_LCS_BUY_RANDOM_CHECK]))
        {
            // $use_limit_days = 30;
            $use_limit_days = intval(BonusConfModel::get('BUY_BONUS_LIMIT_DAYS')) ?: 30;
        }
        $item['bonusExpireDay'] = $use_limit_days;

        $info = '';
        if ($item['deal_id']) {
            $info = '投资奖励';
        } else {
            switch ($item['bonus_type_id']) {
                case self::TYPE_BATCH:
                    $info = '平台奖励';
                    break;
                case self::TYPE_NEW_USER_DEAL:
                    $info = '平台奖励';
                    break;
                case self::TYPE_FIRST_DEAL_FOR_DEAL:
                    $info = '首投奖励';
                    break;
                case self::TYPE_FIRST_DEAL_FOR_INVITE:
                    $info = '邀请投资奖励';
                    break;
                case self::TYPE_REGISTER_FOR_NEW:
                    $info = '注册奖励';
                    break;
                case self::TYPE_REGISTER_FOR_INVITE:
                    $info = '邀请注册奖励';
                    break;
                case self::TYPE_BINDCARD_FOR_NEW:
                    $info = '绑卡奖励';
                    break;
                case self::TYPE_BINDCARD_FOR_INVITE:
                    $info = '邀请绑卡奖励';
                    break;
                case self::TYPE_CASH_FOR_NEW:
                case self::TYPE_CASH_NORMAL_FOR_NEW:
                    $info = '新手注册奖励';
                    break;
                case self::TYPE_CASH_FOR_INVITE:
                    $info = '邀请注册奖励';
                    break;
                case self::TYPE_LCS_BUY_RANDOM:
                case self::TYPE_LCS_BUY_AVERAGE:
                case self::TYPE_LCS_BUY_RANDOM_CHECK:
                case self::TYPE_LCS_BUY_AVERAGE_CHECK:
                    $info = '网信红包';
                    break;

                case self::TYPE_LCS_BUY_BIRTHDAY:
                    $info = '生日红包';
                    break;
                case self::TYPE_O2O_CONFIRM:
                    $info = '礼券奖励';
                    break;
                case self::TYPE_O2O_ACQUIRE_FOR_INVITER:
                    $info = '被邀请人领券返利';
                    break;
                case self::TYPE_O2O_ACQUIRE_FOR_USER:
                    $info = '领券返利';
                    break;
                default:
                    $info = '平台奖励';
                    break;
            }
        }
        $item['info'] = $info;
        $item['accountId'] = $this->getAccountIdViaGroupId($item['id']);
        if ($item['get_count'] > 0) {
            $item['status'] = self::STATUS_GRABING;
            if ($item['get_count'] >= $item['count']) {
                $item['status'] = self::STATUS_GRABED;
            }
        }
        if ($item['expired_at'] < time()) {
            $item['status'] = self::STATUS_EXPIRED;
        }
        return $item;
    }

    /**
     * 根据UID获取分表信息
     */
    public function getListForSyncByUid($uid, $condition, $data, $page = 1, $pagesize = 20)
    {
        $table = DB_PREFIX .'bonus';
        $table .= "_" . BonusUser::getTableId($uid);
        $condition .= "`owner_uid` = :uid";
        $data[':uid'] = $uid;
        $sql = "FROM {$table} WHERE {$condition}";
        $total = BonusModel::instance()->countBySql("SELECT count(*) " . $sql, $data);
        $start = ($page - 1) * $pagesize;
        $condition .= " LIMIT {$start}, {$pagesize}";
        $res = BonusModel::instance()->findAllBySql("SELECT * " . $sql, true, $data);
        return [
            'total' => $total,
            'totalPage' => ceil($total / $pagesize),
            'data' => $res,
        ];
    }

    /**
     * 获取已投列表
     */
    public function getUsedListForSync($condition, $data, $page = 1, $pagesize = 20)
    {
        if (empty($condition)) $condition = '1=1';
        $total = BonusUsedModel::instance()->count($condition, $data);
        $start = ($page - 1) * $pagesize;
        $condition .= " LIMIT {$start}, {$pagesize}";
        $res = BonusUsedModel::instance()->findAll($condition, true, '*', $data);
        return [
            'total' => $total,
            'totalPage' => ceil($total / $pagesize),
            'data' => $res,
        ];
    }

    public function syncSingleBonus($bonusId, $info = '') {
        $taskId = (new GTaskService())->doBackground((new AcquireBonusEvent($bonusId, $info)), 20);
        Logger::info("BonusDataToNewService:BonusModel::single_bonus:bonusId=$bonusId:taskId=$taskId:info=$info");
        return true;
    }

    public function getBonusInfoForSync($condition, $field = "*", $tableSuffix = '') {
        $table = DB_PREFIX .'bonus' . $tableSuffix;
        $sql = sprintf("SELECT %s FROM %s WHERE %s", $field, $table, $condition);

        return \libs\db\Db::getInstance('firstp2p', 'slave')->getAll($sql);
    }

    /**
     * 获取可用红包
     * @param  integer $userId
     * @param  boolean $isDetail 投资时需要True，获取详细红包列表与转账账户
     * @param  integer $money    投资金额
     */
    public function getUsableBonus($userId, $isDetail = false, $money = 0, $orderId = false)
    {
        // if (\core\dao\UserModel::instance()->isEnterpriseUser($userId)) {
        //     return array('money' => 0, 'bonuses' => array(), 'accountInfo' => array());
        // }
        $res = (new RpcService)->getUsableBonus($userId, $isDetail, $money, $orderId);
        // $res['money'] = self::formatMoney($res['money']);
        return $res;
    }

    public function getUsableBonusForGroup($mobile, $bonusInfo)
    {
        $userInfo = UserModel::instance()->findByViaSlave('mobile=":mobile"', 'id', array(':mobile' => $mobile));
        if ($userInfo) {
            $userId = $userInfo['id'];
            $token = $bonusInfo['id'];
            $res = (new RpcService)->getUsableBonusWithoutToken($userId, $token, $bonusInfo['money']);
            return $res ?: 0;
        } else {
            return $this->getUserSumMoney(['mobile' => $mobile, 'status' => 1]);
        }
    }

    /**
     * 消费红包
     * @param  int     $userId
     * @param  array   $records     红包详情，getUsableBonus中的bonuses字段回传
     * @param  float   $money       红包金额，getUsableBonus中的money字段回传
     * @param  int     $orderId     订单ID
     * @param  int     $useTime     时间戳
     * @param  string  $info        标的名称
     * @param  array   $accountInfo 红包账户信息，getUsableBonus中accountInfo字段回传
     */
    public function consumeBonus($userId, $records, $money, $orderId, $useTime, $info = '', $accountInfo = [], $dealType = 0)
    {
        $res = (new RpcService)->consumeBonus($userId, $records, $money, $orderId, $orderId, $dealType, $useTime, $info, $accountInfo, false);
        Logger::wLog(implode('|', [__METHOD__, json_encode($res)]));
        if ($res > 0) return true;
        return false;
    }

    /**
     * 购买黄金消费红包
     * @param  int     $userId
     * @param  array   $records     红包详情，getUsableBonus中的bonuses字段回传
     * @param  float   $money       红包金额，getUsableBonus中的money字段回传
     * @param  int     $orderId     订单ID
     * @param  int     $useTime     时间戳
     * @param  string  $info        标的名称
     * @param  array   $accountInfo 红包账户信息，getUsableBonus中accountInfo字段回传
     */
    public function consumeBonusToGold($userId, $records, $money, $orderId, $useTime, $info = '', $accountInfo = [])
    {
        $res = (new RpcService)->consumeBonus($userId, $records, $money, $orderId, $orderId, 1, $useTime, $info, $accountInfo, false);
        Logger::wLog(implode('|', [__METHOD__, json_encode($res)]));
        if ($res > 0) return true;
        return false;
    }

    public function consumeBonusForMall($userId, $records, $money, $orderId, $useTime, $info = '', $accountInfo = [])
    {
        $res = (new RpcService)->consumeBonus($userId, $records, $money, $orderId, $orderId, 2, $useTime, $info, $accountInfo, false);
        Logger::wLog(implode('|', [__METHOD__, json_encode($res)]));
        if ($res > 0) return true;
        return false;
    }

    /**
     * 红包回滚
     * @param  int $orderId 订单ID
     */
    public function rollbackBonus($orderId)
    {
        $res = (new RpcService)->rollbackBonus($orderId);
        Logger::wLog(implode('|', [__METHOD__, json_encode($res)]));
        if ($res === false) {
            return false;
        }
        if ($res['code'] == 0) return true;
        if ($res['code'] == 10000) return true; // 没有消费，无需回滚
        return false;
    }

    /**
     * 消费确认
     * @param  int $orderId 订单ID
     */
    public function consumeConfirmBonus($orderId)
    {
        $res = (new RpcService)->consumeConfirmBonus($orderId);
        Logger::wLog(implode('|', [__METHOD__, json_encode($res)]));
        if ($res === false) {
            return false;
        }
        if ($res['code'] == 0) return true;
        if ($res['code'] == 10000) return true; // 没有消费
        return false;
    }

    public function getUserBonusInfo($userId)
    {
        $info = (new RpcService)->getUserInfo($userId);
        $res = [
            'usableMoney' => self::formatMoney($info['usableMoney']),
            'usedMoney' => self::formatMoney($info['usedMoney']),
            'expiredMoney' => $info['expiredMoney'],
        ];

        if ($info['expireSoon']) {
            $res['expireSoon'] = [
                'money' => self::formatMoney($info['expireSoon']['money']),
                'expireDate' => date('Y-m-d', $info['expireSoon']['expireDate'])
            ];
        }
        return $res;
    }

    private static function formatMoney($money)
    {
        $money = number_format($money, 2);
        return str_replace('.00', '', $money);
    }

    public function getBonusLogList($userId, $page, $size)
    {
        $res = (new RpcService)->getBonusLogList($userId, $page, $size);
        $data = $res['data'];
        unset($res['data']);
        unset($res['page']);
        $list = [];
        foreach ($data as $item) {
            $status = $item['status'];
            $one = [];
            $one['createTime'] = date('Y-m-d H:i', $item['createTime']);
            $one['info'] = $item['info'];
            if ($status == 1) {
                $one['title'] = $item['info'] ?: self::getFromTypeInfo($item['itemType']);
                $one['info'] = '有效期至：' . date('Y-m-d', $item['expireTime']);
            } else if ($status == 2) {
                $one['title'] = $item['itemType'] == 1 ? '买金抵扣' : '交易抵扣';
            } else if ($status == 3) {
                $one['title'] = '过期扣除';
            }
            $one['money'] = self::formatMoney($item['money']);
            $one['status'] = $status;
            $list[] = $one;
        }
        return [
            'list' => $list,
            'page' => $res,
        ];
    }

    public function getSponsorId($bonusId, $groupId) {
        $accountId = \core\dao\OtoBonusAccountModel::instance()->getAccount(['id' => $bonusId, 'group_id' => $groupId]);
        if (!$accountId) {
            $accountId = app_conf('BONUS_BID_PAY_USER_ID');
        }
        return $accountId;
    }

    public function getAccountIdViaGroupId($groupId)
    {
        $res = \core\dao\OtoBonusAccountModel::instance()->findByViaSlave("bonus_group_id=:id", '*', array(':id' => $groupId));
        if ($res) return $res['account_id'];
        else return app_conf('BONUS_BID_PAY_USER_ID');
    }

    /**
     * 红包使用开关
     */
    public function isBonusEnable() {
        return app_conf('BONUS_DISABLED_SWITCH') ? 0 : 1;
    }
}
