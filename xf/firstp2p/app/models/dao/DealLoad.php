<?php
/**
 * DealLoad class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace app\models\dao;

/**
 * 整合部分投资相关方法
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class DealLoad extends BaseModel {
    public static $SOURCE_TYPE = array(
        'general'     => 0, //前台正常投标
        'appointment' => 1, //后台预约投标
        'reservation' => 5, //前台预约投标
    );

    /**
     * 根据订单id获取投资列表
     * @param $deal_id int 订单id
     * @return array
     */
    public function getDealLoanList($deal_id) {
        return $this->findAll("`deal_id`='{$deal_id}'");
    }

    /**
     * 根据用户id获取投资列表
     * @param $user_id int
     * @param $page int
     * @param $page_size int
     * @param bool|int $status int
     * @param bool $date_start string|false
     * @param bool $date_end string|false
     * @return array('count'=>$count, 'list'=>$list)
     */
    public function getUserLoanList($user_id, $page, $page_size, $status=0, $date_start=false, $date_end=false) {
        $deal_status = $status > 0 ? $status : "1,2,4,5";
        $condition = "`user_id`='{$user_id}' AND `deal_id` IN (SELECT `id` FROM " . Deal::instance()->tableName() . " WHERE `deal_status` IN ({$deal_status}) AND `parent_id` != '0')";
        if ($date_start) {
            $condition .= " AND `create_time`>='" . strtotime($date_start) . "'";
        }
        if ($date_end) {
            $condition .= " AND `create_time`<'" . (strtotime($date_end)+3600*24) . "'";
        }
        $count = $this->count($condition);

        $start = ($page-1) * $page_size;
        $condition .= " ORDER BY `id` DESC LIMIT {$start}, {$page_size}";
        $result = $this->findAll($condition);

        return array("count"=>$count, "list"=>$result);
    }

    /**
     * 根据用户id获取投资数目
     * @param $user_id int
     * @return int
     */
    public function getLoanNumByUserId($user_id) {
        return $this->count("`user_id`='{$user_id}' AND `deal_id` IN (SELECT `id` FROM " . Deal::instance()->tableName() . " WHERE `deal_status` IN (1,2,4,5) AND `parent_id` != '0')");
    }

} // END class DealLoad extends BaseModel
