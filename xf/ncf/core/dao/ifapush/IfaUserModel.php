<?php
namespace core\dao\ifapush;

use core\dao\ifapush\IfaBaseModel;
use core\dao\ifapush\IfaDealModel;


class IfaUserModel extends IfaBaseModel
{
    public function getPushData($seconds,$limit=10,$orderId=false,$dbTimeColumn = ''){

        $oriData =  parent::getPushData($seconds,$limit,$orderId,$dbTimeColumn);

        foreach($oriData as $key=>$item){
            $item->userList = array(
               array(
                   'userPay' => $item->userPay,
                   'userPayAccount' => $item->userPayAccount,
                   'userBank' => $item->userBank,
                   'userBankAccount' => $item->userBankAccount,
               )
            );
        }
        return $oriData;
    }

    /**
     * 获取用户数据，需要对数据进行处理，然后才能上报
     */
    public function  getBatchPushData(){
        $oriData =  parent::getBatchPushData();
        foreach($oriData as $key=>$item){
            $item->userList = array(
                array(
                    'userPay' => $item->userPay,
                    'userPayAccount' => $item->userPayAccount,
                    'userBank' => $item->userBank,
                    'userBankAccount' => $item->userBankAccount,
                )
            );
        }
        return $oriData;
    }


    /**
     * 判断数据是否需要上报给协会
     * @param $userId
     * @param $dealId
     * @return bool|int
     */
    public function isNeedReport($userId,$dealId)
    {
        $idl = new IfaDealModel();
        $cnt =  $this->count("userId='{$userId}'");
        return $cnt > 0 ? false : true;
    }


    public function hasUser($userId)
    {
        $cnt = $this->count("userId='{$userId}'");
        return $cnt > 0 ? true : false;
    }
}