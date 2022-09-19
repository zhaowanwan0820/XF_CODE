<?php

/**
 * @abstract openapi 获取快捷银行卡列表 
 * @author wangqunqiang <wangqunqiang@ucfgroup.com>
 * @date 2015-04-27
 */

namespace openapi\controllers\bank;

use libs\web\Form;
use openapi\controllers\BaseAction;

use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Protos\Ptp\RequestBankList;
/**
 *
 * Class BankList
 * @package openapi\controllers\bank
 */
class BankList extends BaseAction {

    public function init() {
        parent::init();
    }

    public function invoke() {
        try {
            //$request = new RequestBankList();
            $bankService = new \core\service\BankService();
            $result = $bankService->getFastPayBanks();
            if ($result['status'] != '') {
                throw new \Exception($result['msg']);
            }
        } catch (\Exception $e) {
            $this->errorCode = -1;
            $this->errorMsg = $e->getMessage();
            return false;
        }
        $this->json_data = $result['data'];
        return true;
    }

}
