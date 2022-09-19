<?php

namespace core\dao\msgbox;

use core\dao\ProxyModel;

class MsgBoxModel extends ProxyModel {

    public $isSplit = 2;

    public function __construct($params = array()) {
        parent::__construct();
        foreach($params as $key => $value) {
            $this->$key = $value;
        }
        $this->db = \libs\db\Db::getInstance('msg_box');
    }

    /**
     * 获取站内信分组列表
     */
    public function getMsgBoxList($userId)
    {
        $sql = "select group_key,count(is_notice) as total from
                (select group_key,is_notice,system_msg_id,create_time FROM " . $this->tableName() . "
                where is_delete = 0 and to_user_id = " . intval($userId) . " AND is_notice = 55  ORDER BY create_time DESC ) AS TMPA
                group by is_notice order by system_msg_id desc,MAX(create_time) desc,is_notice desc limit 0,10 ";

        return $this->findAllBySql($sql, false, array(), false);
    }

    public function tableName($is_split=false, $hash_key=false, $params=array())
    {
        return parent::tableName(true);
    }

    /**
     * 获取用户分组最新一条信息
     * @param $groupKey
     * @param $userId
     * @return bool
     */
    public function getMsgBoxRow($groupKey, $userId)
    {
        $sql = " group_key = ':group_key' and to_user_id = :user_id order by create_time desc limit 1";
        $msg = $this->findBy($sql, '*', array(
                ':group_key' => $groupKey,
                ':user_id' => $userId,
            )
            , false
        );

        if (empty($msg)) {
            return false;
        }

        $this->_convertContent($msg);
        return $msg;
    }

    /**
     * 获取知道用户消息总数
     */
    public function getuserMsgCount($userId)
    {
        $sql = 'to_user_id = ' . intval($userId) . ' AND is_notice <9';
        return $this->count($sql);
    }

    /**
     * 获取指定一行记录
     */
    public function getMsgRow($userId, $groupKey)
    {
        $condition = "is_delete = 0 and to_user_id = :user_id and group_key = ':group_key'";
        $fields = 'count(*) as count,max(system_msg_id) as system_msg_id,max(id) as id,max(is_notice) as is_notice';
        return $this->findBy($condition, $fields, array(
                ':user_id' => $userId,
                ':group_key' => $groupKey,
            )
            , false
        );
    }

    /**
     * 获取指定分类下的消息
     */
    public function getMsgList($isNotice, $systemMsgId, $userId, $page) {
        $limit = (($page - 1) * app_conf("PAGE_SIZE")) . "," . app_conf("PAGE_SIZE");
        if (in_array($isNotice, array(0, 1, 2, 3, 4, 5, 6, 7, 8))) {
            $condition = ' is_notice <9';
        } else {
            $condition = ' is_notice=:is_notice';
        }
        $condition .= " AND system_msg_id = :system_msg_id  AND to_user_id= :user_info_id and is_delete = 0 ORDER BY create_time DESC ";
        $params = array(
            ':is_notice' => $isNotice,
            ':system_msg_id' => $systemMsgId,
            ':user_info_id' => $userId,
        );
        $count = $this->count($condition, $params);
        $list = $this->findAll($condition . " LIMIT " . $limit, true, '*', $params);

        if (!empty($list)) {
            foreach ($list as &$msg) {
                $this->_convertContent($msg);
            }
        }

        return array("count" => $count, "list" => $list);
    }

    /**
     * 更新消息为已读
     */
    public function updateMsgIsReadByUserIdAndSystemMsgId($isNotice, $userId, $systemMsgId) {
        //消息分类之后的 逻辑
        if (in_array($isNotice, array(0, 1, 2, 3, 4, 5, 6, 7, 8))) {
            $sql_str = '  AND  is_notice <9';
        } else {
            $sql_str = '  AND is_notice=' . intval($isNotice);
        }
        //更新 已读状态
        $where = "is_delete = 0 AND to_user_id = " . intval($userId) . " AND is_read = 0
                    AND system_msg_id = " . intval($systemMsgId) . $sql_str;
        $this->updateAll(array('is_read' => 1, 'read_time' => get_gmtime()), $where);
    }

    /**
     * 返回提示用户的消息列表
     * @param type $userId
     * @return boolean
     */
    public function getUserTipMsgList($userId) {
        $sql = "select group_key,count(is_notice) as total,is_notice from
        (select group_key,is_notice,system_msg_id,create_time FROM " . $this->tableName() . "
        where is_delete = 0 and to_user_id = " . intval($userId) . "
        and is_read = 0 and is_notice = 55 ORDER BY create_time DESC )
        AS TMPA group by is_notice order by system_msg_id desc,MAX(create_time) desc,is_notice desc";

        return $this->findAllBySql($sql, false, array(), false);
    }

    private function _convertContent(&$msg)
    {
        $msg['extraContent'] = [];
        if (strpos($msg['content'], '{') === 0) {
            $content = json_decode($msg['content'], true);
            $msg['content'] = $content['content'];
            $msg['extraContent'] = $content['extraContent'];
        }
    }
}
