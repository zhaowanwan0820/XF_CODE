<?php

require dirname(__DIR__).'/AbstractTestCase.php';

class AesLibTest extends AbstractTestCase
{

    /**
     * 加密
     */
    public function testEncode()
    {
        $data = '测试Aes加解密';
        $key = 'aaaaabbbbbcccc';

        $result = AesLib::encode($data, $key);

        $this->assertEquals($result, 'ExVOTxaEXg7quCO/pLTa9K712xYB72JZF5fQKMf/q4g=');
    }

    /**
     * 解密
     */
    public function testDecode()
    {
        $data = 'ExVOTxaEXg7quCO/pLTa9K712xYB72JZF5fQKMf/q4g=';
        $key = 'aaaaabbbbbcccc';

        $result = AesLib::decode($result, $key);

        $this->assertEquals($result, '测试Aes加解密');
    }

}
