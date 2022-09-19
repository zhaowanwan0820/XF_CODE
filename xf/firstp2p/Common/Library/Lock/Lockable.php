<?php
namespace NCFGroup\Common\Library\Lock;

/**
 * Lockable 悲观锁接口
 *
 * @author jingxu<jingxu@ucfgroup.com>
 * @package libs\lock
 */
interface Lockable
{
    const DEFAULT_TIMEOUT_SEC = 3;
    const WAITTIME_MS = 500;

    public function getLock($key, $timeout_sec = self::DEFAULT_TIMEOUT_SEC);
    public function releaseLock($key);
}
