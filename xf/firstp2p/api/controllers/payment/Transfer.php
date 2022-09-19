<?php
namespace api\controllers\payment;

/**
 * 余额划转
 * @author longbo
 */
use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use NCFGroup\Common\Library\Idworker;
use libs\payment\supervision\Supervision;

class Transfer extends AppBaseAction
{
    const WX_TO_P2P = 1;
    const P2P_TO_WX = 2;

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required'),
            'money' => array('filter' => 'string'),
            'type' => array('filter' => 'int'),
            'dontTip' => array('filter' => 'int'),
            'site_id' => array('filter' => 'int'),
            'biz' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL');
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            $this->tpl->assign('error', $this->error);
            return false;
        }
        if (bccomp($data['money'], '0.01', 2) == -1) {
            $this->setErr('ERR_MANUAL_REASON', '转账金额不能低于0.01元');
            return false;
        }
        if(Supervision::isServiceDown()){ //降级不能划转
            $this->setErr('ERR_MANUAL_REASON', Supervision::maintainMessage());
            return false;
        }

        $svInfo = $this->rpc->local('SupervisionService\svInfo',array($user['id'], 0, $data['biz']));
        if (empty($svInfo['isSvUser'])) {
            return $this->setErr('ERR_MANUAL_REASON', '余额划转失败');
        }
        try {
            if (!empty($data['dontTip'])) {
                $this->rpc->local('SupervisionFinanceService\SetNotPromptTransfer', [$user['id']]);
            }
            $orderId = Idworker::instance()->getId();
            $type = (int) $data['type'];
            if ($type == self::WX_TO_P2P) {
                $limitAmount = $this->rpc->local('UserCarryService\getLimitAmountByUserId', array($user['id']));
                if (bccomp($data['money'], bcsub($user['money'], $limitAmount, 2), 2) == 1) {
                    return $this->setErr('ERR_MANUAL_REASON', '可用余额不足');
                }
                $transferRes = $this->rpc->local(
                    'P2pDealBidService\rechargeToBank',
                    array($orderId, $user['id'], $data['money'])
                );
            } elseif ($type == self::P2P_TO_WX) {
                if (bccomp($data['money'], $svInfo['svBalance'], 2) == 1) {
                    return $this->setErr('ERR_MANUAL_REASON', '可用余额不足');
                }
                $svInfo['isFreePayment'] = 1; //划转免密
                if (!empty($svInfo['isFreePayment'])) {
                    $transferRes = $this->rpc->local(
                        'P2pDealBidService\withdrawToSuper',
                        array($orderId, $user['id'], $data['money'])
                    );
                } else {
                    $transferUrl = sprintf(
                        $this->getHost()."/payment/Transit?params=%s",
                        urlencode(json_encode(['srv' => 'transfer', 'money' => $data['money'], 'return_url' => 'firstp2p://api?type=closeallpage']))
                    );
                    $transferRes = ['url' => $transferUrl];
                }
            }

            if ($transferRes === false) {
                return $this->setErr('ERR_MANUAL_REASON', '余额划转失败');
            } else {
                $this->json_data = $transferRes;
                return true;
            }
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $orderId,$user['id'], " errMsg:" . $e->getMessage())));
            return $this->setErr('ERR_MANUAL_REASON', '余额划转失败');
        }

    }
}
