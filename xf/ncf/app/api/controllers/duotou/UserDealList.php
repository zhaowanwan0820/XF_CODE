<?php
/**
 * UserDealList controller class file.
 *
 * @author 赵辉<zhaohui3@ucfgroup.com>
 * @date   2016-03-01
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use NCFGroup\Protos\Duotou\RequestCommon;

/**
 * 多投-用户已投项目列表
 *
 * @packaged default
 * @author 赵辉<zhaohui3@ucfgroup.com>
 **/
class UserDealList extends DuotouBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'page_num' => array(
                'filter' => 'int',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'page_size' => array(
                'filter' => 'int',
                'message' =>'ERR_PARAMS_VERIFY_FAIL',
            ),
            'deal_id' => array(
                'filter' => 'int',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'status' => array(
                'filter' => 'int',
                'message' =>'ERR_PARAMS_VERIFY_FAIL',
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $user = $this->user;

        $vars = array();
        $vars['userId'] = $user['id'];
        if ($this->form->data['page_num']) {
            $vars['pageNum'] = $this->form->data['page_num'];//默认为1，如果大于实际值，则返回结果为空数据
        }
        if ($this->form->data['page_size']) {
            $vars['pageSize'] = $this->form->data['page_size'];//定义每个页面的记录数，默认为20
        }
        if ($this->form->data['deal_id']) {
            $vars['dealId'] = $this->form->data['deal_id'];
        }
        if ($this->form->data['status']) {
            $vars['status'] = $this->form->data['status'];//0-投资成功 1-切片成功 2-匹配成功 3-赎回申请中 4-赎回成功 5-已结清
        }

        $response = $this->callByObject(array(
            'service' => 'NCFGroup\Duotou\Services\DealLoan',
            'method' => 'getDealLoans',
            'args' => $vars,
        ));

        if ($response['errCode']) {
            $this->setErr('ERR_SYSTEM',$response['errMsg'].'['.$response['errCode'].']');
        }

        $res['totalPage'] = $response['data']['totalPage'];
        $res['totalNum'] = $response['data']['totalNum'];
        $res['list'] = $response['data']['data'];
        $this->json_data = $res;
    }
}
