<?php
/**
 * CouponBindLogModel.class.php
 *
 * @date 2017-11-08
 * @author gengkuan <gengkuan@ucfgroup.com>
 */

namespace core\dao;
use core\dao\CouponBindModel;

class CouponBindLogModel extends BaseModel
{

    public function insertData($data, $user_ids,$isfirst = false)
    {
        if (empty($data)) {
            return false;
        }
        if (empty($user_ids)) {
            return false;
        }
        $coupon_bind = new CouponBindModel();

        foreach ($user_ids as $user_id) {
            $data['user_id']  = $user_id;
            if(!$isfirst){
                $coupon_bind_info = $coupon_bind->getByUserIds($user_ids,true);
                if(!empty($coupon_bind_info[$user_id]['refer_user_id'])){
                    $data['old_short_alias']  =$coupon_bind_info[$user_id]['short_alias'];
                    $data['old_refer_user_id']  =$coupon_bind_info[$user_id]['refer_user_id'];
                }
            }
            $result = $this->db->insert($this->tableName(), $data);
            if (!$result) {
                return false;
            }
        }
        return true;
    }

}

