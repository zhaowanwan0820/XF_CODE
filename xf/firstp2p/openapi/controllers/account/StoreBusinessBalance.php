<?php
namespace openapi\controllers\account;

/**
 * 店商互联openapi
 * 查询指定账户余额
 * @author duxuefeng
 * @date 2017-11-22
 */
use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\Aes;


class StoreBusinessBalance extends BaseAction
{
    private $type = array(1 => '店商互联');

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "type" => array(
                "filter" => "required"
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
        //验证类型
        $data = $this->form->data;
        if(!array_key_exists(intval($data['type']),$this->type)){
            $this->setErr("ERR_PARAMS_ERROR", "type类型错误");
            return false;
        }
        try{
            //获取用户信息
            $interestAccountId = app_conf("DSD_INTEREST_ACCOUNT");
            $generationRechargeAccountId = app_conf("DSD_GR_ACCOUNT");
            $interestAccount = $this->rpc->local('UserService\getUser', array($interestAccountId));
            if(!$interestAccount){
                throw new \Exception("利息用户不存在，userId:" . $interestAccountId);
            }
            $generationRechargeAccount = $this->rpc->local('UserService\getUser', array($generationRechargeAccountId));
            if(!$generationRechargeAccountId){
                throw new \Exception("代充值用户不存在，userId:" . $generationRechargeAccountId);
            }

            //用户余额  存管账户余额
            $interestResult = $this->rpc->local('UserService\getMoneyInfo', array($interestAccount, 0));
            if(!$interestResult){
                throw new \Exception("无法查询利息用户余额，userId:" . $interestAccountId);
            }
            $generationResult = $this->rpc->local('UserService\getMoneyInfo', array($generationRechargeAccount, 0));
            if(!$generationResult){
                throw new \Exception("无法查询代充值用户余额，userId:" . $generationRechargeAccountId);
            }
            $moneyData = array(
                "interest_account" => array(
                    "amount" => $interestResult['bank'],
                    "user_name" =>$interestAccount['user_name'],
                ),
                "generation_recharge_account" => array(
                    "amount" => $generationResult['bank'],
                    "user_name" =>$generationRechargeAccount['user_name'],
               ),
            );
        }catch(\Exception $e){
            $this->setErr("ERR_ENQUIRY_ACCOUNT_FAIL",$e->getMessage());
            return false;
        }
        $this->json_data = $moneyData;
        return true;
    }

}
