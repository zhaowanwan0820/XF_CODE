<?php
/**
 * @author:longbo
 */
namespace core\service\partner\xianghua;

use core\service\partner\common\RequestBase;

class Request extends RequestBase
{

    protected function before() {
        $this->config['timeout'] = 5;
        $this->config['retries'] = 3;
        $config = new Config();
        $clientId = $config->getHostConf('client_id');
        $this->rData['getParams']['client_id'] = $clientId;
    }


}
