<?php

/**
 * 自动债转已付款的收购债权 仅支持普惠及智多新
 * Class ExclusivePurchaseCommand
 */
class ExclusivePurchaseCommand extends CConsoleCommand {
	public $fnLock_pre = '/tmp/ExclusivePurchase';	//项目ID文件锁的前缀
	public $fnLock_tenderid = '';//投资记录ID文件
	public $alarm_content = '';//报警内容
	public $is_email = false;
	public $where = ' ';
	public $buyer_uid = '';
	public $frozen_amount = 0.00;//交易金额
    const TempDir = '/tmp/contractV2/';
    const DisplaceTempDir = '/tmp/displaceContract/';


	/**
	 * 自动债转已付款的收购债权
	 * @param string $id 指定收购记录ID
	 * @return bool
	 */
	public function actionRun($id=''){
		$this->echoLog("ExclusivePurchase run start");
		
		//指定求购计划单笔处理
		if(!empty($id)){
			$this->where .= " and id=$id ";
		}
		
		try {
			//查询待自动债转的求购
            $purchase_sql = "select  * from xf_exclusive_purchase where status=2 {$this->where} ";
            $purchase_list = Yii::app()->phdb->createCommand($purchase_sql)->queryAll();
			if(empty($purchase_list)) {
				$this->echoLog("actionRun: xf_exclusive_purchase is empty");
				return false;
			}
			$this->echoLog("actionRun: purchase_list count:".count($purchase_list));

			$f = $s = 0;
			foreach($purchase_list as $key => $value){
				$purchase_id = $value['id'];

				//根据求购记录ID加文件锁
				$fpLock = $this->enterTenderIdFnLock($purchase_id);

				//逐一债转处理
				$result = $this->actionHandelDebt($purchase_id);
				if($result == false){
					$f++;
					$this->echoLog("ExclusivePurchase handelDebt return false; purchase_id: $purchase_id", "email");
				}else{
					$s++;
					$this->echoLog("ExclusivePurchase handelDebt return true; purchase_id: $purchase_id; ");
				}

				sleep(1);
				//释放文件锁
				$this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
			}

			//增加短信报警
			if($f>0){
				$error_info = "YJ_DEBT_ALARM：DebtTransaction_fail_count：$f";
				$send_ret = SmsIdentityUtils::fundAlarm($error_info, 'DebtTransaction');
				$this->echoLog("ExclusivePurchase handelDebt sendAlarm return $send_ret ");
			}

			$this->echoLog("ExclusivePurchase actionRun end, success_count:$s; fail_count:$f; ");

		} catch (Exception $e) {
			self::echoLog("ExclusivePurchase actionRun Exception,error_msg:".print_r($e->getMessage(),true), "email");
		}

		//邮件报警错误详情
		$this->warningEmail();
	}

	/**
	 * 单笔求购记录记录处理
	 */
	public function actionHandelDebt($id){
		$this->echoLog("handelDebt start id:$id");
		if(empty($id) || !is_numeric($id)){
			$this->echoLog("handelDebt end id:$id params error");
			return false;
		}
		Yii::app()->phdb->beginTransaction();
        Yii::app()->offlinedb->beginTransaction();
		try{
			//兑换记录
			$exchange_log = XfExclusivePurchase::model()->findBySql("select * from xf_exclusive_purchase where id=:id for update", array(':id' => $id));
			if(!$exchange_log || $exchange_log->status != 2   ){
				$this->echoLog("handelDebt end id:$id   status[$exchange_log->status]!=2 || end_time{$exchange_log->end_time} error");
                Yii::app()->offlinedb->rollback();
				Yii::app()->phdb->rollback();
				return false;
			} 

			//获取回购人
            $this->buyer_uid = $exchange_log->purchase_user_id;
			if(empty($this->buyer_uid) || !is_numeric($this->buyer_uid)){
				$this->echoLog("handelDebt end id:$id buyer_uid empty! ");
                Yii::app()->offlinedb->rollback();
                Yii::app()->phdb->rollback();
				return false;
			}

			$assignee_info = XfPurchaseAssignee::model()->findBySql("select * from xf_purchase_assignee where user_id={$this->buyer_uid}  and status=2 for update");
			if(!$assignee_info || FunctionUtil::float_bigger($exchange_log->wait_capital, $assignee_info->frozen_quota, 2)){
				$this->echoLog("handelDebt end id:$id xf_purchase_assignee or frozen_quota  error ");
                Yii::app()->offlinedb->rollback();
                Yii::app()->phdb->rollback();
				return false;
			}

			//获取智多新需要债转的投资记录ID
            $select_sql = ",dl.create_time,dl.user_id,dl.deal_id,dl.id,dl.status,dl.xf_status,dl.black_status,dl.debt_type,dl.wait_capital ";
            $offline_list_load_sql = "select '4' as platform_no  {$select_sql}    from offline_deal_load dl  where dl.exclusive_purchase_id ={$id}   ";
            $offline_load_list = Yii::app()->offlinedb->createCommand($offline_list_load_sql)->queryAll();

            //普惠收购记录
            $list_load_sql = "select '2' as platform_no {$select_sql}  from firstp2p_deal_load dl  where dl.exclusive_purchase_id ={$id}   ";
            $load_list = Yii::app()->phdb->createCommand($list_load_sql)->queryAll();

            $all_deal_load = array_merge($offline_load_list, $load_list);
            if(empty($all_deal_load)){
                $this->echoLog("handelDebt end id:$id firstp2p_deal_load error ");
                Yii::app()->offlinedb->rollback();
                Yii::app()->phdb->rollback();
                return false;
            }
            $c_wait_capital = 0;
            foreach ($all_deal_load as $key=>$value){
                //校验基础数据
                if($value['status'] != 1 || $value['xf_status'] != 0 || $value['black_status'] != 1 || $value['wait_capital'] <= 0 || $value['user_id'] != $exchange_log->user_id){
                    $this->echoLog("handelDebt end id:$id deal_load {$value['id']} error ");
                    Yii::app()->offlinedb->rollback();
                    Yii::app()->phdb->rollback();
                    return false;
                }
                //普惠债转
                if($value['platform_no'] == 2){
                    //创建债权
                    $c_data = array();
                    $c_data['user_id'] = $value['user_id'];
                    $c_data['money'] = $value['wait_capital'];
                    $c_data['discount'] = 0;
                    $c_data['deal_loan_id'] = $value['id'];
                    $c_data['debt_src'] = 6;
                    $c_data['exclusive_purchase_id'] = $id;
                    $create_ret = PhDebtService::getInstance()->createDebt($c_data);
                    if ($create_ret === false || $create_ret['code'] != 0 || empty($create_ret['data'])) {
                        $this->echoLog("handelDebt createDebt tender_id {$value['id']} false:".print_r($create_ret,true), 'error', 'email');
                        Yii::app()->offlinedb->rollback();
                        Yii::app()->phdb->rollback();
                        return false;
                    }

                    //创建成功日志
                    $this->echoLog("handelDebt createDebt tender_id {$value['id']} success");

                    //债权认购
                    $debt_data = array();
                    $debt_data['user_id'] = $this->buyer_uid;
                    $debt_data['money'] = $value['wait_capital'];
                    $debt_data['debt_id'] = $create_ret['data']['debt_id'];
                    $debt_data['frozen_amount'] = $this->frozen_amount;
                    $debt_data['oss_contract_url'] = $exchange_log->oss_contract_url;
                    $debt_transaction_ret = PhDebtService::getInstance()->debtPreTransaction($debt_data);
                    if($debt_transaction_ret['code'] != 0){
                        $this->echoLog("handelDebt debtPreTransaction tender_id {$value['id']} false:".print_r($debt_transaction_ret,true), 'error', 'email');
                        Yii::app()->offlinedb->rollback();
                        Yii::app()->phdb->rollback();
                        return false;
                    }
                }

                //智多新债转
                if($value['platform_no'] == 4){
                    //创建债权
                    $c_data = array();
                    $c_data['user_id'] = $value['user_id'];
                    $c_data['money'] = $value['wait_capital'];
                    $c_data['discount'] = 0;
                    $c_data['deal_loan_id'] = $value['id'];
                    $c_data['debt_src'] = 6;
                    $c_data['platform_no'] = 4;
                    $c_data['exclusive_purchase_id'] = $id;
                    $create_ret = OfflineDebtService::getInstance()->createDebt($c_data);
                    if ($create_ret === false || $create_ret['code'] != 0 || empty($create_ret['data'])) {
                        $this->echoLog("offlineHandelDebt createDebt tender_id {$value['id']} false:".print_r($create_ret,true), 'error', 'email');
                        Yii::app()->offlinedb->rollback();
                        Yii::app()->phdb->rollback();
                        return false;
                    }

                    //创建成功日志
                    $this->echoLog("offlineHandelDebt createDebt tender_id {$value['id']} success");

                    //债权认购
                    $debt_data = array();
                    $debt_data['user_id'] = $this->buyer_uid;
                    $debt_data['money'] = $value['wait_capital'];
                    $debt_data['debt_id'] = $create_ret['data']['debt_id'];
                    $debt_data['frozen_amount'] = $this->frozen_amount;
                    $debt_data['platform_no'] = 4;
                    $debt_data['oss_contract_url'] = $exchange_log->oss_contract_url;
                    $debt_transaction_ret = OfflineDebtService::getInstance()->debtPreTransaction($debt_data);
                    if($debt_transaction_ret['code'] != 0){
                        $this->echoLog("offlineHandelDebt debtPreTransaction tender_id {$exchange_log->tender_id} false:".print_r($debt_transaction_ret,true), 'error', 'email');
                        Yii::app()->offlinedb->rollback();
                        Yii::app()->phdb->rollback();
                        return false;
                    }
                }

                //累计交易加和
                $c_wait_capital = bcadd($c_wait_capital, $value['wait_capital'], 2);
            }

            //累计交易必须要收购债权本金一致
            if(!FunctionUtil::float_equal($exchange_log->wait_capital, $c_wait_capital, 2)){
                $this->echoLog("handelDebt end id:$id  purchase_wait_capital:$exchange_log->wait_capital != c_wait_capital:$c_wait_capital error ");
                Yii::app()->offlinedb->rollback();
                Yii::app()->phdb->rollback();
                return false;
            }


			//求购记录数据更新
			$changeLogRet = BaseCrudService::getInstance()->update("XfExclusivePurchase",array(
				"id" => $id,
				"status" => 3
			), "id");
			if(!$changeLogRet){
				$this->echoLog("DebtExchangeLog update error, id=$exchange_log->id");
                Yii::app()->offlinedb->rollback();
                Yii::app()->phdb->rollback();
				return false;
			}

			//更新收购额度
			$assignee_info->transferred_amount = bcadd($assignee_info->transferred_amount, $exchange_log->wait_capital, 2);
            $assignee_info->frozen_quota = bcsub($assignee_info->frozen_quota, $exchange_log->wait_capital, 2);
			if($assignee_info->save(false, array('transferred_amount', 'frozen_quota')) == false){
				$this->echoLog("DebtExchangeLog update xf_purchase_assignee transferred_amount[$assignee_info->transferred_amount] error, id=$exchange_log->id");
                Yii::app()->offlinedb->rollback();
                Yii::app()->phdb->rollback();
				return false;
			}

			//债转成功数据确认
			Yii::app()->offlinedb->commit();
            Yii::app()->phdb->commit();
			$this->echoLog("handelDebt id:$id;  success");
			return true;
		}catch (Exception $ee) {
			$this->echoLog("handelDebt id:$id; exception:".print_r($ee->getMessage(), true));
            Yii::app()->offlinedb->rollback();
            Yii::app()->phdb->rollback();
			return false;
		}
	}

	/**
	 * 日志记录
	 * @param $yiilog
	 * @param string $level
	 */
	public function echoLog($yiilog, $level = "info") {
		echo date('Y-m-d H:i:s ')." ".microtime()." wx_zxTransferDebt {$yiilog} \n";
		$this->alarm_content .= $yiilog."<br/>";
		if($level == 'email') {
			$level = "error";
			$this->is_email = true;
		}
		Yii::log("transferDebt: {$yiilog}", $level, 'wx_zxTransferDebt');
	}

	//报警邮件
	public function warningEmail(){
        return true;
		if(!empty($this->alarm_content) && $this->is_email ) {
			FunctionUtil::alertToAccountWx($this->alarm_content);
		}
		return true;
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
     * 定向收购自动签署合同
     * @param string $id
     * @return false
     */
    public function actionAutoSignContract($id=''){
        $this->echoLog("ExclusivePurchase AutoSignContract start");

        //指定求购计划单笔处理
        if(!empty($id)){
            $this->where .= " and id=$id ";
        }

        try {
            //查询待自动债转的求购
            $purchase_sql = "select  * from xf_exclusive_purchase where status=3 {$this->where} ";
            $purchase_list = Yii::app()->phdb->createCommand($purchase_sql)->queryAll();
            if(empty($purchase_list)) {
                $this->echoLog("AutoSignContract: xf_exclusive_purchase is empty");
                return false;
            }
            $this->echoLog("AutoSignContract: purchase_list count:".count($purchase_list));

            $f = $s = 0;
            foreach($purchase_list as $key => $value){
                $contract_id = $value['contract_id'];

                $purchase_user_sql = "select  * from xf_assignee_user where status=1 and purchase_status=0 and user_id={$value['user_id']}";
                $purchase_user = Yii::app()->phdb->createCommand($purchase_user_sql)->queryRow();
                if(!$purchase_user){
                    $this->echoLog("AutoSignContract: xf_assignee_user error");
                    return false;
                }

                //受让人法大大账户ID
                 $user_info = User::model()->findByPk($value['purchase_user_id']);
                 if(!$user_info){
                     $this->echoLog("AutoSignContract: purchase_user_id user_info error");
                     return false;
                 }

                 //根据求购记录ID加文件锁
                $fpLock = $this->enterTenderIdFnLock($contract_id);
                //$user_info['yj_fdd_customer_id'] = '9080F8FBA5DEFD5FEABCD605B51628D4';
                $result = XfFddService::getInstance()->invokeExtSignAuto($user_info['yj_fdd_customer_id'], $contract_id, '债权转让协议', 'B盖签');
                if(!$result || $result['code'] != 1000){
                    $this->echoLog("AutoSignContract: $contract_id 合同签署失败   \n" . print_r($result,true));
                    return false;
                }
                //$result['download_url'] = 'https://testapi.fadada.com:8443/api//getdocs.action?app_id=402877&timestamp=20210909190505&v=2.0&msg_digest=QzQwQTA2MDkzMkVFM0JBOEU3MUZFMjhCMTE4NTVERTUxMDc0N0ExQQ==&transaction_id=6139ea4961e07094846910&send_app_id=null';
                //$result['viewpdf_url'] = 'https://testapi.fadada.com:8443/api//viewdocs.action?app_id=402877&timestamp=20210909190505&v=2.0&msg_digest=QzQwQTA2MDkzMkVFM0JBOEU3MUZFMjhCMTE4NTVERTUxMDc0N0ExQQ==&transaction_id=6139ea4961e07094846910&send_app_id=null';

                //文件下载至本地临时目录
                if (!is_dir(self::TempDir . $value['id'])) {
                    mkdir(self::TempDir . $value['id'], 0777, true);
                }
                $date = date('Ymd', $value['add_time']);
                $fileName = 'contract_' . $date . '-' . $value['id'] . '-' . $value['contract_transaction_id'];
                $initData = file_get_contents($result['download_url']);
                $f = $this->OutPutToPath($initData, $fileName);
                if ($f === false) {
                    $this->echoLog("AutoSignContract: $contract_id 合同落地失败   \n" . print_r($result,true));
                    return false;
                }
                //落地成功后更新到oss_download上
                echo "purchase_id = {$value['id']} 开始上传到oss\r\n";
                // 上传到Oss
                $oss_download = $saveName = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR .'xf-exchange'. DIRECTORY_SEPARATOR . $fileName . '.pdf';
                $re = $this->upload($f, $saveName);
                if ($re === false) {
                    $this->echoLog("purchase_id = {$value['id']} 的合同上传oss失败！"  );
                }else{
                    $this->echoLog("purchase_id = {$value['id']} 的合同上传oss成功！"  );
                }

                //求购记录数据更新
                $n_time = time();
                $PurchaseRet = XfExclusivePurchase::model()->updateByPk($value['id'], array(
                    'status' => 4,
                    'assignee_sign_time' => $n_time,
                    "contract_url" => $result['viewpdf_url'],
                    "oss_contract_url" => $oss_download,
                ));
                if(!$PurchaseRet){
                    $this->echoLog("XfExclusivePurchase update error, id={$value['id']}");
                    return false;
                }

                $edit_user_sql = "update xf_assignee_user set purchase_status = 1, purchase_time={$n_time} where id={$purchase_user['id']}";
                $edit_ret = Yii::app()->phdb->createCommand($edit_user_sql)->execute();
                if(!$edit_ret){
                    $this->echoLog("AutoSignContract: edit xf_assignee_user error");
                    return false;
                }

                //投资记录更新合同路径
                $edit_ret02 = $edit_ret01 = false;
                $sql = "SELECT new_tender_id FROM firstp2p_debt_tender WHERE status=2 and exclusive_purchase_id={$value['id']}";
                $ph_load_id = Yii::app()->phdb->createCommand($sql)->queryColumn();
                if($ph_load_id){
                    $condition = " id in ('".implode("','", $ph_load_id)."') ";
                    $edit_ret01 = PHDealLoad::model()->updateAll(array('contract_path'=>$oss_download), $condition);
                }
                $zdx_sql = "SELECT new_tender_id FROM offline_debt_tender WHERE status=2 and exclusive_purchase_id={$value['id']}";
                $zdx_load_id = Yii::app()->offlinedb->createCommand($zdx_sql)->queryColumn();
                if($zdx_load_id){
                    $condition = " id in ('".implode("','", $zdx_load_id)."') ";
                    $edit_ret02 = OfflineDealLoad::model()->updateAll(array('contract_path'=>$oss_download), $condition);
                }
                if($edit_ret01 == false && $edit_ret02 == false){
                    $this->echoLog("AutoSignContract: edit deal_load contract_path error");
                    return false;
                }

                sleep(1);
                //释放文件锁
                $this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
            }

            //增加短信报警
            if($f>0){
                $error_info = "YJ_DEBT_ALARM：DebtTransaction_fail_count：$f";
                $send_ret = SmsIdentityUtils::fundAlarm($error_info, 'DebtTransaction');
                $this->echoLog("ExclusivePurchase AutoSignContract sendAlarm return $send_ret ");
            }

            $this->echoLog("ExclusivePurchase AutoSignContract end, success_count:$s; fail_count:$f; ");

        } catch (Exception $e) {
            self::echoLog("ExclusivePurchase AutoSignContract Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }

        //邮件报警错误详情
        $this->warningEmail();
    }

    /**
     * 文件上传
     * @param $file
     * @param $key
     * @return bool
     */
    private function upload($file, $key)
    {
        Yii::log(basename($file).'文件正在上传!', CLogger::LEVEL_INFO );
        try {
            ini_set('memory_limit', '2048M');
            $re = Yii::app()->oss->bigFileUpload($file, $key);
            unlink($file);
            return $re;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR );
            return false;
        }
    }
    /**
     * 合同落地
     * @param $data
     * @param $fileName
     * @return bool|string
     */
    private function OutPutToPath($data, $fileName)
    {
        $filePath = self::TempDir . DIRECTORY_SEPARATOR .$fileName . '.pdf';
        $status = file_put_contents($filePath, $data);
        if (!$status) {
            return false;
        }
        Yii::log($filePath . ' 合同生成并落地成功!', CLogger::LEVEL_INFO );
        return $filePath;
    }

    /**
     * 积分兑换自动签署合同
     * @param string $id
     * @return false
     */
    public function actionAutoSignExchangeContract($id=''){
        $this->echoLog("ExclusivePurchase AutoSignExchangeContract start");

        //指定求购计划单笔处理
        if(!empty($id)){
            $this->where .= " and id=$id ";
        }

        try {
            //查询待自动签署的合同
            $contract_sql = "select  * from xf_debt_contract where status=1 {$this->where} ";
            $contract_list = Yii::app()->db->createCommand($contract_sql)->queryAll();
            if(empty($contract_list)) {
                $this->echoLog("AutoSignExchangeContract: xf_debt_contract is empty");
                return false;
            }
            $this->echoLog("AutoSignExchangeContract: contract_list count:".count($contract_list));

            $f = $s = 0;
            foreach($contract_list as $key => $value){
                //化债是否完成
                $sql = "SELECT new_deal_load_id FROM firstp2p_debt_exchange_log WHERE status in (1,2) and contract_transaction_id='{$value['contract_transaction_id']}' ";
                $ph_load_id = Yii::app()->phdb->createCommand($sql)->queryColumn();
                $zx_load_id = Yii::app()->db->createCommand($sql)->queryColumn();
                $zdx_sql = "SELECT new_deal_load_id FROM offline_debt_exchange_log WHERE status=2 and contract_transaction_id='{$value['contract_transaction_id']}' ";
                $zdx_load_id = Yii::app()->offlinedb->createCommand($zdx_sql)->queryColumn();
                if(empty($ph_load_id) && empty($zx_load_id) && empty($zdx_load_id)){
                    $this->echoLog("AutoSignContract: new_deal_load not create ");
                    $f++;
                    continue;
                }


                $contract_id = $value['contract_id'];

                //受让人法大大账户ID
                $user_info = User::model()->findByPk($value['buyer_uid']);
                if(!$user_info){
                    $this->echoLog("AutoSignExchangeContract: buyer_uid user_info error");
                    $f++;
                    continue;
                }

                //根据求购记录ID加文件锁
                $fpLock = $this->enterTenderIdFnLock($contract_id);
                //$user_info['yj_fdd_customer_id'] = '9080F8FBA5DEFD5FEABCD605B51628D4';
                $result = XfFddService::getInstance()->invokeExtSignAuto($user_info['yj_fdd_customer_id'], $contract_id, '债权转让协议', 'B盖签');
                if(!$result || $result['code'] != 1000){
                    $this->echoLog("AutoSignContract: $contract_id 合同签署失败   \n" . print_r($result,true));
                    $f++;
                    continue;
                }
                //$result['download_url'] = 'https://testapi.fadada.com:8443/api//getdocs.action?app_id=402877&timestamp=20210909190505&v=2.0&msg_digest=QzQwQTA2MDkzMkVFM0JBOEU3MUZFMjhCMTE4NTVERTUxMDc0N0ExQQ==&transaction_id=6139ea4961e07094846910&send_app_id=null';
                //$result['viewpdf_url'] = 'https://testapi.fadada.com:8443/api//viewdocs.action?app_id=402877&timestamp=20210909190505&v=2.0&msg_digest=QzQwQTA2MDkzMkVFM0JBOEU3MUZFMjhCMTE4NTVERTUxMDc0N0ExQQ==&transaction_id=6139ea4961e07094846910&send_app_id=null';

                //文件下载至本地临时目录
                if (!is_dir(self::TempDir . $value['id'])) {
                    mkdir(self::TempDir . $value['id'], 0777, true);
                }
                $date = date('Ymd', $value['add_time']);
                $fileName = 'contract_' . $date . '-' . $value['id'] . '-' . $value['contract_transaction_id'];
                $initData = file_get_contents($result['download_url']);
                $f = $this->OutPutToPath($initData, $fileName);
                if ($f === false) {
                    $this->echoLog("AutoSignExchangeContract: $contract_id 合同落地失败   \n" . print_r($result,true));
                    $f++;
                    continue;
                }
                //落地成功后更新到oss_download上
                echo "debt_contract_id = {$value['id']} 开始上传到oss\r\n";
                // 上传到Oss
                $oss_download = $saveName = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR .'xf-exchange'. DIRECTORY_SEPARATOR . $fileName . '.pdf';
                $re = $this->upload($f, $saveName);
                if ($re === false) {
                    $this->echoLog("debt_contract_id = {$value['id']} 的合同上传oss失败！"  );
                }else{
                    $this->echoLog("debt_contract_id = {$value['id']} 的合同上传oss成功！"  );
                }

                //投资记录更新合同路径
                $edit_ret02 = $edit_ret01 = $edit_ret03 = false;
                if($ph_load_id){
                    $condition = " id in ('".implode("','", $ph_load_id)."') ";
                    $edit_ret01 = PHDealLoad::model()->updateAll(array('contract_path'=>$oss_download), $condition);
                }
                if($zx_load_id){
                    $condition = " id in ('".implode("','", $zx_load_id)."') ";
                    $edit_ret03 = DealLoad::model()->updateAll(array('contract_path'=>$oss_download), $condition);
                }
                if($zdx_load_id){
                    $condition = " id in ('".implode("','", $zdx_load_id)."') ";
                    $edit_ret02 = OfflineDealLoad::model()->updateAll(array('contract_path'=>$oss_download), $condition);
                }
                if($edit_ret01 == false && $edit_ret02 == false && $edit_ret03 == false){
                    $this->echoLog("AutoSignContract: edit deal_load contract_path error");
                    $f++;
                    continue;
                }

                //合同状态数据更新
                $n_time = time();
                $PurchaseRet = Firstp2pDebtContract::model()->updateByPk($value['id'], array(
                    'status' => 2,
                    'assignee_sign_time' => $n_time,
                    "contract_url" => $result['viewpdf_url'],
                    "oss_contract_url" => $oss_download,
                ));
                if(!$PurchaseRet){
                    $this->echoLog("AutoSignExchangeContract update error, id={$value['id']}");
                    $f++;
                    continue;
                }

                $s++;
                sleep(1);
                //释放文件锁
                $this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
            }

            $this->echoLog("ExclusivePurchase AutoSignExchangeContract end, success_count:$s; fail_count:$f; ");
        } catch (Exception $e) {
            self::echoLog("ExclusivePurchase AutoSignExchangeContract Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }

        //邮件报警错误详情
        $this->warningEmail();
    }

    /**
     * 万峻置换自动签署合同
     * @param string $id
     * @return false
     */
    public function actionAutoSignDisplaceContract($id=''){
        $this->echoLog("ExclusivePurchase AutoSignDisplaceContract start");

        //指定求购计划单笔处理
        if(!empty($id)){
            $this->where .= " and id=$id ";
        }

        try {
            //查询待自动债转的求购
            $purchase_sql = "select  * from xf_displace_record where status=1 {$this->where} ";
            $displace_list = Yii::app()->phdb->createCommand($purchase_sql)->queryAll();
            if(empty($displace_list)) {
                $this->echoLog("AutoSignDisplaceContract: xf_displace_record is empty");
                return false;
            }
            $this->echoLog("AutoSignDisplaceContract: displace_list count:".count($displace_list));


            foreach($displace_list as $key => $value){
                $contract_id = $value['contract_id'];
                $annex_contract_id = $value['annex_contract_id'];
                //受让人法大大账户ID
                $buyer_uid = Yii::app()->c->contract['displace_uid'];
                $bing_uid = Yii::app()->c->contract['bing_uid'];
                $user_info = User::model()->findAllByPk([$buyer_uid, $bing_uid]);
                if(!$user_info){
                    $this->echoLog("AutoSignDisplaceContract: user_info error");
                    return false;
                }
                //根据求购记录ID加文件锁
                $fpLock = $this->enterTenderIdFnLock($contract_id);

                $edit_data = [];
                foreach ($user_info as $k=>$info){
                    //提测试删除
                    //$yj_fdd_customer_id = '8FCE5A86D4D9520AEBE80B1B6B0755F3';
                    $yj_fdd_customer_id = $info->yj_fdd_customer_id;
                    $keyword = '';
                    //乙方签署信息
                    if($info->id == $buyer_uid){
                        $keyword = 'B盖签';
                    }

                    //丙方签署信息
                    elseif($info->id == $bing_uid){
                        $keyword = 'C盖签';
                    }

                    //合同1签署
                    $result_01 = XfFddService::getInstance()->invokeExtSignAuto($yj_fdd_customer_id, $contract_id, '网信普惠账户项下债权及账户权益整体转让协议', $keyword);
                    if(!$result_01 || $result_01['code'] != 1000){
                        $this->echoLog("AutoSignDisplaceContract: $contract_id 合同签署失败   \n" . print_r($result_01,true));
                        return false;
                    }

                    //合同2签署
                    $result_02 = XfFddService::getInstance()->invokeExtSignAuto($yj_fdd_customer_id, $annex_contract_id, '网信普惠账户项下债权及账户权益整体转让协议之补充协议', $keyword);
                    if(!$result_02 || $result_02['code'] != 1000){
                        $this->echoLog("AutoSignDisplaceContract: $annex_contract_id 合同签署失败   \n" . print_r($result_02,true));
                        return false;
                    }

                    //全部签署完成
                    if($k == 1){
                        //文件下载至本地临时目录
                        if (!is_dir(self::DisplaceTempDir . $value['id'])) {
                            mkdir(self::DisplaceTempDir . $value['id'], 0777, true);
                        }
                        $date = date('Ymd', $value['add_time']);
                        $fileName_01 = 'contract_' . $date . '-' . $contract_id;
                        $fileName_02 = 'contract_' . $date . '-' . $annex_contract_id;
                        $initData_01 = file_get_contents($result_01['download_url']);
                        $initData_02 = file_get_contents($result_02['download_url']);
                        $f_01 = $this->OutPutToPath($initData_01, $fileName_01);
                        $f_02 = $this->OutPutToPath($initData_02, $fileName_02);
                        if ($f_01 === false) {
                            $this->echoLog("AutoSignDisplaceContract: $contract_id 合同落地失败   \n" . print_r($result_01,true));
                            return false;
                        }
                        if ($f_02 === false) {
                            $this->echoLog("AutoSignDisplaceContract: $annex_contract_id 合同落地失败   \n" . print_r($result_02,true));
                            return false;
                        }
                        //落地成功后更新到oss_download上
                        echo " key:{$k} $contract_id 开始上传到oss\r\n";
                        $oss_download_01 = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR .'xf-exchange'. DIRECTORY_SEPARATOR . $fileName_01 . '.pdf';
                        $re_01 = $this->upload($f_01, $oss_download_01);
                        if ($re_01 === false) {
                            $this->echoLog(" key:{$k} $contract_id 的合同上传oss失败！"  );
                        }else{
                            $this->echoLog(" key:{$k} $contract_id 的合同上传oss成功！"  );
                        }

                        //合同2落地成功后更新到oss_download上
                        echo " key:{$k} $annex_contract_id 开始上传到oss\r\n";
                        $oss_download_02 = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR .'xf-exchange'. DIRECTORY_SEPARATOR . $fileName_02 . '.pdf';
                        $re_02 = $this->upload($f_02, $oss_download_02);
                        if ($re_02 === false) {
                            $this->echoLog(" key:{$k} $annex_contract_id 的合同上传oss失败！"  );
                        }else{
                            $this->echoLog(" key:{$k} $annex_contract_id 的合同上传oss成功！"  );
                        }

                        $edit_data['contract_url'] = $result_01['viewpdf_url'];
                        $edit_data['oss_contract_url'] = $oss_download_01;
                        $edit_data['annex_oss_contract_url'] = $oss_download_02;
                        $edit_data['assignee_sign_time'] = time();
                        $edit_data['status'] = 2;
                    }
                }

                if(empty($edit_data)){
                    $this->echoLog("AutoSignDisplaceContract edit_data empty, id={$value['id']}");
                    return false;
                }

                $PurchaseRet = XfDisplaceRecord::model()->updateByPk($value['id'], $edit_data);
                if(!$PurchaseRet){
                    $this->echoLog("AutoSignDisplaceContract update error, id={$value['id']}");
                    return false;
                }
                sleep(1);
                //释放文件锁
                $this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
            }
            $this->echoLog("ExclusivePurchase AutoSignDisplaceContract end ");

        } catch (Exception $e) {
            self::echoLog("ExclusivePurchase AutoSignDisplaceContract Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
    }

    /**
     * 集约诉讼自动签署合同
     * @param string $id
     * @return false
     */
    public function actionAutoSignIntensiveContract($id=''){
        $this->echoLog("ExclusivePurchase AutoSignIntensiveContract start");

        //指定求购计划单笔处理
        if(!empty($id)){
            $this->where .= " and id=$id ";
        }

        try {
            //查询待自动债转的求购
            $purchase_sql = "select  * from firstp2p_user where intensive_sign_status=1 and intensive_sign_time_yi=0 {$this->where} ";
            $user_list = Yii::app()->db->createCommand($purchase_sql)->queryAll();
            if(empty($user_list)) {
                $this->echoLog("AutoSignIntensiveContract: firstp2p_user is empty");
                return false;
            }
            $this->echoLog("AutoSignIntensiveContract: user_list count:".count($user_list));

            $f = $s = 0;
            foreach($user_list as $key => $value){
                $contract_id = $value['intensive_contract_id'];
                //受让人法大大账户ID
                $buyer_uid = Yii::app()->c->contract['displace_uid'];
                $user_info = User::model()->findByPk($buyer_uid);
                if(!$user_info){
                    $this->echoLog("AutoSignIntensiveContract: displace_uid user_info error");
                    return false;
                }

                //根据求购记录ID加文件锁
                $fpLock = $this->enterTenderIdFnLock($contract_id);
               // $user_info['yj_fdd_customer_id'] = '8FCE5A86D4D9520AEBE80B1B6B0755F3';
                $result = XfFddService::getInstance()->invokeExtSignAuto($user_info['yj_fdd_customer_id'], $contract_id, '集约诉讼授权协议', 'B盖签');
                if(!$result || $result['code'] != 1000){
                    $this->echoLog("AutoSignIntensiveContract: $contract_id 合同签署失败   \n" . print_r($result,true));
                    return false;
                }

                //文件下载至本地临时目录
                if (!is_dir(self::DisplaceTempDir . $value['id'])) {
                    mkdir(self::DisplaceTempDir . $value['id'], 0777, true);
                }
                $date = date('Ymd', $value['intensive_sign_time']);
                $fileName = 'contract_' . $date . '-' . $value['id'];
                $initData = file_get_contents($result['download_url']);
                $f = $this->OutPutToPath($initData, $fileName);
                if ($f === false) {
                    $this->echoLog("AutoSignIntensiveContract: $contract_id 合同落地失败   \n" . print_r($result,true));
                    return false;
                }
                //落地成功后更新到oss_download上
                echo "user_id = {$value['id']} 开始上传到oss\r\n";
                // 上传到Oss
                $oss_download = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR .'xf-exchange'. DIRECTORY_SEPARATOR . $fileName . '.pdf';
                $re = $this->upload($f, $oss_download);
                if ($re === false) {
                    $this->echoLog("user_id = {$value['id']} 的合同上传oss失败！"  );
                }else{
                    $this->echoLog("user_id = {$value['id']} 的合同上传oss成功！"  );
                }


                $n_time = time();
                $PurchaseRet = User::model()->updateByPk($value['id'], array(
                    'intensive_sign_time_yi' => $n_time,
                    "intensive_contract_url" => $result['download_url'],
                    "intensive_oss_contract_url" => $oss_download,
                ));
                if(!$PurchaseRet){
                    $this->echoLog("AutoSignIntensiveContract update error, id={$value['id']}");
                    return false;
                }

                sleep(1);
                //释放文件锁
                $this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
            }
            $this->echoLog("ExclusivePurchase AutoSignIntensiveContract end, success_count:$s; fail_count:$f; ");

        } catch (Exception $e) {
            self::echoLog("ExclusivePurchase AutoSignIntensiveContract Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
    }


    /**
     * 积分兑换自动签署合同-bug修复
     * @param string $id
     * @return false
     */
    public function actionBugAutoSignExchangeContract($id=''){
        $this->echoLog("ExclusivePurchase BugAutoSignExchangeContract start");

        //指定求购计划单笔处理
        if(!empty($id)){
            $this->where .= " and id=$id ";
        }

        try {
            //查询待自动签署的合同
            $contract_sql = "select  * from xf_debt_contract where status=4 and platform_id=99 and id<=466 {$this->where} ";
            $contract_list = Yii::app()->db->createCommand($contract_sql)->queryAll();
            if(empty($contract_list)) {
                $this->echoLog("bugAutoSignExchangeContract: xf_debt_contract is empty");
                return false;
            }
            $this->echoLog("bugAutoSignExchangeContract: contract_list count:".count($contract_list));

            $f = $s = 0;
            foreach($contract_list as $key => $value){
                $this->echoLog("bugAutoSignContract: contract_transaction_id='{$value['contract_transaction_id']}' start ");

                //化债是否完成
                $sql = "SELECT '2' as plat,platform_no,user_id,tender_id,debt_account,addtime,borrow_id,buyer_uid,new_deal_load_id FROM firstp2p_debt_exchange_log WHERE status=2 and contract_transaction_id='{$value['contract_transaction_id']}' ";
                $ph_load_ids = Yii::app()->phdb->createCommand($sql)->queryAll();
                $zdx_sql = "SELECT '4' as plat,platform_no, user_id,tender_id,debt_account,addtime,borrow_id,buyer_uid,new_deal_load_id FROM offline_debt_exchange_log WHERE status=2 and contract_transaction_id='{$value['contract_transaction_id']}' ";
                $zdx_load_ids = Yii::app()->offlinedb->createCommand($zdx_sql)->queryAll();
                if(empty($ph_load_ids) && empty($zdx_load_ids)){
                    $this->echoLog("bugAutoSignContract: new_deal_load not create ");
                    $f++;
                    continue;
                }
                $debt_exchange_list = array_merge($ph_load_ids, $zdx_load_ids);
                $ph_load_id = $zdx_load_id = [];
                if(!empty($ph_load_ids)){
                    foreach ($ph_load_ids as $v){
                        $ph_load_id[] = $v['tender_id'];
                        $ph_load_id[] = $v['new_deal_load_id'];
                    }
                }
                if(!empty($zdx_load_ids)){
                    foreach ($zdx_load_ids as $v){
                        $zdx_load_id[] = $v['tender_id'];
                        $zdx_load_id[] = $v['new_deal_load_id'];
                    }
                }

                //拼接合同,并且去生成
                $contract_id = $this->autoCreateContract($debt_exchange_list);
                if(!$contract_id){
                    $this->echoLog("bugAutoSignContract: contract_id error ");
                    $f++;
                    continue;
                }

                //受让人法大大账户ID
                $user_info = User::model()->findByPk($value['buyer_uid']);
                if(!$user_info){
                    $this->echoLog("bugAutoSignExchangeContract: buyer_uid user_info error");
                    $f++;
                    continue;
                }

                //根据求购记录ID加文件锁
                $fpLock = $this->enterTenderIdFnLock($contract_id);
                $result = XfFddService::getInstance()->invokeExtSignAuto($user_info['yj_fdd_customer_id'], $contract_id, '债权转让协议', 'B盖签');
                if(!$result || $result['code'] != 1000){
                    $this->echoLog("bugAutoSignContract: $contract_id 合同签署失败   \n" . print_r($result,true));
                    $f++;
                    continue;
                }

                //文件下载至本地临时目录
                if (!is_dir(self::TempDir . $value['id'])) {
                    mkdir(self::TempDir . $value['id'], 0777, true);
                }
                $date = date('Ymd', $value['add_time']);
                $fileName = 'contract_' . $date . '-' . $value['id'] . '-' . $value['contract_transaction_id'];
                $initData = file_get_contents($result['download_url']);
                $f = $this->OutPutToPath($initData, $fileName);
                if ($f === false) {
                    $this->echoLog("bugAutoSignExchangeContract: $contract_id 合同落地失败   \n" . print_r($result,true));
                    $f++;
                    continue;
                }
                //落地成功后更新到oss_download上
                echo "debt_contract_id = {$value['id']} 开始上传到oss\r\n";
                // 上传到Oss
                $oss_download = $saveName = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR .'xf-exchange'. DIRECTORY_SEPARATOR . $fileName . '.pdf';
                $re = $this->upload($f, $saveName);
                if ($re === false) {
                    $this->echoLog("debt_contract_id = {$value['id']} 的合同上传oss失败！"  );
                }else{
                    $this->echoLog("debt_contract_id = {$value['id']} 的合同上传oss成功！"  );
                }

                //投资记录更新合同路径
                $edit_ret02 = $edit_ret01 = $edit_ret03 = false;
                if($ph_load_id){
                    $condition = " id in ('".implode("','", $ph_load_id)."') ";
                    $edit_ret01 = PHDealLoad::model()->updateAll(array('contract_path'=>$oss_download), $condition);
                }

                if($zdx_load_id){
                    $condition = " id in ('".implode("','", $zdx_load_id)."') ";
                    $edit_ret02 = OfflineDealLoad::model()->updateAll(array('contract_path'=>$oss_download), $condition);
                }
                if($edit_ret01 == false  && $edit_ret02 == false){
                    $this->echoLog("bugAutoSignContract: edit deal_load contract_path error");
                }

                //合同状态数据更新
                $n_time = time();
                $PurchaseRet = Firstp2pDebtContract::model()->updateByPk($value['id'], array(
                    'status' => 2,
                    "contract_url" => $result['viewpdf_url'],
                    "oss_contract_url" => $oss_download,
                ));
                if(!$PurchaseRet){
                    $this->echoLog("bugAutoSignExchangeContract update error, id={$value['id']}");
                    $f++;
                    continue;
                }

                $s++;
                sleep(1);
                //释放文件锁
                $this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
            }

            $this->echoLog("ExclusivePurchase bugAutoSignExchangeContract end, success_count:$s; fail_count:$f; ");
        } catch (Exception $e) {
            self::echoLog("ExclusivePurchase bugAutoSignExchangeContract Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
    }

    public function autoCreateContract($debt_exchange_list){
        $total_capital = 0;
        $buyer_uid = 12143676;
        $n = 0;
        //$transaction_id = str_replace('.', '', uniqid('', true));
        //拼接合同内容
        $deal_load_content = $deal_load_content_01 = $deal_load_content_02 = $deal_load_content_03 = $deal_load_content_04 = $deal_load_content_05 = '';
        $deal_load_content_06 = $deal_load_content_07 = $deal_load_content_08 = $deal_load_content_09 = $deal_load_content_10 = $deal_load_content_11 = '';
        $end_time = time()-60*5;
        foreach ($debt_exchange_list as $exchange){
            //普惠
            if($exchange['plat'] == 2){
                $select_sql = " SELECT t.id,b.deal_type,t.debt_type,t.id, b.name AS name, t.deal_id AS borrow_id ,t.create_time AS addtime ,b.buyer_uid  
                FROM firstp2p_deal_load as t 
                LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id 
                WHERE  t.id={$exchange['tender_id']} ";
                $tender_info = Yii::app()->phdb->createCommand($select_sql)->queryRow();
                //合同信息
                $total_capital = bcadd($total_capital, $exchange['debt_account'], 2);
                $buyer_uid = $exchange['buyer_uid'];
                $platform_no = $exchange['platform_no'];
                //债转合同编号根据规则拼接
                $seller_contract_number = implode('-', [date('Ymd', $tender_info['addtime']), $tender_info['deal_type'], $tender_info['borrow_id'], $tender_info['id']]);
                //普惠获取合同编号
                if ($tender_info['debt_type'] == 1  ) {
                    //合同信息
                    $table_name = $tender_info['borrow_id'] % 128;
                    $contract_sql = "select number from contract_$table_name 
                             where deal_load_id = {$tender_info['id']} 
                             and user_id={$exchange['user_id']} 
                             and deal_id={$tender_info['borrow_id']}
                             and type in (0,1) and status=1 and source_type=0  ";
                    $contract_info = Yii::app()->cdb->createCommand($contract_sql)->queryRow();
                    if (!$contract_info) {
                        Yii::log("contract_path tender_id[{$tender_info['id']}]  $contract_sql error  ", 'error');
                        return false;
                    }
                    $seller_contract_number = $contract_info['number'];
                }

                $n+=1;
                if ($n<=25) {
                    $deal_load_content .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender_info['name']}；转让债权本金：{$exchange['debt_account']}元整。\r\n";
                }
                if ($n>25 && $n<= 57) {
                    $deal_load_content_01 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender_info['name']}；转让债权本金：{$exchange['debt_account']}元整。\r\n";
                }
                if ($n>57 && $n<= 89) {
                    $deal_load_content_02 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender_info['name']}；转让债权本金：{$exchange['debt_account']}元整。\r\n";
                }
            }

            //智多新
            if($exchange['plat'] == 4){
                $select_sql = " SELECT b.deal_type,t.debt_type,t.id,b.name AS name ,t.deal_id AS borrow_id ,t.create_time AS addtime,b.buyer_uid  
                             FROM offline_deal_load as t 
                             LEFT JOIN offline_deal as b ON t.deal_id = b.id  
                             WHERE  t.id={$exchange['tender_id']} ";
                $tender_info = Yii::app()->offlinedb->createCommand($select_sql)->queryRow() ;
                //合同信息
                $total_capital = bcadd($total_capital, $exchange['debt_account'], 2);
                $buyer_uid = $exchange['buyer_uid'];
                $platform_no = $exchange['platform_no'];
                //债转合同编号根据规则拼接
                $seller_contract_number = implode('-', [date('Ymd', $tender_info['addtime']), $tender_info['deal_type'], $tender_info['borrow_id'], $tender_info['id']]);
                //普惠获取合同编号
                if ($tender_info['debt_type'] == 1  ) {
                    $contract_info = OfflineContractTask::model()->find("tender_id={$tender_info['id']} and contract_type=1 and type=1 and status=2");
                    if (!$contract_info) {
                        Yii::log("   tender_id[{$tender_info['id']}] OfflineContractTask  error  ", 'error');
                        return false;
                    }
                    $seller_contract_number = $contract_info->contract_no;
                }

                $n+=1;
                if ($n<=25) {
                    $deal_load_content .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender_info['name']}；转让债权本金：{$exchange['debt_account']}元整。\r\n";
                }
                if ($n>25 && $n<= 57) {
                    $deal_load_content_01 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender_info['name']}；转让债权本金：{$exchange['debt_account']}元整。\r\n";
                }
                if ($n>57 && $n<= 89) {
                    $deal_load_content_02 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender_info['name']}；转让债权本金：{$exchange['debt_account']}元整。\r\n";
                }
            }
        }

        $buyer_uid = $buyer_uid ?: 12143676;
        $assignee = User::model()->findByPk($buyer_uid)->attributes;
        if (!$assignee) {
            self::echoLog("debtOrderCommit   assignee error  $buyer_uid " );
            return false;
        }

        //拼接合同内容
        $assignee_idno = GibberishAESUtil::dec($assignee['idno'], Yii::app()->c->contract['idno_key']);
        $seller_user = User::model()->findByPk($exchange['user_id'])->attributes;
        $seller_idno = GibberishAESUtil::dec($seller_user['idno'], Yii::app()->c->contract['idno_key']);
        $template_id = Yii::app()->c->contract[8]['template_id'];
        $shop_name = DebtService::getInstance()->getShopName($platform_no);

        //合同生成
        $cvalue = [
            'title' => '债权转让协议',
            'params' => [
                'contract_id' => implode('-', ['JFDH', date('Ymd', time()), $buyer_uid, $exchange['user_id']]),
                'debt_account_total' =>  $total_capital,
                'A_user_name' => $seller_user['real_name'],
                'A_card_id' => $seller_idno,
                'B_user_name' => $assignee['real_name'],
                'B_card_id' => $assignee_idno,
                'sign_year' => date('Y', $exchange['addtime']),
                'sign_month' => date('m', $exchange['addtime']),
                'sign_day' => date('d', $exchange['addtime']),
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
                'A盖签' => $seller_user['old_yj_fdd_customer_id'],
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
            self::echoLog("debtOrderCommit  order_id[{$exchange['tender_id']}]  合同生成失败！\n" . print_r($result, true) );
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
            self::echoLog("debtOrderCommit  order_id[{$exchange['tender_id']}]  的{$cvalue['title']}加水印失败！\n" . print_r($result, true));
            return false;
        }

        $result = XfFddService::getInstance()->invokeExtSignAuto($seller_user['old_yj_fdd_customer_id'], $contract_id, $doc_title, 'A盖签');
        if(!$result || $result['code'] != 1000){
            self::echoLog("tender_id= {$exchange['tender_id']} 的{$cvalue['title']}合同签署失败！\n" . print_r($result,true));
            return false;
        }

        return $contract_id;

    }


    /**
     * 贾素琴误操作数据修复脚本
     * @param string $id
     * @return false
     *
     */
    public function actionBug2AutoSignExchangeContract($id=''){
        $this->echoLog("ExclusivePurchase Bug2AutoSignExchangeContract start");

        //指定求购计划单笔处理
        if(!empty($id)){
            $this->where .= " and id=$id ";
        }

        try {
            //查询待自动签署的合同
            $contract_sql = "select  * from xf_debt_contract where  id in (501,507,508,509,510,511,512,513) ";
            $contract_list = Yii::app()->db->createCommand($contract_sql)->queryAll();
            if(empty($contract_list)) {
                $this->echoLog("bugAutoSignExchangeContract: xf_debt_contract is empty");
                return false;
            }
            $this->echoLog("bugAutoSignExchangeContract: contract_list count:".count($contract_list));

            $f = $s = 0;
            foreach($contract_list as $key => $value){
                $this->echoLog("bugAutoSignContract: contract_transaction_id='{$value['contract_transaction_id']}' start ");

                //化债是否完成
                $sql = "SELECT '1' as plat,platform_no,user_id,tender_id,debt_account,addtime,borrow_id,buyer_uid,new_deal_load_id FROM firstp2p_debt_exchange_log WHERE status=2 and contract_transaction_id='{$value['contract_transaction_id']}' ";
                $debt_exchange_list = Yii::app()->db->createCommand($sql)->queryAll();
                if(empty($debt_exchange_list) ){
                    $this->echoLog("bugAutoSignContract: new_deal_load not create ");
                    $f++;
                    continue;
                }

                $zx_load_id = [];
                foreach ($debt_exchange_list as $v){
                    $zx_load_id[] = $v['tender_id'];
                    $zx_load_id[] = $v['new_deal_load_id'];
                }


                //拼接合同,并且去生成
                $contract_id = $this->auto2CreateContract($debt_exchange_list);
                if(!$contract_id){
                    $this->echoLog("bugAutoSignContract: contract_id error ");
                    $f++;
                    continue;
                }

                //受让人法大大账户ID
                $user_info = User::model()->findByPk($value['buyer_uid']);
                if(!$user_info){
                    $this->echoLog("bugAutoSignExchangeContract: buyer_uid user_info error");
                    $f++;
                    continue;
                }

                //根据求购记录ID加文件锁
                $fpLock = $this->enterTenderIdFnLock($contract_id);
                $result = XfFddService::getInstance()->invokeExtSignAuto($user_info['yj_fdd_customer_id'], $contract_id, '债权转让协议', 'B盖签');
                if(!$result || $result['code'] != 1000){
                    $this->echoLog("bugAutoSignContract: $contract_id 合同签署失败   \n" . print_r($result,true));
                    $f++;
                    continue;
                }

                //文件下载至本地临时目录
                if (!is_dir(self::TempDir . $value['id'])) {
                    mkdir(self::TempDir . $value['id'], 0777, true);
                }
                $date = date('Ymd', $value['add_time']);
                $fileName = 'contract_' . $date . '-' . $value['id'] . '-' . $value['contract_transaction_id'];
                $initData = file_get_contents($result['download_url']);
                $f = $this->OutPutToPath($initData, $fileName);
                if ($f === false) {
                    $this->echoLog("bugAutoSignExchangeContract: $contract_id 合同落地失败   \n" . print_r($result,true));
                    $f++;
                    continue;
                }
                //落地成功后更新到oss_download上
                echo "debt_contract_id = {$value['id']} 开始上传到oss\r\n";
                // 上传到Oss
                $oss_download = $saveName = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR .'xf-exchange'. DIRECTORY_SEPARATOR . $fileName . '.pdf';
                $re = $this->upload($f, $saveName);
                if ($re === false) {
                    $this->echoLog("debt_contract_id = {$value['id']} 的合同上传oss失败！"  );
                }else{
                    $this->echoLog("debt_contract_id = {$value['id']} 的合同上传oss成功！"  );
                }

                //投资记录更新合同路径
                 $edit_ret01 =  false;
                if($zx_load_id){
                    $condition = " id in ('".implode("','", $zx_load_id)."') ";
                    $edit_ret01 = DealLoad::model()->updateAll(array('contract_path'=>$oss_download), $condition);
                }
                if($edit_ret01 == false  ){
                    $this->echoLog("bugAutoSignContract: edit deal_load contract_path error");
                }

                //合同状态数据更新
                $n_time = time();
                $PurchaseRet = Firstp2pDebtContract::model()->updateByPk($value['id'], array(
                    'status' => 2,
                    "contract_url" => $result['viewpdf_url'],
                    "oss_contract_url" => $oss_download,
                ));
                if(!$PurchaseRet){
                    $this->echoLog("bugAutoSignExchangeContract update error, id={$value['id']}");
                    $f++;
                    continue;
                }

                $s++;
                sleep(1);
                //释放文件锁
                $this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
            }

            $this->echoLog("ExclusivePurchase bug2AutoSignExchangeContract end, success_count:$s; fail_count:$f; ");
        } catch (Exception $e) {
            self::echoLog("ExclusivePurchase bug2AutoSignExchangeContract Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
    }
    public function auto2CreateContract($debt_exchange_list){
        $total_capital = 0;
        $buyer_uid = 12133232;
        $n = 0;
        //$transaction_id = str_replace('.', '', uniqid('', true));
        //拼接合同内容
        $deal_load_content = $deal_load_content_01 = $deal_load_content_02 = $deal_load_content_03 = $deal_load_content_04 = $deal_load_content_05 = '';
        $deal_load_content_06 = $deal_load_content_07 = $deal_load_content_08 = $deal_load_content_09 = $deal_load_content_10 = $deal_load_content_11 = '';
        $end_time = time()-60*5;
        foreach ($debt_exchange_list as $exchange){
            $select_sql = " SELECT t.id,b.deal_type,t.debt_type,t.id, b.name AS name, t.deal_id AS borrow_id ,t.create_time AS addtime ,b.buyer_uid  
                FROM firstp2p_deal_load as t 
                LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id 
                WHERE  t.id={$exchange['tender_id']} ";
            $tender_info = Yii::app()->db->createCommand($select_sql)->queryRow();
            //合同信息
            $total_capital = bcadd($total_capital, $exchange['debt_account'], 2);
            $buyer_uid = $exchange['buyer_uid'];
            $platform_no = $exchange['platform_no'];
            //债转合同编号根据规则拼接
            $seller_contract_number = implode('-', [date('Ymd', $tender_info['addtime']), $tender_info['deal_type'], $tender_info['borrow_id'], $tender_info['id']]);
            //普惠获取合同编号
            if ($tender_info['debt_type'] == 1  ) {
                //合同信息
                $table_name = $tender_info['borrow_id'] % 128;
                $contract_sql = "select number from contract_$table_name 
                             where deal_load_id = {$tender_info['id']} 
                             and user_id={$exchange['user_id']} 
                             and deal_id={$tender_info['borrow_id']}
                             and type in (0,1) and status=1 and  source_type in (2,3)  ";
                $contract_info = Yii::app()->cdb->createCommand($contract_sql)->queryRow();
                if (!$contract_info) {
                    Yii::log("contract_path tender_id[{$tender_info['id']}]  $contract_sql error  ", 'error');
                    return false;
                }
                $seller_contract_number = $contract_info['number'];
            }

            $n+=1;
            if ($n<=25) {
                $deal_load_content .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender_info['name']}；转让债权本金：{$exchange['debt_account']}元整。\r\n";
            }
            if ($n>25 && $n<= 57) {
                $deal_load_content_01 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender_info['name']}；转让债权本金：{$exchange['debt_account']}元整。\r\n";
            }
            if ($n>57 && $n<= 89) {
                $deal_load_content_02 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$tender_info['name']}；转让债权本金：{$exchange['debt_account']}元整。\r\n";
            }


        }

        $buyer_uid = $buyer_uid ?: 12133232;
        $assignee = User::model()->findByPk($buyer_uid)->attributes;
        if (!$assignee) {
            self::echoLog("debtOrderCommit   assignee error  $buyer_uid " );
            return false;
        }

        //拼接合同内容
        $assignee_idno = GibberishAESUtil::dec($assignee['idno'], Yii::app()->c->contract['idno_key']);
        $seller_user = User::model()->findByPk($exchange['user_id'])->attributes;
        $seller_idno = GibberishAESUtil::dec($seller_user['idno'], Yii::app()->c->contract['idno_key']);
        $template_id = Yii::app()->c->contract[8]['template_id'];
        $shop_name = DebtService::getInstance()->getShopName($platform_no);

        //合同生成
        $cvalue = [
            'title' => '债权转让协议',
            'params' => [
                'contract_id' => implode('-', ['JFDH', date('Ymd', time()), $buyer_uid, $exchange['user_id']]),
                'debt_account_total' =>  $total_capital,
                'A_user_name' => $seller_user['real_name'],
                'A_card_id' => $seller_idno,
                'B_user_name' => $assignee['real_name'],
                'B_card_id' => $assignee_idno,
                'sign_year' => date('Y', $exchange['addtime']),
                'sign_month' => date('m', $exchange['addtime']),
                'sign_day' => date('d', $exchange['addtime']),
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
                'A盖签' => $seller_user['old_yj_fdd_customer_id'],
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
            self::echoLog("debtOrderCommit  order_id[{$exchange['tender_id']}]  合同生成失败！\n" . print_r($result, true) );
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
            self::echoLog("debtOrderCommit  order_id[{$exchange['tender_id']}]  的{$cvalue['title']}加水印失败！\n" . print_r($result, true));
            return false;
        }

        $result = XfFddService::getInstance()->invokeExtSignAuto($seller_user['old_yj_fdd_customer_id'], $contract_id, $doc_title, 'A盖签');
        if(!$result || $result['code'] != 1000){
            self::echoLog("tender_id= {$exchange['tender_id']} 的{$cvalue['title']}合同签署失败！\n" . print_r($result,true));
            return false;
        }

        return $contract_id;

    }
	

}
