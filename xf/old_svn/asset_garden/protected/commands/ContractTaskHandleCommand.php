<?php


class ContractTaskHandleCommand extends CConsoleCommand
{
    // 日志文件
    protected $logFile = 'ContractTaskHandleCommand';
    const TempDir = '/tmp/contractV2/';
    private $card_types = [1=>"身份证",2=>"军官证",3=>"台胞证",4=>"护照" ,5=>'营业执照',6=>"外国人永久居留证"];
    private $deal_style = [1=>"按季等额还款",2=>"按月等额还款",3=>"一次性还本付息",4=>"按月付息一次还本" ,5=>'按天一次性还款'];
    public $db_name='db';//db-尊享 phdb-普惠
    public $table_prefix = '';


    /**
     * 批量生成合同入口
     * @param int $type 1尊享 2普惠
     * @param string $tender_id
     * @return mixed
     */
    public function actionRun($type=1, $tender_id=''){


        $this->echoLog("ContractTaskHandle start");

        //区分数据库
        if(!in_array($type, [1,2])){
            $this->echoLog("ContractTaskHandle end, type:$type not in (1,2)");
            return false;
        }
        //普惠
        if($type == 2){
            $this->db_name = "phdb";
            $this->table_prefix = "PH";
        }

        $tenders = $this->getTenders($tender_id);
        if(!$tenders){
            $this->echoLog("ContractTaskHandle end, getTenders return false");
            return false;
        }
        $f = $s = 0;
        foreach ($tenders as $k => $v) {
            $create_ret = $this->actionGenerate($v['borrow_id'], $v['tender_id'], $v['user_id']);
            if($create_ret == false){
                $f++;
                $this->echoLog("ContractTaskHandle end, actionGenerate return false, id:{$v['id']}");
            }else{
                $s++;
                $this->echoLog("ContractTaskHandle end, actionGenerate return true, id:{$v['id']}");
            }
        }

        //增加短信报警
        if($f>0){
            $error_info = "YJ_CONTRACT_ALARM：ContractTaskHandle_fail_count：$f";
            $send_ret = SmsIdentityUtils::fundAlarm($error_info, 'ContractTaskHandle');
            $this->echoLog("ContractTaskHandle sendAlarm return $send_ret ");
        }

        $this->echoLog("ContractTaskHandle end, total:".count($tenders)."; success_count:$s; fail_count:$f;");
    }

    /**
     * 获取要生成合同的信息
     * @param string $tender_id
     * @return array
     */
    private function getTenders($tender_id='')
    {
        $result = [];
        $tender_con = '';
        if(!empty($tender_id) && is_numeric($tender_id)){
            $tender_con .= " and tender_id=$tender_id";
        }
        $contract_model_name = $this->table_prefix.'ContractTask';
        $criteria = new CDbCriteria;
        $criteria->condition = " status=0 $tender_con ";
        $criteria->limit = 50;
        $records = $contract_model_name::model()->findAll($criteria);
        if (empty($records)) {
            return $result;
        }
        foreach ($records as $key => $value) {
            //更新为处理中
            $value->status = 1;
            if(false == $value->save()){
                $this->echoLog("getTenders end, id:{$value->id} edit status=1 error");
                continue;
            }
            //拼接需要的数据
            $finalData = [];
            $finalData['investtime'] = $value->investtime;
            $finalData['tender_id'] = $value->tender_id;
            $finalData['borrow_id'] = $value->borrow_id;
            $finalData['user_id'] = $value->user_id;
            $finalData['type'] = $value->type;
            $finalData['version'] = $value->version;
            $result[] = $finalData;
        }
        return $result;
    }

    /**
     * 更新表数据
     * @param $tender_id
     * @param $fdd_download
     * @param $oss_download
     * @return bool
     */
    private function updateTask($tender_id, $fdd_download, $oss_download){
        $contract_model_name = "{$this->table_prefix}ContractTask";
        $model = $contract_model_name::model()->findByAttributes(['tender_id'=>$tender_id]);
        $model ->status = 2;
        $model ->handletime = time();
        $model ->download = json_encode($fdd_download);
        $model ->oss_download = $oss_download;
        if (!($model->save())) {
            $this->echoLog("updateTask end, tender_id= {$tender_id} error_info:".print_r($model->getErrors(), true));
            $msg = "tender_id= {$tender_id} 数据处理失败！";
            $this->makeFail($tender_id,$msg);
            return false;
        }
        $this->echoLog("updateTask end, tender_id= {$tender_id} 的记录状态修改成功！");
        return true;
    }
    /**
     * 合同落地
     * @param $data
     * @param $fileName
     * @param $borrow_id
     * @return bool|string
     */
    private function OutPutToPath($data, $fileName, $borrow_id)
    {
        $filePath = self::TempDir . $borrow_id. DIRECTORY_SEPARATOR .$fileName . '.pdf';
        $status = file_put_contents($filePath, $data);
        if (!$status) {
            return false;
        }
        Yii::log($filePath . ' 合同生成并落地成功!', CLogger::LEVEL_INFO, $this->logFile);
        return $filePath;
    }

    /**
     * 合同生成
     * @param $investtime
     * @param $borrow_id
     * @param $tender_id
     * @param $type
     * @param $user_id
     * @return bool
     */
    public function actionGenerate($borrow_id, $tender_id, $user_id){
        //合同配置信息获取
        $contract_config = Yii::app()->c->contract;
        //用户信息
        $userInfo = User::model()->findByPk($user_id)->attributes;
        if(empty($userInfo)){
            $msg = "tender_id= {$tender_id} 的合同生成失败！原因：买方{$user_id}用户信息不存在";
            $this->makeFail($tender_id, $msg);
            return false;
        }
        //证件号，身份证号解密
        $idno = GibberishAESUtil::dec($userInfo['idno'], Yii::app()->c->contract['idno_key']);
        $phone = GibberishAESUtil::dec($userInfo['mobile'], Yii::app()->c->contract['idno_key']);
        $customer_id = $userInfo['yj_fdd_customer_id'];
        if(empty($customer_id)){
            //法大大个人CA申请
            $id_type = DebtService::getInstance()->convertCardType($userInfo['id_type']);
            $result = YjFddService::getInstance()->invokeSyncPersonAuto($userInfo['real_name'], $userInfo['id'], $idno, $id_type, $phone);
            if(empty($result) || !isset($result['customer_id'])){
                $msg = "tender_id= {$tender_id} 的个人CA申请失败！\n" . print_r($result, true);
                $this->makeFail($tender_id,$msg);
                return false;
            }
            $customer_id = $result['customer_id'];
            $update_sql = "update firstp2p_user set yj_fdd_customer_id = '{$customer_id}' where id = {$user_id}";
            $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
            if(!$edit_fdd){
                $msg = "tender_id= {$tender_id} yj_fdd_customer_id[$customer_id] edit error！";
                $this->makeFail($tender_id,$msg);
                return false;
            }
        }

        //项目信息
        $deal_model_name = $this->table_prefix.'Deal';
        $deal_info = $deal_model_name::model()->findByPk($borrow_id);
        if(!$deal_info){
            $msg = "tender_id= {$tender_id} deal_info error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        //投资记录
        $deal_load_model_name = $this->table_prefix.'DealLoad';
        $deal_loan_info = $deal_load_model_name::model()->findByPk($tender_id);
        if(!$deal_loan_info){
            $msg = "tender_id= {$tender_id} deal_loan_info error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }
        //出让方信息
        $debt_sql = "select d.user_id,d.tender_id,d.debt_src,t.action_money,d.payee_name,d.payee_bankzone,d.payee_bankcard,t.payer_name,t.payer_bankzone,t.payer_bankcard 
                      from firstp2p_debt_tender t 
                      left join firstp2p_debt d on t.debt_id=d.id 
                      where t.new_tender_id=$tender_id ";
        $debt_info = Yii::app()->{$this->db_name}->createCommand($debt_sql)->queryRow();
        if(!$debt_info){
            $msg = "tender_id= {$tender_id} debt_info error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        //卖方用户信息
        $sellerUserInfo = User::model()->findByPk($debt_info['user_id'])->attributes;
        if(empty($sellerUserInfo)){
            $msg = "tender_id= {$tender_id} 的合同生成失败！原因：卖方{$debt_info['user_id']}用户信息不存在";
            $this->makeFail($tender_id, $msg);
            return false;
        }


        //卖方法大大注册
        $seller_customer_id = $sellerUserInfo['yj_fdd_customer_id'];
        $seller_idno = GibberishAESUtil::dec($sellerUserInfo['idno'], Yii::app()->c->contract['idno_key']);
        $seller_mobile = GibberishAESUtil::dec($sellerUserInfo['mobile'], Yii::app()->c->contract['idno_key']);
        if(empty($seller_customer_id)){
            //法大大个人CA申请
            $id_type = DebtService::getInstance()->convertCardType($sellerUserInfo['id_type']);
            $seller_result = YjFddService::getInstance()->invokeSyncPersonAuto($sellerUserInfo['real_name'], $sellerUserInfo['id'], $seller_idno, $id_type, $seller_mobile);
            if(empty($seller_result) || !isset($seller_result['customer_id'])){
                $msg = "tender_id= $tender_id, seller_user_id={$debt_info['user_id']} 的个人CA申请失败！\n" . print_r($seller_result, true);
                $this->makeFail($tender_id, $msg);
                return false;
            }
            $seller_customer_id = $seller_result['customer_id'];
            $update_sql = "update firstp2p_user set yj_fdd_customer_id = '{$seller_customer_id}' where id = {$debt_info['user_id']}";
            $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
            if(!$edit_fdd){
                $msg = "tender_id= {$tender_id}, seller_user_id={$debt_info['user_id']} yj_fdd_customer_id[$seller_customer_id] edit error！";
                $this->makeFail($tender_id,$msg);
                return false;
            }
        }

        //原投资记录
        $seller_deal_load = $deal_load_model_name::model()->findByPk($debt_info['tender_id']);
        if(!$seller_deal_load){
            $msg = "tender_id= {$tender_id} seller_deal_load error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        //直投合同编号从wx原数据库取
        if($seller_deal_load->debt_type == 1){
            //区分合同来源
            $contract_s = $this->db_name == "phdb" ? " and source_type=0 " : "and source_type in (2,3)";

            //合同信息
            $table_name = $borrow_id%128;
            $contract_sql = "select number from contract_$table_name 
                             where deal_load_id = {$debt_info['tender_id']} 
                             and user_id={$debt_info['user_id']} 
                             and deal_id=$borrow_id 
                             and type in (0,1) and status=1 $contract_s  ";
            $contract_info = Yii::app()->cdb->createCommand($contract_sql)->queryRow();
            if(!$contract_info){
                $msg = "tender_id= {$tender_id} contract_info error,sql:$contract_sql ";
                $this->makeFail($tender_id, $msg);
                return false;
            }
            $seller_contract_number = $contract_info['number'];
        }else{
            //债转合同编号根据规则拼接
            $seller_contract_number = implode('-', [date('Ymd', $seller_deal_load->create_time), $deal_info->deal_type, $borrow_id, $debt_info['tender_id']]);
        }

        //项目还本日期
        $loan_repay_sql = "select max(time) as repayment_time from firstp2p_deal_loan_repay where deal_loan_id = $tender_id and status=0 ";
        $loan_repay_info = Yii::app()->{$this->db_name}->createCommand($loan_repay_sql)->queryRow();
        if(!$loan_repay_info){
            $msg = "tender_id= {$tender_id} loan_repay_info error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        //债转合同模板，变量拼接
        if($debt_info['debt_src'] == 2){
            //新协议
            $contract_types[$contract_config[2]['template_id']] = [
                'title'  => '债权转让与受让协议',
                'params' => [
                    'contract_id'     => implode('-', [date('Ymd', $deal_loan_info->create_time), $deal_info->deal_type, $borrow_id, $tender_id]),
                    'A_user_name'     => $sellerUserInfo['real_name'],
                    'A_card_id'       => $seller_idno,
                    'B_user_name'     => $userInfo['real_name'],
                    'B_card_id'       => $idno,
                    'A_contract_id'   => $seller_contract_number,//卖家原始出借合同编号
                    'borrow_name'     => $deal_info->name,
                    'apr'             => sprintf("%.2f", $deal_info->income_fee_rate).'%',
                    'style'           => $this->deal_style[$deal_info->loantype],
                    'sign_year'       => date('Y', $deal_loan_info->create_time),
                    'sign_month'      => date('m', $deal_loan_info->create_time),
                    'sign_day'        => date('d', $deal_loan_info->create_time),
                    'debt_start_date' => date('Y-m-d', $deal_loan_info->create_time),
                    'debt_end_date'   => date('Y-m-d', $loan_repay_info['repayment_time']),
                    'account'         => $deal_loan_info->wait_capital,
                    'action_money'    => $debt_info['action_money'],
                    'chinese_money'    => ItzUtil::toChineseNumber($debt_info['action_money']),
                    'payer_name'    => $debt_info['payer_name'],
                    'payer_bankzone'    => $debt_info['payer_bankzone'],
                    'payer_bankcard'    => GibberishAESUtil::dec($debt_info['payer_bankcard'], Yii::app()->c->contract['idno_key']),
                    'payee_name'    => $debt_info['payee_name'],
                    'payee_bankzone'    => $debt_info['payee_bankzone'],
                    'payee_bankcard'    => GibberishAESUtil::dec($debt_info['payee_bankcard'], Yii::app()->c->contract['idno_key']),
                ],
                'sign' => [
                    'A盖签' => $seller_customer_id,
                    'B盖签' => $customer_id,
                ],
                'pwd' => '',
            ];
        }else{
            //权益兑换合同模板，变量拼接
            $contract_types[$contract_config[1]['template_id']] = [
                'title'  => '债权转让与受让协议',
                'params' => [
                    'contract_id'     => implode('-', [date('Ymd', $deal_loan_info->create_time), $deal_info->deal_type, $borrow_id, $tender_id]),
                    'A_user_name'     => $sellerUserInfo['real_name'],
                    // 'A_card_type'     => $this->card_types[1],
                    'A_card_id'       => $seller_idno,
                    'B_user_name'     => $userInfo['real_name'],
                    //'B_card_type'     => $this->card_types[1],
                    'B_card_id'       => $idno,
                    'A_contract_id'   => $seller_contract_number,//卖家原始出借合同编号
                    'borrow_name'     => $deal_info->name,
                    'apr'             => sprintf("%.2f", $deal_info->income_fee_rate).'%',
                    'style'           => $this->deal_style[$deal_info->loantype],
                    'sign_year'       => date('Y', $deal_loan_info->create_time),
                    'sign_month'      => date('m', $deal_loan_info->create_time),
                    'sign_day'        => date('d', $deal_loan_info->create_time),
                    'company_name'    => $this->db_name == "db" ? "北京经讯时代科技有限公司" : "北京东方联合投资管理有限公司",
                    'plan_name'       => $this->db_name == "db" ? "网信平台" : "网信普惠平台",
                    'web_address'     => $this->db_name == "db" ? "www.ncfwx.com" : "www.firstp2p.com",
                    //'shop_name'       => "有解",
                    'debt_start_date' => date('Y-m-d', $deal_loan_info->create_time),
                    'debt_end_date'   => date('Y-m-d', $loan_repay_info['repayment_time']),
                    'account'         => $deal_loan_info->wait_capital,
                ],
                'sign' => [
                    'A盖签' => $seller_customer_id,
                    'B盖签' => $customer_id,
                ],
                'pwd' => '',
            ];
        }

        //落地文件地址
        $saveNames = [];
        $fdd_saveNames = [];
        $fdd_save_oss = [];
        //循环生成各种类型合同
        foreach ($contract_types as $ckey => $cvalue){
            //合同文档标题
            $doc_title = $cvalue['title'];
            //合同模版法大大ID
            $template_id = $ckey;
            //填充自定义参数
            $params = $cvalue['params'];
            //生成合同
            $result = FddService::getInstance()->invokeGenerateContract($template_id, $doc_title, $params, $cvalue['dynamic_tables']?:'');
            if(!$result || $result['code'] != 1000){
                $msg = "tender_id= {$tender_id} 的{$cvalue['title']}合同生成失败！\n" . print_r($result,true);
                $this->makeFail($tender_id,$msg);
                return false;
            }
            //法大大合同ID
            $contract_id = $result['contract_id'];

            //加水印
            $text_name = substr("{$sellerUserInfo['real_name']}  {$userInfo['real_name']}", 0, 45);
            $watermark_params = [
                'contract_id' => $contract_id,
                'stamp_type' => 1,
                'text_name' => $text_name,
                'font_size' => 12,
                'rotate' => 45,
                'concentration_factor' => 10,
                'opacity' => 0.2,
            ];
            $result = FddService::getInstance()->watermarkPdf($watermark_params);
            if(!$result || $result['code'] != 1){
                $msg = "tender_id = {$tender_id} 的{$cvalue['title']}加水印失败！\n" . print_r($result,true);
                $this->makeFail($tender_id,$msg);
                return false;
            }

            //合同签署（自动签）多次签署
            foreach ($cvalue['sign'] as $sign_key => $sign_value){
                $result = FddService::getInstance()->invokeExtSignAuto($sign_value, $contract_id, $doc_title, $sign_key);
                if(!$result || $result['code'] != 1000){
                    $msg = "tender_id= {$tender_id} 的{$cvalue['title']}合同签署失败！\n" . print_r($result,true);
                    $this->makeFail($tender_id,$msg);
                    return false;
                }
            }

            $fdd_saveNames[$contract_id] = [
                'contract_id'  => $contract_id,
                'template_id'  => $template_id,
                'customer_id'  => $customer_id,
                'doc_name'     => $doc_title,
                'viewpdf_url'  => $result['viewpdf_url'],
                'download_url' => $result['download_url'],
            ];

            //文件下载至本地临时目录
            if (!is_dir(self::TempDir . $borrow_id)) {
                mkdir(self::TempDir . $borrow_id, 0777, true);
            }
            $date = date('Ymd', $deal_loan_info->create_time);
            $fileName = 'contract_' . $date . '-' . $borrow_id . '-' . $tender_id . '-' . $ckey;
            $initData = file_get_contents($result['download_url']);
            $f = $this->OutPutToPath($initData, $fileName, $borrow_id);
            if ($f === false) {
                $msg = "tender_id= {$tender_id} 的{$cvalue['title']}合同落地失败！";
                $this->makeFail($tender_id,$msg);
                return false;
            }
            //落地成功后更新到oss_download上
            echo $tender_id . "开始上传到oss\r\n";
            // 上传到Oss
            $oss_download = $saveName = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR . $borrow_id . DIRECTORY_SEPARATOR . $fileName . '.pdf';
            $re = $this->upload($f, $saveName);
            if ($re === false) {
                Yii::log("tender_id = $tender_id 的合同上传oss失败！", CLogger::LEVEL_ERROR, $this->logFile);
            }else{
                Yii::log("tender_id = $tender_id 的合同上传oss成功！", CLogger::LEVEL_INFO, $this->logFile);
                echo "修改tender_id= $tender_id 的记录状态... \r\n";
            }
            //是否加密
            /*
            if(isset($cvalue['pwd'])){
                $new_file = str_replace("{$fileName}.pdf", "{$fileName}-pwd.pdf", $f);
                $pwd = substr($userInfo['card_id'], -8, 8);
                exec("pdftk {$f} output {$new_file} user_pw {$pwd}");
                unlink($f);
                $f = $new_file;
                if(!file_exists($f)){
                    $msg = "tender_id= {$tender_id} 的{$cvalue['title']}合同加密失败！";
                    $this->makeFail($tender_id,$msg);
                    return false;
                }
            }*/
        }
        //更新合同任务数据
        $edit_ret = $this->updateTask($tender_id, $fdd_saveNames, $oss_download);
        if($edit_ret == false){
            $msg = "tender_id= {$tender_id} updateTask return false, fdd_saveNames:".print_r($fdd_saveNames, true);
            $this->makeFail($tender_id,$msg);
            return false;
        }

        return true;
    }

    //生成失败报警
    private function makeFail($tender_id, $msg){
        Yii::log($msg, CLogger::LEVEL_ERROR, $this->logFile);
        //更新状态
        $contract_model_name = "{$this->table_prefix}ContractTask";
        $model = $contract_model_name::model()->findByAttributes(['tender_id'=>$tender_id]);
        $model->status = 3;
        $model->save();

        //邮件通知
        $title = '【报警】合同生成失败_'.$contract_model_name;
        FunctionUtil::alertToAccountWx($msg, $title);
    }


    /**
     * 文件上传
     * @param $file
     * @param $key
     * @return bool
     */
    private function upload($file, $key)
    {
        Yii::log(basename($file).'文件正在上传!', CLogger::LEVEL_INFO,$this->logFile);
        try {
            ini_set('memory_limit', '2048M');
            $re = Yii::app()->oss->bigFileUpload($file, $key);
            unlink($file);
            return $re;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, $this->logFile);
            return false;
        }
    }
    /**
     * 日志记录
     * @param $yiilog
     * @param string $level
     */
    public function echoLog($yiilog, $level = "info") {
        echo date('Y-m-d H:i:s ')." ".microtime()." contractTaskHandle {$yiilog} \n";
        Yii::log("contractTaskHandle: {$yiilog}", $level);
    }

}