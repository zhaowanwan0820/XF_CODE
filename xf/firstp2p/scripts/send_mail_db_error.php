<?php
/**
 * 发送数据库错误日志
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 **/

error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(__FILE__). '/../libs/base/IComponent.php';
require_once dirname(__FILE__). '/../libs/queue/TQueue/DriverManager.php';
require_once dirname(__FILE__). '/../libs/base/Component.php';
require_once dirname(__FILE__). '/../libs/queue/TQueue/IQueueAction.php';
require_once dirname(__FILE__). '/../libs/queue/TQueue/TRedisQueue.php';
require_once dirname(__FILE__). '/../libs/queue/ThunderQueue.php';
$conf = require_once (dirname(__FILE__). '/../conf/components.conf.php');


set_time_limit(0);
$oldMemoryLimit = ini_get('memory_limit');
ini_set('memory_limit', '512M');
date_default_timezone_set('Asia/Shanghai'); $thunder = new \libs\queue\ThunderQueue(); $obj = new stdClass();
$obj->class = $conf['components']['thunder']['class'];
$obj->queueType = $conf['components']['thunder']['queueType'];
$obj->server = $conf['components']['thunder']['server'];
$thunder->init($obj);

$qName = 'db_error';
$str = '';
$capacity = $thunder->getCapacity($qName);
$data = $thunder->pop('db_error', $capacity);
if ($capacity > 1) {
    foreach ($data as $_qcontent) {
        $str .= $_qcontent . "<br/>\n";
    }
}
else {
    $str = $data;
}
// 如果有出错的记录，则发告警邮件
// 发送邮件
if ($capacity > 0 ) {
    $datetime = date('Y-m-d H:i');
    $subject = "数据库错误告警";
    $mail_content = '['.$datetime.'] 共有('.$capacity.')条记录<br/>';
    $mail_content .= $str;
    $sendTo = array('p2p_dev@ucfgroup.com');
    $mailReceivers = implode(',', $sendTo);
    mail($mailReceivers, $subject, $mail_content);
}
ini_set('memory_limit', $oldMemoryLimit);
