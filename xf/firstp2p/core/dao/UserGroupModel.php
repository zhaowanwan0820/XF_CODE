<?php
/**
 * UserGroupModel class file.
 *
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

namespace core\dao;

/**
 * 用户组信息
 *
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/
class UserGroupModel extends BaseModel
{
    // 不同的site_id 有不同的默认分组
    public function getDefaultGroupId() { 
        $group_id = $GLOBALS['sys_config']['SITE_USER_GROUP'][$GLOBALS['sys_config']['APP_SITE']];
        if(intval($group_id)==0)
        {
            $sql = sprintf("SELECT id FROM %s ORDER BY score ASC LIMIT 1", $this->escape($this->tableName()));
            $res = $this->findBySql($sql);
            if ($res) {
                return $res->id;
            }
        } else {
            return $group_id;
        }
        return false;
    }

    public function getGroups() {
        $result = array();
        // 走从库
        $list = $this->findAllViaSlave();
        foreach ($list as $item) {
            $result[$item['id']] = $item;
        }
        return $result;
    }

    public function getGroupsByCond($cond = '', $fields = '*') {
        $result = array();
        // 走从库
        $list = $this->findAllViaSlave($cond, true, $fields);
        foreach ($list as $item) {
            $result[$item['id']] = $item;
        }

        return $result;
    }
}
