<?php
namespace NCFGroup\Protos\FundGate;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

class RequestTest extends AbstractRequestBase
{
    public $test1;
    public $test2;

    public function __construct($test1, $test2) 
    {
        $this->test1 = $test1;
        $this->test2 = $test2;
    }
}
