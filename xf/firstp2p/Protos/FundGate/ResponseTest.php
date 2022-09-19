<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

class ResponseTest extends ResponseBase 
{
    public $res;

    public function __construct($res)
    {
        $this->res = $res;
    }
}
