<?php
namespace openapi\controllers\cfp;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestDeals;
use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
class UsefulDeals extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!is_object($userInfo) || $userInfo->resCode) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $pageNo = isset($data['pageNo']) ? $data['pageNo'] : 1;
        $pageSize = isset($data['pageSize']) ? $data['pageSize'] : 10;
        $type = isset($data['type']) ? intval($data['type']) : 0; // 默认热标
        // 只支持上线标,以后可能修改为支持待上线标，暂留 -By wangjiansong@
        $type = 0;

        $request = new SimpleRequestBase();
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpCfp',
            'method' => 'getUsefulDeals',
            'args' => $request
        ));

        $dataPage = $response->getDataPage();
        $dealProtos = $dataPage->getContent();
        $deals = array();
        foreach ($dealProtos as $proto) {
            $tmp = array();
            $tmp['id'] = $proto->id;
            $tmp['name'] = $proto->name;
            $tmp['tagName'] = $proto->tagName;
            $tmp['timeLimit'] = $proto->timeLimit;
            $tmp['total'] = number_format($proto->getTotal() / 10000, 2).'万';;
            $tmp['minLoan'] = $proto->minLoan >= 10000 ? number_format($proto->minLoan / 10000, 2).'万' : number_format($proto->minLoan, 0).'元';
            $tmp['repayment'] = $proto->repayment;
            $tmp['rate'] = $proto->rate;
            $tmp['canLoan'] = number_format($proto->getCanLoan(), 2);
            $tmp['projectAmount'] = $proto->projectAmount == '-' ? '-' : number_format($proto->projectAmount / 10000, 2).'万';
            $tmp['projectLoan'] = $proto->projectLoan == '-' ? '-' : number_format($proto->projectLoan / 10000, 2).'万';
            $deals[] = $tmp;
        }

        $data = array();
        $data['pageNo'] = $dataPage->getPageNo();
        $data['pageSize'] = $dataPage->getPageSize();
        $data['totalPage'] = $dataPage->getTotalPage();
        $data['totalSize'] = $dataPage->getTotalSize();
        $data['deals'] = $deals;

        $this->json_data = $data;
        return true;
    }

}

