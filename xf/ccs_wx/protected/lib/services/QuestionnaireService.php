<?php
/**
 * @file QuestionnaireService.php
 * @author (zhanglei@itouzi.com)
 * @date 2017/02/10
 * 问卷调查相关
 **/
class QuestionnaireService extends ItzInstanceService {
	
	public $_vcc_host = ''; //opmp对应url
	
    public function __construct( ){
        parent::__construct();
        $this->_vcc_host = Yii::app()->c->opmpUrl;
    }
    
    /**
     * 问卷调查详情
     */
    public function answerDetails($order_id=0){
    	Yii::log ( __FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$returnResult = array(
    		'code' => '', 'info' => '', 'data' => array()
    	);
    	$order_id = intval($order_id);
    	if(empty($order_id)){
    		$returnResult['code'] = 1003;
    		$returnResult['info'] = '缺少参数';
    		return $returnResult;
    	}
    	$info = $this->getInfoByOrderId($order_id);
    	if($info){
    		$returnResult['code'] = 0;
    		$returnResult['info'] = 'success';
    		$returnResult['data'] = unserialize($info['answers']);
    	}else{
    		$returnResult['code'] = 100;
    		$returnResult['info'] = '数据不存在';
    	}
    	return $returnResult;
    }
    
    /**
     * 设置问卷调查答案
     * @param unknown $admin_id
     * @param unknown $data
     */
    public function setAnswer($admin_id,$data=array()){
    	Yii::log ( __FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$returnResult = array(
    		'code' => '', 'info' => '', 'data' => array()
    	);
    	$admin_id = intval($admin_id);
    	$order_id = isset($data['orderid']) ? intval($data['orderid']) : '';
    	$order_status = isset($data['order_status']) ? intval($data['order_status']) : '';
    	if(empty($admin_id) || empty($order_status) || empty($order_id) ){
    		$returnResult['code'] = 1003;
    		$returnResult['info'] = '缺少参数';
    		return $returnResult;
    	}
    	$params = array();
    	
    	$params['order_id'] = $order_id;
    	$params['order_status'] = $order_status;
    	$params['admin_id'] = $admin_id;
    	
    	$params['answer1'] = $answer['answer1'] = intval($data['answer1']);
    	$params['answer2'] = $answer['answer2'] = intval($data['answer2']);
    	$params['answer3'] = $answer['answer3'] = intval($data['answer3']);
    	$params['answer4'] = $answer['answer4'] = intval($data['answer4']);
    	$params['answer5'] = $answer['answer5'] = intval($data['answer5']);
    	$params['answer6'] = $answer['answer6'] = intval($data['answer6']);
    	$params['answer7'] = $answer['answer7'] = intval($data['answer7']);
    	$params['answer8'] = $answer['answer8'] = intval($data['answer8']);
    	$params['answer9'] = $answer['answer9'] = intval($data['answer9']);
    	$params['answer10'] = $answer['answer10'] = intval($data['answer10']);
    	
    	foreach ($answer as $k=>$val){
    		if(empty($val)){
    			$returnResult['code'] = 1003;
    			$returnResult['info'] = '缺少参数';
    			return $returnResult;
    		}
    	}
    	$params['answers'] = serialize($answer);
    	$params['addtime'] = time();
    	$params['addip'] = FunctionUtil::ip_address();
    	$res = $this->addQuestion($params);
    	if($res){
    		$returnResult['code'] = 0;
    		$returnResult['info'] = 'Success';
    	}else{
    		$returnResult['code'] = 100;
    		$returnResult['info'] = '添加失败';
    	}
    	return $returnResult;
    }
    
    /**
     * 获取问卷调查数据
     * @param number $order_id
     * @return boolean
     */
    public function getInfoByOrderId($order_id=0){
    	Yii::log (__FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$order_id = intval($order_id);
    	if(empty($order_id)){
    		return false;
    	}
    	$sql = "select * from ccs_questionnaire where order_id={$order_id}";
    	return Yii::app()->ccsdb->createCommand($sql)->queryRow();
    }
    
    /**
     * insert操作
     * @param array $data
     * @return boolean
     */
    public function addQuestion($data){
    	Yii::log (__FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$model = new CcsQuestionnaire();
    	foreach($data as $key=>$value){
    		$model->$key = $value;
    	}
    	if($model->save()==false){
    		Yii::log("ccs_questionnaire_model error: ".print_r($model->getErrors(),true),"error");
    		return false;
    	}else{
    		return true;
    	}
    }
    
}


