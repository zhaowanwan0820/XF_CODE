<?php
/**
 * UserModel class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace core\dao;

use \libs\utils\Monitor;
use core\dao\UserThirdBalanceModel;
use core\dao\WangxinPassportModel;
use core\dao\UserBankcardModel;

/**
 * 用户信息
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class UserModel extends BaseModel {
    const TYPE_MONEY = 0;   //增加余额
    const TYPE_LOCK_MONEY = 1;  //冻结金额，增加冻结资金同时减少余额
    const TYPE_DEDUCT_LOCK_MONEY = 2;   //减少冻结金额
    const TYPE_OPERATE_LOCK_MONEY = 3; // 操作冻结金额
    const TYPE_OPERATE_MONEY = 4; // 操作余额

    const PHOTO_STATUS_DEFAULT = 0;
    const PHOTO_STATUS_PASS = 1;
    const PHOTO_STATUS_REJECT = 2;
    const PHOTO_STATUS_INIT = 3;

    /**
     * 用户类型-普通用户
     * @var int
     */
    const USER_TYPE_NORMAL = 0;

    /**
     * 用户类型-企业用户
     * @var int
     */
    const USER_TYPE_ENTERPRISE = 1;

    // ------------------- user 用户类型名称 -----------------------
    /**
     * 用户类型名称-个人用户
     * @var string
     */
    const USER_TYPE_NORMAL_NAME = '个人用户';

    /**
     * 用户类型名称-企业用户
     * @var string
     */
    const USER_TYPE_ENTERPRISE_NAME = '企业用户';
    // ------------------------- over -------------------------------

    // ------------------------ user 表中字段 -----------------------
    /**
     * user表中字段名称 - real_name
     * @var string
     */
    const TABLE_FIELD_REAL_NAME = 'real_name';

    /**
     * user表中字段名称 - mobile
     * @var string
     */
    const TABLE_FIELD_MOBILE = 'mobile';
    // ------------------------- over -------------------------------

    const MSG_FOR_USER_ACCOUNT_TITLE = ''; // 在短信中对个人用户的title（为适应企业会员的短信信息）

    const ACCOUNT_FREEZE_KEY = 'account_freeze_key_';   // 账户冻结的键值
    const ACCOUNT_FREEZE_TIME = 86400;                  // 账户冻结的时间

    /**
     * 查询用户是否冻结
     * @param string $username 用户名
     * @return bool
     */
    public function isUserAccountFreeze($username) {
        if (empty($username)) {
            return false;
        }

        return \SiteApp::init()->cache->get(self::ACCOUNT_FREEZE_KEY . $username) ? true : false;
    }

    /**
     * 冻结企业用户
     * @param string $username 用户名
     * @return bool
     */
    public function freezeUserAccount($username) {
        if (empty($username)) {
            return false;
        }

        if ($this->isUserAccountFreeze($username)) {
            return true;
        }

        $res = \SiteApp::init()->cache->set(self::ACCOUNT_FREEZE_KEY . $username, 1, self::ACCOUNT_FREEZE_TIME);
        if ($res) {
            // 给用户发送冻结短信
            // 您的企业账号因输入密码错误次数过多已被冻结，24小时后解除冻结状态，若非本人操作请及时修改密码。
            $userInfo = $this->getUserinfoByUsername($username);
            if (empty($userInfo)) {
                return false;
            }
            \libs\sms\SmsServer::instance()->send($userInfo['mobile'], 'TPL_FREEZE_USER_ACCOUNT', array(), $userInfo['id']);
        }

        return $res;
    }

    /**
     * 企业用户-公司证件类型
     * @see conf/dictionary.conf.php里面的CREDENTIALS_TYPE
     * @var array
     */
    public static $credentialsType = array(
        1 => 'BLC', // 营业执照
        2 => 'ORC', // 组织机构代码证
        3 => 'USCC', // 统一社会信用代码/三证合一营业执照
        0 => 'RTC', // 其他企业证件
        'default' => 'BLC', // 默认
    );

    /**
     * 企业用户-法人证件类型
     * @see conf/dictionary.conf.php里面的ID_TYPE
     * @var array
     */
    public static $idCardType = array(
        1 => 'IDC', // 身份证
        4 => 'GAT', // 港澳居民来往内地通行证/港澳台身份证
        6 => 'GAT', // 台湾居民往来大陆通行证/港澳台身份证
        2 => 'PASS_PORT', // 护照
        3 => 'MILIARY', // 军官证
        'default' => 'IDC', // 默认
    );

    /**
     * 需要标记删除的log_info
     * @var array
     */
    public static $userLogMarkDelete = array(
        '智多鑫-转入本金解冻',
        '智多鑫-本金回款并冻结',
        '智多鑫-债权出让',
        '智多鑫-债权出让本金回款并冻结',
    );

    /**
     * 头像照片映射数组配置
     * @var array
     */
    public static $PHOTO_STATUS = array(
        self::PHOTO_STATUS_DEFAULT => '照片未上传',
        self::PHOTO_STATUS_INIT => '照片审核中',
        self::PHOTO_STATUS_PASS => '照片审核通过',
        self::PHOTO_STATUS_REJECT => '照片审核拒绝',
    );

    /**
     * 根据默认主键id查询数据库, 数据表必须存在名称为"id"的字段
     * @param mixed $id 通常是int类型的字段值，也肯能是字符串
     * @return model 如果存在指定记录返回实体对象，否则返回null
     **/
    public function find($id, $fields = "*", $is_slave = false) {
        return parent::find($id, $this->addFieldversionId($fields), $is_slave);
    }

    /**
     * 指定条件语句执行查询
     * @param string $condition 条件语句
     * @return model 如果存在指定记录返回实体对象，否则返回null
     * 此处重载libs/db/Model里面的findBy方法，需要一致 --By wangjiansong@
     **/
    public function findBy($condition, $fields = "*", $params = array(), $is_slave = false) {
        return parent::findBy($condition, $this->addFieldversionId($fields), $params, $is_slave);
    }

    /**
     * 指定条件语句查询多条结果
     * @param string $condition 条件语句
     * @param Boole $is_array 是否返回数组
     * @return model 如果存在指定记录返回实体对象，否则返回null
     **/
    public function findAll($condition = "", $is_array = false, $fields = "*", $params = array()) {
        return parent::findAll($condition, $is_array, $this->addFieldversionId($fields), $params);
    }

    public function findAllViaSlave($condition = "", $is_array = false, $fields = "*", $params = array()) {
        return parent::findAllViaSlave($condition, $is_array, $this->addFieldversionId($fields), $params);
    }

    /**
     * 补充find*方法的field字段，添加version_id字段的返回，以便支持乐观锁
     * @param $fields 传入find*方法的fields字段
     * @return string 补充version_id字段后的fields值
     */
    private function addFieldversionId($fields) {
        $reg = "/((^\s*)|(,\s*))(version_id)((\s*,)|\s*$)/i";
        if (trim($fields) != '*' && !preg_match($reg, $fields)) {
            $fields .= ", version_id";
        }
        return $fields;
    }

    /**
     * 是否异步处理
     */
    public $changeMoneyAsyn = false;

    /**
     * 交易类型
     */
    public $changeMoneyDealType = 0;


    /**
     * 是否调整存管余额
     */
    public $changeSupervisionMoney = true;


    public $isDoNothing = false;

    /**
     * 直接进行资金变动
     * @param float $money 金额
     * @param string $message 类型
     * @param string $note 备注
     * @param int $admin_id 管理员id
     * @param int $is_manage 是否是管理费
     * @param int $money_type 0-money 1-lock_money
     * @param int $negative 用户余额是否允许扣负
     * @return boolean
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    public function changeMoney($money, $message, $note, $admin_id = 0, $is_manage = 0, $money_type = 0, $negative = 0, $bizToken = [])
    {
        if($this->isDoNothing === true){
            return true;
        }

        // 网信不允许直接操作网贷账户资金
        if ($this->changeMoneyDealType == DealModel::DEAL_TYPE_SUPERVISION) {
            \libs\utils\Logger::error("网信不允许直接操作网贷账户资金. log_info:$message, note:$note, user_id:{$this->id}");
            throw new \Exception("网信不允许直接操作网贷账户资金. log_info:$message, note:$note, user_id:{$this->id}");
            return false;
        }

        // 生成业务token
        $bizIds = $bizToken;
        $bizToken = $this->generateBizToken($message, $bizToken);
        if (empty($bizToken)) {
            \libs\utils\Logger::info("changeMoney no bizToken. log_info:$message, note:$note");
        }

        //是否异步执行
        if ($this->changeMoneyAsyn === true) {
            $data = array(
                'user_id' => $this->id,
                'money' => $money,
                'message' => $message,
                'note' => $note,
                'money_type' => $money_type,
                'create_time' => time(),
                'status' => 0,
                'deal_type' => intval($this->changeMoneyDealType),
                'biz_token' => $bizToken,
            );
            if (!$GLOBALS['db']->insert('firstp2p_money_queue', $data)) {
                throw new \Exception('Insert firstp2p_money_queue failed');
            }
            return true;
        }

        \FP::import("libs.utils.logger");
        if ($is_manage == 0 && $money != 0 && ($this->changeMoneyDealType != DealModel::DEAL_TYPE_SUPERVISION)) { // 若为管理费，则只发消息不变更账户余额
            $sql_where = "";

            $sql = "";
            $update_time = get_gmtime();
            if ($money_type == self::TYPE_MONEY) {
                $sql_where = $negative == 0 ? " AND money + '".floatval($money) ."' >= 0" : '';
                $sql = "UPDATE " . $this->tableName() . " SET `money`=`money`+'" . floatval($money) .
                    "',update_time = '{$update_time}' WHERE `id`='" . $this->id . "' {$sql_where}";
            } elseif ($money_type == self::TYPE_LOCK_MONEY) {
                // 不允许扣负
                if ($negative == 0) {
                    if (bccomp($money, '0.00', 2) >= 0) {
                        $sql_where = sprintf(' AND `money` >= \'%s\'', floatval($money));
                    }else{
                        $sql_where = sprintf(' AND `lock_money` + \'%s\' >= 0', floatval($money));
                    }
                }
                $sql = "UPDATE " . $this->tableName() . " SET `money`=`money`-'" . floatval($money) .
                    "', `lock_money`=`lock_money`+'" . floatval($money) . "',update_time = '{$update_time}' WHERE `id`='" . $this->id . "' {$sql_where}";
            } elseif ($money_type == self::TYPE_DEDUCT_LOCK_MONEY) {
                // 不允许扣负
                $sql_where = $negative == 0 ? sprintf(' AND `lock_money` >= \'%s\'', floatval($money)) : '';
                $sql = "UPDATE " . $this->tableName() . " SET `lock_money`=`lock_money`-'" . floatval($money) .
                    "',update_time = '{$update_time}' WHERE `id`='{$this->id}' {$sql_where}";
            }

            if ($sql) {
                $r = $this->db->query($sql);
                $affected_rows = $this->db->affected_rows();
                $user_data = new \core\data\UserData();
                $user_data->clearUserSummary($this->id);
            }
            if (!$r || ($money != 0 && $affected_rows <= 0)) {
                // todo 抛异常？
                $log = array(__FUNCTION__, APP, "update money error", $money, $message, $note, $sql);
                \logger::warn(implode(" | ", $log));
                throw new \Exception("ChangeMoney修改用户余额失败. userId:{$this->id}", '20002');
                return false;
            }
        }


        //更新金额数据
        $row = $this->find($this->id, "money, lock_money, version_id");
        $this->money = $row->money;
        $this->lock_money = $row->lock_money;
        $this->version_id = $row->version_id;

        $res = false;

        //记录资金变动日志
        $user_log = new UserLogModel();
        $user_log->log_info = $message;
        $user_log->note = $note;
        $user_log->log_time = get_gmtime();
        $user_log->log_admin_id = $admin_id;
        $user_log->log_user_id = $this->id;
        $user_log->user_id = $this->id;
        //增加交易类型字段
        $user_log->deal_type = intval($this->changeMoneyDealType);
        if ($money_type == self::TYPE_MONEY) {
            $user_log->money = floatval($money);
        } elseif ($money_type == self::TYPE_LOCK_MONEY) {
            $user_log->money = -floatval($money);
            $user_log->lock_money = floatval($money);
        } elseif ($money_type == self::TYPE_DEDUCT_LOCK_MONEY) {
            $user_log->lock_money = -floatval($money);
        }

        $remaining_money = $this->money;
        $remaining_total_money = $this->lock_money + $this->money;

        $user_log->remaining_money = $remaining_money;
        $user_log->remaining_total_money = $remaining_total_money;
        if(in_array($message,self::$userLogMarkDelete)) {
            $user_log->is_delete = 1;
        }

        // 添加业务token,标id，业务订单号
        $user_log->biz_token = $bizToken;
        $user_log->deal_id = isset($bizIds['dealId'])? intval($bizIds['dealId']):0;
        $user_log->out_order_id = isset($bizIds['outOrderId'])? trim($bizIds['outOrderId']):'';
        if(!$user_log->insert()){
            throw new \Exception("ChangeMoney增加资金记录失败. userId:{$this->id}");
            $res = false;
        } else {
            $res = true;
            $this->_setUserLogMaxId($user_log->id);
        }

        $user_log->remaing_lock_money = $user_log->remaining_total_money - $user_log->remaining_money;

        $log = array_merge(array(__FUNCTION__, @APP, $res), $user_log->getRow());
        \logger::info(implode(" | ", $log));

        $trace = debug_backtrace();
        $caller = isset($trace[1]['function']) ? basename($trace[0]['file']).'/'.$trace[1]['function'].':'.$trace[0]['line'] : '';
        \libs\utils\PaymentApi::log("ChangeMoney. {$caller}, userLog:".json_encode($user_log->getRow(), JSON_UNESCAPED_UNICODE));

        return $res;
    }

    private function _setUserLogMaxId($user_log_id) {
        $max_id = \SiteApp::init()->cache->get('max_user_log_id');
        if (!$max_id) {
            \SiteApp::init()->cache->set('max_user_log_id', $user_log_id, 86400*30);
        }
    }

    /**
     * 封装登录方法
     * @param string $username
     * @param string $password
     * @param int $oauth
     * @return array
     */
    public function doLogin($username, $password, $oauth = 0, $byPassport = false) {
        $user = null;
        if ($byPassport) {
            $condition = sprintf("`passport_id`='%s' AND `is_delete`='0'", $this->escape($username));
            $user = $this->findBy($condition);
        } else {
            if (is_mobile($username)) {//解决手机号与同户名可能冲突问题:匹配大陆手机号特征则按手机号查
                $condition = sprintf("`mobile`='%s' AND `is_delete`='0'",$this->escape($username));
            }else{//非大陆手机号查询和用户名查询
                $condition = sprintf("(`user_name`='%s' OR `mobile`='%s') AND `is_delete`='0'", $this->escape($username), $this->escape($username));
            }

            $user = $this->findBy($condition);
        }

        // 用户不存在，或者是无效用户，已经注销的用户
        if (empty($user) || $user['is_effect'] != 1) {
            return array('status'=>0, 'data'=>ACCOUNT_NO_EXIST_ERROR);
        }

        $user_data = $user->getRow();
        $result['user'] = $user_data;
        $result['status'] = 1;

        // todo 这样的操作是不是不应该放在Model里
        \es_session::set("user_info", $user_data);

        // todo 废弃GLOBALS??
        $GLOBALS['user_info'] = $user_data;

        $result['step'] = intval($user_data['step']);
        Monitor::add('LOGIN_SUCCESS');
        return $result;
    }

    /**
     * 判断用户是否已经存在
     * @param string passport_id
     * @return integer
     */
    public function isUserExists($passportId)
    {
        $passportId = $this->escape($passportId);
        $condition = sprintf(" passport_id = '%s'", $passportId);
        $res = $this->count($condition);
        return $res >= 1 ? true : false;
    }

    public function getUserIdByPassportId($passportId, $extraCondition = '') {
        $passportId = $this->escape($passportId);
        $condition = sprintf(" passport_id = '%s'", $passportId);
        if (!empty($extraCondition)) {
            $condition .= $extraCondition;
        }
        $fields = 'id';
        $res = $this->findBy($condition, $fields);
        if ($res) {
            return $res->id;
        }
        return false;
    }

    public function getCodeByPk($id) {
        $id = intval($id);
        $condition = sprintf(" id = '%d' ", $id);
        $fields = 'code';
        $res = $this->findBy($condition, $fields);
        if ($res) {
            return $res->code;
        }
        return false;
    }

    public function getInviteCodeByPk($id) {
        $id = intval($id);
        $condition = sprintf(" id = '%d' ", $id);
        $fields = ' invite_code ';
        $res = $this->findBy($condition, $fields);
        if ($res) {
            return $res->invite_code;
        }
        return false;
    }

    public function updateInfo($data, $mode) {
        $this->setRow($data);
        if ($mode == 'insert') {
            $this->insert();
            //更新用户等级
            $user_id = $this->db->insert_id();
            $coupon_level_service = new \core\service\CouponLevelService();
            $coupon_level_service->updateUserLevel($user_id);
            return $user_id;
        } else {
            $this->update($data);
            return TRUE;
        }
    }

    public function getInfoByName($user_name, $fields='id,user_name',$is_slave=false) {
        $condition = "user_name=':user_name'";
        return $this->findBy($condition, $fields, array(':user_name' => $user_name),$is_slave);
    }
    /**
     * 根据passport_id，用户名，email获取用户信息
     * todo 将用户名和email设为可选项
     * @param string $passport_id
     * @param string $user_name
     * @param string $email
     * @return obj $user
     */
    public function getUserByPassportId($passport_id, $user_name) {
        $condition = "`passport_id` = '%s' AND `user_name` = '%s'";
        $condition = sprintf($condition, $this->escape($passport_id), $this->escape($user_name));
        $user = $this->findBy($condition);
        return $user;
    }

    /**
     * 根据用户名获取用户全部信息
     * @param type $username
     * @return type
     */
    public function getUserinfoByUsername($username)
    {
        if (strpos($username, ' ') !== false) {
            return false;
        }

        if (is_mobile($username)) {//解决手机号与同户名可能冲突问题:匹配大陆手机号特征则按手机号查
            $condition = "`mobile` = '%s'";
            $condition = sprintf($condition, $this->escape($username));
        }else{//非大陆手机号查询和用户名查询
            $condition = "`user_name` = '%s' or `mobile` = '%s'";
            $condition = sprintf($condition, $this->escape($username), $this->escape($username));
        }

        $user = $this->findBy($condition, '*', array(), true);
        return $user;
    }

    /**
     * 判断用户是否已经存在
     * @param string $username
     * @return integer
     */
    public function isUserExistsByUsername($username)
    {
        $condition = sprintf(" `user_name` = '%s' or `mobile` = '%s' ",$this->escape($username),$this->escape($username));
        $res = $this->count($condition);
        return $res >= 1 ? true : false;
    }
    /**
     * 判断用户是否已经存在
     * @param string $email
     * @return integer
     */
    public function isUserExistsByEmail($email)
    {
        $condition = sprintf(" `email` = '%s' ",$this->escape($email));
        $res = $this->count($condition);
        return $res >= 1 ? true : false;
    }
    /**
     * 判断用户是否已经存在
     * @param string $email
     * @return integer
     */
    public function isUserExistsByEmailSub($email)
    {
        $condition = sprintf(" `email_sub` = '%s' ",$this->escape($email));
        $res = $this->count($condition);
        return $res >= 1 ? true : false;
    }
    /**
     * 判断用户是否已经存在
     * @param string $mobile
     * @return integer
     */
    public function isUserExistsByMobile($mobile)
    {
        $condition = sprintf(" `mobile` = '%s' ",$this->escape($mobile));
        $res = $this->count($condition);
        return $res >= 1 ? true : false;
    }

    /**
     * 通过idno，判断用户是否已经存在
     * @param string $idno
     * @return boolean
     */
    public function isUserExistsByIdno($idno)
    {
        $condition = sprintf(" `idno` = '%s' ",$this->escape($idno));
        $res = $this->count($condition);
        return $res >= 1 ? true : false;
    }

    /**
     * 获取用户id列表
     * @param int $offset
     * @param int $pagesize
     * @param int $start_id
     * @param int $end_id
     * @author zhanglei5@ucfgroup.com
     */
    public function getUserId($offset=null,$page_size=10,$start_id=0,$end_id=0) {
        $param = array();
        $page_size = intval($page_size);
        $sql = 'SELECT `id` FROM '.$this->tableName().' WHERE `is_delete` = 0 and `is_effect` = 1 ';
        if (is_numeric($start_id) && is_numeric($end_id) && $end_id > 0){
            $sql .= " AND (id > $start_id AND id <= $end_id)";
        }
        if(isset($offset)) {
            $sql .= " limit :offset,:page_size";
            $param[':offset'] = $offset;
            $param[':page_size'] = $page_size;
        }

        $result = $this->findAllBySql($sql,true,$param);
        return $result;
    }

    /**
     * photoPass
     * 修改照片认证状态，
     *
     * @param mixed $user_id
     * @param mixed $status
     * @access public
     * @return true / false true 表示修改成功，false表示修改失败
     */
    public function photoPass($user_id, $status) {
        $user = $this->find($user_id);
        if (empty($user)) {
            return false;
        }
        $user->photo_passed = $status;
        $user->photo_passed_time = time();
        if ($user->save()) {
            return true;
        } else {
            return false;
        }
    }

    public function editPasswordByPhone($phone, $pwd){
        $user_info = $this->findBy(sprintf("mobile = '%s'", $this->escape($phone)));
        if($user_info){
            $userData = array(
                    'user_pwd'=> $this->escape($pwd),
                    );
            return $user_info->update($userData);
        }
        return false;
    }

    public function editPasswordByUserId($userId,$pwd) {
        $user_info = $this->findBy(sprintf("id = '%s'", $userId));
        if($user_info){
            $userData = array(
                'user_pwd'=> $this->escape($pwd),
            );
            return $user_info->update($userData);
        }
        return false;
    }
    /**
     * 根据用户身份证号获取用户信息
     * @param type $idno
     * @param type $userid 不包括的用户id
     * @return boolean
     */
    public function getUserByIdno($idno, $userid = '') {
        // 身份证号采用加密存储，统一使用大写的X后缀
        $condition = sprintf(" idno = '%s' ", strtoupper($this->escape($idno)));
        if (!empty($userid)) {
            $condition .= sprintf(" AND id <> '%d' ", $userid);
        }
        $fields = 'id,user_name,real_name,mobile,idno,idcardpassed,is_effect,supervision_user_id,user_purpose';
        $res = $this->findBy($condition, $fields);
        if ($res) {
            return $res;
        }
        return false;
    }

    /**
     * 根据用户身份证号获取所有用户
     * @param type $idno
     * @return boolean
     */
    public function getAllUserByIdno($idno) {
        // 身份证号采用加密存储，统一使用大写的X后缀
        $condition = sprintf(" idno = '%s' ", strtoupper($this->escape($idno)));
        $fields = 'id,user_name,real_name,mobile';
        return $this->findAllViaSlave($condition, true, $fields);
    }

    /**
     * getDeleteUids
     * 获取被删除用户IDlist
     *
     * @access public
     * @return void
     */
    public function getDeleteUids() {
        $condition = "is_delete = 1";
        $users = $this->findAll($condition, true, 'id');
        $uids = array();
        foreach ($users as $user) {
            $uids[] = $user['id'];
        }
        return $uids;
    }

    /**
     * 判断用户是否通过身份认证
     * @param $user_id
     * @return bool
     */
    public function is_idcardpassed($user_id){
        $user = $this->findViaSlave($user_id);
        if(!$user['real_name'] || $user['idcardpassed'] !=1){
            return false;
        }
        return true;
    }

    /**
     * 获取用户总数
     * @param $condition array()
     * @return array
     */
    public function getCount($condition) {
        $res = $this->countViaSlave($condition);
        return $res;
    }

    /**
     * 获取最新注册用户 id
     * @author pengchanglu
     * @return array
     */
    public function getUserLastId(){
        $sql = 'SELECT `id` FROM '.$this->tableName().' WHERE `is_delete` = 0 ORDER BY id DESC  LIMIT 1 ';
        $result = $this->findBySql($sql, array(), false);
        return $result;
    }

    public function getUserListByJob($user_group, $user_tags, $tag_relation = 0){

        $condition = array();
        if($user_group){
            $condition[] = "`group_id` IN (".$this->escape($user_group).")";
        }
        if($user_tags){
            $usertag_relation = new \core\dao\UserTagRelationModel();
            $usertag_service = new \core\service\UserTagService();
            $tagid_arr = $usertag_service->getTagIdsByConstName(explode(',', $user_tags));
            if($tagid_arr){
                $tagids = $this->escape(implode(',', array_keys($tagid_arr)));
                $and_condition = ($tag_relation == 1) ? ' GROUP BY `uid` HAVING COUNT(`uid`)>=' . count(array_keys($tagid_arr)) : '';
                $condition[] = "`id` IN (SELECT `uid` FROM ".$usertag_relation->tableName()." WHERE `tag_id` IN (".$tagids.")$and_condition)";
            }else{
                return array();
            }
        }
        if($condition){
            $sql = sprintf("SELECT `id`,`mobile` FROM %s WHERE %s", $this->tableName(), implode(' AND ', $condition));
        }else{
            $sql = sprintf("SELECT `id`,`mobile` FROM %s", $this->tableName());
        }
        return $this->findAllBySql($sql, true, array());
    }


    /**
    * 通过手机号获取注册用户的信息
    * @param $mobile string
    * @return array()
    */
    public function getUserByMobile($mobile, $fields = "*", $is_slave = false) {
        if (empty($mobile)) {
            return false;
        }
        $ret = $this->findBy("mobile = '" . $this->escape($mobile) . "'", $fields, array() , $is_slave);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 通过用户手机号获取用户id
     * @param String $mobile
     * @return string
     */
    public function getUserIdByMobile($mobile, $is_slave = false)
    {
        $userId = '';
        if (empty($mobile)) {
            return $userId;
        }

        $userInfo = $this->getUserByMobile($mobile, 'id', $is_slave);
        if(!empty($userInfo))
        {
            $userId = $userInfo['id'];
        }

        return $userId;
    }


        /**
     * 通过用户真实姓名获取注册用户的信息
     * @param $mobile string
     * @return array()
     */
    public function getUsersByRealName($realName, $fields = "*") {
        if (empty($realName)) {
            return false;
        }
        $ret = $this->findAllViaSlave("real_name = '" . $this->escape($realName) . "'",true, $fields);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * 通过用户真实姓名获取用户id
     * @param String $realName
     * @return array
     */
    public function getUserIdsByRealName($realName)
    {
        $userids = array();

        if (empty($realName)) {
            return $userids;
        }

        $userInfos = $this->getUsersByRealName($realName , 'id');
        if(!empty($userInfos))
        {
            foreach ($userInfos as $userInfo)
            {
                $userids[]=$userInfo['id'];
            }
        }
        unset($userInfos);
        return $userids;
    }

    /**
     * 批量用户id获取用户手机号
     * @param type $ids
     * @return type
     */
    public function getMobileByIds($ids)
    {
        $condition = "id IN (%s)";
        $condition = sprintf($condition, $this->escape($ids));
        $ret = $this->findAllViaSlave($condition, true, 'id,mobile' );
        return $ret;
    }

    /**
     * 批量用户id获取用户账号
     * @param type $ids
     * @return type
     */
    public function getUserNamesByIds($ids)
    {
        $data = array();

        if(empty($ids))
        {
            return $data;
        }

        if(!is_array($ids))
        {
            $ids  = explode(',', $ids);
        }

        $user_ids = array_map("intval",$ids);
        $ids = implode(',', $ids);

        $condition = "id IN (%s)";
        $condition = sprintf($condition, $this->escape($ids));
        $ret = $this->findAll($condition, true, 'id,user_name');
        if(!empty($ret))
        {
            foreach($ret as $val)
            {
                $data[$val['id']] = $val['user_name'];
            }
        }
        unset($ret);
        return $data;
    }

    /**
     * [根据ids批量获取用户信息]
     * @author <fanjingwen@ucfgroup.com>
     * @param array[seq => int] $ids [用户id数组]
     * @param string $fields [想要获取的字段]
     * @return array [如果存在，就返回用户对象数组]
     */
    public function getUserInfoByIDs($ids, $fields = "`id`, `user_type`, `mobile`, `real_name`, `user_name`")
    {
        $userInfoArr = array();
        if (!is_array($ids)) {
            return $userInfoArr;
        }

        // 去除ids重复值
        $ids = array_unique($ids);

        $idsStr = implode(",", $ids);
        $condition = " `id` IN (%s)";
        $condition = sprintf($condition, $this->escape($idsStr));

        $userInfoArr = $this->findAllViaSlave($condition, true, $fields);

        return $userInfoArr;
    }

     /* 根据siteid获取用户
     * @param int $site_id
     * @return model
     */
    public function getUserBySiteId($site_id = 1, $offset = 0, $count = 10, $updateTime = 0, $sortType = 0)
    {
        $params = array();
        $site_id = intval($site_id);
        $condition = " site_id = :site_id ";
        if (intval($updateTime) > 0) {
            $condition .= " AND (create_time > :update OR update_time > :update) ";
            if ($sortType == 1) {
                $condition .= " ORDER BY update_time ASC ";
            } else {
                $condition .= " ORDER BY update_time DESC ";
            }
            $params[':update'] = $updateTime;
        } elseif ($sortType == 1) {
            $condition .= " ORDER BY id ASC ";
        } else {
            $condition .= " ORDER BY id DESC ";
        }

        $condition .= " LIMIT :offset, :count ";
        $params[':site_id'] = intval($site_id);
        $params[':offset'] = intval($offset);
        $params[':count'] = intval($count);

        $ret = $this->findAllViaSlave($condition, true, '*', $params);
        return empty($ret) ? false : $ret;
    }

    public function getUserByMobileOrIdno($mobile, $idno, $fields = '*') {
        // 身份证号采用加密存储，统一使用大写的X后缀
        $idno = strtoupper($this->escape($idno));
        $mobile = $this->escape($mobile);

        if (!empty($mobile) && !empty($idno)) {
            $conds = "mobile = '".$mobile."' OR idno = '".$idno."'";
        } elseif (!empty($mobile)) {
            $conds = "mobile = '".$mobile."'";
        } elseif (!empty($idno)) {
            $conds = "idno = '".$idno."'";
        } else {
            return false;
        }

        return $this->findAllViaSlave($conds, true,  $fields);
    }

    public function getUserByRealName($realName, $fields = '*') {
        $realName = $this->escape($realName);

        if (!empty($realName)) {
            $conds = " real_name = '".$realName."'";
        } else {
            return false;
        }

        return $this->findAllViaSlave($conds, true,  $fields);
    }

    public function webUnionUserDel($mobile)
    {
        $runSql = "Delete From firstp2p_user where mobile='".$mobile."'";
        return $GLOBALS['db']->query($runSql);
    }


    public function isEnterpriseUser($userId)
    {
        $user = $this->find($userId, 'mobile, user_type, mobile_code', true);
        // 判断用户是否是企业用户:1.用户类型为企业用户;2.手机号国别为86且手机号首位为6 jira4883
        if ((!empty($user['mobile']) && substr($user['mobile'], 0, 1) == 6 && $user['mobile_code'] == '86') || (isset($user['user_type']) && $user['user_type'] == self::USER_TYPE_ENTERPRISE)) {
            return true;
        }
        return false;
    }
    /**
     * 更新用户存管系统id
     * @param int $supervisionUserId
     */
    public function updateSupervisionUserId($supervisionUserId) {
        return $this->updateBy(array('supervision_user_id'=>$supervisionUserId, 'update_time'=>get_gmtime()), sprintf('id=%d', $supervisionUserId));
    }

    /**
     * 把用户注销账户
     * @param int $userId
     */
    public function setUserCancel($userId) {
        $this->updateBy(array('is_effect'=>0, 'idno'=>'', 'idcardpassed'=>0, 'mobile'=>'', 'mobilepassed'=>0, 'payment_user_id'=>0, 'supervision_user_id'=>0, 'update_time'=>get_gmtime()), sprintf('id=%d', intval($userId)));
        return $this->db->affected_rows() > 0 ? true : false;
    }

    public function getUserByWangxinPPid($ppId) {
        $localPassportInfo = WangxinPassportModel::instance()->getPassportByPPid($ppId);
        if (empty($localPassportInfo)) {
            return false;
        }
        return $this->find($localPassportInfo['user_id'], 'id, user_name', true);
    }
    /**
     * 修改用户实名信息
     */
    public function updateUserIdentity($userId, $realName, $idType, $idno) {
        return $this->updateBy(array('real_name'=>$realName, 'id_type'=>$idType, 'idno'=>$idno, 'update_time'=>get_gmtime()), sprintf('id=%d', $userId));
    }

    /**
     * 生成业务token
     */
    private function generateBizToken($logInfo, $bizToken) {

        if (empty($bizToken)) {
            return '';
        }

        foreach($bizToken as $key => $value) {
            $bizToken[$key] = strval($value);
        }

        return json_encode($bizToken);
    }

    /**
     * 获取用户银行卡信息, 挪到UserModel下
     * @return array of UserBankcardModel
     */
    public function getUserBankCard()
    {
        return UserBankcardModel::instance()->getNewCardByUserId($this->id);
    }
} // END class UserModel extends BaseModel
