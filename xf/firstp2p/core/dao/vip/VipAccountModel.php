<?php
/**
 * VipAccountModel
 **/

namespace core\dao\vip;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use libs\utils\Logger;
use core\dao\vip\VipBaseModel;
use libs\utils\PaymentApi;
use core\dao\DealLoadModel;

/**
 * VipAccountModel vip账户信息表
 *
 * @uses BaseModel
 * @author liguizhi <liguizhi@ucfgroup.com>
 * @date 2017-06-22
 */
class VipAccountModel extends VipBaseModel {
    const VIP_UPGRADE_ACCOUNT_FLAG = 'vip_upgrade_account_flag';

    public function addAccount($data) {
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
    public function getVipAccountByUserId ($user_id) {
        $condition = 'user_id = '.intval($user_id);
        $accountInfo = $this->findBy($condition);
        return $accountInfo;
    }

    /**
     * getVipGrade获取用户会员等级详细信息
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-07-19
     * @param mixed $user_id
     * @access public
     * @return void
     */
    public function getVipGradeDetail($user_id, $vipConf = array()) {
        $vipInfo = $this->getVipAccountByUserId($user_id);
        if ($vipInfo) {
            $vipInfo = $vipInfo->getRow();
        }
        if (empty($vipInfo)) {
            //无会员账户信息的为普通用户，默认都为0
            $vipInfo['service_grade'] = 0;
            $vipInfo['actual_grade'] = 0;
            $vipInfo['point'] = 0;
        }
        $ptGradeInfo = array(
            'name' => '普通用户',
            'minInvest' => 0,
        );
        //获取下一等级所需信息
        $grade = $vipInfo['service_grade'];
        $newGrade = $grade + 1;
        $actGrade = $vipInfo['actual_grade'];
        //离下一等级所需的投资金额
        $gradeInfo = $grade ? $vipConf[VipEnum::$vipGradeNoToAlias[$grade]] : $ptGradeInfo;
        $nextGradeInfo = $vipConf[VipEnum::$vipGradeNoToAlias[$newGrade]];
        $actGradeInfo = $actGrade ? $vipConf[VipEnum::$vipGradeNoToAlias[$actGrade]] : $ptGradeInfo;
        $vipInfo['remain_invest_money'] = $nextGradeInfo['minInvest'] - $vipInfo['point'];
        $vipInfo['name'] = $gradeInfo['name'];
        $vipInfo['actName'] = $actGrade ? $actGradeInfo['name'] : '普通用户';
        //离下一等级的升级进度
        if ($vipInfo['point'] > $gradeInfo['minInvest']) {
            $vipInfo['upgrade_percent'] = ($vipInfo['point'] - $gradeInfo['minInvest']) / ($nextGradeInfo['minInvest'] - $gradeInfo['minInvest']);
        } else {
            //保级状态下，进度为零
            $vipInfo['upgrade_percent'] = 0;
        }
        //保级剩余时间：从保级开始计，31天的00:00:00倒计时
        if (!empty($vipInfo['is_relegated'])) {
            //计算保级截止时间
            $vipInfo['remain_relegated_time'] = strtotime(date("Y-m-d",$vipInfo['relegate_time'])) + 31 * 86400 - time();
            //解除保级所需经验
            $vipInfo['remain_relegated_point'] = $gradeInfo['minInvest'] - $vipInfo['point'];
        } else {
            $vipInfo['remain_relegated_time'] = 0;
        }
        //等级描述：当前是A会员，享受a%加息 再投N元可升级为B会员，立享b%加息。
        $vipInfo['raiseInterest'] = (isset($gradeInfo['raiseInterest']) && !empty($gradeInfo['raiseInterest'])) ? $gradeInfo['raiseInterest'] : 0;
        $vipInfo['nextGradeName'] = $nextGradeInfo['name'];
        return $vipInfo;
    }

    public function updateByUserId($data, $user_id) {
        $data['update_time'] = time();
        $condition = "user_id = $user_id";

        $res = $this->updateAll($data, $condition);
        return $this->db->affected_rows();
    }

    public function updatePoint($point, $user_id) {
        if (empty($point) || empty($user_id)) {
            return $this->getVipAccountByUserId($user_id);
        }
        $sql = 'UPDATE firstp2p_vip_account SET point = point + '.intval($point). ' WHERE user_id='.intval($user_id);
        $res = $this->db->query($sql);
        return $this->getVipAccountByUserId($user_id);
    }

    public function addUpgradeFlag($user_id) {
        if (empty($user_id) || $user_id <= 0) {
            return false;
        }
        try {
            $user_id = intval($user_id);
            $redisCache= \SiteApp::init()->dataCache->getRedisInstance();
            $redisCache->setBit(self::VIP_UPGRADE_ACCOUNT_FLAG, $user_id, 1);
            Logger::info('addUpgradeFlag userId:'.$user_id);
        } catch (\Exception $ex) {
            Logger::error('add user upgrade moment failed, userId: '.$user_id.', msg: '.$ex->getMessage());
            return false;
        }
        return true;
    }

    public function clearUpgradeFlag($user_id) {
        Logger::info('clearUpgradeFlag userId:'.$user_id);
        if (empty($user_id) || $user_id <= 0) {
            return false;
        }

        try {
            $user_id = intval($user_id);
            $redisCache= \SiteApp::init()->dataCache->getRedisInstance();
            $redisCache->setBit(self::VIP_UPGRADE_ACCOUNT_FLAG, $user_id, 0);
        } catch (\Exception $ex) {
            Logger::error('clear user upgrade moment failed, userId: '.$user_id.', msg: '.$ex->getMessage());
            return false;
        }
        return true;
    }

    public function checkUpgradeFlag($user_id) {
        if (empty($user_id) || $user_id <= 0) {
            return false;
        }

        try {
            $user_id = intval($user_id);
            $redisCache= \SiteApp::init()->dataCache->getRedisInstance();
            Logger::info('checkUpgradeFlag userId:'.$user_id);
            return $redisCache->getBit(self::VIP_UPGRADE_ACCOUNT_FLAG, $user_id);
        } catch (\Exception $ex) {
            Logger::error('check user upgrade moment failed, userId: '.$user_id.', msg: '.$ex->getMessage());
            return false;
        }
    }

    /**
     * getVipRateSnap根据投资记录返回返利执行时对应vip需要返利的等级
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-09-11
     * @param mixed $userId
     * @param mixed $dealTime
     * @param mixed $rebateTime
     * @access public
     * @return void
     */
    public function getVipRateSnap($userId, $dealTime, $rebateTime) {
        //根据viplog查询交易时最近的一条log
        $condition = "user_id = $userId AND create_time < $dealTime ORDER BY id DESC LIMIT 1";
        $logInfo = VipLogModel::instance()->findBy($condition, 'service_grade, actual_grade');
        $dealLogGrade = empty($logInfo) ? 0 : $logInfo['service_grade'];
        //根据rebateTime获取viplog
        $rebateCond = "user_id = $userId AND create_time < $rebateTime ORDER BY id DESC LIMIT 1";
        $rebateSnapInfo = VipLogModel::instance()->findBy($rebateCond, 'service_grade, actual_grade');
        $rebateLogGrade = empty($rebateSnapInfo) ? 0 : $rebateSnapInfo['service_grade'];
        //获取当前账户等级
        $vipInfo = $this->getVipAccountByUserId($userId);
        $vipGrade = $vipInfo['service_grade'] ?: 0;
        //取返利等级高的
        $gradeArray = array($dealLogGrade, $rebateLogGrade, $logInfo['service_grade'],$vipGrade);
        return max($gradeArray);
    }

    public function getVipUserList($userIds) {
        if (empty($userIds)) {
            return array();
        }
        if (is_array($userIds)) {
            $condition = implode(',', $userIds);
        } else {
            $condition = $userIds;
        }

        $sql = "SELECT * FROM firstp2p_vip_account WHERE user_id in($condition)";
        return $this->findAllBySql($sql);
    }
}
