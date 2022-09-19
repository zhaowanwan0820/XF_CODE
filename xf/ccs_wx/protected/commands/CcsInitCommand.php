<?php
/**
 * ccs 一些基础内容实现
 * 
 */

class CcsInitCommand extends CConsoleCommand
{
    public function actionInitCs()
    {
        echo "initcs start \n";
        $handle = fopen(APP_DIR."/protected/commands/cs.csv","r") or die("no file exit");
        while(!feof($handle)) {
            $data = fgetcsv($handle);
            if(is_numeric($data[0]) && $data[0]>0) {
                $ccs_relation = CcsRelation::model()->findByAttributes(["admin_id"=>$data[0]]);
                if($ccs_relation){
                    continue;
                }
                $agentInfos = CallService::getInstance()->getAgentInfo($data[1],$data[4]);
                foreach($agentInfos as $agentInfo){
                    if($agentInfo["ag_num"] == $data[4]){
                        $ccs_relation = new CcsRelation();
                        $ccs_relation->admin_id = $data[0];
                        $ccs_relation->admin_name = $data[3];
                        $ccs_relation->ag_num = $agentInfo["ag_num"];
                        $ccs_relation->ag_name = $agentInfo["ag_name"];
                        //有可能不是这个密码。就需要改
                        $ccs_relation->ag_password = "123456";
                        $ccs_relation->ag_role = $agentInfo["ag_role"];
                        $ccs_relation->ag_user_role = $agentInfo["user_role"];
                        $ccs_relation->ag_phone = $agentInfo["pho_num"];
                        $ccs_relation->addtime = time();
                        $res = $ccs_relation->save();
                        if($res){
                            $user = User::model()->findByAttributes(["username"=>$data[5]]);
                            $crmAdmin = CrmAdmin::model()->findByAttributes(['admin_id'=>$ccs_relation->admin_id]);
                            if(!$crmAdmin){
                                $crmAdmin = new CrmAdmin();
                            }
                            $crmAdmin->admin_id = $ccs_relation->admin_id;
                            $crmAdmin->relation_id = $ccs_relation->id;
                            $crmAdmin->name = $ccs_relation->ag_name;
                            $crmAdmin->addtime = $ccs_relation->addtime;
                            $crmAdmin->ucenter_uid = $user?$user->ucenter_uid:0;
                            $crmAdmin->type = 2;
                            $crmAdmin->status = 1;
                            $crmAdmin->save();
                            echo $data["2"]." add success \n";
                        }
                        continue 2;
                    }
                }
            }
        }
        echo "initcs end \n";
    }


    public function actionChangeCcsPwd($ag_id,$ag_pwd)
    {
        $ccs_relation = CcsRelation::model()->findByAttributes(["ag_id"=>$ag_id]);
        if(!$ccs_relation){
            echo "$ag_id not found \n";die;
        }
        $ccs_relation->ag_password = $ag_pwd;
        $ccs_relation->save();
        echo "$ag_id pwd change success \n";
    }

    public function actionCleanFreeze()
    {
        $limit = 1000;
        $start = 0;
        do{ 
            $_sql = "SELECT user_id from dw_user where status <> 0 limit $start,$limit";
            $users = Yii::app()->dwdb->createCommand($_sql)->queryAll();
            $user_ids = [];
            foreach($users as $user) {
                $user_ids[] = $user["user_id"];
            }
            $res = CrmUser::model()->deleteByPk($user_ids);
            $start += 1000;
        }while(count($user_ids)>=1000);
        echo "CleanFreeze success \n";
    }

    public function actionInitAdmin()
    {
        $admins = CrmAdmin::model()->findAll();
        foreach($admins as $admin){
            $adminInfo = ItzUser::model()->findByPk($admin->admin_id);
            $ccsRelation = CcsRelation::model()->findByAttributes(['admin_id'=>$admin->admin_id]);
            $crmRelation = CrmRelation::model()->findByAttributes(['admin_id'=>$admin->admin_id]);
            $admin->admin_name = $adminInfo?$adminInfo->username:"";
            if($ccsRelation && $ccsRelation->ag_num){
                $admin->ag_num1 = $ccsRelation->ag_num;
                if($crmRelation && $crmRelation->ag_num && $crmRelation->ag_num != $ccsRelation->ag_num){
                    $admin->ag_num2 = $crmRelation->ag_num;
                }
            }elseif($crmRelation && $crmRelation->ag_num){
                $admin->ag_num1 = $crmRelation->ag_num;
            }
            $admin->save();
        }
    }
}