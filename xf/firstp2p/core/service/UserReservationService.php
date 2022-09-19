<?php
/**
 * 用户预约投标服务
 *
 * @date 2016-11-14
 * @author guofeng@ucfgroup.com
 */

namespace core\service;
use core\dao\UserReservationModel;
use core\dao\ReservationConfModel;
use core\dao\ReservationDealLoadModel;
use core\dao\DealModel;
use core\dao\DealExtModel;
use core\dao\DealSiteModel;
use core\dao\UserModel;
use core\dao\DealLoanTypeModel;
use core\dao\JobsModel;
use core\data\DealData;
use core\service\ReservationConfService;
use core\service\BonusService;
use core\service\DealLoadService;
use core\service\DealService;
use core\service\DealTagService;
use core\service\SupervisionService;
use libs\utils\Logger;
use libs\utils\Monitor;
use core\service\MsgBoxService;
use core\service\CouponService;
use core\service\ReservationMatchService;
use core\service\ReservationEntraService;
use core\service\ReservationDealService;
use core\service\OtoTriggerRuleService;
use core\service\UserService;
use core\service\UserThirdBalanceService;
use core\service\ReservationDiscountService;
use core\service\O2OService;
use core\service\oto\O2ODiscountService;
use core\service\IntelligentInvestmentService;
use core\dao\ReservationMatchModel;
use core\dao\ReservationEntraModel;
use core\dao\DealLoadModel;
use core\dao\RiskAssessmentLevelsModel;
use core\dao\UserRiskAssessmentModel;
use core\dao\DealProjectModel;
use core\dao\ReservationMoneyAssignRatioModel;
use core\service\DealProjectRiskAssessmentService;
use libs\payment\supervision\Supervision;
use core\service\SupervisionBaseService;
use core\service\SupervisionAccountService;
use core\service\DealCustomUserService;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\tmevent\reserve\ProcPrepareEvent;
use core\tmevent\reserve\ProcBidEvent;
use core\tmevent\reserve\ProcCompleteEvent;
use libs\sms\SmsServer;
use NCFGroup\Protos\O2O\Enum\CouponEnum;
use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;
use NCFGroup\Protos\Contract\Enum\ContractTplIdentifierEnum;

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
    const CACHEKEY_YYB_API_LOCK = 'YYB_API_LOCK_%d';

    /**
     * 缓存-预约标-用户在OPENAPI里的用户信息等-Api
     * @var string
     */
    const CACHEKEY_YYB_OPENAPI = 'YYB_OPENAPI_%s';

    /**
     * 缓存-预约标-用户在预约时的redis锁
     * @var string
     */
    const CACHEKEY_YYB_OPENAPI_LOCK = 'YYB_OPENAPI_LOCK_%d';

    /**
     * 缓存-预约标-预约总人数、预约投资总金额
     * @var string
     */
    const CACHEKEY_YYB_STATISTICS = 'YYB_STATISTICS_INFO';

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

    //随心约智投借款类型列表
    public static $ii_deal_types = [DealModel::DEAL_TYPE_EXCLUSIVE, DealModel::DEAL_TYPE_EXCHANGE];

    //预约投资输出编码
    const CODE_RESERVE_USER_INVEST_AMOUNT_NOT_ENOUGH = 1001; //用户可投资金额不足
    const CODE_RESERVE_LOCK_USER_FAIL = 1002; //获取用户锁失败
    const CODE_RESERVE_ENDED = 1003; //该笔预约记录已结束或过期
    const CODE_RESERVE_PROCESSING = 1004; //该笔预约正在处理中
    const CODE_RESERVE_ID_ERROR = 1005; //用户编号或预约编号错误
    const CODE_RESERVE_DEAL_TYPE_ERROR = 1006; //预约借款类型不匹配标的
    const CODE_RESERVE_DEADLINE_ERROR = 1007; //预约期限不匹配标的
    const CODE_RESERVE_USER_NOT_EXIST = 1008; //用户不存在
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
    const KEY_RESERVE_DEAL_LIST = 'RESERVE_DEAL_LIST'; //标的队列
    const KEY_RESERVE_DEAL_SETS = 'RESERVE_DEAL_SETS'; //标的集合，降低队列中标的重复

    const KEY_RESERVE_DEAL_LOCK = 'RESERVE_PROCESS_LOCK_DEAL_%s';
    const KEY_RESERVE_USER_LOCK = 'RESERVE_PROCESS_LOCK_USER_%s';

    //有效预约额
    const KEY_RESERVE_USER_EFFECT_AMOUNT = 'RESERVE_USER_EFFECT_AMOUNT_%s_%s';

    //信息披露
    const KEY_RESERVE_SENT_DISCLOSURE_USER = 'RESERVE_SENT_DISCLOSURE_USER_%s_%s'; //已发送信息披露的用户
    const KEY_RESERVE_DISCLOSURE_DEAL_LIST = 'RESERVE_DISCLOSURE_DEAL_LIST_%s_%s'; //信息披露标的列表缓存
    const KEY_RESERVE_DISCLOSURE_DEAL_NAME_LIST = 'RESERVE_DISCLOSURE_DEAL_NAME_LIST'; //信息披露标的名字列表缓存
    const KEY_RESERVE_DISCLOSURE_DEADLINE_LIST = 'RESERVE_DISCLOSURE_DEADLINE_LIST'; //信息披露期限表


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
     * @param int $userId 用户ID
     * @return \libs\db\model
     */
    public function getUserValidReserveList($userId, $dealTypeList = [])
    {
        $list = array('userReserveList' => array(), 'hasExpireIds' => array());
        // 获取用户[预约中]的预约记录列表
        $userReserveList = UserReservationModel::instance()->getUserReserveList($userId, UserReservationModel::RESERVE_STATUS_ING, 0, 0, $dealTypeList);
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
     * 获取显示网贷开关
     */
    public function getReserveDisplayP2pSwitch() {
        $siteId = \libs\utils\Site::getId();
        return (int) get_config_db('RESERVE_DISPLAY_P2P_SWITCH', $siteId);
    }

    /**
     * 获取入口数据
     */
    public function getReserveEntryData($userId) {
        $entryData = [
            'p2pEntry'          => false,
            'exclusiveEntry'    => false,
        ];
        $displayP2pSwitch = $this->getReserveDisplayP2pSwitch();
        //是否显示尊享
        $isShowExclusive = $this->isShowReserveExclusive($userId);
        switch ($displayP2pSwitch) {
            case UserReservationModel::DISPLAY_P2P_NOT: //不显示网贷
                if ($isShowExclusive) {
                    $entryData['exclusiveEntry'] = true;
                }
                break;
            case UserReservationModel::DISPLAY_P2P: //显示网贷
                if ($isShowExclusive) {
                    $entryData['p2pEntry'] = $entryData['exclusiveEntry'] = true;
                } else {
                    $entryData['p2pEntry'] = true;
                }
                break;
            case UserReservationModel::DISPLAY_P2P_ONLY: //只显示网贷
                $entryData['p2pEntry'] = true;
                break;
        }
        return $entryData;
    }

    /**
     * 是否显示随心约尊享
     */
    public function isShowReserveExclusive($userId) {
        if (empty($userId)) {
            return false;
        }

        // 检查黑名单
        $dealCustomUserService = new DealCustomUserService();
        $isBackList = $dealCustomUserService->checkBlackList($userId);
        if ($isBackList){
            return false;
        }

        //检查有没有预约中的专享，如果有则返回true
        $exclusiveDealType = UserReservationModel::$productTypeMap[UserReservationModel::PRODUCT_TYPE_EXCLUSIVE]; //尊享借款类型
        $list = $this->getUserValidReserveList($userId, $exclusiveDealType);
        if (!empty($list['userReserveList'])) {
            return true;
        }

        // 是否能投尊享
        if (!$dealCustomUserService->canLoanZx($userId)) {
            return false;
        }
        return true;
    }

    /**
     * 根据开关获取借款类型
     */
    public function getDealTypeListBySwitch() {
        $displayP2pSwitch = $this->getReserveDisplayP2pSwitch();
        return UserReservationModel::$displayMap[$displayP2pSwitch];
    }

    /**
     * 获取贷款类型列表
     */
    public function getDealTypeListByProduct($productType, $userId = 0) {
        $dealTypeList = $this->getDealTypeListBySwitch();

        //和产品对应的借款类型求交集
        if (!empty($productType)) {
            $dealTypeList = array_intersect(
                $this->getDealTypeListBySwitch(),
                UserReservationModel::$productTypeMap[$productType]
            );
        }

        //尊享要检查是否显示
        $exclusiveDealType = UserReservationModel::$productTypeMap[UserReservationModel::PRODUCT_TYPE_EXCLUSIVE]; //尊享借款类型
        if (array_intersect($dealTypeList, $exclusiveDealType)) {
            //不显示尊享，要剔除尊享借款类型
            if (!$this->isShowReserveExclusive($userId)) {
                $dealTypeList = array_diff($dealTypeList, $exclusiveDealType);
            }
        }

        return $dealTypeList;
    }

    /**
     * 是否是随心约尊享标
     */
    public function isReserveExclusiveDeal($dealType) {
        $exclusiveDealType = UserReservationModel::$productTypeMap[UserReservationModel::PRODUCT_TYPE_EXCLUSIVE];
        if (in_array($dealType, $exclusiveDealType)) {
            return true;
        }
        return false;
    }

    /**
     * 获取产品类型
     */
    public function getProductByDealType($dealType) {
        $result = 0;
        foreach (UserReservationModel::$productTypeMap as $productType => $dealTypeList) {
            if (in_array($dealType, $dealTypeList)) {
                $result = $productType;
                break;
            }
        }
        return $productType;
    }


    /**
     * 根据用户ID，获取用户的预约记录列表
     * @param int $userId 用户ID
     * @param int $reserveStatus 预约状态
     * @return \libs\db\model
     */
    public function getUserReserveListByPage($userId, $reserveStatus, $page, $count, $dealTypeList)
    {
        return UserReservationModel::instance()->getUserReserveList($userId, $reserveStatus, $page, $count, $dealTypeList);
    }

    /**
     * 检查用户是否还可以再预约
     * @param int $userId
     * @return boolean
     */
    public function checkUserIsReserve($userId)
    {
        return true;
    }

    /**
     * 创建用户预约投标记录
     * @param int $userId 用户ID
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
    public function createUserReserve($userId, $reserveAmountCent, $investDeadline, $expire, $inviteCode = '', $investDeadlineUnit = UserReservationModel::INVEST_DEADLINE_UNIT_DAY, $expireUnit = UserReservationModel::EXPIRE_UNIT_HOUR, $reserveConf = array(), $reserveReferer = 1, $siteId = 1, $extraInfo = [], $discountId = 0, $dealType = 0, $loantype = 0, $investRate = 0)
    {
        //检查预约入口
        $entraService = new ReservationEntraService();
        $reserveEntra = $entraService->getReserveEntra($investDeadline, $investDeadlineUnit, $dealType, $investRate, $loantype);
        if (empty($reserveEntra)) {
            return array('ret'=>false, 'errorMsg' => '未配置预约入口');
        }

        //检查预约期限
        $reservationConfService = new ReservationConfService();
        $reserveConf = $reservationConfService->getReserveInfoByType(ReservationConfModel::TYPE_NOTICE);
        $hasReserveConf = false;
        foreach ($reserveConf['reserve_conf'] as $key => $item) {
            if ($item['expire_unit'] == $expireUnit && $item['expire_unit'] == $expireUnit) {
                $hasReserveConf = true;
            }
        }
        if (!$hasReserveConf) {
            return array('ret'=>false, 'errorMsg' => '未配置预约期限');
        }

        //检查优惠券是否能用
        if ($discountId > 0) {
            $o2oService = new O2OService();
            $discountInfo = $o2oService->getDiscount($discountId);
            if (empty($discountInfo)) {
                return array('ret'=>false, 'errorMsg' => '优惠券使用失败，请稍后重试');
            }

            //判断券状态和预约记录
            $hasDiscount = UserReservationModel::instance()->hasDiscount($discountId);
            if ($discountInfo['status'] != CouponEnum::STATUS_UNUSED || $hasDiscount) {
                return array('ret'=>false, 'errorMsg' => '优惠券已使用或已过期');
            }

            $o2oDiscountService = new O2ODiscountService();
            $reservationConfService = new ReservationConfService();
            $errorInfo = [];
            $deadlineDays = $reservationConfService->convertToDays($investDeadline, $investDeadlineUnit); //转换预约投资天数
            $reserveAmount = bcdiv($reserveAmountCent, 100, 2); //转换元
            $extraParam = ['dealId' => '', 'money' => $reserveAmount, 'bidDayLimit' => $deadlineDays];
            $isCanUseDiscount = $o2oDiscountService->canUseDiscount($userId, $discountId, $discountInfo['discountGroupId'], $errorInfo, CouponGroupEnum::CONSUME_TYPE_RESERVE, $extraParam);
            if (!$isCanUseDiscount) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("优惠券已使用或已过期,discountId:%s,errorInfo:%s",$discountId,json_encode($errorInfo)))));
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
            $result = UserReservationModel::instance()->createUserReserve($userId, $reserveAmountCent, $investDeadline, $expire, $investRate, $inviteCode, $investDeadlineUnit, $expireUnit, $reserveReferer, $siteId, $extraInfo, $discountId, $dealType, $reserveEntra['rate_factor'], $loantype);
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
                'type' => ContractServiceEnum::TYPE_RESERVATION_SUPER,
                'lenderUserId' => $userId,
                'sourceType' => ContractServiceEnum::SOURCE_TYPE_RESERVATION_SUPER,
                'createTime' => time(),
                'tplPrefix' =>ContractTplIdentifierEnum::RESERVATION_CONT,
                'uniqueId' => 0,
            );

            $jobsModel = new JobsModel();
            $jobsModel->priority = JobsModel::PRIORITY_RESERVE_PROTOCOL;
            $r = $jobsModel->addJob('\core\service\SendContractService::sendDtContractJob', array('requestData'=>$param));
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
     * @param int $userId
     */
    public function cancelUserReserve($id, $userId)
    {
        $updateRet = UserReservationModel::instance()->cancelUserReserveById($id, $userId);
        if ($updateRet) {
            // 检查触发规则并发送礼券/投资券，赠送礼品
            $otoTriggerRule = new OtoTriggerRuleService();
            $otoTriggerRule->checkReservationRuleAndSendGift($id);

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
     * @param int $userId
     */
    public function cancelUserReserveBatch($userId)
    {
        return UserReservationModel::instance()->cancelUserReserveByUserId($userId);
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

        if (!in_array($dealType, UserReservationModel::$reserveDealTypeList)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "不支持的借款类型，dealType: {$dealType}")));
            return false;
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "dealType: {$dealType}")));

        //获取预约标的
        $reservationDealService= new ReservationDealService();
        $dealList = $reservationDealService->getReservationDealList($dealType);

        //发送信息披露
        if ($dealType == DealModel::DEAL_TYPE_GENERAL && !empty($dealList)) {
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
            $deal = DealModel::instance()->find($dealId);
            if (empty($deal)) {
                throw new \Exception('标的不存在');
            }
            //启动智投
            $intelligentInvestmentService = new IntelligentInvestmentService();
            if ($this->isEnableIntelligentInvest($deal)) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, 'intelligent investment, dealId: ' . $dealId)));
                $deal = $deal->getRow();
                $intelligentInvestmentService->invest($deal);
            } else {
                $this->processOneDeal($dealId);
            }
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
        $userModel = UserModel::instance();
        $reservationConfService = new ReservationConfService();
        $processTime = time(); //处理时间
        $pageSize = 100; //每次处理几条预约

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_CHECK_BALANCE, sprintf('begin check reserved user balance'))));

        $reserveId = 0;
        while ($reserveList = $userReservationModel->getUserReserveListByLimit($processTime, $pageSize, $reserveId)) {
            foreach ($reserveList as $userReservation) {

                // 检查预约状态
                $userReservation = $userReservationModel->find($userReservation['id']);
                if ($userReservation['reserve_status'] != UserReservationModel::RESERVE_STATUS_ING) { //预约结束
                    continue;
                }

                $reserveId = $userReservation['id'];
                $userId = $userReservation['user_id'];

                //获取用户
                $user = $userModel->find($userId);
                if (empty($user)) { //用户不存在
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_CHECK_BALANCE, sprintf("用户不存在, userId: %s", $userId))));
                    continue;
                }

                //获取有效预约金额
                $effectReserveAmount = $this->getEffectReserveAmount($user, $userReservation);
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_CHECK_BALANCE, sprintf("设置有效预约金额缓存, userId: %s, reserveId: %s, effectReserveAmount: %s", $userId, $reserveId, $effectReserveAmount))));
                $this->_setUserEffectReserveAmountCache($userId, $reserveId, $effectReserveAmount);//设置用户有效金额缓存
            }
        }

        //监控随心约队列
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'list monitor', sprintf(self::KEY_RESERVE_DEAL_LIST . ': %d, ' . self::KEY_RESERVE_DEAL_SETS . ': %d', $redis->llen(self::KEY_RESERVE_DEAL_LIST), $redis->scard(self::KEY_RESERVE_DEAL_SETS)))));

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_CHECK_BALANCE, sprintf('end check reserved user balance'))));
        return true;
    }

    /**
     * 是否使用智能投资
     */
    public function isEnableIntelligentInvest($deal) {
        //预约处理中的专享和交易所标的 智能投资
        if ($deal['deal_status'] == DealModel::$DEAL_STATUS['reserving']
            && in_array($deal['deal_type'], self::$ii_deal_types)) {
            return true;
        }
        return false;
    }

    /**
     * 处理单个标的
     */
    public function processOneDeal($dealId) {
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, 'begin process one deal ' . $dealId)));

        $userReservationModel = UserReservationModel::instance();
        $reservationDealService = new ReservationDealService();

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
                $userId = $userReservation['user_id'];

                //处理单个预约和标的
                $result = $this->processOne($reserveId, $dealId, $userId);

                //跳过标的
                if ($result['respCode'] > self::CODE_RESERVE_SKIP_DEAL) {
                    break 2;
                }
            }
        }

        //更新标的为等待确认，由上标队列发布到线上
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
    public function processOne($reserveId, $dealId, $userId, $assignMoney = 0) {
        $isUnlockUser = false;//是否解锁用户
        try {
            //查询用户有效预约金额不足缓存
            $effectReserveAmountCache = $this->_getUserEffectReserveAmountCache($userId, $reserveId);
            if (null !== $effectReserveAmountCache && bccomp($effectReserveAmountCache, 0, 2) <= 0) {
                throw new \Exception('用户有效预约金额缓存不足', self::CODE_RESERVE_USER_EFFECT_AMOUNT_NOT_ENOUGH);
            }

            $userReservationModel = UserReservationModel::instance();
            $reservationDealLoadModel = new ReservationDealLoadModel();
            $dealModel = DealModel::instance();
            $userModel = UserModel::instance();
            $userRiskAssessmentModel = UserRiskAssessmentModel::instance();
            $dealLoadService = new DealLoadService();
            $dealService = new DealService();
            $otoTriggerRule = new OtoTriggerRuleService();
            $reservationConfService = new ReservationConfService();
            $reservationDiscountService = new ReservationDiscountService();
            $reservationDealService = new ReservationDealService();

            $sourceType = DealLoadModel::$SOURCE_TYPE['reservation']; //前台预约投标
            DealData::$skipPool = true;//预约投资绕过限宽门
            Monitor::add('RESERVE_MATCH_TOTAL'); //添加监控，总匹配次数

            //获取用户锁，防止多进程并发处理
            if (!$this->_lockUser($userId)) {
                Monitor::add('RESERVE_LOCK_USER_FAIL'); //添加监控
                throw new \Exception('获取用户锁失败', self::CODE_RESERVE_LOCK_USER_FAIL);
            }
            $isUnlockUser = true;

            $userReservation = $userReservationModel->find($reserveId);
            if ($userReservation['reserve_status'] != UserReservationModel::RESERVE_STATUS_ING || $userReservation['end_time'] < time()) {
                Monitor::add('RESERVE_ENDED'); //添加监控
                throw new \Exception('该笔预约记录已结束或过期', self::CODE_RESERVE_ENDED);
            }
            $userReservation = $userReservation->getRow();

            if ($userReservation['proc_status'] != UserReservationModel::PROC_STATUS_NORMAL) {
                Monitor::add('RESERVE_PROCESSING'); //添加监控
                throw new \Exception('该笔预约正在处理中', self::CODE_RESERVE_PROCESSING);
            }

            //检查用户编号和预约编号
            if ($userReservation['user_id'] != $userId) {
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
            $user = $userModel->find($userId);
            if (empty($user)) {
                throw new \Exception('用户不存在', self::CODE_RESERVE_USER_NOT_EXIST);
            }

            //单笔投资限额
            //投资限额校验只应用于 P2P 产品，专享类产品不做校验，企业会员不做校验。
            $userService = new UserService($user);
            $userRiskData = $userRiskAssessmentModel->getURA($userId); //用户风险评估数据
            if ($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL && !$userService->isEnterpriseUser()) {
                if (empty($userRiskData)) { //未评估用户，直接跳过预约
                    throw new \Exception('用户未进行风险评估', self::CODE_RESERVE_USER_NOT_RISK_ASSESS);
                }
            }

            //检查账户用途
            $allowReserve = $userService->allowAccountLoan($user['user_purpose']);
            if (!$allowReserve) {
                throw new \Exception('非投资账户不允许预约投资', self::CODE_RESERVE_NOT_INVEST_ACCOUNT);
            }

            // 用户未发送信息披露
            if ( (int) app_conf('RESERVE_SEND_DISCLOSURE_SWITCH') === 1
                && $deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL
                && $this->_getSentDisclosureUserCache($userId, $reserveId) === null ) {
                throw new \Exception(sprintf('用户未发送信息披露'), self::CODE_RESERVE_USER_NOT_SEND_DISCLOSURE);
            }

            //产品等级校验
            if (!$this->_checkProjectRisk($user, $deal, $userReservation, $userRiskData)) {
                Monitor::add('RESERVE_PROJECT_RISK_CHECK_FAIL'); //添加监控，产品风险等级校验失败
                throw new \Exception('产品等级校验失败', self::CODE_RESERVE_PROJECT_RISK_CHECK_FAIL);
            }

            //获取用户可投资金额
            $reserveMinMoney = $this->getReserveMinMoney($userReservation);//预约最低金额
            $investAmount = $this->getInvestAmount($user, $userReservation, false, $deal, $assignMoney);
            if (bccomp($investAmount, 0, 2) <= 0 || bccomp($investAmount, $reserveMinMoney, 2) == -1 || bccomp($investAmount, $deal['min_loan_money'], 2) === -1) {
                Monitor::add('RESERVE_USER_INVEST_AMOUNT_NOT_ENOUGH'); //添加监控，可投资金额不足
                throw new \Exception('用户可投资金额不足', self::CODE_RESERVE_USER_INVEST_AMOUNT_NOT_ENOUGH);
            }

            //用户绑定邀请码
            $couponId = $this->_getUserCouponId($userId);

            //标信息
            $dealInfo = $dealService->getDeal($deal['id'], true);

            // 投标默认站点，读取预约站点，默认主站
            $siteId = !empty($userReservation['site_id']) ? $userReservation['site_id'] : 1;

            //存管降级确保不做投资，实时读取开关
            if ($dealService->isP2pPath($deal) && Supervision::isServiceDownRt()) {
                throw new \Exception('存管已降级，跳过标的', self::CODE_RESERVE_SUPERVISION_SERVICE_DOWN);
            }

            //用户年龄检查
            $ageCheck = $dealService->allowedBidByCheckAge($user);
            if($ageCheck['error'] == true){
                Monitor::add('RESERVE_USER_AGE_CHECK_FAIL'); //添加监控，用户年龄不符合
                throw new \Exception('用户年龄不符合, msg: ' . $ageCheck['msg'], self::CODE_RESERVE_USER_AGE_CHECK_FAIL);
            }

            //检查是否能投随心约尊享
            if ($this->isReserveExclusiveDeal($deal['deal_type']) && !$this->isShowReserveExclusive($userId)) {
                throw new \Exception('用户无法投资随心约尊享', self::CODE_RESERVE_USER_CANNOT_INVEST_EXCLUSIVE);
            }

            //投资
            $dealLoadService = new DealLoadService();
            $optionParams = [
                'reserveInfo' => $userReservation,
            ];
            $beginTime = microtime(true);
            $res = $dealLoadService->bid($userId, $dealInfo, $investAmount, $couponId, $sourceType, $siteId, false, '', 1, $optionParams);
            $endTime = microtime(true);
            if (empty($res) || !empty($res['error'])) {
                Monitor::add('RESERVE_BID_FAIL');
                throw new \Exception('预约投资失败', self::CODE_RESERVE_BID_FAIL);
            }
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'RESERVATION_COST', sprintf('bid, dealId: %s, userId: %s, investAmount: %s, res: %s, cost: %s', $dealInfo['id'], $userId, $investAmount, json_encode($res), round($endTime - $beginTime, 5)))));

            //投资完成之后，刷新预约记录
            $userReservation = $userReservationModel->find($userReservation['id']);

            //发送站内信
            $this->_sendMessage($userReservation, $investAmount);

            // 预约匹配完成的时候，再进行检查、赠送礼品
            if ($userReservation['reserve_status'] == UserReservationModel::RESERVE_STATUS_END) {
                // 检查触发规则并发送礼券/投资券，赠送礼品
                $otoTriggerRule->checkReservationRuleAndSendGift($userReservation['id'], $deal['id']);
                // 如果用户使用的投资券，则进行检查和兑换
                if (!empty($userReservation['discount_id'])) {
                    $reservationDiscountService->asyncExchangeDiscount($userReservation['id']);
                }
            }

            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf('预约投资成功, userId: %s, dealId: %s, reserveId: %s, investAmount: %s', $userId, $deal['id'], $userReservation['id'], $investAmount))));
            //添加监控，预约投资成功
            Monitor::add('RESERVE_BID_SUCCESS');

            //清除用户有效预约金额缓存
            $this->_clearUserEffectReserveAmountCache($userId, $reserveId);

            //解锁用户
            $this->_unlockUser($userId);

            return ['respCode' => self::CODE_RESERVE_BID_SUCCESS, 'respMsg' => '预约投资成功'];

        } catch (\Exception $e) {
            //解锁用户
            $isUnlockUser && $this->_unlockUser($userId);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf("reserveId: %s, dealId: %s, userId: %s, assignMoney: %s, respCode: %s, respMsg: %s", $reserveId, $dealId, $userId, $assignMoney, $e->getCode(), $e->getMessage()))));
            return ['respCode' => $e->getCode(), 'respMsg' => $e->getMessage()];
        }
    }

    /**
     * 用户绑定邀请码
     */
    private function _getUserCouponId($userId) {
        $couponId = null;
        $couponService = new CouponService();
        $couponLatest = $couponService->getCouponLatest($userId);
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
            $result['invest_deadline_unit'] = UserReservationModel::INVEST_DEADLINE_UNIT_DAY;
        } else { //月
            $result['invest_deadline_unit'] = UserReservationModel::INVEST_DEADLINE_UNIT_MONTH;
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
                if ($userReservation['invest_deadline_unit'] != UserReservationModel::INVEST_DEADLINE_UNIT_DAY || $deal['repay_time'] != $userReservation['invest_deadline']) {
                    throw new \Exception('投资期限不匹配', 2);
                }
            } else { //按月匹配
                if ($userReservation['invest_deadline_unit'] != UserReservationModel::INVEST_DEADLINE_UNIT_MONTH || $deal['repay_time'] != $userReservation['invest_deadline']) {
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
    private function _lockUser($userId) {
        $lockKey = sprintf(self::KEY_RESERVE_USER_LOCK, $userId);
        $redis = \SiteApp::init()->dataCache;
        $redisState = $redis->setNx($lockKey, 1, self::EXPIRE_RESERVE_USER_LOCK);
        $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
        return $state === 'OK' ? true : false;
    }

    /**
     * 释放用户锁
     */
    private function _unlockUser($userId) {
        $lockKey = sprintf(self::KEY_RESERVE_USER_LOCK, $userId);
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
    private function _getLimitMoney($user, $deal) {
        $limitMoney = 0; //0 没有限制
        $userService = new UserService($user);
        //投资限额校验只应用于 P2P 产品，专享类产品不做校验，企业会员不做校验。
        if (!empty($deal) && $deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL && !$userService->isEnterpriseUser()) {
            $userRiskAssessmentModel = UserRiskAssessmentModel::instance();
            $userRiskData = $userRiskAssessmentModel->getURA($user['id']); //用户风险评估数据
            $riskLevels = RiskAssessmentLevelsModel::instance()->getEnabledLevels();//风险评估等级表
            $limitMoney = $userRiskAssessmentModel->getLimitMoney($riskLevels, $userRiskData);
        }
        return $limitMoney;
    }

    /**
     * 获取用户余额信息
     */
    private function _getUserMoneyInfo($user, $dealType)
    {
        $userService = new UserService();
        $hasBank = $dealType == DealModel::DEAL_TYPE_GENERAL ? true : false; //是否查询存管余额，减少调存管接口
        $moneyInfo = $userService->getMoneyInfo($user, 0, false, $hasBank);

        //读取资产中心存管金额
        $bankMoney = $moneyInfo['svBalance'] = 0;
        if ($hasBank) {
            $userThirdBalanceService = new UserThirdBalanceService();
            $svBalanceResult = $userThirdBalanceService->getUserSupervisionMoney($user['id']);
            //取最小值，防止资产中心扣负
            $bankMoney = min($moneyInfo['bank'], $svBalanceResult['supervisionBalance']); // 存管
            $moneyInfo['svBalance'] = $svBalanceResult['supervisionBalance'];//资产中心余额
        }

        //计算账户余额
        //网贷类型使用网贷p2p账户，专享交易所等使用网信账户
        $accBalance = bcadd($moneyInfo['lc'], $moneyInfo['bonus'], 2);
        if ($dealType == DealModel::DEAL_TYPE_GENERAL) {
            $accBalance = bcadd($bankMoney, $moneyInfo['bonus'], 2);
        }
        $moneyInfo['accBalance'] = $accBalance;//账户余额
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("userId: %s, dealType: %s, moneyInfo:%s", $user['id'], $dealType, json_encode($moneyInfo)))));
        return $moneyInfo;
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
    public function getEffectReserveAmount($user, $userReservation, $readCache = false) {
        //读取缓存
        if ($readCache) {
            $effectReserveAmountCache = $this->_getUserEffectReserveAmountCache($user['id'], $userReservation['id']);
            if ($effectReserveAmountCache !== null) {
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf('userId: %s, reserveId: %s, readCache: %s, effectReserveAmount: %s', $user['id'], $userReservation['id'], $readCache, $effectReserveAmountCache))));
                return $effectReserveAmountCache;
            }
        }

        //获取账户余额
        $dealType = $userReservation['deal_type'];
        $moneyInfo = $this->_getUserMoneyInfo($user, $dealType);
        $balance = $moneyInfo['accBalance']; //账户余额
        $svBalance = $moneyInfo['svBalance']; //资产中心余额

        //余额不足时，发送短信提醒用户充值
        $reserveMinMoney = $this->getReserveMinMoney($userReservation);
        if (bccomp($balance, 0, 2) <= 0 || bccomp($balance, $reserveMinMoney, 2) == -1) {
            //网贷类型还要检查资产中心余额，防止存管查询接口超时导致
            if ($dealType != DealModel::DEAL_TYPE_GENERAL || bccomp($svBalance, 0, 2) <= 0 || bccomp($svBalance, $reserveMinMoney, 2) == -1) {
                $this->_sendChargeRemindSms($userReservation, $user); //发送余额不足短信
            }
        }

        //剩下的预约金额
        $needReserveAmount = bcdiv($userReservation['reserve_amount'] - $userReservation['invest_amount'], 100, 2);

        //计算有效预约金额
        $effectReserveAmount = min($balance, $needReserveAmount);// 用户余额、剩余预约金额选最小值。

        //处理最后一口预约
        $effectReserveAmount = $this->_procLastOneReserve($userReservation, $effectReserveAmount);

        //小于最低预约额
        if (bccomp($effectReserveAmount, $reserveMinMoney, 2) === -1) {
            $effectReserveAmount = 0;
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf('userId: %s, reserveId: %s, needReserveAmount: %s, reserveDealType: %s, reserveMinMoney: %s, effectReserveAmount: %s', $user['id'], $userReservation['id'], $needReserveAmount, $userReservation['deal_type'], $reserveMinMoney, $effectReserveAmount))));
        return $effectReserveAmount;
    }

    /**
     * 获取用户可投资金额
     */
    public function getInvestAmount($user, $userReservation, $readCache, $deal, $assignMoney = 0) {
        $investAmount = 0;
        //剩下的预约金额 小于 标的最低起投金额
        $needReserveAmount = bcdiv($userReservation['reserve_amount'] - $userReservation['invest_amount'], 100, 2);
        if (bccomp($needReserveAmount, $deal['min_loan_money'], 2) === -1) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf('userId: %s, reserveId: %s, dealId: %s, needReserveAmount: %s, minLoanMoney: %s, investAmount: %s', $user['id'], $userReservation['id'], $deal['id'], $needReserveAmount, $deal['min_loan_money'], $investAmount))));
            return $investAmount;
        }

        //获取有效预约额
        $effectReserveAmount = $this->getEffectReserveAmount($user, $userReservation, $readCache);

        //与标的剩余金额计算最小值
        $needDealMoney = bcsub($deal['borrow_amount'], $deal['load_money'], 2); //标剩余金额
        $investAmount = min($effectReserveAmount, $needDealMoney); //计算最小值
        //标的最高投资金额
        if (bccomp($deal['max_loan_money'], 0, 2) === 1) {
            $investAmount = min($investAmount, $deal['max_loan_money']); //计算最小值
        }

        //单笔投资限额
        $limitMoney = $this->_getLimitMoney($user, $deal);
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

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf('userId: %s, reserveId: %s, dealId: %s, needDealMoney: %s, minLoanMoney: %s, maxLoanMoney: %s, investAmount: %s', $user['id'], $userReservation['id'], $deal['id'], $needDealMoney, $deal['min_loan_money'], $deal['max_loan_money'], $investAmount))));
        return $investAmount;
    }

    /**
     * 产品等级校验
     */
    private function _checkProjectRisk($user, $deal, $userReservation, $userRiskData) {
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
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf("项目无产品等级, userId: %s, dealId: %s, projectId: %s, productMix3Name: %s, projectScore: %s", $user['id'], $deal['id'], $deal['project_id'], $productMix3Name, $projectScore))));
            return false;
        }

        //校验结果
        $userRiskData = $userRiskData ? (is_object($userRiskData) ? $userRiskData->getRow() : $userRiskData) : [];
        $projectRiskRet = $dealProjectRiskService->checkReservationRisk($userReservation['user_id'], $projectScore, true, $userRiskData);
        if (empty($projectRiskRet['result'])) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf("用户评级低于三级产品等级, userId: %s, dealId: %s, projectId: %s, productMix3Name: %s, projectScore: %s, projectRiskRet: %s", $user['id'], $deal['id'], $deal['project_id'], $productMix3Name, $projectScore, json_encode($projectRiskRet)))));
            return false;
        }
        return true;
    }

    /**
     * 发送站内信
     */
    private function _sendMessage($userReservation, $investAmount) {
        //主站才发送站内信
        if ($userReservation['site_id'] != 1) {
            return true;
        }
        $msgbox = new MsgBoxService();
        $userId = $userReservation['user_id'];
        $needReserveAmount = bcdiv($userReservation['reserve_amount'] - $userReservation['invest_amount'], 100, 2); //剩余预约金额
        $title = '预约交易成功';
        $investName = $userReservation['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '出借' : '投资';
        if ($userReservation['reserve_status'] == UserReservationModel::RESERVE_STATUS_END) {//完成预约投资
            $content = sprintf('您在%s通过%s预约的标的，预约金额%s元，本次成功%s金额%s元，%s期限%s%s。',
                date('Y-m-d H:i:s', $userReservation['start_time']),
                self::PRODUCT_NAME,
                bcdiv($userReservation['reserve_amount'], 100, 2),
                $investName,
                $investAmount,
                $investName,
                $userReservation['invest_deadline'],
                UserReservationModel::$investDeadLineUnitConfig[$userReservation['invest_deadline_unit']]);
        } else {
            $content = sprintf('您在%s通过%s预约的标的，预约金额%s元，本次成功%s金额%s元，%s期限%s%s，剩余预约金额%s元，我们会继续帮您预约。',
                date('Y-m-d H:i:s', $userReservation['start_time']),
                self::PRODUCT_NAME,
                bcdiv($userReservation['reserve_amount'], 100, 2),
                $investName,
                $investAmount,
                $investName,
                $userReservation['invest_deadline'],
                UserReservationModel::$investDeadLineUnitConfig[$userReservation['invest_deadline_unit']],
                $needReserveAmount);
        }
        return $msgbox->create($userId, 41, $title, $content);
    }

    /**
     * 设置用户有效预约金额缓存
     */
    private function _setUserEffectReserveAmountCache($userId, $reserveId, $effectReserveAmount) {
        $key = sprintf(self::KEY_RESERVE_USER_EFFECT_AMOUNT, $userId, $reserveId);
        $expire = mt_rand(1620, 1800);//打乱缓存过期时间
        $redis = \SiteApp::init()->dataCache;
        $value = serialize($effectReserveAmount);
        return $redis->getRedisInstance()->setex($key, intval($expire), $value);
    }

    /**
     * 获取用户有效预约金额缓存
     */
    private function _getUserEffectReserveAmountCache($userId, $reserveId) {
        $key = sprintf(self::KEY_RESERVE_USER_EFFECT_AMOUNT, $userId, $reserveId);
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
    private function _clearUserEffectReserveAmountCache($userId, $reserveId) {
        $key = sprintf(self::KEY_RESERVE_USER_EFFECT_AMOUNT, $userId, $reserveId);
        $redis = \SiteApp::init()->dataCache;
        return $redis->remove($key);
    }

    /**
     * 发送充值提醒短信
     */
    private function _sendChargeRemindSms($userReservation, $user)
    {
        $sendSmsKey = sprintf('RESERVE_SEND_CHARGE_REMIND_SMS_%d', $userReservation['id']);
        $expire = max($userReservation['end_time'] - $userReservation['start_time'], 60);
        $redis = \SiteApp::init()->dataCache;
        $redisState = $redis->setNx($sendSmsKey, 1, $expire);
        $state = is_object($redisState) ? $redisState->getPayload(): 'FAIL';
        if ($state === 'OK') {
            $tpl = 'TPL_SMS_RESERVE_CHARGE_REMIND';

            $mobile = $user['user_type'] == UserModel::USER_TYPE_ENTERPRISE ? 'enterprise' : $user['mobile'];
            $smsContent = [
                'accountName' => $userReservation['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '网贷P2P账户' : '网信账户',
                'investName' => $userReservation['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '出借' : '投资',
                'platformName' => $userReservation['site_id'] == 100 ? '网信普惠' : '网信',
            ];
            SmsServer::instance()->send($mobile, $tpl, $smsContent, $user['id'], $userReservation['site_id']);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf('send charge remind sms success, userId: %s, reserveId: %s', $user['id'], $userReservation['id']))));
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
        $msgbox = new MsgBoxService();
        $otoTriggerRule = new OtoTriggerRuleService();
        $reservationDiscountService = new ReservationDiscountService();
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_EXPIRE, 'begin process expired user reservations')));
        $reserveId = 0;
        while ($reserveList = $userReservationModel->getExpiredUserReserveListByLimit($processTime, $pageSize, $reserveId)) {
            foreach ($reserveList as $userReservation) {
                $reserveId = $userReservation['id'];
                $userId = $userReservation['user_id'];
                //检查预约状态，预约结束无需处理
                if ($userReservation['reserve_status'] != UserReservationModel::RESERVE_STATUS_ING) {
                    continue;
                }
                try {
                    //将用户预约设置为过期
                    $updateUserReservation = $userReservationModel->cancelUserReserveById($reserveId, $userId);
                    if ($updateUserReservation <= 0) {
                        throw new \Exception(sprintf('expireUserReservation, reserveId: %s, userId: %s', $reserveId, $userId));
                    }

                    //发送站内信
                    $title = '预约到期';
                    $investName = $userReservation['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '出借' : '投资';
                    $content = sprintf('您在%s通过%s预约的标的，预约金额%s元，预约%s期限%s%s，已到期，实际%s金额%s元。',
                        date('Y-m-d H:i:s', $userReservation['start_time']),
                        self::PRODUCT_NAME,
                        bcdiv($userReservation['reserve_amount'], 100, 2),
                        $investName,
                        $userReservation['invest_deadline'],
                        UserReservationModel::$investDeadLineUnitConfig[$userReservation['invest_deadline_unit']],
                        $investName,
                        bcdiv($userReservation['invest_amount'], 100, 2));
                    $msgbox->create($userReservation['user_id'], 41, $title, $content);

                    // 检查触发规则并发送礼券/投资券，赠送礼品
                    $otoTriggerRule->checkReservationRuleAndSendGift($reserveId);

                    // 如果用户使用的投资券，则进行检查和兑换
                    if (!empty($userReservation['discount_id'])) {
                        $reservationDiscountService->asyncExchangeDiscount($userReservation['id']);
                    }

                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_EXPIRE, sprintf("预约过期成功, reserveId: %s, userId: %s", $reserveId, $userId))));
               } catch (\Exception $e) {
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_EXPIRE, sprintf("预约过期失败, errmsg: %s", $e->getMessage()))));
               }
            }
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_EXPIRE, 'end process expired user reservations')));
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
            $key =  $investConf['invest_deadline'].UserReservationModel::$investDeadLineUnitConfig[$investConf['invest_deadline_unit']];
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
    public function processPrepare($reserveId, $userId, $investAmount, $orderId) {
        $userReservationModel = UserReservationModel::instance();
        //幂等检查
        $userReservation = $userReservationModel->find($reserveId);
        if (!empty($orderId) && !empty($userReservation['proc_id']) && $userReservation['proc_id'] == $orderId
            && $userReservation['proc_status'] == UserReservationModel::PROC_STATUS_ING) {
            return true;
        }

        $investAmountFen = bcmul($investAmount, 100);
        $result = $userReservationModel->updateProcessing($reserveId, $userId, $investAmountFen, $orderId);
        if (!$result) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf("RESERVATION_BID_EXCEPTION, 更新处理中失败, reserveId: %s, userId: %d, investAmount: %d", $reserveId, $userId, $investAmount))));
        }
        return $result;
    }

    /**
     * 恢复处理状态
     */
    public function restoreProcStatus($reserveId, $userId, $orderId) {
        $userReservationModel = UserReservationModel::instance();
        //幂等检查
        $userReservation = $userReservationModel->find($reserveId);
        if ($userReservation['proc_status'] == UserReservationModel::PROC_STATUS_NORMAL) {
            return true;
        }
        $result = $userReservationModel->restoreProcStatus($reserveId, $userId, $orderId);
        if (!$result) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, self::LOG_IDENTIFY_BID, sprintf("RESERVATION_BID_EXCEPTION, 恢复处理状态失败, reserveId: %s, userId: %d", $reserveId, $userId))));
        }
        return $result;
    }

    /**
     * 处理完成
     */
    public function processComplete($reserveId, $userId, $dealId, $investAmount, $orderId) {

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

            // 更新满标的所属站点
            $dealAfterBid = $dealModel->find($dealId);
            if ($dealAfterBid['deal_status'] == DealModel::$DEAL_STATUS['full']) {
                $dealTagService = new DealTagService();
                $tagNameList = $dealTagService->getTagListByDealId($dealId);
                // 掌众等启动类型1的标的满标时，需要把站点更新为“普通标(3个月及以上)”
                if (!empty($tagNameList) && in_array(ReservationMatchModel::TAGNAME_RESERVATION_1, $tagNameList)) {
                    \FP::import("app.deal");
                    $siteId = $GLOBALS['sys_config']['TEMPLATE_LIST']['普通标(3个月及以上)'];
                    update_deal_site($dealId, array($siteId));
                }
            }

            //更新投资金额
            $investAmountFen = bcmul($investAmount, 100);
            $updateInvestAmount = $userReservationModel->updateInvestAmount($reserveId, $userId, $investAmountFen, $orderId);
            if (!$updateInvestAmount) {
                throw new \Exception(sprintf('userReservationModel::updateUserReservation, reserveId: %s, userId: %s', $reserveId, $userId));
            }

            //添加交易记录
            $addRelation = $reservationDealLoadModel->addRelation($reserveId, $loadId, $userId, $dealId, $investAmountFen);
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
            $loadIds = [];
            foreach($relationList as $relation) {
                $relationId = $relation['id'];
                $loadIds[] = $relation['load_id'];
            }

            //投资表
            $dealIds = [];
            $loadSql = sprintf('select id, deal_id, money from firstp2p_deal_load where id in (%s)', implode(',', $loadIds));
            $loadList = $db->getAll($loadSql);
            $dealMoneyMap = [];
            foreach ($loadList as $loadInfo) {
                $dealIds[] = $loadInfo['deal_id'];
                if (!isset($dealMoneyMap[$loadInfo['deal_id']])) {
                    $dealMoneyMap[$loadInfo['deal_id']] = 0;
                }
                $dealMoneyMap[$loadInfo['deal_id']] = bcadd($dealMoneyMap[$loadInfo['deal_id']], $loadInfo['money'], 2);
            }

            //标的
            $dealSql = sprintf('select id, type_id, loantype, repay_time from firstp2p_deal where id in (%s)', implode(',', $dealIds));
            $dealList = $db->getAll($dealSql);
            $dealGroupMap = [];
            foreach ($dealList as $dealInfo) {
                $deadline = $dealInfo['repay_time'];
                $type_id = $dealInfo['type_id'];
                if ($dealInfo['loantype'] == 5) {
                    $deadlineUnit = userReservationModel::INVEST_DEADLINE_UNIT_DAY;
                } else {
                    $deadlineUnit = userReservationModel::INVEST_DEADLINE_UNIT_MONTH;
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
        $reservationConfService = new ReservationConfService();

        $db = \libs\db\Db::getInstance('firstp2p', 'slave');
        $totalAmount = 0;//总的有效预约金额
        $reserveId = 0;
        while ($reserveList = $userReservationModel->getUserReserveListByLimit($processTime, $pageSize, $reserveId, $deadline, $deadlineUnit)) {
            $userIds = [];
            foreach ($reserveList as $userReservation) {
                $reserveId = $userReservation['id'];
                $userIds[] = $userReservation['user_id'];
            }

            //用户账户
            $userSql = sprintf('select id, money from firstp2p_user where id in (%s)', implode(',', $userIds));
            $userList = $db->getAll($userSql);
            $userMap = [];
            foreach ($userList as $value) {
                $userMap[$value['id']] = $value['money'];
            }

            //资产中心
            $thirdSql = sprintf('select id, user_id, supervision_balance from firstp2p_user_third_balance where user_id in (%s)', implode(',', $userIds));
            $thirdList = $db->getAll($thirdSql);
            $thirdMap = [];
            foreach ($thirdList as $value) {
                $thirdMap[$value['user_id']] = $value['supervision_balance'];
            }

            foreach($reserveList as $userReservation) {
                $needReserveAmount = bcdiv($userReservation['reserve_amount'] - $userReservation['invest_amount'], 100, 2); //剩下的预约金额
                $userBalance = isset($userMap[$userReservation['user_id']]) ? $userMap[$userReservation['user_id']] : 0;
                $thirdBalance = isset($thirdMap[$userReservation['user_id']]) ? $thirdMap[$userReservation['user_id']] : 0;
                $totalBalance = bcadd($userBalance, $thirdBalance, 2);
                $reserveMinMoney = $this->getReserveMinMoney($userReservation);
                if (bccomp($totalBalance, $reserveMinMoney, 2) >= 0) {
                    $effectReserveAmount = min($totalBalance, $needReserveAmount);
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
    public function getEffectReserveCountByUserId($userId) {
        $count = 0;
        $reserveList = UserReservationModel::instance()->getUserReserveList($userId, UserReservationModel::RESERVE_STATUS_ING);
        foreach ($reserveList as $reserve) {
            if ($reserve['end_time'] > time()) {
                $count += 1;
            }
        }
        return $count;
    }

    public function isInvestByUserId($userId)
    {
        $db = \libs\db\Db::getInstance('firstp2p', 'slave');
        $Sql = "select user_id from firstp2p_user_reservation where user_id = {$userId} and reserve_status = 1 and invest_amount > 0 limit 1";
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
            return ReservationEntraModel::RESERVE_DEFAULT_MIN_AMOUNT;
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

        if ($dealType !== DealModel::DEAL_TYPE_GENERAL) {
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
                $userId = $userReservation['user_id'];

                //检查预约期限匹配标的
                if (!isset($deadlineMap[$userReservation['invest_deadline'] . '_' . $userReservation['invest_deadline_unit']])) {
                    continue;
                }

                //发过信息披露
                if ($this->_getSentDisclosureUserCache($userId, $reserveId)) {
                    continue;
                }

                //预约已经过期
                $userReservation = $userReservationModel->find($reserveId);
                if ($userReservation['reserve_status'] != UserReservationModel::RESERVE_STATUS_ING || $userReservation['end_time'] < time()) {
                    continue;
                }

                //计算可投资金额，优先读取缓存
                $user = UserModel::instance()->find($userId);
                $investAmount = $this->getEffectReserveAmount($user, $userReservation, true);
                $expireTime = max($userReservation['end_time'] - $userReservation['start_time'], 60);
                //有效用户只发送一次信息披露
                if (bccomp($investAmount, 0, 2) === 1 && $this->_setSentDisclosureUserCache($userId, $reserveId, $expireTime)) {
                    //主站app发站内信，其他平台发短信
                    if ($userReservation['site_id'] == 1 && $userReservation['reserve_referer'] == UserReservationModel::RESERVE_REFERER_APP) {
                        $msgbox = new MsgBoxService();
                        $title = '即将开始匹配';
                        $content = sprintf('您在随心约预约标的即将进行匹配，请您前往随心约预约专区的“信息披露”页面内查看预匹配的项目详情。');
                        $msgbox->create($userId, 41, $title, $content);
                    } else {
                        $tpl = 'TPL_SMS_RESERVE_DISCLOSURE';
                        $user = UserModel::instance()->find($userId);
                        $mobile = $user['user_type'] == UserModel::USER_TYPE_ENTERPRISE ? 'enterprise' : $user['mobile'];
                        $smsContent = [];
                        SmsServer::instance()->send($mobile, $tpl, $smsContent, $userId, 100);
                    }
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, sprintf("发送信息披露成功, reserveId: %d, userId: %d", $reserveId, $userId))));
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
    private function _setSentDisclosureUserCache($userId, $reserveId, $expireTime) {
        $redis = \SiteApp::init()->dataCache;
        $sentKey = sprintf(self::KEY_RESERVE_SENT_DISCLOSURE_USER, $userId, $reserveId);
        return $redis->setNx($sentKey, time(), $expireTime);
    }

    /**
     * 获取已发送信息披露的用户缓存
     */
    private function _getSentDisclosureUserCache($userId, $reserveId) {
        $redis = \SiteApp::init()->dataCache;
        $sentKey = sprintf(self::KEY_RESERVE_SENT_DISCLOSURE_USER, $userId, $reserveId);
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
