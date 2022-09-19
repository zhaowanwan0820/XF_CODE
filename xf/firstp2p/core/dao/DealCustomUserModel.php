<?php
/**
 *
 * 标的定制用户设置
 **/

namespace core\dao;

use libs\utils\Logger;
use core\dao\DealModel;

class DealCustomUserModel extends BaseModel {


    /**
     * 获取标为 进行中和满标的
     */
    public function getEffectiveStateList(){

        $sql = "SELECT DISTINCT dcu.deal_id FROM firstp2p_deal_custom_user AS dcu LEFT JOIN firstp2p_deal d
            ON d.id=dcu.deal_id WHERE d.deal_status IN (1,2) AND d.`is_effect`='1' AND d.`is_delete`='0' AND d.`publish_wait` = 0
            AND d.`is_visible`='1'";

        $result = $this->findAllBySqlViaSlave($sql, true);

        return $result;
    }
    /**
     * 获取有效的所有标的所有用户
     * @return array
     */
    public function getEffectiveStateUserIdsList(){
        $sql = "SELECT DISTINCT dcu.user_id FROM firstp2p_deal_custom_user AS dcu LEFT JOIN firstp2p_deal d
            ON d.id=dcu.deal_id WHERE d.deal_status IN (1,2) AND d.`is_effect`='1' AND d.`is_delete`='0' AND d.`publish_wait` = 0
            AND d.`is_visible`='1' AND dcu.user_id > 0";

        $result = $this->findAllBySqlViaSlave($sql, true);

        return $result;
    }

    /**
     * 获取有效的所有标的所有用户组
     * @return array
     */
    public function getEffectiveStateUserGroupIdsList(){
        $sql = "SELECT DISTINCT dcu.group_id FROM firstp2p_deal_custom_user AS dcu LEFT JOIN firstp2p_deal d
            ON d.id=dcu.deal_id WHERE d.deal_status IN (1,2) AND d.`is_effect`='1' AND d.`is_delete`='0' AND d.`publish_wait` = 0
            AND d.`is_visible`='1' AND dcu.group_id > 0";

        $result = $this->findAllBySqlViaSlave($sql, true);

        return $result;
    }

    /**
     * 判断标存在不存在
     * @param $deal_id
     */
    public function isDealExist($deal_id){
        if (empty($deal_id)){
            return false;
        }

        $where = 'deal_id='.intval($deal_id);
        $ret = $this->findByViaSlave($where,'id');
        if (empty($ret)){
            return false;
        }
        $r = $ret->getRow();
        if (empty($r)){
            return false;
        }

        return true;
    }
    /**
     * 获取逗号分隔的deal_id
     *
     * @return str|false
     */
    public function getCommaSeparatedDealId(){
        $list = $this->getEffectiveStateList();
        if (empty($list)){
            return false;
        }
        $dealIds = array();
        $str = '';
        foreach($list as $v){
            $dealIds[$v['deal_id']] = $v['deal_id'];
        }

        if (!empty($dealIds)){
            $str = implode(',',$dealIds);
        }

        return $str;
    }
    /**
     * 获取单个定制标下的所有用户
     * @param $deal_id
     */
    public function getDealUserList($deal_id){

        $param = array(
            ':deal_id' => $deal_id
        );
        $condition = "deal_id=':deal_id'";

        $result = $this->findAllViaSlave($condition,true,'deal_id,user_id,user_name, type, group_id',$param);

        return $result;
    }

    /**
     * 查找单个标下单个用户
     * @param int $deal_id
     * @param int $user_id
     * return array
     */

    public function getDealOneUser($deal_id, $user_id, $group_id = 0){

        $condition = "deal_id=':deal_id' AND (user_id=':user_id' OR group_id=':group_id')";
        $param = array(
            ':deal_id' => $deal_id,
            ':user_id' => $user_id,
            ':group_id'=> $group_id
        );
        $result = $this->findByViaSlave($condition,'user_id,deal_id,user_name, type, group_id',$param);
        if (empty($result)){
            return false;
        }
        return $result->getRow();
    }

    public function changeUserId($params=array()){
        $this->user_id =intval($params['user_id']);
        $this->deal_id =intval($params['deal_id']);
        $this->user_name = trim($params['user_name']);
        $this->create_time = time();
        $this->admin_id=intval($params['admin_id']);
        if ($this->insert() === false) {
            throw new \Exception("insert special deal user_id fail");
        }
    }

    public function deleteByDealId($deal_id){
        $sql = sprintf("DELETE FROM %s WHERE `deal_id` = %d ", $this->tableName(),$deal_id);
        if($this->execute($sql)=== false){
            return false;
        }
        return true;
    }

    /**
     * 删除导入用户
     * @param $deal_id
     * @return bool
     */
    public function deleteByDealIdType($deal_id){
        $sql = sprintf("DELETE FROM %s WHERE `deal_id` = %d AND `type`=1", $this->tableName(),$deal_id);
        if($this->execute($sql)=== false){
            return false;
        }
        return true;
    }
    /**
     * 获取有专享在途的用户
     */
    public function getUserIdsZhuanXiangZaiTu($deal_type) {
        if (!empty($deal_type)) { // 目前有值只用在补充在交易所不在专享的情况
            $sql = "select distinct dl.user_id user_id from firstp2p_deal_load dl, firstp2p_deal d where dl.deal_id=d.id and d.deal_status in (1,2,4) and d.deal_type=2 and dl.user_id not in (select distinct dl.user_id user_id from firstp2p_deal_load dl, firstp2p_deal d where dl.deal_id=d.id and d.deal_status in (1,2,4) and d.deal_type=3)";
        } else {
            $sql = "select distinct dl.user_id user_id from firstp2p_deal_load dl, firstp2p_deal d where dl.deal_id=d.id and d.deal_status in (1,2,4) and d.deal_type in (2,3)";
        }
        $result = $this->findAllBySqlViaSlave($sql, true);
        return $result;
    }

}
