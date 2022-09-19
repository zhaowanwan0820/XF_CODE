<?php

require dirname(__DIR__).'/AbstractTestCase.php';

class SignatureLibTest extends AbstractTestCase
{

    /**
     * 生成签名
     */
    public function testGenerate()
    {
        $salt = 'XFJRJT';
        $params = array(
            'id' => '19999',
            'name' => 'beijing',
            'time' => '2014-10-12',
        );

        $signature = SignatureLib::generate($params, $salt);

        $this->assertEquals($signature, '9f68335203e0208c84aa81aeeddf9afb');
    }

    /**
     * 验证签名
     */
    public function testVerify()
    {
        $salt = 'XFJRJT';

        //正常情况
        $params = array(
            'id' => '19999',
            'name' => 'beijing',
            'time' => '2014-10-12',
            'sign' => '9f68335203e0208c84aa81aeeddf9afb',
        );

        $result = SignatureLib::verify($params, $salt);
        $this->assertTrue($result);

        //签名错误
        $params = array(
            'id' => '19999',
            'name' => 'beijing',
            'time' => '2014-10-12',
            'sign' => '9f68335203e0208c84aa81aeeddf9afc',
        );

        $result = SignatureLib::verify($params, $salt);
        $this->assertFalse($result);
    }

}
