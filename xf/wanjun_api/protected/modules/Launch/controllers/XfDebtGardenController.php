<?php

class XfDebtGardenController extends XianFengExtendsController
{
    protected $logFile = 'ContractTaskHandleCommand';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    public function __get($name)
    {
        //错误信息
        if($name == 'error_code_info'){
            return Yii::app()->c->errorcodeinfo;
        }
        //风险等级验证
        if($name == "checkUserRisk"){
            if(!empty($this->user_id)){
                $riskInfo = DebtGardenYoujieQuestionService::getInstance()->checkUserRisk($this->user_id);
                return $riskInfo['code'];
            }
        }
    }
    /**
     * 债转服务协议签署接口
     *
     */
    public function actionTransAgree()
    {
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        $model = Yii::app()->db;
        $userInfo = $model->createCommand("select * from  firstp2p_user WHERE id = {$user_id} ")->queryRow();
        if(empty($userInfo)){
            $this->echoJson(array(), 1027, $this->error_code_info[1027]);
        }
        //账户状态无效
        if(empty($userInfo['is_effect'])){
            $this->echoJson(array(), 1034, $this->error_code_info[1034]);
        }
        //帐户已删除放入回收站，1为删除，0为未删除
        if($userInfo['is_delete'] == 1){
            $this->echoJson(array(), 1035, $this->error_code_info[1035]);
        }
        if($userInfo['agree_status'] != 1){
            //更新用户关系表
            $res = $model->createCommand("UPDATE firstp2p_user SET agree_status = 1 WHERE id = {$user_id} ")->execute();
            if (!$res) $this->echoJson(array(), 4010, $this->error_code_info['4010']);
            $this->echoJson(array("user_id" => $user_id, "agree_status" => 1), 0, '更新协议状态成功');
        }
        $this->echoJson(array("user_id" => $user_id, "agree_status" => 1), 0, '获取协议状态成功');
    }
    /**
     * 风险测评债转协议状态查询接口
     *
     */
    public function actionTransLook()
    {
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        $model = Yii::app()->db;
        $levelInfo = Yii::app()->c->itouzi['risk_level'];
        $userInfo = $model->createCommand("select id,agree_status,level_risk_id from  firstp2p_user WHERE id = {$user_id} ")->queryRow();
        if(empty($userInfo)){
            $this->echoJson(array(), 1027, $this->error_code_info[1027]);
        }
        $retData = array('user_id' => $user_id,
                         'agree_status' => $userInfo['agree_status'],//债转协议状态 0:未同意1：已同意
                         'level_risk_id' => $userInfo['level_risk_id'],//风险等级id
                         'risk_level' => !empty($levelInfo[$userInfo['level_risk_id']]) ? $levelInfo[$userInfo['level_risk_id']] : 0,//风险等级
                        );
        $this->echoJson($retData, 0, '返回成功');
    }

    /**
     * 获取风险评测题目接口
     *
     */
    public function actionGetQuestionnaire()
    {
        $type = $this->paramsvaild('type', true);//1风险评级问卷，2再投资问卷，3债消市场问卷
        $user_id = $this->user_id;
        $info = ['type' => $type, 'user_id' => $user_id];
        $this->checkUserRisk;
        $result = DebtGardenYoujieQuestionService::getInstance()->GetQuestionnaire($info);
        if($result['code'] != 0){
            $this->echoJson([], $result['code'],$this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '返回成功');
    }
    /**
     * 风险测评提交接口
     */
    public function actionSendQuestionnaire()
    {
        $qstn_id = $this->paramsvaild('qstn_id', true);//问卷id
        $answerArr = $this->paramsvaild('answerArr', true);
        $answer_time = $this->paramsvaild('answer_time', true);//答题时间s（按秒计算）
        $user_id = $this->user_id;
        $info = array('qstn_id' => $qstn_id, 'answerArr' => $answerArr, 'answer_time' => $answer_time, 'user_id' => $user_id);
        Yii::app()->db->beginTransaction();
        try {
            $result = DebtGardenYoujieQuestionService::getInstance()->SendQuestionnaire($info);
            if($result['code'] != 0){
                Yii::app()->db->rollback();
                $this->echoJson([], $result['code'],$this->error_code_info[$result['code']]);
            }
            Yii::app()->db->commit();
        }catch (Exception $e) {
            Yii::app()->db->rollback();
            Yii::log("SendQuestionnaire error :". print_r($e->getMessage(), true), "error");
            $this->echoJson([], 5000,"网络异常，请稍后重试");
        }
        $this->echoJson($result['data'], 0, '返回成功');
    }

    /**
     * 债转市场转让中的债权
     */
    public function actionDebtList()
    {
        $limit = $this->paramsvaild('limit', false, 10);
        $type  = $this->paramsvaild('type', true, 1); //1表示债权市场 2认购转让中的债权
        if(!in_array($type,[1,2]) || !is_numeric($limit) || !is_numeric($type)){
                $this->echoJson(array(), 2056, "参数有误");
        }
        if($limit > 100){
            $this->echoJson(array(), 4003, "limit限制不能超过100");
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        if($type == 1){
            //债权市场(获取尊享和普惠折扣靠前的前5个债权)
            $dataArr = ['type' => $type,'limit' => $limit];
            $result = DebtGardenYoujieQuestionService::getInstance()->GetZqscDebtList($dataArr);
        }
        if($type == 2){
            $products  = $this->paramsvaild('products', true, 1); //1尊享 2普惠供应链
            $page  = $this->paramsvaild('page', false, 1);
            $order  = $this->paramsvaild('order', false, 2);//1升序2降序
            $field  = $this->paramsvaild('field', false, 1);//1综合排序2转让折扣
            $name  = $this->paramsvaild('name', false);//项目名称
            //2认购转让中的债权
            $dataArr = ['type' => $type, 'limit' => $limit, 'products' => $products, 'page' => $page, 'order' => $order, 'field' => $field, 'name' => $name];
            $result = DebtGardenYoujieQuestionService::getInstance()->GetRgDebtList($dataArr);
        }
        if($result['code'] != 0){
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '查询成功');
    }
    /**
     * 转让中债权项目详情接口
     */
    public function actionTransferDetails()
    {
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        $debt_id = $this->paramsvaild('debt_id', true);//债权ID
        $dataArr = [
            'products' => $products,
            'debt_id' => $debt_id,
        ];
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $result = DebtGardenYoujieQuestionService::getInstance()->TransferDetails($dataArr);
        if($result['code'] != 0){
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '查询成功');
    }
    /**
     * 可转让债权列表
     */
    public function actionTransferableDebtList()
    {
        $products  = $this->paramsvaild('products', true, 1); //1尊享 2普惠供应链
        $page  = $this->paramsvaild('page', false, 1);
        $limit = $this->paramsvaild('limit', false, 10);
        $purchase_id = $this->paramsvaild('purchase_id', false, 0);
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        //专区校验
        /*
        $area_ids = array_keys(Yii::app()->c->xf_config['area_list']);
        if (!empty($_POST['area_id']) && !in_array($_POST['area_id'], $area_ids)) {
            $this->echoJson(array(), 7001, $this->error_code_info[7001]);
        }*/
        //债转专区仅对普惠开放
        if($purchase_id != 0  && $products != 2){
            $this->echoJson(array(), 7003, $this->error_code_info[7003]);
        }

        $dataArr = [
            'limit' => $limit,
            'products' => $products,
            'page' => $page,
            'user_id' => $user_id,
            'purchase_id' => $purchase_id,
            ];
        $result = DebtGardenYoujieQuestionService::getInstance()->transferableDebtList($dataArr);
        if($result['code'] != 0){
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '查询成功');
    }

    /**
     * 项目详情(发布转让)接口
     */
    public function actionDebtDetails()
    {
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        $status = $this->paramsvaild('status', false,1);//1:发布2：重新发布
        if($status == 1){
            $deal_load_id = $this->paramsvaild('deal_load_id', true);//投资记录id
        }
        if($status == 2){
            $debt_id = $this->paramsvaild('debt_id', true);//债权记录id
        }
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $dataArr = [
            'user_id' => $user_id,
            'products' => $products,
            'deal_load_id' => $deal_load_id,
            'debt_id' => $debt_id,
            'status' => $status,
        ];
        $result = DebtGardenYoujieQuestionService::getInstance()->DebtDetails($dataArr);
        if($result['code'] != 0){
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '查询成功');
    }
    /**
     * 我的认购列表接口[交易取消、交易成功、待卖方收款、待付款]
     */
    public function actionSubscriptionOwn()
    {
        $products  = $this->paramsvaild('products', true,1); //1尊享 2普惠供应链
        $status  = $this->paramsvaild('status', false,10); //1-待付款 2-交易成功 3-交易取消 4-待卖方收款 默认10-全部
        $page  = $this->paramsvaild('page', false, 1);
        $limit = $this->paramsvaild('limit', false, 10);
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $dataArr = ['products' => $products, 'user_id' => $user_id, 'status' => $status, 'page' => $page, 'limit' => $limit];
        $result = DebtGardenYoujieQuestionService::getInstance()->SubscriptionOwn($dataArr);
        if($result['code'] != 0){
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '查询成功');
    }

    /**
     * 认购项目详情接口
     */
    public function actionSubscriptionDetails()
    {
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        $debt_tender_id = $this->paramsvaild('debt_tender_id', true);//债权投资记录ID
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $dataArr = [
            'products' => $products,
            'debt_tender_id' => $debt_tender_id,
            'user_id' => $user_id,
        ];
        $result = DebtGardenYoujieQuestionService::getInstance()->SubscriptionDetails($dataArr);
        if($result['code'] != 0){
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '查询成功');
    }
    /**
     * 债权发布接口
     */
    public function actionProjectTransfer()
    {
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        $deal_load_id = $this->paramsvaild('deal_load_id', true);//投资记录ID
        $is_orient = $this->paramsvaild('is_orient', false,2);//定向转让(1是 2不是) //非必传默认2
        $transaction_password = $this->paramsvaild('transaction_password', true);//交易密码
        $money = $this->paramsvaild('money', true);//转让金额
        $discount = $this->paramsvaild('discount', true);//转让折扣
        $effect_days = $this->paramsvaild('effect_days', true);//有效期
        $bankcard_id = $this->paramsvaild('bankcard_id', true);//银行卡ID
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $dataArr = [
            'products' => $products,
            'deal_load_id' => $deal_load_id,
            'user_id' => $user_id,
            'is_orient' => $is_orient,
            'transaction_password' => $transaction_password,
            'money' => $money,
            'discount' => $discount,
            'effect_days' => $effect_days,
            'bankcard_id' => $bankcard_id,
        ];
        $result = DebtGardenYoujieQuestionService::getInstance()->ProjectTransfer($dataArr);
        if($result['code'] != 0){
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '发布成功');


        //获取签约合同地址
        /*
        $result = DebtGardenYoujieQuestionService::getInstance()->getDebtContract($dataArr);
        var_dump($result);die;
        if($result['code'] != 0){
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $result['data'] = "https://testapi.fadada.com:8443/api/extsign.api?app_id=402877&timestamp=20210909212701&v=2.0&transaction_id=613a0ba560bbe231947628&contract_id=613a0ba4d4cb0132520391&customer_id=962211CE0A795B13CB98A5B5B40B7352&doc_title=%E5%80%BA%E6%9D%83%E8%BD%AC%E8%AE%A9%E5%8D%8F%E8%AE%AE&sign_keyword=A盖签&return_url=http%3A%2F%2Fqa2api.xfuser.com%2Fuser%2FXFUser%2FFddApi&read_time=5&msg_digest=NkREMDgyQjI3NEE0OTMyQTIyMTVFODc3OUI0MEI4NDFBMENFQjY0QQ==";
        $this->echoJson($result['data'], 0, '验密通过，去签约并发布');
        */
    }

    /**
     * 重新发布
     */
    public function actionAgainProjectTransfer()
    {
        $this->echoJson(array(), 100, '重新发布已关闭');

        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        $debt_id = $this->paramsvaild('debt_id', true);//债转投资记录ID
        $transaction_password = $this->paramsvaild('transaction_password', true);//交易密码
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $dataArr = [
            'products' => $products,
            'debt_id' => $debt_id,
            'user_id' => $user_id,
            'transaction_password' => $transaction_password,
        ];
        $result = DebtGardenYoujieQuestionService::getInstance()->AgainProjectTransfer($dataArr);
        if($result['code'] != 0){
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '发布成功');
    }
    /**
     * 债权认购接口
     */
    public function actionTransferBuy()
    {
        $debtArr = $this->paramsvaild('debtArr', true);//发起转让投资记录ID(json格式)
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        $buy_code = $this->paramsvaild('buy_code', false);//认购码
        $transaction_password = $this->paramsvaild('transaction_password', true);//交易密码
        if (!ItzUtil::is_json($debtArr)) {
            $this->echoJson(array(), 4006, $this->error_code_info['4006']);
        }
        //验证tenderArr数据
        if (!ItzUtil::checkJson($debtArr, 'money') || !ItzUtil::checkJson($debtArr, 'debt_id')) {
            $this->echoJson(array(), 2056, 'tenderArr参数有误');
        }
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $dataArr = [
            'debtArr' => $debtArr,
            'products' => $products,
            'buy_code' => $buy_code,
            'user_id' => $user_id,
            'transaction_password' => $transaction_password,
        ];
        $result = DebtGardenYoujieQuestionService::getInstance()->transferBuy($dataArr);
        if($result['code'] != 0){
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '认购成功');
        /*
        $result['data'] = "https://testapi.fadada.com:8443/api/extsign.api?app_id=402877&timestamp=20210909212701&v=2.0&transaction_id=613a0ba560bbe231947628&contract_id=613a0ba4d4cb0132520391&customer_id=962211CE0A795B13CB98A5B5B40B7352&doc_title=%E5%80%BA%E6%9D%83%E8%BD%AC%E8%AE%A9%E5%8D%8F%E8%AE%AE&sign_keyword=A盖签&return_url=http%3A%2F%2Fqa2api.xfuser.com%2Fuser%2FXFUser%2FFddApi&read_time=5&msg_digest=NkREMDgyQjI3NEE0OTMyQTIyMTVFODc3OUI0MEI4NDFBMENFQjY0QQ==";
        $this->echoJson($result['data'], 0, '验密通过，去签约并认购');*/
    }
    /**
     * 债转取消接口
     */
    public function actionCancelDebt()
    {
        $debt_id = $this->paramsvaild('debt_id', true);//债转记录ID
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        $checkuser  = $this->paramsvaild('checkuser', false,1); //是否校验用户登录 1:是 2:否
        if($checkuser == 1){
            //验证登录
            $user_id = $this->user_id;
            if (empty($user_id)) {
                $this->echoJson(array(), 4007, $this->error_code_info[4007]);
            }
            if($this->checkUserRisk != 0){
                $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
            }
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
        }
        $model->beginTransaction();
        try {
            $dataArr = [
                'debt_id' => $debt_id,
                'products' => $products,
                'user_id' => $user_id,
                'status' => 3,//取消
                'checkuser' => $checkuser,
            ];
            $result = DebtGardenYoujieQuestionService::getInstance()->CancelDebt($dataArr);
            if($result['code'] != 0){
                $model->rollback();
                $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
            }
            $model->commit();
        }catch (Exception $e) {
            $model->rollback();
            Yii::log("CancelDebt error :". print_r($e->getMessage(), true), "error");
            $this->echoJson([], 5000,"网络异常，请稍后重试");
        }
        $this->echoJson($result['data'], 0, '取消成功');
    }

    /**
     * 认购取消接口（买家取消）
     */
    public function actionCancelTenderDebt()
    {
        $debt_tender_id = $this->paramsvaild('debt_tender_id', true);//认购债权记录ID
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
        }
        $model->beginTransaction();
        try {
            $dataArr = [
                'debt_tender_id' => $debt_tender_id,
                'products' => $products,
                'user_id' => $user_id,
            ];
            $result = DebtGardenYoujieQuestionService::getInstance()->CancelTenderDebt($dataArr);
            if($result['code'] != 0){
                $model->rollback();
                $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
            }
            $model->commit();
        }catch (Exception $e) {
            $model->rollback();
            Yii::log("CancelTenderDebt error :". print_r($e->getMessage(), true), "error");
            $this->echoJson([], 5000,"网络异常，请稍后重试");
        }
        $this->echoJson($result['data'], 0, '取消成功');
    }
    /**
     * 确认收款接口
     */
    public function actionConfirmReceipt()
    {
        $debt_tender_id = $this->paramsvaild('debt_tender_id', false);//认购债权记录ID
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        $decision_src  = $this->paramsvaild('decision_src', false,3); //判定来源1-用户自主确认 2客服判定 3申请客服介入后卖方自主确认
        $decision_maker  = $this->paramsvaild('decision_maker', false); //判定人ID//自主确认的传卖方用户ID 客服判定的传客服ID 必传
        $decision_outcomes  = $this->paramsvaild('decision_outcomes', false); //判定结果
        $debt_id  = $this->paramsvaild('debt_id', false); //债权ID
        if(!empty($debt_id) && !empty($debt_tender_id)){
            $this->echoJson(array(), 2056, "debt_tender_id和debt_id二选一");
        }
        if(count([$debt_tender_id,$debt_id]) == 0){
            $this->echoJson(array(), 2056, "debt_tender_id和debt_id二选一");
        }
        if(!empty($debt_tender_id) && !is_numeric($debt_tender_id)){
            $this->echoJson(array(), 2056, "debt_tender_id 参数错误");
        }
        if(!empty($debt_id) && !is_numeric($debt_id)){
            $this->echoJson(array(), 2056, "debt_id 参数错误");
        }
        if(!in_array($products,Yii::app()->c->xf_config['platform_type']) || !in_array($decision_src,[1,2,3])){
            $this->echoJson(array(), 2056, $this->error_code_info[2056]);
        }

        //验证登录
        $user_id = $this->user_id;
        if($decision_src != 2){
            if (empty($user_id)) {
                $this->echoJson(array(), 4007, $this->error_code_info[4007]);
            }
            if($this->checkUserRisk != 0){
                $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
            }
            $transaction_password  = $this->paramsvaild('transaction_password', true); //交易密码
            //校验交易密码
            $checkInfo = DebtGardenYoujieQuestionService::getInstance()->checkPassWord($user_id, $transaction_password);
            if($checkInfo['code'] != 0){
                $this->echoJson(array(), $checkInfo['code'], $this->error_code_info[$checkInfo['code']]);
            }
        }
        try {
            if ($products == 1) {
                $model = Yii::app()->db;
            } else if ($products == 2) {
                $model = Yii::app()->phdb;
            } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
                $model = Yii::app()->offlinedb;
            }
            $sqladd = '';
            if ($products == 2) {
//                $dealIds = DebtGardenYoujieQuestionService::getInstance()->addPhdbWhere();
//                if(!empty($dealIds)){
//                    $sqladd = " and deal.id in($dealIds)";
//                }
                $sqladd = " and deal.product_class_type = 223";
            }
            if (!empty($debt_id) && in_array($products , [1, 2])){
                $debtInfo = $model->createCommand("select tender.id debt_tender_id 
                                                    from firstp2p_debt debt 
                                                    left join firstp2p_debt_tender tender on debt.id = tender.debt_id
                                                    left join firstp2p_deal deal on debt.borrow_id = deal.id
                                                    left join firstp2p_deal_load dload on dload.id = debt.tender_id where dload.black_status = 1 and tender.status = 6 and debt.id = $debt_id  {$sqladd}")->queryRow();
                $debt_tender_id = $debtInfo['debt_tender_id'];
            } else if (!empty($debt_id) && in_array($products , Yii::app()->c->xf_config['offline_products'])){
                $debtInfo = $model->createCommand("select tender.id debt_tender_id 
                                                    from offline_debt debt 
                                                    left join offline_debt_tender tender on debt.id = tender.debt_id
                                                    left join offline_deal deal on debt.borrow_id = deal.id
                                                    left join offline_deal_load dload on dload.id = debt.tender_id where dload.black_status = 1 and tender.status = 6 and debt.id = $debt_id  {$sqladd}")->queryRow();
                $debt_tender_id = $debtInfo['debt_tender_id'];
            }

            $dataArr = [
                'debt_tender_id' => $debt_tender_id,
                'products' => $products,
                'decision_src' => $decision_src,
                'decision_maker' => in_array($decision_src,[1,3]) ? $user_id : $decision_maker ,
                'decision_outcomes' => $decision_outcomes,
            ];
            $result = DebtService::getInstance()->confirmDebt($dataArr);
            if($result['code'] != 0){
                $sqladd = '';
                if ($products == 2) {
//                    $dealIds = DebtGardenYoujieQuestionService::getInstance()->addPhdbWhere();
//                    if(!empty($dealIds)){
//                        $sqladd = " and deal.id in($dealIds)";
//                    }
                    $sqladd = " and deal.product_class_type = 223";
                }
                if (in_array($products , [1, 2])) {
                    $tenderInfo = $model->createCommand("select tender.id,debt.money,debt.discount,debt.id debt_id,tender.status,tender.user_id,debt.serial_number 
                                                    from firstp2p_debt_tender tender 
                                                    left join firstp2p_debt debt on debt.id = tender.debt_id
                                                    left join firstp2p_deal deal on debt.borrow_id = deal.id
                                                    left join firstp2p_deal_load dload on dload.id = debt.tender_id where dload.black_status = 1 and tender.id = $debt_tender_id {$sqladd}")->queryRow();
                } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
                    $tenderInfo = $model->createCommand("select tender.id,debt.money,debt.discount,debt.id debt_id,tender.status,tender.user_id,debt.serial_number 
                                                    from offline_debt_tender tender 
                                                    left join offline_debt debt on debt.id = tender.debt_id
                                                    left join offline_deal deal on debt.borrow_id = deal.id
                                                    left join offline_deal_load dload on dload.id = debt.tender_id where dload.black_status = 1 and tender.id = $debt_tender_id {$sqladd}")->queryRow();
                }
                if (empty($tenderInfo)) {
                    $this->echoJson(array(), 4002, $this->error_code_info[4002]);
                }
                //转让人收款确认失败——认购方
                $remind = array();
                $remind['sms_code'] = "wx_buyer_seller_receive_money_fail";
                $remind['data']['order_no'] = $tenderInfo["serial_number"];
                $remind['mobile'] = $this->getPhone($tenderInfo['user_id']);
                $smaClass = new XfSmsClass();
                $send_ret = $smaClass->sendToUserByPhone($remind);
                if($send_ret['code'] != 0){
                    Yii::log("ConfirmReceipt user_id:$user_id, debt_id:{$tenderInfo['debt_id']}; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
                }
                $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
            }
        }catch (Exception $e) {
            Yii::log("ConfirmReceipt error :". print_r($e->getMessage(), true), "error");
            $this->echoJson([], 5000,"网络异常，请稍后重试");
        }
        $this->echoJson($result['data'], 0, '收款成功');
    }

    /**
     * 查询解密手机号
     * @param $user_id
     * @return bool|mixed
     */
    private function getPhone($user_id){
        if(empty($user_id) || !is_numeric($user_id)){
            return false;
        }
        //用户信息
        $userInfo = User::model()->findByPk($user_id);
        if(empty($userInfo)){
            return false;
        }
        return GibberishAESUtil::dec($userInfo->mobile, Yii::app()->c->contract['idno_key']);
    }
    /**
     * 个人信息接口
     */
    public function actionPayeeInfo()
    {
        $debt_tender_id = $this->paramsvaild('debt_tender_id', true);//认购债权记录ID
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if(!in_array($products,Yii::app()->c->xf_config['platform_type'])){
            $return_result['code'] = 2056;
            return $return_result;
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
        }
        if (in_array($products , [1, 2])) {
            $tenderInfo = $model->createCommand("select debt.payee_name,debt.payee_bankzone,debt.payee_bankcard,tender.addtime from firstp2p_debt_tender tender left join firstp2p_debt debt on tender.debt_id = debt.id where tender.id = $debt_tender_id")->queryRow();
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $tenderInfo = $model->createCommand("select debt.payee_name,debt.payee_bankzone,debt.payee_bankcard,tender.addtime from offline_debt_tender tender left join offline_debt debt on tender.debt_id = debt.id where tender.id = $debt_tender_id")->queryRow();
        }
        if(empty($tenderInfo)){
            $this->echoJson(array(), 2059, $this->error_code_info[2059]);
        }
        //收款人、付款人信息
        $bankArr = [];
        $bankInfo = Yii::app()->db->createCommand("select id,payer_bankzone from ag_wx_payer_bankzone")->queryAll();
        if(!empty($bankInfo)){
            foreach($bankInfo as $k => $v){
                $bankArr[] = array(
                    "bankcard_id" => $v['id'],
                    "bankzone" => $v['payer_bankzone'],
                );
            }
        }
        //承接待付款有效期倒计时
        $undertake_endtime = $tenderInfo['addtime'] + ConfUtil::get('youjie-undertake-endtime') - time();
        $undertake_endtime = $undertake_endtime > 0 ? $undertake_endtime : 0;
        $payee_bankcard =  GibberishAESUtil::dec($tenderInfo['payee_bankcard'], Yii::app()->c->idno_key);//解密银行卡号;
        $ret = ["payee_name" => $tenderInfo['payee_name'], "payee_bankzone" => $tenderInfo['payee_bankzone'], "payee_bankcard" => $payee_bankcard,"payer_bankzone" => $bankArr,"undertake_endtime" => $undertake_endtime];

        $this->echoJson($ret, 0, '返回成功');
    }

    /**
     * 转账付款接口
     */
    public function actionTransferPayment()
    {
        $debt_tender_id = $this->paramsvaild('debt_tender_id', true);//认购债权记录ID
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        $payer_name  = $this->paramsvaild('payer_name', true); //付款人姓名
        $payer_bankzone  = $this->paramsvaild('payer_bankzone', true); //付款人开户行
        $payer_bankcard  = $this->paramsvaild('payer_bankcard', true); //付款人银行卡号
        $account  = $this->paramsvaild('account', true); //付款金额
        $pay_voucher  = $this->paramsvaild('pay_voucher', true); //付款凭证
        if(!ItzUtil::is_json($pay_voucher)){
            $this->echoJson(array(), 2056, 'pay_voucher参数有误');
        };
        //验证登录
        $user_id = $this->user_id;
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $model->beginTransaction();
        try {
            $dataArr = [
                'debt_tender_id' => $debt_tender_id,
                'products' => $products,
                'user_id' => $user_id,
                'payer_name' => $payer_name,
                'payer_bankzone' => $payer_bankzone,
                'account' => $account,
                'pay_voucher' => $pay_voucher,
                'payer_bankcard' => $payer_bankcard,
            ];
            $result = DebtGardenYoujieQuestionService::getInstance()->TransferPayment($dataArr);
            if($result['code'] != 0){
                $model->rollback();
                $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
            }
            $model->commit();
        }catch (Exception $e) {
            $model->rollback();
            Yii::log("TransferPayment error :". print_r($e->getMessage(), true), "error");
            $this->echoJson([], 5000,"网络异常，请稍后重试");
        }
        $this->echoJson($result['data'], 0, '转账成功');
    }

    /**
     * 校验认购码
     */
    public function actionCheckBuyCode()
    {
        $products  = $this->paramsvaild('products', true); //1尊享 2普惠供应链
        $debt_id  = $this->paramsvaild('debt_id', true); //债权ID
        $buy_code  = $this->paramsvaild('buy_code', true); //认购码
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if(!in_array($products,Yii::app()->c->xf_config['platform_type']) || !is_numeric($debt_id) || !is_numeric($buy_code)){
            $return_result['code'] = 2056;
            return $return_result;
        }
        if ($products == 1) {
            $model = Yii::app()->db;
        } else if ($products == 2) {
            $model = Yii::app()->phdb;
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $model = Yii::app()->offlinedb;
        }
        $sqladd = '';
        if($products == 2){
//            $dealIds = DebtGardenYoujieQuestionService::getInstance()->addPhdbWhere();
//            if(!empty($dealIds)){
//                $sqladd = " and deal.id in($dealIds)";
//            }
            $sqladd = " and deal.product_class_type = 223";
        }
        if (in_array($products , [1, 2])) {
            $tenderInfo = $model->createCommand("select debt.buy_code,debt.id 
                                                from firstp2p_debt debt 
                                                left join firstp2p_deal_load dload on debt.tender_id = dload.id
                                                left join firstp2p_deal deal on deal.id = debt.borrow_id 
                                                where debt.id = $debt_id and dload.black_status = 1 and debt.buy_code > 0 {$sqladd}")->queryRow();
        } else if (in_array($products , Yii::app()->c->xf_config['offline_products'])) {
            $tenderInfo = $model->createCommand("select debt.buy_code,debt.id 
                                                from offline_debt debt 
                                                left join offline_deal_load dload on debt.tender_id = dload.id
                                                left join offline_deal deal on deal.id = debt.borrow_id 
                                                where debt.id = $debt_id and dload.black_status = 1 and debt.buy_code > 0 {$sqladd}")->queryRow();
        }
        if(empty($tenderInfo)){
            $this->echoJson(array(), 2059, $this->error_code_info[2059]);
        }
        if(!empty($tenderInfo['buy_code']) && $tenderInfo['buy_code'] != $buy_code){
            $this->echoJson(array(), 2310, $this->error_code_info[2310]);
        }
        //用户待还本息5万校验
        $result = DebtService::getInstance()->checkUser($user_id);
        if($result == false){
            $this->echoJson(array(), 5005, $this->error_code_info[5005]);
        }
        $this->echoJson(array(), 0, "认购码校验成功");
    }


    /**
     * 汇源专区确认出售接口
     */
    public function actionConfirmSale()
    {
        $deal_load_ids = $this->paramsvaild('deal_load_id', true);//投资记录ID
        $transaction_password = $this->paramsvaild('transaction_password', true);//交易密码
        $purchase_id = $this->paramsvaild('purchase_id', true);//求购记录ID
        $bankcard_id = $this->paramsvaild('bankcard_id', true);//银行卡ID

        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }

        $deal_load_ids = implode(',' , $deal_load_ids);
        $dataArr = [
            'purchase_id' => $purchase_id,
            'deal_load_id' => $deal_load_ids,
            'user_id' => $user_id,
            'transaction_password' => $transaction_password,
            'bankcard_id' => $bankcard_id
        ];
        $sale_result = DebtService::getInstance()->confirmSale($dataArr);
        if($sale_result['code'] != 0){
            $this->echoJson(array(), $sale_result['code'], $this->error_code_info[$sale_result['code']]);
        }
        $this->echoJson($sale_result['data'], 0, '出售成功');
    }
}