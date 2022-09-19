<?php
namespace api\controllers\deal;

/**
 * 投资前尝试划转余额
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\deal\P2pDealBidService;
use core\service\deal\DealService;
use core\service\risk\RiskAssessmentService;
use libs\utils\Logger;

class PreBid extends AppBaseAction
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
            'money' => array(
                'filter' => 'reg',
                'message' => 'ERR_MONEY_FORMAT',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                ),
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
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->user;

        $deal_id = $data['id'];
        $money = $data['money'];
        if (bccomp($money, 0, 2) != 1) {
            $this->setErr('ERR_MONEY_FORMAT');
        }

        if (deal_belong_current_site($deal_id)) {
            $dealService = new DealService();
            $dealInfo = $dealService->getDeal($deal_id, true);
        } else {
            $dealInfo = null;
        }

        if (!$dealInfo) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
        }

        if ($user['is_enterprise_user'] != 1 && $user['idcardpassed'] == 1){
            $RiskAssessmentService = new RiskAssessmentService();
            $riskData = $RiskAssessmentService->getUserRiskAssessmentData($user['id'], $money);
            //风险评估有效期
            if (!$riskData['isRiskValid']) {
                $this->setErr('ERR_MANUAL_REASON', '您的风险评估结果已超过有效期');
            }

            //总出借限额
            if ($riskData['isTotalLimitInvest']) {
                $this->setErr('ERR_MANUAL_REASON', '您预约金额超出总出借金额');
            }
        }

        try {
            $p2pDealBidService = new P2pDealBidService();
            $result = $p2pDealBidService->preBid($user, $dealInfo, $money, $data['source_type'], $data['coupon'], $data['site_id']);
        } catch (\Exception $e) {
            return $this->setErr(-1, $e->getMessage());
        }

        $this->json_data = $result;
    }
}
