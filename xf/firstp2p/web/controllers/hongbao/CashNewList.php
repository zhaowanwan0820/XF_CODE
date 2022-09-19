<?php
/**
 * CashNewList.php
 *
 * @date 2014年10月30日11:52:33
 * @author luzhengshuai <luzhengshuai@ucfgroup.com>
 */

namespace web\controllers\hongbao;

use libs\web\Form;
use web\controllers\hongbao\CashBase;
use core\service\BonusService;
use core\dao\BonusModel;
use core\service\CouponService;

class CashNewList extends CashBase {

    public function init() {
        //if (parent::init() === false) {
        //    return false;
        //}
        $this->form = new Form("get");
        $this->form->rules = array('cn' => array('filter' => 'required'));
        if (!$this->form->validate()) {
            $this->show_error($this->form->getErrorMsg(), '', 0, 1);
            return false;
        }
    }

    public function invoke() {

        $this->cn = $this->form->data['cn'];
        $referUid = CouponService::hexToUserId($this->cn);
        $bonusService = new BonusService();
        $bonusUserList = $bonusService->get_list_by_type(BonusModel::BONUS_CASH_FOR_NEW, '', $referUid, '', '*', 1, 20, 'DESC');
        $this->listView = $this->makeListToView($bonusUserList);

        $this->tpl->assign('bonusList', $this->listView);
        $this->template = "web/views/hongbao/cash_coupon/list_new.html";

    }
}
