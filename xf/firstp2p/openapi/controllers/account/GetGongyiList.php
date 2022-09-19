<?php
/**
 * 公益标列表
 *
 * @author zhangzhuyan <zhangzhuyan@ucfgroup.com>
 */

namespace openapi\controllers\account;

use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Protos\Ptp\RequestGongyiList;
use libs\rpc\Rpc;
use libs\web\Form;
use openapi\conf\Error;
use openapi\controllers\BaseAction;


/**
 * 借款列表
 */
class GetGongyiList extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'page' => ['filter' => 'int'],
            'pageSize' => ['filter' => 'int'],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {

        $data = $this->form->data;
        $page = intval($data['page']) ?: 1;
        $pageSize = intval($data['pageSize']) ?: 20;
        $userInfo = $this->getUserByAccessToken();

        $request = new RequestGongyiList();
        try {
            $request->setPageable(new Pageable($page, $pageSize));
            $request->setUserId(intval($userInfo->userId));
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }

        $response = $GLOBALS['rpc']->callByObject([
            'service' => 'NCFGroup\Ptp\services\PtpDealLoad',
            'method' => 'getUserGongyiList',
            'args' => $request
        ]);

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get list failed";
            return false;
        }

        $this->json_data = $response;
        return true;
    }
}
