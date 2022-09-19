<?php
/**
 * MoneyLogDetail
 *
 * @date 2014-10-30
 * @author wangjiansong <wangjiansong@ucfgroup.com>
 */

namespace openapi\controllers\account;


use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\Error;
use NCFGroup\Protos\Ptp\RequestUserLoadList;
/**
 * 资金记录列表接口
 *
 * Class MoneyLog
 * @package api\controllers\account
 */
class LoadList extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
            "status" => array("filter" => "string", "message" => "status is error", "option" => array('optional' => true)),
            "offset" => array("filter" => "int", "message" => "offset is error", "option" => array('optional' => true)),
            "count" => array("filter" => "int", "message" => "count is error", "option" => array('optional' => true)),
            "compound" => array('filter' => 'int', "option" => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
        // status 状态check合并及参数合并
        if (empty($this->form->data['status'])) {
            $status = 0;
        } else {
            $status = $this->form->data['status'];
            $status_array = explode(',', $status);
            foreach ($status_array as $k => $item) {
                $item = intval($item);
                if ($item == 0) {
                    $status = 0;
                    break;
                } else if (!in_array($item, array(1, 2, 4, 5))) {
                    unset($status_array[$k]);
                }
            }
            $status = ($status == 0) ? 0 : (implode(',', $status_array));
        }
        $this->form->data['status'] = $status;
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        if (isset($data['compound']) && $data['compound'] == 1) {
            $typeStr = '0,1';
        } elseif (isset($data['compound']) && $data['compound'] == 2) {
            $typeStr = '2';
        } else {
            $typeStr = '0';
        }

        $userId = $userInfo->getUserId();
        $offset = empty($data['offset'])?intval($data['offset']):0;
        $count = empty($data['count']) ? 20 : intval($data['count']);

        $request = new RequestUserLoadList();
        $request->setUserId($userId);
        $request->setOffset($offset);
        $request->setCount($count);
        $request->setStatus($data['status']);
        $request->setCompound($typeStr);
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDeal',
            'method' => 'getUserLoadList',
            'args' => $request
        ));
        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get user coupon failed";
            return false;
        }
        $this->json_data = $response->toArray();
    }
}
