<?php

namespace api\controllers\candy;

use api\controllers\AppBaseAction;
use core\service\candy\CandyBucService;
use libs\web\Form;
use core\service\candy\CandyShopService;
use core\service\candy\CandyAccountService;
use core\service\AgreementService;
use core\service\candy\CandyCreService;

class Shop extends AppBaseAction
{
    const IS_H5 = true;
    public function init() {
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

    public function invoke() {
        $data = $this->form->data;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        //用户授权检查
        $this->tpl->assign('token', $data['token']);
        if (!AgreementService::check($loginUser['id'], 'candy')) {
            $this->template = 'api/views/_v48/candy/shop_agreement.html';
            return false;
        }

        $accountService = new CandyAccountService();
        $accountInfo = $accountService->getAccountInfo($loginUser['id']);
        $shopService = new CandyShopService();
        // 处理显示
        $showList = [];
        $showList['coupon'] = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CandyShopService\getCouponList', array(), 'candy'), 600);
        $showList['goods'] = $shopService->getSuggestLifeGoods();

        //信宝黑名单
        $showConfig['BLACK_BUC'] = (new \core\service\BwlistService)->inList('DEAL_CU_BLACK');
        $this->tpl->assign('showConfig', $showConfig);
        $this->tpl->assign('userSummary', $accountInfo);
        $this->tpl->assign('productList', $showList);
        $this->tpl->assign('shopUrl', $GLOBALS['sys_config']['LIFE_SHOP']['SHOP_HOST']);
        $this->template = $this->getTemplate('');
    }
}
