<?php
/**
 * 用户组
 * @date 2014-03-20
 * @author caolong <caolong@ucfgroup.com>
 */

namespace core\service;

use core\dao\UserModel;
use core\dao\UserGroupModel;
use core\service\UserService;
use core\service\UserCouponLevelService;
use openapi\controllers\account\withdrawLog;

/**
 * Class UserService
 * @package core\service
 */
class UserGroupService extends BaseService {

    /**
     * 获取用户信息
     *
     * @param $id
     * @return \libs\db\Model
     */
    public function getGroupInfo($id) {
        if (empty($id)) {
            return false;
        }
        return UserGroupModel::instance()->find($id);
    }

    /**
     * 判断两个用户组对应的机构id是否一致
     * @param intval $id1
     * @param intval $id2
     * @return boolean
     */
    public function agencyUsersIsSameByIds($id1,$id2){
        $id1 = intval($id1);
        $id2 = intval($id2);
        if($id1 && $id2){
            $agency_user_id1 = UserGroupModel::instance()->findViaSlave($id1,'agency_user_id');
            $agency_user_id2 = UserGroupModel::instance()->findViaSlave($id2,'agency_user_id');
            return $agency_user_id1 == $agency_user_id2;
        }
        return false;
    }

    /*
     * 判断两个用户组对应的服务标识是否一致
     * @param intval $id1
     * @param intval $id2
     * @return boolean
    */
    public function checkServiceStatusIsSame($id1,$id2){
        $id1 = intval($id1);
        $id2 = intval($id2);
        if($id1 && $id2){
            $result1 = UserGroupModel::instance()->findViaSlave($id1,'service_status');
            $result2 = UserGroupModel::instance()->findViaSlave($id2,'service_status');
            return $result1['service_status'] === $result2['service_status'];
        }
        return false;
    }

    /**
     * 比例校验
     */
    public function checkRatio($userId){
        $service = new UserService();
        $couponLevelService = new UserCouponLevelService();
        $userInfo = $service -> getUserViaSlave($userId);
        $groupInfo = $this->getGroupInfo($userInfo['group_id']);
        //获取服务人的等级信息
        $levelInfo = $couponLevelService -> getLevelById( $userInfo['new_coupon_level_id']);
        if(intval($groupInfo['is_related'])==1){
            //校验服务人的个人比例是否大于打包比例
            $result = bccomp ($groupInfo['pack_ratio'],$levelInfo['rebate_ratio'],5);
        }else{
            //校验服务人的个人比例 + 机构比例是否大于打包比例上限
            $sumRatio = bcadd($levelInfo['rebate_ratio'],$groupInfo['pack_ratio'],5);
            $result = bccomp($groupInfo['max_pack_ratio'],$sumRatio,5);
        }
        return $result;
    }

    public function getPackRetioDatas(){
        $result = array();
        $list=UserGroupModel::instance()->findAllViaSlave($condition="",$is_array=false,$fields="distinct pack_ratio");
        foreach ($list as $k => $v) {
            $result[$k] = $v['pack_ratio'];
        }
        return $result;
    }

    public function getMaxPackRetioDatas(){
        $result = array();
        $list=UserGroupModel::instance()->findAllViaSlave($condition="",$is_array=false,$fields="distinct max_pack_ratio");
        foreach ($list as $k => $v) {
            $result[$k] = $v['max_pack_ratio'];
        }
        return $result;
    }

    //通过用户id获取组信息 
    public function getGroupInfoByUserId($userId){
        $group = UserModel::instance()->findViaSlave(intval($userId),'group_id');
        if(!empty($group)){
           return UserGroupModel::instance()->findViaSlave(intval($group['group_id']));
        }
        return false;
    }

}
