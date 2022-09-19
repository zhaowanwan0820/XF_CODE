<?php
/**
 * 多投宝申请赎回功能
 *
 * @author wangyiming@ucfgroup.com
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\duotou\DtRedeemService;

class FinplanRedeem extends BaseAction {

    public function init() {
        if(app_conf('DUOTOU_SWITCH') == '0') {
            $this->show_tips("系统维护中，请稍后再试！","系统维护");
            exit;
        }
        if(!is_duotou_inner_user()) {
            $this->show_tips("没有权限,仅内部员工可以查看智多新内容！","没有权限");
            exit;
        }
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $id = $this->form->data['id'];
        $user_id = intval($GLOBALS['user_info']['id']);
        /*去除节假日
        $dcs = new DealCompoundService();
        if($dcs->checkIsHoliday(date('Y-m-d'))) {
            $this->show_error('节假日不可赎回','',1);
            exit;
        }*/

        $dtRedeemService = new DtRedeemService();
        $res  = $dtRedeemService->redeem($id,$user_id);
        if($res['errCode']){
            //赎回超限，特殊处理
            if($res['data'] != false){
                $this->show_error(array('isRedeemMoneyToday' => 1, 'maxDayRedemption' => $res['data']['maxDayRedemption'], 'errMsg' => $res['errMsg']),'',1);
            }else{
                $this->show_error(array('errMsg' => $res['errMsg'], 'isRedeemMoneyToday' => 0),'',1);
            }
            exit;
        } else {
            //简单化处理，申请赎回时间为 当前时间，返利是+1天
            $this->show_success('转让成功！','',1, '', array('gS' => 1,'redeemSuccessTime' => date("Y-m-d H:i:s", time()),'date' =>str_replace(",", "、" ,$res['expiryInterest']), 'moneyBackTime' => date("Y-m-d",strtotime("+1 day"))));
            exit;
        }
    }

}
