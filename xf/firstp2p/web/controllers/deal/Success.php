<?php
/**
 * 投资成功页
 **/

namespace web\controllers\deal;

use core\service\DiscountService;
use libs\web\Form;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use web\controllers\BaseAction;
use libs\utils\Aes;
use libs\web\Url;

class Success extends BaseAction {

    public function init() {
        $this->check_login();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'isDt' => array('filter' => 'int', 'option' => array('optional'=>true)),
            'gS' => array('filter' => 'int'),
            'dP' => array('filter' => 'string'),
            'dT' => array('filter' => 'int'),
            'bm' => array('filter' => 'string'),
            'action' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            app_redirect(url("index"));
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loan_id = $this->form->data['id'];
        $gS = $this->form->data['gS'];
        if($loan_id <= 0){
            app_redirect(url("index"));
        }

        // 多投逻辑
        $isDt = $this->form->data['isDt'];
        $this->tpl->assign('isDt', $isDt);
        if ($isDt == 1) {
            $token_info = $this->rpc->local('IdempotentService\getTokenInfo', array($loan_id));
            $loan_info = $token_info['data'];
            if (empty($loan_info) || $loan_info['userId'] != $GLOBALS['user_info']['id']) {
                app_redirect(url("index"));
            }
            $rpc = new \libs\utils\Rpc('duotouRpc');
            $projectRequest = new \NCFGroup\Protos\Duotou\RequestCommon();
            $projectRequest->setVars(array('project_id' =>  $loan_info['dealId']));
            $response = $rpc->go('NCFGroup\Duotou\Services\Project','getProjectInfoById',$projectRequest);
            if(!$response) {
                return $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888', "", 0, 0, url("index"));
            }
            $project = $response['data'];
            $project['old_name'] = $project['name'];
            $project['rate_show'] = number_format($project['rateYear'], 2);
            $project['url'] = "/finplan/" . $project['id'];

            $this->tpl->assign('deal', $project);
            $this->tpl->assign('loan_info', $loan_info);
            return true;
        } else {
            $loan_info = $this->rpc->local('DealLoadService\getDealLoadDetail', array($loan_id));
        }

        if (empty($loan_info) || $loan_info['user_id'] != $GLOBALS['user_info']['id']) {
            //app_redirect(url("index"));
        }

        $site_id = $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']];

        /**********存管逻辑***********************/
        $isShowBankAlert = $this->rpc->local('SupervisionDealService\setQuickBidAuthCount',array($loan_info['user_id']));



        $this->tpl->assign('isShowBankAlert',$isShowBankAlert);

        $loan_info['deal']['url'] = Url::gene("d", "", Aes::encryptForDeal($loan_info['deal']['id']), true);
        $this->tpl->assign('deal', $loan_info['deal']);
        $this->tpl->assign('loan_info', $loan_info);
        $this->tpl->assign('gS', $gS);
        $discountGoodsPrice = $this->form->data['dP'];
        $this->tpl->assign('discountGoodsPrice', $discountGoodsPrice);
        //查询此次投资使用了多少红包
        // $used_bonus = $this->rpc->local('BonusService\get_used', array($loan_id));
        $used_bonus = $this->form->data['bm'];
        $this->tpl->assign('bonus_use_money', number_format($used_bonus, 2));
        //$this->tpl->assign('user_use_money', number_format($loan_info['money'] - $used_bonus['money'], 2));
        $this->tpl->assign('user_use_money', bcsub($loan_info['money'], $used_bonus, 2));
        $this->tpl->assign('site_name', $GLOBALS['sys_config']['SITE_LIST_TITLE'][$GLOBALS['sys_config']['APP_SITE']]);
        $this->tpl->assign('site_id', $site_id);
        $this->tpl->assign('site_app', $GLOBALS['sys_config']['APP_SITE']);
        //查询此次投资生成了多少红包
        $bonus_maked = $this->rpc->local('BonusService\get_bonus_group', array($loan_id));
        $this->tpl->assign('bonus_maked', $bonus_maked);
        $this->tpl->assign('fubaba_cps_sig', md5($loan_info['id'].'wangxinlicai@123'));
        $this->tpl->assign('euid', \es_cookie::is_set('euid') ? \es_cookie::get('euid') : '');

        //计算富爸爸佣金
        if($loan_info['deal']['loantype']==5){
            $cps_fbb_fee = $loan_info['money'] * $loan_info['deal']['repay_time'] * 0.0274 / 1000 ;
        }else{
            $cps_fbb_fee = $loan_info['money'] * $loan_info['deal']['repay_time'] * 30 * 0.0274 / 1000 ;
        }

        $this->tpl->assign('cps_fbb_fee', round($cps_fbb_fee,2));

        $dealType = CouponGroupEnum::CONSUME_TYPE_P2P;
        // 如果触发o2o券列表，从缓存中读取数据并显示
        $list = array();
        if ($gS) {
            $rpcParams = array($GLOBALS['user_info']['id'], $data['action'], $data['id'], $dealType);
            $list = $this->rpc->local('O2OService\getCouponGroupList', $rpcParams);
        }

        $this->tpl->assign('action', $data['action']);
        $this->tpl->assign('load_id', $loan_id);
        $this->tpl->assign('deal_type', $dealType);
        $this->tpl->assign('couponList', $list);
        $this->tpl->assign('wxlc_home_url', sprintf('//%s', $this->getWxlcDomain()));

        //来源站点数据
        $fromSite = \es_session::get('from_site');
        $this->tpl->assign('from_site', \es_session::get('from_site'));

        //来源是农贷分站
        $fromSiteId = !empty($fromSite['id']) ? $fromSite['id'] : null;
        $this->tpl->assign('is_from_nongdan', is_nongdan_site($fromSiteId));

    }

}
