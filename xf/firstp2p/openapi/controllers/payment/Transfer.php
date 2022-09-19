<?php
namespace openapi\controllers\payment;

/**
 * 余额划转
 * @author yanjun
 */
use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Logger;
use NCFGroup\Common\Library\Idworker;
use libs\payment\supervision\Supervision;

class Transfer extends BaseAction
{
    const WX_TO_P2P = 1;
    const P2P_TO_WX = 2;

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array('filter' => 'required'),
            'money' => array('filter' => 'string'),
            'dontTip' => array('filter' => 'int'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByAccessToken();
        if (empty($user)) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        if (bccomp($data['money'], '0.01', 2) == -1) {
            $this->setErr('ERR_MANUAL_REASON', '转账金额不能低于0.01元');
            return false;
        }

        if(Supervision::isServiceDown()){//降级不能划转
            $this->setErr('ERR_MANUAL_REASON', Supervision::maintainMessage());
            return false;
        }

        $svInfo = $this->rpc->local('SupervisionService\svInfo',array($user->userId));
        if (empty($svInfo['isSvUser'])) {
            return $this->setErr('ERR_MANUAL_REASON', '余额划转失败');
        }
        try {
            if (!empty($data['dontTip'])) {
                $this->rpc->local('SupervisionFinanceService\SetNotPromptTransfer', [$user->userId]);
            }
            $orderId = Idworker::instance()->getId();
            if (!empty($svInfo['isFreePayment'])) {
                $transferRes = $this->rpc->local(
                    'P2pDealBidService\withdrawToSuper',
                    array($orderId, $user->userId, $data['money'])
                );
            } else {
                $transferRes = ['transit' => ['srv' => 'transfer', 'money' => $data['money']]];
            }

            if ($transferRes === false) {
                $this->setErr('ERR_MANUAL_REASON', '余额划转失败');
                return false;
            } else {
                $this->json_data = $transferRes;
                return true;
            }
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $orderId,$user->userId, " errMsg:" . $e->getMessage())));
            $this->setErr('ERR_MANUAL_REASON', '余额划转失败');
            return false;
        }

    }
}
