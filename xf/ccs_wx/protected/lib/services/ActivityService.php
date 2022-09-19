<?php
/**
 * @file ActivityService.php
 * @author (chentengfei@itouzi.com)
 * @date 2018/2/28
 * css 活动相关 service
 **/

class ActivityService extends  ItzInstanceService {
    public $award_type_str = [
        1 => "VIP体验",
        2 => "优惠券奖励",
    ];
	public function __construct(  )
    {
        parent::__construct();
    }

    //获取活动列表
    public function getActivityList($name="",$award_type="",$as = "",$ae = "",$page = 1,$limit = 10)
    {   
        $this->timeoutActivity();
        $this->startActivity();
        $criteria = new CDbCriteria;
        if($name){
            $criteria->addSearchCondition('name',$name);
        }
        if($award_type && in_array($award_type,[1,2])){
            $criteria->addCondition("award_type=$award_type");
        }
        if(intval($as) && intval($ae)){
            $criteria->addBetweenCondition("addtime",intval($as),intval($ae));
        }elseif(intval($as)){
            $criteria->addCondition("addtime >= ".intval($as));
        }elseif(intval($ae)){
            $criteria->addCondition("addtime <= ".intval($ae));
        }
        $total = CcsActivity::model()->countByAttributes([],$criteria);
        $result = ["code"=>1,"info"=>"获取失败！","data"=>[]];
        if(empty($total)){
            $result['data'] = [];
            $result['code'] = 1;
            $result['info'] = "没有对应数据！";
            return $result;
        }
        if($page < 1 || ($page - 1)*$limit > $total){
            $result['data'] = ['listTotal'=>intval($total),'listInfo'=>[]];
            $result['code'] = 100;
            $result['info'] = "分页错误！";
            return $result;
        }
        $criteria->offset = intval(($page-1)*$limit);
        $criteria->limit = intval($limit);
        $criteria->order = 'addtime desc';
        $activities = CcsActivity::model()->findAllByAttributes([],$criteria);
        if($activities){
            $list = [];
            foreach($activities as $activity){
                $list[] = [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'content' => $activity->content,
                    'addtime' => date("Y-m-d H:i:s",$activity->addtime),
                    'starttime' => date("Y-m-d H:i:s",$activity->starttime),
                    'endtime' => date("Y-m-d H:i:s",$activity->endtime-1),
                    'members' => intval($activity->members),
                    'status' => intval($activity->status),
                    'award_type' => intval($activity->award_type),
                    'award_type_str' => $this->award_type_str[$activity->award_type]?:"未知",
                ];
            }
            $result['data'] = ['listTotal'=>intval($total),'listInfo'=>$list];
            $result['code'] = 0;
            $result['info'] = "获取列表成功！";
        }
        return $result; 
    }

    //过期活动
    public function timeoutActivity()
    {
        $endtime = time();
        $res = CcsActivity::model()->updateAll(["status"=>3],"endtime < :endtime and status in (0,1)",[":endtime"=>$endtime]);
        return true;
    }

    //开始活动
    public function startActivity()
    {
        $starttime = time();
        $criteria = new CDbCriteria;
        $criteria->addCondition("starttime > {$starttime}");
        $res = CcsActivity::model()->findAllByAttributes(['status'=>0],$criteria);
        foreach($res as $r){
            $this->giveAward($r->id);
            $r->status = 1;
            $r->save();
        }
        return true;
    }


    //添加活动
    public function addActivity($data)
    {
        $criteria = new CDbCriteria;
        $result = ["code"=>1,"info"=>"添加失败！","data"=>[]];
        if(!$data['name']){
            return $result;
        }
        $criteria = new CDbCriteria;
        $criteria->addSearchCondition('name',$data['name']);
        $name_exit = CcsActivity::model()->countByAttributes([],$criteria);
        if($name_exit){
            $result['code'] = 101;
            $result['info'] = "活动名称已存在！";
            return $result;
        }
        $data['starttime'] = $starttime = strtotime($data['starttime'])?:strtotime("midnight");
        $data['endtime'] = strtotime($data['endtime'])?:strtotime("+3 months",$starttime);
        if($data['endtime'] < $data['starttime']){
            $result['code'] = 102;
            $result['info'] = "结束时间不能小于开始时间！";
            return $result;
        }
        if(!in_array($data['status'],[0,1,2,3]) || !in_array($data['award_type'],[1,2])){
            $result['code'] = 102;
            $result['info'] = "参数错误！";
            return $result;
        }
        if($data['award_type'] == 2 && empty($data['award_couponslot'])) {
            $result['code'] = 102;
            $result['info'] = "类型为优惠券奖励时，须添加优惠券触发点！";
            return $result;
        }
        if(strlen($data['award_couponslot']) >20 || strlen($data['award_trigger']) > 20){
            $result['code'] = 103;
            $result['info'] = "触发点长度不超过20字符！";
            return $result;
        }
        if(mb_strlen($data['name'],'utf-8') > 20 || mb_strlen($data['content'],'utf-8') > 100){
            $result['code'] = 104;
            $result['info'] = "项目名称不得超过20个字，活动简介不得超过100个字";
            return $result;
        }

        $now = time();
        $activity = new CcsActivity();
        $activity->name = $data['name'];
        $activity->content = $data['content'];
        $activity->addtime = $now;
        $activity->starttime = $data['starttime'];
        $activity->endtime = $data['endtime']+86399;
        $activity->status = $data['status']?:0;
        $activity->type = 1;
        $activity->award_type = $data['award_type'];
        $activity->award_couponslot = $data['award_couponslot'];
        $activity->award_trigger = $data['award_trigger'];
        $res = $activity->save();
        if($res){
            $result['code'] = 0;
            $result['info'] = "活动添加成功！";
            return $result;
        }
        return $result; 
    }

    //编辑活动信息
    public function editActivity($activity_id,$data)
    {
        $activity = CcsActivity::model()->findByPk($activity_id);
        $result = ["code"=>1,"info"=>"更新失败！","data"=>[]];
        if(!$activity){
            return $result;
        }
        $data['starttime'] = $starttime = strtotime($data['starttime'])?:strtotime("midnight");
        $data['endtime'] = strtotime($data['endtime'])?:strtotime("+3 months",$starttime);
        if($data['endtime'] < $data['starttime']){
            $result['code'] = 102;
            $result['info'] = "结束时间不能小于开始时间！";
            return $result;
        }
        if(!in_array($data['status'],[0,1,2,3]) || !in_array($data['award_type'],[1,2])){
            $result['code'] = 102;
            $result['info'] = "状态或类型参数错误！";
            return $result;
        }
        if($data['award_type'] == 2 && empty($data['award_couponslot'])) {
            $result['code'] = 102;
            $result['info'] = "类型为优惠券奖励时，须添加优惠券触发点！";
            return $result;
        }
        if(strlen($data['award_couponslot']) >20 || strlen($data['award_trigger']) > 20){
            $result['code'] = 103;
            $result['info'] = "触发点长度不超过20字符！";
            return $result;
        }
        if(mb_strlen($data['name'],'utf-8') > 20 || mb_strlen($data['content'],'utf-8') > 100){
            $result['code'] = 104;
            $result['info'] = "项目名称不得超过20个字，活动简介不得超过100个字";
            return $result;
        }
        if($activity->status > $data['status']){
            $result['code'] = 105;
            $result['info'] = "活动进度无法回退！";
            return $result;
        }
        $old_status = $activity->status;
        $activity->content = $data['content'];
        $activity->addtime = $now;
        $activity->starttime = $data['starttime'];
        $activity->endtime = $data['endtime']+86399;
        $activity->status = $data['status']?:0;
        $activity->type = 1;
        $activity->award_type = $data['award_type'];
        $activity->award_couponslot = $data['award_couponslot'];
        $activity->award_trigger = $data['award_trigger'];
        $res = $activity->save();
        if($res){
            if($old_status == 0 && $data['status'] == 1){
                $this->giveAward($activity_id);
            } 
            $result['code'] = 0;
            $result['info'] = "活动更新成功！";
            return $result;
        }
    }
    
    //给未发奖励的用户补发奖励
    private function giveAward($activity_id)
    {
        $result = 0;
        $activity = CcsActivity::model()->findByPk($activity_id);
        if($activity){
            return $result;
        }
        $unaward_members = CcsActivityMember::model()->findAllByAttributes(['activity_id'=>$activity_id,'awardtime'=>0]);
        foreach($unaward_members as $member){
            $user = User::model()->findByPk($member->user_id);
            if($activity->award_type == 1 && $user->user_grade_code <= $member->max_vip) {
                $vip_experience = ItzVipExperience::model()->findByPk($member->user_id);
                if($vip_experience){
                    if($vip_experience->lv <= $member->max_vip || $vip_experience->endtime < time()){
                        $vip_experience->lv = $member->max_vip;
                        $vip_experience->endtime = strtotime("+60 days midnight");
                        $vip_experience->save();
                    }
                }else{
                    $vip_experience = new ItzVipExperience();
                    $vip_experience->user_id = $member->user_id;
                    $vip_experience->lv = $member->max_vip;
                    $vip_experience->addtime = time();
                    $vip_experience->endtime = strtotime("+60 days midnight");
                    $vip_experience->save();
                }
                $user->user_grade_code = $member->max_vip;
                $user->grade_expire_time = 0;
                $res = $user->update(['user_grade_code','grade_expire_time']);
                if($res){
                    $member->awardtime = time();
                    $member->save();
                    $awarded = true;
                    $result ++;
                }
            }elseif($activity->award_type == 2 && $activity->award_couponslot) {
                $res = CouponSlotClass::getInstance()->couponSlot($member->user_id, $activity->award_couponslot);
                if($res){
                    $member->awardtime = time();
                    $member->save();
                    $awarded = true;
                    $result ++;
                }
            }
            if($awarded && $activity->award_trigger){
                $remind = array();
                $remind['sent_user'] = 0; // 发送人user_id 如无特殊需求保持不变（非必填项）
                $remind['receive_user'] = $member->user_id; // 接收人用户user_id
                if($activity->award_type == 1) {
                    $remind['data']['hd_lyhzhdj'] = $member->max_vip; // 可能需要的参数
                }
                $remind['mtype'] = $activity->award_trigger; // 消息触发点CODE
                NewRemindService::getInstance()->SendToUser($remind,true,true,true,true);
            }
        }
        return $result;
    }

    public function getActivityData($activity_id = 1)
    {
        $result = ["code"=>1,"info"=>"获取失败！","data"=>[]];
        $activity = CcsActivity::model()->findByPk($activity_id);
        if($activity) {
            $data["listInfo"] = [
                'id' => $activity->id,
                'name' => $activity->name,
                'content' => $activity->content,
                'addtime' => date("Y-m-d H:i:s",$activity->addtime),
                'starttime' => date("Y-m-d H:i:s",$activity->starttime),
                'endtime' => date("Y-m-d H:i:s",$activity->endtime),
                'members' => intval($activity->members),
                'status' => intval($activity->status),
                'award_type' => intval($activity->award_type),
                'award_type_str' => $this->award_type_str[$activity->award_type]?:"未知",
                'award_couponslot' => $activity->award_couponslot,
                'award_trigger' => $activity->award_trigger,
            ];
            $result['code'] = 0;
            $result['data'] = $data;
            $result['info'] = "获取成功！";
        }
        return $result;
    }
	//获取活动信息
    public function getActivityInfo($activity_id = 1)
    {
        $result = ["code"=>1,"info"=>"获取失败！","data"=>[]];
        $activity = CcsActivity::model()->findByPk($activity_id);
        if($activity) {
            $data = [
                'id' => $activity->id,
                'name' => $activity->name,
                'content' => $activity->content,
                'addtime' => date("Y-m-d H:i:s",$activity->addtime),
                'starttime' => date("Y-m-d",$activity->starttime),
                'endtime' => date("Y-m-d",$activity->endtime),
                'members' => intval($activity->members),
                'status' => intval($activity->status),
                'award_type' => intval($activity->award_type),
                'award_type_str' => $this->award_type_str[$activity->award_type]?:"未知",
                'investers' => 0,
                'invest_count' => 0,
                'invest_money' => 0.00,
                'coupon_count' => 0,
                'coupon_money' => 0.00
            ];
            if($activity->members) {
                $sql = "SELECT sum(invest_times) as invest_count,sum(invest_amount) as invest_money,sum(coupon_times) as coupon_count,sum(coupon_amount) as coupon_money from ccs_activity_member where activity_id=:activity_id";
                $sum = Yii::app()->ccsdb->createCommand($sql)->bindParam("activity_id",$activity_id,PDO::PARAM_INT)->queryRow();
                $data['invest_count'] = $sum['invest_count']?:0;
                $data['invest_money'] = $sum['invest_money']?:0;
                $data['coupon_count'] = $sum['coupon_count']?:0;
                $data['coupon_money'] = $sum['coupon_money']?:0;
                $sql = "SELECT count(*) as investers from ccs_activity_member where activity_id=:activity_id and invest_times > 0";
                $investers = Yii::app()->ccsdb->createCommand($sql)->bindParam("activity_id",$activity_id,PDO::PARAM_INT)->queryScalar();
                $data['investers'] = $investers?:0;
            }
            $result['code'] = 0;
            $result['data'] = $data;
            $result['info'] = "获取成功！";
        }
	    return $result;
	}
    
    //获取活动成员列表
    public function getActivityMember($activity_id = 1,$page = 1,$limit = 10)
    {
        $result = ["code"=>1,"info"=>"获取失败！","data"=>[]];
        if(!is_numeric($activity_id) || !is_numeric($page) || !is_numeric($limit)){
            return $result;
        }
        $activity = CcsActivity::model()->findByPk($activity_id);
        $members_count = CcsActivityMember::model()->countByAttributes(['activity_id'=>$activity_id]);
        if(!$members_count){
            $result['info'] = "没有相关数据！";
            $result['code'] = 100;
            return $result;
        }
        if(($page -1) * $limit > $members_count || ($page -1) * $limit < 0){
            $result['data'] = ['listTotal'=>intval($members_count),'listInfo'=>[]];
            $result['code'] = 100;
            $result['info'] = "分页错误！";
            return $result;
        }
        $criteria = new CDbCriteria;
        $criteria->offset = intval(($page-1)*$limit);
        $criteria->limit = intval($limit);
        $criteria->order = 'addtime desc,id asc';
        $members = CcsActivityMember::model()->findAllByAttributes(['activity_id'=>$activity_id],$criteria);
        $list = [];
        foreach($members as $member){
            $m = [
                'user_id' => $member->user_id,
                'max_vip' => $member->max_vip,
                'max_invest' => $member->max_invest,
                'last_tender_time' => $member->last_tender_time?date("Y-m-d H:i:s",$member->last_tender_time):"-",
                'last_login_time' => $member->last_login_time?date("Y-m-d H:i:s",$member->last_login_time):"-",
                'invest_count' => $member->invest_times,
                'invest_money' => $member->invest_amount,
                'coupon_count' => $member->coupon_times,
                'coupon_money' => $member->coupon_amount,
                'addtime' => date("Y-m-d H:i:s",$member->addtime)
            ];
            $list [$member->user_id] = $m;
        }
        $result['data']['listTotal'] =  intval($members_count);
        $result['data']['listInfo'] =  array_values($list);
        $result['code'] = 0;
        $result['info'] = "获取成功！";
	    return $result;
	}
    
    //添加活动成员
    public function addActivityMember($activity_id = 1,$data = [])
    {
        $result = ["code"=>1,"info"=>"数据有误！","data"=>[]];
        $total = $success = $fail = $repet = 0;
        $activity = CcsActivity::model()->findByPk($activity_id);
        if($data && $activity){
            foreach($data as $d)
            {
                $total ++;
                //参数错误
                if(!is_numeric($d['user_id']) || !is_numeric($d['lv']) || !is_numeric($d['invest'])){
                    $fail++;
                    continue;
                }
                $member = CcsActivityMember::model()->findByAttributes(['user_id'=>$d['user_id'],'activity_id'=>$activity_id]);
                //已添加的用户
                if($member){
                    $repet++;
                    continue;
                }
                $user = User::model()->findByPk($d['user_id']);
                if(empty($user)){//用户不存在
                    $fail++;
                    continue;
                }
                //奖励为体验vip时，如果用户现有VIP等级大于发放等级，则失败。
                if($activity->award_type == 1 && $user->user_grade_code > $d['lv']){
                    $fail++;
                    continue;
                }
                $last_tender_sql = "SELECT addtime from dw_borrow_tender where user_id = {$d['user_id']} order by addtime desc limit 1";
                $last_tender = Yii::app()->dwdb->createCommand($last_tender_sql)->queryRow();
                $member = new CcsActivityMember();
                $member->user_id = $d['user_id'];
                $member->activity_id = $activity_id;
                $member->max_invest = $d['invest'];
                $member->max_vip = $d['lv'];
                $member->last_tender_time = $last_tender&&$last_tender['addtime']?$last_tender['addtime']:0;
                $member->last_login_time = 0;
                $member->addtime = time();
                $res = $member->save();
                if($res) {//保存成功
                    $success++;
                    $awarded = false;
                    if($activity->status == 1 &&  $activity->award_type == 1 && $user->user_grade_code <= $d['lv']) {
                        $vip_experience = ItzVipExperience::model()->findByPk($d['user_id']);
                        if($vip_experience){
                            if($vip_experience->lv <= $d['lv'] || $vip_experience->endtime < time()){
                                $vip_experience->lv = $d['lv'];
                                $vip_experience->endtime = strtotime("+60 days midnight");
                                $vip_experience->save();
                            }
                        }else{
                            $vip_experience = new ItzVipExperience();
                            $vip_experience->user_id = $d['user_id'];
                            $vip_experience->lv = $d['lv'];
                            $vip_experience->addtime = time();
                            $vip_experience->endtime = strtotime("+60 days midnight");
                            $vip_experience->save();
                        }
                        $user->user_grade_code = intval($d['lv']);
                        $user->grade_expire_time = 0;
                        $res = $user->update(['user_grade_code','grade_expire_time']);
                        if($res){
                            $member->awardtime = time();
                            $member->save();
                            $awarded = true;
                        }
                    }elseif($activity->status == 1 && $activity->award_type == 2 && $activity->award_couponslot) {
                        $res = CouponSlotClass::getInstance()->couponSlot($d['user_id'], $activity->award_couponslot);
                        if($res){
                            $member->awardtime = time();
                            $member->save();
                            $awarded = true;
                        }
                    }
                    if($awarded && $activity->award_trigger){
                        $remind = array();
                        $remind['sent_user'] = 0; // 发送人user_id 如无特殊需求保持不变（非必填项）
                        $remind['receive_user'] = $d['user_id']; // 接收人用户user_id
                        if($activity->award_type == 1) {
                            $remind['data']['hd_lyhzhdj'] = $d['lv']; // 可能需要的参数
                        }
                        $remind['mtype'] = $activity->award_trigger; // 消息触发点CODE
                        NewRemindService::getInstance()->SendToUser($remind,true,true,true,true);
                    }
                }else{
                    $fail++;
                }
            }
        } elseif($data && $activity_id == 0) {
            $d = $data;
            $user = User::model()->findByPk($d['user_id']);
            if(empty($user)){//用户不存
                return false;
            }
            $last_tender_sql = "SELECT addtime from dw_borrow_tender where user_id = {$d['user_id']} order by addtime desc limit 1";
            $last_tender = Yii::app()->dwdb->createCommand($last_tender_sql)->queryRow();
            $member = new CcsActivityMember();
            $member->user_id = $d['user_id'];
            $member->activity_id = $activity_id;
            $member->max_invest = $d['invest']?:0;
            $member->max_vip = $d['lv']?:0;
            $member->last_tender_time = $last_tender&&$last_tender['addtime']?$last_tender['addtime']:0;
            $member->last_login_time = 0;
            $member->addtime = time();
            $res = $member->save();
            return $res;
        }
        if($total){
            $members = CcsActivityMember::model()->countByAttributes(['activity_id'=>$activity_id]);
            $activity->members = $members;
            $activity->save();
            $result['info'] = "本批共有{$total}个用户，其中{$success}个添加成功，{$fail}个失败,{$repet}个已存在！";
            $result['code'] = 0;
        }
	    return $result;
	}
    
    //删除活动成员
    public function deleteActivityMember($activity_id = 1,$user_ids = []) 
    {
        if(!is_array($user_ids) || !is_numeric($activity_id)){
            return false;
        }
        $criteria = new CDbCriteria;  
        $criteria->addInCondition('user_id', $user_ids);
        ItzVipExperience::model()->deleteAll($criteria);
        $criteria->addCondition("activity_id=$activity_id");  
        CcsActivityMember::model()->deleteAll($criteria); //News换成你的模型  
        $members = CcsActivityMember::model()->countByAttributes(['activity_id'=>$activity_id]);
        $activity = CcsActivity::model()->findByPk($activity_id);
        if($activity){
            $activity->members = $members;
            $activity->save();
        }
        return true;
    }
    
    //导出活动成员信息
    public function exportActivityMemberData($activity_id = 1) 
    {
        $result = ["code"=>1,"info"=>"获取失败！","data"=>[]];
        $activity = CcsActivity::model()->findByPk($activity_id);
        if($activity && $activity->members){
            $list = [];
            for($i = 1;$i<=ceil($activity->members/100);$i++){
                $data = $this->getActivityMember($activity_id,$i,100);
                if($data['data']['listInfo']){
                    $list = array_merge($list,$data['data']['listInfo']);
                }
            }
            if($list){
                $result['data']['name'] = $activity->name;
                $result['data']['list'] = $list;
                $result['code'] = 0;
            }
        }
	    return $result;
    }
}
