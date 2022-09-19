<?php
/**
 * EmailModel.php
 *
 * @date 2015-05-19
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;


class EmailModel extends BaseNoSQLModel {

    /**
     * 配置的key值
     */
    protected static $config = 'email';

    /**
     * collection名
     */
    protected static $collection = "data";

}