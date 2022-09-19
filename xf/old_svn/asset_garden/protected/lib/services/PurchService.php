<?php

/**
 *
 */
class PurchService extends ItzInstanceService
{
    public $alarm_content = '';//报警内容

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 认购债权处理
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
        $retAll = array();
        //分页设置
        $pass = ($data['page'] - 1) * $data['limit'];  //跳过数据
        $data_limit = "{$pass},{$data['limit']}";

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
        $orderby = $orderby . $sort;
        //资产花园平台
        $model = Yii::app()->agdb;
        $sqlAdd = '';
        if(!empty($data['b_userid'])){
            $sqlAdd = " user_id = {$data['b_userid']} AND";
        }
        //卖方求购项目类型
        $sql = "SELECT id,discount,acquired_money,money,project_types,project_ids,platform_id,expiry_time FROM ag_purchase_order WHERE {$sqlAdd} money - acquired_money > 0 AND expiry_time >= UNIX_TIMESTAMP() AND status = 1 AND platform_id = {$data['platform_id']} $orderby LIMIT $data_limit";
        $purchaseOrderInfo = $model->createCommand($sql)->queryAll();
        if (empty($purchaseOrderInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        //只有卖家用户id
        if (!empty($data['s_userid'])) {
            //查询卖家的投资记录在存的
            $sql = "SELECT DISTINCT project_id,platform_id FROM ag_tender where platform_id = {$data['platform_id']} AND is_debt_confirm = 1 AND user_id = {$data['s_userid']} and status = 1 AND debt_status = 0";
            $selldata = $model->createCommand($sql)->queryAll();
            if (!empty($selldata)) {
                $projectIds = implode(",", ArrayUtil::array_column($selldata, "project_id"));
                $agProjectInfo = $model->createCommand("select id,type_id from ag_project where id in({$projectIds}) and platform_id = {$data['platform_id']}")->queryAll();
                $project_types = implode(",",ArrayUtil::array_column($agProjectInfo, "type_id"));
            }
        }
        foreach ($purchaseOrderInfo as $key => $val) {
            $val['operable'] = 0;
            //project_ids、project_types不指定收购平台下所有项目
            if (empty($val['project_ids']) && empty($val['project_types']) && !empty($projectIds)) $val['operable'] = 1;
            //指定了项目类型project_types未指定project_ids,匹配project_types
            if(!empty($val['project_types']) && empty($val['project_ids']) && !empty($projectIds) && ItzUtil::intersec($val['project_types'], $project_types))  $val['operable'] = 1;
            //指定了项目project_ids未指定project_types 进行匹配
            if (!empty($val['project_ids']) && empty($val['project_types']) && !empty($projectIds) && ItzUtil::intersec($val['project_ids'], $projectIds))  $val['operable'] = 1;
            //project_ids、project_types都不为空时，校验求购计划添加是否正确
            if(!empty($val['project_types']) && !empty($val['project_ids'])){
                $borrowTypeInfo = $model->createCommand("select id,type_id from ag_project where id IN({$val['project_ids']}) and type_id in({$val['project_types']})")->queryAll();
                if(!empty($borrowTypeInfo) && ItzUtil::intersec($val['project_ids'], $projectIds) && ItzUtil::intersec($val['project_types'], $project_types))  $val['operable'] = 1;
            }
            $retAll[] = array(
                "pur_id" => $val['id'],//求购计划id
                "discount" => $val['discount'],//折扣信息
                "expiry_time" => $val['expiry_time'],//剩余有效期返回时间戳
                "acquired_money" => $val['acquired_money'],//已购得金额
                "money" => $val['money'],//求购金额
                "operable" => $val['operable'],//发起转让状态 1:可操作 0:不可操作
            );
        }
        //可转让的
        if ($data['isable'] == 1) {
            $retIsable = '';//可转让
            foreach ($retAll as $val) {
                if ($val['operable'] == 1) {
                    $retIsable[] = $val;
                }
            }
            $retAll = $retIsable;
        }
        $return_result['code'] = 0;
        $return_result['data'] = $retAll;

        return $return_result;
    }
    /**
     * 发起转让列表
     * @param $pur_id
     * @param $user_id //C1资产花园用户id
     * @param $platform_id //平台id
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
        //校验用户确权同意协议
        $userInfo = $this->transformationUserid($user_id, $platform_id);
        if($userInfo['code'] != 0){
            $return_result['code'] = $userInfo['code'];
            return $return_result;
        }
        $ret = array();
        //资产花园导入平台
        $model = Yii::app()->agdb;
        $sql = "SELECT id,discount,project_ids FROM ag_purchase_order WHERE id = {$pur_id} AND money - acquired_money > 0 AND expiry_time >= UNIX_TIMESTAMP() AND status = 1 ";
        $purchaseOrderInfo = $model->createCommand($sql)->queryRow();
        if (empty($purchaseOrderInfo)) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        //认购计划有包含的项目 项目到期日升序,仅列用户和收购计划共有的项目 在存的无债转
        if (!empty($purchaseOrderInfo['project_ids'])) {
            $sqlAdd = "AND a.project_id IN({$purchaseOrderInfo['project_ids']})";
        }
        if (!empty($type_id)) {
            $sqltypeAdd = " AND b.type_id = $type_id";
        }
        $sql = "SELECT
                a.id,
                b. name AS projectName,
                a.wait_capital,
                a.bond_no,
                b.due_date,
                a.debt_status,
                b.id AS borrow_id
            FROM
                ag_tender a
            INNER JOIN ag_project b ON a.project_id = b.id
            WHERE
                a. status = 1
            AND a.debt_status = 0
            AND a.is_debt_confirm = 1
            AND a.user_id = $user_id $sqlAdd
            AND a.platform_id = $platform_id
            {$sqltypeAdd}
            ORDER BY b.due_date ASC";
        $result = $model->createCommand($sql)->queryAll();
        if (empty($result)) {
            $return_result['code'] = 4002;
            $return_result['info'] = '暂无数据';
            return $return_result;
        }
        $ret["discount"] = $purchaseOrderInfo['discount'];//求购计划折扣
        foreach ($result as $key => $val) {
            $ret['list'][] = array(
                "tender_id" => $val['id'],//发起转让投资记录ID
                "wait_capital" => $val['wait_capital'],//待还本金
                "bond_no" => $val['bond_no'],//合同编号
                "projectName" => $val['projectName'],//项目名称
                "debt_status" => $val['debt_status'],//债权状态 0无债转 1转让中 15已转让
                "borrow_id" => $val['borrow_id'],
            );
        }
        $ret["sum_wait_capital"] = array_sum(ArrayUtil::array_column($ret['list'], "wait_capital"));//待还本金总和
        $ret["expected"] = bcmul(bcdiv($purchaseOrderInfo['discount'], 10, 10), $ret["sum_wait_capital"], 2);//预计回本
        $return_result['code'] = 0;
        $return_result['data'] = $ret;
        return $return_result;
    }



    /**
     * 资方对C1用户求购承担批量调用
     * @param $tenderArr {“发起转让投资记录ID”，“认购金额”} 例：{"1":100，"2":200}
     * @param $pur_id 求购计划id
     * @param $vtype 1:认购 2:校验
     * @return array
     */
    public function  transferTransferBuy($tenderArr, $pur_id, $user_id, $platform_id, $vtype = 1)
    {
        $model = Yii::app()->agdb;
        $tenderInfo = json_decode($tenderArr, true);//传参解析
        $tenderIds = implode(",", ArrayUtil::array_column($tenderInfo, "tender_id"));
        $tender = array_column($tenderInfo, "money", "tender_id");
        $ret = array();//返回结果
        //求购订单表查询
        $sql = "SELECT id,user_id,discount,project_ids FROM ag_purchase_order WHERE id ={$pur_id} AND status = 1";
        $row = $model->createCommand($sql)->queryRow();
        if (empty($row)) {
            return array("code" => 4002);
        }//暂无数据
        if (!empty($row ['project_ids'])) {
            $sqlAdd = " AND a.project_id IN ({$row ['project_ids']})";
        }
        //查询投资记录匹配项目id
        $sql = "SELECT
                    a.id,
                    a.user_id,
                    a.platform_id,
                    b.name,
                    a.wait_capital
                FROM
                    ag_tender a
                LEFT JOIN ag_project b ON a.project_id = b.id WHERE a.id IN ($tenderIds)
                {$sqlAdd}  AND user_id = {$user_id} AND a.platform_id = {$platform_id}";
        $result = $model->createCommand($sql)->queryAll();
        if (empty($result)) {
            return array("code" => 4002);
        }//暂无数据
        foreach ($result as $key => $value) {
            $c_data = array(
                "user_id" => $value['user_id'],//发起方用户ID
                "buy_userid" => $row['user_id'],//求购方用户ID
                "money" => $tender[$value['id']],//发起债转金额
                "debt_src" => 3,//债权来源
                "purchase_order_id" => $row['id'],//债权来源为3的时候添加purchase_order_id求购计划id
                "discount" => $row['discount'],//折扣金额debt_src=1(discount=0);debt_src=2,3(discount:0.01~10)
                "effect_days" => 10,//有效天数
                "tender_id" => $value['id'],//发起债转投资记录ID
                "platform_id" => $value['platform_id'],//权益归属于哪个平台
            );
            if ($vtype == 1) {
                //批量进行债权发布和认购
                $resultArr = $this->debtPreTransaction($c_data);
                if ($resultArr['code'] == 0) {
                    $ret['success'][] = array(
                        "tender_id" => $value['id'],
                        "projectName" => $value['name'],
                        "return_capital" => $value['wait_capital'],
                    );
                } else {
                    $ret['fail'][] = array(
                        "tender_id" => $value['id'],
                        "projectName" => $value['name'],
                        "return_capital" => $value['wait_capital'],
                        "reason" => Yii::app()->c->data['errorcodeinfo'][$resultArr['code']],
                    );
                }
            } elseif ($vtype == 2) {
                //进行认购验证
                $resultArr = $this->debtPreAwcTransaction($c_data);
                if ($resultArr['code'] == 0) {
                    $ret[] = array(
                        "debt_id" => $value['id'],
                        "code" => 0,
                        "reason" => "校验通过",
                    );
                } else {
                    $ret[] = array(
                        "tender_id" => $value['tender_id'],
                        "code" => $resultArr['code'],
                        "reason" => Yii::app()->c->data['errorcodeinfo'][$resultArr['code']],
                    );
                }
            }

        }

        return $ret;
    }

    /**
     * 资方对C1用户求购承担批量调用
     * @param $debtArr {“转让记录ID”，“认购金额”} 例：[{"debt_id": 85,"money": 200}]
     * @param $buy_userid 资方用户id
     * @param $vtype 1:认购 2:校验
     * @param $utype 1:资方用户认购 2：C1用户认购
     * @return array
     */
    public function  transferAmcTransferBuy($debtArr, $buy_userid, $utype, $vtype = 1)
    {
        $model = Yii::app()->agdb;
        $debt = json_decode($debtArr, true);//传参解析
        $debtIds = implode(",", ArrayUtil::array_column($debt, "debt_id"));
        $debtInfo = ItzUtil::array_column($debt, "money", "debt_id");
        $ret = array();//返回结果
        //查询投资记录匹配项目id
        $sql = "SELECT
                    a.id,
                    a.platform_id,
                    a.user_id,
                    a.platform_id,
                    a.tender_id,
                    b.name,
                    a.sold_amount
                FROM
                    ag_debt a
                LEFT JOIN ag_project b ON a.project_id = b.id WHERE a.id IN ($debtIds) AND status = 1";
        $result = $model->createCommand($sql)->queryAll();
        if (empty($result)) {
            return array("code" => 4002);
        }//暂无数据
        foreach ($result as $key => $value) {
            $debt_data = array(
                "user_id" => $buy_userid,//买方用户ID
                "money" => $debtInfo[$value['id']],//发起债转金额
                "debt_src" => 2,//债权来源  1商城换物转让 2债转市场自主转让 3求购计划出售'
                "debt_id" => $value['id'],//被认购债权ID
                "platform_id" => $value['platform_id'],//归属平台id
                "tender_id" => $value['tender_id'],//原投资记录id
                "utype" => $utype,//1:资方用户认购 2：C1用户认购
            );
            $resultArr = $this->debtPreAwcTransaction($debt_data, $vtype);
            if ($resultArr['code'] == 0) {
                $ret['success'][] = array(
                    "debt_id" => $value['id'],
                    "code" => 0,
                    "projectName" => $value['name'],
                    "return_capital" => $debtInfo[$value['id']],//认购金额
                );
            } else {
                $ret['fail'][] = array(
                    "code" => $resultArr['code'],
                    "debt_id" => $value['id'],
                    "projectName" => $value['name'],
                    "return_capital" => $debtInfo[$value['id']],//认购金额
                    "reason" => Yii::app()->c->data['errorcodeinfo'][$resultArr['code']],
                );
            }
        }
        return $ret;
    }

    /**
     * 资方对C1用户求购承担单笔
     * @param array $data [
     * user_id 发起方用户ID
     * money 发起债转金额
     * debt_src 债权来源：0:ITZ自有平台债转 1:AG商城换物转让 2:AG债转市场自主转让 3:AG求购计划出售
     * discount 折扣金额debt_src=1(discount=0);debt_src=2,3(discount:0.01~10)
     * effect_days 有效天数 10,20,30
     * tender_id 发起债转投资记录ID
     * platform_id 平台id
     * ]
     * @return array
     */
    public function debtPreTransaction($c_data)
    {
        Yii::app()->agdb->beginTransaction();
        try {
            //平台信息校验
            $check_p = AgDebtService::getInstance()->checkPlatform($c_data['platform_id']);
            if ($check_p['code'] != 0) {
                $this->echoLog("handelDebt end tender_id:{$c_data['tender_id']} checkPlatform return:" . print_r($check_p,
                        true));
                Yii::app()->agdb->rollback();

                return array("code" => $check_p['code']);
            }
            //创建债权
            $create_ret = AgDebtService::getInstance()->createDebt($c_data);
            if ($create_ret === false || $create_ret['code'] != 0 || empty($create_ret['data'])) {
                $this->echoLog("handelDebt createDebt tender_id {$c_data['tender_id']} false:" . print_r($create_ret,
                        true), 'error', 'email');
                Yii::app()->agdb->rollback();

                return array("code" => $create_ret['code']);
            }
            //创建成功日志
            $this->echoLog("handelDebt createDebt tender_id {$c_data['tender_id']} success");
            //债权认购
            $debt_data = array();
            $debt_data['user_id'] = $c_data['buy_userid'];//求购方用户id
            $debt_data['money'] = $c_data['money'];//债权兑换金额
            $debt_data['debt_id'] = $create_ret['data']['debt_id'];//新生成的债权id
            $debt_data['debt_src'] = 3; //债权来源：1商城换物转让 2债转市场自主转让 3求购计划出售
            $debt_transaction_ret = AgDebtService::getInstance()->debtPreTransaction($debt_data);
            if ($debt_transaction_ret['code'] != 0) {
                $this->echoLog("handelDebt debtPreTransaction tender_id {$c_data['tender_id']} false:" . print_r($debt_transaction_ret,
                        true), 'error', 'email');
                Yii::app()->agdb->rollback();

                return array("code" => $debt_transaction_ret['code']);
            }
            //债转成功数据确认
            Yii::app()->agdb->commit();
            $this->echoLog("handelDebt tender_id:{$c_data['tender_id']} success");

            return array("code" => 0);
        } catch (Exception $ee) {
            $this->echoLog("handelDebt tender_id:{$c_data['tender_id']}; exception:" . print_r($ee->getMessage(),
                    true));
            Yii::app()->agdb->rollback();

            return array("code" => $debt_transaction_ret['code']);
        }
    }

    /**
     * 债权认购（单笔）
     * @param array $debt_data [
     * money 认购金额
     * user_id 买方用户ID
     * debt_id 被认购债权ID
     * debt_src债权来源：1商城换物转让 2债转市场自主转让 3求购计划出售
     * utype 1:资方用户认购 2：C1用户认购
     * ]
     * vtype 1:认购 2:验证
     * @param $buy_userid
     * @return array
     */
    public function debtPreAwcTransaction($debt_data, $vtype = 1)
    {
        Yii::app()->agdb->beginTransaction();
        try {
            //平台信息校验
            $check_p = AgDebtService::getInstance()->checkPlatform($debt_data['platform_id']);
            if ($check_p['code'] != 0) {
                $this->echoLog("handelDebt end tender_id:{$debt_data['tender_id']} checkPlatform return:" . print_r($check_p,
                        true));
                Yii::app()->agdb->rollback();
                return array("code" => $check_p['code']);
            }
            if ($vtype == 1) {
                //债权认购
                $debt_transaction_ret = AgDebtService::getInstance()->debtPreTransaction($debt_data);
                if ($debt_transaction_ret['code'] != 0) {
                    $this->echoLog("handelDebt debtPreTransaction tender_id {$debt_data['tender_id']} false:" . print_r($debt_transaction_ret,
                            true), 'error', 'email');
                    Yii::app()->agdb->rollback();

                    return array("code" => $debt_transaction_ret['code']);
                }
            } elseif ($vtype == 2) {
                //债权认购验证
                $debt_transaction_ret = AgDebtService::getInstance()->AmcTransferBuyRule($debt_data);
                if ($debt_transaction_ret['code'] != 0) {
                    $this->echoLog("handelDebt debtPreTransaction tender_id {$debt_data['tender_id']} false:" . print_r($debt_transaction_ret,
                            true), 'error', 'email');
                    Yii::app()->agdb->rollback();

                    return array("code" => $debt_transaction_ret['code']);
                }
            }

            //债转成功数据确认
            Yii::app()->agdb->commit();
            $this->echoLog("handelDebt tender_id:{$debt_data['tender_id']} success");

            return array("code" => 0);
        } catch (Exception $ee) {
            $this->echoLog("handelDebt tender_id:{$debt_data['tender_id']}; exception:" . print_r($ee->getMessage(),
                    true));
            Yii::app()->agdb->rollback();

            return array("code" => $debt_transaction_ret['code']);
        }
    }

    /**
     * 我的认购列表(资产花园)
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
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $page = $data['page'];
        $order = $data['order'];
        $limit = $data['limit'];
        $user_id = $data['user_id'];
        $platform_id = $data['platform_id'];
        $type = $data['type'];
        if(empty($user_id)){
            $return_result['code'] = 4007;
            return $return_result;
        }
        if ($type == 1) {
            //认购中
            $sqlAdd = " and ad.status = 1";
        } elseif ($type == 2) {
            //认购完成
            $sqlAdd = " and ad.status = 2";
        } elseif ($type == 3) {
            //认购失败
            $sqlAdd = " and ad.status IN(3,4)";
        }
        if (!empty($order) && $order == 1) {
            $order = "order by adt.id desc";
        }
        if (!empty($order) && $order == 2) {
            $order = "order by adt.id asc";
        }
        if (!empty($limit)) {
            $pass = ($page - 1) * $data['limit'];  //跳过数据
            $data_limit = "LIMIT {$pass},{$data['limit']}";
        }
        $model = Yii::app()->agdb;
        $debCount = $model->createCommand("select count(*) from ag_debt_tender adt left join ag_debt ad on adt.debt_id = ad.id left join ag_project_type apt on ad.project_type_id = apt.id left join ag_project ap on ad.project_id = ap.id where adt.user_id = $user_id and ad.platform_id = {$platform_id}  {$sqlAdd}")->queryScalar();
        if (empty($debCount) || $debCount == 0) {
            $return_result['code'] = 4002;
            return $return_result;
        }
        $sql = "select adt.c_viewpdf_url,adt.discount as discount,ap.name as projectName,ad.apr as apr,ad.amount as amount,adt.bond_no as bond_no,ad.id as debt_id,adt.addtime as addtime,apt.type_name as type_name from ag_debt_tender adt left join ag_debt ad on adt.debt_id = ad.id left join ag_project_type apt on ad.project_type_id = apt.id left join ag_project ap on ad.project_id = ap.id  where adt.user_id = $user_id and ad.platform_id = {$platform_id}  {$sqlAdd} {$order} {$data_limit} ";
        $debtTenderInfo = $model->createCommand($sql)->queryAll();
        $return_result['code'] = 0;
        $return_result['count'] = $debCount;
        $return_result['page_count'] = ceil($debCount / $limit);//总页数
        $return_result['status'] = $type;
        $return_result['data'] = $debtTenderInfo;

        return $return_result;
    }
	 /**
     * 通过资产平台用户id获取爱投资平台用户id
     * @param $user_id 资产花园平台用户id
     * @param $platformId
     * @return array
     */
    public function getformationUserid($user_id, $platformId)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );      
        $agmodel = Yii::app()->agdb;
        //查看平台用户关系	
        $userPlatformInfo = $agmodel->createCommand("SELECT platform_user_id,confirm_status,authorization_status,agree_status FROM ag_user_platform WHERE user_id = {$user_id} AND platform_id = {$platformId} and authorization_status = 1 and confirm_status = 1")->queryRow();
        if (!$userPlatformInfo) {
            $return_result['code'] = 2078;
            return $return_result;
        }
        $return_result['data']['platform_user_id'] = $userPlatformInfo['platform_user_id'];		
        return $return_result;
    }
    /**
     * 通过资产平台用户id获取爱投资平台用户id
     * @param $user_id 资产花园平台用户id
     * @param $platformId
     * @return array
     */
    public function transformationUserid($user_id, $platformId)
    {
        //返回数据
        $return_result = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $platformInfo = AgPlatform::model()->findByPk($platformId)->attributes;
        if(empty($platformInfo)){
            $return_result['code'] = 1015;
            return $return_result;
        }
        $agmodel = Yii::app()->agdb;
        //查看平台用户关系		
        $userPlatformInfo = $agmodel->createCommand("SELECT platform_user_id,confirm_status,authorization_status,agree_status FROM ag_user_platform WHERE user_id = {$user_id} AND platform_id = {$platformId}")->queryRow();
        if (!$userPlatformInfo) {
            $return_result['code'] = 2078;
            return $return_result;
        }
        //非平台导入数据验证绑定用户
        if($platformInfo['import_status'] == 2){
            //未绑定用户
            if (empty($userPlatformInfo['platform_user_id'])) {
                $return_result['code'] = 1017;
                return $return_result;
            }
        }
        //是否授权
        if($userPlatformInfo['authorization_status'] != 1){
            $return_result['code'] = 2077;
            return $return_result;
        }
        //是否确权
        if($userPlatformInfo['confirm_status'] != 1){
            $return_result['code'] = 2094;
            return $return_result;
        }
        //是否同意债转服务协议
        if($userPlatformInfo['agree_status'] != 1){
            $return_result['code'] = 2095;
            return $return_result;
        }	
        $return_result['data']['platform_user_id'] = $userPlatformInfo['platform_user_id'];
		
        return $return_result;
    }
    /**
     * 日志记录
     * @param $yiilog
     * @param string $level
     */
    public function echoLog($yiilog, $level = "info")
    {
        $this->alarm_content .= $yiilog . "<br/>";
        if ($level == 'email') {
            $level = "error";
            $this->is_email = true;
        }
        Yii::log("transferDebt: {$yiilog}", $level, 'agTransferDebt');
    }
}