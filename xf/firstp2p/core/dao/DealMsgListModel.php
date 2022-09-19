<?php
/**
 * DealCate class file.
 * @author zhanglei5@ucfgroup.com
 **/

namespace core\dao;

/**
 * DealMsgListModel class
 *
 * @author zhanglei5@ucfgroup.com
 **/
class DealMsgListModel extends BaseModel {
    /**
     * 根据时间(指定时间之后的)获取未发送的邮件/短信
     * @param int $time
     * @param number $send_type  1:邮件  0:短信
     * @return Ambigous <\libs\db\model, NULL, unknown>
     */
    function getNotSendByTime($time,$type='>=',$title='',$send_type=1) {
        $sql = 'select `id`,`dest`,`title`,`content` from '.$this->tableName().' where send_type='.$send_type." and `is_success` != '1' and create_time $type':time'";
        $param[':time'] = $time;
        if(strlen($title) > 0) {
            $sql .= " and `title` = ':title'";
            $param[':title'] = $title;
        }
        $result = $this->findAllBySql($sql,true,$param);

        return $result;
    }
    /**
     * 获取某个标题发送失败的
     * @param unknown $title
     * @param number $send_type
     * @return Ambigous <\libs\db\model, NULL, unknown>
     */
    function getFaildByTitle($title,$send_type=1) {
        $sql = 'select `id`,`dest`,`title`,`content` from '.$this->tableName().' where send_type='.$send_type." and `is_success` != '1' and title=':title'";
        $param[':title'] = $title;
        $result = $this->findAllBySql($sql,true,$param);
        return $result;
    }

    /**
     * updateMsg  修改dealmsglist表
     * @author zhanglei5 <zhanglei5@group.com> 
     * 
     * @param array $data 要set的字段及value
     * @param string $emailId 
     * @access public
     * @return void
     */
    function updateStatusBySCId($data,$scId) {
        $where = " sc_id = '".$this->escape($scId)."' ";
        $set = array();
        foreach ($data as $key => $val) {
           $set[$key] = $this->escape($val); 
        }
        $result = $this->db->autoExecute($this->tableName(), $set, 'UPDATE',$where);
        return $result;
    }
    
    /**
     * getListByTitleAndEmail  根据title和email 查询结果
     * 指定 邮件发送失败报警脚本用 script/email_monitor.php
     * @author zhanglei5 <zhanglei5@group.com> 
     * 
     * @param string $title 
     * @param array $emails 
     * @access public
     * @return void
     */
    public function getListByTitleAndEmail($title,$emails,$createTime = 0) {
        if ($createTime == 0) {
            $createTime = mktime(-8, 0, 0, date("m"), date("d"), date("Y"));    // 今天0点的时间戳
        }
        $where = " where `create_time` > ':createTime' AND `title` = ':title'";
        $param[':title'] = $title;
        $param[':createTime'] = $createTime;

        if(is_array($emails) && count($emails)) {
            $dest = implode('\',\'',$emails);
            $where .= " AND `dest` in ('{$dest}')";
        }
        $sql = "SELECT id,`dest`,`is_received`,`is_send` from ".$this->tableName().$where;
        $list = $this->findAllBySql($sql,true,$param);
        $list = is_array($list) ? $list : array();
        return $list;
    }


    /**
     * getById
     *
     * @param mixed $id
     * @access public
     * @return object
     */
    public function getById($id) {
        $obj = $this->find($id);
        if (empty($obj)) {
            return false;
        } else {
            return $obj;
        }

    }

}
