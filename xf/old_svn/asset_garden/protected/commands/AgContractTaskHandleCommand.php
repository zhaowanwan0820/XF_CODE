<?php


class AgContractTaskHandleCommand extends CConsoleCommand
{
    // 日志文件
    protected $logFile = 'AgContractTaskCommand';
    const TempDir = '/tmp/AgContract/';



    /**
     * 批量生成合同脚本
     * @param string $tender_id
     * @return mixed
     */
    public function actionRun($tender_id=''){
        $this->echoLog("AgContractTaskHandle start");

        $tenders = $this->getTenders($tender_id);
        if(!$tenders){
            $this->echoLog("ContractTaskHandle end, getTenders return false");
            return fasle;
        }
        $f = $s = 0;
        foreach ($tenders as $k => $v) {
            $create_ret = $this->actionGenerate($v['project_id'], $v['tender_id'], $v['user_id'], $v['e_debt_template']);
            if($create_ret == false){
                $f++;
                $this->echoLog("ContractTaskHandle end, actionGenerate return false, id:{$v['id']}");
            }else{
                $s++;
                $this->echoLog("ContractTaskHandle end, actionGenerate return true, id:{$v['id']}");
            }
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
        $criteria = new CDbCriteria;
        $criteria->condition = " status=0 $tender_con ";
        $criteria->limit = 50;
        $records = AgContractTask::model()->findAll($criteria);
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
            $finalData['project_id'] = $value->project_id;
            $finalData['user_id'] = $value->user_id;
            $finalData['e_debt_template'] = $value->e_debt_template;
            $result[] = $finalData;
        }
        return $result;
    }

    /**
     * 更新表数据
     * @param $tender_id
     * @param $saveName
     * @return boolean
     */
    private function updateTask($tender_id, $fdd_download){
        $model = AgContractTask::model()->findByAttributes(['tender_id'=>$tender_id]);
        $model ->status = 2;
        $model ->handletime = time();
        $model ->download = json_encode($fdd_download);
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
     * @param $borrow_id
     * @param $tender_id
     * @param $user_id
     * @param $e_debt_template
     * @return bool
     */
    public function actionGenerate($borrow_id, $tender_id, $user_id, $e_debt_template){
        //合同配置信息获取
        $contract_config = Yii::app()->c->contract;
        //用户信息
        $userInfo = AgUser::model()->findByPk($user_id);
        if(empty($userInfo)){
            $msg = "tender_id= {$tender_id} 的合同生成失败！原因：买方{$user_id}用户信息不存在";
            $this->makeFail($tender_id, $msg);
            return false;
        }
        $customer_id = $userInfo->fdd_customer_id;
        if(empty($customer_id)){
            //法大大个人CA申请
            $result = FddService::getInstance()->invokeSyncPersonAuto($userInfo->real_name, $userInfo->mail, $userInfo->id_no, $userInfo->id_type, $userInfo->phone);
            if(empty($result) || !isset($result['customer_id'])){
                $msg = "tender_id= {$tender_id} 的个人CA申请失败！\n" . print_r($result, true);
                $this->makeFail($tender_id,$msg);
                return false;
            }
            //更新用户法大大ID
            $customer_id = $result['customer_id'];
            $userInfo->fdd_customer_id = $result['customer_id'];
            if(false == $userInfo->save()){
                $msg = "tender_id= {$tender_id} fdd_customer_id[$customer_id] edit error！";
                $this->makeFail($tender_id,$msg);
                return false;
            }
        }

        //项目信息
        $project_info = AgProject::model()->findByPk($borrow_id);
        if(!$project_info){
            $msg = "tender_id= {$tender_id} AgProject error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        //投资记录
        $tender_info = AgTender::model()->findByPk($tender_id);
        if(!$tender_info){
            $msg = "tender_id= {$tender_id} AgTender error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        //平台信息校验
        $check_p = AgDebtService::getInstance()->checkPlatform($tender_info->platform_id);
        if($check_p['code'] != 0){
            $msg = "tender_id= {$tender_id} checkPlatform error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }
        //平台信息
        $plat_info = $check_p['data'];

        //出让方信息
        $debt_sql = "select t.id,d.user_id,d.tender_id from ag_debt_tender t left join ag_debt d on t.debt_id=d.id where t.new_tender_id=$tender_id ";
        $debt_info = Yii::app()->agdb->createCommand($debt_sql)->queryRow();
        if(!$debt_info){
            $msg = "tender_id= {$tender_id} debt_info error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        //卖方用户信息
        $sellerUserInfo = AgUser::model()->findByPk($debt_info['user_id']);
        if(empty($sellerUserInfo)){
            $msg = "tender_id= {$tender_id} 的合同生成失败！原因：卖方{$debt_info['user_id']}用户信息不存在";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        //卖方法大大注册
        $seller_customer_id = $sellerUserInfo['fdd_customer_id'];
        if(empty($seller_customer_id)){
            //法大大个人CA申请
            $seller_result = FddService::getInstance()->invokeSyncPersonAuto($sellerUserInfo->real_name, $sellerUserInfo->mail, $sellerUserInfo->id_no, $sellerUserInfo->id_type, $sellerUserInfo->phone);
            if(empty($seller_result) || !isset($seller_result['customer_id'])){
                $msg = "tender_id= $tender_id, seller_user_id={$debt_info['user_id']} 的个人CA申请失败！\n" . print_r($seller_result, true);
                $this->makeFail($tender_id, $msg);
                return false;
            }
            //更新用户法大大ID
            $seller_customer_id = $seller_result['customer_id'];
            $sellerUserInfo->fdd_customer_id = $seller_customer_id;
            if(false == $sellerUserInfo->save()){
                $msg = "tender_id= {$tender_id} seller_fdd_customer_id[$seller_customer_id] edit error！";
                $this->makeFail($tender_id,$msg);
                return false;
            }
        }

        //原投资记录
        $seller_tender = AgTender::model()->findByPk($debt_info['tender_id']);
        if(!$seller_tender){
            $msg = "tender_id= {$tender_id},seller_tender_id[{$debt_info['tender_id']}]: seller_ag_tender error ";
            $this->makeFail($tender_id, $msg);
            return false;
        }

        //原始合同编号
        $seller_contract_number = $seller_tender->bond_no;
        //项目到期日
        $due_date = $project_info->due_date;
        $new_bond_no = implode('-', [date('Ymd', $tender_info->addtime), $tender_info->platform_id, $borrow_id, $tender_id]);
        //新协议
        $contract_types[$e_debt_template] = [
            'title'  => '债权转让与受让协议',
            'params' => [
                'contract_id'     => $new_bond_no,
                'A_user_name'     => $sellerUserInfo->real_name,
                'A_card_id'       => $sellerUserInfo->id_no,
                'B_user_name'     => $userInfo->real_name,
                'B_card_id'       => $userInfo->id_no,
                'A_contract_id'   => $seller_contract_number,//卖家原始出借合同编号
                'borrow_name'     => $project_info->name,
                'apr'             => sprintf("%.2f", $project_info->apr).'%',
                'style'           => $contract_config['project_style'][$project_info->style],
                'sign_year'       => date('Y', $tender_info->addtime),
                'sign_month'      => date('m', $tender_info->addtime),
                'sign_day'        => date('d', $tender_info->addtime),
                'company_name'    => $plat_info->company_name,
                'plan_name'       => $plat_info->name,
                'web_address'     => $plat_info->platform_url,
                'debt_start_date' => date('Y-m-d', $tender_info->addtime),
                'debt_end_date'   => date('Y-m-d', $due_date),
                'account'         => $tender_info->wait_capital,
            ],
            'sign' => [
                'A盖签' => $seller_customer_id,
                'B盖签' => $customer_id,
            ],
            'pwd' => '',
        ];

        //落地文件地址
        $saveNames = [];
        $fdd_saveNames = [];
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
            $date = date('Ymd', $tender_info->addtime);
            $fileName = 'contract_' . $date . '-' . $borrow_id . '-' . $tender_id . '-' . $ckey;
            $initData = file_get_contents($result['download_url']);
            $f = $this->OutPutToPath($initData, $fileName, $borrow_id);
            if ($f === false) {
                $msg = "tender_id= {$tender_id} 的{$cvalue['title']}合同落地失败！";
                $this->makeFail($tender_id,$msg);
                return false;
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

        //买方合同编号更新
        $tender_info->bond_no = $new_bond_no;
        if(false == $tender_info->save()){
            $msg = "tender_id= {$tender_id} update tender bond_no return false, new_bond_no:$new_bond_no";
            $this->makeFail($tender_id,$msg);
            return false;
        }
        //卖方合同编号更新
        $edit_buyer = AgDebtTender::model()->updateByPk($debt_info['id'], ['bond_no'=>$new_bond_no, 'c_download_url'=>$result['download_url'], 'c_viewpdf_url'=>$result['viewpdf_url']]);
        if(!$edit_buyer){
            $msg = "tender_id= {$tender_id} update debt_tender bond_no return false, new_bond_no:$new_bond_no";
            $this->makeFail($tender_id,$msg);
            return false;
        }

        //更新合同任务数据
        $edit_ret = $this->updateTask($tender_id, $fdd_saveNames);
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
        $model = AgContractTask::model()->findByAttributes(['tender_id'=>$tender_id]);
        $model->status = 3;
        $model->save();

        //邮件通知
        $title = '【报警】合同生成失败';
        FunctionUtil::alertToAccountWx($msg, $title);
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

    /**
     * 证件类型转化
     */
    private function convertCardType($id_type ){
        //网信：1内陆2护照3军官4港澳6台湾99其他
        //爱投资：1-身份证，2-军官证，3-港澳台通行证，4-护照，5-营业执照（企业用户才有），6-外国人永久居留证
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