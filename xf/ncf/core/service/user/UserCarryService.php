<?php
/**
 *  UserCarryService
 * @author caolong <caolong@ucfgroup.com>
 **/
namespace core\service\user;

//use core\dao\UserCarryModel;
use core\dao\supervision\SupervisionWithdrawModel;
use core\dao\account\WithdrawLimitModel;
use core\dao\FinanceQueueModel;
use core\dao\deal\DealModel;
use core\dao\deal\DealLoanTypeModel;

use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\utils\Alarm;

use core\service\BaseService;
use core\service\user\UserService;
use core\service\supervisoin\SupervisionAccountService;
use core\service\account\AccountLimitService;
use core\service\account\AccountService;

use core\enum\UserAccountEnum;
use core\enum\SupervisionEnum;

/**
 * UserFeedback service
 *
 * @packaged default
 * @author 温彦磊 <wenyanlei@ucfgroup.com>
 **/
class UserCarryService extends BaseService
{

    const WITHDRAW_LIMIT_INIT = 0;
    const WITHDRAW_LIMIT_INVIEWING = 1;
    const WITHDRAW_LIMIT_PASSED = 2;
    const WITHDRAW_LIMIT_REFUSED = 3;

    const WITHDRAW_LIMIT_CANCEL_NONE = 0;
    const WITHDRAW_LIMIT_CANCEL_INIT = 1;
    const WITHDRAW_LIMIT_CANCEL_PASSED = 2;
    const WITHDRAW_LIMIT_CANCEL_REFUSED = 3;

    const WITHDRAW_LIMIT_TYPE_T1 = 0;
    const WITHDRAW_LIMIT_TYPE_T2 = 1;
    const WITHDRAW_LIMIT_TYPE_T3 = 2;
    const WITHDRAW_LIMIT_TYPE_T4 = 3;

    const WITHDRAW_LIMIT_STATUS_T1 = 1;
    const WITHDRAW_LIMIT_STATUS_T2 = 2;
    const WITHDRAW_LIMIT_STATUS_T3 = 3;
    const WITHDRAW_LIMIT_STATUS_CANCEL = 4;
    const WITHDRAW_LIMIT_STATUS_FINISH = 5;

    /**
     * 提现限制状态描述
     */
    static $withdrawLimitCn = array(
        self::WITHDRAW_LIMIT_INIT => '提交申请',
        self::WITHDRAW_LIMIT_INVIEWING => '等待审核',
        self::WITHDRAW_LIMIT_PASSED => '通过申请',
        self::WITHDRAW_LIMIT_REFUSED => '拒绝申请',
    );

    /**
     * 限制提现类型
     */
    static $withdrawLimitTypeCn = array(
        self::WITHDRAW_LIMIT_TYPE_T1 => '变现通',
        self::WITHDRAW_LIMIT_TYPE_T2 => '贷后管理',
        self::WITHDRAW_LIMIT_TYPE_T3 => '法律合规',
        self::WITHDRAW_LIMIT_TYPE_T4 => '其他'
    );


    /**
     * 还款状态
     */
    static $withdrawLimitStatusCn = array(
        self::WITHDRAW_LIMIT_STATUS_T1 => '未还款',
        self::WITHDRAW_LIMIT_STATUS_T2 => '还款中',
        self::WITHDRAW_LIMIT_STATUS_T3 => '已还清',
        self::WITHDRAW_LIMIT_STATUS_CANCEL => '已取消',
        self::WITHDRAW_LIMIT_STATUS_FINISH => '已提清',

    );

    /**
     * 限制提现检查
     */
    static public $checkWithdrawLimit = true;

    public function addWithdrawLimitRecord($record) {
        return $this->addWithdrawLimit($record['userId'], $record['username'], $record['amount'], $record['limit_type'], $record['memo'], $record['platform'], $record['account_type'], $record['remain_money']);
    }

    /**
     * 保存提现申请限制记录
     */
    public function addWithdrawLimit($uid, $uname, $amount, $type, $memo = '', $platform = '', $account_type = '', $remain_amount = '') {
        //读取操作人员名称
        $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $_toInsert = array(
            'user_id' => $uid,
            'user_name' => $uname,
            'amount' => $amount,
            'memo' => $memo,
            'create_time' => get_gmtime(),
            'state' => self::WITHDRAW_LIMIT_INIT,
            'adm_name' => $adm_name,
            'adm_id' => $adm_session['adm_id'],
            'type' => $type,
            'platform' => $platform,
            'account_type' => $account_type,
            'remain_money' => $remain_amount,
        );
        try {
            $GLOBALS['db']->autoExecute(DB_PREFIX.'withdraw_limit', $_toInsert, 'INSERT');
        }
        catch(\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate') !== false) {
                $msg = '该用户提交的记录已经存在,请直接到审核列表审核';
                if ($_toInsert['platform'] == UserAccountEnum::PLATFORM_SUPERVISION && $_toInsert['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
                {
                    $msg = '提交失败,用户仍有可提现额度,不能重复申请,请到“可提现额度列表”页中查看';
                }
                throw new \Exception($msg);
            }
        }
        $affRows = $GLOBALS['db']->affected_rows();
        if ($affRows == 1) {
            return true;
        }
        return false;
    }

    public function editLimit($id, $amount, $type) {
        //读取操作人员名称
        $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $_toUpdate= array(
            'modify_amount' => $amount,
            'state' => 0,
            'adm_name' => $adm_name,
            'adm_id' => $adm_session['adm_id'],
            'type' => $type,
        );
        try {
            $GLOBALS['db']->autoExecute(DB_PREFIX.'withdraw_limit', $_toUpdate, 'UPDATE', " id = '{$id} '");
        }
        catch(\Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate') !== false) {
                throw new \Exception('该用户提交的记录已经存在，请直接到审核列表审核');
            }
        }
        $affRows = $GLOBALS['db']->affected_rows();
        if ($affRows == 1) {
            return true;
        }
        return false;
    }

    /**
     * 根据id读取提现限制记录
     */
    public function findLimitById($id, $adm_id = 0) {
        $sql = "SELECT * FROM firstp2p_withdraw_limit WHERE id ='{$id}'";
        if (!empty($adm_id)) {
            $sql .= " AND adm_id != '{$adm_id}'";
        }
        $data = $GLOBALS['db']->getRow($sql);
        return $data;
    }

    /**
     * 根据id读取提现限制记录
     */
    public function findLimitByUserId($user_id, $adm_id = 0) {
        $sql = "SELECT * FROM firstp2p_withdraw_limit WHERE user_id ='{$user_id}'";
        if (!empty($adm_id)) {
            $sql .= " AND adm_id != '{$adm_id}'";
        }
        $data = $GLOBALS['db']->getRow($sql);
        return $data;
    }


    /**
     * 用户是否在受限列表
     * @param $user_id integer
     * @return boolean
     */
    public function isInLimit($user_id) {
        $sql = "SELECT COUNT(*) FROM firstp2p_withdraw_limit WHERE user_id = '{$user_id}' AND state = ".self::WITHDRAW_LIMIT_PASSED;
        $userCount = $GLOBALS['db']->get_slave()->getOne($sql);
        return $userCount == 1;
    }

    /**
     * 判断用户是否存在限制提现
     * @param mix $user 用户id 或者 用户信息
     * @param string $withdrawAmount 出金金额,单位元
     * @param boolean $isSupervision  是否是存管账户
     * @param boolean $useBonus 是否使用红包
     *
     * @return boolean
     */
    public function canWithdrawAmount($user, $withdrawAmount, $isSupervision = false, $useBonus = true, $bonusInfo = [])
    {
        $userId = is_array($user) ? $user['id'] : $user;
        //绕过限制提现检查
        if (self::$checkWithdrawLimit === false) {
            return true;
        }

        $accountList = AccountService::getAccountListByUserId($userId);
        if (empty($accountList)) {
            return true;
        }

        // 需要转换用户输入金额单位
        $withdrawAmount = $withdrawAmount * 100;
        $platform = $accountType = '';

        if ($isSupervision && is_array($accountList))
        {
            $platform = UserAccountEnum::PLATFORM_SUPERVISION;
            $accountType = $accountList[0]['account_type'];
        }

        return (new AccountLimitService())->canWithdrawAmount($user, $withdrawAmount, $platform, $accountType, $useBonus, $bonusInfo);
    }


    /**
     * 用户是否在受限列表
     * @param $user_id integer
     * @return boolean
     */
    public function _canWithdrawAmount($user_id, $withdrawAmount, $caculateSupervisionMoney = false) {
        $sql = "SELECT amount FROM firstp2p_withdraw_limit WHERE user_id = '{$user_id}' AND state = ".self::WITHDRAW_LIMIT_PASSED;
        $amount = $GLOBALS['db']->get_slave()->getOne($sql);

        if(empty($amount) || bccomp($amount, '0.00', 2) <= 0)//限制金额小于0或者没有限制记录，直接返回true
        {
            return true;
        }
        // 计算用户总资产
        $accountMoneyInfo = AccountService::getAccountMoneyById($user_id);
        $userTotalMoney = $accountMoneyInfo['money'];
        if ($caculateSupervisionMoney) {
            $svService = new SupervisionAccountService();
            if ($svService->isSupervisionUser($user_id)) {
                $svUserInfo = $svService->balanceSearch($user_id);
                if ($svUserInfo['status'] == SupervisionEnum::RESPONSE_SUCCESS) {
                    $userTotalMoney = bcadd($userTotalMoney, bcdiv($svUserInfo['data']['availableBalance'], 100, 2), 2);
                }
            }
        }
        // 如果用户存在限制提现金额
        if(bccomp($amount, '0.00', 2) > 0) {
            $remainMoney = bcsub($userTotalMoney, $amount, 2);
            if (bccomp($remainMoney, '0.00', 2) <= 0) {
                return false;
            }
            // 如果用户限制提现金额大于剩余金额，则不通过
            if (bccomp($withdrawAmount, $remainMoney, 2) > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * 判断是否可以进行余额划转指定金额，考虑限制提现
     */
    public function canTransferAmount($userId, $transferAmount) {
        return $this->canWithdrawAmount($userId, $transferAmount);
    }


    /**
     * 获取用户限制提现金额， 单位元
     * @param integer $userId 用户id
     * @return float amount
     */
    public function getLimitAmountByUserId($userId) {
        $sql = "SELECT amount FROM firstp2p_withdraw_limit WHERE user_id = '{$userId}' AND state = ".self::WITHDRAW_LIMIT_PASSED. ' AND platform = '.UserAccountEnum::PLATFORM_WANGXIN;
        $amount = $GLOBALS['db']->get_slave()->getOne($sql);
        return bcadd($amount, '0.00', 2);
    }

    /**
     * 提交限制提现取消申请
     * @param $id integer
     * @param $cancel_state integer
     * @param $adm_name 操作人员用户名
     * @param $adm_id 操作人员id
     */
    public function doCancelAudit($id, $status, $adm_name, $adm_id) {
        $_toUpdate = array(
            'audit_adm_name' => $adm_name,
            'audit_adm_id' => $adm_id,
            'audit_time' => get_gmtime(),
            'update_time' => get_gmtime(),
        );
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        $withdrawLimitRecord = WithdrawLimitModel::instance()->find($id);
        $userInfo = UserService::getUserById($withdrawLimitRecord['user_id']);
        try {
            $db->startTrans();
            if ($status == self::WITHDRAW_LIMIT_CANCEL_PASSED) {
                $sql = "DELETE FROM firstp2p_withdraw_limit WHERE id = '{$id}'";
                $GLOBALS['db']->query($sql);
                $affRows = $GLOBALS['db']->affected_rows();
                if ($withdrawLimitRecord['platform'] == UserAccountEnum::PLATFORM_SUPERVISION && $withdrawLimitRecord['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
                {
                    $insertCancelRecord = [
                        'wl_id'         => $id,
                        'audit_time'    => get_gmtime(),
                        'status'        => self::WITHDRAW_LIMIT_STATUS_CANCEL,
                        'memo'          => $withdrawLimitRecord['memo'],
                        'update_time'   => get_gmtime(),
                    ];

                    $db->autoExecute('firstp2p_withdraw_limit_record', $insertCancelRecord, 'UADTE', "wl_id = $id");
                    $db->commit();
                    return true;
                } else {
                    // 保持黑名单删除记录的逻辑
                    $this->deleteWithdrawLimitRecordByWlid($id);
                    $db->commit();
                    return true;
                }

            }
            else if ($status == self::WITHDRAW_LIMIT_CANCEL_REFUSED) {
                // reset cancel_state
                $_toUpdate['cancel_state'] = self::WITHDRAW_LIMIT_CANCEL_NONE;
                $_toUpdate['adm_id'] = $adm_id;
                $_toUpdate['adm_name'] = $adm_name;
                $_toUpdate['update_time'] = get_gmtime();

            }
            else if ($status == self::WITHDRAW_LIMIT_CANCEL_INIT) {
                $_toUpdate['cancel_state'] = self::WITHDRAW_LIMIT_CANCEL_INIT;
                $_toUpdate['adm_id'] = $adm_id;
                $_toUpdate['adm_name'] = $adm_name;
                $_toUpdate['update_time'] = get_gmtime();
                unset($_toUpdate['audit_adm_id']);
                unset($_toUpdate['audit_time']);
                unset($_toUpdate['audit_adm_name']);
            }
            $GLOBALS['db']->autoExecute('firstp2p_withdraw_limit', $_toUpdate, 'UPDATE', " id = '{$id}'");
            $affRows = $GLOBALS['db']->affected_rows();
            if ($affRows != 1)
            {
                throw new \Exception('审核失败');
            }
            $db->commit();
            return true;
        } catch(\Exception $e) {
            $db->rollback();
            return false;
        }
    }


    /**
     * 判断用户是否上海银行，其他银行单笔提现不能超过500w，上海银行单笔提现不能超过20w
     * @param integer $userId 用户ID
     * @param string $withdrawAmount 提现金额
     * @return array
     */
    public function canWithdraw($userId, $withdrawAmount)
    {
        $sql = "SELECT bank_id FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'";
        $bankId = $GLOBALS['db']->get_slave()->getOne($sql);
        $ret = array();
        $ret['result'] = true;
        $ret['specialBank'] = false;
        $ret['reason'] = '';
        if (!empty($bankId))
        {
            $sqlShortName = "SELECT short_name FROM firstp2p_bank WHERE id = '{$bankId}'";
            $shortName = $GLOBALS['db']->get_slave()->getOne($sqlShortName);
            if (in_array($shortName, array('BOS')))
            {
                $ret['specialBank'] = true;
            }
        }

        if ($ret['specialBank'])
        {
            if (bccomp($withdrawAmount, '200000.00', 2) > 0)
            {
                $ret['reason'] = '您的银行卡仅支持单笔20万元交易，请分多次提现。';
                $ret['result'] = false;
            }
        }
        else
        {
            if (bccomp($withdrawAmount, '5000000.00', 2) > 0)
            {
                $ret['reason'] = '您的银行卡仅支持单笔500万元交易，请分多次提现。';
                $ret['result'] = false;
            }
        }
        return $ret;
    }

    /**
     * 通过限制投资id删除投资限制记录
     * @param int $wl_id
     * @return boolean
     */
    function deleteWithdrawLimitRecordByWlid($wl_id)
    {
       $sql = "DELETE FROM firstp2p_withdraw_limit_record WHERE wl_id = '".intval($wl_id)."'";
       $GLOBALS['db']->query($sql);
       $affRows = $GLOBALS['db']->affected_rows();
       return $affRows == 1;
    }

    function saveWithdrawLimitRecord($wl_id,$status = self::WITHDRAW_LIMIT_STATUS_T1, $adm_name = '', $adm_id = 0)
    {
        $wl_id = intval($wl_id);
        if(empty($wl_id))
        {
            return false;
        }

        //获取限制用户的记录
        $withdrawLimitInfo = $this->findLimitById($wl_id);
        $_toUpdate= [
            'wl_id'         => $wl_id,
            'user_id'       => $withdrawLimitInfo['user_id'],
            'type'          => $withdrawLimitInfo['type'],
            'status'        => self::WITHDRAW_LIMIT_STATUS_T1,
        ];

        // 区分提现记录类型
        $isWhitelist = 0;
        if ($withdrawLimitInfo['platform'] == UserAccountEnum::PLATFORM_SUPERVISION && $withdrawLimitInfo['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
        {
            $isWhitelist = 1;
        }

        $userInfo = UserService::getUserById($_toUpdate['user_id']);
        $accountInfo = AccountService::getAccountMoneyById($_toUpdate['user_id']);

        $_toUpdate['audit_adm_name']= $withdrawLimitInfo['audit_adm_name'];
        $_toUpdate['is_whitelist']  = $isWhitelist;
        $_toUpdate['adm_id']        = $withdrawLimitInfo['adm_id'];
        $_toUpdate['adm_name']      = $withdrawLimitInfo['adm_name'];
        $_toUpdate['audit_adm_id']  = $withdrawLimitInfo['audit_adm_id'];
        $_toUpdate['status']        = $status;
        $_toUpdate['money']         = $accountInfo['money'];
        $_toUpdate['user_name']     = $userInfo['user_name'];
        if($status == self::WITHDRAW_LIMIT_STATUS_T1)//新申请的投资体现限制初始化限制金额，还款中 和还款后限制金额不变
        {
            $_toUpdate['amount'] = $withdrawLimitInfo['amount'];
        }
        $sql = "SELECT COUNT(*) FROM firstp2p_withdraw_limit_record WHERE wl_id = ".$wl_id;
        $userCount = $GLOBALS['db']->get_slave()->getOne($sql);
        if(!empty($userCount))
        {
            $_toUpdate['update_time'] = get_gmtime();
            $GLOBALS['db']->autoExecute('firstp2p_withdraw_limit_record', $_toUpdate, 'UPDATE', " wl_id = ".$wl_id);
            $affRows = $GLOBALS['db']->affected_rows();
        } else {
            $_toUpdate['memo']          = $withdrawLimitInfo['memo'];
            $_toUpdate['remain_money']  = $withdrawLimitInfo['remain_money'];
            $_toUpdate['create_time']   = $withdrawLimitInfo['create_time'];
            $GLOBALS['db']->autoExecute('firstp2p_withdraw_limit_record', $_toUpdate, 'INSERT');
            $affRows = $GLOBALS['db']->affected_rows();
        }
        return $affRows == 1;
    }

    /**
     * 还款之后更新限制记录
     * @param int $user_id
     * @param float $repay_money
     * @return boolean
     */
    function updateWithdrawLimitAfterRepalyMoney($user_id,$repay_money)
    {
        //获取限制用户的记录
        $withdrawLimitInfo = $this->findLimitByUserId($user_id);
        if(empty($withdrawLimitInfo))
        {
            return true;
        }
        $remainMoney = bcsub($withdrawLimitInfo['amount'] , $repay_money , 2);
        if (bccomp($remainMoney, '0.00', 2) <= 0){
           // 如果是白名单的限制提现规则，则不删除记录
           if ($withdrawLimitInfo['platform'] == UserAccountEnum::PLATFORM_SUPERVISION && $withdrawLimitInfo['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
           {
              return true;
           }
           $status = self::WITHDRAW_LIMIT_STATUS_T3;
           $this->saveWithdrawLimitRecord($withdrawLimitInfo['id'],$status);
           $sql = "DELETE FROM firstp2p_withdraw_limit WHERE id = ".$withdrawLimitInfo['id'];
           $GLOBALS['db']->query($sql);
           $affRows = $GLOBALS['db']->affected_rows();
        }
        else{
            $status = self::WITHDRAW_LIMIT_STATUS_T2;
            $_toUpdate = array('amount'=>$remainMoney);
            $GLOBALS['db']->autoExecute('firstp2p_withdraw_limit', $_toUpdate, 'UPDATE', " id = ".$withdrawLimitInfo['id']);
            $affRows = $GLOBALS['db']->affected_rows();
            $this->saveWithdrawLimitRecord($withdrawLimitInfo['id'],$status);
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"status:".$status,"user_id:".$user_id,"repay_money:".$repay_money,"result:". ($affRows == 1?"成功":"失败"))));
        return $affRows == 1;
    }

    /**
     * 获取某用户的提现记录（近7天的）
     */
    public function getWithdrawListByUserId($userId,$offset,$count){
        $list = UserCarryModel::instance()->getListByUid($userId,$offset,$count);
        if(is_array($list) && !empty($list)){
            foreach($list as &$one){
                if($one['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_SUCCESS){
                    $one['status_str'] = '提现成功';
                }elseif($one['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_FAILED){
                    $one['status_str'] = '提现失败';
                }else{
                    $one['status_str'] = '提现处理中';
                }
                $one['create_time'] = $one['create_time']+28800;
                unset($one['id']);
                unset($one['withdraw_status']);
            }
            return $list;
        }
        return array();
    }

    /**
     * 根据标的ID，获取先锋支付的bizType
     * @param int $dealId 标的id
     * @param array $dealInfo 标的详情
     * @return array
     */
    public static function getBizTypeByDealId($dealId = 0, $dealInfo = array(), &$withdrawalType = 1)
    {
        $bizTypeMap = PaymentApi::instance()->getGateway()->getConfig('towithdrawal', 'bizTypeMap');
        isset($bizTypeMap['default']) || $bizTypeMap['default'] = 'q007tx';
        $result = array('bizType'=>$bizTypeMap['default'], 'isInBizMap'=>false);
        // 普通放款
        if (!is_numeric($dealId) || $dealId <= 0)
        {
            return $result;
        }
        // 标的ID大于0时，则默认选择[放款提现]
        $result['bizType'] = isset($bizTypeMap['FKTX']) ? $bizTypeMap['FKTX'] : $bizTypeMap['default'];
        // 按消费类型放款
        if (!isset($bizTypeMap['dealLoanType']) || empty($bizTypeMap['dealLoanType']))
        {
            return $result;
        }
        // 根据标的ID，获取标的类型
        $dealInfo || $dealInfo = DealModel::instance()->findByViaSlave('id = :id', 'type_id', array(':id' => $dealId));
        if (!isset($dealInfo['type_id']) || $dealInfo['type_id'] <= 0)
        {
            return $result;
        }
        // 根据标的类型，获取标的tag
        $dealLoanTypeTag = DealLoanTypeModel::instance()->getLoanTagByTypeId($dealInfo['type_id']);
        if (empty($dealLoanTypeTag))
        {
            return $result;
        }
        // 检查标的tag，是否在配置的bizMap里面
        if (isset($bizTypeMap['dealLoanType'][$dealLoanTypeTag]) && !empty($bizTypeMap['dealLoanType'][$dealLoanTypeTag]))
        {
            $result['bizType'] = $bizTypeMap['dealLoanType'][$dealLoanTypeTag];
            $result['isInBizMap'] = true;
            // 在该配置里的类型，必须是对私的提现
            //$withdrawalType = 1;
        }
        return $result;
    }

    /**
     * 根据标的id自动批准提现申请
     * @param int $deal_id
     * @return bool
     */
    public function doPassByDealId ($deal_id) {
        $uc = UserCarryModel::instance()->getByDealId($deal_id);
        if (!$uc) {
            return false;
        }

        $uc->status = 3;
        $uc->update_time = $uc->update_time_step1 = $uc->update_time_step2 = get_gmtime();
        return $uc->save();
    }

    /**
     * 根据标的id获取提醒状态
     * @param int $deal_id
     * @return object
     */
    public function getByDealIdStatus($deal_id){
        $deal_id = intval($deal_id);
        if (empty($deal_id)){
            return false;
        }

        $user_carray_model = new UserCarryModel();

        return $user_carray_model->getByDealIdStatus($deal_id);
    }

    /**
     * 根据标的ID获取最新的借款人提现申请记录
     */
    public function getLatestByDealId($deal_id) {
        $deal_id = intval($deal_id);
        if (empty($deal_id)) {
            return false;
        }
        $condition = "deal_id = '{$deal_id}' order by id desc limit 1";
        $item = UserCarryModel::instance()->findBy($condition);
        return $item;
    }

    /**
     * 判断是否可以重新发起提现
     */
    public function canRedoWithdraw($user_carry) {
        if (!empty($user_carry['deal_id']) && $user_carry['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_FAILED) {
            $latest = $this->getLatestByDealId($user_carry['deal_id']);
            if (!empty($latest) && $latest['id'] == $user_carry['id']) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断标的放款状态 和 提现状态 均为成功
     */
    public function isDealSuccessfulForLoansAndWithdraw($deal_id)
    {
        $user_carry_obj = $this->getByDealIdStatus($deal_id);
        if (empty($user_carry_obj)) {
            return false;
        } else {
            // 放款状态：会计通过，提现状态：成功
            return (SupervisionEnum::STATUS_ACCOUNTANT_PASS == $user_carry_obj->status && SupervisionEnum::WITHDRAW_STATUS_SUCCESS == $user_carry_obj->withdraw_status);
        }
    }

    /**
     * 获取用户最后一次提现
     * @param integer $userId
     * @return mixed
     */
    public function getLastWithdrawLog($userId) {
        if (empty($userId)) {
            return false;
        }
        $lastWithdrawLog = UserCarryModel::instance()->db->getRow("SELECT * FROM firstp2p_user_carry WHERE user_id = '{$userId}' AND withdraw_status = ".SupervisionEnum::WITHDRAW_STATUS_SUCCESS.' ORDER BY id DESC LIMIT 1');
        $lastP2pWithdarwLog = SupervisionWithdrawModel::instance()->db->getRow("SELECT * FROM firstp2p_supervision_withdraw WHERE user_id = '{$userId}' AND withdraw_status= ".SupervisionEnum::WITHDRAW_STATUS_SUCCESS.' ORDER BY id DESC limit 1');
        $withdrawLog = [];
        if(!empty($lastWithdrawLog) && !empty($lastP2pWithdrawLog) && ($lastWithdrawLog['pay_time'] + 28800 >= $lastP2pWithdrawLog['update_time'])) {
            // 订单号
            $withdrawLog['order_id'] = $lastWithdrawLog['id'];
            // 支付时间
            $withdrawLog['withdraw_time'] = $lastWithdrawLog['withdraw_time'] + 28800;
            // 支付时间格式化
            $withdrawLog['withdraw_datetime'] = date('Y-m-d H:i:s', $withdrawLog['withdraw_time']);
            // 支付金额 单位分
            $withdrawLog['amount'] = bcmul($lastWithdrawLog['money'], 100);
        }
        else if(!empty($lastWithdrawLog) && !empty($lastP2pWithdrawLog) && ($lastWithdrawLog['pay_time'] + 28800 < $lastP2pWithdrawLog['update_time'])) {
            // 订单号
            $withdrawLog['order_id'] = $lastP2pWithdrawLog['out_order_id'];
            // 支付时间
            $withdrawLog['withdraw_time'] = $lastP2pWithdrawLog['update_time'];
            // 支付时间格式化
            $withdrawLog['withdraw_datetime'] = date('Y-m-d H:i:s', $withdrawLog['withdraw_time']);
            // 支付金额 单位分
            $withdrawLog['amount'] = $lastP2pWithdrawLog['amount'];
        } else if (!empty($lastWithdrawLog)) {
            // 订单号
            $withdrawLog['order_id'] = $lastWithdrawLog['id'];
            // 支付时间
            $withdrawLog['withdraw_time'] = $lastWithdrawLog['withdraw_time'] + 28800;
            // 支付时间格式化
            $withdrawLog['wthdraw_datetime'] = date('Y-m-d H:i:s', $withdrawLog['withdraw_time']);
            // 支付金额 单位分
            $withdrawLog['amount'] = bcmul($lastWithdrawLog['money'], 100);
        } else if (!empty($lastP2pWithdrawLog)) {
            // 订单号
            $withdrawLog['order_id'] = $lastP2pWithdrawLog['out_order_id'];
            // 支付时间
            $withdrawLog['withdraw_time'] = $lastP2pWithdrawLog['update_time'];
            // 支付时间格式化
            $withdrawLog['withdraw_datetime'] = date('Y-m-d H:i:s', $withdrawLog['withdraw_time']);
            // 支付金额 单位分
            $withdrawLog['amount'] = $lastP2pWithdrawLog['amount'];
        }
        return $withdrawLog;
    }
    /**
     * 审核用提现限制
     * @param $id integer 记录id
     * @param $status integer 审核状态
     * @param $adm_name string 管理员名称
     * @param $adm_id integer 管理员id
     * @return boolean
     */
    public function doAudit($id, $status, $adm_name, $adm_id, $newAmount = 0) {
        $_toUpdate = array(
            'state' => $status,
            'audit_adm_name' => $adm_name,
            'audit_adm_id' => $adm_id,
            'audit_time' => get_gmtime(),
            'update_time' => get_gmtime(),
        );
        $db = \libs\db\Db::getInstance('firstp2p', 'master');
        try {
            $db->startTrans();
            if ($status == self::WITHDRAW_LIMIT_PASSED) {
                if (bccomp($newAmount, '0.00', 2) > 0) {
                    $_toUpdate['amount'] = $newAmount;
                    $_toUpdate['modify_amount'] = '0.00';
                }
                $withdrawLimitRecord = $db->getRow("SELECT * FROM firstp2p_withdraw_limit WHERE id = '{$id}'");
                $userInfo = UserService::getUserById($withdrawLimitRecord['user_id']);
            }
            if ($status == self::WITHDRAW_LIMIT_REFUSED) {
                if (bccomp($newAmount, '0.00', 2) > 0) {
                    //还原状态到可用
                    $_toUpdate['state'] = self::WITHDRAW_LIMIT_PASSED;
                    $_toUpdate['modify_amount'] = 0.00;
                } else {
                    $sql = "DELETE FROM firstp2p_withdraw_limit WHERE id = '{$id}'";
                    $db->query($sql);
                    $affRows = $db->affected_rows();
                    $this->deleteWithdrawLimitRecordByWlid($id);
                    $db->commit();
                    return $affRows == 1;
                }
            }
            $db->autoExecute('firstp2p_withdraw_limit', $_toUpdate, 'UPDATE', " id = '{$id}'");
            $affRows = $db->affected_rows();
            //投资、提现限制审核通过之后，记录申请记录
            if($status == self::WITHDRAW_LIMIT_PASSED && $affRows == 1)
            {
                $this->saveWithdrawLimitRecord($id,$status, $adm_name, $adm_id);
            }
            if($affRows != 1)
            {
                throw new \Exception('审核失败');
            }
            $db->commit();
            return true;
        } catch(\Exception $e) {
            $db->rollback();
            return false;
        }
    }

}
// END class UserCarryService
