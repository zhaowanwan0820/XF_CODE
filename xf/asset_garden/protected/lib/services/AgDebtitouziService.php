<?php

/**
 * 债转系统
 */
class AgDebtitouziService extends ItzInstanceService
{
    const MIN_LOAN_AMOUNT = 100; //最低起投金额

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 匹配平台债转求购列表
     * @param $data [
     * $b_userid 买家用户id
     * $s_userid 卖家用户id
     * $limit 区间
     * $page 当前页
     * $order 排序1：倒序 2：升序
     * $project_ids  求购项目ids
     * $isable  1：只看可转让的 2：看全部的转让
     * $platform_id 平台id
     * ]
     * @return array
     */
    public function purchaseList($data)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        //分页设置
        $pass = ($data['page'] - 1) * $data['limit'];  //跳过数据
        $data_limit = "LIMIT {$pass},{$data['limit']}";
        //按照字段进行排序
        $sort = "DESC";
        if ($data['order'] == 1) {
            $sort = "DESC";
        }
        //升序
        if ($data['order'] == 2) {
            $sort = "ASC";
        }
        //初始化
        $orderby = "ORDER BY id " . $sort;
        if ($data['field'] == 1) {
            $orderby = "ORDER BY id ";
        }
        if ($data['field'] == 2) {
            $orderby = "ORDER BY discount ";
        }
        $sqlAdd = '';
        if(!empty($data['b_userid'])){
            $sqlAdd = " user_id = {$data['b_userid']} AND";
        }
        $orderby = $orderby . $sort;
        $yiimodel = Yii::app()->yiidb;
        $retAll = '';
        //只看可转让的
        if($data['isable'] == 1){
            $data_limit = "";
        }
        //卖方求购项目类型
        $count = $yiimodel->createCommand("SELECT count(*) FROM itz_ag_purchase_order WHERE {$sqlAdd} money > acquired_money AND expiry_time >= UNIX_TIMESTAMP() AND status = 1")->queryScalar();
        if ($count == 0) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $purchaseOrderInfo = $yiimodel->createCommand("SELECT * FROM itz_ag_purchase_order WHERE {$sqlAdd} money > acquired_money AND expiry_time >= UNIX_TIMESTAMP() AND status = 1 {$orderby} {$data_limit}")->queryAll();
        //卖家用户ID
        $sxborrowIds= '';
        if (!empty($data['s_userid'])) {
            //验证用户是否绑定平台授权确权同意协议
            $transformationInfo = PurchService::getInstance()->getformationUserid($data['s_userid'], $data['platform_id']);
            if($transformationInfo['code'] != 0){
                $return_result['code'] = $transformationInfo['code'];
                return $return_result;
            }
            $platformUserId = $transformationInfo['data']['platform_user_id'];//平台用户id
            //查看卖家投资记录省心计划可债转
            $sxData = array("isAll" => true, "fields" => 'DISTINCT borrow_id', "user_id" => $platformUserId);
            $sxPlanInfo = $this->purchaseSxPlan($sxData);
            if (isset($sxPlanInfo['code']) && $sxPlanInfo['code'] == 0) {
                $sxborrowIds = ItzUtil::array_column($sxPlanInfo['data'], "borrow_id");
                $sxborrowIds = implode(",", $sxborrowIds);
            }
        }
        $itouzi_config = Yii::app()->c->itouzi;
        $project_types = implode(",",array_keys($itouzi_config['itouzi']['type']));
        foreach ($purchaseOrderInfo as $key => $val) {
            $val['operable'] = 0;
            //project_ids、project_types不指定收购平台下所有项目
            if (empty($val['project_ids']) && empty($val['project_types']) && !empty($sxborrowIds)) $val['operable'] = 1;
            //指定了项目类型project_types未指定project_ids,匹配project_types
            if(!empty($val['project_types']) && empty($val['project_ids']) && !empty($sxborrowIds) && ItzUtil::intersec($val['project_types'], $project_types)) $val['operable'] = 1;
            //指定了项目project_ids未指定project_types 进行匹配
            if (!empty($val['project_ids']) && empty($val['project_types']) && !empty($sxborrowIds) && ItzUtil::intersec($val['project_ids'], $sxborrowIds)) $val['operable'] = 1;
            //project_ids、project_types都不为空时，校验求购计划添加是否正确
            if(!empty($val['project_types']) && !empty($val['project_ids'])){
                $borrowTypeInfo = $yiimodel->createCommand("select id,type from dw_borrow where id IN({$val['project_ids']}) and type in({$val['project_types']})")->queryAll();
                if(!empty($borrowTypeInfo) && ItzUtil::intersec($val['project_ids'], $sxborrowIds) && ItzUtil::intersec($val['project_types'], $project_types)) $val['operable'] = 1;
            }
            //全部的
            $retAll[] = array(
                "pur_id" => $val['id'],//求购计划id
                "discount" => $val['discount'],//折扣信息
                "expiry_time" => $val['expiry_time'],//剩余有效期返回时间戳
                "acquired_money" => $val['acquired_money'],//已购得金额
                "money" => $val['money'],//求购金额
                "operable" => $val['operable'],//发起转让状态 1:可操作 0:不可操作
            );
        }
        //只看可转让的       
        if ($data['isable'] == 1) {
            $retIsable = '';//可转让
            foreach ($retAll as $val) {
                if ($val['operable'] == 1) {
                    $retIsable[] = $val;
                }
            }
            $retAll = array_slice($retIsable, $pass, $data['limit']);
        }
        if(empty($retAll)){
            $return_result['code'] = 4002;
            return $return_result;
        }
        $return_result['code'] = 0;
        $return_result['page_count'] = $data['isable'] == 1 ? ceil(count($retAll) / $data['limit']) : ceil($count / $data['limit']);
        $return_result['count'] = $data['isable'] == 1 ? count($retAll) : $count;
        $return_result['data'] = $retAll;
        return $return_result;

    }
    /**
     * 发起转让列表
     * @param $pur_id
     * @param $user_id //平台用户id
     * @param $platform_id //平台用户id
     * @param $type_id //项目类型
     * @return array()
     */
    public function  transferInlist($pur_id, $user_id, $platform_id, $type_id = "")
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        //未登录
        if(empty($user_id)){
            $return_result['code'] = 4007;
            return $return_result;
        }
        //未选择平台
        if(empty($platform_id)){
            $return_result['code'] = 1010;
            return $return_result;
        }
        //校验用户确权同意协议完
        $userInfo = PurchService::getInstance()->getformationUserid($user_id, $platform_id);
        if($userInfo['code'] != 0){
            $return_result['code'] = $userInfo['code'];
            return $return_result;
        }
        //平台用户id
        $platformUserId = $userInfo['data']['platform_user_id'];
        $yiimodel = Yii::app()->yiidb;
        $sql = "SELECT id,discount,project_ids FROM itz_ag_purchase_order WHERE id = {$pur_id}";
        $purchaseOrderInfo = $yiimodel->createCommand($sql)->queryRow();
        if (empty($purchaseOrderInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $ret['list'] = array();
        $yiiModel = Yii::app()->yiidb;
        //认购计划有包含的项目 项目到期日升序,仅列用户和收购计划共有的项目 在存的无债转
        if (!empty($purchaseOrderInfo['project_ids'])) {
            //大标的省心计划
            $data = array(
                "isAll" => true,
                "user_id" => $platformUserId,
                "condition" => "AND borrow_id IN ({$purchaseOrderInfo['project_ids']})"
            );
            
        } else {
            $data = array("isAll" => true, "user_id" => $platformUserId);
        }
        //可转让投资记录
        $sxPlanInfo = $this->purchaseSxPlan($data);
        if($sxPlanInfo['code'] != 0){
            $return_result['code'] = 4002;
            return $return_result;
        }
        if (!empty($type_id)) {
            $borrowAddSql = " AND type = {$type_id}";
        }
        $sxborrowIds = array_unique(ArrayUtil::array_column($sxPlanInfo['data'], "borrow_id"));
        $sxborrowIds = implode(",", $sxborrowIds);
        $borrowInfo = $yiiModel->createCommand("SELECT id,name,type FROM dw_borrow WHERE id IN($sxborrowIds) {$borrowAddSql}")->queryAll();
        if(empty($borrowInfo)){
            $return_result['code'] = 4002;
            return $return_result;
        }
        $borrowInfoTypes = ItzUtil::array_column($borrowInfo, "type", "id");
        $borrowInfoNames = ItzUtil::array_column($borrowInfo, "name", "id");
        foreach ($sxPlanInfo['data'] as $key => $val) {
                $ret['list'][] = array(
                    "tender_id" => $val['id'],//发起转让投资记录ID
                    "wait_capital" => bcsub($val['wait_account'], $val['wait_interest'], 2),//待还本金
                    "bond_no" => implode('-',[date('Ymd', $val['addtime']), $borrowInfoTypes[$val['borrow_id']], $val['borrow_id'], $val['id']]),//合同编号
                    "projectName" => $borrowInfoNames[$val['borrow_id']],//项目名称
                    "debt_status" => $val['debt_status'],//债权状态 0无债转 1转让中 14部分转让成功 15已转让
                    "borrow_id" => $val['borrow_id'],
                );
        }
        //itz_ag_debt_exchange中的tender_id集合
        $tenderIds = ArrayUtil::array_column($sxPlanInfo['data'], "id");
        $tenderIds = implode(",",$tenderIds);
        $exchangeInfo = $yiiModel->createCommand("select tender_id from itz_ag_debt_exchange where tender_id in($tenderIds) and user_id = {$platformUserId} AND status IN(1,2)")->queryColumn();
        if(!empty($exchangeInfo)){
            foreach ($ret['list'] as $key => $value) {
                if (in_array($value['debt_status'] , array(0 , 14)) && in_array($value['tender_id'] , $exchangeInfo)) {
                    $ret['list'][$key]['debt_status'] = 1;
                }
            }
        }
        $ret["discount"] = $purchaseOrderInfo['discount'];//求购计划折扣
        $ret["sum_wait_capital"] = array_sum(ArrayUtil::array_column($ret['list'], "wait_capital"));//待还本金总和
        $ret["expected"] = bcmul(bcdiv($purchaseOrderInfo['discount'], 10, 10), $ret["sum_wait_capital"], 2);//预计回本
        $return_result['code'] = 0;
        $return_result['data'] = $ret;
        return $return_result;

    }
    /**
     * AG求购计划求购批量调用
     * @param  array data[//包含以下数据
     * @param tenderArr {“发起转让投资记录ID”:“认购金额”,"项目类型":202}多个例子：
     * [{"tender_id":325,"money":500,"type":202},{"tender_id":326,"money":100,"type":200}]
     * @param pur_id //求购单id
     * @param platformUserId //卖家平台用户id
     * @param platformId //平台id
     * @param user_id //C1卖家用户id
     * ]
     * @return array
     */
    public function  transferTransferBuy($data)
    {
        $tenderArr = $data['tenderArr'];//发起转让投资记录ID
        $tenderInfo = json_decode($tenderArr, true);//传参解析
        $platformId = $data['platformId'];
        $purchaseOrderId = $data['pur_id'];
        $platformUserId = $data['platformUserId'];
        $user_id = $data['user_id'];
        //校验绑定用户
        if(empty($platformUserId)){
            $return_result['code'] = 2093;
            return $return_result;
        }
        //校验登录
        if(empty($user_id)){
            $return_result['code'] = 4007;
            return $return_result;
        }
        if (empty($tenderArr) || empty($platformId) || empty($platformUserId) || empty($purchaseOrderId)) {
            Yii::log("createDebt  step01 params error:" . json_encode($data), 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }
        $model = Yii::app()->yiidb;
        //求购订单表查询
        $row = $model->createCommand("SELECT id,user_id,buyer_uid,discount,project_ids FROM itz_ag_purchase_order WHERE id ={$purchaseOrderId}")->queryRow();
        if(empty($row)){
            $return_result['code'] = 2065;
            return $return_result;
        }
        //循环调用
        foreach ($tenderInfo as $key => $value) {
            $c_data = array(
                "platformUserId" => $platformUserId,//卖家用户ID
                "user_id" => $user_id,//卖家用户ID
                "buy_userid" => $row['buyer_uid'],//债转交易用户ID
                "money" => $value['money'],//发起债转金额
                "debt_src" => 3,//债权来源0:ITZ自有平台债转 1:AG商城换物转让 2:AG债转市场自主转让 3:AG求购计划出售
                "purchase_order_id" => $purchaseOrderId,//债权来源为3的时候添加purchase_order_id求购计划id
                "effect_days" => 10,//有效天数
                "tender_id" => $value['tender_id'],//发起债转投资记录ID
                "platformId" => $platformId,//权益归属于哪个平台
                "utype" => 3,//1:资方用户认购 2：C1用户认购 3：求购计划出售
            );
            //求购计划批量进行债权发布和认购
            $resultArr = $this->debtAgTransaction($c_data);
            if ($resultArr['code'] == 0) {
                $ret['success'][] = array(
                    "code" => 0,
                    "tender_id" => $value['tender_id'],
                    "projectName" => $resultArr['data']['projectName'],
                    "return_capital" => $value['money'],
                );
            } else {
                $ret['fail'][] = array(
                    "code" => $resultArr['code'],
                    "tender_id" => $value['tender_id'],
                    "projectName" => isset($resultArr['data']['projectName']) ? $resultArr['data']['projectName'] : '',
                    "return_capital" => 0,
                    "reason" => Yii::app()->c->data['errorcodeinfo'][$resultArr['code']],
                    "info" => isset($resultArr['info']) ? $resultArr['info'] : '',
                );
            }
        }
        return $ret;
    }

    /**
     * 求购承担单笔
     * @param array data [
     * user_id 发起方用户ID
     * buy_userid 求购方用户ID
     * money 发起债转金额
     * debt_src 债权来源：0:ITZ自有平台债转 1:AG商城换物转让 2:AG债转市场自主转让 3:AG求购计划出售
     * discount 折扣金额debt_src=1(discount=0);debt_src=2,3(discount:0.01~10)
     * effect_days 有效天数 10,20,30
     * tender_id 发起债转投资记录ID
     * purchase_order_id 求购计划id
     * type 项目类型
     * platform_id 平台id
     * utype 1:资方用户认购 2：C1用户认购 3:AG求购计划出售
     * ]
     * @return array
     */
    public function debtAgTransaction($c_data)
    {
        Yii::app()->yiidb->beginTransaction();
        try {
            //创建债权
            $create_ret = $this->createDebtExchange($c_data);
            if ($create_ret['code'] != 0) {
                Yii::log("handelDebt createDebtExchange tender_id {$c_data['tender_id']} false:" . print_r($create_ret, true), 'error');
                Yii::app()->yiidb->rollback();
                return array("code" => $create_ret['code']);
            }
            //创建成功日志
            Yii::log("handelDebtexchange createDebtExchange tender_id {$c_data['tender_id']} success");
            //债权认购
            $debt_data = array();
            $debt_data['user_id'] = $c_data['buy_userid'];//求购方用户id(平台)
            $debt_data['money'] = $c_data['money'];//债权兑换金额
            $debt_data['debt_id'] = $create_ret['data']['debt_id'];//新生成的债权id
            $debt_data['debt_src'] = $c_data['debt_src']; //债权来源：0:ITZ自有平台债转 1:AG商城换物转让 2:AG债转市场自主转让 3:AG求购计划出售
            $debt_data['platform_id'] = $c_data['platformId']; //平台id
            $debt_data['tender_id'] = $c_data['tender_id']; //发起债转投资记录ID
            $debt_data['purchase_order_id'] = $c_data['purchase_order_id']; //求购计划id
            $debt_data['utype'] = $c_data['utype']; //1:资方用户认购 2：C1用户认购 3:AG求购计划出售
            $debt_transaction_ret = $this->debtPreTransaction($debt_data);
            if ($debt_transaction_ret['code'] != 0) {
                Yii::log("handelDebtexchange debtPreTransaction tender_id {$c_data['tender_id']} false:" . print_r($debt_transaction_ret, true), 'error');
                Yii::app()->yiidb->rollback();
                return array("code" => $debt_transaction_ret['code']);
            }
            //债转成功数据确认
            Yii::app()->yiidb->commit();
            Yii::log("handelDebtexchange tender_id:{$c_data['tender_id']} success");
            return array("code" => 0, "data" => $debt_transaction_ret['data'], "info" => "认购中");
        } catch (Exception $e) {
            Yii::log("handelDebtexchange tender_id:{$c_data['tender_id']}; exception:" . print_r($e->getMessage(), true), "error");
            Yii::app()->yiidb->rollback();
            return array("code" => 2089,"data" => [], "info" => $e->getMessage());
        }
    }

    /**
     * 交易密码验证
     * @param $user_id //C1用户id
     * @param $password
     * @return mixed
     */
    public function checkAgPassWord($user_id, $password)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        if (empty($password)) {
            $return_result['code'] = 2075;
            return $return_result;
        }
        $agUserInfo = AgUser::model()->findByPk($user_id);
        if (empty($agUserInfo)) {
            //用户不存在
            $return_result['code'] = 1027;
            return $return_result;
        }
        //未设置交易密码
        if (empty($agUserInfo->pay_password)) {
            $return_result['code'] = 1021;
            return $return_result;
        }
        //交易密码校验
        if ($agUserInfo->pay_password != md5(md5($password) . $agUserInfo->pay_salt)) {
            $return_result['code'] = 2076;
            return $return_result;
        }
        return $return_result;
    }

    /**
     * 发起债转(添加中间表itz_ag_debt_exchange)
     * @param array $data [
     * money 发起债转金额
     * debt_src 债权来源：0:直投与ITZ自有平台债转 1:AG认购商城换物债权 2:AG自主认购债转市场债权 3:AG求购计划收购债权
     * discount 折扣金额debt_src=1(discount=0);debt_src=2(discount:0.01~10);debt_src=3非必传
     * effect_days 有效天数 10,20,30
     * purchase_order_id 求购计划ID 非必填
     * tender_id 发起债转投资记录ID
     * user_id C1卖方用户id
     * platformId 网贷平台id
     * platformUserId 用户id
     * @return array
     */
    public function createDebtExchange($data)
    {
        $yiiModel = Yii::app()->yiidb;
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        Yii::log("createDebtExchange  params :" . json_encode($data), 'info');
        //用户登录校验
        if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
            $return_result['code'] = 1001;
            return $return_result;
        }

        //用户
        if (empty($data['platformUserId']) || !is_numeric($data['platformUserId'])) {
            $return_result['code'] = 2058;
            return $return_result;
        }
        //参数简单校验
        $user_id = $data['user_id'];//C1投资用户id
        $platformUserId = $data['platformUserId'];//用户id
        $money = $data['money']; //发起债转金额
        $debt_src = $data['debt_src'];//债权来源：0直投与ITZ自有平台债转 1AG认购商城换物债权 2AG自主认购债转市场债权 3AG求购计划收购债权
        $time = time();
        $discount = $data['discount']; //折扣
        $effect_days = $data['effect_days'];
        $tender_id = $data['tender_id'];
        $platformId = $data['platformId'];
        $purchaseOrderId = $data['purchase_order_id'];
        if (empty($effect_days) || empty($platformId) || empty($tender_id) || empty($money) || !is_numeric($money) || !in_array($debt_src, [0, 1, 2, 3])) {
            $return_result['code'] = 2001;
            return $return_result;
        }
        //所传的金额必须是两位小数
        if (!ItzUtil::checkMoney($money)) {
            $return_result['code'] = 2092;
            return $return_result;
        }
        //大标的省心计划
        $sxData = array("isAll" => true, "user_id" => $platformUserId, "condition" => "AND id = {$tender_id}");
        $borrow_tender = $this->purchaseSxPlan($sxData);
        //验证是否有可转让订单
        if ($borrow_tender['code'] != 0) {
            $return_result['code'] = $borrow_tender['code'];
            return $return_result;
        }
        $borrow_tender = $borrow_tender['data'][0];
        //用户信息校验
        if($platformUserId != $borrow_tender['user_id']){
            Yii::log("handelDebt end tender_id:$tender_id; user_id[$user_id] != tender_user[$borrow_tender->user_id] ", 'error');
            $return_result['code'] = 2041;
            return $return_result;
        }
        //求购计划来源债权，需校验求购计划信息
        if ($debt_src == 3) {
            $check_pur_ret = $this->checkPurchaseOrder($data['purchase_order_id'], $borrow_tender, $money);
            if ($check_pur_ret['code'] != 0) {
                $return_result['code'] = $check_pur_ret['code'];
                return $return_result;
            }
            //求购详情
            $order_info = $check_pur_ret['data'];
            $discount = $order_info['discount'];
        }
        //兑换金额必须大于0
        if (FunctionUtil::float_bigger_equal(0, $money, 2)) {
            $return_result['code'] = 2004;
            return $return_result;
        }
        //投资记录待还本金
        $wait_capital = bcsub($borrow_tender['wait_account'], $borrow_tender['wait_interest'], 2);
        //待还本金必须大于0
        if (FunctionUtil::float_bigger_equal(0, $wait_capital, 2)) {
            $return_result['code'] = 2005;
            return $return_result;
        }
        //兑换后剩余本金
        $s_money = bcsub($wait_capital, $money, 2);
        //剩余本金必须大于或等于O
        if (FunctionUtil::float_bigger(0, $s_money, 2)) {
            $return_result['code'] = 2008;
            return $return_result;
        }
        //当剩余金额大于0时
        if(FunctionUtil::float_bigger($s_money, 0, 2)){
            //剩余金额必须大于起投金额
            if(FunctionUtil::float_bigger(self::MIN_LOAN_AMOUNT, $s_money, 2)){
                Yii::log("handelDebt end tender_id:$tender_id, s_money:$s_money < ".self::MIN_LOAN_AMOUNT.", debt_account:$money, wait_capital:$wait_capital", 'error');
                $return_result['code'] = 2009;
                return $return_result;
            }
            //非最后一笔债转，交易金额必须大于起投金额 [兼容历史数据在投金额小于起投情况]
            if(FunctionUtil::float_bigger(self::MIN_LOAN_AMOUNT, $money, 2)){
                Yii::log("handelDebt end tender_id:$tender_id, debt_account:$money < ".self::MIN_LOAN_AMOUNT.", wait_capital:$wait_capital", 'error');
                $return_result['code'] = 2044;
                return $return_result;
            }
        }
        //非权益兑换时，折扣金必须区间0.01~10
        if(in_array($debt_src, [0,2,3]) && (FunctionUtil::float_bigger_equal(0, $discount, 2) || FunctionUtil::float_bigger($discount, 10, 2))){
            Yii::log("createDebt is_from_shop=2, discount[$discount] error  ", 'error');
            $return_result['code'] = 2043;
            return $return_result;
        }
        //商城权益兑换，折扣金额必须为0
        if($debt_src == 1 && !FunctionUtil::float_equal($discount, 0, 2)){
            Yii::log("createDebt is_from_shop=1, discount[$discount] error  ", 'error');
            $return_result['code'] = 2042;
            return $return_result;
        }
        //平台信息校验
        $check_p = $this->checkPlatform($platformId);
        if ($check_p['code'] != 0) {
            $return_result['code'] = $check_p['code'];
            return $return_result;
        }
        //项目信息校验
        $borrow = $yiiModel->createCommand("SELECT id,type FROM dw_borrow WHERE id = {$borrow_tender['borrow_id']}")->queryRow();
        if (empty($borrow)) {
            $return_result['code'] = 2010;
            return $return_result;
        }
        //校验用户是否存在（平台用户id）
        $user_info = $yiiModel->createCommand("SELECT user_id FROM dw_user WHERE user_id = {$platformUserId}")->queryRow();
        if (empty($user_info)) {
            $return_result['code'] = 2026;
            return $return_result;
        }
        //平台关系表验证
        $userPlatformInfo = PurchService::getInstance()->transformationUserid($user_id, $platformId);
        if ($userPlatformInfo['code'] != 0) {
            $return_result['code'] = $userPlatformInfo['code'];
            return $return_result;
        }
        //校验还款计划待还本金
        $capitalSum = $yiiModel->createCommand("SELECT SUM(capital) FROM dw_borrow_collection WHERE tender_id = {$tender_id} AND user_id = {$platformUserId} AND borrow_id = {$borrow['id']} AND status IN(0,16) ")->queryScalar();
        if (empty($capitalSum) || bccomp($capitalSum, $wait_capital, 2) != 0) {
            $return_result['code'] = 2083;
            return $return_result;
        }
        //临时表中是否有重复转让
        $sumNum = $yiiModel->createCommand("SELECT count(*) FROM itz_ag_debt_exchange WHERE tender_id = {$tender_id} AND user_id = {$platformUserId} AND status = 1")->queryScalar();
        $sumNum = !empty($sumNum) ? $sumNum : 0;
        if ($sumNum > 0) {
            $return_result['code'] = 2011;
            return $return_result;
        }
        $purchase_order_id = !empty($purchaseOrderId) ? $purchaseOrderId : 0;
        //itz_ag_debt_exchange表数据组成
        $debt['user_id'] = $platformUserId;
        $debt['borrow_id'] = $borrow_tender['borrow_id'];//项目id
        $debt['borrow_type'] = $borrow['type'];//项目类型对应dw_borrow表type字段
        $debt['ag_sell_user_id'] = $user_id;//卖方用户ID
        $debt['purchase_order_id'] = $purchase_order_id;
        $debt['platform_id'] = $platformId;
        $debt['tender_id'] = $tender_id;
        $debt['debt_account'] = $money;
        $debt['discount'] = $discount;
        $debt['effect_days'] = $effect_days;
        $debt['create_debt_time'] = $time;
        $debt['debt_serial_number'] = FunctionUtil::getAgRequestNo('DEBT');//债转编号
        $debt['debt_src'] = $debt_src;
        $debt['status'] = 1;
        $debtSql = $this->get_insert_db_sql("itz_ag_debt_exchange", $debt);
        $ret = $yiiModel->createCommand($debtSql)->execute();
        if (!$ret) {//添加失败
            $return_result['code'] = 2012;
            return $return_result;
        }
        $debtExchangeId = $yiiModel->getLastInsertID();
        $return_result['code'] = 0;
        $return_result['data']['debt_id'] = $debtExchangeId;
        return $return_result;
    }

    /**
     * 资方认购债权验证
     * @param array debt_data[
     * money 认购金额
     * user_id 买方用户ID
     * debt_id 被认购债权ID
     * platform_id 平台id
     * purchase_order_id 求购计划id
     * utype 1:资方用户认购 2：C1用户认购
     * debt_src 债权来源：0:ITZ自有平台债转 1:AG商城换物转让 2:AG债转市场自主转让 3:AG求购计划出售
     * tender_id 发起债转投资记录ID
     * ]
     * @return array
     */
    public function debtPreTransactionRule($debt_data)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $yiimodel = Yii::app()->yiidb;
        //用户未登录
        if ('' == $debt_data['user_id']) {
            $return_result['code'] = 2058;
            return $return_result;
        }
        //债权id非空校验
        if (!in_array($debt_data['debt_src'], [0, 1, 2, 3,4]) || !in_array($debt_data['utype'], [1, 2]) || $this->emp($debt_data['debt_id']) || $this->emp($debt_data['money']) || !is_numeric($debt_data['debt_id'])) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        //所传的金额必须是两位小数
        if (!ItzUtil::checkMoney($debt_data['money'])) {
            $return_result['code'] = 2092;
            return $return_result;
        }
        //交易金额数据校验
        if (!is_numeric($debt_data['money']) || FunctionUtil::float_bigger_equal(0, $debt_data['money'], 2)) {
            $return_result['code'] = 2016;
            return $return_result;
        }

        $debt_id = $debt_data['debt_id'];//临时表生成订单id
        $account_money = $debt_data['money'];//认购金额
        $buyer_uid = $debt_data['user_id'];//债转交易用户ID（买家用户ID）
        $debt_src = $debt_data['debt_src'];//债权来源
        $platform_id = $debt_data['platform_id'];//平台id
        $tender_id = $debt_data['tender_id'];//发起债转投资记录ID
        $utype = $debt_data['utype'];//发起债转投资记录ID
        //验证C1用户是否为在存用户
        if ($utype == 2) {
            $ag_tender = $yiimodel->createCommand("SELECT count(*) FROM dw_borrow_tender WHERE user_id = {$buyer_uid} AND (wait_account - wait_interest) > 0 AND status = 1")->queryScalar();
            if (empty($ag_tender) || $ag_tender == 0) {
                $return_result['code'] = 2090;
                return $return_result;
            }
            //平台用户关系表
            $user_platform = Yii::app()->agdb->createCommand("select user_id from ag_user_platform where platform_user_id = $buyer_uid and platform_id = $platform_id")->queryRow();
            $ag_buy_user_id = !empty($user_platform) ? $user_platform['user_id'] : 0;
            $userRisk = QuestionService::getInstance()->checkUserRisk($ag_buy_user_id, 2);
            //验证C1认购时指定风险等级
            if($userRisk['code'] != 0){
                $return_result['code'] = $userRisk['code'];
                return $return_result;
            }
        }
        // 获取债权信息数组
        $debt = $yiimodel->createCommand("SELECT * FROM itz_ag_debt_exchange WHERE id = {$debt_id}")->queryRow();
        //卖家用户id(平台用户id)
        $user_id = $debt['user_id'];
        if (!$debt) {
            $return_result['code'] = 2017;
            return $return_result;
        }
        //不能认购自己发布的债权
        if ($buyer_uid == $user_id) {
            $return_result['code'] = 2227;
            return $return_result;
        }
        //认购金额必须全额认购
        if (bccomp($debt['debt_account'], $account_money, 2) != 0) {
            $return_result['code'] = 2088;
            return $return_result;
        }
        //债权已取消
        if (5 == $debt['status']) {
            $return_result['code'] = 2021;
            return $return_result;
        }
        //债权已过期
        if (6 == $debt['status'] || $debt['create_debt_time'] + $debt['effect_days'] * 86400 < time()) {
            $return_result['code'] = 2022;
            return $return_result;
        }
        //已经认购完成
        if (3 == $debt['status']) {
            $return_result['code'] = 2024;
            return $return_result;
        }
        //状态异常
        if ($debt['status'] != 1) {
            $return_result['code'] = 2025;
            return $return_result;
        }
        //校验用户是否存在
        $user_info = $yiimodel->createCommand("SELECT user_id FROM dw_user WHERE user_id = {$buyer_uid}")->queryRow();
        if (empty($user_info)) {
            $return_result['code'] = 2026;
            return $return_result;
        }
        //项目信息校验
        $borrow = $yiimodel->createCommand("SELECT id,type,name FROM dw_borrow WHERE id = {$debt['borrow_id']}")->queryRow();
        if (empty($borrow)) {
            $return_result['code'] = 2028;
            return $return_result;
        }
        //大标的省心计划转让中
        $sxData = array("isAll" => true, "condition" => "AND id = {$tender_id}");
        if ($utype == 2) {
            $sxData = array_merge($sxData, ["user_id" => $user_id]);
        };
        $tender_info = $this->purchaseSxPlan($sxData);
        //验证卖家订单
        if ($tender_info['code'] != 0) {
            $return_result['code'] = $tender_info['code'];
            return $return_result;
        }
        //平台信息校验
        $check_p = $this->checkPlatform($platform_id);
        if ($check_p['code'] != 0) {
            $return_result['code'] = $check_p['code'];
            return $return_result;
        }
        //校验买方账户余额
        $buyer_account_info = $yiimodel->createCommand("select * from dw_account where user_id = {$buyer_uid}")->queryRow();
        if (!$buyer_account_info) {
            $return_result['code'] = 2049;
            return $return_result;
        }
        //实际支付金额 实际支付金额为0.01，非权益兑换时，支付金额根据折扣金计算
        $investMoney = round($debt['discount'] * $account_money * 0.1, 2);
        //ITZ自有平台债转/商城权益兑换订单/自主认购订单:校验账户余额必须大于等于实际支付金额
        if (in_array($debt_src, [0, 1, 2]) && FunctionUtil::float_bigger($investMoney, $buyer_account_info['use_money'], 3)) {
            $return_result['code'] = 2051;
            return $return_result;
        }
        //校验卖方账户信息
        $seller_account_info = $yiimodel->createCommand("SELECT * FROM dw_account WHERE user_id = {$debt['user_id']}")->queryRow();
        if (!$seller_account_info) {
            $return_result['code'] = 2053;
            return $return_result;
        }
        $check_pur_ret = '';
        //求购计划来源订单
        if ($debt_src == 3) {
            //账户冻结金额大于等于实际支付金额
            if (FunctionUtil::float_bigger($investMoney, $buyer_account_info['ag_no_use_money'], 3)) {
                $return_result['code'] = 2050;
                return $return_result;
            }
            //求购计划来源债权，需校验求购计划信息
            $check_pur_ret = $this->checkPurchaseOrder($debt['purchase_order_id'], $tender_info['data'][0], $account_money);
            if ($check_pur_ret['code'] != 0) {
                $return_result['code'] = $check_pur_ret['code'];
                return $return_result;
            }
            //求购信息用户信息校验
            if ($buyer_uid != $check_pur_ret['data']['buyer_uid']) {
                $return_result['code'] = 2072;
                return $return_result;
            }
        }
        $return_result['code'] = 0;
        $return_result['data']['info'] = "验证成功";
        $return_result['data']['borrow'] = $borrow;
        $return_result['data']['purchase_info'] = $check_pur_ret['data'];
        $return_result['data']['projectName'] = $borrow['name'];
        return $return_result;
    }

    /**
     * 认购债权处理
     * @param array $debt_data [
     * money 认购金额
     * user_id 买方用户ID
     * login_user_id 资方后台登录用户id
     * debt_id 被认购债权ID
     * platform_id 平台id
     * debt_src 债权来源：0:ITZ自有平台债转 1:AG商城换物转让 2:AG债转市场自主转让 3:AG求购计划出售
     * tender_id 发起债转投资记录ID
     * purchase_order_id 求购计划id
     * login_userid $utype = 1时资方后台用户id $utype = 2时C1买家用户id
     * utype 1:资方用户认购 2：C1用户认购 3:AG求购计划出售
     * ]
     * @return array
     */
    public function debtPreTransaction($debt_data)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $login_userid = isset($debt_data['login_userid']) ? $debt_data['login_userid'] : 0;
        //资方认购债权验证
        $ruleInfo = $this->debtPreTransactionRule($debt_data);
        if ($ruleInfo['code'] != 0) {
            $return_result['code'] = $ruleInfo['code'];
            return $return_result;
        }
        $yiimodel = Yii::app()->yiidb;
        $debt_id = $debt_data['debt_id'];//临时表生成订单id
        $buyer_uid = $debt_data['user_id'];//债转交易用户ID（买家资方用户ID）
        $platform_id = $debt_data['platform_id'];
        //C1用户认购
        if($debt_data['utype'] == 2){
            //平台用户关系表
            $user_platform = Yii::app()->agdb->createCommand("select user_id from ag_user_platform where platform_user_id = $buyer_uid and platform_id = $platform_id")->queryRow();
            $ag_buy_user_id = !empty($user_platform) ? $user_platform['user_id'] : 0;

        }elseif($debt_data['utype'] == 1){
            //资方后台登录认购
            if(!empty($login_userid)){
                //资方后台用户id ag_admin_user表进行绑定
                $ag_admin_user = Yii::app()->agdb->createCommand("select user_id from ag_admin_user where id = {$debt_data['login_userid']} and is_deleted = 0")->queryRow();
                $ag_buy_user_id = !empty($ag_admin_user['user_id']) ? $ag_admin_user['user_id'] : 0;
            }
            //求购计划认购
            if($debt_data['debt_src'] == 3){
                $purchase_user = Yii::app()->yiidb->createCommand("select user_id from itz_ag_purchase_order where id = {$debt_data['purchase_order_id']} and money - acquired_money > 0 and expiry_time >= UNIX_TIMESTAMP() and status = 1 ")->queryRow();
                $ag_buy_user_id = $purchase_user['user_id'];
            }
        }
        //更新临时表itz_ag_debt_exchange
        $updateArr = array('status' => 2, 'buyer_uid' => $buyer_uid, 'ag_buy_user_id' => $ag_buy_user_id, 'tender_serial_number' => FunctionUtil::getAgRequestNo('IBUY'));
        if($debt_data['utype'] == 2){
            //C1进行认购-AG债转市场自主转让
            $updateArr = array_merge($updateArr,["debt_src" => 2]);
        }elseif($debt_data['utype'] == 1){
            //资方批量认购
            $updateArr = array_merge($updateArr,["debt_src" => 4]);
        }elseif($debt_data['utype'] == 3){
            $updateArr = array_merge($updateArr,["debt_src" => 3]);
        }
        $exchangeUpSql = $this->get_update_db_sql("itz_ag_debt_exchange", $updateArr, "id = {$debt_id}");
        $saveret = $yiimodel->createCommand($exchangeUpSql)->execute();
        if (!$saveret) {
            $return_result['code'] = 2013;
            return $return_result;
        }
        $return_result['code'] = 0;
        $return_result['data'] = array("projectName" => $ruleInfo['data']['projectName'], "debt_id" => $debt_id);
        return $return_result;
    }
    /**
     * 债权取消or过期
     * @param $debt_id 临时表itz_ag_debt_exchange id
     * @param $status 3取消4过期
     * @param string $user_id 用户id
     * @return array
     */
    public function CancelDebt($debt_id, $status, $user_id = '')
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        if (empty($debt_id) || !in_array($status, [3, 4])) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        //取消时，用户ID必传
        if ($status == 3 && empty($user_id)) {
            $return_result['code'] = 2057;
            return $return_result;
        }
        $yiimodel = Yii::app()->yiidb;
        $debt_id = FunctionUtil::verify_id($debt_id);
        if (!empty($user_id)) {
            $user_info = $yiimodel->createCommand("SELECT user_id FROM dw_user WHERE user_id = {$user_id}")->queryRow();
            if (empty($user_info)) {
                $return_result['code'] = 2026;
                return $return_result;
            }
        }

        $debt = $yiimodel->createCommand("select * from itz_ag_debt_exchange where id = $debt_id")->queryRow();
        if (empty($debt)) {
            $return_result['code'] = 2059;
            return $return_result;
        }

        //只能取消自己债权
        if ($status == 3 && $debt['user_id'] != $user_id) {
            $return_result['code'] = 2060;
            return $return_result;
        }

        //非可取消状态
        if (1 != $debt['status']) {
            $return_result['code'] = 2061;
            return $return_result;
        }
        //参数转换
        $sArr = array(
            '3' => 5,//取消
            '4' => 6,//过期
        );
        //变更itz_ag_debt_exchange
        $new_debt['status'] = $sArr[$status];
        $exchangeUpSql = $this->get_update_db_sql("itz_ag_debt_exchange", $new_debt, "id = {$debt_id}");
        $new_debt_ret = $yiimodel->createCommand($exchangeUpSql)->execute();
        if (!$new_debt_ret) {
            $return_result['code'] = 2062;
            return $return_result;
        }
        //取消成功
        return $return_result;
    }

    /**
     * 平台校验
     * @param $platform_id
     * @return array
     */
    public function checkPlatform($platform_id)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        Yii::log("checkPlatform start: platform_id:$platform_id;");
        if (empty($platform_id) || !is_numeric($platform_id)) {
            $return_result['code'] = 2046;
            return $return_result;
        }
        //平台信息
        $plat_info = AgPlatform::model()->findByPk($platform_id);
        if (!$plat_info) {
            $return_result['code'] = 2045;
            return $return_result;
        }
        //平台必须审核通过
        if ($plat_info->status != 1) {
            $return_result['code'] = 2047;
            return $return_result;
        }
        $return_result['data'] = $plat_info;
        return $return_result;
    }

    /**
     * 判断参数是不是空
     * @param $a
     * @return bool true为空，false为非空
     */
    private function emp($a)
    {
        if (!isset($a) || (empty($a) && $a != 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 求购计划校验
     * @param purchase_order_id
     * @param money
     * @param tender_info
     * @param bool $is_lock
     * @return array
     */
    public function checkPurchaseOrder($purchase_order_id, $tender_info, $money, $is_lock = false)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );

        //基本参数校验
        if(empty($tender_info) || empty($purchase_order_id) || !is_numeric($purchase_order_id) || empty($money) ) {
            Yii::log("checkPurchaseOrder purchase_order_id[{$purchase_order_id}] error ", 'error');
            $return_result['code'] = 2064;
            return $return_result;
        }
        //基本参数校验
        if (empty($purchase_order_id) || !is_numeric($purchase_order_id) || empty($money)) {
            $return_result['code'] = 2064;
            return $return_result;
        }
        $yiimodel = Yii::app()->yiidb;
        //求购计划信息
        $condition = $is_lock ? " for update " : "";
        $purchase_order = $yiimodel->createCommand("SELECT * FROM itz_ag_purchase_order WHERE id = {$purchase_order_id} $condition")->queryRow();
        if (empty($purchase_order)) {
            $return_result['code'] = 2065;
            return $return_result;
        }
        //求购计划状态非求购中
        if ($purchase_order['status'] != 1 || $purchase_order['successtime'] != 0 || $purchase_order['expiry_time'] < time()) {
            $return_result['code'] = 2066;
            return $return_result;
        }
        //剩余求购金额
        $debt_sum_account = $yiimodel->createCommand("select sum(debt_account)  from itz_ag_debt_exchange where purchase_order_id = {$purchase_order_id} and status = 2")->queryScalar();
        $debtaccount = !empty($debt_sum_account) ? $debt_sum_account : 0;
        $surplus_pamount = bcsub(bcsub($purchase_order['money'], $purchase_order['acquired_money'], 2),$debtaccount,2);
        if (FunctionUtil::float_bigger($money, $surplus_pamount, 2)) {
            $return_result['code'] = 2067;
            return $return_result;
        }
        //项目类型校验
        if(!empty($purchase_order['project_types'])){
            //项目信息获取
            $project_info = $yiimodel->createCommand("select * from dw_borrow where id = {$tender_info['borrow_id']}")->queryRow();
            if(empty($project_info)){
                Yii::log("checkPurchaseOrder end purchase_order_id:$purchase_order_id, dw_borrow.id:{$tender_info['borrow_id']} not exist ", 'error');
                $return_result['code'] = 2010;
                return $return_result;
            }
            //项目类型
            if(!in_array($project_info['type'], explode(',', $purchase_order['project_types']))){
                Yii::log("checkPurchaseOrder end purchase_order_id:$purchase_order_id, type_id:{$project_info['type']} not in {$purchase_order['project_types']} ", 'error');
                $return_result['code'] = 2071;
                return $return_result;
            }
            //项目ID校验
            if(!empty($purchase_order['project_ids']) && !in_array($tender_info['borrow_id'], explode(',', $purchase_order['project_ids']))){
                Yii::log("checkPurchaseOrder end purchase_order_id:$purchase_order_id, tender_info.project_id:{$tender_info['borrow_id']} not in {$purchase_order['project_ids']} ", 'error');
                $return_result['code'] = 2070;
                return $return_result;
            }
        }
        $return_result['code'] = 0;
        $return_result['data'] = $purchase_order;
        return $return_result;
    }



    /**
     * 资方对C1用户求购承担批量调用校验接口
     * @param $debtArr {“转让记录ID”:“认购金额” , "项目类型":202} 例：[{"debt_id": 85,"money": 200}]
     * @param $buy_userid //买家用户id
     * @return array
     */
    public function  AmcTransferBuyRule($debtArr, $buy_userid)
    {
        $yiimodel = Yii::app()->yiidb;
        $tenderInfo = json_decode($debtArr, true);//传参解析
        $exchangeIds = implode(",", ArrayUtil::array_column($tenderInfo, "debt_id"));
        if (empty($exchangeIds)) return array("code" => 4002);//暂无数据
        $tenderInfo = ItzUtil::array_column($tenderInfo, "money", "debt_id");
        $ret = array();//返回结果
        //查询投资记录匹配项目id
        $result = $yiimodel->createCommand("SELECT * FROM itz_ag_debt_exchange WHERE id IN ($exchangeIds) AND status = 1")->queryAll();
        if (empty($result)) return array("code" => 4002);//暂无数据
        foreach ($result as $key => $value) {
            $debt_data = array();
            $debt_data['user_id'] = $buy_userid;//求购方用户id
            $debt_data['money'] = $tenderInfo[$value['id']];//债权兑换金额
            $debt_data['debt_id'] = $value['id'];//新生成的债权id
            $debt_data['debt_src'] = $value['debt_src']; //债权来源：0:ITZ自有平台债转 1:AG商城换物转让 2:AG债转市场自主转让 3:AG求购计划出售
            $debt_data['platform_id'] = $value['platform_id']; //平台id
            $debt_data['tender_id'] = $value['tender_id']; //发起债转投资记录ID
            $debt_data['type'] = $value['borrow_type'];
            $resultArr = $this->debtPreTransactionRule($debt_data);
            if ($resultArr['code'] == 0) {
                $ret['success'][] = array(
                    "debt_id" => $value['id'],
                    "projectName" => $resultArr['data']['projectName'],
                    "return_capital" => $value['debt_account'],//认购金额
                );
            } else {
                $ret['fail'][] = array(
                    "tender_id" => $value['tender_id'],
                    "projectName" => $resultArr['data']['projectName'],
                    "return_capital" => 0,
                    "reason" => Yii::app()->c->data['errorcodeinfo'][$resultArr['code']],
                );
            }
        }
        return $ret;
    }

    /**
     * 资方对C1用户求购承担批量调用
     * @param $debtArr {“转让记录ID”:“认购金额” , "项目类型":202} 例：[{"debt_id": 85,"money": 200}]
     * @param $buy_userid 买家用户id
     * @param $vType 1:认购，2:验证
     * @param $utype 1:资方用户认购 2：C1用户认购
     * @param $login_userid $utype = 1时资方后台用户id $utype = 2时C1买家用户id
     * @return array
     */
    public function  transferAmcTransferBuy($debtArr, $buy_userid, $utype, $login_userid, $vType = 1)
    {
        $yiimodel = Yii::app()->yiidb;
        $debtInfo = json_decode($debtArr, true);//传参解析
        $exchangeIds = implode(",", ArrayUtil::array_column($debtInfo, "debt_id"));
        if (empty($exchangeIds)) return array("code" => 4002);//暂无数据
        $debtInfo = ItzUtil::array_column($debtInfo, "money", "debt_id");
        $ret = array();//返回结果
        //查询投资记录匹配项目id
        $result = $yiimodel->createCommand("SELECT * FROM itz_ag_debt_exchange WHERE id IN ($exchangeIds) AND status = 1")->queryAll();
        if (empty($result)) return array("code" => 2097);//暂无数据
        foreach ($result as $key => $value) {
            $debt_data = array();
            $debt_data['user_id'] = $buy_userid;//求购方用户id
            $debt_data['money'] = $debtInfo[$value['id']];//债权兑换金额
            $debt_data['debt_id'] = $value['id'];//新生成的债权id
            $debt_data['debt_src'] = 4; //债权来源：0:ITZ自有平台债转 1:AG商城换物转让 2:AG债转市场自主转让 3:AG求购计划出售 4:资方批量认购
            $debt_data['platform_id'] = $value['platform_id']; //平台id
            $debt_data['tender_id'] = $value['tender_id']; //发起债转投资记录ID
            $debt_data['type'] = $value['borrow_type'];
            $debt_data['login_userid'] = $login_userid;
            $debt_data['utype'] = $utype;
            //直接认购
            $resultArr = $this->debtPreAwcTransaction($debt_data, $vType);
            //资方用户认购返回格式
            if ($resultArr['code'] == 0) {
                $ret['success'][] = array(
                    "debt_id" => $value['tender_id'],
                    "code" => 0,
                    "projectName" => $resultArr['data']['projectName'],
                    "return_capital" => $value['debt_account'],//认购金额
                );
            } else {
                $ret['fail'][] = array(
                    "debt_id" => $value['tender_id'],
                    "code" => $resultArr['code'],
                    "projectName" => !empty($resultArr['data']['projectName']) ? $resultArr['data']['projectName'] : '',
                    "return_capital" => 0,
                    "reason" => Yii::app()->c->data['errorcodeinfo'][$resultArr['code']],
                    "info" => !empty($resultArr['info']) ? $resultArr['info'] : '',
                );
            }
        }
        return $ret;
    }

    /**
     * 债权认购（单笔）
     * @param array $debt_data [
     * money 认购金额
     * user_id 买方用户ID
     * debt_id 被认购债权ID
     * utype 1:资方用户认购 2：C1用户认购
     * login_userid $utype = 1时资方后台用户id $utype = 2时C1买家用户id
     * 债权来源：0:ITZ自有平台债转 1:AG商城换物转让 2:AG债转市场自主转让 3:AG求购计划出售
     * ]
     * @param $vtype 1:认购，2:验证
     * @return array
     */
    public function debtPreAwcTransaction($debt_data, $vtype = 1)
    {
        if ($vtype == 1) {
            try {
                Yii::app()->yiidb->beginTransaction();
                //债权认购
                $debt_transaction_ret = $this->debtPreTransaction($debt_data);
                if ($debt_transaction_ret['code'] != 0) {
                    Yii::log("handelDebt debtPreTransaction debt_exchange_id {$debt_data['debt_id']} false:" . print_r($debt_transaction_ret, true), 'error');
                    return array("code" => $debt_transaction_ret['code'], "data" => $debt_transaction_ret['data']);
                }
                //债转成功数据确认
                Yii::app()->yiidb->commit();
                Yii::log("handelDebt tender_id:{$debt_data['tender_id']} success");
                $return_result['code'] = 0;
                $return_result['data'] = $debt_transaction_ret['data'];
                return $return_result;
            } catch (Exception $e) {
                Yii::log("handelDebt debtPreTransaction debt_exchange_id {$debt_data['debt_id']} false:" . $e->getMessage(), 'error');
                Yii::app()->yiidb->rollback();
                $return_result['code'] = 2089;
                $return_result['data'] = $debt_transaction_ret['data'];
                $return_result['info'] = $e->getMessage();
                return $return_result;
            }
        } elseif ($vtype == 2) {
            //债权认购债权验证
            $debt_transaction_ret = $this->debtPreTransactionRule($debt_data);
            if ($debt_transaction_ret['code'] != 0) {
                Yii::log("handelDebt debtPreTransaction debt_exchange_id {$debt_data['debt_id']} false:" . print_r($debt_transaction_ret, true), 'error');
                return array("code" => $debt_transaction_ret['code'], "data" => $debt_transaction_ret['data']);
            }
            $return_result['code'] = 0;
            $return_result['data'] = $debt_transaction_ret['data'];
            return $return_result;
        }
    }

    /**
     * 我的认购列表
     * @param $data [
     * @param page //当前页数(默认为1,正整数)
     * @param limit //每页显示数据量(默认为10,取值范围1至100的正整数)
     * @param order //排序方式(默认为1)：1-认购时间降序，2-认购时间升序
     * @param user_id //卖方用户id
     * @param type //1:认购中2：认购成功3：认购失败
     * @param platform_id //平台id
     * ]
     * @return array
     */
    public function subscriPtion($data)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $debtexchangeInfo = array();
        $page = $data['page'];
        $order = $data['order'];
        $limit = $data['limit'];
        $user_id = $data['user_id'];
        $type = $data['type'];
        if(empty($user_id)){
            $return_result['code'] = 2093;
            return $return_result;
        }
        if ($type == 1) {
            //认购中
            $sqlAdd = " and ade.status IN(1,2)";
        } elseif ($type == 2) {
            //认购完成
            $sqlAdd = " and ade.status = 3";
        } elseif ($type == 3) {
            //认购失败
            $sqlAdd = " and ade.status IN(4,5,6)";
        }
        if (!empty($order) && $order == 1) {
            $order = "order by ade.id desc";
        }
        if (!empty($order) && $order == 2) {
            $order = "order by ade.id asc";
        }
        if (!empty($limit)) {
            $pass = ($page - 1) * $data['limit'];  //跳过数据
            $data_limit = "LIMIT {$pass},{$data['limit']}";
        }
        $model = Yii::app()->yiidb;
        $debCount = $model->createCommand("select count(*) from (itz_ag_debt_exchange ade inner join dw_borrow db on ade.borrow_id = db.id) inner join dw_borrow_tender bt on ade.tender_id = bt.id where ade.buyer_uid = $user_id  {$sqlAdd}")->queryScalar();
        if (empty($debCount) || $debCount == 0) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $typeName = $itouzi = Yii::app()->c->itouzi;
        $sql = "select ade.create_debt_time,ade.effect_days,ade.debt_serial_number,db.apr as apr,db.type as type,db.name as projectName,ade.discount as discount,ade.debt_account as amount,ade.successtime as addtime,ade.id as debt_id , bt.addtime as bt_addtime , ade.borrow_id , ade.tender_id from (itz_ag_debt_exchange ade inner join dw_borrow db on ade.borrow_id = db.id) inner join dw_borrow_tender bt on ade.tender_id = bt.id where ade.buyer_uid = $user_id  {$sqlAdd} {$order} {$data_limit}";
        $debtTenderInfo = $model->createCommand($sql)->queryAll();
        foreach ($debtTenderInfo as $key => $val) {
            $debtexchangeInfo[] = array(
                "projectName" => $val['projectName'],//项目名称
                "apr" => $val['apr'],//年利率
                "amount" => $val['amount'],//转让金额
                "bond_no" => implode('-', array(
                        date('Ymd', $val['bt_addtime']),
                        $val['type'],
                        $val['borrow_id'],
                        $val['tender_id']
                    )
                ),
                "discount" => $val['discount'],//折扣
                "debt_serial_number" => $val['debt_serial_number'],//债转编号
                "type_name" => $typeName['itouzi']['type'][$val['type']],//项目类型
                "addtime" => $val['addtime'],//认购时间
                "expired_time" => $val['create_debt_time'] + $val['effect_days'] * 86400,//项目到期时间
                "c_viewpdf_url" => '',//合同预览地址
            );
        }
        $return_result['code'] = 0;
        $return_result['count'] = $debCount;
        $return_result['page_count'] = ceil($debCount / $limit);//总页数
        $return_result['status'] = $type;
        $return_result['data'] = $debtexchangeInfo;
        return $return_result;
    }
    /**
     * 省心计划可债转
     * @param  array data [包含以下参数
     * @param fields //查询字段
     * @param user_id //平台用户ID
     * @param isAll //是否全部查询 全部true
     * @param page //当前页 默认1
     * @param limit //限制条数默认20
     * @param condition //附加条件 例如 "AND status = 1"
     * @param debt_status //1:可转让 2:转让中
     * ]
     * @return array
     */
    public function purchaseSxPlan($data)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        if (!is_array($data)) {
            $return_result['code'] = 2056;
            $return_result['info'] = '参数有误';

            return $return_result;
        }
        $data['page'] = !empty($data['page']) ? $data['page'] : 1;
        $data['limit'] = !empty($data['limit']) ? $data['limit'] : 20;
        $data['isAll'] = !empty($data['isAll']) ? $data['isAll'] : false;
        $data['fields'] = !empty($data['fields']) ? $data['fields'] : "*";
        if (!is_numeric($data['page']) || !is_numeric($data['limit']) || !is_bool($data['isAll'])) {
            $return_result['code'] = 2056;
            $return_result['info'] = '参数有误';

            return $return_result;
        }
        $yiiModel = Yii::app()->yiidb;
        $sqladd = '';
        if (!empty($data['user_id'])) {
            $sqladd = "user_id = {$data['user_id']} AND";
        }
        //运营发券事故禁止兑换投资记录ID
        $tenderIds = Yii::app()->c->itouzi['itouzi']['prohibit_tenderIds'];
        //本金结清，剩余一期利息未还项目禁止兑换项目ID
        $borrowIds = Yii::app()->c->itouzi['itouzi']['prohibit_borrowIds'];
        //禁止债转用户ID
        $userIds = Yii::app()->c->itouzi['itouzi']['nodebtTouser'];
        if (!$data['isAll']) {
            //分页设置
            $pass = ($data['page'] - 1) * $data['limit'];  //跳过数据
            $data_limit = "LIMIT {$pass},{$data['limit']}";
        }
        //省心计划限制条件
        $bTenderSql = "SELECT {$data['fields']} FROM dw_borrow_tender WHERE {$sqladd} status = 1  AND (wait_account - wait_interest) > 0
                       AND deal_status IN (1,2) AND is_debt_confirm = 1  AND user_id NOT IN({$userIds}) AND debt_status IN(0,14) AND borrow_id  NOT IN({$borrowIds}) AND id NOT IN({$tenderIds}) {$data['condition']} {$data_limit}";
        $borrowTenderInfo = $yiiModel->createCommand($bTenderSql)->queryAll();
        if (empty($borrowTenderInfo)) {
            $return_result['code'] = 2091;
            $return_result['info'] = '暂无可转让投资记录';

            return $return_result;
        }
        $return_result['code'] = 0;
        $return_result['data'] = $borrowTenderInfo;

        return $return_result;

    }
    /**
     * 获取插入语句
     * @param $tbl_name
     * @param $info
     * @return bool|string
     */
    public function get_insert_db_sql($tbl_name, $info)
    {
        if (is_array($info) && !empty($info)) {
            $i = 0;
            foreach ($info as $key => $val) {
                $fields[$i] = $key;
                $values[$i] = $val;
                $i++;
            }
            $s_fields = "(" . implode(",", $fields) . ")";
            $s_values = "('" . implode("','", $values) . "')";
            $sql = "INSERT INTO
                       $tbl_name
                       $s_fields
                   VALUES
                       $s_values";
            return $sql;
        } else {
            return false;
        }
    }

    /**
     * 获取更新SQL语句
     * @param $tbl_name
     * @param $info
     * @param $condition
     * @return bool|string
     */
    public function get_update_db_sql($tbl_name, $info, $condition)
    {
        $i = 0;
        $data = '';
        if (is_array($info) && !empty($info)) {
            foreach ($info as $key => $val) {
                if (isset($val)) {
                    $val = $val;
                    if ($i == 0 && $val !== null) {
                        $data = $key . "='" . $val . "'";
                    } else {
                        $data .= "," . $key . " = '" . $val . "'";
                    }
                    $i++;
                }
            }
            $sql = "UPDATE " . $tbl_name . " SET " . $data . " WHERE " . $condition;
            return $sql;
        } else {
            return false;
        }
    }
}
