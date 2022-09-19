<?php

class DebtReductionController extends \iauth\components\IAuthController
{
    public $pageSize = 10;

    /**
     * 债转记录 列表
     * 提供查询字段：
     * @param user_id       int     用户ID
     * @param borrow_id     int     项目ID
     * @param tender_id     int     投资记录ID
     * @param name          string  项目名称
     * @param status        int     转让状态 1-新建转让中，2-转让成功，3-取消转让，4-过期
     * @param mobile        string  用户手机号
     * @param debt_src      int     债转类型 1-权益兑换
     * @param deal_type     int     项目类型[1-尊享 2-普惠供应链]
     * @param limit         int     每页数据显示量 默认10
     * @param page          int     当前页数 默认1
     * @return array
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
            $where = " WHERE deal.is_zdx = 0 AND debt.debt_src = 1 and debt.status=2 ";
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

                // 校验兑换平台
                if (!empty($_POST['platform_no'])  ) {
                    $p_no   = intval($_POST['platform_no']);
                    $where .= " AND debt.platform_no = {$p_no} ";
                }
                // 校验消费专区ID
                if (!empty($_POST['channel_id'])  ) {
                    $c_id   = intval($_POST['channel_id']);
                    $where .= " AND debt.channel_id = {$c_id} ";
                }
                // 校验债权来源
                // if (!empty($_POST['load_src'])) {
                //     $l_src  = intval($_POST['load_src'])-1;
                //     $where .= " AND debt.load_src = {$l_src} ";
                // }
                // 校验借款人名称
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

                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }

                // 校验兑换平台
                if (!empty($_POST['platform_no']) ) {
                    $p_no   = intval($_POST['platform_no']);
                    $where .= " AND debt.platform_no = {$p_no} ";
                }
                // 校验消费专区ID
                if (!empty($_POST['channel_id'])  ) {
                    $c_id   = intval($_POST['channel_id']);
                    $where .= " AND debt.channel_id = {$c_id} ";
                }
                // 校验债权来源
                // if (!empty($_POST['load_src'])) {
                //     $l_src  = intval($_POST['load_src'])-1;
                //     $where .= " AND debt.load_src = {$l_src} ";
                // }
                // 校验借款人名称
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

                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name   = trim($_POST['name']);
                    $where .= " AND deal.name = '{$name}' ";
                }

                // 校验兑换平台
                if (!empty($_POST['platform_no']) ) {
                    $p_no   = intval($_POST['platform_no']);
                    $where .= " AND debt.platform_no = {$p_no} ";
                }
                // 校验消费专区ID
                if (!empty($_POST['channel_id'])  ) {
                    $c_id   = intval($_POST['channel_id']);
                    $where .= " AND debt.channel_id = {$c_id} ";
                }
                // 校验债权来源
                // if (!empty($_POST['load_src'])) {
                //     $l_src  = intval($_POST['load_src'])-1;
                //     $where .= " AND debt.load_src = {$l_src} ";
                // }
                // 校验借款人名称
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
     * 债转记录 列表 导出
     */
    public function actionDebtListExcel()
    {
        set_time_limit(0);
        // 条件筛选
        $where = " WHERE deal.is_zdx = 0 AND debt.debt_src = 1 and debt.status=2 ";
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

            // 校验兑换平台
            if (!empty($_GET['platform_no'])  ) {
                $p_no   = intval($_GET['platform_no']);
                $where .= " AND debt.platform_no = {$p_no} ";
            }
            // 校验消费专区ID
            if (!empty($_GET['channel_id'])  ) {
                $c_id   = intval($_GET['channel_id']);
                $where .= " AND debt.channel_id = {$c_id} ";
            }
            // 校验债权来源
            // if (!empty($_GET['load_src'])) {
            //     $l_src  = intval($_POST['load_src'])-1;
            //     $where .= " AND debt.load_src = {$l_src} ";
            // }
            // 校验借款人名称
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

            // 校验项目名称
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND deal.name = '{$name}' ";
            }

            // 校验兑换平台
            if (!empty($_GET['platform_no'])  ) {
                $p_no   = intval($_GET['platform_no']);
                $where .= " AND debt.platform_no = {$p_no} ";
            }
            // 校验消费专区ID
            if (!empty($_GET['channel_id'])  ) {
                $c_id   = intval($_GET['channel_id']);
                $where .= " AND debt.channel_id = {$c_id} ";
            }
            // 校验债权来源
            // if (!empty($_GET['load_src'])) {
            //     $l_src  = intval($_POST['load_src'])-1;
            //     $where .= " AND debt.load_src = {$l_src} ";
            // }
            // 校验借款人名称
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

            // 校验项目名称
            if (!empty($_GET['name'])) {
                $name   = trim($_GET['name']);
                $where .= " AND deal.name = '{$name}' ";
            }

            // 校验兑换平台
            if (!empty($_GET['platform_no'])  ) {
                $p_no   = intval($_GET['platform_no']);
                $where .= " AND debt.platform_no = {$p_no} ";
            }
            // 校验消费专区ID
            if (!empty($_GET['channel_id']) ) {
                $c_id   = intval($_GET['channel_id']);
                $where .= " AND debt.channel_id = {$c_id} ";
            }
            // 校验债权来源
            // if (!empty($_GET['load_src'])) {
            //     $l_src  = intval($_POST['load_src'])-1;
            //     $where .= " AND debt.load_src = {$l_src} ";
            // }
            // 校验借款人名称
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
        $data  = "债转ID,债转编号,转让人ID,转让人姓名,转让人手机号,借款编号,借款标题,投资记录ID,投资金额,发起债转金额,已转出金额,折扣,兑换平台,债转合同编号,受让人ID,受让人姓名,受让人手机号,发起时间,转让完成时间,债转合同地址\n";
        $data  = iconv('utf-8', 'GBK', $data);
        foreach ($listInfo as $key => $value) {
            $temp  = "{$value['id']},{$value['serial_number']},{$value['user_id']},{$value['real_name']},{$value['mobile']},{$value['borrow_id']},{$value['name']},{$value['tender_id']},{$value['deal_load_money']},{$value['money']},{$value['sold_money']},{$value['discount']},{$value['platform_no']},{$value['contract_number']},{$value['t_user_id']},{$value['t_real_name']},{$value['t_mobile']},{$value['addtime']},{$value['successtime']},{$value['oss_download']}\n";
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
     * 消债数据看板
     */
    public function actionDataKanban(){
        $platform_data = $shop_data = [];

        //债权人数信息
        $change_sql = "SELECT user_id,platform_no from firstp2p_debt_exchange_log where `status` = 2 and debt_src = 1 group by user_id,platform_no ";
        $zx_user_list = Yii::app()->fdb->createCommand($change_sql)->queryAll();
        $ph_user_list = Yii::app()->phdb->createCommand($change_sql)->queryAll();
        $offline_change_sql = "SELECT user_id,platform_no from offline_debt_exchange_log where `status` = 2 and debt_src = 1 group by user_id,platform_no ";
        $offline_user_list = Yii::app()->offlinedb->createCommand($offline_change_sql)->queryAll();
        $platform_user_list = array_merge($zx_user_list, $ph_user_list, $offline_user_list);
        $shop_platform_user = $platform_user = [];
        foreach ($platform_user_list as $value){
            $shop_platform_user[$value['platform_no']][$value['user_id']] =  $value['user_id'];
            $platform_user[$value['user_id']] = $value['user_id'];
        }

        //总览人数
        $platform_data['debt_number_total'] = count($platform_user);

        //债权总金额
        $debt_sql = "SELECT sum(debt_account) as debt_account_t, platform_no from firstp2p_debt_exchange_log where `status` = 2 and debt_src = 1 group by platform_no ";
        $zx_debt = Yii::app()->fdb->createCommand($debt_sql)->queryAll();
        $ph_debt = Yii::app()->phdb->createCommand($debt_sql)->queryAll();
        $offline_debt_sql = "SELECT sum(debt_account) as debt_account_t, platform_no from offline_debt_exchange_log where `status` = 2 and debt_src = 1 group by platform_no ";
        $offline_debt = Yii::app()->offlinedb->createCommand($offline_debt_sql)->queryAll();
        $platform_debt = array_merge($zx_debt, $ph_debt, $offline_debt);
        $platform_data['debt_total'] = 0;
        foreach ($platform_debt as $value){
            $platform_data['debt_total'] = bcadd($platform_data['debt_total'], $value['debt_account_t'], 2);
            $shop_data[$value['platform_no']]['debt_total'] = empty($shop_data[$value['platform_no']]['debt_total']) ? $value['debt_account_t'] : bcadd($shop_data[$value['platform_no']]['debt_total'], $value['debt_account_t'], 2);
        }

        //积分金额
        $debt_integral_amount_sql = "SELECT sum(debt_integral_amount) as debt_integral_amount_t, platform_id from xf_user_shopping_info where `status` = 1 and debt_integral_amount>0 group by platform_id ";
        $debt_integral_amount = Yii::app()->fdb->createCommand($debt_integral_amount_sql)->queryAll();
        $platform_data['order_total'] = 0;
        foreach ($debt_integral_amount as $value){
            $platform_data['order_total'] = bcadd($platform_data['order_total'], $value['debt_integral_amount_t'], 2);
            $shop_data[$value['platform_id']]['order_total'] = empty($shop_data[$value['platform_id']]['order_total']) ? $value['debt_integral_amount_t'] : bcadd($shop_data[$value['platform_id']]['order_total'], $value['debt_integral_amount_t'], 2);
        }

        //积分余额
        $platform_data['residual_integral'] = bcsub($platform_data['debt_total'], $platform_data['order_total'], 2);
        foreach ($shop_data as $key => $val){
            $shop_data[$key]['residual_integral'] = bcsub($val['debt_total'], $val['order_total'], 2);
            $shop_data[$key]['shop_name'] = Yii::app()->fdb->createCommand("SELECT `name` FROM xf_debt_exchange_platform where id={$key} ")->queryRow()['name'];
            $shop_data[$key]['debt_number_total'] = count($shop_platform_user[$key]);
        }


        $debt_data['platform_data'] = $platform_data;
        $debt_data['shop_data'] = $shop_data;
        return $this->renderPartial('dataKanban', $debt_data);
    }


    //二维数组去重
    public function array_unique_fb($array2D) {
        $temp = [];
        foreach ($array2D as $v) {
            $v = join(",", $v); //降维
            $temp[] = $v;
        }
        $temp = array_unique($temp);//去掉重复的字符串
        return $temp;
    }


}
