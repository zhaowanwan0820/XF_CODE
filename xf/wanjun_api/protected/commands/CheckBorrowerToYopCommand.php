<?php
/**
 * 与易宝校验借款人信息
 */
class CheckBorrowerToYopCommand extends CConsoleCommand
{
    //BIND_SUCCESS ： 绑卡成功 TO_VALIDATE： 待短验 BIND_FAIL： 绑卡失败 BIND_ERROR： 绑卡异常(可重试) TIME_OUT： 超时失败 FAIL： 系统异常
    public static $bind_status = [
        'BIND_SUCCESS'=>1,
        'TO_VALIDATE'=>2,
        'BIND_FAIL'=>3,
        'BIND_ERROR'=>4,
        'TIME_OUT'=>5,
        'FAIL'=>6,
    ];

    //输出日志
    public static function echoLog($log, $warning = false)
    {
        echo date('Y-m-d H:i:s ')." CheckBorrowerToYop : {$log} \n";
        if ($warning) {
            Yii::log("CheckBorrowerToYop {$log}", 'error');
        }
    }

    private $limit = 60;
    private $table = 'xf_borrower_bind_card_info';
    private $db = 'phdb';
 
    /**
     * 用户账户数据导入
     */
    public function actionRun()
    {
        self::echoLog('脚本开始执行……');
        $order ="asc";
        $i = 0;
        while (true) {
            $select_sql = "select * from {$this->table} where id_type = 1 and  status = 1 order by id {$order}  limit {$this->limit}";
  
            $borrowers = Yii::app()->{$this->db}->createCommand($select_sql)->queryAll() ?: [];
            if (empty($borrowers)) {
                break;
            }
            foreach ($borrowers as $borrower) {
                $borrower['request_no'] = FunctionUtil::getRequestNo("BBB");
                $borrower['idno']       = GibberishAESUtil::dec($borrower['idno'], Yii::app()->c->contract['idno_key']);
                $borrower['bankcard']   = GibberishAESUtil::dec($borrower['bankcard'], Yii::app()->c->contract['idno_key']);
                $borrower['mobile']     = GibberishAESUtil::dec($borrower['mobile'], Yii::app()->c->contract['idno_key']);
                
                $re = $this->queryYop($borrower);
                if ($re) {
                    $status     = self::$bind_status[$re['status']]?:9;
                    $errormsg   = isset($re['errormsg'])?$re['errormsg']:'';
                    $cardtop    = isset($re['cardtop'])?$re['cardtop']:'';
                    $cardlast   = isset($re['cardlast'])?$re['cardlast']:'';
                    $bankcode   = isset($re['bankcode'])?$re['bankcode']:'';
                    $verifyStatus = isset($re['verifyStatus'])?$re['verifyStatus']:'';
                    $yborderid  = isset($re['yborderid'])?$re['yborderid']:'';
                    $yop_return = json_encode($re);
                    $now = time();
                    $up_sql = " update {$this->table} set request_no = '{$borrower['request_no']}',update_time={$now}, yborderid='{$yborderid}', verifyStatus='{$verifyStatus}' ,errormsg='{$errormsg}', cardtop = '{$cardtop}',cardlast = '{$cardlast}',bankcode = '{$bankcode}' ,status = {$status},remark = '{$yop_return}' where user_id = {$borrower['user_id']} and status = 1";
                    $up_res = Yii::app()->{$this->db}->createCommand($up_sql)->execute();
                    if ($up_res===false) {
                        self::echoLog('更新数据失败:user_id:'.$borrower['id'], true);
                    }
                }
            }
            $i+=count($borrowers);
            self::echoLog('已处理:'.$i.'条');
        }
        self::echoLog('脚本执行完成');
    }

    public function queryYop($data=[])
    {
        $request = new YopRequest(YopConfig::APP_KEY, YopConfig::PRIVATE_KEY);
        $request->addParam("merchantno", YopConfig::MERCHANT_NO);

        //加入请求参数
        $request->addParam("identityid", $data['user_id']);//商户生成的用户唯一标识
        $request->addParam("cardno", $data['bankcard']);//银行卡号
        $request->addParam("idcardno", $data['idno']);//身份证号
        $request->addParam("username", $data['real_name']);//姓名
        $request->addParam("phone", $data['mobile']);//手机号
        $request->addParam("requestno", $data['request_no']);//商户生成的唯一绑卡请求号


        $request->addParam("requesttime", date('Y-m-d H:i:s'));//请求时间
        $request->addParam("identitytype", "USER_ID");
        $request->addParam("idcardtype", "ID");//身份证号
       
        //提交Post请求
        $response = YopClient3::post("/rest/v1.0/paperorder/nonperceived/auth/request", $request);

        if ($response->validSign==1) {
            $re = $this->object_array($response);
            self::echoLog("CheckBorrowerToYop  yop return data:".print_r($re, true));
            if (strtoupper($re['state']) == 'SUCCESS') {
                return $re['result'];
            }
        }
        return false;
    }

    public function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key=>$value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }



    /**
     * 用户账户数据导入
     * 处理状态是3的
     */
    public function actionRun2()
    {
        self::echoLog('脚本2开始执行……');
        $order = "asc";
        $i = 0;
        while (true) {
            $select_sql = "select * from {$this->table} where id_type = 1 and  status = 3 and  errormsg != '超过鉴权次数限制' order by id {$order}  limit {$this->limit}";
           
            $borrowers = Yii::app()->{$this->db}->createCommand($select_sql)->queryAll() ?: [];
            if (empty($borrowers)) {
                break;
            }
            foreach ($borrowers as $borrower) {
                $borrower['request_no'] = FunctionUtil::getRequestNo("BBB");
                $borrower['idno']       = GibberishAESUtil::dec($borrower['idno'], Yii::app()->c->contract['idno_key']);
                $borrower['bankcard']   = GibberishAESUtil::dec($borrower['bankcard'], Yii::app()->c->contract['idno_key']);
                $borrower['mobile']     = GibberishAESUtil::dec($borrower['mobile'], Yii::app()->c->contract['idno_key']);
                
                $re = $this->queryYop2($borrower);
                if ($re) {
                    $status     = isset(self::$bind_status[$re['status']]) && self::$bind_status[$re['status']] !=3 ? self::$bind_status[$re['status']]:9;
                    $errormsg   = isset($re['errormsg'])?$re['errormsg']:'';
                    $cardtop    = isset($re['cardtop'])?$re['cardtop']:'';
                    $cardlast   = isset($re['cardlast'])?$re['cardlast']:'';
                    $bankcode   = isset($re['bankcode'])?$re['bankcode']:'';
                    $verifyStatus = isset($re['verifyStatus'])?$re['verifyStatus']:'';
                    $yborderid  = isset($re['yborderid'])?$re['yborderid']:'';
                    $yop_return = json_encode($re);
                    $now = time();
                    $up_sql = " update {$this->table} set request_no = '{$borrower['request_no']}',update_time={$now}, yborderid='{$yborderid}', verifyStatus='{$verifyStatus}' ,errormsg='{$errormsg}', cardtop = '{$cardtop}',cardlast = '{$cardlast}',bankcode = '{$bankcode}' ,status = {$status},remark = '{$yop_return}' where user_id = {$borrower['user_id']} and status = 3";
                    $up_res = Yii::app()->{$this->db}->createCommand($up_sql)->execute();
                    if ($up_res===false) {
                        self::echoLog('更新数据失败:user_id:'.$borrower['id'], true);
                    }
                }
            }
            $i+=count($borrowers);
            self::echoLog('已处理:'.$i.'条');
        }
        self::echoLog('脚本执行完成');
    }

    public function queryYop2($data=[])
    {
        $request = new YopRequest(YopConfig::APP_KEY, YopConfig::PRIVATE_KEY);
        $request->addParam("merchantno", YopConfig::MERCHANT_NO);

        //加入请求参数
        $request->addParam("identityid", $data['user_id']);//商户生成的用户唯一标识
        $request->addParam("cardno", $data['bankcard']);//银行卡号
        $request->addParam("idcardno", $data['idno']);//身份证号
        $request->addParam("username", $data['real_name']);//姓名
        $request->addParam("phone", $data['mobile']);//手机号
        $request->addParam("requestno", $data['request_no']);//商户生成的唯一绑卡请求号
        $request->addParam("issms", 'false');//短验
        $request->addParam("authtype", 'COMMON_FOUR');//短验


        $request->addParam("requesttime", date('Y-m-d H:i:s'));//请求时间
        $request->addParam("identitytype", "USER_ID");
        $request->addParam("idcardtype", "ID");//身份证号
       
        //提交Post请求
        $response = YopClient3::post("/rest/v1.0/paperorder/unified/auth/request", $request);

        if ($response->validSign==1) {
            $re = $this->object_array($response);
            self::echoLog("CheckBorrowerToYop  yop return data:".print_r($re, true));
            if (strtoupper($re['state']) == 'SUCCESS') {
                return $re['result'];
            }
        }
        return false;
    }
}
