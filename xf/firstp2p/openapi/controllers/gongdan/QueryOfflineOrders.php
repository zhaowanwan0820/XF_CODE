<?php
/**
 * 转账充值订单查询接口
 * @date 2019/3/18
 * @author: yangshuo5@ucfgroup.com
 */

namespace openapi\controllers\gongdan;

use openapi\controllers\BaseAction;
use libs\web\Form;
use libs\utils\Logger;

class QueryOfflineOrders extends BaseAction
{
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "userId" => array("filter" => "required", "message" => "userId is required"),
            "orderStatus" => array("filter" => "string",),
            "startDate" => array("filter" => "required", "message" => "startDate is required"),
            "endDate" => array("filter" => "required", "message" => "endDate is required"),
            "busType" => array("filter" => "string", ),
            "pageNo" => array("filter" => "required", "message" => "pageNo is required"),
            "pageSize" => array("filter" => "required", "message" => "pageSize is required"),
            "bankCardNo" => array("filter" => "string", "message" => "bankCardNo is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        $params = $this->form->data;

        try {
            $result = array();
            $requestResult = \SiteApp::init()
                ->dataCache
                ->call($this->rpc, 'local', ['PaymentCheckService\queryOfflineOrders', [$params]], 15*60, false, true);
            Logger::info('queryOfflineOrders result :' . json_encode($requestResult) . ' ' . __FILE__ . ' ' . __LINE__);
            if ( !empty($requestResult)) {
                $result['userId'] = $params['userId'];
                $result['recordList'] = $requestResult['pageList'];
            }
        } catch (\Exception $e) {
            Logger::error('queryOfflineOrdersError:'.$e->getMessage());
            $result = [];
        }

        $this->json_data = $result;
    }

}