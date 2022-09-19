<?php
namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;

/**
 * ApplyRedeem
 * 申请赎回展现
 *
 * @uses BaseAction
 * @package default
 */
class ApplyRedeem extends AppBaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'id' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loadId = $data['id'];
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $dealLoad = $this->rpc->local('DealLoadService\getDealLoadDetail', array($loadId));
        if ($dealLoad['user_id'] != $user['id']) {
            $this->setErr('ERR_MANUAL_REASON', '无权限赎回此投资');
            return false;
        }
        if ($dealLoad['deal']['deal_type'] != 1) {
            $this->setErr('ERR_MANUAL_REASON', '此投资不可赎回');
            return false;
        }
        $firstTime = $this->rpc->local('DealCompoundService\getLatestRepayDay', array($dealLoad['deal_id']));
        $sum = $this->rpc->local('DealCompoundService\getCompoundMoneyByDealLoadId', array($loadId,$firstTime['repay_time']));
        $firstDay = to_date($firstTime['repay_time'], 'Y-m-d');

        $data = array();
        $data['id'] = $loadId;
        $data['title'] = "今日申请赎回，{$firstDay}到账";
        $data['sum'] = format_price($sum);
        $data['name'] = $dealLoad['deal']['name'];
        $data['money'] = format_price($dealLoad['money']);
        $data['interest'] = format_price($sum - $dealLoad['money']);
        $data['holiday'] = $firstTime['is_holiday'] ? '（由于节假日，到账日顺延至'.$firstDay.'）' : '';
        $data['firstDay'] = $firstDay;

        $this->tpl->assign('data', $data);
        //$this->json_data = $data;
    }

    public function _after_invoke() {
        $this->tpl->display($this->template);
    }
}
