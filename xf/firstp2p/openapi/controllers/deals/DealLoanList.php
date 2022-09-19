<?php
/**
 * @abstract   openapi标项目投资列表接口
 * @author zhangzhuyan <zhangzhuyan@ucfgroup.com>
 */

namespace openapi\controllers\deals;

use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Protos\Ptp\RequestDealLoanList;
use NCFGroup\Protos\Ptp\ResponseDealLoanList;
use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Aes;

class DealLoanList extends BaseAction
{
    private static $forbidDealStatus = [2, 3, 4, 5];

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'dealId' => ['filter' => 'int', "message" => "dealId is error"],
            'ecid' => ["filter" => "string", "message" => "dealId is error"],
            'page' => ['filter' => 'int'],
            'pageSize' => ['filter' => 'int'],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        if(isset($data['ecid']) && $data['ecid'] != "") {//有加密数据传入
            $dealId = Aes::decryptForDeal($data['ecid']);
        } else {
            $dealId = intval($this->form->data['dealId']);
        }

        if ($dealId <= 0) {
            $this->setErr("ERR_PARAMS_ERROR", "dealId is error");
            return false;
        }

        $page = intval($data['page']) ?: 1;
        $pageSize = intval($data['pageSize']) ?: 20;

        $request = new RequestDealLoanList();
        try {
            $request->setDealId($dealId);
            $request->setPageable(new Pageable($page, $pageSize));
            $request->setForbidDealStatus(self::$forbidDealStatus);
            $userInfo = $this->getUserByAccessToken();
            if (is_object($userInfo) && !$userInfo->resCode) {
                $request->setUserId($userInfo->getUserId());
            }
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }

        $response = new ResponseDealLoanList();
        $response = $GLOBALS['rpc']->callByObject([
            'service' => 'NCFGroup\Ptp\services\PtpDeal',
            'method' => 'getDealLoanList',
            'args' => $request
        ]);

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get list failed";
            return false;
        }

        $this->json_data = $response->getDataPage()->toArray();
        return true;
    }
}
