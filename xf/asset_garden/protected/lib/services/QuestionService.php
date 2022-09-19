<?php

/**
 *
 */
class QuestionService extends ItzInstanceService
{
    /**
     * @param $type 1风险评级问卷，2再投资问卷，3债消市场问卷
     * @param $info [
     * qstn_id 问卷id
     * answerArr 答案 [{"qst_id":1,"answer":2},{"qst_id":2,"answer":2}]
     * answer_time 答题时间s（按秒计算）
     * ]
     */
    public function GetQuestionnaire($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $type = $info['type'];
        $user_id = $info['user_id'];
        //验证登录
        if (empty($user_id)) {
            $return_result['code'] = 4007;
            return $return_result;
        }
        if(!in_array($type,[1,2,3]) || !is_numeric($type)){
            $return_result['code'] = 2056;
            return $return_result;
        }
        $agModel = Yii::app()->agdb;
        $ret = '';
        $condtion = [
            'order' => 'id desc',
            'condition' => 'type=:type and status=:status',
            'params' => [':type' => $type,':status' => 1],
        ];
        $AgQnrQuestionnaireInfo = AgQnrQuestionnaire::model()->find($condtion);
        if(empty($AgQnrQuestionnaireInfo)){
            $return_result['code'] = 4002;
            return $return_result;
        }
        $AgQnrQuestionnaireInfo = $AgQnrQuestionnaireInfo->attributes;
        $qnrCondition = [
            'condition' => 'qstn_id=:qstn_id and status=:status',
            'params' => [':qstn_id' => $AgQnrQuestionnaireInfo['id'],':status' => 1],
        ];
        $AgQnrQuestionInfo = AgQnrQuestion::model()->findAll($qnrCondition);
        if(empty($AgQnrQuestionInfo)){
            $return_result['code'] = 4002;
            return $return_result;
        }
        foreach($AgQnrQuestionInfo as $key => $value){
            $arrAq[] = $value->attributes;
        }
        $qst_ids = implode(',',ItzUtil::array_column($arrAq,'id'));
        $qnrOptionInfo = $agModel->createCommand("select * from ag_qnr_option where qst_id IN({$qst_ids}) and status = 1")->queryAll();
        //排序选项数据
        foreach($qnrOptionInfo as $k => $v){
            $qnrOptionSortInfo[$v['qst_id']][] = array(
                'qto_id' => $v['id'],
                'serial' => $v['serial'],
                'option' => $v['option'],
                'point'  => $v['point'],
            );
        }
        foreach($AgQnrQuestionInfo as $key => $val){
            $ret[] = array(
                'qstn_id' => $AgQnrQuestionnaireInfo['id'],//问卷id
                'qst_id' => $val->id,//题干id
                'question' => $val->question,//题干
                'type' => $val->type,//0:单选题,1:填空题,2:多选题
                'data' => $qnrOptionSortInfo[$val->id],//选项
            );
        }
        $return_result['data'] = $ret;
        return $return_result;
    }
    /**
     * C1提交问卷接口
     * @param $info [
     * qstn_id 问卷id
     * answerArr 答案 [{"qto_id":1,"answer":2},{"qto_id":2,"answer":2}]
     * answer_time 答题时间s（按秒计算）
     * ]
     * @return array
     */
    public function SendQuestionnaire($info)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        $qstn_id = $info['qstn_id'];
        $answerArr = $info['answerArr'];
        $answer_time = $info['answer_time'];
        $user_id = $info['user_id'];
        //验证登录
        if (empty($user_id)) {
            $return_result['code'] = 4007;
            return $return_result;
        }
        if (!is_numeric($qstn_id) || !is_numeric($answer_time)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        //验证answerArr数据
        if (!ItzUtil::checkJson($answerArr, 'qto_id') || !ItzUtil::checkJson($answerArr, 'qst_id') || empty($answerArr)) {
            $return_result['code'] = 2056;
            return $return_result;
        }
        $answerArr = json_decode($answerArr, true);
        $condtion = [
            'order' => 'id desc',
            'condition' => 'id=:id and status=:status',
            'params' => [':id' => $qstn_id, ':status' => 1],
        ];
        $agdbModel = Yii::app()->agdb;
        $AgQnrQuestionnaireInfo = AgQnrQuestionnaire::model()->find($condtion)->attributes;
        if (empty($AgQnrQuestionnaireInfo)) {
            $return_result['code'] = 2099;
            return $return_result;
        }
        //验证问卷是否限答一次
        $agQnrInfo = $agdbModel->createCommand("select count(*) as num,aqq.reply_num_type from ag_qnr_questionnaire aqq left join ag_investigation_answer aia on aqq.id = aia.qstn_id where aqq.id = $qstn_id and aqq.status = 1 and aia.user_id = $user_id")->queryRow();
        if($agQnrInfo['reply_num_type'] == 1){
            if($agQnrInfo['num'] > 0){
                $return_result['code'] = 2108;
                return $return_result;
            }
        }
        //验证提交题目与问卷类型
        $qstIds = ItzUtil::array_column($answerArr,'qst_id');
        $questionInfo = $agdbModel->createCommand("select * from ag_qnr_question where qstn_id  = $qstn_id and status = 1")->queryAll();
        if(empty($questionInfo)){
            $return_result['code'] = 2104;
            return $return_result;
        }

        $questionIds = ItzUtil::array_column($questionInfo,'id');
        $diffArr = array_diff($qstIds,$questionIds);
        if(!empty($diffArr)){
            $return_result['code'] = 2105;
            return $return_result;
        }
        //过滤掉填空题的答案
        foreach($answerArr as $key => $val){
            if(is_numeric($val['qto_id'])){
                $answerIdsArr[] = $val;
            }
        }
        $qtoIds = ItzUtil::array_column($answerIdsArr,'qto_id');
        $qto_ids = implode(",",$qtoIds);
        //计算C1用户答题分数入库
        $total_score = $agdbModel->createCommand("select sum(point) from ag_qnr_option where id in($qto_ids)")->queryScalar();
        $condtion = [
            'condition' => 'end_score>=:end_score',
            'params' => [':end_score' => $total_score],
        ];
        $investigationLevelInfo = AgInvestigationLevel::model()->find($condtion)->attributes;
        if(empty($investigationLevelInfo)){
            $return_result['code'] = 2110;
            return $return_result;
        }
        $insertData = array(
            "user_id" => $user_id,
            "qstn_id" => $qstn_id,
            "total_score" => $total_score,
            "level_name" => $investigationLevelInfo['level_name'],
            "total_time" => $answer_time,
            "level_id" => $investigationLevelInfo['level_id'],
            "add_time" => time(),
        );

        //添加到调查问卷回答表
        $sql = ItzUtil::get_insert_db_sql('ag_investigation_answer', $insertData);

        $ret = $agdbModel->createCommand($sql)->execute();
        if (!$ret) {//添加失败
            $return_result['code'] = 2100;
            return $return_result;
        }
        $answer_id = $agdbModel->getLastInsertID();
        //验证提交答案与题目匹配
        $option = $agdbModel->createCommand("SELECT aqo.id as id,aqo.type,aqq.id as aqrn_id from ag_qnr_question aqq LEFT JOIN ag_qnr_option aqo on aqq.id = aqo.qst_id  where aqq.qstn_id = $qstn_id and aqq.status = 1 AND aqo.status = 1")->queryAll();
        if(empty($option)){
            $return_result['code'] = 2106;
            return $return_result;
        }
        $optiontypeArr = ItzUtil::array_column($option,'type','aqrn_id');
        foreach ($answerArr as $key => $value) {
            if($optiontypeArr[$value['qst_id']] != 2){
                //如果是非填空题
                $total = $agdbModel->createCommand("select count(*) from ag_qnr_question aqq
                    LEFT JOIN ag_qnr_option aqo ON aqq.id = aqo.qst_id where aqq.id = {$value['qst_id']}
                    and aqo.id = {$value['qto_id']} and aqq.status = 1 and aqo.status = 1")->queryScalar();
                if($total == 0){
                    $return_result['code'] = 2107;
                    return $return_result;
                }
            }
            $user_write = '';
            $answer = $value['qto_id'];
            //如果是填空题
            if($optiontypeArr[$value['qst_id']] == 2){
                $user_write = $value['qto_id'];
                $answer = 0;
            }
            $anInsertData[] = array(
                'user_id' => $user_id,
                'qstn_id' => $qstn_id,
                'qst_id' => $value['qst_id'],
                'answer' => $answer,
                'user_write' => $user_write,
                'answer_id' => $answer_id,
                'addtime' => time(),
            );
        }

        $sqlaqa = ItzUtil::get_all_insert_sql('ag_qnr_answer', array_keys($anInsertData[0]), $anInsertData);
        $res = $agdbModel->createCommand($sqlaqa)->execute();
        if (!$res) {//添加失败
            $return_result['code'] = 2100;
            return $return_result;
        }
        $returnData = array('level_id' => 0, 'level_name' => '');
        //如果是风险评测答卷更新用户表中风险等级
        if($AgQnrQuestionnaireInfo['type'] == 1){
            //判断用户是否进行过风险评测
            $condtion = [
                'condition' => 'id=:id',
                'params' => [':id' => $user_id],
            ];
            $userLevelInfo = AgUser::model()->find($condtion)->attributes;
            if(empty($userLevelInfo)){
                $return_result['code'] = 1027;
                return $return_result;
            }
            //用户第二次进行风险评测如果未更新level_id，直接返回数据
            $returnData = array('level_id' => $investigationLevelInfo['level_id'], 'level_name' => $investigationLevelInfo['level_name']);
            if(!empty($userLevelInfo['level_id'])){
                AgUser::model()->updateByPk($user_id, ['level_id' => $investigationLevelInfo['level_id']]);
                $return_result['data'] = $returnData;
                return $return_result;
            }
            $userInfo = AgUser::model()->updateByPk($user_id, ['level_id' => $investigationLevelInfo['level_id']]);
            if(!$userInfo){
                $return_result['code'] = 2101;
                return $return_result;
            }

        }
        $return_result['data'] = $returnData;
        return $return_result;
    }

    /**
     * 风险平层验证
     * @param $user_id
     * @param $type 1:单层验证是否风险评测2：双层验证是否C5积极型进行认购
     * @return array
     */
    public function checkUserRisk($user_id,$type = 1)
    {
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );
        if(empty($user_id)){
            $return_result['code'] = 2057;
            return $return_result;
        }
        if(!is_numeric($user_id) || !in_array($type,[1,2])){
            $return_result['code'] = 2056;
            return $return_result;
        }
        $condtion = [
            'condition' => 'id=:id',
            'params' => [':id' => $user_id],
        ];
        $userInfo = AgUser::model()->find($condtion)->attributes;
        if(empty($userInfo)){
            $return_result['code'] = 1027;
            return $return_result;
        }
        if($userInfo['level_id'] == 0){
            $return_result['code'] = 2102;
            return $return_result;
        }
        //验证是否可认购
        $levelInfo = Yii::app()->c->itouzi['risk_level'];
        if($type == 2){
            $level = ConfUtil::get('risk_level');
            $lv = !empty($level) ? $level : 5;
            if($userInfo['level_id'] != $lv){
                $return_result['code'] = 2103;
                $return_result['info'] = "您的风险承受能力等级为：{$levelInfo[$userInfo['level_id']]}，当前不符合债权认购资质（{$levelInfo[$lv]}），请重新测评";
                return $return_result;
            }
        }
        $return_result['data'] = ['level_id' => $userInfo['level_id'],"level_name" => $levelInfo[$userInfo['level_id']]];
        return $return_result;
    }
}