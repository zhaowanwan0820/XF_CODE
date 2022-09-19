<?php
namespace NCFGroup\Common\Extensions\Base;

use NCFGroup\Common\Library\DateTimeLib;

abstract class AbstractRequestBase extends ProtoBufferBase
{

    /**
     * 请求的时间
     *
     * @var DateTime
     * @required
     */
    public $requestDatetime;

    public function __construct()
    {
        $this->requestDatetime = DateTimeLib::getCurrentDateTime();
    }
}

