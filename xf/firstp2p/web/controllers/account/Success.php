<?php
/**
 * Success.php
 * @date 2014-01-07
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use web\controllers\BaseAction;
use core\service\O2OService;
use libs\utils\PaymentApi;

/**
 * 个人中心-利滚利赎回处理成功也能
 *
 * Class Success
 * @package web\controllers\account
 */
class Success extends BaseAction {

    public function init() {
        $this->check_login();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'gS' => array('filter' => 'int'),
            'action' => array('filter' => 'string'),
            'deal_type' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            app_redirect(url("index"));
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loan_id = $data['id'];
        $dealType = isset($data['deal_type']) ? $data['deal_type'] : CouponGroupEnum::CONSUME_TYPE_P2P;

        if ($loan_id <= 0) {
            app_redirect(url("index"));
        }

        $loan_info = $this->rpc->local('DealLoadService\getDealLoadDetail', array($loan_id));
        if (empty($loan_info) || $loan_info['deal_type'] == 0 || $loan_info['user_id'] != $GLOBALS['user_info']['id']) {
            app_redirect(url("index"));
        }

        $this->tpl->assign('site_name', $GLOBALS['sys_config']['SITE_LIST_TITLE'][$GLOBALS['sys_config']['APP_SITE']]);
        $this->tpl->assign('site_id', $GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']]);
        $this->tpl->assign('site_app', $GLOBALS['sys_config']['APP_SITE']);
        // 查询此次投资生成了多少红包
        $bonus_maked = $this->rpc->local('BonusService\get_bonus_group', array($loan_id));
        $this->tpl->assign('bonus_maked', $bonus_maked);

        // 查询此次投资是否生成了O2O礼券
        $this->tpl->assign('hasCouponList', $data['gS']);
        // 如果触发o2o券列表，从缓存中读取数据并显示
        $list = array();
        if ($this->form->data['gS']) {
            $rpcParams = array($GLOBALS['user_info']['id'], $data['action'], $loan_id, $dealType);
            $list = $this->rpc->local('O2OService\getCouponGroupList', $rpcParams);
        }

        $this->tpl->assign('action', $data['action']);
        $this->tpl->assign('deal_type', $dealType);
        $this->tpl->assign('load_id', $loan_id);
        $this->tpl->assign('couponList', $list);
    }
}
