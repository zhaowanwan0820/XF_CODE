<?php


class ContractTaskOldHandleCommand extends CConsoleCommand
{
    // 日志文件
    protected $logFile = 'ContractTaskHandleCommand';
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

        $this->echoLog("ContractTaskHandleOld start");

        //区分数据库
        if(!in_array($type, [1,2])){
            $this->echoLog("ContractTaskHandleOld end, type:$type not in (1,2)");
            return fasle;
        }
        //普惠
        if($type == 2){
            $this->db_name = "phdb";
            $this->table_prefix = "PH";
        }

        $tenders = $this->getTenders($tender_id);
        if(!$tenders){
            $this->echoLog("ContractTaskHandleOld end, getTenders return false");
            return fasle;
        }
        $fa = $sa = 0;
        foreach ($tenders as $k => $v) {
           $downUrl = json_decode($v['download'],true);
            foreach($downUrl as $k => $val){
                $download_url = $val['download_url'];
            }
            //判断url中是否存在
            if(strpos("%C3%97",$download_url) !== FALSE){
                $download_url = str_replace("%C3%97","&times",$download_url);
            }
            if(str_replace("×","&times",$download_url) !== FALSE){
                $download_url = str_replace("×","&times",$download_url);
            }
            //文件下载至本地临时目录
            if (!is_dir(self::TempDir . $v['borrow_id'])) {
                mkdir(self::TempDir . $v['borrow_id'], 0777, true);
            }
            $date = date('Ymd');
            $fileName = 'contract_' . $date . '-' . $v['borrow_id'] . '-' . $v['tender_id'];
            $initData = file_get_contents($download_url);
            $f = $this->OutPutToPath($initData, $fileName, $v['borrow_id']);
            if ($f === false) {
                $msg = "tender_id= {$tender_id} 的合同落地失败！";
                return false;
            }
            //落地成功后更新到local_download上
            echo $tender_id . "开始上传到oss\r\n";
            // 上传到Oss
            $saveName = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR . $v['borrow_id'] . DIRECTORY_SEPARATOR . $fileName . '.pdf';
            $re = $this->upload($f, $saveName);
            if ($re === false) {
                $fa ++;
                Yii::log("tender_id = $tender_id 的合同上传oss失败！", CLogger::LEVEL_ERROR, $this->logFile);
            }else{
                $sa++;
                Yii::log("tender_id = $tender_id 的合同上传oss成功！", CLogger::LEVEL_INFO, $this->logFile);
                //更新oss_download地址
                $edit_ret = $this->updateTask($v['tender_id'], $saveName);
                if($edit_ret == false){
                    $msg = "tender_id= {$v['tender_id']} updateTask return false, fdd_saveNames:".print_r($saveName, true);
                }
            }

        }
        $this->echoLog("ContractTaskHandleOld end, total:".count($tenders)."; success_count:$sa; fail_count:$fa;");
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
        $criteria->condition = "status=2 and download != '' and oss_download = '' $tender_con ";
        $criteria->limit = 50;
        $records = $contract_model_name::model()->findAll($criteria);
        if (empty($records)) {
            return $result;
        }
        foreach ($records as $key => $value) {
            //拼接需要的数据
            $finalData = [];
            $finalData['tender_id'] = $value->tender_id;
            $finalData['download'] = $value->download;
            $finalData['borrow_id'] = $value->borrow_id;
            $finalData['oss_download'] = $value->oss_download;
            $finalData['task_id'] = $value->task_id;
            $result[] = $finalData;
        }
        return $result;
    }

    /**
     * 更新表数据
     * @param $tender_id
     * @param $saveName
     */
    private function updateTask($tender_id, $fdd_download){
        $contract_model_name = "{$this->table_prefix}ContractTask";
        $model = $contract_model_name::model()->findByAttributes(['tender_id'=>$tender_id]);
        $model ->oss_download = $fdd_download;
        if (!($model->save())) {
            $this->echoLog("updateTask end, tender_id= {$tender_id} error_info:".print_r($model->getErrors(), true));
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