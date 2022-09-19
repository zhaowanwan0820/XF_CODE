<?php
/**
 * 新版crm服务
 */
class NewCrmService extends ItzInstanceService 
{

    protected $expire1 = 3600;
    protected $expire2 = 86400;
    protected $secondaryFlag = false;

    //智齿相关配置
    //access_token接口地址
    protected $sobot_access_url = "https://open.sobot.com/open/platform/getAccessToken.json";
    //api 接口地址
    protected $sobot_api_url = "https://open.sobot.com/open/platform/api.json";
    //appid
    protected $sobot_appid = "123456";
    //appkey
    protected $sobot_appkey = "123456";

    //用户动态类型
    protected $dynamic_types = [
        1 => "鉴权未回调",
        2 => "鉴权失败",
        3 => "充值未回调",
        4 => "充值失败",
        5 => "投资未回调",
        6 => "投资失败",
        7 => "提现成功",
        8 => "提现失败",
    ];

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 输入过滤
     */
    private function filter($data,$date = false)
    {
        $return = [];
        if(is_array($data)){
            foreach($data as $key => $v){
                if(substr($key,-6)=="_start"){
                    $key2 = substr($key,0,-6);
                    $return[$key2][0] = strtotime($v);
                }elseif(substr($key,-4)=="_end"){
                    $key2 = substr($key,0,-4);
                    $return[$key2][1] = strtotime($v) + 86400;
                }elseif($key === 'page'){
                    $return[$key] = intval($v)?:1;
                }elseif($key === 'limit'){
                    $return[$key] = in_array(intval($v),[5,10,20,50,100])?intval($v):10;
                }else{
                    $return[$key] = $this->filter($v,strpos($key,"time") > 0?true:false);
                }
            }
        }elseif(is_string($data)){
            if($date){
                return strtotime($data);
            }
            return htmlspecialchars(addslashes(trim($data)));
        }else{
            $return = $data;
        }
        return $return;
    }

    /**
     * 以论坛昵称搜索
     */
    private function searchBbsName($filter,& $criteria)
    {
        $name = $filter["ucenter_name"];
        if($name) {
            $_bbs_sql = "SELECT `uid` from itz_common_member_field_forum where customstatus like '{$name}%'";
            $bbs_result = Yii::app()->bbs->createCommand($_bbs_sql)->queryAll();
            $uids = [];
            foreach($bbs_result as $b){
                $uids[] = $b["uid"];
            }
            if(empty($uids)){
                return false;
            }
            $criteria->addInCondition("ucenter_uid",$uids);
        }
        return true;
    }

    /**
     * 以crm_user表为基础搜索
     */
    private function searchCrmUser($filter,& $criteria,$type = 2)
    {
        if(in_array($filter['user_type'],[2,4,5,6,8]) || $filter['admin_id']) { //搜索crm_user表信息
            $search = "";
            if(in_array($filter['user_type'],[2,4,5,6,8])){
                $search = "user_type = ".$filter['user_type'];
            } 
            if(intval($filter['admin_id'])){
                $search .= $search?" and ":"";
                $search .= "admin_id = ".$filter['admin_id'];
            }
            if($filter['allot_time'] && intval($filter['allot_time'][0]) && intval($filter['allot_time'][1])){
                $search .= $search?" and ":"";
                $search .= "allot_time between {$filter['allot_time'][0]} and {$filter['allot_time'][1]}";
            }
            if($filter['call_time'] && intval($filter['call_time'][0]) && intval($filter['call_time'][1])){
                $search .= $search?" and ":"";
                $search .= "call_time between {$filter['call_time'][0]} and {$filter['call_time'][1]}";
            }
            if($type == 1){
                $search .= $search?" and ":"";
                $search .= "is_call = 0";
            }
            
            $_crm_sql = "SELECT user_id from crm_user where $search order by allot_time desc";
            $crm_result = Yii::app()->crmdb->createCommand($_crm_sql)->queryAll();
            $user_ids = [];
            foreach($crm_result as $b){
                $user_ids[] = $b["user_id"];
            }
            if(empty($user_ids)){
                return false;
            }
            if(is_array($criteria)){
                if(empty($criteria)){
                    $criteria = $user_ids;
                }else{
                    $criteria = array_intersect($criteria,$user_ids);
                    if(empty($criteria)){
                        return false;
                    }
                }
            }elseif($criteria instanceof CDbCriteria){
                $criteria->addInCondition("user_id",$user_ids);
            }
        }
        return true;
    }

    /**
     * 查询投资用户
     */
    private function searchTenderUser($filter,& $user_ids)
    {
        if($filter["tender_time"]){
            $user_ids_in = '';
            if(!empty($user_ids) && is_array($user_ids)){
                $user_ids_in = ' user_id in ('.implode(',',$user_ids).') and ';
            }
            $_tender_sql = "SELECT distinct user_id  from dw_borrow_tender where {$user_ids_in} addtime between {$filter['tender_time'][0]} and {$filter['tender_time'][1]}";
            $tender_results = Yii::app()->dwdb->createCommand($_tender_sql)->queryAll();
            $tender_users = [];
            foreach($tender_results as $t){
                $tender_users[] = $t["user_id"];
            }
            if(empty($user_ids)){
                $user_ids = $tender_users;
            }else{
                $user_ids = array_intersect($user_ids,$tender_users);
            }
            if(empty($user_ids)){
                return false;
            }
        }
        return true;
    }

    /**
     * 查询主站用户表
     */
    private function searchUser($filter,& $user_ids)
    {
        if($filter["reg_time"]){
            $user_ids_in = '';
            if(!empty($user_ids) && is_array($user_ids)){
                $user_ids_in = ' user_id in ('.implode(',',$user_ids).') and ';
            }
            $_user_sql = "SELECT distinct user_id  from dw_user where {$user_ids_in} addtime between {$filter['reg_time'][0]} and {$filter['reg_time'][1]}";
            $user_results = Yii::app()->dwdb->createCommand($_user_sql)->queryAll();
            $users = [];
            foreach($user_results as $t){
                $users[] = $t["user_id"];
            }
            if(empty($user_ids)){
                $user_ids = $users;
            }else{
                $user_ids = array_intersect($user_ids,$users);
            }
            if(empty($user_ids)){
                return false;
            }
        }
        return true;
    }

    /**
     * 制作列表返回数据
     */
    public function makeUserList($users,$with_reason=false) 
    {
        $user_ids = $ucenter_uids = $result = [];
        foreach($users as $user){
            $user_ids[] = $user->user_id;
            if($user->ucenter_uid){
                $ucenter_uids[$user->user_id] = $user->ucenter_uid;
            }
            $u = [];
            $u["user_id"] = $user->user_id;
            $u["username"] = $user->username;
            $u["sex"] = $user->sex==1?"男":($user->sex==2?"女":"-");
            $u["realname"] = $user->realname?mb_substr($user->realname, 0, 1, 'utf-8').'**':"-";
            $u["phone"] = $user->phone?FunctionUtil::MaskTel($user->phone):"-";
            $u["card_id"] = $user->card_id?FunctionUtil::MaskCardID($user->card_id,4):"-";
            $u["ucenter_uid"] = $user->ucenter_uid;
            $u["ucenter_name"] = "-";
            $u["admin_id"] = 0;
            $u["admin_name"] = "-";
            $u["user_reg_time"] = date("Y-m-d H:i:s",$user->addtime);
            $u["allot_time"] = "-";
            $u["tender_time"] = "-";
            $u["is_call"] = 0;
            $u["call_time"] = "-";
            $u["user_status"] = $user->status;
            $u["user_type"] = $user->isinvested?7:($user->xw_open==2?3:1);
            if($with_reason){
                $u["reasons"] = $this->getUserReportReasons($user->user_id);
            }
            $result[$user->user_id] = $u;
        }
        if($ucenter_uids){//替换论坛昵称
            $_bbs_sql = "SELECT `uid`,customstatus from itz_common_member_field_forum where `uid` in (".implode(",",$ucenter_uids).")";
            $bbs_result = Yii::app()->bbs->createCommand($_bbs_sql)->queryAll();
            foreach($bbs_result as $bbs) {
                foreach($ucenter_uids as $user_id=>$ucenter_uid) {
                    if($bbs["uid"] == $ucenter_uid){
                        $result[$user_id]["ucenter_name"] = $bbs["customstatus"]?:"-";
                    }
                }
            }
        }
        if($user_ids){
            $_crm_sql = "SELECT * from crm_user where user_id in (".implode(",",$user_ids).")";
            $crm_result = Yii::app()->crmdb->createCommand($_crm_sql)->queryAll();
            foreach($crm_result as $crm_user) {
                $result[$crm_user["user_id"]]["admin_id"] =  $crm_user["admin_id"];
                $result[$crm_user["user_id"]]["admin_name"] =  $this->getAdminNameByID($crm_user["admin_id"]);
                $result[$crm_user["user_id"]]["allot_time"] =  date("Y-m-d H:i:s",$crm_user["allot_time"]);
                $result[$crm_user["user_id"]]["is_call"] =  $crm_user["is_call"];
                if($crm_user["user_type"]>$result[$crm_user["user_id"]]["user_type"]){
                    $result[$crm_user["user_id"]]["user_type"] =  $crm_user["user_type"];
                }
            }
            $_crm_sql2 = "SELECT user_id,max(start_time) as start_time from crm_call_record where user_id in (".implode(",",$user_ids).") group by user_id";
            $crm_records = Yii::app()->crmdb->createCommand($_crm_sql2)->queryAll();
            foreach($crm_records as $crm_record){
                if($result[$crm_record["user_id"]]["is_call"]){
                    $result[$crm_record["user_id"]]["call_time"] =  date("Y-m-d H:i:s",$crm_record["start_time"]);
                }
            }
            $_tender_sql = "SELECT user_id,max(addtime) as addtime from dw_borrow_tender where user_id in (".implode(",",$user_ids).") group by user_id";
            $tender_results = Yii::app()->dwdb->createCommand($_tender_sql)->queryAll();
            foreach($tender_results as $tender_result){
                if($result[$tender_result["user_id"]]["is_call"]){
                    $result[$tender_result["user_id"]]["tender_time"] =  date("Y-m-d H:i:s",$tender_result["addtime"]);
                }
            }
        }
        return array_values($result);
    }

    private function getUserReportReasons($user_id)
    {
        $data = [];
        $criteria = new CDbCriteria;
        $criteria->order = "addtime desc,datetime desc";
        $criteria->limit = 4;
        $reasons = CrmReportReason::model()->findAllByAttributes(["user_id"=>$user_id],$criteria);
        foreach($reasons as $reason){
            $str = date("m-d H:i",$reason->datetime)." ".$reason->remark;
            if($reason->realname){
                $str.= " ".$reason->realname;
            }
            if($reason->bank){
                $str.= " ".$reason->bank;
            }
            if($reason->account > 0){
                $str.= " ".$reason->account."元";
            }
            $str.= " ".$reason->describe." ##";
            $data[] = $str;
        }
        return $data;
    }

    /**
     * 获取客维列表
     */
    public function getAdminList($name="",$type = [4,5])
    {
        $return = [];
        $name = $this->filter($name);
        $criteria = new CDbCriteria;
        if($name){
            $criteria->addCondition("name like '%{$name}%'");
        }
        $criteria->order = "addtime desc";
        $admins = CrmAdmin::model()->findAllByAttributes(["status"=>1,"type"=>$type],$criteria);
        foreach($admins as $admin){
            $return[] = ["admin_id"=>$admin->admin_id,"name"=>$admin->name];
        }
        return $return;
    }

    /**
     * 获取客维名
     */
    public function getAdminNameByID($admin_id)
    {
        $admin_list = $this->getAdminList("",[1,2,3,4,5]);
        foreach($admin_list as $value){
            if($value['admin_id'] == $admin_id){
                return $value["name"];
            }
        }
        return "-";
    }


    public function getAllUserList($filter=[])
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1, 'info' => "没有对应数据！", 'data' => array('listTotal' => 0, 'listInfo' => array())
        );
        
        $filter = $this->filter($filter);
        $criteria = new CDbCriteria;
        //基础条件：个人用户，手机认证
        $criteria->addCondition("type_id = 2 and phone_status = 1");
        if(!$this->searchBbsName($filter,$criteria)) {
            return $returnResult;
        }
        if(!$this->searchCrmUser($filter,$criteria)) {
            return $returnResult;
        }
        if($filter['user_id']) {
            $criteria->addCondition("user_id=".$filter['user_id']);
        }
        if($filter['username']) {
            $criteria->addCondition("username='".$filter['username']."'");
        }
        if($filter['realname']) {
            $criteria->addCondition("realname='".$filter['realname']."'");
        }
        if($filter['card_id']) {
            $criteria->addCondition("card_id='".$filter['card_id']."'");
        }
        if($filter['phone']) {
            $criteria->addCondition("phone='".$filter['phone']."'");
        }
        if($filter['reg_time']) {
            $criteria->addBetweenCondition("addtime",$filter['reg_time'][0],$filter['reg_time'][1]);
        }
        //var_dump($filter);die;
        if($filter['user_type'] && $filter['user_type']%2 == 1){
            if($filter['user_type']==1){
                $criteria->addCondition("xw_open<> 2 and isinvested = 0");
            }elseif($filter['user_type']==3){
                $criteria->addCondition("xw_open = 2 and isinvested = 0");
            }elseif($filter['user_type']==7){
                $criteria->addCondition("isinvested = 1");
            }
        }
        
        $count = User::model()->countByAttributes([],$criteria);
        
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->select = "user_id,ucenter_uid,status,sex,username,phone,card_id,realname,user_grade_code,xw_open,isinvested,addtime";
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $criteria->order = "addtime desc";
        $users = User::model()->findAllByAttributes([],$criteria);
        $returnResult["data"]["listInfo"] = $this->makeUserList($users);
        $returnResult["code"] = 0;
        $returnResult["info"] = "获取列表成功！";
        return $returnResult;
    }

    /**
     * 获取我的私海用户
     */
    public function getMySeaUserList($filter,$admin_id,$type=1)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1, 'info' => "暂无私海用户！", 'data' => array('listTotal' => 0, 'listInfo' => array())
        );
        //echo $admin_id;die;
        $filter["admin_id"] = $admin_id;
        $filter = $this->filter($filter);
        $filter_user_ids = [];
        $criteria = new CDbCriteria;
        //基础条件：个人用户，手机认证
        $criteria->addCondition("type_id = 2 and phone_status = 1");
        if(!$this->searchCrmUser($filter,$filter_user_ids,$type)){
            return $returnResult;
        }
        if($filter['tender_time']){ //投资时间过滤
            if(!$this->searchTenderUser($filter,$filter_user_ids)){
                return $returnResult;
            }
        }
        //注册时间过滤
        if($filter['reg_time']){
            if(!$this->searchUser($filter,$filter_user_ids)){
                return $returnResult;
            }
        }
        if($filter_user_ids){
            $criteria->addInCondition("user_id",$filter_user_ids);
        }
        if($filter['user_id']) {
            $criteria->addCondition("user_id=".$filter['user_id']);
        }
        if($filter['realname']) {
            $criteria->addCondition("realname='".$filter['realname']."'");
        }
        if($filter['phone']) {
            $criteria->addCondition("phone='".$filter['phone']."'");
        }
        if($filter['user_type'] && $filter['user_type']%2 == 1){
            if($filter['user_type']==1){
                $criteria->addCondition("xw_open<> 2 and isinvested = 0");
            }elseif($filter['user_type']==3){
                $criteria->addCondition("xw_open = 2 and isinvested = 0");
            }elseif($filter['user_type']==7){
                $criteria->addCondition("isinvested = 1");
            }
        }
        
        $count = User::model()->countByAttributes([],$criteria);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->select = "user_id,ucenter_uid,status,sex,username,phone,card_id,realname,user_grade_code,xw_open,isinvested,addtime";
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        if($type == 1){
            $field_user_id = array_reverse(array_splice($filter_user_ids,0,100));
            $criteria->order = "field(user_id,".implode(",",$field_user_id).") desc";
        } else {
            $criteria->order = "addtime desc";
        }
        $users = User::model()->findAllByAttributes([],$criteria);
        $returnResult["data"]["listInfo"] = $this->makeUserList($users,$type==1);
        $returnResult["code"] = 0;
        $returnResult["info"] = "获取列表成功！";
        return $returnResult;
    }

    public function getReportList($filter = [])
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1, 'info' => "没有相关数据！", 'data' => array('listTotal' => 0, 'listInfo' => array())
        );
        $filter = $this->filter($filter);
        $criteria = new CDbCriteria;
        $_where = [];
        if(intval($filter['type'])) {
            $criteria->addCondition("type='".$filter['type']."'");
            $_where[] = "r.type = {$filter['type']}";
        }
        if(intval($filter['status'])) {
            $criteria->addCondition("status='".$filter['status']."'");
            $_where[] = "r.status = {$filter['status']}";
        }
        if($filter['addtime'] && intval($filter['addtime'][0]) && intval($filter['addtime'][1])) {
            $criteria->addBetweenCondition("addtime",$filter['addtime'][0],$filter['addtime'][1]);
            $_where[] = "r.addtime between {$filter['addtime'][0]} and {$filter['addtime'][1]}";
        }
        $count = CrmReport::model()->countByAttributes([],$criteria);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $_where_sql = implode(" and ",$_where);
        if($_where_sql){
            $_where_sql = "where ". $_where_sql;
        }
        $_report_sql = "SELECT r.*,count(ru.user_id) as members,sum(ru.is_allot) as alloted,count(ru.user_id) - sum(ru.is_allot) as wait_allot FROM crm_report r 
                        left join crm_report_user ru on r.id = ru.report_id $_where_sql group by r.id order by r.addtime desc limit $offset,$limit";
        $reports = Yii::app()->crmdb->createCommand($_report_sql)->queryAll();
        foreach($reports as &$report){
            $report["addtime"] = date("Y-m-d H:i:s",$report["addtime"]);
        }
        $returnResult["data"]["listInfo"] = $reports;
        $returnResult["code"] = 0;
        $returnResult["info"] = "获取列表成功！";
        return $returnResult;
    }

    public function getReportUserList($report_id,$page=1,$limit=10)
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1, 'info' => "用户不存在！", 'data' => []
        );
        $count = CrmReportUser::model()->countByAttributes(["report_id"=>$report_id]);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($page)>0?intval($page):1;
        $limit = intval($limit)>0?intval($limit):10;
        $offset = ($page-1)*$limit;
        if($offset >= $count){
            return $returnResult;
        }
        $user_ids = [];
        $criteria = new CDbCriteria;
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $report_users = CrmReportUser::model()->findAllByAttributes(["report_id"=>$report_id],$criteria);
        foreach($report_users as $u) {
            $user_ids[] = $u->user_id;
        }
        $users = User::model()->findAllByPk($user_ids);
        $returnResult["data"]["listInfo"] = $this->makeUserList($users);
        $returnResult["code"] = 0;
        $returnResult["info"] = "获取列表成功！";
        return $returnResult;
    }

    /**
     * 编辑批量标识
     */
    public function editReportRemark($report_id,$remark="")
    {
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1, 'info' => "修改失败！", 'data' => []
        );
        $remark = $this->filter($remark);
        $report = CrmReport::model()->findByPk($report_id);
        if($report){
            $report->remark = $remark;
            $res = $report->save();
            if($res){
                $returnResult["code"] = 0;
                $returnResult["info"] = "修改成功！";
            }
        }
        return $returnResult;
    }


    public function getUserLabel($user_label,$user_id=0)
    {
        $data = [];
        $tags = CrmUserTag::model()->findAllByAttributes(["status"=>1]);
        $user = CrmUser::model()->findByPk($user_id);
        foreach($tags as $tag){
            $data[$tag->id] = ['id'=>$tag->id,"tag"=>$tag->tag,"checked"=>false];
        }
        
        if($user && $user->user_label){
            $labels = explode(",",$user->user_label);
            foreach($labels as $d){
                $data[$d]["checked"] = true;
            }
        }
        if($user_label){
            $user_tags = explode(",",$user_label);
            $labels = [];
            foreach($user_tags as $d){
                $labels[] = $data[$d]["tag"];
            }
            return $labels;
        }
        return array_values($data);
    }

    /**
     * 获取用户主信息
     */
    public function getUserDetail($user_id)
    {
        $user_id =intval($user_id);
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1, 'info' => "用户不存在！", 'data' => []
        );
        $user = User::model()->findByPk($user_id);
        if(empty($user)){
            return $returnResult;
        }
        $accountInfo = Account::model()->findByAttributes(['user_id'=>$user_id]);
        $crm_user = CrmUser::model()->findByPk($user_id);
        $card_types = [
            1=>'身份证',
            2=>'军官证',
            3=>'港澳台通行证',
            4=>'护照',
            5=>'营业执照',
            6=>'外国人永久居留证',
        ];
        $data = [
            "user_id"=>$user->user_id,
            "phone"=>$user->phone?FunctionUtil::MaskTel($user->phone):"-",  //手机号
            "real_phone" => $user->phone,
            "phone_status"=>$user->phone_status, //手机认证状态，0 未认证 1 已认证
            "realname"=>$user->realname?FunctionUtil::splitName($user->realname)["data"].($user->sex==1?"先生":($user->sex==2?"女士":"**")):"-",  //用户真实姓名
            "usernam"=>$user->username,  //用户名
            "card_id"=>$user->card_id?FunctionUtil::MaskCardID($user->card_id,4):"-",
            "card_type"=>$user->card_type?$card_types[$user->card_type]:"-",  //证件类型
            "user_status"=>$user->status, //  用户状态：0为正常，1为注销，2为冻结、
            "vip" => $user->user_grade_code, //用户vip等级
            "user_type"=>$user->isinvested?7:($user->xw_open==2?3:1),  // 用户类型 1-注册未开户，2-开户失败，3-开户未充值，4-充值失败，5-充值未投资，6-投资失败，7-已投资
            "user_level"=>$crm_user?$crm_user->user_level:0,  //用户投资意向:0-未定义,1-没兴趣,2-近期不考虑,3-有意向
            "user_label"=>$crm_user&&$crm_user->user_label?$this->getUserLabel($crm_user->user_label):[],  //用户标签
            "account"=>[ //账户信息
                'assets'=>"0", //资产总额
                'use_money'=>"0",// 可用余额
                'earn'=>'0',// 净赚取 
                'recharge'=>'0',// 累计充值
                'cash'=>'0',// 累计提现
                'investing'=>'0',// 在投资产
                "coupons"=>"0",  //优惠券数量
                "credit"=>"0", // 积分
                "coins"=>"0", // 论坛金币 
            ],
            "bank_account"=>"-",
            "bank_name"=>"-",
            "bank_status"=>"0",
            "reg_time"=>date("Y-m-d H:i:s",$user->addtime),
            "xw_time"=>"-",
            "recharge_time"=>"-",
            "tender_time"=>"-",
            "last_tender_time"=>"-",
        ];
        //累計充值金額
        $user_recharge_total = UserAccountService::getInstance()->getUserRechargeTotal($user_id);
        $data['account']['recharge'] = $user_recharge_total['data'];
        //累計提現金額
        $user_withdraw_total = UserAccountService::getInstance()->getUserWithdrawTotal($user_id, 1);
        $data['account']['cash'] = $user_withdraw_total['data'];
        //用户待收数据获取
        $user_unreceived_total = UserAccountService::getInstance()->getUserUnreceivedTotal($user_id);
        //待收本金
        $data['account']['investing'] = $user_unreceived_total['data']['user_unreceived_capital'];
        //待收利息
        $tpl_data['user_unreceived_interest'] = $user_unreceived_total['data']['user_unreceived_interest'];
        //待收加息
        $tpl_data['user_unreceived_rewardInterest'] = $user_unreceived_total['data']['user_unreceived_rewardInterest'];
        //可用金额和冻结金额
        $data['account']['use_money'] = $accountInfo?$accountInfo['use_money']:0;
        $data['account']['no_use_money'] = $accountInfo?$accountInfo['no_use_money']:0;
        //现有资产 = 待收本金+待收利息+待收加息+可用余额+冻结金额+冻结奖励金额
        $data['account']['assets'] = $user_unreceived_total['data']['user_unreceived_capital'] +
            $user_unreceived_total['data']['user_unreceived_interest'] +
            $user_unreceived_total['data']['user_unreceived_rewardInterest'] +
            $data['account']['use_money'] +
            $data['account']['no_use_money'];
        //净赚取【现有资产+累计提现-累计充值】
        $data['account']['earn'] = $data['account']['assets'] + $data['account']['cash'] - $data['account']['recharge'];
        $now_time = time();
        //优惠券数量
        $_coupon_sql = "select count(*) as c from dw_coupon where user_id = {$user_id} and status in (0,1) AND expire_time >= " . $now_time . " AND  begin_time <= " . $now_time;
        $data['account']["coupons"] = Yii::app()->dwdb->createCommand($_coupon_sql)->queryScalar()?:0;
        //积分数量
        $_credit_sql = "select value from dw_credit where user_id = {$user_id} ";
        $data['account']["credit"] = Yii::app()->dwdb->createCommand($_credit_sql)->queryScalar()?:0;
        $_coins_sql = "select extcredits2 from itz_common_member_count where uid = ".$user->ucenter_uid;
        $data['account']["coins"] = Yii::app()->bbs->createCommand($_coins_sql)->queryScalar()?:0;
        //银行信息
        $_bank_sql = "select sc.*,b.bank_name from itz_safe_card sc left join itz_bank b on sc.bank_id = b.bank_id where sc.user_id = {$user_id} and status = 2";
        $bankInfo= Yii::app()->dwdb->createCommand($_bank_sql)->queryRow();
        if($bankInfo){
            $data['bank_account'] =FunctionUtil::MaskCardID($bankInfo['card_number'],3);
            $data['bank_name'] = $bankInfo['bank_name'];
            $data['bank_status'] = 1;
        }
        $_xwopen_sql = "SELECT * From itz_open_account_record where user_id = {$user_id} order by create_time desc";
        $xwopenInfo= Yii::app()->dwdb->createCommand($_xwopen_sql)->queryRow();
        if($xwopenInfo){
            $data['xw_time'] = $xwopenInfo['last_time']?date("Y-m-d H:i:s",$xwopenInfo['last_time']):date("Y-m-d H:i:s",$xwopenInfo['create_time']);
        }
        $_recharge_sql = "SELECT * From dw_account_recharge where user_id = {$user_id} and status in (0,1,2) order by  field(`status`,1,2,0),addtime";
        $rechargeInfo= Yii::app()->dwdb->createCommand($_recharge_sql)->queryRow();
        if($rechargeInfo){
            $data['recharge_time'] = date("Y-m-d H:i:s",$rechargeInfo['addtime']);
        }

        $_first_tender_sql = "SELECT * From dw_borrow_tender where user_id = {$user_id} order by addtime";
        $_last_tender_sql = "SELECT * From dw_borrow_tender where user_id = {$user_id} order by addtime desc";
        $firstTenderInfo= Yii::app()->dwdb->createCommand($_first_tender_sql)->queryRow();
        if($firstTenderInfo){
            $data['tender_time'] = date("Y-m-d H:i:s",$firstTenderInfo['addtime']);
        }
        $lastTenderInfo= Yii::app()->dwdb->createCommand($_last_tender_sql)->queryRow();
        if($lastTenderInfo){
            $data['last_tender_time'] = date("Y-m-d H:i:s",$lastTenderInfo['addtime']);
        }

        $returnResult["data"]=$data;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        
        return $returnResult;
    }

    /**
     * 获取用户基本信息
     */
    public function getUserBase($user_id )
    {
        $user_id =intval($user_id);
        Yii::log(__FUNCTION__ . print_r(func_get_args(), true), 'debug');
        $returnResult = array(
            'code' => 1, 'info' => "用户不存在！", 'data' => []
        );
        $user = User::model()->findByPk($user_id);
        if(empty($user)){
            return $returnResult;
        }
        $data = [
            "user_id"=>$user->user_id,
            "email"=>$user->email?:'-', 
            "reg_date"=>date("Y-m-d",$user->addtime),  //注册日期
            "ucenter_uid"=>$user->ucenter_uid, //论坛ID
            "ucenter_name"=>"-",  //论坛昵称
            "urgent"=>[ //紧急联系人
                "name"=>"-",
                "phone"=>"",
                "relationship"=>"",
            ],
            "reg_time"=>date("Y-m-d H:i:s",$user->addtime),  //注册时间
            "reg_device"=>$user->reg_device, //注册设备  
            "reg_platform"=>'-', //注册平台
            "user_src"=>"-", //爱投资用户来源
            "invite_userid"=>$user->invite_userid,  //推荐人id（非邀请用户值为0）
            "user_status"=>$user->status,   //用户状态：0为正常，1为注销，2为冻结
            "weixin"=>[
                'openid'=>"-", //微信openID
                'name'=>"-", //微信昵称
                'bind_time'=>"-", //绑定时间
            ],
            "sex"=>$user->sex==1?"男":($user->sex==2?"女":"-"),   //性别
            "birthday"=>$user->birthday?date("Y-m-d",$user->birthday):"-",  //用户生日
            "tel"=>$user->tel?:"-",//家庭电话  
            "address"=>$user->address?:"-",  //家庭地址
            "postcode"=>$user->postcode?:"-", //邮编
            "high_edu"=>$user->high_edu?:"-",  //最高学历
            "school"=>$user->school?:"-", //毕业院校
            "education_study"=>"-",  //专业
            "merriage_status"=>$user->merriage_status==1?"未婚":($user->merriage_status==2?"已婚":"-"), //婚姻状况
            // "child":0 //生育状况未找到
            // "company":"爱投资" //公司名称未找到
            "business_type"=>$user->business_type?:"-", //公司行业
            "business_scale"=>$user->business_scale?:"-", //公司规模
            "user_position" =>$user->user_position?:"-", //职位
            "month_income"=> $user->month_income?:"-", //月收入
            'inviter' => [
                'realname'=>"",
                'user_id'=>"",
                'user_type'=>"",
                'reg_date'=>"",
            ],
            "invite_time"=>date("Y-m-d H:i:s",$user->addtime),  
        ];
        $data["reg_platform"] = strpos($this->user_src,'isee_') === 0?"安见":"爱投资";
        $_bbs_sql = "SELECT customstatus from itz_common_member_field_forum where `uid` =".$user->ucenter_uid;
        $data["ucenter_name"] = Yii::app()->bbs->createCommand($_bbs_sql)->queryScalar();
        $_weixin_sql = "SELECT * from dw_oauth_relationships where user_id = $user_id and open_type=3 and `status` in (1,2)";
        $weixin = Yii::app()->dwdb->createCommand($_weixin_sql)->queryRow();
        if($weixin){
            $data["weixin"]['openid'] = $weixin->openid;
            $data["weixin"]['name'] = $weixin->name;
            $data["weixin"]['bind_time'] = $weixin->bind_time;
        }
        if($user->invite_userid){
            $inviter = User::model()->findByPk($user->invite_userid);
            if($inviter){
                $data["inviter"]['realname'] = $inviter->realname?mb_substr($inviter->realname, 0, 1, 'utf-8').'**':"-";
                $data["inviter"]['user_id'] = $inviter->user_id;
                $data["inviter"]['user_type'] = $inviter->isinvested?7:($inviter->xw_open==2?3:1);
                $data["inviter"]['reg_date'] = date("Y-m-d",$inviter->addtime);
            }
        }
        $returnResult["data"]=$data;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }

    /**
     * 获取用户投资信息
     */
    public function getUserInvest($user_id,$filter=[])
    {
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $filter = $this->filter($filter);
        $criteria = new CDbCriteria;
        if($filter["status"]){
            $criteria->addCondition("t.status = {$filter["status"]}");
        }
        if($filter["date"]){
            if(is_array($filter["date"])){
                $criteria->addBetweenCondition("t.addtime",$filter["date"][0],$filter["date"][1]);
            } 
        }
        $count = BorrowTender::model()->countByAttributes(['user_id'=>$user_id],$criteria);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $criteria->order = "t.addtime desc";
        $tenders = BorrowTender::model()->with('borrowInfo')->findAllByAttributes(['user_id'=>$user_id],$criteria);
        $list = [];
        foreach($tenders as $tender){
            $l = [
                "tender_id"=>$tender->id,
                "name" => $tender->borrowInfo->name?:"",
                "apr" => $tender->borrowInfo->apr."%",
                "account" => number_format($tender->account,2),
                "addtime" => date("Y-m-d H:i:s",$tender->addtime),
                "coupon" => "",
                "next_interest" => 0.00,
                "paid_interest" => 0.00,
                "unpay_interest" => 0.00,
                "paid_times" => 0,
                "pay_times" => 0,
                "repaytime" => $tender->borrowInfo->repayment_time?date("Y-m-d",$tender->borrowInfo->repayment_time):"-",
                "status" =>$tender->status,
            ];
            if($tender->borrowInfo->type == 3100) {
                $l["apr"] = "动态";
            }
            if($tender->coupon_type){
                if($tender->coupon_type == 1 || $tender->coupon_type == 2){
                    $l['coupon'] = "使用抵现券 ".$tender->coupon_value."元";
                }else{
                    $l['coupon'] = "使用加息券 ".$tender->coupon_value."%";
                }
            }
            $time = 9999999999;
            $collections = BorrowCollection::model()->findAllByAttributes(["tender_id"=>$tender->id]);
            foreach($collections as $c){
                if($c->status == 0 && $c->type <> 5 && $c->interest > 0){
                    if($c->repay_time<$time){
                        $l["next_interest"] = $c->interest;
                        $time = $c->repay_time;
                    }
                    $l["unpay_interest"] += $c->interest;
                    $l["pay_times"] += 1;
                }elseif($c->status == 1 && $c->type <> 5 && $c->interest > 0){
                    $l["paid_interest"] += $c->interest;
                    $l["paid_times"] += 1;
                    $l["pay_times"] += 1;
                }
            }
            $l["next_interest"] = number_format($l["next_interest"],2);
            $l["paid_interest"] = number_format($l["paid_interest"],2);
            $l["unpay_interest"] = number_format($l["unpay_interest"],2);
            $list[] = $l;
        }
        $returnResult["data"]["listInfo"]=$list;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }

    /**
     * 获取用户债权信息
     */
    public function getUserDebt($user_id,$filter=[])
    {
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $filter = $this->filter($filter);
        $criteria = new CDbCriteria;
        if($filter["status"]){
            $criteria->addCondition("t.status = {$filter["status"]}");
        }
        if($filter["date"]){
            if(is_array($filter["date"])){
                $criteria->addBetweenCondition("t.addtime",$filter["date"][0],$filter["date"][1]);
            }
        }
        $count = Debt::model()->countByAttributes(['user_id'=>$user_id],$criteria);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $criteria->order = "t.addtime desc";
        $debts = Debt::model()->with('borrowInfo')->findAllByAttributes(['user_id'=>$user_id],$criteria);
        $list = [];
        $debt_str = [1=>"新建转让",2=>"转让成功",3=>"取消转让",4=>"过期"];
        foreach($debts as $debt){
            $l = [
                "debt_id"=>$debt->id,
                "name" => $debt->borrowInfo->name,
                "apr" => $debt->borrowInfo->apr."%",
                "money" => number_format($debt->money,2),
                "addtime" => date("Y-m-d H:i:s",$debt->addtime),
                "sold_money" => number_format($debt->sold_money,2),
                "discount_money" => number_format($debt->discount_money,2),
                "status" => $debt->status,
                "status_str" => $debt_str[$debt->status],
            ];
            $list[] = $l;
        }
        $returnResult["data"]["listInfo"]=$list;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }

    /**
     * 获取用户债权详情
     */
    public function getUserDebtDetail($debt_id)
    {
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $count = DebtTender::model()->countByAttributes(['debt_id'=>$debt_id]);
        $returnResult["data"]["listTotal"] = intval($count);
        if(empty($count)){
            return $returnResult;
        }
        $debtTenders = DebtTender::model()->with("userInfo")->findAllByAttributes(['debt_id'=>$debt_id]);
        $list= [];
        foreach($debtTenders as $t){
            $list[] = [
                "realname" => $t->userInfo->realname?mb_substr($t->userInfo->realname, 0, 1, 'utf-8').'**':"-",
                "phone" => $t->userInfo->phone?FunctionUtil::MaskTel($t->userInfo->phone):"-",
                "account" => number_format($t->account,2),
                "discount_money" => number_format($t->account - $t->action_money,2),
                "addtime" => date("Y-m-d",$t->addtime),
            ];
        }
        $returnResult["data"]["listInfo"]=$list;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }


    /**
     * 获取用户资金流水
     */
    public function getUserAccountLog($user_id,$filter=[])
    {
        switch ($filter["type"]) {
    		case '1': //充值
    			$type_name = 'recharge';
    			break;
    		case '2': //提现
    			$type_name = 'cash';
    			break;
    		case '3': //投资
    			$type_name = 'invest';
    			break;
    		case '4': //回息  改版
    			$type_name = 'interest';
    			break;
    		case '5': //回本  改版
    			$type_name = 'capital_expire';
    			break;
    		case '6': //债权
    			$type_name = 'capital_debt';
    			break;
    		case '99': //其他
    			$type_name = 'other';
    			break;
    		default:
    			$type_name = 'all';
    			break;
        }
        $page = $filter["page"]?:1;
        $start_time = $end_time = 0;
        if($filter["date"]){
            if(is_array($filter["date"])){
                $start_time = $filter["date"][0];
                $end_time = $filter["date"][1];
            } else {
                $start_time = $filter["date"];
                $endtime = $filter["date"] + 86400;
            }
        }
        $list = [];
        $api_result = NewUserTrade::api()->queryLogs($user_id, $page, $type_name, $start_time, $end_time);
    	if($api_result['logs']){
    		foreach ($api_result['logs'] as $key=>$val){
    			$l = [
                    "type" => $val->logTypeTitle,
                    "borrow_name" => $val->logTypeName,
                    "money" => $val->money,
                    "use_money" => $val->use_money,
                    "no_use_money" => $val->no_use_money,
                    "collection" => $val->collection,
                    "fee" => "-",
                    "addtime" => date("Y-m-d H:i:s",$val->addtime),
                    "datetime" => date("Y-m-d H:i:s",$val->addtime)
                ];
                if($val->log_type == "cash_success"){
                    $related_id = $val->related_id?:(array_reverse(explode("_",$val->transid))[0]);
                    $cash = AccountCash::model()->findByPk($related_id);
                    if($cash){
                        $l["fee"] = $cash->fee;
                        $l["datetime"] = date("Y-m-d H:i:s",$cash->addtime);
                    }
                }
                $list[] = $l;
    		}
    	}
    	$returnResult['data']['listTotal'] = intval($api_result['pager']['itemCount']);
        $returnResult["data"]["listInfo"]= $list;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }

    /**
     * 获取用户优惠券
     */
    public function getUserCoupon($user_id,$filter=[])
    {
        $user_id = intval($user_id);
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $filter = $this->filter($filter);
        $criteria = new CDbCriteria;
        $status_remark = $filter["status"];
        $now_time = time();
        //优惠券使用状态条件
        if ($status_remark == 1) {//1可使用
            $criteria->addCondition("status in (0,1) AND expire_time >= " . $now_time . " AND  begin_time <= " . $now_time);
        } elseif ($status_remark == 2) {//2已使用
            $criteria->addCondition("status = 2 ");
        } elseif ($status_remark == 3) {//3已过期(个人中心仅显示两个月内过期的)
        	$two_months = strtotime("-2 months", $now_time);
            $criteria->addCondition("expire_time>{$two_months} and expire_time <  " . $now_time . "  and status in (0,1,3) ");
        } elseif ($status_remark == 4) {//4未使用（未生效&可使用）
            $criteria->addCondition("((status in (0,1) AND expire_time >= " . $now_time . " AND  begin_time <= " . $now_time . ") or ( begin_time > " . $now_time . "))");
        }
    
        if($filter["date"]){
            if(is_array($filter["date"])){
                $criteria->addBetweenCondition("addtime",$filter["date"][0],$filter["date"][1]);
            } 
        }
        $count = Coupon::model()->countByAttributes(['user_id'=>$user_id],$criteria);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $criteria->order = "t.addtime desc";
        $coupons = Coupon::model()->findAllByAttributes(['user_id'=>$user_id],$criteria);
        $list = [];
        $status_str = [0=>"未使用",1=>"未使用",2=>"已使用",3=>"已过期"];
        foreach($coupons as $coupon){
            $l = [
                "type"=>$coupon->type,
                "type_str"=> in_array($coupon->type,[3,4])?"加息券":"抵现券", //类型
                "amount"=>$coupon->amount,  //面额
                "status"=>$coupon->status,  
                "status_str"=>$status_str[$coupon->status],  //状态
                "expire_time"=>date("Y-m-d",$coupon->expire_time - 1), //有效期
                "addtime"=>date("Y-m-d H:i:s",$coupon->addtime), //获取时间
                "use_time "=>$coupon->use_time?date("Y-m-d H:i:s",$coupon->use_time):"-",  //使用时间
                "remark"=>$coupon->remark, //获取途径
                "limit"=>"全部项目可用",   //使用范围
                "least_invest_amount"=>$coupon->least_invest_amount,   //最小可用金额
                "interest_max_money"=>$coupon->interest_max_money,  //最高加息本金
            ];
            if($coupon->borrow_type>10000000){
                $_coupon_type_sql = "select * from itz_coupon_restrict where borrow_type = ".$coupon->borrow_type;
                $types = Yii::app()->dwdb->createCommand($_coupon_type_sql)->queryAll();
                $limit = "";
                foreach($types as $type){
                    if($type->restrict_type == 1){
                        $limit .= Yii::app()->linkconfig['borrow_type'][$type->type_id]."可用 ";
                    }else{
                        $borrow = Borrow::model()->findByPk($type->type_id);
                        if($borrow){
                            $limit .= $borrow["name"].($type->restrict_type==2?"不可用 ":"可用 ");
                        }
                    }
                }
                $l["limit"] = $limit;
            }
            $list[] = $l;
        }
        $returnResult["data"]["listInfo"]=$list;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }

    /**
     * 获取用户积分变动
     */
    public function getUserCredit($user_id,$filter=[])
    {
        $user_id = intval($user_id);
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $filter = $this->filter($filter);
        $connection = Yii::app()->dwdb;
        // 获取数据
        $sql = "SELECT a.id,a.user_id,a.type_id,a.remark,a.op,a.credit_nid,a.value,a.addtime, b.nid,b.name
            FROM dw_credit_log a
            LEFT JOIN dw_credit_type b ON a.type_id = b.id
            WHERE a.user_id = $user_id ";
        $count_sql = "SELECT count(*) as c
            FROM dw_credit_log a
            LEFT JOIN dw_credit_type b ON a.type_id = b.id
            WHERE a.user_id = $user_id ";
        if (empty($filter["type"])) {
            $condition = '';
        } else {
            switch ($filter["type"]) {
                case '1':
                    $condition = " AND a.credit_nid in ( 'sign_day','sign_week','sign_month','sign_activity','sign_week_times')";
                    break;
                case '2':
                    $condition = " AND b.nid = 'bbs_gold_exchange' ";
                    break;
                case '3':
                    $condition = " AND b.nid = 'reinvest_reward' ";
                    break;
                case '4':
                    $condition = " AND b.nid = 'tender_finished' ";
                    break;
                case '5':
                    $condition = " AND b.nid in ('email','realname','phone','risktest')";
                    break;
                case '6':
                    $condition = " AND b.nid = 'daily_questionnaire'";
                    break;
                case '7':
                    $condition = " AND a.op = 2";
                    break;
            }
        }
        if($filter["date"]){
            if(is_array($filter["date"])){
                $condition .= " AND a.addtime between {$filter["date"][0]} and {$filter["date"][1]}";
            }
        }
        $count_sql .= $condition;
        
        $count = $connection->createCommand($count_sql)->queryScalar();
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $condition .= " order by a.id desc";
        $condition .= " limit $offset,$limit";
        $sql .= $condition;
        $list = $connection->createCommand($sql)->queryAll();
        $listInfo = [];
        foreach($list as $l){
            $listInfo[] = [
                "type" => $l['nid'],
                "type_str" => $l['name'],
                "value" => ($l['op']==1?"+":"-").$l['value'],
                "addtime"=> date("Y-m-d H:i:s",$l['addtime']),
                "remark" => $l['remark'],
            ];
        }
        $returnResult["data"]["listInfo"]=$listInfo;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }

    /**
     * 获取用户外呼记录
     */
    public function getUserCalloutRecord($user_id,$filter=[])
    {
        $user_id = intval($user_id);
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $filter = $this->filter($filter);
        $criteria = new CDbCriteria;
        if($filter["addtime"]){
            if(is_array($filter["addtime"])){
                $criteria->addBetweenCondition("addtime",$filter["addtime"][0],$filter["addtime"][1]);
            } 
        }
        $crmUser = CrmUser::model()->findByPk($user_id);
        if($crmUser && $crmUser->admin_id){
            $criteria->addCondition("t.admin_id = ".$crmUser->admin_id);
        }
        if($filter["status"]){
            $criteria->addCondition("t.status = {$filter["status"]}");
        }
        $count = CrmCallRecord::model()->countByAttributes(["user_id"=>$user_id],$criteria);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $records = CrmCallRecord::model()->findAllByAttributes(["user_id"=>$user_id],$criteria);
        $list = [];
        $status_str = [
            "0" => "-",
            "1" => "呼出",
            "2" => "接通",
            "3" => "未接",
            "4" => "拒接",
        ];
        $level_str = [
            "0" => "-",
            "1" => "没兴趣",
            "2" => "近期不考虑",
            "3" => "有意向",
        ];
        foreach($records as $key=>$value){
            $l = [
                "call_id" => $value->call_id,
                "admin_id" => $value->admin_id,
                "admin_name" => $this->getAdminNameByID($value->admin_id),
                "status" => $status_str[$value->status],
                "user_level" => "-",
                "user_label" => [],
                "addtime" => date("Y-m-d H:i:s",$value->addtime),
                "call_time" => date("Y-m-d H:i:s",$value->start_time),
                "remark" => $value->remark?:"",
                "record_url" => "",
            ];
            $record_info = CallService::getInstance()->getDownRecord(["call_id"=>$value->call_id]);
            if($record_info["code"] == 0){
                $l["record_url"] = $record_info['data']['path'];
            }
            $crm_user = CrmUser::model()->findByPk($value->user_id);
            if($crm_user){
                $l["user_level"] = $level_str[$crm_user->user_level?:0]?:"-";
                $l["user_label"] = $crm_user->user_label?$this->getUserLabel($crm_user->user_label):[];
            }
            $list[] = $l;
        }
        $returnResult["data"]["listInfo"]=$list;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }


    /**
     * 获取用户呼入记录
     */
    public function getUserCallinRecord($user_id,$filter=[])
    {
        $user_id = intval($user_id);
        $userInfo = User::model()->findByPk($user_id);
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $filter = $this->filter($filter);
        $criteria = new CDbCriteria;
        $criteria->addCondition("type = 2");
        if($filter["addtime"]){
            if(is_array($filter["addtime"])){
                $criteria->addBetweenCondition("addtime",$filter["addtime"][0],$filter["addtime"][1]);
            } 
        }
        if($filter["status"]){
            $criteria->addCondition("t.call_status = {$filter["status"]}");
        }
        $count = CcsCallRecord::model()->countByAttributes(["user_phone"=>$userInfo->phone],$criteria);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $records = CcsCallRecord::model()->findAllByAttributes(["user_id"=>$user_id],$criteria);
        $list = [];
        foreach($records as $key=>$value){
            $l = [
                "call_id" => $value->call_id,
                "admin_id" => $value->admin_id,
                "admin_name" => $this->getAdminNameByID($value->admin_id),
                "status" => $status_str[$value->call_status],
                "addtime" => date("Y-m-d H:i:s",$value->addtime),
                "start_time" => date("Y-m-d H:i:s",$value->start_time),
                "call_time" => $value->call_time,
                "record_url" => $value->record_url,
                "remark" => $value->remark?:"",
            ];
            $list[] = $l;
        }
        $returnResult["data"]["listInfo"]=$list;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }

    /**
     * 分配用户
     */
    public function allotUser($admin_id="",$user_ids = [],$allot_type="allot")
    {
        $admin = CrmAdmin::model()->findByAttributes(["admin_id"=>$admin_id,"type"=>[4,5]]);
        $returnResult = array(
            'code' => 1, 'info' => "分配失败", 'data' => []
        );
        if(empty($admin) || empty($user_ids) || !is_array($user_ids)){
            return $returnResult;
        }
        $admin_name = $admin->name;
        $now = time();
        $crm_users = CrmUser::model()->findAllByPk($user_ids);
        $alloted = [];
        $success = 0;
        try{
            foreach($crm_users as $crm_user){
                $alloted[] = $crm_user->user_id;
                if($crm_user->admin_id != $admin_id){//需要进行分配
                    $crm_user_log = new CrmUserLog();
                    $crm_user_log->log_type = $allot_type;
                    $crm_user_log->admin_id = $admin_id;
                    $crm_user_log->user_id = $crm_user->user_id;
                    $crm_user_log->addtime = $now;
                    $res = $crm_user_log->save();
                    if($res){
                        $crm_user->admin_id = $admin_id;
                        $crm_user->is_allot = 1;
                        $crm_user->allot_time = $now;
                        $crm_user->is_call = 0;
                        $crm_user->call_time = 0;
                        $crm_user->updatetime = $now;
                        $res2 = $crm_user->save();
                        if($res2){
                            CrmReportUser::model()->updateAll(["is_allot"=>1],"user_id=:user_id",[":user_id"=>$crm_user->user_id]);
                            $success ++;
                        }
                    }
                }
            }
            $un_allot = array_diff($user_ids,$alloted);
            foreach($un_allot as $user_id){ //表中没有的用户添加进来
                $user = User::model()->findByPk($user_id);
                if($user){
                    $crm_user_log = new CrmUserLog();
                    $crm_user_log->log_type = $allot_type;
                    $crm_user_log->admin_id = $admin_id;
                    $crm_user_log->user_id = $user_id;
                    $crm_user_log->addtime = $now;
                    $res = $crm_user_log->save();
                    if($res){
                        $crmUser = new CrmUser();
                        $crmUser->user_id = $user_id;
                        $crmUser->addtime = $now;
                        $crmUser->user_type = $user->isinvested?7:($user->xw_open==2?3:1);
                        $crmUser->admin_id = $admin_id;
                        $crmUser->is_allot = 1;
                        $crmUser->allot_time = $now;
                        $res2 = $crmUser->save();
                        if($res2){
                            CrmReportUser::model()->updateAll(["is_allot"=>1],"user_id=:user_id",[":user_id"=>$user_id]);
                            $success++;
                        }
                    }
                }
            }
            $returnResult["code"]=0;
            $returnResult["info"]="成功给{$admin_name}分配{$success} 用户！";
            return $returnResult;
        }catch(Exception $e){
            return $returnResult;
        }

    }

    /**
     * 批量导入用户
     */
    public function importReport($filter)
    {
        $filter = $this->filter($filter);
        $success = 0;
        $type = $filter["type"]?:1;
        $user_type = $type==2?2:($type==3?4:0);
        $remark = $filter["remark"]?:"";
        $users = $filter["users"]?:[];
        if(empty($users)){
            return false;
        }
        $now = time();
        $report = new CrmReport();
        $report->type = $type;
        $report->remark = $remark;
        $report->status = 1;
        $report->addtime = $now;
        $res = $report->save();
        
        if(!$res){
            return false;
        }
        $report_id = $report->id;
        foreach($users as $user) {
            $crmUser = CrmUser::model()->findByAttributes(["user_id"=>$user["user_id"]]);
            if(empty($crmUser)){
                $userInfo = User::model()->findByPk($user["user_id"]);
                if($userInfo && $userInfo->status == 0 && $userInfo->phone_status == 1){
                    $crmUser = new CrmUser();
                    $crmUser->user_id = $user["user_id"];
                    $crmUser->addtime = $now;
                    $user_type2 = $userInfo->isinvested?7:($userInfo->xw_open==2?3:1);
                    $crmUser->user_type = max($user_type2,$user_type);
                    $crmUser->admin_id = 0;
                    $crmUser->is_allot = 0;
                    $crmUser->allot_time = $now;
                    $res2 = $crmUser->save();
                    if($res2){
                        $success++;
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }
            if($user['user_type'] && $user['user_type']>$crmUser->user_type){
                $crmUser->user_type = $user['user_type'];
                $crmUser->save();
            }
            foreach($user["reasons"] as $reason){
                $crr = new CrmReportReason();
                $crr->user_id = $reason["user_id"];
                $crr->realname = $reason["realname"];
                $crr->remark = $reason["remark"];
                $crr->bank = $reason["bank"];
                $crr->account = $reason["account"];
                $crr->describe = $reason["describe"];
                $crr->datetime = $reason["datetime"]?:$now;
                $crr->addtime = $now;
                $crr->save();
            }
            $crmReportUser = new CrmReportUser();
            $crmReportUser->user_id = $user["user_id"];
            $crmReportUser->report_id = $report_id;
            $crmReportUser->is_allot = $crmUser->is_allot;
            $crmReportUser->addtime = $now;
            try{
                $res3 = $crmReportUser->save();
                if($res3){
                    $success ++;
                }
            }catch(Exception $e){
                continue;
            }
        }
        if(empty($success)){
            $report->delete();
        }
        return $success;
    }

    /**
     * 删除批次
     */
    public function deleteReport($report_id)
    {
        $report_id = intval($report_id);
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $report = CrmReport::model()->findByPk($report_id);
        if($report) {
            if($report->status!= 1){
                $returnResult["info"] = "该组已分配，无法删除！";
                return $returnResult;
            }
            $report->delete();
            $returnResult["code"]=0;
            $returnResult["info"]="删除成功！";
        }
        return $returnResult;
    }

    /**
     * 批量分配
     */
    public function allotReport($report_id,$allot_array = [])
    {
        $report_id = intval($report_id);
        $returnResult = array(
            'code' => 1, 'info' => "", 'data' => []
        );
        $report = CrmReport::model()->findByPk($report_id);
        if($report) {
            $reportUser = CrmReportUser::model()->findAllByAttributes(["report_id"=>$report_id,"is_allot"=>0]);
            $count = count($reportUser);
            $now = 0;
            foreach($allot_array as $admin_id=>$amount)
            {
                $admin_name = $this->getAdminNameByID($admin_id);
                if($admin_name != "-"){
                    $user_ids = [];
                    for($i = $now;$i<min($now+$amount,$count); $i ++){
                        $user_ids[] = $reportUser[$i]["user_id"];
                    }
                    $res = $this->allotUser($admin_id,$user_ids,"excel_allot");
                    if($res["code"] == 0){
                        $returnResult["info"] .= $res["info"]."\n";
                        $returnResult["code"] = 0;
                    }
                    $now+=$amount;
                }
            }
            if($returnResult["code"] == 0){
                if($now >= $count){
                    $report->status=3;
                }else{
                    $report->status=2;
                }
                $report->save();
            }else{
                $returnResult["info"] = "分配失败！";
            }
        }else{
            $returnResult["info"] = "分配失败！";
        }
        return $returnResult;
    }

    /**
     * 获取全部标签
     */
    public function getAllTags($user_id="")
    {
        $labels = $this->getUserLabel("",$user_id);
        return array('code' => 0, 'info' => "获取成功！", 'data' => $labels);
    }

    /**
     * 通话结果记录
     */
    public function feedBack($filter,$admin_id=0)
    {
        $filter = $this->filter($filter);
        $returnResult = array(
            'code' => 1, 'info' => "保存失败！", 'data' => []
        );
        if(empty($filter['callid'])|| empty($filter['status']) || empty($filter['user_id'])){
            return $returnResult;
        }
        $callRecord = CrmCallRecord::model()->findByAttributes(["call_id"=>$filter['callid']]);
        if(empty($callRecord)){
            return $returnResult;
        }
        $callRecord->status = $filter['status'];
        $callRecord->remark = $filter['remark'];
        $callRecord->quaere_type = $filter['quaere_type'];
        $res = $callRecord->save();
        if(!$res){
            return $returnResult;
        }
        
        $user_id = $callRecord->user_id?:$filter['user_id'];
        $crmUser = CrmUser::model()->findByPk($user_id);
        if(empty($crmUser)){
            $userInfo = User::model()->findByPk($user_id);
            if($userInfo){
                $crmUser = new CrmUser();
                $crmUser->user_id = $user_id;
                $crmUser->addtime = $now;
                $crmUser->user_type = $userInfo->isinvested?7:($user->xw_open==2?3:1);
                $crmUser->admin_id = 0;
                $crm_user->is_allot = 0;
                $crm_user->allot_time = 0;
                $res2 = $crmUser->save();
                if(!$res2){
                    return $returnResult;
                } 
            } else{
                return $returnResult;
            }
        }
        $crmUser->user_level = $filter['user_level']?:0;
        $crmUser->user_label = implode(",",$filter['user_label']?:[]);
        if($admin_id && $crmUser->admin_id == $admin_id && $crmUser->is_call != 1 ){
            $crmUser->is_call = in_array($filter['status'],[3,4])?2:1;
            $crmUser->call_time = $callRecord->addtime;
        }
        $res = $crmUser->save();
        
        if($res){
            $returnResult["code"] = 0;
            $returnResult["info"] = "提交成功";
        }
        return $returnResult;
    }


    public function getRepayCalendar($user_id,$year,$month)
    {
        $returnResult = array(
            'code' => 1, 'info' => "没有相关数据", 'data' => []
        );
        $calendar_data = $this->theSameMonthRepaymentPlan($user_id, $year, $month);
        if(empty($calendar_data)){
            return $returnResult;
        }
        $data = array('monthMoney' => 0, 'day' => array());
        //组合数据
        foreach ($calendar_data as $k=>$v) {
            $data['monthMoney'] += $v['expectedMoney'];
            if($v['actualTime'] > 0){
                $day=$v['actualTime'];
            }else{
                $day=$v['expectedTime'];
            }
            if(!isset($data['day'][$day])){
                $data['day'][$day]['countMoney'] = $v['expectedMoney'];
                $data['day'][$day]['start'] = $day;
                $data['day'][$day]['end'] = $day;
            }else{
                $data['day'][$day]['countMoney'] += $v['expectedMoney'];
            }
            $data['day'][$day]['detail'][] = $v;
        }
        $data['day'] = array_values($data['day']);
        $returnResult["data"]=$data;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }


    public function theSameMonthRepaymentPlan($uid, $year, $month)
    {
        //$data为返回出去的数据
        $data = array();
        //参数校验
        if($uid == '' || $year == '' || $month == ''){
            return $data;
        }

        $startTime = strtotime(date($year.'-'.$month."-1 0:00:00"));
        $month_days = $this->getAllDay($year, $month);//获得这个月一共有多少天
        $endTime = strtotime(date($year.'-'.$month.'-'.$month_days.' 23:59:59'));
        //还款数据获取
        $cdb = new CDbCriteria();
        $cdb->select = 'id,borrow_id,interest,capital,repay_yestime,repay_time,type,status,tender_id ';
        $cdb->condition = "user_id=:uid AND ((repay_yestime>=:startTime AND repay_yestime<=:endTime) OR (repay_time>=:startTime AND repay_time<=:endTime)) AND `status` in (0,1,2,3,5,8)";
        $cdb->params = array(':uid'=>$uid, ':startTime'=>$startTime, ':endTime'=>$endTime);
        $collection = BorrowCollection::model()->findAll($cdb);

        //无符合条件的还款数据
        if(count($collection) == 0){
            return $data;
        }
        //收集还款数据中的项目ID
        $borrow_ids = array();
        $pre_back = array();
        foreach($collection as $v){
            if($v->interest == 0 && $v->capital == 0 || ($v->repay_yestime>0 && ($v->repay_yestime > $endTime || $v->repay_yestime < $startTime))){
                continue;//如果都是0，跳过这次循环
            }
            $borrow = Borrow::model()->findByPk($v->borrow_id);
            if($borrow->type == 3000){
                $case = array(
                    'name' => '',
                );
            }else{
                $case = array(
                    'name' => '',
                );
            }
            $borrow_ids[] = $case['borrow_id'] = $v->borrow_id;
            $case['tender_id'] = $v->tender_id;
            if ($v->type == 5) {
                //加息收益还款金额
                $case['expectedMoney'] = $v->interest;
                $case['type'] = '加息收益';
                $case['type_status'] = 4;
            } elseif ($v->type == 7) {
                //新手奖励
                $case['expectedMoney'] = $v->interest;
                $case['type'] = '新手奖励';
                $case['type_status'] = 4;
            } elseif ($v->type == 8) {
                //平台奖励
                $case['expectedMoney'] = $v->interest;
                $case['type'] = '平台奖励';
                $case['type_status'] = 4;
            } else {
                if ($v->interest>0 && $v->capital>0) {
                    $case['type'] = '等额本息';
                    $case['type_status'] = 3;
                } elseif ($v->interest > 0) {
                    $case['type'] = '收益';
                    $case['type_status'] = 2;
                } elseif ($v->capital > 0) {
                    $case['type'] = '回收本金';
                    $case['type_status'] = 1;
                } else {//如果interest和capital都为0，是不会出现在collection表中的
                    $case['type'] = 'error';
                    Yii::log("dw_borrow_collection error user_id:$uid  id:$v->id  date('Y-m-d H:i:s')", 'error', 'theSameMonthRepaymentPlan');
                }
                //还款金额
                $case['expectedMoney'] = ($v->interest + $v->capital);
            }

            $case['interest']=$v->interest;
            $case['capital']=$v->capital;
            $case['pre'] = 0;
            $case['expectedTime'] = date('Y-m-d',$v->repay_time);
            switch($v->status){
                case '0':
                case '2':
                case '3':
                    $case['actualTime']='-';
                    $case['static']='未支付';
                    $case['status']=2;
                    break;
                case '5':
                    $case['actualTime']=$case['actualTime']='-';
                    $case['static']='未支付';
                    $case['status']=2;
                    break;
                case '1':
                case '8':
                    $case['actualTime']=date('Y-m-d',$v->repay_yestime);
                    $case['static']='已支付';
                    $case['status']=1;
                    break;
            }
            //新加的字段，用于展示提示：智选项目部分提前还款。 2017/10/23 by 陈滕飞
            // 2017/12/15 by 陈滕飞
            if($v->type == 6) { 
                $pre_back[] = $case;
                continue;
            }
            $data[]=$case;
        }

        $pre_array = [];
        foreach($pre_back as $p){
            $flag = 0;
            foreach($data as &$d){
                if($d["tender_id"] == $p["tender_id"] && $d["expectedTime"] == $p["expectedTime"] && $d['capital'] > 0.01) {
                    $flag = 1;
                    $d["pre"] += $p['capital'];
                    $d['expectedMoney'] += $p['capital'];
                    $repay_account_number = number_format($d["capital"] + $d["pre"],2);
                    $origin_capital = number_format($d["capital"],2);
                    $pre_capital = number_format($d["pre"],2);
                    $d["tip"] = "该项目部分资金发生提前还款，实际还款金额为{$repay_account_number}元（其中正常还款金额{$origin_capital}元，提前还款金额{$pre_capital}元）";
                    break;
                }
            }
            if($flag == 0){
                $repay_account_number = number_format($p['capital'],2);
                $p["tip"] = "该项目部分资金发生提前还款，提前还款金额为{$repay_account_number}元";
                $pre_array[] = $p;
            }
        }
        $data = array_merge($data,$pre_array);
        if(count($data) == 0){
            return $data;
        }
        //项目信息获取
        $criteria=new CDbCriteria;
        $criteria->select='type,name,id';
        $criteria->condition=" id in (" . implode(',', $borrow_ids) .')';
        $borrow_infos = Borrow::model()->findAll($criteria);
        if(count($borrow_infos) == 0){
            return $data;
        }
        //拼接项目连接url
        foreach ($borrow_infos as $key => $val) {
            foreach ($data as $d_key =>$d_val){
                if($d_val['borrow_id'] == $val->id){
                    $data[$d_key]['name'] = $val->name;
                }
            }
        }
        return $data;
    }

    //通过传入的年月，计算这个月一共有多少天
    public function getAllDay($year,$month){
        $d31=array(1,3,5,7,8,10,12);
        $d30=array(4,6,9,11);
        if(in_array($month, $d31)){
            return 31;
        }elseif(in_array($month, $d30)){
            return 30;
        }else{
            if(($year%4==0) && ($year%100!=0)){
                return 29;
            }else{
                return 28;
            }
        }
    }

    /**
     * 呼叫挂断接口
     */
    public function setCallRecord($admin_id,$filter)
    {
        $filter =$this->filter($filter);
        Yii::log ( __FUNCTION__.print_r(func_get_args(),true),'info');
     	$returnResult = array(
     		'code' => '', 'info' => '', 'data' => array()
		 );
		//源数据类型
     	$admin_id = intval($admin_id);
     	$call_id = isset($filter['callid'])?addslashes($filter['callid']):0;
     	$type = isset($filter['type'])?intval($filter['type']):0;
     	 
     	$now_time = time();
     	if( empty($call_id) || empty($type) ){
     		$returnResult['code'] = 1003;
     		$returnResult['info'] = '缺少参数';
     		return $returnResult;
		}
     	if($type==1){//呼叫
     		$user_id = isset($filter['user_id']) ? trim($filter['user_id']) : '';
     		$call_type = isset($filter['calltype']) ? intval($filter['calltype']) : 1;
     		if( empty($user_id) || empty($call_type)){
     			$returnResult['code'] = 1003;
     			$returnResult['info'] = '缺少参数';
     			return $returnResult;
            }
            $userInfo = User::model()->findByPk($user_id);
     		//添加数据
     		$info['call_id'] = $call_id;
     		$info['admin_id'] = $admin_id;
            $info['user_id'] = $user_id;
            $info['user_phone'] = $userInfo->phone;
     		$info['type'] = $call_type;
     		$info['start_time'] = $info['addtime'] = $info['updatetime'] = $now_time;
     		$res = $this->addCallRecord($info);
     		if($res){
     			$returnResult['code'] = 0;
     			$returnResult['info'] = 'success';
     		}else{
     			Yii::log ( __FUNCTION__." insert order call record fail admin_id=".$admin_id.',call_id='.$call_id,'error');
     			$returnResult['code'] = 1;
     			$returnResult['info'] = '插入初始通话记录失败！';
     		}
     	}elseif($type==2){ //挂断
     		$user_info = CallService::getInstance()->getUserInfoByAid($admin_id);
     		$record_info['end_time'] = $record_info['updatetime'] = $now_time;
     		$record_info['ag_phone'] = $user_info['ag_phone'];
     		//$record_info['admin_name'] = $user_info['admin_name'];
     		$res = Yii::app()->crmdb->createCommand()->update('crm_call_record',$record_info,'call_id=:call_id', array(':call_id'=>$call_id));
     		if($res){
     			$returnResult['code'] = 0;
     			$returnResult['info'] = 'success';
     		}else {
     			Yii::log ( __FUNCTION__." update order call record fail call_id=".$call_id,'error');
     			$returnResult['code'] = 1;
     			$returnResult['info'] = '更新初始通话记录失败';
     		}
     	}else {
     		$returnResult['code'] = 1005;
     		$returnResult['info'] = '参数传递错误';
     	}
     	return $returnResult;
    }


    public function addCallRecord($data)
    {
    	Yii::log (__FUNCTION__.'--'.print_r(func_get_args(),true),'info');
    	$model = new CrmCallRecord();
    	foreach($data as $key=>$value){
    		$model->$key = $value;
    	}
    	if($model->save()==false){
    		Yii::log("ccs_call_record_model error: ".print_r($model->getErrors(),true),"error");
    		return false;
    	}else{
    		return true;
    	}
    }

    public function editCallRemark($filter)
    {
        $filter = $this->filter($filter);
        $returnResult = array(
            'code' => 1, 'info' => "没有相关数据", 'data' => []
        );
        $callRecord = CrmCallRecord::model()->findByAttributes(["call_id"=>$filter['call_id']]);
        if($callRecord){
            $callRecord->remark = $filter["remark"];
            $res = $callRecord->save();
            if($res){
                $returnResult["code"]=0;
                $returnResult["info"]="修改成功！";
            }else{
                $returnResult["info"]="修改失败！";
            }
        }else{
            $callRecord = CcsCallRecord::model()->findByAttributes(["call_id"=>$filter['call_id']]);
            if($callRecord){
                $callRecord->remark = $filter["remark"];
                $res = $callRecord->save();
                if($res){
                    $returnResult["code"]=0;
                    $returnResult["info"]="修改成功！";
                }else{
                    $returnResult["info"]="修改失败！";
                }
            }
        }
        return $returnResult;
    }

    /**
     * 获取呼入信息
     */
    public function getCallOutRecord($filter)
    {
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $filter = $this->filter($filter);
        $criteria = new CDbCriteria;
        if($filter["admin_id"]){
            $criteria->addCondition("admin_id = {$filter["admin_id"]}");
        }
        if($filter["phone"]){
            $criteria->addCondition("user_phone = {$filter["phone"]}");
        }
        if($filter["status"]){
            $criteria->addCondition("status = {$filter["status"]}");
        }
        if($filter["addtime"]){
            $criteria->addBetweenCondition("addtime",$filter["addtime"][0],$filter["addtime"][1]);
        }
        if($filter["user_level"]){
            $crmUsers = CrmUser::model()->findAllByAttributes(["user_level"=>$filter["user_level"]]);
            $user_ids = [];
            foreach($crmUsers as $crmUser){
                $user_ids[] = $crmUser->user_id;
            }
            if(empty($user_ids)){
                return $returnResult;
            }
            $criteria->addInCondition("user_id",$user_ids);
        }
        if($filter["realname"]){
            $criteria2 = new CDbCriteria;
            $criteria2->select = "user_id";
            $users = User::model()->findAllByAttributes(["realname"=>$filter["realname"],"real_status"=>1],$criteria2);
            $user_ids = [];
            foreach($users as $user){
                $user_ids[] = $user->user_id;
            }
            if(empty($user_ids)){
                return $returnResult;
            }
            $criteria->addInCondition("user_id",$user_ids);
        }
        $count = CrmCallRecord::model()->countByAttributes([],$criteria);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $records = CrmCallRecord::model()->findAllByAttributes([],$criteria);
        $list = [];
        $status_str = [
            "0" => "-",
            "1" => "呼出",
            "2" => "接通",
            "3" => "未接",
            "4" => "拒接",
        ];
        $level_str = [
            "0" => "-",
            "1" => "没兴趣",
            "2" => "近期不考虑",
            "3" => "有意向",
        ];
        foreach($records as $key=>$value){
            $l = [
                "call_id" => $value->call_id,
                "phone" => FunctionUtil::MaskTel($value->user_phone),
                "realname" => $this->getRealnameByPhone($value->user_phone),
                "admin_id" => $value->admin_id,
                "admin_name" => $this->getAdminNameByID($value->admin_id),
                "status" => $value->status,
                "addtime" => date("Y-m-d H:i:s",$value->addtime),
                "start_time" => date("Y-m-d H:i:s",$value->start_time),
                "talk_time" => $value->talk_time,
                "ring_secs" => $value->ring_secs,
                "remark" => $value->remark?:"",
                "user_level" => "-",
                "record_url" => "",
            ];
            $record_info = CallService::getInstance()->getDownRecord(["call_id"=>$value->call_id]);
            if($record_info["code"] == 0){
                $l["record_url"] = $record_info['data']['path'];
            }
            $crm_user = CrmUser::model()->findByPk($value->user_id);
            if($crm_user){
                $l["user_level"] = $level_str[$crm_user->user_level?:0]?:"-";
                $l["user_label"] = $crm_user->user_label?$this->getUserLabel($crm_user->user_label):[];
            }
            $list[] = $l;
        }
        $returnResult["data"]["listInfo"]=$list;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }

    /**
     * 获取呼出信息
     */
    public function getCallInRecord($filter)
    {
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $filter = $this->filter($filter);
        $criteria = new CDbCriteria;
        $criteria->addCondition("type = 2");
        if($filter["admin_id"]){
            $criteria->addCondition("admin_id = {$filter["admin_id"]}");
        }
        if($filter["phone"]){
            $criteria->addCondition("user_phone = {$filter["phone"]}");
        }
        if($filter["status"]){
            $criteria->addCondition("call_status = {$filter["status"]}");
        }
        if($filter["addtime"]){
            $criteria->addBetweenCondition("addtime",$filter["addtime"][0],$filter["addtime"][1]);
        }
        if($filter["start_time"]){
            $criteria->addBetweenCondition("start_time",$filter["start_time"][0],$filter["start_time"][1]);
        }
        if($filter["realname"]){
            $criteria2 = new CDbCriteria;
            $criteria2->select = "phone";
            $users = User::model()->findAllByAttributes(["realname"=>$filter["realname"],"real_status"=>1],$criteria2);
            $phones = [];
            foreach($users as $user){
                if($user->phone){
                    $phones[] = $user->phone;
                }
            }
            if(empty($phones)){
                return $returnResult;
            }
            $criteria->addInCondition("user_phone",$phone);
        }
        $count = CcsCallRecord::model()->countByAttributes([],$criteria);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $records = CcsCallRecord::model()->findAllByAttributes([],$criteria);
        $list = [];
        $status_str = [
            "0" => "-",
            "1" => "呼出",
            "2" => "接通",
            "3" => "未接",
            "4" => "拒接",
        ];
        $level_str = [
            "0" => "-",
            "1" => "没兴趣",
            "2" => "近期不考虑",
            "3" => "有意向",
        ];
        foreach($records as $key=>$value){
            $l = [
                "call_id" => $value->call_id,
                "phone" => FunctionUtil::MaskTel($value->user_phone),
                "realname" => $this->getRealnameByPhone($value->user_phone),
                "admin_id" => $value->admin_id,
                "admin_name" => $this->getAdminNameByID($value->admin_id),
                "status" => $value->call_status,
                "addtime" => date("Y-m-d H:i:s",$value->addtime),
                "start_time" => date("Y-m-d H:i:s",$value->start_time),
                "talk_time" => $value->talk_time,
                "ring_secs" => $value->ring_secs,
                "record_url" => $value->record_url,
                "remark" => $value->remark?:"",
            ];
            $list[] = $l;
        }
        $returnResult["data"]["listInfo"]=$list;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }

    /**
     * 通过手机号获取用户实名
     */
    private function getRealnameByPhone($phone)
    {
        if(empty($phone) || !is_numeric($phone)){
            return "-";
        }
        $user = User::model()->findByAttributes(["phone"=>$phone,"phone_status"=>1]);
        if($user && $user->realname){
            return mb_substr($user->realname, 0, 1, 'utf-8').'**';
        }
        return "-";
    }

    public function getExportReportData($report_id)
    {
        if(!is_numeric($report_id)){
            return [];
        }
        $report = CrmReport::model()->findByPk($report_id);
        if(empty($report)){
            return [];
        }
        $_sql = "SELECT u.* FROM crm_report_user ru left join crm_user u on ru.user_id = u.user_id where ru.report_id = {$report_id}";
        $crm_users = Yii::app()->crmdb->createCommand($_sql)->queryAll();
        $data = [];
        foreach($crm_users as $crm_user){

        }
        return $data;
    }

    /**
     * 获取操作员员列表
     */
    public function getAllAdminList($filter)
    {
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $filter = $this->filter($filter);
        $criteria = new CDbCriteria;
        if($filter["admin_id"]){
            $criteria->addCondition("admin_id = {$filter["admin_id"]}");
        }
        if($filter["name"]){
            $criteria->addCondition("name like '%{$filter["name"]}%'");
        }
        if($filter["type"]){
            $criteria->addCondition("type = {$filter["type"]}");
        }
        $count = CrmAdmin::model()->countByAttributes([],$criteria);
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $criteria->order = "id desc";
        $admins = CrmAdmin::model()->findAllByAttributes([],$criteria);
        $list = [];
        foreach($admins as $admin){
            $l = $admin->attributes;
            $l["addtime"] = date("Y-m-d H:i:s",$admin->addtime);
            $l["pname"] = $admin->p_id?$this->getAdminNameByID($admin->p_id):"-";
            $list[] = $l;
        }
        $returnResult["data"]["listInfo"]=$list;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }

    /**
     * 获取管理员信息
     */
    public function getAdminInfo($admin_id)
    {
        $returnResult = array(
            'code' => 1, 'info' => "管理员不存在！", 'data' => []
        );
        $admin = CrmAdmin::model()->findByAttributes(["admin_id"=>$admin_id]);
        if($admin){
            $returnResult["data"]['listInfo'] = $admin->attributes;
            $returnResult["data"]['listInfo']["addtime"] = date("Y-m-d H:i:s",$admin->addtime);
            $returnResult["code"]=0;
            $returnResult["info"]="获取成功！";
        }
        return $returnResult;
    }

    /**
     * 编辑管理员信息
     */
    public function editAdminInfo($admin_id,$filter)
    {
        $returnResult = array(
            'code' => 1, 'info' => "管理员不存在！", 'data' => []
        );
        $filter = $this->filter($filter);
        $admin = CrmAdmin::model()->findByAttributes(["admin_id"=>$admin_id]);
        if($admin){
            $operate_id = Yii::app()->user->id;
            //加记录
            if(($filter['type'] && $filter['type'] != $admin->type) || $filter['p_id'] != $admin->p_id)
            {
                $crm_user_log = new CrmAdminLog();
                $type = $admin->type % 2 == 0 && $filter['type'] % 2 == 1?1:($admin->type % 2 == 1 && $filter['type'] % 2 == 0?2:3);
                if($type == 2){//降级后释放原组员为无组长状态
                    CrmAdmin::model()->updateAll(["p_id"=>0],"p_id = :p_id",[":p_id"=>$admin_id]);
                }
                $crm_user_log->admin_id = $admin_id;
                $crm_user_log->type = $type;
                
                $crm_user_log->from = $admin->p_id;
                $crm_user_log->to = $filter['p_id'];
                $crm_user_log->operate_id = $operate_id;
                $crm_user_log->addtime = time();
                //将这个用户的重新导出出来
                if(in_array($admin->type,[4,5]) && !in_array($filter['type'],[4,5])){
                    $this->reAllotAllUserByAdminID($admin->admin_id);
                }
                $crm_user_log->save();
            }
            //加记录
            if($filter['status'] == 2){
                $crm_user_log = new CrmAdminLog();
                $crm_user_log->admin_id = $admin_id;
                $crm_user_log->type = 5;
                $crm_user_log->operate_id = $operate_id;
                $crm_user_log->addtime = time();
                $crm_user_log->save();
                if($admin->type == 4 || $admin->type == 5){
                    //将这个用户的重新导出出来
                    $this->reAllotAllUserByAdminID($admin->admin_id);
                }
            }
            $admin->name = $filter['name']?:$admin->name;
            $admin->type = $filter['type']?:$admin->type;
            $admin->status = $filter['status']?:$admin->status;
            $admin->ucenter_uid = $filter['ucenter_uid']?:$admin->ucenter_uid;
            $admin->p_id = in_array($filter['type'],[2,4])?$filter['p_id']:0;
            $admin->ag_num1 = intval($filter['ag_num1'])?:0;
            $admin->ag_num2 = intval($filter['ag_num2'])?:0;
            $res = $admin->save();
            if($res){
                $returnResult["code"]=0;
                $returnResult["info"]="编辑成功！";
            }else{
                $returnResult["info"]="编辑失败！";
            }
        }
        return $returnResult;
    }

    /**
     * 管理员变化时，重新生成分配表
     */
    private function reAllotAllUserByAdminID($admin_id)
    {
        
        if(empty($admin_id) || !is_numeric($admin_id)){
            return ;
        }
        //私海用户
        $count = CrmUser::model()->countByAttributes(['admin_id'=>$admin_id,"is_call"=>1]);
        $admin_name = $this->getAdminNameByID($admin_id);
        $loop = 0;
        $criteria = new CDbCriteria;
        $criteria->limit = 1000;
        while($loop <  ceil($count/1000)){
            $users = [];
            $crmUsers = CrmUser::model()->findAllByAttributes(['admin_id'=>$admin_id,"is_call"=>1],$criteria);
            foreach($crmUsers as $crmUser){
                $users[] = ["user_id"=>$crmUser->user_id,"reasons"=>[["user_id"=>$crmUser->user_id,"remark"=>"客维 {$admin_name} 离职重分配"]]];
                $crmUser->admin_id = 0;
                $crmUser->is_allot = 0;
                $crmUser->allot_time = 0;
                $crmUser->is_call = 0;
                $crmUser->call_time = 0;
                $crmUser->save();
            }
            if($users){
                $allot_data = ["type"=>1,"remark"=>"客维 {$admin_name} 离职释放-私海用户-".$loop,"users"=>$users];
                $this->importReport($allot_data);
                unset($users);
            }
            $loop++;
        }
        //未拨打私海用户
        $count = CrmUser::model()->countByAttributes(['admin_id'=>$admin_id]);
        $admin_name = $this->getAdminNameByID($admin_id);
        $loop = 0;
        $criteria = new CDbCriteria;
        $criteria->limit = 1000;
        while($loop <  ceil($count/1000)){
            $users = [];
            $crmUsers = CrmUser::model()->findAllByAttributes(['admin_id'=>$admin_id],$criteria);
            foreach($crmUsers as $crmUser){
                $users[] = ["user_id"=>$crmUser->user_id,"reasons"=>[["user_id"=>$crmUser->user_id,"remark"=>"客维 {$admin_name} 离职重分配"]]];
                $crmUser->admin_id = 0;
                $crmUser->is_allot = 0;
                $crmUser->allot_time = 0;
                $crmUser->is_call = 0;
                $crmUser->call_time = 0;
                $crmUser->save();
            }
            if($users){
                $allot_data = ["type"=>1,"remark"=>"客维 {$admin_name} 离职释放-未拨打-".$loop,"users"=>$users];
                $this->importReport($allot_data);
                unset($users);
            }
            $loop++;
        }
    }

    /**
     * 获取管理员类型
     */
    public function getAdminType($admin_id)
    {
        $admin = CrmAdmin::model()->findByAttributes(['admin_id'=>$admin_id,'status'=>1]);
        if($admin){
            return $admin->type;
        }else{
            return 0;
        }
    }

    /**
     * 获取管理员首页信息
     */
    public function getMasterStat($filter = array())
    {
        $filter = $this->filter($filter);
        $returnResult = array(
            'code' => 0, 'info' => "获取成功！", 'data' => []
        );
        $startTime = $filter['date'][0]?:strtotime(date('Y-m'));
        $endTime = $filter['date'][1]?:time();
        
        $returnResult['data'] = [
            "adminInfo" => $this->getAdminInfo(Yii::app()->user->id)['data']['listInfo'],
            "sobotStat" => $this->getSobotStat($startTime,$endTime),
            "csStat" => $this->getCsTotalStat($startTime,$endTime),
            "tsStat" => $this->getTsTotalStat($startTime,$endTime),
            "transformStat" => $this->getTransformStat([],$startTime,$endTime),
        ];
        return $returnResult;
    }

    /**
     * 获取客服顶部数据
     */
    public function getCsBase($admin_id)
    {
        $result["adminInfo"] = $this->getAdminInfo($admin_id)["data"]['listInfo'];
        return $result;
    }

    /**
     * 获取客维顶部数据
     */
    public function getTsBase($admin_id)
    {
        $result["adminInfo"] = $this->getAdminInfo($admin_id)["data"]['listInfo'];
        $sql = "SELECT count(distinct u.user_id) as unmark from crm_user u left JOIN crm_call_record cr on u.user_id = cr.user_id and u.admin_id = cr.admin_id and u.allot_time > cr.start_time where cr.status = 2 and u.admin_id = :admin_id and u.user_level = 0";
        $result["stat"]["unmark"] = Yii::app()->crmdb->createCommand($sql)->bindParam(":admin_id",$admin_id,PDO::PARAM_INT)->queryScalar()?:0;
        $result["stat"]["unhandle"] = CrmUser::model()->countByAttributes(["admin_id"=>$admin_id,"is_call"=>0]);
        $result["stat"]["total"] = CrmUser::model()->countByAttributes(["admin_id"=>$admin_id]);
        return $result;
    }

    /**
     * 获取客服统计数据
     */
    public function getCsStat($admin_id,$filter)
    {
        if(empty($admin_id)){
            return [];
        }
        $filter = $this->filter($filter);
        $startTime = $filter['date'][0]?:strtotime(date('Y-m'));
        $endTime = $filter['date'][1]?:time();
        $result = [
            "getThrough" => 0,
            "getThroughPre" => 0,
            "satisfaction" => 0, //满意数
            "dissatisfaction" => 0, //不满意数
            "handled" => 0, //已解决
            "unhandled" => 0, //未解决
            "totalTime" => "0s", //接听总时间
            "bbsPost" => 0, //论坛发帖数
        ];
        $adminInfo = $this->getAdminInfo($admin_id)["data"]['listInfo'];
        if($adminInfo["type"] == 3){
            $admin_ids = $this->getAdminIDsByLeader($adminInfo["admin_id"]);
        }else{
            $admin_ids = $admin_id;
        }
        $criteria = new CDbCriteria;
        $criteria->addBetweenCondition("addtime",$startTime,$endTime);
        $allCall = CcsCallRecord::model()->countByAttributes(["admin_id"=>$admin_ids],$criteria)?:0;
        $criteria2 = $criteria;
        $criteria2->addCondition("talk_time >= 30");
        $calls = CcsCallRecord::model()->countByAttributes(["admin_id"=>$admin_ids,"call_status"=>2],$criteria2)?:0;
        $result["getThrough"] = $calls." / ".$allCall;
        $result["getThroughPre"] =($allCall?round($calls*100/$allCall,2):0)."%";
        $criteria->select = "sum(talk_time) as talk_time";
        $totalTime = CcsCallRecord::model()->findByAttributes(["admin_id"=>$admin_ids],$criteria)->talk_time?:0;
        
        $admin_str = is_array($admin_ids)?implode(",",$admin_ids):$admin_ids;
        $_handled_sql = "select count(*) as c from ccs_call_record where admin_id in ($admin_str) and result like 'handled%' and addtime between $startTime and $endTime";
        $_unhandle_sql = "select count(*) as c from ccs_call_record where admin_id in ($admin_str) and result like 'unsolved%' and addtime between $startTime and $endTime";
        $_statify_sql = "select count(*) as c from ccs_call_record where admin_id in ($admin_str) and result like '%statify%' and addtime between $startTime and $endTime";
        $_disstatify_sql = "select count(*) as c from ccs_call_record where admin_id in ($admin_str) and result like '%discontent%' and addtime between $startTime and $endTime";
        
        $result["handled"] = Yii::app()->ccsdb->createCommand($_handled_sql)->queryScalar();
        $result["unhandled"] = Yii::app()->ccsdb->createCommand($_unhandle_sql)->queryScalar();
        $result["satisfaction"] = Yii::app()->ccsdb->createCommand($_statify_sql)->queryScalar();
        $result["dissatisfaction"] = Yii::app()->ccsdb->createCommand($_disstatify_sql)->queryScalar();
        
        $result["totalTime"] = $this->formatTime($totalTime);
        $result["bbsPost"] = $this->getBbsPost($admin_id,$startTime,$endTime);
        return $result;
    }

    /**
     * 获取客维统计数据
     */
    public function getTsStat($admin_id,$filter)
    {
        $filter = $this->filter($filter);
        $startTime = $filter['date'][0]?:strtotime(date('Y-m'));
        $endTime = $filter['date'][1]?:time();
        if(empty($admin_id)){
            return [];
        }
        $admin_ids = $this->getAdminIDsByLeader($admin_id)?:[$admin_id];
        $result["transformStat"] = $this->getTransformStat($admin_ids,$startTime,$endTime);
        $pInvestMemberSQL = "SELECT count(distinct user_id) as member FROM crm_new_tender WHERE tender_time between :startTime and :endTime and status = 1 and (admin_id = :admin_id or admin_pid = :admin_id)"; 
        $result["investStat"]["member"] = Yii::app()->crmdb->createCommand($pInvestMemberSQL)->bindParam(":startTime",$startTime,PDO::PARAM_INT)->bindParam(":endTime",$endTime,PDO::PARAM_INT)->bindParam(":admin_id",$admin_id,PDO::PARAM_INT)->queryScalar()?:0;
        $pInvestCountSQL = "SELECT count(*) as c FROM crm_new_tender WHERE tender_time between :startTime and :endTime and status = 1 and (admin_id = :admin_id or admin_pid = :admin_id)"; 
        $result["investStat"]["count"] = Yii::app()->crmdb->createCommand($pInvestCountSQL)->bindParam(":startTime",$startTime,PDO::PARAM_INT)->bindParam(":endTime",$endTime,PDO::PARAM_INT)->bindParam(":admin_id",$admin_id,PDO::PARAM_INT)->queryScalar()?:0;
        $pInvestAccountSQL = "SELECT sum(account_init) as member FROM crm_new_tender WHERE tender_time between :startTime and :endTime and status = 1 and (admin_id = :admin_id or admin_pid = :admin_id)"; 
        $result["investStat"]["account"] = Yii::app()->crmdb->createCommand($pInvestAccountSQL)->bindParam(":startTime",$startTime,PDO::PARAM_INT)->bindParam(":endTime",$endTime,PDO::PARAM_INT)->bindParam(":admin_id",$admin_id,PDO::PARAM_INT)->queryScalar()?:0;
        $criteria = new CDbCriteria;
        $criteria->addBetweenCondition("addtime",$startTime,$endTime);
        $result["callStat"]["validCall"] = $calls = CrmCallRecord::model()->countByAttributes(["admin_id"=>$admin_ids,"status"=>2],$criteria)?:0;
        $result["callStat"]["totalCall"] = CrmCallRecord::model()->countByAttributes(["admin_id"=>$admin_ids],$criteria)?:0;
        $criteria->select = "sum(talk_time) as talk_time";
        $totalTime = CrmCallRecord::model()->findByAttributes(["admin_id"=>$admin_ids],$criteria)->talk_time?:0;
        $result["callStat"]["totalTime"] = $this->formatTime($totalTime);
        return $result;
    }

    /** 数据统计开始 */
    /**
     * 获取客服统计数据
     */
    private function getCsTotalStat($startTime,$endTime)
    {
        $result = [
            "online" => 0,
            "total" => 0,
            "getThrough" => 0,
            "callIn" => 0,
            "satisfaction" => 0,
            "dissatisfaction" => 0,
            "handled" => 0,
            "unhandled" => 0,
            "totalTime" => "",
            "bbsPost" => 0,
        ];
        //10分钟内活动的记为在线
        $last_login = time() - 600;
        $result["online"] = CrmAdmin::model()->count("type in (2,3) and status = 1 and last_login >= $last_login");
        $result["total"] = CrmAdmin::model()->countByAttributes(["type"=>[2,3],"status"=>1]);
        $criteria = new CDbCriteria;
        $criteria->addBetweenCondition("addtime",$startTime,$endTime);
        $criteria2 = $criteria3 = $criteria4 = $criteria5 = $criteria6 = $criteria;
        $criteria2->addCondition("talk_time >= 30");
        $result["getThrough"] = CcsCallRecord::model()->countByAttributes(["type"=>2,"call_status"=>2],$criteria2);
        $result["callIn"] = CcsCallRecord::model()->countByAttributes(["type"=>2],$criteria);
        $_handled_sql = "select count(*) as c from ccs_call_record where result like 'handled%' and addtime between $startTime and $endTime";
        $_unhandle_sql = "select count(*) as c from ccs_call_record where result like 'unsolved%' and addtime between $startTime and $endTime";
        $_statify_sql = "select count(*) as c from ccs_call_record where result like '%statify%' and addtime between $startTime and $endTime";
        $_disstatify_sql = "select count(*) as c from ccs_call_record where result like '%discontent%' and addtime between $startTime and $endTime";
        
        $result["handled"] = Yii::app()->ccsdb->createCommand($_handled_sql)->queryScalar();
        $result["unhandled"] = Yii::app()->ccsdb->createCommand($_unhandle_sql)->queryScalar();
        $result["satisfaction"] = Yii::app()->ccsdb->createCommand($_statify_sql)->queryScalar();
        $result["dissatisfaction"] = Yii::app()->ccsdb->createCommand($_disstatify_sql)->queryScalar();
        
        $criteria->select = "sum(talk_time) as talk_time";
        $talk_time = CcsCallRecord::model()->findByAttributes([],$criteria)->talk_time;
        $result["totalTime"] = $this->formatTime($talk_time);
        $result["bbsPost"] = $this->getBbsPost("",$startTime,$endTime);
        return $result;
    }

    /**
     * 格式化时间
     */
    private function formatTime($talk_time)
    {
        $result = "";
        if($talk_time){
            if($hour = intval($talk_time/3600)){
                $result .= $hour."h";
            }
            $talk_time = $talk_time%3600;
            if($min = intval($talk_time/60)){
                $result .= $min."m";
            }
            $sec = $talk_time%60;
            if($sec){
                $result .= $sec."s";
            }
        }
        return $result?:"0s";
    }

    /**
     * 计算论坛回帖数
     */
    private function getBbsPost($admin_id,$startTime,$endTime)
    {
        $criteria = new CDbCriteria;
        $criteria->select = "ucenter_uid";
        $criteria->addCondition("ucenter_uid > 0");
        if(intval($admin_id)){
            $criteria->addCondition("admin_id = $admin_id or p_id = $admin_id");
        }
        $uids = CrmAdmin::model()->findAllByAttributes([],$criteria);
        $uid_array = [];
        foreach($uids as $uid){
            $uid_array[] = intval($uid->ucenter_uid);
        }
        if($uid_array){
            $uidstr = implode(",",$uid_array);
            $sql1 = "select count(*) as c from itz_forum_post where authorid in($uidstr) and dateline between $startTime and $endTime";
            $sql2 = "select count(*) as c from itz_forum_postcomment p where p.authorid in($uidstr) and p.dateline between $startTime and $endTime";
            $count1 = Yii::app()->bbs->createCommand($sql1)->queryScalar();
            $count2 = Yii::app()->bbs->createCommand($sql2)->queryScalar();
            return $count1+$count2;
        }
        return 0;
    }

    /**
     * 获取客维统计数据
     */
    private function getTsTotalStat($startTime,$endTime)
    {
        $result = [
            "online" => 0, //客服在线人数
            "total" => 0,  //客服总人数
            "getThrough" => 0, //接通数
            "allot" => 0,   //分配数
            "callOut" => 0, // 呼出数
            'totalTime'=> '0s',
            "pInvestMember"=> 0, //私海投资人数
            "pInvestAccount" => 0.00, //私海投资金额
            "pTransferMember" => 0, //私海转化人数
            "pTransferAccount" => 0.00 //私海转化金额
        ];
        $last_login = time() - 600;
        $result["online"] = CrmAdmin::model()->count("type in (4,5) and status = 1 and last_login >= $last_login");
        $result["total"] = CrmAdmin::model()->countByAttributes(["type"=>[4,5],"status"=>1]);
        $criteria = new CDbCriteria;
        $criteria->addBetweenCondition("addtime",$startTime,$endTime);
        $result["getThrough"] = CrmCallRecord::model()->countByAttributes(["status"=>2],$criteria);
        $result["callOut"] = CrmCallRecord::model()->countByAttributes([],$criteria);
        $criteria->select = "sum(talk_time) as talk_time";
        $talk_time = CrmCallRecord::model()->findByAttributes([],$criteria)->talk_time;
        $result["totalTime"] = $this->formatTime($talk_time);
        $criteria2 = new CDbCriteria;
        $criteria2->addBetweenCondition("allot_time",$startTime,$endTime);
        $result["allot"] = CrmUser::model()->countByAttributes(['is_allot'=>1],$criteria2);
        $pInvestMemberSQL = "SELECT count(distinct user_id) as member FROM crm_new_tender WHERE tender_time between :startTime and :endTime and status = 1"; 
        $result["pInvestMember"] = Yii::app()->crmdb->createCommand($pInvestMemberSQL)->bindParam(":startTime",$startTime,PDO::PARAM_INT)->bindParam(":endTime",$endTime,PDO::PARAM_INT)->queryScalar()?:0;
        $pInvestAccountSQL = "SELECT sum(account_init) as member FROM crm_new_tender WHERE tender_time between :startTime and :endTime and status = 1"; 
        $result["pInvestAccount"] = Yii::app()->crmdb->createCommand($pInvestAccountSQL)->bindParam(":startTime",$startTime,PDO::PARAM_INT)->bindParam(":endTime",$endTime,PDO::PARAM_INT)->queryScalar()?:0;
        $pTransferMemberSQL = "SELECT count(distinct user_id) as member FROM crm_new_tender WHERE tender_time between :startTime and :endTime and status = 1 and first = 1"; 
        $result["pTransferMember"] = Yii::app()->crmdb->createCommand($pTransferMemberSQL)->bindParam(":startTime",$startTime,PDO::PARAM_INT)->bindParam(":endTime",$endTime,PDO::PARAM_INT)->queryScalar()?:0;
        $pTransferAccountSQL = "SELECT sum(account_init) as member FROM crm_new_tender WHERE tender_time between :startTime and :endTime and status = 1 and first = 1"; 
        $result["pTransferAccount"] = Yii::app()->crmdb->createCommand($pTransferAccountSQL)->bindParam(":startTime",$startTime,PDO::PARAM_INT)->bindParam(":endTime",$endTime,PDO::PARAM_INT)->queryScalar()?:0;
        return $result;
    }

    /**
     * 获取转化统计数据
     */
    private function getTransformStat($admins,$startTime,$endTime)
    {
        $result = [
            "stat"  => [ //初始状态对应人数
                "2" => 0,  //格式或者改成 ["user_status"=>2,"member"=>100]
                "3" => 0,
                "4" => 0,
                "5" => 0,
                "6" => 0,
            ],
            "pie" =>[]
        ];
        if($admins){ 
            $admin_ids = implode(",",$admins);
            $statSQL = "SELECT user_type,count(*)as member,group_concat(user_id) as user_ids  from crm_user where admin_id in ($admin_ids) and allot_time between :startTime and :endTime and user_type in (2,3,4,5,6) group by user_type order by user_type";
        } else {
            $statSQL = "SELECT user_type,count(*)as member,group_concat(user_id) as user_ids  from crm_user where allot_time between :startTime and :endTime and user_type in (2,3,4,5,6) group by user_type order by user_type";
        }
        $stat = Yii::app()->crmdb->createCommand($statSQL)->bindParam(":startTime",$startTime,PDO::PARAM_INT)->bindParam(":endTime",$endTime,PDO::PARAM_INT)->queryAll();
        //$totalMember = 0;
        foreach($stat as $s){
            $result["stat"][$s['user_type']] = $s['member'];
            $user_ids = explode(",",$s['user_ids']);
            $criteria = new CDbCriteria;
            $criteria->select = "user_id,xw_open,isinvested";
            $users = User::model()->findAllByAttributes(['user_id'=>$user_ids],$criteria);
            
            $l = ["from" => $s['user_type'],"data" => []];
            foreach($users as $user){
                if($user->isinvested){
                    $l["data"][7]["type"] = 7;
                    $l["data"][7]["value"]++;
                }elseif($user->xw_open == 2){
                    $rechargeInfo = AccountRecharge::model()->countByAttributes(['user_id'=>$user->user_id,"status"=>1]);
                    if($rechargeInfo){
                        $user_type = max($s['user_type'],5);
                        $l["data"][$user_type]["type"] = $user_type;
                        $l["data"][$user_type]["value"]++;
                    }else{
                        $rechargeFalse = AccountRecharge::model()->countByAttributes(['user_id'=>$user->user_id,"status"=>[0,2]]);
                        if($rechargeFalse){
                            $user_type = max($s['user_type'],4);
                            $l["data"][$user_type]["type"] = $user_type;
                            $l["data"][$user_type]["value"]++;
                        }else{
                            $user_type = max($s['user_type'],3);
                            $l["data"][$user_type]["type"] = $user_type;
                            $l["data"][$user_type]["value"]++;
                        }
                    }
                }else{
                    $user_type = max($s['user_type'],2);
                    $l["data"][$user_type]["type"] = $user_type;
                    $l["data"][$user_type]["value"]++;
                }
            }
            $l["data"] = array_values($l["data"]);
            $result["pie"][] = $l;
        }
        return $result;
    }
    
    /** 获取智齿三方统计数据开始 */
    /**
     * 获取统计数据
     */
    private function getSobotStat($startTime,$endTime)
    {
        
        $robot_stat = $this->getWbSessionRobot($startTime,$endTime);
        $human_stat = $this->getWbSessionHuman($startTime,$endTime);
        $result = [
            "tohumanSession" => ($rebot_stat['toHumanSession']?:0),
            "robotSession" => ($robot_stat['validSession']?:0),
            "humanSession" => ($human_stat['validSession']?:0),
            "robotAvgSessionDuration" => ($human_stat['avgSessionDuration']?:0),
            "humanAvgSessionDuration" =>  ($robot_stat['avgSessionDuration']?:0),
        ];
        return $result;
    }

    /**
     * 获取机器人会话统计
     */
    private function getWbSessionRobot($startTime,$endTime)
    {
        $access_token = $this->getSobotAccessTokenFromCache();
        
        $query['action'] = "wb_session_robot";
        $query['access_token'] = $access_token;
        $query['data']['startDate'] = date("Y-m-d",$startTime);
        $query['data']['endDate'] = date("Y-m-d",$endTime);
        $loop = 0;
        do{
            $result = $this->curl($this->sobot_api_url,$query);
            
            $loop++;
        }while($result['code'] != 1000 && $loop < 3);
        
        if($result['code'] == 1000){
            return $result['data']['item'];
        }
        return [];
    }

    /**
     * 获取人工会话统计
     */
    private function getWbSessionHuman($startTime,$endTime)
    {
        $access_token = $this->getSobotAccessTokenFromCache();
        $query['action'] = "wb_session_robot";
        $query['access_token'] = $access_token;
        $query['data']['startDate'] = date("Y-m-d",$startTime);
        $query['data']['endDate'] = date("Y-m-d",$endTime);
        $loop = 0;
        do{
            $result = $this->curl($this->sobot_api_url,$query);
            $loop++;
        }while($result['code'] != 1000 && $loop < 3);
        if($result['code'] == 1000){
            return $result['data']['item'];
        }
        return [];
    }

    /**
     * 获取智齿accesstoken，使用请通过 fromcache 获取
     */
    private function getSobotAccessToken()
    {
        //时间
        $query['appId'] = $this->sobot_appid;
        $query['createTime'] = time().rand(100,999);
        $query['sign'] = md5($this->sobot_appid.$this->sobot_appkey.$query['createTime']);
        $query['expire'] = 2;
        $url = $this->sobot_access_url."?appId={$query['appId']}&createTime={$query['createTime']}&sign={$query['sign']}&expire={$query['expire']}";
        $loop = 0;
        do{
            $result = $this->curl($url);
            $loop++;
        }while($result['code'] != 1000 && $loop < 3);
        if($result['code'] == 1000){
            return $result['data']['access_token'];
        }
        return "";
    }

    private function curl($url,$curlPost = array())
    {
        $ch = curl_init();//初始化curl
        curl_setopt($ch,CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_TIMEOUT,3);
        if($curlPost){
            curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        }
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return json_decode($data, true);
    }
    /** 获取智齿三方统计数据结束 */

    /** 获取组员信息开始 */
    /**
     * 获取组员ID
     */
    public function getAdminIDsByLeader($leader_id)
    {
        $admins = CrmAdmin::model()->findAllByAttributes(['p_id'=>$leader_id]);
        $ids = [];
        foreach($admins as $admin) {
            $ids[] = $admin->admin_id;
        }
        return $ids; 
    }

    /**
     * 获取组员ucenter_uid
     */
    public function getUcenterIDsByLeader($leader_id)
    {
        $admins = CrmAdmin::model()->findAllByAttributes(['p_id'=>$leader_id]);
        $ids = [];
        foreach($admins as $admin) {
            if($admin->ucenter_uid){
                $ids[] = $admin->ucenter_uid;
            }
        }
        return $ids; 
    }
    /** 获取组员信息结束 */

    /** 公告便签相关开始 */
    /**
     * 获取公告
     */
    public function getNotice($offset = 0,$limit = 3)
    {
        $start_time = strtotime("-7 days midnight");
        $sql = "SELECT * FROM crm_notice where status = 1 and type = 1 and addtime > $start_time order by addtime desc limit :offset,:limit";
        $notices = Yii::app()->crmdb->createCommand($sql)
                ->bindParam(":offset",$offset,PDO::PARAM_INT)
                ->bindParam(":limit",$limit,PDO::PARAM_INT)
                ->queryAll();
        foreach($notices as &$notice){
            $notice["datetime"] = date("Y-m-d H:i:s",$notice["datetime"]);
            $notice["addtime"] = date("Y-m-d H:i:s",$notice["addtime"]);
        }
        return $notices;
    }

    /**
     * 获取公告列表
     */
    public function getNoticeList($filter)
    {
        $filter = $this->filter($filter);
        
        $returnResult = array(
            'code' => 1, 'info' => "获取失败！", 'data' => []
        );
        $criteria = new CDbCriteria;
        if($filter["title"]){
            $title = str_replace("%",'\%',$filter["title"]);
            $criteria->addCondition("title like '%{$title}%'");
        }
        if($filter["date"]){
            $criteria->addBetweenCondition("addtime",$filter["date"][0], $filter["date"][1]);
        }
        $count = CrmNotice::model()->countByAttributes(["type"=>1],$criteria);
        $page = $filter['page']?$filter['page']:1;
        $limit = $filter['limit']?$filter['limit']:10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $criteria->order = "addtime desc";
        $returnResult["data"]["listTotal"] = intval($count);
        $notices = CrmNotice::model()->findAllByAttributes(["type"=>1],$criteria);
        $list = [];
        foreach($notices as $notice){
            $l = $notice->attributes;
            $l["datetime"] = date("Y-m-d H:i:s",$l['datetime']);
            $l["addtime"] = date("Y-m-d H:i:s",$l['addtime']);
            $l["adder"] = $this->getAdminNameByID($l["adder"]);
            $list[] = $l;
        }
        $returnResult["data"]["listInfo"] = $list;
        $returnResult["code"] = 0;
        return $returnResult;
    }

    public function getNoticeInfo($notice_id)
    {
        $returnResult = array(
            'code' => 1, 'info' => "获取失败！", 'data' => []
        );
        $notice = CrmNotice::model()->findByAttributes(["type"=>1,"id"=>$notice_id]);
        if(empty($notice)){
            return $returnResult;
        }
        $l = $notice->attributes;
        $l["datetime"] = date("Y-m-d H:i:s",$l['datetime']);
        $l["addtime"] = date("Y-m-d H:i:s",$l['addtime']);
        $list[] = $l;
        $returnResult["data"]["listInfo"] = $list;
        $returnResult["code"] = 0;
        return $returnResult;
    }

    /**
     * 添加公告
     */
    public function addNotice($filter,$admin_id)
    {
        $filter = $this->filter($filter);
        $notice = new CrmNotice();
        if(empty($filter['title']) || empty($filter['content'])){
            return false;
        }
        $notice->title = $filter['title'];
        $notice->content = $filter['content'];
        $notice->remark = $filter['remark'];
        $notice->type = 1;
        $notice->status = $filter['status']?:1;
        $notice->adder = $admin_id;
        $notice->addtime = time();
        $res = $notice->save();
        if($res){
            return true;
        }
        return false;
    }

    /**
     * 编辑公告
     */
    public function editNotice($notice_id,$filter)
    {
        $filter = $this->filter($filter);
        $notice = CrmNotice::model()->findByPk($notice_id);
        if(empty($notice) || $notice->type != 1){
            return false;
        }
        if(empty($filter['title']) || empty($filter['content'])){
            return false;
        }
        $notice->title = $filter['title'];
        $notice->content = $filter['content'];
        $notice->remark = $filter['remark'];
        $notice->status = $filter['status']?:1;
        $res = $notice->save();
        if($res){
            return true;
        }
        return false;
    }

    /**
     * 删除公告
     */
    public function deleteNotice($notice_ids)
    {
        $notices = CrmNotice::model()->findAllByPk($notice_ids);
        $res = 0;
        foreach($notices as $notice){
            if($notice->delete()){
                $res ++;
            }
        }
        if($res){
            return $res;
        }
        return false;
    }

    /**
     * 获取便签
     */
    public function getMemo($admin_id, $year = 0,$month = 0)
    {
        if(empty($year)){
            $year = date("Y");
        }
        if(empty($month)){
            $month = date("m");
        }
        $startTime = strtotime(date($year.'-'.$month."-1 0:00:00"));
        $month_days = $this->getAllDay($year, $month);//获得这个月一共有多少天
        $endTime = strtotime(date($year.'-'.$month.'-'.$month_days.' 23:59:59'));
        $sql = "SELECT * FROM crm_notice where status = 1 and type = 2 and adder = :admin_id and `datetime` between :startTime and :endTime";
        $memos = Yii::app()->crmdb->createCommand($sql)
                    ->bindParam(":startTime",$startTime,PDO::PARAM_INT)
                    ->bindParam(":endTime",$endTime,PDO::PARAM_INT)
                    ->bindParam(":admin_id",$admin_id,PDO::PARAM_INT)
                    ->queryAll();
        $result = [];
        foreach($memos as $memo){
            $memo["date"] = date("Y/m/d",$memo['datetime']);
            $memo["datetime"] = date("Y-m-d H:i:s",$memo["datetime"]);
            $memo["addtime"] = date("Y-m-d H:i:s",$memo["addtime"]);
            $result[] = $memo;
        }
        return $result;
    }

    public function getMemoInfo($memo_id)
    {
        $returnResult = array(
            'code' => 1, 'info' => "获取失败！", 'data' => []
        );
        $notice = CrmNotice::model()->findByAttributes(["type"=>2,"id"=>$memo_id]);
        if(empty($notice)){
            return $returnResult;
        }
        $l = $notice->attributes;
        $l["datetime"] = date("Y-m-d H:i:s",$l['datetime']);
        $l["addtime"] = date("Y-m-d H:i:s",$l['addtime']);
        $list[] = $l;
        $returnResult["data"]["listInfo"] = $list;
        $returnResult["code"] = 0;
        return $returnResult;
    }

    /**
     * 添加便签
     */
    public function addMemo($filter,$admin_id)
    {
        $filter = $this->filter($filter);
        if(empty($filter['title']) || empty($filter['content'])){
            return false;
        }
        $notice = new CrmNotice();
        $notice->title = $filter['title'];
        $notice->content = $filter['content'];
        $notice->remark = $filter['remark'];
        $notice->type = 2;
        $notice->status = $filter['status']?:1;
        $notice->datetime = $filter['datetime']?:time();
        $notice->adder = $admin_id;
        $notice->addtime = time();
        $res = $notice->save();
        if($res){
            return true;
        }
        return false;
    }

    /**
     * 编辑便签
     */
    public function editMemo($notice_id,$filter)
    {
        $filter = $this->filter($filter);
        $notice = CrmNotice::model()->findByAttributes($notice_id);
        if(empty($notice) || $notice->type != 2){
            return false;
        }
        if(empty($filter['title']) || empty($filter['content'])){
            return false;
        }
        $notice->title = $filter['title'];
        $notice->content = $filter['content'];
        $notice->remark = $filter['remark'];
        $notice->status = $filter['status']?:1;
        $res = $notice->save();
        if($res){
            return true;
        }
        return false;
    }

    /**
     * 删除便签
     */
    public function deleteMemo($notice_id)
    {
        $notice = CrmNotice::model()->findByPk($notice_id);
        if(empty($notice) || $notice->type != 2 || $notice->adder != Yii::app()->user->id){
            return false;
        }
        $res = $notice->delete();
        if($res){
            return true;
        }
        return false;
    }
    /** 公告便签相关结束 */
    /** 动态相关开始  */
    /**
     * 获取管理员动态
     * @param int|array $admin_id 管理员ID
     * @param int $offset 偏移
     * @param int $limit 数量
     * @return array
     */
    public function getDynamic($admin_id,$offset = 0, $limit = 4)
    {
        $admin_id = $this->filter($admin_id);
        //如果你是组长，你就显示组员们的信息
        $admin_ids = $this->getAdminIDsByLeader($admin_id)?:[];
        //只显示三天内的动态（因为有周末）
        $starttime = strtotime("-3 days midnight");
        if($admin_ids){
            $admins = implode(",",$admin_ids);
            //失败比未回调优先级高
            $sql = "SELECT d.* from crm_user_dynamic d left join crm_user u on u.user_id = d.user_id where u.admin_id in ($admins) and d.status = 1 and `datetime` > :starttime order by `type`%2,`datetime` desc limit :offset,:limit";
            $dynamics = Yii::app()->crmdb->createCommand($sql)
                ->bindParam(":offset",$offset,PDO::PARAM_INT)
                ->bindParam(":limit",$limit,PDO::PARAM_INT)
                ->bindParam(":starttime",$starttime,PDO::PARAM_INT)
                ->queryAll();
        }else {
            //失败比未回调优先级高
            $sql = "SELECT d.* from crm_user_dynamic d left join crm_user u on u.user_id = d.user_id where u.admin_id = :admin_id and d.status = 1 and `datetime` > :starttime order by `type`%2,`datetime` desc limit :offset,:limit";
            $dynamics = Yii::app()->crmdb->createCommand($sql)
                ->bindParam(":offset",$offset,PDO::PARAM_INT)
                ->bindParam(":limit",$limit,PDO::PARAM_INT)
                ->bindParam(":starttime",$starttime,PDO::PARAM_INT)
                ->bindParam(":admin_id",$admin_id,PDO::PARAM_INT)
                ->queryAll();
        }
        $result = [];
        foreach($dynamics as $dynamic){
            $user = User::model()->findByPk($dynamic["user_id"]);
            $l = [
                "id" => $dynamic["id"],
                "user_id"    => $dynamic["user_id"],
                "name"   => $user->realname?FunctionUtil::splitName($user->realname)["data"].($user->sex==1?"先生":($user->sex==2?"女士":"**")):"-",
                "phone"      => $user->phone?FunctionUtil::MaskTel($user->phone):'-',
                "type"       => $this->dynamic_types[$dynamic['type']]?:"-",
                "datetime"   => date("Y-m-d H:i:s",$dynamic['datetime']),
            ];
            $result[] = $l;
        }
        return $result;
    }

    /**
     * 隐藏动态
     * @param int $dynamic_id 动态编号
     */
    public function hideDynamic($dynamic_id)
    {
        $dynamic = CrmUserDynamic::model()->findByPk($dynamic_id);
        if($dynamic){
            $dynamic->status = 2;
            $dynamic->save();
        }
        return true;
    }

    /** 动态相关结束  */

    public function getUnmark($admin_id,$filter = array())
    {
        $filter = $this->filter($filter);
        $returnResult = array(
            'code' => 1, 'info' => "获取失败！", 'data' => []
        );
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        $countsql = "SELECT count(*) as c from crm_call_record cr left JOIN crm_user u on u.user_id = cr.user_id and u.admin_id = cr.admin_id and u.allot_time > cr.start_time where cr.status = 2 and u.admin_id = :admin_id and u.is_call = 0";
        $count = Yii::app()->crmdb->createCommand($countsql)->bindParam(":admin_id",$admin_id,PDO::PARAM_INT)->queryScalar()?:0;
        if($offset >= $count){
            return $returnResult;
        }
        $returnResult["data"]["listTotal"] = intval($count);
        $sql = "SELECT cr.user_id,cr.call_id,cr.talk_time,cr.addtime from crm_call_record cr left JOIN crm_user u on u.user_id = cr.user_id and u.admin_id = cr.admin_id and u.allot_time > cr.start_time where cr.status = 2 and u.admin_id = :admin_id and u.is_call = 0 order by cr.start_time desc limit :offset,:limit";
        $calls = Yii::app()->crmdb->createCommand($sql)->bindParam(":admin_id",$admin_id,PDO::PARAM_INT)->bindParam(":offset",$offset,PDO::PARAM_INT)->bindParam(":limit",$limit,PDO::PARAM_INT)->queryAll()?:[];
        $list = [];
        foreach($calls as $call){
            $user = User::model()->findByPk($call['user_id']);
            $l = $call;
            $l["name"] = $user->realname?mb_substr($user->realname, 0, 1, 'utf-8').'**':"-";
            $l["phone"] = $user->phone?FunctionUtil::MaskTel($user->phone):"-";
            $l["addtime"] = date("Y-m-d H:i:s",$call['addtime']);
            $l["call_time"] = $this->formatTime($call['talk_time']);
            $list[] = $l;
        }
        $returnResult["data"]["listInfo"] = $list;
        $returnResult["code"] = 0;
        $returnResult["info"] = "获取成功！";
        return $returnResult;
    }

    /**
     * 获取客维统计数据
     */
    public function getTsStatList($filter = array())
    {
        $filter = $this->filter($filter);
        $returnResult = array(
            'code' => 1, 'info' => "获取失败！", 'data' => []
        );
        $criteria = new CDbCriteria;
        if($filter["name"]){
            $criteria->addSearchCondition("name",$filter["name"]);
        }
        $count = CrmAdmin::model()->countByAttributes(["type"=>[4,5]],$criteria);
        $page = $filter['page']?:1;
        $limit = $filter['limit']?:10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $admins = CrmAdmin::model()->findAllByAttributes(["type"=>[4,5]],$criteria);
        $admin_ids = [];
        $data = [];
        foreach($admins as $admin){
            $admin_ids[] = $admin->admin_id;
            $data[$admin->admin_id] = [
                "admin_id" => $admin->admin_id,
                "name" => $admin->name,
                "status" => $admin->status,
                "callOut" => 0,   //呼出
                "getThrough" => 0, //接通
                "getThroughPre" => 0, //接通率
                "getThroughSum" => 0, //外呼总时长
                "getThroughAve" => 0, //外呼平均时长
                "pTransferAccount" => 0, //首投额
                "pInvestAccount" => 0, //总投资额
                "pRepeatAccount" => 0, //复投额
                "allot" => 0, // 私海用户
            ];
        }
        $startTime = intval($filter['date'][0])?:strtotime(date('Y-m'));
        $endTime = intval($filter['date'][1])?:time();
        $adminids = implode(",",$admin_ids);
        $_callout_sql = "SELECT admin_id,count(*) as num from crm_call_record where admin_id in ($adminids) and addtime between :starttime and :endtime group by admin_id";
        $_getthrough_sql = "SELECT admin_id,count(*) as num,sum(talk_time) as talk_time from crm_call_record where admin_id in ($adminids) and `status` = 2 and addtime between :starttime and :endtime group by admin_id";
        $callout = Yii::app()->crmdb->createCommand($_callout_sql)->bindParam(":starttime",$startTime,PDO::PARAM_INT)->bindParam(":endtime",$endTime,PDO::PARAM_INT)->queryAll();
        $getthrough = Yii::app()->crmdb->createCommand($_getthrough_sql)->bindParam(":starttime",$startTime,PDO::PARAM_INT)->bindParam(":endtime",$endTime,PDO::PARAM_INT)->queryAll();
        foreach($callout as $value){
            $data[$value['admin_id']]["callOut"] = $value['num'];
        }
        foreach($getthrough as $value){
            $data[$value['admin_id']]["getThrough"] = $value['num'];
            $data[$value['admin_id']]["getThroughPre"] = intval($value['num']*100/$data[$value['admin_id']]["callOut"]);
            $data[$value['admin_id']]["getThroughSum"] = $this->formatTime($value['talk_time']);
            $data[$value['admin_id']]["getThroughAve"] = $this->formatTime(intval($value['talk_time']/$value['num']));
        }
        $_account_sql = "SELECT admin_id,`first`,sum(account_init) as account from crm_new_tender where admin_id in ($adminids) and status = 1 and addtime between :starttime and :endtime group by admin_id,`first`";
        $account = Yii::app()->crmdb->createCommand($_account_sql)->bindParam(":starttime",$startTime,PDO::PARAM_INT)->bindParam(":endtime",$endTime,PDO::PARAM_INT)->queryAll();
        foreach($account as $value){
            if($value["first"]){
                $data[$value['admin_id']]["pTransferAccount"] = $value['account'];
                $data[$value['admin_id']]["pInvestAccount"] = $value['account'] + $data[$value['admin_id']]["pRepeatAccount"];
            }else{
                $data[$value['admin_id']]["pRepeatAccount"] = $value['account'];
                $data[$value['admin_id']]["pInvestAccount"] = $value['account'] + $data[$value['admin_id']]["pTransferAccount"];
            }
        }
        $_allot_sql = "SELECT admin_id,count(*) as num from crm_user where admin_id in ($adminids) group by admin_id";
        $allot = Yii::app()->crmdb->createCommand($_allot_sql)->queryAll();
        foreach($allot as $value){
            $data[$value['admin_id']]["allot"] = $value['num'];
        }
        $returnResult["data"]["listTotal"] = intval($count);
        $returnResult["data"]["listInfo"] = array_values($data);
        $returnResult["code"] = 0;
        $returnResult["info"] = "获取成功！";
        return $returnResult;
    }

    public function getCsStatList($filter = array())
    {
        $filter = $this->filter($filter);
        $returnResult = array(
            'code' => 1, 'info' => "获取失败！", 'data' => []
        );
        $criteria = new CDbCriteria;
        if($filter["name"]){
            $criteria->addSearchCondition("name",$filter["name"]);
        }
        $count = CrmAdmin::model()->countByAttributes(["type"=>[2,3]],$criteria);
        $page = $filter['page']?:1;
        $limit = $filter['limit']?:10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $admins = CrmAdmin::model()->findAllByAttributes(["type"=>[2,3]],$criteria);
        $admin_ids = [];
        $data = [];
        foreach($admins as $admin){
            $admin_ids[] = $admin->admin_id;
            $data[$admin->admin_id] = [
                "admin_id" => $admin->admin_id,
                "name" => $admin->name,
                "status" => $admin->status,
                "callIn" => 0, //呼入
                "getThrough" => 0, //接通
                "getThroughPre" => 0, //接通率
                "getThroughSum" => 0, //接听总时长
                "getThroughAve" => 0, //接听平均时长
                "waitTime" =>0, //等待总时长
                "waitAve" =>0,  //等待平均时长
                "callOut" => 0,   //呼出
                "callOutTime" => 0,   //呼出时长
            ];
        }
        $startTime = $filter['date'][0]?:strtotime(date('Y-m'));
        $endTime = $filter['date'][1]?:time();
        $adminids = implode(",",$admin_ids);
        $_callin_sql = "SELECT admin_id,count(*) as num from ccs_call_record where admin_id in ($adminids) and `type` = 2 and addtime between :starttime and :endtime group by admin_id";
        $_callout_sql = "SELECT admin_id,count(*) as num,sum(talk_time) as talk_time from ccs_call_record where admin_id in ($adminids) and `type` = 1 and addtime between :starttime and :endtime group by admin_id";
        $_getthrough_sql = "SELECT admin_id,count(*) as num,sum(talk_time) as talk_time,sum(ring_secs) as ring_time from ccs_call_record where admin_id in ($adminids) and `type` = 2  and `call_status` = 2 and addtime between :starttime and :endtime group by admin_id";
        $callIn = Yii::app()->ccsdb->createCommand($_callin_sql)->bindParam(":starttime",$startTime,PDO::PARAM_INT)->bindParam(":endtime",$endTime,PDO::PARAM_INT)->queryAll();
        $callout = Yii::app()->ccsdb->createCommand($_callout_sql)->bindParam(":starttime",$startTime,PDO::PARAM_INT)->bindParam(":endtime",$endTime,PDO::PARAM_INT)->queryAll();
        $getthrough = Yii::app()->ccsdb->createCommand($_getthrough_sql)->bindParam(":starttime",$startTime,PDO::PARAM_INT)->bindParam(":endtime",$endTime,PDO::PARAM_INT)->queryAll();
        foreach($callIn as $value){
            $data[$value['admin_id']]["callIn"] = $value['num'];
            $data[$value['admin_id']]["callIn"] = $value['num'];
        }
        foreach($getthrough as $value){
            $data[$value['admin_id']]["getThrough"] = $value['num'];
            $data[$value['admin_id']]["getThroughPre"] = intval($value['num']*100/$data[$value['admin_id']]["callIn"]);
            $data[$value['admin_id']]["getThroughSum"] = $this->formatTime($value['talk_time']);
            $data[$value['admin_id']]["getThroughAve"] = $this->formatTime(intval($value['talk_time']/$value['num']));
            $data[$value['admin_id']]["waitTime"] = $this->formatTime($value['ring_time']);
            $data[$value['admin_id']]["waitAve"] = $this->formatTime(intval($value['ring_time']/$value['num']));
        }
        foreach($callout as $value){
            $data[$value['admin_id']]["callOut"] = $value['num'];
            $data[$value['admin_id']]["callOutTime"] = $value['num'];
        }
        $returnResult["data"]["listTotal"] = intval($count);
        $returnResult["data"]["listInfo"] = array_values($data);
        $returnResult["code"] = 0;
        $returnResult["info"] = "获取成功！";
        return $returnResult;
    }


    public function getBorrowList($filter = array())
    {
        $filter = $this->filter($filter);
        $returnResult = array(
            'code' => 1, 'info' => "获取失败！", 'data' => []
        );
        $criteria = new CDbCriteria;
        $criteria->addCondition("status <> 0");
        if($filter["name"]){
            $criteria->addSearchCondition("name",$filter["name"]);
        }
        if($filter["type"]){
            switch($filter["type"]){
                case 2:
                case 5:
                case 6:
                case 7:
                case 402:
                case 3000:
                case 3100:
                    $criteria->addCondition("type = {$filter["type"]}");
                    break;
                case 100:
                    $criteria->addBetweenCondition("type",100,400);
                    break;
           }
        }
        if($filter["formal"] && count($filter["formal"]) == 2 ){
            $criteria->addBetweenCondition("formal_time",$filter["formal"][0],$filter["formal"][1]);
        }
        if($filter["repayment"]  && count($filter["formal"]) == 2 ){
            $criteria->addBetweenCondition("repayment_time",$filter["repayment"][0],$filter["repayment"][1]);
        }

        $count = Borrow::model()->countByAttributes([],$criteria);
        $page = $filter['page']?:1;
        $limit = $filter['limit']?:10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $criteria->order = "formal_time desc";
        $borrows = Borrow::model()->findAllByAttributes([],$criteria);
        $list = [];
        //0-按日计息 按月付息 到期还本，1-按日计息 到期还本息，2-按日计息 月底付息 到期还本息 ，3-按日计息 按季度付息 到期还本，4-等额本金 安月付款， 5-等额本息 按月付款
        $styles = ["按月付息","到期还本息","月底付息","按季度付息","等额本金","等额本息"];
        $types = [2=>"爱担保",5=>"爱融租",6=>"爱保理",7=>"爱收藏",402=>"省心优选",3000=>"智选集合",3100=>"智选计划"];
        foreach($borrows as $borrow){
            $list[] = [
                "name" => $borrow->name, //标的名
                "type" => $types[$borrow->type]?:"省心计划", //标的类型
                "account" => $borrow->account, // 融资金额
                "account_yes" => $borrow->account_yes, // 已融金额
                "apr" => $borrow->apr, //利率
                "style" => $styles[$borrow->style]?:"-", //还款方式
                "formal" => date("Y-m-d",$borrow->formal_time), //上线时间
                "repayment" =>$borrow->repayment_time?date("Y-m-d",$borrow->repayment_time):"未定", //还款时间
            ];
        }
        $returnResult["data"]["listTotal"] = intval($count);
        $returnResult["data"]["listInfo"] = $list;
        $returnResult["code"] = 0;
        $returnResult["info"] = "获取成功！";
        return $returnResult;
    }

    /**
     * 获取用户会员等级变动
     */
    public function getMemberLevel($user_id,$filter=[])
    {
        $user_id = intval($user_id);
        $returnResult = array(
            'code' => 1, 'info' => "没有相关记录！", 'data' => []
        );
        $filter = $this->filter($filter);
        $connection = Yii::app()->dwdb;
        // 获取数据
        $sql = "SELECT previous_grade_code,present_grade_code,log_type,addtime
            FROM itz_user_grade_log
            WHERE user_id = $user_id ";
        $count_sql = "SELECT count(*) as c
            FROM itz_user_grade_log
            WHERE user_id = $user_id ";

        $count = $connection->createCommand($count_sql)->queryScalar();
        $returnResult["data"]["listTotal"] = intval($count);
        $page = intval($filter['page'])>0?intval($filter['page']):1;
        $limit = intval($filter['limit'])>0?intval($filter['limit']):10;
        $offset = ($page-1) * $limit;
        if($offset >= $count){
            return $returnResult;
        }
        $condition = " order by addtime desc";
        $condition .= " limit $offset,$limit";
        $sql .= $condition;
        $list = $connection->createCommand($sql)->queryAll();
        $listInfo = [];
        foreach($list as $l){
            $listInfo[] = [
                "previous" => 'VIP'.$l['previous_grade_code'],
                "present"  => 'VIP'.$l['present_grade_code'],
                "log_type" => $l['log_type'] == 1 ? '升级' : '降级',
                "addtime"  => date("Y-m-d H:i:s",$l['addtime']),
            ];
        }
        $returnResult["data"]["listInfo"]=$listInfo;
        $returnResult["code"]=0;
        $returnResult["info"]="获取成功！";
        return $returnResult;
    }
}