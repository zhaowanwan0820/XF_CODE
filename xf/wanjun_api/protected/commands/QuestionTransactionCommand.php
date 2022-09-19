<?php

/**
 * 问卷调查统计脚本2再投资问卷，3债消市场问卷
 * 每30分钟处理一次
 * Class QuestionTransactionCommand
 */
class QuestionTransactionCommand extends CConsoleCommand {
	public $fnLock_pre = '/tmp/QuestionTrans';	//项目ID文件锁的前缀
	public $fnLock_tenderid = '';//文件执行id
	public $alarm_content = '';//报警内容
	/**
	 * 问卷调查统计2再投资问卷，3债消市场问卷
	 * @return bool
	 */
	public function actionRun(){
        try {
            //添加文件锁
            $fpLock = $this->enterTenderIdFnLock(1);
            $this->echoLog("QuestionTransaction run start");
            $typeArr = [0 => '单选题',1 => '填空题' ,2 => '多选题'];
            //查询题库中类型：2再投资问卷，3债消市场问卷
            $agModel = Yii::app()->agdb;
            //统计表获取有效人数
            $peopleInfo = $agModel->createCommand("select count(*) from ag_investigation_answer aia LEFT JOIN ag_qnr_questionnaire aqq ON aia.qstn_id = aqq.id where aqq.type IN(2,3) AND aqq.status = 1;")->queryScalar();
            if(empty($peopleInfo)){
                $this->echoLog("QuestionTransactionRun: ag_investigation_answer is empty");
                return false;
            }
            //获取有效题干
            $sql = "SELECT aqt.id,aqt.question,aqt.type,aqo.id as qto_id,aqo.option,aqq.type as type_id from ag_qnr_question aqt
                    LEFT JOIN ag_qnr_questionnaire aqq ON aqt.qstn_id = aqq.id
                    LEFT JOIN ag_qnr_option aqo ON aqo.qst_id = aqt.id
                    where aqq.type IN(2,3) AND aqt.status = 1 AND aqq.status = 1 AND aqo.status = 1";
            $qnrQuestion = $agModel->createCommand($sql)->queryAll();
            if(empty($qnrQuestion)){
                $this->echoLog("QuestionTransactionRun: ag_qnr_questionnaire is empty");
                return false;
            }
            //拼接数据
            foreach($qnrQuestion as $key => $val){
                $qnrQuestionQto[$val['id']][] = array(
                    'qto_id' => $val['qto_id'],
                    'option' => $val['option'],
                    'subtotal' => $this->subtotal($val['qto_id'],$val['id']),//小计
                    'proportion' => bcdiv($this->subtotal($val['qto_id'],$val['id']),$peopleInfo,2),//比例
                );
            }
            $qnrQuestionQt = ItzUtil::assoc_unique($qnrQuestion,"id");
            foreach($qnrQuestionQt as $key => $value){
                $retQnrInfo[$value['type_id']][] = array(
                    "id" => $value['id'],
                    "question" => $value['question'],//题干
                    "type" => $typeArr[$value['type']],//选择题、填空、多选
                    "people" => $peopleInfo,//有效答题人数
                    "data" => $qnrQuestionQto[$value['id']],
                );
            }
            //存redis
            if(!empty($retQnrInfo)){
                $redisData = Yii::app()->rcache->set("ag_qnr_questionnaire_proportion",json_encode($retQnrInfo),86400);
                if(!$redisData){
                    $this->echoLog("QuestionTransactionRun end, redis set fail");
                }
                $this->echoLog("QuestionTransactionRun end, success");
            }
            //释放文件锁
            $this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
        } catch (Exception $e) {
            self::echoLog("QuestionTransactionRun Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
	}

    /**
     * 小计统计
     * @param $qto_id
     * @param $qst_id
     * @return mixed
     */
    public function subtotal($qto_id, $qst_id)
    {
        $agModel = Yii::app()->agdb;
        $answerCount = $agModel->createCommand("select count(*) num from ag_qnr_answer where answer = $qto_id and qst_id = $qst_id")->queryScalar();
        return $answerCount;
    }
	/**
	 * 日志记录
	 * @param $yiilog
	 * @param string $level
	 */
	public function echoLog($yiilog, $level = "info") {
		echo date('Y-m-d H:i:s ')." ".microtime()." agTransferDebt {$yiilog} \n";
		$this->alarm_content .= $yiilog."<br/>";
		if($level == 'email') {
			$level = "error";
			$this->is_email = true;
		}
		Yii::log("transferDebt: {$yiilog}", $level, 'agTransferDebt');
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
}
