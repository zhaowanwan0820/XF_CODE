<?php
/**
 * Redeem.php
 * @date 2014-01-07
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 */

namespace web\controllers\account;

use libs\web\Form;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use core\service\O2OService;
/**
 * 个人中心-利滚利赎回
 *
 * Class Redeem
 * @package web\controllers\account
 */
class Redeem extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form('post');
        $this->form->rules = array(
            'id' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->show_error('参数错误！','',1);
            exit;
        }
    }

    public function invoke() {
        $params = $this->form->data;
        $deal_load_id = $params['id'];
        if(!$deal_load_id){
            $this->show_error('参数错误！','',1);
            exit;
        }

        if (app_conf('SWITCH_COMPOUND_REDEEM') == 1) {
            $this->show_error('当前不可赎回！','',1);
            exit;
        }

        $deal_load = $this->rpc->local("DealLoadService\getDealLoadDetail", array($deal_load_id, false));
        if(empty($deal_load)){
            $this->show_error('参数错误！','',1);
            exit;
        }

        $user_info = $GLOBALS['user_info'];
        $rs = $this->rpc->local('DealCompoundService\redeem', array($deal_load_id,$user_info['id']));
        if(!$rs){
            $this->show_error('正在放款中，请稍后重试！','',1);
            exit;
        }

        // 通知贷赎回
        $showGiftInfo = 0;
        $event = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
        $userid = $user_info['id'];
        $loadid = $deal_load_id;
        $digObject = new \core\service\DigService('redeem', array(
            'id' => $user_info['id'],
            'cn' => $deal_load['short_alias'],
            'loadId' => $deal_load_id,
            'money' => $deal_load['money'],
            'siteId' => $deal_load['site_id'],
        ));
        $prizelist = $digObject->getResult();
        if (!empty($prizelist) && app_conf('O2O_WITH_REDEEM')) {
            $showGiftInfo = 1;
        }

        $this->show_success('赎回成功！','',1, '', array('gS' => $showGiftInfo, 'action' => $event , 'id' => $deal_load_id));
        exit;
    }
}
