<?php
namespace openapi\controllers\deal;

/**
 * 投资前尝试划转余额
 * @author yanjun5
 */
use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\Aes;

class PreBid extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'oauth_token' => array(
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
            'ecid' => array(
                'filter' => 'string',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
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
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        if (isset($data['ecid']) && $data['ecid'] != "") {//有加密数据传入
            $data['id'] = Aes::decryptForDeal($data['ecid']);
        } else {
            $data['id'] = intval($data['id']);
        }
        if (!deal_belong_current_site($data['id'])) {
            $this->setErr('2005', '站点来源错误');
            return false;
        }

        $user = $this->getUserByAccessToken();
        if (empty($user)) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }
        $money = $data['money'];
        if (bccomp($money, 0, 2) != 1) {
            $this->setErr('ERR_MONEY_FORMAT');
            return false;
        }
        if (deal_belong_current_site($data['id'])) {
            $dealInfo = $this->rpc->local('DealService\getDeal', array($data['id'], true));
        } else {
            $dealInfo = null;
        }
        if (!$dealInfo) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }

        try {
            $result = $this->rpc->local(
                'P2pDealBidService\preBid',
                array($user->userId, $dealInfo, $money, $data['source_type'], $data['coupon'], $data['site_id'])
            );
        } catch (\Exception $e) {
            $this->setErr('ERR_SYSTEM', $e->getMessage());
            return false;
        }

        $this->json_data = $result;
        return true;
    }
}
