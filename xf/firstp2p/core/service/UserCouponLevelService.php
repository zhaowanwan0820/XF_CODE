<?php

//常用方法
//UserCouponLevelModel::instance()->find($id);
//UserCouponLevelModel::instance()->updateAll($param,$where,$is_affected_rows);

namespace core\service;

use core\dao\UserCouponLevelModel;
use core\dao\CouponGroupLevelModel;
use core\dao\UserGroupModel;
use core\dao\UserModel;
use libs\utils\Logger;

class UserCouponLevelService extends BaseService
{
    public function __construct()
    {
    }

    /**
     *获取用户组和用户等级之间的关系.
     * cache 是否取缓存
     */
    public function getGroupLevel($cache = true)
    {
        if (!$cache) {
            $sql = "select DISTINCT new_coupon_level_id as level_id,group_id from firstp2p_user where new_coupon_level_id != '0' and new_coupon_level_id is not null and group_id != 0 ";
            $result = UserModel::instance()->findAllBySqlViaSlave($sql, true);
        } else {
            $result = $this->getGroupLevelCache();
        }
        return $result;
    }

    /*
     *刷新用户组和等级关系
     */
    public function refreshGroupLevel($grouplevels)
    {
        if (!empty($grouplevels)) {
            try {
                $couponGroupLevelModel = new CouponGroupLevelModel();
                $result = $couponGroupLevelModel->deteteAll();
                if (empty($result)) {
                    throw new Exception('更新失败');
                }

                foreach ($grouplevels as $key => $value) {
                    $result = $couponGroupLevelModel->update(array('group_id' => $value['group_id'], 'level_id' => $value['level_id']));
                    if (empty($result)) {
                        throw new Exception('更新失败');
                    }
                    unset($grouplevels[$key]);
                }

            } catch (\Exception $e) {
                return false;
            }
            return true;
        }
    }

    //获取用户组和等级关系缓存
    public function getGroupLevelCache()
    {
        return CouponGroupLevelModel::instance()->findAllViaSlave('is_delete = 0', true);
    }

    //获取用户组和等级列表
    public function getGroupLevelListByCondition($condition, $pageSize = false, $pageNum = false)
    {
        $couponGroupLevelModel = new CouponGroupLevelModel();
        return $couponGroupLevelModel->getListByCondition($condition, $pageSize, $pageNum);
    }

    //获取用户组和等级列表
    public function getGroupLevelCountByCondition($condition)
    {
        $couponGroupLevelModel = new CouponGroupLevelModel();
        return $couponGroupLevelModel->getCountByCondition($condition);
    }

    //通过id获取id
    public function getLevelById($id)
    {
        if (empty($id)) {
            return array();
        }
        $result = UserCouponLevelModel::instance()->find(intval($id));
        return !empty($result)?$result->getRow():array();
    }

    public function getGroupById($id)
    {
        if (empty($id)) {
            return array();
        }
        $result = UserGroupModel::instance()->find($id);
        return !empty($result)?$result->getRow():array();
    }

    public function getLevelByUserId($id)
    {
        $user = UserModel::instance()->find($id,'new_coupon_level_id',true);
        if (!empty($user)) {
            return $this->getLevelById($user['new_coupon_level_id']);
        }

        return false;
    }

    public function getGroupByUserId($id){
        $user = UserModel::instance()->find($id,'group_id',true);
        if (!empty($user)) {
            return $this->getGroupById($user['group_id']);
        }

        return false;
    }

    /**
     * 获取所有效的服务等级.
     */
    public function getLevels()
    {
        return UserCouponLevelModel::instance()->findAllViaSlave('is_effect = 1', true);
    }

    //获取用户组和等级名称
    public function getGroupAndLevelByUserId($userId)
    {
        $userGroupService = new UserGroupService();
        $group = $userGroupService->getGroupInfoByUserId($userId);
        $level = $this->getLevelByUserId($userId);
        if (empty($level) || empty($group)) {
            return false;
        }

        return $group['name'].'-'.$level['name'];
    }

    //验证用户组和服务等是否匹配
    public function checkGroupMatchLevels($groupInfo)
    {
        if (empty($groupInfo)
            || !isset($groupInfo['is_related'])
            || !isset($groupInfo['max_pack_ratio'])
            || !isset($groupInfo['pack_ratio'])
            || !isset($groupInfo['is_effect'])
            || !isset($groupInfo['service_status'])
            || !isset($groupInfo['id'])
            ) {
            throw new \Exception('参数错误');
        }

        //无效不校验打包系数
        if (0 == $groupInfo['is_effect']) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '用户组无效不需要校验规则')));
            return true;
        }

        //无服务标识不在校验
        if (0 == $groupInfo['service_status']) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '无服务标识不需要校验规则')));
            return true;
        }

        //获取该组下的所有服务等级
        $levels = $this->getLevelsByGroupId($groupInfo['id']);
        //如果该会员组下面没有服务等级，直接返回true
        if (empty($levels)) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '用户组不关联任何服务等级')));
            return true;
        }

        $result = true;
        foreach ($levels as $levelId) {
            $levelInfo = $this->getLevelById($levelId);
            $checkResult = $this->checkLevelMatchGroup($levelInfo, $groupInfo);
            if (!$checkResult) {
                Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, 'levelInfo:'.json_encode($levelInfo), '会员组和等级不匹配')));
                $result = $checkResult;
                break;
            }
        }

        return $result;
    }

    /*
     * 验证服务等级和用户组是否匹配
     */
    public function checkLevelMatchGroups($levelInfo)
    {
        if (empty($levelInfo)
            || !isset($levelInfo['rebate_ratio'])
            || !isset($levelInfo['is_effect'])
            || !isset($levelInfo['id'])) {
            throw new \Exception('参数错误');
        }

        //无效不校验打包系数
        if (0 == $levelInfo['is_effect']) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '服务等级无效不需要校验规则')));
            return true;
        }

        //获该服务等级所在的所有组
        $groups = $this->getGroupsByLevelId($levelInfo['id']);
        //如果改服务等级没有被分配的任何一个组
        if (empty($groups)) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '服务等级不关联任何组')));
            return true;
        }

        $result = true;
        foreach ($groups as $groupId) {
            $groupInfo = $this->getGroupById($groupId);
            //无服务标识不在校验
            if (0 == $groupInfo['service_status']) {
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '无服务标识不需要校验规则')));
                continue;
            }
            $checkResult = $this->checkLevelMatchGroup($levelInfo, $groupInfo);
            if (!$checkResult) {
                Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, 'groupInfo:'.json_encode($groupInfo), '会员组和等级不匹配')));
                $result = $checkResult;
                break;
            }
        }

        return $result;
    }

    /**
     *验证服务等级和用户组是否匹配，机构比例=联动组的打包比例-服务返点比例>0.
     */
    private function checkLevelMatchGroup($levelInfo, $groupInfo)
    {
        if (empty($levelInfo) || empty($groupInfo)) {
            throw new \Exception('用户组或者服务等级为空');
        }

        //机构系数不能小于等于0
        $agencyRebateRatio = $this->getAgencyRebateRatio($levelInfo, $groupInfo);
        if (bccomp($agencyRebateRatio, 0, 5) < 0) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, '机构系数不能小于等于0')));
            return false;
        }

        //验证机构系数+服务比例系数 是否小于等 打包系数上限
        if (!$this->checkMaxPackRatio($agencyRebateRatio, $levelInfo['rebate_ratio'], $groupInfo['max_pack_ratio'])) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, '机构系数加服务系数大于打包比例上限')));
            return false;
        }

        return true;
    }

    /**
     *获取机构系数.
     */
    public function getAgencyRebateRatio($levelInfo, $groupInfo)
    {
        if (1 == $groupInfo['is_related']) {
            return bcsub($groupInfo['pack_ratio'], $levelInfo['rebate_ratio'], 5);
        } else {
            return $groupInfo['pack_ratio'];
        }
    }

    /**
     *验证机构系数+服务比例系数 是否小于等 打包系数上限.
     */
    public function checkMaxPackRatio($agencyRebateRatio, $rebateRatio, $maxRackRatio)
    {
        return bccomp(bcadd($agencyRebateRatio, $rebateRatio, 5), $maxRackRatio, 5) <= 0;
    }

    /**
     * 通过组id和服务等级id判断是否匹配.
     */
    public function checkLevelMatchGroupById($groupId, $levelId)
    {
        $levelInfo = $this->getLevelById($levelId);
        $groupInfo = $this->getGroupById($groupId);
        return $this->checkLevelMatchGroup($levelInfo, $groupInfo);
    }

    /**
     *用户组是否联动.
     */
    public function isRelated($groupId)
    {
        $groupInfo = $this->getGroupById($groupId);
        if (empty($groupInfo)) {
            throw new \Exception('用户组不存在');
        }
        return 1 == $groupInfo['is_related'];
    }

    /**
     *用户组是有服务能力.
     */
    public function hasServiceAbility($groupId)
    {
        $groupInfo = $this->getGroupById($groupId);
        if (empty($groupInfo)) {
            throw new \Exception('用户组不存在');
        }
        return 1 == $groupInfo['service_status'];
    }

    /**
     *获取匹配的组.
     */
    public function getMatchedLevelsByGroupId($groupId)
    {
        $groupInfo = $this->getGroupById($groupId);
        if (empty($groupInfo)) {
            throw new \Exception('用户组不存在');
        }

        $levels = $this->getLevels();
        if (!empty($levels) && $groupInfo['is_effect'] == 1 && $groupInfo['service_status'] == 1) {
            foreach ($levels as $key => $value) {
                if (!$this->checkLevelMatchGroup($value, $groupInfo)) {
                    unset($levels[$key]);
                }
            }
        }
        return $levels;
    }

    public function getLevelsByCondition()
    {
        return $this->findAll($condtion, true);
    }

    //通过组id获取对应的服务等级
    public function getLevelsByGroupId($groupId){
        $result = CouponGroupLevelModel::instance()->findAllViaSlave('is_delete = 0 and level_id != 0 and group_id='.intval($groupId), true);
        return array_column($result,'level_id');
    }

    //通过服务等级id获取对应的等级
    public function getGroupsByLevelId($levelId){
        $result =  CouponGroupLevelModel::instance()->findAllViaSlave('is_delete = 0 and group_id != 0 level_id='.intval($levelId), true);
        return array_column($result,'group_id');
    }

    public function getByName($name){
        return UserCouponLevelModel::instance()->getByName($name);
    }

    public function getByRebateRatio($rebateRatio){
        return UserCouponLevelModel::instance()->getByRebateRatio($rebateRatio);
    }

    public function getUserInGroupCountByGroupId($groupId){
        return CouponGroupLevelModel::instance()->countViaSlave('group_id='.$groupId. ' and is_delete=0');
    }

    public function getUserInGroupCountByLevelId($levelId){
        return CouponGroupLevelModel::instance()->countViaSlave('level_id='.$levelId. ' and is_delete=0');
    }

}
