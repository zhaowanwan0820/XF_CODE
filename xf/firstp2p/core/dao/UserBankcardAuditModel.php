<?php
/**
 * UserBankcardAuditModel class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;

use core\dao\UserBankcardModel;
use core\service\AccountService;

/**
 * 用户银行卡审核
 *
 * @author wenyanlei@ucfgroup.com
 **/
class UserBankcardAuditModel extends BaseModel
{

    public function getlatestCardAuditByUser($user_id, $is_slave = true) {
        $condition = "user_id=:user_id ORDER BY id DESC LIMIT 1";
        return $this->findBy($condition, '*', array(":user_id" => $user_id), $is_slave);
    }
    
    /**
     * usedByOthers 
     * 查看指定卡号是否被指定用户组外其他用户使用且处于审核中状态
     * 
     * @param mixed $bankcard 
     * @param mixed $uids 
     * @access public
     * @return void
     */
    public function usedByOthers($bankcard, $uids) {
        $condition = "bankcard=':bankcard' AND status=1 AND user_id NOT IN(:uids)";
        $params = array(
                        ':bankcard' => $bankcard,
                        ':uids' => $uids,
                    );
        return $this->count($condition, $params);
    }

    /**
     * 获取银行卡号在审核中的记录
     * @param string $cardnum
     */
    public function getRowByCardNum($cardnum){
        $condition = "`bankcard`=':bankcard' AND status = 1 LIMIT 1";
        return $this->findBy($condition, '*', array(':bankcard' => $cardnum));
    }

    /**
     * 获得某个银行卡号的提交审核数量
     *
     * @param string $cardnum
     * @param string $userids user表已删除的记录id
     */
    public function getCountByCardNum($cardnum, $userids){
        $condition = array(':userids' => $userids, ':bankcard' => $cardnum);
        return $this->count("user_id not in (:userids) and bankcard = ':bankcard' and status = 1", $condition);
    }

    /**
     * 清除用户提交的换卡记录请求
     * @param string $userId 用户id
     * @param string $bankcardNo 用户银行卡号
     * @return boolean
     */
    public function clearBankcardAudit($userId, $bankcardNo) {
        $db =\libs\db\Db::getInstance('firstp2p');
        $db->query("DELETE FROM firstp2p_user_bankcard_audit WHERE user_id = '{$userId}' AND bankcard = '{$bankcardNo}'");
        return $db->affected_rows() > 0 ? true : false;
    }

    /**
     * 自动审批
     */
    public function autoAudit($params) {
        if (empty($params)) {
            return false;
        }

        $db = \libs\db\Db::getInstance('firstp2p');
        $userId = intval($params['id']);

        //申请时总资产
        $accountService = new AccountService();
        $totalAssets = $accountService->getUserTotalAssets($userId);

        $userBankcardInfo = array(
            'card_name' => addslashes($params['bank_card_name']), //开户姓名
            'region_lv1' => intval($params['c_region_lv1']),
            'region_lv2' => intval($params['c_region_lv2']),
            'region_lv3' => intval($params['c_region_lv3']),
            'region_lv4' => intval($params['c_region_lv4']),
            'bankzone' => !empty($params['bankzone_1']) ? addslashes($params['bankzone_1']) : addslashes($params['bank_bankzone']),
            'bankcard' => addslashes($params['bank_bankcard']), //处理卡号 只能是数字
            'bank_id' => intval($params['bank_id']),
            'user_id' => $userId,
            'card_type' => (int)$params['card_type'] == 0 ? UserBankcardModel::CARD_TYPE_PERSONAL : UserBankcardModel::CARD_TYPE_BUSINESS, // 银行卡类型
            'auto_audit' => 1,//自动审批
            'fastpay_cert_status' => $params['cert_status'],//认证类型
            'status' => 3,//审批通过
            'verify_status' => 1,
            'create_time' => get_gmtime(),
            'audit_time' => get_gmtime(),
            'total_assets'=>$totalAssets
        );

        $mode = 'INSERT';
        $condition = '';
        return $db->autoExecute('firstp2p_user_bankcard_audit', $userBankcardInfo, $mode, $condition);
    }

} // END class UserBankcardModel extends BaseModel
