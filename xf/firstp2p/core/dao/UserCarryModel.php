<?php
/**
 * UserCarryModel class file.
 *
 * @author caolong@ucfgroup.com
 **/

namespace core\dao;

/**
 * 用户提现
 *
 * @author wenyanlei@ucfgroup.com
 **/
class UserCarryModel extends BaseModel
{
    // 放款状态
    const STATUS_ACCOUNTANT_PASS = 3; // 会计通过

    const WITHDRAW_STATUS_CREATE = 0; // 未处理
    const WITHDRAW_STATUS_SUCCESS = 1; // 提现成功
    const WITHDRAW_STATUS_FAILED = 2; //提现失败
    const WITHDRAW_STATUS_PROCESS = 3; // 处理中
    const WITHDRAW_STATUS_PAY_PROCESS = 4; // 支付已处理

    static $withdrawDesc = array(
        self::WITHDRAW_STATUS_CREATE => '未处理',
        self::WITHDRAW_STATUS_PROCESS => '处理中',
        self::WITHDRAW_STATUS_SUCCESS => '提现成功',
        self::WITHDRAW_STATUS_FAILED => '提现失败',
        self::WITHDRAW_STATUS_PAY_PROCESS => '银行处理中'
    );

    const ROLLTYPE_BORROWER = 1;
    const ROLLTYPE_LOANER = 2;

    static $rollDesc = array(
        self::ROLLTYPE_BORROWER => '借款人',
        self::ROLLTYPE_LOANER => '投标人',
    );

    const LOAN_TYPE_DIRECT_LOAN = 0; // 直接放款
    const LOAN_TYPE_LATER_LOAN  = 1; // 先计息后放款
    const LOAN_AFTER_CHARGE  = 2; // 收费后放款
    const LOAN_AFTER_CHARGE_LATER_LOAN  = 3; // 收费后先计息后放款

    static $loantypeDescCn = array(
        self::LOAN_TYPE_DIRECT_LOAN => '直接放款',
    );

    static $loantypeDesc = array(
        self::LOAN_TYPE_DIRECT_LOAN => '直接放款',
        self::LOAN_TYPE_LATER_LOAN  => '先计息后放款',
        self::LOAN_AFTER_CHARGE  => '收费后放款',
        self::LOAN_AFTER_CHARGE_LATER_LOAN  => '收费后先计息后放款',
    );

    // todo 这个小额提现警告信息已经废弃 下个版本可以移除
    // 小额提现警告信息和对应值
    const WARNING_SAME_CARRY = 2;
    const WARNING_TWO_CARRY = 4;
    const WARNING_NO_CHARGE = 8;
    const WARNING_NAME_INCONSISTENT = 16;
    const WARNING_MONEY_OVER_LIMIT = 32;
    const WARNING_NO_DEAL = 64;

    static $warningMap = array (
        self::WARNING_SAME_CARRY  => '上次提现与本次提现金额一致',
        self::WARNING_TWO_CARRY  => '过去24小时内出现两次提现',
        self::WARNING_NO_CHARGE  => '交易记录中，无充值，直接提现',
        self::WARNING_NAME_INCONSISTENT => '提现人姓名，银行卡户名与身份证信息不一致',
        self::WARNING_MONEY_OVER_LIMIT => '金额大于%d',
        self::WARNING_NO_DEAL => '第一次充值后无投资，直接提现',
    );

    /**
     * 提现是否被风控延迟处理-正常
     * @var int
     */
    const WITHDRAW_IS_NORMAL = 0;
    /**
     * 提现是否被风控延迟处理-延迟
     * @var int
     */
    const WITHDRAW_IS_DELAY = 1;

    /**
     * 提现延迟配置
     * @var array
     */
    public static $withdrawDelayConfig = array(
        'payTime' => 86400, // 24小时内有充值记录
        'withdrawDelayTime' => 86400, // 符合风控规则，24小时后再发起提现请求
        'withdrawMoney' => 500, // 单笔提现金额大于等于500元
    );

    /**
    * 获取用户近7天的数据
    */
    public function getListByUid($userId,$offset=0,$count=30){
        $offset = empty($offset)?0:intval($offset);
        $count = empty($count)?100:intval($count);
        $time = strtotime('-7 days');
        $condition = "user_id=:user_id AND create_time >= :time  order by create_time desc LIMIT :offset,:count";
        $list = $this->findAllViaSlave($condition,true, 'id,money,create_time,withdraw_status', array(':user_id' => $userId, ':time' => $time,':offset'=>$offset,':count'=>$count));
        return $list;
    }

    /**
     * 根据标的id获取提现记录
     * @param int $deal_id
     * @return object
     */
    public function getByDealId($deal_id) {
        $deal_id = intval($deal_id);
        if (!$deal_id) {
            return false;
        }
        $condition = "`type`='1' AND `deal_id`=':deal_id' AND `status`='1'";
        $param = array(":deal_id" => $deal_id);
        return $this->findByViaSlave($condition, '*', $param);
    }


    /**
     *  根据标获取提现状态
     * @param int $deal_id
     * @return object
     */
     public function getByDealIdStatus($deal_id){
         $deal_id = intval($deal_id);
         if (empty($deal_id)){
             return false;
         }
         $condition = "`type`='1' AND `deal_id`=':deal_id' ORDER BY id DESC LIMIT 1";
         $param = array(":deal_id" => $deal_id);
         return $this->findByViaSlave($condition, '*', $param);
    }

    /**
     * 获取平台累计提现金额
     */
    public function getPlatformCarry($time = 0){
        $cond = "";
        if(intval($time) > 0){
            $cond = " AND withdraw_time > ':withdraw_time'";
        }

        $sql = "SELECT sum(money) as total FROM firstp2p_user_carry WHERE  status = ':status' AND withdraw_status = ':withdraw_status'".$cond.";";
        $params = array(':status' => self::STATUS_ACCOUNTANT_PASS, ':withdraw_status' => self::WITHDRAW_STATUS_SUCCESS, ':withdraw_time'=>intval($time));
        $carry = $this->findBySqlViaSlave($sql,$params);

        if(isset($carry['total']) && ($carry['total'] > 0)){
            return floatval($carry['total']);
        }

        return 0;
    }

    /**
     * 添加提现单
     * @param array $params
     * @return boolean
     */
    public function addCarryOrder($params) {
        $data = array(
            'user_id'       => intval($params['user_id']),
            'money'         => floatval($params['money']),
            'fee'           => isset($params['fee']) ? floatval($params['fee']) : 0,
            'type'          => intval($params['type']),
            'status'        => intval($params['status']),
            'deal_id'       => isset($params['deal_id']) ? intval($params['deal_id']) : 0,
            'platform'      => intval($params['platform']),
            'bank_id'       => isset($params['bank_id']) ? intval($params['bank_id']) : 0,
            'bankcard'      => isset($params['bankcard']) ? addslashes($params['bankcard']) : '',
            'bankzone'      => isset($params['bankzone']) ? addslashes($params['bankzone']) : '',
            'real_name'     => isset($params['real_name']) ? addslashes($params['real_name']) : '',
            'region_lv1'    => isset($params['region_lv1']) ? intval($params['region_lv1']) : 0,
            'region_lv2'    => isset($params['region_lv2']) ? intval($params['region_lv2']) : 0,
            'region_lv3'    => isset($params['region_lv3']) ? intval($params['region_lv3']) : 0,
            'region_lv4'    => isset($params['region_lv4']) ? intval($params['region_lv4']) : 0,
            'create_time'   => get_gmtime(),
            'update_time'   => 0,
            'out_order_id' => isset($params['out_order_id']) ? trim($params['out_order_id']) : '',
        );
        $this->setRow($data);

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    /**
     * 更新提现单
     * @param int $id
     * @param int $userId
     * @param int $withdrawStatus
     * @param string $withdrawMsg
     */
    public function updateUserCarry($id, $userId, $withdrawStatus, $withdrawMsg) {
        $condition = sprintf("`id` = '%d' and `user_id` = '%d' and `withdraw_status` not in (%s)", intval($id), intval($userId), self::WITHDRAW_STATUS_SUCCESS . ',' . self::WITHDRAW_STATUS_FAILED);
        $params = array(
            'withdraw_status'  => intval($withdrawStatus),
            'withdraw_msg'  => addslashes($withdrawMsg),
            'withdraw_time'   => get_gmtime(),
            'update_time'   => get_gmtime(),
        );
        $this->updateBy($params, $condition);
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 根据条件获取提现记录
     * @param int $userId
     * @param int $withdrawStatus
     * @param int $startTime
     * @param int $endTime
     */
    public function getListByRange($userId, $withdrawStatus, $startTime, $endTime) {
        $condition = "user_id=:user_id AND create_time >= :start_time AND create_time <= :end_time AND withdraw_status = :withdraw_status";
        $list = $this->findAll($condition,true, 'id,money,create_time,withdraw_status', array(':user_id' => $userId, ':start_time' => $startTime, ':end_time'=>$endTime, ':withdraw_status'=>$withdrawStatus));
        return $list;
    }

} // END class UserBankcardModel extends BaseModel
