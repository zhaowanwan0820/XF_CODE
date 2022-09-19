<?php
namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use core\event\BaseEvent;
use libs\utils\Logger;
use core\service\partner\common\RequestBase;

/**
 * 异步请求第三方服务
 * @author longbo
 */
class PartnerRequestEvent extends BaseEvent
{
    public $requestServer;
    public $requestData;

    public function __construct(RequestBase $requestService, $requestData)
    {
        $this->requestService = $requestService;
        $this->requestData = $requestData;
    }

    public function execute()
    {
        try {
            $res = $this->requestService->setRequestData($this->requestData)->execute();
            Logger::info("PartnerReqData:".var_export($this->requestData,true).",PartnerReqRes:".var_export($res::$response, true));
            $resArr = $res::$response;
            if (isset($resArr['errorCode']) && $resArr['errorCode'] == 0) {
                return true;
            } else {
                throw new \Exception("ErrorRes:".json_encode($resArr));
            }
        } catch (\Exception $e) {
            Logger::error("PartnerRequestFailed.RequestData:".json_encode($this->requestData).". ErrMsg:".$e->getMessage());
            return false;
        }
    }

    public function alertMails() {
        return array('longbo@ucfgroup.com', 'yangshuo5@ucfgroup.com');
    }
}
