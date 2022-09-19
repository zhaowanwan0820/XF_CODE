<?php
/**
  *
  * home/work/local/php/bin/php /home/work/itouzi.com/yii.itouzi.com/dashboard/protected/bin/yiic.php CcsCallrecord run
  * 获取通话录音 并上传录音和问卷调查到opmp
  * 每5分钟执行一次
**/
class CrmInitCommand extends CConsoleCommand {

	public $tags = "活动型,稳定型,保守型,小白型,债券型,固定型,羊毛型,资深型,短期型,长期型";
	public $admin_names = "chenzhengyang01,dongtingrui,liting,guotingting,zhengqing,yuanxiaosong,daiyu,zhaichenglong,chenjia";
	public $admin_names2 = "陈正阳,董廷瑞,李婷,郭婷婷,郑晴,袁晓松,代羽,翟成龙,陈佳";
    public function actionRun(){
		echo "start...\n";
		$ans = explode(",",$this->admin_names);
		$ans2 = explode(",",$this->admin_names2);
		foreach($ans as $k=>$v){
			$this->actionUpdate($v,$ans2[$k]);
		}
		echo "end....\n";
	}

	public function actionAllot()
	{
		$task_count_sql = "SELECT count(*) as c from crm_task where status in (1,3)";
		$count = Yii::app()->crmdb->createCommand($task_count_sql)->queryScalar();
		echo "总 {$count} 条 \n";
		$start = 0;
		do{
			$task_sql = "SELECT user_id,admin_id,call_status,user_level from crm_task where status in (1,3) order by admin_id desc limit $start,1000";
			$tasks = Yii::app()->crmdb->createCommand($task_sql)->queryAll();
			$allots = [];
			foreach($tasks as $task){
				$allots[$task['admin_id']][] = $task['user_id'];
			}
			foreach($allots as $admin_id=>$user_ids){
				NewCrmService::getInstance()->allotUser($admin_id,$user_ids);
			}
			foreach($tasks as $task){
				$crmUser = CrmUser::model()->findByPk($task['user_id']);
				if($crmUser){
					$crmUser->is_call = $task['call_status']==2?1:0;
					$crmUser->user_level = $task['user_level'];
					$crmUser->save();
				}
			}
			$start +=1000;
			echo $start."  ".count($tasks) ."\n";
			Yii::app()->crmdb->flushPdoInstance();
		}while(count($tasks) == 1000);
	}

	public function actionUpdate($name="",$name2="")
	{
		if(empty($name)||empty($name2)){
			echo "请添加参数用户后台名：name 及其实名：name2\n";
			return ;
		}
		$admin = CrmAdmin::model()->findByAttributes(["name"=>$name]);
		if(empty($admin)){
			$admin2 = CrmAdmin::model()->findByAttributes(["name"=>$name2]);
			if($admin2){
				echo "管理员 {$name2} 无需更新！\n";
				return ;
			}
			$crmRelation = CrmRelation::model()->findByAttributes(["admin_name"=>$name,"status"=>1]);
			if(empty($crmRelation)){
				$ccsRelation = CcsRelation::model()->findByAttributes(["admin_name"=>$name]);
				if(empty($ccsRelation)){
					echo "客维库中不存在的管理员：{$name}\n";
					return ;
				}
				$admin = new CrmAdmin();
				$admin->admin_id = $ccsRelation->admin_id;
				$admin->relation_id = $ccsRelation->id;
				$admin->name = $ccsRelation->admin_name;
				$admin->addtime = $ccsRelation->addtime;
				$admin->type = 2;
				$admin->status = 1;
				$admin->save();
			}else{
				$admin = new CrmAdmin();
				$admin->admin_id = $crmRelation->admin_id;
				$admin->relation_id = $crmRelation->id;
				$admin->name = $crmRelation->admin_name;
				$admin->addtime = $crmRelation->addtime;
				$admin->type = 4;
				$admin->status = 1;
				$admin->save();
			}
		}
		$admin->name = $name2;
		$admin->save();
		echo "管理员 {$name2} 更新成功！\n";
	} 

	public function actionRemove($name="")
	{
		if(empty($name)){
			echo "请输入用户名";
			return ;
		}
		$admin = CrmAdmin::model()->findByAttributes(["name"=>$name]);
		if($admin){
			$admin->status = 2;
			$admin->save();
			echo "修改成功！";
		}else{
			echo "未找到相关数据！";
		}
		
	}

	public function actionCleanAllot($name="")
	{
		$sql = "SELECT user_id,remark,`datetime` from (SELECT * from crm_report_reason order by addtime desc,`datetime` desc) c group by user_id";
		$results = Yii::app()->crmdb->createCommand($sql)->queryAll();
		foreach($results as $result){
			if(in_array($result['remark'],["充值成功","鉴权成功","鉴权未充值","充值未投资"])){
				$user = CrmUser::model()->findByPk($result['user_id']);
				if($user){
					CrmReportUser::model()->updateAll(["is_allot"=>1],"user_id=:user_id",[":user_id"=>$result['user_id']]);
					if(!$user->is_call){
						$user->admin_id = 0;
						$user->is_allot = 0;
						$user->allot_time = 0;
						$user->save();
					}
				}
			}
		}
		echo "end....";
	}

	public function actionCleanAdmin()
	{
		$sql = "SELECT admin_id from crm_admin group by admin_id having count(*) > 1";
		$adminIDs = Yii::app()->crmdb->createCommand($sql)->queryAll();
		foreach($adminIDs as $admin){
			$admins = CrmAdmin::model()->findAllByAttributes(["admin_id"=>$admin['admin_id']]);
			$t = $p = 0;
			foreach($admins as $k=>$v){
				if($v->type > $t){
					$p = $k;
					$t = $v->type;
				}
			}
			foreach($admins as $k=>$v){
				if($k != $p){
					$v->delete();
				}
			}
		}
	}
	/**
	 * 重置用户名
	 */
	public function initAdmin2()
	{
		$admin_names = explode(",",$this->admin_names);
		$admin_names2 = explode(",",$this->admin_names2);
		$criteria = new CDbCriteria;
		$criteria->addNotInCondition("name",array_merge($admin_names,$admin_names2));
		$criteria->addInCondition("type",[4,5]);
		$otherAdmins = CrmAdmin::model()->findAllByAttributes([],$criteria);
		foreach($otherAdmins as $admin){
			$admin->type = 1;
			$admin->save();
		}
		foreach($admin_names as $key=>$admin_name){
			$admin = CrmAdmin::model()->findByAttributes(["name"=>[$admin_name,$admin_names2[$key]],"type"=>4]);
			if($admin){
				$admin->name = $admin_names2[$key];
				$admin->save();
			}
		}
	}

	public function initAdmin(){
		//客服
		$ccsRelations = CcsRelation::model()->findAll("");
		echo count($ccsRelations)."\n";
		$crmAdmin1 = new CrmAdmin();
		foreach($ccsRelations as $ccsRelation){
			$crmAdmin = clone $crmAdmin1;
			$crmAdmin->admin_id = $ccsRelation->admin_id;
			$crmAdmin->relation_id = $ccsRelation->id;
			$crmAdmin->name = $ccsRelation->admin_name;
			$crmAdmin->addtime = $ccsRelation->addtime;
			$crmAdmin->type = 2;
			$crmAdmin->status = 1;
			$crmAdmin->save();
		}
		$crmRelations = CrmRelation::model()->findAllByAttributes(["status"=>1]);
		echo count($crmRelations)."\n";
		foreach($crmRelations as $crmRelation){
			$crmAdmin = clone $crmAdmin1;
			$crmAdmin->admin_id = $crmRelation->admin_id;
			$crmAdmin->relation_id = $crmRelation->id;
			$crmAdmin->name = $crmRelation->admin_name;
			$crmAdmin->addtime = $crmRelation->addtime;
			$crmAdmin->type = $crmRelation->role==2?1:($crmRelation->ag_role==3?5:4);
			$crmAdmin->status = 1;
			$crmAdmin->save();
		}
		echo "crmadmin init finish\n";
	}

	public function initTag()
	{
		$tags = explode(",",$this->tags);
		$crmUserTag1 = new CrmUserTag();
		$now = time();
		foreach($tags as $tag){
			$userTag = clone $crmUserTag1;
			$userTag->tag = $tag;
			$userTag->status = 1;
			$userTag->addtime = $now;
			$userTag->save();
		}
		echo "tag init finish\n";
	}
}
?>