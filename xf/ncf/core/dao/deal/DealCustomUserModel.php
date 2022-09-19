<?php
/**
 *
 * 标的定制用户设置
 **/

namespace core\dao\deal;

use libs\utils\Logger;
use core\dao\deal\DealModel;
use core\dao\BaseModel;

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
            AND d.`is_visible`='1'";

        $result = $this->findAllBySqlViaSlave($sql, true);

        return $result;
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
     * 查找单个标下单个用户
     * @param int $deal_id
     * @param int $user_id
     * return array
     */

    public function getDealOneUser($deal_id, $user_id){

        $condition = "deal_id=':deal_id' AND user_id=':user_id'";
        $param = array(
            ':deal_id' => $deal_id,
            ':user_id' => $user_id
        );
        $result = $this->findByViaSlave($condition,'user_id,deal_id,user_name',$param);
        if (empty($result)){
            return false;
        }
        return $result->getRow();
    }

}
