<?php

class IndexController extends CommonController
{
    protected $logFile = 'ContractTaskHandleCommand';
    public $platform_id = '';
    public $platformUserId = '';
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 判断是否需要登录
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
                $riskInfo = QuestionService::getInstance()->checkUserRisk($this->user_id);
                return $riskInfo['code'];
            }
        }
    }
    /**
     * 债权求购列表
     * @param   b_userid    int 买家用户id
     * @param   s_userid    int 卖家用户id
     * @param   limit       查询限制
     * @param   page        当前page
     * @param   field       排序字段1：发布时间:2：折扣
     * @param   order       排序1:倒序2：升序
     * @param   project_ids 求购项目id
     * @param   isable      1：只看可转让的 2：看全部的转让
     * @param   platform_id 平台id
     * @return  array
     */
    public function actionPurchaseList()
    {
        $limit = $this->paramsvaild('limit', false, 10);
        $b_userid = $this->paramsvaild('b_userid', false);//买家用户id
        $s_userid = $this->paramsvaild('s_userid', false);//卖家用户id
        $page = $this->paramsvaild('page', false, 1);     //当前页码
        $order = $this->paramsvaild('order', false, 1);      //排序1:倒序2：升序
        $field = $this->paramsvaild('field', false, 1);      //排序字段1：发布时间:2：折扣
        $project_ids = $this->paramsvaild('project_ids', false);//求购项目id
        $isable = $this->paramsvaild('isable', false, 2);       //1：只看可转让的 2：看全部的转让
        $platform_id = $this->paramsvaild('platform_id', true);   //平台id
        $result_data = array('count' => 0, 'page_count' => 0, 'data' => array());
        //排序
        if (!in_array($order, array(1, 2))) {
            $this->echoJson(array(), 2056, $this->error_code_info['2056']);
        }
        //是否只看可转让的
        if (!in_array($isable, array(1, 2))) {
            $this->echoJson(array(), 2056, $this->error_code_info['2056']);
        }
        //limit限制不大于100
        if ($limit > 100) {
            $this->echoJson(array(), 4003, $this->error_code_info['4003']);
        }

        //不能同时传入买卖双方用户id
        if (!empty($b_userid) && !empty($s_userid)) {
            $this->echoJson(array(), 4004, $this->error_code_info['4004']);
        }
        $data = array(
            "b_userid" => $b_userid,
            "limit" => $limit,
            "page" => $page,
            "order" => $order,
            "field" => $field,
            "project_ids" => $project_ids,
            "platform_id" => $platform_id,
            "s_userid" => !empty($this->user_id) ? $this->user_id : $s_userid,
            "isable" => $isable,
        );
        if(!empty($this->user_id)){
            if($this->checkUserRisk != 0){
                $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
            }
        }
        $platformItz_id = Yii::app()->c->itouzi['itouzi']['platform_id'];
        if($platform_id == $platformItz_id){
            //非导入数据平台
            $create_ret = AgDebtitouziService::getInstance()->purchaseList($data);
        }else{
            //导入数据平台
            $create_ret = PurchService::getInstance()->purchaseList($data);
        }
        if ($create_ret['code'] != 0) {
            if (empty($create_ret['data'])) {
                $create['count'] = 0;
                $create['page_count'] = 0;
                $create['data'] = [];
            }
            $this->echoJson($create, $create_ret['code'], $this->error_code_info[$create_ret['code']]);
        }
        $result_data['count'] = $create_ret['count'];
        $result_data['page_count'] = $create_ret['page_count'];
        $result_data['data'] = $create_ret['data'];
        $this->echoJson($result_data, 0, '查询成功');
    }

    /**
     * 变更是否同意协议记录状态
     *
     */
    public function actionTransAgree()
    {
        $platform_id = $this->paramsvaild('platform_id', true);   //平台id
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        //更新用户关系表
        $old = \Yii::app()->agdb->createCommand("SELECT * FROM ag_user_platform WHERE user_id = {$user_id} AND platform_id = {$platform_id}")->queryRow();
        if ($old) {
            $res = \Yii::app()->agdb->createCommand("UPDATE ag_user_platform SET agree_status = 1 WHERE user_id = {$user_id} AND platform_id = {$platform_id}")->execute();
            if (!$res) $this->echoJson(array(), 4010, $this->error_code_info['4010']);
            $this->echoJson(array("user_id" => $user_id, "agree_status" => 1), 0, '更新协议状态成功');
        } else {
            $time = time();
            $res = \Yii::app()->agdb->createCommand("INSERT INTO ag_user_platform (user_id , platform_id , agree_status , agree_time) VALUES ({$user_id} , {$platform_id} , 1 , {$time}) ")->execute();
            if (!$res) $this->echoJson(array(), 4010, $this->error_code_info['4010']);
            $this->echoJson(array("user_id" => $user_id, "agree_status" => 1), 0, '更新协议状态成功');
        }
    }

    /**
     * 发起转让列表页面
     *
     */
    public function actionTransferInList()
    {
        $platform_id = $this->paramsvaild('platform_id', true);   //平台id
        $pur_id = $this->paramsvaild('pur_id', true);//求购计划id
        $type_id = $this->paramsvaild('type_id', false);//项目类型
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        if (empty($pur_id)) {
            $this->echoJson(array(), 4001, $this->error_code_info['4001']);
        }
        //验证风险等级
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $platformItz_id = Yii::app()->c->itouzi['itouzi']['platform_id'];
        if ($platform_id == $platformItz_id) {
            //平台
            $result = AgDebtitouziService::getInstance()->transferInlist($pur_id, $user_id, $platform_id, $type_id);
        }else{
            //导入数据平台
            $result = PurchService::getInstance()->transferInlist($pur_id, $user_id, $platform_id, $type_id);
        }
        if ($result['code'] != 0) {
            $this->echoJson(array(), $result['code'], $this->error_code_info[$result['code']]);
        }
        $this->echoJson($result['data'], 0, '查询成功');
    }
    /**
     * 批量认购债权
     */
    public function actionTransferBuy()
    {
        $tenderArr = $this->paramsvaild('tenderArr', true);//发起转让投资记录ID(json格式)
        $pur_id = $this->paramsvaild('pur_id', true);//求购计划id
        $platformId = $this->paramsvaild('platform_id', true);   //平台id
        $payPassword = $this->paramsvaild('payPassword', true);//交易密码
        if (!ItzUtil::is_json($tenderArr)) {
            $this->echoJson(array(), 4006, $this->error_code_info['4006']);
        }
        //验证tenderArr数据
        if (!ItzUtil::checkJson($tenderArr, 'money') || !ItzUtil::checkJson($tenderArr, 'tender_id')) {
            $this->echoJson(array(), 2056, 'tenderArr参数有误');
        }
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        //验证风险等级
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $agPassInfo = AgDebtitouziService::getInstance()->checkAgPassWord($user_id, $payPassword);
        //校验支付密码
        if ($agPassInfo['code'] != 0) {
            $this->echoJson(array(), $agPassInfo['code'], $this->error_code_info[$agPassInfo['code']]);
        }
        $platformItz_id = Yii::app()->c->itouzi['itouzi']['platform_id'];
        if ($platformId == $platformItz_id) {
            //平台批量认购
            $userInfo = PurchService::getInstance()->transformationUserid($user_id, $platformId);
            if($userInfo['code'] != 0){
                //校验用户确权同意协议完
                $this->echoJson(array(), $userInfo['code'], $this->error_code_info[$userInfo['code']]);
            }
            $platformUserId = $userInfo['data']['platform_user_id'];
            $dataArr = array(
                "tenderArr" => $tenderArr,
                "pur_id" => $pur_id,
                "platformUserId" => $platformUserId,//平台用户id
                "user_id" => $user_id,//C1用户id
                "platformId" => $platformId,
            );
            $ret = AgDebtitouziService::getInstance()->transferTransferBuy($dataArr);
        }else{
            //其他平台
            $ret = PurchService::getInstance()->transferTransferBuy($tenderArr, $pur_id, $user_id, $this->platform_id);
        }
        //当返回全部失败的时候code码相等直接返回失败code码
        if(empty($ret['success'])){
            $codes = ArrayUtil::array_column($ret['fail'],"code");
            $codeUnique = array_unique($codes);
            //去重之后查看是否唯一code
            if(count($codeUnique) == 1){
                $this->echoJson($ret, $codeUnique[0],$this->error_code_info[$codeUnique[0]]);
            }
        }
        $this->echoJson($ret, 0, '批量返回结果');
    }

    /**
     * 资方批量认购（资方后台）
     */
    public function actionAmcTransferbuy()
    {
        $retArr = ["success" => [],"fail" => []];
        $debtArr = $this->paramsvaild('debtArr', true);//发起转让投资记录ID(json格式)
        $utype = $this->paramsvaild('utype', true);//1:资方用户认购 2：C1用户认购
        if (!ItzUtil::is_json($debtArr)) {
            $this->echoJson(array(), 4006, $this->error_code_info['4006']);
        }
        //验证tenderArr数据
        if (!ItzUtil::checkJson($debtArr, 'money') || !ItzUtil::checkJson($debtArr, 'debt_id')) {
            $this->echoJson(array(), 2056, 'debtArr参数有误');
        }
        //资方后台认购
        if ($utype == 1) {
            $buy_userid = $this->paramsvaild('buy_userid', true);//购买用户id
            $f_platform_id = $this->paramsvaild('f_platform_id', true);//理财机构平台id
        }
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        //C1认购C1
        if ($utype == 2) {
            $f_platform_id = $this->paramsvaild('platform_id', true);   //平台id
            //需要支付密码验证
            $payPassword = $this->paramsvaild('payPassword', true);//C1认购必填
            $agPassInfo = AgDebtitouziService::getInstance()->checkAgPassWord($user_id, $payPassword);
            if ($agPassInfo['code'] != 0) {
                $this->echoJson(array(), $agPassInfo['code'], $this->error_code_info[$agPassInfo['code']]);
            }

            //验证C1认购时指定风险等级双层验证
            $userRisk = QuestionService::getInstance()->checkUserRisk($user_id, 2);
            if($userRisk['code'] != 0){
                $errorInfo = $this->error_code_info[$userRisk['code']];
                if($userRisk['code'] == 2101){
                    $errorInfo = $userRisk['info'];
                }
                $this->echoJson(array(), $userRisk['code'], $errorInfo);
            }
        }
        $platformItz_id = Yii::app()->c->itouzi['itouzi']['platform_id'];
        if ($f_platform_id == $platformItz_id) {
            //平台批量认购
            $userInfo = PurchService::getInstance()->transformationUserid($user_id, $f_platform_id);
            if($userInfo['code'] != 0){
                //校验用户确权同意协议完
                $this->echoJson(array(), $userInfo['code'], $this->error_code_info[$userInfo['code']]);
            }
            $buy_userid = $userInfo['data']['platform_user_id'];//平台用户id
            //如果是平台批量认购
            $login_userid = $this->user_id;
            $ret = AgDebtitouziService::getInstance()->transferAmcTransferBuy($debtArr, $buy_userid, $utype, $login_userid);
        } else {
            //其他平台
            $ret = PurchService::getInstance()->transferAmcTransferBuy($debtArr, $user_id, $utype, 1);
        }
        //当返回全部失败的时候code码相等直接返回失败code码
        if(empty($ret['success'])){
            $codes = ArrayUtil::array_column($ret['fail'],"code");
            $codeUnique = array_unique($codes);
            //去重之后查看是否唯一code
            if(count($codeUnique) == 1){
                $this->echoJson($retArr, $codeUnique[0],$this->error_code_info[$codeUnique[0]]);
            }
        }
        if($ret['code'] != 0){
            $this->echoJson($retArr, $ret['code'],$this->error_code_info[$ret['code']]);
        }
        $this->echoJson($ret, 0, '批量返回结果');
    }

    /**
     * 资方批量认购校验接口
     */
    public function actionAmcTransferbuyRule()
    {
        $retArr = ["success" => [],"fail" => []];
        $debtArr = $this->paramsvaild('debtArr', true);//发起转让投资记录ID(json格式)
        $utype = $this->paramsvaild('utype', true);//1:资方用户认购 2：C1用户认购
        if (!ItzUtil::is_json($debtArr)) {
            $this->echoJson(array(), 4006, $this->error_code_info['4006']);
        }
        if (!in_array($utype, [1, 2])) {
            $this->echoJson(array(), 2056, "参数有误");
        }
        //验证tenderArr数据
        if (!ItzUtil::checkJson($debtArr, 'money') || !ItzUtil::checkJson($debtArr, 'debt_id')) {
            $this->echoJson(array(), 2056, 'debtArr参数有误');
        }
        $user_id = $this->user_id;//验证登录
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        //资方后台认购
        if ($utype == 1) {
            $buy_userid = $this->paramsvaild('buy_userid', true);//购买用户id
            $f_platform_id = $this->paramsvaild('f_platform_id', true);//理财机构平台id
        }
        //C1认购C1
        if ($utype == 2) {
            $f_platform_id = $this->paramsvaild('platform_id', true);   //平台id
            //需要支付密码验证
            $payPassword = $this->paramsvaild('payPassword', true);//C1认购必填
            $agPassInfo = AgDebtitouziService::getInstance()->checkAgPassWord($user_id, $payPassword);
            if ($agPassInfo['code'] != 0) {
                $this->echoJson(array(), $agPassInfo['code'], $this->error_code_info[$agPassInfo['code']]);
            }
            $buy_userid = $this->platformUserId;//平台用户id
            //验证C1认购时指定风险等级双层验证
            $userRisk = QuestionService::getInstance()->checkUserRisk($user_id, 2);
            if($userRisk['code'] != 0){
                $errorInfo = $this->error_code_info[$userRisk['code']];
                if($userRisk['code'] == 2101){
                    $errorInfo = $userRisk['info'];
                }
                $this->echoJson(array(), $userRisk['code'], $errorInfo);
            }
        }
        $platformItz_id = Yii::app()->c->itouzi['itouzi']['platform_id'];
        if ($f_platform_id == $platformItz_id) {
            //如果是平台批量认购
            $login_userid = $this->user_id;
            Yii::log("AmcTransferbuyRule start: debtArr:$debtArr buy_userid:$buy_userid utype:$utype f_platform_id:$f_platform_id");
            $ret = AgDebtitouziService::getInstance()->transferAmcTransferBuy($debtArr, $buy_userid, $utype, $login_userid, 2);
        }else{
            //其他平台
            $ret = PurchService::getInstance()->transferAmcTransferBuy($debtArr, $buy_userid, $utype, 2);
        }
        //当返回全部失败的时候code码相等直接返回失败code码
        if(empty($ret['success'])){
            $codes = ArrayUtil::array_column($ret['fail'],"code");
            $codeUnique = array_unique($codes);
            //去重之后查看是否唯一code
            if(count($codeUnique) == 1){
                $this->echoJson($retArr, $codeUnique[0],$this->error_code_info[$codeUnique[0]]);
            }
        }
        if($ret['code'] != 0){
            $this->echoJson($retArr, $ret['code'],$this->error_code_info[$ret['code']]);
        }
        $this->echoJson($ret, 0, '批量返回结果');
    }

    /**
     * 我的认购列表
     *
     */
    public function actionSubscription()
    {
        $type = $debtArr = $this->paramsvaild('type', true, 1);//1:认购中2：认购成功3：认购失败
        $page = $debtArr = $this->paramsvaild('page', false, 1);//当前页数(默认为1,正整数)
        $limit = $debtArr = $this->paramsvaild('limit', false, 10);//每页显示数据量(默认为10,取值范围1至100的正整数)
        $order = $debtArr = $this->paramsvaild('order', false, 1);//排序方式(默认为1)：1-认购时间降序，2-认购时间升序
        $platform_id = $this->paramsvaild('platform_id', true);//平台id
        $result_data = array('count' => 0, 'page_count' => 0, 'data' => array());
        if (!is_numeric($page) || !is_numeric($type) || !is_numeric($limit) || !is_numeric($order) || !in_array($type, [1, 2, 3])) {
            $this->echoJson(array(), 2056, "参数有误");
        }
        if ($limit > 100) {
            $this->echoJson(array(), 4003, $this->error_code_info[4003]);
        }
        $user_id = $this->user_id;
        //登录验证
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        //验证风险等级
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $dataArr = array(
            "type" => $type,
            "limit" => $limit,
            "page" => $page,
            "order" => $order,
            "platform_id" => $platform_id,
        );
        //如果是平台批量认购
        $platformItz_id = Yii::app()->c->itouzi['itouzi']['platform_id'];
        if ($platformItz_id == $platform_id) {
            //平台用户id
            $userInfo = PurchService::getInstance()->transformationUserid($user_id, $platform_id);
            if($userInfo['code'] != 0){
                //校验用户确权同意协议等
                $this->echoJson(array(), $userInfo['code'], $this->error_code_info[$userInfo['code']]);
            }
            $dataArr['user_id'] = $userInfo['data']['platform_user_id'];//平台用户id
            $result = AgDebtitouziService::getInstance()->subscriPtion($dataArr);
        } else {
            $dataArr['user_id'] = $user_id;
            $result = PurchService::getInstance()->subscriPtion($dataArr);
        }
        if ($result['code'] != 0) {
            if (empty($result['data'])) {
                $create['count'] = 0;
                $create['page_count'] = 0;
                $create['data'] = [];
            }
            $this->echoJson($create, $result['code'], $this->error_code_info[$result['code']]);
        }
        $result_data['count'] = $result['count'];
        $result_data['page_count'] = $result['page_count'];
        $result_data['status'] = $result['status'];
        $result_data['data'] = $result['data'];
        $this->echoJson($result_data, 0, '返回成功');
    }

    /**
     * C1认购订单状态查询
     */
    public function actionAmcTrView()
    {
        $debt_id = $this->paramsvaild('debt_id', true);//债权记录ID
        $platform_id = $this->paramsvaild('platform_id', true);//平台id
        $user_id = $this->user_id;
        //验证登录
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        //验证风险等级
        if($this->checkUserRisk != 0){
            $this->echoJson(array(), $this->checkUserRisk, $this->error_code_info[$this->checkUserRisk]);
        }
        $platformItz_id = Yii::app()->c->itouzi['itouzi']['platform_id'];
        if ($platform_id == $platformItz_id) {
            //平台
            $transformationInfo = PurchService::getInstance()->transformationUserid($user_id, $platform_id);
            if ($transformationInfo['code'] != 0) {
                $this->echoJson([], $transformationInfo['code'], $this->error_code_info[$transformationInfo['code']]);
            }
            $yiimodel = Yii::app()->yiidb;
            //平台用户id
            $userInfo = PurchService::getInstance()->transformationUserid($user_id, $platform_id);
            if($userInfo['code'] != 0){
                //校验用户确权同意协议等
                $this->echoJson(array(), $userInfo['code'], $this->error_code_info[$userInfo['code']]);
            }
            $platform_user_id = $userInfo['data']['platform_user_id'];//平台用户id
            $exchangeInfo = $yiimodel->createCommand("select * from itz_ag_debt_exchange where id = $debt_id and buyer_uid = $platform_user_id")->queryRow();
            if(empty($exchangeInfo))  $this->echoJson([], 4002, '暂无数据');
            //1:转让中, 2:买家已购买待处理, 3:债转成功, 4:债转失败, 5:已取消 6:已过期
            $dateStatus = array('1' => 1, '2' => 1, '3' => 2, '4' => 3, '5' => 3, '6' => 3);
            $this->echoJson(['status' => $dateStatus[$exchangeInfo['status']]], 0, '返回成功');//1:认购中2：认购成功3：认购失败
        } else {
            //导入数据平台
            $agmodel = Yii::app()->agdb;
            $exchangeInfo = $agmodel->createCommand("select * from ag_debt where id = $debt_id and user_id = $user_id  ")->queryRow();
            if (empty($exchangeInfo)) $this->echoJson([], 4002, '暂无数据');
            //1转让中 2转让完成 3用户取消 4自动过期
            $dateStatus = array('1' => 1, '2' => 2, '3' => 3, '4' => 3);
            $this->echoJson(['status' => $dateStatus[$exchangeInfo['status']]], 0, '返回成功');
        }
    }



}