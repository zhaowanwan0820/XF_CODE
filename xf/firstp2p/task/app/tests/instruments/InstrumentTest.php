<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/4/13
 * Time: 10:43
 */

use \NCFGroup\Task\Instrument\DistributionLock;

/**
 * @author dengyi
 *
 * @backupGlobals disabled
 */
class InstrumentTest extends PHPUnit_Framework_TestCase {

    public function testGetLockOnce() {
        getDI()->get('taskRedis')->del('nienie');
        $result = DistributionLock::getInstance()->getLockOnce("nienie");
        $this->assertEquals($result, true);
    }

    public function testGetLockTwice() {
        getDI()->get('taskRedis')->del('nienie');
        $result = DistributionLock::getInstance()->getLockOnce("nienie");
        $this->assertEquals($result, true);
        $result = DistributionLock::getInstance()->getLockOnce("nienie");
        $this->assertEquals($result, false);
    }

    public function testGetLockWait() {
        getDI()->get('taskRedis')->del('nienie');
        $result = DistributionLock::getInstance()->getLockWait("nienie");
        $this->assertEquals($result, true);
    }

    public function testGetLockWaitFail() {
        getDI()->get('taskRedis')->del('nienie');
        $result = DistributionLock::getInstance()->getLockOnce("nienie");
        $this->assertEquals($result, true);
        $result = DistributionLock::getInstance()->getLockWait("nienie", 2);
        $this->assertEquals($result, false);
    }

    public function testReleaseLock() {
        getDI()->get('taskRedis')->del('nienie');
        $result = DistributionLock::getInstance()->getLockOnce("nienie");
        $this->assertEquals($result, true);
        $result = DistributionLock::getInstance()->releaseLock("nienie");
        $this->assertEquals($result, true);
    }
}