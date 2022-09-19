<?php
/**
 * CouponLogService.php.
 *
 * @date 2014-09-28
 *
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\CompoundRedemptionApplyModel;
use core\dao\CouponExtraLogModel;
use core\dao\CouponLogModel;
use core\dao\CouponBindModel;
use core\dao\CouponLogBakModel;
use core\dao\CouponDealModel;
use core\dao\CouponExtraModel;
use core\dao\CouponPayLogModel;
use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\FinanceQueueModel;
use core\dao\UserModel;
use core\dao\EnterpriseModel;
use core\dao\UserBankcardModel;
use libs\lock\LockFactory;
use libs\utils\Logger;
use SebastianBergmann\Exporter\Exception;
use NCFGroup\Protos\Duotou\RequestCommon;
use core\service\third\PlatformService;
use core\service\third\ThirdDealLoadService;

class CouponLogService extends BaseService
{
    /**
     * 记录添加类型-前台用户触发.
     */
    const ADD_TYPE_USER = 1;

    /**
     * 记录添加类型-后台管理员变更.
     */
    const ADD_TYPE_ADMIN = 2;

    /**
     * 标的类型-普通.
     */
    const DEAL_TYPE_GENERAL = 0;
    /**
     * 标的类型-通知贷.
     */
    const DEAL_TYPE_COMPOUND = 1;
    /**
     * 标的类型-交易所
     */
    const DEAL_TYPE_EXCHANGE = 2;
    /**
     * 标的类型-交易所
     */
    const DEAL_TYPE_DAJINSUO = 2;
    /**
     * 标的类型-专享.
     */
    const DEAL_TYPE_EXCLUSIVE = 3;
    /**
     * 标的类型-小贷.
     */
    const DEAL_TYPE_PETTYLOAN = 5;
    /**
     * 标的类型-尊享.
     */
    const DEAL_TYPE_DARKMOON = 6;

    //标类型分组  修改组之后  文件 firstp2p/admin/Tpl/default/Deal/edit.html line 1304，需要再修改下
    public static $deal_type_group1 = array(self::DEAL_TYPE_GENERAL, self::DEAL_TYPE_EXCHANGE, self::DEAL_TYPE_EXCLUSIVE, self::DEAL_TYPE_PETTYLOAN); //一次性结算
    public static $deal_type_group2 = array(self::DEAL_TYPE_COMPOUND); //按周结算

    const DEAL_STATUS_REPAYING = 1;

    const DEAL_STATUS_REPAID = 5;

    const DEAL_STATUS_REDEEMING = 6;

    const DAYS_OF_MONTH = 30;

    const DAYS_OF_YEAR = 360;

    //类型传参，实例化对应model用
    const MODULE_TYPE_P2P = 'p2p'; // p2p
    const MODULE_TYPE_DUOTOU = 'duotou'; //多投
    const MODULE_TYPE_REG = 'reg'; //注册
    const MODULE_TYPE_JIJIN = 'jijin';
    const MODULE_TYPE_GOLD = 'gold'; //黄金定期
    const MODULE_TYPE_GOLDC = 'goldc'; //黄金活期
    const MODULE_TYPE_DARKMOON = 'darkmoon'; //专享线下
    const MODULE_TYPE_NCFPH = 'ncfph'; //普惠
    const MODULE_TYPE_THIRD = 'third'; //第三方标准

    const MODULE_TYPE_P2P_NAME = '邀请奖励';
    const MODULE_TYPE_DUOTOU_NAME = '智多新';
    const MODULE_TYPE_REG_NAME = '邀请注册';
    const MODULE_TYPE_JIJIN_NAME = '基金';
    const MODULE_TYPE_GOLD_NAME = '黄金';
    const MODULE_TYPE_GOLDC_NAME = '黄金活期';
    const MODULE_TYPE_DARKMOON_NAME = '专享线下';
    const MODULE_TYPE_NCFPH_NAME = '普惠';
    const MODULE_TYPE_THIRD_NAME = '网贷-第三方';

    //被邀请人的投资状态
    const STATUS_BIND_BANK_NO = 0; //待绑定银行卡
    const STATUS_INVEST_NO = 1;    //待投资
    const STATUS_INVEST = 2;       //已投资

    const PAGE_TOTAL = 1000;
    const EXPIRE_TIME = 15552000; //86400 * 180

    //类型传参，实例化对应model用
    public static $module_map = array(
            self::MODULE_TYPE_P2P,
            self::MODULE_TYPE_DUOTOU,
            self::MODULE_TYPE_REG,
            self::MODULE_TYPE_JIJIN,
            self::MODULE_TYPE_GOLD,
            self::MODULE_TYPE_GOLDC,
            self::MODULE_TYPE_DARKMOON,
            self::MODULE_TYPE_NCFPH,
            self::MODULE_TYPE_THIRD,
        );

    //前台显示邀请记录tab页名称及开关用
    public static $module_name_map = array(
            self::MODULE_TYPE_P2P => self::MODULE_TYPE_P2P_NAME,
            self::MODULE_TYPE_DUOTOU => self::MODULE_TYPE_DUOTOU_NAME,
            self::MODULE_TYPE_REG => self::MODULE_TYPE_REG_NAME,
            self::MODULE_TYPE_JIJIN => self::MODULE_TYPE_JIJIN_NAME,
            self::MODULE_TYPE_GOLD => self::MODULE_TYPE_GOLD_NAME,
            self::MODULE_TYPE_GOLDC => self::MODULE_TYPE_GOLDC_NAME,
            self::MODULE_TYPE_DARKMOON => self::MODULE_TYPE_DARKMOON_NAME,
            self::MODULE_TYPE_NCFPH => self::MODULE_TYPE_NCFPH_NAME,
            self::MODULE_TYPE_THIRD => self::MODULE_TYPE_THIRD_NAME,
        );

    //邀请码返利记录上线之后的返利叫服务返利，上线之前叫邀请返利，以上线的时间点做的区分
    const DATA_TYPE_ALL = 0;
    const DATA_TYPE_INVITE  = 1; //邀请返利
    const DATA_TYPE_SERVICE = 2; //服务返利

    /**
     * 根据类型，发送不同短信
     */
    protected static $pay_msg_tpl = array(
        '1' => array('rebate_amount' => array('user_log_info' => '注册返利', 'sms_tpl' => 'TPL_SMS_USER_SIGNUP_INVITE', 'sms_title' => '被邀请注册返利'),
                     'referer_rebate_amount' => array('user_log_info' => '邀请返利', 'sms_tpl' => 'TPL_REGISTER_INVITE_REBATE_SMS', 'sms_title' => '邀请他人注册返利'), ),
        '2' => array('rebate_amount' => array('user_log_info' => '投资返利', 'sms_tpl' => 'TPL_SMS_USER_INVEST', 'sms_title' => '投资返利'),
                     'rebate_ratio_amount' => array('user_log_info' => '投资返利', 'sms_tpl' => 'TPL_SMS_USER_INVEST', 'sms_title' => '投资返利'),
                     'referer_rebate_amount' => array('user_log_info' => '邀请返利', 'sms_tpl' => 'TPL_SMS_INVITE_OTHERS_INVEST', 'sms_title' => '推荐人邀请返利'),
                     'referer_rebate_ratio_amount' => array('user_log_info' => '邀请返利', 'sms_tpl' => 'TPL_SMS_INVITE_OTHERS_INVEST', 'sms_title' => '推荐人邀请返利'),
                     'agency_rebate_amount' => array('user_log_info' => '机构返利', 'sms_tpl' => '', 'sms_title' => ''),
                     'agency_rebate_ratio_amount' => array('user_log_info' => '机构返利', 'sms_tpl' => '', 'sms_title' => ''), ), );

    private $dateType;
    public $coupon_log_dao;
    public $coupon_pay_log_dao;

    public function __construct($module = self::MODULE_TYPE_P2P,$dataType = 0)
    {
        $this->dataType = $dataType;
        $this->module = $module;
        if (empty($module) || !in_array($module, self::$module_map)) {
            throw new \Exception('module['.$module.'] is not exist!');
        }
        $this->coupon_log_dao = CouponLogModel::getInstance($module,$this->dataType);
        if(in_array($module, array(self::MODULE_TYPE_P2P, self::MODULE_TYPE_NCFPH,self::MODULE_TYPE_THIRD))) {
            $this->coupon_deal_dao = CouponDealModel::getInstance($module);
        }
        if (in_array($module, array(self::MODULE_TYPE_P2P, self::MODULE_TYPE_DUOTOU, self::MODULE_TYPE_JIJIN))) {
            $this->coupon_pay_log_dao = CouponPayLogModel::getInstance($module);

        }
    }

    /**
     * 添加邀请码使用记录，支持注册和投资.
     *
     * @param $type 邀请码记录类型
     * @param $coupon 邀请码信息
     * @param $consume_user_id 消费用户ID
     * @param $deal_load_id 投标ID
     * @param $coupon_fields 后台添加邀请码的附加信息
     */
    public function addLog($coupon, $consume_user_id, $deal_load_id = 0, $coupon_fields = array())
    {
        $model_class = get_class($this->coupon_log_dao);
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $model_class, $consume_user_id, $deal_load_id, 'coupon info:', json_encode($coupon),
                          json_encode($coupon_fields), );
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        if (empty($coupon) || empty($consume_user_id) || empty($deal_load_id)) {
            Logger::error(implode(' | ', array_merge($log_info, array('error params'))));

            return false;
        }
        if (!in_array($this->module, self::$module_map)) {
            Logger::error(implode(' | ', array_merge($log_info, array('error type'))));

            return false;
        }

        //邀请码记录赋值
        $coupon_log = new $model_class();

        // 同一个标或者注册，只有一条返利记录
        $existDealLoad = $coupon_log->findByDealLoadId($deal_load_id, $consume_user_id);
        if (!empty($existDealLoad)) {
            return false;
        }

        foreach ($coupon as $k => $v) {
            $key_new = CouponLogService::camelToUnderline($k);
            $coupon_log->$key_new = $v;
        }

        if (!empty($coupon_fields) && is_array($coupon_fields)) {
            foreach ($coupon_fields as $key => $val) {
                $coupon_log->$key = $val;
            }
        }
        unset($coupon_log['id']);

        $coupon_log->type = CouponService::TYPE_DEAL;
        $coupon_log->deal_load_id = $deal_load_id;
        $coupon_log->consume_user_id = $consume_user_id;
        $user_model = new UserModel();
        $consume_user = $user_model->findViaSlave($consume_user_id, 'user_name');
        $coupon_log->consume_user_name = $consume_user['user_name'];
        //$refer_user = $user_model->findViaSlave($coupon_log->refer_user_id, 'user_name'); // queryCoupon查出
        //$coupon_log->refer_user_name = $refer_user['user_name'];
        //$coupon_log->referer_rebate_ratio_factor = $this->getUserPayFactor($coupon_log->refer_user_id);
        $coupon_log->create_time = get_gmtime();
        $coupon_log->update_time = get_gmtime();
        // 直接财务待审核状态 还原成原来 todo 确认
        //$coupon_log->pay_status = CouponService::PAY_STATUS_FINANCE_AUDIT;
        //if ($type == CouponService::TYPE_SIGNUP && !isset($coupon_fields['pay_status'])) {
        //    $coupon_log->pay_status = -2; // 注册未实名认证
        //}

        //订单信息
        if (self::MODULE_TYPE_P2P == $this->module) {
            $deal_loan = DealLoadModel::instance()->find($deal_load_id, 'deal_id,money,site_id');
            $deal_info = DealModel::instance()->find($deal_loan->deal_id, 'id,deal_type,loantype,repay_start_time,repay_time,deal_status');
            $coupon_deal = CouponDealModel::instance()->findBy('deal_id=:deal_id', 'rebate_days', array(':deal_id' => $deal_loan->deal_id));
            //同步更新coupon_log 中的 deal_status(标状态)
            if ($deal_info['deal_status'] == DealModel::$DEAL_STATUS['failed']) { //流标
                $coupon_log->deal_status = 2;
            } elseif (in_array($deal_info['deal_status'], array(DealModel::$DEAL_STATUS['repaying'], DealModel::$DEAL_STATUS['repaid']))) { //还款中，已还清
                $coupon_log->deal_status = 1;
            }
            // 小贷的标 邀请人返点比例为0
            if (self::DEAL_TYPE_PETTYLOAN == $deal_info['deal_type']) {
                $coupon_log->referer_rebate_ratio = 0;
            }
            $coupon_log->deal_id = $deal_loan->deal_id;
            $coupon_log->site_id = $deal_loan->site_id;
            $coupon_log->deal_load_money = $deal_loan['money'];
            $coupon_log->deal_type = $deal_info['deal_type']; //标的类型，0为普通标，1为通知贷 2 交易所
            //标的期限，按月*30转为天数
            $coupon_log->deal_repay_days = ('5' == $deal_info['loantype']) ? $deal_info['repay_time'] : $deal_info['repay_time'] * CouponLogService::DAYS_OF_MONTH;
            //返利天数 通知贷=0；对于普通标，如果邀请码配置信息coupon_deal有返利天数的值则取该值，否则去标的期限
            if (CouponLogService::DEAL_TYPE_COMPOUND == $coupon_log->deal_type) {
                $coupon_log->rebate_days = 0;
            } else {
                $coupon_log->rebate_days = (empty($coupon_deal) || empty($coupon_deal['rebate_days'])) ? $coupon_log->deal_repay_days : $coupon_deal['rebate_days'];
            }
            $coupon_log->referer_rebate_ratio_factor = $this->getRebateFactor($deal_info);
        } elseif (self::MODULE_TYPE_DUOTOU == $this->module) {
            $coupon_log->deal_status = 1;
            $coupon_log->deal_id = $coupon_fields['deal_id'];
            $coupon_log->deal_load_money = $coupon_fields['money'];
            $coupon_log->deal_type = self::DEAL_TYPE_COMPOUND; //标的类型，0为普通标，1为通知贷
            $coupon_log->deal_repay_days = 0; //标的期限，多投宝无期限
            $coupon_log->deal_repay_time = '1640880000'; //标的回款日，多投宝无期限，未方便结算定为2019-01-01，埋个3年的地雷,继续埋雷三年 到2021-12-31
            $coupon_log->repay_start_time = $coupon_fields['repay_start_time']; //投资记录的起息时间
            $coupon_log->rebate_days = 0;
            $coupon_log->rebate_days_update_time = $coupon_fields['repay_start_time']; //起息日
            if (empty($coupon_log['deal_id']) || empty($coupon_log['deal_load_money']) || empty($coupon_log['rebate_days_update_time'])) {
                Logger::info(implode(' | ', array_merge($log_info, array('error coupon_fields params'))));
                return false;
            }
        } elseif (self::MODULE_TYPE_JIJIN == $this->module) {
            $coupon_log->deal_status = 1;
            $coupon_log->deal_id = $coupon_fields['deal_id'];
            $coupon_log->deal_load_money = $coupon_fields['money'];
            $coupon_log->deal_type = self::DEAL_TYPE_COMPOUND; //标的类型，0为普通标，1为通知贷或者基金
            $coupon_log->deal_repay_days = 0; //标的期限，基金无期限
            $coupon_log->deal_repay_time = '1640880000';  //标的回款日，多投宝无期限，未方便结算定为2019-01-01，埋个3年的地雷,继续埋雷三年 到2021-12-31
                        $coupon_log->repay_start_time = $coupon_fields['repay_start_time']; //投资记录的起息时间
            $coupon_log->rebate_days = 0;
            $coupon_log->rebate_days_update_time = $coupon_fields['repay_start_time']; //起息日
            if (empty($coupon_log['deal_id']) || empty($coupon_log['deal_load_money']) || empty($coupon_log['rebate_days_update_time'])) {
                Logger::info(implode(' | ', array_merge($log_info, array('error coupon_fields params'))));

                return false;
            }
        } elseif (self::MODULE_TYPE_DARKMOON == $this->module) {//专享线下
            $coupon_log->deal_status = 1;
            $coupon_log->pay_status = CouponService::PAY_STATUS_OFFLINE; //线下结算
            $coupon_log->deal_id = $coupon_fields['deal_id'];
            $coupon_log->deal_load_money = $coupon_fields['money'];
            $coupon_log->deal_type = self::DEAL_TYPE_DARKMOON; //标的类型，0为定期，1不定期
            $coupon_log->deal_repay_days = ('5' == $coupon_fields['loantype']) ? $coupon_fields['repay_time'] : $coupon_fields['repay_time'] * CouponLogService::DAYS_OF_MONTH;
            $coupon_log->rebate_days = $coupon_log->deal_repay_days;
            $couponService = new CouponService($this->module);
            $deal_info = array('deal_type' => $coupon_log->deal_type, 'loantype' => $coupon_fields['loantype']);
            $coupon_log->referer_rebate_ratio_factor = $this->getRebateFactor($deal_info);
        } elseif (self::MODULE_TYPE_GOLD == $this->module) {//黄金
            $coupon_log->deal_status = 1;
            $coupon_log->deal_id = $coupon_fields['deal_id'];
            $coupon_log->deal_load_money = $coupon_fields['money'];
            $coupon_log->deal_type = self::DEAL_TYPE_GENERAL; //标的类型，0为定期，1不定期
            $coupon_log->deal_repay_days = ('5' == $coupon_fields['loantype']) ? $coupon_fields['repay_time'] : $coupon_fields['repay_time'] * CouponLogService::DAYS_OF_MONTH;
            $coupon_log->rebate_days = $coupon_log->deal_repay_days;
            $couponService = new CouponService($this->module);
            $deal_info = $couponService->getDealInfoByDealId($coupon_log['deal_id']);
            if (empty($deal_info)) {
                throw new \Exception('标记录不存在！');
            }
            $coupon_log->referer_rebate_ratio_factor = $this->getRebateFactor($deal_info);
        } elseif (self::MODULE_TYPE_GOLDC == $this->module) {//黄金活期
            $coupon_log->deal_status = 1;
            $coupon_log->deal_id = $coupon_fields['deal_id'];
            $coupon_log->deal_load_money = $coupon_fields['money'];
            $coupon_log->deal_type = self::DEAL_TYPE_GENERAL; //标的类型，0为定期，1不定期
            $coupon_log->deal_repay_days = 1; // 黄金活期返利计算为每天一结
            $coupon_log->rebate_days = $coupon_log->deal_repay_days;
            $coupon_log->referer_rebate_ratio_factor = 0.5; // 黄金活期返利系数为0.5
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($coupon_log->getRow()), 'coupon log info'))));
        }elseif(self::MODULE_TYPE_NCFPH == $this->module) {
            $coupon_deal = $this->coupon_deal_dao->findBy('deal_id=:deal_id', 'rebate_days,deal_type,loantype,start_pay_time,repay_time', array(':deal_id' => $coupon_fields['deal_id']));
            $coupon_log->deal_status = 1;//字段没多大用
            $coupon_log->deal_id = $coupon_fields['deal_id'];
            $coupon_log->site_id = $coupon_fields['site_id'];
            $coupon_log->deal_load_money = $coupon_fields['money'];
            $couponService = new CouponService($this->module);
            $deal_info = $couponService->getDealInfoByDealId($coupon_log['deal_id']);
            $coupon_log->deal_type = $deal_info['deal_type']; //标的类型，0为普通标，1为通知贷 2 交易所
            //标的期限，按月*30转为天数
            $coupon_log->deal_repay_days = $coupon_deal['rebate_days'];
            $coupon_log->deal_type = $coupon_deal['deal_type'];
            $coupon_log->loantype = $coupon_deal['loantype'];
            $coupon_log->repay_start_time = $coupon_deal['start_pay_time'];
            $coupon_log->repay_time = $coupon_deal['repay_time'];
            //返利天数 通知贷=0；对于普通标，如果邀请码配置信息coupon_deal有返利天数的值则取该值，否则去标的期限

            $coupon_log->rebate_days = $coupon_log->deal_repay_days;
            $coupon_log->referer_rebate_ratio_factor = $this->getRebateFactor($deal_info);
        }elseif(self::MODULE_TYPE_THIRD == $this->module) {
            $coupon_deal = $this->coupon_deal_dao->findBy('deal_id=:deal_id', 'rebate_days,deal_type,loantype,start_pay_time,repay_time', array(':deal_id' => $coupon_fields['deal_id']));
            if (empty($coupon_deal)) {
                throw new \Exception('标的优惠码信息不存在！');
            }
            $coupon_log->deal_status = 1;//字段没多大用
            $coupon_log->deal_id = $coupon_fields['deal_id'];
            $coupon_log->site_id = $coupon_fields['site_id'];
            $coupon_log->deal_load_money = $coupon_fields['money'];
            $couponService = new CouponService($this->module);
            $deal_info = $couponService->getDealInfoByDealId($coupon_log['deal_id']);
            $coupon_log->deal_type = $deal_info['deal_type']; //标的类型，0为普通标，2工具投资
            //标的期限，按月*30转为天数
            $coupon_log->deal_repay_days = $coupon_deal['rebate_days'];
            $coupon_log->loantype = $coupon_deal['loantype'];
            $coupon_log->repay_start_time = $coupon_deal['start_pay_time'];
            $coupon_log->repay_time = $coupon_deal['repay_time'];
            //返利天数 通知贷=0；对于普通标，如果邀请码配置信息coupon_deal有返利天数的值则取该值，否则去标的期限
            $coupon_log->rebate_days = $coupon_log->deal_repay_days;
            $coupon_log->referer_rebate_ratio_factor = $this->getRebateFactor($deal_info);
            $coupon_log->client_id = $coupon_fields['client_id'];
        }

        //投资人过了政策时间，就不会给邀请人返利
        //返利政策开关
        $coupon_rebate_policy_switch = app_conf('COUPON_REBATE_POLICY_SWITCH');
        $refer_user_id = $coupon_log->refer_user_id;
        if ('1' == $coupon_rebate_policy_switch && 0 != $refer_user_id && !$this->hasRebate($coupon_log->consume_user_id, $coupon_log->refer_user_id)) {
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($coupon_log->getRow()), '投资人注册时间超过政策时间，不给邀请返利'))));
            $coupon_log->referer_rebate_ratio = 0;
        }
        //投资人无返利
        $coupon_log->rebate_ratio = 0;

        if (CouponLogService::DEAL_TYPE_DARKMOON == $coupon_log->deal_type) {
            if (CouponService::SHORT_ALIAS_DEFAULT !== $coupon_log->short_alias) {
                $coupon_log->referer_rebate_ratio = bccomp($coupon_log->deal_load_money, 500000) >= 0 ? 1.2 : 1;
                $coupon_log->agency_rebate_ratio = bccomp($coupon_log->deal_load_money, 500000) >= 0 ? 1.3 : 1;
            } else {
                $coupon_log->referer_rebate_ratio = 0;
                $coupon_log->agency_rebate_ratio = 0;
            }
        }

        //返点比例金额 ，通知贷返点比较金额初始化为0，做累加计算
        if (CouponLogService::DEAL_TYPE_COMPOUND == $coupon_log->deal_type) {
            $coupon_log->rebate_ratio_amount = 0;
            $coupon_log->referer_rebate_ratio_amount = 0;
            $coupon_log->agency_rebate_ratio_amount = 0;
        } else {
            $coupon_log->rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_log->rebate_ratio, $coupon_log);
            $coupon_log->referer_rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_log->referer_rebate_ratio, $coupon_log);
            $coupon_log->agency_rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_log->agency_rebate_ratio, $coupon_log);
        }

        // 黄金活期没有邀请返利，不结算 (需求没确定如果有机构返利结算与否，此处自由发挥)
        if (self::MODULE_TYPE_GOLDC == $this->module && bccomp($coupon_log->referer_rebate_ratio_amount, '0.00', 2) <= 0) {
            Logger::info(implode(' | ', array_merge($log_info, array(json_encode($coupon_log->getRow()), 'referer_rebate_ratio_amount <= 0', 'pass'))));

            return true;
        }

        // 单个标里同个用户只能获得一次返点金额
        //if ($coupon_log->rebate_amount > 0 && $coupon_log->isExistOneConsumeOneRebate($coupon_log->consume_user_id, $coupon_log->deal_id)) {
        //    $coupon_log->rebate_amount = 0;
        //}

        // 返利金额都记为0, 只算返点比例 20171009
        $coupon_log->rebate_amount = 0;
        $coupon_log->referer_rebate_amount = 0;
        $coupon_log->agency_rebate_amount = 0;

        $rs = $coupon_log->save();
        Logger::info(implode(' | ', array_merge($log_info, array(json_encode($coupon_log->getRow()), 'commit', $rs))));

        if ($rs) {
            $coupon_log = $coupon_log->find($coupon_log['id']);

            return $coupon_log;
        } else {
            return false;
        }
    }

    /**
     * 添加邀请码附加返利消费记录.
     *
     * @param $coupon_extra 附加返利规则
     * @param $coupon_log_id 邀请码记录id（如果为空则以deal_load信息为准，用于首尾标返利）
     * @param $deal_load 投资信息，首尾标返利时传
     */
    public function addCouponExtraLog($coupon_extra, $coupon_log_id, $deal_load = array())
    {
        $log_info = array(__CLASS__, __FUNCTION__, json_encode($coupon_extra), $coupon_log_id);
        if ($deal_load) {
            $log_info[] = is_array($deal_load) ? json_encode($deal_load) : json_encode($deal_load->getRow());
        }
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        if (empty($coupon_extra) || (empty($coupon_log_id) && empty($deal_load))) {
            Logger::info(implode(' | ', array_merge($log_info, array('error params'))));

            return false;
        }
        $GLOBALS['db']->startTrans();
        try {
            if (empty($coupon_log_id)) {
                $coupon_log = $this->coupon_log_dao->findByDealLoadId($deal_load['id']);
                if (empty($coupon_log)) { //新增默认全局邀请码，切换附件返利上线期间，进行中和已满标存在没有默认邀请码情况，之后的新标投资不会到这一步 20141016
                    Logger::info(implode(' | ', array_merge($log_info, array('add short alias default for deal old'))));
                    $coupon_service = new CouponService();
                    $coupon_log = $coupon_service->consume($deal_load['id'], '', $deal_load['user_id'], array(), $coupon_service::COUPON_SYNCHRONOUS);
                }
            } else {
                $coupon_log = $this->coupon_log_dao->find($coupon_log_id);
            }

            if (empty($coupon_log)) {
                throw new \Exception('获取邀请码记录失败!');
            }
            $log_info[] = json_encode($coupon_log->getRow());

            //4个返利数值叠加
            $coupon_extra_log = new CouponExtraLogModel();
            // 返利金额都记为0, 只算返点比例 20171009
            $handle_fields = array('rebate_ratio' => '5', 'referer_rebate_ratio' => '5');
            //$handle_fields = array('rebate_amount' => '2', 'rebate_ratio' => '5', 'referer_rebate_amount' => '2', 'referer_rebate_ratio' => '5');
            //rebate_amount 返点金额  rebate_ratio 返点比例  referer_rebate_amount 邀请人返点金额  referer_rebate_ratio 邀请人返点比例
            $is_coupon_log_update = false;
            foreach ($handle_fields as $field => $scale) {
                $coupon_extra_log->$field = $coupon_extra[$field];
                if (bccomp($coupon_extra[$field], '0.00000', $scale) <= 0) {
                    continue;
                }
                $coupon_log->$field = bcadd($coupon_log[$field], $coupon_extra[$field], $scale);
                $is_coupon_log_update = true;
            }
            if ($is_coupon_log_update) {
                $rs = $coupon_log->save();
                if (empty($rs)) {
                    throw new \Exception('更新邀请码记录失败!');
                }

                // 更新金额计算 ,如果标是普通标类型，则更新返点比例金额，通知贷不更新，因为通知到返点比例金额要做累加，修改了优惠码会把已经反的比例金额按照新的邀请码计算
                if (in_array($coupon_log->deal_type, CouponLogService::$deal_type_group1)) {
                    $rs = $this->updateAmount($coupon_log['id']);
                    if (empty($rs)) {
                        throw new \Exception('更新邀请码记录金额失败!');
                    }
                }
            }

            $coupon_extra_log->coupon_log_id = $coupon_log['id'];
            $coupon_extra_log->deal_id = $coupon_log['deal_id'];
            $coupon_extra_log->deal_load_id = $coupon_log['deal_load_id'];
            $coupon_extra_log->deal_load_money = $coupon_log['deal_load_money'];
            $coupon_extra_log->consume_user_id = $coupon_log['consume_user_id'];
            $coupon_extra_log->consume_user_name = $coupon_log['consume_user_name'];
            $coupon_extra_log->coupon_extra_id = $coupon_extra['id'];
            $coupon_extra_log->tags = $coupon_extra['tags'];
            $coupon_extra_log->create_time = get_gmtime();
            $coupon_extra_log->type = $coupon_extra['source_type'];
            //通知贷上线前，这两个字段用的是coupon_log的比例，存的比例金额等于每次叠加后的比例金额
            $coupon_extra_log->rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_extra_log->rebate_ratio, $coupon_log);
            $coupon_extra_log->referer_rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_extra_log->referer_rebate_ratio, $coupon_log);
            $log_info[] = json_encode($coupon_extra_log->getRow());

            $rs = $coupon_extra_log->insert();
            if (empty($rs)) {
                throw new \Exception('添加附加返利记录失败!');
            }
            $rs = $GLOBALS['db']->commit();
            Logger::info(implode(' | ', array_merge($log_info, array("commit:{$rs}"))));
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(implode(' | ', array_merge($log_info, array('exception:'.$e->getMessage()))));

            return false;
        }

        return $rs;
    }

    /**
     * 更新返点金额计算结果.
     *
     * 用于邀请码消费记录的推荐人或者返利系数变更后的返利金额更新
     *
     * @param $log_id
     *
     * @return bool
     */
    public function updateAmount($log_id)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $log_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $item = $this->coupon_log_dao->find($log_id);
        if (empty($item)) {
            return false;
        }
        $log_info[] = json_encode($item->getRow());

        // 更新返点比例金额
        $item->rebate_ratio_amount = CouponLogService::calRatioAmount($item->rebate_ratio, $item);
        $item->referer_rebate_ratio_amount = CouponLogService::calRatioAmount($item->referer_rebate_ratio, $item);
        $item->agency_rebate_ratio_amount = CouponLogService::calRatioAmount($item->agency_rebate_ratio, $item);

        $rs = $item->save();
        Logger::info(implode(' | ', array_merge($log_info, array(json_encode($item->getRow()), 'done', $rs))));

        return $rs;
    }

    /**
     * 返点比例换算返点比例金额.
     *
     * @param $rebate_ratio 返点比例
     * @param $coupon_log 返利信息
     *
     * @return float|int 返点比例金额
     */
    public static function calRatioAmount($rebate_ratio, $coupon_log, $rebate_days = false)
    {
        $rebate_ratio_amount = 0;
        if ($rebate_ratio <= 0 || empty($coupon_log) || $coupon_log->deal_load_money <= 0 || $coupon_log->referer_rebate_ratio_factor <= 0) {
            return $rebate_ratio_amount;
        }
        if (false === $rebate_days) {
            if (CouponLogService::DEAL_TYPE_COMPOUND == $coupon_log['deal_type']) {
                $rebate_days = $coupon_log->deal_repay_days;
            } else {
                $rebate_days = $coupon_log->rebate_days;
            }
        }
        $rebate_ratio_amount = $coupon_log->deal_load_money * $rebate_ratio * 0.01 * $rebate_days * $coupon_log->referer_rebate_ratio_factor * $coupon_log->discount_ratio * $coupon_log->product_ratio * $coupon_log->tool_ratio / CouponLogService::DAYS_OF_YEAR;

        return round($rebate_ratio_amount, 2);
    }

    /**
     * 驼峰命名转下划线
     */
    public static function camelToUnderline($str)
    {
        if (empty($str)) {
            return '';
        }
        $result = '';
        for ($i = 0; $i < strlen($str); ++$i) {
            if ($str[$i] != strtolower($str[$i])) {
                $result .= '_'.strtolower($str[$i]);
            } else {
                $result .= $str[$i];
            }
        }

        return $result;
    }

    /**
     * 结算邀请码记录.
     *
     * @param $ids 邀请码id。 考虑悲观锁和事务提交不block大量数据，只支持单个id，不支持多个id集合。
     * @param int $pay_status 结算状态 0:未结算，1：原自动结算(现转入3)；2：已结算；3：运营通过；4.财务拒绝
     *
     * @return bool
     */
    public function pay($id)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $turn_on_coupon_pay = app_conf('COUPON_PAY_DISABLE');
        if (1 == $turn_on_coupon_pay) {
            Logger::warn(implode(' | ', array_merge($log_info, array('system deny settlement'))));

            return true;
        }
        //参数检查
        if (empty($id)) {
            Logger::info(implode(' | ', array_merge($log_info, array('error id'))));

            return false;
        }

        $user_dao = new UserModel();
        $syncRemoteData = array(); //资金托管同步参数

        // 悲观锁，以id为锁的键名
        $lockKey = 'CouponLogService-pay-'.$this->module.'-'.$id;
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 300)) {
            throw new \Exception('加锁失败!');
        }

        $coupon_log = $this->coupon_log_dao->find($id);

        $log_info[] = json_encode($coupon_log->getRow());
        // 只结算还款中未结算记录
        if (empty($coupon_log) || empty($coupon_log['deal_load_id'])) {
            throw new \Exception('邀请码记录信息不正确!');
        }
        CouponMonitorService::process(CouponMonitorService::ITEM_PAY, CouponMonitorService::TOTAL, $this->module, $coupon_log['deal_type']);
        //$deal_info = $this->getDealData($coupon_log['deal_id']);
        $couponService = new CouponService($this->module);
        $deal_info = $couponService->getDealInfoByDealId($coupon_log['deal_id']);
        if (empty($deal_info)) {
            throw new \Exception('标记录不存在！');
        }

        //智多新标名称取项目名称
        if (CouponLogService::MODULE_TYPE_DUOTOU == $this->module) {
            $deal_info['name'] = $deal_info['project_name'];
        }

        if (in_array($this->module, array(CouponLogService::MODULE_TYPE_P2P,CouponLogService::MODULE_TYPE_NCFPH))) {
            if ($deal_info['deal_status'] < 4) {
                throw new \Exception('未满标放款,标状态不满足还清时结算');
            }
        }
        if (in_array($this->module, array(CouponLogService::MODULE_TYPE_GOLD))) {
            if ($deal_info['deal_status'] < 4) {
                Logger::info(implode(' | ', array_merge($log_info, array('未满标放款,标状态不满足还清时结算', 'done'))));

                return false;
            }
        }
        if (in_array($this->module, array(CouponLogService::MODULE_TYPE_P2P,CouponLogService::MODULE_TYPE_NCFPH))) {
            $coupon_deal_dao = CouponDealModel::getInstance($this->module);
            $coupon_deal_info = $coupon_deal_dao->findBy('deal_id=:deal_id', 'pay_type', array(':deal_id' => $coupon_log['deal_id']));
            if (1 == $coupon_deal_info['pay_type'] && 5 != $deal_info['deal_status']) {
                throw new \Exception('标状态不满足还清时结算');
            }
        }

        // 已结算
        if (in_array($coupon_log['pay_status'], array(CouponService::PAY_STATUS_AUTO_PAID, CouponService::PAY_STATUS_PAID, CouponService::PAY_STATUS_OFFLINE))) {
            Logger::info(implode(' | ', array_merge($log_info, array('已经结算过', 'done'))));

            return false;
        }

        $GLOBALS['db']->startTrans();
        try {
            // 获取会员记录
            $consume_user = $user_dao->find($coupon_log['consume_user_id'], 'id,mobile,real_name,user_name');
            $refer_user = $user_dao->find($coupon_log['refer_user_id'], 'id,real_name,mobile,group_id');
            if (empty($consume_user)) {
                throw new \Exception('投资用户信息不存在!');
            }

            // 开放平台联盟理财师，线下结算
            $is_refer_user_non_pay_group = empty($refer_user) ? false : CouponService::isPayOffline($refer_user['group_id']);
             Logger::info(implode(' | ', array_merge($log_info,array('是否线下结算 : '.$is_refer_user_non_pay_group))));
            // 投资人返利 -- 停止，注释 20180529
            /*$user_platform_id = $this->get_user_platform($coupon_log);
            $user_platform = $user_dao->find($user_platform_id, 'id');
            if (empty($user_platform)) {
                throw new \Exception('平台用户信息不存在!');
            }
            */

            $coupon_service = new CouponService($this->module);
            if (in_array($coupon_log['deal_type'], CouponLogService::$deal_type_group1)) { //非通知贷
                if ($coupon_log['pay_time']) {
                    throw new \Exception('邀请码记录信息不正确!');
                }
                // 更新邀请码记录状态;
                if ($is_refer_user_non_pay_group) {
                    $rs = $coupon_service->updateLogStatus($id, CouponService::PAY_STATUS_OFFLINE);
                } else {
                    $rs = $coupon_service->updateLogStatus($id, CouponService::PAY_STATUS_PAID);
                }
                if (0 != $rs['code']) {
                    throw new \Exception('更新邀请码记录状态失败!');
                }

                $item = $coupon_log->getRow();
            } else { //通知贷
                if (empty($coupon_log['rebate_days'])) {
                    //如果返利天数为零，并且更新返利时间>=赎回时间，则更新返利状态为已计算，主要是为了防止多投刚结清返利之后，立即赎回，导致返利天数不足一天，无法更新结算状态为已结算
                    if (!empty($coupon_log['deal_repay_time']) && $coupon_log['rebate_days_update_time'] >= $coupon_log['deal_repay_time']) {
                        $rs = $coupon_service->updateLogStatus($id, CouponService::PAY_STATUS_PAID);
                        Logger::info(implode(' | ', array_merge($log_info, array('更新返利天数时间大于等于赎回时间,更新为返利为已结算', $rs))));
                        if (0 != $rs['code']) {
                            throw new \Exception('更新邀请码记录状态失败!');
                        }
                    } else {
                        //需要结算的返利天数，不需结算，可重复执行
                        Logger::info(implode(' | ', array_merge($log_info, array('返利天数为0，忽略', 'done'))));
                    }

                    $GLOBALS['db']->commit();
                    $lock->releaseLock($lockKey); // 解锁
                    return true;
                }

                // 通知贷周期返利记录
                $pay_model_class = get_class($this->coupon_pay_log_dao);
                $coupon_pay_log = new $pay_model_class();
                //todo 检查是否存在已结算？
                foreach ($coupon_log->getRow() as $key => $val) {
                    $coupon_pay_log->$key = $val;
                }
                unset($coupon_pay_log['id']);
                $coupon_pay_log->coupon_log_id = $coupon_log['id'];
                $coupon_pay_log->pay_day = to_timespan(date('Y-m-d'));
                $coupon_pay_log->create_time = get_gmtime();
                $coupon_pay_log->update_time = get_gmtime();
                $coupon_pay_log->pay_time = get_gmtime();

                // 计算周期返利金额
                $coupon_pay_log->rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_pay_log->rebate_ratio, $coupon_pay_log, $coupon_pay_log->rebate_days);
                $coupon_pay_log->referer_rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_pay_log->referer_rebate_ratio, $coupon_pay_log, $coupon_pay_log->rebate_days);
                $coupon_pay_log->agency_rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_pay_log->agency_rebate_ratio, $coupon_pay_log, $coupon_pay_log->rebate_days);
                if (CouponLogService::MODULE_TYPE_P2P == $this->module) {
                    $coupon_pay_log->rebate_amount = round($coupon_log->rebate_amount * $coupon_pay_log->rebate_days / $coupon_pay_log->deal_repay_days, 2);
                    $coupon_pay_log->referer_rebate_amount = round($coupon_log->referer_rebate_amount * $coupon_pay_log->rebate_days / $coupon_pay_log->deal_repay_days, 2);
                    $coupon_pay_log->agency_rebate_amount = round($coupon_log->agency_rebate_amount * $coupon_pay_log->rebate_days / $coupon_pay_log->deal_repay_days, 2);
                }

                $rs = $coupon_pay_log->insert();
                if (empty($rs)) {
                    throw new \Exception('新增通知贷周期返利记录失败');
                }

                //通知贷结算累加
                $coupon_log->rebate_ratio_amount += $coupon_pay_log->rebate_ratio_amount;
                $coupon_log->referer_rebate_ratio_amount += $coupon_pay_log->referer_rebate_ratio_amount;
                $coupon_log->agency_rebate_ratio_amount += $coupon_pay_log->agency_rebate_ratio_amount;
                if (CouponLogService::MODULE_TYPE_DUOTOU == $this->module) {
                    $coupon_log->deal_repay_days += $coupon_pay_log->rebate_days;
                }

                // 更新邀请码记录状态，待返天数清零
                $coupon_log->rebate_days = 0;
                $coupon_log->pay_time = get_gmtime();
                $coupon_log->update_time = get_gmtime();
                $rs = $coupon_log->save();
                if (empty($rs)) {
                    throw new \Exception('更新通知贷邀请码记录失败');
                }

                // 更新邀请码结算状态
                if (CouponService::PAY_STATUS_NOT_PAY == $coupon_log['pay_status'] || CouponService::PAY_STATUS_PAYING == $coupon_log['pay_status']) {
                    $rs = array('code' => '0');
                    //已结清: 1.结算时间超过回款时间;2.最后一次返利天数更新时间时间超过回款时间;
                    if (!empty($coupon_log['deal_repay_time']) && $coupon_pay_log['pay_day'] >= $coupon_log['deal_repay_time'] && $coupon_log['rebate_days_update_time'] >= $coupon_log['deal_repay_time']) {
                        $rs = $coupon_service->updateLogStatus($id, CouponService::PAY_STATUS_PAID);
                        Logger::info(implode(' | ', array_merge($log_info, array('更新为 已结算', json_encode($rs)))));
                    } elseif (CouponService::PAY_STATUS_NOT_PAY == $coupon_log['pay_status']) {
                        //未结算变成结算中
                        $rs = $coupon_service->updateLogStatus($id, CouponService::PAY_STATUS_PAYING);
                        Logger::info(implode(' | ', array_merge($log_info, array('更新为 结算中', json_encode($rs)))));
                    }
                    if (0 != $rs['code']) {
                        throw new \Exception('更新邀请码记录状态失败!');
                    }
                }
                $item = $coupon_pay_log->getRow();
            }

            $admin_id = 0;
            if (defined('ADMIN_ROOT')) {
                $adm_session = \es_session::get(md5(conf('AUTH_KEY')));
                $admin_id = !empty($adm_session) ? $adm_session['adm_id'] : 0;
            }

            // 投资人返利 -- 停止，注释 20180529
            // 返利备注
            /*$note_platform_common = "编号{$item['deal_id']} {$deal_info['name']} 投资记录ID:{$item['deal_load_id']}";
            //$note_user = CouponService::SHORT_ALIAS_DEFAULT == $item['short_alias'] ? '' : "使用邀请码{$item['short_alias']}";
            $note_user = "投资\"{$deal_info['name']}\"返利";
            $note_platform = $note_platform_common . " 投资人{$consume_user->real_name} 返金额";
            $this->payOut(CouponService::TYPE_DEAL, 'rebate_amount', $item, $consume_user, $user_platform, $note_user, $note_platform, $admin_id, $syncRemoteData);
            $note_platform = $note_platform_common . " 投资人{$consume_user->real_name} 返点";
            $this->payOut(CouponService::TYPE_DEAL, 'rebate_ratio_amount', $item, $consume_user, $user_platform, $note_user, $note_platform, $admin_id, $syncRemoteData);
            */
            // 推荐人返利
            if (!empty($refer_user) && !$is_refer_user_non_pay_group) {
                if ('' != $consume_user->mobile) {
                    $consume_info = $consume_user->mobile;
                } else {
                    $consume_info = $consume_user->user_name;
                }
                //返佣红包开关
                //$turn_on_referer_rebate_by_red = app_conf('COUPON_REREFER_REBATE_BY_RED_TRUN_ON');
                //if( 1 == $turn_on_referer_rebate_by_red){//开

                $o2oService = new O2OService();
                //$couponGroupId = app_conf(strtoupper('COUPON_GROUP_ID_REFERER_REBATE_' . $this->module)); //券组id，不同的业务线不同的邀请返利红包券组ID
                $couponGroupId = $this->getCouponGroupId($coupon_log); //券组id，不同的业务线不同的邀请返利红包券组ID
                if (empty($couponGroupId)) {
                    throw new \Exception('返佣红包券组ID配置错误');
                }

                $tonken = 'referer_rebate_'.$coupon_log['deal_load_id'];
                if (!in_array($coupon_log['deal_type'], CouponLogService::$deal_type_group1)) {
                    $tonken .= '_'.$coupon_pay_log->id;
                }
                if (bccomp($item['referer_rebate_ratio_amount'], '0.00', 2) > 0) {
                    $referer_rebate_by_red_result = $o2oService->acquireCoupons($refer_user['id'], $couponGroupId, $tonken, '', $coupon_log['deal_load_id'], false, $item['referer_rebate_ratio_amount']);
                    Logger::info(implode(' | ', array_merge($log_info, array('返佣红包发送结果 : '.$referer_rebate_by_red_result,'返佣红包券组ID : '.$couponGroupId))));
                    if (empty($referer_rebate_by_red_result)) {
                        throw new \Exception('返佣红包发送失败');
                    }
                }

                /*}else{
                    $note_user = CouponService::SHORT_ALIAS_DEFAULT == $item['short_alias'] ? '' : "使用您的邀请码";
                    $note_user = "{$consume_user->real_name}({$consume_info}){$note_user}投资\"{$deal_info['name']}\"返利";
                    $note_platform = $note_platform_common . " 邀请人{$refer_user->real_name} 返金额";
                    $this->payOut(CouponService::TYPE_DEAL, 'referer_rebate_amount', $item, $refer_user, $user_platform, $note_user, $note_platform, $admin_id, $syncRemoteData);
                    $note_platform = $note_platform_common . " 邀请人{$refer_user->real_name} 返点";
                    $this->payOut(CouponService::TYPE_DEAL, 'referer_rebate_ratio_amount', $item, $refer_user, $user_platform, $note_user, $note_platform, $admin_id, $syncRemoteData);
                }*/
            }

            // 机构返利 jira 2956 12月1号停止
            /*
            if (time() < strtotime('2015-12-01')) {
                $agency_user = $user_dao->find($coupon_log['agency_user_id'], 'id,real_name');
                if (!empty($agency_user)) {
                    $note_user = $note_platform_common;
                    $note_platform = $note_platform_common . " 机构{$agency_user->real_name} 返金额";
                    $this->payOut(CouponService::TYPE_DEAL, 'agency_rebate_amount', $item, $agency_user, $user_platform, $note_user, $note_platform, $admin_id, $syncRemoteData);
                    $note_platform = $note_platform_common . " 机构{$agency_user->real_name} 返点";
                    $this->payOut(CouponService::TYPE_DEAL, 'agency_rebate_ratio_amount', $item, $agency_user, $user_platform, $note_user, $note_platform, $admin_id, $syncRemoteData);
                }
            }
            */
            // 资金托管平台同步
            if (!empty($syncRemoteData)) {
                $finance_queue_result = FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
                if (empty($finance_queue_result)) {
                    throw new \Exception('同步资金平台入队列失败');
                }
            }
            $rs = $GLOBALS['db']->commit();
            $lock->releaseLock($lockKey); // 解锁
            Logger::info(implode(' | ', array_merge($log_info, array("commit:{$rs}"))));
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey); // 解锁
            Logger::info(implode(' | ', array_merge($log_info, array('exception:'.$e->getMessage()))));
            CouponMonitorService::process(CouponMonitorService::ITEM_PAY, CouponMonitorService::FAILED, $this->module, $coupon_log['deal_type']);

            return false;
        }

        CouponMonitorService::process(CouponMonitorService::ITEM_PAY, CouponMonitorService::SUCCESS, $this->module, $coupon_log['deal_type']);

        return $rs;
    }

    /**
     * 返利金额支付.
     *
     * @param string  $rebate_amount_type 返点金额，返点比例
     * @param object  $log_item           邀请码记录
     * @param object  $user_to            返利转入用户
     * @param object  $user_platform      平台用户
     * @param object  $note_user          转入用户的资金记录备注
     * @param object  $note_platform      平台用户的资金记录备注
     * @param int     $admin_id           后台操作人id
     * @param unknown $syncRemoteData     资金托管同步参数
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function payOut($type, $rebate_amount_type, $log_item, $user_to, $user_platform, $note_user, $note_platform, $admin_id, &$syncRemoteData)
    {
        $money = $log_item[$rebate_amount_type];
        if (bccomp($money, '0.00', 2) <= 0) {
            return true;
        }
        $log_info = array(__CLASS__, __FUNCTION__, $admin_id, $rebate_amount_type, $money, json_encode($log_item), $user_to['id']);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        // 返利支出
        $user_to->changeMoneyAsyn = true;
        $rs_change_money = $user_to->changeMoney($money, CouponLogService::$pay_msg_tpl[$type][$rebate_amount_type]['user_log_info'], $note_user, $admin_id);
        if (!$rs_change_money) {
            throw new \Exception("结算{$rebate_amount_type}异常!");
        }
        $user_platform->changeMoneyAsyn = true;
        $rs_change_money = $user_platform->changeMoney(-$money, '返利支出', $note_platform, $admin_id);
        if (!$rs_change_money) {
            throw new \Exception("结算{$rebate_amount_type}支出异常!");
        }

        $syncRemoteData[] = array(
            'outOrderId' => 'COUPONLOG|'.$log_item['id'],
            'payerId' => $user_platform['id'],
            'receiverId' => $user_to['id'],
            'repaymentAmount' => bcmul($money, 100), // 以分为单位
            'curType' => 'CNY',
            'bizType' => 3,
            'batchId' => '',
        );

        /* 短信通知
        if (!empty(CouponLogService::$pay_msg_tpl[$type][$rebate_amount_type]['sms_tpl']) && !empty(CouponLogService::$pay_msg_tpl[$type][$rebate_amount_type]['sms_title']) && app_conf("SMS_ON") == 1) {
            $msgcenter = new \Msgcenter();
            $msg_content = array('sms_amount' => format_price($money));
            $msgcenter->setMsg($user_to['mobile'], $user_to['id'], $msg_content, CouponLogService::$pay_msg_tpl[$type][$rebate_amount_type]['sms_tpl'], CouponLogService::$pay_msg_tpl[$type][$rebate_amount_type]['sms_title']);
            $msgcenter->save();
        }*/
        Logger::info(implode(' | ', array_merge($log_info, array('done'))));
    }

    /**
     * 获取标的信息.
     */
    protected function getDealData($deal_id)
    {
        $deal_id = intval($deal_id);
        if ($deal_id <= 0) {
            return array();
        }
        static $deal_info = array();
        if (!isset($deal_info[$this->module][$deal_id])) {
            if (self::MODULE_TYPE_P2P == $this->module) {
                $deal_data = DealModel::instance()->find($deal_id, 'name,deal_status', true);
            } elseif (self::MODULE_TYPE_P2P == $this->module) {
                $deal_data['name'] = 'duotou'; //todo
            }
            if ($deal_data) {
                $deal_info[$this->module][$deal_id] = $deal_data->getRow();
            } else {
                return array();
            }
        }

        return $deal_info[$this->module][$deal_id];
    }

    /**
     * 通知贷申请赎回处理.
     *
     * @param $deal_load_id
     * @param $deal_repay_time 赎回还款时间
     *
     * @return bool
     */
    public function redeem($deal_load_id, $deal_repay_time = 0)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $deal_load_id, $deal_repay_time, 'module :'.$this->module);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        CouponMonitorService::process(CouponMonitorService::ITEM_REDEEM, CouponMonitorService::TOTAL, $this->module);

        //初始化对象，不能用单例模式
        $coupon_log = $this->coupon_log_dao->findByDealLoadId($deal_load_id);

        try {
            if (CouponService::PAY_STATUS_PAID == $coupon_log->pay_status) {
                Logger::info(implode(' | ', array_merge($log_info, array(true, 'coupon rebate settled done'))));
                \libs\utils\Alarm::push('couponRedeem', 'redeem通知贷赎回异常（邀请码已结算）', json_encode($coupon_log->getRow()));

                return true;
            }

            //多投，基金会传过来返利天数，不需要计算
            if (CouponLogService::MODULE_TYPE_P2P == $this->module) {
                if (empty($coupon_log)) {
                    // 异步的话，有可能延迟
                    $coupon_service = new CouponService();
                    $ret = $coupon_service->consume($deal_load_id, '', 0, array(), CouponService::COUPON_SYNCHRONOUS);
                    if (false === $ret) {
                        throw new \Exception('couponlog补漏失败！');
                    }
                    $coupon_log = $this->coupon_log_dao->findByDealLoadId($deal_load_id);
                    Logger::info(implode(' | ', array_merge($log_info, array('redeem Remedy coupon done'))));
                }

                $compound_apply = CompoundRedemptionApplyModel::instance()->getApplyByDealLoanId($deal_load_id);
                $deal = DealModel::instance()->find($coupon_log['deal_id'], 'repay_start_time');
                if (empty($deal) || empty($compound_apply)) {
                    throw new \Exception('标或者通知贷赎回记录不存在！');
                }

                $log_info[] = json_encode($compound_apply->getRow());
                $coupon_log->deal_repay_time = $compound_apply['repay_time'];
                $repay_days = round(($compound_apply['repay_time'] - $deal['repay_start_time']) / 86400);
                // 更新返点金额，线上没有用返点金额，多投宝没有全期限也无法计算
                $coupon_log->rebate_amount = round($coupon_log->rebate_amount * $repay_days / $coupon_log->deal_repay_days, 2);
                $coupon_log->referer_rebate_amount = round($coupon_log->referer_rebate_amount * $repay_days / $coupon_log->deal_repay_days, 2);
                $coupon_log->agency_rebate_amount = round($coupon_log->agency_rebate_amount * $repay_days / $coupon_log->deal_repay_days, 2);

                /*
                // 更新返点比例金额
                $coupon_log->rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_log->rebate_ratio, $coupon_log, $repay_days);
                $coupon_log->referer_rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_log->referer_rebate_ratio, $coupon_log, $repay_days);
                $coupon_log->agency_rebate_ratio_amount = CouponLogService::calRatioAmount($coupon_log->agency_rebate_ratio, $coupon_log, $repay_days);
                */
                // 更新实际计息天数
                $coupon_log->deal_repay_days = $repay_days;
            } elseif (CouponLogService::MODULE_TYPE_DUOTOU == $this->module) {
                if (empty($coupon_log)) {
                    throw new \Exception('coupon_log记录不存在！');
                }
                if (empty($deal_repay_time)) {
                    throw new \Exception('deal_repay_time[赎回到账时间]不能为空！');
                }
                if ($coupon_log->rebate_days_update_time <= 0) {
                    throw new \Exception('rebate_days_update_time[返利天数更新时间]不能为空！');
                }
                $coupon_log->deal_repay_time = $deal_repay_time;
            } else {
                throw new \Exception('未知错误');
            }

            $log_info[] = json_encode($coupon_log->getRow());

            //todo 增加赎回中状态？
            $rs = $coupon_log->save();
            if (!$rs) {
                throw new \Exception('更新coupon_log失败！');
            }
        } catch (\Exception $e) {
            $log_info[] = $e->getMessage();
            Logger::info(implode(' | ', array_merge($log_info, array($rs, 'done'))));
            \libs\utils\Alarm::push('couponRedeem', 'redeem通知贷赎回失败', json_encode($log_info));
            CouponMonitorService::process(CouponMonitorService::ITEM_REDEEM, CouponMonitorService::FAILED, $this->module);

            return false;
        }
        CouponMonitorService::process(CouponMonitorService::ITEM_REDEEM, CouponMonitorService::SUCCESS, $this->module);
        Logger::info(implode(' | ', array_merge($log_info, array($rs, 'done'))));

        return $rs;
    }

    /**
     * 根据推荐用户id获取已经结算的消费记录.
     *
     * @param $refer_user_id 推荐用户id
     * @param $firstRow 起始行数
     * @param $pageSize 列表每页显示行数
     * @param string $short_alias 邀请码
     * @param bool   $is_get_list 是否获取列表
     *
     * @return array
     */
    public function getLogPaid(
        $type,
        $refer_user_id,
        $firstRow = false,
        $pageSize = false,
        $short_alias = '',
        $consume_real_name = '',
        $consume_user_mobile = '',
        $siteId = null,
        $inviteeId = null,
        $pay_status = false,
        $pay_time_start = false,
        $pay_time_end = false
        ){
        $userIds = array();
        if (!empty($consume_real_name)) {
            $userIds = CouponLogModel::getInstance($type)->getConsumeIdByRealName($refer_user_id, $consume_real_name);
            $userIds = empty($userIds) ? array(0) : $userIds;
        } elseif (!empty($consume_user_mobile)) {
            $userId = UserModel::instance()->getUserIdByMobile($consume_user_mobile);
            $userIds = empty($userId) ? array(0) : array($userId);
        }

        $userIds = !empty($inviteeId) ? array($inviteeId) : $userIds;

        if (!$type) { //兼容老接口
            $coupon_list = CouponLogModel::instance()->getLogPaid($type, $refer_user_id, $firstRow, $pageSize, $short_alias, $userIds);
            $coupon_list = $this->compatibleOldData($coupon_list);

            return $coupon_list;
        }

        $coupon_list = array('count' => 0, 'data' => array(
            'referer_rebate_result_amount' => 0.00,
            'referer_rebate_result_amount_no' => 0.00,
            'invest_data' => false, //根据被邀请人ID筛选返利记录  并统计被邀请人的累计返利和待返返利
            'list' => false, ));

        /*
         * 获取列表
         */
        switch ($type) {
            case self::MODULE_TYPE_REG:
                //$list = CouponLogModel::getInstance($type)->getList($refer_user_id, array(), '', $firstRow, $pageSize);
                $list = CouponBindModel::instance()->getListByInviteUserId($refer_user_id,$firstRow,$pageSize);
                break;
            default:
                $list = CouponLogModel::getInstance($type,$this->dataType)->getList($refer_user_id, $userIds, $short_alias, $firstRow, $pageSize, $siteId, $pay_status, $pay_time_start, $pay_time_end);
        }
        $coupon_list['data']['list'] = $list['list'];
        $coupon_list['count'] = $list['count'];

        if (self::MODULE_TYPE_REG != $type) { //通知贷已返
            /**
             * 获取已返待反.
             */
            $refererRebateAmount = CouponLogModel::getInstance($type,$this->dataType)->getRefererRebateAmount($refer_user_id, $userIds, $siteId);
            $coupon_list['data']['referer_rebate_result_amount'] = $refererRebateAmount['referer_rebate_amount'];
            $coupon_list['data']['referer_rebate_result_amount_no'] = $refererRebateAmount['referer_rebate_amount_no'];
            if(in_array($type, array(self::MODULE_TYPE_DUOTOU,self::MODULE_TYPE_P2P))){
                $refererRebateAmountCompound = CouponPayLogModel::getInstance($type,$this->dataType)->refererRebateAmountCompound($refer_user_id, $userIds, $siteId);
                $coupon_list['data']['referer_rebate_result_amount'] += $refererRebateAmountCompound;
            }
        }
           

        if (!empty($inviteeId) && 'p2p' === $type) {//根据被邀请人ID筛选返利记录  并统计被邀请人的累计返利和待返返利
            $coupon_list['data']['invest_data'] = $this->getInviteeRebateData($refer_user_id, $inviteeId);
        }

        if (!$coupon_list['data']['list']) {
            return $coupon_list;
        }
        $list = $coupon_list['data']['list'];
        $user_dao = new UserModel();
        foreach ($list as $k => $item) {
            $deal_info = null;
            if (isset($item['deal_id']) && 0 != $item['deal_id']) {

                $couponService = new CouponService($type);
                $deal_info = $couponService->getDealInfoByDealId($item['deal_id']);
                if(self::MODULE_TYPE_P2P == $type && empty($deal_info)){
                    $couponService = new CouponService('ncfph');
                    $deal_info = $couponService->getDealInfoByDealId($item['deal_id']);
                }

                if (self::MODULE_TYPE_DUOTOU == $type) {
                    $item['deal_type'] = CouponLogService::DEAL_TYPE_COMPOUND;
                    $deal_info['repay_start_time'] = $item['repay_start_time'];
                    $deal_info['name'] = $deal_info['project_name']; //多投标名称取项目名称
                }
                $list[$k]['deal_name'] = $deal_info['name'];
            }

            $consume_user = $user_dao->findViaSlave($item['consume_user_id'], 'id,user_name,real_name,byear,bmonth,bday,mobile,idcardpassed,user_type,create_time');
            $list[$k]['consume_user_name'] = $consume_user['user_name'];

            $consume_real_name = $consume_user['real_name'];
            //企业会员,显示企业名字
            if (UserModel::USER_TYPE_ENTERPRISE == $consume_user['user_type']) {
                $enterpriseInfo = EnterpriseModel::instance()->findByViaSlave('user_id=:user_id', 'company_name', array(':user_id' => $consume_user['id']));
                if ($enterpriseInfo) {
                    $consume_real_name = $enterpriseInfo->company_name;
                }
                // 企业账户直接隐藏掉手机号
                $list[$k]['mobile'] = '';
            } else {
                $list[$k]['mobile'] = format_mobile($consume_user['mobile']);
            }
            $list[$k]['consume_real_name'] = $consume_real_name;
            if (!empty($item['pay_time'])) {
                $list[$k]['pay_time'] = to_date($item['pay_time']);
            } else {
                $list[$k]['pay_time'] = '';
            }

            if ($deal_info) {
                $list[$k]['is_deal'] = 1;
                $list[$k]['repay_time'] = isset($deal_info['repay_time']) ? $deal_info['repay_time'] : '';
                $list[$k]['loantype_time'] = isset($deal_info['loantype']) && 5 == $deal_info['loantype'] ? '天' : '个月';
                $list[$k]['loantype'] = isset($deal_info['loantype']) ? $GLOBALS['dict']['LOAN_TYPE'][$deal_info['loantype']] : '';
                $list[$k]['repay_start_time'] = to_date($deal_info['repay_start_time'], 'Y-m-d');
                $list[$k]['deal_load_money'] = format_price($item['deal_load_money']);
                $list[$k]['deal_name'] = $deal_info['name'];
            }

            if (isset($item['deal_type']) && CouponLogService::DEAL_TYPE_COMPOUND == $item['deal_type']) {
                $pay_result = CouponPayLogModel::getInstance($type)->statByDealLoadId($item['deal_load_id'], $item['refer_user_id']);
                $list[$k]['count_pay'] = $pay_result['count_pay'];
                $list[$k]['sum_pay_refer_amount'] = $pay_result['sum_pay_refer_amount'];
            }

            // 处理文本显示
            $list[$k]['note'] = '';
            $list[$k]['log_info'] = '';
            $list[$k]['pay_status_text'] = '';
            switch ($type) {
                case self::MODULE_TYPE_P2P:
                    $dataText = $this->logPaidFormatP2p($list[$k]);
                    break;
                case self::MODULE_TYPE_DUOTOU:
                    $dataText = $this->logPaidFormatDuoTou($list[$k]);
                    break;
                case self::MODULE_TYPE_THIRD:
                    $list[$k]['client_name'] = $deal_info['client_name'];
                    $dataText = $this->logPaidFormatThird($list[$k]);
                    $item['create_time'] = $dataText['create_time']-28800;
                    break;
                case self::MODULE_TYPE_REG:
                    $dataText = $this->logPaidFormatReg($consume_user);
                    $list[$k]['pay_status_no'] = $dataText['pay_status_no'];
                    $list[$k]['pay_status'] = 0;
                    $item['create_time'] = $consume_user['create_time'];
                    break;
            }
            $list[$k]['rebate_status'] = (in_array($list[$k]['pay_status'], array(CouponService::PAY_STATUS_AUTO_PAID, CouponService::PAY_STATUS_PAID, CouponService::PAY_STATUS_PAYING))) ? 1 : 0;
            $list[$k]['create_time'] = to_date($item['create_time']);
            $list[$k]['pay_status_text'] = $dataText['pay_status_text'];
            $list[$k]['note'] = $dataText['note'];
            $list[$k]['log_info'] = isset($dataText['log_info'])?$dataText['log_info']:'';
            $list[$k]['platform_info'] = isset($dataText['platform_info'])?$dataText['platform_info']:'';
            if (!empty($dataText['pay_money'])) {
                $list[$k]['pay_money'] = $dataText['pay_money'];
            }
        }
        $coupon_list['data']['list'] = $list;

        return $coupon_list;
    }

    /**
     * 获取被邀请人的投资状态及累计返利记录.
     *
     * @param $refer_user_id,array $userIds
     *
     * @return array
     */
    public function getInviteeRebateData($refer_user_id, $inviteeId)
    {
        if (empty($refer_user_id) || empty($inviteeId)) {
            return false;
        }
        $refererRebateAmount = $this->getTotalRefererRebateAmount($refer_user_id, array($inviteeId));
        $investData['consume_rebate_result_amount'] = $refererRebateAmount['referer_rebate_amount'];
        $investData['consume_rebate_result_amount_no'] = $refererRebateAmount['referer_rebate_amount_no'];
        $invitee_dao = new UserModel();
        $invitee_user = $invitee_dao->findViaSlave($inviteeId, 'id,user_name,real_name,byear,bmonth,bday,mobile,idcardpassed,user_type,create_time');
        $investData['invitee_status'] = $this->logPaidFormatReg($invitee_user);

        return $investData;
    }

    /**
     * 格式注册的状态
     *
     * @param array $userInfo
     *
     * @return array
     */
    public function logPaidFormatReg($userInfo)
    {
        $ret = array(
            'pay_status_text' => '',
            'pay_status_no' => '',
            'note' => '',
            'log_info' => '',
        );
        if (empty($userInfo)) {
            return $ret;
        }
        if (!$userInfo['real_name'] || 1 != $userInfo['idcardpassed']) {
            $pay_status_text = '待身份认证及绑卡';
            $pay_status_no = self::STATUS_BIND_BANK_NO;
        } else {
            // 是否绑卡
            $user_bank_card_model = new UserBankcardModel();
            $user_bank_card_info = $user_bank_card_model->getCardByUser($userInfo['id'], 'id,status');
            if (empty($user_bank_card_info) || 0 == $user_bank_card_info['status']) {
                $pay_status_text = '待绑卡';
                $pay_status_no = self::STATUS_BIND_BANK_NO;
            } else {
                // 是否投资
                $pay_status_text = '待投资';
                $pay_status_no = self::STATUS_INVEST_NO;
                $where = "consume_user_id=':consume_user_id' and type=2";
                $isHave = CouponLogModel::getInstance(self::MODULE_TYPE_P2P)->countViaSlave($where, array(':consume_user_id' => $userInfo['id']));
                if ($isHave > 0) {
                    $pay_status_text = '已投资';
                    $pay_status_no = self::STATUS_INVEST;
                }else {
                    // 查询多投宝优惠码记录
                    $where = "consume_user_id=':consume_user_id' and type=2";
                    $isHave = CouponLogModel::getInstance(self::MODULE_TYPE_DUOTOU)->countViaSlave($where, array(':consume_user_id' => $userInfo['id']));
                    if ($isHave > 0) {
                        $pay_status_text = '已投资';
                        $pay_status_no = self::STATUS_INVEST;
                    }else{
                        //代码写真丑，但是为了快，那是这么写
                        $where = "consume_user_id=':consume_user_id' and type=2";
                        $isHave = CouponLogModel::getInstance(self::MODULE_TYPE_NCFPH)->countViaSlave($where, array(':consume_user_id' => $userInfo['id']));
                        if($isHave > 0){
                            $pay_status_text = '已投资';
                            $pay_status_no = self::STATUS_INVEST;
                        }
                    }
                }
            }
        }
        $ret['pay_status_text'] = $pay_status_text;
        $ret['pay_status_no'] = $pay_status_no;

        return $ret;
    }

    public function logPaidFormatP2p($item)
    {
        $ret = array();
        $pay_money = number_format($item['referer_rebate_amount_2part'], 2);
        if (in_array($item['pay_status'], array(CouponService::PAY_STATUS_AUTO_PAID, CouponService::PAY_STATUS_PAID))) {
            $pay_status_text = '已奖励';
            if (CouponLogService::DEAL_TYPE_COMPOUND == $item['deal_type']) {
                $ret['count_pay'] = $item['count_pay'];
                $pay_money = number_format($item['sum_pay_refer_amount'], 2);
                $note = '投资人已赎回 奖励完成';
            } else {
                $note = '奖励完成';
            }
        } elseif (CouponService::PAY_STATUS_PAYING == $item['pay_status']) {
            $pay_status_text = '已奖励';
            $ret['count_pay'] = $item['count_pay'];
            $pay_money = number_format($item['sum_pay_refer_amount'], 2);
            $note = '投资起息后每7天返利一次，直至赎回。';
        } elseif (CouponService::PAY_STATUS_OFFLINE == $item['pay_status']) {
            $pay_status_text = '线下返';
            $note = '奖励转入线下结算';
        } else {
            $couponDealModel = new CouponDealModel();
            $couponDealInfo = $couponDealModel->findByViaSlave("deal_id=':deal_id'", 'pay_type', array(':deal_id' => $item['deal_id']));
            $note = !empty($couponDealInfo['pay_type']) ? '还清后' : '投资后';
            $note .= '15个工作日获得奖励';
            $pay_status_text = '待奖励';
            $note = CouponLogService::DEAL_TYPE_COMPOUND == $item['deal_type'] ? '投资起息后每7天奖励一次，直至赎回。' : $note;
        }
        if (CouponLogService::DEAL_TYPE_COMPOUND == $item['deal_type']) {
            $log_info = "使用 {$item['short_alias']} 投资 “{$item['deal_name']}”。{$item['deal_load_money']}";
        } else {
            $log_info = "使用 {$item['short_alias']} 投资 “{$item['deal_name']}”。{$item['deal_load_money']} /{$item['repay_time']}{$item['loantype_time']}/{$item['loantype']}";
        }
        if ($item['repay_start_time']) {
            $log_info .= '/'.$item['repay_start_time'].'起息';
        }

        $ret['pay_status_text'] = $pay_status_text;
        $ret['note'] = $note;
        $ret['log_info'] = $log_info;
        $ret['pay_money'] = $pay_money;

        return $ret;
    }


    public function logPaidFormatThird($item)
    {
        $ret = array();
        $pay_money = number_format($item['referer_rebate_amount_2part'], 2);
        if (in_array($item['pay_status'], array(CouponService::PAY_STATUS_AUTO_PAID, CouponService::PAY_STATUS_PAID))) {
            $pay_status_text = '已奖励';
            $note = '奖励完成';
        } elseif (CouponService::PAY_STATUS_OFFLINE == $item['pay_status']) {
            $pay_status_text = '线下返';
            $note = '奖励转入线下结算';
        } else {
            $note = '投资后15个工作日获得奖励';
            $pay_status_text = '待奖励';
        }
        $log_info = "使用 {$item['short_alias']} 投资 “网贷-{$item['client_name']}”。{$item['deal_load_money']}/{$item['repay_time']}{$item['loantype_time']}/{$item['loantype']}";
        $platformInfo = PlatformService::getPlatformInfo();
        $dealLoadInfo = ThirdDealLoadService::getInfoById($item['deal_load_id']);
        $ret['platform_info'] = '网贷-'.$platformInfo[$item['client_id']];
        $ret['pay_status_text'] = $pay_status_text;
        $ret['note'] = $note;
        $ret['log_info'] = $log_info;
        $ret['pay_money'] = $pay_money;
        $ret['create_time'] = $dealLoadInfo['update_time'];

        return $ret;
    }

    public function logPaidFormatDuoTou($item)
    {
        $ret = array();
        $pay_money = number_format($item['referer_rebate_amount_2part'], 2);
        if (in_array($item['pay_status'], array(CouponService::PAY_STATUS_AUTO_PAID, CouponService::PAY_STATUS_PAID))) {
            $pay_status_text = '已奖励';
            $ret['count_pay'] = $item['count_pay'];
            $pay_money = number_format($item['sum_pay_refer_amount'], 2);
            $note = '用户已转让/退出 奖励完成';
        } elseif (CouponService::PAY_STATUS_PAYING == $item['pay_status']) {
            $pay_status_text = '已奖励';
            $ret['count_pay'] = $item['count_pay'];
            $pay_money = number_format($item['sum_pay_refer_amount'], 2);
            $note = '加入成功后每7天奖励一次，直至转让/退出。';
        } else {
            $couponDealModel = new CouponDealModel();
            $couponDealInfo = $couponDealModel->findByViaSlave("deal_id=':deal_id'", 'pay_type', array(':deal_id' => $item['deal_id']));
            $note = !empty($couponDealInfo['pay_type']) ? '还清后' : '投资后';
            $note .= '15个工作日获得奖励';
            $pay_status_text = '待奖励';
            $note = '加入成功后每7天奖励一次，直至转让/退出。';
        }

        $log_info = "使用 {$item['short_alias']} 加入 “{$item['deal_name']}”。{$item['deal_load_money']}";

        if ($item['repay_start_time']) {
            $log_info .= '/'.$item['repay_start_time'].'起算';
        }

        $ret['pay_status_text'] = $pay_status_text;
        $ret['note'] = $note;
        $ret['log_info'] = $log_info;
        $ret['pay_money'] = $pay_money;

        return $ret;
    }

    /**
     * 获取投资邀请人数.
     *
     * @param int $consumer_user_id
     */
    public function getInviteNumber($refer_user_id)
    {
        return $this->coupon_log_dao->getConsumeUserIdsByReferUserId($refer_user_id);
    }

    /**
     * 获取总的邀请人.
     *
     * @param int $user_id
     */
    public function getTotalInviteNumber($user_id)
    {
        if (empty($user_id)) {
            return false;
        }
        //$count_p2p = CouponLogModel::getInstance()->getConsumeUserIdsByReferUserId($user_id);
        //$count_duotou = CouponLogModel::getInstance(self::MODULE_TYPE_DUOTOU)->getConsumeUserIdsByReferUserId($user_id);
        //$count_reg = CouponLogModel::getInstance(self::MODULE_TYPE_REG)->getConsumeUserIdsByReferUserId($user_id);
        //$count_ncfph = CouponLogModel::getInstance(self::MODULE_TYPE_NCFPH)->getConsumeUserIdsByReferUserId($user_id);
        $count_bind = CouponBindModel::instance()->getCountByInviteUserId($user_id);
        //$total = $count_p2p + $count_duotou + $count_reg + $count_ncfph + $count_bind;

        return count($count_bind);
    }

    /**
     * 获取.
     *
     * @param $user_id
     * @param array $consume_user_ids
     */
    public function getRefererRebateAmount($user_id, $consume_user_ids = array())
    {
        if (empty($user_id)) {
            return false;
        }

        return $this->coupon_log_dao->getRefererRebateAmount($user_id, $consume_user_ids);
    }

    /**
     * 获得总的投资已返金额 包括 p2p 和多投宝.
     *
     * @param int $user_id
     *
     * @return array
     */
    public function getTotalRefererRebateAmount($user_id, $consume_user_ids = array(), $siteId = null)
    {
        if (empty($user_id)) {
            return false;
        }
        $ret = array('referer_rebate_amount' => 0, 'referer_rebate_amount_no' => 0);

        // p2p
        $referer_rebate_amountP2p = CouponLogModel::getInstance(self::MODULE_TYPE_P2P,$this->dataType)->getRefererRebateAmount($user_id, $consume_user_ids, $siteId);
        $refererRebateAmountCompoundP2p = CouponPayLogModel::getInstance(self::MODULE_TYPE_P2P,$this->dataType)->refererRebateAmountCompound($user_id, $consume_user_ids, $siteId); //通知贷已返
        $referer_rebate_amountP2p['referer_rebate_amount'] += $refererRebateAmountCompoundP2p;

        // duotou
        $referer_rebate_amountDuotou = CouponLogModel::getInstance(self::MODULE_TYPE_DUOTOU,$this->dataType)->getRefererRebateAmount($user_id, $consume_user_ids, $siteId);
        $refererRebateAmountCompoundDuotou = CouponPayLogModel::getInstance(self::MODULE_TYPE_DUOTOU,$this->dataType)->refererRebateAmountCompound($user_id, $consume_user_ids, $siteId); //通知贷已返
        $referer_rebate_amountDuotou['referer_rebate_amount'] += $refererRebateAmountCompoundDuotou;

        //ncfph
        $referer_rebate_amountNcfph = CouponLogModel::getInstance(self::MODULE_TYPE_NCFPH,$this->dataType)->getRefererRebateAmount($user_id, $consume_user_ids, $siteId);

        //third
        $referer_rebate_amountThird = CouponLogModel::getInstance(self::MODULE_TYPE_THIRD,$this->dataType)->getRefererRebateAmount($user_id, $consume_user_ids, $siteId);//第三方

        $ret['referer_rebate_amount'] = $referer_rebate_amountP2p['referer_rebate_amount'] + $referer_rebate_amountDuotou['referer_rebate_amount']+$referer_rebate_amountNcfph['referer_rebate_amount']+$referer_rebate_amountThird['referer_rebate_amount'];
        $ret['referer_rebate_amount_no'] = $referer_rebate_amountP2p['referer_rebate_amount_no'] + $referer_rebate_amountDuotou['referer_rebate_amount_no']+ $referer_rebate_amountNcfph['referer_rebate_amount_no']+$referer_rebate_amountThird['referer_rebate_amount_no'];

        return $ret;
    }

    /**
     * 每日更新邀请码记录的返利天数.
     *
     * @param $coupon_log_id
     *
     * @return bool
     */
    public function updateRebateDays($coupon_log_id)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $coupon_log_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $coupon_log = $this->coupon_log_dao->find($coupon_log_id);
        if (empty($coupon_log)) {
            Logger::info(implode(' | ', array_merge($log_info, array('error coupon_log_id'))));

            return false;
        }
        $log_info[] = json_encode($coupon_log->getRow());

        // 已结算
        if (CouponService::PAY_STATUS_AUTO_PAID == $coupon_log['pay_status'] || CouponService::PAY_STATUS_PAID == $coupon_log['pay_status']) {
            Logger::info(implode(' | ', array_merge($log_info, array('已经结算过，不更新', 'done'))));

            return false;
        }

        // 上次更新时间
        $rebate_days_update_time_old = $coupon_log['rebate_days_update_time'];
        // 第一次更新，上次更新时间为空，取起息日
        if (CouponLogService::MODULE_TYPE_P2P == $this->module && empty($rebate_days_update_time_old)) {
            $deal_model = new DealModel();
            $deal = $deal_model->find($coupon_log['deal_id'], 'repay_start_time,repay_time');
            $rebate_days_update_time_old = $deal['repay_start_time'];
            $log_info[] = '起息时间:'.to_date($deal['repay_start_time'], $format = 'Y-m-d H:i:s');
        } elseif (empty($rebate_days_update_time_old)) {
            Logger::info(implode(' | ', array_merge($log_info, array('error rebate_days_update_time'))));

            return false;
        }

        //本次更新时间
        $rebate_days_update_time_new = to_timespan(date('Y-m-d'));
        $is_deal_repay = 0;
        // 最后更新日为回款日 多投deal_repay_time为空，不赎回就没有回款日，不会进入if逻辑，永远结算
        if (!empty($coupon_log['deal_repay_time']) && $rebate_days_update_time_new > $coupon_log['deal_repay_time']) {
            $is_deal_repay = 1;
            $rebate_days_update_time_new = $coupon_log['deal_repay_time'];
        }

        $log_info[] = '上次更新时间:'.to_date($rebate_days_update_time_old, $format = 'Y-m-d H:i:s');
        $log_info[] = '本次更新时间:'.to_date($rebate_days_update_time_new, $format = 'Y-m-d H:i:s');
        $log_info[] = '回款时间:'.to_date($coupon_log['deal_repay_time'], $format = 'Y-m-d H:i:s');

        if ($rebate_days_update_time_new == $rebate_days_update_time_old) {
            Logger::info(implode(' | ', array_merge($log_info, array('当天已经更新过，不计返利天数', 'done'))));

            return true;
        }
        if ($rebate_days_update_time_new < $rebate_days_update_time_old) {
            Logger::info(implode(' | ', array_merge($log_info, array('错误：本次更新时间晚于上次更新时间', 'done'))));
            if (CouponLogService::MODULE_TYPE_DUOTOU == $this->module && 1 == $is_deal_repay) {
                return true;
            } else {
                return false;
            }
        }

        $rebate_days_add = round((($rebate_days_update_time_new - $rebate_days_update_time_old) / 86400));
        Logger::info(implode(' | ', array_merge($log_info, array('增加返利天数:'.$rebate_days_add))));

        $rs = $this->coupon_log_dao->updateRebateDays($coupon_log_id, $rebate_days_add, $rebate_days_update_time_new);
        Logger::info(implode(' | ', array_merge($log_info, array($rs, 'done'))));

        return $rs;
    }

    /**
     * 根据标ID更新叠加返利天数.
     *
     * @param $deal_id
     *
     * @return bool
     */
    public function updateRebateDaysForDeal($deal_id, $type = self::MODULE_TYPE_P2P)
    {
        if (self::MODULE_TYPE_DUOTOU == $type) {
            $this->coupon_log_dao = CouponLogModel::getInstance($type);
            $this->module = self::MODULE_TYPE_DUOTOU;
        }

        return $this->executeForDeal($deal_id, 'updateRebateDays');
    }

    /**
     * 根据标ID周期结算邀请码返利.
     *
     * @param $deal_id
     *
     * @return bool
     */
    public function payForDeal($deal_id, $type = self::MODULE_TYPE_P2P)
    {
        if (self::MODULE_TYPE_DUOTOU == $type) {
            $this->coupon_log_dao = CouponLogModel::getInstance($type);
            $this->coupon_pay_log_dao = CouponPayLogModel::getInstance($type);
            $this->module = self::MODULE_TYPE_DUOTOU;
        } elseif (self::MODULE_TYPE_GOLD == $type) {
            $this->coupon_log_dao = CouponLogModel::getInstance($type);
            $this->module = self::MODULE_TYPE_GOLD;
            $log_info = array(__CLASS__, __FUNCTION__, $this->module, $deal_id, 'pay');
            $couponService = new CouponService($this->module);
            $deal_info = $couponService->getDealInfoByDealId($deal_id);
            if (empty($deal_info)) {
                Logger::info(implode(' | ', array_merge($log_info, array('标信息不存在', 'done'))));
                return false;
            }
            if ($deal_info['deal_status'] < 4) {
                Logger::info(implode(' | ', array_merge($log_info, array('未满标放款,标状态不满足还清时结算'))));

                return true;
            }
        } elseif (self::MODULE_TYPE_GOLDC == $type) {
            $this->coupon_log_dao = CouponLogModel::getInstance($type);
            $this->module = self::MODULE_TYPE_GOLDC;
        } elseif (self::MODULE_TYPE_NCFPH == $type) {
            $this->coupon_log_dao = CouponLogModel::getInstance($type);
            $this->module = self::MODULE_TYPE_NCFPH;
        } elseif (self::MODULE_TYPE_THIRD == $type) {
            $this->coupon_log_dao = CouponLogModel::getInstance($type);
            $this->module = self::MODULE_TYPE_THIRD;
        }

        return $this->executeForDeal($deal_id, 'pay');
    }

    /**
     * 根据标ID处理邀请码返利.
     *
     * @param $deal_id 标ID
     * @param $function 处理方法
     *
     * @return bool
     */
    private function executeForDeal($deal_id, $function)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $deal_id, $function);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        if (!in_array($function, array('updateRebateDays', 'pay'))) {
            Logger::info(implode(' | ', array_merge($log_info, array('error params'))));

            return false;
        }
        if ('pay' == $function) {
            $turn_on_coupon_pay = app_conf('COUPON_PAY_DISABLE');
            if (1 == $turn_on_coupon_pay) {
                Logger::warn(implode(' | ', array_merge($log_info, array('system deny settlement'))));

                return true;
            }
        }
        $list = $this->coupon_log_dao->findByDealId($deal_id, array(CouponService::PAY_STATUS_NOT_PAY, CouponService::PAY_STATUS_FINANCE_AUDIT,
                                                                    CouponService::PAY_STATUS_PAYING, ), 'id,pay_status');
        Logger::info(implode(' | ', array_merge($log_info, array('select findByDealId end '))));
        if (empty($list)) {
            Logger::info(implode(' | ', array_merge($log_info, array('empty deal list'))));

            return true;
        }
        Logger::info(implode(' | ', array_merge($log_info, array('Lock start '))));
        // 悲观锁，以id为锁的键名
        $lockKey = __CLASS__.'-'.__FUNCTION__.'-'.$function.'-'.$this->module.'-'.$deal_id;
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 600)) {
            Logger::info(implode(' | ', array_merge($log_info, array('Lock fail '))));
            throw new \Exception('加锁失败!');
        }
        Logger::info(implode(' | ', array_merge($log_info, array('Locked end '))));
        $GLOBALS['db']->startTrans();
        try {
            foreach ($list as $item) {
                if (in_array($item['pay_status'], array(CouponService::PAY_STATUS_AUTO_PAID, CouponService::PAY_STATUS_PAID, CouponService::PAY_STATUS_OFFLINE))) {
                    Logger::info(implode(' | ', array_merge($log_info, array($item['id'], $item['pay_status'], '单条已结算，忽略'))));
                } else {
                    $rs = $this->$function($item['id']);
                    Logger::info(implode(' | ', array_merge($log_info, array($item['id'], $item['pay_status'], '单条处理完毕', $rs))));
                    if (empty($rs)) {
                        throw new \Exception("{$function}失败:".$item['id']);
                    }
                }
            }
            $rs = $GLOBALS['db']->commit();
            $lock->releaseLock($lockKey); // 解锁
            Logger::info(implode(' | ', array_merge($log_info, array("commit:{$rs}"))));
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey); // 解锁
            Logger::info(implode(' | ', array_merge($log_info, array('exception:'.$e->getMessage()))));

            return false;
        }

        //用户分析埋点,注意放在事务提交之后,否则GM执行顺序会有问题
        if (CouponLogService::MODULE_TYPE_P2P == $this->module) {
            $userProfileService = new UserProfileService();
            $userProfileService->payCouponProfile($deal_id);
        }

        Logger::info(implode(' | ', array_merge($log_info, array('done'))));

        return true;
    }

    /**
     * 更新沉睡用户的注册邀请码
     *
     * @param $user_id 注册用户ID
     * @param $short_alias_new 新的注册邀请码，为空时清空coupon_log对应记录
     *
     * @return bool|null
     */
    public function changeRegShortAlias($user_id, $short_alias_new)
    {
        $admin_id = 0;
        if (defined('ADMIN_ROOT')) {
            $adm_session = \es_session::get(md5(conf('AUTH_KEY')));
            $admin_id = !empty($adm_session) ? $adm_session['adm_id'] : 0;
        }
        $log_info = array(__CLASS__, __FUNCTION__, $admin_id, $user_id, $short_alias_new);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));

        $user_model = new UserModel();
        $user = $user_model->find($user_id, 'id');
        if (empty($user)) {
            Logger::info(implode(' | ', array_merge($log_info, array('error user'))));

            return false;
        }

        $short_alias_new = strtoupper(trim($short_alias_new));
        $refer_user_id = 0;
        $coupon_service = new CouponService();
        if (!empty($short_alias_new)) {
            $coupon = $coupon_service->checkCoupon($short_alias_new);
            if (empty($coupon)) {
                Logger::info(implode(' | ', array_merge($log_info, array('error coupon'))));

                return false;
            }
            $refer_user_id = $coupon['refer_user_id'];
        }
        $log_info[] = $refer_user_id;

        $GLOBALS['db']->startTrans();
        try {
            // 更新user表
            $user->invite_code = $short_alias_new;
            $user->refer_user_id = $refer_user_id;
            $user->is_rebate = '1';
            $rs = $user->save();
            if (empty($rs)) {
                throw new \Exception('update user error');
            }

            // 删除原coupon_log记录
            $coupon_log_list = $this->coupon_log_dao->findAllByDealLoadId(0, $user_id);
            if (!empty($coupon_log_list)) {
                foreach ($coupon_log_list as $coupon_log) {
                    $log_info[] = json_encode($coupon_log->getRow());
                    $rs = $coupon_log->remove();
                    if (empty($rs)) {
                        throw new \Exception('delete coupon log error');
                    }
                }
            }

            // 新增注册邀请码记录
            if (!empty($short_alias_new)) {
                $rs = $coupon_service->regCoupon($user_id, $short_alias_new, CouponLogService::ADD_TYPE_ADMIN);
                if (empty($rs)) {
                    throw new \Exception('insert coupon log error');
                }
            }

            $rs = $GLOBALS['db']->commit();
            Logger::info(implode(' | ', array_merge($log_info, array($rs))));

            return $rs;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(implode(' | ', array_merge($log_info, array('exception:'.$e->getMessage()))));

            return false;
        }
    }

    /**
     * 满标后处理 附加返利.
     *
     * @param $deal_id
     */
    public function handleCouponExtraForDeal($deal_id)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $deal_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        //11第一笔投资 12最后一笔投资 13最高金额投资
        $coupon_extra_type_list = array(CouponExtraModel::TYPE_FIRST, CouponExtraModel::TYPE_LAST, CouponExtraModel::TYPE_MAX_AMOUNT);
        $deal_load_model = new DealLoadModel();
        $coupon_extra_model = new CouponExtraModel();
        $GLOBALS['db']->startTrans();
        try {
            foreach ($coupon_extra_type_list as $coupon_extra_type) {
                $coupon_extra = $coupon_extra_model->getBySourceType($coupon_extra_type, $deal_id);
                if (!$coupon_extra) {
                    continue;
                }
                $coupon_extra = $coupon_extra[0];
                $log_info[] = json_encode($coupon_extra);
                $deal_load = array();
                switch ($coupon_extra_type) {
                    case 11:
                        $deal_load = $deal_load_model->getDealLoadFirst($deal_id);
                        break;
                    case 12:
                        $deal_load = $deal_load_model->getDealLoadLast($deal_id);
                        break;
                    case 13:
                        $deal_load = $deal_load_model->getDealLoadMoneyMost($deal_id);
                        break;
                }
                if (!$deal_load) {
                    throw new \Exception('获取首尾标记录失败！'.$coupon_extra_type);
                }
                $log_info[] = json_encode($deal_load->getRow());
                $rs = $this->addCouponExtraLog($coupon_extra, array(), $deal_load);
                if (!$rs) {
                    throw new \Exception('添加首尾标叠加记录失败！');
                }
                Logger::info(implode(' | ', $log_info));
            }
            $rs = $GLOBALS['db']->commit();
            Logger::info(implode(' | ', array_merge($log_info, array($rs))));
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(implode(' | ', array_merge($log_info, array('exception:'.$e->getMessage()))));

            return false;
        }

        return $rs;
    }

    /**
     * 根据类型获取列表 首尾标中奖页面用.
     *
     * @param int $type
     * @param int $offset
     * @param int $page_size
     */
    public function getExtraLogListByType($type, $offset, $page_size)
    {
        return CouponExtraLogModel::instance()->getListByType($type, $offset, $page_size);
    }

    /**
     * 根据标批量更新返点比例金额和返利天数.
     *
     * @param int $deal_id     标id
     * @param int $rebate_days
     *
     * @return bool
     */
    public function updateRebateDaysAndAmount($deal_id, $rebate_days)
    {
        if (empty($deal_id) || $rebate_days < 0) {
            return false;
        }
        $log_info = array(__CLASS__, __FUNCTION__, $deal_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        // 更新标的所有返利天数
        $update_rebates_day_res = $this->coupon_log_dao->updateRebateDaysAndAmount($deal_id, $rebate_days);
        if (false === $update_rebates_day_res) {
            return false;
        }
        Logger::info(implode(' | ', array_merge($log_info, array('end success'))));

        return true;
    }

    /**
     * 兼容老接口.
     *
     * @param array $coupon_list
     *
     * @return array
     */
    public function compatibleOldData($coupon_list)
    {
        if (!$coupon_list['data']['list']) {
            return $coupon_list;
        }
        $list = $coupon_list['data']['list'];
        $deal_dao = new DealModel();
        $user_dao = new UserModel();
        $coupon_pay_log_model = new CouponPayLogModel();
        $user_bank_card_model = new UserBankcardModel();
        foreach ($list as $k => $item) {
            $deal_info = null;
            if (0 != $item['deal_id']) {
                $deal_info = $deal_dao->findViaSlave($item['deal_id'], 'name,repay_time,loantype,repay_start_time');
                $list[$k]['deal_name'] = $deal_info['name'];
            }
            $consume_user = $user_dao->findViaSlave($item['consume_user_id'], 'id,user_name,real_name,byear,bmonth,bday,mobile,idcardpassed');
            $list[$k]['consume_user_name'] = $consume_user['user_name'];
            $list[$k]['consume_real_name'] = $consume_user['real_name'];
            $list[$k]['pay_time'] = to_date($item['pay_time']);
            if ($list[$k]['type'] == CouponService::TYPE_SIGNUP) {
                // 注册类型
                // 未实名认证
                if (1 != $consume_user['idcardpassed']) {
                    $list[$k]['pay_status'] = CouponService::PAY_STATUS_NO_IDPASSED;
                } elseif (1 == $consume_user['idcardpassed']) {
                    // 是否绑卡
                    $user_bank_card_info = $user_bank_card_model->getCardByUser($item['consume_user_id'], 'id, status');
                    if (empty($user_bank_card_info) || 0 == $user_bank_card_info['status']) {
                        $list[$k]['pay_status'] = CouponService::PAY_STATUS_IDPASSED;
                    } else {
                        $list[$k]['pay_status'] = CouponService::PAY_STATUS_AUTO_PAID;
                    }
                }
            } else {
                // 投资类型或其他
                $list[$k]['pay_status'] = $item['pay_status'];
            }
            $list[$k]['rebate_status'] = (in_array($list[$k]['pay_status'], array(CouponService::PAY_STATUS_AUTO_PAID, CouponService::PAY_STATUS_PAID, CouponService::PAY_STATUS_PAYING))) ? 1 : 0;
            $list[$k]['mobile'] = $consume_user['mobile'];

            if ($deal_info) {
                $list[$k]['is_deal'] = 1;
                $list[$k]['repay_time'] = $deal_info['repay_time'];
                $list[$k]['loantype_time'] = 5 == $deal_info['loantype'] ? '天' : '个月';
                $list[$k]['loantype'] = $GLOBALS['dict']['LOAN_TYPE'][$deal_info->loantype];
                $list[$k]['repay_start_time'] = to_date($deal_info->repay_start_time, 'Y-m-d');
                $list[$k]['deal_load_money'] = format_price($item['deal_load_money']);
                $list[$k]['deal_name'] = $deal_info->name;
            }

            if (CouponLogService::DEAL_TYPE_COMPOUND == $item['deal_type']) {
                $pay_result = $coupon_pay_log_model->statByDealLoadId($item['deal_load_id'], $item['refer_user_id']);
                $list[$k]['count_pay'] = $pay_result['count_pay'];
                $list[$k]['sum_pay_refer_amount'] = $pay_result['sum_pay_refer_amount'];
            }
        }
        $coupon_list['data']['list'] = $list;

        return $coupon_list;
    }

    /**
     * 获取用户返利记录.
     *
     * @param array $params
     *
     * @return array
     */
    public function getListByParams($params)
    {
        return $this->coupon_log_dao->getListByParams($params);
    }

    /**
     * 获取用户返利记录条数.
     *
     * @param array $params
     */
    public function getCountByParams($params)
    {
        return $this->coupon_log_dao->getCountByParams($params);
    }

    /**
     * 获取按季等额、按月等额的返利系数.
     *
     * @param $deal_info
     *
     * @return float
     */
    public function getRebateFactor($deal_info)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $deal_info['id'], $deal_info['deal_type'], $deal_info['loantype']);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $factor = CouponService::$factor_default;
        if (empty($deal_info) || CouponLogService::DEAL_TYPE_COMPOUND == $deal_info['deal_type']) {
            return $factor;
        }

        switch ($deal_info['loantype']) {
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON']: // 返利年化折算系数_按季等额还款
                $factor_conf = floatval(app_conf('COUPON_RABATE_RATIO_FACTOR_ANJI'));
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']: // 返利年化折算系数_按月等额还款
                $factor_conf = floatval(app_conf('COUPON_RABATE_RATIO_FACTOR_ANYUE'));
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']: // 返利年化折算系数_等额本息固定日还款
                $factor_conf = floatval(app_conf('COUPON_RABATE_RATIO_FACTOR_XFFQ'));
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH']: // 返利年化折算系数_按月等额本金还款
                $factor_conf = floatval(app_conf('COUPON_RABATE_RATIO_FACTOR_ANYUEBJ'));
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH']: // 返利年化折算系数_按季等额本金还款
                $factor_conf = floatval(app_conf('COUPON_RABATE_RATIO_FACTOR_ANJIBJ'));
                break;
            default:
                $factor_conf = false;
        }
        $factor = empty($factor_conf) ? $factor : $factor_conf;
        Logger::info(implode(' | ', array_merge($log_info, array($factor))));

        return $factor;
    }

    /**
     * 更新通知贷返点比例金额.
     *
     * @param int $deal_id
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function updateCompoundRebateRatioAmount($deal_id, $type)
    {
        $this->coupon_log_dao = CouponLogModel::getInstance($type);
        $this->coupon_pay_log_dao = CouponPayLogModel::getInstance($type);

        $list = $this->coupon_log_dao->findByDealId($deal_id, array(CouponService::PAY_STATUS_NOT_PAY, CouponService::PAY_STATUS_FINANCE_AUDIT,
                CouponService::PAY_STATUS_PAYING, ), 'id,deal_load_id,pay_status');
        $log_info = array(__CLASS__, __FUNCTION__, 'deal_id' => $deal_id);
        try {
            if (!empty($list)) {
                foreach ($list as $val) {
                    $log_info['id'] = $val['id'];
                    $log_info['deal_load_id'] = $val['deal_load_id'];
                    if (empty($val['deal_load_id'])) {
                        throw new \Exception('deal_load_id为空!');
                    }

                    //通知贷结算累加
                    $statInfo = $this->coupon_pay_log_dao->statAllByDealLoadId($val['deal_load_id']);
                    $data['rebate_ratio_amount'] = $statInfo->rebate_ratio_amount;
                    $data['referer_rebate_ratio_amount'] = $statInfo->referer_rebate_ratio_amount;
                    $data['agency_rebate_ratio_amount'] = $statInfo->agency_rebate_ratio_amount;
                    if (CouponLogService::MODULE_TYPE_DUOTOU == $this->module) {
                        $data['deal_repay_days'] = $statInfo['rebate_days'];
                    }

                    $log_info['data'] = json_encode($data);
                    $rs = $this->coupon_log_dao->updateBy($data, ' id ='.$val['id']);

                    if (empty($rs)) {
                        throw new \Exception('更新通知贷邀请码记录失败');
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::info(implode(' | ', array_merge($log_info, array('exception:'.$e->getMessage()))));

            return false;
        }

        return true;
    }

    /**
     * 获取返利支出账户.
     *
     * @return int
     */
    public function get_user_platform($coupon_log)
    {
        $user_id = 0;
        if (self::MODULE_TYPE_DUOTOU == $this->module) {
            $user_id = app_conf('COUPON_PAYER_DUOTOU_ID');
        } elseif (self::MODULE_TYPE_GOLD == $this->module) {
            $user_id = app_conf('COUPON_PAYER_ID_GOLD'); // 线上都一样，且无返利现金转账
        } elseif (self::MODULE_TYPE_GOLDC == $this->module) {
            $user_id = app_conf('COUPON_PAYER_ID_GOLDC'); // 线上都一样，且无返利现金转账理
        } elseif ((empty($this->module) || self::MODULE_TYPE_P2P == $this->module) && !empty($coupon_log)) {
            if (CouponLogService::DEAL_TYPE_GENERAL == $coupon_log['deal_type']) {
                $user_id = app_conf('COUPON_PAYER_ID_GENERAL');
            } elseif (CouponLogService::DEAL_TYPE_EXCHANGE == $coupon_log['deal_type']) {
                $user_id = app_conf('COUPON_PAYER_ID_EXCHANGE');
            } elseif (CouponLogService::DEAL_TYPE_EXCLUSIVE == $coupon_log['deal_type']) {
                $user_id = app_conf('COUPON_PAYER_ID_EXCLUSIVE');
            }
        } else {
            $user_id = app_conf('COUPON_PAYER_ID');
        }
        if (empty($user_id)) {
            $user_id = app_conf('COUPON_PAYER_ID');
        }

        return $user_id;
    }

    /**
     * 获取返利红包券组ID配置Key值
     *
     * @return int
     */
    public function getCouponGroupId($coupon_log = false)
    {
        //用控制显示返利红包文案是邀请奖励还是服务奖励，S是服务奖励
        $dealLoadId = !empty($coupon_log)? $coupon_log->getSplitDealLoadId($this->module) : 0;       
        $tail = ($dealLoadId != 0 && $coupon_log->deal_load_id > $dealLoadId) ? '_S':'';
        $couponGroupId = app_conf(strtoupper('COUPON_GROUP_ID_REFERER_REBATE_'.$this->module.$tail));

        return $couponGroupId;
    }

    /**
     * 获取邀请码模块类型列表.
     */
    public static function getModelTypes()
    {
        $defaultModels = array(
                self::MODULE_TYPE_REG => self::MODULE_TYPE_REG_NAME,
                self::MODULE_TYPE_P2P => self::MODULE_TYPE_P2P_NAME,
        );

        $extendModels = array();

        $extendModelsConfig = app_conf('COUPON_EXTEND_MODELS');
        //只对多投开关配置的用户显示多投tab
        if (!empty($extendModelsConfig)) {
            $extendModelsConfigArray = explode(',', $extendModelsConfig);
            foreach ($extendModelsConfigArray as $model) {
                if (isset(self::$module_name_map[$model])) {
                    //对于多投tab，增加用户组限制
                    if (self::MODULE_TYPE_DUOTOU == $model && !is_duotou_inner_user()) {
                        continue;
                    }

                    $extendModels[$model] = self::$module_name_map[$model];
                }
            }
        }
        return array_merge($defaultModels, $extendModels);
    }

    /**
     * coupon_log 流标数据备份到bak表并删除原表数据.
     *
     * @param int $coupon_log_id
     *
     * @return bool
     */
    public function backupForDetele($coupon_log_id)
    {
        if (!is_numeric($coupon_log_id)) {
            return false;
        }
        $log_info = array(__CLASS__, __FUNCTION__, $coupon_log_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $old_id = $coupon_log_id;
        $coupon_log_obj = new CouponLogModel();
        $get_coupon_log_info = $coupon_log_obj->find($old_id);
        if (empty($get_coupon_log_info)) {
            Logger::info(implode(' | ', array_merge($log_info, array('old coupon log info empty'))));

            return false;
        }

        $coupon_log_bak_obj = new CouponLogBakModel();
        // 开始事务
        $GLOBALS['db']->startTrans();
        try {/*{{{*/
            $coupon_log_bak_result = $coupon_log_bak_obj->backupForDetele($old_id);
            if (false === $coupon_log_bak_result) {
                Logger::info(implode(' | ', array_merge($log_info, array('old import bak fail'))));

                return false;
            }
            $coupon_log_del = $get_coupon_log_info->remove();
            if (false === $coupon_log_del) {
                Logger::info(implode(' | ', array_merge($log_info, array('del old fail'))));

                return false;
            }
            $GLOBALS['db']->commit();

            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(implode(' | ', array_merge($log_info, array('Exception', $e->getMessage()))));

            return false;
        }/*}}}*/

        return false;
    }

    /**
     * 绑定客户数.
     *
     * @param int $refer_user_id
     *
     * @return int
     */
    public function getCountUser($refer_user_id)
    {
        $log_info = array(__CLASS__, __FUNCTION__, $refer_user_id);
        try {
            $isBigUser = CouponBindModel::instance()->isBigUser($refer_user_id);

            return $isBigUser;
        } catch (\Exception $e) {
            Logger::info(implode(' | ', array_merge($log_info, array('Exception', $e->getMessage()))));

            return false;
        }
    }

    /**
     * 开放平台用户获取CPS返利数据.
     *
     * @return array
     */
    public function getP2PLogPaid($refer_user_id, $options = array())
    {
        //获取统计
        if ('stat' == $options['getResType']) {
            return array('count' => 0, 'data' => array_merge(array('list' => false), $this->getP2PLogPaidStat($refer_user_id, $options)));
        }

        //获取详情
        if ('list' == $options['getResType']) {
            return $this->getP2PLogPaidList($refer_user_id, $options);
        }

        $list = $this->getP2PLogPaidList($refer_user_id, $options);
        $list['data'] = array_merge($list['data'], $this->getP2PLogPaidStat($refer_user_id, $options));

        return $list;
    }

    //开放平台获取返利明细
    public function getP2PLogPaidList($refer_user_id, $options)
    {
        $coupon_list = array('count' => 0, 'data' => array('list' => false));
        if (!empty($options['consumeUserId']) || !empty($options['mobile'])) {
            $userService = new UserService();
            $userInfo = $userService->getUserByUidOrMobile($options['consumeUserId'], $options['mobile']);
            if (empty($userInfo)) {
                return $coupon_list;
            }
            $options['consumeUserId'] = $userInfo['id'];
        }

        $list = CouponLogModel::getInstance()->getRefererRebateList($refer_user_id, $options);
        $coupon_list['data']['list'] = $list['list'];
        $coupon_list['count'] = $list['count'];

        if (!$coupon_list['data']['list']) {
            return $coupon_list;
        }

        $list = $coupon_list['data']['list'];
        $user_dao = new UserModel();
        foreach ($list as $k => $item) {
            $deal_info = null;
            if (0 != $item['deal_id']) {
                $couponService = new CouponService();
                $deal_info = $couponService->getDealInfoByDealId($item['deal_id']);
                $list[$k]['deal_name'] = $deal_info['name'];
            }

            $consume_user = $user_dao->findViaSlave($item['consume_user_id'], 'id,user_name,real_name,byear,bmonth,bday,mobile,idcardpassed,user_type,create_time');
            $list[$k]['consume_user_name'] = $consume_user['user_name'];

            //企业会员,显示企业名字
            if (UserModel::USER_TYPE_ENTERPRISE == $consume_user['user_type']) {
                $enterpriseInfo = EnterpriseModel::instance()->findByViaSlave('user_id=:user_id', 'company_name', array(':user_id' => $consume_user['id']));
                if ($enterpriseInfo) {
                    $consume_real_name = $enterpriseInfo->company_name;
                }
                // 企业账户直接隐藏掉手机号
                $list[$k]['mobile'] = '';
            } else {
                $list[$k]['mobile'] = format_mobile($consume_user['mobile']);
                $list[$k]['mobileFull'] = $consume_user['mobile'];
            }

            $consume_real_name = $consume_user['real_name'];
            $list[$k]['consume_real_name'] = $consume_real_name;
            if (!empty($item['pay_time'])) {
                $list[$k]['pay_time'] = to_date($item['pay_time']);
            } else {
                $list[$k]['pay_time'] = '';
            }

            if ($deal_info) {
                $list[$k]['is_deal'] = 1;
                $list[$k]['repay_time'] = $deal_info['repay_time'];
                $list[$k]['loantype_time'] = 5 == $deal_info['loantype'] ? '天' : '个月';
                $list[$k]['loantype'] = $GLOBALS['dict']['LOAN_TYPE'][$deal_info['loantype']];
                $list[$k]['repay_start_time'] = to_date($deal_info['repay_start_time'], 'Y-m-d');
                $list[$k]['deal_load_money'] = format_price($item['deal_load_money']);
                $list[$k]['deal_name'] = $deal_info['name'];
            }

            // 处理文本显示
            $list[$k]['note'] = '';
            $list[$k]['log_info'] = '';
            $list[$k]['pay_status_text'] = '';

            $dataText = $this->logPaidFormatP2p($list[$k]);
            $list[$k]['rebate_status'] = (in_array($list[$k]['pay_status'], array(CouponService::PAY_STATUS_AUTO_PAID, CouponService::PAY_STATUS_PAID, CouponService::PAY_STATUS_PAYING))) ? 1 : 0;
            $list[$k]['create_time'] = to_date($item['create_time']);
            $list[$k]['pay_status_text'] = $dataText['pay_status_text'];
            $list[$k]['note'] = $dataText['note'];
            $list[$k]['log_info'] = $dataText['log_info'];

            if (!empty($dataText['pay_money'])) {
                $list[$k]['pay_money'] = $dataText['pay_money'];
            }
        }

        $coupon_list['data']['list'] = $list;

        return $coupon_list;
    }

    //开放平台获取返利统计
    public function getP2PLogPaidStat($refer_user_id, $options = array())
    {
        $return_stat = array('referer_rebate_result_amount' => 0.00, 'referer_rebate_result_amount_no' => 0.00, 'referer_rebate_result_amount_offline' => 0.00);

        $refererRebateAmount = CouponLogModel::getInstance()->getRefererRebateAmount($refer_user_id);
        $return_stat['referer_rebate_result_amount'] = $refererRebateAmount['referer_rebate_amount'];
        $return_stat['referer_rebate_result_amount_no'] = $refererRebateAmount['referer_rebate_amount_no'];

        $refererRebateAmountCompound = CouponPayLogModel::getInstance()->refererRebateAmountCompound($refer_user_id);
        $return_stat['referer_rebate_result_amount'] += $refererRebateAmountCompound;

        $refererRebateAmountOffline = CouponLogModel::getInstance()->getRefererRebateAmountOffline($refer_user_id);
        $return_stat['referer_rebate_result_amount_offline'] = $refererRebateAmountOffline['referer_rebate_amount_offline'];

        return $return_stat;
    }

    /**
     * 投资人投资邀请人是否有返利.
     *
     * @param intval $userId
     *
     * @return bool
     */
    public function hasRebate($userId, $referUserId)
    {
        $userId = intval($userId);
        $referUserId = intval($referUserId);
        $log_info = array('user_id' => $userId);

        $couponService = new CouponService();
        $rebateInfo = $couponService->getRebateInfo($referUserId);
        //政策限制天数为0，表示用户不受限制
        if (true !== $rebateInfo && 0 != $rebateInfo['rebate_effect_days']) {
            $userModel = new UserModel();
            $userInfo = $userModel->find($userId, 'create_time');
            if (get_gmtime() > ($userInfo['create_time'] + $rebateInfo['rebate_effect_days'] * 86400)) {
                $log_info['create_time'] = $userInfo['create_time'];
                $log_info += $rebateInfo;
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, json_encode($log_info), 'msg:邀请人受政策组限制不能获得返利')));

                return false;
            }
        }

        return true;
    }

    public function getUserCountCache($refer_user_id)
    {
        return \SiteApp::init()->dataCache->call(new CouponLogService(), 'getCountUser', array($refer_user_id), self::EXPIRE_TIME);
    }

    /**
     * 获取首页展示的多投宝标的.
     *
     * @return array
     */
    public function getDtRebateFactor($projectId)
    {
        $request = new RequestCommon();
        $request->setVars(array('project_id' => $projectId));
        $log_info = array(__CLASS__, __FUNCTION__, $this->module, $projectId);
        try {
            $rpc = new \libs\utils\Rpc('duotouRpc');
            $response = $rpc->go('\NCFGroup\Duotou\Services\Project', 'getProjectInfoById', $request);
        } catch (\Exception $e) {
            Logger::info(implode(' | ', array_merge($log_info, array('exception:'.$e->getMessage()))));

            return false;
        }
        if (!$response) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, 'fail duotou rpc 调用失败')));

            return false;
        }
        $referer_factor_duotou = !empty($response['data']['rebateFactor']) ? $response['data']['rebateFactor'] : 1; //多投项目乘以返利系数
        return $referer_factor_duotou;
    }

    /**
     * 获取重复的coupon数据
     */
    public function getDuplicateCouponLog($id=0){
        return $this->coupon_log_dao->getDuplicateCouponLog($id);
    }

    /**
     * 删除数据
     */
    public function deleteByCondition($condition){
        return $this->coupon_log_dao->deleteByCondition($condition);
    }

    public function findAllByCondition($condition){
        return $this->coupon_log_dao->findAllViaSlave($condition,true);
    }

    public function getMaxId(){
        $result = $this->coupon_log_dao->findByViaSlave("1" ," max(id) id");
        return !empty($result)? $result->id:0;
    }

        /**
     * 获得总的投资已返金额 包括 p2p 和多投宝.
     *
     * @param int $user_id
     *
     * @return array
     */
    public function getTotalCountByReferUserId($user_id)
    {
        if (empty($user_id)) {
            return false;
        }

        $countP2p = CouponLogModel::getInstance(self::MODULE_TYPE_P2P,$this->dataType)->getCountByReferUserId($user_id);

        $countDuotou= CouponLogModel::getInstance(self::MODULE_TYPE_DUOTOU,$this->dataType)->getCountByReferUserId($user_id);

        $countNcfph = CouponLogModel::getInstance(self::MODULE_TYPE_NCFPH,$this->dataType)->getCountByReferUserId($user_id);

        $countThird = CouponLogModel::getInstance(self::MODULE_TYPE_THIRD,$this->dataType)->getCountByReferUserId($user_id);

        $totalCount = $countP2p + $countDuotou + $countNcfph + $countThird;

        return $totalCount;
    }

    public function getNotInCouponLogLoadIds($loadIds = array())
    {
        $dealLoadIds = array();
        $result = $this->coupon_log_dao->getNotInCouponLogLoadIds($loadIds);
        if (!empty($result)) {
            foreach ($result as $value) {
                $dealLoadIds[] = $value['deal_load_id'];
            }
        }
        return array_diff($loadIds, $dealLoadIds);
    }


}
