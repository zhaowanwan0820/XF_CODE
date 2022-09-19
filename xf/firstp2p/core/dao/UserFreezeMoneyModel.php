<?php
/**
 * UserFreezeMoneyModel class file.
 * @author 王群强 <wagnqunqiang@ucfgroup.com>
 **/

namespace core\dao;

use core\dao\UserModel;

class UserFreezeMoneyModel extends BaseModel {
    const TYPE_APPLY = 1;
    const TYPE_PASSED = 2;
    const TYPE_FAILED = 3;

    const STATUS_APPLY = 0;
    const STATUS_PASSED = 1;
    const STATUS_REFUSED = 2;

    /**
     * 批准或拒绝冻结申请
     * @param int $id
     * @param bool $is_passed true-批准 false-拒绝
     * @userLock
     * @return array
     */
    public function verifyFreezeMoney($id, $is_passed) {
        $id = intval($id);
        $type = $is_passed ? self::STATUS_PASSED : self::STATUS_REFUSED;
        $res_arr = array(
            "res" => false,
            "msg" => "",
        );

        $this->db->startTrans();
        try {
            $condition = "`id`='%d' AND `status`='%d'";
            $condition = sprintf($condition, $this->escape($id), self::STATUS_APPLY);
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
            $_toUpdate['time'] = time();
            $_toUpdate['create_time'] = time();
            $_toUpdateOld = array();
            $user_info = UserModel::instance()->find($apply_info['user_id']);
            $log = $adm_info['adm_name'] . $GLOBALS['dict']['MONEY_APPLY_TYPE'][$type] . "修改" . $user_info['name'] . "账户冻结金额" . format_price($apply_info['money']);
            if (!$is_passed) {
                $_toUpdateOld['status'] = self::STATUS_REFUSED;
                $_toUpdateOld['memo'] = sprintf('%s <br/>拒绝 %s', date('Y-m-d H:i:s'), $adm_info['adm_name']);
                $res_arr['msg'] = "申请已拒绝";
                $res_arr['res'] = true;
            } else {
                $note = empty($apply_info['note']) ? '管理员编辑账户' : addslashes($apply_info['note']);
                $opType = '';
                $_moneyChange = $apply_info['money'];
                $user_info->changeMoneyAsyn = true;
                $_changeResult = $user_info->changeMoney($_moneyChange ,"管理员编辑账户", $note, $adm_info['adm_id'], 0, UserModel::TYPE_LOCK_MONEY);
                $user_info->changeMoneyAsyn = false;
                if (!$_changeResult) {
                    throw new \Exception('冻结/解冻用户余额失败');
                }
                $_toUpdateOld['status'] = self::STATUS_PASSED;
                $_toUpdateOld['memo'] = sprintf('%s <br/>批准 %s', date('Y-m-d H:i:s'), $adm_info['adm_name']);
                $res_arr['msg'] = "申请已批准，用户账户余额已冻结/解冻";
                $res_arr['res'] = true;
            }
            $_toUpdateOld['update_time'] = time();
            $GLOBALS['db']->autoExecute('firstp2p_user_freeze_money', $_toUpdateOld, 'UPDATE', $condition . ' AND status = '. self::STATUS_APPLY);
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
