<?php
/**
 * SmsModel.php
 *
 * @date 2015-05-19
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;


class SmsModel extends BaseNoSQLModel {

    /**
     * 配置的key值
     */
    protected static $config = 'sms';

    /**
     * collection名
     */
    protected static $collection = "data";

}