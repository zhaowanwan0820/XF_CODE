<?php

/**
 * @abstract openapi  获得银行信息列表
 * 
 */

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use core\service\BankService;

/**
 * 获得银行信息列表
 *
 * Class GetBankList
 * @package openapi\controllers\account
 */
class GetBankList extends BaseAction {

    public function init() {
        parent::init();
    }

    public function invoke() {
        $request = new SimpleRequestBase();

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpBank',
            'method' => 'getBankList',
            'args' => $request
        ));
        $this->json_data = $response;
    }
}
