<?php
/**
 * 免密资金划转
 *    bank_to_wx 存管账户->超级账户
 *    wx_to_bank 超级账户->存管账户
 */

namespace web\controllers\supervision;

use NCFGroup\Common\Library\Idworker;
use web\controllers\BaseAction;
use libs\web\Form;

class MoneyTransfer extends BaseAction
{
    const WX_TO_BANK = 'wx_to_bank';
    const BANK_TO_WX = 'bank_to_wx';

    public function init(){
        if(!$this->check_login()) return false;

        $this->form = new Form();

        $this->form->rules = array(
            'money' => array('filter' => 'float'),
            'direction' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->show_error("params error","money is null",1);
        }
    }

    public function invoke()
    {
        $userId = $GLOBALS['user_info']['id'];
        $money = $this->form->data['money'];
        $direction = $this->form->data['direction'];
        if($money <= 0 || !in_array($direction,array(self::WX_TO_BANK,self::BANK_TO_WX))){
            return $this->show_error('参数错误','',1);
        }
        try{
            $orderId = Idworker::instance()->getId();
            $s = new \core\service\P2pDealBidService();
        }catch (\Exception $ex){
            \libs\utils\Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . 'Idworker error:'.$ex->getMessage());
            return $this->show_error('系统繁忙请稍后再试','',1);
        }

        try{
            $userService = new \core\service\UserService();
            $user = \core\dao\UserModel::instance()->find($userId);
            $moneyInfo = $userService->getMoneyInfo($user,$money);
        }catch (\Exception $ex){
            \libs\utils\Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . ' errMsg:'.$ex->getMessage());
            return $this->show_error('系统繁忙请稍后再试','',1);
        }


        if($direction == self::WX_TO_BANK){
            if(bccomp($moneyInfo['lc'],$money,2) == -1){
                return $this->show_error('超级账户余额不足，划转失败','',1);
            }
            $tranRes = $s->rechargeToBank($orderId,$userId,$money);
        }elseif($direction == self::BANK_TO_WX){
            if(bccomp($moneyInfo['bank'],$money,2) == -1){
                return $this->show_error('存管账户余额不足，划转失败','',1);
            }
            $tranRes = $s->withdrawToSuper($orderId,$userId,$money);
        }
        if($tranRes === true){
            return $this->show_success('划转成功','',1);
        }else{
            return $this->show_error('划转失败','',1);
        }
    }
}