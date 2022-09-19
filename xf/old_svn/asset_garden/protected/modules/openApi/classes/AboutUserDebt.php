<?php
class AboutUserDebt
{
    public $user_id = 0;
    public $limit = 0;
    public $offset = 0;
    public $condition = '';
    public $zx_borrow_ids = '';
    public $ph_borrow_ids = '';
    public $wise_borrow_ids = '';
    public $exchangeLeast = 1;
    public $is_from_debt_confirm = false;
    public $isCheckExchangeCommit = false;
    public $ph_order_borrow_id = '7369050,7369054,7351484,7350014,7345206,7372135,7372137,7369055,7369052,7351488'; //注意这里是倒叙，后面的靠前排
    public $zx_order_borrow_id = '6211593,6210631,6208094,6204389,6210308,6210307,6210306,6209044,6209043,6204381,6204376,5540742,6201474,6209019,6208265,6209499,6209498,6209497,5520580,5517729'; //注意这里是倒叙，后面的靠前排
    public function __construct($user_id = 0)
    {
        $this->user_id = $user_id;
    }


    /***************************尊享相关***start*******************************/


    /**
     * 获取用户可兑换债权列表
     * @param $data
     * @return mixed
     */
    public function getUserDebtList($data)
    {
        $returnData = [
            'data' => ['total' => 0, 'list' => []],
            'code' => 0,
            'info' => 'success'
        ];
        $page      = isset($data['page'])  && $data['page'] > 1 ? $data['page'] : 1;
        $limit      = isset($data['limit']) ? $data['limit'] : 10;
        //获取记录总条数
        $sum_count = $this->getUserSumAccountAndTotalTender();
        if (!$sum_count['total_tenders']) {
            //暂无数据
            return $returnData;
        }

        $this->limit = $limit;
        $this->offset = ($page - 1) * $limit;
        $list = $this->getUserCanDebtTenders();
        $returnData['data']['total']    = $sum_count['total_tenders'];
        $returnData['data']['list'] = $list;
        return $returnData;
    }

    /**
     * 获取用户可转让债权
     * @return mixed
     * TODO OK
     */
    public function getUserCanDebtTenders($condition = '')
    {
        $splitPageSql = '';
        if ($this->limit) {
            $splitPageSql = "  limit {$this->limit}  offset {$this->offset} ";
        }

        $notExchangeTenders = $this->getUserCanNotExchangeTenders() ?: [];

        $repayingBorrow = $this->getRepayingDealOrDealLoadId()['deal_id'];

        $disableBorrow = array_merge($repayingBorrow, $this->getDisableBorrow(1));
        if (!empty($disableBorrow)) {
            $condition .= " and b.id  not in (" . implode(',', $disableBorrow) . ")";
        }

        if ($this->isCheckExchangeCommit) {
            if (!empty($notExchangeTenders)) {
                $condition .= " and t.id  not in (" . implode(',', $notExchangeTenders) . ")";
            }
            $splitPageSql = ' FOR UPDATE ';
        }
        if (!$this->is_from_debt_confirm) {
            $condition .= " AND t.is_debt_confirm = 1 ";
        }

        if (!empty($this->zx_borrow_ids)) {
            $condition .= " AND b.id  IN ($this->zx_borrow_ids) ";
        }

        $order = " ORDER BY addtime ASC ";

        if ($this->zx_order_borrow_id) {
            $order = " ORDER BY FIELD(t.deal_id," . $this->zx_order_borrow_id . ") DESC, addtime ASC ";
        }
        $select_sql = " SELECT t.id,t.wait_capital AS account,b.name AS name ,t.deal_id AS borrow_id ,t.create_time AS addtime,t.black_status FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id WHERE t.user_id = :user_id   AND   t.wait_capital > 0  AND t.debt_status=0 AND b.deal_status = 4  {$condition}  {$order}  {$splitPageSql} ";
        $allTenders = Yii::app()->db->createCommand($select_sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        foreach ($allTenders as &$allTender) {
            $allTender['is_black'] =  $allTender['black_status'] == 1 ? 0 : 1;
            $allTender['type'] = 0;
            $allTender['is_exchanging'] = in_array($allTender['id'], $notExchangeTenders) ? 1 : 0;
            $allTender['payment_lately'] = 0;
        }

        return $allTenders;
    }


    /**
     * 获取用户待收本机及可转让tender条数
     * @return array
     * TODO OK
     */
    public function getUserSumAccountAndTotalTender($data = [])
    {
        $returnData = [
            'total_account' => 0,
            'total_tenders' => 0,
        ];
        $condition = '';

        $repayingBorrow = $this->getRepayingDealOrDealLoadId()['deal_id'] ?: [];

        $disableBorrow = array_merge($this->getDisableBorrow(1), $repayingBorrow);

        if (!empty($disableBorrow)) {
            $condition = " AND b.id  not in (" . implode(',', $disableBorrow) . ")";
        }
        if (!$this->is_from_debt_confirm) {
            $condition .= " AND t.is_debt_confirm = 1 ";
        }

        if (!empty($this->zx_borrow_ids)) {
            $condition .= " AND b.id  IN ($this->zx_borrow_ids) ";
        }

        $sum_count_sql = "SELECT sum(t.wait_capital) AS total_account,count(1) AS total_tenders FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id WHERE t.user_id = :user_id  AND t.wait_capital > 0 AND t.debt_status=0 AND b.deal_status = 4 " . $condition;
        $sum_count = Yii::app()->db->createCommand($sum_count_sql)->bindValues([':user_id' => $this->user_id])->queryRow();
        //剔除已兑换的金额
        $returnData['total_account'] = $sum_count['total_account'] ?: 0;
        $returnData['total_tenders'] = $sum_count['total_tenders'] ?: 0;
        return $returnData;
    }

    private function getUserCanNotExchangeTenders()
    {
        $exchangeTender = $this->getUserWaitingOrdersTenders() ?: [];
        $todayRepayTender = $this->getUserTodayRepayTender() ?: [];
        $repayingTender = $this->getRepayingDealOrDealLoadId()['deal_loan_id'] ?: [];
        return array_merge($exchangeTender, $todayRepayTender, $repayingTender);
    }

    /**
     * 今天为还款日的tender
     * @return array
     */
    private function getUserTodayRepayTender()
    {
        return [];
        $sql = " SELECT DISTINCT deal_loan_id FROM firstp2p_deal_loan_repay WHERE loan_user_id = :user_id AND `type` = 1 AND `time` =  " . strtotime(date('Y-m-d 16:00:00'));
        $todayRepayTender = Yii::app()->db->createCommand($sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        if (!$todayRepayTender) {
            return $todayRepayTender;
        }
        $todayRepayTender = ArrayUtil::array_column($todayRepayTender, 'deal_loan_id');
        return $todayRepayTender;
    }

    private function getRepayingDealOrDealLoadId()
    {
        $data = [
            'deal_id' => [], 'deal_loan_id' => [], 'user_id' => []
        ];
        $sql = "select deal_id,deal_loan_id,repay_type,loan_user_id from ag_wx_repayment_plan where status in (0,1,2)  ";
        $repayingDeal = Yii::app()->db->createCommand($sql)->queryAll() ?: [];
        if (!$repayingDeal) {
            return $data;
        }

        $special_deal_id = [];
        foreach ($repayingDeal as $item) {
            if ($item['repay_type'] == 1) {
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
     * 获取待处理订单涉及的tender
     * @return array
     * TODO ok
     */
    public function getUserWaitingOrdersTenders()
    {
        $tenders = [];
        $sql = "select tender_id from firstp2p_debt_exchange_log where user_id=:user_id AND status = 1 ";
        $waitingOrders = Yii::app()->db->createCommand($sql)->bindValues([':user_id' => $this->user_id])->queryAll();
        if (!$waitingOrders) {
            return $tenders;
        }
        $tenders = ArrayUtil::array_column($waitingOrders, 'tender_id');
        return $tenders;
    }




    /***************************尊享相关***end*******************************/


    /***************************普惠相关***start*******************************/



    /**
     * 获取用户可兑换债权列表
     * @param $data
     * @return mixed
     */
    public function getUserDebtListPH($data)
    {
        $returnData = [
            'data' => ['total' => 0, 'list' => []],
            'code' => 0,
            'info' => 'success'
        ];
        $page      = isset($data['page'])  && $data['page'] > 1 ? $data['page'] : 1;
        $limit      = isset($data['limit']) ? $data['limit'] : 10;
        //获取记录总条数
        $sum_count = $this->getUserSumAccountAndTotalTenderPH();
        if (!$sum_count['total_tenders']) {
            //暂无数据
            return $returnData;
        }

        $this->limit = $limit;
        $this->offset = ($page - 1) * $limit;
        $list = $this->getUserCanDebtTendersPH();
        $returnData['data']['total'] = $sum_count['total_tenders'];
        $returnData['data']['list'] = $list;
        return $returnData;
    }


    /**
     * 获取用户可转让债权
     * @return mixed
     * TODO OK
     */
    public function getUserCanDebtTendersPH($condition = '')
    {
        $splitPageSql = '';
        if ($this->limit) {
            $splitPageSql = "  limit {$this->limit}  offset {$this->offset} ";
        }

        $disableBorrow  = array_merge($this->getDisableBorrow(2), $this->getZDXBorrow());
        if (!empty($disableBorrow)) {
            $condition .= " and b.id  not in (" . implode(',', $disableBorrow) . ") ";
        }
        $advisory_condition = '';
        //咨询方的
        if ($advisoryId = $this->getPhDisableAdvisoryId()) {
            //$advisory_condition = ' AND  b.advisory_id  in (' . implode(',', [153, 215, 397, 399]) . ') and b.product_class_type in (5,232,202,223,316)  ';
            $advisory_condition = ' OR ( b.advisory_id  in (' . implode(',', $advisoryId) . ') and b.product_class_type in (5,232,202,223,316)  )';
        }
        $disableExchangeTenders = $this->getUserCanNotExchangeTendersPH() ?: [];

        if ($this->isCheckExchangeCommit) {
            if (!empty($disableExchangeTenders)) {
                $condition .= " and t.id  not in (" . implode(',', $disableExchangeTenders) . ") ";
            }
            $splitPageSql = ' FOR UPDATE ';
        }

        if (!$this->is_from_debt_confirm) {
            $condition .= " AND t.is_debt_confirm = 1 ";
        }
        $yourong = false;
        if (!empty($this->ph_borrow_ids)) {
            if (strripos($this->ph_borrow_ids, 'all') !== false) {
                if (current(explode('_', $this->ph_borrow_ids)) == 'yourongph') {
                    //5 消费贷
                    //232 个体经营贷
                    //202 产融贷
                    //223 供应链
                    //316 企业经营贷
                    $yourong = true;
                    $condition .= '  AND b.advisory_id  in (' . implode(',', [153, 215, 397, 399]) . ') and b.product_class_type in (5,232,202,223,316)  ';
                }
            } else {
                $condition .= " AND b.id  IN ($this->ph_borrow_ids) ";
            }
        }

        //部分还款中的债权
        if ($partialRepayTender = $this->getUserPartialRepayTenderPH()) {
            $condition .= " AND t.id  not in (" . implode(',', $partialRepayTender) . ") ";
        }

        $order = " ORDER BY  t.id ASC ";
        if ($this->ph_order_borrow_id) {
            $order = " ORDER BY FIELD(t.deal_id," . $this->ph_order_borrow_id . ") DESC, t.id ASC ";
        }

        if ($yourong) {
            $order = " ORDER BY FIELD(b.product_class_type,5,232,223) ASC, t.id ASC "; //消费贷的靠前，供应链的靠后。
        }
        $select_sql = " SELECT t.id,t.wait_capital AS account,b.name AS name, t.deal_id AS borrow_id ,t.create_time AS addtime ,t.black_status FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id WHERE t.user_id = :user_id  AND ( b.product_class_type = 223 " . $advisory_condition . " ) AND   t.wait_capital > 0 AND t.debt_status=0   {$condition}  {$order}  {$splitPageSql} ";
        $allTenders = Yii::app()->phdb->createCommand($select_sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        foreach ($allTenders as &$allTender) {
            $allTender['is_black'] =  $allTender['black_status'] == 1 ? 0 : 1;
            $allTender['type'] = 0;
            $allTender['is_exchanging'] = in_array($allTender['id'], $disableExchangeTenders) ? 1 : 0;
            $allTender['payment_lately'] = 0;
        }

        return $allTenders;
    }



    /**
     * 获取用户待收本机及可转让tender条数
     * @return array
     * TODO OK
     */
    public function getUserSumAccountAndTotalTenderPH($data = [])
    {
        $returnData = [
            'total_account' => 0,
            'total_tenders' => 0,
        ];
        $condition = '';
        $disableBorrow  = array_merge($this->getDisableBorrow(2), $this->getZDXBorrow());
        if (!empty($disableBorrow)) {
            $condition = " AND b.id  not in (" . implode(',', $disableBorrow) . ") ";
        }
        $advisory_condition = '';
        //开放咨询方的
        if ($advisoryId = $this->getPhDisableAdvisoryId()) {
            //$advisory_condition = ' AND b.advisory_id  not in (' . implode(',', $advisoryId) . ') ';
            $advisory_condition = ' OR ( b.advisory_id  in (' . implode(',', $advisoryId) . ') and b.product_class_type in (5,232,202,223,316)  )';
        }
        if (!$this->is_from_debt_confirm) {
            $condition .= " AND t.is_debt_confirm = 1 ";
        }

        if (!empty($this->ph_borrow_ids)) {
            if (strripos($this->ph_borrow_ids, 'all') !== false) {
                if (current(explode('_', $this->ph_borrow_ids)) == 'yourongph') {
                    $condition .= '  AND b.advisory_id  in (' . implode(',', [153, 215, 397, 399]) . ') and ';

                    $condition .= '  b.product_class_type in (5,232,202,223,316)  ';
                }
            } else {
                $condition .= " AND b.id  IN ($this->ph_borrow_ids) ";
            }
        }
        //部分还款中的债权
        if ($partialRepayTender = $this->getUserPartialRepayTenderPH()) {
            $condition .= " AND t.id  not in (" . implode(',', $partialRepayTender) . ") ";
        }

        $sum_count_sql = "SELECT sum(t.wait_capital) AS total_account,count(1) AS total_tenders FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id WHERE t.user_id = :user_id  AND ( b.product_class_type = 223 " . $advisory_condition . " ) AND t.wait_capital > 0 AND t.debt_status=0  " . $condition;
        $sum_count = Yii::app()->phdb->createCommand($sum_count_sql)->bindValues([':user_id' => $this->user_id])->queryRow();
        //剔除已兑换的金额
        $returnData['total_account'] = $sum_count['total_account'] ?: 0;
        $returnData['total_tenders'] = $sum_count['total_tenders'] ?: 0;
        return $returnData;
    }

    /**
     * 禁用普惠咨询方
     * @return array
     */
    private function getPhDisableAdvisoryId()
    {
        return [153, 215, 397, 399]; //悠融资产管理（上海）有限公司
    }

    /**
     * 获取智多星项目
     * @return array
     */
    public function getZDXBorrow()
    {
        $data = Yii::app()->rcache->get('_firstp2p_deal_tag_42_44');
        if(empty($data)){
            $sql = "SELECT deal_id FROM firstp2p_deal_tag WHERE tag_id in (42,44) ";
            $res = Yii::app()->phdb->createCommand($sql)->queryAll() ?: [];
            $data = json_encode(ArrayUtil::array_column($res, 'deal_id'));
            Yii::app()->rcache->set('_firstp2p_deal_tag_42_44',$data , 1e5);
        }
        return  json_decode($data);
    }

    /**
     * 不能兑换的债权tender
     * @return array
     */
    private function getUserCanNotExchangeTendersPH()
    {
        $exchangeTender = $this->getUserWaitingOrdersTendersPH() ?: [];
        $todayRepayTender = $this->getUserTodayRepayTenderPH() ?: [];
        return array_merge($exchangeTender, $todayRepayTender);
    }

    /**
     * 获取部分还款中的债权记录
     * @return array
     */
    private function getUserPartialRepayTenderPH()
    {
        $sql = " SELECT distinct deal_loan_id FROM ag_wx_partial_repay_detail WHERE user_id = :user_id and status = 1 and repay_status = 0 ";
        $partialRepayTender = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        if (!$partialRepayTender) {
            return $partialRepayTender;
        }
        $partialRepayTender = ArrayUtil::array_column($partialRepayTender, 'deal_loan_id');
        return $partialRepayTender;
    }
    /**
     * 今天为还款日的tender
     * @return array
     */
    private function getUserTodayRepayTenderPH()
    {
        return [];
        $sql = " SELECT distinct deal_loan_id FROM firstp2p_deal_loan_repay WHERE loan_user_id = :user_id AND `type` = 1 AND `time` =  " . strtotime(date('Y-m-d 16:00:00'));
        $todayRepayTender = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        if (!$todayRepayTender) {
            return $todayRepayTender;
        }
        $todayRepayTender = ArrayUtil::array_column($todayRepayTender, 'deal_loan_id');
        return $todayRepayTender;
    }


    /**1
     * 获取待处理订单涉及的tender
     * @return array
     * TODO ok
     */
    public function getUserWaitingOrdersTendersPH()
    {
        $tenders = [];
        $sql = "select tender_id from firstp2p_debt_exchange_log where user_id=:user_id AND status = 1 ";
        $waitingOrders = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->queryAll();
        if (!$waitingOrders) {
            return $tenders;
        }
        $tenders = ArrayUtil::array_column($waitingOrders, 'tender_id');
        return $tenders;
    }


    /***************************普惠相关***end*******************************/



    /**
     * 禁止黑名单
     * @param int $type
     * @return array
     */
    public function getDisableBorrow($type = 1)
    {
        $sql = "select deal_id from ag_wx_debt_black_list where `type` = {$type} AND status = 1 ";
        $blackBorrow = Yii::app()->db->createCommand($sql)->queryAll() ?: [];
        if (!$blackBorrow) {
            return $blackBorrow;
        }
        $blackBorrow = ArrayUtil::array_column($blackBorrow, 'deal_id');
        return $blackBorrow;
    }


    /**
     * 兑换提交
     * @param $data
     * @return array
     */
    public function debtOrderCommit($data)
    {
        $returnData = [
            'data' => [],
            'code' => 0,
            'info' => 'success'
        ];
        //请求参数整理
        $tenderIds     = isset($data['ids']) ? json_decode($data['ids'], true) : [];
        $phTenderIds   = isset($data['wiseIds']) ? json_decode($data['wiseIds'], true) : [];
        $amount        = floatval($data['amount']) ? floatval($data['amount']) : 0;
        $orderNum      = strval($data['orderNumber']) ?: '';
        $condition     = $ph_condition = ' AND t.black_status = 1 ';
        if (isset($data['is_special'])) {
            $condition   = $ph_condition = ' ';
        }
        if (!empty($tenderIds)) {
            $tenderIds = count($tenderIds) > 1 ? implode(',', $tenderIds) : current($tenderIds);
            $condition = " AND t.id in  ($tenderIds) ";
        }
        if (!empty($phTenderIds)) {
            $phTenderIds = count($phTenderIds) > 1 ? implode(',', $phTenderIds) : current($phTenderIds);
            $ph_condition = " AND t.id in  ($phTenderIds) ";
        }
        $specialMoney1 = 0;
        $specialMoney2 = 0;
        $saveTender1   = [];
        $saveTender2   = [];
        $exchange = [];
        if (!empty($tenderIds)) {
            Yii::app()->db->beginTransaction();
            //获取tender对应信息
            $this->isCheckExchangeCommit = true;
            $canDebtTendersInfo          = $this->getUserCanDebtTenders($condition);
            //校验数据
            $tenderIdsArray = explode(',', $tenderIds);
            //非特殊兑换才校验这个
            if (count($tenderIdsArray) !== count($canDebtTendersInfo)) {
                $returnData['code'] = 2016;
                return $returnData;
            }
            //构建每笔tender消耗金额

            foreach ($canDebtTendersInfo as $key => $orderTender) {
                if ($amount > 0) {
                    $saveTender1[$key]['type']         = 1;
                    $saveTender1[$key]['tender_id']    = $orderTender['id'];
                    $saveTender1[$key]['name']         = $orderTender['name'];
                    $saveTender1[$key]['borrow_id']    = $orderTender['borrow_id'];
                    $saveTender1[$key]['debt_account'] = min($orderTender['account'], $amount);
                    $amount                            = round($amount - $orderTender['account'], 2);
                    $specialMoney1                     = round($orderTender['account'] - $saveTender1[$key]['debt_account'], 2);

                    $d['name']          = $saveTender1[$key]['name'];
                    $d['account']         = $saveTender1[$key]['debt_account'];
                    $d['orderNumber']     = $orderNum;
                    $d['status']         = 1;
                    $exchange[$orderNum][] = $d;
                }
            }
            Yii::log('save tender 1 data ' . print_r($saveTender1, true), 'info', __FUNCTION__);
        }

        if (!empty($phTenderIds)) {
            Yii::app()->phdb->beginTransaction();

            //获取tender对应信息
            $this->isCheckExchangeCommit = true;
            $canDebtPhTendersInfo      = $this->getUserCanDebtTendersPH($ph_condition);
            //校验数据
            $phTenderIdsArray = explode(',', $phTenderIds);
            if (count($phTenderIdsArray) !== count($canDebtPhTendersInfo)) {
                Yii::log('save phTenderIds count($phTenderIdsArray) : ' . count($phTenderIdsArray) . ' count($canDebtPhTendersInfo):' . count($canDebtPhTendersInfo), 'error', __FUNCTION__);
                $returnData['code'] = 2016;
                return $returnData;
            }

            //构建每笔tender消耗金额
            foreach ($canDebtPhTendersInfo as $key => $orderTender) {
                if ($amount > 0) {
                    $saveTender2[$key]['type']         = 2;
                    $saveTender2[$key]['tender_id']    = $orderTender['id'];
                    $saveTender2[$key]['name']         = $orderTender['name'];
                    $saveTender2[$key]['borrow_id']    = $orderTender['borrow_id'];
                    $saveTender2[$key]['debt_account'] = min($orderTender['account'], $amount);
                    $amount                            = round($amount - $orderTender['account'], 2);
                    $specialMoney2                     = round($orderTender['account'] - $saveTender2[$key]['debt_account'], 2);

                    $d['name']          = $saveTender2[$key]['name'];
                    $d['account']         = $saveTender2[$key]['debt_account'];
                    $d['orderNumber']     = $orderNum;
                    $d['status']         = 1;
                    $exchange[$orderNum][] = $d;
                }
            }
            Yii::log('save tender 2 data ' . print_r($saveTender2, true), 'info', __FUNCTION__);
        }

        //处理完后依然剩余金额 暂定不满足，返回异常
        if ($amount > 1) {
            Yii::log('amount > 1  amount:' . $amount, 'error', __FUNCTION__);
            $returnData['code'] = 2001;
            return $returnData;
        }
        //剩余金额不得少于100
        if (($specialMoney1 > 0 && $specialMoney1 < 100) || ($specialMoney2 > 0 && $specialMoney2 < 100)) {
            $returnData['code'] = 2008;
            return $returnData;
        }

        if ($saveTender1 && $saveTender2) {
            $result1 = $this->saveDebtOrders($saveTender1, $orderNum);
            $result2 = $this->saveDebtOrdersPH($saveTender2, $orderNum);

            if (!$result1 || !$result2) {
                Yii::app()->db->rollback();
                Yii::app()->phdb->rollback();
                $returnData['code'] = 100;
                return $returnData;
            }
            Yii::app()->db->commit();
            Yii::app()->phdb->commit();
            $returnData['data'] = $exchange;
            return $returnData;
        }
        if ($saveTender1) {
            $result = $this->saveDebtOrders($saveTender1, $orderNum);
            if (!$result) {
                Yii::app()->db->rollback();
                $returnData['code'] = 100;
                return $returnData;
            }

            Yii::app()->db->commit();
            $returnData['data'] = $exchange;
            return $returnData;
        }
        if ($saveTender2) {
            $result = $this->saveDebtOrdersPH($saveTender2, $orderNum);
            if (!$result) {
                Yii::app()->phdb->rollback();
                $returnData['code'] = 100;
                return $returnData;
            }
            Yii::app()->phdb->commit();
            $returnData['data'] = $exchange;
            return $returnData;
        }
        Yii::log('save error : save tender 1 ' . print_r($saveTender1, true) . ',save tender 2' . print_r($saveTender2, true), 'error', __FUNCTION__);

        $returnData['code'] = 2001;
        return $returnData;
    }

    /**
     * 先锋用
     * @param $data
     * @return array
     */
    public function debtOrderCommitPre($data)
    {
        $returnData = [
            'data' => [],
            'code' => 0,
            'info' => 'success'
        ];
        //请求参数整理
        $tenderIds     = isset($data['ids']) ? json_decode($data['ids'], true) : [];
        $phTenderIds   = isset($data['wiseIds']) ? json_decode($data['wiseIds'], true) : [];
        $amount        = floatval($data['amount']) ? floatval($data['amount']) : 0;
        $orderNum      = strval($data['orderNumber']) ?: '';
        $order_info    = isset($data['order_info']) ? json_decode($data['order_info'], true) : [];
        //订单信息校验
        if (empty($order_info) || empty($data['order_sn'])) {
            $returnData['code'] = 1002;
            return $returnData;
        }

        $condition     = $ph_condition = ' AND t.black_status = 1 ';
        if (isset($data['is_special'])) {
            $condition   = $ph_condition = ' ';
        }
        if (!empty($tenderIds)) {
            $tenderIds = count($tenderIds) > 1 ? implode(',', $tenderIds) : current($tenderIds);
            $condition = " AND t.id in  ($tenderIds) ";
        }
        if (!empty($phTenderIds)) {
            $phTenderIds = count($phTenderIds) > 1 ? implode(',', $phTenderIds) : current($phTenderIds);
            $ph_condition = " AND t.id in  ($phTenderIds) ";
        }
        $specialMoney1 = 0;
        $specialMoney2 = 0;
        $saveTender1   = [];
        $saveTender2   = [];
        $exchange = [];
        if (!empty($tenderIds)) {
            Yii::app()->db->beginTransaction();
            //获取tender对应信息
            $this->isCheckExchangeCommit = true;
            $canDebtTendersInfo          = $this->getUserCanDebtTenders($condition);
            //校验数据
            $tenderIdsArray = explode(',', $tenderIds);
            //非特殊兑换才校验这个
            if (count($tenderIdsArray) !== count($canDebtTendersInfo)) {
                $returnData['code'] = 2016;
                return $returnData;
            }
            //构建每笔tender消耗金额

            foreach ($canDebtTendersInfo as $key => $orderTender) {
                if ($amount > 0) {
                    $saveTender1[$key]['type']         = 1;
                    $saveTender1[$key]['tender_id']    = $orderTender['id'];
                    $saveTender1[$key]['name']         = $orderTender['name'];
                    $saveTender1[$key]['borrow_id']    = $orderTender['borrow_id'];
                    $saveTender1[$key]['debt_account'] = min($orderTender['account'], $amount);
                    $amount                            = round($amount - $orderTender['account'], 2);
                    $specialMoney1                     = round($orderTender['account'] - $saveTender1[$key]['debt_account'], 2);

                    $d['name']          = $saveTender1[$key]['name'];
                    $d['account']         = $saveTender1[$key]['debt_account'];
                    $d['orderNumber']     = $orderNum;
                    $d['status']         = 1;
                    $exchange[$orderNum][] = $d;
                }
            }
            Yii::log('save tender 1 data ' . print_r($saveTender1, true), 'info', __FUNCTION__);
        }

        if (!empty($phTenderIds)) {
            Yii::app()->phdb->beginTransaction();

            //获取tender对应信息
            $this->isCheckExchangeCommit = true;
            $canDebtPhTendersInfo      = $this->getUserCanDebtTendersPH($ph_condition);
            //校验数据
            $phTenderIdsArray = explode(',', $phTenderIds);
            if (count($phTenderIdsArray) !== count($canDebtPhTendersInfo)) {
                Yii::log('save phTenderIds count($phTenderIdsArray) : ' . count($phTenderIdsArray) . ' count($canDebtPhTendersInfo):' . count($canDebtPhTendersInfo), 'error', __FUNCTION__);
                $returnData['code'] = 2016;
                return $returnData;
            }

            //构建每笔tender消耗金额
            foreach ($canDebtPhTendersInfo as $key => $orderTender) {
                if ($amount > 0) {
                    $saveTender2[$key]['type']         = 2;
                    $saveTender2[$key]['tender_id']    = $orderTender['id'];
                    $saveTender2[$key]['name']         = $orderTender['name'];
                    $saveTender2[$key]['borrow_id']    = $orderTender['borrow_id'];
                    $saveTender2[$key]['debt_account'] = min($orderTender['account'], $amount);
                    $amount                            = round($amount - $orderTender['account'], 2);
                    $specialMoney2                     = round($orderTender['account'] - $saveTender2[$key]['debt_account'], 2);

                    $d['name']          = $saveTender2[$key]['name'];
                    $d['account']         = $saveTender2[$key]['debt_account'];
                    $d['orderNumber']     = $orderNum;
                    $d['status']         = 0;
                    $exchange[$orderNum][] = $d;
                }
            }
            Yii::log('save tender 2 data ' . print_r($saveTender2, true), 'info', __FUNCTION__);
        }

        //处理完后依然剩余金额 暂定不满足，返回异常
        if ($amount > 1) {
            Yii::log('amount > 1  amount:' . $amount, 'error', __FUNCTION__);
            $returnData['code'] = 2001;
            return $returnData;
        }
        //剩余金额不得少于100
        if (($specialMoney1 > 0 && $specialMoney1 < 100) || ($specialMoney2 > 0 && $specialMoney2 < 100)) {
            $returnData['code'] = 2008;
            return $returnData;
        }

        if ($saveTender1 && $saveTender2) {
            $result1 = $this->saveDebtOrdersXf($saveTender1, $orderNum, $data['order_info'], $data['order_sn']);
            $result2 = $this->saveDebtOrdersPHXf($saveTender2, $orderNum, $data['order_info'], $data['order_sn']);

            if (!$result1 || !$result2) {
                Yii::app()->db->rollback();
                Yii::app()->phdb->rollback();
                $returnData['code'] = 100;
                return $returnData;
            }

            Yii::app()->db->commit();
            Yii::app()->phdb->commit();
            $returnData['data'] = $exchange;
            return $returnData;
        }
        if ($saveTender1) {
            $result = $this->saveDebtOrdersXf($saveTender1, $orderNum, $data['order_info'], $data['order_sn']);
            if (!$result) {
                Yii::app()->db->rollback();
                $returnData['code'] = 100;
                return $returnData;
            }

            Yii::app()->db->commit();
            $returnData['data'] = $exchange;
            return $returnData;
        }
        if ($saveTender2) {
            $result = $this->saveDebtOrdersPHXf($saveTender2, $orderNum, $data['order_info'], $data['order_sn']);
            if (!$result) {
                Yii::app()->phdb->rollback();
                $returnData['code'] = 100;
                return $returnData;
            }
            Yii::app()->phdb->commit();
            $returnData['data'] = $exchange;
            return $returnData;
        }
        Yii::log('save error : save tender 1 ' . print_r($saveTender1, true) . ',save tender 2' . print_r($saveTender2, true), 'error', __FUNCTION__);

        $returnData['code'] = 2001;
        return $returnData;
    }



    /**
     * 保存兑换债权
     * @param $tenders
     * @param $orderNum
     * @return bool
     * TODO ok
     */
    public function saveDebtOrders($tenders, $orderNum)
    {
        if (empty($tenders)) {
            return false;
        }
        $now = time();
        foreach ($tenders as $tender) {
            $debtOrder = new DebtExchangeLog();
            $debtOrder->user_id = $this->user_id;
            $debtOrder->order_id = $orderNum;
            $debtOrder->borrow_id = $tender['borrow_id'] ?: 0;
            $debtOrder->tender_id = $tender['tender_id'] ?: 0;
            $debtOrder->debt_account = $tender['debt_account'];
            $debtOrder->addtime = $now;
            $debtOrder->status = 1;
            $debtOrder->debt_src = $tender['debt_src'] ?: 1;
            if (!$debtOrder->save()) {
                Yii::log('save debt orders error tenders:' . print_r($tenders, true), 'error', __FUNCTION__);
                return false;
            }
        }
        return true;
    }


    /**
     * 保存兑换债权
     * @param $tenders
     * @param $orderNum
     * @return bool
     * TODO ok
     */
    public function saveDebtOrdersPH($tenders, $orderNum)
    {
        if (empty($tenders)) {
            return false;
        }
        $now = time();
        foreach ($tenders as $tender) {
            $debtOrder = new PHDebtExchangeLog();
            $debtOrder->user_id = $this->user_id;
            $debtOrder->order_id = $orderNum;
            $debtOrder->borrow_id = $tender['borrow_id'] ?: 0;
            $debtOrder->tender_id = $tender['tender_id'] ?: 0;
            $debtOrder->debt_account = $tender['debt_account'];
            $debtOrder->addtime = $now;
            $debtOrder->status = 1;
            $debtOrder->debt_src = $tender['debt_src'] ?: 1;
            if (!$debtOrder->save()) {
                Yii::log('save debt orders error tenders:' . print_r($tenders, true), 'error', __FUNCTION__);
                return false;
            }
        }
        return true;
    }





    /**
     * 保存兑换债权
     * @param $tenders
     * @param $orderNum
     * @param  $order_info
     * @param  $order_sn
     * @return bool
     * TODO ok
     */
    public function saveDebtOrdersXf($tenders, $orderNum, $order_info, $order_sn)
    {
        if (empty($tenders) || empty($order_info) || empty($order_sn)) {
            return false;
        }
        //检验批次号
        $check_ret = DebtExchangeLog::model()->find("order_id='$orderNum' and status in (0,1,2)");
        if($check_ret){
            Yii::log('save debt orders error order_id[$orderNum] error' , 'error', __FUNCTION__);
            return false;
        }

        $now = time();
        foreach ($tenders as $tender) {
            $debtOrder = new DebtExchangeLog();
            $debtOrder->user_id = $this->user_id;
            $debtOrder->order_id = $orderNum;
            $debtOrder->borrow_id = $tender['borrow_id'] ?: 0;
            $debtOrder->tender_id = $tender['tender_id'] ?: 0;
            $debtOrder->debt_account = $tender['debt_account'];
            $debtOrder->order_sn = $order_sn;
            $debtOrder->addtime = $now;
            $debtOrder->status = 0;
            $debtOrder->order_info = $order_info;
            $debtOrder->debt_src = $tender['debt_src'] ?: 1;
            if (!$debtOrder->save()) {
                Yii::log('save debt orders error tenders:' . print_r($tenders, true), 'error', __FUNCTION__);
                return false;
            }
        }
        return true;
    }


    /**
     * 保存兑换债权
     * @param $tenders
     * @param $orderNum
     * @param  $order_info
     * @param  $order_sn
     * @return bool
     * TODO ok
     */
    public function saveDebtOrdersPHXf($tenders, $orderNum, $order_info, $order_sn)
    {
        if (empty($tenders) || empty($order_info) || empty($order_sn)) {
            return false;
        }

        //检验批次号
        $check_ret = PHDebtExchangeLog::model()->find("order_id='$orderNum' and status in (0,1,2)");
        if($check_ret){
            Yii::log('save debt orders error order_id[$orderNum] error' , 'error', __FUNCTION__);
            return false;
        }

        $now = time();
        foreach ($tenders as $tender) {
            $debtOrder = new PHDebtExchangeLog();
            $debtOrder->user_id = $this->user_id;
            $debtOrder->order_id = $orderNum;
            $debtOrder->borrow_id = $tender['borrow_id'] ?: 0;
            $debtOrder->tender_id = $tender['tender_id'] ?: 0;
            $debtOrder->debt_account = $tender['debt_account'];
            $debtOrder->addtime = $now;
            $debtOrder->status = 0;
            $order_info->order_sn = $order_sn;
            $debtOrder->order_info = $order_info;
            $debtOrder->debt_src = $tender['debt_src'] ?: 1;
            if (!$debtOrder->save()) {
                Yii::log('save debt orders error tenders:' . print_r($tenders, true), 'error', __FUNCTION__);
                return false;
            }
        }
        return true;
    }





    /***************************以下是公用逻辑*******************************/

    /**
     * 获取兑换记录状态
     * @param $orders
     * @return array
     */
    public function getOrderStatus($ordersNum)
    {
        $returnData = [
            'data' => [],
            'code' => 100,
            'info' => '订单不存在，请核对单号'
        ];
        if (empty($ordersNum)) {
            $returnData['info'] = '单号不能为空';
            return $returnData;
        }
        $get1 = $get2 = [];
        //获取数据
        $criteria = new CDbCriteria;
        $criteria->select = ['tender_id', 'debt_account', 'order_id', 'status'];
        $criteria->addIncondition("order_id", $ordersNum);
        $orders = DebtExchangeLog::model()->findAll($criteria);
        if (!empty($orders)) {
            $tenderId = [];

            //获取tender
            foreach ($orders as $order) {
                $tenderId[] = $order->tender_id;
            }

            //获取项目名称
            $tenderSql = "select t.id,b.name AS name FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id  where t.id in (" . implode(',', $phTenderId) . ")  ";
            $tenders = Yii::app()->db->createCommand($tenderSql)->queryAll();
            foreach ($tenders as $tender) {
                $tem1[$tender['id']] = $tender['name'];
            }

            $debtStatusToOrderStatus = [
                0 => 2, //发起
                1 => 1, //回调成功
                2 => 1, //转让完成
                3 => 0, //转让失败
                4 => 0, //过期也给失败
            ];
            //处理返回数据
            foreach ($orders as $order) {
                $d['name']             = $tem1[$order->tender_id];
                $d['account']         = $order->debt_account;
                $d['orderNumber']     = $order->order_id;
                $d['status']         = $debtStatusToOrderStatus[$order->status];
                $get1[$order->order_id][] = $d;
            }
        }

        //获取数据
        $criteria = new CDbCriteria;
        $criteria->select = ['tender_id', 'debt_account', 'order_id', 'status'];
        $criteria->addIncondition("order_id", $ordersNum);
        $ph_orders = PHDebtExchangeLog::model()->findAll($criteria);
        if (!empty($ph_orders)) {
            $phTenderId = [];

            //获取tender
            foreach ($ph_orders as $order) {
                $phTenderId[] = $order->tender_id;
            }
            if ($phTenderId) {
                //获取项目名称
                $tenderSql = "select t.id,b.name AS name FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id  where t.id in (" . implode(',', $phTenderId) . ")  ";
                $tenders = Yii::app()->phdb->createCommand($tenderSql)->queryAll();
                foreach ($tenders as $tender) {
                    $tem2[$tender['id']] = $tender['name'];
                }
            }

            $debtStatusToOrderStatus = [
                0 => 2, //发起
                1 => 1, //回调成功
                2 => 1, //转让完成
                3 => 0, //转让失败
                4 => 0, //过期也给失败
            ];
            //处理返回数据
            foreach ($ph_orders as $order) {
                $d['name']             = $tem2[$order->tender_id];
                $d['account']         = $order->debt_account;
                $d['orderNumber']     = $order->order_id;
                $d['status']         = $debtStatusToOrderStatus[$order->status];
                $get2[$order->order_id][] = $d;
            }
        }


        $returnData['data'] = $get1 + $get2;
        $returnData['code'] = 0;
        $returnData['info'] = 'success';
        return $returnData;
    }

    /**
     * 先锋兑换订单信息
     * @param $ordersNum
     * @return array
     */
    public function getXfOrderStatus($ordersNum)
    {
        $returnData = [
            'data' => [],
            'code' => 100,
            'info' => '订单不存在，请核对单号'
        ];
        if (empty($ordersNum)) {
            $returnData['info'] = '单号不能为空';
            return $returnData;
        }
        $get1 = $get2 = [];
        //获取数据
        $criteria = new CDbCriteria;
        $criteria->select = ['tender_id', 'debt_account', 'order_id', 'status'];
        $criteria->condition = " order_id = '$ordersNum' ";
        $orders = DebtExchangeLog::model()->findAll($criteria);
        if (!empty($orders)) {
            $tenderId = [];

            //获取tender
            foreach ($orders as $order) {
                $tenderId[] = $order->tender_id;
            }

            //获取项目名称
            $tenderSql = "select t.id,b.name AS name FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id  where t.id in (" . implode(',', $tenderId) . ")  ";
            $tenders = Yii::app()->db->createCommand($tenderSql)->queryAll();
            foreach ($tenders as $tender) {
                $tem1[$tender['id']] = $tender['name'];
            }

            $debtStatusToOrderStatus = [
                0 => 2, //发起
                1 => 3, //回调成功
                2 => 1, //转让完成
                3 => 0, //转让失败
                4 => 0, //过期也给失败
            ];
            //处理返回数据
            foreach ($orders as $order) {
                $d['name']             = $tem1[$order->tender_id];
                $d['account']         = $order->debt_account;
                $d['orderNumber']     = $order->order_id;
                $d['status']         = $debtStatusToOrderStatus[$order->status];
                $get1[$order->order_id][] = $d;
            }
        }

        //获取数据
        $criteria = new CDbCriteria;
        $criteria->select = ['tender_id', 'debt_account', 'order_id', 'status'];
        $criteria->condition = " order_id = '$ordersNum' ";
        $ph_orders = PHDebtExchangeLog::model()->findAll($criteria);
        if (!empty($ph_orders)) {
            $phTenderId = [];

            //获取tender
            foreach ($ph_orders as $order) {
                $phTenderId[] = $order->tender_id;
            }
            if ($phTenderId) {
                //获取项目名称
                $tenderSql = "select t.id,b.name AS name FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id  where t.id in (" . implode(',', $phTenderId) . ")  ";
                $tenders = Yii::app()->phdb->createCommand($tenderSql)->queryAll();
                foreach ($tenders as $tender) {
                    $tem2[$tender['id']] = $tender['name'];
                }
            }

            $debtStatusToOrderStatus = [
                0 => 2, //发起
                1 => 1, //回调成功
                2 => 1, //转让完成
                3 => 0, //转让失败
                4 => 0, //过期也给失败
            ];
            //处理返回数据
            foreach ($ph_orders as $order) {
                $d['name']             = $tem2[$order->tender_id];
                $d['account']         = $order->debt_account;
                $d['orderNumber']     = $order->order_id;
                $d['status']         = $debtStatusToOrderStatus[$order->status];
                $get2[$order->order_id][] = $d;
            }
        }


        $returnData['data'] = $get1 + $get2;
        $returnData['code'] = 0;
        $returnData['info'] = 'success';
        return $returnData;
    }


    /**
     * 对账脚本，返回订单对应兑换成功的金额
     * @param $startTime
     * @param $endTime
     * @param $page
     * @param $limit
     * @param $onlyGetTotalPage
     * @return array
     */
    public function checkOrdersAccount($startTime, $endTime, $page, $limit, $onlyGetTotalPage)
    {
        $returnData = [
            'data' => [
                'totalPage' => 0,
                'totalAccount' => 0,
                'list' => [],
            ],
            'code' => 0,
            'info' => 'success'
        ];

        if ($onlyGetTotalPage == 1) {
            $countSql = "select count(distinct(order_id)) as totalNum,sum(debt_account) as totalAccount from firstp2p_debt_exchange_log where addtime > {$startTime} AND addtime <= {$endTime} AND status in (1,2) ";
            $totalOrders = Yii::app()->db->createCommand($countSql)->queryRow();
            $returnData['data']['totalPage'] = ceil($totalOrders['totalNum'] / $limit);
            $returnData['data']['totalAccount'] = $totalOrders['totalAccount'] ?: 0;
        }
        $offset = ($page - 1) * $limit;
        //获取数据
        $criteria = new CDbCriteria;
        $criteria->select = " sum(debt_account) as debt_account,order_id ";
        $criteria->condition = " addtime > {$startTime} AND addtime <= {$endTime} AND status in (1,2) ";
        $criteria->group = " order_id ";
        $criteria->limit = $limit;
        $criteria->offset = $offset;
        $orders = DebtExchangeLog::model()->findAll($criteria);
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $tmp['account'] = $order['debt_account'];
                $tmp['orderNumber'] = $order['order_id'];
                $returnData['data']['list'][] = $tmp;
            }
        }

        if ($onlyGetTotalPage == 1) {
            $countSql = "select count(distinct(order_id)) as totalNum,sum(debt_account) as totalAccount from firstp2p_debt_exchange_log where addtime > {$startTime} AND addtime <= {$endTime} AND status in (1,2) ";
            $totalOrders = Yii::app()->phdb->createCommand($countSql)->queryRow();
            $returnData['data']['totalPage'] += ceil($totalOrders['totalNum'] / $limit);
            $returnData['data']['totalAccount'] += $totalOrders['totalAccount'] ?: 0;
            return $returnData;
        }
        $offset = ($page - 1) * $limit;
        //获取数据
        $criteria = new CDbCriteria;
        $criteria->select = " sum(debt_account) as debt_account,order_id ";
        $criteria->condition = " addtime > {$startTime} AND addtime <= {$endTime} AND status in (1,2) ";
        $criteria->group = " order_id ";
        $criteria->limit = $limit;
        $criteria->offset = $offset;
        $orders = PHDebtExchangeLog::model()->findAll($criteria);
        if (!empty($orders)) {
            foreach ($orders as $order) {
                $tmp['account'] = $order['debt_account'];
                $tmp['orderNumber'] = $order['order_id'];
                $returnData['data']['list'][] = $tmp;
            }
        }
        $res = [];
        foreach ($returnData['data']['list'] as $val) {
            $order_id = $val['orderNumber'];
            if (isset($res[$order_id])) {
                $res[$order_id]['account'] += $val['account'];
            } else {
                $res[$order_id] = $val;
            }
        }
        $returnData['data']['list'] = $res;
        return $returnData;
    }

    /**
     * 统计相关
     */
    public function getStatisticsData()
    {
        $returnData = [
            'total_quite_number' => 0,
            'yesterday_quite_number' => 0,
        ];
        $quiteSql = "select quit_number,`time` from wx_statistics_confirmation order by `time` desc limit 2 ";
        $quiteData = Yii::app()->db->createCommand($quiteSql)->queryAll();
        if (!empty($quiteData)) {
            $todayData = isset($quiteData[0]) ? $quiteData[0]['quit_number'] : 0;
            $yesterdayData = isset($quiteData[1]) ? $quiteData[1]['quit_number'] : 0;
            $returnData['total_quite_number'] = $todayData;
            $returnData['yesterday_quite_number'] = $yesterdayData - $todayData;
        }
        //尊享总数
        $zx_total_user_num_sql = "select sum(debt_account) as zx_total_exchange_num,count(DISTINCT user_id) as zx_total_exchange_user from firstp2p_debt_exchange_log where status in (1,2)";
        $returnData += Yii::app()->db->createCommand($zx_total_user_num_sql)->queryRow();
        //普惠总数
        $ph_total_user_num_sql = "select sum(debt_account) as ph_total_exchange_num,count(DISTINCT user_id) as ph_total_exchange_user from firstp2p_debt_exchange_log where status in (1,2)";
        $returnData += Yii::app()->phdb->createCommand($ph_total_user_num_sql)->queryRow();
        //尊享当日
        $zx_today_user_num_sql = "select sum(debt_account) as zx_today_exchange_num,count(DISTINCT user_id) as zx_today_exchange_user from firstp2p_debt_exchange_log where status in (1,2) and addtime >= " . strtotime('midnight');
        $returnData += Yii::app()->db->createCommand($zx_today_user_num_sql)->queryRow();
        //普惠当日
        $ph_today_user_num_sql = "select sum(debt_account) as ph_today_exchange_num,count(DISTINCT user_id) as ph_today_exchange_user from firstp2p_debt_exchange_log where status in (1,2) and addtime >= " . strtotime('midnight');
        $returnData += Yii::app()->phdb->createCommand($ph_today_user_num_sql)->queryRow();
        foreach ($returnData as &$returnDatum) {
            if (!$returnDatum) {
                $returnDatum = 0;
            }
        }
        return $returnData;
    }

    /**
     * todo 下车
     * 获取不可下车的tender
     * @return array
     */
    private function getDisableTenderId()
    {
        $debtStatusIn56Sql = " select tender_id from firstp2p_debt where user_id = :user_id and status in (5,6) ";
        $disableTenders = Yii::app()->phdb->createCommand($debtStatusIn56Sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        if (!empty($disableTenders)) {
            $disableTenders = ArrayUtil::array_column($disableTenders, 'tender_id');
        }
        return $disableTenders;
    }

    /**
     * 过期指定债权
     * @param array $tender_id_arr
     * @return bool
     */
    public function expDebt(array $tender_id_arr)
    {
        if (empty($tender_id_arr)) {
            return true;
        }
        $debtphInfo = Yii::app()->phdb->createCommand("select id from firstp2p_debt  where user_id = :user_id and tender_id in (" . implode(',', $tender_id_arr) . ") and status = 1  ")->bindValues([':user_id' => $this->user_id])->queryAll();
        if (empty($debtphInfo)) {
            return  true;
        }
        foreach ($debtphInfo as $key => $value) {
            $cancelDebtRet = DebtGardenYoujieQuestionService::getInstance()->CancelDebt(['debt_id' => $value['id'], 'status' => 4, 'products' => 2, 'checkuser' => 2]);
            if ($cancelDebtRet['code']) {
                return false;
            }
        }
        return true;
    }

    /**
     * todo 下车
     * 获取用户可转让债权
     * @return mixed
     */
    public function getUserSpecialCanDebtTendersPH($condition = '')
    {
        $disableBorrow  = $this->getDisableBorrow(2);
        if (!empty($disableBorrow)) {
            $condition .= " and fd.id  not in (" . implode(',', $disableBorrow) . ") ";
        }

        $disableExchangeTenders = array_merge($this->getUserCanNotExchangeTendersPH() ?: [], $this->getDisableTenderId());
        if (!empty($disableExchangeTenders)) {
            $condition .= " and fdl.id  not in (" . implode(',', $disableExchangeTenders) . ") ";
        }

        $forUpdate = '';
        if ($this->isCheckExchangeCommit) {
            $forUpdate = ' FOR UPDATE ';
        }

        $advisory_ids = $this->getUserSpecialAdvisoryId();

        if (empty($advisory_ids)) {
            return [];
        }
        $condition .= " and fda.id  in (" . implode(',', $advisory_ids) . ") ";

        $select_sql = " SELECT
                fdl.id,
                fdl.wait_capital as account,
                fdl.deal_id AS borrow_id ,
                fd.name 
            FROM firstp2p_deal_load fdl
            LEFT JOIN firstp2p_deal_tag dt ON fdl.deal_id =dt.deal_id AND dt.tag_id IN (42,44)
            left join firstp2p_deal fd on fdl.deal_id = fd.id
            left join firstp2p_deal_agency fda on fd.advisory_id=fda.id
            WHERE
                fdl.user_id=:user_id and 
                fdl.wait_capital > 0 and
                dt.tag_id IS NULL 
             {$condition}  {$forUpdate} ";
        $tenders =  Yii::app()->phdb->createCommand($select_sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];

        //过期掉债转中的债权
        if ($tenders) {
            if (!$this->expDebt(ArrayUtil::array_column($tenders, 'id'))) {
                return [];
            }
        }

        return $tenders;
    }


    /**
     * todo 下车
     * 获取用户待收本机及可转让tender条数
     * @return array
     */
    public function getUserSpecialSumAccountAndTotalTenderPH()
    {
        $returnData = [
            'total_account' => 0,
            'total_tenders' => 0,
        ];

        $condition = '';
        $disableBorrow  = $this->getDisableBorrow(2);
        if (!empty($disableBorrow)) {
            $condition = " and fd.id  not in (" . implode(',', $disableBorrow) . ") ";
        }

        $disableExchangeTenders = array_merge($this->getUserCanNotExchangeTendersPH() ?: [], $this->getDisableTenderId());

        if (!empty($disableExchangeTenders)) {
            $condition .= " and fdl.id  not in (" . implode(',', $disableExchangeTenders) . ")";
        }
        //指定资方
        $advisory_ids = $this->getUserSpecialAdvisoryId();
        if (empty($advisory_ids)) {
            return $returnData;
        }
        $condition .= " and fda.id  in (" . implode(',', $advisory_ids) . ")";

        $sum_count_sql = "SELECT sum(fdl.wait_capital) AS total_account,count(1) AS total_tenders 
            FROM firstp2p_deal_load fdl
            LEFT JOIN firstp2p_deal_tag dt ON fdl.deal_id =dt.deal_id AND dt.tag_id IN (42,44)
            left join firstp2p_deal fd on fdl.deal_id = fd.id
            left join firstp2p_deal_agency fda on fd.advisory_id=fda.id
            WHERE
                fdl.user_id=:user_id and 
                fdl.wait_capital > 0 and
                dt.tag_id IS NULL  " . $condition;
        $sum_count = Yii::app()->phdb->createCommand($sum_count_sql)->bindValues([':user_id' => $this->user_id])->queryRow();
        //剔除已兑换的金额
        $returnData['total_account'] = $sum_count['total_account'] ?: 0;
        $returnData['total_tenders'] = $sum_count['total_tenders'] ?: 0;
        return $returnData;
    }

    private function getUserSpecialAdvisoryId()
    {
        $select_advisory_sql = " SELECT
                fda.name as advisory_name,
                fda.id as advisory_id 
            FROM firstp2p_deal_load fdl
            LEFT JOIN firstp2p_deal_tag dt ON fdl.deal_id =dt.deal_id AND dt.tag_id IN (42,44)
            left join firstp2p_deal fd on fdl.deal_id = fd.id
            left join firstp2p_deal_agency fda on fd.advisory_id=fda.id
            WHERE
                fdl.user_id=:user_id and 
                fdl.wait_capital > 0 and
                dt.tag_id IS NULL and
                fda.name not in ('杭州大树网络技术有限公司','北京掌众金融信息服务有限公司')
            GROUP BY 
                    fda.name,
                    fda.id
            having sum(fdl.wait_capital) <= 500 ";

        $advisory_ids = [];
        $allAdvisory = Yii::app()->phdb->createCommand($select_advisory_sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        //var_dump(Yii::app()->phdb,$allAdvisory,$this->user_id);die;
        if (empty($allAdvisory)) {
            return $advisory_ids;
        }

        $advisory_ids = ArrayUtil::array_column($allAdvisory, 'advisory_id');
        return $advisory_ids;
    }

    /**
     * TODO 下车
     * 兑换提交
     * @param $data
     * @return array
     */
    public function specialDebtOrderCommit($data)
    {
        $returnData = [
            'data' => [],
            'code' => 0,
            'info' => 'success'
        ];
        //请求参数整理
        $orderNum      = strval($data['orderNumber']) ?: '';
        $saveTender   = [];
        $exchange = [];

        Yii::app()->phdb->beginTransaction();
        //获取tender对应信息
        $this->isCheckExchangeCommit = true;
        $canDebtPhTendersInfo      = $this->getUserSpecialCanDebtTendersPH();
        //校验数据

        if (empty($canDebtPhTendersInfo)) {
            Yii::app()->phdb->rollback();
            Yii::log('orderNum :' . $orderNum . ',specialDebtOrderCommit canDebtPhTendersInfo is empty ', 'error', __FUNCTION__);
            $returnData['code'] = 2020;
            return $returnData;
        }
        $total = 0;
        //构建每笔tender消耗金额
        $now = time();

        $insertSql = " insert into firstp2p_debt_exchange_log (user_id,order_id,tender_id,borrow_id,debt_account,addtime,status,debt_src) values ";

        foreach ($canDebtPhTendersInfo as $key => $orderTender) {
            $insertSql .= " ({$this->user_id},'$orderNum',{$orderTender['id']},{$orderTender['borrow_id']},{$orderTender['account']},{$now},1,4),";

            $total += $orderTender['account'];
            $d['name']          = $orderTender['name'];
            $d['account']         = $orderTender['account'];
            $d['orderNumber']     = $orderNum;
            $d['status']         = 1;
            $exchange[$orderNum][] = $d;
        }
        Yii::log('orderNum :' . $orderNum . ', debt account:' . $total . ', save tender  data ' . print_r($canDebtPhTendersInfo, true), 'info', __FUNCTION__);
        //echo $insertSql;die;
        $result = Yii::app()->phdb->createCommand(rtrim($insertSql, ','))->execute();
        if (!$result) {
            Yii::app()->phdb->rollback();
            $returnData['code'] = 2021;
            return $returnData;
        }
        Yii::app()->phdb->commit();
        $returnData['data'] = $exchange;
        return $returnData;
    }

    public function userDebtRollback($order_id, $account)
    {
        $returnData = [
            'data' => [],
            'code' => 100,
            'info' => '',
        ];
        $account = floatval($account);
        $sql = "select * from firstp2p_debt_exchange_log where user_id=:user_id and  order_id in ($order_id) AND status in (1,2) ";
        $totalOrders = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->queryAll();

        if (!$totalOrders) {
            $returnData['info'] = '债权兑换记录不存在，请核实后重试';
            return $returnData;
        }
        $totalAccount = 0.01;
        $i = 0;

        foreach ($totalOrders as $totalOrder) {
            $totalAccount += $totalOrder['debt_account'];
            if ($totalOrder['return_status'] == 1) {
                $i += 1;
            }
        }
        if (count($totalOrders) == $i) {
            $returnData['code'] = 0;
            $returnData['info'] = '债权已退还';
            return $returnData;
        }

        if (FunctionUtil::float_bigger($account, $totalAccount, 3)) {
            $returnData['info'] = '债权兑换金额不一致，请联系开发核实';
            return $returnData;
        }

        $doDebtRollBack = ReturnDebtService::getInstance()->processOrder(json_encode(explode(',', $order_id)), 2);
        if ($doDebtRollBack['code']) {
            $returnData['info'] =  $doDebtRollBack['info'];
            $returnData['code'] = $doDebtRollBack['code'];
            return $returnData;
        }
        $returnData['code'] = 0;
        return $returnData;
    }
}
