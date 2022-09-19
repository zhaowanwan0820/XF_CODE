<?php
/**
 * @file ActivityService.php
 * @author (chentengfei@itouzi.com)
 * @date 2018/2/28
 * css 活动相关 service
 **/

class QuestionService extends  ItzInstanceService {
    
	public function __construct(  )
    {
        parent::__construct();
    }
    
    public $question_tags = [
        1 => "预约",
        2 => "拒接",
        3 => "未完成",
        4 => "暂忙",
        5 => "流失",
        6 => "已完成",
        7 => "担心安全",
        8 => "考虑",
        9 => "期望",
        10 => "成功投资",
    ];

    public $question_status = [
        0 => "未进行",
        1 => "已完成",
        2 => "未完成",
    ];


    //获取标签结果
    public function getTagList($name)
    {
        $result = ["code"=>1,"info"=>"获取失败！","data"=>[]];
        
        if($name && $this->checkSqlParam($name)){
            $sql = "SELECT id,`name` from qnr_question_tag where `name` like '%{$name}%' and pid <> 0 order by addtime desc limit 10";
        } else {
            $sql = "SELECT id,`name` from qnr_question_tag where  pid <> 0 order by addtime desc limit 10";
        }
        $tags = Yii::app()->dwdb->createCommand($sql)->queryAll();
        if($tags){
            $result['code'] = 0;
            $result['info'] = '获取成功！';
            $result['data']['listTotal'] =  count($tags);
            $result['data']['listInfo'] =  array_values($tags);
        }
        return $result;
    }

    public function checkSqlParam($str)
    {
        $filter_str = "'|and|exec|union|create|insert|select|delete|update|count|*|%|chr|mid|master|truncate|char|declare|xp_|or|--|+";
        $filter = explode("|",$filter_str);
        foreach($filter as $f) {
            if(stripos($str,$f) !== false){
                return false;
            }
        }
        return true;
    }

    //获取用户问券列表
    public function getMemberList($qstn_id,$page = 1,$limit = 10,$tag_id = "",$status = "",$user_id = "",$admin_id = 0)
    {
        
        $result = ["code"=>1,"info"=>"获取失败！","data"=>[]];
        if(!is_numeric($qstn_id) || !is_numeric($page) || !is_numeric($limit) || $limit<1 || $page < 1) {
            $result['info'] = "参数错误！";
            return $result;
        }
        //额外条件
        $search_param = ['activity_id'=>0]; 
        if($user_id && is_numeric($user_id)){
            $search_param['user_id'] = $user_id;
        }
        if($status && (is_numeric($status) || is_array($status))){
            $search_param['status'] = $status;
        }
        $criteria = new CDbCriteria;  
        if($tag_id && is_numeric($tag_id)){
            $criteria->addCondition("FIND_IN_SET({$tag_id},tag)");
        }
        
        if($admin_id && is_numeric($admin_id)){
            $criteria->addCondition(" (admin_id = 0 or admin_id = {$admin_id}) ");
        }
        $count = CcsActivityMember::model()->countByAttributes($search_param,$criteria);        
        
        if(empty($count)) {
            $result['info'] = "没有相关数据！";
            return $result;
        }
        if(($page-1)*$limit > $count) {
            $result['info'] = "页数错误！";
            return $result;
        }
        $criteria->offset = ($page-1)*$limit;
        $criteria->limit = $limit;
        $criteria->order = "updatetime desc,addtime desc";
        $members = CcsActivityMember::model()->findAllByAttributes($search_param,$criteria);

        //问题总数
        $questionSql = "SELECT count(*) as c  FROM qnr_question where qstn_id = :qstn_id and `status` = 1";
        $question = Yii::app()->dwdb->createCommand($questionSql)->bindParam(":qstn_id",$qstn_id,PDO::PARAM_INT)->queryScalar();        
        
        $list = [];
        foreach($members as $member) {
            $l = [
                "user_id" => $member->user_id,
                "question_status" => $member->status,
                "question_status_tips" => $this->question_status[$member->status],
                "question_time" => $member->updatetime?date("Y-m-d H:i:s",$member->updatetime):"-",
                "tags" => [],
                "question_answered" => "0",
                "question_total" => $question,
                "in_my_sea" => $member->admin_id?1:0,
            ];
            //获取用户所有标签
            foreach(explode(",",$member->tag) as $t) {
                if($this->question_tags[$t]){
                    $l['tags'][] = $this->question_tags[$t];
                }
            }
            //获取用户答题数量
            $answerSql = "SELECT count(distinct qst_id) as answers FROM qnr_answer where user_id = :user_id and qstn_id = :qstn_id";
            $answer = Yii::app()->dwdb->createCommand($answerSql)->bindParam(":user_id",$member->user_id,PDO::PARAM_INT)->bindParam(":qstn_id",$qstn_id,PDO::PARAM_INT)->queryScalar();
            if($answer){
                $l['question_answered'] = $answer;
            }
            $list[] = $l;
        }
        $result['code'] = 0;
        $result['info'] = "获取成功！";
        $result['data']['listTotal'] =  intval($count);
        $result['data']['listInfo'] =  array_values($list);
        return $result;
    }

    //获取用户信息 
    public function getMemberInfo($user_id,$qstn_id,$admin_id=0,$is_super=false)
    {
        $result = ["code"=>1,"info"=>"获取失败！","data"=>[]];
        if(!empty($user_id) && is_numeric($user_id)){
            $user = User::model()->findByPk($user_id);
            if(empty($user)){
                $result["info"] = "用户不存在！";
                return $result;
            }
            $data = [
                'realname' => mb_substr($user->realname,0,1,'utf-8')."**",
                'reg_time' => date("Y-m-d H:i:s",$user->addtime),
                'xw_time' => "-",
                'sex' => $user->sex==1?"男":($user->sex==2?"女":"未知"),
                'max_vip' =>$user->user_grade_code,
                'max_invest' => 0,
                'last_tender_time' => '-',
                'last_remit_time' => '=',
                'money' => 0,
                'credit' => 0,
                'coin' => 0,
                'tags' => [],
                'question_status' => 0,
                "question_status_tips" => "",
                'extra' => "",
                'in_my_sea' =>0,
            ];
            $creditSql = "select value from dw_credit where user_id = :user_id";
            $credit = Yii::app()->dwdb->createCommand($creditSql)->bindParam(":user_id",$user_id,PDO::PARAM_INT)->queryScalar();
            $data['credit'] = $credit?:0;
            $xwopenSql = "select create_time from itz_open_account_record where user_id = :user_id and status = 1";
            $xw_open_time = Yii::app()->dwdb->createCommand($xwopenSql)->bindParam(":user_id",$user_id,PDO::PARAM_INT)->queryScalar();
            $data['xw_time'] = $xw_open_time?date("Y-m-d H:i:s",$xw_open_time):'-';
            $ccsMember = CcsActivityMember::model()->findByAttributes(['user_id'=>$user_id,'activity_id'=>0]);
            if(empty($ccsMember)){
                $res = ActivityService::getInstance()->addActivityMember(0,['user_id'=>$user_id]);
                if($res){
                    $ccsMember = CcsActivityMember::model()->findByAttributes(['user_id'=>$user_id,'activity_id'=>0]);
                }else{
                    return $result;
                }
            }
            if(!$is_super && $ccsMember->admin_id != $admin_id && $ccsMember->admin_id != 0){
                $result["info"] = "该用户已进入其它客维私海，您无法查看。";
                return $result;
            }
            if($admin_id && $ccsMember->admin_id == $admin_id){
                $data['in_my_sea'] = 1;
            }
            $data["question_status"] = $ccsMember->status;
            $data["question_status_tips"] = $this->question_status[$ccsMember->status];
            
            $data['extra'] = $ccsMember?$ccsMember->extra:"";
            $data['max_vip'] = $ccsMember&&$ccsMember->max_vip>$user->user_grade_code?$ccsMember->max_vip:$user->user_grade_code;
            $data['max_invest'] = $ccsMember?$ccsMember->max_invest:0;
            $data['last_tender_time'] = $ccsMember->last_tender_time?date("Y-m-d H:i:s",$ccsMember->last_tender_time):'-';
            $accountSql = "select use_money from dw_account where user_id = :user_id";
            $account = Yii::app()->dwdb->createCommand($accountSql)->bindParam(":user_id",$user_id,PDO::PARAM_INT)->queryScalar();
            $data['money'] = $account?:0;
            
            $remitSql = "select repay_yestime from dw_borrow_collection where user_id = :user_id and status = 1 order by repay_yestime desc limit 1";
            $remit = Yii::app()->dwdb->createCommand($remitSql)->bindParam(":user_id",$user_id,PDO::PARAM_INT)->queryScalar();
            $data['last_remit_time'] = $remit?date("Y-m-d H:i:s",$remit):'-';
            $coinSql = "select extcredits2 from itz_common_member_count where uid = :uid";
            $coin = Yii::app()->bbs->createCommand($coinSql)->bindParam(":uid",$user->ucenter_uid,PDO::PARAM_INT)->queryScalar();
            $data['coin'] = $coin?:0;
            foreach(explode(",",$ccsMember->tag) as $t){
                if($t && $this->question_tags[$t])
                    $data['tags'][] = $this->question_tags[$t]; 
            }
           
            //回答状态
            $sql = "SELECT count(distinct a.qst_id) as answered FROM qnr_answer a left join qnr_question q on q.id = a.qst_id and q.status = 1 where a.qstn_id = :qstn_id and a.user_id = :user_id";
            $answer = Yii::app()->dwdb->createCommand($sql)->bindParam(":user_id",$user_id,PDO::PARAM_INT)->bindParam(":qstn_id",$qstn_id,PDO::PARAM_INT)->queryScalar();
            $qnSQL = "SELECT count(*) as c  FROM qnr_question where qstn_id = :qstn_id and `status` = 1";
            $questions = Yii::app()->dwdb->createCommand($qnSQL)->bindParam(":qstn_id",$qstn_id,PDO::PARAM_INT)->queryScalar();
            //0 未回答，1 已完成 2 回答未完成。
            $data['question_status'] = $questions<=$answer?1:$answer?2:0;
            $result['data'] = $data;
            $result["info"] = "获取成功！";
            $result['code'] = 0;
        }
        return $result;
    }

    //把用户加入我的私海
    public function addToMySea($user_id,$admin_id) 
    {
        $result = ["code"=>1,"info"=>"操作失败！","data"=>[]];
        if(empty($user_id) || !is_numeric($user_id) || empty($admin_id) || !is_numeric($admin_id)){
            return $result;
        }
        $ccsMember = CcsActivityMember::model()->findByAttributes(['user_id'=>$user_id,'activity_id'=>0]);
        if(empty($ccsMember)){
            $result["info"] = "无法操作！";
            return $result;
        }
        if($ccsMember->admin_id != $admin_id && $ccsMember->admin_id != 0){
            $result["info"] = "该用户已进入其它客维私海，无法操作！";
            return $result;
        }
        $ccsMember->admin_id = $admin_id;
        $ccsMember->updatetime = time();
        $res = $ccsMember->save();
        if($res){
            $result["info"] = "操作成功！";
            $result["code"] = 0;
        }
        return $result;
    }

    //把用户从我的私海释放
    public function freeFromMySea($user_id,$admin_id) 
    {
        $result = ["code"=>1,"info"=>"操作失败！","data"=>[]];
        if(empty($user_id) || !is_numeric($user_id) || empty($admin_id) || !is_numeric($admin_id)){
            return $result;
        }
        $ccsMember = CcsActivityMember::model()->findByAttributes(['user_id'=>$user_id,'activity_id'=>0]);
        if(empty($ccsMember)){
            $result["info"] = "无法操作！";
            return $result;
        }
        if($ccsMember->admin_id != $admin_id && $ccsMember->admin_id != 0){
            $result["info"] = "该用户不在你的私海里，无法操作！";
            return $result;
        }
        $ccsMember->admin_id = 0;
        $ccsMember->updatetime = time();
        $res = $ccsMember->save();
        if($res){
            $result["info"] = "操作成功！";
            $result["code"] = 0;
        }
        return $result;
    }

    public function getQuestions($qstn_id,$user_id = 0)
    {
        $result = ["code"=>1,"info"=>"获取失败！","data"=>[]];
        if(!is_numeric($qstn_id)) {
            return $result;
        }
        $questionSQL = "SELECT id as question_id,question,`type`,`serial` from qnr_question where qstn_id = :qstn_id and status = 1 order by `sort`";
        $questions = Yii::app()->dwdb->createCommand($questionSQL)->bindParam(":qstn_id",$qstn_id,PDO::PARAM_INT)->queryAll();
        if(empty($questions)){
            return $result;
        }
        $all_options = [];
        foreach($questions as $key=>$question) {
            $optionSql = "SELECT o.id as option_id,o.`type`,o.`serial`,o.`option`,t.name as tag from qnr_option o left join qnr_question_tag t on t.id=o.tag_id  where qst_id = :qst_id and status = 1 order by `serial`";
            $options = Yii::app()->dwdb->createCommand($optionSql)->bindParam(":qst_id",$question['question_id'],PDO::PARAM_INT)->queryAll();
            $all_options = array_merge($options?:[],$all_options);
        }
        $answers = [];
        if($user_id && is_numeric($user_id)) {
            $answerSQL = "SELECT qsto_id as option_id,qst_id as question_id,user_write from qnr_answer where qstn_id = :qstn_id and user_id = :user_id";
            $answers = Yii::app()->dwdb->createCommand($answerSQL)->bindParam(":user_id",$user_id,PDO::PARAM_INT)->bindParam(":qstn_id",$qstn_id,PDO::PARAM_INT)->queryAll();
        }
        foreach($questions as $key => $question){
            $questions[$key]['status'] = 0;
            $questions[$key]['options'] = "";
            foreach($answers as $answer){
                if($answer["question_id"] == $question["question_id"]) {
                    if($answer['user_write']){
                        $questions[$key]['options'] .= $answer['user_write']." ";
                        $questions[$key]['status'] = 1;
                    }else{
                        foreach($all_options as $key2 => $option){
                            if($answer['option_id'] == $option['option_id']){
                                $questions[$key]['options'] .= $option['option']." ";
                                $questions[$key]['status'] = 1;
                            }
                        }
                    }
                }
            }
        }
        if($question){
            $result['data'] = $questions;
            $result["info"] = "获取成功！";
            $result['code'] = 0;
        }
        return $result;
    }


    public function answer($user_id,$status,$tags,$extra="",$admin_id = 0)
    {
        $result = ["code"=>1,"info"=>"回答失败！","data"=>[]];
        if(!is_numeric($user_id) || !is_numeric($status) || !is_numeric($admin_id)){
            return $result;
        }
        $ccsMember = CcsActivityMember::model()->findByAttributes(['user_id'=>$user_id,'activity_id'=>0]);
        if(empty($ccsMember)){
            $result["info"] = "无法编辑！";
            return $result;
        }
        if(!$is_super && $ccsMember->admin_id != $admin_id && $ccsMember->admin_id != 0){
            $result["info"] = "该用户已进入其它客维私海，您无法操作。";
            return $result;
        }
        $ccsMember->status = $status;
        if($tags){
            $ccsMember->tag = implode(",",$tags);
        }else{
            $ccsMember->tag = "";
        }
        $ccsMember->admin_id = $admin_id;
        $ccsMember->extra = $extra;
        $ccsMember->updatetime = time();
        $res = $ccsMember->save();
        if($res){
            $result['data'] = [];
            $result["info"] = "提交成功！";
            $result['code'] = 0;
        }
        return $result;
    }
    
    //获取用户基本信息
    private function getMemberBaseDate($user_ids,$result)
    {
        $users = User::model()->findAllByPk($user_ids);
        foreach($users as $user){
            $result[$user->user_id] = [
                'user_id' => $user->user_id,
                'reg_time' => date("Y-m-d H:i:s",$user->addtime),
                'xw_time' => "-",
                'max_vip' =>$user->user_grade_code,
                'max_invest' => 0,
                'last_tender_time' => '-',
            ];
        }
        $xwopenSql = "select user_id,create_time from itz_open_account_record where user_id in (".implode(",",$user_ids).") and status = 1";
        $xw_opens = Yii::app()->dwdb->createCommand($xwopenSql)->queryAll();
        foreach($xw_opens as $xw_open) {
            if($result[$xw_open['user_id']]){
                $result[$xw_open['user_id']]['xw_time'] = date("Y-m-d H:i:s",$xw_open['create_time']);
            }
        }
        $ccsMembers = CcsActivityMember::model()->findAllByAttributes(['user_id'=>$user_ids,'activity_id'=>0]);
        foreach($ccsMembers as $ccsMember){
            if(isset($result[$ccsMember->user_id])){
                $result[$ccsMember->user_id]['max_invest'] = $ccsMember->max_invest;
                $result[$ccsMember->user_id]['last_tender_time'] = date("Y-m-d H:i:s",$ccsMember->last_tender_time);
                $result[$ccsMember->user_id]['max_vip'] = $ccsMember->max_vip;
            }
        }
        return $result;
    }

    //导出用户问券信息
    public function exportMemberQuestionnaireData($qstn_id)
    {
        $result = ["code"=>1,"info"=>"回答失败！","data"=>[]];
        $has_more = true;
        $page = 1;
        $data_all = [];
        $questionSQL = "SELECT id as question_id,question,`type`,`serial` from qnr_question where qstn_id = :qstn_id and status = 1 order by `sort`";
        $questions = Yii::app()->dwdb->createCommand($questionSQL)->bindParam(":qstn_id",$qstn_id,PDO::PARAM_INT)->queryAll();
        $all_options = [];
        foreach($questions as $question){
            $optionSql = "SELECT o.id as option_id,o.`type`,o.`serial`,o.`option`,t.name as tag from qnr_option o left join qnr_question_tag t on t.id=o.tag_id  where qst_id = :qst_id and status = 1 order by `serial`";
            $options = Yii::app()->dwdb->createCommand($optionSql)->bindParam(":qst_id",$question['question_id'],PDO::PARAM_INT)->queryAll();
            foreach($options as $o){
                $all_options[$o['option_id']] = $o;
            }
        }
        while($has_more){
            $user_ids = [];
            $members = $this->getMemberList($qstn_id,$page,100,"",[1,2]);
            if($members["data"]['listInfo']){
                foreach($members["data"]['listInfo'] as $member){
                    $user_ids[] = $member['user_id'];
                }
            }
            $page++;
            if($user_ids){

                $data_all = $this->getMemberBaseDate($user_ids,$data_all);
                $answerSQL = "SELECT user_id,qsto_id as option_id,qst_id as question_id,user_write from qnr_answer where qstn_id = :qstn_id and user_id in (".implode(",",$user_ids).")";
                $answers = Yii::app()->dwdb->createCommand($answerSQL)->bindParam(":qstn_id",$qstn_id,PDO::PARAM_INT)->queryAll();
                foreach($questions as $key=>$question){
                    foreach($answers as $answer){
                        if($answer['question_id'] != $question['question_id']){
                            continue;
                        }
                        $option = $all_options[$answer['option_id']];
                        $answer_str = "";
                        if($answer['option_id']==0 || $option['type'] == 2){
                            $answer_str = $answer['user_write'];
                        }else{
                            $answer_str = $option['option'];
                        }
                        if($data_all[$answer['user_id']]){
                            if($data_all[$answer['user_id']]['question_'.$key]){
                                $answer_str = $data_all[$answer['user_id']]['question_'.$key].",".$answer_str;
                            }
                            $data_all[$answer['user_id']]['question_'.$key] = $answer_str;
                        }
                    }
                }
            }else{
                $has_more = false;
            }
        }
        if($data_all){
            $result['data']['memberList'] = $data_all;
            $result['data']['questions'] = count($questions);
            $result["info"] = "获取成功！";
            $result['code'] = 0;
        }
        return  $result;
        
    }
}
