<?php
namespace api\controllers\gold;

/**
 * 投资前尝试划转余额 
 * @author zhaohui
 */
use libs\web\Form;
use api\controllers\GoldBaseAction;
use libs\utils\Logger;
class PreBid extends GoldBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_AUTH_FAIL'
            ),
            'id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'source_type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'site_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'coupon' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'buy_price' => array(
                'filter' => 'required',
                'message' => 'buy_price is required',
            ),
            'buy_amount' => array(
                'filter' => 'required',
                'message' => 'buy_amount is required',
            ),
            'discount_amount' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            )
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        $deal_id = $data['id'];
        if (deal_belong_current_site($deal_id) && $deal_id != 1) {
            $dealInfo = $this->rpc->local('GoldService\getDealById', array($deal_id));
        } else {
            $dealInfo['data'] = $this->rpc->local('GoldService\getDealCurrent', array());
        }
        if (empty($dealInfo['data'])) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }

        //如果dealid == 1则认为是优金宝
        if ($deal_id == 1) {//优金宝
            $extPar = array('type'=>'gold_current','buyAmount'=>floatval($data['buy_amount']),'buyPrice'=>floatval($data['buy_price']));
        } else {//优长金
            $extPar = array('type'=>'gold','buyAmount'=>floatval($data['buy_amount']),'buyPrice'=>floatval($data['buy_price']));
        }

        // 费率需要提前计算好,是否有最低费率问题
        $buyFee = floorfix(bcmul($dealInfo['data']['buyerFee'], floatval($data['buy_amount']), 4), 2);
        $buyMoney = bcmul(floatval($data['buy_price']), floatval($data['buy_amount']), 4);
        $money = floorfix(bcadd($buyMoney, $buyFee,2), 2);

        //黄金都不报备，参数写死
        $dealInfo['data']['report_status'] = 0;
        try {
            $result = $this->rpc->local(
                'P2pDealBidService\preBid',
                array($user, $dealInfo['data'], $money, $data['source_type'], $data['coupon'], $data['site_id'],$extPar)
            );
        } catch (\Exception $e) {
            return $this->setErr(-1, $e->getMessage());
        }
        $this->json_data = $result;
    }
}
