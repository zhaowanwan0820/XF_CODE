<?php
/**
 * 通行证修改实名信息
 */

namespace core\tmevent\passport;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\PassportService;

class UpdateCertEvent extends GlobalTransactionEvent {

    /**
     * 通行证ID
     * @var int
     */
    private $ppId;

    /**
     * 旧身份信息
     */
    private $oldIdInfo;

    /**
     * 新身份信息
     */
    private $newIdInfo;

    public function __construct($ppId, $oldIdInfo, $newIdInfo) {
        $this->ppId = $ppId;
        $this->oldIdInfo = $oldIdInfo;
        $this->newIdInfo = $newIdInfo;
        $this->requestId = md5(uniqid(microtime(true),true));
    }

    /**
     * 发起存管-账户销户请求
     *
     */
    public function commit() {
        $passport = new PassportService();
        $res = $passport->updateCert($this->ppId, $this->oldIdInfo, $this->newIdInfo, $this->requestId);
        if (!$res) {
            throw new \Exception('修改通行证手机号失败');
        }
        return true;
    }
}
