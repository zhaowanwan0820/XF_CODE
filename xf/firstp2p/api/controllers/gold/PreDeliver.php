<?php
namespace api\controllers\gold;

/**
 * 提金前尝试划转余额
 */
use libs\web\Form;
use api\controllers\GoldBaseAction;
use libs\utils\Logger;

class PreDeliver extends GoldBaseAction
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
                'goodsAmount' => array(
                    'filter' => 'required',
                    'message' => 'goodsAmount is required',
                    ),
                'goodsDetails' => array(
                     'filter' => 'required',
                     'message' => 'goodsDetails is required'
                    ),
                );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $params=array();
        $user = $this->getUserByToken();
        if (empty($user)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        $dealInfo['data'] = $this->rpc->local('GoldService\getDealCurrent', array());
        if (empty($dealInfo['data'])) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }

        $goods_amount = floatval($data['goodsAmount']);
        if(bccomp($goods_amount, 0) < 1){
            $this->setErr(-1,'提金克重不能小于0.00克');
            return false;
        }
        $extPar = array('type'=>'gold_deliver','goodsAmount'=>$goods_amount,'goodsDetails'=>$data['goodsDetails']);
        // 提金手续费
        $params['goodsDetails']=json_decode(urldecode($data['goodsDetails']),true);
        if(empty($params['goodsDetails'])){
            $this->setErr(-1,'请选择金条');
            return false;
        }
        $params['fee']=$dealInfo['data']['receiveFee'];
        $res= $this->rpc->local('GoldService\getFeeByModel',array($params));
        if($res===false){
            $this->setErr(-1,'商品无效');
            return false;
        }
        $deliverFee=floorfix($res,2);
        //黄金都不报备，参数写死
        $dealInfo['data']['report_status'] = 0;
        try {
            $result = $this->rpc->local(
                'P2pDealBidService\preBid',
                array($user, $dealInfo['data'], $deliverFee, 0, 0, 1,$extPar)
            );
        } catch (\Exception $e) {
            return $this->setErr(-1, $e->getMessage());
        }

        $this->json_data = $result;
    }
}
