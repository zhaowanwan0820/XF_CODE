<?php
/**
 * 通行证修改手机号
 */
namespace core\tmevent\passport;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use libs\utils\Logger;
use core\service\user\PassportService;

class UpdateIdentityEvent extends GlobalTransactionEvent {
    /**
     * 通行证ID
     * @var int
     */
    private $ppId;

    /**
     * 旧手机号
     */
    private $oldMobile;

    /**
     * 新手机号
     */
    private $newMobile;

    public function __construct($ppId, $oldMobile, $newMobile) {
        $this->ppId = $ppId;
        $this->oldMobile = $oldMobile;
        $this->newMobile = $newMobile;
        $this->requestId = md5(uniqid(microtime(true),true));
    }

    /**
     * 发起存管-账户销户请求
     */
    public function commit() {
        $ret = PassportService::updatePassportInfo($this->ppId, $this->oldMobile, $this->newMobile, $this->requestId);
        if (!isset($ret['code']) || $ret['code'] != 0) {
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('ppId:%s, oldMobile:%s, newMobile:%s, requestId:%s, responseData:%s', $this->ppId, $this->oldMobile, $this->newMobile, $this->requestId, json_encode($ret)))));
            throw new \Exception($ret['msg']);
        }
        return true;
    }
}
