<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use NCFGroup\Task\Events\EventInterceptor;

/**
 * 异步任务父类
 * 实现了对数据库的连接
 * BaseEvent
 * @package default
 * @author yangqing <yangqing@ucfgroup.com>
 */
abstract class BaseEvent implements AsyncEvent,EventInterceptor
{

    private $_eventStartTime = 0;

    public function before()
    {
        $this->_eventStartTime = microtime(true);
        \libs\utils\Logger::info('TaskEventStart. event:'.get_called_class());

        $GLOBALS['db'] = \libs\db\Db::getInstance('firstp2p');

        use_config_db();
    }

    public function after()
    {
        \libs\db\Db::destroyInstance('firstp2p');
        \libs\db\Db::destroyInstance('firstp2p', 'slave');

        \libs\db\Model::destroyInstance();

        \libs\utils\Logger::info('TaskEventEnd. event:'.get_called_class().', cost:'.round(microtime(true) - $this->_eventStartTime, 4).'s');
    }

    abstract public function execute();
    abstract public function alertMails();
}
