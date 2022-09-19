<?php
/**
 */
use iauth\models\User;

class BorrowerService extends ItzInstanceService
{
    public static $deal_src_cn = [
        0=>"L库原始数据",1=>"L库掌众",
    ];
    public static $repay_status_cn = [
        0=>'待还款',1=>'还款中',2=>'还款成功',3=>'还款失败',
    ];
    public static $auth_status_cn = [
        0=>'待确认',1=>'待审核',2=>'审核通过',3=>'已拒绝',
    ];
  
    public static $organization_type = [
      
        1=>'北京掌众金融信息服务有限公司',
        2=>'悠融资产管理（上海）有限公司',
        3=>'杭州大树网络技术有限公司（功夫贷）',
      
    ];
    public function __construct()
    {
        parent::__construct();
    }

    
    /**
        * 获取原始借款明细
        *
        * @param [type] $params
        * @return void
        */
    public function getDealOrderList($params)
    {
        $where = '';
        if (false && $params['type'] != 'all') {
            if ($params['type'] == 1) {
                $condition = ['d.repay_auth_flag in (0,1,3)'];
            } else {
                $condition = ['d.repay_auth_flag  = 2 '];
            }
        }

    
        //审核状态
        if (!empty($params['auth_status'])) {
            if ($params['auth_status'] == 1) {
                $condition[] = " d.repay_auth_flag = 0";
            } elseif ($params['auth_status'] == 2) {
                $condition[] = " d.repay_auth_flag  = 1";
            } elseif ($params['auth_status'] == 3) {
                $condition[] = " d.repay_auth_flag  = 3";
            }
        }
        //项目名称
        if (!empty($params['number'])) {
            $condition[] = " o.number = '".trim($params['number'])."'";
        }

        if (!empty($params['user_id'])) {
            $condition[] = " d.user_id = ".trim($params['user_id']);
        }
       
        

        if (!empty($params['deal_id'])) {
            $condition[] = " d.id = ".trim($params['deal_id']);
        }
        if (!empty($params['deal_name'])) {
            $condition[] = " d.name = '".trim($params['deal_name'])."'";
        }

        $user_query = [];
        if (!empty($params['phone'])) {
            $user_query[] = " s_mobile = '".trim($params['phone'])."'";
        }
        if (!empty($params['customer_name'])) {
            $user_query[] = " real_name = '".trim($params['customer_name'])."'";
        }
        if (!empty($params['id_number'])) {
            $user_query[] = " s_idno = '".trim($params['id_number'])."'";
        }
        if ($user_query) {
            $sql = "select  user_id  from xf_borrower_bind_card_info_online where  ".implode(',', $user_query);
            $user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
            if ($user_info) {
                $condition[] = " d.user_id in ('".implode("','", ArrayUntil::array_column($user_info, 'user_id'))."')";
            } else {
                return ['countNum' => 0, 'list' => []];
            }
        }


        //借款时间
        // if (!empty($params['loan_start'])) {
        //     $condition[] = " o.create_time >= '".$params['loan_start']."'";
        // }
        // if (!empty($params['loan_end'])) {
        //     $condition[] = " o.create_time <= '".date('Y-m-d', (strtotime($params['loan_end'])+86400))."'";
        // }

        if (!empty($params['organization_name'])) {
            $condition[] = " o.organization_name  like '%".$params['organization_name']."%'";
        }
        if (!empty($params['organization_type'])) {
            $condition[] = " o.organization_name  like '%".self::$organization_type[$params['organization_type']]."%'";
        }



        //末次还款时间
        if (!empty($params['last_repay_start'])) {
            $condition[] = " d.xf_last_repay_time >= '".strtotime($params['last_repay_start'])."'";
        }
        if (!empty($params['last_repay_end'])) {
            $condition[] = " d.xf_last_repay_time < '".(strtotime($params['last_repay_end'])+86400)."'";
        }

        //借款金额
        if (!empty($params['loan_amount_min'])) {
            $condition[] = " o.loan_amount >= '".$params['loan_amount_min']."'";
        }
        if (!empty($params['loan_amount_max'])) {
            $condition[] = " o.loan_amount <= '".$params['loan_amount_max']."'";
        }

        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }
        
        $result = [];
        $count_sql = "select count(1) from firstp2p_deal as d  join order_info as o on d.approve_number = o.number {$where} ";
        
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select d.user_id,d.xf_last_repay_time, d.repay_auth_flag, d.id ,d.deal_src, d.name as deal_name, d.repay_time as repay_type, d.loantype, d.rate, d.start_time, d.create_time as d_create_time, o.loan_amount, o.organization_name, o.number, o.product_name, o.transaction_number, o.create_time as o_create_time from firstp2p_deal as d  join order_info as o on d.approve_number = o.number   {$where} order by FIELD(d.repay_auth_flag,1,3,0),d.id desc LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
            
            $deal_id_array  = ArrayUntil::array_column($result, 'id');
            if ($deal_id_array) {
                $deal_ids = implode(',', $deal_id_array);
                $_repay_info = Yii::app()->cmsdb->createCommand("select count(1) as un_puy_num, deal_id , sum(principal) as principal ,sum(interest) as interest, sum(new_principal) as new_principal ,sum(new_interest) as new_interest from firstp2p_deal_repay where deal_id in ($deal_ids) and true_repay_time = 0 group by deal_id")->queryAll();
                if ($_repay_info) {
                    foreach ($_repay_info as  $item) {
                        $repay_info[$item['deal_id']] = $item;
                    }
                }
            }

            //c.name as customer_name, c.phone,c.id_number
            $user_ids =   ArrayUntil::array_column($result, 'user_id');
            if($user_ids){
                $user_ids = implode(',',$user_ids);
                $sql = "select  idno as id_number, mobile as phone,user_id,real_name as customer_name from xf_borrower_bind_card_info_online where user_id in ({$user_ids}) ";
                $_user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($_user_info) {
                    foreach ($_user_info as  $item) {
                        $data['customer_name'] = $item['customer_name'];
                        $data['id_number'] = GibberishAESUtil::dec($item['id_number'], Yii::app()->c->idno_key);
                        $data['phone'] = GibberishAESUtil::dec($item['phone'], Yii::app()->c->idno_key);
                        $user_info[$item['user_id']] = $data;
                        unset($data);
                    }
                }
            }


            foreach ($result as &$value) {
                $value+= $user_info[$value['user_id']];
                $value+=$repay_info[$value['id']];
                $value['un_puy_num'] = $value['un_puy_num'].'期';
                $value['deal_loantype'] = Yii::app()->c->xf_config['loantype'][$value['loantype']];//还款方式
                $value['deal_src_cn'] = self::$deal_src_cn[$value['deal_src']];
                $value['repay_type'] = $value['repay_type'].'个月';
                $value['rate'] = floatval($value['rate']);
                $value['loan_amount'] = number_format($value['loan_amount'], 2);
                $value['xf_last_repay_time'] = $value['xf_last_repay_time']>0? date('Y-m-d H:i:s', $value['xf_last_repay_time']):'--';
                $value['auth_status_cn'] = self::$auth_status_cn[$value['repay_auth_flag']];
            }
        }
        return ['countNum' => $total_num, 'list' => $result];
    }
    
    /**
     * 还款计划相关信息
     *
     * @param [type] $deal_id
     * @return void
     */
    public function getAboutDealRepayPlanInfo($deal_id)
    {
        if (empty($deal_id)) {
            return false;
        }
        $sql = "select d.repay_auth_flag, d.id ,d.deal_src, d.name as deal_name, d.repay_time as repay_type, d.start_time, d.create_time as d_create_time, o.loan_amount, o.organization_name, o.product_name, o.transaction_number, o.create_time as o_create_time,o.customer_number,o.number from firstp2p_deal as d  join order_info as o on d.approve_number = o.number  where d.id = {$deal_id}";
        $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        $dealInfo['loan_amount'] = floatval($dealInfo['loan_amount']);

        $sql = "select b.card_number,b.bank_name, c.name as customer_name, c.phone,c.id_number  from customer_bank_info as b   join  customer_info as c  on b.customer_number = c.customer_number where c.order_number = '{$dealInfo['number']}'";
      
        $userInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
       
        $sql = "select id,principal,interest,new_principal,new_interest,true_repay_time,repay_time,consult_fee from firstp2p_deal_repay where deal_id = {$deal_id} order by repay_time desc";
        $repayPlan = Yii::app()->cmsdb->createCommand($sql)->queryAll()?:[];
        if ($repayPlan) {
            $total = count($repayPlan);
            $dealInfo['principal'] = 0;
            $dealInfo['interest'] = 0;
            $dealInfo['new_principal'] = 0;
            $dealInfo['new_interest'] = 0;
            $dealInfo['consult_fee'] = 0;
            foreach ($repayPlan as $key=>  &$value) {
                if ($value['true_repay_time'] == 0) {
                    $dealInfo['principal'] += $value['principal'];
                    $dealInfo['interest'] += $value['interest'];
                    $dealInfo['new_principal'] += $value['new_principal'];
                    $dealInfo['new_interest'] += $value['new_interest'];
                    $dealInfo['consult_fee'] += $value['consult_fee'];
                }
               
                
                $value['repay_num'] = ($total-$key).'/'.$total;
                $_repayPlanNum[$value['id']] = '第'.($total-$key).'期';
                $value['is_repay'] = $value['true_repay_time']>0?'已还':'未还';
                $value['true_repay_time'] = $value['true_repay_time']>0 ? date('Y-m-d H:i:s', $value['true_repay_time']):'--';
                $value['repay_time'] = date('Y-m-d H:i:s', $value['repay_time']);
            }
        }
       
        $sql = "select * from firstp2p_deal_repay_modify_log where deal_id = {$deal_id} order by id desc";
        $modifyLog = Yii::app()->cmsdb->createCommand($sql)->queryAll()?:[];
        if ($modifyLog) {
            foreach ($modifyLog as  &$item) {
                $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
                $item['repay_num'] = $_repayPlanNum[$item['deal_repay_id']];
            }
        }

        return ['userInfo'=>$userInfo,'dealInfo'=> $dealInfo,'repayPlan'=>$repayPlan,'modifyLog'=> $modifyLog];
    }
    /**
     * 获取单条还款计划信息
     *
     * @param [type] $id
     * @return void
     */
    public function getRepayPlanInfo($id)
    {
        $sql = "select id,principal,interest,new_principal,new_interest,true_repay_time,repay_time from firstp2p_deal_repay where id = {$id}";
        $repayPlan = Yii::app()->cmsdb->createCommand($sql)->queryRow();
        if (!$repayPlan) {
            return false;
        }
        $repayPlan['repay_time'] = date('Y-m-d H:i:s', $repayPlan['repay_time']);
        return $repayPlan;
    }

    /**
     * 更新还款计划
     *
     * @param [type] $params
     * @return void
     */
    public function updateRepayPlan($params)
    {
        try {
            Yii::app()->cmsdb->beginTransaction();
            if (empty($params['id'])) {
                throw new Exception('还款计划id不能为空');
            }
            if ($params['new_principal'] < 0) {
                throw new Exception('修改后待还本金不得小于0');
            }
            if ($params['new_interest'] < 0) {
                throw new Exception('修改后待还利息不得小于0');
            }
           
            $sql = "select * from firstp2p_deal_repay where id = {$params['id']} for update";
            $repayInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (empty($repayInfo)) {
                throw new Exception('还款计划不存在');
            }

            $sql = "select * from firstp2p_deal where id = {$repayInfo['deal_id']}";
            $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (!$dealInfo) {
                throw new Exception('借款记录不存在');
            }

            if ($dealInfo['repay_auth_flag']==1) {
                throw new Exception('借款记录已经审核，不能继续修改还款计划');
            }

            if ($params['new_principal'] > $repayInfo['principal']) {
                throw new Exception('修改后待还本机不得大于原待还本金');
            }
            if ($params['new_interest'] > $repayInfo['interest']) {
                throw new Exception('修改后待还利息不得大于原待还利息');
            }
            $now = time();
            $repay_time = $params['repay_flag'] == 1?$now:0;
            $sql = "update firstp2p_deal_repay set true_repay_time = {$repay_time}, new_principal = {$params['new_principal']} ,new_interest = {$params['new_interest']} where id = {$params['id']}";
            $updateRes = Yii::app()->cmsdb->createCommand($sql)->execute();
            if ($updateRes===false) {
                throw new Exception('数据更新失败，请重试');
            }
            $add_id = Yii::app()->request->userHostAddress;
            $add_user_id = \Yii::app()->user->id;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $remark = $params['repay_flag'] ==1? '修改状态为【已还】;' :'';
            if ($params['new_principal'] != $repayInfo['new_principal']) {
                $remark .="【原应还本金】由".$repayInfo['new_principal']."修改为".$params['new_principal'].";";
            }
            if ($params['new_interest'] != $repayInfo['new_interest']) {
                $remark .="【原应还利息】由".$repayInfo['new_interest']."修改为".$params['new_interest'].";";
            }
            $sql = "insert into firstp2p_deal_repay_modify_log (`deal_id`,`deal_repay_id`,`new_principal`,`new_interest`,`add_user_id`,`add_user_name`,`add_ip`,`add_time`,`remark`) value ({$repayInfo['deal_id']},{$repayInfo['id']},{$params['new_principal']},{$params['new_interest']},$add_user_id,'$username','$add_id',$now,'$remark')";
            $insertRes = Yii::app()->cmsdb->createCommand($sql)->execute();
            if ($insertRes === false) {
                throw new Exception('写入更新日志失败，请重试');
            }
            Yii::app()->cmsdb->commit();
            return true;
        } catch (Exception  $e) {
            Yii::app()->cmsdb->rollback();
            throw $e;
        }
    }

    public function authDeal($params)
    {
        try {
            Yii::app()->cmsdb->beginTransaction();
            if (!in_array($params['type'], [1,2,3]) || empty($params['deal_id'])) {
                throw new Exception('参数错误');
            }
            $sql = "select repay_auth_flag from firstp2p_deal where id  = {$params['deal_id']} for update ";
            $dealInfo = Yii::app()->cmsdb->createCommand($sql)->queryRow();
            if (in_array($params['type'], [1,3]) && $dealInfo['repay_auth_flag'] == 2) {
                throw new Exception('该笔借款已经审核通过！');
            }
            if (in_array($params['type'], [2,3]) && $dealInfo['repay_auth_flag'] == 3) {
                throw new Exception('该笔借款已被拒绝通过！');
            }

            $sql = "update firstp2p_deal set repay_auth_flag = {$params['type']} where id = {$params['deal_id']}";
            $updateRes = Yii::app()->cmsdb->createCommand($sql)->execute();
            if ($updateRes===false) {
                throw new Exception('数据更新失败，请重试');
            }

            $add_id = Yii::app()->request->userHostAddress;
            $add_user_id = \Yii::app()->user->id;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $now = time();
            $sql = "insert into firstp2p_deal_auth_log (`deal_id`,`auth_type`,`add_user_id`,`add_user_name`,`add_ip`,`add_time`) value ({$params['deal_id']},{$params['type']},$add_user_id,'$username','$add_id',$now)";
            $insertRes = Yii::app()->cmsdb->createCommand($sql)->execute();
            if ($insertRes === false) {
                throw new Exception('写入更新日志失败，请重试');
            }

            Yii::app()->cmsdb->commit();
            return true;
        } catch (Exception  $e) {
            Yii::app()->cmsdb->rollback();
            throw $e;
        }
    }

    /**
      * 获取还款成功记录
      *
      * @param [type] $params
      * @return void
      */
    public function getRepaySuccessList($params)
    {
        
        $where = '';
        
        $condition = ['r.last_yop_repay_status = 2 '];//'d.xf_last_repay_time > 0','d.repay_auth_flag  = 2 ',

        //订单编号
        if (!empty($params['number'])) {
            $condition[] = " d.approve_number = '".trim($params['number'])."'";
        }
    
        if (!empty($params['deal_id'])) {
            $condition[] = " d.id = ".trim($params['deal_id']);
        }
        if (!empty($params['deal_name'])) {
            $condition[] = " d.name = '".trim($params['deal_name'])."'";
        }

        $user_query = [];
        if (!empty($params['phone'])) {
            $user_query[] = " s_mobile = '".trim($params['phone'])."'";
        }
        if (!empty($params['customer_name'])) {
            $user_query[] = " real_name = '".trim($params['customer_name'])."'";
        }
        if (!empty($params['id_number'])) {
            $user_query[] = " s_idno = '".trim($params['id_number'])."'";
        }
        if ($user_query) {
            $sql = "select  user_id  from xf_borrower_bind_card_info_online where  ".implode(',', $user_query);
            $user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
            if ($user_info) {
                $condition[] = " d.user_id in ('".implode("','", ArrayUntil::array_column($user_info, 'user_id'))."')";
            } else {
                return ['countNum' => 0, 'list' => []];
            }
        }
     
        //还款时间
        if (!empty($params['repay_start'])) {
            $condition[] = " ( r.paid_principal_time >= '".strtotime($params['repay_start'])."' or r.paid_interest_time >= '".strtotime($params['repay_start'])."' )";
        }
        if (!empty($params['repay_end'])) {
            $condition[] = " ( r.paid_principal_time < '".(strtotime($params['repay_end'])+86400)."' or r.paid_interest_time < '".(strtotime($params['repay_end'])+86400)."' )";
        }
    
      
        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }
            
        $result = [];
        $count_sql = "select count(1) from firstp2p_deal_repay as r  join  firstp2p_deal as d on d.id = r.deal_id {$where} ";
        // echo $count_sql;
        // die;
        $total_num = Yii::app()->cmsdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select d.xf_last_repay_time,d.repay_status, d.approve_number as number, d.id as deal_id ,d.deal_src, d.name as deal_name, r.paid_principal,r.paid_interest,r.paid_principal_time,r.paid_interest_time,r.true_repay_time,r.id ,r.user_id,r.principal,r.interest from firstp2p_deal_repay as r  join firstp2p_deal as d on r.deal_id = d.id {$where} order by r.true_repay_time desc LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->cmsdb->createCommand($sql)->queryAll();
                
            $approve_number_array  = ArrayUntil::array_column($result, 'number');
            if ($approve_number_array) {
                $approve_numbers = "'".implode("','", $approve_number_array)."'";
                
                $_organization_name = Yii::app()->cmsdb->createCommand("select organization_name,product_name,number from  order_info  where  number in ({$approve_numbers}) ")->queryAll();
                if ($_organization_name) {
                    foreach ($_organization_name as  $item) {
                        $organization_name[$item['number']] = $item;
                    }
                }
            }
            $user_ids =   ArrayUntil::array_column($result, 'user_id');
            if($user_ids){
                $user_ids = implode(',',$user_ids);
                $sql = "select  idno as id_number, mobile as phone,user_id,real_name as customer_name from xf_borrower_bind_card_info_online where user_id in ({$user_ids}) ";
                $_user_info = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($_user_info) {
                    foreach ($_user_info as  $item) {
                        $data['customer_name'] = $item['customer_name'];
                        $data['id_number'] = GibberishAESUtil::dec($item['id_number'], Yii::app()->c->idno_key);
                        $data['phone'] = GibberishAESUtil::dec($item['phone'], Yii::app()->c->idno_key);
                        $user_info[$item['user_id']] = $data;
                        unset($data);
                    }
                }
            }
            foreach ($result as &$value) {
                $value+=$user_info[$value['user_id']];
                $value+=$organization_name[$value['number']];
              
                $value['paid_interest_time'] = $value['paid_interest_time']>0? date('Y-m-d H:i:s', $value['paid_interest_time']):'--';
                $value['paid_principal_time'] = $value['paid_principal_time']>0? date('Y-m-d H:i:s', $value['paid_principal_time']):'--';
                $value['true_repay_time'] = $value['true_repay_time']>0? date('Y-m-d H:i:s', $value['true_repay_time']):'--';
                $value['repay_status_cn'] = self::$repay_status_cn[$value['repay_status']];
            }
        }
        return ['countNum' => $total_num, 'list' => $result];
    }

    public function getBorrowerList($params)
    {
        $where = '';
        
        if (!empty($params['phone'])) {
            $phone = GibberishAESUtil::enc(trim($params['phone']), Yii::app()->c->idno_key);
            $condition[] = " mobile = '".$phone."'";
        }
        if (!empty($params['user_name'])) {
            $condition[] = " real_name = '".trim($params['user_name'])."'";
        }
        if (!empty($params['id_number'])) {
            $id_number = GibberishAESUtil::enc(trim($params['id_number']), Yii::app()->c->idno_key);
            $condition[] = " idno = '".$id_number."'";
        }
        if ($params['borrower_src'] == 1) {
            $condition[] = " src_zz = 1";
        }
        if ($params['borrower_src'] == 2) {
            $condition[] = " src_ds = 1";
        }
        if ($params['borrower_src'] == 3) {
            $condition[] = " src_other = 1";
        }

        if (!empty($params['id_type'])) {
            $condition[] = " id_type = '".trim($params['id_type'])."'";
        }
        if (!empty($params['status'])) {
            $condition[] =  $params['status'] == 1 ?"status = 1":"status in (6,9)";
        }
        
        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition) ;
        }
        
        $result = [];
        $count_sql = "select count(1) from xf_borrower_bind_card_info_online  {$where} ";
       
        $total_num = Yii::app()->phdb->createCommand($count_sql)->queryScalar();
        if ($total_num > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select  * from xf_borrower_bind_card_info_online  {$where} order by id asc LIMIT {$offset} , {$pageSize} ";
            $result = Yii::app()->phdb->createCommand($sql)->queryAll();
            
            
            foreach ($result as &$value) {
                $value['idno'] =  GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                $value['mobile'] =  GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                $value['bankcard'] =  GibberishAESUtil::dec($value['bankcard'], Yii::app()->c->idno_key);
                $value['id_type'] = $value['id_type'] == 1?'身份证':'企业三证合一';//还款方式
                $value['status'] = self::$bind_status[$value['status']];
                $value['is_set_retail'] = $value['is_set_retail'] == 1?'是':'否';
                $value['src_other'] = $value['src_other'] == 1?'是':'否';
                $value['src_zz'] = $value['src_zz'] == 1?'是':'否';
                $value['src_ds'] = $value['src_ds'] == 1?'是':'否';
                $value['bind_type'] = self::$bind_type[$value['bind_type']];
            }
        }
        return ['countNum' => $total_num, 'list' => $result];
    }
    

    public static $bind_status=[
        '1'=>'成功',
        '0'=>'未处理',
        '9'=>'失败',
        '6'=>'失败',
    ];
    public static $bind_type=[
        '1'=>'协议扣款',
        '2'=>'代扣款',
        '0'=>'',
    ];
}
