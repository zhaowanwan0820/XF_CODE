<?php
namespace NCFGroup\Task\Gearman;

class GearMan extends \GearmanClient
{
	public static function getInstance()
    {
        static $ins;
        if (false == $ins instanceof self)
        {
            $ins = new self();
            foreach (getDI()->get('config')->taskGearman->serverInfos as $serverInfo) {
                $ins->addServer($serverInfo->ip, $serverInfo->port);
            }
        }
        return $ins;
    }
}
