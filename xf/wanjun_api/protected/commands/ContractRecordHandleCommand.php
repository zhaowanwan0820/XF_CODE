<?php
class ContractRecordHandleCommand extends CConsoleCommand
{
    // php yiic ContractRecordHandle run
    // 日志文件
    protected $logFile = 'ContractRecordHandleCommand';
    const TempDir = '/tmp/contractV2/';
    public $db_name='db';//db-尊享 phdb-普惠
    public $table_prefix = '';

    /**
     * 批量生成合同入口
     * @param int $type 1尊享 2普惠
     * @param string $tender_id
     * @return mixed
     */
    public function actionRun($type=1, $tender_id=''){

        $this->echoLog("ContractRecordHandleCommand start");

        //区分数据库
        if(!in_array($type, [1])){
            $this->echoLog("ContractRecordHandleCommand end, type:$type not in (1)");
            return fasle;
        }
        //普惠
        if($type == 2){
            $this->db_name = "phdb";
            $this->table_prefix = "PH";
        }

        $tenders = $this->getTenders($tender_id);
        if(!$tenders){
            $this->echoLog("ContractRecordHandleCommand end, getTenders return false");
            return fasle;
        }
        $f = $s = 0;
        foreach ($tenders as $k => $v) {
            $create_ret = $this->actionGenerate($v['borrow_id'], $v['tender_id'], $v['user_id'], $v['repay_way'], $v['borrower_fdd_id'], $v['agency_fdd_id'], $v['land_fdd_id']);
            if($create_ret == false){
                $f++;
                $this->echoLog("ContractRecordHandleCommand end, actionGenerate return false, id:{$v['id']}");
            }else{
                $s++;
                $this->echoLog("ContractRecordHandleCommand end, actionGenerate return true, id:{$v['id']}");
            }
        }

        //增加短信报警
        if($f>0){
            $error_info = "YJ_CONTRACT_ALARM：ContractRecordHandle_fail_count：$f";
            $send_ret = SmsIdentityUtils::fundAlarm($error_info, 'ContractRecordHandle');
            $this->echoLog("ContractRecordHandleCommand sendAlarm return $send_ret ");
        }

        $this->echoLog("ContractRecordHandleCommand end, total:".count($tenders)."; success_count:$s; fail_count:$f;");
    }

    /**
     * 获取要生成合同的信息
     * @param string $tender_id
     * @return array
     */
    private function getTenders($tender_id = '')
    {
        $result = [];
        $tender_con = '';
        if(!empty($tender_id) && is_numeric($tender_id)){
            $tender_con .= " and loan_id = {$tender_id} ";
        }
        $contract_model_name = $this->table_prefix.'AgWxContractRecord';
        $criteria = new CDbCriteria;
        $criteria->condition = " status = 0 {$tender_con} ";
        $criteria->limit = 50;

        $records = $contract_model_name::model()->findAll($criteria);
        if (empty($records)) {
            return $result;
        }
        foreach ($records as $key => $value) {
            //拼接需要的数据
            $finalData = [];
            $finalData['id']              = $value->id;
            $finalData['tender_id']       = $value->loan_id;
            $finalData['borrow_id']       = $value->deal_id;
            $finalData['user_id']         = $value->user_id;
            $finalData['repay_way']       = $value->repay_way;
            $finalData['borrower_fdd_id'] = $value->borrower_fdd_id;
            $finalData['agency_fdd_id']   = $value->agency_fdd_id;
            $finalData['land_fdd_id']     = $value->land_fdd_id;
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
        $contract_model_name = "{$this->table_prefix}AgWxContractRecord";
        $model = $contract_model_name::model()->findByAttributes(['loan_id'=>$tender_id]);
        $model ->status = 2;
        $model ->handletime = time();
        $model ->successtime = time();
        $model ->download = json_encode($fdd_download);
        $model ->contract_addr = $oss_download;
        if (!($model->save())) {
            $this->echoLog("updateTask end, loan_id= {$tender_id} error_info:".print_r($model->getErrors(), true));
            $msg = "loan_id= {$tender_id} 数据处理失败！";
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
     * @param $borrow_id
     * @param $tender_id
     * @param $type
     * @param $user_id
     * @return bool
     */
    public function actionGenerate($borrow_id, $tender_id, $user_id, $repay_way, $borrower_fdd_id, $agency_fdd_id, $land_fdd_id){
        // 合同配置信息获取
        $contract_config = Yii::app()->c->contract;

        // 用户信息
        $userInfo = User::model()->findByPk($user_id);
        if(empty($userInfo)){
            $msg = "tender_id = {$tender_id} userInfo->{$user_id} error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        // 证件号，身份证号解密
        $idno = GibberishAESUtil::dec($userInfo['idno'], Yii::app()->c->contract['idno_key']);
        $phone = GibberishAESUtil::dec($userInfo['mobile'], Yii::app()->c->contract['idno_key']);
        $customer_id = $userInfo['yj_fdd_customer_id'];
        if(empty($customer_id)){
            //法大大个人CA申请
            $id_type = $this->convertCardType($userInfo['id_type']);
            $result = YjFddService::getInstance()->invokeSyncPersonAuto($userInfo['real_name'], $userInfo['id'], $idno, $id_type, $phone);
            if(empty($result) || !isset($result['customer_id'])){
                $msg = "tender_id = {$tender_id} 的个人CA申请失败！\n" . print_r($result, true);
                $this->makeFail($tender_id,$msg);
                return false;
            }
            $customer_id = $result['customer_id'];
            $update_sql = "update firstp2p_user set yj_fdd_customer_id = '{$customer_id}' where id = {$user_id}";
            $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
            if(!$edit_fdd){
                $msg = "tender_id = {$tender_id} yj_fdd_customer_id[$customer_id] edit error ";
                $this->makeFail($tender_id,$msg);
                return false;
            }
        }

        // 用户银行卡信息
        if ($repay_way == 1) {
            $user_bankcard_model_name = $this->table_prefix.'UserBankcard';
            $sql = "SELECT * FROM firstp2p_user_bankcard WHERE user_id = {$userInfo['id']} AND verify_status = 1";
            $user_bankcard_info = $user_bankcard_model_name::model()->findBySql($sql);
            if(!$user_bankcard_info){
                $msg = "tender_id = {$tender_id} user_bankcard_info error ";
                $this->makeFail($tender_id, $msg);
                return false;
            }
        }

        // 项目信息
        $deal_model_name = $this->table_prefix.'Deal';
        $deal_info = $deal_model_name::model()->findByPk($borrow_id);
        if(!$deal_info){
            $msg = "tender_id = {$tender_id} deal_info->{$borrow_id} error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        // 借款方信息
        $borrower_info = User::model()->findByPk($deal_info->user_id);
        if(empty($borrower_info)){
            $msg = "tender_id = {$tender_id} borrower_info->{$deal_info->user_id} error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        // 借款方企业信息
        if ($borrower_info->user_type == 0) {
            $sql = "SELECT name AS company_name FROM firstp2p_user_company WHERE user_id = {$borrower_info->id} AND is_effect = 1 AND is_delete = 0";
        } else if ($borrower_info->user_type == 1) {
            $sql = "SELECT company_name FROM firstp2p_enterprise WHERE user_id = {$borrower_info->id} AND company_purpose = 2";
        } else {
            $msg = "tender_id = {$tender_id} borrower_info->user_type error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }
        $company_info = Yii::app()->db->createCommand($sql)->queryRow();
        if (!$company_info) {
            $msg = "tender_id = {$tender_id} company_info->{$borrower_info->id} error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        // 投资记录
        $deal_load_model_name = $this->table_prefix.'DealLoad';
        $deal_loan_info = $deal_load_model_name::model()->findByPk($tender_id);
        if(!$deal_loan_info){
            $msg = "tender_id = {$tender_id} deal_loan_info error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        // 还款计划
        $deal_load_repay_model_name = $this->table_prefix.'DealLoanRepay';
        $sql = "SELECT * FROM firstp2p_deal_loan_repay WHERE deal_id = {$deal_info['id']} AND deal_loan_id = {$deal_loan_info['id']} AND loan_user_id = {$userInfo['id']}";
        $deal_loan_repay_info = $deal_load_repay_model_name::model()->findAllBySql($sql);
        if(!$deal_loan_repay_info){
            $msg = "tender_id = {$tender_id} deal_loan_repay_info error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }
        $deal_loan_repay_s1_total    = 0;
        $deal_loan_repay_s1_t1_total = 0;
        $deal_loan_repay_s1_t2_total = 0;
        $deal_loan_repay_s0_t1_total = 0;
        foreach ($deal_loan_repay_info as $key => $value) {
            if ($value->status == 1 && $value->type == 1) {
                $deal_loan_repay_s1_total    += $value->money;
                $deal_loan_repay_s1_t1_total += $value->money;
            } else if ($value->status == 1 && $value->type == 2) {
                $deal_loan_repay_s1_total    += $value->money;
                $deal_loan_repay_s1_t2_total += $value->money;
            } else if ($value->status == 0 && $value->type == 1) {
                $deal_loan_repay_s0_t1_total += $value->money;
            }
        }

        //债转合同模板，变量拼接
        $time     = time();
        $loantype = array(
            1 => '按季等额还款',
            2 => '按月等额还款',
            3 => '一次性还本付息',
            4 => '按月付息一次还本',
            5 => '按天一次性还款'
        );
        if ($repay_way == 1) {
            // 兑付协议
            $contract_types[$contract_config[3]['template_id']] = [
                'title'  => '兑付协议',
                'params' => [
                    'A_company_name'              => $company_info['company_name'],
                    'B_real_name'                 => $userInfo['real_name'],
                    'B_idno'                      => $idno,
                    'B_mobile'                    => $phone,
                    'deal_load_create_time_year'  => date('Y' , $deal_info['create_time']),
                    'deal_load_create_time_month' => date('m' , $deal_info['create_time']),
                    'deal_load_create_time_day'   => date('d' , $deal_info['create_time']),
                    'deal_name'                   => $deal_info['name'],
                    'deal_load_money'             => $deal_loan_info['money'],
                    'deal_load_wait_capital'      => $deal_loan_info['wait_capital'],
                    'deal_repay_time'             => $deal_info['repay_time'],
                    'deal_rate'                   => round($deal_info['rate'] , 2),
                    'deal_loantype'               => $loantype[$deal_info['loantype']],
                    'deal_loan_repay_s1_total'    => $deal_loan_repay_s1_total,
                    'deal_loan_repay_s1_t1_total' => $deal_loan_repay_s1_t1_total,
                    'deal_loan_repay_s1_t2_total' => $deal_loan_repay_s1_t2_total,
                    'deal_loan_repay_s0_t1_total' => $deal_loan_repay_s0_t1_total,
                    'user_bankcard_name'          => $userInfo['real_name'],
                    'user_bankcard_bankzone'      => $user_bankcard_info['bankzone'],
                    'user_bankcard_bankcard'      => GibberishAESUtil::dec($user_bankcard_info['bankcard'], Yii::app()->c->contract['idno_key']),
                    'now_time'                    => date('Y年m月d日' , $deal_loan_info['confirm_repay_time']),
                ],
                'sign' => [
                    'A_autograph' => $borrower_fdd_id,
                    'B_autograph' => $customer_id,
                    'C_autograph' => $agency_fdd_id,
                ],
                'pwd' => '',
            ];

        } else if ($repay_way == 2) {
            // 以房抵债协议
            $contract_types[$contract_config[4]['template_id']] = [
                'title'  => '',
                'params' => [
                    'A_company_name'              => $company_info['company_name'],
                    'B_real_name'                 => $userInfo['real_name'],
                    'B_idno'                      => $idno,
                    'B_mobile'                    => $phone,
                    'deal_load_create_time_year'  => date('Y' , $deal_info['create_time']),
                    'deal_load_create_time_month' => date('m' , $deal_info['create_time']),
                    'deal_load_create_time_day'   => date('d' , $deal_info['create_time']),
                    'deal_name'                   => $deal_info['name'],
                    'deal_load_money'             => $deal_loan_info['money'],
                    'deal_load_wait_capital'      => $deal_loan_info['wait_capital'],
                    'deal_repay_time'             => $deal_info['repay_time'],
                    'deal_rate'                   => round($deal_info['rate'] , 2),
                    'deal_loantype'               => $loantype[$deal_info['loantype']],
                    'deal_loan_repay_s1_total'    => $deal_loan_repay_s1_total,
                    'deal_loan_repay_s1_t1_total' => $deal_loan_repay_s1_t1_total,
                    'deal_loan_repay_s1_t2_total' => $deal_loan_repay_s1_t2_total,
                    'deal_loan_repay_s0_t1_total' => $deal_loan_repay_s0_t1_total,
                    'now_time'                    => date('Y年m月d日' , $deal_loan_info['confirm_repay_time']),
                ],
                'sign' => [
                    'A_autograph' => $borrower_fdd_id,
                    'B_autograph' => $customer_id,
                    'C_autograph' => $agency_fdd_id,
                    'D_autograph' => $land_fdd_id,
                ],
                'pwd' => '',
            ];
        } else {
            $msg = "tender_id = {$tender_id} repay_way error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        //落地文件地址
        $saveNames = [];
        $fdd_saveNames = [];
        $fdd_save_oss = [];
        $oss_download = '';
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
                $msg = "tender_id = {$tender_id} 的{$cvalue['title']}合同生成失败！\n" . print_r($result,true);
                $this->makeFail($tender_id,$msg);
                return false;
            }
            //法大大合同ID
            $contract_id = $result['contract_id'];

            //加水印
            $text_name = substr("{$userInfo['real_name']}  {$phone}", 0, 45);
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
                    $msg = "tender_id = {$tender_id} 的{$cvalue['title']}合同签署失败！\n" . print_r($result,true);
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
        $contract_model_name = "{$this->table_prefix}AgWxContractRecord";
        $model = $contract_model_name::model()->findByAttributes(['loan_id'=>$tender_id]);
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
        echo date('Y-m-d H:i:s ')." ".microtime()." ContractRecordHandleCommand {$yiilog} \n";
        Yii::log("ContractRecordHandleCommand: {$yiilog}", $level);
    }

    /**
     * 证件类型转化
     */
    private function convertCardType($id_type ){
        //网信：1内陆2护照3军官4港澳6台湾99其他
        //-：1-身份证，2-军官证，3-港澳台通行证，4-护照，5-营业执照（企业用户才有），6-外国人永久居留证
        switch ($id_type) {
            case 1: $id_type = 1; break;
            case 2: $id_type = 4; break;
            case 3: $id_type = 2; break;
            case 4: $id_type = 3; break;
            case 6: $id_type = 3; break;
            case 99: $id_type = 6; break;
            default:$id_type = 1; break;
        }
        return $id_type;
    }
}