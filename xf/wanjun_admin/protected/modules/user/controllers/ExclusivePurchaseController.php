<?php
use iauth\models\AuthAssignment;

class ExclusivePurchaseController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            'Success' , 'Error' , 'AssigneeChangeUserId' , 'EditPassword' , 'CheckBankzone' , 'IDCardPicsInfo' , 'addDebtListCondition' , 'AddUserConditionUpload' , 'CheckDealName'
        );
    }

    /**
     * 债转记录 列表 张健
     * 提供查询字段：
     * @param user_id       int     用户ID
     * @param borrow_id     int     项目ID
     * @param tender_id     int     投资记录ID
     * @param name          string  项目名称
     * @param status        int     转让状态 1-新建转让中，2-转让成功，3-取消转让，4-过期
     * @param mobile        string  用户手机号
     * @param debt_src      int     债转类型 1-权益兑换、2-债转交易、3债权划扣
     * @param deal_type     int     项目类型[1-尊享 2-普惠供应链]
     * @param limit         int     每页数据显示量 默认10
     * @param page          int     当前页数 默认1
     */
    public function actionGetDebtList()
    {
        $sql = "select id,name from xf_debt_exchange_platform";
        $_platform = XfDebtExchangePlatform::model()->findAllBySql($sql);
        foreach ($_platform as $item) {
            $platform_no[$item->id]=$item->name;
        }
        if (!empty($_POST)) {
            // 条件筛选
            $where = " WHERE deal.is_zdx = 0 ";
            if (!empty($_POST['condition_id'])) {
                $sql = "SELECT * FROM xf_debt_list_condition WHERE id = '{$_POST['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    if ($condition['true_count'] > 500) {
                        header("Content-type:application/json; charset=utf-8");
                        $result_data['data']  = array();
                        $result_data['count'] = 0;
                        $result_data['code']  = 1;
                        $result_data['info']  = '批量条件超过500行，暂不支持列表查询！';
                        echo exit(json_encode($result_data));
                    }
                    $con_data = json_decode($condition['data_json'], true);
                    if ($condition['platform']) {
                        $_POST['deal_type'] = $condition['platform'];
                    }
                }
            }

           
            
            // 尊享
            if ($_POST['deal_type'] == 1) {

                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where  .= " AND debt.user_id = {$user_id} ";
                }
                // 校验债转编号
                if (!empty($_POST['serial_number'])) {
                    $serial_number = trim($_POST['serial_number']);
                    $where        .= " AND debt.serial_number = '{$serial_number}'";
                }
                // 校验项目ID
                if (!empty($_POST['borrow_id'])) {
                    $borrow_id = intval($_POST['borrow_id']);
                    $where    .= " AND debt.borrow_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['tender_id'])) {
                    $tender_id = intval($_POST['tender_id']);
                    $where    .= " AND debt.tender_id = {$tender_id} ";
                }
                // 校验转让状态
                if (!empty($_POST['status'])) {
                    $sta = intval($_POST['status']);
                    $where .= " AND debt.status = {$sta} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile = trim($_POST['mobile']);
                    $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $where .= " AND user.mobile = '{$mobile}' ";
                }
                // 校验债转类型
                if (!empty($_POST['debt_src'])) {
                    $d_src  = intval($_POST['debt_src']);
                    $where .= " AND debt.debt_src = {$d_src} ";
                }
                // 校验兑换平台
                if (!empty($_POST['platform_no']) && $_POST['debt_src'] == 1) {
                    $p_no   = intval($_POST['platform_no']);
                    $where .= " AND debt.platform_no = {$p_no} ";
                }
                // 校验消费专区ID
                if (!empty($_POST['channel_id']) && $_POST['debt_src'] == 1) {
                    $c_id   = intval($_POST['channel_id']);
                    $where .= " AND debt.channel_id = {$c_id} ";
                }
                // 校验债权来源
                // if (!empty($_POST['load_src'])) {
                //     $l_src  = intval($_POST['load_src'])-1;
                //     $where .= " AND debt.load_src = {$l_src} ";
                // }
                // 校验借款方名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                    $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                    $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $com_arr = array();
                    if ($com_a) {
                        foreach ($com_a as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if ($com_b) {
                        foreach ($com_b as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if (!empty($com_arr)) {
                        $com_str = implode(',', $com_arr);
                        $where  .= " AND deal.user_id IN ({$com_str}) ";
                    } else {
                        $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                        $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($user_id_arr) {
                            $user_id_str = implode(',', $user_id_arr);
                            $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                        } else {
                            $where      .= " AND deal.user_id = '' ";
                        }
                    }
                }

                //咨询方查询
                if (!empty($_POST['advisory'])) {
                    $where .= $this->getAdvisoryConditions($_POST['advisory'], 1);
                }

                // 校验受让人ID
                if (!empty($_POST['t_user_id'])) {
                    $t_user_id = intval($_POST['t_user_id']);
                    $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验受让人手机号
                if (!empty($_POST['t_mobile'])) {
                    $t_mobile = trim($_POST['t_mobile']);
                    $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                    // var_dump($t_mobile);exit;
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($t_user_id) {
                        $t_user_id_str = implode(',', $t_user_id);
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                        $debt_id_res = Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验转让完成时间
                if (!empty($_POST['start'])) {
                    $start  = strtotime($_POST['start'].' 00:00:00');
                    $where .= " AND debt.successtime >= {$start} ";
                }
                if (!empty($_POST['end'])) {
                    $end    = strtotime($_POST['end'].' 23:59:59');
                    $where .= " AND debt.successtime <= {$end} ";
                }
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
                //后台用户
                $adminUserInfo  = \Yii::app()->user->getState('_user');
                if (!empty($adminUserInfo['username'])) {
                    if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                        if ($adminUserInfo['user_type'] == 2) {
                            $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                            if (!empty($deallist)) {
                                $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                                $where .= " AND deal.id IN({$dealIds})";
                            } else {
                                $where .= " AND deal.id < 0";
                            }
                        }
                    }
                }
                // 查询数据总量
                if ($condition) {
                    $redis_time = 86400;
                    $redis_key  = 'XF_Debt_List_Count_ZX_Condition_'.$condition['id'];
                    $redis_val  = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                    } else {
                        if ($con_data) {
                            $count    = 0;
                            $con_page = ceil(count($con_data) / 1000);
                            for ($i = 0; $i < $con_page; $i++) {
                                $con_data_arr = array();
                                for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                                    if (!empty($con_data[$j])) {
                                        $con_data_arr[] = $con_data[$j];
                                    }
                                }
                                $con_data_str = implode(',', $con_data_arr);
                                $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                                $sql = "SELECT count(debt.id) AS count 
                                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                                        LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                                $count_con = Yii::app()->fdb->createCommand($sql)->queryScalar();
                                $count += $count_con;
                            }
                            $con_data_str = implode(',', $con_data);
                            $where .= " AND debt.id IN ({$con_data_str}) ";
                            $set = Yii::app()->rcache->set($redis_key, $count, $redis_time);
                            if (!$set) {
                                Yii::log("{$redis_key} redis count set error", "error");
                            }
                        } else {
                            $where .= " AND debt.id = '' ";
                        }
                    }
                } else {
                    $sql = "SELECT count(debt.id) AS count 
                            FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                            LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                            LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                    $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
                }
                if ($count == 0) {
                    header("Content-type:application/json; charset=utf-8");
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 0;
                    $result_data['info']  = '查询成功';
                    echo exit(json_encode($result_data));
                }
                // 查询数据
                $sql = "SELECT debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src , deal.name, user.real_name, user.mobile , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.id DESC ";
                $pass       = ($page - 1) * $limit;
                $sql       .= " LIMIT {$pass} , {$limit} ";
                if (strlen($sql) > 1048576) {
                    header("Content-type:application/json; charset=utf-8");
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 1;
                    $result_data['info']  = '批量条件过多导致SQL长度超过1M，暂不支持列表查询！';
                    echo exit(json_encode($result_data));
                }
                $list       = Yii::app()->fdb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                // $platform_no[1] = '有解';
                // $platform_no[2] = '悠融优选';

                // $load_src[0] = '常规债权';
                // $load_src[1] = '一键下车退回债权';
                // $load_src[2] = '权益兑换退回债权';

                $debt_id_arr = array();
                //获取当前账号所有子权限
                $authList = \Yii::app()->user->getState('_auth');
                $info_status = 0;
                if (!empty($authList) && strstr($authList, '/user/Debt/DebtInfo') || empty($authList)) {
                    $info_status = 1;
                }
                foreach ($list as $key => $value) {
                    $value['mobile']  = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    $value['mobile']  = $this->strEncrypt($value['mobile'], 3, 4);
                    $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $value['successtime'] = '——';
                    }
                    $value['money']           = number_format($value['money'], 2, '.', ',');
                    $value['sold_money']      = number_format($value['sold_money'], 2, '.', ',');
                    $value['deal_load_money'] = number_format($value['deal_load_money'], 2, '.', ',');
                    $value['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $value['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $value['platform_no'] = '——';
                    }
                    $value['debt_src']        = $debt_src[$value['debt_src']];
                    // $value['load_src']        = $load_src[$value['load_src']];
                    $value['info_status']     = $info_status;
                    
                    $listInfo[] = $value;

                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT dt.debt_id , u.id , u.real_name , u.mobile , dt.new_tender_id , dt.status FROM firstp2p_debt_tender AS dt INNER JOIN firstp2p_user AS u ON dt.user_id = u.id AND dt.debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',', $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM firstp2p_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                foreach ($listInfo as $key => $value) {
                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']    = $debt_tender[$value['id']]['id'];
                        $listInfo[$key]['t_real_name']  = $debt_tender[$value['id']]['real_name'];
                        $listInfo[$key]['t_mobile']     = GibberishAESUtil::dec($debt_tender[$value['id']]['mobile'], Yii::app()->c->idno_key); // 手机号解密
                        $listInfo[$key]['t_mobile']     = $this->strEncrypt($listInfo[$key]['t_mobile'], 3, 4);
                        $listInfo[$key]['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $listInfo[$key]['t_user_id']    = '——';
                        $listInfo[$key]['t_real_name']  = '——';
                        $listInfo[$key]['t_mobile']     = '——';
                        $listInfo[$key]['oss_download'] = '';
                    }

                    $listInfo[$key]['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);
                }

                // 普惠供应链
            } elseif ($_POST['deal_type'] == 2) {

                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where  .= " AND debt.user_id = {$user_id} ";
                }
                // 校验债转编号
                if (!empty($_POST['serial_number'])) {
                    $serial_number = trim($_POST['serial_number']);
                    $where        .= " AND debt.serial_number = '{$serial_number}' ";
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile      = trim($_POST['mobile']);
                    $mobile      = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql         = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                    $user_id     = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($user_id) {
                        $where .= " AND debt.user_id = {$user_id} ";
                    } else {
                        $where .= " AND debt.user_id is NULL ";
                    }
                }
                // 校验项目ID
                if (!empty($_POST['borrow_id'])) {
                    $borrow_id = intval($_POST['borrow_id']);
                    $where    .= " AND debt.borrow_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['tender_id'])) {
                    $tender_id = intval($_POST['tender_id']);
                    $where    .= " AND debt.tender_id = {$tender_id} ";
                }
                // 校验转让状态
                if (!empty($_POST['status'])) {
                    $sta    = intval($_POST['status']);
                    $where .= " AND debt.status = {$sta} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }
                // 校验债转类型
                if (!empty($_POST['debt_src'])) {
                    $d_src  = intval($_POST['debt_src']);
                    $where .= " AND debt.debt_src = {$d_src} ";
                }
                // 校验兑换平台
                if (!empty($_POST['platform_no']) && $_POST['debt_src'] == 1) {
                    $p_no   = intval($_POST['platform_no']);
                    $where .= " AND debt.platform_no = {$p_no} ";
                }
                // 校验消费专区ID
                if (!empty($_POST['channel_id']) && $_POST['debt_src'] == 1) {
                    $c_id   = intval($_POST['channel_id']);
                    $where .= " AND debt.channel_id = {$c_id} ";
                }
                // 校验债权来源
                // if (!empty($_POST['load_src'])) {
                //     $l_src  = intval($_POST['load_src'])-1;
                //     $where .= " AND debt.load_src = {$l_src} ";
                // }
                // 校验借款方名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                    $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                    $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $com_arr = array();
                    if ($com_a) {
                        foreach ($com_a as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if ($com_b) {
                        foreach ($com_b as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if (!empty($com_arr)) {
                        $com_str = implode(',', $com_arr);
                        $where  .= " AND deal.user_id IN ({$com_str}) ";
                    } else {
                        $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                        $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($user_id_arr) {
                            $user_id_str = implode(',', $user_id_arr);
                            $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                        } else {
                            $where      .= " AND deal.user_id = '' ";
                        }
                    }
                }

                //咨询方查询
                if (!empty($_POST['advisory'])) {
                    $where .= $this->getAdvisoryConditions($_POST['advisory'], 2);
                }
                // 校验受让人ID
                if (!empty($_POST['t_user_id'])) {
                    $t_user_id = intval($_POST['t_user_id']);
                    $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验受让人手机号
                if (!empty($_POST['t_mobile'])) {
                    $t_mobile = trim($_POST['t_mobile']);
                    $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($t_user_id) {
                        $t_user_id_str = implode(',', $t_user_id);
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                        $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验转让完成时间
                if (!empty($_POST['start'])) {
                    $start  = strtotime($_POST['start'].' 00:00:00');
                    $where .= " AND debt.successtime >= {$start} ";
                }
                if (!empty($_POST['end'])) {
                    $end    = strtotime($_POST['end'].' 23:59:59');
                    $where .= " AND debt.successtime <= {$end} ";
                }
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
                //后台用户
                $adminUserInfo  = \Yii::app()->user->getState('_user');
                if (!empty($adminUserInfo['username'])) {
                    if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                        if ($adminUserInfo['user_type'] == 2) {
                            $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                            if (!empty($deallist)) {
                                $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                                $where .= " AND deal.id IN({$dealIds})";
                            } else {
                                //不是超级管理员并且没有$dealIds
                                $where .= " AND deal.id < 0";
                            }
                        }
                    }
                }
                // 查询数据总量
                if ($condition) {
                    $redis_time = 86400;
                    $redis_key  = 'XF_Debt_List_Count_PH_Condition_'.$condition['id'];
                    $redis_val  = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                    } else {
                        if ($con_data) {
                            $count    = 0;
                            $con_page = ceil(count($con_data) / 1000);
                            for ($i = 0; $i < $con_page; $i++) {
                                $con_data_arr = array();
                                for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                                    if (!empty($con_data[$j])) {
                                        $con_data_arr[] = $con_data[$j];
                                    }
                                }
                                $con_data_str = implode(',', $con_data_arr);
                                $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                                $sql = "SELECT count(debt.id) AS count 
                                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id  
                                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                                $count_con = Yii::app()->phdb->createCommand($sql)->queryScalar();
                                $count += $count_con;
                            }
                            $con_data_str = implode(',', $con_data);
                            $where .= " AND debt.id IN ({$con_data_str}) ";
                            $set = Yii::app()->rcache->set($redis_key, $count, $redis_time);
                            if (!$set) {
                                Yii::log("{$redis_key} redis count set error", "error");
                            }
                        } else {
                            $where .= " AND debt.id = '' ";
                        }
                    }
                } else {
                    $sql = "SELECT count(debt.id) AS count 
                            FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                            LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                    $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
                }
                if ($count == 0) {
                    header("Content-type:application/json; charset=utf-8");
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 0;
                    $result_data['info']  = '查询成功';
                    echo exit(json_encode($result_data));
                }
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src , deal.name , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id  
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.id DESC ";
                $pass       = ($page - 1) * $limit;
                $sql       .= " LIMIT {$pass} , {$limit} ";
                if (strlen($sql) > 1048576) {
                    header("Content-type:application/json; charset=utf-8");
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 1;
                    $result_data['info']  = '批量条件过多导致SQL长度超过1M，暂不支持列表查询！';
                    echo exit(json_encode($result_data));
                }
                $list       = Yii::app()->phdb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                // $platform_no[1] = '有解';
                // $platform_no[2] = '悠融优选';

                // $load_src[0] = '常规债权';
                // $load_src[1] = '一键下车退回债权';
                // $load_src[2] = '权益兑换退回债权';

                $debt_id_arr = array();
                //获取当前账号所有子权限
                $authList = \Yii::app()->user->getState('_auth');
                $info_status = 0;
                if (!empty($authList) && strstr($authList, '/user/Debt/DebtInfo') || empty($authList)) {
                    $info_status = 1;
                }
                foreach ($list as $key => $value) {
                    $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $value['successtime'] = '——';
                    }
                    $value['money']           = number_format($value['money'], 2, '.', ',');
                    $value['sold_money']      = number_format($value['sold_money'], 2, '.', ',');
                    $value['deal_load_money'] = number_format($value['deal_load_money'], 2, '.', ',');
                    $value['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $value['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $value['platform_no'] = '——';
                    }
                    $value['debt_src']        = $debt_src[$value['debt_src']];
                    // $value['load_src']        = $load_src[$value['load_src']];
                    $value['info_status']     = $info_status;

                    $listInfo[]    = $value;
                    $user_id_arr[] = $value['user_id'];
                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT debt_id , user_id , new_tender_id , status FROM firstp2p_debt_tender WHERE debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            $user_id_arr[] = $value['user_id'];
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',', $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM firstp2p_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                $user_id_str = implode(',', $user_id_arr);
                $user_id_res = array();
                if ($user_id_str) {
                    $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                }
                foreach ($user_id_res as $key => $value) {
                    $temp['real_name'] = $value['real_name'];
                    $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    $temp['mobile']    = $this->strEncrypt($temp['mobile'], 3, 4);

                    $user_id_data[$value['id']] = $temp;
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                    $listInfo[$key]['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']    = $debt_tender[$value['id']]['user_id'];
                        $listInfo[$key]['t_real_name']  = $user_id_data[$debt_tender[$value['id']]['user_id']]['real_name'];
                        $listInfo[$key]['t_mobile']     = $user_id_data[$debt_tender[$value['id']]['user_id']]['mobile'];
                        $listInfo[$key]['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $listInfo[$key]['t_user_id']    = '——';
                        $listInfo[$key]['t_real_name']  = '——';
                        $listInfo[$key]['t_mobile']     = '——';
                        $listInfo[$key]['oss_download'] = '';
                    }

                    $listInfo[$key]['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);
                }

                // 工场微金 智多新 交易所
            } elseif (in_array($_POST['deal_type'], [3, 4, 5])) {
                $where = " WHERE debt.platform_id = {$_POST['deal_type']} ";
                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where  .= " AND debt.user_id = {$user_id} ";
                }
                // 校验债转编号
                if (!empty($_POST['serial_number'])) {
                    $serial_number = trim($_POST['serial_number']);
                    $where        .= " AND debt.serial_number = '{$serial_number}' ";
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile      = trim($_POST['mobile']);
                    $mobile      = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql         = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                    $user_id     = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($user_id) {
                        $where .= " AND debt.user_id = {$user_id} ";
                    } else {
                        $where .= " AND debt.user_id is NULL ";
                    }
                }
                // 校验项目ID
                if (!empty($_POST['borrow_id'])) {
                    $borrow_id = intval($_POST['borrow_id']);
                    $where    .= " AND debt.borrow_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['tender_id'])) {
                    $tender_id = intval($_POST['tender_id']);
                    $where    .= " AND debt.tender_id = {$tender_id} ";
                }
                // 校验转让状态
                if (!empty($_POST['status'])) {
                    $sta    = intval($_POST['status']);
                    $where .= " AND debt.status = {$sta} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }
                // 校验债转类型
                if (!empty($_POST['debt_src'])) {
                    $d_src  = intval($_POST['debt_src']);
                    $where .= " AND debt.debt_src = {$d_src} ";
                }
                // 校验兑换平台
                if (!empty($_POST['platform_no']) && $_POST['debt_src'] == 1) {
                    $p_no   = intval($_POST['platform_no']);
                    $where .= " AND debt.platform_no = {$p_no} ";
                }
                // 校验消费专区ID
                if (!empty($_POST['channel_id']) && $_POST['debt_src'] == 1) {
                    $c_id   = intval($_POST['channel_id']);
                    $where .= " AND debt.channel_id = {$c_id} ";
                }
                // 校验债权来源
                // if (!empty($_POST['load_src'])) {
                //     $l_src  = intval($_POST['load_src'])-1;
                //     $where .= " AND debt.load_src = {$l_src} ";
                // }
                // 校验借款方名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                    $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                    $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $com_arr = array();
                    if ($com_a) {
                        foreach ($com_a as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if ($com_b) {
                        foreach ($com_b as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if (!empty($com_arr)) {
                        $com_str = implode(',', $com_arr);
                        $where  .= " AND deal.user_id IN ({$com_str}) ";
                    } else {
                        $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                        $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($user_id_arr) {
                            $user_id_str = implode(',', $user_id_arr);
                            $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                        } else {
                            $where      .= " AND deal.user_id = '' ";
                        }
                    }
                }
                //咨询方查询
                if (!empty($_POST['advisory'])) {
                    $advisory = trim($_POST['advisory']);
                    $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$name}' ";
                    $advisory_list = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                    if ($advisory_list) {
                        $adv_arr = implode(',', $advisory_list);
                        $where  .= " AND deal.advisory_id IN ({$adv_arr}) ";
                    } else {
                        $where  .= " AND deal.advisory_id IS NULL ";
                    }
                }
                // 校验受让人ID
                if (!empty($_POST['t_user_id'])) {
                    $t_user_id = intval($_POST['t_user_id']);
                    $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验受让人手机号
                if (!empty($_POST['t_mobile'])) {
                    $t_mobile = trim($_POST['t_mobile']);
                    $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($t_user_id) {
                        $t_user_id_str = implode(',', $t_user_id);
                        $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                        $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验转让完成时间
                if (!empty($_POST['start'])) {
                    $start  = strtotime($_POST['start'].' 00:00:00');
                    $where .= " AND debt.successtime >= {$start} ";
                }
                if (!empty($_POST['end'])) {
                    $end    = strtotime($_POST['end'].' 23:59:59');
                    $where .= " AND debt.successtime <= {$end} ";
                }
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
                //后台用户
                $adminUserInfo  = \Yii::app()->user->getState('_user');
                if (!empty($adminUserInfo['username'])) {
                    if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                        if ($adminUserInfo['user_type'] == 2) {
                            $deallist = Yii::app()->offlinedb->createCommand("SELECT offline_deal.id deal_id from offline_deal_agency LEFT JOIN offline_deal ON offline_deal.advisory_id = offline_deal_agency.id WHERE offline_deal_agency.name = '{$adminUserInfo['realname']}' and offline_deal_agency.is_effect = 1 and offline_deal.id > 0")->queryAll();
                            if (!empty($deallist)) {
                                $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                                $where .= " AND deal.id IN({$dealIds})";
                            } else {
                                //不是超级管理员并且没有$dealIds
                                $where .= " AND deal.id < 0";
                            }
                        }
                    }
                }
                // 查询数据总量
                if ($condition) {
                    $redis_time = 86400;
                    if ($_POST['deal_type'] == 3) {
                        $redis_key = 'XF_Debt_List_Count_GCWJ_Condition_'.$condition['id'];
                    } elseif ($_POST['deal_type'] == 4) {
                        $redis_key = 'XF_Debt_List_Count_ZDX_Condition_'.$condition['id'];
                    } elseif ($_POST['deal_type'] == 5) {
                        $redis_key = 'XF_Debt_List_Count_JYS_Condition_'.$condition['id'];
                    }
                    $redis_val  = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                    } else {
                        if ($con_data) {
                            $count    = 0;
                            $con_page = ceil(count($con_data) / 1000);
                            for ($i = 0; $i < $con_page; $i++) {
                                $con_data_arr = array();
                                for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                                    if (!empty($con_data[$j])) {
                                        $con_data_arr[] = $con_data[$j];
                                    }
                                }
                                $con_data_str = implode(',', $con_data_arr);
                                $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                                $sql = "SELECT count(debt.id) AS count 
                                        FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id  
                                        LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                                $count_con = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
                                $count += $count_con;
                            }
                            $con_data_str = implode(',', $con_data);
                            $where .= " AND debt.id IN ({$con_data_str}) ";
                            $set = Yii::app()->rcache->set($redis_key, $count, $redis_time);
                            if (!$set) {
                                Yii::log("{$redis_key} redis count set error", "error");
                            }
                        } else {
                            $where .= " AND debt.id = '' ";
                        }
                    }
                } else {
                    $sql = "SELECT count(debt.id) AS count 
                            FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                            LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                    $count = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
                }
                if ($count == 0) {
                    header("Content-type:application/json; charset=utf-8");
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 0;
                    $result_data['info']  = '查询成功';
                    echo exit(json_encode($result_data));
                }
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src , deal.name , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id  
                        LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.id DESC ";
                $pass       = ($page - 1) * $limit;
                $sql       .= " LIMIT {$pass} , {$limit} ";
                if (strlen($sql) > 1048576) {
                    header("Content-type:application/json; charset=utf-8");
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 1;
                    $result_data['info']  = '批量条件过多导致SQL长度超过1M，暂不支持列表查询！';
                    echo exit(json_encode($result_data));
                }
                $list       = Yii::app()->offlinedb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                // $platform_no[1] = '有解';
                // $platform_no[2] = '悠融优选';

                // $load_src[0] = '常规债权';
                // $load_src[1] = '一键下车退回债权';
                // $load_src[2] = '权益兑换退回债权';

                $debt_id_arr = array();
                //获取当前账号所有子权限
                $authList = \Yii::app()->user->getState('_auth');
                $info_status = 0;
                if (!empty($authList) && strstr($authList, '/user/Debt/DebtInfo') || empty($authList)) {
                    $info_status = 1;
                }
                foreach ($list as $key => $value) {
                    $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $value['successtime'] = '——';
                    }
                    $value['money']           = number_format($value['money'], 2, '.', ',');
                    $value['sold_money']      = number_format($value['sold_money'], 2, '.', ',');
                    $value['deal_load_money'] = number_format($value['deal_load_money'], 2, '.', ',');
                    $value['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $value['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $value['platform_no'] = '——';
                    }
                    $value['debt_src']        = $debt_src[$value['debt_src']];
                    // $value['load_src']        = $load_src[$value['load_src']];
                    $value['info_status']     = $info_status;

                    $listInfo[]    = $value;
                    $user_id_arr[] = $value['user_id'];
                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT debt_id , user_id , new_tender_id , status FROM offline_debt_tender WHERE debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            $user_id_arr[] = $value['user_id'];
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',', $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM offline_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                $user_id_str = implode(',', $user_id_arr);
                $user_id_res = array();
                if ($user_id_str) {
                    $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                }
                foreach ($user_id_res as $key => $value) {
                    $temp['real_name'] = $value['real_name'];
                    $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    $temp['mobile']    = $this->strEncrypt($temp['mobile'], 3, 4);

                    $user_id_data[$value['id']] = $temp;
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                    $listInfo[$key]['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']    = $debt_tender[$value['id']]['user_id'];
                        $listInfo[$key]['t_real_name']  = $user_id_data[$debt_tender[$value['id']]['user_id']]['real_name'];
                        $listInfo[$key]['t_mobile']     = $user_id_data[$debt_tender[$value['id']]['user_id']]['mobile'];
                        $listInfo[$key]['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $listInfo[$key]['t_user_id']    = '——';
                        $listInfo[$key]['t_real_name']  = '——';
                        $listInfo[$key]['t_mobile']     = '——';
                        $listInfo[$key]['oss_download'] = '';
                    }

                    $listInfo[$key]['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);
                }
            }
            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList, '/user/Debt/DebtListExcel') || empty($authList)) {
            $daochu_status = 1;
        }
        $channel_id = Yii::app()->c->channel_id;
        $platform_no[$item->id]=$item->name;
        return $this->renderPartial('GetDebtList', array('daochu_status' => $daochu_status, 'channel_id' => $channel_id,'platform_no'=>$platform_no));
    }

    /**
     * 债转记录 列表 批量条件上传
     */
    public function actionaddDebtListCondition()
    {
        set_time_limit(0);
        if (in_array($_GET['download'], array(1 , 2 , 3))) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            if ($_GET['download'] == 1) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');
                $name = '化债数据查询 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒", time());
            } elseif ($_GET['download'] == 2) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1', '融资经办机构名称');
                $name = '化债数据查询 通过上传融资经办机构名称查询 '.date("Y年m月d日 H时i分s秒", time());
            } elseif ($_GET['download'] == 3) {
                $objPHPExcel->getActiveSheet()->setCellValue('A1', '借款标题');
                $name = '化债数据查询 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒", time());
            }

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
            exit;
        }

        if (!empty($_POST)) {
            if (!in_array($_POST['deal_type'], array(1, 2, 3, 4, 5))) {
                return $this->actionError('请正确选择所属平台', 5);
            }
            if (!in_array($_POST['type'], array(1, 2, 3))) {
                return $this->actionError('请正确输入查询类型', 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'], 5);
            }
            $type         = intval($_POST['type']);
            $file_address = $upload_xls['data'];

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$file_address;
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('上传的文件中无数据', 5);
            }
            unset($data[0]);
            $name = '';
            if ($_POST['deal_type'] == 1) {
                $model = Yii::app()->fdb;
                $platform = 1;
                $name .= '尊享';
                $table = 'firstp2p';
            } elseif ($_POST['deal_type'] == 2) {
                $model = Yii::app()->phdb;
                $platform = 2;
                $name .= '普惠';
                $table = 'firstp2p';
            } elseif (in_array($_POST['deal_type'], [3, 4, 5])) {
                $model = Yii::app()->offlinedb;
                $platform = intval($_POST['deal_type']);
                if ($platform == 3) {
                    $name .= '工场微金';
                } elseif ($platform == 4) {
                    $name .= '智多新';
                } elseif ($platform == 5) {
                    $name .= '交易所';
                }
                $table = 'offline';
            }
            if ($type == 1) {
                if ($Rows > 10001) {
                    return $this->actionError('上传的文件中数据超过一万行', 5);
                }
                $name .= ' 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒", time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','", $user_id_arr)."'";
                $sql = "SELECT id FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                $user_id_data = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $count = count($data);
                if (!$user_id_data) {
                    return $this->renderPartial('addDebtListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $false_id_str));
                }
                $sql = "SELECT user_id FROM ag_wx_assignee_info WHERE status IN (2, 3)";
                $assignee = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0], $user_id_data)) {
                        $false_id_arr[] = $value[0];
                    } elseif (in_array($value[0], $assignee)) {
                        $false_id_arr[] = $value[0].'(受让方账户数据请单独导出)';
                    } else {
                        $user_id_true[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ', $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                
                if ($user_id_true) {
                    $user_id_str = implode(',', $user_id_true);
                    $sql = "SELECT id FROM firstp2p_debt WHERE user_id IN ({$user_id_str})";
                    $debt = $model->createCommand($sql)->queryColumn();
                    if (!$debt) {
                        $debt = array();
                    }
                    $data_json = json_encode($debt);
                } else {
                    $data_json = json_encode(array());
                }
            } elseif ($type == 2) {
                if ($Rows > 1001) {
                    return $this->actionError('上传的文件中数据超过一千行', 5);
                }
                $name .= ' 通过上传融资经办机构名称查询 '.date("Y年m月d日 H时i分s秒", time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','", $user_id_arr)."'";
                $sql = "SELECT id , name FROM {$table}_deal_agency WHERE name IN ({$user_id_str}) ";
                $deal_res = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$deal_res) {
                    return $this->renderPartial('addDebtListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($deal_res as $key => $value) {
                    $deal_name[] = $value['name'];
                    $deal_id[] = $value['id'];
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0], $deal_name)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ', $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                
                $user_id_str = implode(',', $deal_id);
                $sql = "SELECT id FROM {$table}_deal WHERE advisory_id IN ({$user_id_str})";
                $deal_id = $model->createCommand($sql)->queryColumn();
                if ($deal_id) {
                    $deal_id_str = implode(',', $deal_id);
                    $sql = "SELECT id FROM {$table}_debt WHERE borrow_id IN ({$deal_id_str})";
                    $debt = $model->createCommand($sql)->queryColumn();
                    if (!$debt) {
                        $debt = array();
                    }
                    $data_json = json_encode($debt);
                } else {
                    $data_json = json_encode(array());
                }
            } elseif ($type == 3) {
                if ($Rows > 1001) {
                    return $this->actionError('上传的文件中数据超过一千行', 5);
                }
                $name .= ' 通过上传借款标题查询 '.date("Y年m月d日 H时i分s秒", time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = $value[0];
                }
                $user_id_str = "'".implode("','", $user_id_arr)."'";
                $sql = "SELECT id , name FROM {$table}_deal WHERE name IN ({$user_id_str}) ";
                $deal_res = $model->createCommand($sql)->queryAll();
                $count = count($data);
                if (!$deal_res) {
                    return $this->renderPartial('addDebtListCondition', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count));
                }
                foreach ($deal_res as $key => $value) {
                    $deal_name[] = $value['name'];
                    $deal_id[] = $value['id'];
                }
                $false_id_arr = array();
                foreach ($data as $key => $value) {
                    if (!in_array($value[0], $deal_name)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ', $false_id_arr);
                $false_count = count($false_id_arr);
                $true_count = $count - $false_count;
                
                $user_id_str = implode(',', $deal_id);
                $sql = "SELECT id FROM {$table}_debt WHERE borrow_id IN ({$user_id_str})";
                $debt = $model->createCommand($sql)->queryColumn();
                if (!$debt) {
                    $debt = array();
                }
                $data_json = json_encode($debt);
            }
            
            $sql = "INSERT INTO xf_debt_list_condition (type , platform , name , file_address , true_count , false_count , data_json) VALUES ({$type} , {$platform} , '{$name}' , '{$file_address}' , '{$true_count}' , '{$false_count}' , '{$data_json}')";
            $model_a = Yii::app()->fdb;
            $add = $model_a->createCommand($sql)->execute();
            $add_id = $model_a->getLastInsertID();

            if ($add) {
                return $this->renderPartial('addDebtListCondition', array('end' => 2 , 'count' => $count , 'true_count' => $true_count , 'false_count' => $false_count , 'false_id_str' => $false_id_str , 'add_id' => $add_id , 'add_name' => $name));
            } else {
                return $this->actionError('保存查询条件失败', 5);
            }
        }

        return $this->renderPartial('addDebtListCondition', array('end' => 0));
    }


    /**
     * 债转记录 列表 导出 张健
     */
    public function actionDebtListExcel()
    {
        set_time_limit(0);
        // 条件筛选
        $where = " WHERE deal.is_zdx = 0 ";
        if (!empty($_GET['condition_id'])) {
            $sql = "SELECT * FROM xf_debt_list_condition WHERE id = '{$_GET['condition_id']}' ";
            $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($condition) {
                $con_data = json_decode($condition['data_json'], true);
                if ($condition['platform']) {
                    $_GET['deal_type'] = $condition['platform'];
                }
                if ($_GET['deal_type'] == 1) {
                    $redis_key = 'XF_Debt_List_Download_ZX_Condition_'.$condition['id'];
                } elseif ($_GET['deal_type'] == 2) {
                    $redis_key = 'XF_Debt_List_Download_PH_Condition_'.$condition['id'];
                } elseif ($_GET['deal_type'] == 3) {
                    $redis_key = 'XF_Debt_List_Download_GCWJ_Condition_'.$condition['id'];
                } elseif ($_GET['deal_type'] == 4) {
                    $redis_key = 'XF_Debt_List_Download_ZDX_Condition_'.$condition['id'];
                } elseif ($_GET['deal_type'] == 5) {
                    $redis_key = 'XF_Debt_List_Download_JYS_Condition_'.$condition['id'];
                }
                $check = Yii::app()->rcache->get($redis_key);
                if ($check) {
                    echo '<h1>此下载地址已失效</h1>';
                    exit;
                }
            }
        }
        if ($_GET['user_id']=='' && $_GET['serial_number']=='' && $_GET['borrow_id']=='' && $_GET['tender_id']=='' && $_GET['status']=='' && $_GET['name']=='' && $_GET['mobile']=='' && $_GET['debt_src']=='' && $_GET['platform_no']=='' && $_GET['channel_id']=='' && $_GET['company']=='' && $_GET['advisory']=='' && $_GET['t_user_id']=='' && $_GET['t_mobile'] && $_GET['start']=='' && $_GET['end']=='' && $_GET['condition_id']=='') {
            echo '<h1>请输入至少一个查询条件</h1>';
            exit;
        }
        if (empty($_GET['deal_type'])) {
            $_GET['deal_type'] = 1;
        }
        $sql = "select id,name from xf_debt_exchange_platform";
        $_platform = XfDebtExchangePlatform::model()->findAllBySql($sql);
        foreach ($_platform as $item) {
            $platform_no[$item->id]=$item->name;
        }
        // 尊享
        if ($_GET['deal_type'] == 1) {

            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = intval($_GET['user_id']);
                $where  .= " AND debt.user_id = {$user_id} ";
            }
            // 校验债转编号
            if (!empty($_GET['serial_number'])) {
                $serial_number = trim($_GET['serial_number']);
                $where        .= " AND debt.serial_number = '{$serial_number}' ";
            }
            // 校验项目ID
            if (!empty($_GET['borrow_id'])) {
                $borrow_id = intval($_GET['borrow_id']);
                $where    .= " AND debt.borrow_id = {$borrow_id} ";
            }
            // 校验投资记录ID
            if (!empty($_GET['tender_id'])) {
                $tender_id = intval($_GET['tender_id']);
                $where    .= " AND debt.tender_id = {$tender_id} ";
            }
            // 校验转让状态
            if (!empty($_GET['status'])) {
                $sta = intval($_GET['status']);
                $where .= " AND debt.status = {$sta} ";
            }
            // 校验项目名称
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验用户手机号
            if (!empty($_GET['mobile'])) {
                $mobile = trim($_GET['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.mobile = '{$mobile}' ";
            }
            // 校验债转类型
            if (!empty($_GET['debt_src'])) {
                $d_src    = intval($_GET['debt_src']);
                $where .= " AND debt.debt_src = {$d_src} ";
            }
            // 校验兑换平台
            if (!empty($_GET['platform_no']) && $_GET['debt_src'] == 1) {
                $p_no   = intval($_GET['platform_no']);
                $where .= " AND debt.platform_no = {$p_no} ";
            }
            // 校验消费专区ID
            if (!empty($_GET['channel_id']) && $_GET['debt_src'] == 1) {
                $c_id   = intval($_GET['channel_id']);
                $where .= " AND debt.channel_id = {$c_id} ";
            }
            // 校验债权来源
            // if (!empty($_GET['load_src'])) {
            //     $l_src  = intval($_POST['load_src'])-1;
            //     $where .= " AND debt.load_src = {$l_src} ";
            // }
            // 校验借款方名称
            if (!empty($_GET['company'])) {
                $company = trim($_GET['company']);
                $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $com_arr = array();
                if ($com_a) {
                    foreach ($com_a as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if ($com_b) {
                    foreach ($com_b as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if (!empty($com_arr)) {
                    $com_str = implode(',', $com_arr);
                    $where  .= " AND deal.user_id IN ({$com_str}) ";
                } else {
                    $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                    $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($user_id_arr) {
                        $user_id_str = implode(',', $user_id_arr);
                        $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                    } else {
                        $where      .= " AND deal.user_id = '' ";
                    }
                }
            }

            //咨询方查询
            if (!empty($_GET['advisory'])) {
                $where .= $this->getAdvisoryConditions($_GET['advisory'], 1);
            }

            // 校验受让人ID
            if (!empty($_GET['t_user_id'])) {
                $t_user_id = intval($_GET['t_user_id']);
                $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($t_user_id) {
                    $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                    $debt_id_res = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验受让人手机号
            if (!empty($_GET['t_mobile'])) {
                $t_mobile = trim($_GET['t_mobile']);
                $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if ($t_user_id) {
                    $t_user_id_str = implode(',', $t_user_id);
                    $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                    $debt_id_res = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验转让完成时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start'].' 00:00:00');
                $where .= " AND debt.successtime >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end'].' 23:59:59');
                $where .= " AND debt.successtime <= {$end} ";
            }
            //后台用户
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            if (!empty($adminUserInfo['username'])) {
                if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                    if ($adminUserInfo['user_type'] == 2) {
                        $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                        if (!empty($deallist)) {
                            $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                            $where .= " AND deal.id IN({$dealIds})";
                        } else {
                            $where .= " AND deal.id < 0";
                        }
                    }
                }
            }
            // 查询数据总量
            if ($condition) {
                $redis_time = 86400;
                $redis_key  = 'XF_Debt_List_Count_ZX_Condition_'.$condition['id'];
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                    $con_data_str = implode(',', $con_data);
                    $where .= " AND debt.id IN ({$con_data_str}) ";
                } else {
                    if ($con_data) {
                        $count    = 0;
                        $con_page = ceil(count($con_data) / 1000);
                        for ($i = 0; $i < $con_page; $i++) {
                            $con_data_arr = array();
                            for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                                if (!empty($con_data[$j])) {
                                    $con_data_arr[] = $con_data[$j];
                                }
                            }
                            $con_data_str = implode(',', $con_data_arr);
                            $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                            $sql = "SELECT count(debt.id) AS count 
                                    FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                                    LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                                    LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                            $count_con = Yii::app()->fdb->createCommand($sql)->queryScalar();
                            $count += $count_con;
                        }
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                        $set = Yii::app()->rcache->set($redis_key, $count, $redis_time);
                        if (!$set) {
                            Yii::log("{$redis_key} redis count set error", "error");
                        }
                    } else {
                        $where .= " AND debt.id = '' ";
                    }
                }
            } else {
                $sql = "SELECT count(debt.id) AS count 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src, deal.name, user.real_name, user.mobile , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.user_id ASC , debt.addtime DESC LIMIT {$pass} , 500 ";
                $list = Yii::app()->fdb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                // $platform_no[1] = '有解';
                // $platform_no[2] = '悠融优选';

                $debt_id_arr = array();
                foreach ($list as $key => $value) {
                    $list[$key]['mobile']  = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    // $list[$key]['mobile']  = $this->strEncrypt($value['mobile'] , 3 , 4);
                    $list[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $list[$key]['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $list[$key]['successtime'] = '——';
                    }
                    // $list[$key]['money']           = number_format($value['money'] , 2 , '.' , ',');
                    // $list[$key]['sold_money']      = number_format($value['sold_money'] , 2 , '.' , ',');
                    // $list[$key]['deal_load_money'] = number_format($value['deal_load_money'] , 2 , '.' , ',');
                    $list[$key]['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $list[$key]['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $list[$key]['platform_no'] = '——';
                    }
                    $list[$key]['debt_src']        = $debt_src[$value['debt_src']];

                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT dt.debt_id , u.id , u.real_name , u.mobile , dt.new_tender_id , dt.status FROM firstp2p_debt_tender AS dt INNER JOIN firstp2p_user AS u ON dt.user_id = u.id AND dt.debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',', $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM firstp2p_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                foreach ($list as $key => $value) {
                    if (!empty($debt_tender[$value['id']])) {
                        $value['t_user_id']    = $debt_tender[$value['id']]['id'];
                        $value['t_real_name']  = $debt_tender[$value['id']]['real_name'];
                        $value['t_mobile']     = GibberishAESUtil::dec($debt_tender[$value['id']]['mobile'], Yii::app()->c->idno_key); // 手机号解密
                        // $value['t_mobile']     = $this->strEncrypt($value['t_mobile'] , 3 , 4);
                        $value['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $value['t_user_id']    = '——';
                        $value['t_real_name']  = '——';
                        $value['t_mobile']     = '——';
                        $value['oss_download'] = '';
                    }
                    $value['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);

                    $listInfo[] = $value;
                }
            }

            // 普惠供应链
        } elseif ($_GET['deal_type'] == 2) {

            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = intval($_GET['user_id']);
                $where  .= " AND debt.user_id = {$user_id} ";
            }
            // 校验债转编号
            if (!empty($_GET['serial_number'])) {
                $serial_number = trim($_GET['serial_number']);
                $where        .= " AND debt.serial_number = '{$serial_number}' ";
            }
            // 校验用户手机号
            if (!empty($_GET['mobile'])) {
                $mobile      = trim($_GET['mobile']);
                $mobile      = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql         = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                $user_id     = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND debt.user_id = {$user_id} ";
                } else {
                    $where .= " AND debt.user_id is NULL ";
                }
            }
            // 校验项目ID
            if (!empty($_GET['borrow_id'])) {
                $borrow_id = intval($_GET['borrow_id']);
                $where    .= " AND debt.borrow_id = {$borrow_id} ";
            }
            // 校验投资记录ID
            if (!empty($_GET['tender_id'])) {
                $tender_id = intval($_GET['tender_id']);
                $where    .= " AND debt.tender_id = {$tender_id} ";
            }
            // 校验转让状态
            if (!empty($_GET['status'])) {
                $sta    = intval($_GET['status']);
                $where .= " AND debt.status = {$sta} ";
            }
            // 校验项目名称
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验债转类型
            if (!empty($_GET['debt_src'])) {
                $d_src  = intval($_GET['debt_src']);
                $where .= " AND debt.debt_src = {$d_src} ";
            }
            // 校验兑换平台
            if (!empty($_GET['platform_no']) && $_GET['debt_src'] == 1) {
                $p_no   = intval($_GET['platform_no']);
                $where .= " AND debt.platform_no = {$p_no} ";
            }
            // 校验消费专区ID
            if (!empty($_GET['channel_id']) && $_GET['debt_src'] == 1) {
                $c_id   = intval($_GET['channel_id']);
                $where .= " AND debt.channel_id = {$c_id} ";
            }
            // 校验债权来源
            // if (!empty($_GET['load_src'])) {
            //     $l_src  = intval($_POST['load_src'])-1;
            //     $where .= " AND debt.load_src = {$l_src} ";
            // }
            // 校验借款方名称
            if (!empty($_GET['company'])) {
                $company = trim($_GET['company']);
                $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $com_arr = array();
                if ($com_a) {
                    foreach ($com_a as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if ($com_b) {
                    foreach ($com_b as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if (!empty($com_arr)) {
                    $com_str = implode(',', $com_arr);
                    $where  .= " AND deal.user_id IN ({$com_str}) ";
                } else {
                    $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                    $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($user_id_arr) {
                        $user_id_str = implode(',', $user_id_arr);
                        $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                    } else {
                        $where      .= " AND deal.user_id = '' ";
                    }
                }
            }

            //咨询方查询
            if (!empty($_GET['advisory'])) {
                $where .= $this->getAdvisoryConditions($_GET['advisory'], 2);
            }
            // 校验受让人ID
            if (!empty($_GET['t_user_id'])) {
                $t_user_id = intval($_GET['t_user_id']);
                $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($t_user_id) {
                    $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                    $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验受让人手机号
            if (!empty($_GET['t_mobile'])) {
                $t_mobile = trim($_GET['t_mobile']);
                $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if ($t_user_id) {
                    $t_user_id_str = implode(',', $t_user_id);
                    $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                    $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验转让完成时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start'].' 00:00:00');
                $where .= " AND debt.successtime >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end'].' 23:59:59');
                $where .= " AND debt.successtime <= {$end} ";
            }
            //后台用户
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            if (!empty($adminUserInfo['username'])) {
                if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                    if ($adminUserInfo['user_type'] == 2) {
                        $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                        if (!empty($deallist)) {
                            $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                            $where .= " AND deal.id IN({$dealIds})";
                        } else {
                            $where .= " AND deal.id < 0";
                        }
                    }
                }
            }
            // 查询数据总量
            if ($condition) {
                $redis_time = 86400;
                $redis_key  = 'XF_Debt_List_Count_PH_Condition_'.$condition['id'];
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                    $con_data_str = implode(',', $con_data);
                    $where .= " AND debt.id IN ({$con_data_str}) ";
                } else {
                    if ($con_data) {
                        $count    = 0;
                        $con_page = ceil(count($con_data) / 1000);
                        for ($i = 0; $i < $con_page; $i++) {
                            $con_data_arr = array();
                            for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                                if (!empty($con_data[$j])) {
                                    $con_data_arr[] = $con_data[$j];
                                }
                            }
                            $con_data_str = implode(',', $con_data_arr);
                            $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                            $sql = "SELECT count(debt.id) AS count 
                                    FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                                    LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                            $count_con = Yii::app()->phdb->createCommand($sql)->queryScalar();
                            $count += $count_con;
                        }
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                        $set = Yii::app()->rcache->set($redis_key, $count, $redis_time);
                        if (!$set) {
                            Yii::log("{$redis_key} redis count set error", "error");
                        }
                    } else {
                        $where .= " AND debt.id = '' ";
                    }
                }
            } else {
                $sql = "SELECT count(debt.id) AS count 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src, deal.name , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.user_id ASC , debt.addtime DESC LIMIT {$pass} , 500 ";
                $list = Yii::app()->phdb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                // $platform_no[1] = '有解';
                // $platform_no[2] = '悠融优选';

                $debt_id_arr = array();
                foreach ($list as $key => $value) {
                    $list[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $list[$key]['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $list[$key]['successtime'] = '——';
                    }
                    // $list[$key]['money']           = number_format($value['money'] , 2 , '.' , ',');
                    // $list[$key]['sold_money']      = number_format($value['sold_money'] , 2 , '.' , ',');
                    // $list[$key]['deal_load_money'] = number_format($value['deal_load_money'] , 2 , '.' , ',');
                    $list[$key]['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $list[$key]['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $list[$key]['platform_no'] = '——';
                    }
                    $list[$key]['debt_src']        = $debt_src[$value['debt_src']];

                    $user_id_arr[] = $value['user_id'];
                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT debt_id , user_id , new_tender_id , status FROM firstp2p_debt_tender WHERE debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            $user_id_arr[] = $value['user_id'];
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',', $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM firstp2p_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                $user_id_str = implode(',', $user_id_arr);
                $user_id_res = array();
                if ($user_id_str) {
                    $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                }
                foreach ($user_id_res as $key => $value) {
                    $temp['real_name'] = $value['real_name'];
                    $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    // $temp['mobile']    = $this->strEncrypt($temp['mobile'] , 3 , 4);

                    $user_id_data[$value['id']] = $temp;
                }
                foreach ($list as $key => $value) {
                    $value['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                    $value['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                    if (!empty($debt_tender[$value['id']])) {
                        $value['t_user_id']    = $debt_tender[$value['id']]['user_id'];
                        $value['t_real_name']  = $user_id_data[$debt_tender[$value['id']]['user_id']]['real_name'];
                        $value['t_mobile']     = $user_id_data[$debt_tender[$value['id']]['user_id']]['mobile'];
                        $value['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $value['t_user_id']    = '——';
                        $value['t_real_name']  = '——';
                        $value['t_mobile']     = '——';
                        $value['oss_download'] = '';
                    }
                    $value['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);

                    $listInfo[] = $value;
                }
            }

            // 工场微金 智多新 交易所
        } elseif (in_array($_GET['deal_type'], [3, 4, 5])) {
            $where = " WHERE debt.platform_id = {$_GET['deal_type']} ";
            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = intval($_GET['user_id']);
                $where  .= " AND debt.user_id = {$user_id} ";
            }
            // 校验债转编号
            if (!empty($_GET['serial_number'])) {
                $serial_number = trim($_GET['serial_number']);
                $where        .= " AND debt.serial_number = '{$serial_number}' ";
            }
            // 校验用户手机号
            if (!empty($_GET['mobile'])) {
                $mobile      = trim($_GET['mobile']);
                $mobile      = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql         = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                $user_id     = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND debt.user_id = {$user_id} ";
                } else {
                    $where .= " AND debt.user_id is NULL ";
                }
            }
            // 校验项目ID
            if (!empty($_GET['borrow_id'])) {
                $borrow_id = intval($_GET['borrow_id']);
                $where    .= " AND debt.borrow_id = {$borrow_id} ";
            }
            // 校验投资记录ID
            if (!empty($_GET['tender_id'])) {
                $tender_id = intval($_GET['tender_id']);
                $where    .= " AND debt.tender_id = {$tender_id} ";
            }
            // 校验转让状态
            if (!empty($_GET['status'])) {
                $sta    = intval($_GET['status']);
                $where .= " AND debt.status = {$sta} ";
            }
            // 校验项目名称
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验债转类型
            if (!empty($_GET['debt_src'])) {
                $d_src  = intval($_GET['debt_src']);
                $where .= " AND debt.debt_src = {$d_src} ";
            }
            // 校验兑换平台
            if (!empty($_GET['platform_no']) && $_GET['debt_src'] == 1) {
                $p_no   = intval($_GET['platform_no']);
                $where .= " AND debt.platform_no = {$p_no} ";
            }
            // 校验消费专区ID
            if (!empty($_GET['channel_id']) && $_GET['debt_src'] == 1) {
                $c_id   = intval($_GET['channel_id']);
                $where .= " AND debt.channel_id = {$c_id} ";
            }
            // 校验债权来源
            // if (!empty($_GET['load_src'])) {
            //     $l_src  = intval($_POST['load_src'])-1;
            //     $where .= " AND debt.load_src = {$l_src} ";
            // }
            // 校验借款方名称
            if (!empty($_GET['company'])) {
                $company = trim($_GET['company']);
                $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $com_arr = array();
                if ($com_a) {
                    foreach ($com_a as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if ($com_b) {
                    foreach ($com_b as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if (!empty($com_arr)) {
                    $com_str = implode(',', $com_arr);
                    $where  .= " AND deal.user_id IN ({$com_str}) ";
                } else {
                    $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                    $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($user_id_arr) {
                        $user_id_str = implode(',', $user_id_arr);
                        $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                    } else {
                        $where      .= " AND deal.user_id = '' ";
                    }
                }
            }

            //咨询方查询
            if (!empty($_GET['advisory'])) {
                $advisory = trim($_GET['advisory']);
                $sql = "SELECT id FROM offline_deal_agency WHERE name = '{$name}' ";
                $advisory_list = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                if ($advisory_list) {
                    $adv_arr = implode(',', $advisory_list);
                    $where  .= " AND deal.advisory_id IN ({$adv_arr}) ";
                } else {
                    $where  .= " AND deal.advisory_id IS NULL ";
                }
            }
            // 校验受让人ID
            if (!empty($_GET['t_user_id'])) {
                $t_user_id = intval($_GET['t_user_id']);
                $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($t_user_id) {
                    $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                    $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验受让人手机号
            if (!empty($_GET['t_mobile'])) {
                $t_mobile = trim($_GET['t_mobile']);
                $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if ($t_user_id) {
                    $t_user_id_str = implode(',', $t_user_id);
                    $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                    $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验转让完成时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start'].' 00:00:00');
                $where .= " AND debt.successtime >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end'].' 23:59:59');
                $where .= " AND debt.successtime <= {$end} ";
            }
            //后台用户
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            if (!empty($adminUserInfo['username'])) {
                if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                    if ($adminUserInfo['user_type'] == 2) {
                        $deallist = Yii::app()->offlinedb->createCommand("SELECT offline_deal.id deal_id from offline_deal_agency LEFT JOIN offline_deal ON offline_deal.advisory_id = offline_deal_agency.id WHERE offline_deal_agency.name = '{$adminUserInfo['realname']}' and offline_deal_agency.is_effect = 1 and offline_deal.id > 0")->queryAll();
                        if (!empty($deallist)) {
                            $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                            $where .= " AND deal.id IN({$dealIds})";
                        } else {
                            $where .= " AND deal.id < 0";
                        }
                    }
                }
            }
            // 查询数据总量
            if ($condition) {
                $redis_time = 86400;
                if ($_GET['deal_type'] == 3) {
                    $redis_key = 'XF_Debt_List_Count_GCWJ_Condition_'.$condition['id'];
                } elseif ($_GET['deal_type'] == 4) {
                    $redis_key = 'XF_Debt_List_Count_ZDX_Condition_'.$condition['id'];
                } elseif ($_GET['deal_type'] == 5) {
                    $redis_key = 'XF_Debt_List_Count_JYS_Condition_'.$condition['id'];
                }
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                    $con_data_str = implode(',', $con_data);
                    $where .= " AND debt.id IN ({$con_data_str}) ";
                } else {
                    if ($con_data) {
                        $count    = 0;
                        $con_page = ceil(count($con_data) / 1000);
                        for ($i = 0; $i < $con_page; $i++) {
                            $con_data_arr = array();
                            for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                                if (!empty($con_data[$j])) {
                                    $con_data_arr[] = $con_data[$j];
                                }
                            }
                            $con_data_str = implode(',', $con_data_arr);
                            $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                            $sql = "SELECT count(debt.id) AS count 
                                    FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                                    LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                            $count_con = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
                            $count += $count_con;
                        }
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                        $set = Yii::app()->rcache->set($redis_key, $count, $redis_time);
                        if (!$set) {
                            Yii::log("{$redis_key} redis count set error", "error");
                        }
                    } else {
                        $where .= " AND debt.id = '' ";
                    }
                }
            } else {
                $sql = "SELECT count(debt.id) AS count 
                        FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                $count = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src, deal.name , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM offline_debt AS debt LEFT JOIN offline_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN offline_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.user_id ASC , debt.addtime DESC LIMIT {$pass} , 500 ";
                $list = Yii::app()->offlinedb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                // $platform_no[1] = '有解';
                // $platform_no[2] = '悠融优选';

                $debt_id_arr = array();
                foreach ($list as $key => $value) {
                    $list[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $list[$key]['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $list[$key]['successtime'] = '——';
                    }
                    // $list[$key]['money']           = number_format($value['money'] , 2 , '.' , ',');
                    // $list[$key]['sold_money']      = number_format($value['sold_money'] , 2 , '.' , ',');
                    // $list[$key]['deal_load_money'] = number_format($value['deal_load_money'] , 2 , '.' , ',');
                    $list[$key]['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $list[$key]['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $list[$key]['platform_no'] = '——';
                    }
                    $list[$key]['debt_src']        = $debt_src[$value['debt_src']];

                    $user_id_arr[] = $value['user_id'];
                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT debt_id , user_id , new_tender_id , status FROM offline_debt_tender WHERE debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            $user_id_arr[] = $value['user_id'];
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',', $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM offline_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                $user_id_str = implode(',', $user_id_arr);
                $user_id_res = array();
                if ($user_id_str) {
                    $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                }
                foreach ($user_id_res as $key => $value) {
                    $temp['real_name'] = $value['real_name'];
                    $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    // $temp['mobile']    = $this->strEncrypt($temp['mobile'] , 3 , 4);

                    $user_id_data[$value['id']] = $temp;
                }
                foreach ($list as $key => $value) {
                    $value['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                    $value['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                    if (!empty($debt_tender[$value['id']])) {
                        $value['t_user_id']    = $debt_tender[$value['id']]['user_id'];
                        $value['t_real_name']  = $user_id_data[$debt_tender[$value['id']]['user_id']]['real_name'];
                        $value['t_mobile']     = $user_id_data[$debt_tender[$value['id']]['user_id']]['mobile'];
                        $value['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $value['t_user_id']    = '——';
                        $value['t_real_name']  = '——';
                        $value['t_mobile']     = '——';
                        $value['oss_download'] = '';
                    }
                    $value['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);

                    $listInfo[] = $value;
                }
            }
        }
        if ($_GET['deal_type'] == 1) {
            $name = '化债数据查询 尊享 '.date("Y年m月d日 H时i分s秒", time()).'.csv';
        } elseif ($_GET['deal_type'] == 2) {
            $name = '化债数据查询 普惠 '.date("Y年m月d日 H时i分s秒", time()).'.csv';
        } elseif ($_GET['deal_type'] == 3) {
            $name = '化债数据查询 工场微金 '.date("Y年m月d日 H时i分s秒", time()).'.csv';
        } elseif ($_GET['deal_type'] == 4) {
            $name = '化债数据查询 智多新 '.date("Y年m月d日 H时i分s秒", time()).'.csv';
        } elseif ($_GET['deal_type'] == 5) {
            $name = '化债数据查询 交易所 '.date("Y年m月d日 H时i分s秒", time()).'.csv';
        }
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "债转ID,债转编号,转让人ID,转让人姓名,转让人手机号,借款编号,借款标题,投资记录ID,投资金额,发起债转金额,已转出金额,折扣,转让状态,债转类型,兑换平台,债转合同编号,受让人ID,受让人姓名,受让人手机号,发起时间,转让完成时间,债转合同地址\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['serial_number']},{$value['user_id']},{$value['real_name']},{$value['mobile']},{$value['borrow_id']},{$value['name']},{$value['tender_id']},{$value['deal_load_money']},{$value['money']},{$value['sold_money']},{$value['discount']},{$value['status']},{$value['debt_src']},{$value['platform_no']},{$value['contract_number']},{$value['t_user_id']},{$value['t_real_name']},{$value['t_mobile']},{$value['addtime']},{$value['successtime']},{$value['oss_download']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }

        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$name);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $data;
        if ($condition) {
            $redis_time = 3600;
            if ($_GET['deal_type'] == 1) {
                $redis_key = 'XF_Debt_List_Download_ZX_Condition_'.$condition['id'];
            } elseif ($_GET['deal_type'] == 2) {
                $redis_key = 'XF_Debt_List_Download_PH_Condition_'.$condition['id'];
            } elseif ($_GET['deal_type'] == 3) {
                $redis_key = 'XF_Debt_List_Download_GCWJ_Condition_'.$condition['id'];
            } elseif ($_GET['deal_type'] == 4) {
                $redis_key = 'XF_Debt_List_Download_ZDX_Condition_'.$condition['id'];
            } elseif ($_GET['deal_type'] == 5) {
                $redis_key = 'XF_Debt_List_Download_JYS_Condition_'.$condition['id'];
            }
            $set = Yii::app()->rcache->set($redis_key, date('Y-m-d H:i:s', time()), $redis_time);
            if (!$set) {
                Yii::log("{$redis_key} redis download set error", "error");
            }
        }
    }

    /**
     * 债转记录 列表 尊享 普惠
     */
    public function actionGetDebtListZXPH()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = " WHERE deal.is_zdx = 0 ";
            if (!empty($_POST['condition_id'])) {
                $sql = "SELECT * FROM xf_debt_list_condition WHERE id = '{$_POST['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    if ($condition['true_count'] > 500) {
                        header("Content-type:application/json; charset=utf-8");
                        $result_data['data']  = array();
                        $result_data['count'] = 0;
                        $result_data['code']  = 1;
                        $result_data['info']  = '批量条件超过500行，暂不支持列表查询！';
                        echo exit(json_encode($result_data));
                    }
                    $con_data = json_decode($condition['data_json'], true);
                    if ($condition['platform']) {
                        $_POST['deal_type'] = $condition['platform'];
                    }
                }
            }
            $sql = "select id,name from xf_debt_exchange_platform";
            $_platform = XfDebtExchangePlatform::model()->findAllBySql($sql);
            foreach ($_platform as $item) {
                $platform_no[$item->id]=$item->name;
            }
            // 尊享
            if ($_POST['deal_type'] == 1) {

                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where  .= " AND debt.user_id = {$user_id} ";
                }
                // 校验债转编号
                if (!empty($_POST['serial_number'])) {
                    $serial_number = trim($_POST['serial_number']);
                    $where        .= " AND debt.serial_number = '{$serial_number}'";
                }
                // 校验项目ID
                if (!empty($_POST['borrow_id'])) {
                    $borrow_id = intval($_POST['borrow_id']);
                    $where    .= " AND debt.borrow_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['tender_id'])) {
                    $tender_id = intval($_POST['tender_id']);
                    $where    .= " AND debt.tender_id = {$tender_id} ";
                }
                // 校验转让状态
                if (!empty($_POST['status'])) {
                    $sta = intval($_POST['status']);
                    $where .= " AND debt.status = {$sta} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile = trim($_POST['mobile']);
                    $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $where .= " AND user.mobile = '{$mobile}' ";
                }
                // 校验债转类型
                if (!empty($_POST['debt_src'])) {
                    $d_src  = intval($_POST['debt_src']);
                    $where .= " AND debt.debt_src = {$d_src} ";
                }
                // 校验兑换平台
                if (!empty($_POST['platform_no']) && $_POST['debt_src'] == 1) {
                    $p_no   = intval($_POST['platform_no']);
                    $where .= " AND debt.platform_no = {$p_no} ";
                }
                // 校验消费专区ID
                if (!empty($_POST['channel_id']) && $_POST['debt_src'] == 1) {
                    $c_id   = intval($_POST['channel_id']);
                    $where .= " AND debt.channel_id = {$c_id} ";
                }
                // 校验债权来源
                // if (!empty($_POST['load_src'])) {
                //     $l_src  = intval($_POST['load_src'])-1;
                //     $where .= " AND debt.load_src = {$l_src} ";
                // }
                // 校验借款方名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                    $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                    $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $com_arr = array();
                    if ($com_a) {
                        foreach ($com_a as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if ($com_b) {
                        foreach ($com_b as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if (!empty($com_arr)) {
                        $com_str = implode(',', $com_arr);
                        $where  .= " AND deal.user_id IN ({$com_str}) ";
                    } else {
                        $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                        $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($user_id_arr) {
                            $user_id_str = implode(',', $user_id_arr);
                            $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                        } else {
                            $where      .= " AND deal.user_id = '' ";
                        }
                    }
                }

                //咨询方查询
                if (!empty($_POST['advisory'])) {
                    $where .= $this->getAdvisoryConditions($_POST['advisory'], 1);
                }

                // 校验受让人ID
                if (!empty($_POST['t_user_id'])) {
                    $t_user_id = intval($_POST['t_user_id']);
                    $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验受让人手机号
                if (!empty($_POST['t_mobile'])) {
                    $t_mobile = trim($_POST['t_mobile']);
                    $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                    // var_dump($t_mobile);exit;
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($t_user_id) {
                        $t_user_id_str = implode(',', $t_user_id);
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                        $debt_id_res = Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验转让完成时间
                if (!empty($_POST['start'])) {
                    $start  = strtotime($_POST['start'].' 00:00:00');
                    $where .= " AND debt.successtime >= {$start} ";
                }
                if (!empty($_POST['end'])) {
                    $end    = strtotime($_POST['end'].' 23:59:59');
                    $where .= " AND debt.successtime <= {$end} ";
                }
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
                //后台用户
                $adminUserInfo  = \Yii::app()->user->getState('_user');
                if (!empty($adminUserInfo['username'])) {
                    if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                        if ($adminUserInfo['user_type'] == 2) {
                            $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                            if (!empty($deallist)) {
                                $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                                $where .= " AND deal.id IN({$dealIds})";
                            } else {
                                $where .= " AND deal.id < 0";
                            }
                        }
                    }
                }
                // 查询数据总量
                if ($condition) {
                    $redis_time = 86400;
                    $redis_key  = 'XF_Debt_List_Count_ZX_Condition_'.$condition['id'];
                    $redis_val  = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                    } else {
                        if ($con_data) {
                            $count    = 0;
                            $con_page = ceil(count($con_data) / 1000);
                            for ($i = 0; $i < $con_page; $i++) {
                                $con_data_arr = array();
                                for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                                    if (!empty($con_data[$j])) {
                                        $con_data_arr[] = $con_data[$j];
                                    }
                                }
                                $con_data_str = implode(',', $con_data_arr);
                                $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                                $sql = "SELECT count(debt.id) AS count 
                                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                                        LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                                $count_con = Yii::app()->fdb->createCommand($sql)->queryScalar();
                                $count += $count_con;
                            }
                            $con_data_str = implode(',', $con_data);
                            $where .= " AND debt.id IN ({$con_data_str}) ";
                            $set = Yii::app()->rcache->set($redis_key, $count, $redis_time);
                            if (!$set) {
                                Yii::log("{$redis_key} redis count set error", "error");
                            }
                        } else {
                            $where .= " AND debt.id = '' ";
                        }
                    }
                } else {
                    $sql = "SELECT count(debt.id) AS count 
                            FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                            LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                            LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                    $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
                }
                if ($count == 0) {
                    header("Content-type:application/json; charset=utf-8");
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 0;
                    $result_data['info']  = '查询成功';
                    echo exit(json_encode($result_data));
                }
                // 查询数据
                $sql = "SELECT debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src , deal.name, user.real_name, user.mobile , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.id DESC ";
                $pass       = ($page - 1) * $limit;
                $sql       .= " LIMIT {$pass} , {$limit} ";
                if (strlen($sql) > 1048576) {
                    header("Content-type:application/json; charset=utf-8");
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 1;
                    $result_data['info']  = '批量条件过多导致SQL长度超过1M，暂不支持列表查询！';
                    echo exit(json_encode($result_data));
                }
                $list       = Yii::app()->fdb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                // $platform_no[1] = '有解';
                // $platform_no[2] = '悠融优选';

                // $load_src[0] = '常规债权';
                // $load_src[1] = '一键下车退回债权';
                // $load_src[2] = '权益兑换退回债权';

                $debt_id_arr = array();
                //获取当前账号所有子权限
                $authList = \Yii::app()->user->getState('_auth');
                $info_status = 0;
                if (!empty($authList) && strstr($authList, '/user/Debt/DebtInfo') || empty($authList)) {
                    $info_status = 1;
                }
                foreach ($list as $key => $value) {
                    $value['mobile']  = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    $value['mobile']  = $this->strEncrypt($value['mobile'], 3, 4);
                    $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $value['successtime'] = '——';
                    }
                    $value['money']           = number_format($value['money'], 2, '.', ',');
                    $value['sold_money']      = number_format($value['sold_money'], 2, '.', ',');
                    $value['deal_load_money'] = number_format($value['deal_load_money'], 2, '.', ',');
                    $value['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $value['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $value['platform_no'] = '——';
                    }
                    $value['debt_src']        = $debt_src[$value['debt_src']];
                    // $value['load_src']        = $load_src[$value['load_src']];
                    $value['info_status']     = $info_status;
                    
                    $listInfo[] = $value;

                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT dt.debt_id , u.id , u.real_name , u.mobile , dt.new_tender_id , dt.status FROM firstp2p_debt_tender AS dt INNER JOIN firstp2p_user AS u ON dt.user_id = u.id AND dt.debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',', $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM firstp2p_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                foreach ($listInfo as $key => $value) {
                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']    = $debt_tender[$value['id']]['id'];
                        $listInfo[$key]['t_real_name']  = $debt_tender[$value['id']]['real_name'];
                        $listInfo[$key]['t_mobile']     = GibberishAESUtil::dec($debt_tender[$value['id']]['mobile'], Yii::app()->c->idno_key); // 手机号解密
                        $listInfo[$key]['t_mobile']     = $this->strEncrypt($listInfo[$key]['t_mobile'], 3, 4);
                        $listInfo[$key]['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $listInfo[$key]['t_user_id']    = '——';
                        $listInfo[$key]['t_real_name']  = '——';
                        $listInfo[$key]['t_mobile']     = '——';
                        $listInfo[$key]['oss_download'] = '';
                    }

                    $listInfo[$key]['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);
                }

                // 普惠供应链
            } elseif ($_POST['deal_type'] == 2) {

                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where  .= " AND debt.user_id = {$user_id} ";
                }
                // 校验债转编号
                if (!empty($_POST['serial_number'])) {
                    $serial_number = trim($_POST['serial_number']);
                    $where        .= " AND debt.serial_number = '{$serial_number}' ";
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile      = trim($_POST['mobile']);
                    $mobile      = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql         = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                    $user_id     = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($user_id) {
                        $where .= " AND debt.user_id = {$user_id} ";
                    } else {
                        $where .= " AND debt.user_id is NULL ";
                    }
                }
                // 校验项目ID
                if (!empty($_POST['borrow_id'])) {
                    $borrow_id = intval($_POST['borrow_id']);
                    $where    .= " AND debt.borrow_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['tender_id'])) {
                    $tender_id = intval($_POST['tender_id']);
                    $where    .= " AND debt.tender_id = {$tender_id} ";
                }
                // 校验转让状态
                if (!empty($_POST['status'])) {
                    $sta    = intval($_POST['status']);
                    $where .= " AND debt.status = {$sta} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }
                // 校验债转类型
                if (!empty($_POST['debt_src'])) {
                    $d_src  = intval($_POST['debt_src']);
                    $where .= " AND debt.debt_src = {$d_src} ";
                }
                // 校验兑换平台
                if (!empty($_POST['platform_no']) && $_POST['debt_src'] == 1) {
                    $p_no   = intval($_POST['platform_no']);
                    $where .= " AND debt.platform_no = {$p_no} ";
                }
                // 校验消费专区ID
                if (!empty($_POST['channel_id']) && $_POST['debt_src'] == 1) {
                    $c_id   = intval($_POST['channel_id']);
                    $where .= " AND debt.channel_id = {$c_id} ";
                }
                // 校验债权来源
                // if (!empty($_POST['load_src'])) {
                //     $l_src  = intval($_POST['load_src'])-1;
                //     $where .= " AND debt.load_src = {$l_src} ";
                // }
                // 校验借款方名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                    $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                    $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $com_arr = array();
                    if ($com_a) {
                        foreach ($com_a as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if ($com_b) {
                        foreach ($com_b as $key => $value) {
                            $com_arr[] = $value;
                        }
                    }
                    if (!empty($com_arr)) {
                        $com_str = implode(',', $com_arr);
                        $where  .= " AND deal.user_id IN ({$com_str}) ";
                    } else {
                        $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                        $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($user_id_arr) {
                            $user_id_str = implode(',', $user_id_arr);
                            $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                        } else {
                            $where      .= " AND deal.user_id = '' ";
                        }
                    }
                }

                //咨询方查询
                if (!empty($_POST['advisory'])) {
                    $where .= $this->getAdvisoryConditions($_POST['advisory'], 2);
                }
                // 校验受让人ID
                if (!empty($_POST['t_user_id'])) {
                    $t_user_id = intval($_POST['t_user_id']);
                    $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验受让人手机号
                if (!empty($_POST['t_mobile'])) {
                    $t_mobile = trim($_POST['t_mobile']);
                    $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($t_user_id) {
                        $t_user_id_str = implode(',', $t_user_id);
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                        $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验转让完成时间
                if (!empty($_POST['start'])) {
                    $start  = strtotime($_POST['start'].' 00:00:00');
                    $where .= " AND debt.successtime >= {$start} ";
                }
                if (!empty($_POST['end'])) {
                    $end    = strtotime($_POST['end'].' 23:59:59');
                    $where .= " AND debt.successtime <= {$end} ";
                }
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
                //后台用户
                $adminUserInfo  = \Yii::app()->user->getState('_user');
                if (!empty($adminUserInfo['username'])) {
                    if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                        if ($adminUserInfo['user_type'] == 2) {
                            $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                            if (!empty($deallist)) {
                                $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                                $where .= " AND deal.id IN({$dealIds})";
                            } else {
                                //不是超级管理员并且没有$dealIds
                                $where .= " AND deal.id < 0";
                            }
                        }
                    }
                }
                // 查询数据总量
                if ($condition) {
                    $redis_time = 86400;
                    $redis_key  = 'XF_Debt_List_Count_PH_Condition_'.$condition['id'];
                    $redis_val  = Yii::app()->rcache->get($redis_key);
                    if ($redis_val) {
                        $count = $redis_val;
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                    } else {
                        if ($con_data) {
                            $count    = 0;
                            $con_page = ceil(count($con_data) / 1000);
                            for ($i = 0; $i < $con_page; $i++) {
                                $con_data_arr = array();
                                for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                                    if (!empty($con_data[$j])) {
                                        $con_data_arr[] = $con_data[$j];
                                    }
                                }
                                $con_data_str = implode(',', $con_data_arr);
                                $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                                $sql = "SELECT count(debt.id) AS count 
                                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id  
                                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                                $count_con = Yii::app()->phdb->createCommand($sql)->queryScalar();
                                $count += $count_con;
                            }
                            $con_data_str = implode(',', $con_data);
                            $where .= " AND debt.id IN ({$con_data_str}) ";
                            $set = Yii::app()->rcache->set($redis_key, $count, $redis_time);
                            if (!$set) {
                                Yii::log("{$redis_key} redis count set error", "error");
                            }
                        } else {
                            $where .= " AND debt.id = '' ";
                        }
                    }
                } else {
                    $sql = "SELECT count(debt.id) AS count 
                            FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                            LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                    $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
                }
                if ($count == 0) {
                    header("Content-type:application/json; charset=utf-8");
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 0;
                    $result_data['info']  = '查询成功';
                    echo exit(json_encode($result_data));
                }
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src , deal.name , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id  
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.id DESC ";
                $pass       = ($page - 1) * $limit;
                $sql       .= " LIMIT {$pass} , {$limit} ";
                if (strlen($sql) > 1048576) {
                    header("Content-type:application/json; charset=utf-8");
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 1;
                    $result_data['info']  = '批量条件过多导致SQL长度超过1M，暂不支持列表查询！';
                    echo exit(json_encode($result_data));
                }
                $list       = Yii::app()->phdb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                // $platform_no[1] = '有解';
                // $platform_no[2] = '悠融优选';

                // $load_src[0] = '常规债权';
                // $load_src[1] = '一键下车退回债权';
                // $load_src[2] = '权益兑换退回债权';

                $debt_id_arr = array();
                //获取当前账号所有子权限
                $authList = \Yii::app()->user->getState('_auth');
                $info_status = 0;
                if (!empty($authList) && strstr($authList, '/user/Debt/DebtInfo') || empty($authList)) {
                    $info_status = 1;
                }
                foreach ($list as $key => $value) {
                    $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $value['successtime'] = '——';
                    }
                    $value['money']           = number_format($value['money'], 2, '.', ',');
                    $value['sold_money']      = number_format($value['sold_money'], 2, '.', ',');
                    $value['deal_load_money'] = number_format($value['deal_load_money'], 2, '.', ',');
                    $value['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $value['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $value['platform_no'] = '——';
                    }
                    $value['debt_src']        = $debt_src[$value['debt_src']];
                    // $value['load_src']        = $load_src[$value['load_src']];
                    $value['info_status']     = $info_status;

                    $listInfo[]    = $value;
                    $user_id_arr[] = $value['user_id'];
                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT debt_id , user_id , new_tender_id , status FROM firstp2p_debt_tender WHERE debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            $user_id_arr[] = $value['user_id'];
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',', $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM firstp2p_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                $user_id_str = implode(',', $user_id_arr);
                $user_id_res = array();
                if ($user_id_str) {
                    $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                }
                foreach ($user_id_res as $key => $value) {
                    $temp['real_name'] = $value['real_name'];
                    $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    $temp['mobile']    = $this->strEncrypt($temp['mobile'], 3, 4);

                    $user_id_data[$value['id']] = $temp;
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                    $listInfo[$key]['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']    = $debt_tender[$value['id']]['user_id'];
                        $listInfo[$key]['t_real_name']  = $user_id_data[$debt_tender[$value['id']]['user_id']]['real_name'];
                        $listInfo[$key]['t_mobile']     = $user_id_data[$debt_tender[$value['id']]['user_id']]['mobile'];
                        $listInfo[$key]['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $listInfo[$key]['t_user_id']    = '——';
                        $listInfo[$key]['t_real_name']  = '——';
                        $listInfo[$key]['t_mobile']     = '——';
                        $listInfo[$key]['oss_download'] = '';
                    }

                    $listInfo[$key]['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);
                }
            }
            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList, '/user/Debt/DebtListExcelZXPH') || empty($authList)) {
            $daochu_status = 1;
        }
        $channel_id = Yii::app()->c->channel_id;
        return $this->renderPartial('GetDebtListZXPH', array('daochu_status' => $daochu_status, 'channel_id' => $channel_id));
    }

    /**
     * 债转记录 列表 导出 尊享普惠
     */
    public function actionDebtListExcelZXPH()
    {
        set_time_limit(0);
        // 条件筛选
        $where = " WHERE deal.is_zdx = 0 ";
        if (!empty($_GET['condition_id'])) {
            $sql = "SELECT * FROM xf_debt_list_condition WHERE id = '{$_GET['condition_id']}' ";
            $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($condition) {
                $con_data = json_decode($condition['data_json'], true);
                if ($condition['platform']) {
                    $_GET['deal_type'] = $condition['platform'];
                }
                if ($_GET['deal_type'] == 1) {
                    $redis_key = 'XF_Debt_List_Download_ZX_Condition_'.$condition['id'];
                } elseif ($_GET['deal_type'] == 2) {
                    $redis_key = 'XF_Debt_List_Download_PH_Condition_'.$condition['id'];
                }
                $check = Yii::app()->rcache->get($redis_key);
                if ($check) {
                    echo '<h1>此下载地址已失效</h1>';
                    exit;
                }
            }
        }
        if ($_GET['user_id']=='' && $_GET['serial_number']=='' && $_GET['borrow_id']=='' && $_GET['tender_id']=='' && $_GET['status']=='' && $_GET['name']=='' && $_GET['mobile']=='' && $_GET['debt_src']=='' && $_GET['platform_no']=='' && $_GET['channel_id']=='' && $_GET['company']=='' && $_GET['advisory']=='' && $_GET['t_user_id']=='' && $_GET['t_mobile'] && $_GET['start']=='' && $_GET['end']=='' && $_GET['condition_id']=='') {
            echo '<h1>请输入至少一个查询条件</h1>';
            exit;
        }
        if (empty($_GET['deal_type'])) {
            $_GET['deal_type'] = 1;
        }
        $sql = "select id,name from xf_debt_exchange_platform";
        $_platform = XfDebtExchangePlatform::model()->findAllBySql($sql);
        foreach ($_platform as $item) {
            $platform_no[$item->id]=$item->name;
        }
        // 尊享
        if ($_GET['deal_type'] == 1) {

            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = intval($_GET['user_id']);
                $where  .= " AND debt.user_id = {$user_id} ";
            }
            // 校验债转编号
            if (!empty($_GET['serial_number'])) {
                $serial_number = trim($_GET['serial_number']);
                $where        .= " AND debt.serial_number = '{$serial_number}' ";
            }
            // 校验项目ID
            if (!empty($_GET['borrow_id'])) {
                $borrow_id = intval($_GET['borrow_id']);
                $where    .= " AND debt.borrow_id = {$borrow_id} ";
            }
            // 校验投资记录ID
            if (!empty($_GET['tender_id'])) {
                $tender_id = intval($_GET['tender_id']);
                $where    .= " AND debt.tender_id = {$tender_id} ";
            }
            // 校验转让状态
            if (!empty($_GET['status'])) {
                $sta = intval($_GET['status']);
                $where .= " AND debt.status = {$sta} ";
            }
            // 校验项目名称
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验用户手机号
            if (!empty($_GET['mobile'])) {
                $mobile = trim($_GET['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.mobile = '{$mobile}' ";
            }
            // 校验债转类型
            if (!empty($_GET['debt_src'])) {
                $d_src    = intval($_GET['debt_src']);
                $where .= " AND debt.debt_src = {$d_src} ";
            }
            // 校验兑换平台
            if (!empty($_GET['platform_no']) && $_GET['debt_src'] == 1) {
                $p_no   = intval($_GET['platform_no']);
                $where .= " AND debt.platform_no = {$p_no} ";
            }
            // 校验消费专区ID
            if (!empty($_GET['channel_id']) && $_GET['debt_src'] == 1) {
                $c_id   = intval($_GET['channel_id']);
                $where .= " AND debt.channel_id = {$c_id} ";
            }
            // 校验债权来源
            // if (!empty($_GET['load_src'])) {
            //     $l_src  = intval($_POST['load_src'])-1;
            //     $where .= " AND debt.load_src = {$l_src} ";
            // }
            // 校验借款方名称
            if (!empty($_GET['company'])) {
                $company = trim($_GET['company']);
                $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $com_arr = array();
                if ($com_a) {
                    foreach ($com_a as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if ($com_b) {
                    foreach ($com_b as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if (!empty($com_arr)) {
                    $com_str = implode(',', $com_arr);
                    $where  .= " AND deal.user_id IN ({$com_str}) ";
                } else {
                    $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                    $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($user_id_arr) {
                        $user_id_str = implode(',', $user_id_arr);
                        $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                    } else {
                        $where      .= " AND deal.user_id = '' ";
                    }
                }
            }

            //咨询方查询
            if (!empty($_GET['advisory'])) {
                $where .= $this->getAdvisoryConditions($_GET['advisory'], 1);
            }

            // 校验受让人ID
            if (!empty($_GET['t_user_id'])) {
                $t_user_id = intval($_GET['t_user_id']);
                $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($t_user_id) {
                    $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                    $debt_id_res = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验受让人手机号
            if (!empty($_GET['t_mobile'])) {
                $t_mobile = trim($_GET['t_mobile']);
                $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if ($t_user_id) {
                    $t_user_id_str = implode(',', $t_user_id);
                    $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                    $debt_id_res = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验转让完成时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start'].' 00:00:00');
                $where .= " AND debt.successtime >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end'].' 23:59:59');
                $where .= " AND debt.successtime <= {$end} ";
            }
            //后台用户
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            if (!empty($adminUserInfo['username'])) {
                if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                    if ($adminUserInfo['user_type'] == 2) {
                        $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                        if (!empty($deallist)) {
                            $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                            $where .= " AND deal.id IN({$dealIds})";
                        } else {
                            $where .= " AND deal.id < 0";
                        }
                    }
                }
            }
            // 查询数据总量
            if ($condition) {
                $redis_time = 86400;
                $redis_key  = 'XF_Debt_List_Count_ZX_Condition_'.$condition['id'];
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                    $con_data_str = implode(',', $con_data);
                    $where .= " AND debt.id IN ({$con_data_str}) ";
                } else {
                    if ($con_data) {
                        $count    = 0;
                        $con_page = ceil(count($con_data) / 1000);
                        for ($i = 0; $i < $con_page; $i++) {
                            $con_data_arr = array();
                            for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                                if (!empty($con_data[$j])) {
                                    $con_data_arr[] = $con_data[$j];
                                }
                            }
                            $con_data_str = implode(',', $con_data_arr);
                            $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                            $sql = "SELECT count(debt.id) AS count 
                                    FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                                    LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                                    LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                            $count_con = Yii::app()->fdb->createCommand($sql)->queryScalar();
                            $count += $count_con;
                        }
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                        $set = Yii::app()->rcache->set($redis_key, $count, $redis_time);
                        if (!$set) {
                            Yii::log("{$redis_key} redis count set error", "error");
                        }
                    } else {
                        $where .= " AND debt.id = '' ";
                    }
                }
            } else {
                $sql = "SELECT count(debt.id) AS count 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src, deal.name, user.real_name, user.mobile , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN firstp2p_user AS user ON debt.user_id = user.id 
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.user_id ASC , debt.addtime DESC LIMIT {$pass} , 500 ";
                $list = Yii::app()->fdb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                // $platform_no[1] = '有解';
                // $platform_no[2] = '悠融优选';

                $debt_id_arr = array();
                foreach ($list as $key => $value) {
                    $list[$key]['mobile']  = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    // $list[$key]['mobile']  = $this->strEncrypt($value['mobile'] , 3 , 4);
                    $list[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $list[$key]['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $list[$key]['successtime'] = '——';
                    }
                    // $list[$key]['money']           = number_format($value['money'] , 2 , '.' , ',');
                    // $list[$key]['sold_money']      = number_format($value['sold_money'] , 2 , '.' , ',');
                    // $list[$key]['deal_load_money'] = number_format($value['deal_load_money'] , 2 , '.' , ',');
                    $list[$key]['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $list[$key]['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $list[$key]['platform_no'] = '——';
                    }
                    $list[$key]['debt_src']        = $debt_src[$value['debt_src']];

                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT dt.debt_id , u.id , u.real_name , u.mobile , dt.new_tender_id , dt.status FROM firstp2p_debt_tender AS dt INNER JOIN firstp2p_user AS u ON dt.user_id = u.id AND dt.debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',', $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM firstp2p_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                foreach ($list as $key => $value) {
                    if (!empty($debt_tender[$value['id']])) {
                        $value['t_user_id']    = $debt_tender[$value['id']]['id'];
                        $value['t_real_name']  = $debt_tender[$value['id']]['real_name'];
                        $value['t_mobile']     = GibberishAESUtil::dec($debt_tender[$value['id']]['mobile'], Yii::app()->c->idno_key); // 手机号解密
                        // $value['t_mobile']     = $this->strEncrypt($value['t_mobile'] , 3 , 4);
                        $value['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $value['t_user_id']    = '——';
                        $value['t_real_name']  = '——';
                        $value['t_mobile']     = '——';
                        $value['oss_download'] = '';
                    }
                    $value['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);

                    $listInfo[] = $value;
                }
            }
            

            // 普惠供应链
        } elseif ($_GET['deal_type'] == 2) {

            // 校验用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = intval($_GET['user_id']);
                $where  .= " AND debt.user_id = {$user_id} ";
            }
            // 校验债转编号
            if (!empty($_GET['serial_number'])) {
                $serial_number = trim($_GET['serial_number']);
                $where        .= " AND debt.serial_number = '{$serial_number}' ";
            }
            // 校验用户手机号
            if (!empty($_GET['mobile'])) {
                $mobile      = trim($_GET['mobile']);
                $mobile      = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql         = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                $user_id     = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND debt.user_id = {$user_id} ";
                } else {
                    $where .= " AND debt.user_id is NULL ";
                }
            }
            // 校验项目ID
            if (!empty($_GET['borrow_id'])) {
                $borrow_id = intval($_GET['borrow_id']);
                $where    .= " AND debt.borrow_id = {$borrow_id} ";
            }
            // 校验投资记录ID
            if (!empty($_GET['tender_id'])) {
                $tender_id = intval($_GET['tender_id']);
                $where    .= " AND debt.tender_id = {$tender_id} ";
            }
            // 校验转让状态
            if (!empty($_GET['status'])) {
                $sta    = intval($_GET['status']);
                $where .= " AND debt.status = {$sta} ";
            }
            // 校验项目名称
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验债转类型
            if (!empty($_GET['debt_src'])) {
                $d_src  = intval($_GET['debt_src']);
                $where .= " AND debt.debt_src = {$d_src} ";
            }
            // 校验兑换平台
            if (!empty($_GET['platform_no']) && $_GET['debt_src'] == 1) {
                $p_no   = intval($_GET['platform_no']);
                $where .= " AND debt.platform_no = {$p_no} ";
            }
            // 校验消费专区ID
            if (!empty($_GET['channel_id']) && $_GET['debt_src'] == 1) {
                $c_id   = intval($_GET['channel_id']);
                $where .= " AND debt.channel_id = {$c_id} ";
            }
            // 校验债权来源
            // if (!empty($_GET['load_src'])) {
            //     $l_src  = intval($_POST['load_src'])-1;
            //     $where .= " AND debt.load_src = {$l_src} ";
            // }
            // 校验借款方名称
            if (!empty($_GET['company'])) {
                $company = trim($_GET['company']);
                $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                $com_a   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' ";
                $com_b   = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $com_arr = array();
                if ($com_a) {
                    foreach ($com_a as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if ($com_b) {
                    foreach ($com_b as $key => $value) {
                        $com_arr[] = $value;
                    }
                }
                if (!empty($com_arr)) {
                    $com_str = implode(',', $com_arr);
                    $where  .= " AND deal.user_id IN ({$com_str}) ";
                } else {
                    $sql         = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                    $user_id_arr =  Yii::app()->fdb->createCommand($sql)->queryColumn();
                    if ($user_id_arr) {
                        $user_id_str = implode(',', $user_id_arr);
                        $where      .= " AND deal.user_id IN ({$user_id_str}) ";
                    } else {
                        $where      .= " AND deal.user_id = '' ";
                    }
                }
            }

            //咨询方查询
            if (!empty($_GET['advisory'])) {
                $where .= $this->getAdvisoryConditions($_GET['advisory'], 2);
            }
            // 校验受让人ID
            if (!empty($_GET['t_user_id'])) {
                $t_user_id = intval($_GET['t_user_id']);
                $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($t_user_id) {
                    $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                    $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验受让人手机号
            if (!empty($_GET['t_mobile'])) {
                $t_mobile = trim($_GET['t_mobile']);
                $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();
                if ($t_user_id) {
                    $t_user_id_str = implode(',', $t_user_id);
                    $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id IN ({$t_user_id_str}) AND status = 2";
                    $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验转让完成时间
            if (!empty($_GET['start'])) {
                $start  = strtotime($_GET['start'].' 00:00:00');
                $where .= " AND debt.successtime >= {$start} ";
            }
            if (!empty($_GET['end'])) {
                $end    = strtotime($_GET['end'].' 23:59:59');
                $where .= " AND debt.successtime <= {$end} ";
            }
            //后台用户
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            if (!empty($adminUserInfo['username'])) {
                if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                    if ($adminUserInfo['user_type'] == 2) {
                        $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                        if (!empty($deallist)) {
                            $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                            $where .= " AND deal.id IN({$dealIds})";
                        } else {
                            $where .= " AND deal.id < 0";
                        }
                    }
                }
            }
            // 查询数据总量
            if ($condition) {
                $redis_time = 86400;
                $redis_key  = 'XF_Debt_List_Count_PH_Condition_'.$condition['id'];
                $redis_val  = Yii::app()->rcache->get($redis_key);
                if ($redis_val) {
                    $count = $redis_val;
                    $con_data_str = implode(',', $con_data);
                    $where .= " AND debt.id IN ({$con_data_str}) ";
                } else {
                    if ($con_data) {
                        $count    = 0;
                        $con_page = ceil(count($con_data) / 1000);
                        for ($i = 0; $i < $con_page; $i++) {
                            $con_data_arr = array();
                            for ($j = $i * 1000; $j < ($i + 1) * 1000; $j++) {
                                if (!empty($con_data[$j])) {
                                    $con_data_arr[] = $con_data[$j];
                                }
                            }
                            $con_data_str = implode(',', $con_data_arr);
                            $where_con    = $where." AND debt.id IN ({$con_data_str}) ";
                            $sql = "SELECT count(debt.id) AS count 
                                    FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                                    LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where_con} ";
                            $count_con = Yii::app()->phdb->createCommand($sql)->queryScalar();
                            $count += $count_con;
                        }
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND debt.id IN ({$con_data_str}) ";
                        $set = Yii::app()->rcache->set($redis_key, $count, $redis_time);
                        if (!$set) {
                            Yii::log("{$redis_key} redis count set error", "error");
                        }
                    } else {
                        $where .= " AND debt.id = '' ";
                    }
                }
            } else {
                $sql = "SELECT count(debt.id) AS count 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ";
                $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            $page_count = ceil($count / 500);
            for ($i = 0; $i < $page_count; $i++) {
                $pass = $i * 500;
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src, deal.name , debt.serial_number , deal_load.money AS deal_load_money , deal_load.create_time , deal.deal_type , debt.platform_no 
                        FROM firstp2p_debt AS debt LEFT JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id 
                        LEFT JOIN firstp2p_deal_load AS deal_load ON debt.tender_id = deal_load.id {$where} ORDER BY debt.user_id ASC , debt.addtime DESC LIMIT {$pass} , 500 ";
                $list = Yii::app()->phdb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';
                $debt_src[5] = '一键下车退回';
                $debt_src[6] = '权益兑换退回';

                // $platform_no[1] = '有解';
                // $platform_no[2] = '悠融优选';

                $debt_id_arr = array();
                foreach ($list as $key => $value) {
                    $list[$key]['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $list[$key]['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $list[$key]['successtime'] = '——';
                    }
                    // $list[$key]['money']           = number_format($value['money'] , 2 , '.' , ',');
                    // $list[$key]['sold_money']      = number_format($value['sold_money'] , 2 , '.' , ',');
                    // $list[$key]['deal_load_money'] = number_format($value['deal_load_money'] , 2 , '.' , ',');
                    $list[$key]['status']          = $status[$value['status']];
                    if ($value['debt_src'] == 1) {
                        $list[$key]['platform_no'] = $platform_no[$value['platform_no']];
                    } else {
                        $list[$key]['platform_no'] = '——';
                    }
                    $list[$key]['debt_src']        = $debt_src[$value['debt_src']];

                    $user_id_arr[] = $value['user_id'];
                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT debt_id , user_id , new_tender_id , status FROM firstp2p_debt_tender WHERE debt_id IN ({$debt_id_str}) ";
                    $tender_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                            $user_id_arr[] = $value['user_id'];
                            if ($value['status'] == 2) {
                                $new_tender_id[] = $value['new_tender_id'];
                            }
                        }
                        if (!empty($new_tender_id)) {
                            $new_tender_id_str = implode(',', $new_tender_id);
                            $sql = "SELECT tender_id , oss_download FROM firstp2p_contract_task WHERE tender_id IN ({$new_tender_id_str}) ";
                            $task_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                            if ($task_res) {
                                foreach ($task_res as $key => $value) {
                                    $task_data[$value['tender_id']] = $value;
                                }
                            } else {
                                $task_data = array();
                            }
                        } else {
                            $task_data = array();
                        }
                        foreach ($debt_tender as $key => $value) {
                            if (!empty($task_data[$value['new_tender_id']]['oss_download'])) {
                                $debt_tender[$key]['oss_download'] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$task_data[$value['new_tender_id']]['oss_download'];
                            } else {
                                $debt_tender[$key]['oss_download'] = '';
                            }
                        }
                    }
                }
                $user_id_str = implode(',', $user_id_arr);
                $user_id_res = array();
                if ($user_id_str) {
                    $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                }
                foreach ($user_id_res as $key => $value) {
                    $temp['real_name'] = $value['real_name'];
                    $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    // $temp['mobile']    = $this->strEncrypt($temp['mobile'] , 3 , 4);

                    $user_id_data[$value['id']] = $temp;
                }
                foreach ($list as $key => $value) {
                    $value['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                    $value['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                    if (!empty($debt_tender[$value['id']])) {
                        $value['t_user_id']    = $debt_tender[$value['id']]['user_id'];
                        $value['t_real_name']  = $user_id_data[$debt_tender[$value['id']]['user_id']]['real_name'];
                        $value['t_mobile']     = $user_id_data[$debt_tender[$value['id']]['user_id']]['mobile'];
                        $value['oss_download'] = $debt_tender[$value['id']]['oss_download'];
                    } else {
                        $value['t_user_id']    = '——';
                        $value['t_real_name']  = '——';
                        $value['t_mobile']     = '——';
                        $value['oss_download'] = '';
                    }
                    $value['contract_number'] = implode('-', [date('Ymd', $value['create_time']), $value['deal_type'], $value['borrow_id'], $value['tender_id']]);

                    $listInfo[] = $value;
                }
            }
        }
        if ($_GET['deal_type'] == 1) {
            $name = '化债数据查询 尊享 '.date("Y年m月d日 H时i分s秒", time()).'.csv';
        } elseif ($_GET['deal_type'] == 2) {
            $name = '化债数据查询 普惠 '.date("Y年m月d日 H时i分s秒", time()).'.csv';
        }
        $name  = iconv('utf-8', 'GBK', $name);
        $data  = "债转ID,债转编号,转让人ID,转让人姓名,转让人手机号,借款编号,借款标题,投资记录ID,投资金额,发起债转金额,已转出金额,折扣,转让状态,债转类型,兑换平台,债转合同编号,受让人ID,受让人姓名,受让人手机号,发起时间,转让完成时间,债转合同地址\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['serial_number']},{$value['user_id']},{$value['real_name']},{$value['mobile']},{$value['borrow_id']},{$value['name']},{$value['tender_id']},{$value['deal_load_money']},{$value['money']},{$value['sold_money']},{$value['discount']},{$value['status']},{$value['debt_src']},{$value['platform_no']},{$value['contract_number']},{$value['t_user_id']},{$value['t_real_name']},{$value['t_mobile']},{$value['addtime']},{$value['successtime']},{$value['oss_download']}\n";
            $data .= iconv('utf-8', 'GBK', $temp);
        }

        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$name);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $data;
        if ($condition) {
            $redis_time = 3600;
            if ($_GET['deal_type'] == 1) {
                $redis_key = 'XF_Debt_List_Download_ZX_Condition_'.$condition['id'];
            } elseif ($_GET['deal_type'] == 2) {
                $redis_key = 'XF_Debt_List_Download_PH_Condition_'.$condition['id'];
            }
            $set = Yii::app()->rcache->set($redis_key, date('Y-m-d H:i:s', time()), $redis_time);
            if (!$set) {
                Yii::log("{$redis_key} redis download set error", "error");
            }
        }
    }

    /**
     * 债转详情
     */
    public function actionDebtInfo()
    {
        if (!empty($_POST)) {
            if (!in_array($_POST['deal_type'], array(1 , 2 , 3 , 4 , 5))) {
                $this->echoJson(array(), 3009, "请正确输入查询类型");
            }
            if (empty($_POST['debt_id']) || !is_numeric($_POST['debt_id'])) {
                $this->echoJson(array(), 3015, "请正确输入债转记录ID");
            }
            if ($_POST['deal_type'] == 1) {
                $model = Yii::app()->fdb;
                $table = 'firstp2p';
            } elseif ($_POST['deal_type'] == 2) {
                $model = Yii::app()->phdb;
                $table = 'firstp2p';
            } elseif (in_array($_POST['deal_type'], [3, 4, 5])) {
                $model = Yii::app()->offlinedb;
                $table = 'offline';
            }
            $debt_id = intval($_POST['debt_id']);
            $sql = "SELECT debt.id AS debt_id , deal.name , debt.status , debt.money , debt.discount , debt.buy_code , debt.endtime , dt.cancel_time , debt.payee_name , debt.payee_bankzone , debt.payee_bankcard , debt.serial_number , dt.payer_name , dt.payer_bankzone , dt.payer_bankcard , dt.payment_voucher , dt.addtime , dt.submit_paytime , debt.successtime , debt.arrival_amount , dt.id AS debt_tender_id , dt.user_id AS t_user_id , debt.addtime AS debt_addtime , dt.action_money , debt.user_id 
                    FROM {$table}_debt AS debt 
                    LEFT JOIN {$table}_deal AS deal ON debt.borrow_id = deal.id 
                    LEFT JOIN {$table}_debt_tender AS dt ON debt.id = dt.debt_id AND dt.status IN (1 , 2 , 6) WHERE debt.id = {$debt_id} ";
            $result = $model->createCommand($sql)->queryRow();
            if (!$result) {
                $this->echoJson(array(), 3016, "债转记录ID输入错误");
            }
            $sql = "SELECT real_name , mobile FROM firstp2p_user WHERE id = {$result['user_id']}";
            $user_info = Yii::app()->fdb->createCommand($sql)->queryRow();

            $result['deal_type']       = $_POST['deal_type'];
            $result['payment_voucher'] = explode(',', $result['payment_voucher']);
            $result['real_name']       = $user_info['real_name'];
            $result['mobile']          = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->idno_key); // 手机号解密
            $result['payee_bankcard']  = GibberishAESUtil::dec($result['payee_bankcard'], Yii::app()->c->idno_key);
            $result['payer_bankcard']  = GibberishAESUtil::dec($result['payer_bankcard'], Yii::app()->c->idno_key);
            if ($result['payment_voucher']) {
                foreach ($result['payment_voucher'] as $key => $value) {
                    if ($result['addtime'] <= 1578394800) {
                        $result['payment_voucher'][$key] = 'https://service.zichanhuayuan.com'.DIRECTORY_SEPARATOR.$value;
                    } else {
                        $result['payment_voucher'][$key] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$value;
                    }
                }
            }
            if ($result['debt_tender_id']) {
                $sql = "SELECT * FROM ag_wx_debt_appeal WHERE debt_id = {$result['debt_id']} AND debt_tender_id = {$result['debt_tender_id']} AND products = {$_POST['deal_type']}";
                $appeal = Yii::app()->fdb->createCommand($sql)->queryRow();
            } else {
                $appeal = array();
            }
            if ($appeal) {
                $sql = "SELECT realname FROM itz_user WHERE id = {$appeal['decision_maker']}";
                $result['is_appeal']           = 1;
                $result['appeal_addtime']      = $appeal['addtime'];
                $result['decision_maker']      = Yii::app()->db->createCommand($sql)->queryScalar();
                $result['decision_time']       = $appeal['decision_time'];
                $result['decision_status']     = $appeal['status'];
                $result['decision_outaccount'] = $appeal['decision_outaccount'];
                $result['decision_outcomes']   = $appeal['decision_outcomes'];
            } else {
                $result['is_appeal']           = 0;
                $result['appeal_addtime']      = 0;
                $result['decision_maker']      = '';
                $result['decision_time']       = 0;
                $result['decision_status']     = 0;
                $result['decision_outaccount'] = '';
                $result['decision_outcomes']   = '';
            }
            if ($result['t_user_id']) {
                $sql    = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id = {$result['t_user_id']}";
                $t_user = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($t_user) {
                    $result['t_user_id']   = $t_user['id'];
                    $result['t_real_name'] = $t_user['real_name'];
                    $result['t_mobile']    = GibberishAESUtil::dec($t_user['mobile'], Yii::app()->c->idno_key); // 手机号解密
                }
            } else {
                $result['t_user_id']   = '';
                $result['t_real_name'] = '';
                $result['t_mobile']    = '';
            }
            $this->echoJson($result, 0, '查询成功');
        }
        return $this->renderPartial('DebtInfo', array());
    }

    /**
     * 未确认收款债转记录
     */
    public function actionDebtAppealA()
    {
        if (!empty($_POST)) {
            if (empty($_POST['deal_type'])) {
                $_POST['deal_type'] = 1;
            }
            // 尊享
            if ($_POST['deal_type'] == 1) {

                // 条件筛选
                $where = "";
                $da_sta = intval($_POST['da_status']);
                if ($_POST['type'] == 1) {
                    $where .= " AND da.type = 1 AND da.status = {$da_sta} ";
                } elseif ($_POST['type'] == 2) {
                    $where .= " AND da.type = 2 AND da.status = {$da_sta} ";
                }
                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where  .= " AND debt.user_id = {$user_id} ";
                }
                // 校验债转编号
                if (!empty($_POST['serial_number'])) {
                    $serial_number = trim($_POST['serial_number']);
                    $where        .= " AND debt.serial_number = '{$serial_number}' ";
                }
                // 校验项目ID
                if (!empty($_POST['borrow_id'])) {
                    $borrow_id = intval($_POST['borrow_id']);
                    $where    .= " AND debt.borrow_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['tender_id'])) {
                    $tender_id = intval($_POST['tender_id']);
                    $where    .= " AND debt.tender_id = {$tender_id} ";
                }
                // 校验转让状态
                if (!empty($_POST['status'])) {
                    $sta = intval($_POST['status']);
                    $where .= " AND debt.status = {$sta} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile = trim($_POST['mobile']);
                    $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $where .= " AND user.mobile = '{$mobile}' ";
                }
                // 校验债转类型
                if (!empty($_POST['debt_src'])) {
                    $src    = intval($_POST['debt_src']);
                    $where .= " AND debt.debt_src = {$src} ";
                }
                // 校验借款方名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                    $com_a   = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' AND e.company_purpose = 2";
                    $com_b   = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    $com_arr = array();
                    if ($com_a) {
                        $com_arr[] = $com_a;
                    }
                    if ($com_b) {
                        $com_arr[] = $com_b;
                    }
                    if (!empty($com_arr)) {
                        $com_str = implode(',', $com_arr);
                        $where .= " AND deal.user_id IN ({$com_str}) ";
                    } else {
                        $where .= " AND deal.user_id is NULL ";
                    }
                }
                // 校验受让人ID
                if (!empty($_POST['t_user_id'])) {
                    $t_user_id = intval($_POST['t_user_id']);
                    $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验受让人手机号
                if (!empty($_POST['t_mobile'])) {
                    $t_mobile = trim($_POST['t_mobile']);
                    $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->fdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验转让完成时间
                if (!empty($_POST['start'])) {
                    $start  = strtotime($_POST['start'].' 00:00:00');
                    $where .= " AND debt.successtime >= {$start} ";
                }
                if (!empty($_POST['end'])) {
                    $end    = strtotime($_POST['end'].' 23:59:59');
                    $where .= " AND debt.successtime <= {$end} ";
                }
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
                // 查询数据总量
                $sql = "SELECT count(deal.id) AS count 
                        FROM (firstp2p_debt AS debt INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id)
                        INNER JOIN firstp2p_user AS user ON debt.user_id = user.id 
                        INNER JOIN ag_wx_debt_appeal AS da ON debt.id = da.debt_id {$where} ";
                $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src, deal.name, user.real_name, user.mobile , debt.serial_number , da.status AS appeal_status
                        FROM (firstp2p_debt AS debt INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id)
                        INNER JOIN firstp2p_user AS user ON debt.user_id = user.id 
                        INNER JOIN ag_wx_debt_appeal AS da ON debt.id = da.debt_id AND da.products = 1 {$where} ORDER BY debt.id DESC ";
                $pass       = ($page - 1) * $limit;
                $sql       .= " LIMIT {$pass} , {$limit} ";
                $list       = Yii::app()->fdb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';

                $da_status[1] = '客服介入待处理';
                $da_status[2] = '判定有效交易';
                $da_status[3] = '判定无效交易';

                $debt_id_arr = array();
                foreach ($list as $key => $value) {
                    $value['mobile']  = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $value['successtime'] = '——';
                    }
                    $value['money']      = number_format($value['money'], 2, '.', ',');
                    $value['sold_money'] = number_format($value['sold_money'], 2, '.', ',');
                    $value['status']     = $status[$value['status']];
                    $value['debt_src']   = $debt_src[$value['debt_src']];
                    $value['number']     = $key+1;
                    $value['deal_type']  = 1;
                    $value['da_status']  = $da_status[$da_sta];
                    $listInfo[] = $value;

                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT dt.debt_id , u.id , u.real_name , u.mobile FROM firstp2p_debt_tender AS dt INNER JOIN firstp2p_user AS u ON dt.user_id = u.id AND dt.debt_id IN ($debt_id_str) AND dt.status IN (1 , 2 , 6)";
                    $tender_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value;
                        }
                    }
                }
                foreach ($listInfo as $key => $value) {
                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']   = $debt_tender[$value['id']]['id'];
                        $listInfo[$key]['t_real_name'] = $debt_tender[$value['id']]['real_name'];
                        $listInfo[$key]['t_mobile']    = GibberishAESUtil::dec($debt_tender[$value['id']]['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    } else {
                        $listInfo[$key]['t_user_id']   = '——';
                        $listInfo[$key]['t_real_name'] = '——';
                        $listInfo[$key]['t_mobile']    = '——';
                    }
                }

                // 普惠供应链
            } elseif ($_POST['deal_type'] == 2) {

                // 条件筛选
                $where = "";
                $da_sta = intval($_POST['da_status']);
                if ($_POST['type'] == 1) {
                    $sql = "SELECT debt_id FROM ag_wx_debt_appeal WHERE type = 1 AND products = 2 AND status = {$da_sta} ";
                    $where_debt_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                } elseif ($_POST['type'] == 2) {
                    $sql = "SELECT debt_id FROM ag_wx_debt_appeal WHERE type = 2 AND products = 2 AND status = {$da_sta} ";
                    $where_debt_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                }
                if ($where_debt_id_arr) {
                    $where_debt_id_str = implode(',', $where_debt_id_arr);
                    $where .= " AND debt.id IN ({$where_debt_id_str}) ";
                } else {
                    $where .= " AND debt.id = -1 ";
                }
                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where  .= " AND debt.user_id = {$user_id} ";
                }
                // 校验债转编号
                if (!empty($_POST['serial_number'])) {
                    $serial_number = trim($_POST['serial_number']);
                    $where        .= " AND debt.serial_number = '{$serial_number}' ";
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile      = trim($_POST['mobile']);
                    $mobile      = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql         = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                    $user_id     = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($user_id) {
                        $where .= " AND debt.user_id = {$user_id} ";
                    } else {
                        $where .= " AND debt.user_id is NULL ";
                    }
                }
                // 校验项目ID
                if (!empty($_POST['borrow_id'])) {
                    $borrow_id = intval($_POST['borrow_id']);
                    $where    .= " AND debt.borrow_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['tender_id'])) {
                    $tender_id = intval($_POST['tender_id']);
                    $where    .= " AND debt.tender_id = {$tender_id} ";
                }
                // 校验转让状态
                if (!empty($_POST['status'])) {
                    $sta    = intval($_POST['status']);
                    $where .= " AND debt.status = {$sta} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }
                // 校验债转类型
                if (!empty($_POST['debt_src'])) {
                    $src    = intval($_POST['debt_src']);
                    $where .= " AND debt.debt_src = {$src} ";
                }
                // 校验借款方名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                    $com_a   = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' AND e.company_purpose = 2";
                    $com_b   = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    $com_arr = array();
                    if ($com_a) {
                        $com_arr[] = $com_a;
                    }
                    if ($com_b) {
                        $com_arr[] = $com_b;
                    }
                    if (!empty($com_arr)) {
                        $com_str = implode(',', $com_arr);
                        $where .= " AND deal.user_id IN ({$com_str}) ";
                    } else {
                        $where .= " AND deal.user_id is NULL ";
                    }
                }
                // 校验受让人ID
                if (!empty($_POST['t_user_id'])) {
                    $t_user_id = intval($_POST['t_user_id']);
                    $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验受让人手机号
                if (!empty($_POST['t_mobile'])) {
                    $t_mobile = trim($_POST['t_mobile']);
                    $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验转让完成时间
                if (!empty($_POST['start'])) {
                    $start  = strtotime($_POST['start'].' 00:00:00');
                    $where .= " AND debt.successtime >= {$start} ";
                }
                if (!empty($_POST['end'])) {
                    $end    = strtotime($_POST['end'].' 23:59:59');
                    $where .= " AND debt.successtime <= {$end} ";
                }
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
                // 查询数据总量
                $sql = "SELECT count(deal.id) AS count 
                        FROM firstp2p_debt AS debt 
                        INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id {$where} ";
                $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src, deal.name , debt.serial_number 
                        FROM firstp2p_debt AS debt 
                        INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id {$where} ORDER BY debt.id DESC ";
                $pass       = ($page - 1) * $limit;
                $sql       .= " LIMIT {$pass} , {$limit} ";
                $list       = Yii::app()->phdb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';

                $da_status[1] = '客服介入待处理';
                $da_status[2] = '判定有效交易';
                $da_status[3] = '判定无效交易';

                foreach ($list as $key => $value) {
                    $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $value['successtime'] = '——';
                    }
                    $value['money']      = number_format($value['money'], 2, '.', ',');
                    $value['sold_money'] = number_format($value['sold_money'], 2, '.', ',');
                    $value['status']     = $status[$value['status']];
                    $value['debt_src']   = $debt_src[$value['debt_src']];
                    $value['number']     = $key+1;
                    $value['appeal_status'] = $da_sta;
                    $value['deal_type']  = 2;
                    $value['da_status']  = $da_status[$da_sta];

                    $listInfo[]    = $value;
                    $user_id_arr[] = $value['user_id'];
                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT debt_id , user_id FROM firstp2p_debt_tender WHERE debt_id IN ($debt_id_str) ";
                    $tender_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value['user_id'];
                            $user_id_arr[] = $value['user_id'];
                        }
                    }
                }
                $user_id_str = implode(',', $user_id_arr);
                $user_id_res = array();
                if ($user_id_str) {
                    $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                }
                foreach ($user_id_res as $key => $value) {
                    $temp['real_name'] = $value['real_name'];
                    $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密

                    $user_id_data[$value['id']] = $temp;
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                    $listInfo[$key]['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']   = $debt_tender[$value['id']];
                        $listInfo[$key]['t_real_name'] = $user_id_data[$debt_tender[$value['id']]]['real_name'];
                        $listInfo[$key]['t_mobile']    = $user_id_data[$debt_tender[$value['id']]]['mobile'];
                    } else {
                        $listInfo[$key]['t_user_id']   = '——';
                        $listInfo[$key]['t_real_name'] = '——';
                        $listInfo[$key]['t_mobile']    = '——';
                    }
                }

                // 工场微金 智多新 交易所
            } elseif (in_array($_POST['deal_type'], [3, 4, 5])) {

                // 条件筛选
                $where = " AND debt.platform_id = {$_POST['deal_type']} ";
                $da_sta = intval($_POST['da_status']);
                if ($_POST['type'] == 1) {
                    $sql = "SELECT debt_id FROM ag_wx_debt_appeal WHERE type = 1 AND products = {$_POST['deal_type']} AND status = {$da_sta} ";
                    $where_debt_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                } elseif ($_POST['type'] == 2) {
                    $sql = "SELECT debt_id FROM ag_wx_debt_appeal WHERE type = 2 AND products = {$_POST['deal_type']} AND status = {$da_sta} ";
                    $where_debt_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
                }
                if ($where_debt_id_arr) {
                    $where_debt_id_str = implode(',', $where_debt_id_arr);
                    $where .= " AND debt.id IN ({$where_debt_id_str}) ";
                } else {
                    $where .= " AND debt.id = -1 ";
                }
                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where  .= " AND debt.user_id = {$user_id} ";
                }
                // 校验债转编号
                if (!empty($_POST['serial_number'])) {
                    $serial_number = trim($_POST['serial_number']);
                    $where        .= " AND debt.serial_number = '{$serial_number}' ";
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile      = trim($_POST['mobile']);
                    $mobile      = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql         = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                    $user_id     = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($user_id) {
                        $where .= " AND debt.user_id = {$user_id} ";
                    } else {
                        $where .= " AND debt.user_id is NULL ";
                    }
                }
                // 校验项目ID
                if (!empty($_POST['borrow_id'])) {
                    $borrow_id = intval($_POST['borrow_id']);
                    $where    .= " AND debt.borrow_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['tender_id'])) {
                    $tender_id = intval($_POST['tender_id']);
                    $where    .= " AND debt.tender_id = {$tender_id} ";
                }
                // 校验转让状态
                if (!empty($_POST['status'])) {
                    $sta    = intval($_POST['status']);
                    $where .= " AND debt.status = {$sta} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }
                // 校验债转类型
                if (!empty($_POST['debt_src'])) {
                    $src    = intval($_POST['debt_src']);
                    $where .= " AND debt.debt_src = {$src} ";
                }
                // 校验借款方名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                    $com_a   = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' AND e.company_purpose = 2";
                    $com_b   = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    $com_arr = array();
                    if ($com_a) {
                        $com_arr[] = $com_a;
                    }
                    if ($com_b) {
                        $com_arr[] = $com_b;
                    }
                    if (!empty($com_arr)) {
                        $com_str = implode(',', $com_arr);
                        $where .= " AND deal.user_id IN ({$com_str}) ";
                    } else {
                        $where .= " AND deal.user_id is NULL ";
                    }
                }
                // 校验受让人ID
                if (!empty($_POST['t_user_id'])) {
                    $t_user_id = intval($_POST['t_user_id']);
                    $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验受让人手机号
                if (!empty($_POST['t_mobile'])) {
                    $t_mobile = trim($_POST['t_mobile']);
                    $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                    $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($t_user_id) {
                        $sql = "SELECT debt_id FROM offline_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                        $debt_id_res = Yii::app()->offlinedb->createCommand($sql)->queryColumn();
                        if ($debt_id_res) {
                            $debt_id_res_str = implode(',', $debt_id_res);
                            $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                        } else {
                            $where .= " AND debt.id = -1 ";
                        }
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                }
                // 校验转让完成时间
                if (!empty($_POST['start'])) {
                    $start  = strtotime($_POST['start'].' 00:00:00');
                    $where .= " AND debt.successtime >= {$start} ";
                }
                if (!empty($_POST['end'])) {
                    $end    = strtotime($_POST['end'].' 23:59:59');
                    $where .= " AND debt.successtime <= {$end} ";
                }
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
                // 查询数据总量
                $sql = "SELECT count(deal.id) AS count 
                        FROM offline_debt AS debt 
                        INNER JOIN offline_deal AS deal ON debt.borrow_id = deal.id {$where} ";
                $count = Yii::app()->offlinedb->createCommand($sql)->queryScalar();
                // 查询数据
                $sql = "SELECT
                        debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src, deal.name , debt.serial_number 
                        FROM offline_debt AS debt 
                        INNER JOIN offline_deal AS deal ON debt.borrow_id = deal.id {$where} ORDER BY debt.id DESC ";
                $pass       = ($page - 1) * $limit;
                $sql       .= " LIMIT {$pass} , {$limit} ";
                $list       = Yii::app()->offlinedb->createCommand($sql)->queryAll();

                $status[1] = '转让中';
                $status[2] = '交易成功';
                $status[3] = '交易取消';
                $status[4] = '已过期';
                $status[5] = '待付款';
                $status[6] = '待收款';

                $debt_src[1] = '权益兑换';
                $debt_src[2] = '债转交易';
                $debt_src[3] = '债权划扣';
                $debt_src[4] = '一键下车';

                $da_status[1] = '客服介入待处理';
                $da_status[2] = '判定有效交易';
                $da_status[3] = '判定无效交易';

                foreach ($list as $key => $value) {
                    $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                    if ($value['successtime'] != 0) {
                        $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                    } else {
                        $value['successtime'] = '——';
                    }
                    $value['money']      = number_format($value['money'], 2, '.', ',');
                    $value['sold_money'] = number_format($value['sold_money'], 2, '.', ',');
                    $value['status']     = $status[$value['status']];
                    $value['debt_src']   = $debt_src[$value['debt_src']];
                    $value['number']     = $key+1;
                    $value['appeal_status'] = $da_sta;
                    $value['deal_type']  = $_POST['deal_type'];
                    $value['da_status']  = $da_status[$da_sta];

                    $listInfo[]    = $value;
                    $user_id_arr[] = $value['user_id'];
                    $debt_id_arr[] = $value['id'];
                }
                $debt_tender = array();
                if ($debt_id_arr) {
                    $debt_id_str = implode(',', $debt_id_arr);
                    $sql = "SELECT debt_id , user_id FROM offline_debt_tender WHERE debt_id IN ($debt_id_str) ";
                    $tender_res = Yii::app()->offlinedb->createCommand($sql)->queryAll();
                    if ($tender_res) {
                        foreach ($tender_res as $key => $value) {
                            $debt_tender[$value['debt_id']] = $value['user_id'];
                            $user_id_arr[] = $value['user_id'];
                        }
                    }
                }
                $user_id_str = implode(',', $user_id_arr);
                $user_id_res = array();
                if ($user_id_str) {
                    $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                    $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                }
                foreach ($user_id_res as $key => $value) {
                    $temp['real_name'] = $value['real_name'];
                    $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密

                    $user_id_data[$value['id']] = $temp;
                }
                foreach ($listInfo as $key => $value) {
                    $listInfo[$key]['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                    $listInfo[$key]['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                    if (!empty($debt_tender[$value['id']])) {
                        $listInfo[$key]['t_user_id']   = $debt_tender[$value['id']];
                        $listInfo[$key]['t_real_name'] = $user_id_data[$debt_tender[$value['id']]]['real_name'];
                        $listInfo[$key]['t_mobile']    = $user_id_data[$debt_tender[$value['id']]]['mobile'];
                    } else {
                        $listInfo[$key]['t_user_id']   = '——';
                        $listInfo[$key]['t_real_name'] = '——';
                        $listInfo[$key]['t_mobile']    = '——';
                    }
                }
            }
            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }
        return $this->renderPartial('DebtAppealA', array());
    }

    /**
     * 确认未到账债转记录
     */
    public function actionDebtAppealB()
    {
        return $this->renderPartial('DebtAppealB', array());
    }

    /**
     * 未确认收款债转记录&确认未到账债转记录 判定
     */
    public function actionDebtJudge()
    {
        if (empty($_POST['deal_type']) || !in_array($_POST['deal_type'], array(1 , 2 , 3 , 4, 5))) {
            $this->echoJson(array(), 3009, "请正确输入查询类型");
        }
        if ($_POST['deal_type'] == 1) {
            $model    = Yii::app()->fdb;
            $products = 1;
            $table    = 'firstp2p';
        } elseif ($_POST['deal_type'] == 2) {
            $model    = Yii::app()->phdb;
            $products = 2;
            $table    = 'firstp2p';
        } elseif (in_array($_POST['deal_type'], [3, 4, 5])) {
            $model    = Yii::app()->offlinedb;
            $products = intval($_POST['deal_type']);
            $table    = 'offline';
        }
        if (empty($_POST['debt_id']) || !is_numeric($_POST['debt_id'])) {
            $this->echoJson(array(), 3015, '请正确输入债转记录ID');
        }
        $debt_id = intval($_POST['debt_id']);
        if (empty($_POST['operation']) || !in_array($_POST['operation'], array(1 , 2))) {
            $this->echoJson(array(), 3021, '请正确输入判定操作');
        }
        if (empty($_POST['outcomes'])) {
            $this->echoJson(array(), 3022, '请输入判定结果');
        }
        $outcomes  = trim($_POST['outcomes']);
        $sql       = "SELECT * FROM {$table}_debt WHERE id = {$debt_id}";
        $debt_info = $model->createCommand($sql)->queryRow();
        if (!$debt_info) {
            $this->echoJson(array(), 3016, '债转记录ID输入错误');
        }
        if ($debt_info['status'] != 6) {
            $this->echoJson(array(), 3023, '债转状态错误');
        }
        $sql         = "SELECT * FROM {$table}_debt_tender WHERE debt_id = {$debt_info['id']} AND status = 6";
        $debt_tender = $model->createCommand($sql)->queryRow();
        if (!$debt_tender) {
            $this->echoJson(array(), 3019, '此债转记录未查询到认购记录');
        }
        $sql = "SELECT * FROM ag_wx_debt_appeal WHERE products = {$products} AND debt_id = {$debt_info['id']} AND debt_tender_id = {$debt_tender['id']}";
        $appeal_info = Yii::app()->fdb->createCommand($sql)->queryRow();
        if (!$appeal_info) {
            $this->echoJson(array(), 3024, '此债转记录未查询到申诉记录');
        }
        if ($appeal_info['status'] != 1) {
            $this->echoJson(array(), 3025, '申诉记录状态错误');
        }
        $sql = "SELECT mobile FROM firstp2p_user WHERE id = {$debt_tender['user_id']}";
        $debt_tender_user = Yii::app()->fdb->createCommand($sql)->queryScalar();
        $debt_tender_user = GibberishAESUtil::dec($debt_tender_user, Yii::app()->c->idno_key);

        $sql = "SELECT mobile FROM firstp2p_user WHERE id = {$debt_info['user_id']}";
        $debt_user = Yii::app()->fdb->createCommand($sql)->queryScalar();
        $debt_user = GibberishAESUtil::dec($debt_user, Yii::app()->c->idno_key);

        $time       = time();
        $op_user_id = Yii::app()->user->id;
        $op_user_id = $op_user_id ? $op_user_id : 0 ;
        $ip         = Yii::app()->request->userHostAddress;
        if ($_POST['operation'] == 1) {
            $api    = Yii::app()->c->wx_confirm_debt_api;
            $params = array(
                'products'          => $products,
                'debt_tender_id'    => $debt_tender['id'],
                'decision_src'      => 2,
                'decision_maker'    => $op_user_id,
                'decision_outcomes' => $outcomes
            );
            $result = $this->curlRequest($api.'/Launch/XfDebtGarden/ConfirmReceipt', 'POST', $params);

            if ($result && $result['code'] == 0) {
                $this->echoJson(array(), 0, '操作成功');
            } else {
                $params_json = json_encode($params);
                $result_json = json_encode($result);
                Yii::log("DebtJudge params:{$params_json}; api:{$api}/Launch/XfDebtGarden/ConfirmReceipt; result error:{$result_json};");
                $this->echoJson(array(), 3026, $result['info']);
            }
        } elseif ($_POST['operation'] == 2) {
            if ($_POST['deal_type'] == 1) {
                $model->beginTransaction();
                if ($time > $debt_info['endtime']) {
                    $sql = "UPDATE {$table}_debt SET status = 4 WHERE id = {$debt_info['id']}";
                } else {
                    $sql = "UPDATE {$table}_debt SET status = 1 WHERE id = {$debt_info['id']}";
                }
                $update_a = $model->createCommand($sql)->execute();

                $sql = "UPDATE {$table}_debt_tender SET status = 5 , cancel_time = {$time} WHERE id = {$debt_tender['id']}";
                $update_b = $model->createCommand($sql)->execute();

                $sql = "UPDATE ag_wx_debt_appeal SET status = 3 , decision_time = {$time} , decision_maker = {$op_user_id} , decision_outcomes = '{$outcomes}' WHERE id = {$appeal_info['id']}";
                $update_c = $model->createCommand($sql)->execute();
                if (!$update_a || !$update_b || !$update_c) {
                    $model->rollback();
                    $this->echoJson(array(), 3026, '操作失败');
                }
                $model->commit();

                $smaClass                   = new XfSmsClass();
                $remind                     = array();
                $remind['sms_code']         = "wx_buyer_trade_fail";
                $remind['mobile']           = $debt_tender_user;
                $remind['data']['order_no'] = $debt_info['serial_number'];
                $remind['data']['reason']   = $outcomes;
                $send_ret_a                 = $smaClass->sendToUserByPhone($remind);
                if ($send_ret_a['code'] != 0) {
                    Yii::log("DebtJudge user_id:{$debt_tender['user_id']}, debt_id:{$debt_info['id']}; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                }

                $remind['sms_code']         = "wx_seller_trade_fail";
                $remind['mobile']           = $debt_user;
                $send_ret_b                 = $smaClass->sendToUserByPhone($remind);
                if ($send_ret_b['code'] != 0) {
                    Yii::log("DebtJudge user_id:{$debt_info['user_id']}, debt_id:{$debt_info['id']}; sendToUser seller error:".print_r($remind, true)."; return:".print_r($send_ret_b, true), "error");
                }

                $this->echoJson(array(), 0, '操作成功');
            } elseif (in_array($_POST['deal_type'], [2, 3, 4, 5])) {
                $model->beginTransaction();
                Yii::app()->fdb->beginTransaction();
                if ($time > $debt_info['endtime']) {
                    $sql = "UPDATE {$table}_debt SET status = 4 WHERE id = {$debt_info['id']}";
                } else {
                    $sql = "UPDATE {$table}_debt SET status = 1 WHERE id = {$debt_info['id']}";
                }
                $update_a = $model->createCommand($sql)->execute();

                $sql = "UPDATE {$table}_debt_tender SET status = 5 , cancel_time = {$time} WHERE id = {$debt_tender['id']}";
                $update_b = $model->createCommand($sql)->execute();

                $sql = "UPDATE ag_wx_debt_appeal SET status = 3 , decision_time = {$time} , decision_maker = {$op_user_id} , decision_outcomes = '{$outcomes}' WHERE id = {$appeal_info['id']}";
                $update_c = Yii::app()->fdb->createCommand($sql)->execute();
                if (!$update_a || !$update_b || !$update_c) {
                    $model->rollback();
                    Yii::app()->fdb->rollback();
                    $this->echoJson(array(), 3026, '操作失败');
                }
                $model->commit();
                Yii::app()->fdb->commit();

                $smaClass                   = new XfSmsClass();
                $remind                     = array();
                $remind['sms_code']         = "wx_buyer_trade_fail";
                $remind['mobile']           = $debt_tender_user;
                $remind['data']['order_no'] = $debt_info['serial_number'];
                $remind['data']['reason']   = $outcomes;
                $send_ret_a                 = $smaClass->sendToUserByPhone($remind);
                if ($send_ret_a['code'] != 0) {
                    Yii::log("DebtJudge user_id:{$debt_tender['user_id']}, debt_id:{$debt_info['id']}; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                }

                $remind['sms_code']         = "wx_seller_trade_fail";
                $remind['mobile']           = $debt_user;
                $send_ret_b                 = $smaClass->sendToUserByPhone($remind);
                if ($send_ret_b['code'] != 0) {
                    Yii::log("DebtJudge user_id:{$debt_info['user_id']}, debt_id:{$debt_info['id']}; sendToUser seller error:".print_r($remind, true)."; return:".print_r($send_ret_b, true), "error");
                }

                $this->echoJson(array(), 0, '操作成功');
            }
        }
    }

    /**
     * 用户管理 列表 张健
     * 提供查询字段：
     * @param id                int     用户ID
     * @param user_name         string  用户名
     * @param real_name         string  真实姓名
     * @param idno              string  证件号码
     * @param bankcard          string  银行卡号
     * @param invite_code       string  服务人邀请码
     * @param user_purpose      string  账户类型
     * @param mobile            string  手机号码
     * @param condition_id      string  指定用户id查询, condition_id为xf_upload_user_condition的id, 里面存储了指定的用户id
     * @param limit             int     每页数据显示量 默认10
     * @param page              int     当前页数 默认1
     */
    public function actionGetUserList()
    {
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        if (!empty($_POST)) {
            // 条件筛选
            $where = "";        // 查询总条数sql的where条件
            $where_list = "";   // 查询列表数据sql的where条件, 会查询多个表用户, 用户银行卡, 用户所属组等表, 查询总条数只查询用户表
            // 校验平台ID
            // if (!empty($_POST['platform'])) {
            //     $pla    = intval($_POST['platform']);
            //     $where .= " AND platform_id = {$pla} ";
            // }
            // 校验用户ID
            if (!empty($_POST['id'])) {
                $id     = intval(trim($_POST['id']));
                $where .= " AND user.id = {$id} ";
            }
            // 校验会员编号
            if (!empty($_POST['member_id'])) {
                // 会员编号转为用户id
                $member_user_id = intval($this->de32Tonum(trim($_POST['member_id'])));
                $where .= " AND user.id = {$member_user_id} ";
            }
            // 校验用户名
            if (!empty($_POST['user_name'])) {
                $user_name = trim($_POST['user_name']);
                $where    .= " AND user.user_name = '{$user_name}' ";
            }
            // 校验真实姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where    .= " AND user.real_name = '{$real_name}' ";
            }
            // 校验证件号码
            if (!empty($_POST['idno'])) {
                $idno   = trim($_POST['idno']);
                $idno   = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key); // 证件号码加密
                $where .= " AND user.idno = '{$idno}' ";
            }
            // 校验手机号码
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.mobile = '{$mobile}' ";
            }
            // 校验银行卡号
            if (!empty($_POST['bankcard'])) {
                $bankcard = trim($_POST['bankcard']);
                $bankcard = GibberishAESUtil::enc($bankcard, Yii::app()->c->idno_key); // 银行卡号加密
                $where_list .= " AND card.bankcard = '{$bankcard}' ";
            }
            // 校验服务人邀请码
            if (!empty($_POST['short_alias'])) {
                $short_alias = trim($_POST['short_alias']);
                $where_list .= " AND coupon_bind.short_alias = '{$short_alias}' ";
            }
            // 校验账户类型
            $user_purpose_type = $_POST['user_purpose'];
            if (!empty($user_purpose_type) || strcmp($user_purpose_type, 0) == 0) {
                $user_purpose = trim($_POST['user_purpose']);
                $where .= " AND user.user_purpose = '{$user_purpose}' ";
            }
            if (!empty($_POST['condition_id'])) {
                // 拼指定的user_id
                $user_condition_sql = "SELECT data_json FROM xf_upload_user_condition WHERE id = '{$_POST['condition_id']}' ";
                $user_condition = Yii::app()->fdb->createCommand($user_condition_sql)->queryRow();
                if ($user_condition) {
                    $con_data = json_decode($user_condition['data_json'], true);
                    if ($con_data) {
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND user.id IN ({$con_data_str}) ";
                    }
                }
            }
            $where_list .= $where;

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

            // 查询数据总量
            // 除账户类型外, 其它的查询条件为空时, 从缓存中取条数
            if (empty($_POST['id']) && empty($_POST['bankcard']) && empty($_POST['member_id']) &&
                empty($_POST['user_name']) &&
                empty($_POST['real_name']) && empty($_POST['idno']) && empty($_POST['mobile']) &&
                empty($_POST['short_alias']) &&
                empty($_POST['condition_id'])) {
                if (
                    in_array(
                        $_POST['user_purpose'],
                        array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21)
                    )
                ) {
                    $rcache_name = 'firstp2p_user_purpose_count'.$_POST['user_purpose'];
                } else {
                    $rcache_name = 'firstp2p_user_count';
                }
                $count = Yii::app()->rcache->get($rcache_name);
                if (!$count) {
                    $sql       = "SELECT count(id) AS count FROM firstp2p_user as user WHERE 1 = 1 {$where} ";
                    $count     = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($count != 0) {
                        $redisData = Yii::app()->rcache->set($rcache_name, $count, 86400);
                        if (!$redisData) {
                            Yii::log("redis {$rcache_name} set error", "error");
                        }
                    }
                }
            } else {
                $sql   = "SELECT count(id) AS count FROM firstp2p_user as user WHERE 1 = 1 {$where} ";
                $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT user.id, user.user_name, user.real_name, user.mobile, user.create_time, 
            user.user_purpose, user.user_type,
            user_group.name, user_group.prefix, 
            coupon_bind.short_alias, coupon_bind.refer_user_id, 
            coupon_bind.invite_user_group_id, coupon_bind.refer_user_name
            FROM firstp2p_user as user 
            LEFT JOIN firstp2p_coupon_bind  as coupon_bind
            ON user.id = coupon_bind.user_id
            LEFT JOIN firstp2p_user_group as user_group
            ON user.group_id = user_group.id
            LEFT JOIN firstp2p_user_bankcard as card
            ON user.id = card.user_id and card.verify_status=1
            WHERE 1 = 1 {$where_list} ORDER BY id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();

            // $enterprise_user_arr = array(); // 企业用户ID数组, 用来查询企业证件类型和手机号
            // $enterprise_user_phone = array();
            $refer_user_arr = array();      // 服务人id数组
            $group_list_data = array();     // 服务人所属组id和名称
            $refer_real_name = array();     // 服务人id以及姓名
            // 服务人ID拼接字符串
            foreach ($list as $key => $value) {
                if (!empty($value['refer_user_id'])) {
                    array_push($refer_user_arr, $value['refer_user_id']);
                }
                // 企业用户
                // if ($value['user_type'] == 1) {
                //     array_push($enterprise_user_arr, $value['id']);
                // }
            }
            if ($refer_user_arr) {
                $refer_user = "'".implode("','", $refer_user_arr)."'";
                // 获取查询的服务人ID所属组名称以及服务人姓名
                $refer_sql  = "SELECT g.id, g.name, a.real_name, a.id as user_id
                                FROM firstp2p_user as a 
                                INNER JOIN 
                                firstp2p_user_group as g
                                ON a.group_id = g.id  
                                WHERE a.id in ({$refer_user})";
                $group_list = Yii::app()->fdb->createCommand($refer_sql)->queryAll();
                foreach ($group_list as $key => $value) {
                    // 以组id得到
                    $group_list_data[$value['user_id']] = $value['name'];
                    $refer_real_name[$value['user_id']] = $value['real_name'];
                }
            }
            // if ($enterprise_user_arr) {
            //     // 查询企业用户证件类型和手机号
            //     $enterprise_user = "'".implode("','" , $enterprise_user_arr)."'";
            //     $enterprise_sql = "select user_id, credentials_type,legalbody_mobile from firstp2p_enterprise where user_id in({$enterprise_user})";
            //     $enterprise_list = Yii::app()->fdb->createCommand($enterprise_sql)->queryAll();
            //     foreach ($enterprise_list as $key => $value) {
            //         $enterprise_user_phone[$value['user_id']] = $value['legalbody_mobile'];
            //     }
            // }

            foreach ($list as $key => $value) {
                // 会员编号
                $value['member_id']       = $this->numTo32($value['id'], $value['user_type']);
                if ($value['mobile']) {
                    $value['mobile']      = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                }
                // if ($value['user_type'] == 1) {
                // 企业用户, 取enterprise表的联系人手机号
                // $value['mobile'] = $enterprise_user_phone[$value['id']];
                // }
                $value['create_time']     = date('Y-m-d H:i:s', $value['create_time']);
                // 帐户类型
                $value['user_purpose']    = $this->getUserPurpose($value['user_purpose']);
                // 服务人所在组别名称
                $value['user_group_name'] = $group_list_data[$value['refer_user_id']];
                // 服务人姓名
                $value['refer_real_name']    = $refer_real_name[$value['refer_user_id']];
                // 本人邀请码
                $value['user_code'] =  $value['prefix'].$this->userIdToHex($value['id']);
                $value['info_status']     = 0;
                if (!empty($authList) && strstr($authList, '/user/Debt/GetUserInfo') || empty($authList)) {
                    $value['info_status'] = 1;
                }
                $listInfo[]               = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        $user_export_auth = 0;
        if (!empty($authList) && strstr($authList, '/user/Debt/UserListExcel') || empty($authList)) {
            $user_export_auth = 1;
        }
        return $this->renderPartial('GetUserList', array('user_export_auth' => $user_export_auth));
    }

    /**
    * 用户信息管理-用户管理 上传查询条件功能
    *
    */
    public function actionAddUserConditionUpload()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
            $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');
            $name = '用户管理-批量条件上传-上传用户ID模板';
            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
            exit;
        }

        if (!empty($_POST)) {
            $type         = intval($_POST['type']);
            if ($type != 1) {
                return $this->actionError('请正确输入查询类型', 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'], 5);
            }
            $file_address = $upload_xls['data'];

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$file_address;
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();      // 获取用户编号列表
            if ($Rows < 2) {
                return $this->actionError('上传的文件中无数据', 5);
            }
            if ($Rows > 100001) {
                return $this->actionError('上传的文件中数据超过一万行', 5);
            }
            unset($data[0]);
            $name = '';
            if ($type == 1) {
                $name .= ' 通过上传用户ID查询 '.date("Y年m月d日 H时i分s秒", time());
                foreach ($data as $key => $value) {
                    $user_id_arr[] = intval($value[0]);
                }
                $user_id_str = "'".implode("','", $user_id_arr)."'";
                $sql = "SELECT id FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                // 根据excel中的用户编号查询数据库用户表
                $user_id_data = Yii::app()->fdb->createCommand($sql)->queryColumn();
                $count = count($data);
                $false_id_arr = array();    // 不在DB中的用户编号
                if (!$user_id_data) {
                    return $this->renderPartial('AddUserConditionUpload', array('end' => 1 , 'count' => $count , 'true_count' => 0 , 'false_count' => $count , 'false_id_str' => $false_id_str));
                }
                foreach ($data as $key => $value) {
                    if (!in_array($value[0], $user_id_data)) {
                        $false_id_arr[] = $value[0];
                    }
                }
                $false_id_str = implode(' , ', $false_id_arr);
                $true_count = count($user_id_data);
                $false_count = count($false_id_arr);
                $data_json = json_encode($user_id_data);
            }
            // 查询条件保存到数据库
            $sql = "INSERT INTO xf_upload_user_condition (type, name , file_address , data_json) 
                    VALUES ({$type} , '{$name}' , '{$file_address}' , '{$data_json}')";
            $add = Yii::app()->fdb->createCommand($sql)->execute();
            $add_id = Yii::app()->fdb->getLastInsertID();
            if ($add) {
                return $this->renderPartial(
                    'AddUserConditionUpload',
                    array('end' => 2 , 'count' => $count , 'true_count' => $true_count , 'false_count' => $false_count , 'false_id_str' => $false_id_str , 'add_id' => $add_id , 'add_name' => $name)
                );
            } else {
                return $this->actionError('保存查询条件失败', 5);
            }
        }
        return $this->renderPartial('AddUserConditionUpload', array('end' => 0));
    }

    /**
     * 用户信息管理-用户管理 导出功能
     */
    public function actionUserListExcel()
    {
        if (!empty($_GET)) {
            set_time_limit(0);
            // 条件筛选
            $where = "WHERE 1 = 1";
            // 校验用户ID
            if (!empty($_GET['id'])) {
                $id     = intval(trim($_GET['id']));
                $where .= " AND user.id = {$id} ";
            }
            // 校验会员编号
            if (!empty($_GET['member_id'])) {
                // 会员编号转为用户id
                $member_user_id = intval($this->de32Tonum(trim($_GET['member_id'])));
                $where .= " AND user.id = {$member_user_id} ";
            }
            // 校验用户名
            if (!empty($_GET['user_name'])) {
                $user_name = trim($_GET['user_name']);
                $where    .= " AND user.user_name = '{$user_name}' ";
            }
            // 校验真实姓名
            if (!empty($_GET['real_name'])) {
                $real_name = trim($_GET['real_name']);
                $where    .= " AND user.real_name = '{$real_name}' ";
            }
            // 校验证件号码
            if (!empty($_GET['idno'])) {
                $idno   = trim($_GET['idno']);
                $idno   = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key); // 证件号码加密
                $where .= " AND user.idno = '{$idno}' ";
            }
            // 校验手机号码
            if (!empty($_GET['mobile'])) {
                $mobile = trim($_GET['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.mobile = '{$mobile}' ";
            }
            // 校验银行卡号
            if (!empty($_GET['bankcard'])) {
                $bankcard = trim($_GET['bankcard']);
                $bankcard = GibberishAESUtil::enc($bankcard, Yii::app()->c->idno_key); // 银行卡号加密
                $where .= " AND card.bankcard = '{$bankcard}' ";
            }
            // 校验服务人邀请码
            if (!empty($_GET['short_alias'])) {
                $short_alias = trim($_GET['short_alias']);
                $where .= " AND coupon_bind.short_alias = '{$short_alias}' ";
            }
            // 校验账户类型
            $user_purpose_type = $_GET['user_purpose'];
            if (!empty($user_purpose_type) || strcmp($user_purpose_type, 0) == 0) {
                $user_purpose = trim($_GET['user_purpose']);
                $where .= " AND user.user_purpose = '{$user_purpose}' ";
            }
            // 第一个搜索块搜索
            if ($_GET['search_type'] == 1 && empty($id) && empty($member_user_id) && empty($user_name) && empty($real_name) && empty($idno) && empty($mobile) && empty($bankcard) && empty($short_alias)) {
                echo iconv("UTF-8", "gbk//TRANSLIT", '<h1>除账户类型外, 请至少选择一个查询条件</h1>');
                exit;
            }
            // 第二个搜索块搜索
            if ($_GET['search_type'] == 2 && empty($_GET['condition_id'])) {
                echo iconv("UTF-8", "gbk//TRANSLIT", '<h1>缺少查询条件，请先上传文件</h1>');
                exit;
            }
            if (!empty($_GET['condition_id'])) {
                $sql = "SELECT * FROM xf_upload_user_condition WHERE id = '{$_GET['condition_id']}' ";
                $condition = Yii::app()->fdb->createCommand($sql)->queryRow();
                if ($condition) {
                    $con_data = json_decode($condition['data_json'], true);
                    if ($con_data) {
                        $con_data_str = implode(',', $con_data);
                        $where .= " AND user.id IN ({$con_data_str}) ";
                    }
                    $redis_key = 'XF_User_List_Download_Condition_'.$condition['id'];
                    $check = Yii::app()->rcache->get($redis_key);
                    if ($check) {
                        echo '<h1>此下载地址已失效</h1>';
                        exit;
                    }
                }
            }
            // 查询数据
            $sql = "SELECT user.id, user.user_name, user.real_name, user.mobile, user.id_type, user.idno, 
                    user.user_purpose, user.user_type,
                    coupon_bind.refer_user_id,
                    card.bankcard, card.bankzone
                    FROM firstp2p_user as user 
                    LEFT JOIN firstp2p_coupon_bind  as coupon_bind
                    ON user.id = coupon_bind.user_id
                    LEFT JOIN firstp2p_user_bankcard as card
                    ON user.id = card.user_id and card.verify_status=1
                    {$where} ORDER BY id DESC ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            if (!$list) {
                echo iconv("UTF-8", "gbk//TRANSLIT", '<h1>暂无数据</h1>');
                exit;
            }
            $enterprise_user_arr = array(); // 企业用户ID数组, 用来查询企业证件类型和手机号
            $enterprise_user_idtype = array();
            // $enterprise_user_phone = array();
            $refer_user_arr = array();      // 服务人id数组
            $group_list_data = array();     // 服务人所属组id和名称
            $refer_real_name = array();     // 服务人id以及姓名
            $refer_mobile = array();        // 服务人手机号
            // 服务人ID拼接字符串
            foreach ($list as $key => $value) {
                if (!empty($value['refer_user_id'])) {
                    array_push($refer_user_arr, $value['refer_user_id']);
                }
                // 企业用户
                if ($value['user_type'] == 1) {
                    array_push($enterprise_user_arr, $value['id']);
                }
            }
            if ($refer_user_arr) {
                $refer_user = "'".implode("','", $refer_user_arr)."'";
                // 获取查询的服务人ID所属组名称以及服务人姓名手机号
                $refer_sql  = "SELECT g.id, g.name, a.real_name, a.id as user_id, a.mobile
                                FROM firstp2p_user as a 
                                INNER JOIN 
                                firstp2p_user_group as g
                                ON a.group_id = g.id  
                                WHERE a.id in ({$refer_user})";
                $group_list = Yii::app()->fdb->createCommand($refer_sql)->queryAll();
                foreach ($group_list as $key => $value) {
                    // key: 服务人ID, value 组名称 姓名 手机号
                    $group_list_data[$value['user_id']] = $value['name'];
                    $refer_real_name[$value['user_id']] = $value['real_name'];
                    $refer_mobile[$value['user_id']] = $value['mobile'];
                }
            }
            if ($enterprise_user_arr) {
                // 查询企业用户证件类型和手机号
                $enterprise_user = "'".implode("','", $enterprise_user_arr)."'";
                $enterprise_sql = "select user_id, credentials_type,legalbody_mobile from firstp2p_enterprise where user_id in({$enterprise_user})";
                $enterprise_list = Yii::app()->fdb->createCommand($enterprise_sql)->queryAll();
                foreach ($enterprise_list as $key => $value) {
                    $enterprise_user_idtype[$value['user_id']] = $value['credentials_type'];
                    // $enterprise_user_phone[$value['user_id']] = $value['legalbody_mobile'];
                }
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
            $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '会员名称');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '用户姓名');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '手机号');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '证件类型');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '证件号码');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '银行卡号');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '开户行信息');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '服务人ID');
            $objPHPExcel->getActiveSheet()->setCellValue('J1', '服务人姓名');
            $objPHPExcel->getActiveSheet()->setCellValue('K1', '服务人手机号');
            $objPHPExcel->getActiveSheet()->setCellValue('L1', '服务人所在组别名称');
            $objPHPExcel->getActiveSheet()->setCellValue('M1', '账户类型');

            foreach ($list as $key => $value) {
                if ($value['mobile']) {
                    $value['mobile']        = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    if (empty($value['mobile'])) {
                        $value['mobile'] = "";
                    }
                }
                if ($value['idno']) {
                    $value['idno']          = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key); // 证件号码解密
                    if (empty($value['idno'])) {
                        $value['idno'] = "";
                    }
                }
                $value['id_type']           = $this->getIdType($value['id_type'], $value['idno']);
                if ($value['bankcard']) {
                    $value['bankcard']      = GibberishAESUtil::dec($value['bankcard'], Yii::app()->c->idno_key); // 银行卡号解密
                    if (empty($value['bankcard'])) {
                        $value['bankcard'] = "";
                    }
                }
                $value['refer_mobile']      = $refer_mobile[$value['refer_user_id']];
                if ($value['refer_mobile']) {
                    $value['refer_mobile']  = GibberishAESUtil::dec($value['refer_mobile'], Yii::app()->c->idno_key); // 手机号解密
                    if (empty($value['refer_mobile'])) {
                        $value['refer_mobile'] = "";
                    }
                }
                $value['user_purpose']      = $this->getUserPurpose($value['user_purpose']);
                // 企业用户
                if ($value['user_type'] == 1) {
                    //     $value['mobile']        = $enterprise_user_phone[$value['id']];
                    $value['id_type']       = $this->getEnterpriseIdType($enterprise_user_idtype[$value['id']]);
                }
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['user_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['real_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['mobile']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['id_type']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['idno'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['bankcard'].' ');
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['bankzone']);

                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['refer_user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2), $refer_real_name[$value['refer_user_id']]);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['refer_mobile']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2), $group_list_data[$value['refer_user_id']]);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2), $value['user_purpose']);
            }
            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '用户记录 '.date("Y年m月d日 H时i分s秒", time());
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");
            if ($condition) {
                $redis_time = 3600;
                $redis_key = 'XF_User_List_Download_Condition_'.$condition['id'];
                $set = Yii::app()->rcache->set($redis_key, date('Y-m-d H:i:s', time()), $redis_time);
                if (!$set) {
                    Yii::log("{$redis_key} redis download set error", "error");
                }
            }
            $objWriter->save('php://output');
        }
    }


    /**
     * 用户管理 详情 张健
     * @param id    int     用户ID
     */
    public function actionGetUserInfo()
    {
        // 校验用户ID
        if (!empty($_GET['id'])) {
            $id   = intval($_GET['id']);
            $sql = "SELECT user.id, user.user_name, user.real_name, user.mobile, user.create_time, 
            user.user_purpose, user.id_type, user.idno, user.user_type,
            user_group.name, user_group.prefix,
            coupon_bind.short_alias, coupon_bind.refer_user_id, 
            coupon_bind.invite_user_group_id, coupon_bind.refer_user_name, card.bankcard, card.bankzone
            FROM firstp2p_user as user 
            LEFT JOIN firstp2p_coupon_bind  as coupon_bind
            ON user.id = coupon_bind.user_id
            LEFT JOIN firstp2p_user_group as user_group
            ON user.group_id = user_group.id
            LEFT JOIN firstp2p_user_bankcard as card
            ON user.id = card.user_id and card.verify_status=1
            WHERE user.id = {$id} limit 1";
            $info = Yii::app()->fdb->createCommand($sql)->queryRow();
            // 用户存在
            if ($info) {
                // 查询服务人所在组别名称
                // 服务人id
                $refer_user_id = intval($info['refer_user_id']);
                $sql             = "SELECT g.name, a.real_name 
                                    FROM firstp2p_user as a 
                                    INNER JOIN 
                                    firstp2p_user_group as g
                                    ON a.group_id = g.id  
                                    WHERE a.id = {$refer_user_id}";
                $refer_info = Yii::app()->fdb->createCommand($sql)->queryRow();
            }
            $info['create_time']            = date('Y-m-d H:i:s', $info['create_time']);
            $info['idno']                   = GibberishAESUtil::dec($info['idno'], Yii::app()->c->idno_key); // 证件号码解密
            $info['mobile']                 = GibberishAESUtil::dec($info['mobile'], Yii::app()->c->idno_key); // 手机号解密
            $info['bankcard']               = GibberishAESUtil::dec($info['bankcard'], Yii::app()->c->idno_key); // 银行卡号解密
            // 服务人所属组名
            $info['invite_user_group_name']    = $refer_info['name'];
            // 服务人姓名
            $info['refer_real_name']        = $refer_info['real_name'];
            // 证件类型
            $info['id_type'] = $this->getIdType($info['id_type'], $info['idno']);
            // 帐户类型
            $info['user_purpose'] = $this->getUserPurpose($info['user_purpose']);
            // 会员编号
            $info['member_id'] = $this->numTo32($info['id'], $info['user_type']);
            // 本人邀请码
            $info['user_code'] =  $info['prefix'].$this->userIdToHex($info['id']);
            // 企业用户, 查询企业用户的证件类型和手机号
            // if ($info['user_type'] == 1) {
            //     $enterprise_sql = "select user_id, credentials_type,legalbody_mobile from firstp2p_enterprise where user_id={$id}";
            //     $enterprise_list = Yii::app()->fdb->createCommand($enterprise_sql)->queryRow();
            //     $info['mobile'] = $enterprise_list['legalbody_mobile'];
            //     $info['id_type'] = $this->getEnterpriseIdType($enterprise_list['credentials_type']);
            // }
        }

        return $this->renderPartial('GetUserInfo', array('info' => $info));
    }

    /**
    * 获取普通用户证件类型
    * @param id_type    int     证件类型
    * @param idno       string     证件号码
    */
    private function getIdType($id_type, $id_no)
    {
        // 证件类型做key, value转换
        $id_type_arr = array("1" => "身份证", "2" => "护照", "3" => "军官",
                    "4" => "港澳", "6" => "台湾", "99" => "其他");
        if (in_array($id_type, array_keys($id_type_arr))) {
            // id_type 是1,2,3,4,6,99
            return $id_type_arr[$id_type];
        } else {
            // 长度44的当身份证号，24就是港澳
            if (strlen($id_no) == 44) {
                return "身份证";
            } elseif (strlen($id_no) == 22) {
                return "港澳";
            }
        }
        
        return "";
    }

    /**
    * 获取企业用户证件类型
    * @param id_type    int     证件类型
    */
    private function getEnterpriseIdType($id_type)
    {
        // 证件类型做key, value转换
        $enterprise_type_arr = array("0" => "其他", "1" => "营业执照", "2" => "组织机构代码证",
                    "3" => "三证合一营业执照");
        if (in_array($id_type, array_keys($enterprise_type_arr))) {
            // id_type 是1,2,3,4,6,99
            return $enterprise_type_arr[$id_type];
        }
        
        return "";
    }

    /**
    * 获取帐户类型
    @param user_purpose    int     帐户类型
    */
    private function getUserPurpose($user_purpose)
    {
        // 帐户类型做key, value转换
        $user_purpose_arr = array("0" => "借贷混合用户", "1" => "投资户", "2" => "融资户", "3" => "咨询户",
                        "4" => "担保/代偿I户", "5" => "渠道户", "6" => "渠道虚拟户", "7" => "资产收购户",
                        "8" => "担保/代偿II-b户", "9" => "受托资产管理户", "10" => "交易中心（所）", "11" => "平台户",
                        "12" => "保证金户", "13" => "支付户", "14" => "投资券户", "15" => "红包户",
                        "16" => "担保/代偿II-a户", "17" => "放贷户", "18" => "垫资户", "19" => "管理户",
                        "20" => "商户账户", "21" => "营销补贴户");
        if (in_array($user_purpose, array_keys($user_purpose_arr))) {
            // id_type 是1,2,3,4,6,99
            return $user_purpose_arr[$user_purpose];
        }
        return "";
    }

    /**
    * 根据用户id和用户类型获取会员编号
    * no no为用户ID,即firstp2p_user.id
    * type type为用户类型，即firstp2p_user.user_type
    */
    private function numTo32($no, $type=0)
    {
        $no+=34000000;
        $char_array=array("2", "3", "4", "5", "6", "7", "8", "9",
           "A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M",
           "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $rtn = "";
        while ($no >= 32) {
            $rtn = $char_array[fmod($no, 32)].$rtn;
            $no = floor($no/32);
        }

        $prefix = '00';
        if ($type == 1) {
            $prefix = '66';
        }
        return $prefix.$char_array[$no].$rtn;
    }

    private function userIdToHex($user_id, $start_id = 1000000, $cardinal_number = 16777216)
    {
        if (empty($user_id)) {
            return false;
        }
        if ($user_id>=$start_id) {
            // 转32进制
            // 从g开始以此类推h、i、j、.....
            $convert_value = base_convert($user_id+$cardinal_number, 10, 32);
            $str_hex = strtoupper($convert_value);
            $search = array('I','O');
            $replace = array('Y','Z');
            // 替换掉模糊的i和o
            $result = str_replace($search, $replace, $str_hex);
        } else {
            $str_hex = strtoupper(dechex($user_id));
            $result = str_pad($str_hex, 5, '0', STR_PAD_LEFT);
        }

        return $result;
    }

    /**
    * 会员编号转为用户ID
    */
    private function de32Tonum($no_32)
    {
        $char_array=array("2", "3", "4", "5", "6", "7", "8", "9",
            "A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M",
            "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $num = substr($no_32, 2);//2wwch
        $no = 0;
        for ($i = 0;$i <= strlen($num)-1;$i++) {
            $no = $no * 32 + array_search($num[$i], $char_array);
        }
        $no = $no - 34000000;
        return $no;
    }

    /**
     * 校验用户ID
     * @param id    int     用户ID
     * @return array
     */
    private function checkUserID($id)
    {
        $sql    = "SELECT * FROM firstp2p_user WHERE id = {$id} ";
        $result = Yii::app()->fdb->createCommand($sql)->queryRow();
        if ($result) {
            $idno   = GibberishAESUtil::dec($result['idno'], Yii::app()->c->idno_key);
            $mobile = GibberishAESUtil::dec($result['mobile'], Yii::app()->c->idno_key);
            if (empty($result['real_name'])) {
                return false;
            } elseif (empty($result['idno']) || empty($idno)) {
                return false;
            } elseif (empty($result['mobile']) || empty($mobile)) {
                return false;
            } else {
                return $result;
            }
        } else {
            return false;
        }
    }

    /**
     * 校验投资记录ID
     * @param tender_id     int     投资记录ID
     * @param deal_type     int     所属平台 1尊享 2普惠
     * @return array
     */
    private function checkTenderID($deal_type, $tender_id)
    {
        if ($deal_type == 1) {
            $model  = Yii::app()->fdb;
            $sql    = "SELECT * FROM firstp2p_deal_load WHERE id = {$tender_id} ";
            $result = $model->createCommand($sql)->queryRow();
        } elseif ($deal_type == 2) {
            $model  = Yii::app()->phdb;
            $sql    = "SELECT * FROM firstp2p_deal_load WHERE id = {$tender_id} ";
            $result = $model->createCommand($sql)->queryRow();
        } elseif (in_array($deal_type, array(3 , 4, 5))) {
            $model  = Yii::app()->offlinedb;
            $sql    = "SELECT * FROM offline_deal_load WHERE id = {$tender_id} AND platform_id = {$deal_type} ";
            $result = $model->createCommand($sql)->queryRow();
        }
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 校验项目ID
     * @param deal_id       int     项目ID
     * @param deal_type     int     所属平台 1尊享 2普惠
     * @return array
     */
    private function checkDealID($deal_type, $deal_id)
    {
        if ($deal_type == 1) {
            $model  = Yii::app()->fdb;
            $sql    = "SELECT * FROM firstp2p_deal WHERE id = {$deal_id} ";
            $result = $model->createCommand($sql)->queryRow();
        } elseif ($deal_type == 2) {
            $model  = Yii::app()->phdb;
            $sql    = "SELECT * FROM firstp2p_deal WHERE id = {$deal_id} ";
            $result = $model->createCommand($sql)->queryRow();
        } elseif (in_array($deal_type, array(3 , 4, 5))) {
            $model  = Yii::app()->offlinedb;
            $sql    = "SELECT * FROM offline_deal WHERE id = {$deal_id} AND platform_id = {$deal_type} ";
            $result = $model->createCommand($sql)->queryRow();
        }
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 校验项目名称
     * @param deal_name     int     用户ID
     * @param deal_type     int     所属平台 1尊享 2普惠
     * @return array
     */
    private function checkDealName($deal_type, $deal_name)
    {
        if ($deal_type == 1) {
            $model  = Yii::app()->fdb;
            $sql    = "SELECT * FROM firstp2p_deal WHERE name = '{$deal_name}' ";
            $result = $model->createCommand($sql)->queryRow();
        } elseif ($deal_type == 2) {
            $model  = Yii::app()->phdb;
            $sql    = "SELECT * FROM firstp2p_deal WHERE name = '{$deal_name}' ";
            $result = $model->createCommand($sql)->queryRow();
        } elseif (in_array($deal_type, array(3 , 4, 5))) {
            $model  = Yii::app()->offlinedb;
            $sql    = "SELECT * FROM offline_deal WHERE name = '{$deal_name}' AND platform_id = {$deal_type} ";
            $result = $model->createCommand($sql)->queryRow();
        }
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 上传图片 张健
     * @param content   string  图片的base64内容
     * @return string
     */
    private function upload_base64($content)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $content, $result)) {
            $pic_type    = $result[2]; // 匹配出图片后缀名
            $dir_name    = date('Ymd');
            $dir_address = "upload/" . $dir_name . '/';
            if (!file_exists($dir_address)) {
                mkdir($dir_address, 0777, true);
            }
            $pic_name    = time() . rand(10000, 99999) . ".{$pic_type}";
            $pic_address = $dir_address . $pic_name;
            if (file_put_contents($pic_address, base64_decode(str_replace($result[1], '', $content)))) {
                return $pic_address;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 债权扣除记录 添加 张健
     * @param deal_type         int     所属平台 1尊享 2普惠
     * @param user_id           int     用户ID
     * @param tender_id         int     投资记录ID
     * @param deal_id           int     项目ID(非必传,与项目名称二选一)
     * @param deal_name         string  项目名称(非必传,与项目ID二选一)
     * @param buyback_user_id   int     回购用户ID(非必传,默认12131543)
     * @param debt_account      float   债权划扣金额
     * @param agreement_pic     file    授权债转协议图片
     * @return json
     */
    public function actionAddDebtDeduct()
    {
        if (!empty($_POST)) {
            // 校验所属平台
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || !in_array($_POST['deal_type'], array(1, 2, 3, 4, 5))) {
                return $this->actionError('请正确输入所属平台', 5);
            }
            // 校验用户ID
            if (empty($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
                return $this->actionError('请正确输入用户ID', 5);
            }
            $user_id_info = $this->checkUserID($_POST['user_id']);
            if (!$user_id_info) {
                return $this->actionError('用户ID输入错误', 5);
            }
            // 校验投资记录ID
            if (empty($_POST['tender_id']) || !is_numeric($_POST['tender_id'])) {
                return $this->actionError('请正确输入投资记录ID', 5);
            }
            $tender_id_info = $this->checkTenderID($_POST['deal_type'], $_POST['tender_id']);
            if (!$tender_id_info) {
                return $this->actionError('投资记录ID输入错误', 5);
            }
            // 校验投资记录黑名单
            if ($tender_id_info['black_status'] == 2) {
                return $this->actionError('此投资记录已被加入黑名单，原因：'.$tender_id_info['join_reason'], 5);
            }
            // 校验项目ID和项目名称至少有一个
            if (empty($_POST['deal_id']) && empty($_POST['deal_name'])) {
                return $this->actionError('请输入借款ID或借款标题其中至少一项', 5);
            }
            // 校验借款ID
            $deal_id_info = array();
            if (!empty($_POST['deal_id'])) {
                if (!is_numeric($_POST['deal_id'])) {
                    return $this->actionError('请正确输入借款ID', 5);
                }
                $deal_id_info = $this->checkDealID($_POST['deal_type'], $_POST['deal_id']);
                if (!$deal_id_info) {
                    return $this->actionError('借款ID输入错误', 5);
                }
            }
            // 校验借款标题
            $deal_name_info = array();
            if (!empty($_POST['deal_name'])) {
                $deal_name      = trim($_POST['deal_name']);
                $deal_name_info = $this->checkDealName($_POST['deal_type'], $deal_name);
                if (!$deal_name_info) {
                    return $this->actionError('借款标题输入错误', 5);
                }
            }
            // 校验借款ID与借款标题是否匹配
            if ($deal_id_info && $deal_name_info) {
                if ($deal_id_info['id'] != $deal_name_info['id']) {
                    return $this->actionError('借款ID与借款标题不匹配', 5);
                }
            }
            if ($deal_id_info) {
                $deal_info = $deal_id_info;
            }
            if ($deal_name_info) {
                $deal_info = $deal_name_info;
            }
            // 校验项目黑名单
            $sql = "SELECT id FROM ag_wx_debt_black_list WHERE type = {$_POST['deal_type']} AND deal_id = {$deal_info['id']} AND status = 1";
            $black_list = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($black_list) {
                return $this->actionError('此借款项目已被加入债转黑名单', 5);
            }
            // 校验投资记录与项目是否匹配
            if ($tender_id_info['deal_id'] != $deal_info['id']) {
                return $this->actionError('投资记录与项目不匹配', 5);
            }
            // 校验投资记录与用户是否匹配
            if ($tender_id_info['user_id'] != $user_id_info['id']) {
                return $this->actionError('投资记录与用户不匹配', 5);
            }
            // 校验回购用户ID
            if (empty($_POST['buyback_user_id']) || !is_numeric($_POST['buyback_user_id'])) {
                return $this->actionError('请正确输入回购用户ID', 5);
            }
            $buyback_user_id_info = $this->checkUserID($_POST['buyback_user_id']);
            if (!$buyback_user_id_info) {
                return $this->actionError('回购用户ID输入错误', 5);
            }
            // 校验用户ID与回购用户ID是否相同
            if ($user_id_info['id'] == $buyback_user_id_info['id']) {
                return $this->actionError('用户ID与回购用户ID不能相同', 5);
            }
            // $buyback_user_id_array = Yii::app()->c->buyback_user_id;
            $sql      = "SELECT * FROM ag_wx_assignee_info WHERE user_id = '{$_POST['buyback_user_id']}' AND status != 0 ";
            $assignee = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$assignee) {
                return $this->actionError('此回购用户ID不在受让方列表中', 5);
            }
            if ($assignee['status'] == 1) {
                return $this->actionError('此回购用户ID在受让方列表中处于待审核状态', 5);
            }
            if ($assignee['status'] == 3) {
                return $this->actionError('此回购用户ID在受让方列表中处于暂停状态', 5);
            }
            // 校验债权划扣金额
            if (empty($_POST['debt_account']) || !is_numeric($_POST['debt_account']) || bccomp($_POST['debt_account'], 0, 2) == -1) {
                return $this->actionError('请正确输入债权划扣金额', 5);
            }
            if (bccomp($_POST['debt_account'], $tender_id_info['wait_capital'], 2) == 1) {
                return $this->actionError('债权划扣金额不能大于待还本金', 5);
            }
            if (bccomp($_POST['debt_account'], $tender_id_info['wait_capital'], 2) != 0) {
                if (bccomp($_POST['debt_account'], 100, 2) == -1) {
                    return $this->actionError('债权划扣金额不能小于100元', 5);
                }
                $wait_capital = bcsub($tender_id_info['wait_capital'], $_POST['debt_account'], 2);
                if (bccomp($wait_capital, 100, 2) == -1) {
                    return $this->actionError('扣除债权划扣金额后的待还本金不能小于100元', 5);
                }
            }
            if (bccomp($_POST['debt_account'], ($assignee['transferability_limit'] - $assignee['transferred_amount']), 2) == 1) {
                return $this->actionError('债权划扣金额不能大于此回购用户的剩余可受让金额', 5);
            }
            $file = $this->upload_rar('file');
            if ($file['code'] !== 0) {
                return $this->actionError($file['info'], 5);
            }
            // 添加数据
            $sql = "SELECT * FROM firstp2p_debt_deduct_log WHERE user_id = {$user_id_info['id']} AND tender_id = {$tender_id_info['id']} AND deal_id = {$deal_info['id']} AND deal_type = {$_POST['deal_type']} AND status IN (0 , 1)";
            $check = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check) {
                return $this->actionError('您添加的投资记录有待处理的扣除任务', 5);
            }
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $time       = time();
            $ip         = Yii::app()->request->userHostAddress;
            $sql        = "INSERT INTO firstp2p_debt_deduct_log (user_id , tender_id , deal_id , deal_type , buyback_user_id , debt_account , op_user_id , addtime , addip , deal_name , agreement_pic) VALUES({$user_id_info['id']} , {$tender_id_info['id']} , {$deal_info['id']} , {$_POST['deal_type']} , {$buyback_user_id_info['id']} , {$_POST['debt_account']} , {$op_user_id} , {$time} , '{$ip}' , '{$deal_info['name']}' , '{$file['data']}') ";
            $result     = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                return $this->actionError('添加债权扣除记录失败', 5);
            }

            return $this->actionSuccess('添加债权扣除记录成功', 3);
        }

        return $this->renderPartial('AddDebtDeduct', array());
    }

    /**
     * 债权扣除记录 列表 张健
     * 提供查询字段：
     * @param deal_type         int         所属平台 1尊享 2普惠
     * @param user_id           int         用户ID
     * @param buyback_user_id   int         回购用户ID
     * @param status            int         状态
     * @param deal_name         string      状态
     * @param limit             int         每页数据显示量 默认10
     * @param page              int         当前页数 默认1
     */
    public function actionDebtDeductList()
    {
        // 条件筛选
        $where = "";
        // 校验所属平台
        if (empty($_GET['deal_type'])) {
            $_GET['deal_type'] = 1;
        }
        if (!empty($_GET['deal_type'])) {
            $deal_type = intval($_GET['deal_type']);
            $where    .= " AND l.deal_type = {$deal_type} ";
        }
        // 校验用户ID
        if (!empty($_GET['user_id'])) {
            $user_id = intval($_GET['user_id']);
            $where  .= " AND l.user_id = {$user_id} ";
        }
        // 校验用户手机号
        if (!empty($_GET['mobile'])) {
            $mobile = trim($_GET['mobile']);
            $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
            $where .= " AND u.mobile = '{$mobile}' ";
        }
        // 校验回购用户ID
        if (!empty($_GET['buyback_user_id'])) {
            $buyback_user_id = intval($_GET['buyback_user_id']);
            $where  .= " AND l.buyback_user_id = {$buyback_user_id} ";
        }
        // 校验状态
        if (!empty($_GET['status'])) {
            $sta     = intval($_GET['status']) - 1;
            $where  .= " AND l.status = {$sta} ";
        }
        // 校验项目名称
        if (!empty($_GET['deal_name'])) {
            $deal_name = trim($_GET['deal_name']);
            $where    .= " AND l.deal_name = '{$deal_name}' ";
        }
        // 校验项目ID
        if (!empty($_GET['deal_id'])) {
            $deal_id = intval($_GET['deal_id']);
            $where  .= " AND l.deal_id = {$deal_id} ";
        }
        // 校验投资记录ID
        if (!empty($_GET['deal_load_id'])) {
            $deal_load_id = intval($_GET['deal_load_id']);
            $where       .= " AND l.tender_id = {$deal_load_id} ";
        }
        // 校验每页数据显示量
        if (!empty($_GET['limit'])) {
            $limit = intval($_GET['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }
        $sql   = "SELECT count(l.id) AS count FROM firstp2p_debt_deduct_log AS l INNER JOIN firstp2p_user AS u ON l.user_id = u.id {$where} ";
        $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
        // 查询数据
        $sql = "SELECT l.* , u.mobile FROM firstp2p_debt_deduct_log AS l INNER JOIN firstp2p_user AS u ON l.user_id = u.id {$where} ORDER BY l.id DESC ";
        $page_count = ceil($count / $limit);
        if ($page > $page_count) {
            $page = $page_count;
        }
        if ($page < 1) {
            $page = 1;
        }
        $pass = ($page - 1) * $limit;
        $sql .= " LIMIT {$pass} , {$limit} ";
        $list = Yii::app()->fdb->createCommand($sql)->queryAll();
        foreach ($list as $key => $value) {
            $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
            if ($value['start_time'] != 0) {
                $value['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
            } else {
                $value['start_time'] = '';
            }
            if ($value['successtime'] != 0) {
                $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
            } else {
                $value['successtime'] = '';
            }
            $value['debt_account'] = number_format($value['debt_account'], 2, '.', ',');
            $value['mobile']       = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
            $listInfo[] = $value;
        }

        $criteria = new CDbCriteria();
        $pages    = new CPagination($count);
        $pages->pageSize = $limit;
        $pages->applyLimit($criteria);
        $pages = $this->widget('CLinkPager', array(
                'header'=>'',
                'firstPageLabel' => '首页',
                'lastPageLabel' => '末页',
                'prevPageLabel' => '上一页',
                'nextPageLabel' => '下一页',
                'pages' => $pages,
                'maxButtonCount'=>8,
                'cssFile'=>false,
                'htmlOptions' =>array("class"=>"pagination"),
                'selectedPageCssClass'=>"active"
            ), true);
        $page_count_arr = array();
        for ($i = 1; $i <= $page_count; $i++) {
            $page_count_arr[$i] = $i;
        }

        $status[0] = '待处理';
        $status[1] = '已启动';
        $status[2] = '交易完成';
        $status[3] = '交易失败';

        return $this->renderPartial('DebtDeductList', array('listInfo' => $listInfo, 'pages' => $pages , 'count' => $count , 'limit' => $limit , 'page' => $page , 'page_count_arr' => $page_count_arr , 'deal_type' => $deal_type , 'status' => $status));
    }

    /**
     * 债权扣除记录 查看授权债转协议图片 张健
     * @param id    int     记录ID
     */
    public function actionAgreementPic()
    {
        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM firstp2p_debt_deduct_log WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
        }

        return $this->renderPartial('AgreementPic', array('res' => $res));
    }

    /**
     * 债权扣除记录 编辑 张健
     * @param id                int     记录ID
     * @param tender_id         int     投资记录ID
     * @param deal_id           int     项目ID
     * @param buyback_user_id   int     回购用户ID(非必传,默认12131543)
     * @param debt_account      float   债权划扣金额
     * @return json
     */
    public function actionEditDebtDeduct()
    {
        if (!empty($_POST)) {
            // 校验记录ID
            if (empty($_POST['id'])) {
                return $this->actionError('请输入记录ID', 5);
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM firstp2p_debt_deduct_log WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                return $this->actionError('记录ID输入错误', 5);
            }
            if ($old['status'] != 0) {
                return $this->actionError('此债权扣除记录不能编辑', 5);
            }
            // 校验所属平台
            if (empty($_POST['deal_type']) || !is_numeric($_POST['deal_type']) || !in_array($_POST['deal_type'], array(1, 2, 3, 4, 5))) {
                return $this->actionError('请正确输入所属平台', 5);
            }
            // 校验投资记录ID
            if (empty($_POST['tender_id']) || !is_numeric($_POST['tender_id'])) {
                return $this->actionError('请正确输入投资记录ID', 5);
            }
            $tender_id_info = $this->checkTenderID($_POST['deal_type'], $_POST['tender_id']);
            if (!$tender_id_info) {
                return $this->actionError('投资记录ID输入错误', 5);
            }
            // 校验投资记录黑名单
            if ($tender_id_info['black_status'] == 2) {
                return $this->actionError('此投资记录已被加入黑名单，原因：'.$tender_id_info['join_reason'], 5);
            }
            // 校验借款ID和借款标题至少有一个
            if (empty($_POST['deal_id']) && empty($_POST['deal_name'])) {
                return $this->actionError('请输入借款ID或借款标题其中至少一项', 5);
            }
            // 校验借款ID
            $deal_id_info = array();
            if (!empty($_POST['deal_id'])) {
                if (!is_numeric($_POST['deal_id'])) {
                    return $this->actionError('请正确输入借款ID', 5);
                }
                $deal_id_info = $this->checkDealID($_POST['deal_type'], $_POST['deal_id']);
                if (!$deal_id_info) {
                    return $this->actionError('借款ID输入错误', 5);
                }
            }
            // 校验项目名称
            $deal_name_info = array();
            if (!empty($_POST['deal_name'])) {
                $deal_name      = trim($_POST['deal_name']);
                $deal_name_info = $this->checkDealName($_POST['deal_type'], $deal_name);
                if (!$deal_name_info) {
                    return $this->actionError('借款标题输入错误', 5);
                }
            }
            // 校验项目ID与项目名称是否匹配
            if ($deal_id_info && $deal_name_info) {
                if ($deal_id_info['id'] != $deal_name_info['id']) {
                    return $this->actionError('借款ID与借款标题不匹配', 5);
                }
            }
            if ($deal_id_info) {
                $deal_info = $deal_id_info;
            }
            if ($deal_name_info) {
                $deal_info = $deal_name_info;
            }
            // 校验项目黑名单
            $sql = "SELECT id FROM ag_wx_debt_black_list WHERE type = {$_POST['deal_type']} AND deal_id = {$deal_info['id']} AND status = 1";
            $black_list = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($black_list) {
                return $this->actionError('此借款项目已被加入债转黑名单', 5);
            }
            // 校验投资记录与项目是否匹配
            if ($tender_id_info['deal_id'] != $deal_info['id']) {
                return $this->actionError('投资记录与项目不匹配', 5);
            }
            // 校验投资记录与用户是否匹配
            if ($tender_id_info['user_id'] != $old['user_id']) {
                return $this->actionError('投资记录与用户不匹配', 5);
            }
            // 校验回购用户ID
            if (empty($_POST['buyback_user_id']) || !is_numeric($_POST['buyback_user_id'])) {
                return $this->actionError('请正确输入回购用户ID', 5);
            }
            $buyback_user_id_info = $this->checkUserID($_POST['buyback_user_id']);
            if (!$buyback_user_id_info) {
                return $this->actionError('回购用户ID输入错误', 5);
            }
            // 校验用户ID与回购用户ID是否相同
            if ($old['user_id'] == $buyback_user_id_info['id']) {
                return $this->actionError('用户ID与回购用户ID不能相同', 5);
            }
            // $buyback_user_id_array = Yii::app()->c->buyback_user_id;
            $sql      = "SELECT * FROM ag_wx_assignee_info WHERE user_id = '{$_POST['buyback_user_id']}' AND status != 0 ";
            $assignee = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$assignee) {
                return $this->actionError('此回购用户ID不在受让方列表中', 5);
            }
            if ($assignee['status'] == 1) {
                return $this->actionError('此回购用户ID在受让方列表中处于待审核状态', 5);
            }
            if ($assignee['status'] == 3) {
                return $this->actionError('此回购用户ID在受让方列表中处于暂停状态', 5);
            }
            // 校验债权划扣金额
            if (empty($_POST['debt_account']) || !is_numeric($_POST['debt_account'])) {
                return $this->actionError('请正确输入债权划扣金额', 5);
            }
            if (bccomp($_POST['debt_account'], $tender_id_info['wait_capital'], 2) == 1) {
                return $this->actionError('债权划扣金额不能大于待还本金', 5);
            }
            if (bccomp($_POST['debt_account'], $tender_id_info['wait_capital'], 2) != 0) {
                $wait_capital = bcsub($tender_id_info['wait_capital'], $_POST['debt_account'], 2);
                if (bccomp($_POST['debt_account'], 100, 2) == -1) {
                    return $this->actionError('债权划扣金额不能小于100元', 5);
                }
                if (bccomp($wait_capital, 100, 2) == -1) {
                    return $this->actionError('扣除债权划扣金额后的待还本金不能小于100元', 5);
                }
            }
            if (bccomp($_POST['debt_account'], ($assignee['transferability_limit'] - $assignee['transferred_amount']), 2) == 1) {
                return $this->actionError('债权划扣金额不能大于此回购用户的剩余可受让金额', 5);
            }
            $file = $this->upload_rar('file');
            if ($file['code'] == 0) {
                $pic = " , agreement_pic = '{$file['data']}' ";
            } else {
                $pic = '';
            }
            // 修改数据
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            $sql        = "UPDATE firstp2p_debt_deduct_log SET tender_id = {$tender_id_info['id']} , deal_id = {$deal_info['id']} , buyback_user_id = {$buyback_user_id_info['id']} , deal_type = {$_POST['deal_type']} , debt_account = {$_POST['debt_account']} , op_user_id = {$op_user_id} , addip = '{$ip}' , deal_name = '{$deal_info['name']}' {$pic} WHERE id = {$old['id']} ";
            $result     = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                return $this->actionError('保存债权扣除记录失败', 5);
            }
            return $this->actionSuccess('保存债权扣除记录成功', 3);
        }

        // 校验记录ID
        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM firstp2p_debt_deduct_log WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                return $this->actionError('记录ID输入错误', 5);
            }
            $old['addtime'] = date('Y-m-d H:i:s', $old['addtime']);
            if ($old['start_time'] != 0) {
                $old['start_time'] = date('Y-m-d H:i:s', $old['start_time']);
            } else {
                $old['start_time'] = '';
            }
            if ($old['successtime'] != 0) {
                $old['successtime'] = date('Y-m-d H:i:s', $old['successtime']);
            } else {
                $old['successtime'] = '';
            }
            $agreement_pic             = explode('/', $old['agreement_pic']);
            $old['agreement_pic_a']    = $agreement_pic[2];
        } else {
            return $this->actionError('请输入记录ID', 5);
        }

        return $this->renderPartial('EditDebtDeduct', array('info' => $old));
    }

    /**
     * 债权扣除记录 启动 张健
     * @param id    int     记录ID
     * @return json
     */
    public function actionStartDebtDeduct()
    {
        // 校验记录ID
        if (!empty($_POST['id'])) {
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM firstp2p_debt_deduct_log WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array(), 1000, '记录ID输入错误');
            }
            // 校验记录状态
            if ($old['status'] != 0) {
                $this->echoJson(array(), 1001, '此记录不能重复启动');
            }
            $time   = time();
            $sql    = "UPDATE firstp2p_debt_deduct_log SET status = 1 , start_time = {$time} WHERE id = {$old['id']} ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array(), 1002, '启动失败');
            }
            $this->echoJson(array(), 0, '启动成功');
        }
    }

    /**
     * 尊享 待还款列表 列表 张健
     * @param product_class     string      产品大类
     * @param deal_name         string      借款标题
     * @param approve_number    string      交易所备案号
     * @param project_name      string      项目名称
     * @param advisory_name     string      融资经办机构
     * @param real_name         string      借款人姓名
     * @param repay_type        int         还款资金类型 1-本金 2-利息
     * @param repay_status      int         还款状态 0-待还 1-已还清
     * @param start             string      开始日期
     * @param end               string      截止日期
     * @param limit             int         每页数据显示量 默认10
     * @param page              int         当前页数 默认1
     */
    public function actionLoanRepayList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = "";
            // 校验产品大类
            if (!empty($_POST['product_class'])) {
                $class  = trim($_POST['product_class']);
                $where .= " AND project_product_class = '{$class}' ";
            }
            // 校验借款标题
            if (!empty($_POST['deal_id'])) {
                $deal_id = intval($_POST['deal_id']);
                $where  .= " AND deal_id = {$deal_id} ";
            }
            // 校验借款标题
            if (!empty($_POST['deal_name'])) {
                $deal_name = trim($_POST['deal_name']);
                $where    .= " AND deal_name = '{$deal_name}' ";
            }
            // 校验交易所备案号
            if (!empty($_POST['approve_number'])) {
                $approve_number = trim($_POST['approve_number']);
                $where         .= " AND jys_record_number = '{$approve_number}' ";
            }
            // 校验项目名称
            if (!empty($_POST['project_name'])) {
                $project_name = trim($_POST['project_name']);
                $where       .= " AND project_name = '{$project_name}' ";
            }
            // 校验融资经办机构
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $where        .= " AND deal_advisory_name = '{$advisory_name}' ";
            }
            // 校验借款人姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where    .= " AND deal_user_real_name = '{$real_name}' ";
            }
            // 校验还款资金类型
            if (!empty($_POST['repay_type'])) {
                $repay_type = intval($_POST['repay_type']);
                $where     .= " AND repay_type = {$repay_type} ";
            }
            // 校验还款状态
            if (!empty($_POST['repay_status'])) {
                $repay_status = intval($_POST['repay_status']) - 1;
                $where       .= " AND repay_status = {$repay_status} ";
            }
            // 校验开始日期
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']) - 28800;
                $where .= " AND loan_repay_time >= {$start} ";
            }
            // 校验截止日期
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']) - 28800;
                $where .= " AND loan_repay_time <= {$end} ";
            }
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
            //后台用户
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            if (!empty($adminUserInfo['username'])) {
                if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                    if ($adminUserInfo['user_type'] == 2) {
                        $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                        if (!empty($deallist)) {
                            $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                            $where .= " AND deal_id IN({$dealIds})";
                        } else {
                            //不是超级管理员并且没有$dealIds
                            $where .= " AND deal_id < 0";
                        }
                    }
                }
            }
            // 查询数据总量
            $sql   = "SELECT count(id) AS count FROM ag_wx_stat_repay WHERE 1 = 1 {$where} ";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql   = "SELECT * FROM ag_wx_stat_repay WHERE 1 = 1 {$where} ORDER BY deal_id , loan_repay_time";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();

            $loantype = Yii::app()->c->xf_config['loantype'];

            $type[1] = '本金';
            $type[2] = '利息';

            $status[0] = '待还';
            $status[1] = '已还';

            //获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');

            foreach ($list as $key => $value) {
                if ($value['deal_loantype'] == 5) {
                    $value['deal_repay_time'] .= '天';
                } else {
                    $value['deal_repay_time'] .= '个月';
                }
                $value['deal_repay_start_time'] = date('Y-m-d', $value['deal_repay_start_time'] + 28800);
                $value['loan_repay_time']       = date('Y-m-d', $value['loan_repay_time'] + 28800);
                $value['real_amount']           = bcsub($value['repay_amount'], $value['repaid_amount'], 2);
                $value['real_amount']           = number_format($value['real_amount'], 2, '.', ',');
                $value['repay_amount']          = number_format($value['repay_amount'], 2, '.', ',');
                $value['repaid_amount']         = number_format($value['repaid_amount'], 2, '.', ',');
                $value['borrow_amount']         = number_format($value['borrow_amount'], 2, '.', ',');
                $value['repay_type']            = $type[$value['repay_type']];
                $value['repay_status_name']     = $status[$value['repay_status']];
                $value['deal_loantype']         = $loantype[$value['deal_loantype']];
                $value['add_status']            = 0;
                $value['daochu_status']         = 0;
                if (!empty($authList) && strstr($authList, '/user/Debt/StartLoanRepay') || empty($authList)) {
                    $value['add_status'] = 1;
                }
                if (!empty($authList) && strstr($authList, '/user/Debt/LoanRepayListExcel') || empty($authList)) {
                    $value['daochu_status'] = 1;
                }
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('LoanRepayList', array());
    }

    /**
     * 尊享 待还款列表 列表 导出出借人信息 张健
     */
    public function actionLoanRepayListExcel()
    {
        if (empty($_GET['stat_repay_id'])) {
            echo iconv("UTF-8", "gbk//TRANSLIT", "<h1>请输入还款统计ID</h1>");
            exit;
        }
        if (!is_numeric($_GET['stat_repay_id'])) {
            echo iconv("UTF-8", "gbk//TRANSLIT", "<h1>还款统计ID类型输入错误</h1>");
            exit;
        }
        $id         = intval($_GET['stat_repay_id']);
        $sql        = "SELECT * FROM ag_wx_stat_repay WHERE id = {$id}";
        $stat_repay = Yii::app()->fdb->createCommand($sql)->queryRow();
        if (!$stat_repay) {
            echo iconv("UTF-8", "gbk//TRANSLIT", "<h1>还款统计ID输入错误</h1>");
            exit;
        }
        $sql = "SELECT loan_user_id , money , deal_loan_id , deal_id
                FROM firstp2p_deal_loan_repay 
                WHERE deal_id = {$stat_repay['deal_id']} and type = {$stat_repay['repay_type']} and time = {$stat_repay['loan_repay_time']} and status = 0 and money > 0";
        $res = Yii::app()->fdb->createCommand($sql)->queryAll();
        if (!$res) {
            echo iconv("UTF-8", "gbk//TRANSLIT", "<h1>未查询到还款计划</h1>");
            exit;
        }
        foreach ($res as $key => $value) {
            $ubc_array[] = $value['loan_user_id'];
            $dl_array[]  = $value['deal_loan_id'];
            $d_array[]   = $value['deal_id'];
            $u_array[]   = $value['loan_user_id'];
        }
        $ubc_string = implode(',', $ubc_array);
        $dl_string  = implode(',', $dl_array);
        $d_string   = implode(',', $d_array);
        $u_string   = implode(',', $u_array);

        $sql     = "SELECT user_id , bankcard , card_name , bankzone , bank_id FROM firstp2p_user_bankcard WHERE user_id IN ({$ubc_string}) AND verify_status = 1";
        $ubc_res = Yii::app()->fdb->createCommand($sql)->queryAll();
        if ($ubc_res) {
            foreach ($ubc_res as $key => $value) {
                $ubc_data[$value['user_id']] = $value;

                $bl_array[] = $value['bankzone'];
                $b_array[]  = $value['bank_id'];
            }
        } else {
            $ubc_data = array();
            $bl_array = array();
            $b_array  = array();
        }

        if ($b_array) {
            $b_string = implode(",", $b_array);
            $sql      = "SELECT id , name FROM firstp2p_bank WHERE id IN ({$b_string})";
            $b_res    = Yii::app()->fdb->createCommand($sql)->queryAll();
            if ($b_res) {
                foreach ($b_res as $key => $value) {
                    $b_data[$value['id']] = $value;
                }
            } else {
                $b_data = array();
            }
        } else {
            $b_data = array();
        }

        if ($bl_array) {
            $bl_string = "'".implode("','", $bl_array)."'";
            $sql       = "SELECT name , province , city , bank_id FROM firstp2p_banklist WHERE name IN ({$bl_string}) AND status = 1";
            $bl_res    = Yii::app()->fdb->createCommand($sql)->queryAll();
            if ($bl_res) {
                foreach ($bl_res as $key => $value) {
                    $bl_data[$value['name']] = $value;
                }
            } else {
                $bl_data = array();
            }
        } else {
            $bl_data = array();
        }

        $sql    = "SELECT id , black_status , join_reason , repay_way , debt_type FROM firstp2p_deal_load WHERE id IN ({$dl_string})";
        $dl_res = Yii::app()->fdb->createCommand($sql)->queryAll();
        if ($dl_res) {
            foreach ($dl_res as $key => $value) {
                $dl_data[$value['id']] = $value;
            }
        } else {
            $dl_data = array();
        }

        $sql   = "SELECT id , name FROM firstp2p_deal WHERE id IN ({$d_string})";
        $d_res = Yii::app()->fdb->createCommand($sql)->queryAll();
        if ($d_res) {
            foreach ($d_res as $key => $value) {
                $d_data[$value['id']] = $value;
            }
        } else {
            $d_data = array();
        }

        $sql   = "SELECT id , idno FROM firstp2p_user WHERE id IN ({$u_string})";
        $u_res = Yii::app()->fdb->createCommand($sql)->queryAll();
        if ($u_res) {
            foreach ($u_res as $key => $value) {
                $u_data[$value['id']] = $value;
            }
        } else {
            $u_data = array();
        }

        $sql      = "SELECT tender_id , status FROM firstp2p_debt WHERE tender_id IN ({$dl_string}) AND status IN (1 , 5 , 6)";
        $debt_res = Yii::app()->fdb->createCommand($sql)->queryAll();
        if ($debt_res) {
            foreach ($debt_res as $key => $value) {
                $debt_data[$value['tender_id']] = $value;
            }
        } else {
            $debt_data = array();
        }

        $status[1] = '否';
        $status[2] = '是';

        $black_status[1] = '否';
        $black_status[2] = '是';

        $repay_way[1] = '现金展期兑付';
        $repay_way[2] = '实物抵债兑付';

        $debt_status[1] = '转让中';
        $debt_status[5] = '待付款';
        $debt_status[6] = '待收款';

        foreach ($res as $key => $value) {
            if (!empty($ubc_data[$value['loan_user_id']])) {
                $res[$key]['bankcard']  = GibberishAESUtil::dec($ubc_data[$value['loan_user_id']]['bankcard'], Yii::app()->c->idno_key);
                $res[$key]['card_name'] = $ubc_data[$value['loan_user_id']]['card_name'];
                $res[$key]['bankzone']  = $ubc_data[$value['loan_user_id']]['bankzone'];

                if (!empty($b_data[$ubc_data[$value['loan_user_id']]['bank_id']])) {
                    $res[$key]['branch']    = $b_data[$ubc_data[$value['loan_user_id']]['bank_id']]['name'];
                } else {
                    $res[$key]['branch']    = '';
                }

                if (!empty($bl_data[$res[$key]['bankzone']])) {
                    $res[$key]['province']  = $bl_data[$res[$key]['bankzone']]['province'];
                    $res[$key]['city']      = $bl_data[$res[$key]['bankzone']]['city'];
                    $res[$key]['bank_id']   = $bl_data[$res[$key]['bankzone']]['bank_id'];
                } else {
                    $res[$key]['province']  = '';
                    $res[$key]['city']      = '';
                    $res[$key]['bank_id']   = '';
                }
            } else {
                $res[$key]['bankcard']  = '';
                $res[$key]['card_name'] = '';
                $res[$key]['bankzone']  = '';
                $res[$key]['province']  = '';
                $res[$key]['city']      = '';
                $res[$key]['bank_id']   = '';
            }

            $res[$key]['black_status'] = $black_status[$dl_data[$value['deal_loan_id']]['black_status']];
            $res[$key]['join_reason']  = $dl_data[$value['deal_loan_id']]['join_reason'];
            $res[$key]['status']       = $status[$dl_data[$value['deal_loan_id']]['debt_type']];
            $res[$key]['name']         = $d_data[$value['deal_id']]['name'];
            if ($u_data[$value['loan_user_id']]['idno']) {
                $res[$key]['idno']     = GibberishAESUtil::dec($u_data[$value['loan_user_id']]['idno'], Yii::app()->c->idno_key);
            } else {
                $res[$key]['idno']     = '';
            }
            $res[$key]['repay_way']    = $repay_way[$dl_data[$value['deal_loan_id']]['repay_way']];

            if (!empty($debt_data[$value['deal_loan_id']])) {
                $res[$key]['debt_status'] = $debt_status[$debt_data[$value['deal_loan_id']]['status']];
            } else {
                $res[$key]['debt_status'] = '';
            }
        }

        include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
        include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
        $objPHPExcel = new PHPExcel();
        // 设置当前的sheet
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('第一页');
        // 保护
        $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);

        $objPHPExcel->getActiveSheet()->setCellValue('A1', '借款标题');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', '投资记录ID');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', '用户ID');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', '身份证号');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', '收款方账号');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', '收款方户名');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', '收款方银行');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', '收款方开户行所在省');
        $objPHPExcel->getActiveSheet()->setCellValue('I1', '收款方开户行所在市');
        $objPHPExcel->getActiveSheet()->setCellValue('J1', '银行联行号');
        $objPHPExcel->getActiveSheet()->setCellValue('K1', '收款方开户行名称');
        $objPHPExcel->getActiveSheet()->setCellValue('L1', '金额');
        $objPHPExcel->getActiveSheet()->setCellValue('M1', '是否是转让');
        $objPHPExcel->getActiveSheet()->setCellValue('N1', '是否加入黑名单');
        $objPHPExcel->getActiveSheet()->setCellValue('O1', '备注');
        $objPHPExcel->getActiveSheet()->setCellValue('P1', '还款兑付方式');
        $objPHPExcel->getActiveSheet()->setCellValue('Q1', '债转状态');

        foreach ($res as $key => $value) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['deal_loan_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['loan_user_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), substr($value['idno'], 0, 6).'********'.substr($value['idno'], -4, 4));
            $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), ' '.$value['bankcard']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['card_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['bankzone']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['province']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['city']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . ($key + 2), ' '.$value['bank_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . ($key + 2), $value['branch']);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . ($key + 2), $value['money']);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . ($key + 2), $value['status']);
            $objPHPExcel->getActiveSheet()->setCellValue('N' . ($key + 2), $value['black_status']);
            $objPHPExcel->getActiveSheet()->setCellValue('O' . ($key + 2), $value['join_reason']);
            $objPHPExcel->getActiveSheet()->setCellValue('P' . ($key + 2), $value['repay_way']);
            $objPHPExcel->getActiveSheet()->setCellValue('Q' . ($key + 2), $value['debt_status']);
        }

        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
        $name = "出借人信息 还款统计ID：{$id} ".date("Y年m月d日 H时i分s秒", time());

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="'.$name.'.xls"');
        header("Content-Transfer-Encoding:binary");

        $objWriter->save('php://output');
    }

    /**
     * 尊享 待还款列表 添加线下还款 张健
     * @param id                        主键ID
     * @param deal_id                   项目id
     * @param type                      必须为1  1尊享 2普惠
     * @param deal_name                 借款标题
     * @param jys_record_number         交易所备案编号
     * @param deal_advisory_id          融资经办机构ID
     * @param deal_advisory_name        融资经办机构名称
     * @param deal_user_id              借款方ID
     * @param deal_user_real_name       借款人姓名
     * @param repayment_form            还款形式：0线下，1线上 目前必须为0
     * @param repay_type                还款类型 1常规还款2特殊还款'
     * @param loan_repay_type           资金类型 1-本金 2-利息 3本息全回(特殊还款可传3)
     * @param evidence_pic              还款或收款凭证图
     * @param plan_time                 计划还款时间 可不填，填必须大于等于今日凌晨
     * @param normal_time               正常还款时间
     * @param repayment_total           还款金额
     * @param loan_user_id              投资人ID（特殊还款二选一必填）
     * @param deal_loan_id              投资记录ID（特殊还款二选一必填）
     * @param project_name              项目名称
     * @param project_product_class     产品大类
     * @param project_id                父项目id
     * @param file                      还款凭证
     */
    public function actionStartLoanRepay()
    {
        if (!empty($_POST)) {
//            $file = $this->upload_rar('file');
//            if ($file['code'] !== 0) {
//                return $this->actionError($file['info'] , 5);
//            }
//            $data['evidence_pic']          = $file['data'];                                 // 还款凭证
            $data['repay_id']              = $_POST['id'];                                  // 主键ID
            $data['deal_id']               = $_POST['deal_id'];                             // 项目id
            $data['type']                  = 1;                                             // 尊享
            $data['deal_name']             = $_POST['deal_name'];                           // 借款标题
            $data['jys_record_number']     = $_POST['jys_record_number'];                   // 交易所备案编号
            $data['deal_advisory_id']      = $_POST['deal_advisory_id'];                    // 融资经办机构ID
            $data['deal_advisory_name']    = $_POST['deal_advisory_name'];                  // 融资经办机构名称
            $data['deal_user_id']          = $_POST['deal_user_id'];                        // 借款方ID
            $data['deal_user_real_name']   = $_POST['deal_user_real_name'];                 // 借款人姓名
            $data['repayment_form']        = 0;                                             // 线下
            $data['repay_type']            = 1;                                             // 常规还款
            $data['loan_repay_type']       = $_POST['repay_type'];                          // 还款资金类型 1-本金 2-利息
            $data['plan_time']             = strtotime($_POST['plan_time']);                // 计划还款时间
            $data['normal_time']           = strtotime($_POST['loan_repay_time']) - 28800;  // 正常还款时间
            $data['repayment_total']       = $_POST['real_amount'];                         // 还款金额
            $data['loan_user_id']          = '';                                            // 投资人ID（特殊还款二选一必填）
            $data['deal_loan_id']          = '';                                            // 投资记录ID（特殊还款二选一必填）
            $data['project_name']          = $_POST['project_name'];                        // 项目名称
            $data['project_product_class'] = $_POST['project_product_class'];               // 产品大类
            $data['project_id']            = $_POST['project_id'];                          // 父项目id
            // 调用services
            $result = AddpaymentService::getInstance()->addRepaymentPlan($data);
            if ($result['code'] != 0) {
                unlink('./'.$data['evidence_pic']);
                return $this->actionError($result['info'], 5);
            }
            return $this->actionSuccess('添加线下还款成功', 3);
        }

        if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM ag_wx_stat_repay WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }
            $res['loan_repay_time'] = date('Y-m-d', $res['loan_repay_time'] + 28800);
            $res['real_amount']     = bcsub($res['repay_amount'], $res['repaid_amount'], 2);
        } else {
            return $this->actionError('请输入ID', 5);
        }

        return $this->renderPartial('StartLoanRepay', array('info' => $res));
    }

    /**
     * 上传压缩文件 张健
     * @param name  string  压缩文件名称
     * @return array
     */
    private function upload_rar($name)
    {
        $file  = $_FILES[$name];
        $types = array('rar' , 'zip' , '7z');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的压缩文件超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的压缩文件超过了脚本限制' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => '压缩文件只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有压缩文件被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => '压缩文件写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => '压缩文件上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $name      = $file['name'];
        $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($file_type, $types)) {
            return array('code' => 2007 , 'info' => '压缩文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000, 99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir, 0777, true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建压缩文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"], './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存压缩文件成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存压缩文件失败' , 'data' => '');
        }
    }

    /**
     * 上传CSV文件 张健
     * @param name  string  文件名称
     * @return array
     */
    private function upload_csv($name)
    {
        $file  = $_FILES[$name];
        $types = array('csv');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的CSV文件超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的CSV文件超过了脚本限制' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => 'CSV文件只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有CSV文件被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => 'CSV文件写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => 'CSV文件上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $name      = $file['name'];
        $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($file_type, $types)) {
            return array('code' => 2007 , 'info' => 'CSV文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000, 99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir, 0777, true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建CSV文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"], './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存CSV文件成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存CSV文件失败' , 'data' => '');
        }
    }

    /**
     * 上传xls文件 张健
     * @param name  string  文件名称
     * @return array
     */
    private function upload_xls($name)
    {
        $file  = $_FILES[$name];
        $types = array('xlsx' , 'xls');
        if ($file['error'] != 0) {
            switch ($file['error']) {
                case 1:
                    return array('code' => 2000 , 'info' => '上传的xls文件超过了服务器限制' , 'data' => '');
                    break;
                case 2:
                    return array('code' => 2001 , 'info' => '上传的xls文件超过了脚本限制' , 'data' => '');
                    break;
                case 3:
                    return array('code' => 2002 , 'info' => 'xls文件只有部分被上传' , 'data' => '');
                    break;
                case 4:
                    return array('code' => 2003 , 'info' => '没有xls文件被上传' , 'data' => '');
                    break;
                case 6:
                    return array('code' => 2004 , 'info' => '找不到临时文件夹' , 'data' => '');
                    break;
                case 7:
                    return array('code' => 2005 , 'info' => 'xls文件写入失败' , 'data' => '');
                    break;
                default:
                    return array('code' => 2006 , 'info' => 'xls文件上传发生未知错误' , 'data' => '');
                    break;
            }
        }
        $name      = $file['name'];
        $file_type = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($file_type, $types)) {
            return array('code' => 2007 , 'info' => 'xls文件类型不匹配' , 'data' => '');
        }
        $new_name = time() . rand(10000, 99999);
        $dir      = date('Ymd');
        if (!is_dir('./upload/' . $dir)) {
            $mkdir = mkdir('./upload/' . $dir, 0777, true);
            if (!$mkdir) {
                return array('code' => 2008 , 'info' => '创建xls文件目录失败' , 'data' => '');
            }
        }
        $new_url = 'upload/' . $dir . '/' . $new_name . '.' . $file_type;
        $result  = move_uploaded_file($file["tmp_name"], './' . $new_url);
        if ($result) {
            return array('code' => 0 , 'info' => '保存xls文件成功' , 'data' => $new_url);
        } else {
            return array('code' => 2009 , 'info' => '保存xls文件失败' , 'data' => '');
        }
    }

    /**
     * 成功提示页 张健
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionSuccess($msg = '成功', $time = 3)
    {
        return $this->renderPartial('success', array('type' => 1 , 'msg' => $msg , 'time' => $time));
    }

    /**
     * 失败提示页 张健
     * @param msg   string  提示信息
     * @param time  int     显示时间
     */
    public function actionError($msg = '失败', $time = 3)
    {
        return $this->renderPartial('success', array('type' => 2 ,'msg' => $msg , 'time' => $time));
    }

    /**
     * 尊享 待审核列表 列表 张健
     * @param product_class     string      产品大类
     * @param deal_name         string      借款标题
     * @param approve_number    string      交易所备案号
     * @param project_name      string      项目名称
     * @param advisory_name     string      融资经办机构
     * @param real_name         string      借款人姓名
     * @param repay_type        int         还款资金类型
     * @param type              int         还款类型
     * @param start_a           string      正常还款日期
     * @param start             string      计划还款开始日期
     * @param end               string      计划还款截止日期
     * @param status            string      状态
     * @param limit             int         每页数据显示量 默认10
     * @param page              int         当前页数 默认1
     */
    public function actionExamineLoanRepayList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = "";
            // 校验还款形式
            if (!empty($_POST['repayment'])) {
                $repayment = intval($_POST['repayment']) - 1;
                $where    .= " AND p.repayment_form = {$repayment} ";
            }
            // 校验产品大类
            if (!empty($_POST['product_class'])) {
                $class  = trim($_POST['product_class']);
                $where .= " AND p.project_product_class = '{$class}' ";
            }
            // 校验借款ID
            if (!empty($_POST['deal_id'])) {
                $deal_id = intval($_POST['deal_id']);
                $where  .= " AND p.deal_id = '{$deal_id}' ";
            }
            // 校验借款标题
            if (!empty($_POST['deal_name'])) {
                $deal_name = trim($_POST['deal_name']);
                $where    .= " AND p.deal_name = '{$deal_name}' ";
            }
            // 校验交易所备案号
            if (!empty($_POST['approve_number'])) {
                $approve_number = trim($_POST['approve_number']);
                $where         .= " AND p.jys_record_number = '{$approve_number}' ";
            }
            // 校验项目名称
            if (!empty($_POST['project_name'])) {
                $project_name = trim($_POST['project_name']);
                $where       .= " AND p.project_name = '{$project_name}' ";
            }
            // 校验融资经办机构
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $where        .= " AND p.deal_advisory_name = '{$advisory_name}' ";
            }
            // 校验借款人姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where    .= " AND p.deal_user_real_name = '{$real_name}' ";
            }
            // 校验还款资金类型
            if (!empty($_POST['repay_type'])) {
                $repay_type = intval($_POST['repay_type']);
                $where     .= " AND p.loan_repay_type = {$repay_type} ";
            }
            // 校验还款类型
            if (!empty($_POST['type'])) {
                $t      = intval($_POST['type']);
                $where .= " AND p.repay_type = {$t} ";
            }
            // 校验正常还款日期
            if (!empty($_POST['start_a'])) {
                $start_a = strtotime($_POST['start_a']) - 28800;
                $where  .= " AND p.normal_time = '{$start_a}' ";
            }
            // 校验计划还款开始日期
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND p.plan_time >= {$start} ";
            }
            // 校验计划还款截止日期
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND p.plan_time <= {$end} ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                $sta    = intval($_POST['status']) - 1;
                $where .= " AND p.status = {$sta} ";
            }
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
            //后台用户
            $adminUserInfo  = \Yii::app()->user->getState('_user');
            $whereAdd = '';
            if (!empty($adminUserInfo['username'])) {
                if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                    if ($adminUserInfo['user_type'] == 2) {
                        $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                        if (!empty($deallist)) {
                            $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                            $whereAdd = " AND d.id IN({$dealIds})";
                        } else {
                            $whereAdd = " AND d.id < 0";
                        }
                    }
                }
            }
            // 查询数据总量
            $sql   = "SELECT count(p.id) AS count FROM ag_wx_repayment_plan AS p INNER JOIN firstp2p_deal AS d ON p.deal_id = d.id {$where} where p.status != 5 $whereAdd";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql   = "SELECT p.* , d.id AS deal_id , d.borrow_amount , d.repay_time AS deal_repay_time , d.loantype AS deal_loantype , d.rate AS deal_rate , d.success_time AS deal_repay_start_time FROM ag_wx_repayment_plan AS p INNER JOIN firstp2p_deal AS d ON p.deal_id = d.id {$where} where p.status != 5 $whereAdd GROUP BY id DESC";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();

            $loantype = Yii::app()->c->xf_config['loantype'];

            $type[1] = '本金';
            $type[2] = '利息';
            $type[3] = '本息全回';

            $ty[1] = '常规还款';
            $ty[2] = '特殊还款';

            $status[0] = '待审核';
            $status[1] = '审核通过';
            $status[2] = '审核未通过';
            $status[3] = '还款成功';
            $status[4] = '还款失败';

            $repay[0] = '线下';
            $repay[1] = '线上';

            //获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');

            foreach ($list as $key => $value) {
                if ($value['deal_loantype'] == 5) {
                    $value['deal_repay_time'] .= '天';
                } else {
                    $value['deal_repay_time'] .= '个月';
                }
                $value['deal_repay_start_time'] = date('Y-m-d', $value['deal_repay_start_time'] + 28800);
                $value['plan_time']             = date('Y-m-d', $value['plan_time']);
                if ($value['task_success_time']) {
                    $value['task_success_time'] = date('Y-m-d H:i:s', $value['task_success_time']);
                } else {
                    $value['task_success_time'] = '——';
                }
                $value['repayment_total']       = number_format($value['repayment_total'], 2, '.', ',');
                $value['borrow_amount']         = number_format($value['borrow_amount'], 2, '.', ',');
                $value['repayment_form']        = $repay[$value['repayment_form']];
                $value['deal_loantype']         = $loantype[$value['deal_loantype']];
                $value['loan_repay_type']       = $type[$value['loan_repay_type']];
                $value['repay_type_name']       = $ty[$value['repay_type']];
                $value['status_name']           = $status[$value['status']];
                if ($value['evidence_pic']) {
                    $value['evidence_pic'] = "<a class='layui-btn layui-btn-xs layui-btn-normal' href='/{$value['evidence_pic']}' download title='下载还款凭证'><i class='layui-icon'>&#xe601;</i>下载</a>";
                }
                if ($value['attachments_url']) {
                    $value['attachments_url'] = "<a class='layui-btn layui-btn-xs layui-btn-normal' href='/{$value['attachments_url']}' download title='下载附件'><i class='layui-icon'>&#xe601;</i>下载</a>";
                }
                $value['edit_status']   = 0;
                $value['pass_status']   = 0;
                $value['reject_status'] = 0;
                if (!empty($authList) && strstr($authList, '/user/Debt/EditExamineLoanRepay') || empty($authList)) {
                    $value['edit_status'] = 1;
                }
                if (!empty($authList) && strstr($authList, '/user/Debt/PassExamineLoanRepay') || empty($authList)) {
                    $value['pass_status'] = 1;
                }
                if (!empty($authList) && strstr($authList, '/user/Debt/RejectExamineLoanRepay') || empty($authList)) {
                    $value['reject_status'] = 1;
                }
                if (in_array($value['status'], array(0 , 2))) {
                    $value['edit_status'] = 2;
                }
                if ($value['status'] == 0) {
                    $value['pass_status']   = 2;
                    $value['reject_status'] = 2;
                }

                $normal_time     = explode(',', $value['normal_time']);
                $normal_time_arr = array();
                foreach ($normal_time as $k => $v) {
                    $normal_time_arr[] = date('Y-m-d', ($v + 28800));
                }
                $value['normal_time_str'] = implode(',', $normal_time_arr);

                $listInfo[] = $value;
            }
            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }
        
        return $this->renderPartial('ExamineLoanRepayList', array());
    }

    /**
     * 尊享 待审核列表 通过 张健
     * @param id    int     ID
     */
    public function actionPassExamineLoanRepay()
    {
        // 校验ID
        if (!empty($_POST['id'])) {
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_repayment_plan WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array(), 1000, 'ID输入错误');
            }
            // 校验状态
            if ($old['status'] != 0) {
                $this->echoJson(array(), 1001, '此记录不能重复审核');
            }
            // 校验计划还款日期
            if ($old['plan_time'] < strtotime(date('Y-m-d', time()))) {
                $this->echoJson(array(), 1002, '此记录的计划还款时间已经过期，请先编辑后再审核');
            }
            // 校验凭证
            if ($old['evidence_pic'] == '') {
                $this->echoJson(array(), 1003, '此记录未上传还款凭证');
            }
            $start_admin_id = Yii::app()->user->id;
            $start_admin_id = $start_admin_id ? $start_admin_id : 0 ;
            $starttime      = time();
            $startip        = Yii::app()->request->userHostAddress;
            $sql            = "UPDATE ag_wx_repayment_plan SET status = 1 , start_admin_id = {$start_admin_id} , starttime = {$starttime} , startip = '{$startip}' WHERE id = {$old['id']} ";
            $result         = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array(), 1004, '操作失败');
            }
            $this->echoJson(array(), 0, '操作成功');
        }
    }

    /**
     * 尊享 待审核列表 拒绝 张健
     * @param id            int     ID
     * @param task_remark   string  拒绝原因
     */
    public function actionRejectExamineLoanRepay()
    {
        // 校验ID
        if (!empty($_POST['id']) && !empty($_POST['task_remark'])) {
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_repayment_plan WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array(), 1000, 'ID输入错误');
            }
            // 校验状态
            if ($old['status'] != 0) {
                $this->echoJson(array(), 1001, '此记录不能重复审核');
            }
            $start_admin_id = Yii::app()->user->id;
            $start_admin_id = $start_admin_id ? $start_admin_id : 0 ;
            $starttime      = time();
            $startip        = Yii::app()->request->userHostAddress;
            $sql            = "UPDATE ag_wx_repayment_plan SET status = 2 , start_admin_id = {$start_admin_id} , starttime = {$starttime} , startip = '{$startip}' , task_remark = '{$_POST['task_remark']}' WHERE id = {$old['id']} ";
            $result         = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array(), 1002, '操作失败');
            }
            $this->echoJson(array(), 0, '操作成功');
        }
    }

    /**
     * 尊享 待审核列表 线下 常规 编辑 张健
     * @param id                        主键ID
     * @param deal_id                   项目id
     * @param type                      必须为1  1尊享 2普惠
     * @param deal_name                 借款标题
     * @param jys_record_number         交易所备案编号
     * @param deal_advisory_id          融资经办机构ID
     * @param deal_advisory_name        融资经办机构名称
     * @param deal_user_id              借款方ID
     * @param deal_user_real_name       借款人姓名
     * @param repayment_form            还款形式：0线下，1线上 目前必须为0
     * @param repay_type                还款类型 1常规还款2特殊还款'
     * @param loan_repay_type           资金类型 1-本金 2-利息 3本息全回(特殊还款可传3)
     * @param evidence_pic              还款或收款凭证图
     * @param plan_time                 计划还款时间 可不填，填必须大于等于今日凌晨
     * @param normal_time               正常还款时间
     * @param repayment_total           还款金额
     * @param loan_user_id              投资人ID（特殊还款二选一必填）
     * @param deal_loan_id              投资记录ID（特殊还款二选一必填）
     * @param project_name              项目名称
     * @param project_product_class     产品大类
     * @param file                      还款凭证
     */
    public function actionEditExamineLoanRepay()
    {
        if (!empty($_POST)) {
            $file = $this->upload_rar('file');
            if ($file['code'] === 0) {
                $data['evidence_pic'] = $file['data'];
            } else {
                $data['evidence_pic'] = $_POST['old_evidence_pic'];
            }
            $data['id']                    = $_POST['id'];                              // 主键ID
            $data['repay_id']              = $_POST['repay_id'];                        // stat_repay主键
            $data['deal_id']               = $_POST['deal_id'];                         // 项目id
            $data['type']                  = 1;                                         // 尊享
            $data['deal_name']             = $_POST['deal_name'];                       // 借款标题
            $data['jys_record_number']     = $_POST['jys_record_number'];               // 交易所备案编号
            $data['deal_advisory_id']      = $_POST['deal_advisory_id'];                // 融资经办机构ID
            $data['deal_advisory_name']    = $_POST['deal_advisory_name'];              // 融资经办机构名称
            $data['deal_user_id']          = $_POST['deal_user_id'];                    // 借款方ID
            $data['deal_user_real_name']   = $_POST['deal_user_real_name'];             // 借款人姓名
            $data['repayment_form']        = 0;                                         // 线下
            $data['repay_type']            = 1;                                         // 常规还款
            $data['loan_repay_type']       = $_POST['loan_repay_type'];                 // 还款资金类型 1-本金 2-利息
            $data['plan_time']             = strtotime($_POST['plan_time']);            // 计划还款时间
            $data['normal_time']           = strtotime($_POST['normal_time']) - 28800;  // 正常还款时间
            $data['repayment_total']       = $_POST['repayment_total'];                 // 还款金额
            $data['loan_user_id']          = '';                                        // 投资人ID（特殊还款二选一必填）
            $data['deal_loan_id']          = '';                                        // 投资记录ID（特殊还款二选一必填）
            $data['project_name']          = $_POST['project_name'];                    // 项目名称
            $data['project_product_class'] = $_POST['project_product_class'];           // 产品大类

            // 调用services
            $result = AddpaymentService::getInstance()->addRepaymentPlan($data);
            if ($result['code'] != 0) {
                return $this->actionError($result['info'], 5);
            }
            return $this->actionSuccess('保存成功', 3);
        }

        if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM ag_wx_repayment_plan WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }
            $res['normal_time']     = date('Y-m-d', $res['normal_time'] + 28800);
            $res['plan_time']       = date('Y-m-d', $res['plan_time']);
        } else {
            return $this->actionError('请输入ID', 5);
        }

        return $this->renderPartial('EditExamineLoanRepay', array('info' => $res));
    }

    /**
     * ajax校验借款标题 张健
     * @param project_product_class     string   产品大类
     * @param deal_name                 string   借款标题
     */
    public function actionCheckDealName()
    {
        if (empty($_POST['project_product_class'])) {
            $this->echoJson(array(), 1000, '请选择产品大类');
        }
        if (empty($_POST['deal_name'])) {
            $this->echoJson(array(), 1001, '请输入借款标题');
        }
        $project_product_class = trim($_POST['project_product_class']);
        $deal_name             = trim($_POST['deal_name']);
        $sql  = "SELECT * FROM firstp2p_deal WHERE name = '{$deal_name}' ";
        $deal = Yii::app()->fdb->createCommand($sql)->queryRow();
        if (!$deal) {
            $this->echoJson(array(), 1002, '借款标题输入错误');
        }
        $sql          = "SELECT * FROM firstp2p_deal_project WHERE id = {$deal['project_id']} ";
        $deal_project = Yii::app()->fdb->createCommand($sql)->queryRow();
        if (!$deal_project) {
            $this->echoJson(array(), 1003, '所属项目不存在');
        }
        if ($deal_project['product_class'] != $project_product_class) {
            $this->echoJson(array(), 1004, '此借款标题不属于所选产品大类');
        }
        $sql             = "SELECT DISTINCT(time) FROM firstp2p_deal_loan_repay WHERE deal_id = {$deal['id']} and status = 0";
        $deal_loan_repay = Yii::app()->fdb->createCommand($sql)->queryAll();
        if ($deal_loan_repay) {
            foreach ($deal_loan_repay as $key => $value) {
                $deal_loan_repay[$key]['time_name'] = date('Y-m-d', ($value['time'] + 28800));
            }
        } else {
            $deal_loan_repay = array();
        }
        $this->echoJson(array('project_name' => $deal_project['name'] , 'deal_loan_repay' => $deal_loan_repay , 'deal' => $deal , 'deal_project' => $deal_project), 0, '成功');
    }

    /**
     * 尊享 线下 特殊还款申请 张健
     * @param deal_id                   项目id
     * @param type                      必须为1  1尊享 2普惠
     * @param deal_name                 借款标题
     * @param jys_record_number         交易所备案编号
     * @param deal_advisory_id          融资经办机构ID
     * @param deal_advisory_name        融资经办机构名称
     * @param deal_user_id              借款方ID
     * @param deal_user_real_name       借款人姓名
     * @param repayment_form            还款形式：0线下，1线上 目前必须为0
     * @param repay_type                还款类型 1常规还款2特殊还款'
     * @param loan_repay_type           资金类型 1-本金 2-利息 3本息全回(特殊还款可传3)
     * @param plan_time                 计划还款时间 可不填，填必须大于等于今日凌晨
     * @param normal_time               正常还款时间
     * @param repayment_total           还款金额
     * @param loan_user_id              投资人ID（特殊还款二选一必填）
     * @param deal_loan_id              投资记录ID（特殊还款二选一必填）
     * @param project_name              项目名称
     * @param project_product_class     产品大类
     * @param file                      还款凭证
     * @param file_csv                  附件
     */
    public function actionAddSpecialLoanRepay()
    {
        if (!empty($_POST)) {
//            $file = $this->upload_rar('file');
//            if ($file['code'] !== 0) {
//                return $this->actionError($file['info'] , 5);
//            }
            $file_csv = $this->upload_csv('file_csv');
            if ($file_csv['code'] === 0) {
                $data['attachments_url']   = $file_csv['data'];
            } else {
                $data['attachments_url']   = '';
            }
//            $data['evidence_pic']          = $file['data'];                             // 还款凭证
            $data['deal_id']               = $_POST['deal_id'];                         // 项目id
            $data['type']                  = 1;                                         // 尊享
            $data['deal_name']             = $_POST['deal_name'];                       // 借款标题
            $data['jys_record_number']     = $_POST['jys_record_number'];               // 交易所备案编号
            $data['deal_advisory_id']      = $_POST['deal_advisory_id'];                // 融资经办机构ID
            $data['deal_advisory_name']    = $_POST['deal_advisory_name'];              // 融资经办机构名称
            $data['deal_user_id']          = $_POST['deal_user_id'];                    // 借款方ID
            $data['deal_user_real_name']   = $_POST['deal_user_real_name'];             // 借款人姓名
            $data['repayment_form']        = 0;                                         // 线下
            $data['repay_type']            = 2;                                         // 特殊还款
            $data['loan_repay_type']       = $_POST['loan_repay_type'];                 // 还款资金类型 1-本金 2-利息 3-本息全回
            $data['plan_time']             = strtotime($_POST['plan_time']);            // 计划还款时间
            $data['normal_time']           = $_POST['normal_time'];                     // 正常还款时间
            $data['repayment_total']       = $_POST['repayment_total'];                 // 还款金额
            if ($_POST['type'] == 1) {
                $data['loan_user_id']      = $_POST['loan_user_id'];                    // 投资人ID（特殊还款二选一必填）
            } elseif ($_POST['type'] == 2) {
                $data['deal_loan_id']      = $_POST['deal_loan_id'];                    // 投资记录ID（特殊还款二选一必填）
            }
            $data['project_name']          = $_POST['project_name'];                    // 项目名称
            $data['project_product_class'] = $_POST['project_product_class'];           // 产品大类
            // 调用services
            $result = AddpaymentService::getInstance()->addRepaymentPlan($data);
            if ($result['code'] != 0) {
                return $this->actionError($result['info'], 5);
            }
            return $this->actionSuccess('添加特殊还款申请成功', 3);
        }

        return $this->renderPartial('AddSpecialLoanRepay', array());
    }

    /**
     * 尊享 线下 特殊还款申请 张健
     * @param id                        主键ID
     * @param deal_id                   项目id
     * @param type                      必须为1  1尊享 2普惠
     * @param deal_name                 借款标题
     * @param jys_record_number         交易所备案编号
     * @param deal_advisory_id          融资经办机构ID
     * @param deal_advisory_name        融资经办机构名称
     * @param deal_user_id              借款方ID
     * @param deal_user_real_name       借款人姓名
     * @param repayment_form            还款形式：0线下，1线上 目前必须为0
     * @param repay_type                还款类型 1常规还款2特殊还款'
     * @param loan_repay_type           资金类型 1-本金 2-利息 3本息全回(特殊还款可传3)
     * @param plan_time                 计划还款时间 可不填，填必须大于等于今日凌晨
     * @param normal_time               正常还款时间
     * @param repayment_total           还款金额
     * @param loan_user_id              投资人ID（特殊还款二选一必填）
     * @param deal_loan_id              投资记录ID（特殊还款二选一必填）
     * @param project_name              项目名称
     * @param project_product_class     产品大类
     * @param file                      还款凭证
     * @param file_csv                  附件
     */
    public function actionEditSpecialLoanRepay()
    {
        if (!empty($_POST)) {
            $file = $this->upload_rar('file');
            if ($file['code'] === 0) {
                $data['evidence_pic']      = $file['data'];
            } else {
                $data['evidence_pic']      = $_POST['old_evidence_pic'];
            }
            $file_csv = $this->upload_csv('file_csv');
            if ($file_csv['code'] === 0) {
                $data['attachments_url']   = $file_csv['data'];
            } else {
                $data['attachments_url']   = $_POST['old_attachments_url'];
            }
            $data['id']                    = $_POST['id'];                              // ID
            $data['deal_id']               = $_POST['deal_id'];                         // 项目id
            $data['type']                  = 1;                                         // 尊享
            $data['deal_name']             = $_POST['deal_name'];                       // 借款标题
            $data['jys_record_number']     = $_POST['jys_record_number'];               // 交易所备案编号
            $data['deal_advisory_id']      = $_POST['deal_advisory_id'];                // 融资经办机构ID
            $data['deal_advisory_name']    = $_POST['deal_advisory_name'];              // 融资经办机构名称
            $data['deal_user_id']          = $_POST['deal_user_id'];                    // 借款方ID
            $data['deal_user_real_name']   = $_POST['deal_user_real_name'];             // 借款人姓名
            $data['repayment_form']        = 0;                                         // 线下
            $data['repay_type']            = 2;                                         // 特殊还款
            $data['loan_repay_type']       = $_POST['loan_repay_type'];                 // 还款资金类型 1-本金 2-利息 3-本息全回
            $data['plan_time']             = strtotime($_POST['plan_time']);            // 计划还款时间
            $data['normal_time']           = $_POST['normal_time'];                     // 正常还款时间
            $data['repayment_total']       = $_POST['repayment_total'];                 // 还款金额
            if ($_POST['type'] == 1) {
                $data['loan_user_id']      = $_POST['loan_user_id'];                    // 投资人ID（特殊还款二选一必填）
            } elseif ($_POST['type'] == 2) {
                $data['deal_loan_id']      = $_POST['deal_loan_id'];                    // 投资记录ID（特殊还款二选一必填）
            }
            $data['project_name']          = $_POST['project_name'];                    // 项目名称
            $data['project_product_class'] = $_POST['project_product_class'];           // 产品大类
            // 调用services
            $result = AddpaymentService::getInstance()->addRepaymentPlan($data);
            if ($result['code'] != 0) {
                return $this->actionError($result['info'], 5);
            }
            return $this->actionSuccess('保存成功', 3);
        }

        if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM ag_wx_repayment_plan WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }
            $res['plan_time']         = date('Y-m-d', $res['plan_time']);
            $evidence_pic             = explode('/', $res['evidence_pic']);
            $res['evidence_pic_a']    = $evidence_pic[2];
            $attachments_url          = explode('/', $res['attachments_url']);
            $res['attachments_url_a'] = $attachments_url[2];
        } else {
            return $this->actionError('请输入ID', 5);
        }

        return $this->renderPartial('EditSpecialLoanRepay', array('info' => $res));
    }

    /**
     * SQL查询 张健
     */
    public function actionSQLSelect()
    {
        return $this->renderPartial('SQLSelect', array());
    }

    /**
     * 用款方信息 添加 张健
     */
    public function actionAddUsingMoneySide()
    {
        if (!empty($_POST['name'])) {
            $name  = trim($_POST['name']);
            $sql   = "SELECT * FROM firstp2p_using_money_side WHERE name = '{$name}' ";
            $check = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check) {
                $this->echoJson(array(), 1000, '用款方名称已经存在');
            }
            $sql    = "INSERT INTO firstp2p_using_money_side (name) VALUES('{$name}') ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array(), 1001, '添加用款方失败');
            }
            $this->echoJson(array(), 0, '添加用款方成功');
        }

        return $this->renderPartial('AddUsingMoneySide', array());
    }

    /**
     * 用款方信息 列表 张健
     */
    public function actionUsingMoneySide()
    {
        // 校验用款方名称
        if (!empty($_GET['name'])) {
            $name  = trim($_GET['name']);
            $where = " WHERE name = '{$name}' ";
        }
        // 校验每页数据显示量
        if (!empty($_GET['limit'])) {
            $limit = intval($_GET['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }
        $sql   = "SELECT count(id) AS count FROM firstp2p_using_money_side {$where} ";
        $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
        // 查询数据
        $sql = "SELECT * FROM firstp2p_using_money_side {$where} ORDER BY id DESC ";
        $page_count = ceil($count / $limit);
        if ($page > $page_count) {
            $page = $page_count;
        }
        if ($page < 1) {
            $page = 1;
        }
        $pass = ($page - 1) * $limit;
        $sql .= " LIMIT {$pass} , {$limit} ";
        $list = Yii::app()->fdb->createCommand($sql)->queryAll();

        $criteria = new CDbCriteria();
        $pages    = new CPagination($count);
        $pages->pageSize = $limit;
        $pages->applyLimit($criteria);
        $pages = $this->widget('CLinkPager', array(
                'header'=>'',
                'firstPageLabel' => '首页',
                'lastPageLabel' => '末页',
                'prevPageLabel' => '上一页',
                'nextPageLabel' => '下一页',
                'pages' => $pages,
                'maxButtonCount'=>8,
                'cssFile'=>false,
                'htmlOptions' =>array("class"=>"pagination"),
                'selectedPageCssClass'=>"active"
            ), true);

        return $this->renderPartial('UsingMoneySide', array('list' => $list , 'pages' => $pages , 'count' => $count));
    }

    /**
     * 用款方信息 编辑 张健
     */
    public function actionEditUsingMoneySide()
    {
        // 添加借款企业
        if (!empty($_POST) && $_POST['type'] == 1) {
            // var_dump($_POST);exit;
            if (empty($_POST['id'])) {
                $this->echoJson(array(), 1000, '请输入ID');
            }
            $id  = intval($_POST['id']);
            if (empty($_POST['company'])) {
                $this->echoJson(array(), 1001, '请输入借款企业');
            }
            $sql = "SELECT * FROM firstp2p_using_money_side WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 1002, 'ID输入错误');
            }
            foreach ($_POST['company'] as $key => $value) {
                $str  = "({$res['id']},";
                $temp = explode('_', $value);
                if ($temp[0] != 'C' && $temp['0'] != 'E') {
                    $this->echoJson(array(), 1003, '借款企业来源输入错误');
                }
                if (empty($temp[1])) {
                    $this->echoJson(array(), 1004, '请输入借款企业ID');
                }
                if ($temp[0] == 'C') {
                    $str  .= "1,";
                    $sql   = "SELECT user_id , name AS company_name FROM firstp2p_user_company WHERE user_id = '{$temp[1]}' AND is_effect = 1 AND is_delete = 0 ";
                    $check = Yii::app()->fdb->createCommand($sql)->queryRow();
                } elseif ($temp[0] == 'E') {
                    $str  .= "2,";
                    $sql   = "SELECT user_id , company_name FROM firstp2p_enterprise WHERE user_id = '{$temp[1]}' ";
                    $check = Yii::app()->fdb->createCommand($sql)->queryRow();
                }
                if (!$check) {
                    $this->echoJson(array(), 1005, '借款企业ID输入错误');
                }
                $str .= "{$check['user_id']},";
                $str .= "'{$check['company_name']}')";

                $data_arr[] = $str;
            }
            $data_str = implode(',', $data_arr);
            $sql      = "INSERT INTO firstp2p_using_money_side_info (ums_id , source , user_id , company_name) VALUES {$data_str} ";
            // var_dump($sql);exit;
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array(), 1007, '添加借款企业失败');
            }
            $this->echoJson(array(), 0, '添加借款企业成功');
        }

        // 批量删除借款企业
        if (!empty($_POST) && $_POST['type'] == 2) {
            if (empty($_POST['id'])) {
                $this->echoJson(array(), 1000, '请选择要删除的借款企业');
            }
            $id = implode(',', $_POST['id']);
            if (!$id) {
                $this->echoJson(array(), 1001, '借款企业ID输入错误');
            }
            $sql    = "DELETE FROM firstp2p_using_money_side_info WHERE id IN ({$id}) ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array(), 1002, '批量删除借款企业失败');
            }
            $this->echoJson(array(), 0, '批量删除'.$result.'个借款企业成功');
        }

        if (!empty($_POST) && $_POST['type'] == 3) {
            if (!empty($_POST['name'])) {
                $name = trim($_POST['name']);

                $sql         = "SELECT user_id FROM firstp2p_using_money_side_info";
                $all_user_id = Yii::app()->fdb->createCommand($sql)->queryColumn();

                $company = array();
                $sql     = "SELECT user_id,name FROM firstp2p_user_company WHERE is_effect = 1 AND is_delete = 0 AND name = '{$name}' ";
                $com     = Yii::app()->fdb->createCommand($sql)->queryAll();
                if ($com) {
                    foreach ($com as $key => $value) {
                        if (!in_array($value['user_id'], $all_user_id)) {
                            $temp          = array();
                            $temp['value'] = "C_{$value['user_id']}";
                            $temp['title'] = $value['name'];

                            $company[] = $temp;
                        }
                    }
                }

                $sql = "SELECT user_id,company_name FROM firstp2p_enterprise WHERE company_name = '{$name}' ";
                $ent = Yii::app()->fdb->createCommand($sql)->queryAll();
                if ($ent) {
                    foreach ($ent as $key => $value) {
                        if (!in_array($value['user_id'], $all_user_id)) {
                            $temp          = array();
                            $temp['value'] = "E_{$value['user_id']}";
                            $temp['title'] = $value['company_name'];

                            $company[] = $temp;
                        }
                    }
                }
                if (!empty($company)) {
                    $this->echoJson($company, 0, '查询成功');
                } else {
                    if ($com || $ent) {
                        $this->echoJson(array(), 1001, '此借款企业已被其他用款方关联');
                    } else {
                        $this->echoJson(array(), 1002, '未查询到借款企业');
                    }
                }
            } else {
                $this->echoJson(array(), 1000, '请输入借款企业名称');
            }
        }

        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM firstp2p_using_money_side WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->actionError('ID输入错误', 5);
            }
            $sql    = "SELECT * FROM firstp2p_using_money_side_info WHERE ums_id = {$res['id']} ";
            $result = Yii::app()->fdb->createCommand($sql)->queryAll();
            if (!$result) {
                $result = array();
            }
        }

        return $this->renderPartial('EditUsingMoneySide', array('res' => $res , 'result' => $result));
    }

    /**
     * 用款方信息 详情 张健
     */
    public function actionUsingMoneySideInfo()
    {
        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM firstp2p_using_money_side WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->actionError('ID输入错误', 5);
            }
            $sql    = "SELECT * FROM firstp2p_using_money_side_info WHERE ums_id = {$res['id']} ";
            $result = Yii::app()->fdb->createCommand($sql)->queryAll();
            if (!$result) {
                $result = array();
            }
        }

        return $this->renderPartial('UsingMoneySideInfo', array('res' => $res , 'result' => $result));
    }

    /**
     * 受让方信息记录 列表 张健
     */
    public function actionAssigneeData()
    {
        // 校验所属平台
        if (!empty($_GET['type'])) {
            $type = intval($_GET['type']);
        } else {
            $_GET['type'] = 1;
            $type         = 1;
        }
        // if ($type == 1) {
        //     $model = Yii::app()->fdb;
        //     $sql = "SELECT DISTINCT del.buyer_uid AS id , u.real_name AS name FROM firstp2p_debt_exchange_log AS del INNER JOIN firstp2p_user AS u ON del.buyer_uid = u.id ";
        //     $user_arr = $model->createCommand($sql)->queryAll();
        //     if (!$user_arr) {
        //         $user_arr = array(0 => array('id' => -1 , 'name' => '暂无受让人'));
        //     }
        // } else if ($type == 2) {
        //     $model    = Yii::app()->phdb;
        //     $sql      = "SELECT DISTINCT buyer_uid AS id FROM firstp2p_debt_exchange_log";
        //     $user_arr = $model->createCommand($sql)->queryAll();
        //     if ($user_arr) {
        //         foreach ($user_arr as $key => $value) {
        //             $user_where_arr[] = $value['id'];
        //         }
        //         $user_where_str = implode(',' , $user_where_arr);
        //         $sql            = "SELECT DISTINCT id , real_name FROM firstp2p_user WHERE id IN ({$user_where_str}) ";
        //         $user_name      = Yii::app()->fdb->createCommand($sql)->queryAll();
        //         foreach ($user_name as $key => $value) {
        //             $name[$value['id']] = $value['real_name'];
        //         }
        //         foreach ($user_arr as $key => $value) {
        //             $user_arr[$key]['name'] = $name[$value['id']];
        //         }
        //     } else {
        //         $user_arr = array(0 => array('id' => -1 , 'name' => '暂无受让人'));
        //     }
        // }
        $sql = "SELECT assi.user_id AS id , user.real_name AS name FROM ag_wx_assignee_info AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status IN (2, 3)";
        $user_arr = Yii::app()->fdb->createCommand($sql)->queryAll();
        if (!$user_arr) {
            $user_arr = array(0 => array('id' => -1 , 'name' => '暂无受让人'));
        }
        if (!empty($_GET['user'])) {
            if ($_GET['user'] == "ALL") {
                $user  = "ALL";
                $where = "";
            } else {
                $user = intval($_GET['user']);
                $where = " AND del.buyer_uid = {$user} ";
            }
        } else {
            $_GET['user'] = "ALL";
            $user         = "ALL";
            $where = "";
        }
        // 校验开始日期
        if (!empty($_GET['start'])) {
            $start  = strtotime($_GET['start'].' 00:00:00');
            $where .= " AND del.successtime >= {$start} ";
        }
        // 校验截止日期
        if (!empty($_GET['end'])) {
            $end    = strtotime($_GET['end'].' 23:59:59');
            $where .= " AND del.successtime <= {$end} ";
        }
        // 校验借款标题
        if (!empty($_GET['deal_name'])) {
            $deal_name = trim($_GET['deal_name']);
            $where    .= " AND d.name = '{$deal_name}' ";
        }
        // 校验项目名称
        if (!empty($_GET['project_name'])) {
            $project_name = trim($_GET['project_name']);
            $where       .= " AND dp.name = '{$project_name}' ";
        }
        // 校验融资经办机构
        if (!empty($_GET['agency_name'])) {
            $agency_name = trim($_GET['agency_name']);
            $where      .= " AND da.name = '{$agency_name}' ";
        }
        // 校验交易所备案产品号
        if (!empty($_GET['number'])) {
            $number = trim($_GET['number']);
            $where .= " AND d.jys_record_number = '{$number}' ";
        }
        // 校验借款企业
        if (!empty($_GET['company'])) {
            $company = trim($_GET['company']);
            $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
            $com_a   = Yii::app()->fdb->createCommand($sql)->queryScalar();
            $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' AND e.company_purpose = 2";
            $com_b   = Yii::app()->fdb->createCommand($sql)->queryScalar();
            $com_arr = array();
            if ($com_a) {
                $com_arr[] = $com_a;
            }
            if ($com_b) {
                $com_arr[] = $com_b;
            }
            if (!empty($com_arr)) {
                $com_str = implode(',', $com_arr);
                $where .= " AND d.user_id IN ({$com_str}) ";
            } else {
                $where .= " AND d.user_id is NULL ";
            }
        }
        // 校验用款方
        if (!empty($_GET['ums_name'])) {
            $ums_name = trim($_GET['ums_name']);
            $sql      = "SELECT DISTINCT i.user_id FROM firstp2p_using_money_side AS s INNER JOIN firstp2p_using_money_side_info AS i ON s.id = i.ums_id WHERE s.name = '{$ums_name}' ";
            $user_id  = Yii::app()->fdb->createCommand($sql)->queryColumn();
            if (!empty($user_id)) {
                $user_str = implode(',', $user_id);
                $where .= " AND d.user_id IN ({$user_str}) ";
            } else {
                $where .= " AND d.user_id is NULL ";
            }
        }
        // 校验承接债权途径
        if (!empty($_GET['debt_src'])) {
            $debt_src = intval($_GET['debt_src']);
            $where   .= " AND del.debt_src = {$debt_src} ";
        }
        // 查询数据
        if ($type == 1) {
            $model = Yii::app()->fdb;
            $sql = "SELECT
                    d.id , sum(del.debt_account) AS debt_account , count(DISTINCT del.user_id) AS count , d.name AS deal_name , dp.name AS project_name , da.name AS agency_name , d.user_id , u.user_type , d.jys_record_number
                    FROM (((firstp2p_deal AS d
                    INNER JOIN firstp2p_debt_exchange_log AS del ON d.id = del.borrow_id)
                    INNER JOIN firstp2p_deal_project AS dp ON d.project_id = dp.id)
                    INNER JOIN firstp2p_deal_agency AS da ON d.advisory_id = da.id)
                    INNER JOIN firstp2p_user AS u ON d.user_id = u.id
                    WHERE del.status = 2 AND del.debt_account > 0 {$where} GROUP BY d.id ORDER BY debt_account DESC ";
        } elseif ($type == 2) {
            $model = Yii::app()->phdb;
            $sql = "SELECT
                    d.id , sum(del.debt_account) AS debt_account , count(DISTINCT del.user_id) AS count , d.name AS deal_name , dp.name AS project_name , da.name AS agency_name , d.user_id , d.jys_record_number
                    FROM ((firstp2p_deal AS d
                    INNER JOIN firstp2p_debt_exchange_log AS del ON d.id = del.borrow_id)
                    INNER JOIN firstp2p_deal_project AS dp ON d.project_id = dp.id)
                    INNER JOIN firstp2p_deal_agency AS da ON d.advisory_id = da.id
                    WHERE del.status = 2 AND del.debt_account > 0 {$where} GROUP BY d.id ORDER BY debt_account DESC ";
        } else {
            $this->actionError('所属平台输入错误', 5);
        }
        $list  = $model->createCommand($sql)->queryAll();
        $count = count($list);
        $total = 0;
        if ($list) {
            // 校验每页数据显示量
            if (!empty($_GET['limit'])) {
                $limit = intval($_GET['limit']);
                if ($limit < 1) {
                    $limit = 1;
                }
            } else {
                $limit = 10;
            }
            // 校验当前页数
            if (!empty($_GET['page'])) {
                $page = intval($_GET['page']);
            } else {
                $page = 1;
            }
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }

            $criteria                  = new CDbCriteria();
            $pages                     = new CPagination($count);
            $pages->pageSize           = $limit;
            $pages->applyLimit($criteria);
            $pages                     = $this->widget('CLinkPager', array(
                'header'               =>'',
                'firstPageLabel'       => '首页',
                'lastPageLabel'        => '末页',
                'prevPageLabel'        => '上一页',
                'nextPageLabel'        => '下一页',
                'pages'                => $pages,
                'maxButtonCount'       => 8,
                'cssFile'              => false,
                'htmlOptions'          => array("class" => "pagination"),
                'selectedPageCssClass' => "active"
            ), true);

            $start = ($page - 1) * $limit;
            $end   = $start + $limit - 1;
            foreach ($list as $key => $value) {
                $total = bcadd($total, $value['debt_account'], 2);
                if ($key >= $start && $key <= $end) {
                    $value['debt_account'] = number_format($value['debt_account'], 2, '.', ',');
                    $value['ums_name']     = '';
                    $value['company_name'] = '';

                    $res_where_arr[] = $value['user_id'];
                    $listInfo[]      = $value;
                }
            }
            $total = number_format($total, 2, '.', ',');
            // 查用款方信息
            $res_where_str = implode(',', $res_where_arr);
            $sql = "SELECT s.name AS ums_name , i.company_name , i.user_id FROM firstp2p_using_money_side AS s INNER JOIN firstp2p_using_money_side_info AS i ON s.id = i.ums_id WHERE i.user_id in ($res_where_str) ";
            $res = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($res as $key => $value) {
                $ums[$value['user_id']]['ums_name']     = $value['ums_name'];
                $ums[$value['user_id']]['company_name'] = $value['company_name'];
            }
            $user_type = array(0 => array() , 1 => array() , 2 => array());
            foreach ($listInfo as $key => $value) {
                if (!empty($ums[$value['user_id']])) {
                    $listInfo[$key]['ums_name']     = $ums[$value['user_id']]['ums_name'];
                    $listInfo[$key]['company_name'] = $ums[$value['user_id']]['company_name'];
                } else {
                    if ($type == 1) {
                        if ($value['user_type'] == 0) {
                            $user_type[1][] = $value['user_id'];
                        } elseif ($value['user_type'] == 1) {
                            $user_type[2][] = $value['user_id'];
                        }
                    } elseif ($type == 2) {
                        $user_type[0][] = $value['user_id'];
                    }
                }
            }
            if (!empty($user_type[0])) {
                $where_0 = implode(',', $user_type[0]);
                $sql     = "SELECT id,user_type FROM firstp2p_user WHERE id IN ({$where_0}) ";
                $res_0   = Yii::app()->fdb->createCommand($sql)->queryAll();
                foreach ($res_0 as $key => $value) {
                    if ($value['user_type'] == 0) {
                        $user_type[1][] = $value['id'];
                    } elseif ($value['user_type'] == 1) {
                        $user_type[2][] = $value['id'];
                    }
                }
            }
            if (!empty($user_type[1])) {
                $where_1 = implode(',', $user_type[1]);
                $sql     = "SELECT user_id,name FROM firstp2p_user_company WHERE is_effect = 1 AND is_delete = 0 AND user_id IN ({$where_1}) ";
                $res_1   = Yii::app()->fdb->createCommand($sql)->queryAll();
                foreach ($res_1 as $key => $value) {
                    $company_name[$value['user_id']] = $value['name'];
                }
            }
            if (!empty($user_type[2])) {
                $where_2 = implode(',', $user_type[2]);
                $sql     = "SELECT user_id,company_name FROM firstp2p_enterprise WHERE user_id IN ({$where_2}) ";
                $res_2   = Yii::app()->fdb->createCommand($sql)->queryAll();
                foreach ($res_2 as $key => $value) {
                    $company_name[$value['user_id']] = $value['company_name'];
                }
            }
            foreach ($listInfo as $key => $value) {
                if ($value['company_name'] == '' && !empty($company_name[$value['user_id']])) {
                    $listInfo[$key]['company_name'] = $company_name[$value['user_id']];
                }
            }
        }
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList, '/user/Debt/AssigneeDataExcel') || empty($authList)) {
            $daochu_status = 1;
        }

        return $this->renderPartial('AssigneeData', array('listInfo' => $listInfo , 'pages' => $pages , 'count' => $count , 'type' => $type , 'user' => $user , 'total' => $total , 'user_arr' => $user_arr , 'daochu_status' => $daochu_status));
    }

    
    /**
     * 受让方信息记录 导出 张健
     */
    public function actionAssigneeDataExcel()
    {
        // 校验所属平台
        if (!empty($_GET['type'])) {
            $type = intval($_GET['type']);
        } else {
            $_GET['type'] = 1;
            $type         = 1;
        }
        // if ($type == 1) {
        //     $model = Yii::app()->fdb;
        //     $sql = "SELECT DISTINCT del.buyer_uid AS id , u.real_name AS name FROM firstp2p_debt_exchange_log AS del INNER JOIN firstp2p_user AS u ON del.buyer_uid = u.id ";
        //     $user_arr = $model->createCommand($sql)->queryAll();
        //     if (!$user_arr) {
        //         $user_arr = array(0 => array('id' => -1 , 'name' => '暂无受让人'));
        //     }
        // } else if ($type == 2) {
        //     $model    = Yii::app()->phdb;
        //     $sql      = "SELECT DISTINCT buyer_uid AS id FROM firstp2p_debt_exchange_log";
        //     $user_arr = $model->createCommand($sql)->queryAll();
        //     if ($user_arr) {
        //         foreach ($user_arr as $key => $value) {
        //             $user_where_arr[] = $value['id'];
        //         }
        //         $user_where_str = implode(',' , $user_where_arr);
        //         $sql            = "SELECT DISTINCT id , real_name FROM firstp2p_user WHERE id IN ({$user_where_str}) ";
        //         $user_name      = Yii::app()->fdb->createCommand($sql)->queryAll();
        //         foreach ($user_name as $key => $value) {
        //             $name[$value['id']] = $value['real_name'];
        //         }
        //         foreach ($user_arr as $key => $value) {
        //             $user_arr[$key]['name'] = $name[$value['id']];
        //         }
        //     } else {
        //         $user_arr = array(0 => array('id' => -1 , 'name' => '暂无受让人'));
        //     }
        // }
        $sql = "SELECT assi.user_id AS id , user.real_name AS name FROM ag_wx_assignee_info AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status IN (2, 3)";
        $user_arr = Yii::app()->fdb->createCommand($sql)->queryAll();
        if (!$user_arr) {
            $user_arr = array(0 => array('id' => -1 , 'name' => '暂无受让人'));
        }
        if (!empty($_GET['user'])) {
            if ($_GET['user'] == "ALL") {
                $user  = "ALL";
                $where = "";
            } else {
                $user = intval($_GET['user']);
                $where = " AND del.buyer_uid = {$user} ";
            }
        } else {
            $_GET['user'] = "ALL";
            $user         = "ALL";
            $where = "";
        }
        // 校验开始日期
        if (!empty($_GET['start'])) {
            $start  = strtotime($_GET['start'].' 00:00:00');
            $where .= " AND del.successtime >= {$start} ";
        }
        // 校验截止日期
        if (!empty($_GET['end'])) {
            $end    = strtotime($_GET['end'].' 23:59:59');
            $where .= " AND del.successtime <= {$end} ";
        }
        // 校验借款标题
        if (!empty($_GET['deal_name'])) {
            $deal_name = trim($_GET['deal_name']);
            $where    .= " AND d.name = '{$deal_name}' ";
        }
        // 校验项目名称
        if (!empty($_GET['project_name'])) {
            $project_name = trim($_GET['project_name']);
            $where       .= " AND dp.name = '{$project_name}' ";
        }
        // 校验融资经办机构
        if (!empty($_GET['agency_name'])) {
            $agency_name = trim($_GET['agency_name']);
            $where      .= " AND da.name = '{$agency_name}' ";
        }
        // 校验交易所备案产品号
        if (!empty($_GET['number'])) {
            $number = trim($_GET['number']);
            $where .= " AND d.jys_record_number = '{$number}' ";
        }
        // 校验借款企业
        if (!empty($_GET['company'])) {
            $company = trim($_GET['company']);
            $sql     = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
            $com_a   = Yii::app()->fdb->createCommand($sql)->queryScalar();
            $sql     = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' AND e.company_purpose = 2";
            $com_b   = Yii::app()->fdb->createCommand($sql)->queryScalar();
            $com_arr = array();
            if ($com_a) {
                $com_arr[] = $com_a;
            }
            if ($com_b) {
                $com_arr[] = $com_b;
            }
            if (!empty($com_arr)) {
                $com_str = implode(',', $com_arr);
                $where .= " AND d.user_id IN ({$com_str}) ";
            } else {
                $where .= " AND d.user_id is NULL ";
            }
        }
        // 校验用款方
        if (!empty($_GET['ums_name'])) {
            $ums_name = trim($_GET['ums_name']);
            $sql      = "SELECT DISTINCT i.user_id FROM firstp2p_using_money_side AS s INNER JOIN firstp2p_using_money_side_info AS i ON s.id = i.ums_id WHERE s.name = '{$ums_name}' ";
            $user_id  = Yii::app()->fdb->createCommand($sql)->queryColumn();
            if (!empty($user_id)) {
                $user_str = implode(',', $user_id);
                $where .= " AND d.user_id IN ({$user_str}) ";
            } else {
                $where .= " AND d.user_id is NULL ";
            }
        }
        // 校验承接债权途径
        if (!empty($_GET['debt_src'])) {
            $debt_src = intval($_GET['debt_src']);
            $where   .= " AND del.debt_src = {$debt_src} ";
        }
        // 查询数据
        if ($type == 1) {
            $model = Yii::app()->fdb;
            $sql = "SELECT
                    d.id , sum(del.debt_account) AS debt_account , count(DISTINCT del.user_id) AS count , d.name AS deal_name , dp.name AS project_name , da.name AS agency_name , d.user_id , u.user_type , d.jys_record_number
                    FROM (((firstp2p_deal AS d
                    INNER JOIN firstp2p_debt_exchange_log AS del ON d.id = del.borrow_id)
                    INNER JOIN firstp2p_deal_project AS dp ON d.project_id = dp.id)
                    INNER JOIN firstp2p_deal_agency AS da ON d.advisory_id = da.id)
                    INNER JOIN firstp2p_user AS u ON d.user_id = u.id
                    WHERE del.status = 2 AND del.debt_account > 0 {$where} GROUP BY d.id ORDER BY debt_account DESC ";
        } elseif ($type == 2) {
            $model = Yii::app()->phdb;
            $sql = "SELECT
                    d.id , sum(del.debt_account) AS debt_account , count(DISTINCT del.user_id) AS count , d.name AS deal_name , dp.name AS project_name , da.name AS agency_name , d.user_id , d.jys_record_number
                    FROM ((firstp2p_deal AS d
                    INNER JOIN firstp2p_debt_exchange_log AS del ON d.id = del.borrow_id)
                    INNER JOIN firstp2p_deal_project AS dp ON d.project_id = dp.id)
                    INNER JOIN firstp2p_deal_agency AS da ON d.advisory_id = da.id
                    WHERE del.status = 2 AND del.debt_account > 0 {$where} GROUP BY d.id ORDER BY debt_account DESC ";
        } else {
            echo iconv("UTF-8", "gbk//TRANSLIT", '<h1>所属平台输入错误</h1>');
        }
        $list  = $model->createCommand($sql)->queryAll();
        $count = count($list);
        $total = 0;
        if ($list) {
            foreach ($list as $key => $value) {
                $total = bcadd($total, $value['debt_account'], 2);

                $value['ums_name']     = '';
                $value['company_name'] = '';

                $res_where_arr[] = $value['user_id'];
                $listInfo[]      = $value;
            }
            // 查用款方信息
            $res_where_str = implode(',', $res_where_arr);
            $sql = "SELECT s.name AS ums_name , i.company_name , i.user_id FROM firstp2p_using_money_side AS s INNER JOIN firstp2p_using_money_side_info AS i ON s.id = i.ums_id WHERE i.user_id in ($res_where_str) ";
            $res = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($res as $key => $value) {
                $ums[$value['user_id']]['ums_name']     = $value['ums_name'];
                $ums[$value['user_id']]['company_name'] = $value['company_name'];
            }
            $user_type = array(0 => array() , 1 => array() , 2 => array());
            foreach ($listInfo as $key => $value) {
                if (!empty($ums[$value['user_id']])) {
                    $listInfo[$key]['ums_name']     = $ums[$value['user_id']]['ums_name'];
                    $listInfo[$key]['company_name'] = $ums[$value['user_id']]['company_name'];
                } else {
                    if ($type == 1) {
                        if ($value['user_type'] == 0) {
                            $user_type[1][] = $value['user_id'];
                        } elseif ($value['user_type'] == 1) {
                            $user_type[2][] = $value['user_id'];
                        }
                    } elseif ($type == 2) {
                        $user_type[0][] = $value['user_id'];
                    }
                }
            }
            if (!empty($user_type[0])) {
                $where_0 = implode(',', $user_type[0]);
                $sql     = "SELECT id,user_type FROM firstp2p_user WHERE id IN ({$where_0}) ";
                $res_0   = Yii::app()->fdb->createCommand($sql)->queryAll();
                foreach ($res_0 as $key => $value) {
                    if ($value['user_type'] == 0) {
                        $user_type[1][] = $value['id'];
                    } elseif ($value['user_type'] == 1) {
                        $user_type[2][] = $value['id'];
                    }
                }
            }
            if (!empty($user_type[1])) {
                $where_1 = implode(',', $user_type[1]);
                $sql     = "SELECT user_id,name FROM firstp2p_user_company WHERE is_effect = 1 AND is_delete = 0 AND user_id IN ({$where_1}) ";
                $res_1   = Yii::app()->fdb->createCommand($sql)->queryAll();
                foreach ($res_1 as $key => $value) {
                    $company_name[$value['user_id']] = $value['name'];
                }
            }
            if (!empty($user_type[2])) {
                $where_2 = implode(',', $user_type[2]);
                $sql     = "SELECT user_id,company_name FROM firstp2p_enterprise WHERE user_id IN ({$where_2}) ";
                $res_2   = Yii::app()->fdb->createCommand($sql)->queryAll();
                foreach ($res_2 as $key => $value) {
                    $company_name[$value['user_id']] = $value['company_name'];
                }
            }
            foreach ($listInfo as $key => $value) {
                if ($value['company_name'] == '' && !empty($company_name[$value['user_id']])) {
                    $listInfo[$key]['company_name'] = $company_name[$value['user_id']];
                }
            }
            $name = '';
            foreach ($user_arr as $key => $value) {
                if ($user == $value['id']) {
                    $name = $value['name'];
                }
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(30);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '受让债权金额');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '人数');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '项目名称');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '交易所备案产品号');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '借款企业');
            $objPHPExcel->getActiveSheet()->setCellValue('H1', '融资经办机构');
            $objPHPExcel->getActiveSheet()->setCellValue('I1', '用款方');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $key+1);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['debt_account']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['count']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['deal_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['project_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['jys_record_number']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['company_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . ($key + 2), $value['agency_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . ($key + 2), $value['ums_name']);
            }

            $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 3), "受让人:{$name}");
            if ($type == 1) {
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 3), '所属平台:尊享');
            } elseif ($type == 2) {
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 3), '所属平台:普惠');
            }
            
            $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 3), "查询日期:");
            $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 3), "{$_GET['start']}");
            $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 3), "{$_GET['end']}");
            $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 3), "受让债权金额合计:");
            $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 3), "{$total}");

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = "受让方信息记录:{$name} ".date("Y年m月d日 H时i分s秒", time());

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
        } else {
            echo iconv("UTF-8", "gbk//TRANSLIT", '<h1>暂无数据</h1>');
        }
    }

    /**
     * 债转黑名单 列表 张健
     */
    public function actionBlackList()
    {
        // 条件筛选
        $where = "";
        // 校验所属平台
        if (empty($_GET['type'])) {
            $_GET['type'] = 1;
        }
        if (!empty($_GET['type'])) {
            $type   = intval($_GET['type']);
            $where .= " type = {$type} ";
        }
        // 校验借款ID
        if (!empty($_GET['deal_id'])) {
            $deal_id = intval($_GET['deal_id']);
            $where  .= " AND deal_id = {$deal_id} ";
        }
        // 校验借款标题
        if (!empty($_GET['deal_name'])) {
            $deal_name = trim($_GET['deal_name']);
            $where    .= " AND deal_name = '{$deal_name}' ";
        }
        // 校验状态
        if (!empty($_GET['status'])) {
            $sta    = intval($_GET['status']);
            $where .= " AND status = {$sta} ";
        }
        // 校验每页数据显示量
        if (!empty($_GET['limit'])) {
            $limit = intval($_GET['limit']);
            if ($limit < 1) {
                $limit = 1;
            }
        } else {
            $limit = 10;
        }
        // 校验当前页数
        if (!empty($_GET['page'])) {
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }
        //后台用户
        $adminUserInfo  = \Yii::app()->user->getState('_user');
        if (!empty($adminUserInfo['username'])) {
            if ($adminUserInfo['username'] != Yii::app()->iDbAuthManager->admin) {
                if ($adminUserInfo['user_type'] == 2) {
                    $deallist = Yii::app()->fdb->createCommand("SELECT firstp2p_deal.id deal_id from firstp2p_deal_agency LEFT JOIN firstp2p_deal ON firstp2p_deal.advisory_id = firstp2p_deal_agency.id WHERE firstp2p_deal_agency.name = '{$adminUserInfo['realname']}' and firstp2p_deal_agency.is_effect = 1 and firstp2p_deal.id > 0")->queryAll();
                    if (!empty($deallist)) {
                        $dealIds = implode(",", ItzUtil::array_column($deallist, "deal_id"));
                        $where .= " AND deal_id IN({$dealIds})";
                    } else {
                        $where .= " AND deal_id < 0";
                    }
                }
            }
        }
        $sql   = "SELECT count(id) AS count FROM ag_wx_debt_black_list WHERE {$where} ";
        $count = Yii::app()->fdb->createCommand($sql)->queryScalar();

        if ($count > 0) {
            $ty = array(1=>'尊享', 2=>'普惠', 3=>'工场微金', 4=>'智多新', 5=>'交易所');
            // 查询数据
            $sql = "SELECT * FROM ag_wx_debt_black_list WHERE {$where} ORDER BY id DESC ";
            $page_count = ceil($count / $limit);
            if ($page > $page_count) {
                $page = $page_count;
            }
            if ($page < 1) {
                $page = 1;
            }
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($list as $key => $value) {
                if ($value['addtime'] > 0) {
                    $value['addtime']    = date('Y-m-d H:i:s', $value['addtime']);
                } else {
                    $value['addtime'] = '——';
                }
                if ($value['updatetime'] > 0) {
                    $value['updatetime'] = date('Y-m-d H:i:s', $value['updatetime']);
                } else {
                    $value['updatetime'] = '——';
                }
                $value['type'] = $ty[$value['type']];
                $listInfo[] = $value;
            }

            $criteria = new CDbCriteria();
            $pages    = new CPagination($count);
            $pages->pageSize = $limit;
            $pages->applyLimit($criteria);
            $pages = $this->widget('CLinkPager', array(
                'header'=>'',
                'firstPageLabel' => '首页',
                'lastPageLabel' => '末页',
                'prevPageLabel' => '上一页',
                'nextPageLabel' => '下一页',
                'pages' => $pages,
                'maxButtonCount'=>8,
                'cssFile'=>false,
                'htmlOptions' =>array("class"=>"pagination"),
                'selectedPageCssClass'=>"active"
            ), true);

            $status[1] = '开启';
            $status[2] = '关闭';
        }
        return $this->renderPartial('BlackList', array('listInfo' => $listInfo, 'pages' => $pages , 'count' => $count , 'limit' => $limit , 'page' => $page , 'type' => $type , 'status' => $status));
    }

    /**
     * 债转黑名单 添加 张健
     */
    public function actionAddBlackList()
    {
        if (!empty($_POST)) {
            // 校验所属平台
            if (empty($_POST['type']) || !is_numeric($_POST['type']) || !in_array($_POST['type'], array(1, 2, 3, 4, 5))) {
                $this->echoJson(array(), 1000, '请正确输入所属平台');
            }
            // 校验借款标题
            if (empty($_POST['deal_name'])) {
                $this->echoJson(array(), 1001, '请输入借款标题');
            }
            $deal_name = trim($_POST['deal_name']);
            $deal_info = $this->checkDealName($_POST['type'], $deal_name);
            if (!$deal_info) {
                $this->echoJson(array(), 1002, '借款标题输入错误');
            }
            // 添加数据
            $sql   = "SELECT * FROM ag_wx_debt_black_list WHERE deal_id = {$deal_info['id']} AND type = {$_POST['type']}";
            $check = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check) {
                $this->echoJson(array(), 1020, '您添加的借款信息已经存在');
            }
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $time       = time();
            $ip         = Yii::app()->request->userHostAddress;
            $sql        = "INSERT INTO ag_wx_debt_black_list (type , deal_id , deal_name , op_user_id , addtime , addip , status , updatetime) VALUES({$_POST['type']} , {$deal_info['id']} , '{$deal_info['name']}' , {$op_user_id} , {$time} , '{$ip}' , 1 , {$time}) ";
            $result     = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array(), 1021, '添加债转黑名单失败');
            }

            $this->echoJson(array(), 0, '添加债转黑名单成功');
        }

        return $this->renderPartial('AddBlackList', array());
    }

    /**
     * 债转黑名单 开启&关闭 张健
     */
    public function actionCloseBlackList()
    {
        // 校验记录ID
        if (!empty($_POST['id'])) {
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_debt_black_list WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array(), 1000, 'ID输入错误');
            }
            if ($old['status'] == 1) {
                $status = 2;
            } elseif ($old['status'] == 2) {
                $status = 1;
            }
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            $time       = time();
            $sql        = "UPDATE ag_wx_debt_black_list SET status = {$status} , op_user_id = {$op_user_id} , addip = '{$ip}' , updatetime = {$time} WHERE id = {$old['id']} ";
            $result     = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array(), 1002, '操作失败');
            }
            $this->echoJson(array(), 0, '操作成功');
        }
    }

    /**
     * 用户银行卡管理 列表 张健
     */
    public function actionUserBankCard()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = "";
            // 校验平台ID
            // if (!empty($_POST['platform'])) {
            //     $pla    = intval($_POST['platform']);
            //     $where .= " AND u.platform_id = {$pla} ";
            // }
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND ub.user_id = {$user_id} ";
            }
            // 校验银行卡号
            if (!empty($_POST['bankcard'])) {
                $bankcard = trim($_POST['bankcard']);
                $bankcard = GibberishAESUtil::enc($bankcard, Yii::app()->c->idno_key); // 银行卡号加密
                $where   .= " AND ub.bankcard = '{$bankcard}' ";
            }
            // 校验用户手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND u.mobile = '{$mobile}' ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                if ($_POST['status'] == 1) {
                    $where .= " AND ub.verify_status = 1 ";
                } elseif ($_POST['status'] == 2) {
                    $where .= " AND ub.verify_status = 0 ";
                }
            }
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
            if (empty($_POST['user_id']) && empty($_POST['bankcard']) && empty($_POST['mobile']) && empty($_POST['status'])) {
                $count = Yii::app()->rcache->get("firstp2p_user_bankcard_count");
                if (!$count) {
                    $sql = "SELECT count(ub.id) AS count FROM (firstp2p_user AS u INNER JOIN firstp2p_user_bankcard AS ub ON u.id = ub.user_id) INNER JOIN firstp2p_bank AS b ON ub.bank_id = b.id ";
                    $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($count != 0) {
                        $redisData = Yii::app()->rcache->set("firstp2p_user_bankcard_count", $count, 86400);
                        if (!$redisData) {
                            Yii::log("redis firstp2p_user_bankcard_count set error", "error");
                        }
                    }
                }
            } else {
                $sql = "SELECT count(ub.id) AS count FROM (firstp2p_user AS u INNER JOIN firstp2p_user_bankcard AS ub ON u.id = ub.user_id) INNER JOIN firstp2p_bank AS b ON ub.bank_id = b.id {$where} ";
                $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            }
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT ub.id , ub.user_id , u.mobile , b.name , ub.bankcard , ub.card_name , ub.bankzone , ub.verify_status , ub.update_time , ub.notes FROM (firstp2p_user AS u INNER JOIN firstp2p_user_bankcard AS ub ON u.id = ub.user_id) INNER JOIN firstp2p_bank AS b ON ub.bank_id = b.id {$where} ORDER BY ub.id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            $verify_status[0] = '无效';
            $verify_status[1] = '有效';

            // $sql = "SELECT * FROM ag_wx_platform";
            // $platform = Yii::app()->fdb->createCommand($sql)->queryAll();
            // foreach ($platform as $key => $value) {
            //     $platform_data[$value['id']] = $value['name'];
            // }
            $bankzone_arr     = array();
            foreach ($list as $key => $value) {
                if ($value['mobile']) {
                    $value['mobile']        = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                }
                if ($value['bankcard']) {
                    $value['bankcard_real'] = GibberishAESUtil::dec($value['bankcard'], Yii::app()->c->idno_key); // 银行卡解密
                }
                if ($value['update_time'] > 0) {
                    $value['update_time'] = date('Y-m-d H:i:s', $value['update_time']);
                } else {
                    $value['update_time'] = '——';
                }
                $value['verify_status_name'] = $verify_status[$value['verify_status']];
                $value['province']           = '——';
                $value['city']               = '——';
                $value['branch_no']          = '——';
                // $value['platform_id']        = $platform_data[$value['platform_id']];
                $listInfo[] = $value;

                if (!empty($value['bankzone'])) {
                    $bankzone_arr[] = $value['bankzone'];
                }
            }
            if ($bankzone_arr) {
                $bankzone_str = "'".implode("','", $bankzone_arr)."'";
                $sql = "SELECT * FROM firstp2p_banklist WHERE name IN ({$bankzone_str}) AND status = 1";
                $bankzone_res = Yii::app()->fdb->createCommand($sql)->queryAll();
                if ($bankzone_res) {
                    foreach ($bankzone_res as $key => $value) {
                        $bankzone_data[$value['name']] = $value;
                    }
                } else {
                    $bankzone_data = array();
                }
            } else {
                $bankzone_data = array();
            }
            foreach ($listInfo as $key => $value) {
                if (!empty($value['bankzone']) && !empty($bankzone_data[$value['bankzone']])) {
                    $listInfo[$key]['province']  = $bankzone_data[$value['bankzone']]['province'];
                    $listInfo[$key]['city']      = $bankzone_data[$value['bankzone']]['city'];
                    $listInfo[$key]['branch_no'] = $bankzone_data[$value['bankzone']]['bank_id'];
                }
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $edit_status = 0;
        if (!empty($authList) && strstr($authList, '/user/Debt/EditUserBankCard') || empty($authList)) {
            $edit_status = 1;
        }
        // $sql = "SELECT * FROM ag_wx_platform";
        // $platform = Yii::app()->fdb->createCommand($sql)->queryAll();
        return $this->renderPartial('UserBankCard', array('edit_status' => $edit_status , 'platform' => $platform));
    }

    public function actionCheckBankzone()
    {
        if (!empty($_POST['bankzone'])) {
            $bankzone = trim($_POST['bankzone']);
            $sql = "SELECT count(id) FROM firstp2p_banklist WHERE name LIKE '%{$bankzone}%' AND status = 1";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                $this->echoJson(array(), 2, '未查询到开户行名称');
            }
            if ($count > 10) {
                $this->echoJson(array(), 3, '查询到开户行名称多余10个，请继续补全名称');
            }
            $sql  = "SELECT id , name FROM firstp2p_banklist WHERE name LIKE '%{$bankzone}%' AND status = 1";
            $res  = Yii::app()->fdb->createCommand($sql)->queryAll();
            $temp = array();
            foreach ($res as $key => $value) {
                $temp[$value['name']] = $value;
            }
            $result = array();
            foreach ($temp as $key => $value) {
                $result[] = $value;
            }
            if (count($result) == 1) {
                $result[0]['selected'] = 'selected';
            }
            $this->echoJson($result, 0, '查询成功');
        } else {
            $this->echoJson(array(), 1, '请输入开户行名称');
        }
    }

    /**
     * 用户银行卡管理 编辑 张健
     */
    public function actionEditUserBankCard()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
                $this->echoJson(array(), 1, '请正确输入银行卡ID');
            }
            if (empty($_POST['bankzone'])) {
                $this->echoJson(array(), 2, '请输入开户行名称');
            }
            if (empty($_POST['verify_status']) || !in_array($_POST['verify_status'], array(1 , 2))) {
                $this->echoJson(array(), 3, '请选择验证状态');
            }
            if (empty($_POST['notes'])) {
                $this->echoJson(array(), 4, '请输入备注');
            }
            $id       = intval($_POST['id']);
            $bankzone = intval($_POST['bankzone']);
            $notes    = trim($_POST['notes']);
            if ($_POST['verify_status'] == 1) {
                $verify_status = 1;
            } elseif ($_POST['verify_status'] == 2) {
                $verify_status = 0;
            }
            $sql = "SELECT * FROM firstp2p_user_bankcard WHERE id = {$id}";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                $this->echoJson(array(), 5, '银行卡ID输入错误');
            }
            $sql   = "SELECT * FROM firstp2p_banklist WHERE id = {$bankzone} AND status = 1";
            $check = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$check) {
                $this->echoJson(array(), 6, '未查询到此开户行名称');
            }
            $time = time();
            $sql = "UPDATE firstp2p_user_bankcard SET bankzone = '{$check['name']}' , verify_status = {$verify_status} , update_time = {$time} , notes = '{$notes}' WHERE id = {$old['id']}";
            $res = Yii::app()->fdb->createCommand($sql)->execute();
            if ($verify_status == 1) {
                $sql = "UPDATE firstp2p_user_bankcard SET verify_status = 0 , update_time = {$time} WHERE id != {$old['id']} AND user_id = {$old['user_id']} ";
                $update = Yii::app()->fdb->createCommand($sql)->execute();
            }
            if ($res) {
                $this->echoJson(array(), 0, '保存成功');
            } else {
                $this->echoJson(array(), 7, '保存失败');
            }
            exit;
        }

        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM firstp2p_user_bankcard WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                return $this->actionError('银行卡ID输入错误', 5);
            }
            $sql  = "SELECT * FROM firstp2p_user WHERE id = {$old['user_id']}";
            $user = Yii::app()->fdb->createCommand($sql)->queryRow();
            $sql  = "SELECT * FROM firstp2p_bank WHERE id = {$old['bank_id']}";
            $bank = Yii::app()->fdb->createCommand($sql)->queryRow();
            $old['bankcard'] = GibberishAESUtil::dec($old['bankcard'], Yii::app()->c->idno_key);
            $user['mobile']  = GibberishAESUtil::dec($user['mobile'], Yii::app()->c->idno_key);
        } else {
            return $this->actionError('请输入银行卡ID', 5);
        }

        return $this->renderPartial('EditUserBankCard', array('info' => $old , 'user' => $user , 'bank' => $bank));
    }

    /**
     * 银行卡审核列表 列表
     */
    public function actionReviewBankCard()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = "";
            // 校验平台ID
            // if (!empty($_POST['platform'])) {
            //     $pla    = intval($_POST['platform']);
            //     $where .= " AND u.platform_id = {$pla} ";
            // }
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND ub.user_id = {$user_id} ";
            }
            // 校验银行卡号
            if (!empty($_POST['bankcard'])) {
                $bankcard = trim($_POST['bankcard']);
                $bankcard = GibberishAESUtil::enc($bankcard, Yii::app()->c->idno_key); // 银行卡号加密
                $where   .= " AND ub.bankcard = '{$bankcard}' ";
            }
            // 校验用户手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND u.mobile = '{$mobile}' ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                $sta = $_POST['status'] - 1;
                $where .= " AND ub.status = {$sta} ";
            }
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
            $sql = "SELECT count(ub.id) AS count FROM ((firstp2p_user AS u INNER JOIN ag_wx_review_bank_record AS ub ON u.id = ub.user_id) INNER JOIN firstp2p_bank AS b ON ub.bank_id = b.id) INNER JOIN firstp2p_banklist AS bl ON ub.branch_id = bl.id AND bl.status = 1 {$where} ";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header("Content-type:application/json; charset=utf-8");
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql = "SELECT ub.id , ub.user_id , u.mobile , b.name , ub.bankcard , u.real_name AS card_name , bl.name AS bankzone , ub.status , ub.crt_time , ub.crt_user_id , ub.remark , bl.province , bl.city , bl.bank_id AS branch_no FROM ((firstp2p_user AS u INNER JOIN ag_wx_review_bank_record AS ub ON u.id = ub.user_id) INNER JOIN firstp2p_bank AS b ON ub.bank_id = b.id) INNER JOIN firstp2p_banklist AS bl ON ub.branch_id = bl.id AND bl.status = 1 {$where} ORDER BY ub.id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            $status[0] = '待审核';
            $status[1] = '审核通过';
            $status[2] = '审核拒绝';

            // $sql = "SELECT * FROM ag_wx_platform";
            // $platform = Yii::app()->fdb->createCommand($sql)->queryAll();
            // foreach ($platform as $key => $value) {
            //     $platform_data[$value['id']] = $value['name'];
            // }
            $crt_user_id_arr = array();
            foreach ($list as $key => $value) {
                if ($value['mobile']) {
                    $value['mobile']        = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密
                }
                if ($value['bankcard']) {
                    $value['bankcard_real'] = GibberishAESUtil::dec($value['bankcard'], Yii::app()->c->idno_key); // 银行卡解密
                }
                if ($value['crt_time'] > 0) {
                    $value['crt_time'] = date('Y-m-d H:i:s', $value['crt_time']);
                } else {
                    $value['crt_time'] = '——';
                }
                $value['status_name'] = $status[$value['status']];
                // $value['platform_id'] = $platform_data[$value['platform_id']];
                $listInfo[] = $value;

                if ($value['crt_user_id'] != 0) {
                    $crt_user_id_arr[] = $value['crt_user_id'];
                }
            }
            if ($crt_user_id_arr) {
                $crt_user_id_str = implode(',', $crt_user_id_arr);
                $sql = "SELECT id, realname FROM itz_user WHERE id IN ({$crt_user_id_str})";
                $user_res = Yii::app()->db->createCommand($sql)->queryAll();
                if ($user_res) {
                    foreach ($user_res as $key => $value) {
                        $user_data[$value['id']] = $value;
                    }
                } else {
                    $user_data = array();
                }
            } else {
                $user_data = array();
            }
            foreach ($listInfo as $key => $value) {
                if (!empty($user_data[$value['crt_user_id']])) {
                    $listInfo[$key]['crt_user_name'] = $user_data[$value['crt_user_id']]['realname'];
                } else {
                    $listInfo[$key]['crt_user_name'] = '——';
                }
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $edit_status = 0;
        if (!empty($authList) && strstr($authList, '/user/Debt/EditReviewBankCard') || empty($authList)) {
            $edit_status = 1;
        }
        // $sql = "SELECT * FROM ag_wx_platform";
        // $platform = Yii::app()->fdb->createCommand($sql)->queryAll();
        return $this->renderPartial('ReviewBankCard', array('edit_status' => $edit_status , 'platform' => $platform));
    }

    /**
     * 银行卡审核列表 查看身份证照片
     */
    public function actionIDCardPicsInfo()
    {
        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT * FROM ag_wx_review_bank_record WHERE id = {$id} ";
            $old = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$old) {
                return $this->actionError('记录ID输入错误', 5);
            }
            $idcard_pics = explode(',', $old['idcard_pics']);
        // foreach ($idcard_pics as $key => $value) {
            //     $idcard_pics[$key] = Yii::app()->c->oss_preview_address.DIRECTORY_SEPARATOR.$value;
            // }
        } else {
            return $this->actionError('请输入记录ID', 5);
        }

        return $this->renderPartial('IDCardPicsInfo', array('idcard_pics' => $idcard_pics));
    }

    /**
     * 银行卡审核列表 通过&拒绝操作
     */
    public function actionEditReviewBankCard()
    {
        if (!empty($_POST)) {
            $model = Yii::app()->fdb;
            if (empty($_POST['id'])) {
                $this->echoJson(array(), 1, '请输入记录ID');
            }
            if (empty($_POST['remark'])) {
                $this->echoJson(array(), 2, '请输入备注');
            }
            if (empty($_POST['status'])) {
                $this->echoJson(array(), 3, '请输入状态');
            }
            $id     = intval($_POST['id']);
            $remark = trim($_POST['remark']);
            $sql = "SELECT * FROM ag_wx_review_bank_record WHERE id = {$id}";
            $res = $model->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 4, '记录ID输入错误');
            }
            if ($res['status'] != 0) {
                $this->echoJson(array(), 5, '记录状态错误');
            }
            $sql      = "SELECT * FROM firstp2p_banklist WHERE id = {$res['branch_id']} AND status = 1";
            $banklist = $model->createCommand($sql)->queryRow();
            if (!$banklist) {
                $this->echoJson(array(), 6, '开户行ID错误');
            }
            $sql  = "SELECT * FROM firstp2p_user WHERE id = {$res['user_id']}";
            $user = $model->createCommand($sql)->queryRow();
            if (!$user) {
                $this->echoJson(array(), 7, '用户ID错误');
            }
            if (!in_array($_POST['status'], array(1 , 2))) {
                $this->echoJson(array(), 8, '状态输入错误');
            }
            $status     = intval($_POST['status']);
            $time       = time();
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $model->beginTransaction();
            $sql = "UPDATE ag_wx_review_bank_record SET crt_time = {$time} , crt_user_id = {$op_user_id} , remark = '{$remark}' , status = {$status} WHERE id = {$res['id']}";
            $update = $model->createCommand($sql)->execute();
            $add = true;
            if ($status == 1) {
                $sql = "UPDATE firstp2p_user_bankcard SET verify_status = 0 , update_time = {$time} WHERE user_id = {$user['id']} ";
                $upd = $model->createCommand($sql)->execute();
                $sql = "INSERT INTO firstp2p_user_bankcard (bank_id , bankcard , bankzone , user_id , card_name , create_time , update_time , verify_status , branch_no) VALUES ({$res['bank_id']} , '{$res['bankcard']}' , '{$banklist['name']}' , {$res['user_id']} , '{$user['real_name']}' , {$time} , {$time} , 1 , {$banklist['bank_id']})";
                $add = $model->createCommand($sql)->execute();
            }
            if ($update && $add) {
                $model->commit();

                // 发送短信
                $smaClass = new XfSmsClass();
                $remind   = array();
                if ($status == 1) {

                    // 审核成功
                    $remind['sms_code'] = "wx_bank_card_auth_success";
                    $remind['mobile']   = GibberishAESUtil::dec($user['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    $send_ret_a         = $smaClass->sendToUserByPhone($remind);
                    if ($send_ret_a['code'] != 0) {
                        Yii::log("EditReviewBankCard sms_code:{$remind['sms_code']}, user_id:{$user['id']}, mobile:{$remind['mobile']}, ag_wx_review_bank_record:{$res['id']}; sendToUser status_1 error:".print_r($remind, true)."; return:".print_r($send_ret_a, true), "error");
                    }
                } elseif ($status == 2) {

                    // 审核失败
                    $remind['sms_code']       = "wx_bank_card_auth_fail";
                    $remind['mobile']         = GibberishAESUtil::dec($user['mobile'], Yii::app()->c->idno_key); // 手机号解密
                    $remind['data']['reason'] = $remark;
                    $send_ret_b               = $smaClass->sendToUserByPhone($remind);
                    if ($send_ret_b['code'] != 0) {
                        Yii::log("EditReviewBankCard sms_code:{$remind['sms_code']}, user_id:{$user['id']}, mobile:{$remind['mobile']}, ag_wx_review_bank_record:{$res['id']}, reason:{$remark}; sendToUser status_2 error:".print_r($remind, true)."; return:".print_r($send_ret_b, true), "error");
                    }
                }
                $this->echoJson(array(), 0, '操作成功');
            } else {
                $model->rollback();
                $this->echoJson(array(), 9, '操作失败');
            }
        }
    }

    /**
     * 部分还款录入
     */
    public function actionAddPartial()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '投资记录ID');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '还款金额（单位元，保留两位小数）');

            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = '还款信息模板 '.date("Y年m月d日 H时i分s秒", time());

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
            exit;
        }

        if (!empty($_POST)) {
            // 校验付款方
            if (empty($_POST['pay_user'])) {
                return $this->actionError('请输入付款方', 5);
            }
            $pay_user = trim($_POST['pay_user']);
            // 校验还款信息
            if (empty($_FILES['template'])) {
                return $this->actionError('请上传还款信息', 5);
            }
            $upload_xls = $this->upload_xls('template');
            if ($upload_xls['code'] != 0) {
                return $this->actionError($upload_xls['info'], 5);
            }
            $template_url = $upload_xls['data'];
            // 校验计划还款日期
            if (!empty($_POST['pay_plan_time'])) {
                $pay_plan_time = strtotime($_POST['pay_plan_time']);
            } else {
                $pay_plan_time = strtotime(date('Y-m-d', time()));
            }
            // 校验还款凭证
            // if (empty($_FILES['proof'])) {
            //     return $this->actionError('请上传还款凭证' , 5);
            // }
            // $upload_rar = $this->upload_rar('proof');
            // if ($upload_rar['code'] != 0) {
            //     return $this->actionError($upload_rar['info'] , 5);
            // }
            // $proof_url = $upload_rar['data'];
            $proof_url = '';

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$template_url;
            $xlsReader = PHPExcel_IOFactory::createReader('Excel5');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('还款信息中无数据', 5);
            }
            if ($Rows > 20001) {
                return $this->actionError('还款信息中的数据超过2万行', 5);
            }
            unset($data[0]);
            $result = PartialService::getInstance()->add_partial_repayment($pay_user, $pay_plan_time, $template_url, $proof_url, $data);
            if ($result['code'] != 0) {
                return $this->actionError($result['info'], 5);
                unlink('./'.$template_url);
                unlink('./'.$proof_url);
            }
            return $this->actionSuccess($result['info'], 3);
        }

        return $this->renderPartial('AddPartial', array());
    }

    /**
     * 部分还款 编辑上传凭证
     */
    public function actionEditPartialProof()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])) {
                return $this->actionError('请输入ID', 5);
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_partial_repayment WHERE id = {$id}";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }
            if ($res['status'] != 1) {
                return $this->actionError('状态错误', 5);
            }
            // 校验还款凭证
            if (empty($_FILES['proof']) && empty($res['proof_url'])) {
                return $this->actionError('请上传还款凭证', 5);
            }
            $upload_rar = $this->upload_rar('proof');
            if ($upload_rar['code'] != 0) {
                if (!empty($res['proof_url'])) {
                    $proof_url = $res['proof_url'];
                } else {
                    return $this->actionError($upload_rar['info'], 5);
                }
            } else {
                $proof_url = $upload_rar['data'];
            }
            // 校验计划还款日期
            $now = strtotime(date('Y-m-d', time()));
            if (!empty($_POST['pay_plan_time'])) {
                $pay_plan_time = strtotime($_POST['pay_plan_time']);
            } else {
                $pay_plan_time = $now;
            }
            if ($pay_plan_time < $now) {
                return $this->actionError('计划还款时间必须大于等于今日凌晨', 5);
            }
            $time = time();
            $sql  = "UPDATE ag_wx_partial_repayment SET proof_url = '{$proof_url}' , pay_plan_time = {$pay_plan_time} , updatetime = {$time} WHERE id = {$res['id']}";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                return $this->actionError('保存失败', 5);
            }
            return $this->actionSuccess('保存成功', 3);
        }

        if (empty($_GET['id'])) {
            return $this->actionError('请输入ID', 5);
        }
        $id  = intval($_GET['id']);
        $sql = "SELECT * FROM ag_wx_partial_repayment WHERE id = {$id}";
        $res = Yii::app()->fdb->createCommand($sql)->queryRow();
        if (!$res) {
            return $this->actionError('ID输入错误', 5);
        }
        if ($res['status'] != 1) {
            return $this->actionError('状态错误', 5);
        }
        $res['pay_plan_time'] = date('Y-m-d', $res['pay_plan_time']);
        $proof_url            = explode('/', $res['proof_url']);
        $res['proof_url']     = $proof_url[2];

        return $this->renderPartial('EditPartialProof', array('res' => $res));
    }

    private function curlRequest($api, $method = 'GET', $params = array(), $headers = [], $json_decode = true)
    {
        $curl = curl_init();
        switch (strtoupper($method)) {
            case 'GET':
                if (!empty($params)) {
                    $api .= (strpos($api, '?') ? '&' : '?') . http_build_query($params);
                }
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, true);
                if (is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
        }

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $api);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }

        $response = curl_exec($curl);
        if ($response === false) {
            curl_close($curl);
            return false;
        } else {
            // 解决windows 服务器 BOM 问题
            $response = trim($response, chr(239).chr(187).chr(191));
            if ($json_decode) {
                $response = json_decode($response, true);
            }
        }
        curl_close($curl);
        return $response;
    }

    /**
     * 受让方列表 - 列表
     */
    public function actionAssigneeList()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $where = "";
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND assi.user_id = {$user_id} ";
            }
            // 校验姓名
            if (!empty($_POST['real_name'])) {
                $real_name = trim($_POST['real_name']);
                $where  .= " AND user.real_name = '{$real_name}' ";
            }
            // 校验手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.mobile = '{$mobile}' ";
            }
            // 校验证件号码
            if (!empty($_POST['idno'])) {
                $idno = trim($_POST['idno']);
                $idno = GibberishAESUtil::enc($idno, Yii::app()->c->idno_key); // 手机号加密
                $where .= " AND user.idno = '{$idno}' ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                $sta    = intval($_POST['status']);
                $where .= " AND assi.status = {$sta} ";
            }
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
            $sql = "SELECT count(assi.id) AS count FROM xf_purchase_assignee AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id   {$where} ";
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
            $sql = "SELECT assi.id , assi.user_id ,  user.real_name , user.mobile , user.idno , assi.frozen_quota,assi.transferability_limit , assi.transferred_amount , assi.agreement_url , assi.status , assi.add_time , assi.purchase_time, assi.purchase_discount FROM xf_purchase_assignee AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id   {$where} ORDER BY assi.id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();
            $status[1] = '待审核';
            $status[2] = '审核通过';
            $status[3] = '审核通过（暂停）';
            //获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');
            foreach ($list as $key => $value) {
                if ($value['mobile']) {
                    $value['mobile'] = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key);
                }
                if ($value['idno']) {
                    $value['idno'] = GibberishAESUtil::dec($value['idno'], Yii::app()->c->idno_key);
                }
                $value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
                $value['transferability_limit']         = number_format($value['transferability_limit'], 2, '.', ',');
                $value['transferred_amount']            = number_format($value['transferred_amount'], 2, '.', ',');
                $value['status_name']                   = $status[$value['status']];
                $temp = explode('/', $value['agreement_url']);
                if ($temp[0] == 'upload') {
                    $value['agreement_url'] = "<a class='layui-btn layui-btn-xs layui-btn-normal' href='/{$value['agreement_url']}' download title='下载合作框架协议'><i class='layui-icon'>&#xe601;</i>下载</a>";
                } elseif ($temp[0] == 'assignee_info') {
                    $value['agreement_url'] = "<a class='layui-btn layui-btn-xs layui-btn-normal' href='".Yii::app()->c->oss_preview_address."/".$value['agreement_url']."' target='_blank' title='下载合作框架协议'><i class='layui-icon'>&#xe601;</i>下载</a>";
                }
                $value['edit_status']    = 0;
                $value['verify_status']  = 0;
                $value['del_status']     = 0;
                $value['suspend_status'] = 0;
                if (!empty($authList) && strstr($authList, '/user/exclusivePurchase/EditAssignee') || empty($authList)) {
                    $value['edit_status'] = 1;
                }
                if (!empty($authList) && strstr($authList, '/user/exclusivePurchase/VerifyAssignee') || empty($authList)) {
                    $value['verify_status'] = 1;
                }
                if (!empty($authList) && strstr($authList, '/user/exclusivePurchase/SuspendAssignee') || empty($authList)) {
                    $value['suspend_status'] = 1;
                }
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $add_status = 0;
        if (!empty($authList) && strstr($authList, '/user/exclusivePurchase/AddAssignee') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('AssigneeList', array('add_status' => $add_status));
    }

    /**
     * 文件上传OSS
     * @param $filePath
     * @param $ossPath
     * @return bool
     */
    private function upload_oss($filePath, $ossPath)
    {
        Yii::log(basename($filePath).'文件正在上传!', CLogger::LEVEL_INFO);
        try {
            ini_set('memory_limit', '2048M');
            $res = Yii::app()->oss->bigFileUpload($filePath, $ossPath);
            unlink($filePath);
            return $res;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            return false;
        }
    }

    /**
     * 受让方列表 - 添加
     */
    public function actionAddAssignee()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel2007.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');

            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $name = '受让方分配用户ID '.date("Y年m月d日 H时i分s秒", time());

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xlsx"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
            exit;
        }

        if (!empty($_POST)) {
            if (empty($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
                return $this->actionError('请正确输入用户ID', 60);
            }
            if (empty($_POST['purchase_time']) || !is_numeric($_POST['purchase_time']) || $_POST['purchase_time'] <= 0) {
                return $this->actionError('请正确输入收购时效', 60);
            }
            if (empty($_POST['purchase_discount']) || !is_numeric($_POST['purchase_discount']) || $_POST['purchase_discount']<0.01 || $_POST['purchase_discount']>10) {
                return $this->actionError('请正确输入收购最高折扣', 60);
            }
            $user_id = intval($_POST['user_id']);
            $sql     = "SELECT * FROM firstp2p_user WHERE id = {$user_id}";
            $res     = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('用户ID输入错误', 60);
            }
            if ($res['is_effect'] != 1) {
                return $this->actionError('此用户账号无效', 60);
            }
            if ($res['is_delete'] != 0) {
                return $this->actionError('此用户账号已被放入回收站', 60);
            }
            $sql   = "SELECT * FROM xf_purchase_assignee WHERE user_id = {$user_id} AND status != 0";
            $check = Yii::app()->phdb->createCommand($sql)->queryRow();
            if ($check) {
                return $this->actionError('此用户ID已被加入受让方', 60);
            }
            if (empty($_POST['limit']) || !is_numeric($_POST['limit']) || $_POST['limit'] <= 0 || $_POST['limit'] > 1000000000) {
                return $this->actionError('请正确输入受让额度', 60);
            }
            $transferability_limit = round($_POST['limit'], 2);
            $file = $this->upload_rar('file');
            if ($file['code'] !== 0) {
                return $this->actionError($file['info'], 5);
            }
            $upload_oss = $this->upload_oss('./'.$file['data'], 'assignee_info/'.$file['data']);
            if ($upload_oss === false) {
                return $this->actionError('合作框架协议上传至OSS失败', 60);
            }
            unlink('./'.$file['data']);
            $agreement_url = 'assignee_info/'.$file['data'];
            $time        = time();
            $insert_data = array();
            $file_a = $this->upload_xls('file_a');
            if ($file_a['code'] !== 0) {
                return $this->actionError($file_a['info'], 60);
            }
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
            $xlsPath   = './'.$file_a['data'];
            $xlsReader = PHPExcel_IOFactory::createReader('Excel2007');
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets = $xlsReader->load($xlsPath);
            $Rows   = $Sheets->getSheet(0)->getHighestRow();
            $data   = $Sheets->getSheet(0)->toArray();
            if ($Rows < 2) {
                return $this->actionError('分配用户ID文件中无数据', 60);
            }
            if ($Rows > 100001) {
                return $this->actionError('分配用户ID文件中的数据超过十万行', 60);
            }
            unset($data[0]);
            unlink('./'.$file_a['data']);
            $user_id_arr   = array();
            $f = $s = 0;
            foreach ($data as $key => $value) {
                if (empty($value[0]) || !is_numeric($value[0])) {
                    $insert_data[$key] = "({$user_id} , 0 , 2 , '上传文件中用户ID输入错误' , {$time})";
                    $f+=1;
                } elseif (in_array( $value[0], $user_id_arr)) {
                    $insert_data[$key] = "({$user_id} , {$value[0]} , 2 , '上传文件中用户ID重复出现' , {$time})";
                    $f+=1;
                } elseif ( !in_array($value[0], $user_id_arr)) {
                    $user_id_arr[$key]  = intval($value[0]);
                }
            }
            //校验用户ID
            if ($user_id_arr) {
                $user_id_str = implode(',', $user_id_arr);
                //普惠在途用户
                $sql = "SELECT distinct user_id FROM firstp2p_deal_load  WHERE status=1 and xf_status=0 and black_status=1 and user_id IN ({$user_id_str}) ";
                $ph_user_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                //智多新在途用户
                $zdx_sql = "SELECT distinct user_id FROM offline_deal_load  WHERE platform_id=4 and status=1 and xf_status=0 and black_status=1 and user_id IN ({$user_id_str}) ";
                $zdx_user_id_res = Yii::app()->offlinedb->createCommand($zdx_sql)->queryColumn();
                $check_user = array_merge($ph_user_id_res, $zdx_user_id_res);


                //已分配校验
                $au_sql = "SELECT distinct au.user_id   FROM xf_purchase_assignee AS info INNER JOIN xf_assignee_user AS au ON info.user_id = au.assignee_user_id AND info.status = 2 AND au.status = 1 and au.assignee_user_id !=$user_id  AND au.user_id IN ({$user_id_str}) ";
                $check_assi_id_res  = Yii::app()->phdb->createCommand($au_sql)->queryColumn();

                foreach ($user_id_arr as $key=>$val){
                    if(in_array($val, $check_assi_id_res)){
                        $insert_data[$key] = "({$user_id} , {$val} , 2 , '上传文件中用户ID已分配，不可重复分配' , {$time})";
                        $f+=1;
                        continue;
                    }
                    if(!in_array($val, $check_user)){
                        $insert_data[$key] = "({$user_id} , {$val} , 2 , '上传文件中用户ID未持有有效在途债权' , {$time})";
                        $f+=1;
                        continue;
                    }
                    $insert_data[$key] = "({$user_id} , {$val} , 1 , '' , {$time})";
                    $s+=1;
                }
            }

            //如果全部失败禁止录入
            $c_row = $Rows-1;
            if ($c_row == $f) {
                return $this->actionError('分配用户ID文件中无有效数据，请核对数据后再录入', 60);
            }


            ksort($insert_data);
            $phdb = Yii::app()->phdb;
            $phdb->beginTransaction();

            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            $sql = "INSERT INTO xf_purchase_assignee ( user_id , purchase_time,purchase_discount,transferability_limit , agreement_url , status , add_user_id , add_ip , add_time ) VALUES ({$user_id} ,{$_POST['purchase_time']},{$_POST['purchase_discount']}, {$transferability_limit} , '{$agreement_url}' , 1 , {$op_user_id} , '{$ip}' , {$time} ) ";
            $add_assignee_info = $phdb->createCommand($sql)->execute();
            $add_assignee_deal = true;
            if ($insert_data) {
                $insert_data_str = implode(',', $insert_data);
                $sql = "INSERT INTO xf_assignee_user (assignee_user_id , user_id , status , failed_reason , add_time) VALUES {$insert_data_str} ";
                $add_assignee_user = $phdb->createCommand($sql)->execute();
            }
            //受让方添加
            if (!$add_assignee_info || !$add_assignee_user) {
                $phdb->rollback();
                return $this->actionError('添加受让方失败', 60);
            }
            $phdb->commit();
            $i_c = $f+$s;
            return $this->actionSuccess("添加受让方成功 </br> <table style='margin-left: 20px;width:100%;font-size:20px;text-align: left;font-weight:300;'><tr><td >本次上传'用户分配表':</td> </tr> <tr><td >共上传：".$i_c." 人; 校验成功：".$s." 人;校验失败:".$f." 人 </td> </tr> <tr><td >上传说明：每次上传分配数据后，以最后一次'校验成功'的用户为最终分配结果</td> </tr></table>", 60);
        }

        return $this->renderPartial('AddAssignee', array());
    }

    /**
     * 受让方列表 - 指定借款ID详情
     */
    public function actionAssigneeUser()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $assignee_user_id = intval($_POST['assignee_user_id']);
            $where = " WHERE assignee_user_id = {$assignee_user_id} AND status IN (1, 2, 3) ";
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND user_id = {$user_id} ";
            }

            // 校验上传状态
            if (!empty($_POST['status'])) {
                $sta    = intval($_POST['status']);
                $where .= " AND status = {$sta} ";
            }
            // 校验上传状态
            if ($_POST['purchase_status'] === 0 || $_POST['purchase_status'] == 1) {
                $purchase_status  = intval($_POST['purchase_status']);
                $where .= " AND purchase_status = {$purchase_status} ";
            }
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
            $sql = "SELECT count(*) AS count FROM xf_assignee_user {$where} ";
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
            $sql = "SELECT * FROM xf_assignee_user {$where} ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->phdb->createCommand($sql)->queryAll();

            $status = array(1 => '上传成功' , 2 => '上传失败' , 3 => '上传成功（失效）');
            $purchase_status = ['待收购', '已收购'];
            foreach ($list as $key => $value) {
                $value['status_name']   = $status[$value['status']];
                $value['purchase_status_name']   = $purchase_status[$value['purchase_status']];
                $value['add_time']      = date('Y-m-d H:i:s', $value['add_time']);
                $listInfo[] = $value;
            }

            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $daochu_status = 0;
        if (!empty($authList) && strstr($authList, '/user/Debt/AssigneeDealExcel') || empty($authList)) {
            $daochu_status = 1;
        }
        return $this->renderPartial('AssigneeUser', array('daochu_status' => $daochu_status));
    }

    /**
     * 受让方列表 - 指定借款ID详情导出
     */
    public function actionAssigneeUser2Excel()
    {
        if (!empty($_GET)) {
            $model = Yii::app()->phdb;
            // 条件筛选
            if (empty($_GET['assignee_user_id']) || !is_numeric($_GET['assignee_user_id']) ) {
                echo '<h1>请正确输入信息</h1>';exit;
            }
            $where = " WHERE assignee_user_id = '{$_GET['assignee_user_id']}' AND status IN (1, 2, 3)  ";
            $sql = "SELECT * FROM xf_assignee_user $where ";
            $partial = $model->createCommand($sql)->queryRow();
            if (!$partial) {
                echo '<h1>暂无数据需要导出</h1>';exit;
            }

            // 用户ID
            if (!empty($_GET['user_id'])) {
                $user_id = trim($_GET['user_id']);
                $where  .= " AND user_id = '{$user_id}' ";
            }
            // 导入状态
            if (!empty($_GET['status'])) {
                $status = intval($_GET['status']);
                $where .= " AND status = '{$status}' ";
            }
            // 收购状态
            if ($_POST['purchase_status'] === 0 || $_POST['purchase_status'] == 1) {
                $purchase_status  = intval($_POST['purchase_status']);
                $where .= " AND purchase_status = {$purchase_status} ";
            }
            // 查询数据
            $sql = "SELECT * FROM xf_assignee_user {$where} ";
            $list = $model->createCommand($sql)->queryAll();
            if (!$list) {
                echo '<h1>未查询到数据</h1>';exit;
            }
            $status = array(1 => '上传成功' , 2 => '上传失败' , 3 => '上传成功（失效）');
            $purchase_status = ['待收购', '已收购'];
            foreach ($list as $key => $value) {
                $value['status_name']   = $status[$value['status']];
                $value['purchase_status_name']   = $purchase_status[$value['purchase_status']];
                $value['add_time']      = date('Y-m-d H:i:s', $value['add_time']);
                $listInfo[] = $value;
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel5.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objPHPExcel->getActiveSheet()->setCellValue('A1' , '序号');
            $objPHPExcel->getActiveSheet()->setCellValue('B1' , '用户ID');
            $objPHPExcel->getActiveSheet()->setCellValue('C1' , '上传状态');
            $objPHPExcel->getActiveSheet()->setCellValue('D1' , '收购状态');
            $objPHPExcel->getActiveSheet()->setCellValue('E1' , '上传时间');
            $objPHPExcel->getActiveSheet()->setCellValue('F1' , '原因');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2) , $value['id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2) , $value['user_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2) , $value['status_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2) , $value['purchase_status_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2) , $value['add_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2) , $value['failed_reason']);
            }
            $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
            $name = "定向收购-受让人分配用户ID导出 ".date("Y年m月d日 H时i分s秒" , time());
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xls"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
        }


    }

    /**
     * 受让方列表 - 查看 - 导出
     */
    public function actionAssigneeDealExcel()
    {
        if (!empty($_GET['user_id'])) {
            // 条件筛选
            $user_id = intval($_GET['user_id']);
            $where = " WHERE user_id = {$user_id} AND status IN (1, 2, 3) ";
            // 校验借款ID
            if (!empty($_GET['deal_id'])) {
                $deal_id = intval($_GET['deal_id']);
                $where  .= " AND deal_id = {$deal_id} ";
            }
            // 校验借款标题
            if (!empty($_GET['deal_name'])) {
                $deal_name = trim($_GET['deal_name']);
                $where    .= " AND deal_name = '{$deal_name}' ";
            }
            // 校验上传状态
            if (!empty($_GET['status'])) {
                $sta    = intval($_GET['status']);
                $where .= " AND status = {$sta} ";
            }
            // 校验所属平台
            if (!empty($_GET['platform_id'])) {
                $platform_id = intval($_GET['platform_id']);
                $where      .= " AND platform_id = {$platform_id} ";
            }
            $sql   = "SELECT count(*) AS count FROM ag_wx_assignee_deal {$where} ";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                echo '<h1>暂无数据</h1>';
                exit;
            }
            // 查询数据
            $sql  = "SELECT * FROM ag_wx_assignee_deal {$where} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();
            $platform[1] = '尊享';
            $platform[2] = '普惠';
            $platform[3] = '工场微金';
            $platform[4] = '智多新';
            $platform[5] = '交易所';
            $status = array(1 => '上传成功' , 2 => '上传失败' , 3 => '上传成功（失效）');
            
            foreach ($list as $key => $value) {
                $value['platform_name'] = $platform[$value['platform_id']];
                $value['status_name']   = $status[$value['status']];
                $value['add_time']      = date('Y-m-d H:i:s', $value['add_time']);

                $listInfo[] = $value;
            }

            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel2007.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(60);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '借款ID');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', '借款标题');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', '所属平台');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', '平台ID');
            $objPHPExcel->getActiveSheet()->setCellValue('E1', '状态');
            $objPHPExcel->getActiveSheet()->setCellValue('F1', '上传时间');
            $objPHPExcel->getActiveSheet()->setCellValue('G1', '原因');

            foreach ($listInfo as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValue('A' . ($key + 2), $value['deal_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . ($key + 2), $value['deal_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . ($key + 2), $value['platform_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . ($key + 2), $value['platform_id']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . ($key + 2), $value['status_name']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . ($key + 2), $value['add_time']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . ($key + 2), $value['failed_reason']);
            }
            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $name = '受让方(用户ID：'.$user_id.')指定借款ID '.date("Y年m月d日 H时i分s秒", time());
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xlsx"');
            header("Content-Transfer-Encoding:binary");
            $objWriter->save('php://output');
        }
    }

    /**
     * 受让方列表 - 编辑
     */
    public function actionEditAssignee()
    {
        ini_set('max_execution_time', '0');
        if ($_GET['download'] == 1) {
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
            include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/Writer/Excel2007.php';
            $objPHPExcel = new PHPExcel();
            // 设置当前的sheet
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('第一页');
            // 保护
            // $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', '用户ID');

            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
            $name = '受让方分配用户ID '.date("Y年m月d日 H时i分s秒", time());

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");
            header('Content-Disposition:attachment;filename="'.$name.'.xlsx"');
            header("Content-Transfer-Encoding:binary");

            $objWriter->save('php://output');
            exit;
        }

        if (!empty($_POST)) {
            if (empty($_POST['id'])) {
                return $this->actionError('请输入ID', 5);
            }
            $phdb  = Yii::app()->phdb;
            $time = time();
            $id   = intval($_POST['id']);
            $sql  = "SELECT assi.id , assi.user_id ,  user.real_name , user.mobile , user.idno , assi.transferability_limit , assi.transferred_amount , assi.agreement_url , assi.status , assi.add_time , assi.purchase_time , assi.purchase_discount   FROM xf_purchase_assignee AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status != 0 AND assi.id = {$id} ";
            $res = $phdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            $user_id      = $res['user_id'];
            $add_user_sql = " , update_user_id = {$op_user_id} , update_ip = '{$ip}' , update_time = {$time} ";
            //受让额度校验
            if (empty($_POST['limit']) || !is_numeric($_POST['limit']) || $_POST['limit'] < $res['transferred_amount'] || $_POST['limit'] > 1000000000) {
                return $this->actionError('请正确输入受让额度', 5);
            }
            if (empty($_POST['purchase_time']) || !is_numeric($_POST['purchase_time']) || $_POST['purchase_time'] <= 0) {
                return $this->actionError('请正确输入收购时效', 5);
            }
            if (empty($_POST['purchase_discount']) || !is_numeric($_POST['purchase_discount']) || $_POST['purchase_discount']<0.01 || $_POST['purchase_discount']>10) {
                return $this->actionError('请正确输入收购最高折扣', 5);
            }
            $transferability_limit = round($_POST['limit'], 2);

            //编辑合作协议
            $agreement_url_sql = '';
            if($_FILES['file']['size'] > 0){
                $file = $this->upload_rar('file');
                if ($file['code'] != 0) {
                    return $this->actionError('合作框架协议上传失败', 5);
                }
                $upload_oss = $this->upload_oss('./'.$file['data'], 'assignee_info/'.$file['data']);
                if ($upload_oss === false) {
                    return $this->actionError('合作框架协议上传至OSS失败', 5);
                }
                unlink('./'.$file['data']);
                $agreement_url     = 'assignee_info/'.$file['data'];
                $agreement_url_sql = " , agreement_url = '{$agreement_url}' ";
            }

            $status_sql = "";
            if($_FILES['file_a']['size'] > 0){
                $insert_data = array();
                $file_a = $this->upload_xls('file_a');
                if ($file_a['code'] !== 0 && $_FILES['file_a']['size'] > 0) {
                    return $this->actionError($file_a['info'], 5);
                }

                if ($file_a['code'] === 0) {
                    include APP_DIR . '/protected/extensions/phpexcel/PHPExcel.php';
                    include APP_DIR . '/protected/extensions/phpexcel/PHPExcel/IOFactory.php';
                    $xlsPath   = './'.$file_a['data'];
                    $xlsReader = PHPExcel_IOFactory::createReader('Excel2007');
                    $xlsReader->setReadDataOnly(true);
                    $xlsReader->setLoadSheetsOnly(true);
                    $Sheets = $xlsReader->load($xlsPath);
                    $Rows   = $Sheets->getSheet(0)->getHighestRow();
                    $data   = $Sheets->getSheet(0)->toArray();
                    if ($Rows < 2) {
                        return $this->actionError('分配用户ID文件中无数据', 5);
                    }
                    if ($Rows > 100001) {
                        return $this->actionError('分配用户ID文件中的数据超过十万行', 5);
                    }
                    unset($data[0]);
                    unlink('./'.$file_a['data']);
                    $user_id_arr   = array();
                    foreach ($data as $key => $value) {
                        if (empty($value[0]) || !is_numeric($value[0])) {
                            $insert_data[$key] = "({$user_id} , {$value[0]} , 2 , '上传文件中用户ID输入错误' , {$time})";
                        } elseif (in_array( $value[0], $user_id_arr)) {
                            $insert_data[$key] = "({$user_id} , {$value[0]} , 2 , '上传文件中用户ID重复出现' , {$time})";
                        } elseif ( !in_array($value[0], $user_id_arr)) {
                            $user_id_arr[$key]  = intval($value[0]);
                        }
                    }

                    //校验用户ID
                    if ($user_id_arr) {
                        $user_id_str = implode(',', $user_id_arr);
                        //普惠在途用户
                        $sql = "SELECT distinct user_id FROM firstp2p_deal_load  WHERE status=1 and xf_status=0 and black_status=1 and user_id IN ({$user_id_str}) ";
                        $ph_user_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                        //智多新在途用户
                        $zdx_sql = "SELECT distinct user_id FROM offline_deal_load  WHERE platform_id=4 and status=1 and xf_status=0 and black_status=1 and user_id IN ({$user_id_str}) ";
                        $zdx_user_id_res = Yii::app()->offlinedb->createCommand($zdx_sql)->queryColumn();
                        $check_user = array_merge($ph_user_id_res, $zdx_user_id_res);

                        //已分配校验
                        $au_sql = "SELECT distinct au.user_id   FROM xf_purchase_assignee AS info INNER JOIN xf_assignee_user AS au ON info.user_id = au.assignee_user_id AND info.status = 2 AND au.status = 1 and au.assignee_user_id !=$user_id  AND au.user_id IN ({$user_id_str}) ";
                        $check_assi_id_res  = Yii::app()->phdb->createCommand($au_sql)->queryColumn();

                        //已收购完成校验
                        $purchase_sql = "SELECT distinct au.user_id   FROM  xf_assignee_user AS au where   au.status = 1 and au.purchase_status =1 and au.assignee_user_id =$user_id  AND au.user_id IN ({$user_id_str}) ";
                        $check_purchase_id_res  = Yii::app()->phdb->createCommand($purchase_sql)->queryColumn();

                        foreach ($user_id_arr as $key=>$val){
                            //已经收购的不可重新分配
                            if(in_array($val, $check_purchase_id_res)){
                                continue;
                            }
                            if(in_array($val, $check_assi_id_res)){
                                $insert_data[$key] = "({$user_id} , {$val} , 2 , '上传文件中用户ID已分配，不可重复分配' , {$time})";
                                continue;
                            }
                            if(!in_array($val, $check_user)){
                                $insert_data[$key] = "({$user_id} , {$val} , 2 , '上传文件中用户ID未持有有效在途债权' , {$time})";
                                continue;
                            }
                            $insert_data[$key] = "({$user_id} , {$val} , 1 , '' , {$time})";
                        }
                    }
                }
            }

            ksort($insert_data);
            $phdb->beginTransaction();

            //移除未被收购的原分配用户信息
            $sql = "UPDATE xf_assignee_user SET status = 0 , failed_reason = '修改，被移除！' WHERE assignee_user_id = {$user_id} and purchase_status=0  ";
            $delete_assignee_user = $phdb->createCommand($sql)->execute();


            //更新受让人信息
            $sql = "UPDATE xf_purchase_assignee SET update_time = {$time} , transferability_limit = {$transferability_limit} , purchase_time = {$_POST['purchase_time']}, purchase_discount = {$_POST['purchase_discount']}   {$agreement_url_sql} {$add_user_sql} {$status_sql} WHERE id = {$res['id']} ";
            $update_assignee_info = $phdb->createCommand($sql)->execute();

            $add_assignee_deal = true;
            if ($insert_data) {
                $status_sql = " , status = 1 ";
                $insert_data_str = implode(',', $insert_data);
                $sql = "INSERT INTO xf_assignee_user (assignee_user_id , user_id , status , failed_reason , add_time) VALUES {$insert_data_str} ";
                $add_assignee_user = $phdb->createCommand($sql)->execute();
            }

            if (!$update_assignee_info || !$add_assignee_deal) {
                $phdb->rollback();
                return $this->actionError('保存失败', 5);
            }
            $phdb->commit();
            return $this->actionSuccess('保存成功', 3);
        }

        if (!empty($_GET['id'])) {
            $id  = intval($_GET['id']);
            $sql = "SELECT assi.id , assi.user_id ,  user.real_name , user.mobile , user.idno , assi.transferability_limit , assi.transferred_amount , assi.agreement_url , assi.status , assi.add_time, assi.purchase_time, assi.purchase_discount   FROM xf_purchase_assignee AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status != 0 AND assi.id = {$id} ";
            $res = Yii::app()->phdb->createCommand($sql)->queryRow();
            if (!$res) {
                return $this->actionError('ID输入错误', 5);
            }
            if ($res['mobile']) {
                $res['mobile'] = GibberishAESUtil::dec($res['mobile'], Yii::app()->c->idno_key);
            }
            if ($res['idno']) {
                $res['idno'] = GibberishAESUtil::dec($res['idno'], Yii::app()->c->idno_key);
            }
            $agreement_url         = explode('/', $res['agreement_url']);
            if ($agreement_url[0] == 'upload') {
                $res['agreement_name'] = $agreement_url[2];
            } elseif ($agreement_url[0] == 'assignee_info') {
                $res['agreement_name'] = $agreement_url[3];
            }
        } else {
            return $this->actionError('请输入ID', 5);
        }

        return $this->renderPartial('EditAssignee', array('res' => $res));
    }

    /**
     * 受让方列表 - 审核
     */
    public function actionVerifyAssignee()
    {
        if (empty($_POST['id'])) {
            $this->echoJson(array(), 1, '请输入ID');
        }
        if (empty($_POST['status']) || !in_array($_POST['status'], [2,4])) {
            $this->echoJson(array(), 1, '状态异常，请联系研发团队');
        }

        $id  = intval($_POST['id']);
        $sql = "SELECT * FROM xf_purchase_assignee where  id = {$id} ";
        $res = Yii::app()->phdb->createCommand($sql)->queryRow();
        if (!$res) {
            $this->echoJson(array(), 2, 'ID输入错误');
        }
        if ($res['status'] != 1) {
            $this->echoJson(array(), 3, '状态错误,非待审核状态');
        }

        //有待收购用户
        $sql = "SELECT * FROM xf_assignee_user WHERE assignee_user_id = {$res['user_id']} AND status=1 and purchase_status=0";
        $user_id_arr = Yii::app()->phdb->createCommand($sql)->queryAll();
        if (!$user_id_arr) {
            $this->echoJson(array(), 4, '此受让方未查询到上传成功且待收购的用户ID');
        }

        $op_user_id = Yii::app()->user->id;
        $op_user_id = $op_user_id ? $op_user_id : 0 ;
        $ip = Yii::app()->request->userHostAddress;
        $time = time();
        $sql = "UPDATE xf_purchase_assignee SET update_time = {$time} , status = {$_POST['status']} , update_user_id = {$op_user_id} , update_ip = '{$ip}' WHERE id = {$res['id']} ";
        $update_assignee_info = Yii::app()->phdb->createCommand($sql)->execute();
        if (!$update_assignee_info ) {
            $this->echoJson(array(), 4, '操作失败');
        }
        $this->echoJson(array(), 0, '操作成功');
    }

    /**
     * 受让方列表 - 移除
     */
    public function actionDelAssignee()
    {
        if (!empty($_POST)) {
            if (empty($_POST['id'])) {
                $this->echoJson(array(), 1, '请输入ID');
            }
            $fdb = Yii::app()->fdb;
            $id  = intval($_POST['id']);
            $sql = "SELECT assi.id , assi.user_id ,  user.real_name , user.mobile , user.idno , assi.transferability_limit , assi.transferred_amount , assi.agreement_url , assi.status , assi.add_time , assi.buyer_type FROM ag_wx_assignee_info AS assi INNER JOIN firstp2p_user AS user ON assi.user_id = user.id AND assi.status != 0 AND assi.id = {$id} ";
            $res = $fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 2, 'ID输入错误');
            }
            if ($res['status'] != 1) {
                $this->echoJson(array(), 3, '状态错误');
            }
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $ip         = Yii::app()->request->userHostAddress;
            $time       = time();
            $fdb->beginTransaction();
            $sql = "UPDATE ag_wx_assignee_info SET update_time = {$time} , status = 0 , update_user_id = {$op_user_id} , update_ip = '{$ip}' WHERE id = {$res['id']} ";
            $result = $fdb->createCommand($sql)->execute();
            $update = true;
            if ($res['buyer_type'] == 2) {
                $sql    = "UPDATE ag_wx_assignee_deal SET status = 0 , failed_reason = '未通过，被移除！' WHERE user_id = {$res['user_id']}";
                $update = $fdb->createCommand($sql)->execute();
            }
            if (!$result || !$update) {
                $fdb->rollback();
                $this->echoJson(array(), 4, '操作失败');
            }
            $fdb->commit();
            $this->echoJson(array(), 0, '操作成功');
        }
    }

    /**
     * 受让方列表 - 暂停
     */
    public function actionSuspendAssignee()
    {
        if (empty($_POST['id'])) {
            $this->echoJson(array(), 1, '请输入ID');
        }
        if (empty($_POST['status']) || !in_array($_POST['status'], [3,2])) {
            $this->echoJson(array(), 1, '状态有误操作失败');
        }
        $phdb = Yii::app()->phdb;
        $id  = intval($_POST['id']);
        $status = $_POST['status'];
        $sql = "SELECT * FROM xf_purchase_assignee  where  id = {$id} ";
        $res = $phdb->createCommand($sql)->queryRow();
        if (!$res) {
            $this->echoJson(array(), 2, 'ID输入错误');
        }
        if ($_POST['status'] == 3 && $res['status'] != 2) {
            $this->echoJson(array(), 3, '状态错误,审核通过状态下才可暂停');
        }
        if ($_POST['status'] == 2 && $res['status'] != 3) {
            $this->echoJson(array(), 3, '状态错误,暂停状态下才可取消暂停');
        }

        $op_user_id = Yii::app()->user->id;
        $op_user_id = $op_user_id ? $op_user_id : 0 ;
        $ip = Yii::app()->request->userHostAddress;
        $time = time();
        $sql = "UPDATE xf_purchase_assignee SET update_ip = '{$ip}' ,update_user_id = {$op_user_id} ,update_time = {$time} , status = {$status} WHERE id = {$res['id']} ";
        $update_assignee_info = $phdb->createCommand($sql)->execute();
        if (!$update_assignee_info) {
            $this->echoJson(array(), 5, '操作失败');
        }
        $this->echoJson(array(), 0, '操作成功');
    }

    /**
     * 受让方列表 - AJAX校验用户ID
     */
    public function actionAssigneeChangeUserId()
    {
        if (!empty($_POST['user_id'])) {
            $user_id = trim($_POST['user_id']);
            if (!is_numeric($user_id) || $user_id < 1) {
                $this->echoJson(array(), 2, '请正确输入用户ID');
            }
            $user_id = intval($user_id);
            $sql     = "SELECT * FROM firstp2p_user WHERE id = {$user_id}";
            $res     = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array(), 3, '用户ID输入错误');
            }
            if ($res['is_effect'] != 1) {
                $this->echoJson(array(), 4, '此用户账号无效');
            }
            if ($res['is_delete'] != 0) {
                $this->echoJson(array(), 5, '此用户账号已被放入回收站');
            }
            $sql   = "SELECT * FROM ag_wx_assignee_info WHERE user_id = {$user_id} AND status != 0 ";
            if (!empty($_POST['old_id'])) {
                $old_id = intval($_POST['old_id']);
                $sql .= " AND id != {$old_id} ";
            }
            $check = Yii::app()->fdb->createCommand($sql)->queryRow();
            if ($check) {
                $this->echoJson(array(), 6, '此用户ID已被加入受让方');
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
            $this->echoJson(array(), 1, '请输入用户ID');
        }
    }

    /**
     * 修改密码
     */
    public function actionEditPassword()
    {
        $id = Yii::app()->user->id;
        $sql = "SELECT * FROM itz_user WHERE id = {$id}";
        $old = Yii::app()->db->createCommand($sql)->queryRow();

        if (!empty($_POST)) {
            if (empty($_POST['old_password'])) {
                exit(json_encode(array('data'=>array() , 'code'=>1, 'info'=>"请输入旧密码")));
            }
            if (empty($_POST['password']) || empty($_POST['repass'])) {
                exit(json_encode(array('data'=>array() , 'code'=>2, 'info'=>"请输入密码")));
            }
            if ($_POST['password'] != $_POST['repass']) {
                exit(json_encode(array('data'=>array() , 'code'=>3, 'info'=>"两次密码不一致")));
            }
            if (!$old) {
                exit(json_encode(array('data'=>array() , 'code'=>4, 'info'=>"获取用户信息失败，请重新登录")));
            }
            $old_password = md5(md5($_POST['old_password']));
            if ($old_password != $old['password']) {
                exit(json_encode(array('data'=>array() , 'code'=>5, 'info'=>"旧密码输入错误")));
            }
            $password = md5(md5($_POST['password']));
            if ($password == $old['password']) {
                exit(json_encode(array('data'=>array() , 'code'=>6, 'info'=>"新密码与旧密码一致，无需修改")));
            }
            $sql = "UPDATE itz_user SET password = '{$password}' WHERE id = {$id}";

            $result = Yii::app()->db->createCommand($sql)->execute();
            if (!$result) {
                exit(json_encode(array('data'=>array() , 'code'=>7, 'info'=>"修改密码失败")));
            }
            exit(json_encode(array('data'=>array() , 'code'=>0, 'info'=>"修改密码成功")));
        }

        return $this->renderPartial("EditPassword", array('old'=>$old));
    }


    /**
     * 获取咨询方查询条件
     * @param $name
     * @param $type
     * @return string
     */
    private function getAdvisoryConditions($name, $type)
    {
        $conditions = " AND deal.advisory_id is NULL ";
        if (empty($name) || !in_array($type, [1,2])) {
            return $conditions;
        }
        $name = trim($name);
        $dbname = $type == 1 ? 'fdb' : 'phdb';
        $sql = "SELECT id FROM firstp2p_deal_agency where name = '{$name}' ";
        $advisory_list = Yii::app()->$dbname->createCommand($sql)->queryAll();
        if (!$advisory_list) {
            return $conditions;
        }

        $adv_arr = [];
        foreach ($advisory_list as $advisory) {
            $adv_arr[] = $advisory['id'];
        }
        $conditions =  " AND deal.advisory_id IN (". implode(',', $adv_arr) .") ";
        return $conditions;
    }



    /**
     * 汇源专区客服判定记录
     */
    public function actionHyDebtAppealA()
    {
        if (!empty($_POST)) {
            // 条件筛选
            $da_sta = intval($_POST['da_status']);
            $where = " AND debt.id = -1 ";
            $type = $_POST['type'] ?: 2;
            $sql = "SELECT debt_id FROM ag_wx_debt_appeal WHERE type = {$type} AND products = 2 AND status = {$da_sta} and area_id=1 ";
            $where_debt_id_arr = Yii::app()->fdb->createCommand($sql)->queryColumn();
            if ($where_debt_id_arr) {
                $where_debt_id_str = implode(',', $where_debt_id_arr);
                $where = " AND debt.id IN ({$where_debt_id_str}) ";
            }
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND debt.user_id = {$user_id} ";
            }
            // 校验债转编号
            if (!empty($_POST['serial_number'])) {
                $serial_number = trim($_POST['serial_number']);
                $where .= " AND debt.serial_number = '{$serial_number}' ";
            }
            // 校验用户手机号
            if (!empty($_POST['mobile'])) {
                $mobile = trim($_POST['mobile']);
                $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                $user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($user_id) {
                    $where .= " AND debt.user_id = {$user_id} ";
                } else {
                    $where .= " AND debt.user_id is NULL ";
                }
            }
            // 校验项目ID
            if (!empty($_POST['borrow_id'])) {
                $borrow_id = intval($_POST['borrow_id']);
                $where .= " AND debt.borrow_id = {$borrow_id} ";
            }
            // 校验投资记录ID
            if (!empty($_POST['tender_id'])) {
                $tender_id = intval($_POST['tender_id']);
                $where .= " AND debt.tender_id = {$tender_id} ";
            }
            // 校验转让状态
            if (!empty($_POST['status'])) {
                $sta = intval($_POST['status']);
                $where .= " AND debt.status = {$sta} ";
            }
            // 校验项目名称
            if (!empty($_POST['name'])) {
                $name = trim($_POST['name']);
                $where .= " AND deal.name = '{$name}' ";
            }
            // 校验债转类型
            if (!empty($_POST['debt_src'])) {
                $src = intval($_POST['debt_src']);
                $where .= " AND debt.debt_src = {$src} ";
            }
            // 校验借款方名称
            if (!empty($_POST['company'])) {
                $company = trim($_POST['company']);
                $sql = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND c.is_effect = 1 AND c.is_delete = 0 ";
                $com_a = Yii::app()->fdb->createCommand($sql)->queryScalar();
                $sql = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' AND e.company_purpose = 2";
                $com_b = Yii::app()->fdb->createCommand($sql)->queryScalar();
                $com_arr = array();
                if ($com_a) {
                    $com_arr[] = $com_a;
                }
                if ($com_b) {
                    $com_arr[] = $com_b;
                }
                if (!empty($com_arr)) {
                    $com_str = implode(',', $com_arr);
                    $where .= " AND deal.user_id IN ({$com_str}) ";
                } else {
                    $where .= " AND deal.user_id is NULL ";
                }
            }
            // 校验受让人ID
            if (!empty($_POST['t_user_id'])) {
                $t_user_id = intval($_POST['t_user_id']);
                $sql = "SELECT id FROM firstp2p_user WHERE id = {$t_user_id}";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($t_user_id) {
                    $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                    $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验受让人手机号
            if (!empty($_POST['t_mobile'])) {
                $t_mobile = trim($_POST['t_mobile']);
                $t_mobile = GibberishAESUtil::enc($t_mobile, Yii::app()->c->idno_key); // 手机号加密
                $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$t_mobile}'";
                $t_user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if ($t_user_id) {
                    $sql = "SELECT debt_id FROM firstp2p_debt_tender WHERE user_id = {$t_user_id} AND status = 2";
                    $debt_id_res = Yii::app()->phdb->createCommand($sql)->queryColumn();
                    if ($debt_id_res) {
                        $debt_id_res_str = implode(',', $debt_id_res);
                        $where .= " AND debt.id IN ({$debt_id_res_str}) ";
                    } else {
                        $where .= " AND debt.id = -1 ";
                    }
                } else {
                    $where .= " AND debt.id = -1 ";
                }
            }
            // 校验转让完成时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start'].' 00:00:00');
                $where .= " AND debt.successtime >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end'].' 23:59:59');
                $where .= " AND debt.successtime <= {$end} ";
            }
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
            // 查询数据总量
            $sql = "SELECT count(deal.id) AS count 
                    FROM firstp2p_debt AS debt 
                    INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id {$where} ";
            $count = Yii::app()->phdb->createCommand($sql)->queryScalar();
            // 查询数据
            $sql = "SELECT
                    debt.id, debt.user_id, debt.tender_id , debt.borrow_id , debt.money, debt.sold_money, debt.discount, debt.addtime, debt.successtime, debt.status, debt.debt_src, deal.name , debt.serial_number 
                    FROM firstp2p_debt AS debt 
                    INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id {$where} ORDER BY debt.id DESC ";

            $pass       = ($page - 1) * $limit;
            $sql       .= " LIMIT {$pass} , {$limit} ";
            $list       = Yii::app()->phdb->createCommand($sql)->queryAll();


            $status[1] = '转让中';
            $status[2] = '交易成功';
            $status[3] = '交易取消';
            $status[4] = '已过期';
            $status[5] = '待付款';
            $status[6] = '待收款';

            $debt_src[1] = '权益兑换';
            $debt_src[2] = '债转交易';
            $debt_src[3] = '债权划扣';
            $debt_src[4] = '一键下车';

            $da_status[1] = '客服介入待处理';
            $da_status[2] = '判定有效交易';
            $da_status[3] = '判定无效交易';

            foreach ($list as $key => $value) {
                $value['addtime'] = date('Y-m-d H:i:s', $value['addtime']);
                if ($value['successtime'] != 0) {
                    $value['successtime'] = date('Y-m-d H:i:s', $value['successtime']);
                } else {
                    $value['successtime'] = '——';
                }
                $value['money']      = number_format($value['money'], 2, '.', ',');
                $value['sold_money'] = number_format($value['sold_money'], 2, '.', ',');
                $value['status_ch']  = $status[$value['status']];
                $value['debt_src']   = $debt_src[$value['debt_src']];
                $value['number']     = $key+1;
                $value['appeal_status'] = $da_sta;
                $value['deal_type']  = 2;
                $value['da_status']  = $da_status[$da_sta];

                $listInfo[]    = $value;
                $user_id_arr[] = $value['user_id'];
                $debt_id_arr[] = $value['id'];
            }
            $debt_tender = array();
            if ($debt_id_arr) {
                $debt_id_str = implode(',', $debt_id_arr);
                $sql = "SELECT debt_id , user_id FROM firstp2p_debt_tender WHERE debt_id IN ($debt_id_str) ";
                $tender_res = Yii::app()->phdb->createCommand($sql)->queryAll();
                if ($tender_res) {
                    foreach ($tender_res as $key => $value) {
                        $debt_tender[$value['debt_id']] = $value['user_id'];
                        $user_id_arr[] = $value['user_id'];
                    }
                }
            }
            $user_id_str = implode(',', $user_id_arr);
            $user_id_res = array();
            if ($user_id_str) {
                $sql = "SELECT id , real_name , mobile FROM firstp2p_user WHERE id IN ({$user_id_str}) ";
                $user_id_res = Yii::app()->fdb->createCommand($sql)->queryAll();
            }
            foreach ($user_id_res as $key => $value) {
                $temp['real_name'] = $value['real_name'];
                $temp['mobile']    = GibberishAESUtil::dec($value['mobile'], Yii::app()->c->idno_key); // 手机号解密

                $user_id_data[$value['id']] = $temp;
            }
            foreach ($listInfo as $key => $value) {
                $listInfo[$key]['real_name'] = $user_id_data[$value['user_id']]['real_name'];
                $listInfo[$key]['mobile']    = $user_id_data[$value['user_id']]['mobile'];

                if (!empty($debt_tender[$value['id']])) {
                    $listInfo[$key]['t_user_id']   = $debt_tender[$value['id']];
                    $listInfo[$key]['t_real_name'] = $user_id_data[$debt_tender[$value['id']]]['real_name'];
                    $listInfo[$key]['t_mobile']    = $user_id_data[$debt_tender[$value['id']]]['mobile'];
                } else {
                    $listInfo[$key]['t_user_id']   = '——';
                    $listInfo[$key]['t_real_name'] = '——';
                    $listInfo[$key]['t_mobile']    = '——';
                }
            }


            header("Content-type:application/json; charset=utf-8");
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }
        return $this->renderPartial('HyDebtAppealA', array());
    }
}
