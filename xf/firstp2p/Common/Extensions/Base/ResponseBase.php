<?php
namespace NCFGroup\Common\Extensions\Base;

use NCFGroup\Common\Library\DateTimeLib;
class ResponseBase extends ProtoBufferBase
{
    // 返回时间
    // public $returnDatetime;
    // 返回代码
    // public $resCode;
    // 返回描述，可选
    // public $descp;

    public function __construct($response = null)
    {
        // $this->returnDatetime = DateTimeLib::getCurrentDateTime();
        // $this->resCode = AbstractErrorCodeBase::SUCCESS;
        // $this->descp = '';
    }

    public function flash($responseHandler)
    {
        $responseHandler->setContentType('application/json');
        $responseHandler->sendHeaders();
        $responseHandler->setContent(json_encode($this->toArray(), JSON_UNESCAPED_UNICODE));
        $responseHandler->send();
    }

    // public function toArray()
    // {
    //     $thisVars = get_object_vars($this);
    //     unset($thisVars['returnDatetime'], $thisVars['resCode'], $thisVars['descp']);
    //     return $thisVars;
    // }
}
