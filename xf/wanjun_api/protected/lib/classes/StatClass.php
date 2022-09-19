<?php
/*
 * Stat
 */

class StatClass  {
    
    /**
     * TODO: 根据项目ID获取项目投资金额统计数据
     * @param: int $borrowId
     * @return: array $statRecords
     */
    public function getInvestDetailByBorrowId($borrowId, $order="", $offset=0, $limit=10) {
        $ItzStatInvestModel = new ItzStatInvest();
        $criteria = new CDbCriteria;
        if(!empty($order)) $criteria->order = $order;
        $criteria->offset = $offset;
        $criteria->limit = $limit;

        $attributes = array(
            "borrow_id"    =>   $borrowId,   
        );
        $statRecords = $ItzStatInvestModel->findAllByAttributes($attributes, $criteria);
        return $statRecords;
    }

    public function getInvestDetailCountByBorrowId($borrowId) {
        $ItzStatInvestModel = new ItzStatInvest();
        $attributes = array(
            "borrow_id"    =>   $borrowId,   
        );
        return $ItzStatInvestModel->countByAttributes($attributes);
    }
    
    /**
     * TODO: 根据项目ID获取项目还款金额统计数据
     * @param: int $borrowId
     * @return: array $statRecords
     */
    public function getRepayDetailByBorrowId($borrowId, $order="", $offset=0, $limit=10) {
        $ItzStatRepayModel = new ItzStatRepay();
        $criteria = new CDbCriteria;
        if(!empty($order)) $criteria->order = $order;
        $criteria->offset = $offset;
        $criteria->limit = $limit;

        $attributes = array(
            "borrow_id"    =>   $borrowId,   
        );
        $statRecords = $ItzStatRepayModel->findAllByAttributes($attributes, $criteria);
        return $statRecords;
    }

    public function getRepayDetailCountByBorrowId($borrowId) {
        $ItzStatRepayModel = new ItzStatRepay();
        $attributes = array(
            "borrow_id"    =>   $borrowId,   
        );
        return $ItzStatRepayModel->countByAttributes($attributes);
    }

    // TODO:保存按日投资金额统计
    public function saveRepayByDay($attributes,$flag=0) {
        $model=new ItzStatRepay;

        $row = $model->findByAttributes( array( 'borrow_id' => $attributes['borrow_id'], 'repay_time' => $attributes['repay_time'] ) );
        if(empty($row) || ($flag == 1)) {
            $model->attributes = $attributes;
            return $model->save();
        } else {
            $row->attributes = $attributes;
            return $row->save();
        }
    }

    // TODO: 保存按日还款统计
    public function saveInvestMoneyByDay($attributes) {
        $model = new ItzStatInvest;

        $row = $model->findByAttributes( array( 'borrow_id' => $attributes['borrow_id'], 'day' => $attributes['day'] ) );
        if(empty($row)) {
            $model->attributes = $attributes;
            return $model->save();
        } else {
            $row->attributes = $attributes;
            return $row->save();
        }
    }


    public function statRepayDetail($borrowId) {
        //SELECT FROM_UNIXTIME(value_date, '%Y-%m-%d') AS '起息日期', FROM_UNIXTIME(repay_time, '%Y-%m-%d') AS '还款日期', SUM(repay_account) AS '还款总额', SUM(capital) AS '本金', SUM(interest) AS '利息' FROM dw_borrow_collection WHERE tender_id IN (SELECT DISTINCT(id) FROM dw_borrow_tender WHERE borrow_id='185') GROUP BY FROM_UNIXTIME(repay_time, '%Y-%m-%d');

        $BorrowCollectionModel = new BorrowCollection();
        $BorrowTenderModel = new BorrowTender();
        $BorrowModel = new Borrow();
        $criteria = new CDbCriteria;
        $criteria->condition = " id = ".$borrowId;
        $attributes = array();
        $BorrowResult = $BorrowModel->findAllByAttributes($attributes, $criteria);
        $borrowInfo = $BorrowResult[0]->getAttributes();

        // TODO:获取项目投资记录
        $tenders = $BorrowTenderModel->findAllByAttributes( array( 'borrow_id' => $borrowId ), array('index' => 'id') );
        $tenderIdarr = array_keys($tenders);

        if($tenderIdarr) {
            $connection = Yii::app()->db;
            $sql = "SELECT value_date, repay_time, sum(repay_account) as repay_account_sum, sum(capital) as capital_sum,"
                    ." sum(interest) as interest_sum, FROM_UNIXTIME(repay_time, '%Y-%m-%d') FROM ".$BorrowCollectionModel->tableName()
                    ." WHERE type != 5 AND tender_id IN (".implode(',', $tenderIdarr).") GROUP BY FROM_UNIXTIME(repay_time, '%Y-%m-%d')";
            $command = $connection->createCommand($sql);
            $statRecords = $command->queryAll();

            foreach ($statRecords as $k => $v) {                            //等额本息直接取融资总额
                # 企业还款时间
                if ($borrowInfo['delay_value_days'] == 2) {
                    $statRecords[$k]['company_repay_time'] = $v['repay_time'] - 86400; 
                }else{
                    $statRecords[$k]['company_repay_time'] = $v['repay_time']; 
                }
                # 融完时间
                $statRecords[$k]['last_tender_time'] = $borrowInfo['last_tender_time'];
                # 融资额
                if (5 == $borrowInfo['style']) {
                    $statRecords[$k]['finance_amount'] = $borrowInfo['account'];
                }else{
                    $tenderIdStr = '';
                    //刚刚融满的项目 所以collection不用加status=0
                    $sql = "SELECT distinct(tender_id) tender_id from ".$BorrowCollectionModel->tableName()." where borrow_id = $borrowId and repay_time = ".$v['repay_time'];
                    $command = $connection->createCommand($sql);
                    $tenderIdsRes = $command->queryAll();
                    foreach ($tenderIdsRes as $key => $val) {
                        $tenderIdStr .= $val['tender_id'].",";
                    }
                    $sql = "SELECT sum(account_init) account FROM ".$BorrowTenderModel->tableName()." where id IN (".trim($tenderIdStr,',').")";
                    $command = $connection->createCommand($sql);
                    $accountRes = $command->queryAll();
                    $statRecords[$k]['finance_amount'] = $accountRes[0]['account'];
                }
            }
            return $statRecords;
        } else {
            return array();
        }
    }

    public function statInvestTotalByTime($borrowId, $starttime = 0, $endtime = 0 ) {
        //SELECT addtime, SUM(account) AS account_sum FROM dw_borrow_tender WHERE borrow_id = ':borrowid' GROUP BY addtime;
        
        $_bindvalues = array();

        $_wheresql = ' borrow_id = '.$borrowId;
        if(!empty($starttime) && is_int($starttime)) {
            $_wheresql .= ' and addtime >= '.$starttime;
        }
        if(!empty($endtime) && is_int($endtime)) {
            $_wheresql .= ' and addtime < '.$endtime;
        }

       /*
        $_wheresql = ' borrow_id = :borrowid ';
        $_bindvalues[':borrowid'] = $borrowId;
        if(!empty($starttime)) {
            $_wheresql .= ' and addtime >= :starttime ';
            $_bindvalues[':starttime'] = $starttime;
        }
        if(!empty($endtime)) {
            $_wheresql .= ' and addtime < :endtime ';
            $_bindvalues[':endtime'] = $endtime;
        }
        */

        $BorrowTenderModel = new BorrowTender();
        $btTableName = $BorrowTenderModel->tableName();
        $connection = Yii::app()->db;//dw_borrow_tender:table
        $sql = "SELECT addtime, SUM(account) as account_sum FROM ".$btTableName
                ." WHERE ".$_wheresql
                ." GROUP BY FROM_UNIXTIME(addtime, '%Y-%m-%d')";
        $command = $connection->createCommand($sql);
        /*
        foreach($_bindvalues as $key => $value) {
            $command->bindValue($key, $value);
        }*/
        $statRecords = $command->queryAll();

        return $statRecords;
    }

    /**
     * 智选计划企业还款
     */
    public function WiseStatRepayDetail($wiseBorrowInfo=array())
    {
        if (empty($wiseBorrowInfo)) {
            return [];
        }
        $wise_borrow_id  = $wiseBorrowInfo['wise_borrow_id'];
        $statRepayData   = array();
        $WiseTenderModel = new ItzWiseTender();
        // 智选计划小贷类 投资成功当天计息
        if ($wiseBorrowInfo['borrow_type'] == 2 && $wiseBorrowInfo['style'] != 5) {
            $sql = "SELECT tender_time, sum(account_init) as account, FROM_UNIXTIME(tender_time, '%Y-%m-%d') tender_time_cn FROM ".$WiseTenderModel->tableName()
                    ." WHERE wise_borrow_id = '".$wise_borrow_id."' and status in(2,17,19) and debt_type=3 GROUP BY FROM_UNIXTIME(tender_time, '%Y-%m-%d') ";
            $tenderRecords = Yii::app()->db->createCommand($sql)->queryAll();
            //融资天数
            $statRepayDetails = array();
            foreach ($tenderRecords as $tenderInfo) {
                # $statRepayDetail 融资一天对应的还款明细
                $statRepayDetail  = $this->getStatDetail($wiseBorrowInfo,$tenderInfo);
                foreach ($statRepayDetail as &$sRow) {
                    # 融资总额
                    $sRow['finance_amount']      = $tenderInfo['account'];
                    $sRow['repay_type']          = 2;
                    $sRow['company_repay_time']  = $sRow['repayment_time'] - 24*60*60;
                    $sRow['investor_value_time'] = strtotime($tenderInfo['tender_time_cn']);
                }
                $statRepayDetails = array_merge($statRepayDetails,$statRepayDetail);
            }
        // 智选计划雁阵类 融满次日计息
        }elseif ($wiseBorrowInfo['borrow_type'] == 3) {
            $statRepayDetails = $this->getYanzhenStatDetail($wiseBorrowInfo);
            foreach ($statRepayDetails as &$statRepayDetail) {
                $statRepayDetail['repay_type']           = 3;
                $statRepayDetail['company_repay_time']   = 0;
                $statRepayDetail['investor_value_time']  = 0;
            }
        }else{
            return [];
        }
        # its_stat_repay value_time repay_time
        foreach ($statRepayDetails as &$statRepayDetail) {
            $statRepayDetail['value_time']  = $statRepayDetail['repayment_time'] - $statRepayDetail['days']*24*60*60;
            $statRepayDetail['repay_time']  = $statRepayDetail['repayment_time'];   
            $statRepayDetail['repay_money'] = $statRepayDetail['repayment_account'];
        }
        return $statRepayDetails;
    }


    /**
     * 根据投资成功时间、借款周期（天）获取起息时间、还款时间、利息
     */
    public function getStatDetail($borrowInfo=array(),$tenderInfo=array()){

        $tender_time = strtotime($tenderInfo['tender_time_cn']);
        $eq = array(
            'account'=>$tenderInfo['account'],
            'year_apr'=>$borrowInfo['apr'],
            'repayment_time'=>$borrowInfo['repayment_time'],
            'borrow_style'=>$borrowInfo['style'],
            'delay_value_days'=>$borrowInfo['delay_value_days'],    // 0 当日计息1 次日计息
            'repay_months'=>BorrowService::getInstance()->handleTimelimit($borrowInfo['formal_time'], $borrowInfo['repayment_time']),
            'formal_time'=>$borrowInfo['formal_time'],    //等额本息, formal_time必传
            'borrow_time'=>$tender_time,
        );
        $interestList = InterestPayUtil::EqualInterest($eq);//计算还息表
        return $interestList;
    }

    public function getYanzhenStatDetail($borrowInfo=array()){

        $tender_next_time = strtotime(date('Y-m-d',strtotime('+1 day',$borrowInfo['finish_time']))); // 项目融满时间的第二天零点
        /* 生成大项目还款记录 */
        $eq = array(
            'account'          => $borrowInfo['account'],           //用户投资的钱
            'year_apr'         => $borrowInfo['apr'],                   //项目利息apr
            'repayment_time'   => $borrowInfo['repayment_time'],        //最后还本时间
            'borrow_style'     => $borrowInfo['style'],                 // 项目还款方式：目前使用 0，1，3，5
            'delay_value_days' => 2,                                    // 起息延后天数 计息方式：0 当日计息 1 次日计息 2 融满次日计息
            'formal_time'      => $borrowInfo['formal_time'],           //等额本息, formal_time必传
            'borrow_time'      => $borrowInfo['finish_time'],           //传入的是 项目融满时间
            'month_limit'	   => $borrowInfo['month_limit'],			//月天数判断
        );

        //等额本息的项目还款期数
        if($borrowInfo['style'] == 5){
            $eq['repay_months'] = $borrowInfo['time_limit'];
        }else{
            $eq['repay_months'] = BorrowService::getInstance()->handleTimelimit($tender_next_time, $borrowInfo['repayment_time']);
        }
        
        $interestList = WiseInterestPayUtil::EqualInterest($eq);        //计算还息表
        return $interestList;
    }
}
