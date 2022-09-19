<?php
/**
 * @author:longbo
 */
namespace core\service\partner\treefinance;

use core\service\partner\common\RequestBase;

class Request extends RequestBase
{

    protected function before() {
        $this->config['timeout'] = 5;
        $this->config['retries'] = 3;
    }


}
