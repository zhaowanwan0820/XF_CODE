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
                $oss_download = $saveName = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . $fileName . '.pdf';
                $re = $this->upload($f, $saveName);
                if ($re === false) {
                    $this->echoLog("purchase_id = {$value['id']} 的合同上传oss失败！"  );
                }else{
                    $this->echoLog("purchase_id = {$value['id']} 的合同上传oss成功！"  );
                }

                //求购记录数据更新
               // $PurchaseRet = BaseCrudService::getInstance()->update("XfExclusivePurchase",array(
              //      "id" => $id,
              //      'status' => 4,
              //      'assignee_sign_time' => time(),
              //      "contract_url" => $result['viewpdf_url'],
               //     "oss_contract_url" => $oss_download,
                //), "id");
               // var_dump($PurchaseRet);
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
                $contract_id = $value['contract_id'];

                //受让人法大大账户ID
                $user_info = User::model()->findByPk($value['buyer_uid']);
                if(!$user_info){
                    $this->echoLog("AutoSignExchangeContract: buyer_uid user_info error");
                    $f++;
                    return false;
                }

                //根据求购记录ID加文件锁
                $fpLock = $this->enterTenderIdFnLock($contract_id);
                //$user_info['yj_fdd_customer_id'] = '9080F8FBA5DEFD5FEABCD605B51628D4';
                $result = XfFddService::getInstance()->invokeExtSignAuto($user_info['yj_fdd_customer_id'], $contract_id, '债权转让协议', 'B盖签');
                if(!$result || $result['code'] != 1000){
                    $this->echoLog("AutoSignContract: $contract_id 合同签署失败   \n" . print_r($result,true));
                    $f++;
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
                    $this->echoLog("AutoSignExchangeContract: $contract_id 合同落地失败   \n" . print_r($result,true));
                    $f++;
                    return false;
                }
                //落地成功后更新到oss_download上
                echo "debt_contract_id = {$value['id']} 开始上传到oss\r\n";
                // 上传到Oss
                $oss_download = $saveName = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . $fileName . '.pdf';
                $re = $this->upload($f, $saveName);
                if ($re === false) {
                    $this->echoLog("debt_contract_id = {$value['id']} 的合同上传oss失败！"  );
                }else{
                    $this->echoLog("debt_contract_id = {$value['id']} 的合同上传oss成功！"  );
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
                    return false;
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

	

}
