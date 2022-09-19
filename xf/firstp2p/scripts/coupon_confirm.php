<?php

require_once(dirname(__dir__) . '/app/init.php');

use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Protos\O2O\RequestGetPagableData;

use libs\rpc\Rpc;
use libs\utils\Logger;
use core\service\O2OService;

class CouponConfirm {

    public function __construct() {
        $this->_setRuntime();
        $this->_initialize();
    }

    private function _setRuntime() {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
    }

    private function _initialize() {
        $this->_status   ='1, 4';
        $this->_groupId  = 6670;
        $this->_cacheKey = "o2o:coupon:confirm:group:" . $this->_groupId;

        $this->_rpc = new Rpc();
        $this->_o2oService = new O2OService();
        $this->_redis = \SiteApp::init()->dataCache->getRedisInstance();

        $lastId = $this->_redis->get($this->_cacheKey);
        $this->_lastId = $lastId ? $lastId : 66211409;
    }

    private function _getConfirmList() {
        $request = new RequestGetPagableData();
        $request->setPageable(new Pageable(1, 1000, 'id ASC'));
        $request->setCondition(sprintf("couponGroupId = %d AND status IN (%s) AND id > %d", $this->_groupId, $this->_status, $this->_lastId));

        try {
            $response = $this->_o2oService->requestO2O('\NCFGroup\O2O\Services\Coupon', 'getCouponListByCond', $request);
            return $response['dataPage']['data'];
        } catch(\Exception $e) {
            Logger::error(sprintf("查询待核销列表失败, 券组id: %s", $this->_groupId));
            exit(255);
        }
    }

    private function _dealCounponItem($coupon) {
        $couponInfo = $this->_rpc->local('O2OService\setCouponConfirm', [$coupon['couponNumber'], $coupon['ownerUserId']]);
        if (empty($couponInfo)) {
            Logger::error(sprintf("核销券码失败, 数据: %s", json_encode($coupon)));
            exit(255);
        }

        $this->_lastId = $coupon['id'];
        $this->_redis->set($this->_cacheKey, $this->_lastId);
    }

    public function execute() {
        Logger::info(sprintf("开始处理, 券组id: %s, 开始Id: %s", $this->_groupId, $this->_lastId));

        do {
            $list = $this->_getConfirmList();
            if (empty($list)) {
                $this->_redis->set($this->_cacheKey, $this->_lastId);
                Logger::info(sprintf("处理完毕, 券组id: %s, 最后处理id: %s", $this->_groupId, $this->_lastId));
                return true;
            }

            foreach ($list as $item) {
                $this->_dealCounponItem($item);
            }
        } while (true);
    }

}

$instance = new CouponConfirm();
$instance->execute();
