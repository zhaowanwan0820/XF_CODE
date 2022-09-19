<?php
/**
 * Created by PhpStorm.
 * User: itouzi
 * Date: 2018/8/24
 * Time: 18:01
 */
class ZxPlanClass {
    /**
     * 强制退出智选计划
     * @param $userId
     * @param $tenderId
     */
    public function handleExit($userId, $tenderId)
    {

        $returnResult = array("code" => -1, "info" => "", "data" => []);
        $errorCodes = Yii::app()->c->apicodeconfig;
        if (empty($userId) || empty($tenderId)) {
            $returnResult['code'] = 1;
            $returnResult['info'] = $errorCodes[$returnResult['code']];
            return $returnResult;
        }

        // 发起退出记录数据
        $requestNo = FunctionUtil::getRequestNo("QUITJH");
        $quitInfo = new ItzUndoInfo();
        $quitInfo->request_no = $requestNo;
        $quitInfo->user_id = $userId;
        $quitInfo->app_id = $tenderId;
        $quitInfo->device = 'pc';
        $quitInfo->type = 2;
        $quitInfo->status = 1;
        $quitInfo->addtime = time();
        $quitInfo->addip = '127.0.0.1';
        if (!$quitInfo->save()) {
            $returnResult['code'] = 99;
            return $returnResult;
        }

        Yii::app()->db->beginTransaction();
        try {
            $sql = "SELECT * FROM dw_borrow_tender WHERE id=:tender_id for update";
            $tenderInfo = Yii::app()->db->createCommand($sql)->bindValues(array(":tender_id" => $tenderId))->queryRow();
            // $tenderInfo = BorrowTender::model()->findByPk($tenderId);
            if (empty($tenderInfo)) {
                Yii::app()->db->rollback();
                $returnResult['code'] = 3;
                $returnResult['info'] = $errorCodes[$returnResult['code']];
                return $returnResult;
            }
            if ($tenderInfo['status'] != 1) {
                Yii::app()->db->rollback();
                $returnResult['code'] = 4;
                $returnResult['info'] = $errorCodes[$returnResult['code']];
                return $returnResult;
            }
            if ($tenderInfo['debt_status'] == 1) {
                Yii::app()->db->rollback();
                $returnResult['code'] = 5;
                $returnResult['info'] = $errorCodes[$returnResult['code']];
                return $returnResult;
            }
            if ($tenderInfo['debt_status'] > 0) {
                Yii::app()->db->rollback();
                $returnResult['code'] = 6;
                $returnResult['info'] = $errorCodes[$returnResult['code']];
                return $returnResult;
            }

            //更新为退出成功
            $updateSql = "UPDATE itz_wise_tender SET status=17 WHERE  tender_id=$tenderId AND status=2 and surplus_capital=0  and user_id={$userId}";
            $res = Yii::app()->db->createCommand($updateSql)->execute();

            //更新续投中资金
            $be_continue_capital = $tenderInfo['be_continue_capital'];

            // 还息数据
            $dividend = array();
            if ($be_continue_capital > 0) {
                // 取消续投
                $cancelRes = WisePlanService::getInstance()->cancelUserContinue($tenderId);
                if ($cancelRes['code'] != 0) {
                    Yii::app()->db->rollback();
                    Yii::log("cancelUserContinue error,tender_id:" . $tenderId, "error", __FUNCTION__);
                    $returnResult['code'] = 5335;
                    $returnResult['info'] = $errorCodes[$returnResult['code']];
                    return $returnResult;
                }
                if (count($cancelRes['data']['xw_data']) > 0) {
                    $dividend = $cancelRes['data']['xw_data'];
                }
                $edit_tender_ret = BorrowTender::model()->updateByPk($tenderId, array('be_continue_capital' => 0));
                var_dump($tenderId, $edit_tender_ret);
                if ($edit_tender_ret === false) {
                    var_dump('edit borrow_tender be_continue_capital error');
                    return $returnResult;
                }
                $moneyDetail = array(
                    "money_total" => $tenderInfo['be_continue_capital'],
                    "money_real" => $tenderInfo['be_continue_capital'],
                    "money_real_recharge" => 0,
                    "money_invested" => $tenderInfo['be_continue_capital'],
                    "recharge_detail" => []
                );
                $borrowInfo = Yii::app()->db->createCommand("select id,type from dw_borrow where id=" . $tenderInfo['borrow_id'])->queryRow();
                $itzFr = WisePlanService::getInstance()->unfreezeMoneyAndCoupon($userId, $tenderInfo['be_continue_capital'], $moneyDetail, "wait_continue", $tenderInfo['id'], $borrowInfo);
                if ($itzFr === false) {
                    Yii::app()->db->rollback();
                    Yii::log("unfreezeMoneyAndCoupon error tenderInfo:" . $tenderInfo['id'] . "money:" . $tenderInfo['be_continue_capital'], "error", __FUNCTION__);
                    $error_log = "取消续投解冻爱投资账户用户资金失败，tenderInfo:" . $tenderInfo['id'] . "money:" . $tenderInfo['be_continue_capital'];
                    // 发送报警
                    FunctionUtil::alertToAccountTeam($error_log, array(), true);
                    $returnResult['code'] = 5335;
                    $returnResult['info'] = $errorCodes[$returnResult['code']];
                    return $returnResult;
                }
                // 待取消续投
                $capitalUnfreeze = WisePlanService::getInstance()->capitalUnfreeze($tenderInfo['be_continue_capital'], $userId);
                if (!$capitalUnfreeze) {
                    Yii::app()->db->rollback();
                    Yii::log("capitalUnfreeze error tender_id:" . $tenderId, CLogger::LEVEL_ERROR, __FUNCTION__);
                    $returnResult['code'] = 2271;
                    $returnResult['info'] = $errorCodes[$returnResult['code']];
                    return $returnResult;
                }
                // 给用户批量还息
                if (count($dividend) > 0) {
                    $request_data = array(
                        'serviceName' => 'ASYNC_TRANSACTION',
                        'userDevice' => 'PC',
                    );
                    $request_data['reqData']['batchNo'] = FunctionUtil::getRequestNo("PLANBC");
                    $request_data['reqData']['bizDetails'] = $dividend;
                    $result = CurlService::getInstance()->service($request_data);
                    if ($result['code'] != 0) {
                        $msg = "RepayInterest: request_data: " . print_r($request_data, true) . " return: " . print_r($result, true);
                        Yii::log($msg, CLogger::LEVEL_ERROR, __FUNCTION__);
                        Yii::app()->db->rollback();
                        $returnResult['code'] = 5333;
                        $returnResult['info'] = $errorCodes[$returnResult['code']];
                        return $returnResult;
                    }
                }
            }

            $continueAmount = 0;
            $continueTenderId = array();
            $quitMoney = 0;
            $quitId = array();
            $qiutTenderInfo = array();
            $queryWiseTenderSql = "SELECT * FROM itz_wise_tender WHERE tender_id=:tender_id AND status in (0,1,2,16) and surplus_capital>0 for update";
            $wiseTenders = Yii::app()->db->createCommand($queryWiseTenderSql)->bindValues(array(":tender_id" => $tenderId))->queryAll();
            if (!empty($wiseTenders)) {
                foreach ($wiseTenders as $k => &$v) {
                    if ($v['status'] == 1 || $v['status'] == 0) {
                        Yii::app()->db->rollback();
                        Yii::log("itz_wise_borrow_id[{$v['wise_borrow_id']}] not exist ", "error");
                        $returnResult['code'] = 8;
                        $returnResult['info'] = $errorCodes[$returnResult['code']];
                        return $returnResult;
                    }
                    //校验wise_borrow表数据
                    $wise_borrow = ItzWiseBorrow::model()->find("wise_borrow_id = '{$v['wise_borrow_id']}'")->attributes;
                    if (!$wise_borrow) {
                        Yii::app()->db->rollback();
                        Yii::log("itz_wise_borrow_id[{$v['wise_borrow_id']}] not exist ", "error");
                        $returnResult['code'] = 9;
                        $returnResult['info'] = $errorCodes[$returnResult['code']];
                        return $returnResult;
                    }
                    if ($v['status'] == 2) {
                        $quitMoney += $v['surplus_capital'];
                        $quitId[] = $v['id'];
                        $qiutTenderInfo[$v['id']]['apr'] = $wise_borrow['apr'];
                    }
                }
            }


            // 需要退出的金额
            if (FunctionUtil::float_bigger($quitMoney, 0, 3)) {
                $wisePlanSql = "select * from itz_wise_plan_collection where wise_tender_id in (" . implode(",", $quitId) . ") and status in(0,2,4)";
                $planCollection = Yii::app()->db->createCommand($wisePlanSql)->queryAll();
                if (empty($planCollection)) {
                    Yii::app()->db->rollback();
                    Yii::log("ItzWisePlanCollection not exist,wise_tender_id:" . print_r($quitId, true), "error", __FUNCTION__);
                    $returnResult['code'] = 10;
                    $returnResult['info'] = $errorCodes[$returnResult['code']];
                    return $returnResult;
                }
                $wisePlanMoney = 0;
                $needQuitMoney = 0;
                $realQuitId = array();
                $realQuitInfo = array();
                foreach ($planCollection as $k => &$v) {
                    $wisePlanMoney += $v['capital'];
                    // 计划今日还款
                    if ($v['repay_time'] >= strtotime("midnight") && $v['repay_time'] < strtotime("midnight") + 86400) {
                        continue;
                    } else {
                        $realQuitId[] = $v['wise_tender_id'];
                        $needQuitMoney += $v['capital'];
                        $realQuitInfo[] = array(
                            "user_id" => $userId,
                            "wise_tender_id" => $v['wise_tender_id'],
                            "tender_id" => $tenderId,
                            "quit_amount" => $v['capital'],
                            "wait_quit_amount" => $v['capital'],
                            "borrow_id" => $tenderInfo['borrow_id'],
                            "wise_borrow_apr" => $qiutTenderInfo[$v['wise_tender_id']]['apr'],
                            "collection_repay_time" => $v['repay_time'],
                        );
                    }
                }
                if (!FunctionUtil::float_equal($quitMoney, $wisePlanMoney, 3)) {
                    Yii::log("ItzWisePlanCollection amount not equal,tender_id:" . $tenderId . " quitMoney:{$quitMoney} wisePlanMoney:{$wisePlanMoney}", "error", __FUNCTION__);
                    Yii::app()->db->rollback();
                    $returnResult['code'] = 11;
                    $returnResult['info'] = $errorCodes[$returnResult['code']];
                    return $returnResult;
                }
                // 退出中
                if (count($realQuitId) > 0) {
                    $updateWiseTender = "UPDATE itz_wise_tender SET status=19 WHERE id in (" . implode(",", $realQuitId) . ")";
                    $ret = Yii::app()->db->createCommand($updateWiseTender)->execute();
                    if ($ret == false) {
                        Yii::log("updateWiseTender error wise_tender_id:" . print_r($quitId, true), "error", __FUNCTION__);
                        Yii::app()->db->rollback();
                        $returnResult['code'] = 12;
                        $returnResult['info'] = $errorCodes[$returnResult['code']];
                        return $returnResult;
                    }
                    $insertSql = WisePlanService::getInstance()->joinInsertInvestExitSql($realQuitInfo);
                    $ret = Yii::app()->db->createCommand($insertSql)->execute();
                    if ($ret == false) {
                        Yii::log("save investExit error:" . print_r($realQuitInfo, true), "error", __FUNCTION__);
                        Yii::app()->db->rollback();
                        $returnResult['code'] = 13;
                        $returnResult['info'] = $errorCodes[$returnResult['code']];
                        return $returnResult;
                    }
                    // 更新borrow表的debt_account
                    $updateBorrow = "UPDATE dw_borrow SET debt_account=debt_account+" . $needQuitMoney . " WHERE id=" . $tenderInfo['borrow_id'];
                    $ret = Yii::app()->db->createCommand($updateBorrow)->execute();
                    if ($ret == false) {
                        Yii::log("update dw_borrow error borrow_id:" . $tenderInfo['borrow_id'] . " +debt_account:" . $needQuitMoney, "error", __FUNCTION__);
                        Yii::app()->db->rollback();
                        $returnResult['code'] = 14;
                        $returnResult['info'] = $errorCodes[$returnResult['code']];
                        return $returnResult;
                    }
                }
                $ret = BaseCrudService::getInstance()->update("BorrowTender", array("id" => $tenderInfo['id'], "debt_status" => 1, "quit_apply_time" => time()), "id");
                if ($ret == false) {
                    Yii::log("updateBorrowTender debt_status error tender_id:" . $tenderId, "error", __FUNCTION__);
                    Yii::app()->db->rollback();
                    $returnResult['code'] = 15;
                    $returnResult['info'] = $errorCodes[$returnResult['code']];
                    return $returnResult;
                }

            }
            $ret = BaseCrudService::getInstance()->update("ItzUndoInfo", array("id" => $quitInfo->id, "status" => 2), "id");
            if ($ret == false) {
                Yii::log("updateItzUndoInfo status error id:" . $quitInfo->id, "error", __FUNCTION__);
                Yii::app()->db->rollback();
                $returnResult['code'] = 16;
                $returnResult['info'] = $errorCodes[$returnResult['code']];
                return $returnResult;
            }
            //新手申请退出成功，发送奖励
            if ($tenderInfo['extra_reward_type'] == 2) {
                $repayTime = time();
                $ret = ItzBorrowReward::model()->updateAll(array('repay_time' => $repayTime), 'tender_id=:tender_id and novice_project=1', array(':tender_id' => $tenderId));
                if ($ret == false) {
                    Yii::log("updateIItzBorrowReward repay_time error tender_id:" . $tenderId, "error", __FUNCTION__);
                    Yii::app()->db->rollback();
                    $returnResult['code'] = 5335;
                    $returnResult['info'] = $errorCodes[$returnResult['code']];
                    return $returnResult;
                }
            }
            Yii::app()->db->commit();
            $returnResult['code'] = 0;
            return $returnResult;
        } catch (Exception $e) {
            Yii::app()->db->rollback();
            Yii::log("quit zxjh error args: " . print_r(func_get_args(), true) . " error info " . print_r($e->getMessage(), true), "error", __FUNCTION__);
            $returnResult['code'] = 17;
            $returnResult['info'] = $errorCodes[$returnResult['code']];
            return $returnResult;
        }

    }
}