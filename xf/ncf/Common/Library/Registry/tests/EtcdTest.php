<?php

ini_set('display_errors', 'On'); error_reporting(E_ALL);

require_once dirname(dirname(__DIR__)).'/Curl.php';
require_once dirname(dirname(__DIR__)).'/CommonLogger.php';
require_once dirname(dirname(__DIR__)).'/Registry/Etcd.php';

use NCFGroup\Common\Library\Registry\Etcd;

class EtcdTest extends \PHPUnit_Framework_TestCase
{

    const ETCD_HOST = 'http://localhost:2379';

    const ETCD_USER = 'root';

    const ETCD_PASSWORD = 'root';

    const SERVICE_PREFIX = '/example';

    public function testGetServiceInfo()
    {
        $etcd = new Etcd(array(self::ETCD_HOST), self::ETCD_USER, self::ETCD_PASSWORD);

        $reflection = new \ReflectionObject($etcd);
        $property = $reflection->getProperty('token');
        $property->setAccessible(true);
        $token = $property->getValue($etcd);
        $this->assertGreaterThan(10, strlen($token), "token is invalid:{$token}");

        $result = $etcd->getServiceInfo('/example');
        print_r($result);

        $this->assertNotEmpty($result, 'service info is empty');
        $this->assertArrayHasKey('IP', $result);
    }

    public function testRetry()
    {
        $etcd = new Etcd(array('wrong-host', self::ETCD_HOST), self::ETCD_USER, self::ETCD_PASSWORD);
        $result = $etcd->getServiceInfo('/example');
        print_r($result);

        $this->assertNotEmpty($result, 'service info is empty');
        $this->assertArrayHasKey('IP', $result);
    }

    /**
     * @expectedException \Exception
     **/
    public function testHostsEmpty()
    {
        $etcd = new Etcd(array(), self::ETCD_USER, self::ETCD_PASSWORD);
    }

    /**
     * @expectedException \Exception
     **/
    public function testConnectFailed()
    {
        $etcd = new Etcd(array('wrong-host'), self::ETCD_USER, self::ETCD_PASSWORD);
    }

    /**
     * @expectedException \Exception
     **/
    public function testAuthFailed()
    {
        $etcd = new Etcd(array(self::ETCD_HOST), self::ETCD_USER, 'wrong-password');
    }

    /**
     * @expectedException \Exception
     **/
    public function testPrefixEmptyException()
    {
        $etcd = new Etcd(array(self::ETCD_HOST), self::ETCD_USER, self::ETCD_PASSWORD);
        $etcd->getServiceInfo('');
    }

    /**
     * @expectedException \Exception
     **/
    public function testNotExistsException()
    {
        $etcd = new Etcd(array(self::ETCD_HOST), self::ETCD_USER, self::ETCD_PASSWORD);
        $etcd->getServiceInfo('/not-exists-prefix');
    }

}
