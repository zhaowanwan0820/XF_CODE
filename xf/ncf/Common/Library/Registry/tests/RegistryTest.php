<?php

ini_set('display_errors', 'On'); error_reporting(E_ALL);

require_once dirname(dirname(__DIR__)).'/Curl.php';
require_once dirname(dirname(__DIR__)).'/CommonLogger.php';
require_once dirname(dirname(__DIR__)).'/Registry/Etcd.php';
require_once dirname(dirname(__DIR__)).'/Registry/Registry.php';

use NCFGroup\Common\Library\Registry\Registry;

class RegistryTest extends \PHPUnit_Framework_TestCase
{

    public function testGetServiceInfo()
    {
        Registry::$config = array(
            'hosts' => array('http://localhost:2379'),
            'username' => 'root',
            'password' => 'root',
        );
        $result = Registry::getServiceInfo('/example');
        print_r($result);

        $this->assertNotEmpty($result, 'service info is empty');
        $this->assertArrayHasKey('IP', $result);
        $this->assertArrayHasKey('Port', $result);
    }

    public function testGetServiceInfoCache()
    {
        Registry::$config = array(
            'hosts' => array('http://localhost:2379'),
            'username' => 'root',
            'password' => 'root',
        );
        Registry::getServiceInfo('/example');
        $this->assertArrayHasKey('/example', Registry::$serviceInfo, 'service should exists');

        $result = Registry::getServiceInfo('/example');
        $this->assertArrayHasKey('IP', $result);
    }

}
