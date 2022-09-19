<?php

/**
 * Detail.php
 *
 * @date 2014-03-21
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\DealLoanTypeService;

/**
 * 订单详情页面接口
 *
 * Class Detail
 * @package api\controllers\deals
 */
class InvestList extends AppBaseAction
{

    private $_forbid_deal_status;

    //const IS_H5 = true;

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'dealId' => array(
                'filter' => 'required',
                'message' => 'dealId is required',
            ),
            'pageNum' => array(
                'filter' => 'int',
                'message' => 'pageNum must int',
                'option' => array('optional' => true)
            ),
            'pageSize' => array(
                'filter' => 'int',
                'message' => 'pageNum must int',
                'option' => array('optional' => true)
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $pageNum = intval($data['pageNum']) ? intval($data['pageNum']) : '';
        $pageSize = intval($data['pageSize']) ? intval($data['pageSize']) : '';
        $res = $this->rpc->local('GoldService\getDealLog', array($data['dealId'], $pageNum, $pageSize));
        $this->json_data = $res;

    }

}