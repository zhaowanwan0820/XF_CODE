<?php
/**
 * @author longbo
 */
namespace core\service\partner\qianduola;

use core\service\partner\common\RequestBase;

class Request extends RequestBase
{

    protected function before() {
        $this->config['timeout'] = 3;
        $this->config['retries'] = 2;
        $this->config['output'] = 'format';

        $config = new Config();
        $clientId = $config->getHostConf('wx_client_id');
        $this->rData['getParams']['client_id'] = $clientId;

    }

}
