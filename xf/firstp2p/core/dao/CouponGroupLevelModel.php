<?php

namespace core\dao;

class CouponGroupLevelModel extends BaseModel
{
    //刷新用户组和服务等级对应关系
    public function update($data)
    {
        $sql = 'select count(id) from '.$this->tableName()." where group_id = '".$data['group_id']."' and level_id = '".$data['level_id']."'";
        $count = $this->countBySql($sql, array(), true);
        $now = time();
        if (0 == $count) {
            $sql = 'insert into '.$this->tableName()."(group_id,level_id,create_time,update_time) VALUES('".$data['group_id']."','".$data['level_id']."','".$now."','".$now."')";
        } else {
            $sql = 'update '.$this->tableName()." set is_delete=0,update_time='".$now."' where group_id='".$data['group_id']."' and level_id='".$data['level_id']."'";
        }

        return $this->execute($sql);
    }

    public function deteteAll()
    {
        return $this->updateBy(array('is_delete' => 1), '1');
    }

    public function getListByCondition($condition = array(), $pageSize = false, $pageNum = false)
    {
        $sql = 'SELECT
                gl.*,
                ug.NAME group_name,
                ug.service_status,
                ug.is_effect group_is_effect,
                ug.max_pack_ratio,
                ug.pack_ratio,
                ug.is_related,
                cl.NAME level_name,
                cl.rebate_ratio,
                cl.is_effect level_is_effect,
                if(ug.is_related=1,ug.pack_ratio-cl.rebate_ratio,ug.pack_ratio) as agency_rebate_ratio,
                if(((ug.is_related=1 && ug.pack_ratio>=cl.rebate_ratio) or ug.is_related=0) && if(ug.is_related=1,ug.pack_ratio<=ug.max_pack_ratio,(ug.pack_ratio+cl.rebate_ratio)<=ug.max_pack_ratio) && ug.is_effect=1 && cl.is_effect=1,1,0) as rule_status
            FROM
                firstp2p_coupon_group_level gl 
                left join firstp2p_user_group ug on gl.group_id = ug.id 
                left join firstp2p_user_coupon_level cl on gl.level_id = cl.id
            WHERE
            ';
        $where = $this->_setWhere($condition);
        $sql .= $where;

        $sql .= ' order by rule_status,id asc ';

        if (!empty($pageSize) && !empty($pageNum)) {
            $limit = ' limit '.($pageNum - 1) * $pageSize.','.$pageSize;
            $sql .= $limit;
        }

        return $this->findAllBySqlViaSlave($sql, true);
    }

    public function getCountByCondition($condition = array())
    {
        $sql = 'SELECT count(gl.id) as count FROM
            firstp2p_coupon_group_level gl
            left join firstp2p_user_group ug on gl.group_id = ug.id
            left join firstp2p_user_coupon_level cl on gl.level_id = cl.id
            WHERE';
        $sql .= $this->_setWhere($condition);
        return $this->countBySql($sql, array(), true);
    }

    private function _setWhere($condition)
    {
        $where = ' is_delete = 0 ';
        if (isset($condition['group_name']) && !empty($condition['group_name'])) {
            $where .= " AND ug.NAME ='".$condition['group_name']."'";
        }
        if (isset($condition['service_status'])) {
            $where .= " AND ug.service_status ='".$condition['service_status']."'";
        }
        if (isset($condition['group_is_effect'])) {
            $where .= " AND ug.is_effect ='".$condition['group_is_effect']."'";
        }
        if (isset($condition['level_name']) && !empty($condition['level_name'])) {
            $where .= " AND cl.NAME ='".$condition['level_name']."'";
        }
        if (isset($condition['level_id']) && !empty($condition['level_id'])) {
            $where .= " AND cl.id ='".$condition['level_id']."'";
        }
        if (isset($condition['group_id']) && !empty($condition['group_id'])) {
            $where .= " AND ug.id ='".$condition['group_id']."'";
        }

        return $where;
    }
}
