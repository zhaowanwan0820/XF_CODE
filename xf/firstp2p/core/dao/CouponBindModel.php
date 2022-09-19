<?php
/**
 * CouponBindModel.class.php.
 *
 * @date 2015-07-09
 *
 * @author wangzhen3 <wangzhen3@ucfgroup.com>
 */

namespace core\dao;

class CouponBindModel extends BaseModel
{
    /**
     * 通过投资人id获取邀请码
     *
     * @param array or string $ids
     * @param $is_slave bool 主从库
     *
     * @return array:
     */
    public function getByUserIds($user_ids, $is_slave = true)
    {
        $short_Aliases = array();

        if (empty($user_ids) || !is_array($user_ids)) {
            return $short_Aliases;
        }

        $user_ids = array_map('intval', $user_ids);
        $user_ids = implode(',', $user_ids);

        $sql = 'SELECT * FROM '.$this->tableName().' WHERE  user_id in('.$user_ids.')';

        $result = $this->findAllBySql($sql, true, false, $is_slave);

        if (!empty($result)) {
            foreach ($result as $val) {
                $short_Aliases[$val['user_id']] = $val;
            }
        }

        unset($result);

        return $short_Aliases;
    }

    /**
     * 通过推荐人获取绑定邀请记录.
     *
     * @param int $refer_user_id
     * @param int $first_row
     *
     * @return array
     */
    public function getByReferUserId($refer_user_id, $first_row = 0, $page_size = 10, $is_slave = true)
    {
        $array = array();
        if (!is_numeric($refer_user_id) || empty($refer_user_id)) {
            return $array;
        }

        if (!is_numeric($first_row) || !is_numeric($page_size)) {
            return $array;
        }

        $limit = intval($first_row).','.intval($page_size);
        $sql = 'SELECT refer_user_id,user_id,create_time,short_alias FROM '.$this->tableName().' WHERE refer_user_id='.intval($refer_user_id)." ORDER BY create_time DESC, id DESC LIMIT $limit";
        $result = $this->findAllBySql($sql, true, false, $is_slave);

        return $result;
    }

    /**
     * 检查邀请码绑定记录是否存在.
     *
     * @param array or string or int $user_ids
     *
     * @return $data = array(
                "update" => array(1,2),
                "insert" => array(3,4)
        );
     */
    public function checkRecordExist($user_ids)
    {
        $data = array(
                'update' => array(),
                'insert' => array(),
        );

        if (empty($user_ids)) {
            return $data;
        }

        if (is_array($user_ids)) {
            $ids = array_map('intval', $user_ids);
        } else {
            $user_ids = array(intval($user_ids));
        }

        $sql = 'SELECT user_id FROM '.$this->tableName().' WHERE  user_id in('.implode(',', $user_ids).')';

        $result = $this->findAllBySql($sql, true);

        $existIds = array();
        if (!empty($result)) {
            foreach ($result as $val) {
                $existIds[] = $val['user_id'];
            }
        }

        $data['update'] = $existIds; //存在的id
        $data['insert'] = array_diff($user_ids, $existIds); //不存在的id

        return $data;
    }

    /**
     * 检查绑定表里面是否有邀请记录.
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function ifExist($user_id)
    {
        $user_id = intval($user_id);
        $sql = 'SELECT count(user_id) count FROM '.$this->tableName().' WHERE  user_id = '.$user_id;
        $result = $this->db->getOne($sql);

        return $result >= 1;
    }

    /**
     * 批量插入投资人邀请码
     *
     * @param array $data
     *
     * @return bool
     */
    public function insertData($data)
    {
        if (empty($data)) {
            return false;
        }

        foreach ($data as $val) {
            $result = $this->db->insert($this->tableName(), $val);

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    public function insertOneData($data)
    {
        if (empty($data)) {
            return false;
        }

        return $this->db->insert($this->tableName(), $data);
    }

    /**
     * 更新一条记录.
     */
    public function upateDataByUserid($data, $user_id)
    {
        if (empty($data) || empty($user_id)) {
            return false;
        }

        return $this->updateBy($data, ' user_id = '.intval($user_id));
    }

    /**
     * 批量修改投资人邀请码
     *
     * @param array $data
     * @param array $ids
     *
     * @return bool
     */
    public function updateDataByUserIds($data, $user_ids)
    {
        if (empty($data) || empty($user_ids)) {
            return false;
        }
        $user_ids = array_map('intval', $user_ids);

        return $this->updateBy($data, ' user_id in ('.implode(',', $user_ids).')');
    }

    /**
     * 绑定客户数.
     *
     * @param int $refer_user_id
     *
     * @return int
     */
    public function isBigUser($refer_user_id)
    {
        $isBig = 0;
        $invite_count = app_conf('COUPON_BY_REALNAME_MAX');
        $sql = 'SELECT count(user_id) count  FROM '.$this->tableName().' WHERE  refer_user_id = '.intval($refer_user_id);
        $result = $this->db->getOne($sql);
        // TODO The type of result is integer, no count. By sunxuefeng
        if ($result >= $invite_count) {
            $isBig = 1;
        }

        return $isBig;
    }


    public function getCountByReferUserId($refer_user_id){
        $consume_user_ids = array();
        $sql = 'SELECT user_id  FROM '.$this->tableName().' WHERE  refer_user_id = '.intval($refer_user_id);
        $result = $this->findAllBySqlViaSlave($sql,true);
        if (!empty($result)) {
            foreach ($result as $value) {
                $consume_user_ids[$value['user_id']] = $value['user_id'];
            }
        }
        return $consume_user_ids;
    }

    public function getCountByInviteUserId($invite_user_id){
        $consume_user_ids = array();
        $sql = 'SELECT user_id  FROM '.$this->tableName().' WHERE  invite_user_id = '.intval($invite_user_id);
        $result = $this->findAllBySqlViaSlave($sql,true);
        if (!empty($result)) {
            foreach ($result as $value) {
                $consume_user_ids[$value['user_id']] = $value['user_id'];
            }
        }
        return $consume_user_ids;
    }

    public function getListByInviteUserId($invite_user_id,$firstRow = false,$pageSize = false){
        $data = array(
            'count'=>0,
            'list'=>false
            );

        $sql = 'SELECT count(user_id) count  FROM '.$this->tableName().' WHERE  invite_user_id = '.intval($invite_user_id);
        $data['count'] = $this->db->getOne($sql);

        $sql = 'SELECT user_id as consume_user_id FROM '.$this->tableName().' WHERE  invite_user_id = '.intval($invite_user_id);
        if ($firstRow !== false && $pageSize !== false) {
            $sql .= " ORDER BY user_id DESC LIMIT " . $firstRow . ", " . $pageSize;
        }
        $list = $this->findAllBySqlViaSlave($sql,true);
        $data['list'] = $list;

        return $data;
    }
}
