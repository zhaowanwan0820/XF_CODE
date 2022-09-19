<?php
/**
 * UserBankcardModel class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;
use core\dao\UserModel;

/**
 * 用户银行卡
 *
 * @author wenyanlei@ucfgroup.com
 **/
class UserBankcardModel extends BaseModel
{
    const STATUS_UNBIND = 0; // 未绑卡
    const STATUS_BINDED = 1; // 已绑卡

    const VERIFY_STATUS_UNVALIDATE = 0; //未验卡
    const VERIFY_STATUS_VALIDATED = 1; // 已验卡
    const VERIFY_STATUS_VALIDATING = 2; // 验卡中

    const CARD_TYPE_PERSONAL = 0; // 对私卡
    const CARD_TYPE_BUSINESS = 1; // 对公卡


    public static $cert_status_map = array(
        'EXTERNAL_CERT'     => 1, //IVR语音认证
        'FASTPAY_CERT'      => 2, //快捷认证(四要素认证)
        'TRANSFER_CERT'     => 3, //转账认证
        'WHITELIST_CERT'    => 4, //白名单
        'REMIT_CERT'        => 5, //打款认证
        'ONLY_CARD'         => 6, //卡密认证
        'AUDIT_CERT'        => 7, //人工认证
        'NO_CERT'           => 8, //未认证
        'MER_WHIT_CERT'     => 9, // 商户白名单认证
        'INSIDE_CERT'       => 10, // 内部认证
    );

    /**
     * 获取单条用户银行卡记录
     * @param string $where
     */
    public function getUserBankCardRow($condition='') {
        $result = $this->findBy($condition . " LIMIT 1 ", '*', array(), true);
        return $result;
    }

    public function getCardByUser($user_id, $field="*", $is_slave = true) {
        //获取用户银行卡信息
        $condition = "user_id=:user_id";
        $result = $this->findBy($condition, $field, array(':user_id' => $user_id), $is_slave);
        return $result;
    }

    public function getBankcardByUserIdArr($userIdArr, $field="*", $is_slave = true) {
        $condition = "user_id IN (:userIdArr) ";
        return $this->findAll($condition, $is_slave, $field, array(':userIdArr' => implode(',', $userIdArr)));
    }

    public function getOneCardByUser($user_id, $is_slave = true) {
        $condition = "user_id=:user_id LIMIT 1";
        $result = $this->findBy($condition, '*', array(':user_id' => $user_id), $is_slave);
        return $result;
    }

    public function getNewCardByUserId($user_id, $field='*', $is_slave=true) {
        $condition = 'user_id=:user_id ORDER BY id DESC LIMIT 1';
        $result = $this->findBy($condition, $field, array(':user_id' => $user_id), $is_slave);
        return $result;
    }

    /**
     * 插入用户绑卡记录
     * @param array $data
     */
    public function insertCard($data) {
        return $this->db->autoExecute($this->tableName(), $data, 'INSERT');
    }

    /**
     * 更新用户绑卡记录
     * @param int $id
     * @param array $data
     */
    public function updateCard($id, $data) {
        return $this->db->autoExecute($this->tableName(), $data, "UPDATE", 'id = '.$this->escape($id));
    }

    /**
     * 更新用户绑卡记录
     * @param integer $userId 用户ID
     * @param array $data
     * @return bool
     */
    public function updateCardByUserId($userId, $data) {
        $this->db->autoExecute($this->tableName(), $data, "UPDATE", 'user_id = '.$this->escape($userId));
        $rows = $this->db->affected_rows();
        return $rows >= 1 ? true : false;
    }


    /**
     * 更新用户认证类型
     * @param integer $userId 用户ID
     * @param integer $cardNo 卡号
     * @param string  $cert 认证类型 非数字
     * @return bool
     */
    public function updateCertStatusByUserIdAndCardNo($userId, $cardNo, $cert) {
        $cert_status = isset(self::$cert_status_map[$cert]) ? self::$cert_status_map[$cert] : 0;
        $data = array(
            'cert_status' => $cert_status
        );
        $this->db->autoExecute($this->tableName(), $data, "UPDATE", "user_id = '" . $this->escape($userId) . "' and bankcard = '" . $this->escape($cardNo) . "'");
        $rows = $this->db->affected_rows();
        return $rows >= 1 ? true : false;
    }

    /**
     *根据userId获取对象
     */
    public function getByUserId($userId){
        if(empty($userId)){
            throw new \Exception('$userId不能为空');
        }
        $condition = ' user_id = '.intval($userId);
        $result = $this->findBy($condition . " LIMIT 1 " );
        return $result;
    }

    /**
     * getUsersByRegions
     * 根据地区返回银行卡属于该地区的所有用户ID
     *
     * @param mixed $regions
     * @access public
     * @return void
     */
    public function getUsersByRegions($regions) {
        if (empty($regions)) {
            return null;
        }
        $condition = "region_lv3 IN (:region)";
        return $this->findAll($condition, true, 'user_id', array(":region" => $regions));
    }

    /**
     * getValidRegionIds
     * 获取当前有效地区ID
     *
     * @access public
     * @return void
     */
    public function getValidRegionIds() {
        return $this->findAll("", true, "DISTINCT region_lv3 AS rid");
    }

    /**
     * getMorInfoByUser
     * 获取用户绑定银行卡的银行编码等信息
     *
     * @param mixed $uid
     * @access public
     * @return void
     */
    public function getMorInfoByUser($uid) {
        $query = "SELECT ub.bankcard, b.name, bc.value, bc.short_name FROM firstp2p_user_bankcard ub, firstp2p_bank b, firstp2p_bank_charge bc WHERE ub.user_id=:uid AND ub.bank_id=b.id AND b.name=bc.name";
        return $this->findBySql($query, array(':uid' => $uid));
    }

    /**
     * usedByOthers
     * 查看指定卡号是否被指定用户组外其他用户使用
     *
     * @param mixed $bankcard
     * @param mixed $uids
     * @access public
     * @return void
     */
    public function usedByOthers($bankcard, $uids) {
        $condition = "bankcard=':bankcard' AND user_id NOT IN(:uids)";
        $params = array(
                        ':bankcard' => $bankcard,
                        ':uids' => $uids,
                    );
        return $this->count($condition, $params);
    }

    /**
     * 根据银行卡号获取单条信息
     * @param string $cardnum
     */
    public function getRowByCardNum($cardnum){
        $condition = "`bankcard`=':bankcard' LIMIT 1";
        $result = $this->findBy($condition, '*', array(':bankcard' => $cardnum));
        return $result;
    }

    /**
     * 获得某个银行卡号的绑定数量
     *
     * @param string $cardnum
     * @param string $userids user表已删除的记录id
     */
    public function getCountByCardNum($cardnum, $userids){
        $condition = "user_id not in (:userids) and bankcard = ':bankcard'";
        $params = array(':userids' => $userids, ':bankcard' => $cardnum);
        return $this->count($condition, $params);
    }

    /**
     * 读取用户已绑卡但是银行卡名称为空的银行卡列表
     * @return array
     */
    public function getEmptyBankNameList() {
        $condition = ' bank_id = 0 AND status = 1 ';
        return $this->findAll($condition, true, 'id,user_id,bankcard');
    }

    /**
     * 删除用户绑卡记录
     * @param string $userId 用户Id
     * @return boolean
     */
    public function unbindCard($userId) {
        $db = \libs\db\Db::getInstance('firstp2p');
        $result = $db->query("DELETE FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'");
        return $result;
    }

    /**
     * 根据银行联行号码获取绑卡的用户列表
     * @param int $bankId
     * @param array $userIds
     */
    public function getBankCardListByBankUserId($bankId, $userIds) {
        if (empty($bankId) && empty($userIds)) {
            return [];
        }
        return $this->findAllViaSlave(sprintf('bank_id=%d AND user_id IN (%s)', (int)$bankId, join(',', $userIds)), true);
    }

    /**
     * 更新四要素认证状态
     */
    public function updateVerifyStatusByUserIdAndCardNo($userId, $cardNo, $verifyStatus = 1) {
        $data = array(
            'verify_status' => $verifyStatus
        );
        return $this->db->autoExecute($this->tableName(), $data, "UPDATE", "user_id = '" . $this->escape($userId) . "' and bankcard = '" . $this->escape($cardNo) . "'");
    }

    /**
     * [根据ids批量获取用户银行信息]
     * @param array $userIds [用户id数组]
     * @param string $fields [想要获取的字段]
     * @return array [如果存在，就返回用户银行卡列表]
     */
    public function getBankListByUserIds($userIds, $fields = '*') {
        $bankList = array();
        if (!is_array($userIds)) {
            return $bankList;
        }

        // 去除userIds重复值
        $userIds = array_unique($userIds);

        $userIdsStr = implode(',', $userIds);
        $condition = sprintf('`user_id` IN (%s)', $this->escape($userIdsStr));
        $bankList = $this->findAllViaSlave($condition, true, $fields);

        return $bankList;
    }
} // END class UserBankcardModel extends BaseModel
