<?php
/**
 * DeliveryService.php
*
* @date 2015-07-23
* @author zhaohui <zhaohui3@ucfgroup.com>
*/

namespace core\service;

use core\dao\DeliveryModel;
/**
 * Class DeliveryService
 * @package core\service
 */
class DeliveryService extends BaseService {
    /**
     * 更新用户信息
     * @param type $data
     * @return boolean
     */
    public function updateInfo($data,$mode='update')
    {
        $userDao = DeliveryModel::instance();
        if($mode == 'insert') {
            $userDao->setRow($data);
            $userDao->insert();
            return $user_id;
        } else {
            if(empty($data['id']))
            {
                return false;
            }
            $userDao->setRow(array('id'=>$data['id']));
            return $userDao->update($data);
        }
    }
    /**
     * 根据获取用户信息
     * @param type $uid
     * @return boolean
     */
    public function getInfoByUid($uid) {
        $userDao = DeliveryModel::instance();
        $condition = "user_id=$uid";
        return $userDao->getInfoByCondition($condition,$is_array=true,$fields="*", $params = array());
    }
}