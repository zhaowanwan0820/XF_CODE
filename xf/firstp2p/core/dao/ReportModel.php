<?php
/**
 * ReportModel class file.
 *
 * @author wenyanlei@ucfgroup.com
 **/

namespace core\dao;

/**
 * 财务报表
 *
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/
class ReportModel extends BaseModel
{
    const IS_PUBLISH = 1;
    const IS_PREPARING = 0;

    public function findLast() {
        $sql = 'SELECT * FROM firstp2p_report WHERE is_pub = ' .self::IS_PUBLISH.' ORDER BY id DESC LIMIT 1';
        return $GLOBALS['db']->get_slave()->getRow($sql);
    }

    public function findByTerm($term) {
        $sql = "SELECT * FROM firstp2p_report WHERE term = '{$term}' AND is_pub = ".self::IS_PUBLISH.' ORDER BY id DESC LIMIT 1';
        return $GLOBALS['db']->get_slave()->getRow($sql);
    }
} // END class ReportModel extends BaseModel
