<?php
/**
 * 验密投资轮询接口
 */

namespace web\controllers\deal;

use core\service\dealload\DealLoadService;
use core\service\deal\P2pIdempotentService;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\web\Url;
use core\enum\P2pIdempotentEnum;
use core\service\user\UserService;


class BidSecretCallBack extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'orderId' => array('filter' => 'string', 'optional' => true),
         );
        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg(), "", 1);
        }
    }

    /**
     * status
     *  0 -- 处理异常
     *  1 -- 处理中
     *  2 -- 投资成功
     *  3 -- 投资失败
     * @return bool|void
     */
    public function invoke() {
        $user = $GLOBALS['user_info'];

        $data = array(
            0 => array('status'=>0,'msg'=>'处理中','data'=>''),
            1 => array('status'=>1,'msg'=>'系统异常','data'=>''),
            2 => array('status'=>2,'msg'=>'投资成功','data'=>''),
            3 => array('status'=>3,'msg'=>'出借失败,请稍后查看资金记录','data'=>''),
        );

        if(!$user){
            return ajax_return($data[1]);
        }

        $orderId = $this->form->data['orderId'];

        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);


        if(!$orderInfo){
            return ajax_return($data[0]); // 可能订单还没有保存ajax请求已发出
        }
        if($orderInfo['loan_user_id'] != $user['id']){
            return ajax_return($data[1]);
        }

        if($orderInfo['status'] != P2pIdempotentEnum::STATUS_CALLBACK){
            return ajax_return(($data[0]));
        }

        if($orderInfo['status'] == P2pIdempotentEnum::RESULT_WAIT){
            return ajax_return(($data[0]));
        }elseif($orderInfo['result'] == P2pIdempotentEnum::RESULT_SUCC){
            $data[2]['data'] = $this->getSuccessInfoByOrderType($orderInfo);
            return ajax_return($data[2]);
        }else{
            ajax_return($data[3]);
        }
    }

    private function getSuccessInfoByOrderType($orderInfo){
        if($orderInfo['type'] == 1){
            return $this->getP2pBidSuccessInfo($orderInfo);
        }else{
            return $this->getDtBidSuccessInfo($orderInfo);
        }
    }

    private function getDtBidSuccessInfo($orderInfo){
        $data['money'] = $orderInfo['money'];
        $data['url'] = Url::gene("finplan", "dtsuccess",array('id' => $orderInfo['order_id']));
        return $data;
    }

    private function getP2pBidSuccessInfo($orderInfo){
        $deal = \core\dao\deal\DealModel::instance()->find($orderInfo['deal_id']);
        $user = UserService::getUserById($orderInfo['loan_user_id']);
        $money = $orderInfo['money'];
        $orderParams = json_decode($orderInfo['params'],true);
        $dealId = $deal['id'];
        $loadId = $orderInfo['load_id'];

        $otherParams = array(
            'siteId' => $orderParams['siteId'],
            'discountId' => $orderParams['discountId'],
            'discountGoodsPrice' =>  $orderParams['discountGoodsPrice'],
            'discountGoodsType' =>  $orderParams['discountGoodsType'],
        );

        $dealLoadService = new DealLoadService();
        $jumpData = $dealLoadService->getJumpDataAfterBid($user,$loadId,$dealId,$money,$otherParams);
        $data['money'] = $money;
        $data['url'] = Url::gene("deal", "success", $jumpData);
       return $data;
    }
}
