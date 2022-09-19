<?php
/**
 * Created by PhpStorm.
 * User: zhaohui3
 * Date: 2017/8/29
 * Time: 10:18
 */

namespace core\dao;

/**
 * app步数兑换红包
 **/
class AppStepsBonusModel extends BaseModel
{
    /**
     * 更新用户步数记录相关信息
     * @param int $id
     * @param array $data
     */
    public function saveStepsBonusById($id, $data) {
        if ($id === 0) {
            $this->db->autoExecute('firstp2p_app_steps_bonus', $data, 'INSERT');
        } else {
            $this->db->autoExecute('firstp2p_app_steps_bonus', $data, 'UPDATE', "id='{$id}'");
        }
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 获得超过用户步数的百分比
     * @param $steps
     * @return bool|string
     */
    public function getPercentSteps($steps) {
        if (intval($steps) <= 0) {
            return false;
        }
        $minTime =  mktime(0,0,0,date('m'),date('d'),date('Y'));//当天0:0:0秒
        $sql = sprintf("select COUNT(*) AS count  FROM %s WHERE `steps` <  %d AND `update_time` > %d UNION select COUNT(*) AS count  FROM %s WHERE `update_time` > %d",
            AppStepsBonusModel::instance()->tableName(),intval($steps),$minTime,AppStepsBonusModel::instance()->tableName(),$minTime);
        $res = $this->findAllBySqlViaSlave($sql,true);
        return count($res) <= 1 ? 1 : bcdiv($res['0']['count'],$res['1']['count'],2);
    }

    /**
     * 根据uid和device_no获取用户数据（取数据是或的关系）
     * @param $uid
     * @param $device_no
     * @return bool|string
     */
    public function getInfoByUidDeviceNo($uid,$device_no) {
        $uid = intval($uid);
        $device_no = htmlspecialchars($device_no);
        if ($uid == 0 || empty($device_no)) {
            return false;
        }
        $condition = 'user_id = '.$uid .' or device_no =\''. $device_no .'\'';
        return $this->findAllViaSlave($condition, true, 'id,user_id,steps,is_award,device_no,update_time');
    }
    /**
     *  根据ID删除记录
     * @param unknown $id
     * @return boolean|Ambigous <boolean, resource>
     */
    public function deleteById($id) {
        if (empty($id)) {
            return false;
        }
        $sql = sprintf("delete FROM %s WHERE `id` =  %d",AppStepsBonusModel::instance()->tableName(),intval($id));
        return $this->execute($sql);
    }
}