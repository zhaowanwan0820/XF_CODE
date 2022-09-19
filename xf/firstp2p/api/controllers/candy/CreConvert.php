<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use libs\web\Form;
use core\service\candy\CandyAccountService;
use core\service\candy\CandyCreService;
use core\service\AgreementService;
use libs\cre\Cre;
use libs\utils\Aes;
use core\service\candy\CandyUtilService;
use libs\db\Db;

class CreConvert extends AppBaseAction
{

    const IS_H5 = true;

    public function init()
    {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 用户授权检查
        $this->tpl->assign('token', $data['token']);
        if (!AgreementService::check($loginUser['id'], 'candy')) {
            $this->template = 'api/views/_v48/candy/shop_agreement.html';
            return false;
        }
        try {
            $requestResult = Cre::instance()->requestUserValidate($loginUser['id']);
            if ($requestResult['code'] == Cre::RESPONSE_CODE_NO_REGISTER) {
                // 处理token加密
                $aes = new Aes();
                $aesToken = $aes->encode($data['token'], base64_decode($GLOBALS['sys_config']['TOKEN_ENCRYPT_KEY']));
                $aesTokenHandle = $aes->urlEncode($aesToken);
                // 跳转中间页面
                $url = '/candy/creMid?authToken='.$aesTokenHandle;
                app_redirect($url);
                return true;
            }
            if ($requestResult['code'] !== 0) {
                $this->setErr("EERR_SYSTEM");
                return false;
            }
        } catch (\Exception $e) {
            $this->setErr("EERR_SYSTEM");
            return false;
        }

        $accountService = new CandyAccountService();
        $creService = new CandyCreService();

        // 用户信宝兑换CRE比例
        $convertRate = $creService->getCandyCreRate($loginUser['id']);

        $accountInfo = $accountService->getAccountInfo($loginUser['id']);
        // 用户信宝可兑换CRE数量
        $this->tpl->assign('creAmount', number_format($creService->calcCreAmount($convertRate, $accountInfo['amount']), $creService::CRE_AMOUNT_DECIMALS));
        if (empty($accountInfo['amount'])) {
            $accountInfo['amount'] = 0;
        } else {
            $accountInfo['amount'] = number_format($accountInfo['amount'], $accountService::AMOUNT_DECIMALS);
        }
        $creConvertLimit = $creService->getOrCreateConvertCreLimit();
        $creUserUsed = $creService->getUserConvertCreUsed($loginUser['id']);

        // CRE兑换开关
        $this->tpl->assign('creOpen', $creService->isOpen());
        $this->tpl->assign('convertRate', $convertRate);
        $this->tpl->assign('mobile', $loginUser['mobile']);
        // 用户信宝账户信息
        $this->tpl->assign('accountInfo', $accountInfo);
        // 用户今日投资总额
        $this->tpl->assign('totalInvestAmount', CandyUtilService::getUserInvestAmountToday($loginUser['id']), CandyUtilService::LIMIT_DEAL_AMOUNT_ANNUALIZED);
        // CRE剩余总库存以及可兑换额度
        $this->tpl->assign('creConvertTotalLimit', number_format($creConvertLimit['cre_amount_total'] - $creConvertLimit['cre_amount_used'], CandyCreService::CRE_AMOUNT_DECIMALS));
        $this->tpl->assign('creConvertUserLimit', number_format($creConvertLimit['cre_amount_user_total'] - $creUserUsed, CandyCreService::CRE_AMOUNT_DECIMALS));
    }

}