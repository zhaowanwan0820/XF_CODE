<?php

class HandleOfflineDataCommand extends CConsoleCommand
{

    private $fnLockTender = '/tmp/HandleOfflineDataRunTender.pid';
    private $fnLockRepay = '/tmp/HandleOfflineDataRunRepay.pid';
    private $fnLockAccount = '/tmp/HandleOfflineDataRunAccount.pid';
    private $repayLogErrorNum = 0;
    private $repayLogErrorCapital = 0;
    private $repayLogErrorInterest = 0;

    /**
     * 跑脚本加锁
     */
    private static function enterLock($config)
    {
        if (empty($config['fnLock'])) {
            return false;
        }
        $fnLock = $config['fnLock'];
        $fpLock = fopen($fnLock, 'w+');
        if ($fpLock) {
            if (flock($fpLock, LOCK_EX | LOCK_NB)) {
                return $fpLock;
            }
            fclose($fpLock);
            $fpLock = null;
        }

        return false;
    }

    /**
     * 检查跑脚本加锁
     */
    private static function releaseLock($config)
    {
        if (!$config['fpLock']) {
            return;
        }
        $fpLock = $config['fpLock'];
        $fnLock = $config['fnLock'];
        flock($fpLock, LOCK_UN);
        fclose($fpLock);
        unlink($fnLock);
    }

    //输出日志
    public static function echoLog($log, $warning = false)
    {
        echo date('Y-m-d H:i:s ')." HandleOfflineData : {$log} \n";
        if ($warning) {
            Yii::log("HandleOfflineData {$log}", 'error');
            try {
                DingNotice::warning('线下数据迁移处理脚本异常', $log);
            }catch (Exception $e){
                Yii::log("HandleOfflineData ding error  {$e->getMessage()}", 'error');
            }
        }
    }

    private $limit = 500;

    /**
     * 出借记录导入.
     */
    public function actionTenderRun()
    {
        self::echoLog('start');
        $fpLock = self::enterLock(['fnLock' => $this->fnLockTender]);
        if (!$fpLock) {
            exit(__CLASS__.' '.__METHOD__.'  running!!!');
        }

        $importFile = OfflineImportFile::model()->findByAttributes(['auth_status' => 1, 'deal_status' => 0]);
        if ($importFile) {
            self::echoLog('start handle import file:'.$importFile->id);
            self::echoLog('start deal bank_id ! import file:'.$importFile->id);
            $this->actionDealBankId($importFile->id);
            self::echoLog('deal bank_id  success ! import file:'.$importFile->id);
            while (true) {
                $sql = "select * from offline_import_content where file_id = {$importFile->id} and status = 1 and  deal_status = 0 limit {$this->limit}";
                $import = OfflineImportContent::model()->findAllBySql($sql);
                if (empty($import)) {
                    break;
                }
                foreach ($import as $value) {
                    try {
                        Yii::app()->fdb->beginTransaction();
                        Yii::app()->offlinedb->beginTransaction();
                        //处理业务相关数据
                        HandleOfflineDataService::tenderRun($importFile->platform_id, $value->getAttributes());
                        //处理导入记录状态
                        $value->deal_status = 1;
                        $value->status = 4;
                        $value->update_time = time();
                        if (false == $value->save()) {
                            throw new Exception(__FUNCTION__.'更新offline_import_content  deal_status = 1 失败 id :'.$value->id);
                        }
                        Yii::app()->fdb->commit();
                        Yii::app()->offlinedb->commit();
                    } catch (Exception $e) {
                        self::echoLog($e->getMessage());
                        Yii::app()->fdb->rollback();
                        Yii::app()->offlinedb->rollback();
                        $value->deal_status = 1;
                        $value->status = 5;
                        $value->update_time = time();
                        $value->remark = $e->getMessage();
                        if (false == $value->save()) {
                            self::echoLog(__FUNCTION__.'更新offline_import_content deal_status = 2 失败 id :'.$value->id, true);
                        }

                    }
                }
            }

            $sql = "select status,sum(wait_capital) as wait_capital ,sum(wait_interest) as wait_interest,count(1) as num from offline_import_content where file_id = {$importFile->id} and status in (4,5) group by status";

            $result = Yii::app()->offlinedb->createCommand($sql)->queryAll();
            foreach ($result as $item){
                if($item['status'] == 5){
                    $importFile->handle_fail_capital_amount = $item['wait_capital'];
                    $importFile->handle_fail_interest_amount = $item['wait_interest'];
                    $importFile->handle_fail_num = $item['num'];
                }else{
                    $importFile->handle_success_num = $item['num'];;
                    $importFile->handle_success_capital_amount = $item['wait_capital'];
                    $importFile->handle_success_interest_amount = $item['wait_interest'];
                }
            }

            $importFile->deal_status = 1;
            $importFile->update_time = time();
            if (false == $importFile->save()) {
                self::echoLog(__FUNCTION__.'更新导入文件表处理状态失败 error:'.print_r($importFile->getErrors(),true), true);
            }
        } else {
            self::echoLog('没有待处理的file记录');
        }
        self::echoLog('脚本执行完成');
        self::releaseLock(['fnLock' => $this->fnLockTender, 'fpLock' => $fpLock]);
    }

    /**
     * 还款计划导入.
     */
    public function actionRepayRun()
    {
        $fpLock = self::enterLock(['fnLock' => $this->fnLockRepay]);
        if (!$fpLock) {
            exit(__CLASS__.' '.__METHOD__.'  running!!!');
        }
        $uploadRepayFile = OfflineUploadRepayFile::model()->findByAttributes(['auth_status' => 1, 'deal_status' => 0]);

        if (!empty($uploadRepayFile)) {
            self::echoLog('start handle repay file:'.$uploadRepayFile->id);
            //批量校验已导入的债权关系与当前导入的还款计划涉及的资金是否一致；
            $this->checkTenderAndRepayData($uploadRepayFile);

            while (true) {
                $sql = "select * from offline_upload_repay_log where file_id = {$uploadRepayFile->id} and status = 1 and  deal_status = 0 limit {$this->limit}";
                $uploadRepayLog = OfflineUploadRepayLog::model()->findAllBySql($sql);
                if (empty($uploadRepayLog)) {
                    break;
                }
                foreach ($uploadRepayLog as $value) {
                    try {
                        Yii::app()->offlinedb->beginTransaction();
                        //处理业务相关数据
                        HandleOfflineDataService::repayRun($uploadRepayFile->platform_id, $value->getAttributes());
                        //处理导入记录状态
                        $value->deal_status = 1;
                        $value->status = 4;
                        $value->update_time = time();
                        if (false == $value->save()) {
                            throw new Exception(__FUNCTION__.'更新offline_upload_repay_log  deal_status = 1 失败 id :'.$value->id);
                        }
                        Yii::app()->offlinedb->commit();
                        ++$uploadRepayFile->handle_success_num;
                        $uploadRepayFile->handle_success_capital_amount += $value->capital;
                        $uploadRepayFile->handle_success_interest_amount += $value->interest;
                    } catch (Exception $e) {
                        self::echoLog($e->getMessage(), true);
                        Yii::app()->offlinedb->rollback();
                        $value->deal_status = 1;
                        $value->status = 5;
                        $value->update_time = time();
                        $value->remark = $e->getMessage();
                        if (false == $value->save()) {
                            self::echoLog(__FUNCTION__.'更新offline_upload_repay_file deal_status = 2 失败 id :'.$value->id, true);
                        }
                        ++$uploadRepayFile->handle_fail_num;
                        $uploadRepayFile->handle_fail_capital_amount += $value->capital;
                        $uploadRepayFile->handle_fail_interest_amount += $value->interest;
                    }
                }
            }
            $uploadRepayFile->handle_fail_num += $this->repayLogErrorNum;
            $uploadRepayFile->handle_fail_capital_amount += $this->repayLogErrorCapital;
            $uploadRepayFile->handle_fail_interest_amount += $this->repayLogErrorInterest;
            $uploadRepayFile->deal_status = 1;
            $uploadRepayFile->update_time = time();
            if (false == $uploadRepayFile->save()) {
                self::echoLog(__FUNCTION__.'更新导入文件表处理状态失败 error:'.print_r($uploadRepayFile->getErrors(),true), true);
            }
        } else {
            self::echoLog('没有待处理的file记录');
        }
        self::echoLog('脚本执行完成');
        self::releaseLock(['fnLock' => $this->fnLockRepay, 'fpLock' => $fpLock]);
    }

    /**
     *批量校验已导入的债权关系与当前导入的还款计划涉及的资金是否一致.
     *
     * @param $uploadRepayFile
     */
    private function checkTenderAndRepayData($uploadRepayFile)
    {
        ini_set('memory_limit', '1024M');
        //获取当前导入的还款计划
        $uploadRepayGroupLogs = Yii::app()->offlinedb->createCommand("select sum(capital) as wait_capital ,sum(interest) as wait_interest ,order_sn ,count(order_sn) as num  from offline_upload_repay_log where order_sn > 0 and  status = 1  group by order_sn ")->queryAll();
        if ($uploadRepayGroupLogs && $groupLogs = ArrayUntil::array_column($uploadRepayGroupLogs, null, 'order_sn')) {
            //获取出借记录表数据
            $tenderInfosArr = Yii::app()->offlinedb->createCommand('select order_sn,wait_capital,wait_interest from offline_deal_load where order_sn in (select distinct(order_sn) as order_sn from offline_upload_repay_log where  order_sn > 0 and  status = 1  )')->queryAll();
            $tenderInfos = ArrayUntil::array_column($tenderInfosArr, null, 'order_sn');
            //开始比对
            foreach ($groupLogs as $order_sn => $groupLog) {
                $error = '';
                if (isset($tenderInfos[$order_sn])) {
                    if (!FunctionUtil::float_equal($tenderInfos[$order_sn]['wait_capital'], $groupLog['wait_capital'], 3)) {
                        $error = '还款计划总待还本金与债权关系中的待还本金金额不一致 出借记录待还:'.$tenderInfos[$order_sn]['wait_capital'].'还款计划总待还:'.$groupLog['wait_capital'].' 原订单号:'.$order_sn.PHP_EOL;
                    }
                    if (!FunctionUtil::float_equal($tenderInfos[$order_sn]['wait_interest'], $groupLog['wait_interest'], 3)) {
                        $error .= '还款计划总待还利息与债权关系中的待还利息金额不一致 出借记录待还:'.$tenderInfos[$order_sn]['wait_interest'].'还款计划总待还:'.$groupLog['wait_interest'].' 原订单号:'.$order_sn;
                    }
                } else {
                    $error = '债权关系未导入完整。'.' 原订单号:'.$order_sn;
                }
                if (!empty($error)) {
                    $this->repayLogErrorNum += $groupLog['num'];
                    $this->repayLogErrorCapital += $groupLog['wait_capital'];
                    $this->repayLogErrorInterest += $groupLog['wait_interest'];
                    $res = OfflineUploadRepayLog::model()->updateAll(['remark' => $error, 'update_time' => time(), 'status' => 5], "order_sn = '{$order_sn}'");
                    if (false == $res) {
                        self::echoLog('还款计划与债权关系金额比对存在不一致 更新导入还款计划失败状态fail 原始订单号：'.$order_sn.' error:'.$error);
                    }
                }
            }
        }
    }

    /**
     * 用户账户数据导入
     */
    public function actionAccountRun(){
        $fpLock = self::enterLock(['fnLock' => $this->fnLockAccount]);
        if (!$fpLock) {
            exit(__CLASS__.' '.__METHOD__.'  running!!!');
        }
        $uploadUserAccountFile = OfflineUploadUserAccountFile::model()->findByAttributes(['auth_status' => 1, 'deal_status' => 0]);

        if (!empty($uploadUserAccountFile)) {
            self::echoLog('start handle user account file:'.$uploadUserAccountFile->id);
            //批量校验已导入的债权关系与当前导入的还款计划涉及的资金是否一致；
            $this->checkTenderAndRepayData($uploadUserAccountFile);

            while (true) {
                $sql = "select * from offline_upload_user_account_log where file_id = {$uploadUserAccountFile->id} and status = 1 and  deal_status = 0 limit {$this->limit}";
                $uploadUserAccountLog = OfflineUploadUserAccountLog::model()->findAllBySql($sql);
                if (empty($uploadUserAccountLog)) {
                    break;
                }
                foreach ($uploadUserAccountLog as $value) {
                    try {
                        Yii::app()->offlinedb->beginTransaction();
                        //处理业务相关数据
                        HandleOfflineDataService::accountRun($uploadUserAccountFile->platform_id, $value->getAttributes());
                        //处理导入记录状态
                        $value->deal_status = 1;
                        $value->status = 4;
                        $value->update_time = time();
                        if (false == $value->save()) {
                            throw new Exception(__FUNCTION__.'更新offline_upload_user_account_log  deal_status = 1 失败 id :'.$value->id);
                        }
                        Yii::app()->offlinedb->commit();
                        ++$uploadUserAccountFile->handle_success_num;
                        $uploadUserAccountFile->handle_success_wait_amount += $value->wait_amount;
                    } catch (Exception $e) {
                        self::echoLog($e->getMessage(), true);
                        Yii::app()->offlinedb->rollback();
                        $value->deal_status = 1;
                        $value->status = 5;
                        $value->update_time = time();
                        $value->remark = $e->getMessage();
                        if (false == $value->save()) {
                            self::echoLog(__FUNCTION__.'更新offline_upload_user_account_file deal_status = 2 失败 id :'.$value->id, true);
                        }
                        ++$uploadUserAccountFile->handle_fail_num;
                        $uploadUserAccountFile->handle_fail_wait_amount += $value->wait_amount;
                    }
                }
            }

            $uploadUserAccountFile->deal_status = 1;
            $uploadUserAccountFile->update_time = time();
            if (false == $uploadUserAccountFile->save()) {
                self::echoLog(__FUNCTION__.'更新导入文件表处理状态失败 error:'.print_r($uploadUserAccountFile->getErrors(),true), true);
            }
        } else {
            self::echoLog('没有待处理的file记录');
        }
        self::echoLog('脚本执行完成');
        self::releaseLock(['fnLock' => $this->fnLockAccount, 'fpLock' => $fpLock]);
    }


    public function actionCommonPhone(){



//        $importFiles = OfflineImportFile::model()->findAllByAttributes(['auth_status' => 1, 'deal_status' => 0]);
//        if(empty($importFiles)){
//            self::echoLog('no import file data');
//        }
        //写文件
        $file_log = "./user_common_mobile_phone.csv";
        $tmp_file_log = fopen($file_log, "w");
        $title_log = [
            'mobile'            =>  '手机号码',
            'user_name'         =>  '待导入的姓名',
            'idno'              =>  '待导入的证件号',
            'isset_user_name'   =>  '已存在的姓名',
            'isset_idno'        =>  '已存在的证件号',
            'isset_mobile'      =>  '已存在的手机号',
            'isset_user_id'     =>  '已存在user_id'
        ];
        $out_log = iconv("UTF-8","gbk//IGNORE",implode(",",$title_log))."\n";
        fwrite($tmp_file_log,$out_log);
        //foreach ($importFiles as $importFile) {
            $sql = "select mobile_phone,user_name,idno from offline_import_content where file_id = 328 group by idno ";
            $content= OfflineImportContent::model()->findAllBySql($sql);
            if(empty($content)){
                //continue;
            }
            foreach ($content as $item) {
                if(empty($item->mobile_phone)){
                    continue;
                }

                if(empty($item->idno)){
                    $data = [
                        'mobile'            =>  $item->mobile_phone,
                        'user_name'         =>  $item->user_name,
                        'idno'              =>  $item->idno."\t",
                        'isset_user_name'   =>  '',
                        'isset_idno'        =>  ''."\t",
                        'isset_mobile'        =>  ''."\t",
                        'isset_user_id'     =>  0
                    ];
                    $out = iconv("UTF-8","gbk//IGNORE",implode(",",$data))."\n";
                    fwrite($tmp_file_log,$out);
                    continue;
                }
                $_id_no = GibberishAESUtil::enc(trim($item->idno), Yii::app()->c->idno_key);
                $_mobile = GibberishAESUtil::enc(trim($item->mobile_phone), Yii::app()->c->idno_key);
                $userInfo = Firstp2pUser::model()->findByAttributes(['is_effect' => 1, 'mobile' => $_mobile]);

                if($userInfo ){
                    if ($userInfo->idno !== $_id_no || trim($userInfo->real_name) !== trim($item->user_name)) {
                        //excel数据
                        $data = [
                            'mobile'            =>  $item->mobile_phone,
                            'user_name'         =>  $item->user_name,
                            'idno'              =>  $item->idno."\t",
                            'isset_user_name'   =>  $userInfo->real_name,
                            'isset_idno'        =>  GibberishAESUtil::dec($userInfo->idno , Yii::app()->c->idno_key)."\t",
                            'isset_mobile'      =>  GibberishAESUtil::dec($userInfo->mobile , Yii::app()->c->idno_key)."\t",
                            'isset_user_id'     =>  $userInfo->id
                        ];
                        $out = iconv("UTF-8","gbk//IGNORE",implode(",",$data))."\n";
                        fwrite($tmp_file_log,$out);
                    }
                }
            }
       // }
        fclose($tmp_file_log);
    }

    /**
     * 录入数据处理银行id
     * @param $file_id
     * @return bool
     */
    public function actionDealBankId($file_id){

        $sql = "select * from offline_import_content where file_id = {$file_id} and status = 1 and  deal_status = 0 and bank_id = 0 and bank_number > 0 ";
        $content= OfflineImportContent::model()->findAllBySql($sql);
        if(empty($content)){
            return true;
        }
        $bankInfo = include APP_DIR . '/protected/config/bankList.php';
        $bankList = $bankInfo['bankDetail'];
        foreach ($content as $item){
            $bank_id = 0;
            $item->bank_number = str_replace(' ', '',  $item->bank_number);
            $res = $this->bankInfo($item->bank_number,$bankList);
            if($res){
                $bank_name = explode('-',$res)[0];

                foreach ($bankInfo['bankName'] as $name=>$id) {
                    if($name === $bank_name){
                        $bank_id = $id;
                        break;
                    }
                    $res = $this->getBankName($name,$bank_name);

                    if($res>=4){
                        $bank_id = $id;
                    }

                    if($res>=6){
                        $bank_id = $id;
                        break;
                    }
                }
                if($bank_id){
                    $item->bank_id = $bank_id;
                    $item->save();
                    continue;
                }
            }

            foreach ($bankInfo['bankName'] as $name=>$id) {

                if($item->bankzone===$name){
                    $bank_id = $id;
                    break;
                }

                $res = $this->getBankName($item->bankzone,$name);
                if($res>=4){
                    $bank_id = $id;
                }

                if($res>=6){
                    $bank_id = $id;
                    break;
                }
            }
            if( $bank_id ){
                $item->bank_id = $bank_id;
                $item->save();
                continue;
            }

        }
        return  true;
    }

    /**
     * 根据银行卡号获取银行名称
     * @param $card
     * @param $bankList
     * @return bool
     */
    private  function bankInfo($card,$bankList)
    {
        $card_8 = substr($card, 0, 8);
        if (isset($bankList[$card_8])) {
            return  $bankList[$card_8];
        }
        $card_6 = substr($card, 0, 6);
        if (isset($bankList[$card_6])) {
            return  $bankList[$card_6];
        }
        $card_5 = substr($card, 0, 5);
        if (isset($bankList[$card_5])) {
            return  $bankList[$card_5];
        }
        $card_4 = substr($card, 0, 4);
        if (isset($bankList[$card_4])) {
            return  $bankList[$card_4];
        }
        return  false;
    }

    /**
     * 获取两个字符串最长子串
     * @param $str1
     * @param $str2
     * @return bool|int
     */
    private function getBankName($str1,$str2){

        $arr1 = preg_split('/(?<!^)(?!$)/u', $str1 );

        $arr2 = preg_split('/(?<!^)(?!$)/u', $str2 );
        //计算字符串的长度
        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);
        //初始化相同字符串的长度
        $len = 0;
        //初始化相同字符串的起始位置
        $pos = -1;
        for ($i = 0; $i < $len1; $i++) {
            for ($j = 0; $j < $len2; $j++) {
                //找到首个相同的字符
                if ($arr1[$i] == $arr2[$j]) {
                    //判断后面的字符是否相同
                    for ($p = 0; (($i + $p) < $len1) &&
                    (($j + $p) < $len2) &&
                    ($arr1[$i + $p] == $arr2[$j + $p]) &&
                    ($arr1[$i + $p] <> ''); $p++);
                    if ($p > $len) {
                        $pos = $i;
                        $len = $p;
                    }
                }
            }
        }
        if ($pos == -1) {
            return false;
        } else {
            return  mb_strlen(mb_substr($str1, $pos, $len));
        }
    }
}
