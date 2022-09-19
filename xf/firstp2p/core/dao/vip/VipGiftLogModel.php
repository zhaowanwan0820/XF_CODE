<?php
/**
 * VipGiftLogModel
 **/

namespace core\dao\vip;
use core\dao\vip\VipBaseModel;
use core\dao\vip\VipLogModel;

/**
 * VipGiftLogModel vip升级礼包记录表
 *
 * @uses BaseModel
 * @author liguizhi <liguizhi@ucfgroup.com>
 * @date 2017-06-22
 */
class VipGiftLogModel extends VipBaseModel {
    
    const VIP_AWARD_TYPE_BIRTHDAY = 1;        //生日礼包
    const VIP_AWARD_TYPE_ANNIVERSARY = 2;     //周年礼包
    const VIP_AWARD_TYPE_UPGRADE = 3;         //升级礼包

    const VIP_GIFT_STATUS_INIT = 0;           //礼包发送状态：0-未发送
    const VIP_GIFT_STATUS_SUCCESS = 1;        //礼包发送状态：1-已发送

    const VIP_GIFT_TOKEN_PRE_BIRTHDAY = 'vip_birthday_';      //生日礼包记录前缀
    const VIP_GIFT_TOKEN_PRE_ANNIVERSARY = 'vip_anniversary_';//周年礼包记录前缀
    const VIP_GIFT_TOKEN_PRE_UPGRADE = 'vip_upgrade_';        //升级礼包记录前缀

    public function addLog($data) {
        foreach ($data as $field => $value) {
            if ($data[$field] !== NULL && $data[$field] !== '') {
                $this->$field = $this->escape($data[$field]);
            }
        }

        $this->create_time = time();

        if ($this->insert()) {
            return $this->db->insert_id();
        }

        return false;
    }

    public function getVipGiftLogByToken($token) {
        if (empty($token)) {
            return false;
        }
        $token = trim($token);
        $condition = "token = '$token'";
        $giftInfo = $this->findBy($condition);
        return $giftInfo;
    }

    /**
     * getUpgradeGiftCount获取用户最后一次升级的礼包数
     * 1.查询viplog中最后一条记录
     * 2.以viplog的logid查询礼包记录
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-26
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function getUpgradeGiftCount($userId) {
        if (empty($userId)) {
            return 0;
        }
        $condition = 'user_id= '. intval($userId) . ' ORDER BY id DESC';
        $lastVipLog = VipLogModel::instance()->findBy($condition);
        if ($lastVipLog) {
            $vipLogId = $lastVipLog['id'];
            $condition = 'user_id='. intval($userId). ' AND log_id='. $vipLogId;
            return $this->count($condition);
        } else {
            return 0;
        }
    }
}
