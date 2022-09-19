<?php
/**
 * User class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace app\models\dao;

/**
 * 用户信息
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class User extends BaseModel
{
    /**
     * 直接进行资金变动
     * @param float $money 金额
     * @param string $message 类型
     * @param string $note 备注
     * @param int $admin_id 管理员id
     * @param int $is_manage 是否是管理费
     * @return boolean
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    public function changeMoney($money, $message, $note, $admin_id = 0, $is_manage = 0)
    {
        if ($is_manage == 0) { // 若为管理费，则只发消息不变更账户余额
            $r = $this->db->query("UPDATE " . $this->tableName() . " SET `money`=`money`+'" . floatval($money) . "' WHERE `id`='" . $this->id . "'");
            if (!$r) {
                return false;
            }
        }

	    $row = $this->find($this->id);
	
        //记录资金变动日志
        $user_log = new UserLog();
        $user_log->log_info = $message;
        $user_log->note = $note;
        $user_log->log_time = get_gmtime();
        $user_log->log_admin_id = $admin_id;
        $user_log->log_user_id = $this->id;
        $user_log->user_id = $this->id;
        $user_log->money = floatval($money);
	    $user_log->remaining_money = $row->money;
	    $user_log->remaining_total_money = $row->money + $row->lock_money;
        if(!$user_log->save()){
            return false;
        }
        return true;
    }
} // END class User extends BaseModel
