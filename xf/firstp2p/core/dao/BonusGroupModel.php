<?php
/**
 * BonusModel class file.
 *
 * @author wangshijie@ucfgroup.com
 */

namespace core\dao;

use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\Bonus\AcquireBonusGroupEvent;
use libs\utils\Logger;
use core\service\bonus\RpcService;

/**
 * 红包
 *
 * @author wangshijie@ucfgroup.com
 */
class BonusGroupModel extends BaseModel
{
    /**
     * 生成红包基本信息
     * @param array $data 插入字段
     * @return int $result 红包组id
     */
    public function add_record($data, $syncFlg = true) {
        $this->setRow($data);
        $this->_isNew = true;
        $result = $this->save();
        if (!$result) {
            return false;
        }
        if ($syncFlg && RpcService::getGroupSwitch(RpcService::GROUP_SWITCH_WRITE) && $this->id) {
            $taskId = (new GTaskService())->doBackground((new AcquireBonusGroupEvent($this->id)), 20);
            Logger::info(implode('|', [__METHOD__, 'to gearman', $this->id, $taskId]));
        }

        return $this->id;//返回生成的红包组id
    }

    /**
     * 获取分组列表
     * @param unknown $user_id
     * @param unknown $page_data
     * @return \ArrayList
     */
    public function get_list($user_id, $page_data){

        $params[':user_id'] = $user_id;
        $condition = "`user_id` = ':user_id' && created_at >= ".strtotime(date('Y-m-d', strtotime('-30 days')))." ORDER BY `id` DESC";

        $res = array();
        if($page_data['make_page']){
            $res['count'] = $this->countViaSlave($condition, $params);
            $condition .= ' LIMIT :start , :page_size';
            $params[':start'] = ($page_data['page'] - 1) * $page_data['page_size'];
            $params[':page_size'] = $page_data['page_size'];
        }
        $res['list'] = $this->findAllViaSlave($condition, true, '*', $params);
        return $res;
    }

    /**
     * 获取可以发送的红包列表，保证可以发送的红包在最前
     */
    public function get_valid_group($user_id) {
        $sql = 'SELECT A.* FROM %s A LEFT JOIN %s B ON A.id=B.group_id where A.user_id=%s && (B.status=0 OR B.status is null) && A.expired_at > %s && A.created_at >= %s GROUP BY A.id ORDER BY A.expired_at ASC';
        $sql = sprintf($sql, 'firstp2p_bonus_group', 'firstp2p_bonus', intval($user_id), time(), strtotime(date('Y-m-d', strtotime('-180 days'))));
        return $this->findAllBySql($sql, true, array(), true);
    }

    /**
     * 获取已经被领取完或者已经过期的红包组
     */
    public function get_invalid_group($user_id, $start = 0, $limit = 10, $ids = array()) {
        if (!$user_id) {
            return false;
        }
        if (!is_array($ids)) {
            $ids = array(intval($ids));
        }
        $ids = implode(',', $ids);
        if (!empty($ids)) {
            $sql = sprintf('SELECT * FROM firstp2p_bonus_group WHERE user_id="%s" && id NOT IN(%s) && created_at >= %s ORDER BY id DESC LIMIT %s,%s', $user_id, $ids, strtotime(date('Y-m-d', strtotime('-30 days'))), $start, $limit);
        } else {
            $sql = sprintf('SELECT * FROM firstp2p_bonus_group WHERE user_id="%s" && created_at >= %s ORDER BY id DESC LIMIT %s,%s', $user_id, strtotime(date('Y-m-d', strtotime('-30 days'))), $start, $limit);
        }
        return $this->findAllBySql($sql, true, array(), true);
    }
    /**
     * 获取用户当天获取的返利红包
     */
    public function getRebateBonusCount($userId, $groupType) {

        $dateStart = strtotime(date('Y-m-d'));
        $dateEnd = $dateStart + 3600*24;
        $condition = ' user_id = ' .$userId. ' AND bonus_type_id = ' .$groupType
                     . ' AND created_at >= ' .$dateStart. ' AND created_at < ' .$dateEnd;
        return $this->count($condition);
    }

    /**
     * 更新红包组过期时间
     * @param  [type] $orderID    [description]
     * @param  [type] $expireTime [description]
     * @return [type]             [description]
     */
    public function updateExpireTime($orderID, $expireTime)
    {
        $orderID = addslashes($orderID);
        $sql = "UPDATE firstp2p_bonus_buy_order as o, firstp2p_bonus_group as g SET g.expired_at = {$expireTime} WHERE o.group_id = g.id and o.order_id = '{$orderID}'";
        return $this->updateRows($sql);
    }

    /**
     * 更新红包组个数
     * @param  [type] $groupIDs [description]
     * @param  string $type     [description]
     * @return [type]           [description]
     */
    public function updateCount($groupIDs, $type = 'get')
    {
        $count_col = '';
        if ($type == 'get') {
            $count_col = 'get_count';
        }
        if ($type == 'used') {
            $count_col = 'used_count';
        }
        if (is_array($groupIDs)) {
            foreach ($groupIDs as $key => $value) {
                if (empty($value)) unset($groupIDs[$key]);
                else $groupIDs[$key] = intval($value);
            }
            $groupIDs = implode(',', $groupIDs);
            $sql = "UPDATE `firstp2p_bonus_group` SET `{$count_col}` = `{$count_col}` + 1 WHERE `id` IN ({$groupIDs})";
        } else {
            $groupIDs = intval($groupIDs);
            $sql = "UPDATE `firstp2p_bonus_group` SET `{$count_col}` = `{$count_col}` + 1 WHERE `id` = {$groupIDs}";
        }
        return $this->updateRows($sql);
    }

    /**
     * 设定过期时间
     * @param [type] $id   [description]
     * @param [type] $used [description]
     * @param [type] $get  [description]
     */
    public function setCount($id, $used, $get)
    {
        $sql = "UPDATE `firstp2p_bonus_group` SET `used_count` = {$used}, `get_count` = {$get} WHERE `id` = {$id}";
        return $this->updateRows($sql);
    }
}
