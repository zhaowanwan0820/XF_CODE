<?php

use core\dao\JobsModel;

class OffexchangeBatchModel extends CommonModel {

    protected $tableName = "exchange_batch";
    const RATENUM = 100000;
    const DEAL_STATUS_ING = 1;
    const DEAL_STATUS_FK = 2;
    const DEAL_STATUS_DONE = 3;

    public function getOexchangeBatchList($where="", $pageNum=1, $pageSize=10, $order=" `id` desc "){
        $pageNum = max(1, intval($pageNum));
        $pageSize = intval($pageSize);
        $offset = ($pageNum - 1) * $pageSize;
        $list = $this->where ( $where )->order ($order)->limit ( $offset.', '.$pageSize)->findAll ();
        $aRet = array();
        if(empty($list)){
            return $aRet;
        }
        foreach($list as $item){
            $aRet[$item['id']] = $this->formatRate($item);
        }
        return $aRet;
    }

    public function getOexchangeBatchCount($where=""){
        return $this->where($where)->count();
    }

    private function formatRate($item){
        $item['amount'] = number_format($item['amount']/100, 2, ".", "");
        $item['consult_rate'] = number_format($item['consult_rate']/self::RATENUM, 5, ".", ""); //借款咨询费率
        $item['consult_fee'] = number_format($item['consult_fee']/100, 2, ".", "");
        $item['guarantee_rate'] = number_format($item['guarantee_rate']/self::RATENUM, 5, ".", "");
        $item['guarantee_fee'] = number_format($item['guarantee_fee']/100, 2, ".", "");
        $item['invest_adviser_rate'] = number_format($item['invest_adviser_rate']/self::RATENUM, 5, ".", "");
        $item['invest_adviser_fee'] = number_format($item['invest_adviser_fee']/100, 2, ".", "");
        $item['publish_server_rate'] = number_format($item['publish_server_rate']/self::RATENUM, 5, ".", "");
        $item['publish_server_fee'] = number_format($item['publish_server_fee']/100, 2, ".", "");
        $item['hang_server_rate'] = number_format($item['hang_server_rate']/self::RATENUM, 5, ".", "");
        $item['hang_server_fee'] = number_format($item['hang_server_fee']/100, 2, ".", "");
        return $item;
    }

    public function getById($id){
        $list = $this->getOexchangeBatchList(" id= ".intval($id));
        if(empty($list[$id])){
            return array();
        }
        return $list[$id];
    }

    public function saveOexchangeBatch($aBatch){
        $oModelP = D("OffexchangeProject");
        $aPro = $oModelP->getById($aBatch['pro_id']);
        if(empty($aBatch['id'])){
            $where = " `pro_id` = ".$aBatch['pro_id'] . " AND deal_status = 1 AND is_ok = 1 ";
            $iCountQT = $this->getOexchangeBatchCount($where);
            if($iCountQT > 0){
                return "有未放款批次不可新增修改";
            }
            $aMaxNumBatch = $this->getOexchangeBatchList(" `pro_id` = ".$aBatch['pro_id'], 1, 1, " `batch_number` desc ");
            $iMaxNum = 0;
            if(!empty($aMaxNumBatch)){
                $maxBatch = array_pop($aMaxNumBatch);
                $iMaxNum =  intval($maxBatch['batch_number']);
            }
            $aBatch['batch_number'] = $iMaxNum + 1;
            //根据银交所项目非首批次挂牌服务费为0修改
            $aConfJys = explode(',', app_conf('EXCHANGE_JYSID_FHANGSERVERFEE'));
            if(in_array($aPro['jys_id'], $aConfJys) && $aBatch['batch_number'] > 1){
                $aBatch['hang_server_rate'] = 0;
            }
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $aBatch['cuid'] = intval($adm_session['adm_id']);

            return $this->add($aBatch);
        }else{
            $aOldBatch = $this->getById($aBatch['id']);
            if(empty($aOldBatch) || $aOldBatch['deal_status'] > 1){
                return false;
            }
            if(empty($aPro) || $aPro['deal_status'] != 2){
                return "项目状态不可放款";
            }
            $where = " `pro_id` = ".$aOldBatch['pro_id'] . " AND deal_status = 1 AND is_ok = 1 AND id <> ".$aBatch['id'];
            $iCountQT = $this->getOexchangeBatchCount($where);
            if($iCountQT > 0){
                return "有未放款批次不可新增修改";
            }

            $aSaveData = array('id'=>intval($aBatch['id']));
            $deal_status = intval($aBatch['deal_status']);
            $is_ok = intval($aBatch['is_ok']);
            $is_last_start = intval($aBatch['is_last_start']);
            $give_money_time = trim($aBatch['give_money_time']);
            if($deal_status == 1){
            }elseif($deal_status == 2){//放款
                if($is_ok == 0 || empty($give_money_time)){
                    return "放款不能作废或放款时间未空";
                }
                if($is_last_start){
                    $aHaveLast = M('exchange_batch')->where(" `pro_id` = ".$aOldBatch['pro_id']." and deal_status in (2,3) and `is_ok` = 1 and `is_last_start` = 1")->select();
                    if(!empty($aHaveLast)){
                        return "本项目已经有最后批次起息";
                    }
                }
                //只有变为还款中才记录时间
                $aSaveData['give_money_time'] = $give_money_time;
                $aSaveData['repay_start_time'] = strtotime($give_money_time);
                //批次金额  分
                $iBatchAmount = M('exchange_load')->where(" `batch_id` = ".$aBatch['id']." and `project_id` = ".$aOldBatch['pro_id']." and `status` = 1 ")->sum('pay_money');
                if(empty($iBatchAmount)){
                    return "批次金额为0";
                }
            }else{
                return false;
            }

            $aSaveData['is_ok'] = $is_ok;
            $aSaveData['deal_status'] = $deal_status;
            $aSaveData['is_last_start'] = $is_last_start;
            $aSaveData['utime'] = date('Y-m-d H:i:s');

            //更新批次信息
            $this->startTrans();
            if($aSaveData['is_last_start'] != $aOldBatch['is_last_start']){
                $bFee = $this->updateBatchFee($aSaveData['id'], $aSaveData['is_last_start']);
                if(!$bFee){
                    $this->rollback();
                    return "费用更新失败";
                }
            }
            $bSave = $this->save($aSaveData);
            if(!$bSave){
                $this->rollback();
                return "批次信息更新失败";
            }
            if($deal_status == self::DEAL_STATUS_FK){//放款
                //更新项目信息
                $aProSave = array("id" => $aOldBatch['pro_id'], "real_amount" => $aPro['real_amount'] * 100 + $iBatchAmount, "utime" => $aSaveData['utime']);
                if($is_last_start){
                    $aProSave['give_money_time'] = $give_money_time;
                    $aProSave['deal_status'] = 3;
                }
                $bProSave = $oModelP->save($aProSave);
                if($bProSave === false){
                    $this->rollback();
                    return "更新项目信息失败";
                }
                //添加还款回款计划jobs
                $function  = '\core\service\ExchangeService::genBatchLoadRepayPlan';
                $bJobs = JobsModel::instance()->addJob($function, array(array('batchId' => $aSaveData['id'])));
                if(!$bJobs) {
                    $this->rollback();
                    return "还款回款计划jobs添加失败";
                }
            }
            if($aSaveData['is_ok'] == 0){
                //批次作废同时作废投资记录
                $res = M('ExchangeLoad')->execute("UPDATE firstp2p_exchange_load SET `status` = 2 WHERE batch_id = {$aSaveData['id']};");
                if (false === $res) {
                    $model->rollback();
                    return $this->error("作废投资记录, 请重试");
                }
            }
            $this->commit();
            if($deal_status == self::DEAL_STATUS_FK && $is_last_start){
                $function  = '\core\service\ExchangeProjectService::synProjectStatus';
                JobsModel::instance()->addJob($function, array(array('projectId' => $aProSave['id'])));
            }
            return $bSave;
        }
    }

    public function updateBatchFee($iBatchId, $isLast = false){
        $iBatchId = intval($iBatchId);
        $aBatch = $this->getById($iBatchId);
        if(empty($aBatch) || $aBatch['deal_status'] != self::DEAL_STATUS_ING){
            return false;
        }
        $oModelP = D("OffexchangeProject");
        $aPro = $oModelP->getById($aBatch['pro_id']);
        if(empty($aPro) || $aPro['deal_status'] != 2){
            return false;
        }
        $iBatchAmount = M('exchange_load')->where(" `batch_id` = $iBatchId and `project_id` = ".$aBatch['pro_id']." and `status` = 1 ")->sum('pay_money');
        $aSaveData = array('id'=>intval($iBatchId));
        $aSaveData['amount'] = $iBatchAmount;
        $iDanwei = $aPro['repay_type'] == 1 ? 360 : 12;
        $aSaveData['consult_fee'] = intval(round($aBatch['consult_rate'] * $iBatchAmount * $aPro['repay_time'] / (100 * $iDanwei), 5));
        $aSaveData['guarantee_fee'] = intval(round($aBatch['guarantee_rate'] * $iBatchAmount * $aPro['repay_time'] / (100 * $iDanwei), 5));
        $aSaveData['invest_adviser_fee'] = intval(round($aBatch['invest_adviser_rate'] * $iBatchAmount * $aPro['repay_time'] / (100 * $iDanwei), 5));
        $aSaveData['publish_server_fee'] = intval(round($aBatch['publish_server_rate'] * $iBatchAmount * $aPro['repay_time'] / (100 * $iDanwei), 5));
        //根据银交所项目非首批次挂牌服务费为0修改
        $aConfJys = explode(',', app_conf('EXCHANGE_JYSID_FHANGSERVERFEE'));
        if(in_array($aPro['jys_id'], $aConfJys)){
            //$aSaveData['hang_server_fee'] = intval(round($aBatch['hang_server_rate'] * $aPro['amount'] * 100 * $aPro['repay_time'] / (100 * $iDanwei), 5));
            $aSaveData['hang_server_fee'] = intval(round($aBatch['hang_server_rate'] * $aPro['amount'] * $aPro['repay_time'] / $iDanwei, 5));//上面公式化简
        }else{
            $aSaveData['hang_server_fee'] = intval(round($aBatch['hang_server_rate'] * $iBatchAmount * $aPro['repay_time'] / (100 * $iDanwei), 5));
        }
        if( ($isLast || $aBatch['is_last_start'])
            &&
            ($aPro['invest_adviser_real_rate'] != $aBatch['invest_adviser_rate'] || $aPro['publish_server_real_rate'] != $aBatch['publish_server_rate'])
        ){//最后批次起息 需重新计算费率
            //计算实际投资顾问费率
            if($aPro['invest_adviser_real_rate'] != $aBatch['invest_adviser_rate']){
                $iQTBatchInvestAdviserFee = M('exchange_batch')->where(" `pro_id` = ".$aBatch['pro_id']." and deal_status in (2,3) and `is_ok` = 1")->sum('invest_adviser_fee');
                $iTaTalInvestAdviserFee = intval(round(($aPro['real_amount']*100 + $iBatchAmount) * $aPro['invest_adviser_real_rate'] * $aPro['repay_time'] / (100 * $iDanwei), 5));
                $aSaveData['invest_adviser_fee'] = $iTaTalInvestAdviserFee - $iQTBatchInvestAdviserFee;
                $aSaveData['invest_adviser_fee'] = max(0, $aSaveData['invest_adviser_fee']);
                $aSaveData['invest_adviser_rate'] = intval(round($aSaveData['invest_adviser_fee'] * $iDanwei / ($iBatchAmount * $aPro['repay_time']) * 100 * self::RATENUM, 5));
            }
            //计算实际发行服务费率
            if($aPro['publish_server_real_rate'] != $aBatch['publish_server_rate']){
                $iQTBatchPublishServerFee = M('exchange_batch')->where(" `pro_id` = ".$aBatch['pro_id']." and deal_status in (2,3) and `is_ok` = 1")->sum('publish_server_fee');
                $iTaTalPublishServerFee = intval(round(($aPro['real_amount']*100 + $iBatchAmount) * $aPro['publish_server_real_rate'] * $aPro['repay_time'] / (100 * $iDanwei), 5));
                $aSaveData['publish_server_fee'] = $iTaTalPublishServerFee - $iQTBatchPublishServerFee;
                $aSaveData['publish_server_fee'] = max(0, $aSaveData['publish_server_fee']);
                $aSaveData['publish_server_rate'] = intval(round($aSaveData['publish_server_fee'] * $iDanwei / ($iBatchAmount * $aPro['repay_time']) * 100 * self::RATENUM, 5));
            }
        }
        $aSaveData['utime'] = date('Y-m-d H:i:s');
        $aSaveData['fee_time'] = $aSaveData['utime'];
        return $this->save($aSaveData);
    }

}
