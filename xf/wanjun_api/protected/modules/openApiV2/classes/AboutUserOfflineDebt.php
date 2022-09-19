<?php

class AboutUserOfflineDebt
{
    public $user_id = 0;
    public $limit = 0;
    public $offset = 0;
    public $condition = '';
    public $zx_borrow_ids = '';
    public $ph_borrow_ids = '';
    public $wise_borrow_ids = '';
    public $exchangeLeast = 1;
    public $is_from_debt_confirm = true;
    public $isCheckExchangeCommit = false;
    public $ph_order_borrow_id = ''; //注意这里是倒叙，后面的靠前排
    public $zx_order_borrow_id = ''; //注意这里是倒叙，后面的靠前排
    public $shop_app_id = 0;
    public $debt_type;
    public $is_not_check_white_list = false;
    public $area_id = 0;//专区
    public function __construct($user_id = 0, $debt_type=0, $appid=0, $area_code='')
    {
        $this->user_id = $user_id;
        $this->debt_type = $debt_type;
        $this->shop_app_id = $appid;
        if (!empty($area_code)) {
            $select_sql = "select id,status from xf_debt_exchange_special_area where appid=:appid and code=:code ";
            $res = Yii::app()->db->createCommand($select_sql)->bindValues([':appid' =>$appid])->bindValues([':code' =>$area_code])->queryRow() ?: [];
            $this->area_id =  $res && $res['status']==1? $res['id']:-1;
        }
    }

    /***************************尊享相关***start*******************************/

    /**
     * 获取用户可兑换债权列表.
     * @param $data
     * @return mixed
     */
    public function getUserOfflineDebtList($data)
    {
        $returnData = [
            'data' => ['total' => 0, 'list' => []],
            'code' => 0,
            'info' => 'success',
        ];
        $page = isset($data['page']) && $data['page'] > 1 ? $data['page'] : 1;
        $limit = isset($data['limit']) ? $data['limit'] : 10;
        //获取记录总条数
        $sum_count = $this->getUserSumAccountAndTotalTender($data);
        if (!$sum_count['total_tenders']) {
            //暂无数据
            return $returnData;
        }

        $this->limit = $limit;
        $this->offset = ($page - 1) * $limit;
        $list = $this->getUserCanDebtTenders();
        $returnData['data']['total'] = $sum_count['total_tenders'];
        $returnData['data']['list'] = $list;

        return $returnData;
    }

    /**
     * 获取用户可转让债权.
     * @return mixed
     */
    public function getUserCanDebtTenders($condition = '')
    {
        $returnData = [];
        $commonUserDebt = new CommonUserDebt(0, $this->user_id);
        if ($commonUserDebt->checkUserPurchase()) {
            return $returnData;
        }

        $splitPageSql = '';
        if ($this->limit) {
            $splitPageSql = "  limit {$this->limit}  offset {$this->offset} ";
        }
        $condition .= " AND t.platform_id = ".$this->debt_type;
        $notExchangeTenders = $this->getUserCanNotExchangeTenders() ?: [];
        $repayingBorrow = $this->getRepayingDealOrDealLoadId()['deal_id'];

        $disableBorrow = array_merge($repayingBorrow, $this->getDisableBorrow($this->debt_type));
        if (!empty($disableBorrow)) {
            $condition .= ' and b.id  not in ('.implode(',', $disableBorrow).')';
        }

        if ($this->isCheckExchangeCommit) {
            if (!empty($notExchangeTenders)) {
                $condition .= ' and t.id  not in ('.implode(',', $notExchangeTenders).')';
            }
            $splitPageSql = ' FOR UPDATE ';
        }

        $allowListJoin = '';
        if ($this->is_not_check_white_list) {
        } elseif ($this->getShopAllowBorrow($this->debt_type)) {
            $allowListJoin = " left join firstp2p.xf_debt_exchange_deal_allow_list as al on b.id = al.deal_id ";
            $condition .= " AND al.appid = {$this->shop_app_id} AND  al.status = 1 AND al.type = {$this->debt_type}  AND al.area_id = {$this->area_id} ";
        } else {
            return  [];
        }

        $order = ' ORDER BY t.id ASC ';

        if ($this->zx_order_borrow_id) {
            $order = ' ORDER BY FIELD(t.deal_id,'.$this->zx_order_borrow_id.') DESC, t.id ASC ';
        }
        $select_sql = " SELECT b.deal_type,t.debt_type,t.id,t.wait_capital AS account,b.name AS name ,t.deal_id AS borrow_id ,t.create_time AS addtime,t.black_status,b.buyer_uid  FROM offline_deal_load as t LEFT JOIN offline_deal as b ON t.deal_id = b.id {$allowListJoin} WHERE t.user_id = :user_id and t.xf_status = 0 and t.status = 1 AND   t.wait_capital > 0 AND t.black_status = 1 AND t.debt_status=0 AND b.deal_status = 4  {$condition}  {$order}  {$splitPageSql} ";
        //echo $select_sql;die;
        $allTenders = Yii::app()->offlinedb->createCommand($select_sql)->bindValues([':user_id' => $this->user_id])->queryAll() ;
        if (!$allTenders) {
            return [];
        }

        foreach ($allTenders as &$allTender) {
            $allTender['is_black'] = 1 == $allTender['black_status'] ? 0 : 1;
            $allTender['type'] = $this->debt_type;
            $allTender['is_exchanging'] = in_array($allTender['id'], $notExchangeTenders) ? 1 : 0;
            $allTender['payment_lately'] = 0;
        }

        return $allTenders;
    }

    /**
     * 获取用户待收本机及可转让tender条数.
     * @return array
     */
    public function getUserSumAccountAndTotalTender($data = [])
    {
        $returnData = [
            'total_account' => 0,
            'total_tenders' => 0,
        ];

        $commonUserDebt = new CommonUserDebt(0, $this->user_id);
        if ($commonUserDebt->checkUserPurchase()) {
            return $returnData;
        }


        if ($this->debt_type == 3) {
            //return $returnData;
        }

        $condition = '';

        $repayingBorrow = $this->getRepayingDealOrDealLoadId()['deal_id'] ?: [];

        $disableBorrow = array_merge($this->getDisableBorrow($this->debt_type), $repayingBorrow);

        if (!empty($disableBorrow)) {
            $condition = ' AND b.id  not in ('.implode(',', $disableBorrow).')';
        }
        $allowListJoin = '';
        if ($this->is_not_check_white_list) {
        } elseif ($this->getShopAllowBorrow($this->debt_type)) {
            $allowListJoin = " left join firstp2p.xf_debt_exchange_deal_allow_list as al on b.id = al.deal_id ";
            $condition .= " AND al.appid = {$this->shop_app_id} AND  al.status = 1 AND al.type = {$this->debt_type} AND al.area_id = {$this->area_id} ";
        } else {
            return  $returnData;
        }
        $condition .= " AND t.platform_id = ".$this->debt_type;

        $sum_count_sql = 'SELECT sum(t.wait_capital) AS total_account,count(1) AS total_tenders FROM offline_deal_load as t LEFT JOIN offline_deal as b ON t.deal_id = b.id '.$allowListJoin.' WHERE t.user_id = :user_id AND t.xf_status = 0 AND t.wait_capital > 0 AND t.black_status = 1 AND t.debt_status=0 AND b.deal_status = 4 '.$condition;


        $sum_count = Yii::app()->offlinedb->createCommand($sum_count_sql)->bindValues([':user_id' => $this->user_id])->queryRow();
        $exchanging_sql = "select sum(debt_account) as debt_account from offline_debt_exchange_log where user_id = :user_id and status = 1";
        $exchanging_sum_count = Yii::app()->offlinedb->createCommand($exchanging_sql)->bindValues([':user_id' => $this->user_id])->queryRow();
        $exchanging_amount = 0;
        if ($exchanging_sum_count) {
            $exchanging_amount = $exchanging_sum_count['debt_account'];
        }
        //剔除已兑换的金额
        $balance = bcsub($sum_count['total_account'], $exchanging_amount, 2);
        //剔除已兑换的金额
        $returnData['total_account'] = $balance > 0 ?$balance : 0;
        $returnData['total_tenders'] = $sum_count['total_tenders'] ?: 0;

        return $returnData;
    }

    private function getUserCanNotExchangeTenders()
    {
        $exchangeTender = $this->getUserWaitingOrdersTenders() ?: [];
        $repayingTender = $this->getRepayingDealOrDealLoadId()['deal_loan_id'] ?: [];

        return array_merge($exchangeTender, $repayingTender);
    }


    private function getRepayingDealOrDealLoadId()
    {
        $data = [
            'deal_id' => [], 'deal_loan_id' => [], 'user_id' => [],
        ];
        return $data;
        $sql = 'select deal_id,deal_loan_id,repay_type,loan_user_id from ag_wx_repayment_plan where status in (0,1,2)  ';
        $repayingDeal = Yii::app()->offlinedb->createCommand($sql)->queryAll() ?: [];
        if (!$repayingDeal) {
            return $data;
        }

        $special_deal_id = [];
        foreach ($repayingDeal as $item) {
            if (1 == $item['repay_type']) {
                $data['deal_id'][] = $item['deal_id'];
            } else {
                if (!empty($item['deal_loan_id'])) {
                    $data['deal_loan_id'] = array_merge($data['deal_loan_id'], explode(',', $item['deal_loan_id']));
                }
                if (!empty($item['loan_user_id'])) {
                    $data['user_id'] = array_merge($data['user_id'], explode(',', $item['loan_user_id']));
                    $special_deal_id[] = $item['deal_id'];
                }
            }
        }

        if (!empty($data['user_id']) && in_array($this->user_id, $data['user_id'])) {
            $data['deal_id'] = array_merge($data['deal_id'], $special_deal_id);
        }

        return $data;
    }

    /**
     * 获取待处理订单涉及的tender.
     * @return array
     */
    public function getUserWaitingOrdersTenders()
    {
        $tenders = [];
        $sql = 'select tender_id from offline_debt_exchange_log where user_id=:user_id AND status = 1 ';
        $waitingOrders = Yii::app()->offlinedb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->queryAll();
        if (!$waitingOrders) {
            return $tenders;
        }
        $tenders = ArrayUtil::array_column($waitingOrders, 'tender_id');

        return $tenders;
    }

    /***************************尊享相关***end*******************************/


    /**
     * 禁止黑名单.
     * @param int $type
     * @return array
     */
    public function getDisableBorrow($type)
    {
        $sql = "select deal_id from ag_wx_debt_black_list where `type` = {$type} AND status = 1 ";
        $blackBorrow = Yii::app()->db->createCommand($sql)->queryAll() ?: [];
        if (!$blackBorrow) {
            return $blackBorrow;
        }
        $blackBorrow = ArrayUtil::array_column($blackBorrow, 'deal_id');
        return $blackBorrow;
    }

    public function getShopAllowBorrow($type)
    {
        $sql = "select deal_id from xf_debt_exchange_deal_allow_list where appid = {$this->shop_app_id} and area_id = {$this->area_id} and  `type` = {$type} AND status = 1 ";
        $res = Yii::app()->db->createCommand($sql)->queryRow() ?: [];
        return  $res?true:false;
    }

    /**
     * 兑换提交.
     * @param $data
     * @return array
     */
    public function debtOrderCommit($data)
    {
        $returnData = [
            'data' => [],
            'code' => 0,
            'info' => '',
        ];
        //请求参数整理
        $tenderIds = !empty($data['ids'])? $data['ids'] : ArrayUtil::array_column($data['select_debt'], 'id');

        if (empty($tenderIds)) {
            $returnData['code'] = 2023;
            return $returnData;
        }
        if (!in_array($data['debt_type'], [3,4,5])) {
            $returnData['code'] = 2024;
            return $returnData;
        }
        $amount = $amount_init =  floatval($data['amount']) ? floatval($data['amount']) : 0;
        $orderNum = strval($data['exchange_no']) ?: '';

        $tenderIds = count($tenderIds) > 1 ? implode(',', $tenderIds) : current($tenderIds);
        $condition = " AND t.id in  ($tenderIds) AND t.black_status = 1 ";

        $notify_url = $data['notify_url'];
        $redirect_url = $data['redirect_url'];
        $appid = $data['appid'];
        $buyer_uid ='';

        try {

            //校验兑换流水号是否存在信息
            $end_time = time()-60*5;
            $check_ret = OfflineDebtExchangeLog::model()->find("order_id='$orderNum' and user_id={$this->user_id} and (status!=9 or (status=9 and addtime>=$end_time))");
            if ($check_ret) {
                $returnData['code'] = 2032;
                return $returnData;
            }
             
            Yii::app()->offlinedb->beginTransaction();

           
            //获取tender对应信息
            $this->isCheckExchangeCommit = true;
            $canDebtTendersInfo = $this->getUserCanDebtTenders($condition);
            //校验数据
            $tenderIdsArray = explode(',', $tenderIds);
            //非特殊兑换才校验这个
            if (count($tenderIdsArray) !== count($canDebtTendersInfo)) {
                $returnData['code'] = 2016;
                return $returnData;
            }
            //构建每笔tender消耗金额

            $res = $this->makeSaveExchangeTender($canDebtTendersInfo, $data['debt_type'], $amount, $buyer_uid, $data);
            if ($res['code']) {
                return  $res;
            }
            $saveTender = $res['data'];

            if ($saveTender) {
                $notice_data = [
                    'amount'=>$amount_init,
                    'appid'=>$appid,
                    'user_id'=>$this->user_id,
                    'order_id'=>$orderNum,
                    'notify_url'=>$notify_url,
                    'order_info'=>$data['goodsInfo'],
                    'order_sn'=>$data['goods_order_no'],
                ];
                $notice_data = json_encode($notice_data);
                $result = $this->saveDebtOrdersTmp($saveTender, $orderNum,$redirect_url, $notice_data);
                if (!$result) {
                    Yii::app()->offlinedb->rollback();
                    $returnData['code'] = 100;
                    return $returnData;
                }

                Yii::app()->offlinedb->commit();
                $returnData['contract_url'] = $result;
                //$this->saveNotice($amount_init, $orderNum, $appid, $notify_url, $data['goodsInfo'], $data['goods_order_no']);
                return $returnData;
            }
        } catch (Exception $e) {
            Yii::app()->offlinedb->rollback();
            Yii::log('save error debtType:'.$data['debtType'].' : save tender  '.print_r($saveTender, true) .' Exception:'.$e->getMessage(), 'error', __FUNCTION__);
        }

        Yii::log('save error debtType:'.$data['debtType'].' : save tender  '.print_r($saveTender, true), 'error', __FUNCTION__);
        $returnData['code'] = 2001;
        return $returnData;
    }

    private function makeSaveExchangeTender($canDebtTendersInfo, $type, $amount, $buyer_uid, $data)
    {
        $returnData = [
            'data' => [],
            'code' => 0,
            'info' => 'success',
        ];

        $otherTender = $tianbu_tenderList =  [];
        foreach ($canDebtTendersInfo as $item) {
            if ($item['account']<5) {
                $tianbu_tenderList[] = $item;
            } else {
                $otherTender[] = $item;
            }
        }

        $canDebtTendersInfo = array_merge($tianbu_tenderList, $otherTender);
        //构建每笔tender消耗金额
        foreach ($canDebtTendersInfo as $key => $orderTender) {
            $surplusMoney = 0;
            if ($amount <= 0) {
                $returnData['code'] = 2025;
                return $returnData;
            }
            if ($amount > 0) {
                $saveTender[$key]['type'] = $type;
                $saveTender[$key]['buyer_uid'] = $orderTender['buyer_uid'];
                $saveTender[$key]['tender_id'] = $orderTender['id'];
                $saveTender[$key]['name'] = $orderTender['name'];
                $saveTender[$key]['borrow_id'] = $orderTender['borrow_id'];
                $saveTender[$key]['debt_account'] = min($orderTender['account'], $amount);
                $saveTender[$key]['order_sn'] = $data['goods_order_no'];
                $saveTender[$key]['order_info'] = $data['goodsInfo'];
                $saveTender[$key]['platform_no'] = $data['appid'];
                $saveTender[$key]['debt_type'] = $orderTender['debt_type'];
                $saveTender[$key]['deal_type'] = $orderTender['deal_type'];
                $saveTender[$key]['addtime'] = $orderTender['addtime'];
                $amount = bcsub($amount, $saveTender[$key]['debt_account'], 2);
                $surplusMoney = bcsub($orderTender['account'], $saveTender[$key]['debt_account'], 2);
            }
            if ($surplusMoney>0 && FunctionUtil::float_bigger(5, $surplusMoney, 3)) {
                $temp_money = bcsub(5, $surplusMoney, 2);
                $amount = bcadd($amount, $temp_money, 2);
                $saveTender[$key]['debt_account'] =  bcsub($saveTender[$key]['debt_account'], $temp_money, 2);
            }
            if ($surplusMoney>0 && FunctionUtil::float_bigger(5, $saveTender[$key]['debt_account'], 3)) {
                $returnData['code'] = 2027;
                return $returnData;
            }
        }

        Yii::log('save tender  amount:'.$amount.' debtType: '.$data['debt_type'].'  data '.print_r($saveTender, true), 'info', __FUNCTION__);

        if ($amount > 0) {
            $returnData['code'] = 2026;
            return $returnData;
        }

        //构建债权受让人
        $res = DebtExchangeService::getInstance()->makeDebtBuyer($saveTender);
        if ($res['code']) {
            return  $res;
        }
        $returnData['data'] = $res['data'];
        return $returnData;
    }


    /**
     * @param $amount
     * @param $orderNum
     * @param $appid
     * @param string $notify_url
     * @param string $goodsInfo
     * @param string $goods_order_no
     * @return bool
     */
    private function saveNotice($amount, $orderNum, $appid, $notify_url='', $goodsInfo='', $goods_order_no='')
    {
        $notice = new XfDebtExchangeNotice();
        $notice->amount = $amount;
        $notice->appid = $appid;
        $notice->user_id = $this->user_id;
        $notice->order_id = $orderNum;
        $notice->notify_url = $notify_url;
        $notice->order_info = $goodsInfo;
        $notice->order_sn = $goods_order_no;
        $notice->created_at = time();
        $notice->notice_time_1 = time();
        $notice->notice_time_2 = time()+30;
        $notice->notice_time_3 = time()+300;
        return  $notice->save();
    }
    /**
     * 保存兑换债权.
     * @param $tenders
     * @param $orderNum
     * @return bool
     */
    public function saveDebtOrders($tenders, $orderNum)
    {
        if (empty($tenders)) {
            return false;
        }
        $now = time();
        foreach ($tenders as $tender) {
            $debtOrder = new OfflineDebtExchangeLog();
            $debtOrder->user_id = $this->user_id;
            $debtOrder->order_id = $orderNum;
            $debtOrder->borrow_id = $tender['borrow_id'] ?: 0;
            $debtOrder->tender_id = $tender['tender_id'] ?: 0;
            $debtOrder->debt_account = $tender['debt_account'];
            $debtOrder->addtime = $now;
            $debtOrder->status = 1;
            $debtOrder->platform_no = $tender['platform_no'];
            $debtOrder->buyer_uid = $tender['buyer_uid'];
            $debtOrder->debt_src = $tender['debt_src'] ?: 1;
            $debtOrder->platform_id = $this->debt_type ;
            $debtOrder->area_id = $this->area_id ;

            if (!$debtOrder->save()) {
                Yii::log('save debt orders error tenders:'.print_r($tenders, true), 'error', __FUNCTION__);

                return false;
            }
        }

        return true;
    }

    public function saveDebtOrdersTmp($tenders, $orderNum, $redirect_url, $notice_data)
    {
        if (empty($tenders)) {
            return false;
        }
        $now = time();
        $buyer_uid = $total_capital = 0;
        $transaction_id = str_replace('.', '', uniqid('', true));
        //拼接合同内容
        $deal_load_content = $deal_load_content_01 = $deal_load_content_02 = $deal_load_content_03 = $deal_load_content_04 = $deal_load_content_05 = '';
        $deal_load_content_06 = $deal_load_content_07 = $deal_load_content_08 = $deal_load_content_09 = $deal_load_content_10 = $deal_load_content_11 = '';
        foreach ($tenders as $key=>$tender) {
            //提测试注释
            //$tender['buyer_uid'] =12133514;

            //合同信息
            $total_capital = bcadd($total_capital, $tender['debt_account'], 2);
            $buyer_uid = $tender['buyer_uid'];
            $platform_no = $tender['platform_no'];
            //债转合同编号根据规则拼接
            $seller_contract_number = implode('-', [date('Ymd', $tender['addtime']), $tender['deal_type'], $tender['borrow_id'], $tender['tender_id']]);

            //智多新获取合同编号
            if ($tender['debt_type'] == 1  ) {
                $contract_info = OfflineContractTask::model()->find("tender_id={$tender['tender_id']} and contract_type=1 and type=1 and status=2");
                if (!$contract_info) {
                    Yii::log("saveDebtOrdersTmp tender_id[{$tender['tender_id']}] OfflineContractTask error  ", 'error');
                    return false;
                }
                $seller_contract_number = $contract_info->contract_no;
            }

            $n = $key+1;
            if ($n<=25) {
                $deal_load_content .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>25 && $n<= 57) {
                $deal_load_content_01 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>57 && $n<= 89) {
                $deal_load_content_02 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>89 && $n<= 121) {
                $deal_load_content_03 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>121 && $n<= 153) {
                $deal_load_content_04 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>153 && $n<= 185) {
                $deal_load_content_05 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>185 && $n<= 217) {
                $deal_load_content_06 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>217 && $n<= 249) {
                $deal_load_content_07 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>249 && $n<= 281) {
                $deal_load_content_08 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>281 && $n<= 313) {
                $deal_load_content_09 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>313 && $n<= 345) {
                $deal_load_content_10 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }
            if ($n>345 && $n<= 377) {
                $deal_load_content_11 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender['name']}；转让债权本金：{$tender['debt_account']}元整。\r\n";
            }

            $debtOrder = new OfflineDebtExchangeLog();
            $debtOrder->user_id = $this->user_id;
            $debtOrder->order_id = $orderNum;
            $debtOrder->borrow_id = $tender['borrow_id'] ?: 0;
            $debtOrder->tender_id = $tender['tender_id'] ?: 0;
            $debtOrder->debt_account = $tender['debt_account'];
            $debtOrder->addtime = $now;
            $debtOrder->status = 9;
            $debtOrder->platform_no = $tender['platform_no'];
            $debtOrder->buyer_uid = $tender['buyer_uid'];
            $debtOrder->debt_src = $tender['debt_src'] ?: 1;
            $debtOrder->platform_id = $this->debt_type ;
            $debtOrder->area_id = $this->area_id ;
            $debtOrder->contract_transaction_id = $transaction_id;
            if (!$debtOrder->save()) {
                Yii::log('save saveDebtOrdersOfflineTmp debt orders error tenders:'.print_r($tenders, true), 'error', __FUNCTION__);
                return false;
            }
        }

        //受让人信息查询
        $buyer_uid = !empty($buyer_uid) ? $buyer_uid : DebtService::getInstance()->getBuyerUid($total_capital);
        $assignee = User::model()->findByPk($buyer_uid)->attributes;
        if (!$assignee) {
            Yii::log("saveDebtOrdersPHTmp order_id[{$orderNum}]   assignee error  $buyer_uid ", 'error');
            return false;
        }

        //拼接合同内容
        $assignee_idno = GibberishAESUtil::dec($assignee['idno'], Yii::app()->c->contract['idno_key']);
        $seller_user = User::model()->findByPk($this->user_id)->attributes;
        $seller_idno = GibberishAESUtil::dec($seller_user['idno'], Yii::app()->c->contract['idno_key']);
        $template_id = empty($deal_load_content_03) ? Yii::app()->c->contract[8]['template_id'] : Yii::app()->c->contract[9]['template_id'];
        $shop_name = DebtService::getInstance()->getShopName($platform_no);

        //合同生成
        $cvalue = [
            'title' => '债权转让协议',
            'params' => [
                'contract_id' => implode('-', ['JFDH', date('Ymd', time()), $buyer_uid, $this->user_id]),
                'debt_account_total' =>  $total_capital,
                'A_user_name' => $seller_user['real_name'],
                'A_card_id' => $seller_idno,
                'B_user_name' => $assignee['real_name'],
                'B_card_id' => $assignee_idno,
                'sign_year' => date('Y', $now),
                'sign_month' => date('m', $now),
                'sign_day' => date('d', $now),
                'company_name' =>   "北京东方联合投资管理有限公司",
                'plan_name' =>   "网信普惠平台",
                'shop_name' =>   $shop_name,
                'web_address' =>   "www.firstp2p.com",
                'deal_load_content' => $deal_load_content,//债权信息
                'deal_load_content_one' => $deal_load_content_01,
                'deal_load_content_two' => $deal_load_content_02,
                'deal_load_content_three' => $deal_load_content_03,
                'deal_load_content_four' => $deal_load_content_04,
                'deal_load_content_five' => $deal_load_content_05,
                'deal_load_content_8' => $deal_load_content_06,
                'deal_load_content_9' => $deal_load_content_07,
                'deal_load_content_10' => $deal_load_content_08,
                'deal_load_content_11' => $deal_load_content_09,
                'deal_load_content_12' => $deal_load_content_10,
                'deal_load_content_13' => $deal_load_content_11,
            ],
            'sign' => [
                'A盖签' => $seller_user['yj_fdd_customer_id'],
                'B盖签' => '',
            ],
            'pwd' => '',
        ];

        //合同文档标题
        $doc_title = $cvalue['title'];
        //填充自定义参数
        $params = $cvalue['params'];
        //生成合同
        $result = XfFddService::getInstance()->invokeGenerateContract($template_id, $doc_title, $params, $cvalue['dynamic_tables']?:'');
        if (!$result || $result['code'] != 1000) {
            Yii::log("saveDebtOrdersTmp order_id[{$orderNum}]  合同生成失败！\n" . print_r($result, true), 'error');
            return false;
        }
        //法大大合同ID
        $contract_id = $result['contract_id'];

        //加水印
        $text_name = mb_substr("{$seller_user['real_name']}  {$assignee['real_name']}", 0, 15, 'utf-8');
        $watermark_params = [
            'contract_id' => $contract_id,
            'stamp_type' => 1,
            'text_name' => $text_name,
            'font_size' => 12,
            'rotate' => 45,
            'concentration_factor' => 10,
            'opacity' => 0.2,
        ];
        $result = XfFddService::getInstance()->watermarkPdf($watermark_params);
        if (!$result || $result['code'] != 1) {
            Yii::log("saveDebtOrdersTmp order_id[{$orderNum}]   的{$cvalue['title']}加水印失败！\n" . print_r($result, true), 'error');
            return false;
        }

        //卖方手动签署合同
        $sign_contract_url = XfFddService::getInstance()->invokeExtSign($seller_user['yj_fdd_customer_id'], $contract_id, $doc_title, 'A盖签', $transaction_id, 1);
        if (!$sign_contract_url) {
            Yii::log("saveDebtOrdersTmp order_id[{$orderNum}]  收购合同获取签署地址失败！\n" . print_r($result, true), 'error');
            return false;
        }

        //合临时表数据写入
        $redirect_url = urldecode($redirect_url);
        $strc = substr_count($redirect_url, '?');
        $redirect_url = $strc>0 ? $redirect_url."&exchange_no={$orderNum}" : $redirect_url."?exchange_no={$orderNum}";
        $redirect_url = urlencode($redirect_url);

        $debt['user_id'] = $this->user_id;
        $debt['deal_load_id'] = 0;
        $debt['status'] = 0;
        $debt['contract_transaction_id'] = $transaction_id;
        $debt['add_time'] = $now;
        $debt['contract_url'] = $sign_contract_url;
        $debt['contract_id'] = $contract_id;
        //$redirect_url = $redirect_url."?exchange_no={$orderNum}";
        $debt['return_url'] = $redirect_url;
        $debt['platform_id'] = 4;
        $debt['notice_data'] = $notice_data;
        $debt['buyer_uid'] = $buyer_uid;
        $ret = BaseCrudService::getInstance()->add('Firstp2pDebtContract', $debt);
        if(false == $ret){//添加失败
            Yii::log("saveDebtOrdersTmp  user_id[{$this->user_id}] , order_id[{$orderNum}] : add DebtContract error ", 'error');
            return false;
        }
        //合同签署地址
        return $sign_contract_url;
    }




    /***************************以下是公用逻辑*******************************/

    /**
     * 获取兑换记录状态
     * @param $params
     * @return array
     */
    public function getOrderStatus($params)
    {
        $returnData = [
            'data' => [],
            'code' => 100,
            'info' => '订单不存在，请核对单号',
        ];
        $ordersNum = $params['exchange_no'];
        if (empty($ordersNum)) {
            $returnData['info'] = '单号不能为空';
            return $returnData;
        }
        $user_id = intval($params['openid']);
        if (empty($ordersNum)) {
            $returnData['info'] = 'openid 不能为空';
            return $returnData;
        }
        $amount = 0;
        $is_set = 0;
        $create_time = 0;
        $exchangeInfo = DebtExchangeLog::model()->findBySql("select sum(debt_account) as debt_account,order_id,min(addtime) as addtime from offline_debt_exchange_log where user_id = {$user_id} and  order_id = '{$ordersNum}'");
        if (!empty($exchangeInfo)) {
            $is_set = $exchangeInfo->order_id?1:0;
            $create_time = $exchangeInfo->addtime;
            $amount = bcadd($amount, $exchangeInfo->debt_account, 2);
        }

        //获取数据
        $phExchangeInfo = PHDebtExchangeLog::model()->findBySql("select sum(debt_account) as debt_account,order_id,min(addtime) as addtime from offline_debt_exchange_log where user_id = {$user_id} and  order_id = '{$ordersNum}'");
        if (!empty($phExchangeInfo)) {
            $is_set = $exchangeInfo->order_id?1:0;
            $create_time = $exchangeInfo->addtime;
            $amount = bcadd($amount, $phExchangeInfo->debt_account, 2);
        }

        $returnData['data']['exchange_no'] = $ordersNum;
        $returnData['data']['create_time'] = $create_time;
        $returnData['data']['amount'] = $amount;
        $returnData['data']['status'] = $is_set?($amount > 0 ? '1' : '0') :'-1';
        $returnData['code'] = 0;
        $returnData['info'] = 'success';

        return $returnData;
    }
}
