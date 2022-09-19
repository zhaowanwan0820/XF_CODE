<?php


class YjShopContractCommand extends CConsoleCommand
{
    // 日志文件
    protected $logFile = 'YjShopContractCommand';
    const TempDir = '/tmp/yjShopContract/';
    

    /**
     * 批量生成合同入口
     * @param string $id
     * @return mixed
     */
    public function actionRun($id=''){
        $this->echoLog("yjShopContract start");

        //获取待处理任务
        $condition = " status=0 ";
        if(!empty($id) && is_numeric($id)){
            $condition .= " and id=$id";
        }
        $criteria = new CDbCriteria;
        $criteria->condition = $condition;
        $criteria->limit = 50;
        $records = AgWxShopContract::model()->findAll($criteria);
        if (empty($records)) {
            $this->echoLog("yjShopContract end, getHandelList return false");
            return false;
        }

        //逐一处理
        $f = $s = 0;
        foreach ($records as $k => $v) {
            $create_ret = $this->actionGenerate($v);
            if($create_ret == false){
                $f++;
                $this->echoLog("yjShopContract end, actionGenerate return false, id:{$v['id']}");
            }else{
                $s++;
                $this->echoLog("yjShopContract end, actionGenerate return true, id:{$v['id']}");
            }
        }

        //增加短信报警
        if($f>0){
            $error_info = " yjShopContract_fail_count：$f";
            $send_ret = FunctionUtil::alertToAccountTeam($error_info);
            $this->echoLog("yjShopContract sendAlarm return $send_ret ");
        }
        
        $this->echoLog("yjShopContract end, total:".count($records)."; success_count:$s; fail_count:$f;");
    }




    /**
     * 合同落地
     * @param $data
     * @param $fileName
     * @return bool|string
     */
    private function OutPutToPath($data, $fileName)
    {
        $filePath = self::TempDir.$fileName . '.pdf';
        $status = file_put_contents($filePath, $data);
        if (!$status) {
            return false;
        }
        $this->echoLog($filePath . ' 合同生成并落地成功!');
        return $filePath;
    }


    /**
     * 合同生成
     * @param $record_info
     * @return bool
     */
    public function actionGenerate($record_info){
        //合同配置信息获取
        $contract_config = Yii::app()->c->contract;
        //基础参数校验
        if(empty($record_info) || empty($contract_config) || empty($record_info->user_id)){
            $this->echoLog("id= {$record_info->id} 的电子协议生成失败！contract_config error or record_info error " );
            return false;
        }
        $id = $record_info->id;
        $user_id = $record_info->user_id;
        //用户信息
        $userInfo = User::model()->findByPk($user_id)->attributes;
        if(empty($userInfo)){
            $msg = "id= {$id} 的合同生成失败！原因：买方{$user_id}用户信息不存在";
            $this->makeFail($id, $msg);
            return false;
        }

        //证件号，身份证号解密
        $idno = GibberishAESUtil::dec($userInfo['idno'], Yii::app()->c->contract['idno_key']);
        $phone = GibberishAESUtil::dec($userInfo['mobile'], Yii::app()->c->contract['idno_key']);
        $a_customer_id = $userInfo['yj_fdd_customer_id'];
        if(empty($a_customer_id)){
            //法大大个人CA申请
            $id_type = DebtService::getInstance()->convertCardType($userInfo['id_type']);
            $result = YjFddService::getInstance()->invokeSyncPersonAuto($userInfo['real_name'], $userInfo['id'], $idno, $id_type, $phone);
            if(empty($result) || !isset($result['customer_id'])){
                $msg = "id= {$id} 的个人CA申请失败！\n" . print_r($result, true);
                $this->makeFail($id, $msg);
                return false;
            }
            $a_customer_id = $result['customer_id'];
            $update_sql = "update firstp2p_user set yj_fdd_customer_id = '{$a_customer_id}' where id = {$user_id}";
            $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
            if(!$edit_fdd){
                $msg = "user_id= {$user_id} yj_fdd_customer_id[$a_customer_id] edit error！";
                $this->makeFail($id, $msg);
                return false;
            }
        }

        //银行名称转换
        $contract_data_list = $record_info->contract_data;
        $contract_data = json_decode($contract_data_list, true);

        //权益兑换合同模板，变量拼接
        $contract_types[$contract_config[5]['template_id']] = [
            'title'  => "债权兑换积分服务协议",
            'params' => [
                'contract_id' => implode('-', [date('Ymd', $record_info->addtime), $record_info->type, $id, $user_id]),
                "yyNumber" =>  $contract_data['yyNumber'],//            盈益标的数量
                "jhNumber" =>  $contract_data['jhNumber'],//            嘉汇标的数量
                "yjNumber" =>  $contract_data['yjNumber'],//            盈嘉标的数量
                "hsNumber" =>  $contract_data['hsNumber'], //           汇晟标的数量
                "gylNumber" =>  $contract_data['gylNumber'],//           供应链标的数量
                "gtNumber" =>  $contract_data['gtNumber'],//            个体经营贷标的数量
                "qyNumber" =>  $contract_data['qyNumber'], //           企业经营贷标的数量
                "totalNumber" =>  $contract_data['totalNumber'], //      标的总金额
                'sign_date'  => date('Y年m月d日'),
            ],
            'sign' => [
                'A盖签' => $a_customer_id,
                'B盖签' => $contract_config[5]['yj_fdd_id'],
            ],
            'pwd' => '',
        ];

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
                $msg = "id= {$id} 的{$cvalue['title']}合同生成失败！\n" . print_r($result,true);
                $this->makeFail($id, $msg);
                return false;
            }
            //法大大合同ID
            $contract_id = $result['contract_id'];

            //合同签署（自动签）多次签署
            foreach ($cvalue['sign'] as $sign_key => $sign_value){
                $result = FddService::getInstance()->invokeExtSignAuto($sign_value, $contract_id, $doc_title, $sign_key);
                if(!$result || $result['code'] != 1000){
                    $msg = "id= {$id} 的{$cvalue['title']}}合同签署失败！\n" . print_r($result,true);
                    $this->makeFail($id, $msg);
                    return false;
                }
            }

            $fdd_saveNames[$contract_id] = [
                'contract_id'  => $contract_id,
                'template_id'  => $template_id,
                'doc_name'     => $doc_title,
                'viewpdf_url'  => $result['viewpdf_url'],
                'download_url' => $result['download_url'],
            ];

            $this->echoLog("id = $id 的fdd合同信息：fdd_info:".print_r($fdd_saveNames, true));

            //文件下载至本地临时目录
            if (!is_dir(self::TempDir)) {
                mkdir(self::TempDir, 0777, true);
            }
            $date = date('Ymd');
            $fileName = 'contract_' . $date . '-' . $user_id . '-' . $record_info->id . '-' . $ckey;
            $initData = file_get_contents($result['download_url']);
            $f = $this->OutPutToPath($initData, $fileName);
            if ($f === false) {
                $msg = "id= {$id} 的{$cvalue['title']}合同落地失败！";
                $this->makeFail($id, $msg);
                return false;
            }
            //落地成功后更新到oss_download上
            echo $id . "开始上传到oss\r\n";
            // 上传到Oss
            $oss_download = $saveName = 'yjShopContract/contracts' . DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR . $fileName.'.pdf';
            echo $oss_download;
            $re = $this->upload($f, $saveName);
            if ($re === false) {
                $this->echoLog("id = $id 的合同上传oss失败！");
            }else{
                $this->echoLog("id = $id 的合同上传oss成功！");
                echo "修改id= $id 的记录状态... \r\n";
            }

        }

        //更新合同生成状态
        $edit_ret = AgWxShopContract::model()->updateByPk($id, ['status'=>1,'contract_url'=>$oss_download]);
        if(!$edit_ret){
            $msg = "id= {$id} edit AgWxShopContract error, contract_url:$oss_download";
            $this->makeFail($id, $msg);
            return false;
        }

        return true;
    }

    //生成失败报警
    private function makeFail($id, $msg){
        $this->echoLog($msg);
        //更新状态
       // AgWxShopContract::model()->updateByPk($id, ['status'=>3]);
    }


    /**
     * 文件上传
     * @param $file
     * @param $key
     * @return bool
     */
    private function upload($file, $key)
    {
        $this->echoLog(basename($file).'文件正在上传!');
        try {
            ini_set('memory_limit', '2048M');
            $re = Yii::app()->oss->bigFileUpload($file, $key);
            unlink($file);
            return $re;
        } catch (Exception $e) {
            $this->echoLog($e->getMessage());
            return false;
        }
    }
    /**
     * 日志记录
     * @param $yiilog
     * @param string $level
     */
    public function echoLog($yiilog, $level = "info") {
        echo date('Y-m-d H:i:s ')." ".microtime()." yjShopContract {$yiilog} \n";
        Yii::log("yjShopContract: {$yiilog}", $level);
    }

}