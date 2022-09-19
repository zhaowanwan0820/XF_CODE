<?php

class AboutUserAuth
{
    public $user_id = 0;

    const AUTH_STATUS_PASS   = 2;
    const AUTH_STATUS_REJECT = 3;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }

    public function getUserAuthInfo()
    {
        $authInfo = AuthDebtExchangeLog::model()->findByAttributes(['user_id' => $this->user_id, 'auth_status' => self::AUTH_STATUS_PASS]);
        return $authInfo;
    }

    /**
     * @return array 上传照片
     */
    public function savePhotoAuthInfo($data)
    {
        $returnResult        = [
            'data' => [],
            'code' => 0,
            'info' => 'success',
        ];
        $card1               = $data['filepath1'] ?: '';
        $card2               = $data['filepath2'] ?: '';
        $card3               = $data['filepath3'] ?: '';
        $verifyscore         = $data['verifyscore'] ?: '';
        $authstatus          = $data['authstatus'] ?: '';
        $cardid              = $data['cardid'] ?: '';
        $name                = $data['name'] ?: '';
        $order_id            = $data['orderid'] ?: 0;
        $error_msg           = $data['errormsg'] ?: '';
        $result_sta          = $data['resultsta'] ?: 0;
        $now                 = time();
        $authDebtExchangeLog = AuthDebtExchangeLog::model()->findByAttributes(['user_id' => $this->user_id, 'auth_status' => self::AUTH_STATUS_PASS]);

        if ($authDebtExchangeLog) {
            Yii::log("  save card error user_id:" . $this->user_id, 'error', __FUNCTION__);
            $returnResult['code'] = 0;
            $returnResult['info'] = '该用户已审核通过';
            return $returnResult;
        }

        $newAuthDebtExchangeLog              = new AuthDebtExchangeLog();
        $newAuthDebtExchangeLog->user_id     = $this->user_id;
        $newAuthDebtExchangeLog->card1       = $card1;
        $newAuthDebtExchangeLog->card2       = $card2;
        $newAuthDebtExchangeLog->card3       = $card3;
        $newAuthDebtExchangeLog->addtime     = $now;
        $newAuthDebtExchangeLog->auth_status = $authstatus;

        $newAuthDebtExchangeLog->order_id      = $order_id;
        $newAuthDebtExchangeLog->error_msg     = $error_msg;
        $newAuthDebtExchangeLog->result_status = $result_sta;
        if ($authstatus == self::AUTH_STATUS_PASS) {
            $newAuthDebtExchangeLog->auth_time = $now;
        }
        $newAuthDebtExchangeLog->verify_score = $verifyscore ? round($verifyscore, 2) : 0.00;
        $newAuthDebtExchangeLog->card_id      = $cardid;
        $newAuthDebtExchangeLog->real_name    = $name;
        if ($authstatus == self::AUTH_STATUS_REJECT && $verifyscore >= 80) {
            $newAuthDebtExchangeLog->auth_info = '认证身份证号与用户原平台身份证号不符';
        }
        if (!$newAuthDebtExchangeLog->save()) {
            Yii::log("  save card error user_id:" . $this->user_id, 'error', __FUNCTION__);
            $returnResult['code'] = 100;
            $returnResult['info'] = '网络忙，稍后重试';
        }
        return $returnResult;

    }

    public function checkUserLogin()
    {
        $returnResult = [
            'data' => [],
            'code' => 0,
            'info' => 'success',
        ];
        $sql = "select user_id from ag_wx_assignee_info where user_id =:user_id and status in (2,3)  ";
        $returnResult['code'] = Yii::app()->db->createCommand($sql)->bindValues([':user_id'=>$this->user_id])->queryRow() ? 2022 : 0;
        return $returnResult;

    }

    /**
     * 同意新协议
     * @param $debtInfo
     * @param $type
     * @return array
     */
    public function confirmUserDebt($debtInfo,$type)
    {
        $returnResult = [
            'data' => [],
            'code' => 0,
            'info' => 'success',
        ];
        $sql = "select * from ag_wx_shop_contract where user_id =:user_id and `type`=:type ";
        if(!$res = Yii::app()->db->createCommand($sql)->bindValues([':user_id'=>$this->user_id,':type'=>$type])->queryRow()){
            $now = time();
            $insertSql = " insert into ag_wx_shop_contract (user_id,contract_data,`type`,addtime) values ({$this->user_id},'$debtInfo',$type,$now) ";
            if(! Yii::app()->db->createCommand($insertSql)->execute()){
                $returnResult['code'] = 3001;
            }
        }else{
            $returnResult['data'] = $res;
        }
        return $returnResult;
    }

    /**
     * 用户新协议
     * @return array
     */
    public function getUserAgreementInfo()
    {
        $returnResult = [
            'data' => [],
            'code' => 0,
            'info' => 'success',
        ];
        $sql = "select `type`,contract_url from ag_wx_shop_contract where user_id =:user_id and status = 1  ";
        $res = Yii::app()->db->createCommand($sql)->bindValues([':user_id'=>$this->user_id])->queryAll()?:[];
        $returnResult['data'] = $res;
        return $returnResult;
    }


}

