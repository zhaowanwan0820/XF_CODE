<?php
/**
 * @author longbo
 */
namespace core\service\partner\changtao;

use core\service\partner\common\RequestBase;

class Request extends RequestBase
{

    protected function before() {
        $this->config['timeout'] = 3;
        $this->config['retries'] = 2;
        $this->config['output'] = 'format';
        $this->config['responseFormat'] = [
                'code' => 'code',
                'codeVal' => '000',
                'msg' => 'codeDesc',
                'data' => 'returnData',
                ];


        $this->rData['getParams']['channelSource'] = 'WANGXIN';

    }

}
