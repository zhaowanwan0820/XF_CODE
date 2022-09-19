<?php

/**
 * 专区求购
 * Class PurchaseController
 */
class PurchaseController extends XianFengExtendsController
{

    /**
     * 专区收购详情接口
     */
    public function actionPurchaseInfo()
    {
        //提测试删除
        //$this->user_id=12135279;

        $result_data = [];
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson($result_data, 1016, $XF_error_code_info[1016]);
        }

        //专区求购ID
        $id = intval($_POST['id']);
        if (empty($id) || !is_numeric($id)) {
            $this->echoJson($result_data, 3000, $XF_error_code_info[3000]);
        }

        //用户未过期求购详情
        $purchase_sql = "SELECT user_id,wait_capital,discount,purchase_amount,real_name,bank_name,bank_card,end_time,user_sign_time,assignee_sign_time,credentials_url,contract_url,status FROM xf_exclusive_purchase WHERE id = {$id} and status in (0,1,2,3,4)";
        $result_data = $purchase_info = Yii::app()->phdb->createCommand($purchase_sql)->queryRow();
        if (!$purchase_info || $purchase_info['user_id'] != $user_id) {
            $this->echoJson($result_data, 3001, $XF_error_code_info[3001]);
        }

        //仅可查看自己的求购详情
        if ($purchase_info['user_id'] != $user_id) {
            $this->echoJson($result_data, 3002, $XF_error_code_info[3002]);
        }

        //签约剩余时间
        if ($purchase_info['status'] == 0) {
            $result_data['sign_remaining_time'] = gmstrftime('%H时%M分%S秒', time()-$purchase_info['end_time']);
        }

        //签约剩余时间
        if (in_array($purchase_info['status'], [1,2,3,4])) {
            $result_data['user_sign_time'] = date('Y-m-d H:i:s', $purchase_info['user_sign_time']);
        }

        //签约完成时间
        if ($purchase_info['status'] == 4) {
            $result_data['assignee_sign_time'] = date('Y-m-d H:i:s', $purchase_info['assignee_sign_time']);
        }

        $result_data['purchase_status'] = Yii::app()->c->xf_config['purchase_status'][$purchase_info['status']];


        //用户法大大实名认证状态
        $result_data['fdd_real_status'] = 0;
        $user_sql = "SELECT id,fdd_real_status,yj_fdd_customer_id FROM firstp2p_user WHERE id = {$user_id}  ";
        $user_info = Yii::app()->db->createCommand($user_sql)->queryRow();
        if ($user_info['fdd_real_status'] == 1) {
            $result_data['fdd_real_status'] = 1;
        }

        $this->echoJson($result_data, 0, $XF_error_code_info[0]);
    }

    /**
     * 获取用户被收购债权记录
     */
    public function actionPurchaseDebtList()
    {
        //提测试删除
        //$this->user_id=12135279;

        //提交方式校验
        if (empty($_POST)) {
            $this->echoJson([], 2005, Yii::app()->c->XF_error_code_info[2005]);
        }

        //校验用户登录状态
        if (!$this->user_id) {
            $this->echoJson([], 1016, Yii::app()->c->XF_error_code_info[1016]);
        }

        //专区求购ID
        $id = intval($_POST['id']);
        if (empty($id) || !is_numeric($id)) {
            $this->echoJson([], 3000, Yii::app()->c->XF_error_code_info[3000]);
        }

        //limit最大值50
        if (isset($_POST['limit']) && $_POST['limit']>50) {
            $this->echoJson([], 1032, Yii::app()->c->XF_error_code_info[1032]);
        }

        //默认值赋值
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;

        //普惠总条数
        $count_load_sql = "select count(1) from firstp2p_deal_load where exclusive_purchase_id ={$id} ";
        $count_load = Yii::app()->phdb->createCommand($count_load_sql)->queryScalar();

        //智多新总条数
        $count_load_sql = "select count(1) from offline_deal_load where exclusive_purchase_id ={$id} ";
        $offline_count_load = Yii::app()->offlinedb->createCommand($count_load_sql)->queryScalar();
        if ($count_load == 0 && $offline_count_load == 0) {
            $this->echoJson([], 0);
        }
        $offline_load_list = [];
        if ($offline_count_load >0) {
            $offline_list_load_sql = "select dl.wait_capital,FROM_UNIXTIME(dl.create_time) as create_time,dd.name as deal_name 
                              from offline_deal_load dl 
                              left join offline_deal dd on dd.id=dl.deal_id 
                              where exclusive_purchase_id ={$id} order by dl.id desc ";
            $offline_load_list = Yii::app()->offlinedb->createCommand($offline_list_load_sql)->queryAll();
        }

        //普惠收购记录sql
        $offset = ($page - 1) * $limit;
        $list_load_sql = "select dl.wait_capital,FROM_UNIXTIME(dl.create_time) as create_time,dd.name as deal_name 
                              from firstp2p_deal_load dl 
                              left join firstp2p_deal dd on dd.id=dl.deal_id 
                              where exclusive_purchase_id ={$id} order by dl.id desc limit $offset,$limit ";
        $load_list = Yii::app()->phdb->createCommand($list_load_sql)->queryAll();

        header("Content-type:application/json; charset=utf-8");
        $result_data['data'] = array_merge($offline_load_list, $load_list);
        $result_data['count'] = $count_load+$offline_count_load;
        $result_data['page_count'] = ceil($count_load / $limit);
        $result_data['code'] = 0;
        $result_data['info'] = '';
        echo exit(json_encode($result_data));
    }

    /**
     * 获取用户法大大实名认证地址
     */
    public function actionUserFddInfo()
    {

        //提测试删除
        //$this->user_id=12135279;

        $result_data = [
            'fdd_real_url' => '',
            'fdd_real_status' => 0,
            'sign_contract_url' => '',
            'sign_contract_status' => 0,
        ];
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson($result_data, 1016, $XF_error_code_info[1016]);
        }

        //专区求购ID
        $id = intval($_POST['id']);

        if(empty($id)){
            $purchase_sql = "SELECT id FROM xf_exclusive_purchase WHERE user_id = {$user_id} and status not in (5)  order by id desc ";
            $purchase_info = Yii::app()->phdb->createCommand($purchase_sql)->queryRow();
            if($purchase_info){
                $id = $purchase_info['id'];
            }
        }

        if (empty($id) || !is_numeric($id)) {
            $this->echoJson([], 3000, Yii::app()->c->XF_error_code_info[3000]);
        }
        

        //收购信息查询
        $purchase_sql = "SELECT * FROM xf_exclusive_purchase WHERE id = {$id}  ";
        $purchase_info = Yii::app()->phdb->createCommand($purchase_sql)->queryRow();
        if (!$purchase_info) {
            $this->echoJson([], 3001, Yii::app()->c->XF_error_code_info[3001]);
        }
        $idno = $purchase_info['idno'];
        $phone = $purchase_info['mobile_phone'];
        $bank_card_no = $purchase_info['bank_card'];
        $now_time = time();
        //用户法大大实名认证状态
        $user_sql = "SELECT id,real_name,fdd_real_status,idno,mobile,yj_fdd_customer_id FROM firstp2p_user WHERE id = {$user_id}  ";
        $user_info = Yii::app()->db->createCommand($user_sql)->queryRow();
        $result_data['fdd_real_status'] = $user_info['fdd_real_status'];
        $result_data['sign_contract_status'] = $purchase_info['status'];
        if (in_array($user_info['fdd_real_status'], [0, 2])) {
            $customer_id = 0;
            $id_type = DebtService::getInstance()->convertCardType($user_info['id_type']);
            $smrz_user_id = intval('99999999'.$user_info['id']);
            $result = XfFddService::getInstance()->invokeSyncVerifyUrl($user_info['real_name'], $smrz_user_id, $idno, $id_type, $phone, $bank_card_no, $customer_id);
            if (empty($result) || $result['code'] != 1 || $result['fdd_real_transaction_no'] == '' || $result['fdd_real_url'] == '') {
                $this->echoJson($result_data, 3004, Yii::app()->c->XF_error_code_info[3004]);
            }
            //记录法大大客户编号及实名认证交易号
            $customer_id = $result['customer_id'];
            $edit_f =  ",yj_fdd_customer_id = '$customer_id' "  ;
            $fdd_real_transaction_no = $result['fdd_real_transaction_no'];
            $update_sql = "update firstp2p_user set  fdd_real_status=2,fdd_real_transaction_no ='{$fdd_real_transaction_no}'  {$edit_f}  where id = {$user_id}";
            $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
            if (!$edit_fdd) {
                $this->echoJson($result_data, 3005, Yii::app()->c->XF_error_code_info[3005]);
            }

            //返回法大大实名认证地址
            $result_data['fdd_real_url'] = $result['fdd_real_url'];
            $this->echoJson($result_data, 0, Yii::app()->c->XF_error_code_info[0]);
        }

        //签约状态
        if ($purchase_info['status'] != 0) {
            if($result_data['sign_contract_status'] == 1){
                $this->echoJson($result_data, 0);
            }else{
                $this->echoJson($result_data, 3006, Yii::app()->c->XF_error_code_info[3006]);
            }
        }

        //签约时效时间不可小于当前时间
        if ($purchase_info['end_time'] < $now_time) {
            $this->echoJson($result_data, 3007, Yii::app()->c->XF_error_code_info[3007]);
        }

        //智多新收购债权记录
        $offline_list_load_sql = "select '4' as platform_no ,dd.deal_type ,dl.create_time,dl.user_id,dl.deal_id,dl.id,dl.status,dl.xf_status,dl.black_status,dl.debt_type,dl.wait_capital ,dd.name as deal_name 
                              from offline_deal_load dl 
                              left join offline_deal dd on dd.id=dl.deal_id 
                              where dl.exclusive_purchase_id ={$id} order by dl.id desc ";
        $offline_load_list = Yii::app()->offlinedb->createCommand($offline_list_load_sql)->queryAll();

        //普惠收购记录
        $list_load_sql = "select '2' as platform_no ,dd.deal_type,dl.create_time,dl.user_id,dl.deal_id,dl.id,dl.status,dl.xf_status,dl.black_status,dl.debt_type,dl.wait_capital ,dd.name as deal_name 
                              from firstp2p_deal_load dl 
                              left join firstp2p_deal dd on dd.id=dl.deal_id 
                              where dl.exclusive_purchase_id ={$id} order by dl.id desc   ";
        $load_list = Yii::app()->phdb->createCommand($list_load_sql)->queryAll();

        $all_deal_load = array_merge($offline_load_list, $load_list);
        if (empty($all_deal_load)) {
            $this->echoJson($result_data, 3008, Yii::app()->c->XF_error_code_info[3008]);
        }
        //合同模板选定
        $c_all_deal_load = count($all_deal_load);
        $template_id = $this->getPurchaseTemplate($c_all_deal_load);
        if (!$template_id) {
            $this->echoJson($result_data, 3011, Yii::app()->c->XF_error_code_info[3011]);
        }
        //受让人信息查询
        $purchase_assignee = User::model()->findByPk($purchase_info['purchase_user_id'])->attributes;
        if (!$purchase_assignee) {
            $this->echoJson([], 3013, Yii::app()->c->XF_error_code_info[3013]);
        }
        //拼接合同内容
        $deal_load_content = $deal_load_content_01 = $deal_load_content_02 = $deal_load_content_03 = $deal_load_content_04 = $deal_load_content_05 = '';
        $deal_load_content_06 = $deal_load_content_07 = $deal_load_content_08 = $deal_load_content_09 = $deal_load_content_10 = $deal_load_content_11 = '';
        $deal_load_content_12 = $deal_load_content_13 = $deal_load_content_14 = $deal_load_content_15= '';
        $assignee_idno = GibberishAESUtil::dec($purchase_assignee['idno'], Yii::app()->c->contract['idno_key']);
        $total_capital = 0;
        foreach ($all_deal_load as $key=>$value) {
            //校验基础数据
            if ($value['status'] != 1 || $value['xf_status'] != 0 || $value['black_status'] != 1 || $value['wait_capital'] <= 0) {
                Yii::log("actionUserFddInfo 3009:  deal_load {$value['id']} error", 'error');
                $this->echoJson($result_data, 3009, Yii::app()->c->XF_error_code_info[3009]);
            }
            $total_capital = bcadd($total_capital, $value['wait_capital'], 2);
            //债转合同编号根据规则拼接
            $seller_contract_number = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['deal_id'], $value['id']]);
            //普惠获取合同编号
            if ($value['debt_type'] == 1 && $value['platform_no'] == 2) {
                //合同信息
                $table_name = $value['deal_id'] % 128;
                $contract_sql = "select number from contract_$table_name 
                             where deal_load_id = {$value['id']} 
                             and user_id={$value['user_id']} 
                             and deal_id={$value['deal_id']}
                             and type in (0,1) and status=1 and source_type=0  ";
                $contract_info = Yii::app()->cdb->createCommand($contract_sql)->queryRow();
                if (!$contract_info) {
                    Yii::log("actionUserFddInfo 3012:  firstp2p_deal_load {$value['id']} contract_$table_name  error", 'error');
                    $this->echoJson($result_data, 3012, Yii::app()->c->XF_error_code_info[3012]);
                }
                $seller_contract_number = $contract_info['number'];
            }
            //智多新直投合同编号从wx原数据库取
            if ($value['debt_type'] == 1 && $value['platform_no'] == 4) {
                $contract_info = OfflineContractTask::model()->find("tender_id={$value['id']} and contract_type=1 and type=1 and status=2");
                if (!$contract_info) {
                    Yii::log("actionUserFddInfo 3012:  offline_deal_load {$value['id']} offline_contract_task  error", 'error');
                    $this->echoJson($result_data, 3012, Yii::app()->c->XF_error_code_info[3012]);
                }
                $seller_contract_number = $contract_info->contract_no;
            }

            $n = $key+1;
            if ($n<=15) {
                $deal_load_content .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>15 && $n<= 47) {
                $deal_load_content_01 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>47 && $n<= 79) {
                $deal_load_content_02 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>79 && $n<= 111) {
                $deal_load_content_03 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>111 && $n<= 143) {
                $deal_load_content_04 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>143 && $n<= 170) {
                $deal_load_content_05 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>170 && $n<= 202) {
                $deal_load_content_06 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>202 && $n<= 234) {
                $deal_load_content_07 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>234 && $n<= 266) {
                $deal_load_content_08 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>266 && $n<= 298) {
                $deal_load_content_09 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>298 && $n<= 330) {
                $deal_load_content_10 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>330 && $n<= 362) {
                $deal_load_content_11 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>362 && $n<= 394) {
                $deal_load_content_12 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>394 && $n<= 426) {
                $deal_load_content_13 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>426 && $n<= 458) {
                $deal_load_content_14 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
            if ($n>458 && $n<= 490) {
                $deal_load_content_15 .= "序号{$n}：合同编号：{$seller_contract_number}；项目名称：{$value['deal_name']}；转让债权本金：{$value['wait_capital']}元整。\r\n";
            }
        }

        //收购合同模板，变量拼接
        $action_money = $purchase_info['purchase_amount'];
        $cvalue = [
            'title' => '债权转让协议',
            'params' => [
                'contract_id' => implode('-', ['WXPH', date('Ymd', time()), $purchase_info['purchase_user_id'], $purchase_info['user_id'], $id]),
                'A_user_name' => $purchase_info['real_name'],
                'A_card_id' => $purchase_info['idno'],
                'B_user_name' => $purchase_assignee['real_name'],
                'B_card_id' => $assignee_idno,
                'deal_load_content' => $deal_load_content,//债权信息
                'total_capital' =>  $total_capital,
                'action_money' => $action_money,
                'chinese_money' => ItzUtil::toChineseNumber($action_money),
                'payee_name' => $purchase_info['real_name'],
                'payee_bankzone' => $purchase_info['bank_name'],
                'payee_bankcard' => $purchase_info['bank_card'],
                'sign_year' => date('Y', time()),
                'sign_month' => date('m', time()),
                'sign_day' => date('d', time()),
                'sign_year_y' => date('Y', time()),
                'sign_month_y' => date('m', time()),
                'sign_day_y' => date('d', time()),
            ],
            'sign' => [
                'A盖签' => $user_info['yj_fdd_customer_id'],
                'B盖签' => $purchase_assignee['yj_fdd_customer_id'],
            ],
            'pwd' => '',
        ];

        if ($c_all_deal_load>15) {
            $cvalue['params']['deal_load_content_one'] = $deal_load_content_01;
        }
        if ($c_all_deal_load>47) {
            $cvalue['params']['deal_load_content_two'] = $deal_load_content_02;
            $cvalue['params']['deal_load_content_three'] = $deal_load_content_03;
            $cvalue['params']['deal_load_content_four'] = $deal_load_content_04;
            $cvalue['params']['deal_load_content_five'] = $deal_load_content_05;
            $cvalue['params']['deal_load_content_8'] = $deal_load_content_06;
            $cvalue['params']['deal_load_content_9'] = $deal_load_content_07;
            $cvalue['params']['deal_load_content_10'] = $deal_load_content_08;
            $cvalue['params']['deal_load_content_11'] = $deal_load_content_09;
            $cvalue['params']['deal_load_content_12'] = $deal_load_content_10;
            $cvalue['params']['deal_load_content_13'] = $deal_load_content_11;
            $cvalue['params']['deal_load_content_14'] = $deal_load_content_12;
            $cvalue['params']['deal_load_content_15'] = $deal_load_content_13;
            $cvalue['params']['deal_load_content_16'] = $deal_load_content_14;
            $cvalue['params']['deal_load_content_17'] = $deal_load_content_15;
        }

        //合同文档标题
        $doc_title = $cvalue['title'];
        //填充自定义参数
        $params = $cvalue['params'];
        //生成合同
        $result = XfFddService::getInstance()->invokeGenerateContract($template_id, $doc_title, $params, $cvalue['dynamic_tables']?:'');
        if (!$result || $result['code'] != 1000) {
            Yii::log("purchase_id= {$id} 的{$cvalue['title']}合同生成失败！\n" . print_r($result, true), 'error');
            $this->echoJson($result_data, 3014, Yii::app()->c->XF_error_code_info[3014]);
        }
        //法大大合同ID
        $contract_id = $result['contract_id'];

        //加水印
        $text_name = mb_substr("{$purchase_info['real_name']}  {$purchase_assignee['real_name']}", 0, 15, 'utf-8');
        //$transaction_id = str_replace('.', '', uniqid('', true));
        $transaction_id = FunctionUtil::getRequestNo('ZSSG');
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
            Yii::log("purchase_id= {$id} 的{$cvalue['title']}加水印失败！\n" . print_r($result, true), 'error');
            $this->echoJson($result_data, 3015, Yii::app()->c->XF_error_code_info[3015]);
        }

        //记录合同信息
        $update_sql = "update xf_exclusive_purchase set  contract_id='$contract_id',contract_transaction_id='$transaction_id'   where id = {$id}";
        $edit_fdd = Yii::app()->phdb->createCommand($update_sql)->execute();
        if (!$edit_fdd) {
            Yii::log("purchase_id= {$id} 的{$cvalue['title']}更新失败！\n" . print_r($result, true), 'error');
            $this->echoJson($result_data, 3017, Yii::app()->c->XF_error_code_info[3017]);
        }

        //卖方手动签署合同
        $sign_contract_url = XfFddService::getInstance()->invokeExtSign($user_info['yj_fdd_customer_id'], $contract_id, $doc_title, 'A盖签', $transaction_id);
        if (!$sign_contract_url) {
            Yii::log("purchase_id= {$id} 的{$cvalue['title']}收购合同获取签署地址失败！\n" . print_r($result, true), 'error');
            $this->echoJson($result_data, 3016, Yii::app()->c->XF_error_code_info[3016]);
        }
        //合同签署地址
        $result_data['sign_contract_url'] = $sign_contract_url;
        $this->echoJson($result_data, 0);
    }

    private function getPurchaseTemplate($num)
    {
        $purchase_template = Yii::app()->c->contract['purchase_template'];
        if (!$purchase_template) {
            return false;
        }
        $num_list = array_keys($purchase_template);
        foreach ($num_list as $v) {
            $v_string = explode('_', $v);
            if ($num >= $v_string[0] && $num <= $v_string[1]) {
                return $purchase_template[$v];
            }
        }
        return false;
    }

    /**
     * 获取用户求购列表
     */
    public function actionGetPurchaseList()
    {
        //提测试删除
        //$this->user_id=12135279;

        $result_data = [];
        $XF_error_code_info = Yii::app()->c->XF_error_code_info;
        $user_id = $this->user_id;
        if (empty($user_id) || !is_numeric($user_id)) {
            $this->echoJson($result_data, 1016, $XF_error_code_info[1016]);
        }

        //用户未过期求购详情
        $purchase_sql = "SELECT id,user_id,wait_capital,discount,purchase_amount,status FROM xf_exclusive_purchase WHERE user_id = {$user_id} and status in (0,1,2,3,4)";
        $result_data = $purchase_info = Yii::app()->phdb->createCommand($purchase_sql)->queryAll();
        if (!$purchase_info) {
            $this->echoJson($result_data, 3001, $XF_error_code_info[3001]);
        }
        $result_data[0]['purchase_status'] = Yii::app()->c->xf_config['purchase_status'][$purchase_info[0]['status']];
        $this->echoJson($result_data, 0, $XF_error_code_info[0]);
    }
}
