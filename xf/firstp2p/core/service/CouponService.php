<?php
/**
 * Coupon.php.
 *
 * @date 2014-02-25 14:56
 *
 * @author liangqiang@ucfgroup.com
 */

namespace core\service;

use core\dao\CouponLevelRebateModel;
use core\dao\CouponLogRegModel;
use core\dao\CouponSpecialModel;
use core\dao\CouponExtraModel;
use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\DealLoanTypeModel;
use core\dao\UserModel;
use core\dao\UserGroupModel;
use core\dao\UserBasicGroupModel;
use core\dao\CouponLogModel;
use core\dao\FinanceQueueModel;
use core\dao\CouponDealModel;
use libs\lock\LockFactory;
use libs\utils\Logger;
use NCFGroup\Task\Services\TaskService as GTaskService;
use core\event\CouponLog\ConsumeEvent;
use libs\utils\Rpc;
use NCFGroup\Protos\Duotou\RequestCommon;
use core\dao\CouponBindModel;
use core\service\CouponMonitorService;
use core\service\UserProfileService;
use core\service\DealService;
use core\service\CouponDealService;
use libs\sms\SmsServer;
use core\service\ncfph\DealService as NcfphDealService;
use core\service\third\ThirdDealService;


require_once APP_ROOT_PATH.'system/utils/es_session.php';

/**
 * 邀请码服务接口.
 */
class CouponService extends BaseService
{
    /**
     * 类型-注册.
     */
    const TYPE_SIGNUP = 1;

    /**
     * 类型-投资.
     */
    const TYPE_DEAL = 2;

    /**
     * 投资来源附加返利类型 用户tag.
     *
     * @var int
     */
    const TYPE_USER_TAG = 20;

    /**
     * 投资来源附加返利类型 标tag.
     *
     * @var int
     */
    const TYPE_DEAL_TAG = 21;

    /**
     * 投资默认全局邀请码
     */
    const SHORT_ALIAS_DEFAULT = 'F00000';

    const PAY_STATUS_NO_IDPASSED = -2;

    const PAY_STATUS_IDPASSED = -1;

    const PAY_STATUS_NOT_PAY = 0;

    const PAY_STATUS_AUTO_PAID = 1;

    const PAY_STATUS_PAID = 2;

    const PAY_STATUS_FINANCE_AUDIT = 3;

    const PAY_STATUS_FINANCE_REJECTED = 4;

    const PAY_STATUS_PAYING = 5;

    const PAY_STATUS_OFFLINE = 6; // 线下结算

    /**
     * 邀请码用户ID编码长度.
     */
    const SHORT_ALIAS_USER_ID_LENGTH = 5;

    /**
     * 32进制用户id基数前缀 pow(32,4)*16 = H0000.
     */
    const COUPON_HEX32_BASE_NUMBER = 16777216;

    /**
     * 邀请码链接存储短码的cookie的key.
     */
    const LINK_COUPON_KEY = 'link_coupon';

    /**
     * 邀请码是否绑定.
     */
    const UNFIXED = 1; //邀请码未绑定
    const FIXED = 1; //邀请码已经绑定

    /**
     * 默认返利系数.
     */
    public static $factor_default = 1.0000;

    /**
     * 缓存时间.
     */
    protected static $cache_time = 180;

    /**
     * 邀请码等级信息.
     */
    protected $levels;

    /**
     * 邀请码记录是否同步.
     */
    const COUPON_ASYNCHRONOUS_DEFAULT = 0;
    const COUPON_SYNCHRONOUS = 1; // 同步

    const COUPON_REFERER_REBATE_RATIO = 0.91; // 返利系数
    /*
     * 业务系统的邀请记录coupon_log_model实例
     */
    public $coupon_log_dao;

    public $module;

    public function __construct($module = CouponLogService::MODULE_TYPE_P2P)
    {
        $this->module = $module;
        if (empty($this->module) || !in_array($this->module, CouponLogService::$module_map)) {
            throw new \Exception('module['.$module.'] is not exist!');
        }
        $this->coupon_log_dao = CouponLogModel::getInstance($module);
    }

    /**
     * 校验邀请码，包括判断过期有效逻辑.
     *
     * @param $short_alias 邀请码
     * @param int $deal_id 订单id 有值则校验该订单下的邀请码信息，0则校验全局邀请码信息
     *
     * @return bool 正确返回邀请码信息，错误返回false
     */
    public function checkCoupon($short_alias)
    {
        $coupon = $this->queryCoupon($short_alias, true);
        if (!empty($coupon) && $coupon['is_effect']) {
            return $coupon;
        } else {
            return false;
        }
    }

    /**
     * 查询邀请码信息缓存信息.
     *
     * @param $short_alias 邀请码短码
     * @param $is_finance_passed_needed 是否需要资金托管开户认证。资金托管要求用户开户，需用户通过通过实名认证或者曾经使用过邀请码。
     * @param $deal_id 订单id 有值则校验该订单下的邀请码信息，0则校验全局邀请码信息
     *
     * @return bool
     */
    public function queryCoupon($short_alias, $is_finance_passed_needed = false)
    {
        return \SiteApp::init()->dataCache->call(new CouponService($this->module), 'queryCouponNoCache', array($short_alias, $is_finance_passed_needed,
                                                                                                  ), CouponService::$cache_time);
    }

    /**
     * 查询邀请码信息.
     *
     * @param $short_alias 邀请码短码
     * @param $is_finance_passed_needed 是否需要资金托管开户认证。资金托管要求用户开户，需用户通过通过实名认证或者曾经使用过邀请码。
     * @param $deal_id 订单id 有值则校验该订单下的邀请码信息，0则校验全局邀请码信息
     *
     * @return bool
     */
    public function queryCouponNoCacheOld($short_alias, $is_finance_passed_needed = false, $deal_id = 0)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $short_alias, $is_finance_passed_needed, $deal_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        if (empty($short_alias)) {
            return false;
        }
        // 不允许输入i,I和o,O
        if (false != stripos($short_alias, 'i') || false != stripos($short_alias, 'o')) {
            return false;
        }

        //黄金使用全局邀请码配置
        if (in_array($this->module, array(CouponLogService::MODULE_TYPE_GOLD, CouponLogService::MODULE_TYPE_GOLDC, CouponLogService::MODULE_TYPE_DARKMOON, CouponLogService::MODULE_TYPE_NCFPH))) {
            $deal_id = 0;
        }

        //如果标类型为掌众的比例取全局
        if (!empty($deal_id) && $this->isRebateWithGlobalConfig($deal_id)) {
            $deal_id = 0;
        }

        // 去除空格
        $short_alias = str_replace(' ', '', $short_alias);
        $short_alias = strtoupper($short_alias);
        $remark = '您可获额外年化rebate_ratio%的返利，返利的计算及发放时间参见具体项目详情页内的规则描述。';

        //标的信息及结算系数,按季按月的备注比例乘以系数
        $referer_rebate_ratio_factor = self::$factor_default;
        if ($deal_id) {
            $deal_info = DealModel::instance()->find($deal_id, 'id,deal_type,loantype,repay_time,deal_status');
            $coupon_log_service = new CouponLogService();
            $referer_rebate_ratio_factor = $coupon_log_service->getRebateFactor($deal_info);
        }

        //特殊邀请码
        $is_special = 0;
        $coupon_special = $this->getCouponSpecial($short_alias, $deal_id);
        if (!empty($coupon_special)) {
            Logger::info(implode(' | ', array_merge($log_info, array('coupon_special', json_encode($coupon_special)))));
            $coupon_special['rebate_ratio_show'] = floatval($coupon_special['rebate_ratio'] * $referer_rebate_ratio_factor);
            $coupon_special['remark'] = str_replace('rebate_ratio', $coupon_special['rebate_ratio_show'], $remark);
            if (empty($coupon_special['refer_user_id'])) {
                return $coupon_special;
            } else {
                $is_special = 1;
                $user_id = $coupon_special['refer_user_id'];
            }
        }

        //特殊邀请码
        if (0 == $is_special) {
            //邀请码所属会员信息
            $user_id = CouponService::hexToUserId($short_alias); // 邀请码16进制转10进制用户ID
            if (empty($user_id)) {
                Logger::info(implode(' | ', array_merge($log_info, array('error user_id'))));

                return false;
            }
        }

        //邀请人信息
        if (!empty($user_id)) {
            $user_model = new UserModel();
            $user = $user_model->find($user_id, 'id,user_name,real_name,idcardpassed,coupon_level_id,is_delete,is_effect,coupon_disable,group_id', true);
            if (empty($user) || $user['is_delete'] || empty($user['is_effect'])) {
                Logger::info(implode(' | ', array_merge($log_info, array('error user info'))));

                return false;
            }
        }
        //返回特殊邀请码
        if (1 == $is_special) {
            $coupon_special['refer_user_name'] = $user['user_name'];
            $coupon_special['group_id'] = $user['group_id'];
            $coupon_special['referer_rebate_ratio'] = $this->getReferer_rebate_ratio($coupon_special['referer_rebate_ratio'], $coupon_special['group_id']);
            Logger::info(implode(' | ', array_merge($log_info, array('coupon_special', json_encode($coupon_special)))));

            return $coupon_special;
        }
        // 资金托管开户认证，需要通过实名认证或使用过邀请码
        if ($is_finance_passed_needed) {
            $is_idcardpassed = true;
            $payment_service_obj = new PaymentService();
            if (!$payment_service_obj->hasRegister($user_id) || !$user['real_name'] || 1 != $user['idcardpassed']) {
                $is_idcardpassed = false;
            }
            if (!$is_idcardpassed && !$this->isCouponUsed($user_id)) {
                Logger::info(implode(' | ', array_merge($log_info, array('idcardpassed fail'))));

                return false;
            }
        }
        unset($user['real_name'], $user['idcardpassed']);

        // 获取邀请码信息
        $prefix = CouponService::getShortAliasPrefix($short_alias);
        if (empty($prefix)) {
            Logger::info(implode(' | ', array_merge($log_info, array('error prefix'))));
            return false;
        }
        $coupon_level_rebate_model = new CouponLevelRebateModel();
        $result_coupon = $coupon_level_rebate_model->queryCoupon($deal_id, $user['coupon_level_id'], $prefix);
        if (empty($result_coupon)) {
            Logger::info(implode(' | ', array_merge($log_info, array('error coupon info'))));

            return false;
        }

        // 查询等级信息获取机构用户
        $coupon_level_service = new CouponLevelService();
        $levels = $coupon_level_service->getAllLevels();
        if (empty($levels)) {
            Logger::info(implode(' | ', array_merge($log_info, array('error levels'))));
            return false;
        }

        $now = get_gmtime();
        $result_coupon['short_alias'] = $short_alias;
        $result_coupon['refer_user_id'] = $user_id;
        $result_coupon['refer_user_name'] = $user['user_name'];
        $result_coupon['coupon_disable'] = $user['coupon_disable'];
        $result_coupon['agency_user_id'] = $levels[$result_coupon['level_id']]['agency_user_id'];
        $result_coupon['group_id'] = $levels[$result_coupon['level_id']]['group_id'];
        $result_coupon['rebate_ratio_show'] = floatval($result_coupon['rebate_ratio'] * $referer_rebate_ratio_factor);
        $result_coupon['referer_rebate_ratio'] = $this->getReferer_rebate_ratio($result_coupon['referer_rebate_ratio'], $result_coupon['group_id']);
        $result_coupon['remark'] = str_replace('rebate_ratio', $result_coupon['rebate_ratio_show'], $remark);
        Logger::info(implode(' | ', array_merge($log_info, array('coupon', json_encode($result_coupon)))));

        return empty($result_coupon) ? false : $result_coupon;
    }


    public function queryCouponNoCache($short_alias, $is_finance_passed_needed = false){

        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $short_alias, $is_finance_passed_needed);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        if (empty($short_alias)) {
            Logger::error(implode(' | ', array_merge($log_info, array('short_alias is null'))));
            return false;
        }
        // 不允许输入i,I和o,O
        if (false != stripos($short_alias, 'i') || false != stripos($short_alias, 'o')) {
            Logger::error(implode(' | ', array_merge($log_info, array('i,I,o,O is deny'))));
            return false;
        }

        $short_alias = str_replace(' ', '', $short_alias);
        $short_alias = strtoupper($short_alias);
        $user_id = CouponService::shortAliasToReferUserId($short_alias); //邀请码转用户id，包含特殊邀请码
        if (empty($user_id)) {
            Logger::error(implode(' | ', array_merge($log_info, array('error user_id'))));
            return false;
        }

        $user = $this->getUserById($user_id);
        if (empty($user) || $user['is_delete'] || empty($user['is_effect'])) {
            Logger::error(implode(' | ', array_merge($log_info, array('error user info'))));
            return false;
        }

        // 资金托管开户认证，需要通过实名认证或使用过邀请码
        if ($is_finance_passed_needed) {
            $is_idcardpassed = true;
            $payment_service_obj = new PaymentService();
            if (!$payment_service_obj->hasRegister($user_id) || !$user['real_name'] || 1 != $user['idcardpassed']) {
                $is_idcardpassed = false;
            }
            if (!$is_idcardpassed && !$this->isCouponUsed($user_id)) {
                Logger::error(implode(' | ', array_merge($log_info, array('idcardpassed fail'))));
                return false;
            }
        }

        $userCoupon = $this->getUserCoupon($user_id);
        if(empty($userCoupon)){
            Logger::error(implode(' | ', array_merge($log_info, array('userCoupon is empty'))));
            return false;
        }

        Logger::info(implode(' | ', array_merge($log_info, array('coupon', json_encode($userCoupon)))));

        return $userCoupon;

    }
    /**
     * 根据用户ID获取第一个邀请码
     *
     * @param $userId 绑定用户ID
     *
     * @return array|bool 邀请码短码信息
     */
    public function getOneUserCoupon($userId)
    {
        return $this->getUserCoupon($userId);
    }

    /**
     *获取用户邀请码 
     */
    public function getUserCoupon($userId)
    {   
        $userCouponLevelService = new UserCouponLevelService();
        $userInfo = $this->getUserById(intval($userId));
        $levelInfo = $userCouponLevelService->getLevelById(intval($userInfo['new_coupon_level_id']));
        $groupInfo = $userCouponLevelService->getGroupById(intval($userInfo['group_id']));

        if(empty($userInfo) || empty($levelInfo) || empty($groupInfo)){
            Logger::error(implode(' | ', array(__CLASS__,__FUNCTION__,__LINE__,'userId:'. $userId,'用户信息不正确')));
            return false;
        }

        //判断用户邀请码是否无效，不能绑码
        $coupon['is_effect'] = 1;
        if(!$this->shortAliasIsEffect($userInfo,$levelInfo,$groupInfo)){
            $coupon['is_effect'] = 0;
            Logger::error(implode(' | ', array(__CLASS__,__FUNCTION__,__LINE__,'userId:'. $userId,'用户邀请码无效')));
        }

        if($groupInfo['service_status'] == 1){
            $coupon['referer_rebate_ratio'] = $levelInfo['rebate_ratio'];
            $coupon['agency_rebate_ratio'] = $userCouponLevelService->getAgencyRebateRatio($levelInfo,$groupInfo);
            $coupon['referer_rebate_ratio'] = $this->getReferer_rebate_ratio($coupon['referer_rebate_ratio'],$groupInfo['id']);
        }else{
            $coupon['referer_rebate_ratio'] = 0;
            $coupon['agency_rebate_ratio'] = 0;
            Logger::info(implode(' | ', array(__CLASS__,__FUNCTION__,__LINE__,'userId:'. $userId,'用户无服务能力')));
        }

        $coupon['short_alias'] = CouponService::userIdToHex($userId,$groupInfo['prefix']);
        $coupon['short_alias'] = strtoupper($coupon['short_alias']);
        $coupon['refer_user_id'] = $userInfo['id'];
        $coupon['refer_user_name'] = $userInfo['user_name'];
        $coupon['group_id'] = $groupInfo['id'];
        $coupon['level_id'] = $levelInfo['id'];
        $coupon['service_status'] =  $groupInfo['service_status'];
        $coupon['coupon_disable'] = intval($userInfo['coupon_disable']);
        $coupon['agency_user_id'] = $groupInfo['agency_user_id'];
        return $coupon;
    }

    /**
     * 判断是否使用过邀请码
     *
     * @param $user_id
     *
     * @return bool|int
     */
    public function isCouponUsed($user_id)
    {
        return $this->coupon_log_dao->isCouponUsed($user_id);
    }


    /**
     *兼容一人多码逻辑
     */
    public function getUserCoupons($userId){
        $coupons = array();
        $coupon = $this->getUserCoupon($userId);
        if(!empty($coupon)){
            $coupons[$coupon['short_alias']] = $coupon;
        }
        return $coupons;
    }

    /**
     * 查询特殊邀请码
     *
     * @param $short_alias
     *
     * @return bool|\libs\db\Model
     */
    public function getCouponSpecial($short_alias, $deal_id = 0)
    {
        if (empty($short_alias)) {
            return false;
        }
        $short_alias = strtoupper($short_alias);
        $coupon_special_model = new CouponSpecialModel();
        $coupon = $coupon_special_model->getByShortAlias($short_alias, $deal_id);
        if (empty($coupon)) {
            return false;
        }
        $now = get_gmtime();
        $coupon['is_valid'] = $now >= $coupon['valid_begin'] && $now <= $coupon['valid_end'];
        $coupon['valid_begin'] = to_date($coupon['valid_begin']);
        $coupon['valid_end'] = to_date($coupon['valid_end']);

        return $coupon;
    }

    /**
     * 投资邀请记录(注册不调用).
     *
     * @param $type 邀请码记录类型
     * @param $short_alias 邀请码短码
     * @param $consume_user_id 邀请码ID（长码）可空
     * @param $deal_load_id 投标ID
     * @param $coupon_fields 投后台添加邀请码的附加信息
     * @param $is_asynchronous 0为缺省，1为同步，2为异步
     * @param $handshakeStatus 0为默认 1开始，2结束
     *
     * @return bool|null 消费结果
     */
    public function consume(
        $deal_load_id,
        $short_alias = '',
        $consume_user_id = 0,
        $coupon_fields = array(),
                            $is_asynchronous = self::COUPON_ASYNCHRONOUS_DEFAULT
    ) {
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $deal_load_id, $short_alias, $consume_user_id, json_encode($coupon_fields),
                          $is_asynchronous, );

        //邀请码消费开关
        $turn_on_coupon_consume_enable = app_conf('COUPON_CONSUME_ENABLE');
        if ('N' == $turn_on_coupon_consume_enable) {
            Logger::info(implode(' | ', array_merge($log_info, array('COUPON_CONSUME_ENABLE is closed'))));

            return true;
        }
        // 多投宝同步调用
        if (CouponLogService::MODULE_TYPE_P2P == $this->module && self::COUPON_ASYNCHRONOUS_DEFAULT == $is_asynchronous && isset($GLOBALS['sys_config']['IS_COUPON_LOG_ASYNCHRONOUS']) && rand(1, 100) <= intval($GLOBALS['sys_config']['IS_COUPON_LOG_ASYNCHRONOUS'])) {
            Logger::info(implode(' | ', array_merge($log_info, array('asynchronous insert queue start'))));
            $params = array(
                'module' => $this->module,
                'deal_load_id' => $deal_load_id,
                'short_alias' => $short_alias,
                'consume_user_id' => $consume_user_id,
                'coupon_fields' => $coupon_fields,
            );
            $event = new ConsumeEvent($params);
            $task_service = new GTaskService();
            $rs = $task_service->doBackground($event, 1);
            if (empty($rs)) {
                Logger::info(implode(' | ', array_merge($log_info, array('insert queue fail consumeEvent'))));
            }
            Logger::info(implode(' | ', array_merge($log_info, array('insert queue end'))));

            return true;
        }

        CouponMonitorService::process(CouponMonitorService::ITEM_CONSUME, CouponMonitorService::TOTAL, $this->module);
        $rs = $this->consumeSynchronous($deal_load_id, $short_alias, $consume_user_id, $coupon_fields);
        if ($rs) {
            CouponMonitorService::process(CouponMonitorService::ITEM_CONSUME, CouponMonitorService::SUCCESS, $this->module, $rs['deal_type']);
        } else {
            CouponMonitorService::process(CouponMonitorService::ITEM_CONSUME, CouponMonitorService::FAILED, $this->module);
        }

        return $rs;
    }

    /**
     * 消费邀请码(包括注册和投资).
     *
     * @param $type 邀请码记录类型
     * @param $short_alias 邀请码短码
     * @param $consume_user_id 邀请码ID（长码）可空
     * @param $deal_load_id 投标ID
     * @param $coupon_fields 投后台添加邀请码的附加信息
     * @param $handshakeStatus 握手状态 参考const
     *
     * @return bool|null 消费结果
     */
    public function consumeSynchronous($deal_load_id, $short_alias = '', $consume_user_id = 0, $coupon_fields = array())
    {
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $deal_load_id, $short_alias, $consume_user_id, json_encode($coupon_fields));
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $type = $this->module;

        // 同一个标或者注册，只有一条返利记录
        $existDealLoad = $this->coupon_log_dao->findByDealLoadId($deal_load_id, $consume_user_id);
        if (!empty($existDealLoad)) {
            Logger::info(implode(' | ', array_merge($log_info, array('exists coupon log, skip'))));

            return $existDealLoad;
        }

        //如果是p2p还是需要校验dealLoadId 是不是存在的
        if (CouponLogService::MODULE_TYPE_P2P == $type) {
            $deal_load = DealLoadModel::instance()->find($deal_load_id, 'deal_id,money,source_type,user_id,short_alias');
            if (empty($deal_load)) {
                Logger::error(implode(' | ', array_merge($log_info, array('deal load info error deal_load_id '.$deal_load_id))));

                return false;
            }
            $consume_user_id = $deal_load['user_id'];
            $short_alias = $deal_load['short_alias'];
        }

        // 绑码信息
        $coupon_bind_service = new CouponBindService();
        $coupon_bin_info = $coupon_bind_service->getByUserId($consume_user_id, $short_alias);
        if (empty($coupon_bin_info)) {
            Logger::error(implode(' | ', array_merge($log_info, array('coupon bind info is empty'))));
            \libs\utils\Alarm::push('couponbind', '用户绑定关系不存在', json_encode(array('user_d' => $consume_user_id)));

            return false;
        }
        $is_alarm = 1;
        // 排除投资记录用自己码的告警,用自己码绑定表中优惠码为空
        if (!empty($short_alias)) {
            $coupon_info = $this->checkCoupon($short_alias);
            if (!empty($coupon_info) && $coupon_info['refer_user_id'] == $consume_user_id && empty($coupon_bin_info['short_alias'])) {
                $is_alarm = 0;
                unset($coupon_info);
            }
        }
        //用户传过来有效码，实际已绑定，但是读出来未绑定，这种情况很少
        if (!$coupon_bin_info['is_fixed'] && !empty($short_alias) && 1 == $is_alarm) {
            usleep(10000);
            $couponBindModel = new CouponBindModel();
            $result = $couponBindModel->getByUserIds(array($consume_user_id), false); //读存库
            $coupon_bin_info = $result[$consume_user_id];
            \libs\utils\Alarm::push('couponbind', '用户传过来有效码，实际已绑定，但是读出来未绑定', json_encode($coupon_bin_info));
        }

        if (!empty($coupon_bin_info) && !empty($short_alias) && $coupon_bin_info['short_alias'] != $short_alias && 1 == $is_alarm) {
            Logger::error(implode(' | ', array_merge($log_info, array(json_encode($coupon_bin_info), $short_alias,
                                                                      'coupon bind short_alias Not equal to params short_alias', ))));
            $alarm_data = $coupon_bin_info;
            $alarm_data['params_short_alias'] = $short_alias;
            $alarm_data['deal_load_id'] = $deal_load_id;
            \libs\utils\Alarm::push('couponbind', '邀请码绑定关系读出来的和传过来的不一致', json_encode($alarm_data));
            unset($alarm_data);
        }

        $short_alias = empty($coupon_bin_info['short_alias']) ? '' : $coupon_bin_info['short_alias'];

        $deal_id = empty($deal_load) ? 0 : $deal_load['deal_id'];
        $deal_id = empty($deal_id) ? intval($coupon_fields['deal_id']) : $deal_id;

        //投资客户系数
        $coupon_fields['discount_ratio'] = empty($coupon_bin_info['discount_ratio']) ? 1 : $coupon_bin_info['discount_ratio'];
        //产品系数
        $coupon_fields['product_ratio'] = $this->getProductRatio($deal_id);
        //工具系数
        $coupon_fields['tool_ratio'] = $this->getToolRatio($deal_id);

        // 默认全局邀请码
        $deal_service = new DealService();
        // 兼容旧智多新在网信站点的逻辑
        if (empty($short_alias) || ($this->isNullRebate($deal_id))) {
            $coupon = CouponService::getShortAliasDefault();
            Logger::info(implode(' | ', array_merge($log_info, array('deal_id '.$deal_id))));
        } else {
            $short_alias = str_replace(' ', '', $short_alias);
            $coupon = CouponService::checkCoupon($short_alias); // 常规邀请码
        }
        Logger::info(implode(' | ', array_merge($log_info, array(json_encode($coupon)))));
        if (empty($coupon)) {
            $coupon = CouponService::getShortAliasDefault();
        }

        // 悲观锁
        $lockKey = 'CouponService-consume-'.$type.'-'.$deal_load_id.'-'.$consume_user_id;
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 300)) {
            Logger::error(implode(' | ', array_merge($log_info, array('getLock fail '.$lockKey))));

            return false;
        }
        $GLOBALS['db']->startTrans();
        try {
            $coupon_log_service = new CouponLogService($this->module);
            $coupon_log = $coupon_log_service->addLog($coupon, $consume_user_id, $deal_load_id, $coupon_fields);
            if (empty($coupon_log)) {
                throw new \Exception('添加邀请码记录失败!');
            }

            // 只有p2p 添加投资来源附加返利处理
            if (CouponLogService::MODULE_TYPE_P2P == $type) {
                // 掌众的标不做附加返利
                if (empty($deal_id) || !$this->isRebateWithGlobalConfig($deal_id)) {
                    $rs = $this->handleCouponExtraForSourceType($coupon_log, $deal_load);
                    if (empty($rs)) {
                        throw new \Exception('添加投资来源附加返利记录失败!');
                    }
                }
            }

            $rs = $GLOBALS['db']->commit();
            $lock->releaseLock($lockKey); // 解锁
            Logger::info(implode(' | ', array_merge($log_info, array("commit:{$rs}"))));
            if (empty($rs)) {
                return false;
            } else {
                //用户统计埋点
                if (CouponLogService::MODULE_TYPE_P2P == $type) {
                    $userProfileService = new UserProfileService();
                    $isTzd = 1 == $coupon_log['deal_type']; // @todo 坑
                    $userProfileMoney = $coupon_log['referer_rebate_amount'] + $coupon_log['referer_rebate_ratio_amount'];
                    $userProfileService->addCouponLogProfile($coupon_log['consume_user_id'], $userProfileMoney, $isTzd);
                }

                return $coupon_log;
            }
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey); // 解锁
            Logger::info(implode(' | ', array_merge($log_info, array('exception:'.$e->getMessage()))));
            \libs\utils\Alarm::push('couponConsume', 'consume投资消费优惠码失败', json_encode($log_info).';exception:'.$e->getMessage());

            return false;
        }
    }

    /**
     * 获取默认全局邀请码
     *
     * @return array
     */
    public static function getShortAliasDefault()
    {
        return array('short_alias' => CouponService::SHORT_ALIAS_DEFAULT, 'rebate_amount' => 0, 'rebate_ratio' => 0, 'referer_rebate_amount' => 0,
                     'referer_rebate_ratio' => 0, 'product_ratio'=>0,'tool_ratio' =>0,'discount_ratio'=>0,'referer_rebate_ratio_factor'=>0);
    }

    /**
     * 处理邀请码附加返利，把附加返利叠加到邀请码返利数值上.
     *
     * @param int    $coupon_log 邀请码记录信息
     * @param object $deal_load  投标信息
     *
     * @return 返利叠加后的邀请码信息
     */
    private function handleCouponExtraForSourceType($coupon_log, $deal_load)
    {
        if (empty($coupon_log) || !isset($deal_load['source_type'])) {
            return false;
        }
        $log_info = array(__CLASS__, __FUNCTION__, json_encode($coupon_log->getRow()), json_encode($deal_load));
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));

        $source_type = $deal_load['source_type'];

        //投资来源附加返利
        $coupon_extra_model = new CouponExtraModel();
        $coupon_extra_source_type = $coupon_extra_model->getBySourceType($source_type, $coupon_log['deal_id']);
        $coupon_extra_list = empty($coupon_extra_source_type) ? array() : $coupon_extra_source_type;
        Logger::info(implode(' | ', array_merge($log_info, array(json_encode($coupon_extra_source_type)))));

        //tag附加返利
        $coupon_extra_type_list = array(CouponService::TYPE_USER_TAG);
        //$coupon_extra_type_list = array(CouponService::TYPE_USER_TAG,CouponService::TYPE_DEAL_TAG);
        foreach ($coupon_extra_type_list as $type_tag) {
            $coupon_extra_tag_list = $this->handleCouponExtraForTag($type_tag, $coupon_log);
            if (!empty($coupon_extra_tag_list)) {
                $coupon_extra_list = array_merge($coupon_extra_list, $coupon_extra_tag_list);
            }
        }

        if (empty($coupon_extra_list)) {
            return true;
        }
        $coupon_log_service = new CouponLogService();
        foreach ($coupon_extra_list as $coupon_extra_item) {
            $ret = $coupon_log_service->addCouponExtraLog($coupon_extra_item, $coupon_log['id']);
            if (false === $ret) {
                return false;
            }
        }

        return true;
    }

    /**
     * 处理邀请码附加返利类型为tag，把附加返利叠加到邀请码返利数值上.
     *
     * @param int   $type_tag
     * @param array $coupon_log
     *
     * @return array
     */
    private function handleCouponExtraForTag($type_tag, $coupon_log)
    {
        $log_info = array(__CLASS__, __FUNCTION__, json_encode($coupon_log->getRow()));
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $common_tags = array();
        if (CouponService::TYPE_USER_TAG == $type_tag) {
            $user_tag_service = new UserTagService();
            // 走主库
            $user_tag = $user_tag_service->getTags($coupon_log['consume_user_id'], false);
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($user_tag)))));
            if (!empty($user_tag)) {
                $user_tag = array_values($user_tag);
                foreach ($user_tag as $key => $user_tag_v) {
                    $common_tags[$key]['id'] = $user_tag_v['tag_id'];
                }
            }
            //}else if($type_tag == CouponService::TYPE_DEAL_TAG){
            //    $common_tags = $deal_load_ext['tags'];
        }
        if (empty($common_tags)) {
            return array();
        }

        //附加返利信息
        $coupon_extra_model = new CouponExtraModel();
        $coupon_extra_list = $coupon_extra_model->getBySourceType($type_tag, $coupon_log['deal_id']);

        if (empty($coupon_extra_list)) { // 没有对应投资来源的附加返利
            return array();
        }

        foreach ($coupon_extra_list as $k => $coupon_extra) {
            if (false == $this->checkCouponExtraTag($common_tags, $coupon_extra['tags'])) {
                unset($coupon_extra_list[$k]);
            }
        }
        if (empty($coupon_extra_list)) {
            return array();
        }
        Logger::info(implode(' | ', array_merge($log_info, array(json_encode($coupon_extra_list)))));

        return $coupon_extra_list;
    }

    /**
     * 附加返利类型匹配用户tag和标tag.
     *
     * @param array $tag_ids
     * @param array $coupon_extra_tag 多维数组
     *
     * @return bool
     */
    public function checkCouponExtraTag($tag_ids, $coupon_extra_tag)
    {
        $log_info = array(__CLASS__, __FUNCTION__, json_encode($tag_ids), json_encode($coupon_extra_tag));
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        if (empty($tag_ids) || !is_array($tag_ids) || empty($coupon_extra_tag)) {
            return false;
        }
        $coupon_extra_tag_ids = array();
        if (is_string($coupon_extra_tag)) {
            $coupon_extra_rule_tag_ids = explode(',', $coupon_extra_tag);
        } else {
            return false;
        }

        // 重新组合tag_ids
        $user_deal_tag_ids = array();
        foreach ($tag_ids as $v) {
            $user_deal_tag_ids[] = $v['id'];
        }
        /* 两个单元仅在 (string) $elem1 === (string) $elem2 时被认为是相同的。
         * 也就是说，当字符串的表达是一样的时候。 只处理一维
         */
        $intersect = array_intersect($coupon_extra_rule_tag_ids, $user_deal_tag_ids);
        $ret = array_diff($coupon_extra_rule_tag_ids, $intersect);
        Logger::info(implode(' | ', array(json_encode($ret), json_encode($user_deal_tag_ids), json_encode($coupon_extra_rule_tag_ids))));
        if (empty($ret)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 1.注册完成，邀请记录处理，不走consume逻辑.
     *
     * @param $user_id
     */
    public function regCoupon($user_id, $short_alias, $add_type = CouponLogService::ADD_TYPE_USER)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $user_id, $short_alias, $add_type);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        if (empty($user_id)) {
            return false;
        }

        $consume_user = UserModel::instance()->findViaSlave($user_id, 'user_name,create_time');
        if (empty($consume_user)) {
            Logger::info(implode(' | ', array_merge($log_info, array('error user info'))));

            return false;
        }

        if (empty($short_alias)) {
            $coupon = CouponService::getShortAliasDefault(); // 默认全局邀请码
        } else {
            $short_alias = str_replace(' ', '', $short_alias);
            $coupon = CouponService::checkCoupon($short_alias); // 常规邀请码
        }
        if (empty($coupon)) {
            Logger::info(implode(' | ', array_merge($log_info, array('error coupon info'))));

            return false;
        }

        // 悲观锁
        $lockKey = 'CouponService-regCoupon-'.$user_id;
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 300)) {
            Logger::error(implode(' | ', array_merge($log_info, array('getLock fail '.$lockKey))));

            return false;
        }
        $GLOBALS['db']->startTrans();
        try {
            $coupon_log = new CouponLogRegModel();
            // 同一个注册，只有一条返利记录
            $existLog = $coupon_log->findAllByConsumeUserId($user_id);
            if (!empty($existLog)) {
                return false;
            }

            $coupon_log->type = CouponService::TYPE_SIGNUP;
            $coupon_log->refer_user_name = $coupon['refer_user_name'];
            $coupon_log->consume_user_id = $user_id;
            $coupon_log->consume_user_name = $consume_user['user_name'];
            $coupon_log->refer_user_id = $coupon['refer_user_id'];
            $coupon_log->refer_user_name = $coupon['refer_user_name'];
            $coupon_log->agency_user_id = $coupon['agency_user_id'];
            $coupon_log->short_alias = $coupon['short_alias'];
            $coupon_log->rebate_ratio = $coupon['rebate_ratio'];
            $coupon_log->referer_rebate_ratio = $coupon['referer_rebate_ratio'];
            $coupon_log->agency_rebate_ratio = $coupon['agency_rebate_ratio'];
            $coupon_log->pay_status = CouponService::PAY_STATUS_AUTO_PAID;
            $coupon_log->pay_time = get_gmtime();
            $coupon_log->create_time = $consume_user['create_time'];
            //$coupon_log->create_time = get_gmtime();

            //后台添加注册邀请记录
            if (CouponLogService::ADD_TYPE_ADMIN == $add_type) {
                $admin_id = 0;
                if (defined('ADMIN_ROOT')) {
                    $adm_session = \es_session::get(md5(conf('AUTH_KEY')));
                    $admin_id = !empty($adm_session) ? $adm_session['adm_id'] : 0;
                }
                $coupon_log['admin_id'] = $admin_id;
                $coupon_log['add_type'] = $add_type;
            }

            $rs = $coupon_log->save();
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($coupon_log->getRow()), $rs))));
            $rs = $GLOBALS['db']->commit();
            $lock->releaseLock($lockKey); // 解锁
            Logger::info(implode(' | ', array_merge($log_info, array("commit:{$rs}"))));
            if (empty($rs)) {
                throw new \Exception('添加邀请码记录失败!');
            } else {
                return $coupon_log;
            }
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey); // 解锁
            Logger::info(implode(' | ', array_merge($log_info, array('exception:'.$e->getMessage()))));

            return false;
        }
    }

    /**
     * 2.用户完成实名认证更新优惠劵记录状态
     *
     * @param $user_id 用户id
     *
     * @return bool
     */
    public function updateStatusForIDPassed($user_id)
    {
        $log_info = array(__CLASS__, __FUNCTION__, APP, $user_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));

        return true;
        /* 邀请码服务化调整，邀请注册状态不依赖此处 20160125
        if (empty($user_id) || !is_numeric($user_id)) {
            return false;
        }
        $coupon_log = $this->getLogByDealLoadId(0, $user_id);
        if (empty($coupon_log)) {
            return false;
        }
        $this->updateLogStatus($coupon_log['id'], CouponService::PAY_STATUS_IDPASSED);
        */
    }

    /**
     * 3.用户完成绑定银行卡更新优惠劵记录状态并支出返利.
     *
     * @param $user_id
     *
     * @return bool
     */
    public function regRebatePay($user_id)
    {
        $log_info = array(__CLASS__, __FUNCTION__, APP, $user_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        /* 邀请码服务化调整，邀请注册状态不依赖此处 20160125
        //参数检查
        if (empty($user_id) || !is_numeric($user_id)) {
            return false;
        }
        $admin_id = 0;
        if (defined("ADMIN_ROOT")) {
            $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
            $admin_id = !empty($adm_session) ? $adm_session['adm_id'] : 0;
        }
        $log_info[] = $admin_id;

        $coupon_log = $this->getLogByDealLoadId(0, $user_id);
        //没有使用邀请码注册的情况
        if (empty($coupon_log)) {
            Logger::info(implode(" | ", array_merge($log_info, array('error id'))));
            return true;
        }
        $id = $coupon_log['id'];
        $log_info[] = $id;

        $user_dao = new UserModel();
        $user_platform = $user_dao->find(app_conf('COUPON_PAYER_ID'), 'id');
        if (empty($user_platform)) {
            Logger::info(implode(" | ", array_merge($log_info, array('error user_platform'))));
            return false;
        }

        $syncRemoteData = array(); //资金托管同步参数

        // 悲观锁，以id为锁的键名
        $lockKey = "CouponService-pay-" . $id;
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 300)) {
            throw new \Exception('加锁失败!');
        }
        $GLOBALS['db']->startTrans();
        try {
            $item = $this->coupon_log_dao->find($id);
            $log_info[] = json_encode($item->getRow());
            // 只结算还款中未结算记录
            if (empty($item) || $item->pay_time) {
                throw new \Exception('邀请码记录信息不正确!');
            }
            // 获取会员记录
            $consume_user = $user_dao->find($item->consume_user_id, 'id,mobile,real_name,user_name');
            $refer_user = $user_dao->find($item->refer_user_id, 'id,real_name,mobile');
            if (empty($consume_user)) {
                throw new \Exception('投资用户信息不存在!');
            }

            // 更新邀请码记录状态
            $rs = $this->updateLogStatus($id, CouponService::PAY_STATUS_AUTO_PAID);
            if ($rs['code'] != 0) {
                throw new \Exception("更新邀请码记录状态失败!");
            }
            //更新注册用户返利状态
            $update_reg_user_status_result = $consume_user->update(array('is_rebate' => 1));
            if ($update_reg_user_status_result === false) {
                throw new \Exception('更新注册用户已返状态失败');
            }

            $coupon_log_service = new CouponLogService();
            // 注册人返利
            $note_user = '使用邀请码' . $item['short_alias'] . '注册返利';
            $note_platform = '受邀注册返利';
            $coupon_log_service->payOut(CouponService::TYPE_SIGNUP, 'rebate_amount', $item, $consume_user, $user_platform, $note_user, $note_platform, $admin_id, $syncRemoteData);

            // 推荐人返利
            if (!empty($refer_user)) {
                $note_user = $refer_user['real_name'] . '使用邀请码' . $item['short_alias'] . '进行注册返利';
                $note_platform = '邀请注册返利';
                $coupon_log_service->payOut(CouponService::TYPE_SIGNUP, 'referer_rebate_amount', $item, $refer_user, $user_platform, $note_user, $note_platform, $admin_id, $syncRemoteData);
            }

            // 资金托管平台同步
            if (!empty($syncRemoteData)) {
                $finance_queue_result = FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
                if (empty($finance_queue_result)) {
                    throw new \Exception("同步资金平台入队列失败");
                }
            }

            //站内信
            \FP::import("libs.common.app");
            $msg = '您已经成为网信理财投资人，可获得' . $item['rebate_amount'] . '元现金返利，<a href="/account/money">立即查看</a>';
            send_user_msg("获得返利", $msg, 0, $item['consume_user_id'], get_gmtime(), 0, true, 1);
            $referer_rebate_amount = $item['referer_rebate_amount'];
            if ($refer_user && $referer_rebate_amount > 0) {
                $msg_invite = '您邀请的（' . $consume_user->user_name . '），已经成为网信理财投资人。您获得' . $referer_rebate_amount . '元现金返利，<a href="/account/money">立即查看</a>';
                send_user_msg("邀请好友获得返利", $msg_invite, 0, $item['refer_user_id'], get_gmtime(), 0, true, 1);
            }

            $rs = $GLOBALS['db']->commit();
            $lock->releaseLock($lockKey); // 解锁
            Logger::info(implode(" | ", array_merge($log_info, array("commit:{$rs}"))));
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey); // 解锁
            Logger::info(implode(" | ", array_merge($log_info, array("exception:" . $e->getMessage()))));
            return false;
        }
        return $rs;
        */
    }

    /**
     * 更新邀请码流程状态 （单条）.
     *
     * @param $id 邀请码记录id
     * @param $status 要更改的状态
     *
     * @return array(code => ,message) code = 0 为正确
     */
    public function updateLogStatus($id, $status)
    {
        $admin_id = 0;
        if (defined('ADMIN_ROOT')) {
            $adm_session = \es_session::get(md5(conf('AUTH_KEY')));
            $admin_id = !empty($adm_session) ? $adm_session['adm_id'] : 0;
        }
        $log_info = array(__CLASS__, __FUNCTION__, APP, $admin_id, $id, $status);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));

        if (empty($id) || !in_array($status, array(-1, -2, 0, 1, 2, 3, 4, 5, 6))) {
            $ret = array('code' => -1, 'message' => '参数错误');
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($ret)))));

            return $ret;
        }
        $coupon_log_info = $this->coupon_log_dao->find($id, 'deal_status,pay_status,type');
        if (empty($coupon_log_info)) {
            $ret = array('code' => -2, 'message' => '该记录不存在');
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($ret)))));

            return $ret;
        }
        // 检查标的放款状态 -1未绑定身份证，-2未绑定银行卡 不再使用couponlog的dealstatus，因有可能更新同步失败，直接使用deal的dealstatus
        /*if (!in_array($status, array(-1, -2, 1))) {
            if ($coupon_log_info['deal_status'] != 1) {
                $ret = array('code' => '-10', 'message' => '该记录的订单状态不是还款中');
                Logger::info(implode(" | ", array_merge($log_info, array(json_encode($ret)))));
                return $ret;
            }
        }*/
        if (in_array($coupon_log_info['pay_status'], array(2, 6))) {
            $ret = array('code' => '-5', 'message' => '已结算');
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($ret)))));

            return $ret;
        }

        $ret = array('code' => 0, 'message' => '');
        if ($coupon_log_info['pay_status'] == $status) {
            // 已经更新过同样状态，不再更新
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($ret)))));

            return $ret;
        }

        switch ($status) {
            case 0: // 未结算
                $ret = array('code' => -3, 'message' => '状态为初始状态，操作错误');
                break;
            case 1: // 自动结算
                if (!in_array($coupon_log_info['pay_status'], array(-1, -2))) {
                    $ret = array('code' => -4, 'message' => '结算状态不对，或已结算或结算状态错误');
                }
                break;
            case 2: // 手工结算
                // 初始状态可以结算
                if (!in_array($coupon_log_info['pay_status'], array(0, 3, 4, 5))) {
                    $ret = array('code' => -6, 'message' => '结算状态不是待审核，请先财务通过');
                }
                break;
            case 3: // 待审核
                if (0 != $coupon_log_info['pay_status'] && 4 != $coupon_log_info['pay_status']) {
                    $ret = array('code' => -7, 'message' => '结算状态不对，不是初始状态');
                }
                break;
            case 4: // 财务拒绝
                if (3 != $coupon_log_info['pay_status']) {
                    $ret = array('code' => -8, 'message' => '结算状态不是待审核状态,请先结算');
                }
                break;
            case 5: // 通知贷周期返利中
                if (0 != $coupon_log_info['pay_status']) {
                    $ret = array('code' => -9, 'message' => '结算状态不对，之前应是未处理状态');
                }
                break;
            case 6: // 开放平台联盟线下结算
                if (0 != $coupon_log_info['pay_status']) {
                    $ret = array('code' => -9, 'message' => '结算状态不对，之前应是未处理状态');
                }
                break;
            case -1:
                // 类型必须是注册返利
                if (1 != $coupon_log_info['type']) {
                    $ret = array('code' => -11, 'message' => '返利类型错误');
                }
                break;
            default:
                break;
        }
        if (0 != $ret['code']) {
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($ret)))));

            return $ret;
        }

        // 更新状态
        $result = $this->coupon_log_dao->updateLogStatus($id, $status, $admin_id);
        if (empty($result)) {
            $ret = array('code' => -9, 'message' => '结算状态更新失败');
        }
        Logger::info(implode(' | ', array_merge($log_info, array(json_encode($ret)))));

        return $ret;
    }

    /**
     * 更新邀请码消费记录状态
     *
     * @param $deal_id 借款项目ID
     * @param $deal_status 订单状态，0:投标成功; 1:订单还清; 2:流标
     *
     * @return bool
     */
    public function updateLogStatusByDealId($deal_id, $deal_status)
    {
        $log_info = array(__CLASS__, __FUNCTION__, APP, $deal_id, $deal_status);
        if (empty($deal_id)) {
            return false;
        }
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $rs = $this->coupon_log_dao->updateLogStatusByDealId($deal_id, $deal_status);
        if (!$rs) {
            Logger::info(implode(' | ', array_merge($log_info, array('fail'))));

            return false;
        }

        //todo 用不了
        //订单变成还款中时，自动结算
        if (1 == $deal_status && 1 == app_conf('TURN_ON_COUPON_AUTO_PAY')) {
            Logger::info(implode(' | ', array_merge($log_info, array('auto pay'))));
            $pay_list = $this->coupon_log_dao->findByDealId($deal_id, CouponService::PAY_STATUS_NOT_PAY);
            if (!empty($pay_list)) {
                foreach ($pay_list as $item) {
                    // 更新为待审核状态
                    $this->updateLogStatus($item['id'], CouponService::PAY_STATUS_FINANCE_AUDIT);
                }
            }
        }
        Logger::info(implode(' | ', array_merge($log_info, array('success'))));

        return true;
    }

    /**
     * 邀请码转用户ID.
     *
     * @param $short_alias
     * @param $user_id 用户id
     *
     * @return number
     */
    public static function hexToUserId($short_alias)
    {
        $userId = 0;
        $hex = substr($short_alias, -CouponService::SHORT_ALIAS_USER_ID_LENGTH);
        $prefix = substr($short_alias,0,-CouponService::SHORT_ALIAS_USER_ID_LENGTH);
        //不允许输入i,I和o,O,且邀请码长度必须为6;
        if (false != stripos($hex, 'i') || false != stripos($hex, 'o') || strlen($short_alias) < 6) {
            return $userId;
        }
        // 统一转换大写
        $hex = strtoupper($hex);
        $base_prefix = $hex[0];

        if (ord($base_prefix) >= ord('G')) {
            $search = array('Y', 'Z');
            $replace = array('I', 'O');
            $hex = str_replace($search, $replace, $hex);
            $userId = base_convert($hex, 32, 10) - CouponService::COUPON_HEX32_BASE_NUMBER;
        } else {
            $userId = hexdec($hex);
        }

        //重新生成邀请码,如果和传进来不一致，则为无效邀请码
        //防止前端传过来一个不符合规则的码，然后被转成一个正确的码
        $coupon = self::userIdToHex($userId,$prefix);
        if(strtoupper($short_alias) != $coupon){
            $userId = 0;
        }

        return $userId;
    }

    /**
     * 获取邀请码的前缀
     *
     * @param $short_alias
     *
     * @return string
     */
    public static function getShortAliasPrefix($short_alias)
    {
        return substr($short_alias, 0, -CouponService::SHORT_ALIAS_USER_ID_LENGTH);
    }

    /**
     * 根据用户ID生成邀请码短码
     *
     * 取十六进制编码
     */
    public static function userIdToHex($user_id,$prefix = '')
    {
        if (empty($user_id)) {
            return '';
        }
        // 读配置判断是否生成32进制数（默认为100万）
        $start_id = app_conf('COUPON_HEX32_START_USER_ID');
        $start_id = empty($start_id) ? 1000000 : $start_id;

        if ($user_id >= $start_id) {
            // 转32进制
            // 从g开始以此类推h、i、j、.....
            $cardinal_number = CouponService::COUPON_HEX32_BASE_NUMBER;
            $convert_value = base_convert($user_id + $cardinal_number, 10, 32);
            $str_hex = strtoupper($convert_value);
            $search = array('I', 'O');
            $replace = array('Y', 'Z');
            // 替换掉模糊的i和o
            $result = str_replace($search, $replace, $str_hex);
        } else {
            $str_hex = strtoupper(dechex($user_id));
            $result = str_pad($str_hex, CouponService::SHORT_ALIAS_USER_ID_LENGTH, '0', STR_PAD_LEFT);
        }

        if(empty($prefix)){
            $prefix = self::getPrefixByUserId($user_id);
        }
        $prefix = strtoupper($prefix);

        return $prefix.$result;
    }

    /**
     * 获取会员返利系数.
     *
     * 取会员个人的返利系数，如果不存在，默认值取1
     *
     * @param $user_id
     *
     * @return float
     */
    public function getUserPayFactor($user_id)
    {
        $factor = CouponService::$factor_default;
        if (empty($user_id)) {
            return $factor;
        }
        $user_model = new UserModel();
        $user_info = $user_model->find($user_id);
        if (!empty($user_info) && 0 != $user_info['is_effect'] && 1 != $user_info['is_delete']) {
            if (!empty($user_info['channel_pay_factor']) && $user_info['channel_pay_factor'] > 0) {
                $factor = $user_info['channel_pay_factor'];
            }
        }

        return $factor;
    }

    /**
     * 获取所有需要绑定的邀请码，包括特殊邀请码和普通邀请码
     *
     * @deprecate 效率低，没有跟标走
     */
    public function getCouponsFixed()
    {
        $couponSpecialModel = new CouponSpecialModel();
        $coupon_special_fixed_list = $couponSpecialModel->getCouponsFixed(); //特殊绑定邀请码列表
        $coupon_fixed_list = CouponLevelRebateModel::instance()->getCouponsFixed(); //普通绑定邀请码列表
        $coupon_special_fixed_list = empty($coupon_special_fixed_list) ? array() : $coupon_special_fixed_list;
        $coupon_fixed_list = empty($coupon_fixed_list) ? array() : $coupon_fixed_list;

        return array_merge($coupon_special_fixed_list, $coupon_fixed_list);
    }

    /**
     * 获取最近使用邀请码码
     *
     * 优先级
     * 1.有绑定要求的邀请码，自动返填绑定，不可取消
     * 2.通过链接过来投标的，返填链接中的邀请码，可取消
     * 3.取最近一次用户投标使用的邀请码，可取消
     *
     * @param $consume_user_id 投标会员ID
     *
     * @return array key值说明：short_alias:邀请码， is_fixed:是否绑定
     */
    public function getCouponLatest($consume_user_id)
    {
        $log_info = array(__CLASS__, __FUNCTION__, APP, $consume_user_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        if (empty($consume_user_id)) {
            Logger::info(implode(' | ', array_merge($log_info, array('empty consume_user_id'))));

            return false;
        }

        $is_fixed = true;
        $short_alias = '';
        $coupon_bind_service = new CouponBindService();
        $coupon_bind = $coupon_bind_service->getByUserId($consume_user_id);
        Logger::info(implode(' | ', array_merge($log_info, array('coupon_bind', json_encode($coupon_bind)))));
        if (!empty($coupon_bind)) {
            $short_alias = $coupon_bind['short_alias'];
            $is_fixed = !empty($coupon_bind['is_fixed']);
        }

        // 邀请链接带来的邀请码
        if (empty($short_alias) && !$is_fixed) {
            $short_alias = trim(\es_cookie::get(CouponService::LINK_COUPON_KEY)); //邀请链接
            Logger::info(implode(' | ', array_merge($log_info, array('LINK_COUPON input', $short_alias))));
        }

        $coupon_result = array();
        if (!empty($short_alias)) {
            $coupon_result = $this->checkCoupon($short_alias);
        }

        $short_alias = !empty($coupon_result) ? $coupon_result['short_alias'] : '';
        $result = array('short_alias' => $short_alias, 'is_fixed' => $is_fixed, 'coupon' => $coupon_result);
        Logger::info(implode(' | ', array_merge($log_info, array('result', json_encode($result)))));

        return $result;
    }

    /**
     * 未使用邀请码的用户是否绑定不允许使用邀请码
     * 新用户及老用户（未绑码的）在满足“2次投资或注册N天”的条件后仍未填码的，不允许用邀请码
     *
     * @param $consume_user_id 投资用户id
     *
     * @return bool
     */
    public function isFixedWithoutCoupon($consume_user_id)
    {
        $lock_days = app_conf('COUPON_LOCK_DAYS'); // N天后绑定
        $lock_date_start = app_conf('COUPON_LOCK_DATE_START'); //上线时间，老用户从这天开始计算，N天后绑定
        $lock_user_id_start = app_conf('COUPON_LOCK_USER_ID_START'); //此ID前为返利新政的老用户，取值会在2015-07-02停机上线前确定
        $lock_days = empty($lock_days) ? 30 : $lock_days;
        $lock_date_start = empty($lock_date_start) ? '2015-07-02' : $lock_date_start;
        $lock_time_start = strtotime($lock_date_start);
        $now = time();
        $log_info = array(__CLASS__, __FUNCTION__, APP, $consume_user_id, $lock_days, $lock_date_start, $lock_time_start, $now);

        // 老用户计算锁定时间，从上线时间开始算
        $time_count_start = 0;
        if (!empty($lock_user_id_start) && $consume_user_id <= $lock_user_id_start) {
            $time_count_start = $lock_time_start;
        }

        if (empty($time_count_start)) {
            $user = UserModel::instance()->find($consume_user_id, 'create_time');
            $time_count_start = $user['create_time'] + 28800;
        }
        $log_info[] = $time_count_start;

        // 超过N天，绑定
        if ($now > $time_count_start + ($lock_days * 86400)) {
            Logger::info(implode(' | ', array_merge($log_info, array("超过{$lock_days}做绑定"))));

            return true;
        }

        return false;
        // 投资超过两笔，绑定 -暂不使用
        //$condition = "`user_id`=':user_id'";
        //$deal_load_count = DealLoadModel::instance()->count($condition, array(':user_id' => $consume_user_id));
        //Logger::info(implode(" | ", array_merge($log_info, array("投资{$deal_load_count}笔"))));
        //return $deal_load_count > 2;
    }

    /**
     * 取某个借款金额列的金额汇总.
     *
     * @param $deal_id intval 借款id
     *
     * @return float
     */
    public function getDealOnlineFee($deal_id, $field)
    {
        return $this->coupon_log_dao->getDealSumAmount($deal_id, $field);
    }

    /**
     * 取某个借款邀请码金额相关汇总.
     *
     * @param $deal_id intval 借款id
     *
     * @return float
     */
    public function getDealCouponAmountData($deal_id)
    {
        return $this->coupon_log_dao->getDealCouponSumAmount($deal_id);
    }

    /**
     * 是否显示和使用邀请码
     *
     * 满足条件
     * 1.邀请码开关打开
     * 2.p2p主站
     * 3.不是新手标
     *
     * @param bool $deal_id 订单id，可空
     *
     * @return bool
     */
    public static function isShowCoupon($deal_id = false)
    {
        $rs = isCouponValidForBranchSite() && CouponService::isValidForBeginner($deal_id);
        if (empty($rs)) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $deal_id, 'isShowCoupon:false')));
        }

        return $rs;
    }

    /**
     * 判断订单若为新手标，是否可用邀请码
     *
     * @param $deal_id 订单id
     *
     * @return bool
     */
    public static function isValidForBeginner($deal_id)
    {
        //邀请码总开关关闭
        if ('1' != app_conf('TURN_ON_COUPON')) {
            return false;
        }
        if (empty($deal_id)) {
            return true;
        }
        // 新手标可用开关打开
        if ('1' == app_conf('TURN_ON_COUPON_BEGINNER')) {
            return true;
        }
        $isForBeginner = DealModel::instance()->isForBeginner($deal_id);

        return !$isForBeginner;
    }

    /**
     * 是否存在标所属的返利规则.
     *
     * @param $deal_id
     *
     * @return int
     */
    public function existsDealCoupon($deal_id)
    {
        $rebate_model = new CouponLevelRebateModel();
        if ($rebate_model->existsDealCoupon($deal_id)) {
            return true;
        }
        $coupon_special_model = new CouponSpecialModel();

        return $coupon_special_model->existsDealCoupon($deal_id);
    }

    /**
     * 复制标的返利规则.
     *
     * @param $desc_deal_id 新标ID
     * @param int $src_deal_id 源规则标ID
     *
     * @return bool
     */
    public function copyRebate($desc_deal_id, $src_deal_id = 0)
    {
        //掌众标不复制优惠码规则
        if ($this->isRebateWithGlobalConfig($desc_deal_id)) {
            return true;
        }
        $GLOBALS['db']->startTrans();
        try {
            $rebate_model = new CouponLevelRebateModel();
            $rs = $rebate_model->copyRebate($desc_deal_id, $src_deal_id);
            if (!$rs) {
                throw new \Exception('复制CouponLevelRebate记录失败!');
            }
            $coupon_special_model = new CouponSpecialModel();
            $rs = $coupon_special_model->copyRebate($desc_deal_id, $src_deal_id);
            if (!$rs) {
                throw new \Exception('复制CouponSpecial记录失败!');
            }
            $coupon_extra_model = new CouponExtraModel();
            $rs = $coupon_extra_model->copyRebate($desc_deal_id, $src_deal_id);
            if (!$rs) {
                throw new \Exception('复制CouponExtra记录失败!');
            }
            $rs = $GLOBALS['db']->commit();
            if (!$rs) {
                throw new \Exception('复制事务提交失败!');
            }
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();

            return false;
        }

        return true;
    }

    /**
     * 根据consume_user_idid 查询注册返利.
     *
     * @param int $deal_load_id
     * @param int $consume_user_id
     */
    public function getLogByDealLoadId($deal_load_id, $consume_user_id = false)
    {
        return $this->coupon_log_dao->findByDealLoadId($deal_load_id, $consume_user_id);
    }

    /**
     * 使用过用户的邀请码
     *
     * @param $refer_user_id
     * @param string $fields
     */
    public function getShortAliasUsed($refer_user_id, $fields = 'short_alias')
    {
        $list = $this->coupon_log_dao->getShortAliasUsed($refer_user_id, $fields);

        return $list;
    }

    /**
     * 根据传入的字段返回用于返利的邀请码字.
     *
     * @param $str 邀请码，可能是字符串，也可能是电话
     *
     * @return 返回字符串的邀请码,形式如FA0FA
     */
    public function getShortAliasFormMobilOrAlias($str)
    {
        if (empty($str)) {
            return false;
        }
        $str = addslashes($str);
        $rule = array('options' => array('regexp' => '/^0?(13[0-9]|15[0-9]|18[0-9]|14[57]|17[0-9])[0-9]{8}/'));
        $mobileValide = filter_var($str, FILTER_VALIDATE_REGEXP, $rule);
        if (false === $mobileValide) {
            return array('type' => 'alias', 'alias' => $str, 'userName' => 'null');
        } else {
            return false;
            /*
            // 用手机号转成邀请码
            $userModel = new UserModel();
            $userInfo = $userModel->getUserByMobile($str,'id,real_name,sex');
            $userId = $userInfo['id'];
            //0:女 1:男
            $sex = $userInfo['sex']?'先生':'女士';
            $userName = mb_substr($userInfo['real_name'],0,1,'utf-8');
            $oneUserCoupon = $this->getOneUserCoupon($userId);
            $alias = $oneUserCoupon['short_alias'];
            return array('type'=>'mobile','alias'=>$alias,'userName'=>$userName.$sex);
            */
        }
    }

    /**
     * 根据传入的字段返回用于返利的邀请码字,with Cache
     * 执行getShortAliasFormMobilOrAliasDB.
     */
    public function getShortAliasFormMobilOrAliasCache($str)
    {
        $str = addslashes($str);
        $key = sprintf('short_alias_from_%s', $str);

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!empty($redis)) {
            $dataFromCache = $redis->get($key);
            if (empty($dataFromCache)) {
                $dataFromDB = $this->getShortAliasFormMobilOrAliasDB($str);
                if (!empty($dataFromDB)) {
                    $redis->set($key, json_encode($dataFromDB), 'ex', 3600 * 24);
                }

                return $dataFromDB;
            } else {
                return json_decode($dataFromCache, true);
            }
        } else {
            return $this->getShortAliasFormMobilOrAliasDB($str);
        }
    }

    public function checkShortAliasFormMobilOrAlias($str)
    {
        $shortAliasInfo = $this->getShortAliasFormMobilOrAlias($str);
        $shortAlias = $shortAliasInfo['alias'];
        $ret = $this->checkCoupon($shortAlias);

        return $ret;
    }

    /**
     * 获取一个码对应的用户.
     */
    public function getReferUserId($cn)
    {
        $couponInfo = $this->checkCoupon($cn);
        if (!empty($couponInfo)) {
            return $couponInfo['refer_user_id'];
        }

        return self::hexToUserId($cn);
    }

    public static function sendWarnMsg($title, $content)
    {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, $title, 'start')));
        \FP::import('libs.common.dict');
        $warn_list = \dict::get('COUPON_WARN');
        $msgcenter = new \msgcenter();
        foreach ($warn_list as $dest) {
            if (is_numeric($dest)) { // 短信, 没有count值不发
                $rs = SmsServer::instance()->send($dest, 'TPL_COMPATIBLE', array($title));
               } else { // 邮件
                $rs = $msgcenter->setMsg($dest, 0, $content, false, $title);
            }
        }
        $rs = $msgcenter->save();
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, $title, json_encode($rs))));
    }

    /**
     * 通过标Id获取标信息.
     *
     * @param intval $deal_id
     *
     * @return bool|array
     */
    public function getDealInfoByDealId($deal_id)
    {
        $deal_id = intval($deal_id);
        if ($deal_id > 0) {
            $result = $this->getDealInfoByDealIds(array($deal_id));
            if (isset($result[$deal_id])) {
                return $result[$deal_id];
            }
        }

        return false;
    }

    /**
     * 通过标ids获取标信息.
     *
     * @param array $deal_ids
     *
     * @return bool|array
     */
    public function getDealInfoByDealIds($deal_ids)
    {
        if (empty($deal_ids) || !is_array($deal_ids)) {
            return false;
        }
        $deal_ids = array_map('intval', $deal_ids);

        switch ($this->module) {
            case CouponLogService::MODULE_TYPE_DUOTOU:
                return $this->getDuoTouDealInfoByDealIds($deal_ids);
                break;

            case CouponLogService::MODULE_TYPE_GOLD:
                return $this->getGoldDealInfoByDealIds($deal_ids);
                break;

            case CouponLogService::MODULE_TYPE_GOLDC:
                return $this->getGoldcDealInfoByDealIds($deal_ids);
                break;

            case CouponLogService::MODULE_TYPE_P2P:
                return $this->getP2pDealInfoByDealIds($deal_ids);
                break;
            case CouponLogService::MODULE_TYPE_NCFPH:
                return $this->getNcfphDealInfoByDealIds($deal_ids);

            case CouponLogService::MODULE_TYPE_THIRD:
                return $this->getThirdDealInfoByDealIds($deal_ids);
                break;
            default:
                return false;
        }
    }

    protected function getDuoTouDealInfoByDealIds($deal_ids)
    {
        $log_info = array(__CLASS__, __FUNCTION__, implode(',', $deal_ids));
        $deals = array();

        $rpc = new Rpc('duotouRpc');
        $request = new RequestCommon();
        $request->setVars(array('projectIds' => $deal_ids));
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project', 'getProjectInfoByIds', $request);
        $log_info[] = json_encode($response);
        Logger::info(implode(' | ', $log_info));
        if ($response['errCode']) {
            \libs\utils\Alarm::push('DuotouRPC', '多投Rpc告警(\NCFGroup\Duotou\Services\Project::getProjectInfoByIds)', json_encode($log_info));

            return false;
        }

        if (empty($response['data'])) {
            return $deals;
        }

        foreach ($response['data'] as $item) {
            $deals[$item['id']]['deal_id'] = $item['id'];
            $deals[$item['id']]['name'] = $item['name'];
            $deals[$item['id']]['rateYear'] = $item['rateYear'];
            $deals[$item['id']]['rateDay'] = $item['rateDay'];
            $deals[$item['id']]['deal_status'] = $item['status'];
            $deals[$item['id']]['project_name'] = $item['name'];
        }
        unset($response);

        return $deals;
    }

    /**
     * 通过标ids获取黄金定期标的信息.
     */
    protected function getGoldDealInfoByDealIds($deal_ids)
    {
        $log_info = array(__CLASS__, __FUNCTION__, implode(',', $deal_ids));
        //获取标的信息
        $deals = array();
        $rpc = new \libs\rpc\Rpc();
        foreach ($deal_ids as $dealId) {
            $res = $rpc->local('GoldService\getDealById', array($dealId));
            if (0 != $res['errCode']) {
                continue;
            }
            $deals[$dealId] = $res['data'];
            $deals[$dealId]['deal_status'] = $res['data']['dealStatus'];
        }
        Logger::info(implode(' | ', $log_info));
        return $deals;
    }

    /**
     * 通过标ids获取普惠定期标的信息.
     */
    protected function getNcfphDealInfoByDealIds($deal_ids)
    {
        $log_info = array(__CLASS__, __FUNCTION__, implode(',', $deal_ids));
        //获取标的信息
        $deals = array();
        foreach ($deal_ids as $dealId) {
            $deals[$dealId] =NcfphDealService::getDeal($dealId,false,false);
        }
        Logger::info(implode(' | ', $log_info));
        return $deals;
    }


    protected function getP2pDealInfoByDealIds($deal_ids){
        $log_info = array(__CLASS__, __FUNCTION__, implode(',', $deal_ids));
        //获取标的信息
        $deals = array();
        $dealService = new DealService();
        foreach ($deal_ids as $dealId) {
            $deals[$dealId] = $dealService->getDeal($dealId,false,false);
        }
        Logger::info(implode(' | ', $log_info));
        return $deals;
    }


    /**
     * 通过标ids获取黄金活期标的信息.
     */
    protected function getGoldcDealInfoByDealIds($deal_ids)
    {
        $log_info = array(__CLASS__, __FUNCTION__, implode(',', $deal_ids));
        //获取标的信息
        $deals = array();
        foreach ($deal_ids as $dealId) {
            $deals[$dealId]['deal_id'] = $dealId;
            $deals[$dealId]['name'] = '黄金活期';
            $deals[$dealId]['deal_status'] = '5';
        }
        Logger::info(implode(' | ', $log_info));
        return $deals;
    }

    protected function getThirdDealInfoByDealIds($deal_ids){
        $log_info = array(__CLASS__, __FUNCTION__, implode(',', $deal_ids));
        //获取标的信息
        $deals = array();
        $dealService = new ThirdDealService();
        foreach ($deal_ids as $dealId) {
            $deals[$dealId] = $dealService->getDeal($dealId);
        }
        Logger::info(implode(' | ', $log_info));
        return $deals;
    }

    /**
     * 通过标名称获取标信息.
     *
     * @param string $name
     *
     * @return bool
     */
    public function getDealInfoByName($name)
    {
        if (empty($name)) {
            return false;
        }

        switch ($this->module) {
            case CouponLogService::MODULE_TYPE_DUOTOU:
                return $this->getDuoTouDealInfoByName($name);
                break;
            default:
                return false;
        }
    }

    protected function getDuoTouDealInfoByName($name)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $name);
        $deals = array();

        $rpc = new Rpc('duotouRpc');
        $request = new RequestCommon();
        $request->setVars(array('projectName' => $name));
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project', 'getProjectInfoByName', $request);
        $log_info[] = json_encode($response);
        Logger::info(implode(' | ', $log_info));
        if ($response['errCode']) {
            \libs\utils\Alarm::push('DuotouRPC', '多投Rpc告警(\NCFGroup\Duotou\Services\Project::getProjectInfoByName)', json_encode($log_info));

            return false;
        }

        if (empty($response['data'])) {
            return $deals;
        }

        foreach ($response['data'] as $item) {
            $deals[$item['id']]['deal_id'] = $item['id'];
            $deals[$item['id']]['name'] = $item['name'];
            $deals[$item['id']]['rateYear'] = $item['rateYear'];
            $deals[$item['id']]['rateDay'] = $item['rateDay'];
            $deals[$item['id']]['deal_status'] = $item['status'];
        }
        unset($response);

        return $deals;
    }

    /**
     * 邀请码转理财师id.
     *
     * @param string $shorAlias
     *
     * @return int
     */
    public function shortAliasToReferUserId($shorAlias)
    {
        $referUserId = false;
        if (!empty($shorAlias)) {
            $coupon_special = $this->getCouponSpecial($shorAlias);
            if (!empty($coupon_special)) {
                if (!empty($coupon_special['refer_user_id'])) {
                    $referUserId = $coupon_special['refer_user_id'];
                }
            } else {
                $referUserId = CouponService::hexToUserId($shorAlias);
            }
        }

        return $referUserId;
    }

    /**
     * 获取理财师返利政策信息.
     *
     * @param unknown $userId
     *
     * @return bool|array
     */
    public function getRebateInfo($referUserId)
    {
        $referUserId = intval($referUserId);
        $data = array('refer_user_id' => $referUserId);

        $userModel = new UserModel();
        $userInfo = $userModel->find($referUserId, 'group_id');
        $data['group_id'] = $userInfo['group_id'];
        if (empty($userInfo['group_id'])) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, json_encode($data), 'error:用户不存在')));

            return true;
        }

        $userGroupModel = new UserGroupModel();
        $userGroupInfo = $userGroupModel->find($userInfo['group_id'], 'basic_group_id');
        $data['basic_group_id'] = $userGroupInfo['basic_group_id'];
        if (empty($userGroupInfo['basic_group_id'])) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, json_encode($data), 'msg:用户组不存在或者关联政策组id为0')));

            return true;
        }

        $userBasicGroupModel = new UserBasicGroupModel();
        $userBasicGroupInfo = $userBasicGroupModel->find($userGroupInfo['basic_group_id'], 'rebate_effect_days');
        $data['rebate_effect_days'] = $userBasicGroupInfo['rebate_effect_days'];
        if (empty($userBasicGroupInfo)) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, json_encode($data), 'msg:政策组不存在')));

            return true;
        }

        return $data;
    }

    /**
     * 判断是否开放平台联盟理财师，线下结算.
     *
     * @param unknown $refer_user_group_id 理财师用户组id
     *
     * @return bool|array
     */
    public static function isPayOffline($refer_user_group_id)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $refer_user_group_id);
        $is_refer_user_non_pay_group = false;
        $non_pay_group_id_conf = app_conf('COUPON_PAY_OFFLINE_GROUP_ID');
        if (!empty($non_pay_group_id_conf)) {
            $non_pay_group_ids = explode(',', $non_pay_group_id_conf);
            if (!empty($non_pay_group_ids) && in_array($refer_user_group_id, $non_pay_group_ids)) {
                Logger::info(implode(' | ', array_merge($log_info, array($non_pay_group_id_conf, 'in non pay group'))));
                $is_refer_user_non_pay_group = true;
            }
        }
        Logger::info(implode(' | ', array_merge($log_info, array($non_pay_group_id_conf, 'is not PayOffline'))));

        return $is_refer_user_non_pay_group;
    }

    /**
     * 获取0.91税率邀请返利系数，除‘转线下结算’外其余全线乘0.91.
     */
    public function getReferer_rebate_ratio($referer_rebate_ratio, $refer_user_group_id = false)
    {
        //开放平台转线下理财师返利比例不打折
        if (!empty($refer_user_group_id) && CouponService::isPayOffline($refer_user_group_id)) {
            return $referer_rebate_ratio;
        }
        //返佣红包开关
        $turn_on_referer_rebate_by_red = app_conf('COUPON_REREFER_REBATE_BY_RED_TRUN_ON');
        if (1 == $turn_on_referer_rebate_by_red) {//开
            $referer_rebate_ratio = round($referer_rebate_ratio * CouponService::COUPON_REFERER_REBATE_RATIO, 5); //返利乘系数0.91
        }

        return $referer_rebate_ratio;
    }

    /**
     * 判断是否采用全局邀请返利配置的标的。有些标的类型逻辑不能统一加入DealService的掌众、信石、功夫贷标的判断方法，在此额外判断.
     *
     * @param intval $deal_id
     *
     * @return bool
     */
    public function isRebateWithGlobalConfig($deal_id)
    {
        // 需要全局返利规则配置的标的类型TAG
        $typeTagList[] = DealLoanTypeModel::TYPE_XJDCDT; //现金贷-车贷通 feature/ZXSHHT-23 20170926
        $typeTagList[] = DealLoanTypeModel::TYPE_XJDYYJ; //现金贷-优易借 放心花 feature/fangxinhua 20171026
        $typeTagList[] = DealLoanTypeModel::TYPE_DSD; //店商互联 feature/5336-2 20171204
        $typeTagList[] = DealLoanTypeModel::TYPE_ZZJRXS; //闪电消费(线上) 掌众50天 feature/zz-50d 20171207
        $typeTagList[] = DealLoanTypeModel::TYPE_DFD; //东风贷 feature/5537 20180326
        $typeTagList[] = DealLoanTypeModel::TYPE_HDD; //汇达贷 feature/5537 20180326
        $typeTagList[] = DealLoanTypeModel::TYPE_NDD; //农担支农贷 20180510
        $typeTagList[] = DealLoanTypeModel::TYPE_GRZFFQ; //个人租房分期 20180802
        $typeIdList = DealLoanTypeModel::instance()->getIdListByTag($typeTagList);

        $dealTypeId = DealModel::instance()->find(intval($deal_id), 'type_id', true);
        if (!empty($dealTypeId) && in_array($dealTypeId['type_id'], $typeIdList)) {
            return true;
        }

        //公用判断逻辑
        $deal_service = new DealService();
        $rs = $deal_service->isZhangzhongDeal($deal_id);

        return $rs;
    }

    /**
     * 不给返利.
     */
    public function isNullRebate($deal_id)
    {
        if($this->module == CouponLogService::MODULE_TYPE_P2P || $this->module == CouponLogService::MODULE_TYPE_NCFPH){
            $couponDealService = new CouponDealService($this->module);
            $couponDeal = $couponDealService->getCouponDealByDealId($deal_id);
            if(isset($couponDeal['is_rebate']) && $couponDeal['is_rebate'] == couponDealModel::IS_REBATE_NO){
                Logger::info(__CLASS__.' | '. __FUNCTION__.' | '.$this->module.' | '.'deal_id : '.$deal_id . ' | couponDeal true');
                return true;
            }
        }

        $deal = self::getDealInfoByDealId($deal_id);
        if( isset($deal['isNd']) && $deal['isNd'] == 1 ){
            Logger::info(__CLASS__.' | '. __FUNCTION__.' | '.$this->module.' | '.'deal_id : '.$deal_id . ' | ph true');
            return true;
        }elseif( isset($deal['isDtb']) && $deal['isDtb'] == 1 ){
            Logger::info(__CLASS__.' | '. __FUNCTION__.' | '.$this->module.' | '.'deal_id : '.$deal_id . ' | ph dt true');
            return true;
        }
        Logger::info(__CLASS__.' | '. __FUNCTION__.' | '.$this->module.' | '.'deal_id : '.$deal_id . ' | false');
        return false;
    }

    /**
     **获取产品等级分类返点系数.
     **/
    public function getDealGradeTypeRadioFactor($dealGradeTypeName)
    {
        $dealTypeGradeService = new DealTypeGradeService();
        $dealTypeGrade = $dealTypeGradeService->findByName(addslashes($dealGradeTypeName));
        if (!empty($dealTypeGrade)) {
            return floatval($dealTypeGrade['radio_factor']);
        }
        Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,"dealGradeTypeName:".$dealGradeTypeName,'产品三级名称不存在')));
        return 1;
    }

    /**
     * 获取天数对应的返点比例系数.
     */
    public function getDaysRadioFator($days)
    {
        $days = intval($days);
        FP::import('libs.common.dict');
        $daysRadioFatorConf = (array) dict::get('DATS_RADIO_FATOR');
        if (!empty($daysRadioFatorConf)) {
            //首先匹配单个指定期限的
            foreach ($daysRadioFatorConf as $conf) {
                if (preg_match("/(\d):(\d\.\d)/", $conf, $matches)) {
                    if ($days == $matches[1]) {
                        return floatval($matches[2]);
                    }
                } else {
                    continue;
                }
            }

            //然后在匹配区间
            foreach ($daysRadioFatorConf as $conf) {
                if (preg_match("/\[(\d),(\d)\)(\d\.\d)/", $conf, $matches)) {
                    if ($days >= $matches[1] && $days < $matches[2]) {
                        return floatval($matches[3]);
                    }
                } else {
                    continue;
                }
            }
        }

        return 1;
    }

    //用户邀请码是否无效,不能绑码，不能投资
    private function shortAliasIsEffect($userInfo,$levelInfo,$groupInfo){
         $log_info = array(__CLASS__, __FUNCTION__);
        if (empty($userInfo) || $userInfo['is_delete'] || empty($userInfo['is_effect'])) {
            Logger::error(implode(' | ', array_merge($log_info, array('error user info'))));
            return false;
        }

        if (empty($levelInfo) || empty($levelInfo['is_effect'])) {
            Logger::error(implode(' | ', array_merge($log_info, array('error level info'))));
            return false;
        }

        if (empty($groupInfo) || empty($groupInfo['is_effect'])) {
            Logger::error(implode(' | ', array_merge($log_info, array('error group info'))));
            return false;
        }

        $userCouponLevelService = new UserCouponLevelService();
        $agencyRebateRatio = $userCouponLevelService->getAgencyRebateRatio($levelInfo,$groupInfo);
        if(bccomp($agencyRebateRatio, 0, 5) == -1){
            Logger::error(implode(' | ', array_merge($log_info, array('机构返利不能小于0'))));
            return false;
        }
        if(!$userCouponLevelService->checkMaxPackRatio($agencyRebateRatio, $levelInfo['rebate_ratio'], $groupInfo['max_pack_ratio'])){
            Logger::error(implode(' | ', array_merge($log_info, array('机构返利加服务返利必须小于打包比例系数上限'))));
            return false;
        }
        return true;
    }

    //获取用户信息
    private function getUserById($userId){
        return UserModel::instance()->find($userId, 'id,user_name,real_name,idcardpassed,new_coupon_level_id,is_delete,is_effect,coupon_disable,group_id', true);   
    }

    public function getToolRatio($dealId){
        switch ($this->module) {
            case CouponLogService::MODULE_TYPE_DUOTOU:
                return $this->getDtToolRatio($dealId);
                break;
            default:
                return 1;
        }
    }

    public function getProductNameByDealId($dealId = 0){
          switch ($this->module) {
            case CouponLogService::MODULE_TYPE_DUOTOU:
                $productName = "智多新";
                break;

            case CouponLogService::MODULE_TYPE_GOLD:
                $productName = "优长金";
                break;

            case CouponLogService::MODULE_TYPE_GOLDC:
                $productName = "优金宝";
                break;

            case CouponLogService::MODULE_TYPE_P2P:
                $productName = (new DealService())->getProductNameByDealId($dealId);
                break;
            case CouponLogService::MODULE_TYPE_NCFPH:
                $productName = (new NcfphDealService())->getProductNameByDealId($dealId);
                break;
            default:
                $productName = '';
        }
        return $productName;
    }

    public function getProductRatio($dealId){
        $productName = $this->getProductNameByDealId($dealId);
        $productRatio = $this->getDealGradeTypeRadioFactor($productName);
        Logger::info(implode(' | ',array(__CLASS__,__FUNCTION__,"module:".$this->module,"dealId:".$dealId,"productName:".$productName,"productRatio:".$productRatio)));
        return $productRatio;
    }

    /**
     * 获取智多新工具系数
     * @return array
     */
    public function getDtToolRatio($projectId)
    {
        $request = new RequestCommon();
        $request->setVars(array('project_id' => $projectId));
        $log_info = array(__CLASS__, __FUNCTION__,$projectId);
        $rpc = new \libs\utils\Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project', 'getProjectInfoById', $request);
        if(empty($response) || !isset($response['data'])){
            throw new \Exception("duotouRpc 调用失败");
        }

        return isset($response['data']['rebateFactor']) ? $response['data']['rebateFactor'] : 1;
    }

    static public function getPrefixByUserId($userId){
        $userCouponLevelService = new UserCouponLevelService();
        $group = $userCouponLevelService->getGroupByUserId($userId);
        return !empty($group)? $group['prefix']:"F";
    }


    public function hasServiceAbility($userId){
        $userCouponLevelService = new UserCouponLevelService();
        $group = $userCouponLevelService->getGroupByUserId($userId);
        return 1 == $group['service_status'];
    }

    public function haveServiceEntranceNoCache($userId){
        $couponLogService = new CouponLogService(CouponLogService::MODULE_TYPE_P2P,CouponLogService::DATA_TYPE_SERVICE);
        return $this->hasServiceAbility($userId) || $couponLogService->getTotalCountByReferUserId($userId);
    }

    public function haveServiceEntrance($userId){
        return \SiteApp::init()->dataCache->call(new CouponService(), 'haveServiceEntranceNoCache', array($userId), 1);
    }

}
