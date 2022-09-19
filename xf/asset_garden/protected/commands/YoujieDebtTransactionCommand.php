<?php

/**
 * 有解过期脚本
 * Class YoujieDebtTransactionCommand
 */
class YoujieDebtTransactionCommand extends CConsoleCommand {
    public $fnLock_pre = '/tmp/YoujieDebtTransaction';	//项目ID文件锁的前缀
    public $fnLock_tenderid = '';//文件执行id
    public $alarm_content = '';//报警内容
    public $is_email = false;
    /**
     * 有解过期脚本
     * @return bool
     */
    public function actionRun(){
        try {
            //添加文件锁
            $fpLock = $this->enterTenderIdFnLock(1);
            $this->echoLog("YoujieDebtTransaction run start");
            //转让中数据->过期 尊享
            $now = time();
            $debtzxCount = Yii::app()->db->createCommand("select count(*) from firstp2p_debt debt left join firstp2p_deal_load dload on debt.tender_id = dload.id
                                                         where  dload.black_status =1 and debt.status = 1 and debt.endtime < $now")->queryScalar();
            if($debtzxCount > 0){
                $debtzxInfo = Yii::app()->db->createCommand("select debt.id from firstp2p_debt debt left join firstp2p_deal_load dload on debt.tender_id = dload.id
                                                            where dload.black_status =1 and debt.status = 1 and debt.endtime < $now")->queryAll();
                foreach($debtzxInfo as $key => $value){
                    $cancelDebtRet = DebtGardenYoujieQuestionService::getInstance()->CancelDebt(['debt_id' => $value['id'], 'status' => 4, 'products' => 1, 'checkuser' => 2]);
                    if($cancelDebtRet['code']!='0'){
                        $errorInfo = Yii::app()->c->errorcodeinfo;
                        $this->echoLog("Expired Debt wrong debt_id {$value['id']}, return:".print_r($errorInfo[$cancelDebtRet['code']],true), 'email');
                    }
                }
            }
            //转让中数据->过期 普惠供应链
            $debtphCount = Yii::app()->phdb->createCommand("select count(*) from firstp2p_debt debt left join firstp2p_deal_load dload on debt.tender_id = dload.id
                                                          left join firstp2p_deal deal on deal.id = dload.deal_id where  dload.black_status = 1 and deal.product_class_type = 223 and debt.status = 1 and debt.endtime < $now")->queryScalar();
            if($debtphCount > 0){
                $debtphInfo = Yii::app()->phdb->createCommand("select debt.id from firstp2p_debt debt left join firstp2p_deal_load dload on debt.tender_id = dload.id
                                                          left join firstp2p_deal deal on deal.id = dload.deal_id where   dload.black_status = 1 and deal.product_class_type = 223 and debt.status = 1 and debt.endtime < $now")->queryAll();
                foreach($debtphInfo as $key => $value){
                    $cancelDebtRet = DebtGardenYoujieQuestionService::getInstance()->CancelDebt(['debt_id' => $value['id'], 'status' => 4, 'products' => 2, 'checkuser' => 2]);
                    if($cancelDebtRet['code']!='0'){
                        $errorInfo = Yii::app()->c->errorcodeinfo;
                        $this->echoLog("Expired Debt wrong debt_id {$value['id']}, return:".print_r($errorInfo[$cancelDebtRet['code']],true), 'email');
                    }
                }
            }
            //转让中数据->过期 线下产品
            $offline_sql = "from offline_debt debt left join offline_deal_load dload on debt.tender_id = dload.id left join offline_deal deal on deal.id = dload.deal_id where  dload.black_status = 1 and debt.status = 1 and debt.endtime < {$now}";
            $debtphCount = Yii::app()->offlinedb->createCommand("select count(*) $offline_sql ")->queryScalar();
            if($debtphCount > 0){
                $debtphInfo = Yii::app()->offlinedb->createCommand("select debt.id,debt.platform_id $offline_sql")->queryAll();
                foreach($debtphInfo as $key => $value){
                    $cancelDebtRet = DebtGardenYoujieQuestionService::getInstance()->CancelDebt(['debt_id' => $value['id'], 'status' => 4, 'products' => $value['platform_id'], 'checkuser' => 2]);
                    if($cancelDebtRet['code']!='0'){
                        $errorInfo = Yii::app()->c->errorcodeinfo;
                        $this->echoLog("Expired Debt wrong debt_id {$value['id']}, return:".print_r($errorInfo[$cancelDebtRet['code']],true), 'email');
                    }
                }
            }

            //已承接超时->取消 线下
            $youjie_undertake_endtime = ConfUtil::get('youjie-undertake-endtime');
            $offline_sql_01 = "from offline_debt_tender tender left join offline_debt debt on tender.debt_id = debt.id
                                                     left join offline_deal deal on debt.borrow_id = deal.id
                                                     left join offline_deal_load dload on dload.id = debt.tender_id
                                                     where  dload.black_status = 1 and tender.status = 1 and debt.status = 5 and tender.addtime < $now - $youjie_undertake_endtime ";
            $tenderCount = Yii::app()->offlinedb->createCommand("select count(*) $offline_sql_01")->queryScalar();
            if($tenderCount > 0){
                $tenderInfo = Yii::app()->offlinedb->createCommand("select tender.id,debt.platform_id   $offline_sql_01")->queryAll();
                foreach($tenderInfo as $key => $value){
                    $cancelDebtTenderRet = DebtGardenYoujieQuestionService::getInstance()->CancelTenderDebt(['debt_tender_id' => $value['id'], 'status' => 2, 'products' => $value['platform_id']]);
                    if($cancelDebtTenderRet['code']!='0'){
                        $errorInfo = Yii::app()->c->errorcodeinfo;
                        $this->echoLog("Expired Debt wrong debt_tender_id {$value['id']}, return:".print_r($errorInfo[$cancelDebtTenderRet['code']],true), 'email');
                    }
                }
            }

            //待卖方确认超时->客服介入 线下
            $youjie_payment_endtime = ConfUtil::get('youjie-payment-endtime');
            $offline_sql_02 = "from offline_debt_tender tender left join offline_debt debt on tender.debt_id = debt.id
                                             left join offline_deal deal on debt.borrow_id = deal.id
                                             left join offline_deal_load dload on dload.id = debt.tender_id
                                             where dload.black_status = 1 and tender.status = 6 and debt.status = 6 and tender.submit_paytime < $now - $youjie_payment_endtime";
            $tenderCount = Yii::app()->offlinedb->createCommand("select count(*) $offline_sql_02")->queryScalar();
            if($tenderCount > 0){
                $tenderInfo = Yii::app()->offlinedb->createCommand("select tender.id,debt.platform_id $offline_sql_02")->queryAll();
                foreach($tenderInfo as $key => $value){
                    $cancelDebtTenderRet = DebtGardenYoujieQuestionService::getInstance()->TimeoutCustomer(['debt_tender_id' => $value['id'], 'products' => $value['platform_id']]);
                    if($cancelDebtTenderRet['code']!='0'){
                        $errorInfo = Yii::app()->c->errorcodeinfo;
                        $this->echoLog("Expired TimeoutCustomer wrong debt_tender_id {$value['id']}, return:".print_r($errorInfo[$cancelDebtTenderRet['code']],true), 'email');
                    }
                }
            }



            //已承接超时->取消 普惠供应链
            $tenderCount = Yii::app()->phdb->createCommand("select count(*) from firstp2p_debt_tender tender left join firstp2p_debt debt on tender.debt_id = debt.id
                                                     left join firstp2p_deal deal on debt.borrow_id = deal.id
                                                     left join firstp2p_deal_load dload on dload.id = debt.tender_id
                                                     where  dload.black_status = 1 and tender.status = 1 and debt.status = 5 and tender.addtime < $now - $youjie_undertake_endtime and deal.product_class_type = 223")->queryScalar();
            if($tenderCount > 0){
                $tenderInfo = Yii::app()->phdb->createCommand("select tender.id from firstp2p_debt_tender tender left join firstp2p_debt debt on tender.debt_id = debt.id
                                                     left join firstp2p_deal deal on debt.borrow_id = deal.id
                                                     left join firstp2p_deal_load dload on dload.id = debt.tender_id
                                                     where  dload.black_status = 1 and tender.status = 1 and debt.status = 5 and tender.addtime < $now - $youjie_undertake_endtime and deal.product_class_type = 223")->queryAll();
                foreach($tenderInfo as $key => $value){
                    $cancelDebtTenderRet = DebtGardenYoujieQuestionService::getInstance()->CancelTenderDebt(['debt_tender_id' => $value['id'], 'status' => 2, 'products' => 2]);
                    if($cancelDebtTenderRet['code']!='0'){
                        $errorInfo = Yii::app()->c->errorcodeinfo;
                        $this->echoLog("Expired Debt wrong debt_tender_id {$value['id']}, return:".print_r($errorInfo[$cancelDebtTenderRet['code']],true), 'email');
                    }
                }
            }
            //已承接超时->取消 尊享
            $tenderCount = Yii::app()->db->createCommand("select count(*) from firstp2p_debt_tender tender left join firstp2p_debt debt on tender.debt_id = debt.id
                                                     left join firstp2p_deal deal on debt.borrow_id = deal.id
                                                     left join firstp2p_deal_load dload on dload.id = debt.tender_id
                                                     where dload.black_status = 1 and tender.status = 1 and debt.status = 5 and tender.addtime < $now - $youjie_undertake_endtime")->queryScalar();
            if($tenderCount > 0){
                $tenderInfo = Yii::app()->db->createCommand("select tender.id from firstp2p_debt_tender tender left join firstp2p_debt debt on tender.debt_id = debt.id
                                                     left join firstp2p_deal deal on debt.borrow_id = deal.id
                                                     left join firstp2p_deal_load dload on dload.id = debt.tender_id
                                                     where dload.black_status = 1 and tender.status = 1 and debt.status = 5 and tender.addtime < $now - $youjie_undertake_endtime")->queryAll();
                foreach($tenderInfo as $key => $value){
                    $cancelDebtTenderRet = DebtGardenYoujieQuestionService::getInstance()->CancelTenderDebt(['debt_tender_id' => $value['id'], 'status' => 2, 'products' => 1]);
                    if($cancelDebtTenderRet['code']!='0'){
                        $errorInfo = Yii::app()->c->errorcodeinfo;
                        $this->echoLog("Expired Debt wrong debt_tender_id {$value['id']}, return:".print_r($errorInfo[$cancelDebtTenderRet['code']],true), 'email');
                    }
                }
            }
            //待卖方确认超时->客服介入 尊享
            $youjie_payment_endtime = ConfUtil::get('youjie-payment-endtime');
            $tenderCount = Yii::app()->db->createCommand("select count(*) from firstp2p_debt_tender tender left join firstp2p_debt debt on tender.debt_id = debt.id
                                             left join firstp2p_deal deal on debt.borrow_id = deal.id
                                             left join firstp2p_deal_load dload on dload.id = debt.tender_id
                                             where dload.black_status = 1 and tender.status = 6 and debt.status = 6 and tender.submit_paytime < $now - $youjie_payment_endtime")->queryScalar();
            if($tenderCount > 0){
                $tenderInfo = Yii::app()->db->createCommand("select tender.id from firstp2p_debt_tender tender left join firstp2p_debt debt on tender.debt_id = debt.id
                                             left join firstp2p_deal deal on debt.borrow_id = deal.id
                                             left join firstp2p_deal_load dload on dload.id = debt.tender_id
                                             where dload.black_status = 1 and tender.status = 6 and debt.status = 6 and tender.submit_paytime < $now - $youjie_payment_endtime")->queryAll();
                foreach($tenderInfo as $key => $value){
                    $cancelDebtTenderRet = DebtGardenYoujieQuestionService::getInstance()->TimeoutCustomer(['debt_tender_id' => $value['id'], 'products' => 1]);
                    if($cancelDebtTenderRet['code']!='0'){
                        $errorInfo = Yii::app()->c->errorcodeinfo;
                        $this->echoLog("Expired TimeoutCustomer wrong debt_tender_id {$value['id']}, return:".print_r($errorInfo[$cancelDebtTenderRet['code']],true), 'email');
                    }
                }
            }

            //待卖方确认超时->客服介入 普惠供应链
            $youjie_payment_endtime = ConfUtil::get('youjie-payment-endtime');
            $tenderCount = Yii::app()->phdb->createCommand("select count(*) from firstp2p_debt_tender tender left join firstp2p_debt debt on tender.debt_id = debt.id
                                             left join firstp2p_deal deal on debt.borrow_id = deal.id
                                             left join firstp2p_deal_load dload on dload.id = debt.tender_id
                                             where dload.black_status = 1 and tender.status = 6 and debt.status = 6 and deal.product_class_type = 223 and tender.submit_paytime < $now - $youjie_payment_endtime")->queryScalar();
            if($tenderCount > 0){
                $tenderInfo = Yii::app()->phdb->createCommand("select tender.id from firstp2p_debt_tender tender left join firstp2p_debt debt on tender.debt_id = debt.id
                                             left join firstp2p_deal deal on debt.borrow_id = deal.id
                                             left join firstp2p_deal_load dload on dload.id = debt.tender_id
                                             where  dload.black_status = 1 and tender.status = 6 and debt.status = 6 and deal.product_class_type = 223 and tender.submit_paytime < $now - $youjie_payment_endtime")->queryAll();
                foreach($tenderInfo as $key => $value){
                    $cancelDebtTenderRet = DebtGardenYoujieQuestionService::getInstance()->TimeoutCustomer(['debt_tender_id' => $value['id'], 'products' => 2]);
                    if($cancelDebtTenderRet['code']!='0'){
                        $errorInfo = Yii::app()->c->errorcodeinfo;
                        $this->echoLog("Expired TimeoutCustomer wrong debt_tender_id {$value['id']}, return:".print_r($errorInfo[$cancelDebtTenderRet['code']],true), 'email');
                    }
                }
            }

            //释放文件锁
            $this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
            $this->echoLog("debt procExpired end!!!");
            $this->warningEmail();
        } catch (Exception $e) {
            self::echoLog("YoujieDebtTransactionRun Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
    }
    //报警邮件
    public function warningEmail(){
        if(!empty($this->alarm_content) && $this->is_email) {
            FunctionUtil::alertToAccountWx($this->alarm_content);
        }
        return true;
    }
    /**
     * 日志记录
     * @param $yiilog
     * @param string $level
     */
    public function echoLog($yiilog, $level = "info") {
        echo date('Y-m-d H:i:s ')." ".microtime()." YoujieagTransferDebt {$yiilog} \n";
        $this->alarm_content .= $yiilog."<br/>";
        if($level == 'email') {
            $level = "error";
            $this->is_email = true;
        }
        Yii::log("YoujietransferDebt: {$yiilog}", $level, 'YoujieTransferDebt');
    }

    /**
     * 根据项目ID建立文件锁
     */
    private function enterTenderIdFnLock($tender_id){
        $tender_id = (int)$tender_id;
        if($tender_id<=0) {
            self::echoLog($tender_id." illegal!!!");
            exit(1);
        }
        $this->fnLock_tenderid = $this->fnLock_pre.$tender_id.'.pid';
        $fpLock = $this->enterLock(array('fnLock'=>$this->fnLock_tenderid));
        if(!$fpLock){
            self::echoLog($this->fnLock_tenderid." Having Run!!!");
            exit(1);
        }
        return $fpLock;
    }
    /**
     * 检查跑脚本加锁
     */
    private static function releaseLock($config){
        if (!$config['fpLock']){
            return;
        }
        $fpLock = $config['fpLock'];
        $fnLock = $config['fnLock'];
        flock($fpLock, LOCK_UN);
        fclose($fpLock);
        unlink($fnLock);
    }
    /**
     * 跑脚本加锁
     */
    private static function enterLock($config){
        if(empty($config['fnLock'])){
            return false;
        }
        $fnLock = $config['fnLock'];
        $fpLock = fopen( $fnLock, 'w+');
        if($fpLock){
            if ( flock( $fpLock, LOCK_EX | LOCK_NB ) ) {
                return $fpLock;
            }
            fclose( $fpLock );
            $fpLock = null;
        }
        return false;
    }
}
