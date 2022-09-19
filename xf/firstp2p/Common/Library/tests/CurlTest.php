<?php

require dirname(__DIR__).'/Curl.php';

use NCFGroup\Common\Library\Curl;

class CurlTest extends \PHPUnit_Framework_TestCase
{

    public function testGet()
    {
        //https
        $curl = Curl::instance();
        $result = $curl->get('https://service.sfxxrz.com/simpleCheck.ashx');
        $this->assertStringStartsWith('{', $result);
        $this->assertEquals('200', $curl->resultInfo['code']);

        //http
        $curl = Curl::instance();
        $result = $curl->get('http://rongapi.cn/idcheck');
        $this->assertStringStartsWith('{', $result);
        $this->assertEquals('403', $curl->resultInfo['code']);

        //302
        $curl = Curl::instance();
        $result = $curl->get('https://www.baidu.com/aaaaa');
        $this->assertEquals('302', $curl->resultInfo['code']);
    }

    public function testPost()
    {
        //https
        $curl = Curl::instance();
        $result = $curl->setTimeout(1)->post('https://service.sfxxrz.com/simpleCheck.ashx', array());
        $this->assertStringStartsWith('{', $result);
        $this->assertEquals('200', $curl->resultInfo['code']);

        //http
        $curl = Curl::instance();
        $result = $curl->setTimeout(1)->post('http://rongapi.cn/idcheck', array());
        $this->assertStringStartsWith('{', $result);
        $this->assertEquals('403', $curl->resultInfo['code']);

        //302
        $curl = Curl::instance();
        $result = $curl->setTimeout(1)->post('https://www.baidu.com/aaaaa', array());
        $this->assertEquals('302', $curl->resultInfo['code']);
    }

}
