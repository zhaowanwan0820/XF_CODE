<?php
/**
 * 用户预约投标服务
 *
 * @date 2016-11-14
 * @author guofeng@ucfgroup.com
 */

namespace core\service\reserve;

use core\service\BaseService;
use core\dao\reserve\UserReservationModel;
use core\dao\reserve\ReservationConfModel;
use core\dao\reserve\ReservationEntraModel;
use core\dao\reserve\ReservationDealLoadModel;
use core\dao\reserve\ReservationMoneyAssignRatioModel;
use core\dao\deal\DealModel;
use core\dao\deal\DealExtModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\deal\DealLoadModel;
use core\dao\project\DealProjectModel;
use core\dao\risk\RiskAssessmentLevelsModel;
use core\dao\risk\UserRiskAssessmentModel;
use core\dao\account\AccountModel;
use core\dao\jobs\JobsModel;
use core\data\DealData;
use libs\utils\Logger;
use libs\utils\Monitor;
use libs\sms\SmsServer;
use core\service\msgbox\MsgboxService;
use core\service\o2o\DiscountService;
use core\service\o2o\OtoTriggerRuleService;
use core\service\coupon\CouponService;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\service\account\AccountAuthService;
use core\service\reserve\ReservationDiscountService;
use core\service\reserve\ReservationDealService;
use core\service\reserve\ReservationConfService;
use core\service\reserve\ReservationEntraService;
use core\service\dealload\DealLoadService;
use core\service\deal\DealService;
use core\service\supervision\SupervisionService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\deal\P2pIdempotentService;
use core\service\bonus\BonusService;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\Idworker;
use core\tmevent\reserve\ProcPrepareEvent;
use core\tmevent\reserve\ProcBidEvent;
use core\tmevent\reserve\ProcCompleteEvent;
use core\enum\UserEnum;
use core\enum\ReserveEnum;
use core\enum\ReserveConfEnum;
use core\enum\ReserveEntraEnum;
use core\enum\DealEnum;
use core\enum\CouponEnum;
use core\enum\JobsEnum;
use core\enum\contract\ContractEnum;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractTplIdentifierEnum;

class UserReservationService extends BaseService
{
    /**
     * 缓存-预约标-用户在API里的用户信息等-Api
     * @var string
     */
    const CACHEKEY_YYB_API = 'YYB_API_%s';

    /**
     * 缓存-预约标-用户在预约时的redis锁
     * @var string
     */
    const CACHEKEY_YYB_API_LOCK = 'PH_YYB_API_LOCK_%d';

    /**
     * 缓存-预约标-用户在OPENAPI里的用户信息等-Api
     * @var string
     */
    const CACHEKEY_YYB_OPENAPI = 'PH_YYB_OPENAPI_%s';

    /**
     * 缓存-预约标-用户在预约时的redis锁
     * @var string
     */
    const CACHEKEY_YYB_OPENAPI_LOCK = 'PH_YYB_OPENAPI_LOCK_%d';

    /**
     * 缓存-预约标-预约总人数、预约投资总金额
     * @var string
     */
    const CACHEKEY_YYB_STATISTICS = 'PH_YYB_STATISTICS_INFO';

    /**
     * 产品名称
     * @var string
     */
    const PRODUCT_NAME = '随心约';

    /**
     * 预约标的日志标识-投标
     * @var string
     */
    const LOG_IDENTIFY_BID = 'RESERVATION_BID';

    /**
     * 预约标的日志标识-过期
     * @var string
     */
    const LOG_IDENTIFY_EXPIRE = 'RESERVATION_EXPIRE';

    /**
     * 预约标的日志标识-检查用户余额
     * @var string
     */
    const LOG_IDENTIFY_CHECK_BALANCE = 'RESERVATION_CHECK_BALANCE';

    /**
     * 同一投资期限，是否只能预约1次
     * @var int
     */
    const IS_ONLY_ONE = 0;

    //产品三级名称映射
    private static $productMix3NameMap = [];

    //预约投资输出编码
    const CODE_RESERVE_USER_INVEST_AMOUNT_NOT_ENOUGH = 1001; //用户可投资金额不足
    const CODE_RESERVE_LOCK_USER_FAIL = 1002; //获取用户锁失败
    const CODE_RESERVE_ENDED = 1003; //该笔预约记录已结束或过期
    const CODE_RESERVE_PROCESSING = 1004; //该笔预约正在处理中
    const CODE_RESERVE_ID_ERROR = 1005; //用户编号或预约编号错误
    const CODE_RESERVE_DEAL_TYPE_ERROR = 1006; //预约借款类型不匹配标的
    const CODE_RESERVE_DEADLINE_ERROR = 1007; //预约期限不匹配标的
    const CODE_RESERVE_USER_NOT_EXIST = 1008; //账户不存在
    const CODE_RESERVE_USER_NOT_RISK_ASSESS = 1009; //用户未进行风险评估
    const CODE_RESERVE_NOT_INVEST_ACCOUNT = 1010; //非投资账户不允许预约投
    const CODE_RESERVE_USER_NOT_AUTH = 1011; //未授权免密投资
    const CODE_RESERVE_PROJECT_RISK_CHECK_FAIL = 1012; //产品等级校验失败
    const CODE_RESERVE_USER_AGE_CHECK_FAIL = 1013; //用户年龄不符合
    const CODE_RESERVE_BID_FAIL = 1014; //预约投资失败
    const CODE_RESERVE_BID_SUCCESS = 1015; //预约投资成功
    const CODE_RESERVE_USER_EFFECT_AMOUNT_NOT_ENOUGH = 1016; //有效预约不足
    const CODE_RESERVE_USER_NOT_SEND_DISCLOSURE = 1017; //用户未发送信息披露
    const CODE_RESERVE_USER_CANNOT_INVEST_EXCLUSIVE = 1018; //用户不能投资随心约尊享
    const CODE_RESERVE_DEAL_MATCH_ERROR = 1019; //预约记录和标的不匹配

    const CODE_RESERVE_SKIP_DEAL = 2000; //跳过标的，分界线

    const CODE_RESERVE_DEAL_FULL = 2001; //标的已经满标或者等待确认中
    const CODE_RESERVE_SUPERVISION_SERVICE_DOWN = 2002; //存管已降级，跳过标的

    //过期时间
    const EXPIRE_RESERVE_USER_LOCK = 120;

    //redis键名
    const KEY_RESERVE_DEAL_LIST = 'PH_RESERVE_DEAL_LIST'; //标的队列
    const KEY_RESERVE_DEAL_SETS = 'PH_RESERVE_DEAL_SETS'; //标的集合，降低队列中标的重复

    const KEY_RESERVE_DEAL_LOCK = 'PH_RESERVE_PROCESS_LOCK_DEAL_%s';
    const KEY_RESERVE_USER_LOCK = 'PH_RESERVE_PROCESS_LOCK_USER_%s';

    //有效预约额
    const KEY_RESERVE_USER_EFFECT_AMOUNT = 'PH_RESERVE_USER_EFFECT_AMOUNT_%s_%s';

    //信息披露
    const KEY_RESERVE_SENT_DISCLOSURE_USER = 'PH_RESERVE_SENT_DISCLOSURE_USER_%s_%s'; //已发送信息披露的用户
    const KEY_RESERVE_DISCLOSURE_DEAL_LIST = 'PH_RESERVE_DISCLOSURE_DEAL_LIST_%s_%s'; //信息披露标的列表缓存
    const KEY_RESERVE_DISCLOSURE_DEAL_NAME_LIST = 'PH_RESERVE_DISCLOSURE_DEAL_NAME_LIST'; //信息披露标的名字列表缓存
    const KEY_RESERVE_DISCLOSURE_DEADLINE_LIST = 'PH_RESERVE_DISCLOSURE_DEADLINE_LIST'; //信息披露期限表


    /**
     * 根据id获取用户预约
     */
    public function getUserReservationById($reserveId)
    {
        $userReservation = UserReservationModel::instance()->find($reserveId);
        if (!empty($userReservation)) {
            $userReservation = $userReservation->getRow();
            //转换投资天数
            $reservationConfService = new ReservationConfService();
            $deadlineDays = $reservationConfService->convertToDays($userReservation['invest_deadline'], $userReservation['invest_deadline_unit']);
            $userReservation['deadline_days'] = $deadlineDays;
        }
        return $userReservation;
    }

    /**
     * 根据用户ID，获取用户[预约中+有效期内]的预约记录列表
     * @param int $accountId 账户ID
     * @return \libs\db\model
     */
    public function getUserValidReserveList($accountId, $dealTypeList = [])
    {
        if (empty($accountId)) {
            return [];
        }
        $list = array('userReserveList' => array(), 'hasExpireIds' => array());
        // 获取用户[预约中]的预约记录列表
        $userReserveList = UserReservationModel::instance()->getUserReserveList($accountId, ReserveEnum::RESERVE_STATUS_ING, 0, 0, $dealTypeList);
        if (!empty($userReserveList)) {
            $currentTimestamp = time();
            foreach ($userReserveList as $key => $item) {
                // 预约截止时间未过期
                if ($item['end_time'] < $currentTimestamp) {
                    unset($userReserveList[$key]);
                    // 记录已过期的预约记录的ID
                    $list['hasExpireIds'][] = $item['id'];
                }
            }
            $list['userReserveList'] = $userReserveList;
        }
        return $list;
    }

    /**
     * 根据用户ID，获取用户的预约记录列表
     * @param int $accountId 账户ID
     * @param int $reserveStatus 预约状态
     * @return \libs\db\model
     */
    public function getUserReserveListByPage($accountId, $reserveStatus, $page, $count, $dealTypeList)
    {
        if (empty($accountId)) {
            return [];
        }
        return UserReservationModel::instance()->getUserReserveList($accountId, $reserveStatus, $page, $count, $dealTypeList);
    }

    /**
     * 检查用户是否还可以再预约
     * @param int $accountId 账户ID
     * @return boolean
     */
    public function checkUserIsReserve($accountId)
    {
        return true;
    }

    /**
     * 创建用户预约投标记录
     * @param int $accountId 账户ID
     * @param int $reserveAmountCent 预约金额，单位为分
     * @param int $investDeadline 投资期限
     * @param int $expire 预约有效期
     * @param string $inviteCode 邀请码
     * @param int $investDeadlineUnit 投资期限单位(1:天2:月)
     * @param int $expireUnit 预约有效期单位(1:小时2:天)
     * @param array $reserveConf 预约配置信息
     * @param int $reserveReferer 预约来源(1:APP|2:M|3:Admin)
     * @param int $siteId 分站id
     * @param int $discountId 投资券id
     * @param int $dealType 借款类型
     */
    public function createUserReserve($accountId, $reserveAmountCent, $investDeadline, $expire, $inviteCode = '', $investDeadlineUnit = ReserveEnum::INVEST_DEADLINE_UNIT_DAY, $expireUnit = ReserveEnum::EXPIRE_UNIT_HOUR, $reserveConf = array(), $reserveReferer = 1, $siteId = 1, $extraInfo = [], $discountId = 0, $dealType = 0, $loantype = 0, $investRate = 0)
    {
        //检查预约入口
        $entraService = new ReservationEntraService();
        $reserveEntra = $entraService->getReserveEntra($investDeadline, $investDeadlineUnit, $dealType, $investRate, $loantype);
        if (empty($reserveEntra)) {
            return array('ret'=>false, 'errorMsg' => '未配置预约入口');
        }

        //检查预约期限
        $reservationConfService = new ReservationConfService();
        $reserveConf = $reservationConfService->getReserveInfoByType(ReserveConfEnum::TYPE_NOTICE_P2P);
        $hasReserveConf = false;
        foreach ($reserveConf['reserve_conf'] as $key => $item) {
            if ($item['expire_unit'] == $expireUnit && $item['expire_unit'] == $expireUnit) {
                $hasReserveConf = true;
            }
        }
        if (!$hasReserveConf) {
            return array('ret'=>false, 'errorMsg' => '未配置预约期限');
        }
        $userId = AccountService::getUserId($accountId);

        //检查优惠券是否能用
        if ($discountId > 0) {
            $discountInfo = DiscountService::getDiscount($discountId);
            if (empty($discountInfo)) {
                return array('ret'=>false, 'errorMsg' => '优惠券使用失败，请稍后重试');
            }

            //判断券状态和预约记录
            $hasDiscount = UserReservationModel::instance()->hasDiscount($discountId);
            if ($discountInfo['status'] != CouponEnum::STATUS_UNUSED || $hasDiscount) {
                return array('ret'=>false, 'errorMsg' => '优惠券已使用或已过期');
            }

            $reservationConfService = new ReservationConfService();
            $deadlineDays = $reservationConfService->convertToDays($investDeadline, $investDeadlineUnit); //转换预约投资天数
            $reserveAmount = bcdiv($reserveAmountCent, 100, 2); //转换元
            $extraParam = ['dealId' => '', 'money' => $reserveAmount, 'bidDayLimit' => $deadlineDays];
            $isCanUseDiscount = DiscountService::canUseDiscount($userId, $discountId, $discountInfo['discountGroupId'], DiscountService::CONSUME_TYPE_RESERVE, $extraParam);
            if (!$isCanUseDiscount) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("优惠券已使用或已过期,discountId:%s",$discountId))));
                return array('ret'=>false, 'errorMsg' => '优惠券已使用或已过期');
            }
            //额外信息，预约列表展示使用
            $extraInfo['discountType'] = $discountInfo['type'];
            $extraInfo['discountGoodsPrice'] = $discountInfo['goodsPrice'];
        }

        try {
            $db = \libs\db\Db::getInstance('firstp2p');
            $db->startTrans();
            // 创建用户预约投标记录
            $result = UserReservationModel::instance()->createUserReserve($accountId, $reserveAmountCent, $investDeadline, $expire, $investRate, $inviteCode, $investDeadlineUnit, $expireUnit, $reserveReferer, $siteId, $extraInfo, $discountId, $dealType, $reserveEntra['rate_factor'], $loantype);
            if (!$result['ret']) {
                throw new \Exception('添加用户预约失败');
            }
            $reserveId = $result['id'];

            //异步冻结投资券
            if ($discountId > 0) {
                $reservationDiscountService = new ReservationDiscountService();
                $reservationDiscountService->asyncFreezeDiscount($reserveId);
            }

            //生成预约协议
            $param = array(
                'dealId' => $reserveId,
                'borrowUserId' => 0,
                'projectId' => ContractServiceEnum::RESERVATION_PROJECT_ID,
                'dealLoadId' => 0,
                'type' => ContractServiceEnum::TYPE_RESERVATION,
                'lenderUserId' => $userId,
                'sourceType' => ContractServiceEnum::SOURCE_TYPE_RESERVATION,
                'createTime' => time(),
                'tplPrefix' =>ContractTplIdentifierEnum::RESERVATION_CONT,
                'uniqueId' => 0,
            );

            $jobsModel = new JobsModel();
            $jobsModel->priority = JobsEnum::PRIORITY_RESERVE_PROTOCOL;
            $r = $jobsModel->addJob('\core\service\contract\SendContractService::sendDtContractJob', array('requestData'=>$param));
            if ($r === false) {
                throw new \Exception("添加预约协议jobs失败");
            }

            $db->commit();
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "添加用户预约失败,error:".$e->getMessage())));
            $db->rollback();
        }

        return $result;
    }

    /**
     * 用户取消某条预约记录
     * @param int $id
     * @param int $dealId
     * @param int $accountId
     */
    public function cancelUserReserve($id, $accountId)
    {
        $updateRet = UserReservationModel::instance()->cancelUserReserveById($id, $accountId);
        if ($updateRet) {
            // 检查触发规则并发送礼券/投资券，赠送礼品
            $otoTriggerRuleService = new OtoTriggerRuleService();
            $otoTriggerRuleService->checkReservationRuleAndSendGift($id);

            // 如果用户使用的投资券，则进行检查和兑换
            $userReservation = UserReservationModel::instance()->find($id);
            if (!empty($userReservation['discount_id'])) {
                $reservationDiscountService = new ReservationDiscountService();
                $reservationDiscountService->asyncExchangeDiscount($userReservation['id']);
            }
        }
        return $updateRet;
    }

    /**
     * 系统批量取消某用户的预约记录
     * @param int $accountId
     */
    public function cancelUserReserveBatch($accountId)
    {
        return UserReservationModel::instance()->cancelUserReserveByUserId($accountId);
    }

    /**
     * push标的
     */
    public function pushDeal($dealId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        //降低标的重复处理
        if (!$this->_isExistDealLock($dealId) && $redis->sadd(self::KEY_RESERVE_DEAL_SETS, $dealId)) {
            $redis->lpush(self::KEY_RESERVE_DEAL_LIST, $dealId);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "push deal " . $dealId)));
        } else {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "标的正在处理或队列中有重复标的, dealId: " . $dealId)));
        }
        return true;
    }

    /**
     * pop标的
     */
    public function popDeal() {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $dealId = $redis->rpop(self::KEY_RESERVE_DEAL_LIST);
        if ($dealId > 0) {
            $redis->srem(self::KEY_RESERVE_DEAL_SETS, $dealId);//移除集合标的
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "pop deal " . $dealId)));
        }
        return $dealId;
    }

    /**
     * 收集标的
     */
    public function collect($dealType) {
        //随心约收集开关
        if((int)app_conf('RESERVE_SCRIPT_COLLECT_SWITCH') === 0) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "随心约收集已关闭")));
            return true;
        }

        if (!in_array($dealType, ReserveEnum::$reserveDealTypeList)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "不支持的借款类型，dealType: {$dealType}")));
            return false;
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "dealType: {$dealType}")));

        //获取预约标的
        $reserveDealService = new ReservationDealService();
        $dealList = $reserveDealService->getReservationDealList($dealType);

        //发送信息披露
        if ($dealType == DealEnum::DEAL_TYPE_GENERAL && !empty($dealList)) {
            $this->_sendDisclosure($dealType, $dealList);
        }

        foreach ($dealList as $deal) {
            $this->pushDeal($deal['id']);
        }
        return true;
    }

    /**
     * 处理标的
     */
    public function process()
    {
        //随心约处理开关
        if((int)app_conf('RESERVE_SCRIPT_PROCESS_SWITCH') === 0) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "随心约处理已关闭")));
            return true;
        }

        //从redis队列提取一个标的
        $dealId = $this->popDeal();
        if (empty($dealId)) {
            return true;
        }

        //获取标的锁
        if (!$this->_lockDeal($dealId)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "获取标的锁失败, dealId: " . $dealId)));
            return true;
        }

        try {
            $this->processOneDeal($dealId);
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "errmsg: " . $e->getMessage() . ', dealId: ' . $dealId)));
        }

        //解除标的锁
        $this->_unlockDeal($dealId);

        return true;
    }

    /**
     * 检查用户余额
     * 设置可投资金额缓存
     */
    public function checkUserBalance() {
        //开关
        if((int)app_conf('RESERVE_SCRIPT_CHECK_USER_BALANCE_SWITCH') === 0) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_CHECK_BALANCE, "系统维护中，请稍后再试！")));
            return false;
        }
        $userReservationModel = UserReservationModel::instance();
        $reservationConfService = new ReservationConfService();
        $processTime = time(); //处理时间
        $pageSize = 100; //每次处理几条预约

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_CHECK_BALANCE, sprintf('begin check reserved account balance'))));

        $reserveId = 0;
        while ($reserveList = $userReservationModel->getUserReserveListByLimit($processTime, $pageSize, $reserveId)) {
            foreach ($reserveList as $userReservation) {

                // 检查预约状态
                $userReservation = $userReservationModel->find($userReservation['id']);
                if ($userReservation['reserve_status'] != ReserveEnum::RESERVE_STATUS_ING) { //预约结束
                    continue;
                }

                $reserveId = $userReservation['id'];
                $accountId = $userReservation['user_id'];

                //获取用户
                $account = AccountService::getAccountInfoById($accountId);
                if (empty($account)) { //账户不存在
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_CHECK_BALANCE, sprintf("账户不存在, accountId: %s", $accountId))));
                    continue;
                }
                $userId = $account['user_id'];
                $account['user_info'] = UserService::getUserById($userId);

                //获取有效预约金额
                $effectReserveAmount = $this->getEffectReserveAmount($account, $userReservation);
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_CHECK_BALANCE, sprintf("设置有效预约金额缓存, accountId: %s, reserveId: %s, effectReserveAmount: %s", $accountId, $reserveId, $effectReserveAmount))));
                $this->_setUserEffectReserveAmountCache($accountId, $reserveId, $effectReserveAmount);//设置用户有效金额缓存
            }
        }

        //监控随心约队列
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'list monitor', sprintf(self::KEY_RESERVE_DEAL_LIST . ': %d, ' . self::KEY_RESERVE_DEAL_SETS . ': %d', $redis->llen(self::KEY_RESERVE_DEAL_LIST), $redis->scard(self::KEY_RESERVE_DEAL_SETS)))));

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_CHECK_BALANCE, sprintf('end check reserved account balance'))));
        return true;
    }

    /**
     * 处理单个标的
     */
    public function processOneDeal($dealId) {
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, 'begin process one deal ' . $dealId)));

        $userReservationModel = UserReservationModel::instance();

        $deal = DealModel::instance()->find($dealId);
        $dealExt = DealExtModel::instance()->getInfoByDeal($dealId, false);

        $investDeadlineArray = $this->getInvestDeadlineByDeal($deal);
        $deadline = isset($investDeadlineArray['invest_deadline']) ? $investDeadlineArray['invest_deadline'] : 0;
        $deadlineUnit = isset($investDeadlineArray['invest_deadline_unit']) ? $investDeadlineArray['invest_deadline_unit'] : 0;

        $processTime = time(); //处理时间
        $pageSize = 100; //每次处理几条预约

        $reserveId = 0;
        while ($reserveList = $userReservationModel->getUserReserveListByLimit($processTime, $pageSize, $reserveId, $deadline, $deadlineUnit, $deal['deal_type'], 0, 0, $dealExt['income_base_rate'], $deal['loantype'])) {
            foreach ($reserveList as $userReservation) {
                $reserveId = $userReservation['id'];
                $accountId = $userReservation['user_id'];

                //处理单个预约和标的
                $result = $this->processOne($reserveId, $dealId, $accountId);

                //跳过标的
                if ($result['respCode'] > self::CODE_RESERVE_SKIP_DEAL) {
                    break 2;
                }
            }
        }

        //更新标的为等待确认，由上标队列发布到线上
        $reservationDealService = new ReservationDealService();
        $reservationDealService->updateReserveDealWaiting($dealId);

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, 'end process one deal ' . $dealId)));

        return true;
    }

    /**
     * 处理单个预约和标的
     * $param float $assignMoney 指定投资金额 单位元
     * @return array
     * respCode > 2000 跳过标的
     */
    public function processOne($reserveId, $dealId, $accountId, $assignMoney = 0) {
        $isUnlockUser = false;//是否解锁用户
        try {
            //查询用户有效预约金额不足缓存
            $effectReserveAmountCache = $this->_getUserEffectReserveAmountCache($accountId, $reserveId);
            if (null !== $effectReserveAmountCache && bccomp($effectReserveAmountCache, 0, 2) <= 0) {
                throw new \Exception('用户有效预约金额缓存不足', self::CODE_RESERVE_USER_EFFECT_AMOUNT_NOT_ENOUGH);
            }

            $userReservationModel = UserReservationModel::instance();
            $reservationDealLoadModel = new ReservationDealLoadModel();
            $dealModel = DealModel::instance();
            $userRiskAssessmentModel = UserRiskAssessmentModel::instance();
            $dealLoadService = new DealLoadService();
            $dealService = new DealService();
            $reservationDiscountService = new ReservationDiscountService();
            $reservationDealService = new ReservationDealService();

            $sourceType = DealLoadModel::$SOURCE_TYPE['reservation']; //前台预约投标
            DealData::$skipPool = true;//预约投资绕过限宽门
            Monitor::add('RESERVE_MATCH_TOTAL'); //添加监控，总匹配次数

            //获取用户锁，防止多进程并发处理
            if (!$this->_lockUser($accountId)) {
                Monitor::add('RESERVE_LOCK_USER_FAIL'); //添加监控
                throw new \Exception('获取用户锁失败', self::CODE_RESERVE_LOCK_USER_FAIL);
            }
            $isUnlockUser = true;

            $userReservation = $userReservationModel->find($reserveId);
            if ($userReservation['reserve_status'] != ReserveEnum::RESERVE_STATUS_ING || $userReservation['end_time'] < time()) {
                Monitor::add('RESERVE_ENDED'); //添加监控
                throw new \Exception('该笔预约记录已结束或过期', self::CODE_RESERVE_ENDED);
            }
            $userReservation = $userReservation->getRow();

            if ($userReservation['proc_status'] != ReserveEnum::PROC_STATUS_NORMAL) {
                Monitor::add('RESERVE_PROCESSING'); //添加监控
                throw new \Exception('该笔预约正在处理中', self::CODE_RESERVE_PROCESSING);
            }

            //检查用户编号和预约编号
            if ($userReservation['user_id'] != $accountId) {
                throw new \Exception('用户编号或预约编号错误', self::CODE_RESERVE_ID_ERROR);
            }

            $deal = $reservationDealService->getReservationDealById($dealId, false);
            if (empty($deal)) {
                Monitor::add('RESERVE_DEAL_FULL'); //添加监控
                throw new \Exception('标的已经满标或者等待确认中', self::CODE_RESERVE_DEAL_FULL);
            }

            //检查预约记录是否和标的匹配
            $matchRet = $this->_checkReserveDealMatch($userReservation, $deal);
            if (!empty($matchRet['code'])) {
                throw new \Exception($matchRet['msg'], self::CODE_RESERVE_DEAL_MATCH_ERROR);
            }

            //获取用户
            $account = AccountService::getAccountInfoById($accountId);
            if (empty($account)) {
                throw new \Exception('账户不存在', self::CODE_RESERVE_USER_NOT_EXIST);
            }

            $userId = $account['user_id'];
            $account['user_info'] = UserService::getUserById($userId);
            if (empty($account['user_info'])) {
                throw new \Exception('用户不存在', self::CODE_RESERVE_USER_NOT_EXIST);
            }

            //单笔投资限额
            //投资限额校验只应用于 P2P 产品，专享类产品不做校验，企业会员不做校验。
            $userRiskData = $userRiskAssessmentModel->getURA($userId); //用户风险评估数据
            if ($deal['deal_type'] == DealEnum::DEAL_TYPE_GENERAL && !$account['user_info']['is_enterprise_user']) {
                if (empty($userRiskData)) { //未评估用户，直接跳过预约
                    throw new \Exception('用户未进行风险评估', self::CODE_RESERVE_USER_NOT_RISK_ASSESS);
                }
            }

            //检查账户用途
            $allowReserve = AccountService::allowAccountLoan($account['account_type']);
            if (!$allowReserve) {
                throw new \Exception('非投资账户不允许预约投资', self::CODE_RESERVE_NOT_INVEST_ACCOUNT);
            }

            // 检查授权
            $grantInfo = AccountAuthService::checkAccountAuth($accountId);
            if (!empty($grantInfo)) {
                throw new \Exception('未授权免密投资', self::CODE_RESERVE_USER_NOT_AUTH);
            }

            // 用户未发送信息披露
            if ( (int) app_conf('RESERVE_SEND_DISCLOSURE_SWITCH') === 1
                && $deal['deal_type'] == DealEnum::DEAL_TYPE_GENERAL
                && $this->_getSentDisclosureUserCache($accountId, $reserveId) === null ) {
                throw new \Exception(sprintf('用户未发送信息披露'), self::CODE_RESERVE_USER_NOT_SEND_DISCLOSURE);
            }

            //产品等级校验
            if (!$this->_checkProjectRisk($account, $deal, $userReservation, $userRiskData)) {
                Monitor::add('RESERVE_PROJECT_RISK_CHECK_FAIL'); //添加监控，产品风险等级校验失败
                throw new \Exception('产品等级校验失败', self::CODE_RESERVE_PROJECT_RISK_CHECK_FAIL);
            }

            //获取用户可投资金额
            $reserveMinMoney = $this->getReserveMinMoney($userReservation);//预约最低金额
            $investAmount = $this->getInvestAmount($account, $userReservation, false, $deal, $assignMoney);
            if (bccomp($investAmount, 0, 2) <= 0 || bccomp($investAmount, $reserveMinMoney, 2) == -1 || bccomp($investAmount, $deal['min_loan_money'], 2) === -1) {
                Monitor::add('RESERVE_USER_INVEST_AMOUNT_NOT_ENOUGH'); //添加监控，可投资金额不足
                throw new \Exception('用户可投资金额不足', self::CODE_RESERVE_USER_INVEST_AMOUNT_NOT_ENOUGH);
            }

            //用户绑定邀请码
            $couponId = $this->_getUserCouponId($account, $deal);

            //标信息
            $dealInfo = $dealService->getDeal($deal['id'], true);

            // 投标默认站点，读取预约站点，默认主站
            $siteId = !empty($userReservation['site_id']) ? $userReservation['site_id'] : 100;

            //存管降级不做投资
            if ($deal['report_status'] == 1 && SupervisionService::isServiceDown()) {
                throw new \Exception('存管已降级，跳过标的', self::CODE_RESERVE_SUPERVISION_SERVICE_DOWN);
            }

            //用户年龄检查
            $ageCheck = $dealService->allowedBidByCheckAge($account);
            if($ageCheck['error'] == true){
                Monitor::add('RESERVE_USER_AGE_CHECK_FAIL'); //添加监控，用户年龄不符合
                throw new \Exception('用户年龄不符合, msg: ' . $ageCheck['msg'], self::CODE_RESERVE_USER_AGE_CHECK_FAIL);
            }

            //幂等检查
            $dealLoadService = new DealLoadService();
            $optionParams = [
                'reserveInfo' => $userReservation,
                'canUseBonus' => BonusService::isBonusEnable(),
            ];
            $beginTime = microtime(true);
            $res = $dealLoadService->bid($userId, $dealInfo, $investAmount, $couponId, $sourceType, $siteId, '', 1, $optionParams);
            $endTime = microtime(true);
            if (empty($res) || !empty($res['error'])) {
                Monitor::add('RESERVE_BID_FAIL');
                throw new \Exception('预约投资失败', self::CODE_RESERVE_BID_FAIL);
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'RESERVATION_COST', sprintf('bid, dealId: %s, userId: %s, investAmount: %s, res: %s, cost: %s', $dealInfo['id'], $userId, $investAmount, json_encode($res), round($endTime - $beginTime, 5)))));

            //投资完成之后，刷新预约记录
            $userReservation = $userReservationModel->find($userReservation['id']);

            //发送站内信
            $this->_sendMessage($account, $userReservation, $investAmount);

            // 预约匹配完成的时候，再进行检查、赠送礼品
            if ($userReservation['reserve_status'] == ReserveEnum::RESERVE_STATUS_END) {
                // 检查触发规则并发送礼券/投资券，赠送礼品
                $otoTriggerRuleService = new OtoTriggerRuleService();
                $otoTriggerRuleService->checkReservationRuleAndSendGift($userReservation['id'], $deal['id']);
                // 如果用户使用的投资券，则进行检查和兑换
                if (!empty($userReservation['discount_id'])) {
                    $reservationDiscountService->asyncExchangeDiscount($userReservation['id']);
                }
            }

            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf('预约投资成功, accountId: %s, dealId: %s, reserveId: %s, investAmount: %s', $accountId, $deal['id'], $userReservation['id'], $investAmount))));
            //添加监控，预约投资成功
            Monitor::add('RESERVE_BID_SUCCESS');

            //清除用户有效预约金额缓存
            $this->_clearUserEffectReserveAmountCache($accountId, $reserveId);

            //解锁用户
            $this->_unlockUser($accountId);

            return ['respCode' => self::CODE_RESERVE_BID_SUCCESS, 'respMsg' => '预约投资成功'];

        } catch (\Exception $e) {
            //解锁用户
            $isUnlockUser && $this->_unlockUser($accountId);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf("reserveId: %s, dealId: %s, accountId: %s, assignMoney: %s, respCode: %s, respMsg: %s", $reserveId, $dealId, $accountId, $assignMoney, $e->getCode(), $e->getMessage()))));
            return ['respCode' => $e->getCode(), 'respMsg' => $e->getMessage()];
        }
    }

    /**
     * 用户绑定邀请码
     */
    private function _getUserCouponId($account, $deal) {
        $couponId = null;
        $couponLatest = CouponService::getCouponLatest($account['user_id']);
        if (empty($couponLatest)) {
            $couponLatest['is_fixed'] = true;
        }
        if ($couponLatest['is_fixed'] === true) {
            $couponId = isset($couponLatest['coupon']['short_alias']) ? $couponLatest['coupon']['short_alias']:"";
        }
        return $couponId;
    }

    /**
     * 根据标的获取投资期限
     */
    public function getInvestDeadlineByDeal($deal) {
        $result = [];
        if (empty($deal)) {
            return $result;
        }
        $result['invest_deadline'] = $deal['repay_time'];
        if ($deal['loantype'] == 5) { //天
            $result['invest_deadline_unit'] = ReserveEnum::INVEST_DEADLINE_UNIT_DAY;
        } else { //月
            $result['invest_deadline_unit'] = ReserveEnum::INVEST_DEADLINE_UNIT_MONTH;
        }

        return $result;
    }

    /**
     * 检查预约记录和标的匹配
     */
    private function _checkReserveDealMatch($userReservation, $deal) {
        try {
            if (empty($userReservation) || empty($deal)) {
                throw new \Exception('缺少参数', 1);
            }

            if ($deal['loantype'] == 5) { //按天匹配
                if ($userReservation['invest_deadline_unit'] != ReserveEnum::INVEST_DEADLINE_UNIT_DAY || $deal['repay_time'] != $userReservation['invest_deadline']) {
                    throw new \Exception('投资期限不匹配', 2);
                }
            } else { //按月匹配
                if ($userReservation['invest_deadline_unit'] != ReserveEnum::INVEST_DEADLINE_UNIT_MONTH || $deal['repay_time'] != $userReservation['invest_deadline']) {
                    throw new \Exception('投资期限不匹配', 2);
                }
            }

            if ($userReservation['deal_type'] != $deal['deal_type']) {
                throw new \Exception('贷款类型不匹配', 3);
            }

            if (!empty($userReservation['loantype']) && $userReservation['loantype'] != $deal['loantype']) {
                throw new \Exception('还款类型不匹配', 4);
            }

            if ($userReservation['invest_rate'] > 0 && bccomp($userReservation['invest_rate'], $deal['income_base_rate'], 2) !== 0) {
                throw new \Exception('投资年化利率不匹配', 5);
            }
        } catch (\Exception $e) {
            return ['code' => $e->getCode(), 'msg' => $e->getMessage()];
        }
        return ['code' => 0, 'msg' => '成功'];

    }
    /**
     * 加用户锁
     */
    private function _lockUser($accountId) {
        $lockKey = sprintf(self::KEY_RESERVE_USER_LOCK, $accountId);
        $redis = \SiteApp::init()->dataCache;
        $redisState = $redis->setNx($lockKey, 1, self::EXPIRE_RESERVE_USER_LOCK);
        $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
        return $state === 'OK' ? true : false;
    }

    /**
     * 释放用户锁
     */
    private function _unlockUser($accountId) {
        $lockKey = sprintf(self::KEY_RESERVE_USER_LOCK, $accountId);
        $redis = \SiteApp::init()->dataCache;
        return $redis->remove($lockKey);
    }

    /**
     * 加标的锁
     */
    private function _lockDeal($dealId) {
        $lockKey = sprintf(self::KEY_RESERVE_DEAL_LOCK, $dealId);
        $redis = \SiteApp::init()->dataCache;
        return $redis->getRedisInstance()->setNx($lockKey, 1);
    }

    /**
     * 释放标的锁
     */
    private function _unlockDeal($dealId) {
        $lockKey = sprintf(self::KEY_RESERVE_DEAL_LOCK, $dealId);
        $redis = \SiteApp::init()->dataCache;
        return $redis->remove($lockKey);
    }

    /**
     * 检查标的锁是否存在
     */
    private function _isExistDealLock($dealId) {
        $lockKey = sprintf(self::KEY_RESERVE_DEAL_LOCK, $dealId);
        $redis = \SiteApp::init()->dataCache;
        return $redis->getRedisInstance()->exists($lockKey);
    }


    /**
     * 获取单笔投资限额
     */
    private function _getLimitMoney($account, $deal) {
        $limitMoney = 0; //0 没有限制
        //投资限额校验只应用于 P2P 产品，专享类产品不做校验，企业会员不做校验。
        if (!empty($deal) && $deal['deal_type'] == DealEnum::DEAL_TYPE_GENERAL && !$account['user_info']['is_enterprise_user']) {
            $userRiskAssessmentModel = UserRiskAssessmentModel::instance();
            $userRiskData = $userRiskAssessmentModel->getURA($account['id']); //用户风险评估数据
            $riskLevels = RiskAssessmentLevelsModel::instance()->getEnabledLevels();//风险评估等级表
            $limitMoney = $userRiskAssessmentModel->getLimitMoney($riskLevels, $userRiskData);
        }
        return $limitMoney;
    }

    /**
     * 获取用户余额信息
     */
    private function _getUserMoneyInfo($account, $dealType)
    {
        //获取账户余额
        $moneyInfo = AccountService::getAccountMoneyInfo($account['id']);

        //取最小值，防止资产中心扣负
        $minMoney = min($moneyInfo['bankMoney'], $moneyInfo['accountMoney']); // 存管

        //计算总余额，加上红包
        $isBonusEnable = BonusService::isBonusEnable();
        $totalBalance = $isBonusEnable ? bcadd($minMoney, $moneyInfo['bonusMoney'], 2) : $minMoney;

        $result = [
            'totalBalance' => $totalBalance,
            'localBalance' => $moneyInfo['accountMoney'],
        ];
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("accountId: %s, dealType: %s, moneyInfo:%s, isBonusEnable: %s", $account['id'], $dealType, json_encode($result), $isBonusEnable))));
        return $result;
    }

    /**
     * 处理最后一口预约
     */
    private function _procLastOneReserve($userReservation, $amount) {
        $needReserveAmount = bcdiv($userReservation['reserve_amount'] - $userReservation['invest_amount'], 100, 2);
        //最后一口预约问题, 当剩余预约小于最低投资额时，预留出这部分金额，确保下次完成预约
        $reserveMinMoney = $this->getReserveMinMoney($userReservation);
        //剩下的预约金额
        $needReserveAmountIfInvest = bcsub($needReserveAmount, $amount, 2);//如果投资，剩下的预约金额
        if (bccomp($needReserveAmountIfInvest, 0, 2) == 1 && bccomp($needReserveAmountIfInvest, $reserveMinMoney, 2) == -1 && bccomp($amount, $reserveMinMoney, 2) != -1) {
            $amount = bcsub($amount, bcsub($reserveMinMoney, $needReserveAmountIfInvest, 2), 2);
        }
        return $amount;
    }

    /**
     * 处理最后一口标
     */
    private function _procLastOneDeal($deal, $amount) {
        $needDealMoney = bcsub($deal['borrow_amount'], $deal['load_money'], 2); //标剩余金额
        $minLeft = bcsub($needDealMoney, $amount, 2);
        if (bccomp($minLeft, 0, 2) === 1 && bccomp($minLeft, $deal['min_loan_money'], 2) === -1) {
            $amount = bcsub($amount, bcsub($deal['min_loan_money'], $minLeft, 2), 2);
        }
        return $amount;
    }

    /**
     * 获取有效预约金额
     * $readCache 读取缓存
     */
    public function getEffectReserveAmount($account, $userReservation, $readCache = false) {
        //读取缓存
        if ($readCache) {
            $effectReserveAmountCache = $this->_getUserEffectReserveAmountCache($account['id'], $userReservation['id']);
            if ($effectReserveAmountCache !== null) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf('accountId: %s, reserveId: %s, readCache: %s, effectReserveAmount: %s', $account['id'], $userReservation['id'], $readCache, $effectReserveAmountCache))));
                return $effectReserveAmountCache;
            }
        }

        //获取账户余额
        $dealType = $userReservation['deal_type'];
        $moneyInfo = $this->_getUserMoneyInfo($account, $dealType);
        $balance = $moneyInfo['totalBalance']; //账户总余额
        $localBalance = $moneyInfo['localBalance']; //本地余额

        //余额不足时，发送短信提醒用户充值
        $reserveMinMoney = $this->getReserveMinMoney($userReservation);
        if (bccomp($balance, 0, 2) <= 0 || bccomp($balance, $reserveMinMoney, 2) == -1) {
            //网贷类型还要检查资产中心余额，防止存管查询接口超时导致
            if ($dealType != DealEnum::DEAL_TYPE_GENERAL || bccomp($localBalance, 0, 2) <= 0 || bccomp($localBalance, $reserveMinMoney, 2) == -1) {
                $this->_sendChargeRemindSms($userReservation, $account); //发送余额不足短信
            }
        }

        //剩下的预约金额
        $needReserveAmount = bcdiv($userReservation['reserve_amount'] - $userReservation['invest_amount'], 100, 2);

        //计算有效预约金额
        $effectReserveAmount = min($balance, $needReserveAmount);// 用户余额、剩余预约金额选最小值。

        //处理最后一口预约
        $effectReserveAmount = $this->_procLastOneReserve($userReservation, $effectReserveAmount);

        //小于最低预约投资额
        if (bccomp($effectReserveAmount, $reserveMinMoney, 2) === -1) {
            $effectReserveAmount = 0;
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf('accountId: %s, reserveId: %s, needReserveAmount: %s, reserveDealType: %s, reserveMinMoney: %s, effectReserveAmount: %s', $account['id'], $userReservation['id'], $needReserveAmount, $userReservation['deal_type'], $reserveMinMoney, $effectReserveAmount))));
        return $effectReserveAmount;
    }

    /**
     * 获取用户可投资金额
     */
    public function getInvestAmount($account, $userReservation, $readCache, $deal, $assignMoney = 0) {
        $investAmount = 0;
        //剩下的预约金额 小于 标的最低起投金额
        $needReserveAmount = bcdiv($userReservation['reserve_amount'] - $userReservation['invest_amount'], 100, 2);
        if (bccomp($needReserveAmount, $deal['min_loan_money'], 2) === -1) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf('accountId: %s, reserveId: %s, dealId: %s, needReserveAmount: %s, minLoanMoney: %s, investAmount: %s', $account['id'], $userReservation['id'], $deal['id'], $needReserveAmount, $deal['min_loan_money'], $investAmount))));
            return $investAmount;
        }

        //获取有效预约额
        $effectReserveAmount = $this->getEffectReserveAmount($account, $userReservation, $readCache);

        //与标的剩余金额计算最小值
        $needDealMoney = bcsub($deal['borrow_amount'], $deal['load_money'], 2); //标剩余金额
        $investAmount = min($effectReserveAmount, $needDealMoney); //计算最小值
        //标的最高投资金额
        if (bccomp($deal['max_loan_money'], 0, 2) === 1) {
            $investAmount = min($investAmount, $deal['max_loan_money']); //计算最小值
        }

        //单笔投资限额
        $limitMoney = $this->_getLimitMoney($account, $deal);
        if (bccomp($limitMoney, 0) == 1) {
            $investAmount = min($investAmount, $limitMoney);
        }

        //处理最后一口标
        $investAmount = $this->_procLastOneDeal($deal, $investAmount);

        //处理最后一口预约
        $investAmount = $this->_procLastOneReserve($userReservation, $investAmount);

        //如果指定投资金额
        if (bccomp($assignMoney, 0, 2) === 1) {
            $investAmount = bccomp($investAmount, $assignMoney, 2) >= 0 ? $assignMoney : 0;
        }

        //小于最低预约投资额
        $reserveMinMoney = $this->getReserveMinMoney($userReservation);
        if (bccomp($effectReserveAmount, $reserveMinMoney, 2) === -1) {
            $effectReserveAmount = 0;
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf('accountId: %s, reserveId: %s, dealId: %s, needDealMoney: %s, minLoanMoney: %s, maxLoanMoney: %s, investAmount: %s', $account['id'], $userReservation['id'], $deal['id'], $needDealMoney, $deal['min_loan_money'], $deal['max_loan_money'], $investAmount))));
        return $investAmount;
    }

    /**
     * 产品等级校验
     */
    private function _checkProjectRisk($account, $deal, $userReservation, $userRiskData) {
        $dealProjectRiskService = new DealProjectRiskAssessmentService();
        //开关
        if (DealProjectRiskAssessmentService::$is_reserve_check_enable != DealProjectRiskAssessmentService::CHECK_ENABLE) {
            return true;
        }

        $dealProjectModel = new DealProjectModel();
        $reservationConfigService = new ReservationConfService();

        //产品三级名称
        $dealProjectInfo = $dealProjectModel->findByViaSlave('id=:project_id','product_mix_3',array(':project_id'=> $deal['project_id']));
        $productMix3Name = !empty($dealProjectInfo['product_mix_3']) ? $dealProjectInfo['product_mix_3'] : '';

        //等级分数
        $projectScore = $reservationConfigService->getScoreByDeadLine(
            $userReservation['invest_deadline'],
            $userReservation['invest_deadline_unit'],
            $userReservation['deal_type'],
            $userReservation['invest_rate'],
            $userReservation['loantype'],
            $productMix3Name
        );
        if ($projectScore == false) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf("项目无产品等级, accountId: %s, dealId: %s, projectId: %s, productMix3Name: %s, projectScore: %s", $account['id'], $deal['id'], $deal['project_id'], $productMix3Name, $projectScore))));
            return false;
        }

        //校验结果
        $userRiskData = $userRiskData ? (is_object($userRiskData) ? $userRiskData->getRow() : $userRiskData) : [];
        $projectRiskRet = $dealProjectRiskService->checkReservationRisk($account['user_id'], $projectScore, true, $userRiskData);
        if (empty($projectRiskRet['result'])) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf("用户评级低于三级产品等级, accountId: %s, dealId: %s, projectId: %s, productMix3Name: %s, projectScore: %s, projectRiskRet: %s", $account['id'], $deal['id'], $deal['project_id'], $productMix3Name, $projectScore, json_encode($projectRiskRet)))));
            return false;
        }
        return true;
    }

    /**
     * 发送站内信
     */
    private function _sendMessage($account, $userReservation, $investAmount) {
        //主站才发送站内信
        if ($userReservation['site_id'] != 1) {
            return true;
        }
        $msgbox = new MsgboxService();
        $userId = $account['user_id'];
        $needReserveAmount = bcdiv($userReservation['reserve_amount'] - $userReservation['invest_amount'], 100, 2); //剩余预约金额
        $title = '预约交易成功';
        $investName = $userReservation['deal_type'] == DealEnum::DEAL_TYPE_GENERAL ? '出借' : '投资';
        if ($userReservation['reserve_status'] == ReserveEnum::RESERVE_STATUS_END) {//完成预约投资
            $content = sprintf('您在%s通过%s预约的标的，预约金额%s元，本次成功%s金额%s元，%s期限%s%s。',
                date('Y-m-d H:i:s', $userReservation['start_time']),
                self::PRODUCT_NAME,
                bcdiv($userReservation['reserve_amount'], 100, 2),
                $investName,
                $investAmount,
                $investName,
                $userReservation['invest_deadline'],
                ReserveEnum::$investDeadLineUnitConfig[$userReservation['invest_deadline_unit']]);
        } else {
            $content = sprintf('您在%s通过%s预约的标的，预约金额%s元，本次成功%s金额%s元，%s期限%s%s，剩余预约金额%s元，我们会继续帮您预约。',
                date('Y-m-d H:i:s', $userReservation['start_time']),
                self::PRODUCT_NAME,
                bcdiv($userReservation['reserve_amount'], 100, 2),
                $investName,
                $investAmount,
                $investName,
                $userReservation['invest_deadline'],
                ReserveEnum::$investDeadLineUnitConfig[$userReservation['invest_deadline_unit']],
                $needReserveAmount);
        }
        return $msgbox->create($userId, 41, $title, $content);
    }

    /**
     * 设置用户有效预约金额缓存
     */
    private function _setUserEffectReserveAmountCache($accountId, $reserveId, $effectReserveAmount) {
        $key = sprintf(self::KEY_RESERVE_USER_EFFECT_AMOUNT, $accountId, $reserveId);
        $expire = mt_rand(1620, 1800);//打乱缓存过期时间
        $redis = \SiteApp::init()->dataCache;
        $value = serialize($effectReserveAmount);
        return $redis->getRedisInstance()->setex($key, intval($expire), $value);
    }

    /**
     * 获取用户有效预约金额缓存
     */
    private function _getUserEffectReserveAmountCache($accountId, $reserveId) {
        $key = sprintf(self::KEY_RESERVE_USER_EFFECT_AMOUNT, $accountId, $reserveId);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $value = $redis->get($key);
        if (null !== $value) {
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * 清理用户有效预约金额缓存
     */
    private function _clearUserEffectReserveAmountCache($accountId, $reserveId) {
        $key = sprintf(self::KEY_RESERVE_USER_EFFECT_AMOUNT, $accountId, $reserveId);
        $redis = \SiteApp::init()->dataCache;
        return $redis->remove($key);
    }

    /**
     * 发送充值提醒短信
     */
    private function _sendChargeRemindSms($userReservation, $account)
    {
        $sendSmsKey = sprintf('RESERVE_SEND_CHARGE_REMIND_SMS_%d', $userReservation['id']);
        $expire = max($userReservation['end_time'] - $userReservation['start_time'], 60);
        $redis = \SiteApp::init()->dataCache;
        $redisState = $redis->setNx($sendSmsKey, 1, $expire);
        $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
        if ($state === 'OK') {
            $tpl = 'TPL_SMS_RESERVE_CHARGE_REMIND';

            $userId = $account['user_id'];
            $mobile = $account['user_info']['is_enterprise_user'] ? 'enterprise' : $account['user_info']['mobile'];
            $smsContent = [
                'accountName' => $userReservation['deal_type'] == DealEnum::DEAL_TYPE_GENERAL ? '网贷P2P账户' : '网信账户',
                'investName' => $userReservation['deal_type'] == DealEnum::DEAL_TYPE_GENERAL ? '出借' : '投资',
                'platformName' => $userReservation['site_id'] == 100 ? '网信普惠' : '网信',
            ];
            SmsServer::instance()->send($mobile, $tpl, $smsContent, $userId, $userReservation['site_id']);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf('send charge remind sms success, accountId: %s, reserveId: %s', $account['id'], $userReservation['id']))));
        }
    }

    /**
     * 用户预约过期处理
     * @param int $processTime
     * @param int $pageSize 每批执行的记录数
     */
    public function expire($processTime, $pageSize)
    {
        //开关
        if((int)app_conf('RESERVE_SCRIPT_EXPIRE_SWITCH') === 0) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_EXPIRE, "系统维护中，请稍后再试！")));
            return false;
        }
        $userReservationModel = UserReservationModel::instance();
        $msgbox = new MsgboxService();
        $reservationDiscountService = new ReservationDiscountService();
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_EXPIRE, 'begin process expired account reservations')));
        $reserveId = 0;
        while ($reserveList = $userReservationModel->getExpiredUserReserveListByLimit($processTime, $pageSize, $reserveId)) {
            foreach ($reserveList as $userReservation) {
                $reserveId = $userReservation['id'];
                $accountId = $userReservation['user_id'];
                //检查预约状态，预约结束无需处理
                if ($userReservation['reserve_status'] != ReserveEnum::RESERVE_STATUS_ING) {
                    continue;
                }
                try {
                    //将用户预约设置为过期
                    $updateUserReservation = $userReservationModel->cancelUserReserveById($reserveId, $accountId);
                    if ($updateUserReservation <= 0) {
                        throw new \Exception(sprintf('expireUserReservation, reserveId: %s, accountId: %s', $reserveId, $accountId));
                    }

                    $account = AccountService::getAccountInfoById($accountId);

                    //发送站内信
                    $title = '预约到期';
                    $investName = $userReservation['deal_type'] == DealEnum::DEAL_TYPE_GENERAL ? '出借' : '投资';
                    $content = sprintf('您在%s通过%s预约的标的，预约金额%s元，预约%s期限%s%s，已到期，实际%s金额%s元。',
                        date('Y-m-d H:i:s', $userReservation['start_time']),
                        self::PRODUCT_NAME,
                        bcdiv($userReservation['reserve_amount'], 100, 2),
                        $investName,
                        $userReservation['invest_deadline'],
                        ReserveEnum::$investDeadLineUnitConfig[$userReservation['invest_deadline_unit']],
                        $investName,
                        bcdiv($userReservation['invest_amount'], 100, 2));
                    $msgbox->create($account['user_id'], 41, $title, $content);

                    // 检查触发规则并发送礼券/投资券，赠送礼品
                    $otoTriggerRuleService = new OtoTriggerRuleService();
                    $otoTriggerRuleService->checkReservationRuleAndSendGift($reserveId);

                    // 如果用户使用的投资券，则进行检查和兑换
                    if (!empty($userReservation['discount_id'])) {
                        $reservationDiscountService->asyncExchangeDiscount($userReservation['id']);
                    }

                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_EXPIRE, sprintf("预约过期成功, reserveId: %s, accountId: %s", $reserveId, $accountId))));
               } catch (\Exception $e) {
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_EXPIRE, sprintf("预约过期失败, errmsg: %s", $e->getMessage()))));
               }
            }
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_EXPIRE, 'end process expired account reservations')));
    }

    /**
     * 获取当日预约总人数、总的预约投资金额
     * @param boolean $readDb 是否读取数据库
     */
    public function getReservationStatisticsForCard($deadline = 21, $unit = 1, $readDb = false) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $key = sprintf(self::CACHEKEY_YYB_STATISTICS."_%s_%s", intval($deadline), intval($unit));
        if (!$readDb) {
            $reservationStatisticsCard = $redis->hGetAll($key);
            if (!empty($reservationStatisticsCard)) {
                return $reservationStatisticsCard;
            }
        }

        $reservationStatisticsCard = UserReservationModel::instance()->getReservationStatisticsForCard($deadline, $unit);
        $redis->hMset($key, $reservationStatisticsCard);
        // 获取预约标在app卡片的数据缓存时间
        $appCardExpire = intval(app_conf('YYB_APP_CARD_EXPIRE'));
        $redis->expire($key, (!empty($appCardExpire) ? $appCardExpire : 600));
        return $reservationStatisticsCard;
    }

    /**
     * 获取当日预约总人数、总的预约投资金额
     * @param boolean $readDb 是否读取数据库
     */
    public function getReserveStats($investLine, $investUnit, $dealType, $investRate, $loantype, $readDb = false) {
        if (empty($investLine) || empty($investUnit)) {
            return array();
        }
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $key = sprintf(self::CACHEKEY_YYB_STATISTICS."_%s", implode('_', func_get_args()));
        if (!$readDb) {
            $reserveStats = $redis->hGetAll($key);
            if (!empty($reserveStats)) {
                return $reserveStats;
            }
        }

        $reserveStats = UserReservationModel::instance()->getReserveStats($investLine, $investUnit, $dealType, $investRate, $loantype);
        $redis->hMset($key, $reserveStats);
        // 获取预约标在app卡片的数据缓存时间
        $appCardExpire = intval(app_conf('YYB_APP_CARD_EXPIRE'));
        $redis->expire($key, (!empty($appCardExpire) ? $appCardExpire : 600));
        return $reserveStats;
    }

    /**
     * 查询预约信息(针对掌众侧)
     * @param array $investDeadlineArray
     * $investDeadlineArray = array(array('invest_deadline' => 21,'invest_deadline_unit' => 1));
     * @return array
     */
    public function getReservationInfo($investDeadlineArray) {
        if(empty($investDeadlineArray)){
            return false;
        }
        $userReservationModel = UserReservationModel::instance();
        foreach ($investDeadlineArray as $investConf) {
            $key =  $investConf['invest_deadline'].ReserveEnum::$investDeadLineUnitConfig[$investConf['invest_deadline_unit']];
            $res = $userReservationModel->getReservationInfo($investConf['invest_deadline'],$investConf['invest_deadline_unit']);
            if (empty($res['amount'])) {
                $res['amount'] = '0.00';
            }
            $ret[$key] = $res;
        }
        return $ret;
    }

    /**
     * 处理准备，更新处理状态
     */
    public function processPrepare($reserveId, $accountId, $investAmount, $orderId) {
        $userReservationModel = UserReservationModel::instance();
        //幂等检查
        $userReservation = $userReservationModel->find($reserveId);
        if (!empty($orderId) && !empty($userReservation['proc_id']) && $userReservation['proc_id'] == $orderId
            && $userReservation['proc_status'] == ReserveEnum::PROC_STATUS_ING) {
            return true;
        }

        $investAmountFen = bcmul($investAmount, 100);
        $result = $userReservationModel->updateProcessing($reserveId, $accountId, $investAmountFen, $orderId);
        if (!$result) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf("RESERVATION_BID_EXCEPTION, 更新处理中失败, reserveId: %s, accountId: %d, investAmount: %d", $reserveId, $accountId, $investAmount))));
        }
        return $result;
    }

    /**
     * 恢复处理状态
     */
    public function restoreProcStatus($reserveId, $accountId, $orderId) {
        $userReservationModel = UserReservationModel::instance();
        //幂等检查
        $userReservation = $userReservationModel->find($reserveId);
        if ($userReservation['proc_status'] == ReserveEnum::PROC_STATUS_NORMAL) {
            return true;
        }
        $result = $userReservationModel->restoreProcStatus($reserveId, $accountId, $orderId);
        if (!$result) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf("RESERVATION_BID_EXCEPTION, 恢复处理状态失败, reserveId: %s, accountId: %d", $reserveId, $accountId))));
        }
        return $result;
    }

    /**
     * 处理完成
     */
    public function processComplete($reserveId, $accountId, $dealId, $investAmount, $orderId) {

        $dealModel = DealModel::instance();
        $userReservationModel = UserReservationModel::instance();
        $reservationDealLoadModel = ReservationDealLoadModel::instance();
        $p2pIdempotentService = new P2pIdempotentService();

        //查询loadId
        $orderInfo = $p2pIdempotentService->getInfoByOrderId($orderId);
        if (empty($orderInfo)) {
            return false;
        }
        $loadId = $orderInfo['load_id'];

        //幂等检查
        $isExist = $reservationDealLoadModel->isExistRelation($reserveId, $loadId);
        if ($isExist) {
            return true;
        }

        try {
            //开启事务
            $db = \libs\db\Db::getInstance('firstp2p');
            $db->startTrans();

            //更新投资金额
            $investAmountFen = bcmul($investAmount, 100);
            $updateInvestAmount = $userReservationModel->updateInvestAmount($reserveId, $accountId, $investAmountFen, $orderId);
            if (!$updateInvestAmount) {
                throw new \Exception(sprintf('userReservationModel::updateUserReservation, reserveId: %s, accountId: %s', $reserveId, $accountId));
            }

            //添加交易记录
            $addRelation = $reservationDealLoadModel->addRelation($reserveId, $loadId, $accountId, $dealId, $investAmountFen);
            if (!$addRelation) {
                throw new \Exception(sprintf('reservationDealLoadModel::addRelation, reserveId: %s, loadId: %s', $reserveId, $loadId));
            }

            //提交事务
            $rs = $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, "RESERVATION_BID_EXCEPTION, 预约投资失败, ".$e->getMessage())));
            return false;
        }
    }

    /**
     * 获取已投资金额
     * 按产品类型、投资期限分组
     */
    public function getInvestAmountGroupByDate($date) {

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("begin getInvestAmountGroupByDate, date: %s", $date))));
        $pageSize = 1000;

        $groupData = [];
        $db = \libs\db\Db::getInstance('firstp2p', 'slave');
        $relationId = 0;
        $reservationDealLoadModel = ReservationDealLoadModel::instance();
        while ($relationList = $reservationDealLoadModel->getList($date, $relationId, $pageSize)) {
            $dealIds = [];
            $dealMoneyMap = [];
            foreach($relationList as $relation) {
                $dealIds[] = $relation['deal_id'];
                $relationId = $relation['id'];
                $investMoney = bcdiv($relation['invest_amount'], 100, 2);
                if (!isset($dealMoneyMap[$relation['deal_id']])) {
                    $dealMoneyMap[$relation['deal_id']] = 0;
                }
                $dealMoneyMap[$relation['deal_id']] = bcadd($dealMoneyMap[$relation['deal_id']], $investMoney, 2);
            }

            //标的
            $dealSql = sprintf('select id, type_id, loantype, repay_time from firstp2p_deal where id in (%s)', implode(',', $dealIds));
            $dealList = $db->getAll($dealSql);
            $dealGroupMap = [];
            foreach ($dealList as $dealInfo) {
                $deadline = $dealInfo['repay_time'];
                $type_id = $dealInfo['type_id'];
                if ($dealInfo['loantype'] == 5) {
                    $deadlineUnit = ReserveEnum::INVEST_DEADLINE_UNIT_DAY;
                } else {
                    $deadlineUnit = ReserveEnum::INVEST_DEADLINE_UNIT_MONTH;
                }
                $groupKey = $type_id . '_' . $deadline . '_' . $deadlineUnit;
                $dealGroupMap[$dealInfo['id']] = $groupKey;
            }

            //合并数据
            foreach($dealMoneyMap as $dealId => $money) {
                $groupKey = $dealGroupMap[$dealId];
                if (!isset($groupData[$groupKey])) {
                    $groupData[$groupKey] = 0;
                }
                $groupData[$groupKey] = bcadd($groupData[$groupKey], $money, 2);
            }
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("end getInvestAmountGroupByDate, date: %s, groupData: %s", $date, json_encode($groupData)))));
        return $groupData;
    }

    /**
     * 获取总剩余有效预约金额
     */
    public function getTotalEffectReserveAmount($deadline = 0, $deadlineUnit = 0) {
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("begin getTotalEffectReserveAmount, deadline: %s, deadlineUnit: %s", $deadline, $deadlineUnit))));

        $processTime = time();
        $pageSize = 1000;
        $userReservationModel = UserReservationModel::instance();

        $totalAmount = 0;//总的有效预约金额
        $reserveId = 0;
        while ($reserveList = $userReservationModel->getUserReserveListByLimit($processTime, $pageSize, $reserveId, $deadline, $deadlineUnit)) {
            $accountIds = [];
            foreach ($reserveList as $userReservation) {
                $reserveId = $userReservation['id'];
                $accountIds[] = $userReservation['user_id'];
            }

            //账户资金
            $accountList = AccountModel::instance()->getInfoByIds($accountIds);
            $accountMap = [];
            foreach ($accountList as $value) {
                $accountMap[$value['id']] = bcdiv($value['money'], 100, 2);
            }

            foreach($reserveList as $userReservation) {
                $needReserveAmount = bcdiv($userReservation['reserve_amount'] - $userReservation['invest_amount'], 100, 2); //剩下的预约金额
                $accountBalance = isset($accountMap[$userReservation['user_id']]) ? $accountMap[$userReservation['user_id']] : 0;
                $reserveMinMoney = $this->getReserveMinMoney($userReservation);
                if (bccomp($accountBalance, $reserveMinMoney, 2) >= 0) {
                    $effectReserveAmount = min($accountBalance, $needReserveAmount);
                    $totalAmount = bcadd($totalAmount, $effectReserveAmount, 2);
                }
            }
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("end getTotalEffectReserveAmount, deadline: %s, deadlineUnit: %s, totalAmount: %s", $deadline, $deadlineUnit, $totalAmount))));
        return $totalAmount;
    }

    /**
     * 根据产品类型获取有效剩余预约金额
     *
     * 随心约预约资金按比例分配
     * FIRSTPTOP-5267
     */
    public function getEffectReserveAmountByTypeId($typeId, $deadline, $deadlineUnit) {
        $date = date('Y-m-d');
        //获取有效剩余预约金额
        $totalEffectReserveAmount = $this->getTotalEffectReserveAmount($deadline, $deadlineUnit);

        //获取资金分配比例
        $reservationMoneyAssignRatioModel = new ReservationMoneyAssignRatioModel();
        $moneyAssignRatio = $reservationMoneyAssignRatioModel->getMoneyAssignRatio($deadline, $deadlineUnit);
        if (empty($moneyAssignRatio)) {
            return $totalEffectReserveAmount;
        }

        $moneyLimit = 0;//当日限制金额
        $needMoneyLimit = 0;//剩余当日限制金额
        $limitTypeId = 0;//限制金额的产品类型id
        $typeIdMap = [];//产品类型映射数据
        $investAmountGroup = $this->getInvestAmountGroupByDate($date);//获取已投资金额
        foreach ($moneyAssignRatio as $value) {
            $groupKey = $value['type_id'] . '_' . $deadline . '_' . $deadlineUnit;
            $investAmount = isset($investAmountGroup[$groupKey]) ? $investAmountGroup[$groupKey] : 0;
            $typeIdMap[$value['type_id']]['invest_amount'] = $investAmount;
            $typeIdMap[$value['type_id']]['money_ratio'] = $value['money_ratio'];
            if ( bccomp($value['money_limit'], 0, 2) === 1 ) {
                $moneyLimit = $value['money_limit'];
                $limitTypeId = $value['type_id'];
                if (bccomp($moneyLimit, $investAmount, 2) === 1) {
                    $needMoneyLimit = bcsub($moneyLimit , $investAmount, 2);
                }
            }
        }

        //没有对应资金分配比例
        if (!isset($typeIdMap[$typeId])) {
            return $totalEffectReserveAmount;
        }

        //有限制的产品类型 例如放心花
        if ($limitTypeId == $typeId) {
            //Min（预约池总预约额度X比例，今日可预约额度上限-已匹配额度）
            return min( bcmul($totalEffectReserveAmount, $typeIdMap[$typeId]['money_ratio'], 2), $needMoneyLimit );
        }

        //不限制的产品类型 例如掌众
        if (bccomp($moneyLimit, 0, 2) === 1) {
            //有限制产品类型的预约金额(放心花)
            $limitTypeAmount = min( bcmul($totalEffectReserveAmount, $typeIdMap[$limitTypeId]['money_ratio'], 2), $needMoneyLimit );
            //总预约金额 - 有限制产品类型的预约金额(放心花)
            return bcsub($totalEffectReserveAmount, $limitTypeAmount, 2);
        }
        return bcmul($totalEffectReserveAmount, $typeIdMap[$typeId]['money_ratio'], 2);
    }

    /**
     * 根据类型标签获取有效剩余预约金额
     *
     * 随心约预约资金按比例分配
     * FIRSTPTOP-5267
     */
    public function getEffectReserveAmountByTypeTag($typeTag, $deadline, $deadlineUnit) {
         $typeId = DealLoanTypeModel::instance()->getIdByTag($typeTag);
         if (empty($typeId)) {
             return 0;
         }
         return $this->getEffectReserveAmountByTypeId($typeId, $deadline, $deadlineUnit);
    }

    /**
     * 未完成而有效的随心约预约
     */
    public function getEffectReserveCountByUserId($accountId) {
        $count = 0;
        $reserveList = UserReservationModel::instance()->getUserReserveList($accountId, ReserveEnum::RESERVE_STATUS_ING);
        foreach ($reserveList as $reserve) {
            if ($reserve['end_time'] > time()) {
                $count += 1;
            }
        }
        return $count;
    }

    public function isInvestByUserId($accountId)
    {
        $db = \libs\db\Db::getInstance('firstp2p', 'slave');
        $Sql = "select user_id from firstp2p_user_reservation where user_id = {$accountId} and reserve_status = 1 and invest_amount > 0 limit 1";
        $res = $db->getOne($Sql);
        if (!empty($res)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取预约记录最低金额
     */
    public function getReserveMinMoney($userReservation) {
        $entraService = new ReservationEntraService();
        $entra = $entraService->getReserveEntra($userReservation['invest_deadline'], $userReservation['invest_deadline_unit'], $userReservation['deal_type'], $userReservation['invest_rate'], $userReservation['loantype'], -1);
        if (empty($entra) || empty($entra['min_amount'])) {
            return ReserveEntraEnum::RESERVE_DEFAULT_MIN_AMOUNT;
        }
        return bcdiv($entra['min_amount'], 100, 2);
    }

    /**
     * 发送信息披露
     */
    private function _sendDisclosure($dealType, $dealList) {
        if ((int) app_conf('RESERVE_SEND_DISCLOSURE_SWITCH') !== 1) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("发送信息披露已关闭"))));
            return false;
        }

        if ($dealType !== DealEnum::DEAL_TYPE_GENERAL) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("无需发送信息披露, dealType: %d", $dealType))));
            return false;
        }

        if (empty($dealList)) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("没有标的, dealType: %d", $dealType))));
            return false;
        }

        //收集标的期限
        $deadlineMap = [];
        foreach ($dealList as $deal) {
            $investDeadlineArray = $this->getInvestDeadlineByDeal($deal);
            $deadline = isset($investDeadlineArray['invest_deadline']) ? $investDeadlineArray['invest_deadline'] : 0;
            $deadlineUnit = isset($investDeadlineArray['invest_deadline_unit']) ? $investDeadlineArray['invest_deadline_unit'] : 0;
            $deadlineMap[$deadline . '_' . $deadlineUnit] = 1;
        }

        $userReservationModel = UserReservationModel::instance();
        $pageSize = 1000; //每次处理几条预约
        $reserveId = 0;
        $processTime = time();
        while ($reserveList = $userReservationModel->getUserReserveListByLimit($processTime, $pageSize, $reserveId, 0, 0, $dealType)) {
            foreach ($reserveList as $userReservation) {
                $reserveId = $userReservation['id'];
                $accountId = $userReservation['user_id'];

                //检查预约期限匹配标的
                if (!isset($deadlineMap[$userReservation['invest_deadline'] . '_' . $userReservation['invest_deadline_unit']])) {
                    continue;
                }

                //发过信息披露
                if ($this->_getSentDisclosureUserCache($accountId, $reserveId)) {
                    continue;
                }

                //预约已经过期
                $userReservation = $userReservationModel->find($reserveId);
                if ($userReservation['reserve_status'] != ReserveEnum::RESERVE_STATUS_ING || $userReservation['end_time'] < time()) {
                    continue;
                }

                //计算可投资金额，优先读取缓存
                $account = AccountService::getAccountInfoById($accountId);
                $userId = $account['user_id'];
                $account['user_info'] = UserService::getUserById($userId);
                $investAmount = $this->getEffectReserveAmount($account, $userReservation, true);
                $expireTime = max($userReservation['end_time'] - $userReservation['start_time'], 60);
                //有效用户只发送一次信息披露
                if (bccomp($investAmount, 0, 2) === 1 && $this->_setSentDisclosureUserCache($accountId, $reserveId, $expireTime)) {
                    //主站app发站内信，其他平台发短信
                    if ($userReservation['site_id'] == 1 && $userReservation['reserve_referer'] == ReserveEnum::RESERVE_REFERER_APP) {
                        $msgbox = new MsgboxService();
                        $title = '即将开始匹配';
                        $content = sprintf('您在随心约预约标的即将进行匹配，请您前往随心约预约专区的“信息披露”页面内查看预匹配的项目详情。');
                        $msgbox->create($userId, 41, $title, $content);
                    } else {
                        $tpl = 'TPL_SMS_RESERVE_DISCLOSURE';
                        $mobile = $account['user_info']['is_enterprise_user'] ? 'enterprise' : $account['user_info']['mobile'];
                        $smsContent = [];
                        SmsServer::instance()->send($mobile, $tpl, $smsContent, $userId, 100);
                    }
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("发送信息披露成功, reserveId: %d, accountId: %d", $reserveId, $accountId))));
                }
            }
        }
        //构建缓存，信息披露页面展示使用
        $this->_setDisclosureDealListCache($dealList);

        $waitTime = (int) app_conf('RESERVE_SEND_DISCLOSURE_WAIT_TIME') ? : 600; //处理等待时间
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("sleep %ss", $waitTime))));
        sleep($waitTime);

        return true;
    }

    /**
     * 记录已发送信息披露的用户缓存
     */
    private function _setSentDisclosureUserCache($accountId, $reserveId, $expireTime) {
        $redis = \SiteApp::init()->dataCache;
        $sentKey = sprintf(self::KEY_RESERVE_SENT_DISCLOSURE_USER, $accountId, $reserveId);
        return $redis->setNx($sentKey, time(), $expireTime);
    }

    /**
     * 获取已发送信息披露的用户缓存
     */
    private function _getSentDisclosureUserCache($accountId, $reserveId) {
        $redis = \SiteApp::init()->dataCache;
        $sentKey = sprintf(self::KEY_RESERVE_SENT_DISCLOSURE_USER, $accountId, $reserveId);
        $value = $redis->getRedisInstance()->get($sentKey);
        if (null !== $value) {
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * 重置信息披露标的缓存
     */
    private function _setDisclosureDealListCache($dealList) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        //标的写到缓存区，使用redis的zsets结构
        $dealMap = []; //日期对应标的表
        $redis->del(self::KEY_RESERVE_DISCLOSURE_DEAL_NAME_LIST); //标的名称集合
        foreach ($dealList as $deal) {
            $investDeadlineArray = $this->getInvestDeadlineByDeal($deal);
            $deadline = isset($investDeadlineArray['invest_deadline']) ? $investDeadlineArray['invest_deadline'] : 0;
            $deadlineUnit = isset($investDeadlineArray['invest_deadline_unit']) ? $investDeadlineArray['invest_deadline_unit'] : 0;
            $dealMap[$deadline . '_' . $deadlineUnit][$deal['id']] = $deal['create_time'];
            $redis->hset(self::KEY_RESERVE_DISCLOSURE_DEAL_NAME_LIST, $deal['id'], $deal['name']);
        }
        $redis->expire(self::KEY_RESERVE_DISCLOSURE_DEAL_NAME_LIST, 3700);

        //构建缓存
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "dealMap: " . json_encode($dealMap))));
        foreach ($dealMap as $deadlineStr => $dealIds) {
            list($deadline, $deadlineUnit) = explode('_', $deadlineStr);
            $dealListKey = sprintf(self::KEY_RESERVE_DISCLOSURE_DEAL_LIST, $deadline, $deadlineUnit);
            $redis->del($dealListKey);
            $redis->zadd($dealListKey, $dealIds);
            $redis->expire($dealListKey, 3600);
        }

        //设置期限
        $this->setDisclosureDeadlineList(array_keys($dealMap));
        return true;
    }

    /**
     * 设置信息披露期限列表
     */
    public function setDisclosureDeadlineList($deadlineList) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $redis->del(self::KEY_RESERVE_DISCLOSURE_DEADLINE_LIST);
        $redis->sadd(self::KEY_RESERVE_DISCLOSURE_DEADLINE_LIST, $deadlineList);
        $redis->expire(self::KEY_RESERVE_DISCLOSURE_DEADLINE_LIST, 3600);
        return true;
    }

    /**
     * 获取信息披露期限列表
     */
    public function getDisclosureDeadlineList() {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $result = $redis->smembers(self::KEY_RESERVE_DISCLOSURE_DEADLINE_LIST);
        return $result;
    }

    /**
     * 获取信息披露标的缓存
     * 分页获取
     */
    public function getDisclosureDealListCacheByPage($deadline, $deadlineUnit, $page = 1, $pageSize = 20) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $dealListKey = sprintf(self::KEY_RESERVE_DISCLOSURE_DEAL_LIST, $deadline, $deadlineUnit);
        $start = ($page - 1) * $pageSize;
        $stop = $start + $pageSize - 1;

        //分页获取标的列表
        $dealList = $redis->zrange($dealListKey, $start, $stop);
        $list = [];
        foreach ($dealList as $dealId) {
            $dealName = $redis->hget(self::KEY_RESERVE_DISCLOSURE_DEAL_NAME_LIST, $dealId);
            if (empty($dealName)) {
                continue;
            }
            $list[] = [
                'id' => $dealId,
                'product_id' => \libs\utils\Aes::encryptForDeal($dealId),
                'name' => $dealName,
            ];
        }
        //总数
        $total = $redis->zcard($dealListKey);
        $result = [
            'list'  => $list,
            'total' => $total,
            'totalPage' => ceil($total / $pageSize),
        ];
        return $result;
    }

    /**
     * 获取最新预约动态
     */
    public function getNewReserve($money, $limit = 10, $limit_time = 86400)
    {
        return UserReservationModel::instance()->getNewReserve($money, $limit, $limit_time);
    }


}
