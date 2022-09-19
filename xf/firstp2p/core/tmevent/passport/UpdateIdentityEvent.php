<?php
/**
 * 通行证修改手机号
 */

namespace core\tmevent\passport;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\PassportService;
use core\dao\WangxinPassportModel;

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
        // 同步更新本地通行证
        $res = WangxinPassportModel::instance()->updatePassportByPPid($this->ppId, ['identity' => $this->newMobile]);
        if (!$res) {
            throw new \Exception('本地通行证信息更新失败');
        }
        $passport = new PassportService();
        $res = $passport->updateIdentity($this->ppId, $this->oldMobile, $this->newMobile, $this->requestId);
        if (!$res) {
            throw new \Exception('修改通行证手机号失败');
        }
        return true;
    }
}
