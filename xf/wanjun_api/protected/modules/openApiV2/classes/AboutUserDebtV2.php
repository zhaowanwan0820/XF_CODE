<?php

class AboutUserDebtV2
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
    public $ph_order_borrow_id = '7369050,7369054,7351484,7350014,7345206,7372135,7372137,7369055,7369052,7351488'; //注意这里是倒叙，后面的靠前排
    public $zx_order_borrow_id = '6211593,6210631,6208094,6204389,6210308,6210307,6210306,6209044,6209043,6204381,6204376,5540742,6201474,6209019,6208265,6209499,6209498,6209497,5520580,5517729'; //注意这里是倒叙，后面的靠前排
    public $shop_app_id = 0;
    public $change_order_id = 0;

    public $is_not_check_white_list = false;

    public $area_id = 0;//专区

    public function __construct($user_id = 0, $appid=0, $area_code='')
    {
        $this->user_id = $user_id;
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
    public function getUserDebtList($data)
    {
        $returnData = [
            'data' => ['total' => 0, 'list' => []],
            'code' => 0,
            'info' => 'success',
        ];
        $page = isset($data['page']) && $data['page'] > 1 ? $data['page'] : 1;
        $limit = isset($data['limit']) ? $data['limit'] : 10;
        //获取记录总条数
        $sum_count = $this->getUserSumAccountAndTotalTender();
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
        $splitPageSql = '';
        if ($this->limit) {
            $splitPageSql = "  limit {$this->limit}  offset {$this->offset} ";
        }

        $notExchangeTenders = $this->getUserCanNotExchangeTenders() ?: [];

        $repayingBorrow = $this->getRepayingDealOrDealLoadId()['deal_id'];

        $disableBorrow = array_merge($repayingBorrow, $this->getDisableBorrow(1));
        if (!empty($disableBorrow)) {
            $condition .= ' and b.id  not in ('.implode(',', $disableBorrow).')';
        }

        if ($this->isCheckExchangeCommit) {
            if (!empty($notExchangeTenders)) {
                $condition .= ' and t.id  not in ('.implode(',', $notExchangeTenders).')';
            }
            $splitPageSql = ' FOR UPDATE ';
        }
        if (!$this->is_from_debt_confirm) {
            $condition .= ' AND t.is_debt_confirm = 1 ';
        }
        $allowListJoin = '';
        if ($allowBorrow = $this->getShopAllowBorrow(1)) {
            $allowListJoin = " left join xf_debt_exchange_deal_allow_list as al on b.id = al.deal_id  ";
            $condition .= " AND al.appid = {$this->shop_app_id} AND  al.status = 1 AND al.type = 1 AND  al.area_id={$this->area_id} ";
        } else {
            return  [];
        }

        $order = ' ORDER BY addtime ASC ';

        if ($this->zx_order_borrow_id) {
            $order = ' ORDER BY FIELD(t.deal_id,'.$this->zx_order_borrow_id.') DESC, addtime ASC ';
        }
        $select_sql = " SELECT  b.deal_type,t.debt_type,t.id,t.wait_capital AS account,b.name AS name ,t.deal_id AS borrow_id ,t.create_time AS addtime,t.black_status,b.buyer_uid  FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id {$allowListJoin} WHERE t.user_id = :user_id  AND t.xf_status = 0 AND   t.wait_capital > 0 AND t.black_status = 1 AND t.debt_status=0 AND b.deal_status = 4  {$condition}  {$order}  {$splitPageSql} ";
        $allTenders = Yii::app()->db->createCommand($select_sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        foreach ($allTenders as &$allTender) {
            $allTender['is_black'] = 1 == $allTender['black_status'] ? 0 : 1;
            $allTender['type'] = 1;
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
        $condition = '';

        $repayingBorrow = $this->getRepayingDealOrDealLoadId()['deal_id'] ?: [];

        $disableBorrow = array_merge($this->getDisableBorrow(1), $repayingBorrow);

        if (!empty($disableBorrow)) {
            $condition = ' AND b.id  not in ('.implode(',', $disableBorrow).')';
        }
        $allowListJoin = '';
        if ($allowBorrow = $this->getShopAllowBorrow(1)) {
            $allowListJoin = " left join xf_debt_exchange_deal_allow_list as al on b.id = al.deal_id  ";
            $condition .= " AND al.appid = {$this->shop_app_id} AND  al.status = 1 AND al.type = 1 AND al.area_id={$this->area_id} ";
        } else {
            return $returnData;
        }

        $sum_count_sql = 'SELECT sum(t.wait_capital) AS total_account,count(1) AS total_tenders FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id '.$allowListJoin.' WHERE t.user_id = :user_id AND  t.xf_status = 0 AND t.wait_capital > 0 AND t.black_status = 1 AND t.debt_status=0 AND b.deal_status = 4 '.$condition;
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
     * 今天为还款日的tender.
     * @return array
     */
    private function getUserTodayRepayTender()
    {
        return [];
        $sql = ' SELECT DISTINCT deal_loan_id FROM firstp2p_deal_loan_repay WHERE loan_user_id = :user_id AND `type` = 1 AND `time` =  '.strtotime(date('Y-m-d 16:00:00'));
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
            'deal_id' => [], 'deal_loan_id' => [], 'user_id' => [],
        ];
        $sql = 'select deal_id,deal_loan_id,repay_type,loan_user_id from ag_wx_repayment_plan where status in (0,1,2)  ';
        $repayingDeal = Yii::app()->db->createCommand($sql)->queryAll() ?: [];
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
        $sql = 'select tender_id from firstp2p_debt_exchange_log where user_id=:user_id AND status = 1 ';
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
     * 获取用户可兑换债权列表.
     * @param $data
     * @return mixed
     */
    public function getUserDebtListPH($data)
    {
        $returnData = [
            'data' => ['total' => 0, 'list' => []],
            'code' => 0,
            'info' => 'success',
        ];

       

        $page = isset($data['page']) && $data['page'] > 1 ? $data['page'] : 1;
        $limit = isset($data['limit']) ? $data['limit'] : 10;
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
     * 获取用户可转让债权.
     * @return mixed
     */
    public function getUserCanDebtTendersPH($condition = '')
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

        $disableBorrow = array_merge($this->getDisableBorrow(2), $this->getZDXBorrow());
        if (!empty($disableBorrow)) {
            $condition .= ' and b.id  not in ('.implode(',', $disableBorrow).') ';
        }
        $advisory_condition = '';
        //咨询方的
        if ($advisoryId = $this->getPhDisableAdvisoryId()) {
            //$advisory_condition = ' AND  b.advisory_id  in (' . implode(',', [153, 215, 397, 399]) . ') and b.product_class_type in (5,232,202,223,316)  ';
            $advisory_condition = ' OR ( b.advisory_id  in ('.implode(',', $advisoryId).') and b.product_class_type in (5,232,202,223,316)  )';
        }
        $disableExchangeTenders = $this->getUserCanNotExchangeTendersPH() ?: [];

        if ($this->isCheckExchangeCommit) {
            if (!empty($disableExchangeTenders)) {
                $condition .= ' and t.id  not in ('.implode(',', $disableExchangeTenders).') ';
            }
            $splitPageSql = ' FOR UPDATE ';
        }

        if (!$this->is_from_debt_confirm) {
            $condition .= ' AND t.is_debt_confirm = 1 ';
        }
        $allowListJoin = '';
        if ($this->is_not_check_white_list) {
        } elseif ($this->getShopAllowBorrow(2)) {
            //$condition .= " AND b.id  IN (".implode(',',$allowBorrow).") ";
            $allowListJoin = " left join firstp2p.xf_debt_exchange_deal_allow_list as al on b.id = al.deal_id ";
            $condition .= " AND al.appid = {$this->shop_app_id} AND  al.status = 1 AND al.type = 2  AND al.area_id = {$this->area_id} ";
        } else {
            return  [];
        }

        //部分还款中的债权
        if ($partialRepayTender = $this->getUserPartialRepayTenderPH()) {
            $condition .= ' AND t.id  not in ('.implode(',', $partialRepayTender).') ';
        }

        $order = ' ORDER BY  t.id ASC ';
        if ($this->ph_order_borrow_id) {
            $order = ' ORDER BY FIELD(t.deal_id,'.$this->ph_order_borrow_id.') DESC, t.id ASC ';
        }
        $select_sql = ' SELECT b.deal_type,t.debt_type,t.id,t.wait_capital AS account,b.name AS name, t.deal_id AS borrow_id ,t.create_time AS addtime ,t.black_status,b.buyer_uid  FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id  '.$allowListJoin.'  WHERE t.user_id = :user_id  AND  t.xf_status = 0 AND ( b.product_class_type = 223 '.$advisory_condition." ) AND   t.wait_capital > 0 AND t.black_status = 1 AND t.debt_status=0   {$condition}  {$order}  {$splitPageSql} ";
        $allTenders = Yii::app()->phdb->createCommand($select_sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        foreach ($allTenders as &$allTender) {
            $allTender['is_black'] = 1 == $allTender['black_status'] ? 0 : 1;
            $allTender['type'] = 2;
            $allTender['is_exchanging'] = in_array($allTender['id'], $disableExchangeTenders) ? 1 : 0;
            $allTender['payment_lately'] = 0;
        }

        return $allTenders;
    }

    /**
     * 获取用户待收本机及可转让tender条数.
     * @return array
     */
    public function getUserSumAccountAndTotalTenderPH($data = [])
    {
        $returnData = [
            'total_account' => 0,
            'total_tenders' => 0,
        ];

        $commonUserDebt = new CommonUserDebt(0, $this->user_id);
        if ($commonUserDebt->checkUserPurchase()) {
            return $returnData;
        }
        $condition = '';
        $disableBorrow = array_merge($this->getDisableBorrow(2), $this->getZDXBorrow());
        if (!empty($disableBorrow)) {
            $condition = ' AND b.id  not in ('.implode(',', $disableBorrow).') ';
        }
        $advisory_condition = '';
        //开放咨询方的
        if ($advisoryId = $this->getPhDisableAdvisoryId()) {
            //$advisory_condition = ' AND b.advisory_id  not in (' . implode(',', $advisoryId) . ') ';
            $advisory_condition = ' OR ( b.advisory_id  in ('.implode(',', $advisoryId).') and b.product_class_type in (5,232,202,223,316)  )';
        }
        $allowListJoin = '';
        if ($this->is_not_check_white_list) {
        } elseif ($this->getShopAllowBorrow(2)) {
            //$condition .= " AND b.id  IN (".implode(',',$allowBorrow).") ";
            $allowListJoin = " left join firstp2p.xf_debt_exchange_deal_allow_list as al on b.id = al.deal_id ";
            $condition .= " AND al.appid = {$this->shop_app_id} AND  al.status = 1 AND al.type = 2 AND al.area_id = {$this->area_id} ";
        } else {
            return  $returnData;
        }

        //部分还款中的债权
        if ($partialRepayTender = $this->getUserPartialRepayTenderPH()) {
            $condition .= ' AND t.id  not in ('.implode(',', $partialRepayTender).') ';
        }

        $sum_count_sql = 'SELECT sum(t.wait_capital) AS total_account,count(1) AS total_tenders FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id '.$allowListJoin.' WHERE t.user_id = :user_id AND  t.xf_status = 0 AND ( b.product_class_type = 223 '.$advisory_condition.' ) AND t.wait_capital > 0 AND t.black_status = 1 AND t.debt_status=0  '.$condition;

        $sum_count = Yii::app()->phdb->createCommand($sum_count_sql)->bindValues([':user_id' => $this->user_id])->queryRow();


        $exchanging_sql = "select sum(debt_account) as debt_account from firstp2p_debt_exchange_log where user_id = :user_id and status = 1";
        $exchanging_sum_count = Yii::app()->phdb->createCommand($exchanging_sql)->bindValues([':user_id' => $this->user_id])->queryRow();
        $exchanging_amount = 0;
        if ($exchanging_sum_count) {
            $exchanging_amount = $exchanging_sum_count['debt_account'];
        }
        $balance = bcsub($sum_count['total_account'], $exchanging_amount, 2);
        //剔除已兑换的金额
        $returnData['total_account'] = $balance > 0 ?$balance : 0;
        $returnData['total_tenders'] = $sum_count['total_tenders'] ?: 0;

        return $returnData;
    }

    /**
     * 禁用普惠咨询方.
     * @return array
     */
    private function getPhDisableAdvisoryId()
    {
        return [153, 215, 397, 399]; //悠融资产管理（上海）有限公司
    }

    /**
     * 获取智多星项目.
     *
     * @return array
     */
    public function getZDXBorrow()
    {
        $data = Yii::app()->rcache->get('_firstp2p_deal_tag_42_44');
        if (empty($data)) {
            $sql = 'SELECT deal_id FROM firstp2p_deal_tag WHERE tag_id in (42,44) ';
            $res = Yii::app()->phdb->createCommand($sql)->queryAll() ?: [];
            $data = json_encode(ArrayUtil::array_column($res, 'deal_id'));
            Yii::app()->rcache->set('_firstp2p_deal_tag_42_44', $data, 1e5);
        }

        return  json_decode($data);
    }

    /**
     * 不能兑换的债权tender.
     * @return array
     */
    private function getUserCanNotExchangeTendersPH()
    {
        $exchangeTender = $this->getUserWaitingOrdersTendersPH() ?: [];
        $todayRepayTender = $this->getUserTodayRepayTenderPH() ?: [];

        return array_merge($exchangeTender, $todayRepayTender);
    }

    /**
     * 智多鑫不能兑换的债权tender.
     * @return array
     */
    private function getUserCanNotExchangeTendersZdx()
    {
        $exchangeTender = $this->getUserWaitingOrdersTendersZdx() ?: [];
        $todayRepayTender = $this->getUserPartialRepayTenderZdx() ?: [];

        return array_merge($exchangeTender, $todayRepayTender);
    }

    /**
     * 获取部分还款中的债权记录.
     * @return array
     */
    public function getUserPartialRepayTenderPH()
    {
        $sql = ' SELECT distinct deal_loan_id FROM ag_wx_partial_repay_detail WHERE user_id = :user_id and status = 1 and repay_status = 0 ';
        $partialRepayTender = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        if (!$partialRepayTender) {
            return $partialRepayTender;
        }
        $partialRepayTender = ArrayUtil::array_column($partialRepayTender, 'deal_loan_id');

        return $partialRepayTender;
    }

    /**
     * 获取智多新部分还款中的债权记录.
     * @return array
     */
    private function getUserPartialRepayTenderZdx()
    {
        $sql = ' SELECT distinct deal_loan_id FROM offline_partial_repay_detail WHERE user_id = :user_id and status = 1 and repay_status = 0 and platform_id=4 ';
        $partialRepayTender = Yii::app()->offlinedb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        if (!$partialRepayTender) {
            return $partialRepayTender;
        }
        $partialRepayTender = ArrayUtil::array_column($partialRepayTender, 'deal_loan_id');

        return $partialRepayTender;
    }



    /**
     * 今天为还款日的tender.
     * @return array
     */
    private function getUserTodayRepayTenderPH()
    {
        return [];
        $sql = ' SELECT distinct deal_loan_id FROM firstp2p_deal_loan_repay WHERE loan_user_id = :user_id AND `type` = 1 AND `time` =  '.strtotime(date('Y-m-d 16:00:00'));
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
     */
    public function getUserWaitingOrdersTendersPH()
    {
        $tenders = [];
        $sql = 'select tender_id from firstp2p_debt_exchange_log where user_id=:user_id AND status = 1 ';
        $waitingOrders = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $this->user_id])->queryAll();
        if (!$waitingOrders) {
            return $tenders;
        }
        $tenders = ArrayUtil::array_column($waitingOrders, 'tender_id');

        return $tenders;
    }


    /**1
     * 获取待处理订单涉及的tender
     * @return array
     */
    public function getUserWaitingOrdersTendersZdx()
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

    /***************************普惠相关***end*******************************/

    /**
     * 禁止黑名单.
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

    public function getShopAllowBorrow($type)
    {
        $sql = "select deal_id from xf_debt_exchange_deal_allow_list where appid = {$this->shop_app_id}  and area_id = {$this->area_id} and  `type` = {$type} AND status = 1 ";
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
        //请求参数整理 兼容上线 前端先上
        $tenderIds = !empty($data['ids'])? $data['ids'] : ArrayUtil::array_column($data['select_debt'], 'id');
        
        if (empty($tenderIds)) {
            $returnData['code'] = 2023;
            return $returnData;
        }
        if (!in_array($data['debt_type'], [1,2,3,4])) {
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

        $buyer_uid = '';

        $saveTender = [];
        $exchange = [];

        $end_time = time()-60*5;
        if ($data['debt_type']==1) {
            try {
                //校验兑换流水号是否存在信息
                $check_ret = DebtExchangeLog::model()->find("order_id='$orderNum' and user_id={$this->user_id} and (status!=9 or (status=9 and addtime>={$end_time}))");
                if ($check_ret) {
                    $returnData['code'] = 2032;
                    $returnData['info'] = '流水号重复';
                    return $returnData;
                }
                Yii::app()->db->beginTransaction();
               
                $this->isCheckExchangeCommit = true;
                $canDebtTendersInfo = $this->getUserCanDebtTenders($condition);
                //校验数据
                $tenderIdsArray = explode(',', $tenderIds);
                //非特殊兑换才校验这个
                if (count($tenderIdsArray) !== count($canDebtTendersInfo)) {
                    Yii::app()->db->rollback();
                    $returnData['code'] = 2016;
                    return $returnData;
                }
                //构建每笔tender消耗金额

                $res = $this->makeSaveExchangeTender($canDebtTendersInfo, $data['debt_type'], $amount, $buyer_uid, $data);
                if ($res['code']) {
                    Yii::app()->db->rollback();
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
                        Yii::app()->db->rollback();
                        $returnData['code'] = 100;
                        return $returnData;
                    }

                    Yii::app()->db->commit();
                    $returnData['data'] = $exchange;
                    $returnData['contract_url'] = $result;
                    //$this->saveNotice($amount_init, $orderNum, $appid, $notify_url, $data['goodsInfo'], $data['goods_order_no']);
                    return $returnData;
                }
            } catch (Exception $e) {
                Yii::app()->db->rollback();
                Yii::log('save error debtType:'.$data['debtType'].' : save tender  '.print_r($saveTender, true) .' Exception:'.$e->getMessage(), 'error', __FUNCTION__);
            }
        } elseif ($data['debt_type']==2) {
            try {

                //校验兑换流水号是否存在信息
                $check_ret = PHDebtExchangeLog::model()->find("order_id='$orderNum' and user_id={$this->user_id} and (status!=9 or (status=9 and addtime>=$end_time))");
                if ($check_ret) {
                    $returnData['code'] = 2032;
                    return $returnData;
                }
                Yii::app()->phdb->beginTransaction();
                
                //获取tender对应信息
                $this->isCheckExchangeCommit = true;
                $canDebtTendersInfo = $this->getUserCanDebtTendersPH($condition);
                //校验数据
                $phTenderIdsArray = explode(',', $tenderIds);
                if (count($phTenderIdsArray) !== count($canDebtTendersInfo)) {
                    Yii::log('save phTenderIds count($phTenderIdsArray) : '.count($phTenderIdsArray).' count($canDebtPhTendersInfo):'.count($canDebtTendersInfo), 'error', __FUNCTION__);
                    $returnData['code'] = 2016;
                    Yii::app()->phdb->rollback();
                    return $returnData;
                }

                $res = $this->makeSaveExchangeTender($canDebtTendersInfo, $data['debt_type'], $amount, $buyer_uid, $data);
                if ($res['code']) {
                    Yii::app()->phdb->rollback();
                    return  $res;
                }
                $saveTender = $res['data'];

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
                $result = $this->saveDebtOrdersPHTmp($saveTender, $orderNum,$redirect_url, $notice_data);
                if (!$result) {
                    Yii::app()->phdb->rollback();
                    $returnData['code'] = 100;
                    return $returnData;
                }
                Yii::app()->phdb->commit();
                $returnData['data'] = $exchange;
                $returnData['contract_url'] = $result;
                //$this->saveNotice($amount_init, $orderNum, $appid, $notify_url, $data['goodsInfo'], $data['goods_order_no']);
                return $returnData;
            } catch (Exception $e) {
                Yii::app()->phdb->rollback();
                Yii::log('save error debtType:'.$data['debtType'].' : save tender  '.print_r($saveTender, true) .' Exception:'.$e->getMessage(), 'error', __FUNCTION__);
            }
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
            $debtOrder = new DebtExchangeLog();
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
            $debtOrder->area_id = $this->area_id;
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
            //合同信息
            $total_capital = bcadd($total_capital, $tender['debt_account'], 2);
            $buyer_uid = $tender['buyer_uid'];
            $platform_no = $tender['platform_no'];
            //债转合同编号根据规则拼接
            $seller_contract_number = implode('-', [date('Ymd', $tender['addtime']), $tender['deal_type'], $tender['borrow_id'], $tender['tender_id']]);

            //尊享获取合同编号
            if ($tender['debt_type'] == 1  ) {
                //合同信息
                $table_name = $tender['borrow_id'] % 128;
                $contract_sql = "select number from contract_$table_name 
                             where deal_load_id = {$tender['tender_id']} 
                             and user_id={$this->user_id} 
                             and deal_id={$tender['borrow_id']}
                             and type in (0,1) and status=1 and source_type in (2,3)  ";
                $contract_info = Yii::app()->cdb->createCommand($contract_sql)->queryRow();
                if (!$contract_info) {
                    Yii::log("saveDebtOrdersTmp tender_id[{$tender['tender_id']}]  $contract_sql error  ", 'error');
                    return false;
                }
                $seller_contract_number = $contract_info['number'];
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

            $debtOrder = new DebtExchangeLog();
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
            $debtOrder->area_id = $this->area_id;
            $debtOrder->contract_transaction_id = $transaction_id;
            if (!$debtOrder->save()) {
                Yii::log("save saveDebtOrdersTmp user_id[{$this->user_id}] orders error tenders:".print_r($tenders, true), 'error', __FUNCTION__);
                return false;
            }
        }
        //受让人信息查询
        $buyer_uid = !empty($buyer_uid) ? $buyer_uid : DebtService::getInstance()->getBuyerUid($total_capital);
        $assignee = User::model()->findByPk($buyer_uid)->attributes;
        if (!$assignee) {
            Yii::log("saveDebtOrdersTmp tender_id[{$tender['tender_id']}]  assignee error  $buyer_uid ", 'error');
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
                'company_name' =>   "北京经讯时代科技有限公司",
                'plan_name' =>   "网信平台",
                'shop_name' =>   $shop_name,
                'web_address' =>   "www.ncfwx.com",
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
            Yii::log("saveDebtOrdersTmp order_id[{$orderNum}] 合同生成失败！\n" . print_r($result, true), 'error');
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
        $debt['return_url'] = $redirect_url;
        $debt['platform_id'] = 1;
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




    /**
     * 保存兑换债权.
     * @param $tenders
     * @param $orderNum
     * @return bool
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
            $debtOrder->platform_no = $tender['platform_no'];
            $debtOrder->buyer_uid = $tender['buyer_uid'];
            $debtOrder->debt_src = $tender['debt_src'] ?: 1;
            $debtOrder->area_id = $this->area_id;
            if (!$debtOrder->save()) {
                Yii::log('save debt orders error tenders:'.print_r($tenders, true), 'error', __FUNCTION__);

                return false;
            }
        }

        return true;
    }

    public function saveDebtOrdersPHTmp($tenders, $orderNum, $redirect_url, $notice_data)
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
            //普惠获取合同编号
            if ($tender['debt_type'] == 1  ) {
                //合同信息
                $table_name = $tender['borrow_id'] % 128;
                $contract_sql = "select number from contract_$table_name 
                             where deal_load_id = {$tender['tender_id']} 
                             and user_id={$this->user_id} 
                             and deal_id={$tender['borrow_id']}
                             and type in (0,1) and status=1 and source_type=0  ";
                $contract_info = Yii::app()->cdb->createCommand($contract_sql)->queryRow();
                if (!$contract_info) {
                    Yii::log("saveDebtOrdersPHTmp tender_id[{$tender['tender_id']}]  $contract_sql error  ", 'error');
                    return false;
                }
                $seller_contract_number = $contract_info['number'];
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

            $debtOrder = new PHDebtExchangeLog();
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
            $debtOrder->area_id = $this->area_id;
            $debtOrder->contract_transaction_id = $transaction_id;
            if (!$debtOrder->save()) {
                Yii::log('saveDebtOrdersPHTmp save debt orders error tenders:'.print_r($tenders, true), 'error', __FUNCTION__);
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
            Yii::log("saveDebtOrdersPHTmp order_id[{$orderNum}]  合同生成失败！\n" . print_r($result, true), 'error');
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
            Yii::log("saveDebtOrdersPHTmp order_id[{$orderNum}]   的{$cvalue['title']}加水印失败！\n" . print_r($result, true), 'error');
            return false;
        }

        //卖方手动签署合同
        $sign_contract_url = XfFddService::getInstance()->invokeExtSign($seller_user['yj_fdd_customer_id'], $contract_id, $doc_title, 'A盖签', $transaction_id, 1);
        if (!$sign_contract_url) {
            Yii::log("saveDebtOrdersPHTmp order_id[{$orderNum}]  收购合同获取签署地址失败！\n" . print_r($result, true), 'error');
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
        //$redirect_url = base64_encode($redirect_url."?exchange_no={$orderNum}");
        $debt['return_url'] = $redirect_url;
        $debt['platform_id'] = 2;
        $debt['notice_data'] = $notice_data;
        $debt['buyer_uid'] = $buyer_uid;
        $ret = BaseCrudService::getInstance()->add('Firstp2pDebtContract', $debt);
        if(false == $ret){//添加失败
            Yii::log("saveDebtOrdersPhTmp  user_id[{$this->user_id}] , order_id[{$orderNum}] : add Firstp2pDebtContract error ", 'error');
            return false;
        }
        //合同签署地址
        return $sign_contract_url;
    }


    /**
     * 保存兑换债权.
     * @param $tenders
     * @param $orderNum
     * @param  $order_info
     * @param  $order_sn
     * @return bool
     */
    public function saveDebtOrdersXf($tenders, $orderNum, $order_info, $order_sn)
    {
        if (empty($tenders) || empty($order_info) || empty($order_sn)) {
            return false;
        }
        //检验批次号
        $check_ret = DebtExchangeLog::model()->find("order_id='$orderNum' and status in (0,1,2)");
        if ($check_ret) {
            Yii::log('save debt orders error order_id[$orderNum] error', 'error', __FUNCTION__);

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
                Yii::log('save debt orders error tenders:'.print_r($tenders, true), 'error', __FUNCTION__);

                return false;
            }
        }

        return true;
    }

    /**
     * 保存兑换债权.
     * @param $tenders
     * @param $orderNum
     * @param  $order_info
     * @param  $order_sn
     * @return bool
     */
    public function saveDebtOrdersPHXf($tenders, $orderNum, $order_info, $order_sn)
    {
        if (empty($tenders) || empty($order_info) || empty($order_sn)) {
            return false;
        }

        //检验批次号
        $check_ret = PHDebtExchangeLog::model()->find("order_id='$orderNum' and status in (0,1,2)");
        if ($check_ret) {
            Yii::log('save debt orders error order_id[$orderNum] error', 'error', __FUNCTION__);

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
                Yii::log('save debt orders error tenders:'.print_r($tenders, true), 'error', __FUNCTION__);

                return false;
            }
        }

        return true;
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

        $res   = XfDebtExchangeNotice::model()->findByAttributes(['user_id' => $this->user_id, 'order_id' =>  $ordersNum,'appid'=>$this->shop_app_id]);
        if ($res) {
            $returnData['data']['exchange_no'] = $ordersNum;
            $returnData['data']['create_time'] = $res->created_at;
            $returnData['data']['openid'] = $res->user_id;
            $returnData['data']['amount'] = $res->amount;
            $returnData['data']['status'] = 1;
            $returnData['code'] = 0;
            $returnData['info'] = 'success';
            if ($res->status==0) {
                $res->status = 2;
                $res->save();
            }
            return $returnData;
        }

        $amount = 0;
        $is_set_1 = $is_set_2 = $is_set_3 = 0;

        $exchangeInfo = DebtExchangeLog::model()->findBySql("select sum(debt_account) as debt_account,order_id,min(addtime) as addtime from firstp2p_debt_exchange_log where user_id = {$user_id} and  order_id = '{$ordersNum}'");
        if (!empty($exchangeInfo)) {
            $is_set_1 = $exchangeInfo->order_id?1:0;
            $create_time_1 = $exchangeInfo->addtime;
            $amount = bcadd($amount, $exchangeInfo->debt_account, 2);
        }

        //获取数据
        $phExchangeInfo = PHDebtExchangeLog::model()->findBySql("select sum(debt_account) as debt_account,order_id,min(addtime) as addtime from firstp2p_debt_exchange_log where user_id = {$user_id} and  order_id = '{$ordersNum}'");
        if (!empty($phExchangeInfo)) {
            $is_set_2 = $phExchangeInfo->order_id?1:0;
            $create_time_2 = $phExchangeInfo->addtime;
            $amount = bcadd($amount, $phExchangeInfo->debt_account, 2);
        }
        //获取数据
        $offlineExchangeInfo = OfflineDebtExchangeLog::model()->findBySql("select sum(debt_account) as debt_account,order_id,min(addtime) as addtime from offline_debt_exchange_log where user_id = {$user_id} and  order_id = '{$ordersNum}'");
        if (!empty($offlineExchangeInfo)) {
            $is_set_3 = $offlineExchangeInfo->order_id?1:0;
            $create_time_3 = $offlineExchangeInfo->addtime;
            $amount = bcadd($amount, $offlineExchangeInfo->debt_account, 2);
        }

        $returnData['data']['exchange_no'] = $ordersNum;
        $returnData['data']['create_time'] = max($create_time_1, $create_time_2, $create_time_3);
        $returnData['data']['openid'] = $user_id;
        $returnData['data']['amount'] = $amount;
        $returnData['data']['status'] = ($is_set_1+$is_set_2+$is_set_3)?($amount > 0 ? '1' : '0') :'-1';
        $returnData['code'] = 0;
        $returnData['info'] = 'success';

        return $returnData;
    }

    /**
     * 先锋兑换订单信息.
     * @param $ordersNum
     * @return array
     */
    public function getXfOrderStatus($ordersNum)
    {
        $returnData = [
            'data' => [],
            'code' => 100,
            'info' => '订单不存在，请核对单号',
        ];
        if (empty($ordersNum)) {
            $returnData['info'] = '单号不能为空';

            return $returnData;
        }
        $get1 = $get2 = [];
        //获取数据
        $criteria = new CDbCriteria();
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
            $tenderSql = 'select t.id,b.name AS name FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id  where t.id in ('.implode(',', $tenderId).')  ';
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
                $d['name'] = $tem1[$order->tender_id];
                $d['account'] = $order->debt_account;
                $d['orderNumber'] = $order->order_id;
                $d['status'] = $debtStatusToOrderStatus[$order->status];
                $get1[$order->order_id][] = $d;
            }
        }

        //获取数据
        $criteria = new CDbCriteria();
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
                $tenderSql = 'select t.id,b.name AS name FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id  where t.id in ('.implode(',', $phTenderId).')  ';
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
                $d['name'] = $tem2[$order->tender_id];
                $d['account'] = $order->debt_account;
                $d['orderNumber'] = $order->order_id;
                $d['status'] = $debtStatusToOrderStatus[$order->status];
                $get2[$order->order_id][] = $d;
            }
        }

        $returnData['data'] = $get1 + $get2;
        $returnData['code'] = 0;
        $returnData['info'] = 'success';

        return $returnData;
    }

    /**
     * 对账脚本，返回订单对应兑换成功的金额.
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
            'info' => 'success',
        ];

        if (1 == $onlyGetTotalPage) {
            $countSql = "select count(distinct(order_id)) as totalNum,sum(debt_account) as totalAccount from firstp2p_debt_exchange_log where addtime > {$startTime} AND addtime <= {$endTime} AND status in (1,2) ";
            $totalOrders = Yii::app()->db->createCommand($countSql)->queryRow();
            $returnData['data']['totalPage'] = ceil($totalOrders['totalNum'] / $limit);
            $returnData['data']['totalAccount'] = $totalOrders['totalAccount'] ?: 0;
        }
        $offset = ($page - 1) * $limit;
        //获取数据
        $criteria = new CDbCriteria();
        $criteria->select = ' sum(debt_account) as debt_account,order_id ';
        $criteria->condition = " addtime > {$startTime} AND addtime <= {$endTime} AND status in (1,2) ";
        $criteria->group = ' order_id ';
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

        if (1 == $onlyGetTotalPage) {
            $countSql = "select count(distinct(order_id)) as totalNum,sum(debt_account) as totalAccount from firstp2p_debt_exchange_log where addtime > {$startTime} AND addtime <= {$endTime} AND status in (1,2) ";
            $totalOrders = Yii::app()->phdb->createCommand($countSql)->queryRow();
            $returnData['data']['totalPage'] += ceil($totalOrders['totalNum'] / $limit);
            $returnData['data']['totalAccount'] += $totalOrders['totalAccount'] ?: 0;

            return $returnData;
        }
        $offset = ($page - 1) * $limit;
        //获取数据
        $criteria = new CDbCriteria();
        $criteria->select = ' sum(debt_account) as debt_account,order_id ';
        $criteria->condition = " addtime > {$startTime} AND addtime <= {$endTime} AND status in (1,2) ";
        $criteria->group = ' order_id ';
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
     * 统计相关.
     */
    public function getStatisticsData()
    {
        $returnData = [
            'total_quite_number' => 0,
            'yesterday_quite_number' => 0,
        ];
        $quiteSql = 'select quit_number,`time` from wx_statistics_confirmation order by `time` desc limit 2 ';
        $quiteData = Yii::app()->db->createCommand($quiteSql)->queryAll();
        if (!empty($quiteData)) {
            $todayData = isset($quiteData[0]) ? $quiteData[0]['quit_number'] : 0;
            $yesterdayData = isset($quiteData[1]) ? $quiteData[1]['quit_number'] : 0;
            $returnData['total_quite_number'] = $todayData;
            $returnData['yesterday_quite_number'] = $yesterdayData - $todayData;
        }
        //尊享总数
        $zx_total_user_num_sql = 'select sum(debt_account) as zx_total_exchange_num,count(DISTINCT user_id) as zx_total_exchange_user from firstp2p_debt_exchange_log where status in (1,2)';
        $returnData += Yii::app()->db->createCommand($zx_total_user_num_sql)->queryRow();
        //普惠总数
        $ph_total_user_num_sql = 'select sum(debt_account) as ph_total_exchange_num,count(DISTINCT user_id) as ph_total_exchange_user from firstp2p_debt_exchange_log where status in (1,2)';
        $returnData += Yii::app()->phdb->createCommand($ph_total_user_num_sql)->queryRow();
        //尊享当日
        $zx_today_user_num_sql = 'select sum(debt_account) as zx_today_exchange_num,count(DISTINCT user_id) as zx_today_exchange_user from firstp2p_debt_exchange_log where status in (1,2) and addtime >= '.strtotime('midnight');
        $returnData += Yii::app()->db->createCommand($zx_today_user_num_sql)->queryRow();
        //普惠当日
        $ph_today_user_num_sql = 'select sum(debt_account) as ph_today_exchange_num,count(DISTINCT user_id) as ph_today_exchange_user from firstp2p_debt_exchange_log where status in (1,2) and addtime >= '.strtotime('midnight');
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
     * 获取不可下车的tender.
     * @return array
     */
    private function getDisableTenderId()
    {
        $debtStatusIn56Sql = ' select tender_id from firstp2p_debt where user_id = :user_id and status in (5,6) ';
        $disableTenders = Yii::app()->phdb->createCommand($debtStatusIn56Sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];
        if (!empty($disableTenders)) {
            $disableTenders = ArrayUtil::array_column($disableTenders, 'tender_id');
        }

        return $disableTenders;
    }

    /**
     * 过期指定债权.
     *
     * @return bool
     */
    public function expDebt(array $tender_id_arr)
    {
        if (empty($tender_id_arr)) {
            return true;
        }
        $debtphInfo = Yii::app()->phdb->createCommand('select id from firstp2p_debt  where user_id = :user_id and tender_id in ('.implode(',', $tender_id_arr).') and status = 1  ')->bindValues([':user_id' => $this->user_id])->queryAll();
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
     * 获取用户可转让债权.
     * @return mixed
     */
    public function getUserSpecialCanDebtTendersPH($condition = '')
    {
        $disableBorrow = $this->getDisableBorrow(2);
        if (!empty($disableBorrow)) {
            $condition .= ' and fd.id  not in ('.implode(',', $disableBorrow).') ';
        }

        $disableExchangeTenders = array_merge($this->getUserCanNotExchangeTendersPH() ?: [], $this->getDisableTenderId());
        if (!empty($disableExchangeTenders)) {
            $condition .= ' and fdl.id  not in ('.implode(',', $disableExchangeTenders).') ';
        }

        $forUpdate = '';
        if ($this->isCheckExchangeCommit) {
            $forUpdate = ' FOR UPDATE ';
        }

        $advisory_ids = $this->getUserSpecialAdvisoryId();

        if (empty($advisory_ids)) {
            return [];
        }
        $condition .= ' and fda.id  in ('.implode(',', $advisory_ids).') ';

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
                dt.tag_id IS NULL and fdl.xf_status = 0
             {$condition}  {$forUpdate} ";
        $tenders = Yii::app()->phdb->createCommand($select_sql)->bindValues([':user_id' => $this->user_id])->queryAll() ?: [];

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
     * 获取用户待收本机及可转让tender条数.
     * @return array
     */
    public function getUserSpecialSumAccountAndTotalTenderPH()
    {
        $returnData = [
            'total_account' => 0,
            'total_tenders' => 0,
        ];

        $condition = '';
        $disableBorrow = $this->getDisableBorrow(2);
        if (!empty($disableBorrow)) {
            $condition = ' and fd.id  not in ('.implode(',', $disableBorrow).') ';
        }

        $disableExchangeTenders = array_merge($this->getUserCanNotExchangeTendersPH() ?: [], $this->getDisableTenderId());

        if (!empty($disableExchangeTenders)) {
            $condition .= ' and fdl.id  not in ('.implode(',', $disableExchangeTenders).')';
        }
        //指定资方
        $advisory_ids = $this->getUserSpecialAdvisoryId();
        if (empty($advisory_ids)) {
            return $returnData;
        }
        $condition .= ' and fda.id  in ('.implode(',', $advisory_ids).')';

        $sum_count_sql = 'SELECT sum(fdl.wait_capital) AS total_account,count(1) AS total_tenders 
            FROM firstp2p_deal_load fdl
            LEFT JOIN firstp2p_deal_tag dt ON fdl.deal_id =dt.deal_id AND dt.tag_id IN (42,44)
            left join firstp2p_deal fd on fdl.deal_id = fd.id
            left join firstp2p_deal_agency fda on fd.advisory_id=fda.id
            WHERE
                fdl.user_id=:user_id and 
                fdl.wait_capital > 0 and 
                fdl.xf_status = 0 and 
                dt.tag_id IS NULL  '.$condition;
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
                fdl.xf_status = 0 and 
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
     * 兑换提交.
     * @param $data
     * @return array
     */
    public function specialDebtOrderCommit($data)
    {
        $returnData = [
            'data' => [],
            'code' => 0,
            'info' => 'success',
        ];
        //请求参数整理
        $orderNum = strval($data['orderNumber']) ?: '';
        $saveTender = [];
        $exchange = [];

        Yii::app()->phdb->beginTransaction();
        //获取tender对应信息
        $this->isCheckExchangeCommit = true;
        $canDebtPhTendersInfo = $this->getUserSpecialCanDebtTendersPH();
        //校验数据

        if (empty($canDebtPhTendersInfo)) {
            Yii::app()->phdb->rollback();
            Yii::log('orderNum :'.$orderNum.',specialDebtOrderCommit canDebtPhTendersInfo is empty ', 'error', __FUNCTION__);
            $returnData['code'] = 2020;

            return $returnData;
        }
        $total = 0;
        //构建每笔tender消耗金额
        $now = time();

        $insertSql = ' insert into firstp2p_debt_exchange_log (user_id,order_id,tender_id,borrow_id,debt_account,addtime,status,debt_src) values ';

        foreach ($canDebtPhTendersInfo as $key => $orderTender) {
            $insertSql .= " ({$this->user_id},'$orderNum',{$orderTender['id']},{$orderTender['borrow_id']},{$orderTender['account']},{$now},1,4),";

            $total += $orderTender['account'];
            $d['name'] = $orderTender['name'];
            $d['account'] = $orderTender['account'];
            $d['orderNumber'] = $orderNum;
            $d['status'] = 1;
            $exchange[$orderNum][] = $d;
        }
        Yii::log('orderNum :'.$orderNum.', debt account:'.$total.', save tender  data '.print_r($canDebtPhTendersInfo, true), 'info', __FUNCTION__);
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
            if (1 == $totalOrder['return_status']) {
                ++$i;
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
            $returnData['info'] = $doDebtRollBack['info'];
            $returnData['code'] = $doDebtRollBack['code'];

            return $returnData;
        }
        $returnData['code'] = 0;

        return $returnData;
    }


    /**
     * 获取用户可下车金额
     * @param int $user_id
     * @param int $is_r_loads
     * @return array
     */
    public function getUserXcAmount($user_id, $is_r_loads=0)
    {
        //返回
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );

        $commonUserDebt = new CommonUserDebt(0, $this->user_id);
        if ($commonUserDebt->checkUserPurchase()) {
            $return_result['code'] = 100;

            return $return_result;
        }

        //记录用户行为
        Yii::log("getUserXcAmount  user_id: $user_id start", 'info');
        //简单校验
        if (empty($user_id) || !is_numeric($user_id)) {
            Yii::log("getUserXcAmount user_id=[$user_id] error", 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }

        //悠融咨询方
        $advisoryId = [153,215,397,399];
        $disable_condition = $advisory_condition = $ph_condition = $zdx_condition = '';

        //禁止信息[黑名单]
        $disableBorrow  = $this->getDisableBorrow(2);
        if (!empty($disableBorrow)) {
            $disable_condition = " AND b.id  not in (" . implode(',', $disableBorrow) . ") ";
        }

        //剔除禁止兑换的债权[还款中，兑换处理中]
        if ($partialRepayTender = $this->getUserCanNotExchangeTendersPH()) {
            $ph_condition .= " AND t.id  not in (" . implode(',', $partialRepayTender) . ") ";
        }

        //剔除智多鑫禁止兑换的债权[还款中，兑换处理中]
        if ($partialRepayTender = $this->getUserCanNotExchangeTendersZdx()) {
            $zdx_condition .= " AND t.id  not in (" . implode(',', $partialRepayTender) . ") ";
        }

        //sql拼接
        $amount_select = "SELECT sum(t.wait_capital) AS total_account";
        $public_sql = "  FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id 
          WHERE b.is_zdx=0 and t.user_id = $user_id AND t.wait_capital > 0 AND t.xf_status = 0  AND t.debt_status=0 and t.status=1  " . $disable_condition;

        //普惠非悠融债权总金额
        $ph_nyr_sql = $public_sql . ' AND b.advisory_id not in (' . implode(',', $advisoryId) . ') ' . $ph_condition;
        $ph_nyr_amount = Yii::app()->phdb->createCommand($amount_select.$ph_nyr_sql)->queryScalar() ?: 0;

        //普惠悠融债权总金额
        $ph_yr_sql = $public_sql . ' AND b.advisory_id in (' . implode(',', $advisoryId) . ') '. $ph_condition;
        $ph_yr_amount = Yii::app()->phdb->createCommand($amount_select.$ph_yr_sql)->queryScalar() ?: 0;

        //智多新总额
        $zdx_yr_sql = str_replace("firstp2p", "offline", $public_sql) . ' AND t.platform_id = 4 '. $zdx_condition;
        $zdx_yr_amount = Yii::app()->offlinedb->createCommand($amount_select.$zdx_yr_sql)->queryScalar() ?: 0;

        //可兑换债权必须大于100
        $wait_capital = round($ph_nyr_amount + $ph_yr_amount + $zdx_yr_amount, 2);
        if (FunctionUtil::float_bigger_equal(100, $wait_capital, 2)) {
            Yii::log("getUserXcAmount user_id=[$user_id]: wait_capital[$wait_capital]<=0 ", 'error');
            $return_result['code'] = 6005;
            return $return_result;
        }

        //悠融占比低于50%禁止兑换
        $yr_rate = round(($ph_yr_amount+$zdx_yr_amount)/$wait_capital, 6);
        if (FunctionUtil::float_bigger(0.50, $yr_rate, 2)) {
            Yii::log("getUserXcAmount user_id=[$user_id]: yr_rate[$yr_rate]<0.5 ", 'error');
            $return_result['code'] = 6001;
            return $return_result;
        }

        //返回
        $return_result['data'] = [
            'ph_nyr_amount' => $ph_nyr_amount,//普惠非悠融可兑换金额
            'ph_yr_amount' => $ph_yr_amount,//普惠悠融金额
            'zdx_yr_amount' => $zdx_yr_amount,//智多新金额
            'wait_capital' => $wait_capital,//在途本金
            'yr_rate' => $yr_rate, //悠融占比
        ];

        //投资记录信息返回
        $deal_load_list = [];
        if ($is_r_loads == 1) {
            $loads_select = "SELECT t.id, t.wait_capital";
            if ($ph_nyr_amount  >0) {
                $deal_load_list['ph_nyr_list'] =  Yii::app()->phdb->createCommand($loads_select.$ph_nyr_sql)->queryAll();
            }

            if ($ph_yr_amount  >0) {
                $deal_load_list['ph_yr_list'] = Yii::app()->phdb->createCommand($loads_select.$ph_yr_sql)->queryAll();
            }

            if ($zdx_yr_amount  >0) {
                $deal_load_list['zdx_yr_list'] = Yii::app()->offlinedb->createCommand($loads_select.$zdx_yr_sql)->queryAll();
            }

            $return_result['data']['deal_load_list'] = $deal_load_list;
        }
        return $return_result;
    }


    /**
     * 处理用户可下车金额
     * @param int $user_id
     * @param int $amount
     * @param string $order_id
     * @return array
     */
    public function handelUserXcAmount($user_id, $amount, $order_id)
    {
        //返回
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );
        $commonUserDebt = new CommonUserDebt(0, $this->user_id);
        if ($commonUserDebt->checkUserPurchase()) {
            $return_result['code'] = 100;
            return $return_result;
        }
        //记录用户行为
        Yii::log("handelUserXcAmount  user_id[$user_id], amount[$amount] start", 'info');
        //简单校验
        if (empty($order_id) || empty($user_id) || !is_numeric($user_id) || !is_numeric($amount) || FunctionUtil::float_bigger_equal(0, $amount, 2)) {
            Yii::log("handelUserXcAmount user_id=[$user_id] or amount[$amount] error", 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }
        //获取用户可转让总额
        $user_wait_info = $this->getUserXcAmount($user_id, 1);
        if ($user_wait_info['code'] != 0 || empty($user_wait_info['data']['deal_load_list'])) {
            Yii::log("handelUserXcAmount user_id=[$user_id] getUserXcAmount return error", 'error');
            return $user_wait_info;
        }

        //分配每种类型应兑换的金额
        /* 第一期实现全额下车
        $change_money_info = $this->allotChangeMoney($user_wait_info, $amount);
        if($change_money_info['code'] != 0){
            Yii::log("handelUserXcAmount user_id=[$user_id] allotChangeMoney return error", 'error');
            return $change_money_info;
        }*/

        //下车金额务必与待还本金一致，只支持出清操作
        if (!FunctionUtil::float_equal($user_wait_info['data']['wait_capital'], $amount, 2)) {
            Yii::log("handelUserXcAmount user_id=[$user_id] wait_capital[{$user_wait_info['data']['wait_capital']}]!=amount[$amount] ", 'error');
            $return_result['code'] = 6003;
            return $return_result;
        }

        //执行兑换
        $this->change_order_id = $order_id;
        $this->user_id = $user_id;
        $zdx_yr_amount = $user_wait_info['data']['zdx_yr_amount'];
        $deal_load_list = $user_wait_info['data']['deal_load_list'];
        $ph_load_list = array_merge((array)$deal_load_list['ph_nyr_list'], (array)$deal_load_list['ph_yr_list']);
        $ph_wait_capital = bcadd($user_wait_info['data']['ph_yr_amount'], $user_wait_info['data']['ph_nyr_amount'], 2);
        $this->yrBeginTransaction($zdx_yr_amount);
        try {
            //普惠兑换(第一期不区分是否悠融)
            if (!empty($ph_load_list)) {
                $ph_nyr_ret = $this->toExchangeLog($ph_wait_capital, $ph_load_list);
                if ($ph_nyr_ret['code'] != 0) {
                    $this->yrRollback($zdx_yr_amount);
                    Yii::log("handelUserXcAmount user_id=[$user_id] ph_load_list toExchangeLog return error", 'error');
                    return $ph_nyr_ret;
                }
            }

            //智多新兑换
            if (!empty($deal_load_list['zdx_yr_list'])) {
                $zdx_yr_ret = $this->toExchangeLog($user_wait_info['data']['zdx_yr_amount'], $deal_load_list['zdx_yr_list'], 'offlinedb');
                if ($zdx_yr_ret['code'] != 0) {
                    $this->yrRollback($zdx_yr_amount);
                    Yii::log("handelUserXcAmount user_id=[$user_id] zdx_yr_amount toExchangeLog return error", 'error');
                    return $zdx_yr_ret;
                }
            }

            //同步到礼包统计数据
            $yr_amount = bcadd($user_wait_info['data']['ph_yr_amount'], $user_wait_info['data']['zdx_yr_amount'], 2);
            $sync_ret = $this->syncGiftData($yr_amount, $user_wait_info['data']['wait_capital']);
            if ($sync_ret['code'] != 0) {
                $this->yrRollback($zdx_yr_amount);
                Yii::log("handelUserXcAmount , syncGiftData return error,code={$sync_ret['code']}", 'error');
                return $sync_ret;
            }

            //执行兑换成功
            $this->yrCommit($zdx_yr_amount);
        } catch (Exception $e) {
            $this->yrRollback($zdx_yr_amount);
            $return_result['code'] = 5000;
            Yii::log("handelUserXcAmount user_id=[$user_id] Fail:".print_r($e->getMessage(), true), "error");
            return $return_result;
        }
        return $return_result;
    }

    /**
     * 分配金额[第一期实现全额下车]
     * @param $user_wait_info
     * @param $amount
     * @return mixed
     */
    private function allotChangeMoney($user_wait_info, $amount)
    {
    }


    /**
     * 执行兑换
     * @param $debt_money
     * @param $loan_list
     * @param $db_name
     * @return array
     */
    private function toExchangeLog($debt_money, $loan_list, $db_name = 'phdb')
    {
        //返回
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );

        //校验
        if (empty($loan_list) || !is_array($loan_list) || FunctionUtil::float_bigger_equal(0, $debt_money, 2) || !in_array($db_name, ['phdb', 'db', 'offlinedb'])) {
            Yii::log("toExchangeLog params error", 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }

        //model
        $model_prefix = "offline_";
        $deal_model_name = "OfflineDeal";
        if ($db_name == 'phdb') {
            $model_prefix = "firstp2p_";
            $deal_model_name = "PHDeal";
        }

        //拼接写入数据
        $debt_exchange_str = $debt_exchange_key = '';
        //单笔处理并拼接sql
        foreach ($loan_list as $value) {
            $deal_load = Yii::app()->{$db_name}->createCommand("select * from {$model_prefix}deal_load where id={$value['id']} for update ")->queryRow();
            if (!$deal_load || $deal_load['status'] != 1 || $deal_load['wait_capital'] != $value['wait_capital']) {
                Yii::log("toExchangeLog deal_load_id:{$value['id']} error", 'error');
                $return_result['code'] = 6004;
                return $return_result;
            }

            //获取buyer_uid
            $deal_info = $deal_model_name::model()->findByPk($deal_load['deal_id']);
            if (!$deal_info || $deal_info->deal_status != 4) {
                Yii::log("toExchangeLog deal_id:{$deal_load['deal_id']} error", 'error');
                $return_result['code'] = 6006;
                return $return_result;
            }

            //拼接sql
            $buyer_uid = $deal_info->buyer_uid ? $deal_info->buyer_uid : DebtService::getInstance()->getBuyerUid($deal_load['wait_capital']);
            $insert_change_data = array(
                'user_id' => $deal_load['user_id'],
                'tender_id' => $deal_load['id'],
                'order_id' => $this->change_order_id,
                'debt_account' => $deal_load['wait_capital'],
                'addtime' => time(),
                'status' => 1,
                'borrow_id' => $deal_load['deal_id'],
                'buyer_uid' => $buyer_uid,
                'debt_src' => 1,
                'platform_no' => 4, //暂定
                'order_info' => '',
                'order_sn' => ''
            );

            //线下产品记录平台ID
            if ($db_name == 'offlinedb') {
                $insert_change_data['platform_id'] = $deal_load['platform_id'];
            }
            $debt_exchange_str .= "( '".  implode("','", $insert_change_data) ."' ),";
            $debt_exchange_key = array_keys($insert_change_data);
        }

        //写入数据校验
        if (empty($debt_exchange_str) || empty($debt_exchange_key)) {
            Yii::log("toExchangeLog debt_exchange_str or debt_exchange_key empty", 'error');
            $return_result['code'] = 6007;
            return $return_result;
        }

        //批量写入
        $debt_exchange_str = rtrim($debt_exchange_str, ',');
        $debt_exchange_key = implode(",", $debt_exchange_key);
        $i_sql = "INSERT INTO {$model_prefix}debt_exchange_log (".$debt_exchange_key.") VALUES $debt_exchange_str";
        $result = Yii::app()->{$db_name}->createCommand($i_sql)->execute();
        if (false == $result) {
            Yii::log("toExchangeLog , insert into {$model_prefix}debt_exchange_log fail; sql:$i_sql", 'error');
            $return_result['code'] = 6007;
            return $return_result;
        }

        //写入成功
        return $return_result;
    }


    protected function yrBeginTransaction($zdx_yr_amount)
    {
        Yii::app()->phdb->beginTransaction();
        if (is_numeric($zdx_yr_amount) && FunctionUtil::float_bigger($zdx_yr_amount, 0, 2)) {
            Yii::app()->offlinedb->beginTransaction();
        }
    }

    protected function yrRollback($zdx_yr_amount)
    {
        Yii::app()->phdb->rollback();
        if (is_numeric($zdx_yr_amount) && FunctionUtil::float_bigger($zdx_yr_amount, 0, 2)) {
            Yii::app()->offlinedb->rollback();
        }
    }

    protected function yrCommit($zdx_yr_amount)
    {
        Yii::app()->phdb->commit();
        if (is_numeric($zdx_yr_amount) && FunctionUtil::float_bigger($zdx_yr_amount, 0, 2)) {
            Yii::app()->offlinedb->commit();
        }
    }


    /**
     * 同步到礼包统计数据
     * @param $yr_amount
     * @param $debt_money
     * @return mixed
     */
    private function syncGiftData($yr_amount, $debt_money)
    {
        //返回
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );

        //校验
        if (!is_numeric($debt_money) ||  FunctionUtil::float_bigger_equal(0, $debt_money, 2) || !is_numeric($yr_amount) ||  FunctionUtil::float_bigger_equal(0, $yr_amount, 2)) {
            Yii::log("syncGiftData params error", 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }

        //校验是否白名单用户
        $user_detail_info = XfDebtLiquidationUserDetails::model()->findBySql("select * from xf_debt_liquidation_user_details where user_id=$this->user_id for update");
        if (!$user_detail_info || $user_detail_info->status != 1) {
            Yii::log("syncGiftData user_id=$this->user_id xf_debt_liquidation_user_details check error", 'error');
            $return_result['code'] = 6010;
            return $return_result;
        }

        //查询归属礼包
        $gift_info = XfGiftExchange::model()->find("exchange_min < $debt_money and exchange_max>=$debt_money");
        if (!$gift_info) {
            Yii::log("syncGiftData debt_money:$debt_money Failed to match gift bag ", 'error');
            $return_result['code'] = 6008;
            return $return_result;
        }

        //确定kpi字段名称
        if ($gift_info->kpi_1_min < $debt_money && $gift_info->kpi_1_max >= $debt_money) {
            $kpi_real_user = 'kpi_1_real_user';
            $kpi_name = 'kpi_1';
        } elseif ($gift_info->kpi_2_min < $debt_money && $gift_info->kpi_2_max >= $debt_money) {
            $kpi_real_user = 'kpi_2_real_user';
            $kpi_name = 'kpi_2';
        } elseif ($gift_info->kpi_3_min < $debt_money && $gift_info->kpi_3_max >= $debt_money) {
            $kpi_real_user = 'kpi_3_real_user';
            $kpi_name = 'kpi_3';
        } else {
            Yii::log("syncGiftData debt_money:$debt_money in gift_id=$gift_info->id  kpi error ", 'error');
            $return_result['code'] = 6011;
            return $return_result;
        }

        //更新礼包数据
        $gift_info = XfGiftExchange::model()->findBySql("select * from xf_gift_exchange where id=$gift_info->id for update");
        $gift_info->liquidation_user += 1;
        $gift_info->yr_debt_total =  bcadd($gift_info->yr_debt_total, $yr_amount, 2);
        $gift_info->debt_total =  bcadd($gift_info->debt_total, $debt_money, 2);
        $gift_info->liquidation_cost = round($gift_info->yr_debt_total*0.25, 2);
        $gift_info->$kpi_real_user += 1;
        if ($gift_info->save(false, array('yr_debt_total', 'liquidation_user', 'debt_total', 'liquidation_cost', $kpi_real_user)) == false) {
            Yii::log("syncGiftData id=$gift_info->id xf_gift_exchange update failed ", 'error');
            $return_result['code'] = 6009;
            return $return_result;
        }

        //记录用户兑换明细
        $user_detail_info->real_debt_total = $debt_money; // 实际用户总债权
        $user_detail_info->real_yr_debt_total = $yr_amount; // 实际用户悠融债权
        $user_detail_info->gift_id = $gift_info->id; //实际礼包ID
        $user_detail_info->liquidation_time = time(); // 下车时间
        $user_detail_info->status = 2; //下车状态：1-待下车，2-已下车
        $user_detail_info->$kpi_name = 1;
        if ($user_detail_info->save(false, array('real_debt_total', 'real_yr_debt_total', 'gift_id', 'liquidation_time', 'status', $kpi_name)) == false) {
            Yii::log("syncGiftData user_id=$this->user_id  xf_debt_liquidation_user_details update failed ", 'error');
            $return_result['code'] = 6009;
            return $return_result;
        }

        //兑换数据记录成功
        return $return_result;
    }
}
