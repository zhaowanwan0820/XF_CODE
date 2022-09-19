<?php
/**
  *
  * home/work/local/php/bin/php /home/work/itouzi.com/yii.itouzi.com/dashboard/protected/bin/yiic.php CcsCallrecord run
  * 获取通话录音 并上传录音和问卷调查到opmp
  * 每5分钟执行一次
**/
class CcsCallrecordCommand extends CConsoleCommand {

    public function actionRun(){
    	Yii::log ( "CcsCallrecordCommand RUN START !",'info');
		$this->getRecord();
    }
    
    /**
     * 获取
     * 新数据
     */
    public function getRecord(){
    	$time = time()-300;
    	$sql = "select call_id,type,category,status,app_id,order_status from ccs_call_record where result='' AND append=1 AND end_time>0 AND end_time<{$time}";
    	$info = Yii::app()->ccsdb->createCommand($sql)->queryAll();
    	if (empty($info)){
    		echo "Info Is Empty ! \r\n";
    		return false;
    	}
    	//设置通话记录
    	$this->setRecord($info);
    	//获取通话录音
    	$this->setRecordPath($info);
    }
    
    /**
     * 获取录音地址
     */
    public function setRecordPath($info){
    	foreach ($info as $value){
    		$data = array();
    		$data['call_id'] = $call_id = $value['call_id'];
    		$return_result = CallService::getInstance()->getDownRecord($data);
    		if( $return_result['code'] === 0){
    			$record_info = array();
    			$record_info['record_url'] = $record_url = $return_result['data']['path'];
    			$record_info['updatetime'] = time();
    			$res = Yii::app()->ccsdb->createCommand()->update('ccs_call_record',$record_info,'call_id=:call_id', array(':call_id'=>$call_id));
    			if(!$res){
    				echo "CcsCallrecordCommand record_url update error call_id: ".$call_id."\r\n";
    				Yii::log ( "CcsCallrecordCommand update call record_url call_id=".$call_id,CLogger::LEVEL_ERROR);
    			}else{
    				echo "CcsCallrecordCommand record_url update success call_id: ".$call_id."\r\n";
    				//根据条件发送给opmp录音和问卷调查信息
    				if(in_array($value['category'], array(1,2,3))){
    					if(in_array($value['status'], array(4,5,6,7,8,9))){
	    					$exam = array();
							if($value['category']==2){//冷静期后回访上传调查问卷
								$sql_answer = "select * from ccs_questionnaire where order_id={$value['app_id']}";
								$answer_info = Yii::app()->ccsdb->createCommand($sql_answer)->queryRow();
								if(empty($answer_info)){
									echo "Answer Info Is Empty ! \r\n";
									continue;
								}else {
									$exam['exam_result'] = json_encode(unserialize($answer_info['answers']));
								}
							}
							if(in_array($value['status'], array(4,6,9))){
								$exam['is_pass'] = 1;
							}elseif(in_array($value['status'], array(5,8))){
								$exam['is_pass'] = 2;
							}elseif($value['status']==7){
								$exam['is_pass'] = 3;
							}
							if($value['category']==1){
								$exam['approver_type'] = 1;
							}elseif($value['category']==2){
								$exam['approver_type'] = 3;
							}elseif($value['category']==3){
								$exam['approver_type'] = 6;
							}
							//$exam['approver_type'] = $value['category']==1 ? $value['category'] : $value['category']+1;
							$exam['audio_path'] = $record_url;
							$exam_result = CallService::getInstance()->uploadAudioExam($value['app_id'],$exam);
							if($exam_result['code'] === 0){
								echo "CcsCallrecordCommand uploadAudioExam success call_id: ".$call_id."\r\n";
							}else{
								echo "CcsCallrecordCommand uploadAudioExam fail call_id: ".$call_id."\r\n";
								Yii::log ( "CcsCallrecordCommand uploadAudioExam fail call_id=".$call_id.' info:'.print_r($exam_result,true),CLogger::LEVEL_ERROR);
							}
    					}
    				}
    			}
    		}else{
    			echo "CcsCallrecordCommand record_url update fail call_id: ".$call_id."\r\n";
    			Yii::log ( "CcsCallrecordCommand update call record_url call_id=".$call_id,CLogger::LEVEL_ERROR);
    		}
    	}
    }
    
    /**
     * 获取通话记录
     * 新数据
     */
    public function setRecord($info){
    	foreach ($info as $value){
    		$data = array();
    		$call_id = $value['call_id'];
    		echo 'call_id: '.$call_id."\r\n";
    		$data['call_type'] = 1;
    		$data['call_id'] = $call_id;
    		$return_result = CallService::getInstance()->getCallOutInfo($data);
    		echo " code: ".$return_result['code']."\r\n";
    		if($return_result['code']==0){
    			if( count($return_result['data'])>0 ){
    				$call_info = $return_result['data'][0];
    				$record_info['start_time'] = strtotime($call_info['start_time']);
    				$record_info['end_time'] = strtotime($call_info['end_time']);
    				$record_info['call_time'] = $record_info['end_time'] - $record_info['start_time'] ;
    				$record_info['talk_time'] = $call_info['conn_secs'];
    				if($value['type']==2){
    					$record_info['user_phone'] = $call_info['cus_phone'];
    				}
    				$record_info['ag_phone'] = $call_info['ag_phone'];
    				$record_info['ring_secs'] = $record_info['call_time'] - $record_info['talk_time'] ;
    				$record_info['updatetime'] = time();
    				//$record_info['ag_phone'] = $call_info['ag_phone'];
    				$record_info['result'] = $call_info['result'];
    				$record_info['endresult'] = $call_info['endresult'];
    				if($call_info['result']=='0'){
    					$record_info['status'] = '2';
    				}else {
    					$record_info['status'] = '3';
    				}
    				$res = Yii::app()->ccsdb->createCommand()->update('ccs_call_record',$record_info,'call_id=:call_id', array(':call_id'=>$call_id));
    				if(!$res){
    					echo "CcsCallrecordCommand update error call_id: ".$call_id."\r\n";
    					Yii::log ( "CcsCallrecordCommand update call record error, call_id=".$call_id.',msg:'.print_r($res,true),CLogger::LEVEL_ERROR);
    				}else{
    					echo "CcsCallrecordCommand update success call_id: ".$call_id."\r\n";
    				}
    			}else{
    				echo "CcsCallrecordCommand call_info is not exists call_id:".$call_id."\r\n";
    				$record = array();
    				$record['result'] = -1;
    				$record_info['updatetime'] = time();
    				Yii::app()->ccsdb->createCommand()->update('ccs_call_record',$record,'call_id=:call_id', array(':call_id'=>$call_id));
    				Yii::log ( "CcsCallrecordCommand {$call_id} info is not exists ",CLogger::LEVEL_ERROR);
    			}
    		}else{
    			Yii::log ( "CcsCallrecordCommand get {$call_id} info fail,errorinfo:".print_r($return_result,true),CLogger::LEVEL_ERROR);
    		}
    	}
	}
	

	public function actionCallIn($start = "",$days = 1)
	{
		$start_time = date("Y-m-d H:i:s",$start?strtotime($start):strtotime("-1 days midnight"));
		$end_time = date("Y-m-d H:i:s",strtotime("+$days days",strtotime($start_time)));
		$data["start_time"] = $start_time;
		$data["end_time"] = $end_time;
		$return_result = CallService::getInstance()->getCallOutInfo($data);
		foreach($return_result as $key => $value){
			echo $key.":".(is_array($value)?count($value):$value)."\n";
		}
		$error = 0;
		if($return_result["code"] == 0){
			$fileName = "/home/work/logs/CALLIN-".date("YmdHis").".csv";
			$content = "";
			$head = array_keys($return_result["data"][0]);
			$content .= implode(",",$head)."\n";
			foreach($return_result["data"] as $data){
				if($data["call_type"] == "呼入"){
					$call_record = CcsCallRecord::model()->findByAttributes(["call_id"=>$data["call_id"]]);
					if(empty($call_record)){
						$call_record = new CcsCallRecord();
					}
					$adminInfo = CrmAdmin::model()->findByAttributes(['ag_num1'=>$data['ag_num']]);
					if(empty($adminInfo)){
						$adminInfo = CrmAdmin::model()->findByAttributes(['ag_num2'=>$data['ag_num']]);
					}
					$call_record->admin_id = $adminInfo?$adminInfo->admin_id:0;
					$call_record->admin_name = $adminInfo?$adminInfo->admin_name:"unknow";
					$call_record->call_id = $data["call_id"];
					$call_record->status = 1;
					$call_record->type = 2;
					$call_record->ag_phone = $data["ag_phone"];
					$call_record->user_phone = $data["cus_phone"];
					$call_record->start_time = strtotime($data["start_time"]);
					$call_record->end_time = strtotime($data["end_time"]);
					$call_record->call_status = $data["conn_secs"] > 0 ? 2:3;
					$call_record->call_time = $data["all_secs"];
					$call_record->talk_time = $data["conn_secs"];
					$call_record->ring_secs = $data["ring_secs"];
					$call_record->endresult = $data["endresult"];
					
					$last = array_values(array_reverse($data))[0];
					$evaluate_result = explode("-",$last?:"");
					switch($evaluate_result[0]){
						case "解决":
							$handle ="handled";
							break;
						case "未解决":
							$handle ="unsolved";
							break;
						default:
							$handle ="unknow";
					}
					$statify =  "unknow";
					if(isset($evaluate_result[1])){
						switch($evaluate_result[1]){
							case "满意":
								$statify ="statify";
								break;
							case "不满意":
								$statify ="discontent";
								break;
							case "一般":
								$statify ="justok";
								break;
							default:
								$statify ="unknow";
						}
					}
					$call_record->result = $handle.",".$statify;
					$call_record->order_status = 0;
					$call_record->addtime = strtotime($data["start_time"]);
					$call_record->updatetime = strtotime($data["start_time"]);
					$res = $call_record->save();
					if(!$res){
						$error++;
						$content .= implode(",",$data)."\n";
					}
				}
			}
			echo "getCallInResult success . faild $error \n";
		}else{
			echo "getCallInResult error code:".$return_result["code"]." info:".$return_result["info"]."\n";
		}
		
	}
}
?>