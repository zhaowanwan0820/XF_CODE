<?php

/**
 * Link class file.
 * @author caolong<caolong@ucfgroup.com>
 * */

namespace core\dao;
use libs\db\MysqlDb;
/**
 * Link class
 *
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 * */
class MsgBoxModel extends ProxyModel {

    // 消息状态：未读
    const MSG_STATUS_UNREAD = 0;
    // 消息状态：已读
    const MSG_STATUS_READ = 1;

    public $isNewDb = true;

    public $isSplit = 2;

    public function __construct($params = array()) {
        parent::__construct();
        foreach($params as $key => $value) {
            $this->$key = $value;
        }
        if($this->isNewDb) {//读写都走新库新表。
            $this->db = \libs\db\Db::getInstance('msg_box');
        }
    }

    public function tableName($is_split=true, $hash_key=false, $params=array()) {
        if($this->isNewDb) {//读写都走新库新表。
            $tableName = parent::tableName($is_split, $hash_key, $params);
        } else {
            $tableName =  BaseModel::tableName();
        }
        return $tableName;
    }

    /**
     * 获取指定分类下的消息
     * @param unknown $is_notice
     * @param unknown $system_msg_id
     * @param unknown $user_info_id
     * @param unknown $page
     * @return multitype:unknown
     */
    public function getMsgList($is_notice, $system_msg_id, $user_info_id, $page) {
        $limit = (($page - 1) * app_conf("PAGE_SIZE")) . "," . app_conf("PAGE_SIZE");
        if (in_array($is_notice, array(0, 1, 2, 3, 4, 5, 6, 7, 8))) {
            $condition = ' is_notice <9';
        } else {
            //$condition=  ' is_notice='.$is_notice;
            $condition = ' is_notice=:is_notice';
        }
        $condition .= " AND system_msg_id = :system_msg_id  AND to_user_id= :user_info_id and is_delete = 0 ORDER BY create_time DESC ";
        $params = array(
            ':is_notice' => $is_notice,
            ':system_msg_id' => $system_msg_id,
            ':user_info_id' => $user_info_id,
        );
        $count = $this->count($condition, $params);//以后的读写分离由mysql proxy做，所以在代码层面去掉读备的操作。
        $list = $this->findAll($condition . " LIMIT " . $limit, true, '*', $params);

        if (!empty($list)) {
            foreach ($list as &$msg) {
                $this->_convertContent($msg);
            }
        }

        return array("count" => $count, "list" => $list);
    }

    /**
     * 获取指定一行记录
     * @param unknown $user_id
     * @param unknown $group_key
     * @return Ambigous <\libs\db\model, NULL, unknown>
     */
    public function getMsgRow($user_id, $group_key) {
        $condition = "is_delete = 0 and to_user_id = :user_id and group_key = ':group_key'";
        $fields = 'count(*) as count,max(system_msg_id) as system_msg_id,max(id) as id,max(is_notice) as is_notice';
        return $this->findBy($condition, $fields, array(
                    ':user_id' => $user_id,
                    ':group_key' => $group_key,
                        )
                        , false
        );
    }

    /**
     * 获取用户消息数据列表
     * @param unknown $user_id
     * @param unknown $limit
     */
    public function getMsgBoxList($user_id, $is_firstp2p=false) {
//        $sql = "select group_key,count(is_notice) as total from
//                (select group_key,is_notice,system_msg_id,create_time FROM " . DB_PREFIX . "msg_box
//                where is_delete = 0 and to_user_id = " . intval($user_id) . " and `type` = 0  ORDER BY create_time DESC ) AS TMPA
//                group by is_notice order by system_msg_id desc,MAX(create_time) desc,is_notice desc limit 0,10 ";
        if ($is_firstp2p) {
            $sql = "select group_key,count(is_notice) as total from
                (select group_key,is_notice,system_msg_id,create_time FROM " . $this->tableName() . "
                where is_delete = 0 and to_user_id = " . intval($user_id) . " AND is_notice = 55  ORDER BY create_time DESC ) AS TMPA
                group by is_notice order by system_msg_id desc,MAX(create_time) desc,is_notice desc limit 0,10 ";
        } else {
            $sql = "select group_key,count(is_notice) as total from
                (select group_key,is_notice,system_msg_id,create_time FROM " . $this->tableName() . "
                where is_delete = 0 and to_user_id = " . intval($user_id) . "  ORDER BY create_time DESC ) AS TMPA
                group by is_notice order by system_msg_id desc,MAX(create_time) desc,is_notice desc limit 0,10 ";
        }
        return $this->findAllBySql($sql, false, array(), false);
    }

    /**
     * 根据
     * @param unknown $group_key
     * @param unknown $user_id
     * @return Ambigous <\libs\db\model, NULL, unknown>
     */
    public function getMsgBoxRow($group_key, $user_id) {
        $sql = " group_key = ':group_key' and to_user_id = :user_id order by create_time desc limit 1";
        $msg = $this->findBy($sql, '*', array(
                    ':group_key' => $group_key,
                    ':user_id' => $user_id,
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
     * @param unknown $user_id
     * @return Ambigous <number, string, boolean>
     */
    public function getuserMsgCount($user_id) {
        $sql = 'to_user_id = ' . intval($user_id) . ' AND is_notice <9';
        return $this->count($sql);
    }

    /**
     * updateMsgIsReadByUserIdAndSystemMsgId
     *
     * @param mixed $isNotice
     * @param mixed $userId
     * @param mixed $systemMsgId
     * @access public
     * @return void
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
    public function getUserTipMsgList($userId, $is_firstp2p=false) {
//        $sql = "select group_key,count(is_notice) as total,is_notice from
//        (select group_key,is_notice,system_msg_id,create_time FROM " . DB_PREFIX . "msg_box
//        where is_delete = 0 and to_user_id = " . intval($userId) . " and `type` = 0
//        and is_read = 0 and is_delete = 0 and type = 0 and (system_msg_id>=1 or is_notice >= 1) ORDER BY create_time DESC )
//        AS TMPA group by is_notice order by system_msg_id desc,MAX(create_time) desc,is_notice desc";
        if ($is_firstp2p) {
            $sql = "select group_key,count(is_notice) as total,is_notice from
        (select group_key,is_notice,system_msg_id,create_time FROM " . $this->tableName() . "
        where is_delete = 0 and to_user_id = " . intval($userId) . "
        and is_read = 0 and is_notice = 55 ORDER BY create_time DESC )
        AS TMPA group by is_notice order by system_msg_id desc,MAX(create_time) desc,is_notice desc";
        } else {
            $sql = "select group_key,count(is_notice) as total,is_notice from
        (select group_key,is_notice,system_msg_id,create_time FROM " . $this->tableName() . "
        where is_delete = 0 and to_user_id = " . intval($userId) . "
        and is_read = 0 and (system_msg_id>=1 or is_notice >= 1) ORDER BY create_time DESC )
        AS TMPA group by is_notice order by system_msg_id desc,MAX(create_time) desc,is_notice desc";
        }

//        $msgList = $GLOBALS['db']->get_slave()->getAll($sql);
        return $this->findAllBySql($sql, false, array(), false);
    }

    private function _convertContent(&$msg) {
        $msg['extraContent'] = [];
        if (strpos($msg['content'], '{') === 0) {
            $content = json_decode($msg['content'], true);
            $msg['content'] = $content['content'];
            $msg['extraContent'] = $content['extraContent'];
        }
    }
}
