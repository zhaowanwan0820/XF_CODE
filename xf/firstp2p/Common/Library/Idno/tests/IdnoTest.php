<?php

require dirname(__DIR__).'/Idno.php';
require dirname(__DIR__).'/Providers/Rongshu.php';
require dirname(dirname(__DIR__)).'/Curl.php';
require dirname(dirname(__DIR__)).'/CommonLogger.php';

use NCFGroup\Common\Library\Idno\Idno;

class IdnoTest extends \PHPUnit_Framework_TestCase
{

    public function testGet()
    {
        //正确
        $result = Idno::verifyName('王思聪', '210203198801034012');
        $this->assertEquals('0', $result['code']);

        //身份证号不匹配
        $result = Idno::verifyName('王思从', '210203198801034012');
        $this->assertEquals('-200', $result['code']);

        //身份证号格式错误
        $result = Idno::verifyName('王思聪', '210203198801034013');
        $this->assertEquals('-111', $result['code']);
    }

}
