<?php
/**
 * 兑换记录列表
 */

namespace web\controllers\seller;

use libs\web\Form;
use api\conf\Error;
use web\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Appliedlist extends BaseAction {
    public function init() {
        parent::init();
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'p' => array('filter' => 'int', 'option' => array('optional' => true)),
            'site_id' => array('filter' => 'int', 'option' => array('optional' => true)),
            'date_start' => array('filter' => 'string', 'option' => array('optional' => true)),
            'date_end' => array('filter' => 'string', 'option' => array('optional' => true)),
            'couponCode' => array('filter' => 'string', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $error = Error::get($this->form->getErrorMsg());
            $this->show_error($error['errmsg']);
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS ['user_info'];
        $page = intval($data['p']);
        $page = $page ? $page : 1;
        $page_size = 10;
        $beginTime = isset($data['date_start']) ? strtotime($data['date_start']) : 0;
        $endTime = isset($data['date_end']) ? strtotime($data['date_end']." 23:59:59") : 0;
        $couponCode = isset($data['couponCode']) ? trim($data['couponCode']) : '';
        $count= $this->rpc->local('O2OService\getConfirmedCouponCount', array($loginUser['id'], $beginTime, $endTime, $couponCode));
        $applyList= $this->rpc->local('O2OService\getConfirmedCouponListForWeb', array($loginUser['id'], $page, $page_size, $beginTime, $endTime, $couponCode));
        if ($count > $page_size) {
            $page_model = new \Page($count, $page_size); //初始化分页对象
            $pages = $page_model->show();
            $this->tpl->assign('pages', $pages);
        }
        if($beginTime) {
            $this->tpl->assign('date_start', $data['date_start']);
        }
        if($endTime) {
            $this->tpl->assign('date_end', $data['date_end']);
        }
        if($couponCode) {
            $this->tpl->assign('couponCode', $couponCode);
        }
        $this->tpl->assign('applyList', $applyList);
        $this->tpl->assign('applyListCount', count($applyList));
        #$this->template = 'web/views/v2/seller/list.html';
        $this->tpl->assign("inc_file", "web/views/v2/seller/list.html");
        $this->template = "web/views/v2/account/frame.html";
    }
}
