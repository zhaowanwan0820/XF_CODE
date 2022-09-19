<?php
/**
 * MoneyApplyModel class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\UserModel;
use core\dao\FinanceQueueModel;

/**
 * MoneyApplyModel class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class MoneyApplyModel extends BaseModel {
    const TYPE_APPLY = 1;
    const TYPE_PASSED = 2;
    const TYPE_FAILED = 3;

    const STATUS_APPLY = 0;
    const STATUS_PASSED = 1;
    const STATUS_PAYED = 2;

    /**
     * 批准或拒绝线下充值申请
     * @param int $id
     * @param bool $is_passed true-批准 false-拒绝
     * @userLock
     * @return array
     */
    public function verifyMoneyApply($id, $is_passed) {
        $id = intval($id);
        $type = $is_passed ? self::TYPE_PASSED : self::TYPE_FAILED;
        $res_arr = array(
            "res" => false,
            "msg" => "",
        );

        $this->db->startTrans();
        try {
            $condition = "`id`='%d' AND `type`='%d'";
            $condition = sprintf($condition, $this->escape($id), self::TYPE_APPLY);
            $apply_info = $this->findBy($condition);
            if (empty($apply_info)) {
                throw new \Exception('申请不存在');
            }
            if ($apply_info['status'] != self::STATUS_APPLY) {
                throw new \Exception('该申请已失效');
            }
            $_toUpdate = array();
            $adm_info = \es_session::get(md5(conf("AUTH_KEY")));
            $_toUpdate['user_id'] = $apply_info['user_id'];
            $_toUpdate['parent_id'] = intval($apply_info['id']);
            $_toUpdate['money'] = $apply_info['money'];
            $_toUpdate['admin_id'] = $adm_info['adm_id'];
            $_toUpdate['type'] = $type;
            $_toUpdate['time'] = get_gmtime();
            $_toUpdate['create_time'] = get_gmtime();
            $GLOBALS['db']->autoExecute('firstp2p_money_apply', $_toUpdate, 'INSERT');
            $affectRows = $GLOBALS['db']->affected_rows();
            if ($affectRows <= 0) {
                throw new \Exception('创建新的充值记录失败');
            }
            $_toUpdateOld = array();
            $user_info = UserModel::instance()->find($apply_info['user_id']);
            $log = $adm_info['adm_name'] . $GLOBALS['dict']['MONEY_APPLY_TYPE'][$type] . "修改" . $user_info['name'] . "账户余额" . format_price($apply_info['money']);
            if (!$is_passed) {
                $_toUpdateOld['status'] = self::STATUS_PASSED;
                $res_arr['msg'] = "申请已拒绝";
                $res_arr['res'] = true;
            } else {
                $note = empty($apply_info['note']) ? '管理员代充值' : addslashes($apply_info['note']);
                $_changeResult = $user_info->changeMoney($apply_info['money'], "充值", $note, $adm_info['adm_id'], 0, 0, 1); //加钱不判断余额为负
                if (!$_changeResult) {
                    throw new \Exception('修改用户余额失败');
                }
                $_toUpdateOld['status'] = self::STATUS_PAYED;
                $res_arr['msg'] = "申请已批准，用户账户余额已修改";
                $res_arr['res'] = true;
            }
            $_toUpdateOld['update_time'] = get_gmtime();
            $GLOBALS['db']->autoExecute('firstp2p_money_apply', $_toUpdateOld, 'UPDATE', $condition . ' AND status = '. self::STATUS_APPLY);
            $affectRows = $GLOBALS['db']->affected_rows();
            if ($affectRows <= 0 ) {
                throw new \Exception('保存批准状态失败');
            }

            $_saveResult = save_log($log, 1, var_export($_toUpdate, true));
            if (!$_saveResult) {
                throw new \Exception('保存操作日志失败');
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            $res_arr['res'] = false;
            $res_arr['msg'] = '操作失败，' . $e->getMessage();
        }
        return $res_arr;
    }
}
