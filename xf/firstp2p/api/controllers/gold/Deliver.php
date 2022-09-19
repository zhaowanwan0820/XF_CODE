<?php

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;
use core\service\GoldDeliverService;
use NCFGroup\Protos\Gold\Enum\CommonEnum;
use NCFGroup\Common\Library\Idworker;
use core\service\MobileCodeService;

class Deliver extends GoldBaseAction {


    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'goodsAmount' => array('filter' => 'required', 'message' => 'goodsAmount is required'),
            'goodsDetails' => array('filter' => 'required', 'message' => 'goodsDetails is required'),
            'ticket' => array('filter' => 'required', 'message' => 'ticket is required'),
            'code' => array('filter' => 'required','message' => 'code is required')
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
            return false;
        }

    }

    public function invoke() {

        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
        }

        //验证验证码
        $vcode = \SiteApp::init()->cache->get('gold_deliver_checkverfycode'.$user['mobile']);
        if ($data['code'] != $vcode) {
            $this->setErr('ERR_MANUAL_REASON','验证码错误');
            return false;
        }
        //验证ticket
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $ticketRes = $redis->get($data['ticket']);
        if ($ticketRes != $user['id'] || empty($ticketRes)) {
            $this->setErr('ERR_MANUAL_REASON','请不要重复提交订单');
            return false;
        }

        //检查是否授权
        $res = $this->rpc->local('GoldService\isAuth', array($user['id']));
        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON','获取用户授权信息失败');
            return false;
        }

        if ($res['errCode'] == 0 && !$res['data']) {
            $this->setErr('ERR_MANUAL_REASON','用户未授权，不能提金');
            return false;
        }

        $goldDeliverService = new GoldDeliverService(
                $user['id'],
                floatval($data['goodsAmount']),
                json_decode(urldecode($data['goodsDetails']),true),
                $data['ticket']
            );

        $res = $goldDeliverService->doDeliver();

        if ($res['errCode'] != 0) {
            $this->setErr('ERR_MANUAL_REASON',$res['msg']);
            return false;
        }
        $redis->del($data['ticket']);
        \SiteApp::init()->cache->delete('gold_deliver_checkverfycode'.$user['mobile']);
        if (empty($res['data']['url'])){
            $res['data']="/gold/Error?token=".$data['token']."&orderId=".$res['data']['orderId'];
        }else{
            $res['data']=$res['data']['url'];
        }
        $this->json_data = $res['data'];
    }

}
