<?php
/**
 * PaymentNotice class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\DealOrderModel;

/**
 * PaymentNotice class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class PaymentNoticeModel extends BaseModel {

    /**
     * 充值来源-WEB
     * @var int
     */
    const PLATFORM_WEB = 1;

    /**
     * 充值来源-Android
     * @var int
     */
    const PLATFORM_ANDROID = 2;

    /**
     * 充值来源-IOS
     * @var int
     */
    const PLATFORM_IOS = 3;

    /**
     * 充值来源-移动Web
     * @var int
     */
    const PLATFORM_MOBILEWEB = 4;

    /**
     * 充值来源-后台
     * @var int
     */
    const PLATFORM_ADMIN = 5;

    /**
     * 充值来源-pos
     * @var int
     */
    const PLATFORM_POS = 6;

    /**
     * 充值来源-线下充值
     * @var int
     */
    const PLATFORM_OFFLINE = 7;

    /**
     * 充值来源-H5
     * @var int
     */
    const PLATFORM_H5 = 8;

    /**
     * 充值来源-工资宝
     * @var int
     */
    const PLATFORM_SALARY = 9;

    /**
     * 充值来源-开放平台退款
     * @var int
     */
    const PLATFORM_REFUND= 10;

    /**
     * 充值来源-易宝支付
     * @var int
     */
    const PLATFORM_YEEPAY = 11;

    /**
     * 充值来源 - 绑卡认证费用
     * @var int
     */
    const PLATFORM_AUTHCARD = 12;

    //来自第三方平台web
    const PLATFORM_WEB_THIRD = 13;

    // 充值来源 - 来自基金赎回
    const PLATFORM_FUND_REDEEM = 14;

    // 业务来源 - 来自理财师客户端
    const PLATFORM_LCS = 15;

    // 业务来源 - 存管
    const PLATFORM_SUPERVISION = 16;

    // 业务来源 - 存管自动扣款充值代扣
    const PLATFORM_SUPERVISION_AUTORECHARGE = 17;

    // 业务来源 - 大额充值
    const PLATFORM_OFFLINE_V2 = 18;

    // 业务来源 - 新协议支付
    const PLATFORM_H5_NEW_CHARGE = 19;

    // 业务来源 - 企业用户充值H5
    const PLATFORM_ENTERPRISE_H5CHARGE = 20;

    // 业务来源 - App充值限额后改用PC网银充值
    const PLATFORM_APPTOPC_CHARGE = 21;

    /**
     * 充值状态-未支付
     * @var int
     */
    const IS_PAID_NO = 0;

    /**
     * 充值状态-支付成功
     * @var int
     */
    const IS_PAID_SUCCESS = 1;

    /**
     * 充值状态-待支付
     * @var int
     */
    const IS_PAID_ING = 2;

    /**
     * 充值状态-支付失败
     * @var int
     */
    const IS_PAID_FAIL = 3;

    const AMOUNT_LIMIT_NULL = 0; // 未区分
    const AMOUNT_LIMIT_SMALL = 1; // 小额
    const AMOUNT_LIMIT_BIG = 2; // 大额

    const PAYMENT_YEEPAY = 3; // 易宝支付
    const PAYMENT_UCFPAY = 4; // 先锋支付

    //线上充值来源
    public static $onlinePlatform = [
        self::PLATFORM_WEB,
        self::PLATFORM_ANDROID,
        self::PLATFORM_IOS,
        self::PLATFORM_MOBILEWEB,
        self::PLATFORM_H5,
        self::PLATFORM_SUPERVISION,
        self::PLATFORM_H5_NEW_CHARGE,
    ];
    //大额充值来源
    public static $offlinePlatform = [
        self::PLATFORM_OFFLINE_V2,
        self::PLATFORM_APPTOPC_CHARGE,
    ];
    //移动端充值来源
    public static $mobilePlatform = [
        self::PLATFORM_ANDROID,
        self::PLATFORM_IOS,
        self::PLATFORM_MOBILEWEB,
        self::PLATFORM_H5,
        self::PLATFORM_H5_NEW_CHARGE,
        ];
    //wap端充值来源
    public static $wapPlatform = [
        self::PLATFORM_H5,
        self::PLATFORM_H5_NEW_CHARGE,
        self::PLATFORM_MOBILEWEB,
        ];
    //pc端充值来源
    public static $pcPlatform = [
        self::PLATFORM_WEB,
        self::PLATFORM_OFFLINE,
        self::PLATFORM_OFFLINE_V2,
    ];
    //APP端充值来源
    public static $appPlatform = [
        self::PLATFORM_ANDROID,
        self::PLATFORM_IOS,
        self::PLATFORM_APPTOPC_CHARGE,
    ];

    const TRIGGER_CHARGE_ONLINE = 19; // 快捷充值（线上）
    const TRIGGER_CHARGE_OFFLINE = 20; // 大额充值（线下）

    // 支付通道-网贷账户
    const CHARGE_TYPE_NCFPH = 'BCL';
    // 支付通道-先锋支付
    const CHARGE_TYPE_UCFPAY = 'XFZF';
    // 支付通道-易宝支付
    const CHARGE_TYPE_YEEPAY = 'YEEPAY';
    // 支付通道映射配置
    public static $chargeTypeConfig = [
        self::CHARGE_QUICK_CHANNEL => self::CHARGE_TYPE_UCFPAY,
        self::CHARGE_YEEPAY_CHANNEL => self::CHARGE_TYPE_YEEPAY,
        self::CHARGE_NCFPH_CHANNEL => self::CHARGE_TYPE_NCFPH,
    ];

    // 支付通道-网贷限额
    const CHARGE_NCFPH_CHANNEL = 'UCF_PAY';
    // 支付通道-先锋支付
    const CHARGE_QUICK_CHANNEL = 'XFZF_PAY';
    // 支付通道-易宝支付
    const CHARGE_YEEPAY_CHANNEL = 'YEEPAY_PAY';
    // 支付通道映射配置
    public static $chargeChannelConfig = [
        self::CHARGE_QUICK_CHANNEL => '先锋支付',
        self::CHARGE_YEEPAY_CHANNEL => '易宝支付',
        self::CHARGE_NCFPH_CHANNEL => '网贷限额',
    ];

    /**
     * 充值来源
     * @var array
     */
    const RESOURCE_FASTPAY = 1;
    const RESOURCE_YEEPAY = 2;
    const RESOURCE_OFFLINEPAY = 3;
    const RESOURCE_OPEN = 4;
    const RESOURCE_PC = 5;
    const RESOURCE_ORDER = 6;
    public static $chargeResourceShowConfig = [
        self::RESOURCE_FASTPAY => '快捷-充值',
        self::RESOURCE_YEEPAY => '易宝-充值',
        self::RESOURCE_OFFLINEPAY => '线下-充值',
        self::RESOURCE_OPEN => '开通模式-充值',
        self::RESOURCE_PC => 'PC-充值',
        self::RESOURCE_ORDER => '下单模式-充值',
    ];
    public static $chargeResourceGroupConfig = [
        self::RESOURCE_FASTPAY => [self::PLATFORM_ANDROID, self::PLATFORM_IOS, self::PLATFORM_MOBILEWEB, self::PLATFORM_H5, self::PLATFORM_H5_NEW_CHARGE],
        self::RESOURCE_YEEPAY => [
            self::PLATFORM_IOS,
            self::PLATFORM_WEB,
            self::PLATFORM_ANDROID,
            self::PLATFORM_MOBILEWEB,
            self::PLATFORM_ADMIN,
            self::PLATFORM_POS,
            self::PLATFORM_OFFLINE,
            self::PLATFORM_H5,
            self::PLATFORM_SALARY,
            self::PLATFORM_REFUND,
            self::PLATFORM_YEEPAY,
            self::PLATFORM_AUTHCARD,
            self::PLATFORM_WEB_THIRD,
            self::PLATFORM_FUND_REDEEM,
            self::PLATFORM_LCS,
            self::PLATFORM_SUPERVISION,
            self::PLATFORM_SUPERVISION_AUTORECHARGE,
            self::PLATFORM_OFFLINE_V2,
            self::PLATFORM_H5_NEW_CHARGE,
            self::PLATFORM_ENTERPRISE_H5CHARGE,
            self::PLATFORM_APPTOPC_CHARGE,
        ],
        self::RESOURCE_OFFLINEPAY => [self::PLATFORM_OFFLINE],
        self::RESOURCE_OPEN => [self::PLATFORM_OFFLINE_V2],
        self::RESOURCE_PC => [self::PLATFORM_WEB],
        self::RESOURCE_ORDER => [self::PLATFORM_APPTOPC_CHARGE],
    ];
    public static $chargeResourceNameConfig = [
        self::PAYMENT_YEEPAY => [
            self::PLATFORM_WEB => '易宝-充值',
            self::PLATFORM_IOS => '易宝-充值',
            self::PLATFORM_ANDROID => '易宝-充值',
            self::PLATFORM_MOBILEWEB => '易宝-充值',
            self::PLATFORM_ADMIN => '易宝-充值',
            self::PLATFORM_POS => '易宝-充值',
            self::PLATFORM_OFFLINE => '易宝-充值',
            self::PLATFORM_H5 => '易宝-充值',
            self::PLATFORM_SALARY => '易宝-充值',
            self::PLATFORM_REFUND => '易宝-充值',
            self::PLATFORM_YEEPAY => '易宝-充值',
            self::PLATFORM_AUTHCARD => '易宝-充值',
            self::PLATFORM_WEB_THIRD => '易宝-充值',
            self::PLATFORM_FUND_REDEEM => '易宝-充值',
            self::PLATFORM_LCS => '易宝-充值',
            self::PLATFORM_SUPERVISION => '易宝-充值',
            self::PLATFORM_SUPERVISION_AUTORECHARGE => '易宝-充值',
            self::PLATFORM_OFFLINE_V2 => '易宝-充值',
            self::PLATFORM_H5_NEW_CHARGE => '易宝-充值',
            self::PLATFORM_ENTERPRISE_H5CHARGE => '易宝-充值',
            self::PLATFORM_APPTOPC_CHARGE => '易宝-充值',
        ],
        self::PAYMENT_UCFPAY => [
            self::PLATFORM_ANDROID => '快捷-充值',
            self::PLATFORM_IOS => '快捷-充值',
            self::PLATFORM_MOBILEWEB => '快捷-充值',
            self::PLATFORM_H5 => '快捷-充值',
            self::PLATFORM_H5_NEW_CHARGE => '快捷-充值',
            self::PLATFORM_OFFLINE => '线下-充值',
            self::PLATFORM_OFFLINE_V2 => '开通模式-充值',
            self::PLATFORM_WEB => 'PC-充值',
            self::PLATFORM_APPTOPC_CHARGE => '下单模式-充值',
        ],
    ];

    //支付通道映射字段
    public static $chargeChannelMap = [
        self::CHARGE_QUICK_CHANNEL => self::PAYMENT_UCFPAY,
        self::CHARGE_YEEPAY_CHANNEL => self::PAYMENT_YEEPAY,
    ];

    public function getListByOrder($order_id) {
        $condition = "order_id=:order_id";
        return $this->findBy($condition, '*', array(":order_id" => $order_id));
    }

    /**
     * getInfoByNoticeSn 
     * 根据notice_sn获取数据记录
     * 
     * @param mixed $notice_sn 
     * @access public
     * @return void
     */
    public function getInfoByNoticeSn($notice_sn) {
        $condition = "notice_sn=':notice_sn'";
        $notice = $this->findBy($condition, '*', array(':notice_sn' => $notice_sn));
        return $notice;
    }

    /**
     * 根据用户UID、订单号获取充值订单数据
     * @param string $notice_sn
     * @return \libs\db\model
     */
    public function getInfoByUserIdNoticeSn($userId, $noticeSn) {
        $condition = "notice_sn=':notice_sn' AND user_id=:user_id";
        $notice = $this->findByViaSlave($condition, '*', array(':notice_sn' => $noticeSn, ':user_id' => $userId));
        return $notice;
    }

    /**
     * 根据id、user_id获取充值订单数据
     * @param int $id
     * @param int $userId
     * @return \libs\db\model
     */
    public function getInfoByIdUserId($id, $userId) {
        $condition = "id=':id' AND user_id=:user_id";
        return $this->findBy($condition, '*', array(':id' => $id, ':user_id' => $userId));
    }

    /**
     * 根据订单ID获取充值订单数据
     * @param int $id
     * @return \libs\db\model
     */
    public function getInfoById($id) {
        return $this->findByViaSlave('id=:id', '*', array(":id" => $id));
    }

    /**
     * 计算用户当天网信充值成功总金额 单位元
     * @param integer $userId
     * @return floatval
     */
    public function sumUserChargeAmountToday($userId)
    {
        $now = time();
        $todayTimeBegins = $now - $now % 86400 - 28800;
        $todayTimeEnds = $todayTimeBegins + 86400;
        $sql = "SELECT sum(money) FROM firstp2p_payment_notice WHERE user_id = '{$userId}' AND pay_time >= '{$todayTimeBegins}' AND pay_time < '{$todayTimeEnds}' AND is_paid = 1";
        return $this->db->getOne($sql);
    }

    /**
     * 计算用户当天网信线上充值成功总金额 单位元
     * @param integer $userId
     * @return floatval
     */
    public function sumUserOnlineChargeAmountToday($userId, $chargeChannel = '')
    {
        $todayTimeBegins = strtotime(date('Y-m-d')) - 28800;
        $todayTimeEnds = $todayTimeBegins + 86400;
        $sql = "SELECT sum(money) FROM firstp2p_payment_notice WHERE user_id = '{$userId}' AND pay_time >= '{$todayTimeBegins}' AND pay_time < '{$todayTimeEnds}' AND is_paid = 1";
        $sql .= sprintf(' AND platform IN (%s) ', implode(',', self::$onlinePlatform));

        //区分支付平台
        if ($chargeChannel == self::CHARGE_QUICK_CHANNEL) {
            $sql .= sprintf(' AND payment_id = %d', self::PAYMENT_UCFPAY);
        } else if ($chargeChannel == self::CHARGE_YEEPAY_CHANNEL) {
            $sql .= sprintf(' AND payment_id = %d', self::PAYMENT_YEEPAY);
        }
        return $this->db->getOne($sql);
    }

    /**
     * 计算用户当天充值成功总金额 单位元
     * 按渠道分组
     * @param integer $userId
     * @return floatval
     */
    public function groupUserChargeAmountToday($userId, $platforms = []) {
        $todayTimeBegins = strtotime(date('Y-m-d')) - 28800;
        $todayTimeEnds = $todayTimeBegins + 86400;
        $sql = "SELECT payment_id, sum(money) as money FROM firstp2p_payment_notice WHERE user_id = '{$userId}' AND pay_time >= '{$todayTimeBegins}' AND pay_time < '{$todayTimeEnds}' AND is_paid = 1";
        if (!empty($platforms)) {
            $sql .= sprintf(' AND platform IN (%s)', implode(',', $platforms));
        }
        $sql .= ' GROUP BY payment_id ';
        $group = $this->db->getAll($sql);
        $result = [];
        $total = 0;
        foreach ($group as $val) {
            $total = bcadd($val['money'], $total, 2);
            $result[$val['payment_id']] = $val['money'];
        }
        $result['total'] = $total;
        return $result;
    }

    public function getRecentList($user_id,$offset=0,$count=100) {
        $offset = empty($offset)?0:intval($offset);
        $count = empty($count)?100:intval($count);
        $time = strtotime('-7 days');
        $condition = "user_id=:user_id AND create_time >= :time AND is_paid IN (3, 2, 1, 0) order by create_time desc LIMIT :offset,:count";
        $list = $this->findAllViaSlave($condition,true, '*', array(':user_id' => $user_id, ':time' => $time,':offset'=>$offset,':count'=>$count));
        if (is_array($list)) {
            foreach ($list as $k => $item) {
                    $status_cn = '未付款';
                    if ($item['is_paid'] == 2) {
                        $status_cn = $item['amount_limit'] == self::AMOUNT_LIMIT_BIG ? '银行处理中' : '付款中';
                    }
                    else if ($item['is_paid'] == 1) {
                        $status_cn = '付款成功';
                    }else if ($item['is_paid'] == 3){
                        $status_cn = '付款失败';
                    }
                    $list[$k]['status_cn'] = $status_cn;
                    // deal order id
//                     $deal_order = DealOrderModel::instance()->find($item['order_id'], '*', true);
//                     if (!empty($deal_order)) {
//                          $list[$k]['notice_sn'] = $deal_order->order_sn;
//                     }
            }
        }
        return $list;
    }

    /**
     * updateOuterNoticeNo 
     * 更新outer_notice_sn
     * 
     * @param mixed $id 
     * @param mixed $outer_notice_sn 
     * @access public
     * @return void
     */
    public function updateOuterNoticeNo($id, $outer_notice_sn) {
        $notice = $this->find($id);
        if ($notice) {
            $notice->outer_notice_sn = $outer_notice_sn;
            $notice->save();
        }
        return true;
    }

    /**
     * 获取平台累计充值金额
     */
    public function getPlatformPayment($time = 0){
        $cond = "";
        if(intval($time) > 0){
            $cond = " AND pay_time > ".intval($time);
        }
        $sql = "SELECT sum(money) as total FROM firstp2p_payment_notice WHERE is_paid=1".$cond." ;";
        $payment = $this->findBySqlViaSlave($sql);
        if(isset($payment['total']) && ($payment['total'] > 0)){
            return floatval($payment['total']);
        }

        return 0;
    }

    /**
     * 查询用户24小时内是否有【充值成功的】记录
     * @param int $userId 用户ID
     * @param int $createTime 充值时间
     * @return \libs\db\model
     */
    public function hasExistByUserId($userId, $createTime = 0) {
        $condition = (int)$createTime > 0 ? sprintf('AND create_time >=%d', $createTime) : '';
        // 排除线下充值、小额转账认证、基金赎回等充值来源
        $notInPlatform = join(',', [self::PLATFORM_OFFLINE, self::PLATFORM_AUTHCARD, self::PLATFORM_FUND_REDEEM, self::PLATFORM_REFUND]);
        $data = $this->findByViaSlave(sprintf('user_id=\'%d\' %s AND is_paid=%d AND platform NOT IN (%s) LIMIT 1', (int)$userId, $condition, self::IS_PAID_SUCCESS, $notInPlatform), 'id');
        return !empty($data['id']) ? true : false;
    }
}
