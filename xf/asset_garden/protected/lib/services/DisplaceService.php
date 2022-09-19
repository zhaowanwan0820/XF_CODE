<?php

class  DisplaceService extends ItzInstanceService
{
    const MIN_LOAN_AMOUNT = 5; //最低起投金额
    public  $borrow_type = [2,3];//尊享支持2/3
    public $table_prefix;
    public $db_name = 'db';
    const TempDir = '/tmp/displaceContract/';


    /**
     * @return string
     */
    public function getDisplaceContractUrl($user_info){
        $disable_condition = '';
        $user_id = $user_info['id'];
        if(empty($user_info) || empty($user_id) || !is_numeric($user_id)){
            Yii::log("getDisplaceContractUrl user_id[$user_id] error", 'error');
            return false;
        }

        //普惠转让中债权
        $end_time = time()-60*5;
        $check_ret = PHDebtExchangeLog::model()->find(" user_id={$user_id} and (status=1 or (status=9 and addtime>=$end_time)) ");
        if($check_ret){
            Yii::log("getDisplaceContractUrl user_id[$user_id] 01 PHDebtExchangeLog   error", 'error');
            return false;
        }

        //智多新转让中债权
        $check_ret = OfflineDebtExchangeLog::model()->find(" user_id={$user_id} and (status=1 or (status=9 and addtime>={$end_time})) ");
        if ($check_ret) {
            Yii::log("getDisplaceContractUrl user_id[$user_id] 02 OfflineDebtExchangeLog   error", 'error');
            return false;
        }

        //执行中定向收购
        $purchase_sql = "SELECT id,status FROM xf_exclusive_purchase WHERE user_id = {$user_id} and status in (1,2,3)";
        $result_data  = Yii::app()->phdb->createCommand($purchase_sql)->queryRow();
        if($result_data){
            Yii::log("getDisplaceContractUrl user_id[$user_id] 03 xf_exclusive_purchase   error", 'error');
            return false;
        }

        //禁止信息[黑名单]
        $disableBorrow  = $this->getDisableBorrow(2);
        if (!empty($disableBorrow)) {
            $disable_condition = " AND t.deal_id  not in (" . implode(',', $disableBorrow) . ") ";
        }

        //普惠债权总金额
        $public_sql = " SELECT t.id, t.wait_capital FROM firstp2p_deal_load as t   
          WHERE   t.user_id = $user_id AND t.wait_capital > 0 AND t.xf_status = 0  AND t.black_status = 1  and t.status=1  " . $disable_condition;
        $ph_load = Yii::app()->phdb->createCommand($public_sql)->queryAll();
        $ph_load_ids = $zdx_load_ids = [];
        $displace_capital = $debt_type = 0;
        if($ph_load){
            $debt_type = 2;
            foreach ($ph_load as $p_val){
                $ph_load_ids[] = $p_val['id'];
                $displace_capital = bcadd($displace_capital, $p_val['wait_capital'], 2);
            }
        }

        //智多新总额
        $zdx_yr_sql = str_replace("firstp2p", "offline", $public_sql) . ' AND t.platform_id = 4 ';
        $zdx_load = Yii::app()->offlinedb->createCommand($zdx_yr_sql)->queryAll();;
        if($zdx_load){
            $debt_type = 4;
            foreach ($zdx_load as $z_val){
                $zdx_load_ids[] = $z_val['id'];
                $displace_capital = bcadd($displace_capital, $z_val['wait_capital'], 2);
            }
        }
        //无可兑换债权
        if(empty($ph_load) && empty($zdx_load)){
            Yii::log("getDisplaceContractUrl user_id[$user_id] 04 deal_load   error", 'error');
            return false;
        }

        if(!empty($ph_load) && !empty($zdx_load)){
            $debt_type = 99;
        }
        //受让人
        $buyer_uid = Yii::app()->c->contract['displace_uid'];
        if(!$buyer_uid){
            Yii::log("getDisplaceContractUrl user_id[$user_id] 05 buyer_uid  error", 'error');
            return false;
        }
        //受让人信息查询
        $assignee = User::model()->findByPk($buyer_uid)->attributes;
        if (!$assignee) {
            Yii::log("getDisplaceContractUrl   user_id[$user_id] 06 buyer_uid  error ", 'error');
            return false;
        }


        //拼接合同内容
        $seller_user = $user_info;
        $seller_idno = GibberishAESUtil::dec($seller_user['idno'], Yii::app()->c->contract['idno_key']);
        $customer_mobile = GibberishAESUtil::dec($seller_user['mobile'], Yii::app()->c->contract['idno_key']);
        $template_id = Yii::app()->c->contract['displace_contract_template'];
        $annex_template_id = Yii::app()->c->contract['displace_contract_annex_template'];
        //$bing_uid =Yii::app()->c->contract['bing_uid'];
        $sign_data = [];
        //合同生成=====================================================================================================
        $displace_cvalue = [
            'title' => '网信普惠账户项下债权及账户权益整体转让协议',
            'params' => [
                'total_displace' =>  $displace_capital,
                'user_real_name' => $seller_user['real_name'],
                'user_id_01' => $seller_user['id'],
                'user_id_no' => $seller_idno,
                'user_id_02' => $seller_user['id'],
                'yi_total_displace' => $displace_capital,
                'date_01' => date('Y年m月d日'),
                'date_02' => date('Y年m月d日'),
                'date_03' => date('Y年m月d日')
            ],
            'sign' => [
                'A盖签' => $seller_user['yj_fdd_customer_id'],
                'B盖签' => '',
                'C盖签' => '',
            ],
            'pwd' => '',
        ];

        //生成合同
        $result = XfFddService::getInstance()->invokeGenerateContract($template_id, $displace_cvalue['title'], $displace_cvalue['params']);
        if (!$result || $result['code'] != 1000) {
            Yii::log("getDisplaceContractUrl   user_id[$user_id] 合同生成失败！\n" . print_r($result, true), 'error');
            return false;
        }
        //法大大合同ID
        $contract_id = $result['contract_id'];

        //加水印
        $text_name = mb_substr("{$seller_user['real_name']}  {$assignee['real_name']}", 0, 15, 'utf-8');
        $watermark_params = [
            'contract_id' => $contract_id,
            'stamp_type' => 1,
            'text_name' => $text_name,
            'font_size' => 12,
            'rotate' => 45,
            'concentration_factor' => 10,
            'opacity' => 0.2,
        ];
        $result = XfFddService::getInstance()->watermarkPdf($watermark_params);
        if (!$result || $result['code'] != 1) {
            Yii::log("getDisplaceContractUrl   user_id[$user_id]  的{$displace_cvalue['title']}加水印失败！\n" . print_r($result, true), 'error');
            return false;
        }

        $displace_contract_transaction_id = FunctionUtil::getRequestNo('WJZH');
        $sign_data[] = [
            'contractId' => $contract_id,
            'signKeyword' => 'A盖签',
            'transactionId' => $displace_contract_transaction_id,
        ];


        //合同二生成======================================================================
        $annex_cvalue = [
            'title' => '网信普惠账户项下债权及账户权益整体转让协议之补充协议',
            'params' => [
                'user_real_name' => $seller_user['real_name'],
                'user_id_01' => $seller_user['id'],
                'user_id_no' => $seller_idno,
                'user_id_02' => $seller_user['id'],
                'date_01' => date('Y年m月d日'),
                'date_02' => date('Y年m月d日'),
                'date_03' => date('Y年m月d日')
            ],
            'sign' => [
                'A盖签' => $seller_user['yj_fdd_customer_id'],
                'B盖签' => '',
                'C盖签' => '',
            ],
            'pwd' => '',
        ];

        //生成合同
        $result = XfFddService::getInstance()->invokeGenerateContract($annex_template_id, $annex_cvalue['title'], $annex_cvalue['params']);
        if (!$result || $result['code'] != 1000) {
            Yii::log("getDisplaceContractUrl   user_id[$user_id] 合同生成失败！\n" . print_r($result, true), 'error');
            return false;
        }
        //法大大合同ID
        $annex_contract_id = $result['contract_id'];

        //加水印
        $text_name = mb_substr("{$seller_user['real_name']}  {$assignee['real_name']}", 0, 15, 'utf-8');
        $watermark_params = [
            'contract_id' => $annex_contract_id,
            'stamp_type' => 1,
            'text_name' => $text_name,
            'font_size' => 12,
            'rotate' => 45,
            'concentration_factor' => 10,
            'opacity' => 0.2,
        ];
        $result = XfFddService::getInstance()->watermarkPdf($watermark_params);
        if (!$result || $result['code'] != 1) {
            Yii::log("getDisplaceContractUrl   user_id[$user_id]  的{$annex_cvalue['title']}加水印失败！\n" . print_r($result, true), 'error');
            return false;
        }

        $annex_transactionId = FunctionUtil::getRequestNo('ZHFJ');
        $sign_data[] = [
            'contractId' => $annex_contract_id,
            'signKeyword' => 'A盖签',
            'transactionId' => $annex_transactionId,
        ];
        //====================================================

        //卖方手动签署合同
        $batch_id = FunctionUtil::getRequestNo('BHZH');
        $batch_title = '批量签署-整体转让协议';
        $sign_contract_url = XfFddService::getInstance()->gotoBatchSemiautoSignPage($batch_id,$batch_title,$sign_data,$seller_user['yj_fdd_customer_id'],$customer_mobile );
        if (!$sign_contract_url) {
            Yii::log("getDisplaceContractUrl   user_id[$user_id]  收购合同获取签署地址失败！\n" . print_r($result, true), 'error');
            return false;
        }

        $zdx = count($zdx_load_ids);
        $this->zhBeginTransaction($zdx);
        try {
            //置换数据记录
            $displace_data = [
                'user_id' => $user_id,//出借人ID
                'real_name' => $user_info['real_name'],// 姓名
                'bank_card' => $user_info['bankcard'],// 银行卡号
                'province_name' => $user_info['province_name'],// 身份证号省
                'card_address' => $user_info['card_address'],// 证件号匹配地址
                'displace_capital' => $displace_capital,//置换本金
                'status' => 0,//置换状态：0-签约待回调，1-用户已签约待万峻签约，2-万峻已签约待债转,3已债转待置换，4-置换完成，5-置换失败
                'add_ip' => FunctionUtil::ip_address(),// 用户操作置换的IP地址
                'add_time' => time(),//用户操作置换时间
                'displace_type' => 1,//置换方式：0-系统批量操作，1-用户法大大签约，2-用户确认签约，3-用户其他签约
                'contract_url' => $sign_contract_url,// 合同地址
                'debt_type' => $debt_type,//用户操作置换时间
                'contract_id' => $contract_id,// 合同ID
                'annex_contract_id' => $annex_contract_id,// 合同ID
                'contract_transaction_id' => $batch_id,// 合同生成交易号
                'annex_contract_transaction_id' => $annex_transactionId,
                'displace_contract_transaction_id' => $displace_contract_transaction_id,
                'add_device' => $_POST['add_device'],
                'add_browser' => $_POST['add_browser'],
                'ph_increase_reduce' => $user_info['ph_increase_reduce'],
            ];

            //登录设备
            if(empty($_POST['add_device']) && empty($_POST['add_browser'])){
                $agent = FunctionUtil::get_user_agent();
                $displace_data['add_device'] = $agent['brand'];
                $displace_data['add_browser'] = $agent['browser'];
            }
            $displace_data['mobile_phone'] = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key);
            $displace_data['idno'] = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key);
            $ret = BaseCrudService::getInstance()->add('XfDisplaceRecord', $displace_data);
            if(false == $ret){
                Yii::log("getDisplaceContractUrl   user_id[$user_id]  addDisplace error", 'error');
                $this->zhRollback($zdx);
                return false;
            }
            //普惠记录
            if($ph_load_ids){
                $edit_load_sql = "update firstp2p_deal_load set displace_id={$ret['id']},displace_amount=wait_capital where user_id={$user_id} and status=1 and xf_status=0 and black_status=1 and wait_capital>0 and id in (".implode(',', $ph_load_ids).")";
                $result = Yii::app()->phdb->createCommand($edit_load_sql)->execute();
                if (!$result) {
                    Yii::log("getDisplaceContractUrl   user_id[$user_id]  $edit_load_sql error", 'error');
                    $this->zhRollback($zdx);
                    return false;
                }
            }

            //智多新记录
            if($zdx_load_ids){
                $edit_load_sql = "update offline_deal_load set displace_id={$ret['id']},displace_amount=wait_capital where user_id={$user_id} and status=1 and xf_status=0 and black_status=1 and wait_capital>0 and platform_id=4 and id in (".implode(',', $zdx_load_ids).")";
                $result = Yii::app()->offlinedb->createCommand($edit_load_sql)->execute();
                if (!$result) {
                    Yii::log("getDisplaceContractUrl   user_id[$user_id]  $edit_load_sql error", 'error');
                    $this->zhRollback($zdx);
                    return false;
                }
            }
            $this->zhCommit($zdx);
            Yii::log("getDisplaceContractUrl   user_id[$user_id]  return  $sign_contract_url", 'error');
            return $sign_contract_url;
        }catch (Exception $e) {
            $this->zhRollback($zdx);
            Yii::log("getDisplaceContractUrl user_id=[$user_id] Fail:".print_r($e->getMessage(), true), "error");
            return false;
        }
    }

    //置换操作
    public function displace($user_id){
        if(empty($user_id) || !is_numeric($user_id)){
            Yii::log(" displace user_id:$user_id error ", 'error');
            return false;
        }

        $sql = "SELECT * FROM firstp2p_user WHERE id = '{$user_id}' AND is_effect = 1 AND is_delete = 0 ";
        $user_info = Yii::app()->db->createCommand($sql)->queryRow();
        if($user_info['is_displace'] == 1){
            Yii::log(" displace user_id:$user_id is_displace=1 ", 'error');
            return false;
        }
        $user_sql = "SELECT * FROM xf_user_recharge_withdraw WHERE user_id = {$user_id}  ";
        $user_recharge_info = Yii::app()->db->createCommand($user_sql)->queryRow();
        if($user_recharge_info['ph_increase_reduce'] > 0){
            Yii::log(" displace user_id:$user_id ph_increase_reduce【{$user_recharge_info['ph_increase_reduce']}】>0 ", 'error');
            return false;
        }
        //普惠转让中债权
        $end_time = time()-60*5;
        $check_ret = PHDebtExchangeLog::model()->find(" user_id={$user_id} and (status=1 or (status=9 and addtime>=$end_time)) ");
        if($check_ret){
            Yii::log("displace user_id[$user_id] 01 PHDebtExchangeLog   error", 'error');
            return false;
        }

        //智多新转让中债权
        $check_ret = OfflineDebtExchangeLog::model()->find(" user_id={$user_id} and (status!=9 or (status=9 and addtime>={$end_time})) ");
        if ($check_ret) {
            Yii::log("displace user_id[$user_id] 02 OfflineDebtExchangeLog   error", 'error');
            return false;
        }

        //禁止信息[黑名单]
        $disableBorrow  = $this->getDisableBorrow(2);
        if (!empty($disableBorrow)) {
            $disable_condition = " AND t.deal_id  not in (" . implode(',', $disableBorrow) . ") ";
        }

        //普惠债权总金额
        $public_sql = " SELECT t.id, t.wait_capital FROM firstp2p_deal_load as t   
          WHERE   t.user_id = $user_id AND t.wait_capital > 0 AND t.xf_status = 0  AND t.black_status = 1  and t.status=1  " . $disable_condition;
        $ph_load = Yii::app()->phdb->createCommand($public_sql)->queryAll();
        $ph_load_ids = $zdx_load_ids = [];
        $displace_capital = $debt_type = 0;
        if($ph_load){
            $debt_type = 2;
            foreach ($ph_load as $p_val){
                $ph_load_ids[] = $p_val['id'];
                $displace_capital = bcadd($displace_capital, $p_val['wait_capital'], 2);
            }
        }

        //智多新总额
        $zdx_yr_sql = str_replace("firstp2p", "offline", $public_sql) . ' AND t.platform_id = 4 ';
        $zdx_load = Yii::app()->offlinedb->createCommand($zdx_yr_sql)->queryAll();;
        if($zdx_load){
            $debt_type = 4;
            foreach ($zdx_load as $z_val){
                $zdx_load_ids[] = $z_val['id'];
                $displace_capital = bcadd($displace_capital, $z_val['wait_capital'], 2);
            }
        }
        //无可兑换债权
        if(empty($ph_load) && empty($zdx_load)){
            Yii::log("displace user_id[$user_id] 04 deal_load   error", 'error');
            return false;
        }

        if(!empty($ph_load) && !empty($zdx_load)){
            $debt_type = 99;
        }

        $sql  = "SELECT * FROM firstp2p_user_bankcard WHERE user_id = {$user_info['id']} AND verify_status = 1";
        $card = Yii::app()->db->createCommand($sql)->queryRow();
        $user_info['bankcard'] = '';
        if (!empty($card['bankcard'])) {
            $user_info['bankcard'] = GibberishAESUtil::dec($card['bankcard'], Yii::app()->c->idno_key);
        }
        
        $this->allBeginTransaction();
        try {

            //置换数据记录
            $displace_data = [
                'user_id' => $user_id,//出借人ID
                'real_name' => $user_info['real_name'],// 姓名
                'bank_card' => $user_info['bankcard'],// 银行卡号
                'province_name' => $user_info['province_name'],// 身份证号省
                'card_address' => $user_info['card_address'],// 证件号匹配地址
                'displace_capital' => $displace_capital,//置换本金
                'status' => 2,//置换状态：0-签约待回调，1-用户已签约待万峻签约，2-万峻已签约待债转,3已债转待置换，4-置换完成，5-置换失败
                'add_ip' => FunctionUtil::ip_address(),// 用户操作置换的IP地址
                'add_time' => time(),//用户操作置换时间
                'displace_type' => $_POST['displace_type'],//置换方式：0-系统批量操作，1-用户法大大签约，2-用户确认签约，3-用户其他签约 
                'debt_type' => $debt_type,
                'add_device' => $_POST['add_device'],
                'add_browser' => $_POST['add_browser'],
                'ph_increase_reduce' => $user_recharge_info['ph_increase_reduce'],
            ];

            //登录设备
            if(empty($_POST['add_device']) && empty($_POST['add_browser'])){
                $agent = FunctionUtil::get_user_agent();
                $displace_data['add_device'] = $agent['brand'];
                $displace_data['add_browser'] = $agent['browser'];
            }
            $displace_data['mobile_phone'] = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key);
            $displace_data['idno'] = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->idno_key);
            $ret = BaseCrudService::getInstance()->add('XfDisplaceRecord', $displace_data);
            if(false == $ret){
                Yii::log("displace   user_id[$user_id]  addDisplace error", 'error');
                $this->allRollback();
                return false;
            }
            //普惠记录
            if($ph_load_ids){
                $edit_load_sql = "update firstp2p_deal_load set displace_id={$ret['id']},displace_amount=wait_capital where user_id={$user_id} and status=1 and xf_status=0 and black_status=1 and wait_capital>0 and id in (".implode(',', $ph_load_ids).")";
                $result = Yii::app()->phdb->createCommand($edit_load_sql)->execute();
                if (!$result) {
                    Yii::log("displace   user_id[$user_id]  $edit_load_sql error", 'error');
                    $this->allRollback();
                    return false;
                }
            }

            //智多新记录
            if($zdx_load_ids){
                $edit_load_sql = "update offline_deal_load set displace_id={$ret['id']},displace_amount=wait_capital where user_id={$user_id} and status=1 and xf_status=0 and black_status=1 and wait_capital>0 and platform_id=4 and id in (".implode(',', $zdx_load_ids).")";
                $result = Yii::app()->offlinedb->createCommand($edit_load_sql)->execute();
                if (!$result) {
                    Yii::log("displace   user_id[$user_id]  $edit_load_sql error", 'error');
                    $this->allRollback();
                    return false;
                }
            }

            $update_sql = "update firstp2p_user set  is_displace=1  where id = {$user_id}";
            $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
            if (!$edit_fdd) {
                Yii::log("displace   user_id[$user_id]   is_displace edit error ", "error");
                self::allRollback();
                return false;
            }

            $this->allCommit();
            Yii::log("displace   user_id[$user_id]  displace success", 'error');
            return true;
        }catch (Exception $e) {
            $this->allRollback();
            Yii::log("displace user_id=[$user_id] Fail:".print_r($e->getMessage(), true), "error");
            return false;
        } 
    }

    /**
     * 记录登录日志
     * @param $data
     * @return array
     */
    public function  addLoginLog($data){
        //返回数据预定义
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );
        //用户登录校验
        if(empty($data['user_id']) || !is_numeric($data['user_id'])){
            Yii::log("addLoginLog user_id error", 'error');
            return false;
        }

        //登录方式
        if(!in_array($data['login_type'], [0,1])){
            Yii::log("addLoginLog user_id[{$data['user_id']}]: login_type error", 'error');
            return false;
        }

        //登录设备
        if(empty($data['login_device']) && empty($data['login_browser'])){
            $agent = FunctionUtil::get_user_agent();
            $data['login_device'] = $agent['brand'];
            $data['login_browser'] = $agent['browser'];
        }
        
        //`xf_user_login_log` 表数据组成
        $log_data = $data;
        $log_data['login_ip'] = FunctionUtil::ip_address();
        $log_data['login_time'] = time();
        $ret = BaseCrudService::getInstance()->add('XfUserLoginLog', $log_data);
        if(false == $ret){
            Yii::log("addLoginLog user_id[{$data['user_id']}]: add XfUserLoginLog error", 'error');
            return false;
        }

        Yii::log("addLoginLog user_id[{$data['user_id']}] login success");
        return true;
    }

    /**
     * 禁止黑名单.
     * @param int $type
     * @return array
     */
    public function getDisableBorrow($type = 1)
    {
        $sql = "select deal_id from ag_wx_debt_black_list where `type` = {$type} AND status = 1 ";
        $blackBorrow = Yii::app()->db->createCommand($sql)->queryAll() ?: [];
        if (!$blackBorrow) {
            return $blackBorrow;
        }
        $blackBorrow = ArrayUtil::array_column($blackBorrow, 'deal_id');
        return $blackBorrow;
    }

    protected function zhBeginTransaction($zdx=0)
    {
        Yii::app()->phdb->beginTransaction();
        if ($zdx>0) {
            Yii::app()->offlinedb->beginTransaction();
        }
    }

    protected function zhRollback($zdx=0)
    {
        Yii::app()->phdb->rollback();
        if ($zdx>0) {
            Yii::app()->offlinedb->rollback();
        }
    }

    protected function zhCommit($zdx=0)
    {
        Yii::app()->phdb->commit();
        if ($zdx>0) {
            Yii::app()->offlinedb->commit();
        }
    }

    public function signContract($transaction_id, $tracsaction_str){
        $return_data = [
            'data' => [],
            'code' => 0
        ];
        if(empty($transaction_id) || !in_array($tracsaction_str, ['WJZH','ZHFJ'])){
            Yii::log("signContract transaction_id=[{$transaction_id}] empty", "error");
            $return_data['code'] = 3032;
            return $return_data;
        }
        self::allBeginTransaction();
        try {
            $where = $tracsaction_str == 'WJZH' ? "displace_contract_transaction_id = '{$transaction_id}'" : "annex_contract_transaction_id = '{$transaction_id}'" ;
            $contract_sql = "SELECT * FROM xf_displace_record WHERE $where for update  ";
            $contract_info = Yii::app()->phdb->createCommand($contract_sql)->queryRow();
            if(!$contract_info){
                Yii::log("signContract displace_contract_transaction_id=[{$transaction_id}] xf_displace_record empty", "error");
                self::allRollback();
                $return_data['code'] = 3024;
                return $return_data;
            }
            if($contract_info['status'] != 0){
                Yii::log("signContract displace_contract_transaction_id=[{$transaction_id}] 非待签署状态", "error");
                self::allRollback();
                $return_data['code'] = 3026;
                return $return_data;
            }

            //尊享
            $edit_fdd01 = $edit_fdd02 = true;
            $where = " displace_id>0 and status=1 and user_id={$contract_info['user_id']} and wait_capital>0 and xf_status=0 and black_status=1" ;
            if ($contract_info['debt_type'] == 2){
                $ph_ret = PHDealLoad::model()->find("displace_id = '{$contract_info['id']}' ");
                if(!$ph_ret){
                    $update_sql = "update firstp2p_deal_load set  displace_id = '{$contract_info['id']}' where $where ";
                    $edit_fdd01 = Yii::app()->phdb->createCommand($update_sql)->execute();
                }
            }elseif ($contract_info['debt_type'] == 4){
                $zdx_ret = OfflineDealLoad::model()->find("displace_id = '{$contract_info['id']}' ");
                if(!$zdx_ret){
                    $update_sql = "update offline_deal_load set  displace_id = '{$contract_info['id']}' where $where  and platform_id=4";
                    $edit_fdd01 = Yii::app()->offlinedb->createCommand($update_sql)->execute();
                }
            }elseif ($contract_info['debt_type'] == 99){
                $zdx_ret = OfflineDealLoad::model()->find("displace_id = '{$contract_info['id']}' ");
                if(!$zdx_ret){
                    $update_sql = "update firstp2p_deal_load set  displace_id = '{$contract_info['id']}' where $where";
                    $edit_fdd01 = Yii::app()->phdb->createCommand($update_sql)->execute();
                    $update_sql = "update offline_deal_load set  displace_id = '{$contract_info['id']}' where $where and platform_id=4";
                    $edit_fdd02 = Yii::app()->offlinedb->createCommand($update_sql)->execute();
                }
            }else{
                Yii::log("signContract transaction_id=[{$transaction_id}] debt_type error", "error");
                self::allRollback();
                $return_data['code'] = 3003;
                return $return_data;
            }
            if(!$edit_fdd01 && !$edit_fdd02){
                Yii::log("signContract transaction_id=[{$transaction_id}] 更新兑换记录状态失败 ", "error");
                self::allRollback();
                $return_data['code'] = 3025;
                return $return_data;
            }


            //文件下载至本地临时目录
            /*
            $download_url = $_GET['download_url'];
            $borrow_id = $contract_info['id'];
            if (!is_dir(self::TempDir . $borrow_id)) {
                mkdir(self::TempDir . $borrow_id, 0777, true);
            }
            $date = date('Ymd', $contract_info['add_time']);
            $fileName = 'contract_' . $date . '-' . $borrow_id;
            $initData = file_get_contents($download_url);
            $f = $this->OutPutToPath($initData, $fileName, $borrow_id);
            if ($f === false) {
                Yii::log("signContract transaction_id=[{$transaction_id}] 合同落地失败", "error");
                self::allRollback();
                $return_data['code'] = 3034;
                return $return_data;
            }
            //落地成功后更新到oss_download上
            $oss_download = ConfUtil::get('OSS-ccs-yj.fileName') . DIRECTORY_SEPARATOR . $borrow_id . DIRECTORY_SEPARATOR . $fileName . '.pdf';
            $re = $this->upload($f, $oss_download);
            if ($re === false) {
                Yii::log("signContract transaction_id=[{$transaction_id}] 的合同上传oss失败($oss_download)", "error");
                self::allRollback();
                $return_data['code'] = 3034;
                return $return_data;
            }
            */
            //用户信息
            $user_sql = "SELECT * FROM firstp2p_user WHERE id = '{$contract_info['user_id']}' for update  ";
            $user_info = Yii::app()->db->createCommand($user_sql)->queryRow();
            if(!$user_info || $user_info['is_displace'] == 1){
                Yii::log("signContract transaction_id=[{$transaction_id}] is_displace=1", "error");
                self::allRollback();
                $return_data['code'] = 3033;
                return $return_data;
            }
            $viewpdf_url = urldecode($_POST['viewpdf_url']);
            $now_time = time();
            $update_sql = "update xf_displace_record set  user_sign_time={$now_time},status=1,contract_url='{$viewpdf_url}'  where id = {$contract_info['id']}";
            $edit_fdd = Yii::app()->phdb->createCommand($update_sql)->execute();
            if (!$edit_fdd) {
                Yii::log("signContract transaction_id=[{$transaction_id}] 更新合同状态失败", "error");
                self::allRollback();
                $return_data['code'] = 3025;
                return $return_data;
            }

            $update_sql = "update firstp2p_user set  is_displace=1  where id = {$contract_info['user_id']}";
            $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
            if (!$edit_fdd) {
                Yii::log("signContract transaction_id=[{$transaction_id}] is_displace edit error ", "error");
                self::allRollback();
                $return_data['code'] = 3031;
                return $return_data;
            }



            Yii::log("signContract transaction_id=[{$transaction_id}] sign success ", "error");
            self::allCommit();
            return $return_data;
        }catch (Exception $e) {
            Yii::log("signContract transaction_id=[{$transaction_id}] Exception:".print_r($e->getMessage(), true), "error");
            self::allRollback();
            $return_data['code'] = 3031;
            return $return_data;
        }
    }

    protected function allBeginTransaction()
    {
        Yii::app()->db->beginTransaction();
        Yii::app()->phdb->beginTransaction();
        Yii::app()->offlinedb->beginTransaction();
    }

    protected function allRollback()
    {
        Yii::app()->offlinedb->rollback();
        Yii::app()->phdb->rollback();
        Yii::app()->db->rollback();
    }

    protected function allCommit()
    {
        Yii::app()->offlinedb->commit();
        Yii::app()->phdb->commit();
        Yii::app()->db->commit();
    }

    /**
     * 合同落地
     * @param $data
     * @param $fileName
     * @param $borrow_id
     * @return bool|string
     */
    private function OutPutToPath($data, $fileName, $borrow_id)
    {
        $filePath = self::TempDir . $borrow_id. DIRECTORY_SEPARATOR .$fileName . '.pdf';
        $status = file_put_contents($filePath, $data);
        if (!$status) {
            return false;
        }
        Yii::log($filePath . ' 合同生成并落地成功!', CLogger::LEVEL_INFO, $this->logFile);
        return $filePath;
    }

    /**
     * 文件上传
     * @param $file
     * @param $key
     * @return bool
     */
    private function upload($file, $key)
    {
        Yii::log(basename($file).'文件正在上传!', CLogger::LEVEL_INFO,$this->logFile);
        try {
            ini_set('memory_limit', '2048M');
            $re = Yii::app()->oss->bigFileUpload($file, $key);
            unlink($file);
            return $re;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, $this->logFile);
            return false;
        }
    }


    /**
     * 集约诉讼签约地址
     * @param $user_info
     * @return false
     */
    public function getIntensiveContractUrl($user_info){
        $user_id = $user_info['id'];
        if(empty($user_info) || empty($user_id) || !is_numeric($user_id)){
            Yii::log("getIntensiveContractUrl user_id[$user_id] error", 'error');
            return false;
        }

        $total_capital = 0;
        $transaction_id = FunctionUtil::getRequestNo('JYSS');
         //拼接合同内容
        $seller_user = $user_info;
        $seller_idno = GibberishAESUtil::dec($seller_user['idno'], Yii::app()->c->contract['idno_key']);
        $template_id = Yii::app()->c->contract['intensive_contract_template'];

        //合同生成
        $cvalue = [
            'title' => '授权委托书',
            'params' => [
                'contract_id' => implode('-', ['JYSS', date('Ymd', time()), $user_id]),
                'A_user_name' => $seller_user['real_name'],
                'A_card_id' => $seller_idno,
                'sign_year' => date('Y'),
                'sign_month' => date('m'),
                'sign_day' => date('d'),
                'sign_date' => date('Y年m月d日')
            ],
            'sign' => [
                'A盖签' => $seller_user['yj_fdd_customer_id'],
                'B盖签' => '',
            ],
            'pwd' => '',
        ];

        //合同文档标题
        $doc_title = $cvalue['title'];
        //填充自定义参数
        $params = $cvalue['params'];
        //生成合同
        $result = XfFddService::getInstance()->invokeGenerateContract($template_id, $doc_title, $params, '');
        if (!$result || $result['code'] != 1000) {
            Yii::log("getIntensiveContractUrl   user_id[$user_id] 合同生成失败！\n" . print_r($result, true), 'error');
            return false;
        }
        //法大大合同ID
        $contract_id = $result['contract_id'];

        //加水印
        $text_name = mb_substr("{$seller_user['real_name']}  万峻", 0, 15, 'utf-8');
        $watermark_params = [
            'contract_id' => $contract_id,
            'stamp_type' => 1,
            'text_name' => $text_name,
            'font_size' => 12,
            'rotate' => 45,
            'concentration_factor' => 10,
            'opacity' => 0.2,
        ];
        $result = XfFddService::getInstance()->watermarkPdf($watermark_params);
        if (!$result || $result['code'] != 1) {
            Yii::log("getIntensiveContractUrl   user_id[$user_id]  的{$cvalue['title']}加水印失败！\n" . print_r($result, true), 'error');
            return false;
        }

        //卖方手动签署合同
        $sign_contract_url = XfFddService::getInstance()->invokeExtSign($seller_user['yj_fdd_customer_id'], $contract_id, $doc_title, 'A盖签', $transaction_id,1);
        if (!$sign_contract_url) {
            Yii::log("getIntensiveContractUrl   user_id[$user_id]  集约诉讼授权合同获取签署地址失败！\n" . print_r($result, true), 'error');
            return false;
        }

        $update_sql = "update firstp2p_user set  intensive_contract_id='{$contract_id}',intensive_sign_status=2,intensive_contract_transaction_id ='{$transaction_id}'     where id = {$user_id}";
        $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
        if (!$edit_fdd) {
            Yii::log("user_id:[{$user_info['id']}]  user/XFUser/UserInfo er-02:  sql:$update_sql", 'error');
            return false;
        }
        return  $sign_contract_url;

    }


    public function signJyssContract($transaction_id){
        $return_data = [
            'data' => [],
            'code' => 0
        ];
        if(empty($transaction_id)){
            Yii::log("signJyssContract transaction_id=[{$transaction_id}] empty", "error");
            $return_data['code'] = 3032;
            return $return_data;
        }
        try {
            $contract_sql = "SELECT * FROM firstp2p_user WHERE intensive_contract_transaction_id = '$transaction_id'   ";
            $contract_info = Yii::app()->db->createCommand($contract_sql)->queryRow();
            if(!$contract_info){
                Yii::log("signJyssContract transaction_id=[{$transaction_id}] firstp2p_user empty", "error");
                $return_data['code'] = 3024;
                return $return_data;
            }
            if($contract_info['intensive_sign_status'] == 1){
                Yii::log("signJyssContract transaction_id=[{$transaction_id}] 已签约", "error");
                return $return_data;
            }
            if($contract_info['intensive_sign_status'] != 2){
                Yii::log("signJyssContract transaction_id=[{$transaction_id}] 签约流程异常", "error");
                $return_data['code'] = 3038;
                return $return_data;
            }

            $viewpdf_url = urldecode($_GET['viewpdf_url']);
            $now_time = time();
            $update_sql = "update firstp2p_user set  intensive_sign_time={$now_time},intensive_sign_status=1,intensive_contract_url='{$viewpdf_url}'  where id = {$contract_info['id']}";
            $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
            if (!$edit_fdd) {
                Yii::log("signContract transaction_id=[{$transaction_id}] 更新合同状态失败", "error");
                $return_data['code'] = 3025;
                return $return_data;
            }
        }catch (Exception $e) {
            Yii::log("signContract transaction_id=[{$transaction_id}] Exception:".print_r($e->getMessage(), true), "error");
            $return_data['code'] = 3031;
            return $return_data;
        }
    }

}
