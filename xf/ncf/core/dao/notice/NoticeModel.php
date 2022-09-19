<?php
/**
 * 消息公告Model
 * @author longbo
 */

namespace core\dao\notice;
use libs\db\MysqlDb;
use core\dao\BaseModel;

class NoticeModel extends BaseModel
{

    public function __construct()
    {
        parent::__construct();
        $this->db = MysqlDb::getInstance('msg_box');
    }


    public function getList($offset = 0, $count =10, $limitShowTime = 0)
    {
        $condition = ' status = 1 AND type = 0 AND create_time >='. intval($limitShowTime);
        $condition .= ' ORDER BY create_time DESC ';
        $condition .= ' LIMIT :offset, :count ';
        $params = array(
            ':offset' => intval($offset),
            ':count' => intval($count),
        );
        $field = 'id, title, content, url, exclude_site, create_time as time';
        return $this->findAll($condition, true, $field, $params);
    }

    public function getCount($updateTime = 0)
    {
        $condition = ' status = 1 AND type = 0 AND create_time > :updateTime ';
        $params = array(
            ':updateTime' => intval($updateTime),
        );
        return $this->count($condition, $params);
    }

}
