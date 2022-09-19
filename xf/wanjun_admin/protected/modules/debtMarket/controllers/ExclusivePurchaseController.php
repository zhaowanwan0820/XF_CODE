<?php
use iauth\models\AuthAssignment;

class ExclusivePurchaseController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
              'Upload','GetPeopleData','GetDebtData','GetAccountData','GetStatisticsList','UserDebtList','GetUserInfo','AssigneeChangeUserId'
         );
    }
   
    /**
     * 专属求购
     *
     * @return void
     */
    public function actionIndex()
    {
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'              => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize'          => $pageSize,
                'user_id'          =>Yii::app()->request->getParam('user_id'),
                'real_name'   =>Yii::app()->request->getParam('real_name'),
                'mobile_phone'      =>Yii::app()->request->getParam('mobile_phone'),
                'status'   =>Yii::app()->request->getParam('status'),
            ];
            //获取用户列表
            $importFileInfo         = ExclusivePurchaseService::getInstance()->getList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }
        $res = ExclusivePurchaseService::getInstance()->getLoginUserAssigneeID();

        return $this->renderPartial('index', ['is_assignee'=>!!$res]);
    }
    
    /**
     * 创建求购
     *
     * @return void
     */
    public function actionCreate()
    {
        if (\Yii::app()->request->isPostRequest) {
            try {
                $params   = [
                    'user_id'     => \Yii::app()->request->getParam('user_id'),
                    'purchase_amount'     => \Yii::app()->request->getParam('purchase_amount'),
                    'user_chong_ti_cha'     => \Yii::app()->request->getParam('user_chong_ti_cha'),
                    'discount'     => \Yii::app()->request->getParam('discount'),
                    'purchase_type'     => \Yii::app()->request->getParam('purchase_type'),
                ];
                $params['buyer_user_id'] = ExclusivePurchaseService::getInstance()->getLoginUserAssigneeID();
                
                $res = ExclusivePurchaseService::getInstance()->createPurchase($params);
                if ($res) {
                    $importFileInfo['code'] = 0;
                    $importFileInfo['info'] = 'success';
                    echo json_encode($importFileInfo);
                    exit;
                }
            } catch (Exception $e) {
                $importFileInfo['code'] = 100;
                $importFileInfo['info'] = $e->getMessage();
                echo json_encode($importFileInfo);
                exit;
            }
        }
        return $this->renderPartial('create');
    }

    /**
     * 求购记录详情
     *
     * @return void
     */
    public function actionDetail()
    {
        $id =  \Yii::app()->request->getParam('id');
       
        $data['purchaseInfo']  = ExclusivePurchaseService::getInstance()->purchaseDetail($id);
        return $this->renderPartial('detail', $data);
    }
    

    /**
     * 求购记录审核
     *
     * @return void
     */
    public function actionAudit()
    {
        $id =  \Yii::app()->request->getParam('id');
        if (\Yii::app()->request->isPostRequest) {
            try {
                //获取用户列表
                $res['data']         = ExclusivePurchaseService::getInstance()->confirmPay($id);
                $res['code'] = 0;
                $res['info'] = 'success';
                echo json_encode($res);
                die;
            } catch (\Exception  $e) {
                $res['code'] = 100;
                $res['info'] = $e->getMessage();
                echo json_encode($res);
                exit;
            }
        }
        $data['purchaseInfo']  = ExclusivePurchaseService::getInstance()->purchaseDetail($id);
        return $this->renderPartial('audit', $data);
    }
    

      

    /**
     * 求购记录审核
     *
     * @return void
     */
    public function actionOfflineAudit()
    {
       
        $id =  \Yii::app()->request->getParam('id');
        if (\Yii::app()->request->isPostRequest) {
           
            try {
                $params   = [
                    'id'     => \Yii::app()->request->getParam('id'),
                    'image_url'     => \Yii::app()->request->getParam('image_url'),
                ];
                //获取用户列表
                $res['data']         = ExclusivePurchaseService::getInstance()->offlineConfirmPay($params);
                $res['code'] = 0;
                $res['info'] = 'success';
                echo json_encode($res);
                die;
            } catch (\Exception  $e) {
                $res['code'] = 100;
                $res['info'] = $e->getMessage();
                echo json_encode($res);
                exit;
            }
        }
        $data['purchaseInfo']  = ExclusivePurchaseService::getInstance()->purchaseDetail($id);
        return $this->renderPartial('offline_audit', $data);
    }
    


    /**
    * 查看用户债权列表
    *
    * @return void
    */
    public function actionUserDebtList()
    {
        if (\Yii::app()->request->isPostRequest) {
            $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
            $params   = [
                'page'              => \Yii::app()->request->getParam('page') ?: 1,
                'pageSize'          => $pageSize,
                'id'          =>Yii::app()->request->getParam('id'),
                
            ];
            //获取用户列表
            $importFileInfo         = ExclusivePurchaseService::getInstance()->getUserExclusiveDebtList($params);
            $importFileInfo['code'] = 0;
            $importFileInfo['info'] = 'success';
            echo json_encode($importFileInfo);
            die;
        }
       
        return $this->renderPartial('user_debt_list');
    }

    /**
     * 创建专属收购 获取用户信息
     *
     * @return void
     */
    public function actionGetUserInfo()
    {
        $user_id =  Yii::app()->request->getParam('user_id');

       

        //获取用户列表
        $res         = ExclusivePurchaseService::getInstance()->getUserInfo($user_id);
        
        if ($res) {
            $buyer_user_id= ExclusivePurchaseService::getInstance()->getLoginUserAssigneeID();

            $sql     = "SELECT * FROM xf_assignee_user WHERE assignee_user_id = {$buyer_user_id} and user_id = {$user_id} and status = 1 and purchase_status = 0 ";
            $re     = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$re) {
                $info['code'] = 100;
                $info['info'] = '出借人未分配至当前受让人';
                echo json_encode($info);
                die;
            }
          
            $info['data'] = $res;
            $info['code'] = 0;
            $info['info'] = 'success';
        } else {
            $info['code'] = 100;
            $info['info'] = '用户不存在';
        }
       
        echo json_encode($info);
        die;
    }

    public function actionAssigneeChangeUserId()
    {
        if (!empty($_POST['buyer_user_id'])) {
            $buyer_user_id = trim($_POST['buyer_user_id']);
           
            if (!is_numeric($buyer_user_id) || $buyer_user_id < 1) {
                $this->echoJson(array(), 2, '请正确输入受让人ID');
            }
            $buyer_user_id = intval($buyer_user_id);

          

            $sql     = "SELECT * FROM firstp2p_user WHERE id = {$buyer_user_id}";
            $res     = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 3, '受让人不存在');
            }
            if ($res['is_effect'] != 1) {
                $this->echoJson(array(), 4, '此用户账号无效');
            }
            if ($res['is_delete'] != 0) {
                $this->echoJson(array(), 5, '此用户账号已被放入回收站');
            }
            if ($res['user_type'] != 1) {
                $this->echoJson(array(), 5, '该用户非企业用户');
            }
            
            $result['user_id']   = $res['id'];
            $result['real_name'] = $res['real_name'];
            if ($res['mobile']) {
                $result['mobile'] = GibberishAESUtil::dec($res['mobile'], Yii::app()->c->idno_key);
            } else {
                $result['mobile'] = '';
            }
            if ($res['idno']) {
                $result['idno'] = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            } else {
                $result['idno'] = '';
            }
            $this->echoJson($result, 0, '查询成功');
        } else {
            $this->echoJson(array(), 1, '请输入受让人ID');
        }
    }

    public function actionEditUserBank()
    {

        if (\Yii::app()->request->isPostRequest) {
            $params   = [
                'user_id'=>Yii::app()->request->getParam('user_id'),
                'bankcard'=>Yii::app()->request->getParam('bankcard'),
                'bank_mobile'=>Yii::app()->request->getParam('bank_mobile'),
                'bank_name'=>Yii::app()->request->getParam('bank_name'),
                'sms_code'=>Yii::app()->request->getParam('sms_code'),
                'step'=>Yii::app()->request->getParam('step')
            ];
           
            try {
                ExclusivePurchaseService::getInstance()->editUserBank($params);
                $result['code'] = 0;
                $result['info'] = 'success';
            } catch (Exception $e) {
                $result['code'] = 100;
                $result['info'] = $e->getMessage();
            }
            echo json_encode($result);
            die;
        }
        $user_id = Yii::app()->request->getParam('user_id')?:0;
       
       
        return $this->renderPartial('edit_user_bank', ['user_id'=>$user_id ]);
    }
    //数据统计
    public function actionStats()
    {
        return $this->renderPartial('statistics');
    }
    //数据统计
    public function actionGetAmountData()
    {
        header("Content-type:application/json; charset=utf-8");
        $result_data = ['data'=>[], 'code'=>0, 'info'=>''];
        $name = [
            'total_quotas' => '总资金额度',
            'frozen_quotas' => '冻结中',
            'surplus_quotas' => '剩余额度',
            'finish_quotas' => '交易完成',
        ];

        $res = ExclusivePurchaseService::getInstance()->getQuotasStat();
        $data['total'] = $res['total_quotas'];
        unset($res['total_quotas']);
        foreach ($res as $key => $value) {
            $data['detail'][] = [
                'name' => $name[$key],
                'value' => $value,
            ];
        }
    
        $result_data['data'] = $data;
        $result_data['info']  = '查询成功';
        echo exit(json_encode($result_data));
    }

    //数据统计
    public function actionGetDebtData()
    {
        header("Content-type:application/json; charset=utf-8");
        $result_data = ['data'=>[], 'code'=>0, 'info'=>''];
        $name = [
            'total_debt' => '总债权金额',
            'be_sign_debt' => '待签约',
            'be_paid_debt' => '待付款',
            'finish_debt' => '交易完成',
        ];

        $res = ExclusivePurchaseService::getInstance()->getDebtStat();
        $data['total'] = $res['total_debt'];
        unset($res['total_debt']);
        foreach ($res as $key => $value) {
            $data['detail'][] = [
                'name' => $name[$key],
                'value' => $value,
            ];
        }
    
        $result_data['data'] = $data;
        $result_data['info']  = '查询成功';
        echo exit(json_encode($result_data));
    }


    //数据统计
    public function actionGetPeopleData()
    {
        header("Content-type:application/json; charset=utf-8");
        $result_data = ['data'=>[], 'code'=>0, 'info'=>''];
        $name = [
         
            'total_user' => '总出借人数',
            'be_sign_user' => '待签约',
            'be_paid_user' => '待付款',
            'finish_user' => '已出清',
            'fail_user' => '失败',
        
        ];

        $res = ExclusivePurchaseService::getInstance()->getUserStat();
        $data['total'] = $res['total_user'];
        unset($res['total_user']);
        foreach ($res as $key => $value) {
            $data['detail'][] = [
                'name' => $name[$key],
                'value' => $value,
            ];
        }
    
        $result_data['data'] = $data;
        $result_data['info']  = '查询成功';
        echo exit(json_encode($result_data));
    }

    public function actionGetStatisticsList()
    {
        $pageSize = $this->pageSize = \Yii::app()->request->getParam('limit') ?: 10; //展示几条
        $params   = [
            'page'              => \Yii::app()->request->getParam('page') ?: 1,
            'pageSize'          => $pageSize,
         
        ];
        //获取用户列表
        $importFileInfo         = ExclusivePurchaseService::getInstance()->getStatisticsList($params);
        $importFileInfo['code'] = 0;
        $importFileInfo['info'] = 'success';
        echo json_encode($importFileInfo);
        die;
    }

    public function actionCompanyUser()
    {
       
        $user_id = \Yii::app()->user->id;
        $where = " id = {$user_id}";
        $userInfo = Yii::app()->db->createCommand("select * from itz_user where $where ")->queryRow();
        $assignee_id = $userInfo['assignee_id'];


        if (!empty($_POST)) {
            // 条件筛选
            /*
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            if ($adminUserInfo['username'] == Yii::app()->iDbAuthManager->admin) {

            }else{

            }*/
            if(!empty($assignee_id)){
                $awhere = " and xau.assignee_user_id = $assignee_id ";
            }else{
                $awhere = "";
            }
            // 校验用户ID
            $where = '';
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND xau.user_id = {$user_id} ";
            }
            // 校验姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where  .= " AND fu.real_name = '{$real_name}' ";
            }
            // 校验手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND fu.mobile = '{$mobile}' ";
            }
            // 校验状态
            if (isset($_POST['status']) && $_POST['status'] !== '') {
                $sta    = intval($_POST['status']);
                if($sta == 6){
                    $where .= " and ep.status is null ";
                }else{
                    $where .= " AND ep.status = {$sta} ";
                }
            }

            if (empty($where) && empty($awhere)){
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            $where = $awhere.$where;
            // 校验每页数据显示量
            if (!empty($_POST['limit'])) {
                $limit = intval($_POST['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_POST['page'])) {
                $page = intval($_POST['page']);
            } else {
                $page = 1;
            }
            $sql = "SELECT count(distinct xau.user_id) from xf_assignee_user xau  
LEFT JOIN xf_purchase_assignee xpa on xpa.user_id=xau.assignee_user_id 
LEFT JOIN firstp2p_user fu on xau.user_id=fu.id 
LEFT JOIN xf_exclusive_purchase ep on xau.user_id=ep.user_id 
where xau.status=1 and xpa.status=2  {$where}  ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT ep.recharge_withdrawal_difference as ph_increase_reduce , ep.wait_capital as ep_wait_capital,ep.purchase_amount as ep_purchase_amount,xau.user_id,fu.real_name,fu.mobile,min(ep.status) as status,fu2.real_name as assignee_name from xf_assignee_user xau  
LEFT JOIN xf_purchase_assignee xpa on xpa.user_id=xau.assignee_user_id 
LEFT JOIN firstp2p_user fu on xau.user_id=fu.id 
    LEFT JOIN firstp2p_user fu2 on xpa.user_id=fu2.id 
LEFT JOIN xf_exclusive_purchase ep on xau.user_id=ep.user_id 
where xau.status=1 and xpa.status=2  {$where} 
GROUP BY xau.user_id ";
            //left join firstp2p_deal_load dl on dl.user_id=xau.user_id and dl.status=1 and dl.xf_status=0 and dl.black_status=1 and dl.wait_capital>0
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            $status[1] = '待付款';
            $status[2] = '已付款待债转';
            $status[3] = '已债转待生成合同';
            $status[4] = '交易完成';
            $status[5] = '已失效待收购';
            $status[0] = '待签约';

            //获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');
            $audit_status = 0;
            if (!empty($authList) && strstr($authList, '/debtMarket/ExclusivePurchase/sendSms') || empty($authList)) {
                $audit_status = 1;
            }

            foreach ($list as $key => $value) {
                $value['mobile'] = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                $value['status_name'] = isset($status[$value['status']]) ? $status[$value['status']] : "待收购";

                if(!empty($value['ep_purchase_amount'])){
                    $value['purchase_amount'] = $value['ep_purchase_amount'];
                    $value['wait_capital'] = $value['ep_wait_capital'];
                }else{
                    //普惠本金
                    $d_sql = "SELECT  sum(wait_capital)   from firstp2p_deal_load  where user_id={$value['user_id']} and status=1 and xf_status=0 and black_status=1 and wait_capital>0";
                    $phwait_capital = Yii::app()->phdb->createCommand($d_sql)->queryScalar() ?: 0;

                    //智多新本金
                    $zdx_sql = "SELECT  sum(wait_capital)   from offline_deal_load  where user_id={$value['user_id']} and status=1 and xf_status=0 and black_status=1 and wait_capital>0 and platform_id=4";
                    $zdxwait_capital = Yii::app()->offlinedb->createCommand($zdx_sql)->queryScalar() ?: 0;

                    $value['wait_capital'] = bcadd($phwait_capital, $zdxwait_capital, 2);
                    //重提差
                    $u_sql = "SELECT  ph_increase_reduce  from xf_user_recharge_withdraw  where user_id={$value['user_id']}";
                    $r_user = Yii::app()->fdb->createCommand($u_sql)->queryRow();
                    $value['ph_increase_reduce'] = $r_user['ph_increase_reduce'];
                    $value['purchase_amount'] = round($r_user['ph_increase_reduce']*0.2, 2);
                }

                $value['wait_capital'] = $value['wait_capital'] ?: 0;
                $value['purchase_amount'] = $value['purchase_amount'] > 0 ? $value['purchase_amount'] : 0;
                $value['audit_status'] = $audit_status;
//var_dump($value);
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }


        return $this->renderPartial('CompanyUser' );
    }

    /**
     * 求购下发短信
     */
    public function actionSendSms()
    {
        $return_data = ['code'=>0, 'info'=>''];
        try {
            $id = $_POST['id'];
            if(!is_numeric($id) || empty($id)){
                $return_data['code'] = 100;
                $return_data['info'] = '参数错误';
                exit(json_encode($return_data));
            }
            $sql = "SELECT * from  xf_exclusive_purchase where user_id={$id}";
            $res = Yii::app()->phdb->createCommand($sql)->queryRow();
            if(!$res){
                $return_data['code'] = 100;
                $return_data['info'] = '此用户尚未创建专属收购';
                exit(json_encode($return_data));
            }
            if($res['status'] != 0){
                $return_data['code'] = 100;
                $return_data['info'] = '非待签约状态不可下发短信';
                exit(json_encode($return_data));
            }
            $assignee_tel = Yii::app()->c->xf_config['assignee_tel'];
            if(empty($assignee_tel[$res['purchase_user_id']])){
                $return_data['code'] = 100;
                $return_data['info'] = '收购人联系方式为空';
                exit(json_encode($return_data));
            }

            //短信下发
            $smaClass = new XfSmsClass();
            $remind = array();
            $remind['sms_code'] = "reissue_create_exclusive_purchase_end";
            $remind['mobile'] = $res['mobile_phone'];
            $remind['data']['assignee_tel'] = $assignee_tel[$res['purchase_user_id']];
            $send_ret_a = $smaClass->sendToUserByPhone($remind);
            if ($send_ret_a['code'] != 0) {
                Yii::log("SendSMS user_id:{$res['user_id']}; error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                $return_data['code'] = 100;
                $return_data['info'] = '短信发送失败';
                exit(json_encode($return_data));
            }

            $return_data['code'] = 0;
            $return_data['info'] = '短信下发成功';
            exit(json_encode($return_data));
        } catch (Exception $e) {
            $return_data['code'] = 100;
            $return_data['info'] = '网络异常请稍后再试';
            exit(json_encode($return_data));
        }
    }
}
