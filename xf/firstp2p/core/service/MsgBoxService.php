<?php

/**
 * 站内信Service
 */

namespace core\service;

use core\dao\MsgBoxModel;
use core\dao\UserMsgConfigModel;
use libs\utils\Logger;
use core\service\PushService;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\UserMsgEvent;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

class MsgBoxService extends BaseService {

    //分页默认和限制
    const APP_MSG_LIST_PAGESIZE_LIMIT = 100;
    const APP_MSG_LIST_PAGESIZE_DEFAULT = 10;

    /**
     * 用户开关设置
     */
    public function userConfigSet($userId, $switches) {
        $switchesResult = array();
        foreach ($switches as $key => $value) {
            if (isset(MsgBoxEnum::$appType[$key])) {
                $switchesResult[$key] = $value ? 1 : 0;
            }
        }

        return UserMsgConfigModel::instance()->setSwitches($userId, 'push_switches', $switchesResult);
    }

    /**
     * 用户开关查询
     */
    public function userConfigGet($userId) {
        $switches = UserMsgConfigModel::instance()->getSwitches($userId, 'push_switches');

        $result = array();
        foreach (MsgBoxEnum::$appTypeGroup as $groupId => $typeGroup) {
            foreach ($typeGroup as $typeId) {
                $result['switches'][$groupId]['detail'][] = array(
                    'type' => $typeId,
                    'title' => MsgBoxEnum::$appType[$typeId],
                    'status' => isset($switches[$typeId]) ? intval($switches[$typeId]) : 1,
                );
            }
        }

        return $result;
    }

    /**
     * 移动端消息类型列表
     */
    public function getAppTypeList() {
        $result = array();
        foreach (MsgBoxEnum::$appType as $id => $title) {
            $result[] = array(
                'type' => $id,
                'title' => $title,
            );
        }

        return $result;
    }

    /**
     * 移动端消息类型列表-新版本
     */
    public function getStructAppTypeList($showWxb = false) {
        $result = array();
        $typeList = array();
        foreach (MsgBoxEnum::$structAppType as $key => $value) {
            $res = array();
            foreach ($value as $type => $title) {
                $res[] = array(
                        'type' => $type,
                        'title' => ($showWxb && $type == 30) ? app_conf('NEW_BONUS_TITLE') : $title,
                );
            }
            $result['typeTitle'] = $key;
            $result['typeData'] = $res;
            $typeList[] = $result;
        }

        return $typeList;
    }

    /**
     * 移动端获取未读消息数
     */
    public function getUnreadCount($userId) {
        $condition = ' to_user_id = ":to_user_id" AND is_read = :is_read AND is_notice IN (' . implode(',', array_keys(MsgBoxEnum::$appType)) . ')';
        $params = array(':to_user_id' => $userId, ':is_read' => MsgBoxModel::MSG_STATUS_UNREAD);
        return MsgBoxModel::instance(array('to_user_id' => $userId))->count($condition, $params);
    }

    /**
     * getList
     * 读取移动端消息列表
     *
     * @param integer $userId 用户ID
     * @param integer $lastReadTime 最新阅读时间
     * @param integer $currentPage  当前页
     * @param integer $pageSize 一页显示条数
     * @access public
     * @return array
     */
    public function getAppMsgList($userId, $lastReadTime = '', $status = MsgBoxModel::MSG_STATUS_UNREAD, $type, $page = 1, $pageSize = 10) {
        if (!$lastReadTime) {
            $lastReadTime = get_gmtime();
        }
        //智多新和随鑫约的筛选
        if(in_array($type, array_keys(MsgBoxEnum::$sameAppType))){
            $sameTitle = MsgBoxEnum::$sameAppType[$type];
            if($type == 371 || $type == 372){//智多新的 结息和投标完成提示的title
                $duotouTitle = MsgBoxEnum::$structAppType['智多新'][$type];
            }
            $type = substr($type, 0,2);
        }

        $condition = 'to_user_id = ":to_user_id"';
        $params = array(':to_user_id' => $userId);
        if ($type === NULL || $type === '') {
            $condition .= ' AND is_notice IN (' . implode(',', array_keys(MsgBoxEnum::$appType)) . ')';
        } else {
            $condition .= ' AND is_notice = :is_notice';
            $params[':is_notice'] = $type;
        }
        //智多新和随鑫约的筛选
        if(isset($sameTitle)){
            $titleCondition = " AND title = ':title'";
            $params[':title'] = $sameTitle;
            if(!empty($duotouTitle)){//智多新的 结息和投标完成提示
                $titleCondition = " AND (title = ':title' or title = ':newTitle')";
                $params[':title'] = $sameTitle;
                $params[':newTitle'] = $duotouTitle;
            }
            $condition .= $titleCondition;
        }

        if ($status !== '' && $status !== NULL && $status == MsgBoxModel::MSG_STATUS_UNREAD) {
            //TODO 现在不用客户端时间
            $condition .= ' AND (is_read= :is_read OR read_time >= :read_time) AND create_time <= :read_time ORDER BY id DESC';
            $params[':is_read'] = MsgBoxModel::MSG_STATUS_UNREAD;
            $params[':read_time'] = $lastReadTime;
        } else if ($status == MsgBoxModel::MSG_STATUS_READ) {
            $condition .= ' AND is_read= :is_read AND read_time < :read_time ORDER BY id DESC';
            $params[':is_read'] = MsgBoxModel::MSG_STATUS_READ;
            $params[':read_time'] = $lastReadTime;
        } else {
            $condition .= ' ORDER BY is_read ASC,id DESC ';
        }
        $condition .= ' LIMIT ' . ($page - 1) * $pageSize . ',' . $pageSize;

        $list = MsgBoxModel::instance(array('to_user_id' => $userId))->findAll($condition, true, 'id, is_notice, title, content, is_read, create_time', $params);
        $message = array();
        foreach ($list as $msg) {
            $msg['content'] = strip_tags($msg['content']);
            $msg['extraContent'] = [];
            $structData = array();
            if (strpos($msg['content'], '{') === 0) {
                $content = json_decode($msg['content'], true);
                $msg['content'] = $content['content'];
                $msg['extraContent'] = $content['extraContent'];
                $structData['newContent'] = !empty($msg['extraContent']['main_content']) ? $msg['extraContent']['main_content'] : $msg['content'];//回款数据
                !empty($msg['extraContent']['money'])         ? $structData['money']      = $msg['extraContent']['money']         : '';//回款总额
                !empty($msg['extraContent']['turn_type'])     ? $structData['turnType']   = $msg['extraContent']['turn_type']     : ''; //跳转类型
                !empty($msg['extraContent']['repay_periods']) ? $structData['periodInfo'] = $msg['extraContent']['repay_periods'] : ''; //回款周期
                !empty($msg['extraContent']['prepay_tips'])   ? $structData['prepay']     = $msg['extraContent']['prepay_tips']   : ''; //提前回款标识
                !empty($msg['extraContent']['url'])           ? $structData['url']        = $msg['extraContent']['url']           : '';
            }else{
                $structData['newContent'] =  $msg['content'];
            }
            $message = array(
                'id' => $msg['id'],
                'title' => empty($msg['title']) ? MsgBoxEnum::$appType[$msg['is_notice']] : $msg['title'],
                'content' => $msg['content'],
                'status' => $msg['is_read'],
                'time' => to_date($msg['create_time'])
            );
            $messageList[] = array_merge($message,$structData);
        }

        // 标记未读的消息为已读
        if ($status == MsgBoxModel::MSG_STATUS_UNREAD) {
            $this->markUnreadRead($list, $userId);
        }

        $unreadCount = $this->getUnreadCount($userId);
        return array('sysTime' => $lastReadTime, 'message' => $messageList, 'unreadCount' => $unreadCount);
    }

    /**
     * 标记当前消息列表为已读
     */
    public function markUnreadRead($list, $userId = null) {
        if (empty($list)) {
            return false;
        }

        foreach ($list as $msg) {
            $ids[] = $msg['id'];
        }

        $where = 'id IN (' . implode(',', $ids) . ') AND is_read=0';
        $data = array('is_read' => MsgBoxModel::MSG_STATUS_READ, 'read_time' => get_gmtime());
        return MsgBoxModel::instance(array('to_user_id' => $userId))->updateAll($data, $where);  //这里需要测试，检查是否正确。
    }

    /**
     * 将所有消息标记已读
     */
    public function markAllRead($userId) {
        $params = array('is_read' => MsgBoxModel::MSG_STATUS_READ, 'read_time' => get_gmtime());
        $where = 'to_user_id = "' . $userId . '" AND is_read=0';
        return MsgBoxModel::instance(array('to_user_id' => $userId))->updateAll($params, $where);
    }

    /**
     * 创建消息
     */
    public function create($userId, $typeId, $title, $content, $extraContent = array()) {
        if (app_conf('MSG_BOX_ENABLE')) {
            $content = array(
                'content' => $content,
                'extraContent' => $extraContent
            );
            $content = json_encode($content, JSON_UNESCAPED_UNICODE);
            $event = new UserMsgEvent($title, $content, 0, $userId, get_gmtime(), 0, true, $typeId);
            $obj = new GTaskService();
            $obj->doBackground($event, 1, 'NORMAL', null, 'domq_message');
        }
    }

    /**
     * 推送消息
     */
    public function push(MsgBoxModel $message) {
        //是否需要推送
        $type = $message->is_notice;
        if (!isset(MsgBoxEnum::$appType[$type])) {
            return true;
        }

        $userId = $message->to_user_id;
        $content = strip_tags($message->content);
        $params = array('type' => 'msg');

        //用户是否已关闭
        $switches = UserMsgConfigModel::instance()->getSwitches($userId, 'push_switches');
        if (isset($switches[$type]) && $switches[$type] == 0) {
            return false;
        }

        $badge = $this->getUnreadCount($userId);

        $pushService = new PushService();
        return $pushService->toSingle($userId, $content, $badge, $params);
    }

    /**
     * 根据用户id获取消息记录
     * @param int $user_id
     * @param string $group_key
     * @return array
     */
    public function getMsgByUserId($user_id, $group_key) {
        return MsgBoxModel::instance(array('to_user_id' => $user_id))->getMsgRow($user_id, $group_key);
    }

    /**
     * 获取用户消息列表
     * @param bool $is_notice
     * @param int $system_msg_id
     * @param int $user_id
     * @param int $page
     * @return array
     */
    public function getMsgList($is_notice, $system_msg_id, $user_id, $page) {
        return MsgBoxModel::instance(array('to_user_id' => $user_id))->getMsgList($is_notice, $system_msg_id, $user_id, $page);
    }

    /**
     * 更新消息的已读状态
     * @param bool $is_notice
     * @param int $user_id
     * @param int @system_msg_id
     * @return bool
     */
    public function updateMsgIsReadByUserIdAndSystemMsgId($is_notice, $user_id, $system_msg_id) {
        return MsgBoxModel::instance(array('to_user_id' => $user_id))->updateMsgIsReadByUserIdAndSystemMsgId($is_notice, $user_id, $system_msg_id);
    }

    /**
     * 获取用户提示消息
     * @param type $userId
     * @return boolean
     */
    public function getUserTipMsgList($userId, $is_firstp2p=false) {
        if (empty($userId)) {
            return false;
        }
        $msgList = MsgBoxModel::instance(array('to_user_id' => $userId))->getUserTipMsgList($userId, $is_firstp2p);
        return $msgList;
    }

}
