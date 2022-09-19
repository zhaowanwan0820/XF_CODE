<?php

namespace api\controllers\duotou;

use libs\utils\Logger;
use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\UserService;
use core\service\duotou\DtP2pDealBidService;
use core\service\supervision\SupervisionFinanceService;
use core\service\user\VipService;
use core\service\o2o\CouponService;
use core\enum\CouponGroupEnum;

/**
 * 多投验密投资接口
 **/
class DuotouBidReturn extends DuotouBaseAction
{
    protected $useSession = true;
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_GET_USER_FAIL',
            ),
            'orderId' => array(
                'filter' => 'required',
                'message' => 'orderId缺失',
            ),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $userInfo = $this->user;
        $formData = $this->form->data;
        $orderId = trim($formData['orderId']);
        if (empty($orderId)) {
            $this->setErr('ERR_SYSTEM', '缺少OrderId参数');
        }

        try {
            $orderRes = (new SupervisionFinanceService())->orderSearch($orderId);
            Logger::info('DuotouBidOrderRes:'.json_encode($orderRes));

            if($orderRes['status'] == 'F' && $orderRes['respCode'] == '1035'){
                $this->setErr('ERR_SYSTEM', '投资进行中,请稍后查看资金记录');
            }

            $status = 0;
            if ($orderRes['status'] == 'S' && isset($orderRes['data'])) {
                $status = $orderRes['data']['status'];
            }
            $result = (new DtP2pDealBidService())->dealBidForSecret($orderId, $userInfo['id'], $status);
        } catch (\Exception $e) {
            $this->setErr('ERR_SYSTEM', '投资失败:'.$e->getMessage());
        }

        if ($result['errCode'] != 0) {
            $this->setErr('ERR_DEAL_FORBID_BID', $result['errMsg']);
        }

        // 投资记录id
        $load_id = $result['data']['loadId'];//投资记录id
        $isFirstInvest = $result['data']['isFirst'];//是否首次投资

        $prizeType = '';
        $prizeTitle = '';
        $prizeDesc = '';
        $prizeUrl = '';
        if ($userInfo['isFromWxlc']) {
            // 获取o2o的触发结果
            $action = $isFirstInvest ? CouponGroupEnum::TRIGGER_DUOTOU_FIRST_DOBID : CouponGroupEnum::TRIGGER_DUOTOU_REPEAT_DOBID;
            $rpcParams = array($userInfo['id'], $action, $load_id, CouponGroupEnum::CONSUME_TYPE_DUOTOU);
            $o2oRes = CouponService::getFormatInfoWithGroupList(
                $userInfo['id'],
                $action,
                $load_id,
                $formData['token'],
                CouponGroupEnum::CONSUME_TYPE_DUOTOU,
                $this->isWapCall()
            );

            $prizeType = $o2oRes['prizeType'];
            $prizeTitle = $o2oRes['prizeTitle'];
            $prizeDesc = $o2oRes['prizeDesc'];
            $prizeUrl = $o2oRes['prizeUrl'];
        }

        $repayStartTime = "";
        $vipInfo = VipService::getVipInfo($userInfo['id']);
        $raiseInterest = 0;
        if ($vipInfo) {
            $raiseInterest = VipService::getVipInterest($vipInfo['service_grade']);
        }

        $res = array(
            'money' => number_format($result['data']['money'], 2),
            'projectName' => $result['data']['projectName'],
            'repayStartTime' => $repayStartTime,
            'prize_type' => $prizeType,
            'prize_url' => $prizeUrl,
            'prize_title' => $prizeTitle,
            'prize_desc' => $prizeDesc,
            'bonus_ttl' => 0,
            'bonus_url' => '',
            'bonus_face' => '',
            'bonus_title' => '',
            'bonus_content' => '',
            'bonus_bid_finished' => '',
            'vipPoint' => '加入次日开始发放',
            'vipInfo' => ($raiseInterest > 0) ? '加入次日开始发放' : '',
            'isFirstInvest' => intval($isFirstInvest),
            'res' => $isFirstInvest ? '恭喜您已完成首次加入' : '加入成功'
        );

        $this->json_data = $res;
    }
}
