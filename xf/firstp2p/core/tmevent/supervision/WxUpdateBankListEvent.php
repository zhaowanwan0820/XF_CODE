<?php
/**
 * 网信理财-修改银行支行信息Event
 *
 */

namespace core\tmevent\supervision;

use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use core\service\BanklistService;
use core\dao\BanklistModel;

class WxUpdateBankListEvent extends GlobalTransactionEvent {
    /**
     * 参数列表
     * @var array
     */
    private $data;
    /**
     * 参数列表-旧
     * @var array
     */
    private $oldData;

    public function __construct($data, $oldData) {
        $this->data = $data;
        $this->oldData = $oldData;
        $this->bankListService = new BanklistService();
    }

    /**
     * 网信理财-修改银行支行信息
     */
    public function execute() {
        return $this->bankListService->saveBankListById($this->data);
    }

    public function rollback() {
        if (empty($this->oldData)) {
            $this->oldData = BanklistModel::instance()->findViaSlave($this->data['id'], '*');
        }
        return $this->bankListService->saveBankListById($this->oldData);
    }
}