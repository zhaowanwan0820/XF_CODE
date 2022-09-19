<?php

require dirname(__DIR__).'/AbstractTestCase.php';

class ApiTest extends AbstractTestCase
{

    public function testRequest()
    {
        //UcfPay
        $ret = Api::instance('ucfpay')->request('queryUserFundInfo', array('userId' => 1987));

        $this->assertEquals($ret['respCode'], '0002');
        $this->assertEquals($ret['respMsg'], '查询的基金签约信息不存在');
        $this->assertEquals($ret['status'], '0002');

        //FirstP2P
        $ret = Api::instance('firsp2p')->request('getUserInfo', array('userId' => 1987));

        //$this->assertEquals($ret['status'], '0002');
    }

}
