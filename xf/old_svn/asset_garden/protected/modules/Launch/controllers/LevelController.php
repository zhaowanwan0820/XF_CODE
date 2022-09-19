<?php

class LevelController extends CommonController
{
    protected $logFile = 'ContractTaskHandleCommand';
    public $platform_id = '';
    public $platformUserId = '';
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 判断是否需要登录
     */
    public function __get($name)
    {
        //错误信息
        if($name == 'error_code_info'){
            return Yii::app()->c->errorcodeinfo;
        }
        //风险等级验证
        if($name == "checkUserRisk"){
            if(!empty($this->user_id)){
                $riskInfo = QuestionService::getInstance()->checkUserRisk($this->user_id);
                return $riskInfo['code'];
            }
        }
    }
    /**
     * 查询同意协议记录状态
     *
     */
    public function actionTransLook()
    {
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        $platform_id = $this->paramsvaild('platform_id', true);   //平台id
        $user = \Yii::app()->agdb->createCommand()
            ->select('agree_status')
            ->from('ag_user_platform')
            ->where("user_id = {$user_id} and platform_id = {$platform_id}")
            ->queryRow();
        if (!$user) {
            $ret = array(
                'agree_status' => 0,
            );
            $this->echoJson($ret, 0, '获取协议状态成功');
            // $this->echoJson('', 4009, $this->error_code_info['4009']);
        } else {
            $ret = array(
                'agree_status' => $user['agree_status'],
            );
            $this->echoJson($ret, 0, '获取协议状态成功');
        }
    }
    /**
     * 查询用户风险评级状态
     *
     */
    public function actionTransLookRisk()
    {
        //验证登录
        $user_id = $this->user_id;
        if (empty($user_id)) {
            $this->echoJson(array(), 4007, $this->error_code_info[4007]);
        }
        $userInfo = QuestionService::getInstance()->checkUserRisk($user_id);
        $risk_level = $userInfo['data']['level_name'];
        if($userInfo['code'] != 0){
            $risk_level = 0;
        }
        //查询问卷调查（债消市场、再投资）
        $agModel = Yii::app()->agdb;
        $reinvestment_status = 0;
        $debt_status = 0;
        $answerInfo = $agModel->createCommand("select DISTINCT aqq.type from ag_qnr_answer aqa left join ag_qnr_questionnaire aqq on aqa.qstn_id = aqq.id where aqa.user_id = {$user_id} and aqq.status = 1")->queryAll();
        if(!empty($answerInfo)){
            foreach($answerInfo as $key => $val){
                if($val['type'] == 2){
                    //2再投资问卷
                    $reinvestment_status = 1;
                }elseif($val['type'] == 3){
                    //3债消市场问卷
                    $debt_status = 1;
                }
            }
        }
        $ret = array(
            'user_id' => $user_id,
            'risk_level' => $risk_level,
            'level_id' => $userInfo['data']['level_id'],
            'reinvestment_status' => $reinvestment_status,
            'debt_status' => $debt_status,
        );
        $this->echoJson($ret, 0, '返回成功');
    }
    /**
     * echoJson
     * 输出json
     *
     * @param mixed $data
     * @param int $code 0:success
     * @access protected
     * @return void
     */
    protected function echoJson($data = array(), $code = 0, $info = "", $plain_flag = false)
    {
        if ($plain_flag) {
            if (strpos(isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '', 'application/json') !== false) {
                header('Content-type:text/plain; charset=utf-8');
            }
        } else {
            header("Content-type:application/json; charset=utf-8");
        }
        $data = ArrayUtil::getArray($data);
        $res["data"] = $data;
        $res['code'] = intval($code);
        $res['info'] = $info?:(Yii::app()->c->errorcodeinfo[$code]?:'');
        echo exit(json_encode($res));

    }

    /**
     * java数据解析接口
     */
    public function actionAnalysis()
    {
        $key = $this->paramsvaild('key', true);//redis中的key
        $type = $this->paramsvaild('type', true);//1:json格式化 2:序列化
        if(!in_array($type,[1,2])){
            $this->echoJson(array(), 2056, $this->error_code_info['2056']);
        }
        $data = Yii::app()->rcache->get($key);
        if(!$data){
            $this->echoJson(array(), 4002, $this->error_code_info['4002']);
        }
        if($type == 1){
            if(!ItzUtil::is_json($data)){
                $this->echoJson(array(), 4006, $this->error_code_info['4006']);
            }
            $data = json_decode($data,true);
        }elseif($type == 2){
            if(!ItzUtil::is_serialized($data)){
                $this->echoJson(array(), 2109, $this->error_code_info['2109']);
            }
            $data = unserialize($data);
        }
        $this->echoJson($data, 0, '返回成功');
    }
    /**
     * C1获取问卷接口
     *
     */
    public function actionGetQuestionnaire()
    {
        $type = $this->paramsvaild('type', true);//1风险评级问卷，2再投资问卷，3债消市场问卷
        $user_id = $this->user_id;
        $info = ['type' => $type, 'user_id' => $user_id];
        $result = QuestionService::getInstance()->GetQuestionnaire($info);
        if($result['code'] != 0){
            $this->echoJson([], $result['code'],$this->error_code_info[$result['code']]);
        }
        //添加埋点redis计数器
        if($type == 2 || $type == 3){
            Yii::app()->rcache->incr("ag_qnr_questionnaire_type_id_{$type}","1");
        }
        $this->echoJson($result['data'], 0, '返回成功');
    }
    /**
     * C1提交问卷接口
     */
    public function actionSendQuestionnaire()
    {
        $qstn_id = $this->paramsvaild('qstn_id', true);//问卷id
        $answerArr = $this->paramsvaild('answerArr', true);
        Yii::log("SendQuestionnaire: $answerArr");
        $answer_time = $this->paramsvaild('answer_time', true);//答题时间s（按秒计算）
        $user_id = $this->user_id;
        $info = array('qstn_id' => $qstn_id, 'answerArr' => $answerArr, 'answer_time' => $answer_time, 'user_id' => $user_id);
        Yii::app()->agdb->beginTransaction();
        try {
            $result = QuestionService::getInstance()->SendQuestionnaire($info);
            if($result['code'] != 0){
                $this->echoJson([], $result['code'],$this->error_code_info[$result['code']]);
            }
            Yii::app()->agdb->commit();
        }catch (Exception $e) {
            Yii::app()->agdb->rollback();
            $this->echoJson($e->getMessage(), 2089,"返回成功");
        }
        $this->echoJson($result['data'], 0, '返回成功');
    }
    /**
     * 验证参数
     * @param  $field 参数名称
     * @param  $default 默认值
     * auth hanzhaoxing
     * @return void
     */
    public function paramsvaild($field, $mustfill = 0, $default = '')
    {
        if (!Yii::app()->request->isPostRequest) {
            $this->echoJson([], 2074, $field . Yii::app()->c->data['errorcodeinfo']['2074']);
        }
        $data = Yii::app()->request->getParam($field);
        if ($mustfill === true) {
            if (empty($data) || $data === 0) {
                $this->echoJson([], 4001, $field . Yii::app()->c->data['errorcodeinfo']['4001']);
            }
        }
        if (!empty($default)) {
            $data = isset($data) ? $data : $default;
        }
        return $data;
    }
}