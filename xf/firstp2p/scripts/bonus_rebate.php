<?php
/**
 * 定时处理红包返利
 */
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';
use libs\utils\Logger;
use core\dao\BonusUsedModel;
use core\dao\BonusModel;
use core\dao\BonusConfModel;
use core\dao\DealLoadModel;
use core\service\UserTagService;

set_time_limit(0);

class BonusRebate {
    public function __construct() {
        $this->_deal_load = new DealLoadModel();
        $this->_user_tag = new UserTagService();
        $this->_new_user_tag_name = 'BONUS_NEW_USER_REGISTER';
    }
    public function send() {
        //$sql = "SELECT * FROM " . DB_PREFIX . "bonus WHERE `id` IN (SELECT `bonus_id` FROM " . DB_PREFIX . "bonus_used WHERE `status`='0') limit 2";
        $sql = "select bonus.owner_uid, bonus.sender_uid,bonus.group_id,bonus.money, used.deal_load_id, bonus.id from " . DB_PREFIX . "bonus as bonus join " . DB_PREFIX . "bonus_used as used where used.`status` = 0 AND bonus.id = used.bonus_id order by used.id ";
        $arr_bonus = $GLOBALS['db']->get_slave()->getAll($sql);
    
        $bonus_used_model = new BonusUsedModel();
        $bonus_model = new BonusModel();
        foreach ($arr_bonus as $bonus) {
            $GLOBALS['db']->startTrans();
            try{
                // 如果是返利红包，则直接更新已结算的状态
                if ($this->_is_bonus_rebate($bonus) === false) {
                    // 给使用人返利
                    $user_id = $bonus['owner_uid']; //var_dump($user_id, $bonus);
                    $rs = $this->_is_new_user($bonus['deal_load_id'],$user_id);
                    if ($rs['is_new_user'] === true) { // 如果使用红包者是新用户
                        $owner_rebate_money =    bcmul($bonus['money'], BonusConfModel::get('BONUS_REBATE_NEW_OWNER_PERCENT'), 2);  //算出根据百分比的红包返利 
                        $owner_rebate_money =    bcadd($owner_rebate_money, BonusConfModel::get('BONUS_REBATE_NEW_OWNER_VALUE'), 2);  //加上 值返利金额 

                        $sender_rebate_money = bcmul($bonus['money'], BonusConfModel::get('BONUS_REBATE_NEW_SENDER_PERCENT'), 2); // 根据百分比计算发放者的红包返利
                        $sender_rebate_money = bcadd($sender_rebate_money, BonusConfModel::get('BONUS_REBATE_NEW_SENDER_VALUE'), 2);  //加上 值返利金额 
                    } else { // 老用户
                        $owner_rebate_money =    bcmul($bonus['money'], BonusConfModel::get('BONUS_REBATE_OLD_OWNER_PERCENT'), 2);  //算出根据百分比的红包返利 
                        $owner_rebate_money =    bcadd($owner_rebate_money, BonusConfModel::get('BONUS_REBATE_OLD_OWNER_VALUE'), 2);  //加上 值返利金额 

                        $sender_rebate_money = bcmul($bonus['money'], BonusConfModel::get('BONUS_REBATE_OLD_SENDER_PERCENT'), 2); // 根据百分比计算发放者的红包返利
                        $sender_rebate_money = bcadd($sender_rebate_money, BonusConfModel::get('BONUS_REBATE_OLD_SENDER_VALUE'), 2);  //加上 值返利金额 
                    }
//  var_dump($rs , $bonus, $owner_rebate_money, $sender_rebate_money);

                    $load_money_limit = BonusConfModel::get('BONUS_REBATE_LOAD_MONEY_LIMIT');

//var_dump($owner_rebate_money, $sender_rebate_money);
                    if ( $rs['deal_load']['money'] >= $load_money_limit) {  // 本次投资金额大于 限制金额才给予红包返利
                        // 给投资用户生成一条投资奖励红包记录
                        if ($owner_rebate_money > 0) {
                            $bonus_model->insert_one($user_id, $owner_rebate_money, BonusConfModel::get('BONUS_REBATE_EXPIRED_DAY')); 
                        }

                        // 给发放人返利
                        $sender_uid = $bonus['sender_uid'];
                        // 给红包发放用户生成一条投资奖励红包记录
                        if ($sender_rebate_money > 0) {
                            $bonus_model->insert_one($sender_uid, $sender_rebate_money, BonusConfModel::get('BONUS_REBATE_EXPIRED_DAY'));
                        }
                    }
                    /**
                    if ($rs['is_new_user'] === true ) { // 如果是新用户则删除用户标签
                        $rs = $this->_user_tag->delUserTagsByConstName($user_id, $this->_new_user_tag_name);
                    }
                    **/
                }

                // 这里为毛不直接 update？
                $obj = $bonus_used_model->getBonusUsedByid($bonus['id']);
                $obj->status = 1;
                $obj->save();
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                return false;
            }
        }
    }

    // 判断一个用户是不是新用户
    private function _is_new_user($deal_load_id, $user_id) {
        $loads = $this->_deal_load->getBefore($deal_load_id, $user_id); //var_dump($loads); die;
        $rs = array(
            'is_new_user' => false,
            'deal_load' => $loads[0]
        );

        if (count($loads) > 1) {    //不是第一笔投资
            return $rs;
        }

        //判断 新用户标签
        $is_have = $this->_user_tag->getTagByConstNameUserId($this->_new_user_tag_name, $user_id);
        if ($is_have === false) {   //不是新用户
            return $rs;
        }
        $rs['is_new_user'] = true;
        return $rs;

    }
    
    // 判断一个红包是否是返利红包，如果是返利红包，则不再继续返利
    private function _is_bonus_rebate($bonus) {
        if ($bonus['group_id'] == 0 && $bonus['sender_uid'] == 0) {
            return true; 
        } else {
            return false;
        }
    }
}

$obj = new BonusRebate();
$obj->send();
