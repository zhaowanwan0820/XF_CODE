<?php
/**
 */
use iauth\models\User;

class DebtPurchaseService extends ItzInstanceService
{
    public static $status_cn = [
        0=>"待审核",1=>"求购中",2=>"已完成",3=>"已终止",4=>'已拒绝'
    ];

    public static $deal_status_cn=[
        0=>"待审核",1=>"启用中",2=>"已拒绝",3=>"已终止",4=>"已禁用",
    ];
    public static $debt_status_cn = [
        1=>"转让中",2=>"交易成功",3=>"交易取消",4=>"已过期",5=>"待付款", 6=>"待收款确认"
    ];
    public function __construct()
    {
        parent::__construct();
    }

    public function getAssignees($params)
    {
        if (empty($params['status'])) {
            $where[] = " a.status = 2";
        } else {
        }
        if ($params['area_id']) {
            $where[] = "a.area_id = ".$params['area_id'];
        }
        if ($where) {
            $where = " where ".implode(' and ', $where);
        }
        $sql = "select a.*,u.real_name from  xf_debt_assignee_info as a left join firstp2p_user as u on a.user_id = u.id  {$where}";
        $list = Yii::app()->phdb->createCommand($sql)->queryAll();
        return $list;
    }

    
    public function getList($params)
    {
        $M= $params['deal_type']==1?'XfPlanPurchase':"PHXfPlanPurchase";
        $where = '';
        $condition = [];
        //管理员全量
        $addUsers = ArrayUntil::array_column((new User())->getList(1, 10000)['userData'], 'id', 'username');
        //受让人全量
       
        //
        if (!empty($params['area_id'])) {
            $condition[] = " area_id = ".$params['area_id'];
        }
        if (!empty($params['discount'])) {
            $condition[] = " discount = ".$params['discount'];
        }
        if ($params['to_be_processed'] == 1) {
            $condition[] = " traded_num != trading_num";
        }
        if ($params['to_be_processed'] == 2) {
            $condition[] = " traded_num = trading_num";
        }
        if (!empty($params['buyer_people'])) {
            //查数据库
            $userInfo = Yii::app()->fdb->createCommand("SELECT id, real_name from firstp2p_user where real_name = '{$params['buyer_people']}'")->queryRow();
            if (!$userInfo) {
                $user_id = 0;
            } else {
                $user_id = $userInfo['id'];
            }
            $condition[] = " user_id = ".$user_id;
        }
        if (!empty($params['purchase_status'])) {
            $condition[] = " status = ".$params['purchase_status'];
        }
        //发布人搜所
        if (!empty($params['release_people'])) {
            $condition[] = " add_user_id = ".($addUsers[$params['release_people']]?:'-1');
        }
        if (!empty($params['action_start'])) {
            $condition[] = " starttime >= ".strtotime($params['action_start']);
        }
        if (!empty($params['action_end'])) {
            $condition[] = " endtime <= ".(strtotime($params['action_end'])+86400);
        }

        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition);
        }
        
        // $area_list = ArrayUntil::array_column(Yii::app()->c->xf_config['area_list'], 'name', 'id');

        
    
        $fileList = [];
        $countFile = $M::model()->countBySql('select count(1) from xf_plan_purchase  '.$where);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select * from xf_plan_purchase   {$where} order by id desc  LIMIT {$offset} , {$pageSize} ";
            $_file = $M::model()->findAllBySql($sql);
            foreach ($_file as $item) {
                $buyer_users[] = $item->user_id;
                $list['id']=$item->id;
                // $list['area_name']=$area_list[$item->area_id];//专区名称
                $list['discount']=$item->discount;//折扣
                $list['total_amount']=$item->total_amount;//求购总金额
                $list['purchased_amount']=$item->purchased_amount;//已购债权
                $list['budget_amount']=round($item->total_amount*$item->discount/10, 2);//预算金额
                $list['use_amount']=round($item->purchased_amount*$item->discount/10, 2);//已用金额
                $list['trading_amount']=$item->trading_amount;
                $list['traded_num']=$item->traded_num;
                $list['trading_num']=$item->trading_num;
                $list['buyer_user_id'] = $item->user_id;
                $list['add_user_name']=$item->add_user_id;//添加人
                $list['status'] = $item->status;
                $list['status_cn']=self::$status_cn[$item->status];
                $list['start_time']=date('Y/m/d H:i:s', $item->starttime);
                $list['end_time']=date('Y/m/d H:i:s', $item->endtime);
                $fileList[] = $list;
            }
           
            if ($buyer_users) {
                $list = Yii::app()->fdb->createCommand("SELECT id, real_name from firstp2p_user where id in (".implode(',', $buyer_users).")")->queryAll();
               
                $users = ArrayUntil::array_column($list, 'real_name', 'id');
               
                foreach ($fileList as &$value) {
                    $value['buyer_user']= $users[$value['buyer_user_id']];
                }
            }
        }
        return ['countNum' => $countFile, 'list' => $fileList];
    }

    public function getDealList($params)
    {
        $where = ' 1 ';
        if (!empty($params['purchase_id'])) {
            $where .= " and l.purchase_id =".$params['purchase_id'];
        }
        
        if (isset($params['status'])) {
            $where .= " and l.status =".$params['status'];
        }
        if (!empty($params['deal_id'])) {
            $where .= " and l.deal_id =".$params['deal_id'];
        }
        if (isset($params['type']) && in_array($params['type'], [1,2,3,4,5])) {
            $where .= " and  l.type=".$params['type'];
        }
        
        $db = 'phdb';
        $deal_table = "ncfph.firstp2p_deal";
        
        if (!empty($params['name'])) {
            $sql = "select id from  $deal_table where name = '".trim($params['name'])."'";
            $info = Yii::app()->$db->createCommand($sql)->queryRow();
            if (!$info) {
                return ['countNum' => 0, 'list' => []];
            }
            $where .= " and  l.deal_id=".$info['id'];
        }
        $_file=[];
        $countSql = "select count(1) from  xf_plan_purchase_deal as l  where ".$where;
       
        $countFile = Yii::app()->$db->createCommand($countSql)->queryScalar();
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select l.* from xf_plan_purchase_deal as l  where {$where} order by l.id desc  LIMIT {$offset} , {$pageSize} ";
            $_file = Yii::app()->$db->createCommand($sql)->queryAll();
            $deal_id_attr = ArrayUntil::array_column($_file, 'deal_id');
            
            $deal_info= Yii::app()->$db->createCommand("select id,name from $deal_table where id in (".implode(',', $deal_id_attr).")")->queryAll();
            if ($deal_info) {
                foreach ($deal_info as $value) {
                    $deal_infos[$value['id']] = $value['name'];
                }
            }
            foreach ($_file as &$item) {
                $item['created_at']=date('Y-m-d H:i:s', $item['created_at']);
                $item['update_at']=$item['update_at']?date('Y-m-d H:i:s', $item['update_at']):'';
                $item['status_cn'] = self::$deal_status_cn[$item['status']];
                // $item['type_cn'] = $this->type_cn[$item['type']];
                $item['name'] = $deal_infos[$item['deal_id']];
            }
        }
        return ['countNum' => $countFile, 'list' => $_file];
    }
   
    /**
     * 创建求购单
     *
     * @param [type] $area_id
     * @return void
     */
    public function createPurchase($area_id=1)
    {
        try {
            $db = $_POST['deal_type']==1?"fdb":'phdb';
            Yii::app()->$db->beginTransaction();


            if (empty($_POST['buyer_people'])) {
                throw new Exception('受让人不能为空');
            }
            if (empty($_POST['total_amount'])) {
                throw new Exception('求购总额不能为空');
            }
            if (empty($_POST['discount'])) {
                throw new Exception('折扣不能为空');
            }
            if ($_POST['discount'] > 10 || $_POST['discount']< 0.01) {
                throw new Exception('折扣范围 0.01-10');
            }
            if (empty($_POST['period_validity'])) {
                throw new Exception('有效期不能为空');
            }
            if ($_POST['period_validity'] > 30) {
                throw new Exception('有效期最长30天');
            }
            if (empty($_FILES['file']['name'])) {
                throw new Exception('请选择文件上传');
            }

            // 读取数据
            $file = CUploadedFile::getInstanceByName(key($_FILES));
            if ($file->getHasError()) {
                $error = [
                    1 => '上传文件超过了服务器限制',
                    2 => '上传文件超过了脚本限制',
                    3 => '文件只有部分被上传',
                    4 => '没有文件被上传',
                    6 => '找不到临时文件夹',
                    7 => '文件写入失败',
                ];
                throw new Exception(isset($error[$file->getError()]) ? $error[$file->getError()] : '未知错误');
            }

            Yii::$enableIncludePath = false;
            Yii::import('application.extensions.phpexcel.PHPExcel', 1);
            $excelFile     = $file->getTempName();
            $inputFileType = PHPExcel_IOFactory::identify($excelFile);
            $objReader     = PHPExcel_IOFactory::createReader($inputFileType);
            $excelReader   = $objReader->load($excelFile);
            $phpexcel      = $excelReader->getSheet(0);
            $total_line    = $phpexcel->getHighestRow();
            $execlData     = $phpexcel->toArray();

            // 验证模板
            if ($total_line > 31000) {
                throw new Exception('数据量过大');
            }
            // 验证模板
            if ($total_line <=0) {
                throw new Exception('请上传指定借款编号');
            }

            array_shift($execlData);
           

            $dir      = 'upload/purchase/' . date('Ym', time()) . '/';
            $file_dir = $dir . time().mt_rand(1000, 999). '.' . $file->getExtensionName();
            if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
                throw new Exception('创建目录失败');
            }
            if (!$file->saveAs($file_dir)) {
                throw new Exception('上传失败');
            }


            $res     = Yii::app()->phdb->createCommand("select status,transferability_limit,transferred_amount,trading_amount from xf_debt_assignee_info where user_id  = {$_POST['buyer_people']} and area_id = {$area_id} ")->queryRow();
            if (!$res) {
                throw new Exception('受让人不存在');
            }
            if ($res['status'] != 2) {
                throw new Exception('受让人状态不可用');
            }
            $now = time();
           
            //进行中的剩余总额
           
            $surplus     = Yii::app()->$db->createCommand("select sum(total_amount-purchased_amount-trading_amount)  from xf_plan_purchase where user_id  = {$_POST['buyer_people']} and area_id = {$area_id} and status  = 1 and starttime < {$now} and endtime > {$now}")->queryScalar();
           
            if ($_POST['total_amount'] > (bcsub($res['transferability_limit'], ($res['transferred_amount']+$res['trading_amount']+$surplus), 2))) {
                throw new Exception('求购金额超过受让人可受让金额');
            }
            $m = $_POST['deal_type']==1?'XfPlanPurchase':"PHXfPlanPurchase";
           
            $model = new $m();
            $model->area_id = $area_id;
            $model->discount = $_POST['discount'];
            $model->user_id = $_POST['buyer_people'];
            $model->total_amount = $_POST['total_amount'];
            $model->starttime = $now;
            $model->add_time = $now;
            $model->add_ip = Yii::app()->request->userHostAddress;
            $model->add_user_id = \Yii::app()->user->id;
            $model->endtime = strtotime("+".($_POST['period_validity']+1)." day midnight");
            $model->status = 0;
            
            if (!$model->save()) {
                throw new Exception('数据保存失败",请重试');
            }
            $purchase_id = $model->id;
            $execlData = array_unique(ArrayUntil::array_column($execlData, '0'));


            $deal_ids = implode(',', $execlData);
            
            $total_deal = Yii::app()->$db->createCommand("select count(1) from firstp2p_deal where id in ({$deal_ids})")->queryScalar();
           
            if ($total_deal != count($execlData)) {
                throw new Exception('导入借款编号总数与数据库比对数值不一致');
            }

            $insert_sql = "INSERT INTO xf_plan_purchase_deal (`purchase_id`,`deal_id`,`status`,`add_time`,`update_time`)". ' values ';
            $i = 0;
            foreach ($execlData as $deal_id) {
                if (empty($deal_id)) {
                    continue;
                }
                $insert_sql .= " ($purchase_id,$deal_id,0,$now,$now),";
                $i++;
            }
            $insert_sql = rtrim($insert_sql, ',');
            
            if (false === Yii::app()->$db->createCommand($insert_sql)->execute()) {
                throw new Exception('数据导入失败');
            }

            Yii::app()->$db->commit();
            return true;
        } catch (Exception $e) {
            Yii::app()->$db->rollback();

            throw $e;
        }
        # code...
    }
    /**
     * 终止求购单
     *
     * @param [type] $params
     * @return void
     */
    public function stopPurchase($params)
    {
        try {
            $db = $_POST['deal_type']==1?"fdb":'phdb';
            Yii::app()->$db->beginTransaction();

            if (empty($params['id'])) {
                throw new Exception('求购信息ID不能为空');
            }
            
            $m = $params['deal_type']==1?'XfPlanPurchase':"PHXfPlanPurchase";
            $model = $m::model()->findByAttributes(['id'=>$params['id']]);
            
            if ($model->status == 0) {
                throw new Exception('该笔求购信息未审核');
            }
            if ($model->status == 4) {
                throw new Exception('该笔求购信息审核被拒绝');
            }

            if ($model->status == 3) {
                throw new Exception('该笔求购信息已经终止');
            }
            if ($model->status == 2) {
                throw new Exception('该笔求购信息已完成');
            }
           
            $model->status = 3;
            
            if (!$model->save()) {
                throw new Exception('数据保存失败，请重试');
            }
            $now = time();
            $update_sql = "update xf_plan_purchase_deal set status = 3,update_time={$now} where purchase_id ={$params['id']} ";
            if (false === Yii::app()->$db->createCommand($update_sql)->execute()) {
                throw new Exception('债权明细更新状态失败');
            }

            Yii::app()->$db->commit();
            Yii::log('debt purchase stopPurchase: admin_id ['.\Yii::app()->user->id.'] 终止了 id为 ['.$params['id'].'] 的求购记录', 'info', __CLASS__);
            return true;
        } catch (Exception $e) {
            Yii::app()->$db->rollback();
            throw $e;
        }
    }

    /**
    * 审核求购单
    *
    * @param [type] $params
    * @return void
    */
    public function authPurchase($params)
    {
        try {
            $db = $params['deal_type']==1?"fdb":'phdb';
            Yii::app()->$db->beginTransaction();

            if (empty($params['id'])) {
                throw new Exception('求购信息ID不能为空');
            }
            
            $m = $params['deal_type']==1?'XfPlanPurchase':"PHXfPlanPurchase";
            $model = $m::model()->findByAttributes(['id'=>$params['id']]);
            
           
            if ($model->status == 4) {
                throw new Exception('该笔求购信息审核被拒绝');
            }

            if ($model->status == 3) {
                throw new Exception('该笔求购信息已经终止');
            }
            if ($model->status == 2) {
                throw new Exception('该笔求购信息已完成');
            }

            $res     = Yii::app()->phdb->createCommand("select status,transferability_limit,transferred_amount,trading_amount from xf_debt_assignee_info where user_id  = {$model->user_id} and area_id = {$params['area_id']} ")->queryRow();
            if (!$res) {
                throw new Exception('受让人不存在');
            }
            if ($res['status'] != 2) {
                throw new Exception('受让人状态不可用');
            }
            $now = time();

            //进行中的剩余总额
           
            $surplus     = Yii::app()->$db->createCommand("select sum(total_amount-purchased_amount-trading_amount)  from xf_plan_purchase where user_id  = {$model->user_id} and area_id = {$params['area_id']} and status  = 1 and starttime < {$now} and endtime > {$now}")->queryScalar();
           
            if ($model->total_amount > (bcsub($res['transferability_limit'], ($res['transferred_amount']+$res['trading_amount']+$surplus), 2))) {
                throw new Exception('求购金额超过受让人可受让金额');
            }

            if ($params['type']==1) {
                $model->status = 1;
                $str = '通过';
            } else {
                $model->status = 4;
                $str = '拒接';
            }
            
            if (!$model->save()) {
                throw new Exception('数据保存失败，请重试');
            }
            $deal_auth_status = $params['type']==1?1:2;
            $now = time();
            $update_sql = "update xf_plan_purchase_deal set status = {$deal_auth_status},update_time={$now} where purchase_id ={$params['id']} ";
            if (false === Yii::app()->$db->createCommand($update_sql)->execute()) {
                throw new Exception('债权明细更新状态失败');
            }

            Yii::app()->$db->commit();
            Yii::log('debt purchase authPurchase: admin_id ['.\Yii::app()->user->id.'] 审核'.$str.' id为 ['.$params['id'].'] 的求购记录 ', 'info', __CLASS__);

            return true;
        } catch (Exception $e) {
            Yii::app()->$db->rollback();
            throw $e;
        }
    }
    /**
     * 单个项目是否启用
     *
     * @param [type] $params
     * @return void
     */
    public function purchaseDealStatus($params)
    {
        try {
            $db = $_POST['deal_type']==1?"fdb":'phdb';

            if ($params['type']==1) {
                $deal_auth_status = 1;
                $str = '启用';
            } else {
                $deal_auth_status = 4;
                $str = '禁用';
            }
            $now = time();
            $update_sql = "update xf_plan_purchase_deal set status = {$deal_auth_status},update_time={$now} where id ={$params['id']} ";
            if (false === Yii::app()->$db->createCommand($update_sql)->execute()) {
                throw new Exception('债权明细更新状态失败');
            }
            Yii::log('debt purchase purchaseDealStatus: admin_id ['.\Yii::app()->user->id.'] '.$str.' id为 ['.$params['id'].'] 的项目记录 ', 'info', __CLASS__);

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 求购出售详情
     *
     * @param [type] $params
     * @return void
     */
    public function purchaseDetail($params)
    {
        $db = $params['deal_type']==1?'fdb':"phdb";
        if (empty($params['id'])) {
            throw new Exception('数据保存失败，请重试');
        }
        $where[] = " d.purchase_id = ".$params['id'];
        if ($params['status']==1) {
            $where[] = " d.status = 2 ";
        } elseif ($params['status']==2) {
            $where[] = " d.status in (5,6) ";
        }
        if (!empty($params['real_name'])) {
            $where[] = " u.real_name = '".$params['real_name']."'";
        }
        
        if (!empty($params['bank_num'])) {
            $where[] = "d.payee_bankcard  = '".GibberishAESUtil::enc($params['bank_num'], Yii::app()->c->idno_key)."'";
        }
        if (!empty($params['user_id'])) {
            $where[] = "d.user_id = ".$params['user_id'];
        }
        $where = " where ".implode(' and ', $where);
        $count_sql =  "select count(1) from firstp2p_debt as d left join firstp2p_user as u on d.user_id = u.id left join firstp2p_deal as dl on d.borrow_id = dl.id   {$where} ";
        $count = Yii::app()->$db->createCommand($count_sql)->queryScalar();
        $page = $params['page'] ?: 1;
        $pageSize = $params['pageSize'] ?: 10;
        $offset = ($page - 1) * $pageSize;
        $sql = "select d.id, d.addtime,d.money, d.status, d.tender_id, d.user_id, d.serial_number, d.payee_bankcard, u.real_name, dl.name from firstp2p_debt as d left join firstp2p_user as u on d.user_id = u.id left join firstp2p_deal as dl on d.borrow_id = dl.id   {$where} order by d.id desc  LIMIT {$offset} , {$pageSize} ";
      
        $list = Yii::app()->$db->createCommand($sql)->queryAll();
        if ($list) {
            foreach ($list as &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
                $value['money'] = number_format($value['money'], 2);
                $value['payee_bankcard'] = GibberishAESUtil::dec($value['payee_bankcard'], Yii::app()->c->idno_key);
                $value['status_cn'] = self::$debt_status_cn[$value['status']];
            }
        }

        return ['countNum' => $count, 'list' => $list];
    }

    public function saveCredentials($params)
    {
        try {
            if (empty($params['debt_id'])) {
                throw new Exception('债权出售ID不能为空');
            }
            if (empty($params['payment_voucher'])) {
                throw new Exception('付款凭证不能为空');
            }
            $now = time();
            $db= $params['deal_type']==1?'fdb':"phdb";
            Yii::app()->$db->beginTransaction();
            $debt_info  = Yii::app()->$db->createCommand("select status,user_id from firstp2p_debt where id  = {$params['debt_id']} for update ")->queryRow();
            if (!$debt_info) {
                throw new Exception('转让记录不存在');
            }

            if ($debt_info['status']!=5) {
                throw new Exception('转让状态已经变更，请核实');
            }
            
            $sql = "UPDATE firstp2p_debt_tender SET status = 6 , payment_voucher = '{$params['payment_voucher']}' , submit_paytime={$now} WHERE debt_id = {$params['debt_id']} ";
            $res = Yii::app()->$db->createCommand($sql)->execute();
            
            if (! $res) {
                throw new Exception('数据保存失败，请重试-1');
            }

            $sql = "UPDATE firstp2p_debt SET status = 6  WHERE id = {$params['debt_id']} ";
            $res = Yii::app()->$db->createCommand($sql)->execute();
            if (! $res) {
                throw new Exception('数据保存失败，请重试-2');
            }
            $userInfo = Yii::app()->fdb->createCommand("SELECT id, mobile from firstp2p_user where id  = {$debt_info['user_id']}")->queryRow();
            if (!$userInfo) {
                throw new Exception('出售方不存在');
            }
    
            $mobile = GibberishAESUtil::dec($userInfo['mobile'], Yii::app()->c->idno_key);
            $smaClass                   = new XfSmsClass();
            $remind                     = array();
            $remind['sms_code']         = "receipt_uploaded_to_seller";
            $remind['mobile']           = $mobile;
            $send_ret_a                 = $smaClass->sendToUserByPhone($remind);
            if ($send_ret_a['code'] != 0) {
                Yii::log("upload Credentials send sms error debt id:{$params['debt_id']} info:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
            }

            Yii::app()->$db->commit();
            return true;
        } catch (Exception $e) {
            Yii::app()->$db->rollback();
            throw $e;
        }
    }

    public function viewCredentials($params)
    {
        $url = '';
        if (empty($params['debt_id'])) {
            return  $url;
        }
        $db= $params['deal_type']==1?'fdb':"phdb";
        Yii::app()->$db->beginTransaction();
        $debt_tender_info  = Yii::app()->$db->createCommand("select payment_voucher from firstp2p_debt_tender where debt_id  = {$params['debt_id']}  ")->queryRow();
        if (!$debt_tender_info) {
            return  $url;
        }
        if (empty($debt_tender_info['payment_voucher'])) {
            return  $url;
        }
        return Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$debt_tender_info['payment_voucher'];
    }
}
