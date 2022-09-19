<?php
/**
 * OtoCouponResendModel class file.
 *
 * @author liguizhi@ucfgroup.com
 */

namespace core\dao;

/**
 * 礼券补发表
 *
 * @author liguizhi@ucfgroup.com
 */
class OtoCouponResendModel extends BaseModel
{
    //审核状态
    const STATUS_INIT = 1;     //待审核
    const STATUS_SUCCESS = 2;  //已通过
    const STATUS_FAILED = 3;   //未通过

    //发送方式
    const TYPE_USERID = 1;     //用户id
    const TYPE_CSV    = 2;     //导入csv

    public static $status = array(
        1 => '待审核',
        2 => '审核通过',
        3 => '未通过'
    );

    public static $sendType = array(
        self::TYPE_USERID => '会员ID',
        self::TYPE_CSV => '导入CSV'
    );


    public function getTask($id) {
        $condition = "id = '$id'";
        $task = $this->findBy($condition);
        if ($task['type'] == OtoCouponResendModel::TYPE_CSV) {
            $static_host = app_conf('STATIC_HOST');
            $task['user_id_list'] = (substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/' . $task['user_id_list'];
        }
        $task['type_desc'] = self::$sendType[$task['type']];
        return $task;
    }

    public function getList($condition='', $page=1, $pageSize=20) {
        $result = array();
        $nowTime = time();
        $start = ($page-1) * $pageSize;
        if ($condition) {
            $sql = "SELECT * FROM firstp2p_oto_coupon_resend WHERE $condition ORDER BY id DESC LIMIT $start, $pageSize";
            $countSql = "SELECT count(*) FROM firstp2p_oto_coupon_resend WHERE $condition";
        } else {
            $sql = "SELECT * FROM firstp2p_oto_coupon_resend ORDER BY id DESC LIMIT $start, $pageSize";
            $countSql = "SELECT count(*) FROM firstp2p_oto_coupon_resend";
        }
        $tmpList = $this->findAllBySqlViaSlave($sql);
        if ($tmpList) {
            foreach($tmpList as $item) {
                $result['list'][] = $item->getRow();
            }
        }
        $count = $this->findBySql($countSql);
        $result['count'] = $count->getRow();
        return $result;
    }

    public function addTask($data) {

        foreach ($data as $field => $value) {
            if ($data[$field] !== NULL && $data[$field] !== '') {
                $this->$field = $this->escape($data[$field]);
            }
        }

        $this->create_time = time();

        if ($this->insert()) {
            return $this->db->insert_id();
        }

        return false;
    }

    public function updateTask($data) {
        $condition = "id = {$data['id']}";
        unset($data['id']);
        $res = $this->updateAll($data, $condition);
        return $this->db->affected_rows();
    }
}
