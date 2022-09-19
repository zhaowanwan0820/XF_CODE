<?php
/**
 */
use iauth\models\User;

class ExclusivePurchaseService extends ItzInstanceService
{
    //0-待签约，1-待付款，2-已付款待债转，3-已债转待生成合同，4-交易完成 ，5-已失效
    public static $status_cn = [
        0=>"待签约",1=>"待付款",2=>"已付款待债转",3=>"已债转待生成合同",4=>'交易完成',5=>'已失效'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getPhDisableDealId()
    {
        $sql = "select deal_id from ag_wx_debt_black_list where `type` = 2 AND status = 1 ";
        $blackBorrow = Yii::app()->fdb->createCommand($sql)->queryAll() ?: [];
        if (!$blackBorrow) {
            return $blackBorrow;
        }
        $blackBorrow = ArrayUtil::array_column($blackBorrow, 'deal_id');
        return $blackBorrow;
    }

    public function getUserWaitingOrdersTendersPH($user_id)
    {
        $tenders = [];
        $sql = 'select tender_id from firstp2p_debt_exchange_log where user_id=:user_id AND status = 1 ';
        $waitingOrders = Yii::app()->phdb->createCommand($sql)->bindValues([':user_id' => $user_id])->queryAll();
        if (!$waitingOrders) {
            return $tenders;
        }
        $tenders = ArrayUtil::array_column($waitingOrders, 'tender_id');

        return $tenders;
    }

    public function getUserWaitingOrdersTendersZdx($user_id)
    {
        $tenders = [];
        $sql = " SELECT distinct tender_id from offline_debt_exchange_log where user_id=:user_id AND status = 1 ";
        $waitingOrders = Yii::app()->offlinedb->createCommand($sql)->bindValues([':user_id' => $user_id])->queryAll();
        if (!$waitingOrders) {
            return $tenders;
        }
        $tenders = ArrayUtil::array_column($waitingOrders, 'tender_id');

        return $tenders;
    }

    /**
     * 获取智多新部分还款中的债权记录.
     * @return array
     */
    public function getUserPartialRepayTenderZdx($user_id)
    {
        $sql = ' SELECT distinct deal_loan_id FROM offline_partial_repay_detail WHERE user_id = :user_id and status = 1 and repay_status = 0 and platform_id=4 ';
        $partialRepayTender = Yii::app()->offlinedb->createCommand($sql)->bindValues([':user_id' => $user_id])->queryAll() ?: [];
        if (!$partialRepayTender) {
            return $partialRepayTender;
        }
        $partialRepayTender = ArrayUtil::array_column($partialRepayTender, 'deal_loan_id');

        return $partialRepayTender;
    }

    /**
     * 获取用户代还本金
     *
     * @param [type] $user_id
     * @return void
     */
    public function getUserPhWaitCapital($user_id)
    {
        $disable_condition = $advisory_condition = $ph_condition = $zdx_condition = '';

        //禁止信息[黑名单]
        $disableBorrow  = $this->getPhDisableDealId();
        if (!empty($disableBorrow)) {
            $disable_condition = " AND b.id  not in (" . implode(',', $disableBorrow) . ") ";
        }

        //剔除禁止兑换的债权[还款中，兑换处理中]
        if ($partialRepayTender = $this->getUserWaitingOrdersTendersPH($user_id)) {
            $ph_condition .= " AND t.id  not in (" . implode(',', $partialRepayTender) . ") ";
        }

        //剔除智多鑫禁止兑换的债权[兑换处理中]
        if ($exchangeTender = $this->getUserWaitingOrdersTendersZdx($user_id)) {
            $zdx_condition .= " AND t.id  not in (" . implode(',', $exchangeTender) . ") ";
        }
        //剔除智多鑫禁止兑换的债权[还款中]
        if ($partialRepayTender = $this->getUserPartialRepayTenderZdx($user_id)) {
            $zdx_condition .= " AND t.id  not in (" . implode(',', $partialRepayTender) . ") ";
        }

        //sql拼接
        $amount_select = "SELECT sum(t.wait_capital) AS total_account";
        $public_sql = "  FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id 
             WHERE b.is_zdx=0 and t.user_id = $user_id AND t.wait_capital > 0 AND t.xf_status = 0  AND t.debt_status=0 and t.status=1  " . $disable_condition;
        $ph_sql = $public_sql . $ph_condition;
        $ph_amount = Yii::app()->phdb->createCommand($amount_select.$ph_sql)->queryScalar() ?: 0;
   
        //智多新总额
        $zdx_yr_sql = str_replace("firstp2p", "offline", $public_sql) . ' AND t.platform_id = 4 '. $zdx_condition;
        $zdx_yr_amount = Yii::app()->offlinedb->createCommand($amount_select.$zdx_yr_sql)->queryScalar() ?: 0;

        return $ph_amount + $zdx_yr_amount ;
    }

    /**
     * 获取用户出借记录
     *
     * @param [type] $user_id
     * @return void
     */
    public function getUserWaitDealLoad($user_id)
    {
        $disable_condition = $advisory_condition = $ph_condition = $zdx_condition = '';

        //禁止信息[黑名单]
        $disableBorrow  = $this->getPhDisableDealId();
        if (!empty($disableBorrow)) {
            $disable_condition = " AND b.id  not in (" . implode(',', $disableBorrow) . ") ";
        }

        //剔除禁止兑换的债权[还款中，兑换处理中]
        if ($partialRepayTender = $this->getUserWaitingOrdersTendersPH($user_id)) {
            $ph_condition .= " AND t.id  not in (" . implode(',', $partialRepayTender) . ") ";
        }

        //剔除智多鑫禁止兑换的债权[兑换处理中]
        if ($exchangeTender = $this->getUserWaitingOrdersTendersZdx($user_id)) {
            $zdx_condition .= " AND t.id  not in (" . implode(',', $exchangeTender) . ") ";
        }
        //剔除智多鑫禁止兑换的债权[还款中]
        if ($partialRepayTender = $this->getUserPartialRepayTenderZdx($user_id)) {
            $zdx_condition .= " AND t.id  not in (" . implode(',', $partialRepayTender) . ") ";
        }

        //sql拼接
        $query_select = "SELECT t.id";
        $public_sql = "  FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id 
             WHERE b.is_zdx=0 and t.user_id = $user_id AND t.wait_capital > 0 AND t.xf_status = 0  AND t.debt_status=0 and t.status=1  " . $disable_condition;
        $ph_sql = $public_sql . $ph_condition;
        $ph_deal_load_ids = Yii::app()->phdb->createCommand($query_select.$ph_sql)->queryAll() ?: [];
        if ($ph_deal_load_ids) {
            $ph_deal_load_ids = ArrayUntil::array_column($ph_deal_load_ids, 'id');
        }
        //智多新总额
        $zdx_yr_sql = str_replace("firstp2p", "offline", $public_sql) . ' AND t.platform_id = 4 '. $zdx_condition;
        $zdx_yr_deal_load_ids = Yii::app()->offlinedb->createCommand($query_select.$zdx_yr_sql)->queryAll() ?: [];
        if ($zdx_yr_deal_load_ids) {
            $zdx_yr_deal_load_ids = ArrayUntil::array_column($zdx_yr_deal_load_ids, 'id');
        }

        return ['ph_deal_load_ids'=>$ph_deal_load_ids,'zdx_deal_load_ids'=>$zdx_yr_deal_load_ids];
    }

    public function getUserInfoFromFirstP2p($user_id)
    {
        $sql = "SELECT user.id, user.user_name, user.real_name, user.mobile, user.idno, card.bankcard, card.bankzone
        FROM firstp2p_user as user
        LEFT JOIN firstp2p_user_bankcard as card
        ON user.id = card.user_id and card.verify_status=1
        WHERE user.id = {$user_id} order by card.id desc limit 1";
        return Yii::app()->fdb->createCommand($sql)->queryRow();
    }

    /**
     * 获取用户信息
     *
     * @param [type] $user_id
     * @return void
     */
    public function getUserInfo($user_id)
    {

        $info = $this->getUserInfoFromFirstP2p($user_id);
        if(!$info ){
            return false;
        }
        $result['real_name'] =  $info['real_name'];
        $result['bankname'] =  $info['bankname'];
        $result['bankcode'] =  $info['bankcode'];
        $result['idno'] =  GibberishAESUtil::dec($info['idno'], Yii::app()->c->idno_key);
        $result['mobile_phone'] =  GibberishAESUtil::dec($info['mobile'], Yii::app()->c->idno_key);
        $result['bank_card'] =  GibberishAESUtil::dec($info['bankcard'], Yii::app()->c->idno_key);
        $result['wait_capital'] = $this->getUserPhWaitCapital($user_id);
        $result['recharge_withdrawal_difference'] = $this->getUserRechargeWithdrawalDiff($user_id);//充值 提现  差值
        return $result;
    }
    
    
    //充值 提现  差值
    public function getUserRechargeWithdrawalDiff($user_id)
    {
        $sql = "SELECT ph_increase_reduce FROM xf_user_recharge_withdraw WHERE user_id = {$user_id}";
        $res = Yii::app()->fdb->createCommand($sql)->queryScalar() ?: 0;
        return round($res,2);
    }

    /**
     * 获取专属收购列表
     *
     * @param array $params
     * @return array
     */
    public function getList($params)
    {
        $M= "XfExclusivePurchase";
        $where = '';
        $condition = [];
      
        $buyer_user_id = ExclusivePurchaseService::getInstance()->getLoginUserAssigneeID();

        if ($buyer_user_id) {
            $condition[] = " a.purchase_user_id = ".  $buyer_user_id;
        }
       
        if (!empty($params['user_id'])) {
            $condition[] = " a.user_id = ".$params['user_id'];
        }
        if (!empty($params['real_name'])) {
            $condition[] = " a.real_name = '".$params['real_name']."'";
        }
        if (!empty($params['mobile_phone'])) {
            $condition[] = " a.mobile_phone = '".$params['mobile_phone']."'";
        }
        if (isset($params['status']) && $params['status'] >= 0) {
            $condition[] = " a.status = '".$params['status']."'";
        }
     

        if (!empty($condition)) {
            $where = ' where '. implode(' and ', $condition);
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $audit_status = 0;
        if (!empty($authList) && strstr($authList, '/debtMarket/ExclusivePurchase/audit') || empty($authList)) {
            $audit_status = 1;
        }
        
    
        $fileList = [];
        $countFile = $M::model()->countBySql('select count(1) from xf_exclusive_purchase as a  '.$where);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select a.*,b.real_name as assignee_name from xf_exclusive_purchase as a 
 left join firstp2p_user b on b.id=a.purchase_user_id 
 {$where} order by a.user_sign_time desc  LIMIT {$offset} , {$pageSize} ";
            $_file = $M::model()->findAllBySql($sql);
            $_file =  Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($_file as $item) {
               // $list['id']=$item->id;
               // $list['user_id']=$item->user_id;
               // $list['real_name']=$item->real_name;
               // $list['mobile_phone']=$item->mobile_phone;
                //$list['idno']=$item->idno;
              //  $list['bank_card']=$item->bank_card;
               // $list['wait_capital']=$item->wait_capital;
               // $list['purchase_amount']=$item->purchase_amount;
               // $list['discount']=$item->discount;//折扣
               // $list['add_user_name']=$item->add_user_name;//添加人
               // $list['status'] = $item->status;
                $item['status_cn']=self::$status_cn[$item['status']];
                $item['start_time']=date('Y/m/d H:i:s', $item['start_time']);
                $item['end_time']=date('Y/m/d H:i:s', $item['end_time']);
                $item['audit_status']=$audit_status;
               // $list['recharge_withdrawal_difference']=$item['recharge_withdrawal_difference'];
                $item['user_sign_time']= !empty($item['user_sign_time']) ? date('Y/m/d H:i:s', $item['user_sign_time']) : '-';
                $item['assignee_sign_time']=!empty($item['assignee_sign_time']) ? date('Y/m/d H:i:s', $item['assignee_sign_time']) : '-';
                $item['pay_time']= !empty($item['pay_time']) ? date('Y/m/d H:i:s', $item['pay_time']) : '-';
                //$list['assignee_name']=$item->assignee_name;

                $fileList[] = $item;
            }
        }
        return ['countNum' => $countFile, 'list' => $fileList];
    }

    /**
     * 获取求购对应的债权列表
     *
     * @param [type] $params
     * @return void
     */
    public function getUserExclusiveDebtList($params)
    {
        if (empty($params['id'])) {
            return [];
        }

        $countSql = "SELECT count(1) FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id where t.exclusive_purchase_id = {$params['id']} ";
       
        $countFile = Yii::app()->phdb->createCommand($countSql)->queryScalar();

        $page = $params['page'] ?: 1;
        $zdx = [];
        if ($page == 1) {
            $sql = "SELECT t.id,t.wait_capital AS account,b.name AS name ,t.deal_id AS borrow_id ,t.create_time  FROM offline_deal_load as t LEFT JOIN offline_deal as b ON t.deal_id = b.id  where t.exclusive_purchase_id = {$params['id']}  order by t.id desc ";
            $zdx = Yii::app()->offlinedb->createCommand($sql)->queryAll()?:[];
        }
            
        $pageSize = $params['pageSize'] ?: 10;
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT t.id,t.wait_capital AS account,b.name AS name ,t.deal_id AS borrow_id ,t.create_time  FROM firstp2p_deal_load as t LEFT JOIN firstp2p_deal as b ON t.deal_id = b.id  where t.exclusive_purchase_id = {$params['id']}  order by t.id desc LIMIT {$offset} , {$pageSize}";
        $_file = Yii::app()->phdb->createCommand($sql)->queryAll()?:[];
        
        $data = array_merge($zdx, $_file);

        foreach ($data as &$item) {
            $item['create_time']=date('Y-m-d H:i:s', $item['create_time']);
        }
        return ['countNum' => $countFile, 'list' => $data];
    }
   
    /**
     * 创建求购单
     *
     * @param [type] $area_id
     * @return void
     */
    public function createPurchase($params)
    {
        try {
            $user_id = $params['user_id'];
            if (empty($user_id)) {
                throw new Exception('出借人不能为空');
            }
            if (empty($params['buyer_user_id'])) {
                throw new Exception('受让人不能为空');
            }
            if (empty($params['purchase_amount'])) {
                throw new Exception('收购金额不能为空');
            }
            if (empty($params['purchase_type'])) {
                throw new Exception('收购类型不能为空');
            }
            if ($params['discount'] > 10 || $params['discount']< 0.01) {
                throw new Exception('折扣范围 0.01-10');
            }
            if (empty($params['user_chong_ti_cha'])) {
                throw new Exception('用户充提差不能为空');
            }

            //校验用户是否有债转中数据
            $check_ret = $this->checkUserDebt($user_id);
            if($check_ret == false){
                throw new Exception('请先联系用户取消转让中债权，再发起收购');
            }
            
            $deal_load_info = $this->getUserWaitDealLoad($user_id);
            //bug修复
            $deal_load_info['zdx_yr_deal_load_ids'] = $deal_load_info['zdx_deal_load_ids'];

            if ($deal_load_info['ph_deal_load_ids'] && $deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->phdb->beginTransaction();
                Yii::app()->offlinedb->beginTransaction();
            } elseif ($deal_load_info['ph_deal_load_ids']) {
                Yii::app()->phdb->beginTransaction();
            } elseif ($deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->offlinedb->beginTransaction();
            }
            
            
            $xf_assignee_user      = Yii::app()->phdb->createCommand("select * from xf_assignee_user where user_id  = {$params['user_id']} and assignee_user_id = {$params['buyer_user_id']} for update")->queryRow();
            if (!$xf_assignee_user) {
                throw new Exception('受让人与出借人未关联');
            }
           
            if ($xf_assignee_user['purchase_status'] == 1) {
                throw new Exception('出借人债权已被收购');
            }

            $user_info = $this->getUserInfo($user_id);
            if ($user_info['recharge_withdrawal_difference'] != $params['user_chong_ti_cha']) {
                throw new Exception('出借人债权总额发生变化，请重新查询');
            }
            //专属列表数据
            $xf_exclusive_purchase     = Yii::app()->phdb->createCommand("select * from xf_exclusive_purchase where user_id  = {$params['user_id']} and status <= 4 and purchase_user_id = {$params['buyer_user_id']}")->queryRow();
            if ($xf_exclusive_purchase) {
                throw new Exception('该出借人与受让人债转进行中');
            }

            $now = time();
            //受让人信息
            $xf_purchase_assignee     = Yii::app()->phdb->createCommand("select * from xf_purchase_assignee where user_id  = {$params['buyer_user_id']}")->queryRow();
            if (!$xf_purchase_assignee) {
                throw new Exception('该受让人不在范围内');
            }
           
            if ($xf_purchase_assignee ['status'] != 2) {
                throw new Exception('受让人状态不可用');
            }

            $transferability_limit =  $xf_purchase_assignee['transferability_limit'];//总受让额度
            $frozen_quota =  $xf_purchase_assignee['frozen_quota'];//冻结的
            $transferred_amount =  $xf_purchase_assignee['transferred_amount'];//已收购的
            //专属数据

            //受让人额度校验
            $check_amount = round($frozen_quota + $transferred_amount + $user_info['wait_capital'], 2);
            if ($check_amount > $transferability_limit) {
                throw new Exception('超过受让人受让额度');
            }
    
            $queryData = [
                'traceid'=>$params['user_id'],
                'cardno'=>$user_info['bank_card'],
            ];
            $query_res = $this->queryBankNameYop($queryData);
            ;
            if (!$query_res) {
                throw new Exception('请求易宝查询接口异常');
            }
            if ($query_res['isvalid'] == 'INVALID') {
                throw new Exception('该银行卡状态不可用');
            }
            
          

            $model = new XfExclusivePurchase();
            $model->discount = $params['discount'];
            $model->purchase_amount = $params['purchase_amount'];
            $model->user_id = $params['user_id'];
            $model->purchase_user_id = $params['buyer_user_id'];
            $model->wait_capital = $user_info['wait_capital'];
            $model->recharge_withdrawal_difference = $user_info['recharge_withdrawal_difference'];
            $model->real_name = $user_info['real_name'];
            $model->mobile_phone = $user_info['mobile_phone'];
            $model->idno = $user_info['idno'];
            $model->bank_name = $query_res['bankname'];//开户行
            $model->bank_card = $user_info['bank_card'];
            $model->bankcode = $query_res['bankcode'];//银行编码
            $model->end_time = $now + ($xf_purchase_assignee['purchase_time']*3600);
            $model->start_time = $now;
            $model->add_time = $now;
            $model->order_no = FunctionUtil::getRequestNo("PAY");
            $model->add_ip = Yii::app()->request->userHostAddress;
            $model->add_user_id = \Yii::app()->user->id;
            $model->status = 0;
            $userInfo = Yii::app()->user->getState("_user");
            $model->add_user_name = $userInfo['username'];
            
            

            if (!$model->save()) {
                throw new Exception('数据保存失败",请重试-1');
            }
            $id = $model->id;
            
            $sql = " UPDATE xf_purchase_assignee SET frozen_quota = frozen_quota + {$user_info['wait_capital']} WHERE user_id = {$params['buyer_user_id']}";
            $res = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res === false) {
                throw new Exception('数据保存失败",请重试-3');
            }
            if ($deal_load_info['ph_deal_load_ids']) {
                $sql = " UPDATE firstp2p_deal_load SET exclusive_purchase_id = $id WHERE user_id = {$user_id} and id in (".implode(',', $deal_load_info['ph_deal_load_ids']).")";
                $res = Yii::app()->phdb->createCommand($sql)->execute();
                if ($res === false) {
                    throw new Exception('数据保存失败",请重试-2');
                }
            }
            if ($deal_load_info['zdx_yr_deal_load_ids']) {
                $sql = " UPDATE offline_deal_load SET exclusive_purchase_id = $id WHERE platform_id=4 and user_id = {$user_id} and id in (".implode(',', $deal_load_info['zdx_yr_deal_load_ids']).")";
                $res = Yii::app()->offlinedb->createCommand($sql)->execute();
                if ($res === false) {
                    throw new Exception('数据保存失败",请重试-3');
                }
            }

            if ($deal_load_info['ph_deal_load_ids'] && $deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->phdb->commit();
                Yii::app()->offlinedb->commit();
            } elseif ($deal_load_info['ph_deal_load_ids']) {
                Yii::app()->phdb->commit();
            } elseif ($deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->offlinedb->commit();
            }

            //todo 临时不发
            // return true;
            //发短信
            $smaClass                   = new XfSmsClass();
            $remind                     = array();
            $remind['sms_code']         = "create_exclusive_purchase";
            $remind['mobile']           = ConfUtil::get('xf_fdd_app_id') == '001542'?$user_info['mobile_phone']:'13716970622';
            $remind['data']['hour']     = $xf_purchase_assignee['purchase_time'];

            $send_ret_a = $smaClass->sendToUserByPhone($remind);
        
            if ($send_ret_a['code'] != 0) {
                Yii::log("SendSMS user_id:{$user_info['id']}; error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
            }
            return true;
        } catch (Exception $e) {
            if ($deal_load_info['ph_deal_load_ids'] && $deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->phdb->rollback();
                Yii::app()->offlinedb->rollback();
            } elseif ($deal_load_info['ph_deal_load_ids']) {
                Yii::app()->phdb->rollback();
            } elseif ($deal_load_info['zdx_yr_deal_load_ids']) {
                Yii::app()->offlinedb->rollback();
            }
           

            throw $e;
        }
    }


    private function checkUserDebt($user_id){

        $d_sql = "SELECT  id   from firstp2p_deal_load  where user_id={$user_id} and status=1 and xf_status=0 and black_status=1 and wait_capital>0 and debt_status=1";
        $ph_load = Yii::app()->phdb->createCommand($d_sql)->queryRow() ;
        if($ph_load){
            return false;
        }

        $zdx_sql = "SELECT  id  from offline_deal_load  where user_id={$user_id} and status=1 and xf_status=0 and black_status=1 and wait_capital>0 and platform_id=4 and debt_status=1";
        $zdx_load = Yii::app()->offlinedb->createCommand($zdx_sql)->queryRow() ;
        if($zdx_load){
            return false;
        }

        return  true;
    }
    public static $payment_log_status = [
        0=>'未支付',
        1=>'支付处理中',
        2=>'支付成功',
        3=>'支付失败',
    ];

    /**
     * 专属求购详情
     *
     * @param [type] $id
     * @return void
     */
    public function purchaseDetail($id)
    {
        if (empty($id)) {
            return [];
        }
        $xf_exclusive_purchase     = Yii::app()->phdb->createCommand("select * from xf_exclusive_purchase where id  = {$id}")->queryRow();
        if (!$xf_exclusive_purchase) {
            return [];
        }
        $xf_exclusive_purchase['payment_log'] = [];
        $payment_log = Yii::app()->phdb->createCommand("select * from xf_exclusive_purchase_payment_log where exclusive_purchase_id  = {$id} order by id desc")->queryAll();
        if ($payment_log) {
            foreach ($payment_log as $key => &$value) {
                $value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
                $value['status_cn'] = self::$payment_log_status[$value['status']];
                if($value['status'] == 2){
                    $value['remark'] = '';
                }
            }
            $xf_exclusive_purchase['payment_log'] = $payment_log ;
        }
        

        return $xf_exclusive_purchase;
    }


    /**
     * 更换银行卡
     *
     * @param [type] $params
     * @return void
     */
    public function editUserBank($params)
    {
        try {
           
            //throw new Exception('测试环境功能暂时不可用');
            //$params['user_id'] = 12130848;
            //throw new Exception('功能暂时不可用');

            if (!FunctionUtil::getLuhn($params['bankcard'])) {
                throw new Exception('银行卡号格式错误');
            }

        
            $queryData = [
                'traceid'=>$params['user_id'],
                'cardno'=>$params['bankcard'],
            ];
            $query_res = $this->queryBankNameYop($queryData);
            
            if (!$query_res) {
                throw new Exception('请求易宝查询接口异常');
            }
            if ($query_res['isvalid'] == 'INVALID') {
                throw new Exception('该银行卡状态不可用');
            }
            
            $userInfo = $this->getUserInfoFromFirstP2p($params['user_id']);

            if (!$userInfo) {
                throw new Exception('出借人不存在');
            }

            $current_bankcard_mobile = GibberishAESUtil::dec($userInfo['mobile'], Yii::app()->c->idno_key);
            $current_bankcard  = GibberishAESUtil::dec($userInfo['bankcard'], Yii::app()->c->idno_key);

            if ($params['bankcard'] ==  $current_bankcard && $params['bank_mobile'] == $current_bankcard_mobile) {
                throw new Exception('银行卡信息相同 无需更换');
            }

            $bankInfo = new Firstp2pUserBankcard();
            $bankInfo->user_id = $params['user_id'];
            $bankInfo->card_name = $userInfo['real_name'];
            $bankInfo->bank_id = 0;
            $bankInfo->bankcard = GibberishAESUtil::enc($params['bankcard'], Yii::app()->c->idno_key);
            $bankInfo->bankzone = $params['bank_name'];
            $bankInfo->verify_status =  1; //有 【有效的银行卡】 就 新增一个无效的。
            $bankInfo->create_time = time();
            $bankInfo->region_lv1 = '0';
            $bankInfo->region_lv2 = '0';
            $bankInfo->region_lv3 = '0';
            $bankInfo->region_lv4 = '0';
            if (false === $bankInfo->save()) {
                throw new Exception('创建用户银行卡失败'.json_encode($bankInfo->getErrors(), JSON_UNESCAPED_UNICODE));        
            }
            return true;
        } catch (Exception  $e) {
            throw $e;
        }
    }


    // /**
    //  * 修改银行卡号
    //  *
    //  * @param [type] $params
    //  * @return void
    //  */
    // public function editUserBankStep1($params)
    // {
    //     try {
    //         Yii::app()->phdb->beginTransaction();
    //         Yii::app()->fdb->beginTransaction();
    //         //throw new Exception('测试环境功能暂时不可用');
    //         //$params['user_id'] = 12130848;
    //         //throw new Exception('功能暂时不可用');

    //         if (!FunctionUtil::getLuhn($params['bankcard'])) {
    //             throw new Exception('银行卡号格式错误');
    //         }

    //         if (!FunctionUtil::IsMobile($params['bank_mobile'])) {
    //             throw new Exception('手机号码格式错误');
    //         }
            
            
            
    //         // $sql = "select * from xf_borrower_bind_card_info_online where user_id = {$params['user_id']} for update ";
    //         // $userInfo = Yii::app()->phdb->createCommand($sql)->queryRow();

    //         $userInfo = $this->getUserInfoFromFirstP2p($params['user_id']);

    //         if (!$userInfo) {
    //             throw new Exception('出借人不存在');
    //         }

           
    //         $current_bankcard_mobile = GibberishAESUtil::dec($userInfo['mobile'], Yii::app()->c->idno_key);
    //         $current_bankcard  = GibberishAESUtil::dec($userInfo['bankcard'], Yii::app()->c->idno_key);

    //         if ($params['bankcard'] ==  $current_bankcard && $params['bank_mobile'] == $current_bankcard_mobile) {
    //             throw new Exception('银行卡信息相同 无需更换');
    //         }

         
    //         //请求易宝
    //         $borrower['request_no'] = $request_no = FunctionUtil::getRequestNo("EBBE");
    //         $borrower['idno']       = GibberishAESUtil::dec($userInfo['idno'], Yii::app()->c->idno_key);
    //         $borrower['bankcard']   = $params['bankcard'] ;
    //         $borrower['mobile']     = $params['bank_mobile'] ;
    //         $borrower['user_id']    = $params['user_id'];
    //         $borrower['real_name']  = $userInfo['real_name'];
    //         // todo 临时关闭
    //         $re = $this->bindBankCardYopStep1($borrower);
    //         Yii::log("borrowerService  ".__FUNCTION__." Yop return :".json_encode($re, JSON_UNESCAPED_UNICODE), 'info');

    //         //$re = ['status'=>'BIND_SUCCESS'];
    //         if (!$re) {
    //             throw new Exception('请求易宝接口异常');
    //         }
    //         $status     = self::$yibao_bind_status[$re['status']]?:9;

           
    //         $errormsg   = isset($re['errormsg'])?$re['errormsg']:'';
    //         $cardtop    = isset($re['cardtop'])?$re['cardtop']:'';
    //         $cardlast   = isset($re['cardlast'])?$re['cardlast']:'';
    //         $bankcode   = isset($re['bankcode'])?$re['bankcode']:'';
    //         $verifyStatus = isset($re['verifyStatus'])?$re['verifyStatus']:'';
    //         $yborderid  = isset($re['yborderid'])?$re['yborderid']:'';
    //         $remark = json_encode($re);
    //         $now = time();

    //         if (!in_array($status, [1,2])) {
    //             throw new Exception('易宝绑卡返回失败:'.$errormsg);
    //         }
    //         $current_admin_id = \Yii::app()->user->id;
            
    //         $add_ip      = Yii::app()->request->userHostAddress;
           
            
    //         $bankInfo = new Firstp2pUserBankcard();
    //         $bankInfo->user_id = $params['user_id'];
    //         $bankInfo->card_name = $userInfo['real_name'];
    //         $bankInfo->bank_id = 0;
    //         $bankInfo->bankcard = GibberishAESUtil::enc($params['bankcard'], Yii::app()->c->idno_key);
    //         $bankInfo->bankzone = $params['bank_name'];
    //         $bankInfo->verify_status =  0; //有 【有效的银行卡】 就 新增一个无效的。
    //         $bankInfo->create_time = time();
    //         $bankInfo->request_no = $request_no;
    //         $bankInfo->region_lv1 = '0';
    //         $bankInfo->region_lv2 = '0';
    //         $bankInfo->region_lv3 = '0';
    //         $bankInfo->region_lv4 = '0';
    //         if (false === $bankInfo->save()) {
    //             throw new Exception('创建用户银行卡失败'.json_encode($bankInfo->getErrors(), JSON_UNESCAPED_UNICODE));        
    //         }

    //         $admin_user = Yii::app()->user->getState("_user");
    //         $username = $admin_user['username'];


    //         $sql = "INSERT INTO xf_borrower_bank_info_modify_log (user_id,current_bank_mobile,current_bankcard,after_bank_mobile,after_bankcard,after_bank_name,request_no,status,errormsg,cardtop,cardlast,bankcode,remark,yborderid,verifyStatus,add_user_id,add_user_name,add_ip,add_time) VALUES ";
    //         $sql .= "({$params['user_id']},'{$current_bankcard_mobile}','{$current_bankcard}','{$params['bank_mobile']}','{$params['bankcard']}','{$params['bank_name']}','{$request_no}',{$status},'{$errormsg}','{$cardtop}','{$cardlast}','{$bankcode}','{$remark}','{$yborderid}','{$verifyStatus}','{$current_admin_id}','{$username}','{$add_ip}','{$now}')";
    //         $sql = rtrim($sql, ',');
    //         // echo $sql;
    //         // die;
    //         $res = Yii::app()->phdb->createCommand($sql)->execute();
    //         if ($res === false) {
    //             throw new Exception('写入换卡记录表失败');
    //         }

    //         Yii::app()->phdb->commit();
    //         Yii::app()->fdb->commit();
    //         return true;
    //     } catch (Exception  $e) {
    //         Yii::app()->phdb->rollback();
    //         Yii::app()->fdb->rollback();
    //         throw $e;
    //     }
    // }

    //BIND_SUCCESS ： 绑卡成功 TO_VALIDATE： 待短验 BIND_FAIL： 绑卡失败 BIND_ERROR： 绑卡异常(可重试) TIME_OUT： 超时失败 FAIL： 系统异常
    public static $yibao_bind_status = [
        'BIND_SUCCESS'=>1,
        'TO_VALIDATE'=>2,
        'BIND_FAIL'=>3,
        'BIND_ERROR'=>4,
        'TIME_OUT'=>5,
        'FAIL'=>6,
    ];

    // /**
    //  * todo 废弃
    //  * 修改银行卡号
    //  *
    //  * @param [type] $params
    //  * @return void
    //  */
    // public function editUserBankStep2($params)
    // {
    //     try {
    //         //throw new Exception('功能暂时不可用');

    //         Yii::app()->phdb->beginTransaction();
            
    //         $sql = "select * from xf_borrower_bank_info_modify_log where user_id = {$params['user_id']} order by id desc limit 1 for update ";
    //         $bankcardModifyLog = Yii::app()->phdb->createCommand($sql)->queryRow();

    //         if (!$bankcardModifyLog) {
    //             Yii::app()->phdb->rollback();
    //             throw new Exception('换卡申请记录不存在');
    //         }

    //         if ($bankcardModifyLog['status'] == 1) {
    //             Yii::app()->phdb->rollback();
    //             throw new Exception('换卡已成功');
    //         }

    //         $wait_modify_bank_mobile = $bankcardModifyLog['after_bank_mobile'];
    //         $wait_modify_bankcard  = $bankcardModifyLog['after_bankcard'];

    //         if ($params['bankcard'] !=  $wait_modify_bankcard || $params['bank_mobile'] != $wait_modify_bank_mobile) {
    //             Yii::app()->phdb->rollback();
    //             throw new Exception('银行卡信息不一致,请重启发起绑卡申请');
    //         }
    //         //请求易宝

    //         //请求易宝
    //         $borrower['request_no'] = $bankcardModifyLog['request_no'];
    //         $borrower['validatecode']   = $params['sms_code'] ;
    //         //todo 临时注释
    //         $re = $this->bindBankCardYopStep2($borrower);
    //         //$re = ['status'=>'BIND_SUCCESS'];
    //         Yii::log("borrowerService  ".__FUNCTION__." Yop return :".json_encode($re, JSON_UNESCAPED_UNICODE), 'info');

    //         if (!$re) {
    //             throw new Exception('请求易宝接口异常');
    //         }
 
    //         $status     = self::$yibao_bind_status[$re['status']]?:9;
    //         $errormsg   = isset($re['errormsg'])?$re['errormsg']:'success';
    //         $cardtop    = isset($re['cardtop'])?$re['cardtop']:'';
    //         $cardlast   = isset($re['cardlast'])?$re['cardlast']:'';
    //         $bankcode   = isset($re['bankcode'])?$re['bankcode']:'';
    //         $verifyStatus = isset($re['verifyStatus'])?$re['verifyStatus']:'';
    //         $yborderid  = isset($re['yborderid'])?$re['yborderid']:'';
    //         $remark = json_encode($re);
    //         $now = time();

    //         $sql = "UPDATE xf_borrower_bank_info_modify_log SET 
    //                 status = {$status} ,
    //                 errormsg = '{$errormsg}',
    //                 cardtop = '{$cardtop}',
    //                 cardlast = '{$cardlast}',
    //                 bankcode = '{$bankcode}',
    //                 remark = '{$remark}',
    //                 yborderid = '{$yborderid}',
    //                 verifyStatus = '{$verifyStatus}' 
    //             where id = {$bankcardModifyLog['id']}";
              
    //         $res = Yii::app()->phdb->createCommand($sql)->execute();
    //         if ($res === false) {
    //             Yii::app()->phdb->rollback();
    //             throw new Exception('写入换卡记录表失败');
    //         }

    //         if ($status == 1) {
    //             $sql = "UPDATE firstp2p_user_bankcard set verify_status=1  where user_id = {$params['user_id']} and request_no = '{$bankcardModifyLog['request_no']}'";
    //             $res = Yii::app()->fdb->createCommand($sql)->execute();
    //             if ($res === false) {
    //                 Yii::app()->phdb->rollback();
    //                 throw new Exception('写入创建新还款计划表记录表失败');
    //             }
    //             Yii::app()->phdb->commit();
    //         } else {
    //             Yii::app()->phdb->commit();
    //             throw new Exception('易宝绑卡失败:'. $errormsg);
    //         }
    //     } catch (Exception  $e) {
    //         throw $e;
    //     }
    // }


    // public function bindBankCardYopStep1($data=[])
    // {
    //     $request = new YopRequest(YopConfig::APP_KEY, YopConfig::CFCA_PRIVATE_KEY);
    //     $request->addParam("merchantno", YopConfig::MERCHANT_NO);

    //     //加入请求参数
    //     $request->addParam("identityid", $data['user_id']);//商户生成的用户唯一标识
    //     $request->addParam("cardno", $data['bankcard']);//银行卡号
    //     $request->addParam("idcardno", $data['idno']);//身份证号
    //     $request->addParam("username", $data['real_name']);//姓名
    //     $request->addParam("phone", $data['mobile']);//手机号
    //     $request->addParam("requestno", $data['request_no']);//商户生成的唯一绑卡请求号


    //     $request->addParam("requesttime", date('Y-m-d H:i:s'));//请求时间
    //     $request->addParam("identitytype", "USER_ID");
    //     $request->addParam("idcardtype", "ID");//身份证号
    //     $request->addParam("issms", "true");//短信
    //     $request->addParam("authtype", "COMMON_FOUR");//固定值
       
    //     //提交Post请求
    //     $response = YopClient3::post("/rest/v1.0/paperorder/unified/auth/request", $request);
       
    //     //code...
    //     if ($response->validSign==1) {
    //         $re = $this->object_array($response);
            
    //         if (strtoupper($re['state']) == 'SUCCESS') {
    //             return $re['result'];
    //         }
    //     }
    //     return false;
    // }


    // public function bindBankCardYopStep2($data=[])
    // {
    //     $request = new YopRequest(YopConfig::APP_KEY, YopConfig::CFCA_PRIVATE_KEY);
    //     $request->addParam("merchantno", YopConfig::MERCHANT_NO);

    //     //加入请求参数
    //     $request->addParam("requestno", $data['request_no']);//商户生成的唯一绑卡请求号
    //     $request->addParam("validatecode", $data['validatecode']);//短信验证码， 6 位数字

    //     //提交Post请求
    //     $response = YopClient3::post("/rest/v1.0/paperorder/auth/confirm", $request);

    //     if ($response->validSign==1) {
    //         $re = $this->object_array($response);
            
    //         if (strtoupper($re['state']) == 'SUCCESS') {
    //             return $re['result'];
    //         }
    //     }
    //     return false;
    // }


    public function queryBankNameYop($data=[])
    {
        //维持不用改
        $request = new YopRequest(YopConfig::APP_KEY, YopConfig::CFCA_PRIVATE_KEY);
        $request->addParam("merchantno", YopConfig::MERCHANT_NO);

        //加入请求参数
        $request->addParam("cardno", $data['cardno']);//商户生成的唯一绑卡请求号
        $request->addParam("traceid", $data['traceid']);//

        //提交Post请求
        $response = YopClient3::post("/rest/v1.0/paperorder/temppay/bankcardrecord", $request);

        if ($response->validSign==1) {
            $re = $this->object_array($response);
            
            if (strtoupper($re['state']) == 'SUCCESS') {
                return $re['result'];
            }
        }
        return false;
    }

    public function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key=>$value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }

    /**
     * 确认打款
     *
     * @param int $id
     * @return void
     */
    public function confirmPay($id)
    {
        try {
           
            Yii::app()->phdb->beginTransaction();

            if (empty($id)) {
                throw new Exception('求购记录ID不能为空');
            }

            

            $xf_exclusive_purchase     = Yii::app()->phdb->createCommand("select * from xf_exclusive_purchase where id  = {$id} for update ")->queryRow();
            if (!$xf_exclusive_purchase) {
                throw new Exception('求购记录不存在');
            }

            $config = Yii::app()->c->payment_account_config[$xf_exclusive_purchase['purchase_user_id']];

            if (empty($config)) {
                throw new Exception('支付信息配置异常:'.$xf_exclusive_purchase['purchase_user_id']);
            }

            if ($xf_exclusive_purchase['status'] == 5) {
                throw new Exception('该笔求购已失效');
            }

            if ($xf_exclusive_purchase['status'] > 1) {
                throw new Exception('该笔求购已付款');
            }

            if ($xf_exclusive_purchase['pay_status'] == 1) {
                throw new Exception('该笔求购支付中，请耐心等待');
            }

            if ($xf_exclusive_purchase['pay_status'] == 2) {
                throw new Exception('该笔求购已支付');
            }

            if ($xf_exclusive_purchase['status'] == 0) {
                throw new Exception('出借人未签约');
            }
         
            $current_admin_id = \Yii::app()->user->id;
            
            $add_ip      = Yii::app()->request->userHostAddress;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $batch_no = date('YmdHis').mt_rand(1000, 9999);
            $now = time();
        
            $sql = "INSERT INTO xf_exclusive_purchase_payment_log (user_id,exclusive_purchase_id,batch_no,purchase_amount,status,add_user_id,add_user_name,add_ip,add_time) VALUES ";
            $sql .= "({$xf_exclusive_purchase['user_id']},{$xf_exclusive_purchase['id']},'{$batch_no}','{$xf_exclusive_purchase['purchase_amount']}',1,'{$current_admin_id}','{$username}','{$add_ip}','{$now}')";
            $sql = rtrim($sql, ',');
            // echo $sql;
            // die;
            $res = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res === false) {
                throw new Exception('写入支付记录表失败');
            }

            $sql = "UPDATE xf_exclusive_purchase SET pay_status = 1  where id  = {$id} ";
            $res = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res === false) {
                throw new Exception('更新收购表支付失败失败');
            }
            Yii::app()->phdb->commit();
        } catch (\Exception $e) {
            Yii::log(__CLASS__." confirm payment Exception:".$e->getMessage(), 'error');
            Yii::app()->phdb->rollback();
            throw $e;
        }

        $xf_exclusive_purchase['batch_no'] =  $batch_no ;
    
        $re = $this->transfer_send($xf_exclusive_purchase);

       
        if (!$re || $re['result']['errorCode'] != 'BAC001') {
            try {
                Yii::app()->phdb->beginTransaction();
                $str = '易宝返回异常:'.$re['result']['errorMsg'].$re['error']['message'];
                $sql = "UPDATE xf_exclusive_purchase_payment_log SET status = 3 ,remark = '{$str}' where batch_no = '{$batch_no}'";
                $res = Yii::app()->phdb->createCommand($sql)->execute();
                if ($res == false) {
                    throw new Exception($str.' 更新支付记录表状态失败 batch_no:'.$batch_no);
                }
                $sql = "UPDATE xf_exclusive_purchase SET pay_status = 0  where id  = {$id} ";
                $res = Yii::app()->phdb->createCommand($sql)->execute();
                if ($res === false) {
                    throw new Exception('更新收购表支付失败失败');
                }
                Yii::app()->phdb->commit();
                //code...
            } catch (Exception $e) {
                Yii::app()->phdb->rollback();
                //throw $th;
            }
            
            throw new Exception($str);
        }
        return true;
    }

       /**
     * 确认打款
     *
     * @param int $id
     * @return void
     */
    public function offlineConfirmPay($params)
    {
        try {
            $id = $params['id'];
           
            if (empty($id)) {
                throw new Exception('求购记录ID不能为空');
            }
            if(empty($params['image_url'])){
                throw new Exception('请上传付款凭证');
            }
            $image_url = $params['image_url'];
            Yii::app()->phdb->beginTransaction();
            $xf_exclusive_purchase     = Yii::app()->phdb->createCommand("select * from xf_exclusive_purchase where id  = {$id} for update ")->queryRow();
            if (!$xf_exclusive_purchase) {
                throw new Exception('求购记录不存在');
            }

            if ($xf_exclusive_purchase['status'] == 5) {
                throw new Exception('该笔求购已失效');
            }

            if ($xf_exclusive_purchase['status'] > 1) {
                throw new Exception('该笔求购已付款');
            }

            if ($xf_exclusive_purchase['pay_status'] == 1) {
                throw new Exception('该笔求购支付中，请耐心等待');
            }

            if ($xf_exclusive_purchase['pay_status'] == 2) {
                throw new Exception('该笔求购已支付');
            }

            if ($xf_exclusive_purchase['status'] == 0) {
                throw new Exception('出借人未签约');
            }
         
            $current_admin_id = \Yii::app()->user->id;
            
            $add_ip      = Yii::app()->request->userHostAddress;
            $userInfo = Yii::app()->user->getState("_user");
            $username = $userInfo['username'];
            $batch_no = date('YmdHis').mt_rand(1000, 9999);

            $now = time();
        
            $sql = "INSERT INTO xf_exclusive_purchase_payment_log (batch_no,user_id,exclusive_purchase_id,remark,purchase_amount,status,add_user_id,add_user_name,add_ip,add_time) VALUES ";
            $sql .= "('{$batch_no}',{$xf_exclusive_purchase['user_id']},{$xf_exclusive_purchase['id']},'线下付款','{$xf_exclusive_purchase['purchase_amount']}',2,'{$current_admin_id}','{$username}','{$add_ip}','{$now}')";
            $sql = rtrim($sql, ',');
            // echo $sql;
            // die;
            $res = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res === false) {
                throw new Exception('写入支付记录表失败');
            }

            $sql = "UPDATE xf_exclusive_purchase SET pay_time={$now},pay_type = 1, status = 2,pay_status = 2,credentials_url='{$image_url}'  where id  = {$id} ";
            $res = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res === false) {
                throw new Exception('更新收购表支付失败失败');
            }
            Yii::app()->phdb->commit();
            return true;
        } catch (\Exception $e) {
            Yii::log(__CLASS__." confirm payment Exception:".$e->getMessage(), 'error');
            Yii::app()->phdb->rollback();
            throw $e;
        }
    }


    public function query_customer_amount($data){

        $config = Yii::app()->c->payment_account_config[$data['purchase_user_id']];
        
        $request = new YopRequest($config['APP_KEY'], $config['CFCA_PRIVATE_KEY']);
        $request->addParam("merchantno", $config['MERCHANT_NO']);

       
        //提交Post请求
        $response = YopClient3::post("/rest/v1.0/balance/query_customer_amount", $request);
        $re = $this->object_array($response);
        Yii::log(__CLASS__." payment request:".json_encode($data, JSON_UNESCAPED_UNICODE)."  yibao return ".json_encode($re, JSON_UNESCAPED_UNICODE), 'info');

        if ($re['validSign'] == 1) {
            return $re;
        }
    }


    public function transfer_send($data=[])
    {

        $config = Yii::app()->c->payment_account_config[$data['purchase_user_id']];
        
        $request = new YopRequest($config['APP_KEY'], $config['CFCA_PRIVATE_KEY']);
        $request->addParam("merchantno", $config['MERCHANT_NO']);

        //加入请求参数
        $request->addParam("batchNo", $data['batch_no']);//商户生成的唯一请求号
        $request->addParam("orderId", $data['order_no']);//商户生成的唯一订单号
        $request->addParam("amount", $data['purchase_amount']);//金额
        $request->addParam("accountName", $data['real_name']);//收款帐户的开户姓名
        $request->addParam("accountNumber", $data['bank_card']);//收款帐户的卡号
        $request->addParam("bankCode", $data['bankcode']);//银行编码
      

        //提交Post请求
        $response = YopClient3::post("/rest/v1.0/balance/transfer_send", $request);
        $re = $this->object_array($response);
        Yii::log(__CLASS__." payment request:".json_encode($data, JSON_UNESCAPED_UNICODE)."  yibao return ".json_encode($re, JSON_UNESCAPED_UNICODE), 'info');

        if ($re['validSign'] == 1) {
            return $re;
        }
        return  false ;
    }
    
    /**
     * 资金额度统计
     * @return int[]
     */
    public function getQuotasStat()
    {
        $return_data = array(
            'total_quotas' => 0,
            'frozen_quotas' => 0,
            'surplus_quotas' => 0,
            'finish_quotas' => 0,
        );

        // 查询数据总量
        $sql = "select sum(transferability_limit) from xf_purchase_assignee where status in (2,3)";
        $transferability_limit = Yii::app()->phdb->createCommand($sql)->queryScalar();
        if ($transferability_limit > 0) {
            $return_data['total_quotas'] = $transferability_limit;
        }

        //冻结中额度
        $frozen_quotas = $this->getPurchaseData([0,1]);
        $return_data['frozen_quotas'] = $frozen_quotas ?: 0;
        //交易完成额度
        $finish_quotas = $this->getPurchaseData([2,3,4]);
        $return_data['finish_quotas'] = $finish_quotas ?: 0;
        //剩余额度
        $return_data['surplus_quotas'] = bcsub($return_data['total_quotas'], $return_data['frozen_quotas']+$return_data['finish_quotas'], 2);
        return $return_data;
    }

    /**
     * 债权统计
     * @return int[]
     */
    public function getDebtStat()
    {
        $return_data = array(
            'total_debt' => 0,
            'be_sign_debt' => 0,
            'be_paid_debt' => 0,
            'finish_debt' => 0,
        );
        // 总债权金额（元）   0-待签约，1-待付款，2-已付款待债转，3-已债转待生成合同，4-交易完成   求购计划对应的收购金额和
        $total_debt = $this->getPurchaseData([0,1,2,3,4]);
        $return_data['total_debt'] = $total_debt ?: 0;

        //待签约债权（元） 0-待签约
        $be_sign_debt = $this->getPurchaseData([0]);
        $return_data['be_sign_debt'] = $be_sign_debt ?: 0;

        //待付款债权（元）  1-待付款
        $be_paid_debt = $this->getPurchaseData([1]);
        $return_data['be_paid_debt'] = $be_paid_debt ?: 0;

        //已交易完成债权（元）  2-已付款待债转，3-已债转待生成合同，4-交易完成
        $finish_debt = $this->getPurchaseData([2,3,4]);
        $return_data['finish_debt'] = $finish_debt ?: 0;
        return $return_data;
    }

    /**
     * 人数统计
     * @return int[]
     */
    public function getUserStat()
    {
        $return_data = array(
            'total_user' => 0,
            'be_sign_user' => 0,
            'be_paid_user' => 0,
            'finish_user' => 0,
            'fail_user' => 0,
        );
        //总出借人数  求购计划对应的人数   0-待签约，1-待付款，2-已付款待债转，3-已债转待生成合同，4-交易完成 ，5-已失效
        $total_user = $this->getPurchaseUser([0,1,2,3,4,5]);
        $return_data['total_user'] = $total_user ?: 0;

        //待签约人数   0-待签约，
        $be_sign_user = $this->getPurchaseUser([0]);
        $return_data['be_sign_user'] = $be_sign_user ?: 0;

        //待付款人数  1-待付款
        $be_paid_user = $this->getPurchaseUser([1]);
        $return_data['be_paid_user'] = $be_paid_user ?: 0;

        //已出清人数  2-已付款待债转，3-已债转待生成合同，4-交易完成
        $finish_user = $this->getPurchaseUser([2,3,4]);
        $return_data['finish_user'] = $finish_user ?: 0;

        //交易失败人数   5-已失效
        $fail_user = $this->getPurchaseUser([5]);
        $return_data['fail_user'] = $fail_user ?: 0;

        return $return_data;
    }


    private function getPurchaseData($status = [])
    {
        if (!is_array($status) || empty($status)) {
            return false;
        }
        $sql = "select sum(wait_capital) from xf_exclusive_purchase where status in (".implode(',', $status).")";
        $wait_capital = Yii::app()->phdb->createCommand($sql)->queryScalar();
        return $wait_capital;
    }

    private function getPurchaseUser($status = [])
    {
        if (!is_array($status) || empty($status)) {
            return false;
        }
        $sql = "select count(distinct user_id) from xf_exclusive_purchase where status in (".implode(',', $status).")";
        $purchase_user = Yii::app()->phdb->createCommand($sql)->queryScalar();
        return $purchase_user;
    }

    /**
     * 下载凭证
     *
     * @param array $data
     * @return void
     */
    public function download_receipt($data=[])
    {
        try {
            $config = Yii::app()->c->payment_account_config[$data['purchase_user_id']];
        
            $request = new YopRequest($config['APP_KEY'], $config['CFCA_PRIVATE_KEY']);
            $request->addParam("merchantno", $config['MERCHANT_NO']);

    
            //加入请求参数
            $request->addParam("batchNo", $data['batch_no']);//商户生成的唯一请求号
            $request->addParam("orderId", $data['order_no']);//商户生成的唯一订单号
            //get请求
            $response = YopClient3::get("/yos/v1.0/balance/yop-simple-remit/download-electronic-receipt", $request);
        
            $response = end($response);
            $name = 'payment_receipt/'.$data['batch_no'].'_download_receipt.jpg';
            $path = APP_DIR.'/public/upload/'.$name ;
            file_put_contents($path, $response);
            $res = Yii::app()->oss->bigFileUpload($path, $name);
            Yii::log(__CLASS__." download_receipt  oss return ".json_encode($res, JSON_UNESCAPED_UNICODE), 'info');

            return  Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$name;
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * 打款结果异步回调
     *
     * @param array $data
     * @return void
     */
    public function paymentCallBack($data)
    {
        try {
            Yii::app()->phdb->beginTransaction();
           
            if (empty($data)) {
                throw new Exception('易宝data dec fail');
            }
        
            $payment_log     = Yii::app()->phdb->createCommand("select * from xf_exclusive_purchase_payment_log where batch_no = '{$data['batchNo']}' for update ")->queryRow();
            if (!$payment_log) {
                throw new Exception('求购记录不存在');
            }

            if ($payment_log['status'] == 2) {
                throw new Exception('SUCCESS');
            }

            if ($data['transferStatusCode'] != '0026' || $data['bankTrxStatusCode'] != 'S') {
                throw new Exception('not SUCCESS');
            }
            $remark = json_encode($data,JSON_UNESCAPED_UNICODE);
            $sql = "UPDATE xf_exclusive_purchase_payment_log SET status = 2,remark='{$remark}' where id = {$payment_log['id']}";
      
            $res = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res === false) {
                Yii::app()->phdb->rollback();
                throw new Exception('更新支付记录表失败');
            }

            $pay_time = time();
            $sql = "UPDATE xf_exclusive_purchase SET pay_time={$pay_time},status = 2,pay_status=2 where id = {$payment_log['exclusive_purchase_id']}";
      
            $res = Yii::app()->phdb->createCommand($sql)->execute();
            if ($res === false) {
                Yii::app()->phdb->rollback();
                throw new Exception('更新求购表失败');
            }
            Yii::app()->phdb->commit();
        } catch (\Exception $e) {
            Yii::log(__CLASS__.' paymentCallBack  exception:'.$e->getMessage(), "error");
            Yii::app()->phdb->rollback();
            throw $e;
        }

        $exclusive_purchase   = Yii::app()->phdb->createCommand("select * from xf_exclusive_purchase where id = {$payment_log['exclusive_purchase_id']} ")->queryRow();

        
        if (empty($exclusive_purchase['credentials_url'])) {
            $query = [
                'batch_no'=>$data['batchNo'],
                'order_no'=>$data['orderId'],
                'purchase_user_id'=>$exclusive_purchase['purchase_user_id'],
            ];
            try {
                $re = $this->download_receipt($query);
                if ($re) {
                    $sql = "UPDATE xf_exclusive_purchase SET credentials_url = '{$re}' where id = {$payment_log['exclusive_purchase_id']}";
                    $res = Yii::app()->phdb->createCommand($sql)->execute();
                    if ($res === false) {
                    }
                }
            } catch (Exception $e) {
                //throw $th;
            }
        }
       
        echo "SUCCESS";
        exit;
    }

    public function getStatisticsList($params)
    {
        $M= "XfPurchaseStatistics";
      
        $fileList = [];
        $countFile = $M::model()->countBySql('select count(1) from xf_purchase_statistics  ');
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select * from xf_purchase_statistics  order by id desc  LIMIT {$offset} , {$pageSize} ";
            $fileList =  Yii::app()->phdb->createCommand($sql)->queryAll();
            foreach ($fileList as $key => &$value) {
                $value['handle_time'] = date('Y-m-d', $value['handle_time']);
            }
        }
        return ['countNum' => $countFile, 'list' => $fileList];
    }

    public $assignee_field = 'assignee_id';
    //获取受让人id
    public function getLoginUserAssigneeID()
    {
        //催收公司展示分配的借款人
        $current_admin_id = \Yii::app()->user->id;
        $adminInfo = Yii::app()->db->createCommand("select * from itz_user where id = {$current_admin_id}")->queryRow();
        return $adminInfo[$this->assignee_field];
    }


}
