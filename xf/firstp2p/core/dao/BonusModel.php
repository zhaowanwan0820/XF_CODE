<?php
/**
 * BonusModel class file.
 *
 * @author wangshijie@ucfgroup.com
 */

namespace core\dao;

use libs\utils\Logger;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\Bonus\AcquireBonusEvent;
use core\dao\JobsModel;

/**
 * 红包
 *
 * @author wangshijie@ucfgroup.com
 */
class BonusModel extends BaseModel
{
    const TYPE_GET = 1; //获取到得红包类型
    const TYPE_SEND = 2; //发送的红包类型

    // 单个红包类型
    const BONUS_NORMAL = 0;
    const BONUS_FIRST_DEAL_FOR_DEAL = 1;
    const BONUS_FIRST_DEAL_FOR_INVITE = 2;
    const BONUS_REGISTER_FOR_NEW = 3;
    const BONUS_REGISTER_FOR_INVITE = 4;
    const BONUS_BINDCARD_FOR_NEW = 5;
    const BONUS_BINDCARD_FOR_INVITE = 6;
    const BONUS_CASH_FOR_NEW = 7;
    const BONUS_CASH_FOR_INVITE = 8;
    const BONUS_PLATFORM_AWARD = 9;
    const BONUS_EVENT_AWARD = 10;
    const BONUS_TASK = 11;
    const BONUS_HYLAIWU = 12;
    const BONUS_O2O_ACQUIRE_FOR_INVITER = 13;
    const BONUS_O2O_CONFIRMED_REBATE = 14;
    const BONUS_CASH_NORMAL_FOR_NEW = 15;
    const BONUS_YAOYIYAO = 16;
    const BONUS_GAME_CIRCLE = 19;
    const BONUS_O2O_ACQUIRE_FOR_USER = 20;
    const BONUS_STOCK = 21; //股票基金
    const BONUS_GOLD_COIN = 22; //换金币
    const BONUS_ACTIVITY = 23;
    const BONUS_DAYDAYYAO = 24; // 天天摇合作红包
    const BONUS_SUBSITE_SIGN = 25; // 分站签到红包
    const BONUS_DISCOUNT_REBATE = 26; // 返现券奖励
    const BONUS_LCS_RANDOM = 27; // 理财师随机红包
    const BONUS_LCS_AVERAGE = 28; // 理财师等额红包
    const BONUS_DISCOUNT_RAISE_RATE = 29;  // 加息券奖励
    const BONUS_REFUND = 30;  // 买红包退款红包
    const BONUS_BIRTHDAY_LCS = 31;  // 购买的生日红包（显示为理财师）
    const BONUS_BIRTHDAY = 32;  // 购买的生日红包（显示为平台）
    const BONUS_LCS_RANDOM_LCS = 33; // 理财师随机红包(显示理财师)
    const BONUS_LCS_AVERAGE_LCS = 34; // 理财师等额红包(显示理财师)
    const BONUS_COUPON = 35; // 返利红包
    const BONUS_NEW_ROLLBACK = 36; // 新系统回滚红包
    const BONUS_DISCOUNT_GOLD = 37; // 黄金券
    const BONUS_WEIXIN = 38; // 微信领红包

    static $typeConfig = array(
        BonusModel::BONUS_FIRST_DEAL_FOR_DEAL => '首投奖励',
        BonusModel::BONUS_FIRST_DEAL_FOR_INVITE => '邀请投资奖励',
        BonusModel::BONUS_REGISTER_FOR_NEW => '注册奖励',
        BonusModel::BONUS_REGISTER_FOR_INVITE => '邀请注册奖励',
        BonusModel::BONUS_BINDCARD_FOR_NEW => '绑卡奖励',
        BonusModel::BONUS_BINDCARD_FOR_INVITE => '邀请绑卡奖励',
        BonusModel::BONUS_CASH_FOR_NEW => '新手注册奖励',
        BonusModel::BONUS_CASH_NORMAL_FOR_NEW => '新手注册奖励',
        BonusModel::BONUS_CASH_FOR_INVITE => '邀请注册奖励',
        BonusModel::BONUS_DISCOUNT_REBATE => '返现券奖励',
        BonusModel::BONUS_DISCOUNT_RAISE_RATE => '加息券奖励',
        BonusModel::BONUS_DISCOUNT_GOLD => '黄金券奖励'
    );

    /**
     * 红包类型对应中文名称
     * @var array
     */
    public static $nameConfig = array(
        BonusModel::BONUS_NORMAL => '普通红包',
        BonusModel::BONUS_FIRST_DEAL_FOR_DEAL => '首投奖励',
        BonusModel::BONUS_FIRST_DEAL_FOR_INVITE => '邀请投资奖励',
        BonusModel::BONUS_CASH_FOR_NEW => '新手注册奖励3.0',
        BonusModel::BONUS_CASH_NORMAL_FOR_NEW => '新手注册奖励3.1',
        BonusModel::BONUS_HYLAIWU => '汇源项目支持',
        BonusModel::BONUS_EVENT_AWARD => '活动奖励',
        BonusModel::BONUS_TASK => '红包任务',
        BonusModel::BONUS_CASH_FOR_INVITE => '邀请注册奖励',
        BonusModel::BONUS_GAME_CIRCLE => '游戏红包',
        BonusModel::BONUS_STOCK => '股票基金红包',
        BonusModel::BONUS_YAOYIYAO => '摇一摇红包',
        BonusModel::BONUS_DAYDAYYAO => '天天摇红包',
        BonusModel::BONUS_O2O_ACQUIRE_FOR_INVITER => '邀请投资奖励|O2O',
        BonusModel::BONUS_O2O_ACQUIRE_FOR_USER => '首投奖励|O2O',
        BonusModel::BONUS_O2O_CONFIRMED_REBATE => '兑券奖励|O2O',
        BonusModel::BONUS_SUBSITE_SIGN => '分站签到红包',
        BonusModel::BONUS_LCS_RANDOM => '理财师随机红包',
        BonusModel::BONUS_LCS_AVERAGE => '理财师等额红包',
        BonusModel::BONUS_REFUND => '退款红包',
        BonusModel::BONUS_DISCOUNT_REBATE => '返现券奖励',
        BonusModel::BONUS_DISCOUNT_RAISE_RATE => '加息券奖励',
        BonusModel::BONUS_DISCOUNT_GOLD => '黄金券奖励'
    );

    // 返利状态
    // 未返利
    const UN_REBATE = 0;
    // 成功返利
    const REBATE_SUCCESS = 1;
    // 无法返利
    const CANNOT_REBATE = 2;

    /**
     * 生成红包记录
     */
    public function insert_batch($sender_uid, $group_id, $bonuses, $bonusType = 0, $taskId = 0) {
        $values = array();
        foreach ($bonuses as $bonus) {
            $values[] = "($sender_uid, $group_id, $bonus, $bonusType, $taskId)";
        }
        $sql = 'INSERT INTO %s (`sender_uid`, `group_id`, `money`, `type`, `task_id`) VALUES %s';
        $sql = sprintf($sql, 'firstp2p_bonus', implode(', ', $values));
        return $this->updateRows($sql);
    }

    /**
     * 非预生成红包，用户获取红包直接插入红包
     */
    public function single_bonus($group_id, $sender_uid, $owner_uid, $mobile, $status, $money,
                                 $created_at, $expired_at, $openid, $referMobile,
                                 $type = self::BONUS_NORMAL, $replaceBonusID = 0, $taskId = 0, $syncSwitch = true, $info ='') {
        // if (\core\dao\UserModel::instance()->isEnterpriseUser($owner_id)) {
        //     return false;
        // }
        $expired_at = strtotime(date("Y-m-d", $expired_at)) + (86400 - 1);
        if ($replaceBonusID > 0) {
            return $this->updateSingleBonus($replaceBonusID, $group_id, $sender_uid, $owner_uid,
                $mobile, $status, $money, $created_at, $expired_at, $openid, $referMobile, $type, $taskId);
        }
        $sql = 'INSERT INTO %s (group_id, sender_uid, owner_uid, mobile, status, money, created_at, expired_at, openid, refer_mobile, type, task_id) VALUES (%s, "%s", "%s", "%s", %s, %s, %s, %s, "%s", "%s", %s, %s)';
        $sql = sprintf($sql, 'firstp2p_bonus', $group_id, $sender_uid, $owner_uid, $mobile, $status, $money, $created_at, $expired_at, $openid, $referMobile, $type, $taskId);

        // 开启事务
        $this->db->startTrans();
        try {
            $this->updateRows($sql);
            $bonusId = $this->db->insert_id();
            if ($syncSwitch && $owner_uid > 0) {
                $jobsModel = new JobsModel();
                $param = array('bonusId' => $bonusId, 'info' => $info);
                $jobsModel->priority = 150;
                $r = $jobsModel->addJob('\core\service\BonusService::syncSingleBonus', $param);
                if ($r === false) {
                    throw new \Exception("BonusSingleModelTransFailed:$sql");
                }
            }
            $this->db->commit();
            return $bonusId;
        } catch (\Exception $e) {
            $this->db->rollback();
            Logger::info("BonusDataToNewService:BonusModel::single_bonus:exception:".json_encode($e));
            return false;
        }
    }

    /**
     * 更新红包记录
     */
    public function updateSingleBonus($bonusID, $group_id, $sender_uid, $owner_uid,
        $mobile, $status, $money, $created_at, $expired_at, $openid, $referMobile,
        $type = self::BONUS_NORMAL, $taskId = 0)
    {
        $sql = "UPDATE `%s` SET `group_id` = %s, `sender_uid` = '%s', `owner_uid` = '%s', `mobile` = '%s',
                    `status` = %s, `money` = %s, `created_at` = %s, `expired_at` = %s, `openid` = '%s',
                    `refer_mobile` = '%s', `type` = %s, `task_id` = %s WHERE `id` = %s";
        $sql = sprintf($sql, 'firstp2p_bonus', $group_id, $sender_uid, $owner_uid, $mobile,
            $status, $money, $created_at, $expired_at, $openid, $referMobile, $type, $taskId, $bonusID);
        return $this->updateRows($sql);
    }

    /**
     * 生成单条返利红包记录（无红包组）
     * @param int $owner_id 所属用户
     * @param float $money 红包金额
     * @param int $expired_day 过期天数
     * @return bool
     */
    public function insert_one($owner_id, $money, $expired_day, $type = 0, $mobile = '') {
        $user = \core\dao\UserModel::instance()->find($owner_id);
        if ($user['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE || substr($user['mobile'], 0, 1) == '6') {
            return false;
        }

        $this->group_id = 0;
        $this->sender_uid = 0;
        $this->owner_uid = $owner_id;
        $this->mobile = $mobile;
        $this->money = $money;
        $this->status = 1;
        $this->created_at = time();
        $this->mobile = $mobile;
        if ($type > 0) {
            $this->expired_at = $this->created_at + 86400 * $expired_day;
            $this->type = $type;
        } else {
            $this->expired_at = strtotime("+{$expired_day} days", mktime(0, 0, 0));
        }
        $this->expired_at = strtotime(date("Y-m-d", $this->expired_at)) + (86400 - 1);
        $this->insert();
        $bonusId = $this->id;
        $taskId = (new GTaskService())->doBackground((new AcquireBonusEvent($bonusId)), 20);
        Logger::info(array('owner_id' => $owner_id, 'money' => $money, 'expired_day' => $expired_day, 'type' => $type, 'mobile' => $mobile, 'taskId' => $taskId));
        return $bonusId;
    }

    /**
     * 批量插入红包
     * @param  int $groupID
     * @param  array  $bonusInfo
     */
    public function insert_all($groupID, $bonusInfo = [])
    {
        $sql = 'INSERT INTO `firstp2p_bonus` (`group_id`, `sender_uid`, `owner_uid`, `mobile`, `status`, `money`, `created_at`, `expired_at`, `type`) VALUES ';
        $values = [];
        $createdAt = time();
        foreach ($bonusInfo as $info) {
            $expiredAt = $createdAt + 86400 * $info['expireDay'];
            $values[] = "({$groupID}, {$info['senderID']}, {$info['ownerID']}, {$info['mobile']}, 1, {$info['money']}, {$createdAt}, {$expiredAt}, {$info['type']})";
        }
        $sql .= implode(',', $values);
        return $this->updateRows($sql);
    }

    /**
     * 抢红包数据库中存储的字段status(0未领取1已领取2已使用)
     */
    public function collection($group_id, $mobile, $owner_uid, $created_at, $expired_at, $openid = '', $referMobile = '') {
        $table = 'firstp2p_bonus';
        $expired_at = strtotime(date("Y-m-d", $expired_at)) + (86400 - 1);
        $order_by = ($owner_uid == 0) ? ' ORDER BY `money` DESC ' : '';
        $sql = 'UPDATE %s SET `mobile`="%s", `owner_uid`=%s,`status`=%s, `created_at`=%s, `expired_at`=%s, `openid`="%s", `refer_mobile`="%s" WHERE `group_id`=%s AND `status`=%s AND `mobile`= "%s" %s LIMIT 1';
        $sql = sprintf($sql, $table, $mobile, intval($owner_uid), 1, $created_at, $expired_at, $openid, $refer_mobile, $group_id, 0, '', $order_by);
        return $this->updateRows($sql);
    }

    /**
     * 使用红包,改为已使用状态
     */
    public function update_record($ids) {
        $sql = 'UPDATE %s SET `status`=%d WHERE `id` IN (%s) LIMIT %d';
        $sql = sprintf($sql, $this->tableName(), 2, implode(',', $ids), count($ids));
        return $this->updateRows($sql);
    }

    /**
     * 获取红包记录，包含发送与获取到得红包, 获取红包使用mobile手机号获取，发送红包使用uid
     * @param string $mobile 获取红包用户的手机号
     * @param int $stauts 红包状态(1 未使用，未领取 2 已使用 3 已过期)|非数据库中状态
     * @param int $uid 发送者ID
     * @param int $type 类型
     * @param array 红包列表
     */
    public function get_list($user_id, $status, $type = self::TYPE_GET, $page_data = array(), $is_slave = true) {
        $condition = '';
        $params = array();
        $params[':user_id'] = $user_id;
        if ($type == self::TYPE_SEND) {
            $condition = '`sender_uid`=":user_id"';
        } else {
            $condition = '`owner_uid`=":user_id"';
        }
        if ($status == 2) {
            $params[':status'] = 2;
        } else {
            $condition .= $status == 3 ? ' AND `expired_at` < :expired_at' : ' AND `expired_at` > :expired_at';
            $params[':status'] = $type == self::TYPE_SEND ? 0 : 1;
            $params[':expired_at'] = time();
        }
        if ($status != 1) {
            $condition .= ' AND created_at >= ' . strtotime(date('Y-m-d', strtotime('-30 days')));
        }
        $condition .= ' AND `status`=:status ORDER BY expired_at ' . ($status == 1 ? 'ASC' : 'DESC');

        $count = $is_slave ? 'countViaSlave' : 'count';
        $findAll = $is_slave ? 'findAllViaSlave' : 'findAll';

        if($page_data['make_page']){
            $count = $this->$count($condition, $params);
            $condition .= ' LIMIT :start , :page_size';
            $params[':start'] = ($page_data['page'] - 1) * $page_data['page_size'];
            $params[':page_size'] = $page_data['page_size'];
            $list = $this->$findAll($condition, true, $this->get_fields(), $params);
            return array('count' => $count, 'list' => $list);
        }

        return $this->$findAll($condition, true, $this->get_fields(), $params);
    }

    /**
     * 获取各种状态红包的总额
     * @param unknown $group_id
     * @param unknown $status (1,已使用、2,未使用、3,已过期)
     * @return float
     */
    public function get_sum_money($group_id, $status){
        if($status == 1){
            $condition = '`status` != 0';
        }else{
            $condition = sprintf('`status` = %d', $status);
        }
        $sql = sprintf(
                "SELECT sum(`money`) AS sum_money FROM %s WHERE `group_id` = %d AND %s",
                $this->tableName(), $group_id, $condition
        );
        $res = $this->findBySql($sql, array(), true);
        return $res['sum_money'];
    }

    /**
     * 获取需要返回的字段
     */
    private function get_fields() {
        return "`id`, `group_id`, `sender_uid`, `owner_uid`, `mobile`, `status`, `money`, `created_at`, `expired_at`, `type`, `task_id`";
    }

    /**
     * get_user_sum_money
     *
     * @param array $args
     * @access public
     * @return float
     */
    public function get_user_sum_money($args = array(), $notExpired = true) {
        $condition = '';
        if ($args['status']) {
            $condition .= "status = {$args['status']}";
        } else {
            $condition .= "status > 0";
        }

        if (!empty($args['userId'])) {
            $condition .= " AND owner_uid = '{$args['userId']}'";
        }
        if (!empty($args['mobile'])) {
            $condition .= " AND mobile = '{$args['mobile']}'";
        }
        if (!empty($args['openid'])) {
            $condition .= " AND openid = '{$args['openid']}'";
        }

        if (!empty($args['type'])) {
            $condition .= " AND type = {$args['type']}";
        }

        if ($notExpired) {
            $condition .= ' AND expired_at > ' . time();
        }
        //红包将在24小时内过期的金额
        if ($args['endExpireTime']) {
            $condition .= " AND expired_at <= {$args['endExpireTime']}";
        }

        $sql = sprintf("SELECT sum(`money`) AS sum_money FROM %s WHERE %s", $this->tableName(), $condition);
        $res = $this->findBySqlViaSlave($sql);
        return $res['sum_money'] ? $res['sum_money'] : 0;
    }

    /**
     * 获取用户当天获取的首投返利红包
     */
    public function getRebateBonusCount($userId, $bonusType, $dealTime = 0) {

        $dateStart = $dealTime ? $dealTime : strtotime(date('Y-m-d'));
        $dateEnd = $dateStart + 3600*24;
        $condition = ' owner_uid = ' .$userId. ' AND type = ' .$bonusType
                     . ' AND created_at >= ' .$dateStart. ' AND created_at < ' .$dateEnd;
        return $this->count($condition);
    }

    /**
     * 获取可以使用的红包列表，保证可以使用的红包在最前
     */
    public function get_valid_bonus($user_id) {
        $sql = 'SELECT * FROM %s WHERE owner_uid = %s && status=1 && expired_at > %s ORDER BY expired_at ASC';
        $sql = sprintf($sql, 'firstp2p_bonus', intval($user_id), time());
        return $this->findAllBySql($sql, true, array(), true);
    }

    /**
     * 获取已经使用或者已经过期的红包
     */
    public function get_invalid_bonus($user_id, $start = 0, $limit = 10, $ids = array()) {
        if (!$user_id) {
            return false;
        }
        if (!is_array($ids)) {
            $ids = array(intval($ids));
        }
        $ids = implode(',', $ids);
        if (!empty($ids)) {
            $sql = sprintf('SELECT * FROM firstp2p_bonus WHERE owner_uid="%s" && id NOT IN(%s) && created_at >= %s ORDER BY expired_at DESC LIMIT %s,%s', $user_id, $ids, strtotime(date('Y-m-d', strtotime('-30 days'))), $start, $limit);
        } else {
            $sql = sprintf('SELECT * FROM firstp2p_bonus WHERE owner_uid="%s" && created_at >= %s ORDER BY expired_at DESC LIMIT %s,%s', $user_id, strtotime(date('Y-m-d', strtotime('-30 days'))), $start, $limit);
        }
        return $this->findAllBySql($sql, true, array(), true);
    }

    /**
     * 构造生成红包Item信息
     */
    public static function getAcquireItemInfo($bonus)
    {
        $itemType = $bonus['type'];
        if ($bonus['group_id'] > 0) {
            $itemId = $bonus['group_id'];
        } elseif (in_array($bonus['type'], array(11, 13, 14, 20, 26, 29, 30, 31, 32, 33, 34, 35))) {
            $itemId = $bonus['task_id'];
        } elseif (in_array($bonus['type'], array(2, 7, 8, 15))) {
            $itemId = $bonus['refer_mobile'];
        } else {
            $itemId = '';
        }
        $token = $bonus['id'];
        return [$token, $itemType, $itemId];
    }

}
