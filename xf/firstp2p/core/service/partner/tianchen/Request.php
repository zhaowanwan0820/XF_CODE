<?php
/**
 * @author longbo
 */
namespace core\service\partner\tianchen;

use core\service\partner\common\RequestBase;

class Request extends RequestBase
{

    protected function before() {
        $this->config['timeout'] = 3;
        $this->config['retries'] = 2;
        $this->config['signType'] = 1;
        $this->config['requestJson'] = 1;
        $this->config['output'] = 'format';
        $this->config['responseFormat'] = [
                'code' => 'status',
                'codeVal' => '00000000',
                'msg' => 'info',
                'data' => 'data',
                ];

    }

}
