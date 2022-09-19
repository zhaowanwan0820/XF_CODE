<?php
class Firstp2pUserCommand extends CConsoleCommand
{
    // php54 yiic.php Firstp2pUser run --type=1 --db=1 --user_id=12124022

    /**
     * 整合尊享&普惠的账户余额、账户冻结金额、历史充值金额、历史提现金额
     * @param type  1-普惠账户余额&账户冻结金额 2-尊享历史充值金额、历史提现金额 3-普惠历史充值金额、历史提现金额 4-修正普惠账户余额、账户冻结金额
     * @param db    1-更新尊享数据库的firstp2p_user 2-更新普惠数据库的firstp2p_user
     */
    public function actionRun($type = 0 , $db = 1 , $user_id = 0){
        echo "Firstp2pUser start, type:{$type} \n";
        if (!in_array($type , array(1 , 2 , 3 , 4))) {
            echo "Firstp2pUser end, type:{$type} not in (1 , 2, 3) \n";
            return false;
        }
        if (!in_array($db , array(1 , 2))) {
            echo "Firstp2pUser end, db:{$db} not in (1 , 2) \n";
            return false;
        }
        if ($user_id) {
            $where = " AND user_id = {$user_id} ";
        } else {
            $where = "";
        }
        if ($db == 1) {
            $model = Yii::app()->fdb;
        } else if ($db == 2) {
            $model = Yii::app()->phdb;
        }
        if ($type == 1) {
            // 普惠账户余额&账户冻结金额
            // $where      = " AND user_id > 2344267 "; // 临时条件
            $sql        = "SELECT count(*) FROM firstp2p_account WHERE 1 = 1 {$where} ";
            $count      = Yii::app()->phdb->createCommand($sql)->queryScalar();
            $page_count = ceil($count / 1000);
            for ($i = 0; $i < $page_count; $i++) {
                $pass    = $i * 1000;
                $sql     = "SELECT user_id , money , lock_money FROM firstp2p_account WHERE 1 = 1 {$where} LIMIT {$pass} , 1000 ";
                $account = Yii::app()->phdb->createCommand($sql)->queryAll();
                if (!$account) {
                    echo "Firstp2pUser end, type:{$type}, get firstp2p_account error \n";
                    return false;
                }
                foreach ($account as $key => $value) {
                    if ($value['money'] != 0 || $value['lock_money'] != 0) {
                        $money      = $value['money'] / 100;
                        $lock_money = $value['lock_money'] / 100;
                        $sql        = "SELECT ph_money , ph_lock_money FROM firstp2p_user WHERE id = {$value['user_id']}";
                        $user_info  = $model->createCommand($sql)->queryRow();
                        if (!$user_info) {
                            echo "user_id:{$value['user_id']}, user_info select error, continue \n";
                            continue;
                        }
                        if ($money == $user_info['ph_money'] && $lock_money == $user_info['ph_lock_money']) {
                            echo "user_id:{$value['user_id']}, ph_money & ph_lock_money is updated, continue \n";
                            continue;
                        }
                        $sql    = "UPDATE firstp2p_user SET ph_money = {$money} , ph_lock_money = {$lock_money} WHERE id = {$value['user_id']}";
                        $result_a = Yii::app()->fdb->createCommand($sql)->execute();
                        $result_b = Yii::app()->phdb->createCommand($sql)->execute();
                        if ($result_a && $result_b) {
                            echo "user_id:{$value['user_id']}, update OK \n";
                        } else {
                            echo "user_id:{$value['user_id']}, update error \n";
                            break;
                        }
                    } else {
                        echo "user_id:{$value['user_id']}, ph_money & ph_lock_money is 0, continue \n";
                    }
                }
            }
        } else if ($type == 2) {

            // 尊享历史充值金额、历史提现金额
            $sql = "SELECT id FROM firstp2p_user ";
            $user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
            if ($user_id) {
                foreach ($user_id as $key => $value) {
                    $sql   = "SELECT SUM(money) AS money FROM firstp2p_payment_notice WHERE is_paid = 1 AND user_id = {$value} ";
                    $money = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if (!$money) {
                        $money = 0;
                    }
                    $zx_recharge = $money;

                    $sql   = "SELECT SUM(money) AS money FROM firstp2p_user_carry WHERE withdraw_status = 1 AND status = 3 AND user_id = {$value} ";
                    $money = Yii::app()->fdb2->createCommand($sql)->queryScalar();
                    if (!$money) {
                        $money = 0;
                    }
                    $zx_withdraw = $money;

                    $sql = "UPDATE firstp2p_user SET zx_recharge = {$zx_recharge} , zx_withdraw = {$zx_withdraw} WHERE id = {$value}";
                    $res = Yii::app()->fdb->createCommand($sql)->execute();
                    $res = Yii::app()->phdb->createCommand($sql)->execute();
                    echo "user_id:{$value}, update OK \n";
                }
            }
            
        } else if ($type == 3) {

            // 普惠历史充值金额、历史提现金额
            $sql = "SELECT id FROM firstp2p_user WHERE id > 11247273 ";
            $user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
            if ($user_id) {
                foreach ($user_id as $key => $value) {
                    $sql    = "SELECT SUM(amount) AS amount FROM firstp2p_supervision_charge WHERE pay_status = 1 AND user_id = {$value} ";
                    $amount = Yii::app()->phdb->createCommand($sql)->queryScalar();
                    if (!$amount) {
                        $amount = 0;
                    }
                    $ph_recharge = $amount / 100;

                    $sql      = "SELECT SUM(amount) AS amount FROM firstp2p_supervision_withdraw WHERE withdraw_status = 1 AND user_id = {$value} ";
                    $amount   = Yii::app()->phdb->createCommand($sql)->queryScalar();
                    $amount_m = Yii::app()->phdb2->createCommand($sql)->queryScalar();
                    if (!$amount) {
                        $amount = 0;
                    }
                    if (!$amount_m) {
                        $amount_m = 0;
                    }
                    $ph_withdraw = ($amount + $amount_m) / 100;

                    $sql = "UPDATE firstp2p_user SET ph_recharge = {$ph_recharge} , ph_withdraw = {$ph_withdraw} WHERE id = {$value}";
                    $res = Yii::app()->fdb->createCommand($sql)->execute();
                    $res = Yii::app()->phdb->createCommand($sql)->execute();
                    echo "user_id:{$value}, update OK \n";
                }
            }

        } else if ($type == 4) {
            // 修正普惠账户余额、账户冻结金额
            $sql = "SELECT DISTINCT u.id FROM firstp2p_user u LEFT JOIN firstp2p_account a ON u.id = a.user_id WHERE a.money/100 != u.ph_money OR a.lock_money/100 != u.ph_lock_money";
            $user_id_arr = Yii::app()->phdb->createCommand($sql)->queryColumn();
            if ($user_id_arr) {
                foreach ($user_id_arr as $key => $value) {
                    $sql        = "SELECT money , lock_money FROM firstp2p_account WHERE user_id = {$value}";
                    $account    = Yii::app()->phdb->createCommand($sql)->queryRow();
                    $money      = $account['money'] / 100;
                    $lock_money = $account['lock_money'] / 100;
                    $sql        = "UPDATE firstp2p_user SET ph_money = {$money} , ph_lock_money = {$lock_money} WHERE id = {$value}";
                    $result_a   = Yii::app()->fdb->createCommand($sql)->execute();
                    $result_b   = Yii::app()->phdb->createCommand($sql)->execute();
                    if ($result_a && $result_b) {
                        echo "user_id:{$value['user_id']}, update OK \n";
                    } else {
                        echo "user_id:{$value['user_id']}, update error \n";
                        break;
                    }
                }
            }
        }
        echo "Firstp2pUser end \n";
    }

    /**
     * 在途出借人明细 导出 全量
     * time php54 yiic.php Firstp2pUser DealLoadBYUser2ExcelAll
     */
    public function actionDealLoadBYUser2ExcelAll()
    {
        set_time_limit(0);
        $time = date('Y-m-d H:i:s' , time());
        echo "DealLoadBYUser2ExcelAll start, time:{$time} \n";
        // 排除受让人ID（除了3120608张翠英）
        $sql = "SELECT user_id FROM ag_wx_assignee_info WHERE transferred_amount > 0 AND user_id != 3120608 ";
        $assignee = Yii::app()->fdb->createCommand($sql)->queryColumn();
        $user_id_str = implode(',' , $assignee);
        // 条件筛选
        $where      = " WHERE user.is_online = 1 AND user.id NOT IN ({$user_id_str}) ";
        $sql        = "SELECT count(*) FROM firstp2p_user AS user {$where}";
        $count      = Yii::app()->fdb->createCommand($sql)->queryScalar();
        echo "count:{$count} \n";
        $page_count = ceil($count / 500);
        echo "page_count:{$page_count} \n";
        for ($i = 0; $i < $page_count; $i++) {
            echo "page:{$i} start \n";
            $pass = $i * 500;
            $sql = "SELECT user.id , user.real_name , user.mobile , user.idno , user_group.name AS group_name , user.sex , user.byear , bind.refer_user_id , refer.real_name AS refer_name , bind.short_alias , refer_group.name AS refer_group_name , user.money , user.lock_money , user.ph_money , user.ph_lock_money , user.zx_recharge , user.zx_withdraw , user.ph_recharge , user.ph_withdraw 
                    FROM firstp2p_user AS user 
                    LEFT JOIN firstp2p_user_group AS user_group ON user.group_id = user_group.id 
                    LEFT JOIN firstp2p_coupon_bind AS bind ON user.id = bind.user_id 
                    LEFT JOIN firstp2p_user AS refer ON refer.id = bind.refer_user_id 
                    LEFT JOIN firstp2p_user_group AS refer_group ON refer.group_id = refer_group.id 
                    {$where} GROUP BY user.id LIMIT {$pass} , 500 ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            echo "user list is OK \n";
            // 用户的账户信息
            $user_ids = implode(",", ArrayUtil::array_column($list , "id"));
            // 尊享 在途本金 & 在途利息 & 历史累计收益额
            echo "user ZX money start \n";
            $zx_wait_capital_res = Yii::app()->fdb->createCommand("SELECT user_id , SUM(wait_capital) AS wait_capital , SUM(wait_interest) AS wait_interest , SUM(yes_interest) AS yes_interest FROM firstp2p_deal_load WHERE user_id IN ({$user_ids}) AND status = 1 GROUP BY user_id")->queryAll();
            if (!empty($zx_wait_capital_res)) {
                foreach ($zx_wait_capital_res as $key => $value) {
                    $zx_wait_capital[$value['user_id']] = $value;
                }
            }
            echo "user ZX money is OK \n";
            // 普惠 在途本金 & 在途利息 & 历史累计收益额
            echo "user PH money start \n";
            $ph_wait_capital_res = Yii::app()->phdb->createCommand("SELECT user_id , SUM(wait_capital) AS wait_capital , SUM(wait_interest) AS wait_interest , SUM(yes_interest) AS yes_interest FROM firstp2p_deal_load WHERE user_id IN ({$user_ids}) AND status = 1 GROUP BY user_id")->queryAll();
            if (!empty($ph_wait_capital_res)) {
                foreach ($ph_wait_capital_res as $key => $value) {
                    $ph_wait_capital[$value['user_id']] = $value;
                }
            }
            echo "user PH money is OK \n";
            $sex[0] = '女';
            $sex[1] = '男';
            $mobile_array = array();
            foreach ($list as $key => $value) {
                $list[$key]['mobile']            = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                $list[$key]['mobile_a']          = substr($list[$key]['mobile'] , 0 , 7);
                // $list[$key]['mobile_b']          = $this->strEncrypt($value['mobile'] , 3 , 4);
                $list[$key]['idno']              = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                // $list[$key]['idno']              = $this->strEncrypt($value['idno'] , 6 , 8);
                if (in_array($value['sex'] , array(0 , 1))) {
                    $list[$key]['sex'] = $sex[$value['sex']];
                } else {
                    $list[$key]['sex'] = '';
                }
                $list[$key]['byear']             = date('Y' , time()) - $value['byear'];
                if ($list[$key]['byear'] > 150) {
                    $list[$key]['byear'] = '——';
                }
                if (!empty($zx_wait_capital[$value['id']])) {
                    $list[$key]['zx_wait_capital']  = $zx_wait_capital[$value['id']]['wait_capital'];
                    $list[$key]['zx_wait_interest'] = $zx_wait_capital[$value['id']]['wait_interest'];
                    $list[$key]['zx_revenue']       = $zx_wait_capital[$value['id']]['yes_interest'];
                } else {
                    $list[$key]['zx_wait_capital']  = 0;
                    $list[$key]['zx_wait_interest'] = 0;
                    $list[$key]['zx_revenue']       = 0;
                }
                if (!empty($ph_wait_capital[$value['id']])) {
                    $list[$key]['ph_wait_capital']  = $ph_wait_capital[$value['id']]['wait_capital'];
                    $list[$key]['ph_wait_interest'] = $ph_wait_capital[$value['id']]['wait_interest'];
                    $list[$key]['ph_revenue']       = $ph_wait_capital[$value['id']]['yes_interest'];
                } else {
                    $list[$key]['ph_wait_capital']  = 0;
                    $list[$key]['ph_wait_interest'] = 0;
                    $list[$key]['ph_revenue']       = 0;
                }
                if ($list[$key]['zx_wait_capital'] == 0 && $list[$key]['zx_wait_interest'] == 0 && $list[$key]['ph_wait_capital'] == 0 && $list[$key]['ph_wait_interest'] == 0) {
                    unset($list[$key]);
                } else {
                    $list[$key]['total_capital']  = $list[$key]['zx_wait_capital'] + $list[$key]['ph_wait_capital'];
                    $list[$key]['total_interest'] = $list[$key]['zx_wait_interest'] + $list[$key]['ph_wait_interest'];
                    if (!empty($list[$key]['mobile_a']) && is_numeric($list[$key]['mobile_a'])) {
                        $mobile_array[] = $list[$key]['mobile_a'];
                    }
                }
            }
            echo "user list data is OK \n";
            if ($mobile_array) {
                $mobile_string = implode(',' , $mobile_array);
                $sql = "SELECT mobile , provice , city FROM firstp2p_mobile_area WHERE mobile IN ({$mobile_string}) ";
                $mobile_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                foreach ($mobile_res as $key => $value) {
                    $mobile_data[$value['mobile']] = $value['provice'] . $value['city'];
                }
            } else {
                $mobile_data = array();
            }
            echo "mobile_area is OK \n";
            foreach ($list as $key => $value) {
                if (!empty($mobile_data[$value['mobile_a']])) {
                    $value['mobile_area'] = $mobile_data[$value['mobile_a']];
                } else {
                    $value['mobile_area'] = '';
                }                

                $listInfo[] = $value;
                echo "user_id:{$value['id']} is OK \n";
            }
        }
        echo "data to csv start \n";
        $name = '在途出借人明细(尊享+普惠) 全量 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        $data  = "用户ID,用户姓名,用户所属组别名称,性别,年龄,手机号所在地,服务人ID,服务人姓名,服务人邀请码,服务人所属组别名称,尊享账户余额,普惠账户余额,尊享账户冻结金额,普惠账户冻结金额,尊享历史充值金额,普惠历史充值金额,尊享历史提现金额,普惠历史提现金额,尊享在途本金,普惠在途本金,尊享在途利息,普惠在途利息,总在途本金,总在途利息,尊享历史累计收益额,普惠历史累计收益额\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['real_name']},{$value['group_name']},{$value['sex']},{$value['byear']},{$value['mobile_area']},{$value['refer_user_id']},{$value['refer_name']},{$value['short_alias']},{$value['refer_group_name']},{$value['money']},{$value['ph_money']},{$value['lock_money']},{$value['ph_lock_money']},{$value['zx_recharge']},{$value['ph_recharge']},{$value['zx_withdraw']},{$value['ph_withdraw']},{$value['zx_wait_capital']},{$value['ph_wait_capital']},{$value['zx_wait_interest']},{$value['ph_wait_interest']},{$value['total_capital']},{$value['total_interest']},{$value['zx_revenue']},{$value['ph_revenue']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }
        $file_address = APP_DIR . "/public/upload/DealLoadBYUser2ExcelAll/{$name}";
        if (file_put_contents($file_address , $data)) {
            echo "data to csv is OK \n";
            // $oss_address = ConfUtil::get('OSS-ccs-yj.fileName').DIRECTORY_SEPARATOR.'DealLoadBYUser2ExcelAll'.DIRECTORY_SEPARATOR.$name;
            // echo $oss_address . "\n";
            // var_dump(Yii::app()->xf_oss);
            // $res = Yii::app()->xf_oss->bigFileUpload($file_address , $oss_address);
            // var_dump($res);
            // if (1) {
                // unlink($file);
                // $oss_download = "https://xfccs.zichanhuayuan.com/upload/DealLoadBYUser2ExcelAll/{$name}";
                // $MailClass    = new MailClass();
                // var_dump($MailClass);
                // $title          = "在途出借人明细(尊享+普惠) 全量导出";
                // $content        = "下载地址：{$oss_download}";
                // echo "{$content}\n";
                // // $send_to_mail[] = 'zhaoyujuan@ucfgroup.com';
                // $send_to_mail[] = 'zhaowanwan@nebula-sc.com';
                // $send_to_mail[] = 'zhangjian@nebula-sc.com';
                // $result = true;
                // foreach ($send_to_mail as $key => $value) {
                //     $send = $MailClass->yjSend($value , $title , $content);
                //     var_dump($send);
                //     if ($send['code'] != 0) {
                //         $result = false;
                //     }
                // }
                // if(!$result){
                //     Yii::log("DealLoadBYUser2ExcelAll send email return false , send_mail:".print_r($send_to_mail, true));
                // }
            // } else {
            //     echo "csv upload oss is error \n";
            //     Yii::log("DealLoadBYUser2ExcelAll upload oss error");
            // }
        } else {
            echo "data to csv is error \n";
            Yii::log("DealLoadBYUser2ExcelAll save csv error");
        }

        $time = date('Y-m-d H:i:s' , time());
        echo "DealLoadBYUser2ExcelAll end, time:{$time} \n";
    }

    /**
     * 在途出借人明细 导出 尊享
     * @param platform 1尊享 2普惠
     * time php54 yiic.php Firstp2pUser DealLoadBYUser2Excel --platform=1
     */
    public function actionDealLoadBYUser2Excel($platform = 0)
    {
        set_time_limit(0);
        echo "DealLoadBYUser2Excel start \n";
        // 条件筛选
        $where = " WHERE deal_load.status = 1 ";
        if (!in_array($platform , array(1 , 2))) {
            echo "platform is error \n";
            exit;
        }
        if ($platform == 1) {
            $model = Yii::app()->fdb;
        } else if ($platform == 2) {
            $model = Yii::app()->phdb;
        }
        $sql = "SELECT count(DISTINCT deal_load.user_id) AS count FROM firstp2p_deal_load AS deal_load {$where} ";
        $count = $model->createCommand($sql)->queryScalar();
        if ($count == 0) {
            echo "no data \n";
            exit;
        }
        echo "count $count \n";
        $page_count = ceil($count / 500);
        for ($i = 0; $i < $page_count; $i++) {
            $pass = $i * 500;
            // 查询数据
            $sql = "SELECT deal_load.user_id AS id , user.real_name , user.mobile , user.idno , user_group.name AS group_name , user.sex , user.byear , bind.refer_user_id , refer.real_name AS refer_name , bind.short_alias , refer_group.name AS refer_group_name , user.money , user.lock_money , user.ph_money , user.ph_lock_money , user.zx_recharge , user.zx_withdraw , user.ph_recharge , user.ph_withdraw , SUM(wait_capital) AS wait_capital , SUM(wait_interest) AS wait_interest 
                    FROM firstp2p_deal_load AS deal_load 
                    LEFT JOIN firstp2p_user AS user ON user.id = deal_load.user_id 
                    LEFT JOIN firstp2p_user_group AS user_group ON user.group_id = user_group.id 
                    LEFT JOIN firstp2p_coupon_bind AS bind ON user.id = bind.user_id 
                    LEFT JOIN firstp2p_user AS refer ON refer.id = bind.refer_user_id 
                    LEFT JOIN firstp2p_user_group AS refer_group ON refer.group_id = refer_group.id 
                    {$where} GROUP BY deal_load.user_id LIMIT {$pass} , 500 ";
            $list = $model->createCommand($sql)->queryAll();

            $sex[0] = '女';
            $sex[1] = '男';
            $user_id_arr = array();
            foreach ($list as $key => $value) {
                $list[$key]['mobile']   = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                $list[$key]['mobile_a'] = substr($list[$key]['mobile'] , 0 , 7);
                $list[$key]['idno']     = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                $list[$key]['sex']      = $sex[$value['sex']];
                $list[$key]['byear']    = date('Y' , time()) - $value['byear'];
                if ($list[$key]['byear'] > 150) {
                    $list[$key]['byear'] = '——';
                }
                if ($platform == 1) {
                    $list[$key]['recharge_money'] = $value['zx_recharge'];
                    $list[$key]['withdraw_money'] = $value['zx_withdraw'];
                } else if ($platform == 2) {
                    $list[$key]['money']      = $value['ph_money'];
                    $list[$key]['lock_money'] = $value['ph_lock_money'];

                    $list[$key]['recharge_money'] = $value['ph_recharge'];
                    $list[$key]['withdraw_money'] = $value['ph_withdraw'];
                }
                if (!empty($list[$key]['mobile_a']) && is_numeric($list[$key]['mobile_a'])) {
                    $mobile_array[] = $list[$key]['mobile_a'];
                }
                $list[$key]['yes_interest'] = '0.00';
                $user_id_arr[] = $value['id'];
            }
            if ($mobile_array) {
                $mobile_string = implode(',' , $mobile_array);
                $sql = "SELECT mobile , provice , city FROM firstp2p_mobile_area WHERE mobile IN ({$mobile_string}) ";
                $mobile_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                foreach ($mobile_res as $key => $value) {
                    $mobile_data[$value['mobile']] = $value['provice'] . $value['city'];
                }
            } else {
                $mobile_data = array();
            }
            $interest_data = array();
            if ($user_id_arr) {
                $user_id_str = implode(',' , $user_id_arr);
                $sql = "SELECT user_id , SUM(yes_interest) AS yes_interest FROM firstp2p_deal_load WHERE user_id IN ({$user_id_str}) GROUP BY user_id ";
                $interest_res = $model->createCommand($sql)->queryAll();
                if ($interest_res) {
                    foreach ($interest_res as $key => $value) {
                        $interest_data[$value['user_id']] = $value['yes_interest'];
                    }
                }
            }
            foreach ($list as $key => $value) {
                $value['mobile_area']  = $mobile_data[$value['mobile_a']];
                $value['yes_interest'] = $interest_data[$value['id']];

                $listInfo[] = $value;
            }
        }
        echo "data OK \n";
        if ($platform == 1) {
            $name = 'DealLoadBYUser2Excel ZX '.date("Y_m_d H_i_s" , time()).'.csv';
        } else if ($platform == 2) {
            $name = 'DealLoadBYUser2Excel PH '.date("Y_m_d H_i_s" , time()).'.csv';
        }
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "用户ID,用户姓名,用户所属组别名称,性别,年龄,手机号所在地,服务人ID,服务人姓名,服务人邀请码,服务人所属组别名称,账户余额,账户冻结金额,历史充值金额,历史提现金额,在途本金,在途利息,历史累计收益额\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['real_name']},{$value['group_name']},{$value['sex']},{$value['byear']},{$value['mobile_area']},{$value['refer_user_id']},{$value['refer_name']},{$value['short_alias']},{$value['refer_group_name']},{$value['money']},{$value['lock_money']},{$value['recharge_money']},{$value['withdraw_money']},{$value['wait_capital']},{$value['wait_interest']},{$value['yes_interest']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }
        $file_address = APP_DIR . "/public/upload/DealLoadBYUser2ExcelAll/{$name}";
        if (file_put_contents($file_address , $data)) {
            echo "data to csv OK \n";
            echo "address is {$file_address} \n";
        } else {
            echo "data to csv error \n";
        }
    }

    /**
     * 智多新 在途出借人明细 导出
     * time php54 yiic.php Firstp2pUser ZDXDealLoadBYUser2Excel
     */
    public function actionZDXDealLoadBYUser2Excel()
    {
        set_time_limit(0);
        echo "ZDXDealLoadBYUser2Excel start \n";
        // 条件筛选
        $platform_id = 4;
        $where = " WHERE deal_load.status = 1 AND deal_load.platform_id = {$platform_id} ";
        $model = Yii::app()->offlinedb;
        $sql = "SELECT count(DISTINCT deal_load.user_id) AS count FROM offline_deal_load AS deal_load {$where} ";
        $count = $model->createCommand($sql)->queryScalar();
        if ($count == 0) {
            echo "no data \n";
            exit;
        }
        echo "count $count \n";
        $page_count = ceil($count / 500);
        for ($i = 0; $i < $page_count; $i++) {
            $pass = $i * 500;
            // 查询数据
            $sql = "SELECT deal_load.user_id AS id , SUM(deal_load.wait_capital) AS wait_capital , SUM(deal_load.wait_interest) AS wait_interest , user.money , user.lock_money 
                    FROM offline_deal_load AS deal_load 
                    LEFT JOIN offline_user_platform AS user ON deal_load.user_id = user.user_id 
                    {$where} GROUP BY deal_load.user_id LIMIT {$pass} , 500 ";
            $list = $model->createCommand($sql)->queryAll();

            $sex[0] = '女';
            $sex[1] = '男';
            $user_id_arr = array();
            foreach ($list as $key => $value) {
                $user_id_arr[] = $value['id'];
            }
            $user_id_str = implode(',' , $user_id_arr);
            $sql = "SELECT id , real_name , mobile , idno , sex , byear FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
            $user_res = Yii::app()->fdb->createCommand($sql)->queryAll();
            $user_info = array();
            foreach ($user_res as $key => $value) {
                $user_info[$value['id']] = $value;
            }
            foreach ($list as $key => $value) {
                $value['real_name'] = $user_info[$value['id']]['real_name'];
                $value['mobile']   = GibberishAESUtil::dec($user_info[$value['id']]['mobile'], Yii::app()->c->idno_key);
                $value['mobile_a'] = substr($value['mobile'] , 0 , 7);
                $value['idno']     = GibberishAESUtil::dec($user_info[$value['id']]['idno'], Yii::app()->c->idno_key);
                $value['sex']      = $sex[$user_info[$value['id']]['sex']];
                $value['byear']    = date('Y' , time()) - $user_info[$value['id']]['byear'];
                if ($value['byear'] > 150) {
                    $value['byear'] = '——';
                }
                $value['yes_interest'] = $interest_data[$value['id']] ? $interest_data[$value['id']] : '0.00';
                $value['mobile_area']  = '';
                if (!empty($value['mobile_a']) && is_numeric($value['mobile_a'])) {
                    $mobile_array[] = $value['mobile_a'];
                }
                
                $listInfo[] = $value;
            }
            if ($mobile_array) {
                $mobile_string = implode(',' , $mobile_array);
                $sql = "SELECT mobile , provice , city FROM firstp2p_mobile_area WHERE mobile IN ({$mobile_string}) ";
                $mobile_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                foreach ($mobile_res as $key => $value) {
                    $mobile_data[$value['mobile']] = $value['provice'] . $value['city'];
                }
            } else {
                $mobile_data = array();
            }
            foreach ($listInfo as $key => $value) {
                $listInfo[$key]['mobile_area'] = $mobile_data[$value['mobile_a']];
            }
        }
        echo "data OK \n";
        $name  = 'DealLoadBYUser2Excel ZDX '.date("Y_m_d H_i_s" , time()).'.csv';
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "用户ID,用户姓名,性别,年龄,手机号所在地,账户余额,账户冻结金额,在途本金,在途利息,历史累计收益额\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['real_name']},{$value['sex']},{$value['byear']},{$value['mobile_area']},{$value['money']},{$value['lock_money']},{$value['wait_capital']},{$value['wait_interest']},{$value['yes_interest']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }
        $file_address = APP_DIR . "/public/upload/DealLoadBYUser2ExcelAll/{$name}";
        if (file_put_contents($file_address , $data)) {
            echo "data to csv OK \n";
            echo "address is {$file_address} \n";
        } else {
            echo "data to csv error \n";
        }
    }

    public function actionOfflineBYUser2ExcelAll()
    {
        set_time_limit(0);
        $time = date('Y-m-d H:i:s' , time());
        echo "Firstp2pUser OfflineBYUser2ExcelAll start, time:{$time} \n";
        // 排除受让人ID（除了3120608张翠英）
        $sql = "SELECT user_id FROM ag_wx_assignee_info WHERE transferred_amount > 0 AND user_id != 3120608 ";
        $assignee = Yii::app()->fdb->createCommand($sql)->queryColumn();
        $user_id_str = implode(',' , $assignee);
        $sql = "SELECT a.id,a.real_name,a.mobile,sum(b.wait_capital) as wait_capital
from offline_deal_load b
left join firstp2p_user a on b.user_id=a.id
WHERE b.status=1 and b.wait_capital>0 and b.platform_id=4 AND b.user_id NOT IN ({$user_id_str}) 
group by b.user_id  ";
        $list = Yii::app()->offlinedb->createCommand($sql)->queryAll();


        $sql = "SELECT mobile , provice , city FROM firstp2p_mobile_area  ";
        $mobile_res = Yii::app()->fdb->createCommand($sql)->queryAll();
        foreach ($mobile_res as $key => $value) {
            $mobile_data[$value['mobile']] = $value['provice'] . $value['city'];
        }
        $listInfo = [];
        foreach ($list as $key => $value) {
            $value['mobile']   = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
            $value['mobile_a'] = substr($value['mobile'] , 0 , 7);


            if (!empty($mobile_data[$value['mobile_a']])) {
                $value['mobile_area'] = $mobile_data[$value['mobile_a']];
            } else {
                $value['mobile_area'] = '';
            }
            $listInfo[] = $value;
            echo "user_id:{$value['id']} is OK \n";
        }


        echo "data to csv start \n";
        $name = '在途出zdx全量 '.date("Y年m月d日 H时i分s秒" , time()).'.csv';
        $data  = "用户ID,用户姓名,手机号所在地,capital\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['real_name']},{$value['mobile_area']},{$value['wait_capital']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }
        $file_address = APP_DIR . "/public/upload/ZDXDealLoadUser/{$name}";
        if (file_put_contents($file_address , $data)) {
            echo "data to csv is OK \n";
        } else {
            echo "data to csv is error \n";
            Yii::log("ZDXDealLoadUser save csv error");
        }

        $time = date('Y-m-d H:i:s' , time());
        echo "ZDXDealLoadUser end, time:{$time} \n";
    }
}