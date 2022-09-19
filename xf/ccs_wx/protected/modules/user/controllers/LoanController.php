<?php
use iauth\models\AuthAssignment;
class LoanController extends \iauth\components\IAuthController
{
    //不加权限限制的接口
    public function allowActions()
    {
        return array(
            ''
        );
    }
    /**
     * 在途投资记录 列表
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
    public function actionGetLoanList()
    {
        if (!empty($_POST)) {
            // 尊享
            if ($_POST['deal_type'] == 1) {
                // 条件筛选
                $where = "WHERE 1 = 1";
                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where .= " AND dealload.user_id = {$user_id} ";
                }
                //检验黑名单
                if (!empty($_POST['black_status'])) {
                    $black_status = intval($_POST['black_status']);
                    $where .= " AND dealload.black_status = {$black_status} ";
                }
                // 校验项目ID
                if (!empty($_POST['deal_id'])) {
                    $borrow_id = intval($_POST['deal_id']);
                    $where .= " AND dealload.deal_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['load_id'])) {
                    $id = intval($_POST['load_id']);
                    $where .= " AND dealload.id = {$id} ";
                }
                // 校验转让状态
                if (isset($_POST['debt_status']) && $_POST['debt_status'] != 20) {
                    $debt_status = intval($_POST['debt_status']);
                    $where .= " AND dealload.debt_status = {$debt_status} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name = trim($_POST['name']);
                    $dealInfo = Yii::app()->fdb->createCommand("SELECT id FROM firstp2p_deal WHERE name ='{$name}' ")->queryRow();
                    if(!empty($dealInfo)){
                        $where .= " AND dealload.deal_id = {$dealInfo['id']}";
                    }else{
                        $where .= " AND dealload.deal_id = 0";
                    }
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile = trim($_POST['mobile']);
                    $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $userId = Yii::app()->fdb->createCommand("SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ")->queryScalar();
                    if(!empty($userId)){
                        $where .= " AND dealload.user_id = '{$userId}' ";
                    }else{
                        $where .= " AND dealload.user_id = 0 ";
                    }
                }
                // 投资状态检验
                if (!empty($_POST['deal_src'])) {
                    //wait_capital>0 在途
                    $deal_src = intval($_POST['deal_src']);
                    if ($deal_src == 1) {
                        $where .= " AND dealload.wait_capital > 0 ";
                        $deal_src = 1;
                    } elseif ($deal_src == 2) {
                        //wait_capital=0 已结清
                        $where .= " AND dealload.wait_capital = 0 ";
                        $deal_src = 2;
                    }
                }
                // 校验借款人名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $com_a = Yii::app()->fdb->createCommand("SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND u.user_type = 0 AND c.is_effect = 1 AND c.is_delete = 0 ")->queryColumn();
                    $com_b = Yii::app()->fdb->createCommand("SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' AND u.user_type = 1 ")->queryColumn();
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
                        $dealInfo = Yii::app()->fdb->createCommand("select id from firstp2p_deal where user_id IN({$com_str})")->queryAll();
                        if (!empty($dealInfo)) {
                            $dealIds = implode(',', ArrayUtil::array_column($dealInfo, 'id'));
                            $where .= " AND dealload.deal_id IN ({$dealIds}) ";
                        } else {
                            $where .= " AND dealload.deal_id = '' ";
                        }
                    } else {
                        $where .= " AND dealload.deal_id = ''";
                    }
                }
                //是否确权
                if($_POST['is_debt_confirm'] != 2 && isset($_POST['is_debt_confirm'])){
                    $is_debt_confirm = intval($_POST['is_debt_confirm']);
                    $where .= " AND dealload.is_debt_confirm = {$is_debt_confirm}";
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
                //查询数据总量/未确权和已确权/加入和未加入黑名单/债转状态未债转转让中转让完成/
                $type_field_arr = ['deal_type' => $_POST['deal_type'], 'is_debt_confirm' => $_POST['is_debt_confirm'], 'black_status' => $_POST['black_status'], 'debt_status' => $_POST['debt_status'], 'deal_src' => $_POST['deal_src'] ];
                $count = $this->setGetArr($type_field_arr, $_POST, $where);
                if (!empty($count) && $count > 0) {
                    // 查询数据
                    $sql = "select dealload.is_debt_confirm,dealload.black_status,dealload.join_reason,dealload.id,dealload.money,dealload.wait_capital,dealload.user_id,dealload.deal_id,dealload.debt_status,dealload.create_time from firstp2p_deal_load as dealload {$where} order by dealload.id desc ";
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
                    if (!empty($list)) {
                        //真实姓名user
                        $userIds = implode(",", ArrayUtil::array_column($list, "user_id"));
                        $userInfo = Yii::app()->fdb->createCommand("select user.real_name,user.id,user.mobile from firstp2p_user user WHERE id IN({$userIds})")->queryAll();
                        if (!empty($userInfo)) {
                            $real_names = $this->array_column_low($userInfo, 'real_name', 'id');
                            $mobiles = $this->array_column_low($userInfo, 'mobile', 'id');
                        }
                        //项目信息deal
                        $dealIds = implode(",", ArrayUtil::array_column($list, "deal_id"));
                        $dealInfo = Yii::app()->fdb->createCommand("select deal.name,deal.id from firstp2p_deal deal WHERE id IN({$dealIds})")->queryAll();
                        if (!empty($dealInfo)) {
                            $dealNames = $this->array_column_low($dealInfo, 'name', 'id');
                        }
                        //计划回款时间
                        $deal_loan_ids = implode(",", ArrayUtil::array_column($list, "id"));
                        $loanRepayInfo = Yii::app()->fdb->createCommand("select deal_loan_id,id,MAX(time) time from firstp2p_deal_loan_repay WHERE deal_loan_id IN({$deal_loan_ids}) group by deal_loan_id")->queryAll();
                        if (!empty($loanRepayInfo)) {
                            $timeInfo = $this->array_column_low($loanRepayInfo, 'time','deal_loan_id');
                        }
                    }
                    $debt_name = array('0' => "未债转", "1" => "新创建转让中", "15" => "全部转让成功");
                    $debt_confirm_name = ["未确权","已确权"];
                    //获取当前账号所有子权限
                    $authList = \Yii::app()->user->getState('_auth');
                    $edit_status = 0;
                    if (!empty($authList) && strstr($authList,'/user/Loan/EditLoad') || empty($authList)) {
                        $edit_status = 1;
                    }
                    foreach ($list as $key => $value) {
                        $value['deal_type'] = 1;
                        $value['edit_status'] = $edit_status;
                        $value['mobile'] = GibberishAESUtil::dec($mobiles[$value['user_id']], Yii::app()->c->idno_key); // 手机号解密
                        $value['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                        $value['money'] = number_format($value['money'], 2, '.', ',');//投标金额
                        $value['wait_capital'] = number_format($value['wait_capital'], 2, '.', ',');//在途本金
                        $value['status_name'] = $value['wait_capital'] > 0 ? "在途" : "已结清";//投资记录状态
                        $value['black_status_name'] = $value['black_status'] == 2 ? "是" : "否";//黑名单状态
                        $value['debt_name'] = $debt_name[$value['debt_status']];//债转状态
                        $value['name'] = $dealNames[$value['deal_id']];//项目名称
                        $value['real_name'] = $real_names[$value['user_id']];//真实姓名
                        $value['join_reason'] = $value['join_reason'];//加入理由
                        $value['is_debt_confirm'] = $debt_confirm_name[$value['is_debt_confirm']];//是否确权0:未确权1:已确权
                        $value['time'] = date("Y-m-d",($timeInfo[$value['id']] + 8 * 3600));//计划回款时间(默认添加8小时)
                        $listInfo[] = $value;
                    }
                } else {
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 0;
                    $result_data['info']  = '查询成功';
                    echo exit(json_encode($result_data));
                }

            // 普惠
            } else if ($_POST['deal_type'] == 2) {
                // 条件筛选
                $where = "WHERE 1 = 1";
                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where .= " AND dealload.user_id = {$user_id} ";
                }
                //检验黑名单
                if (!empty($_POST['black_status'])) {
                    $black_status = intval($_POST['black_status']);
                    $where .= " AND dealload.black_status = {$black_status} ";
                }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile = trim($_POST['mobile']);
                    $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                    $user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($user_id) {
                        $where .= " AND dealload.user_id = {$user_id} ";
                    } else {
                        $where .= " AND dealload.user_id is NULL ";
                    }
                }
                // 校验项目ID
                if (!empty($_POST['deal_id'])) {
                    $borrow_id = intval($_POST['deal_id']);
                    $where .= " AND dealload.deal_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['load_id'])) {
                    $id = intval($_POST['load_id']);
                    $where .= " AND dealload.id = {$id} ";
                }
                // 校验转让状态
                if (isset($_POST['debt_status']) && $_POST['debt_status'] != 20) {
                    $debt_status = intval($_POST['debt_status']);
                    $where .= " AND dealload.debt_status = {$debt_status} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name = trim($_POST['name']);
                    $dealInfo = Yii::app()->phdb->createCommand("SELECT id FROM firstp2p_deal WHERE name ='{$name}' ")->queryRow();
                    if (!empty($dealInfo)) {
                        $where .= " AND dealload.deal_id = {$dealInfo['id']}";
                    }else{
                        $where .= " AND dealload.deal_id = 0";
                    }

                }
                // 投资状态检验
                if (!empty($_POST['deal_src'])) {
                    //wait_capital>0 在途
                    $deal_src = intval($_POST['deal_src']);
                    if ($deal_src == 1) {
                        $where .= " AND dealload.wait_capital > 0 ";
                        $deal_src = 1;
                    } elseif ($deal_src == 2) {
                        //wait_capital=0 已结清
                        $where .= " AND dealload.wait_capital = 0 ";
                        $deal_src = 2;
                    }
                }
                // 校验借款人名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company}' AND u.user_type = 0 AND c.is_effect = 1 AND c.is_delete = 0 ";
                    $com_a = Yii::app()->fdb->createCommand($sql)->queryColumn();
                    $sql = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company}' AND u.user_type = 1 ";
                    $com_b = Yii::app()->fdb->createCommand($sql)->queryColumn();
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
                        $dealInfo = Yii::app()->fdb->createCommand("select id from firstp2p_deal where user_id IN({$com_str})")->queryAll();
                        if (!empty($dealInfo)) {
                            $dealIds = implode(',', ArrayUtil::array_column($dealInfo, 'id'));
                            $where .= " AND dealload.deal_id IN ({$dealIds}) ";
                        } else {
                            $where .= " AND dealload.deal_id = '' ";
                        }
                    } else {
                        $where .= " AND dealload.deal_id = ''";
                    }
                }
                //是否确权
                if($_POST['is_debt_confirm'] != 2 && isset($_POST['is_debt_confirm'])){
                    $is_debt_confirm = intval($_POST['is_debt_confirm']);
                    $where .= " AND dealload.is_debt_confirm = {$is_debt_confirm}";
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
                //查询数据总量/未确权和已确权/加入和未加入黑名单/债转状态未债转转让中转让完成/
                $type_field_arr = ['deal_type' => $_POST['deal_type'], 'is_debt_confirm' => $_POST['is_debt_confirm'], 'black_status' => $_POST['black_status'], 'debt_status' => $_POST['debt_status'], 'deal_src' => $_POST['deal_src'] ];
                $count = $this->setGetArr($type_field_arr, $_POST, $where);
                if (!empty($count) && $count > 0) {
                    $whereAdd = '';
                    $dealIds = $this->addPhdbWhere();
                    if(!empty($dealIds)){
                        $whereAdd = "and dealload.deal_id in($dealIds)";
                    }
                    $sql = "SELECT
                        dealload.id,dealload.is_debt_confirm,dealload.black_status,dealload.join_reason,dealload.black_status,dealload.debt_status, dealload.user_id, dealload.deal_id AS deal_id, dealload.money, dealload.wait_capital as wait_capital, dealload.create_time
                        FROM firstp2p_deal_load AS dealload  left join firstp2p_deal deal on deal.id = dealload.deal_id {$where} {$whereAdd} ORDER BY dealload.id DESC ";
                    $page_count = ceil($count / $limit);
                    if ($page > $page_count) {
                        $page = $page_count;
                    }
                    if ($page < 1) {
                        $page = 1;
                    }
                    $pass = ($page - 1) * $limit;
                    $sql .= " LIMIT {$pass} , {$limit} ";
                    $list = Yii::app()->phdb->createCommand($sql)->queryAll();
                    if (!empty($list)) {
                        //真实姓名user
                        $userIds = implode(",", ArrayUtil::array_column($list, "user_id"));
                        $userInfo = Yii::app()->fdb->createCommand("select user.real_name,user.id,user.mobile from firstp2p_user user WHERE id IN({$userIds})")->queryAll();
                        if (!empty($userInfo)) {
                            $real_names = $this->array_column_low($userInfo, 'real_name', 'id');
                            $mobiles = $this->array_column_low($userInfo, 'mobile', 'id');
                        }
                        //项目信息deal
                        $dealIds = implode(",", ArrayUtil::array_column($list, "deal_id"));
                        $dealInfo = Yii::app()->phdb->createCommand("select deal.name,deal.id from firstp2p_deal deal WHERE id IN({$dealIds})")->queryAll();
                        if (!empty($dealInfo)) {
                            $dealNames = $this->array_column_low($dealInfo, 'name', 'id');
                        }
                        //计划回款时间
                        $deal_loan_ids = implode(",", ArrayUtil::array_column($list, "id"));
                        $loanRepayInfo = Yii::app()->phdb->createCommand("select deal_loan_id,id,MAX(time) time from firstp2p_deal_loan_repay WHERE deal_loan_id IN({$deal_loan_ids}) group by deal_loan_id")->queryAll();
                        if (!empty($loanRepayInfo)) {
                            $timeInfo = $this->array_column_low($loanRepayInfo, 'time','deal_loan_id');
                        }
                    }
                    $debt_name = array('0' => "未债转", "1" => "新创建转让中", "15" => "全部转让成功");
                    $debt_confirm_name = ["未确权","已确权"];
                    //获取当前账号所有子权限
                    $authList = \Yii::app()->user->getState('_auth');
                    $edit_status = 0;
                    if (!empty($authList) && strstr($authList,'/user/Loan/EditLoad') || empty($authList)) {
                        $edit_status = 1;
                    }
                    foreach ($list as $key => $value) {
                        $value['deal_type'] = 2;
                        $value['edit_status'] = $edit_status;
                        $value['mobile'] = GibberishAESUtil::dec($mobiles[$value['user_id']], Yii::app()->c->idno_key); // 手机号解密
                        $value['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                        $value['money'] = number_format($value['money'], 2, '.', ',');//投标金额
                        $value['wait_capital'] = number_format($value['wait_capital'], 2, '.', ',');//在途本金
                        $value['status_name'] = $value['wait_capital'] > 0 ? "在途" : "已结清";//投资记录状态
                        $value['black_status_name'] = $value['black_status'] == 2 ? "是" : "否";//黑名单状态
                        $value['debt_name'] = $debt_name[$value['debt_status']];//债转状态
                        $value['name'] = $dealNames[$value['deal_id']];//项目名称
                        $value['real_name'] = $real_names[$value['user_id']];//真实姓名
                        $value['join_reason'] = $value['join_reason'];//加入理由
                        $value['is_debt_confirm'] = $debt_confirm_name[$value['is_debt_confirm']];//是否确权0:未确权1:已确权
                        $value['time'] = date("Y-m-d",($timeInfo[$value['id']] + 8 * 3600));//计划回款时间(默认添加8小时)
                        $listInfo[] = $value;
                    }
                } else {
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 0;
                    $result_data['info']  = '查询成功';
                    echo exit(json_encode($result_data));
                }

            // 金融工场
            } else if ($_POST['deal_type'] == 3) {
                // 条件筛选
                $where = "";
                // 校验用户ID
                if (!empty($_POST['user_id'])) {
                    $user_id = intval($_POST['user_id']);
                    $where .= " AND deal_load.user_id = {$user_id} ";
                }
                //检验黑名单
                // if (!empty($_POST['black_status'])) {
                //     $black_status = intval($_POST['black_status']);
                //     $where .= " AND deal_load.black_status = {$black_status} ";
                // }
                // 校验用户手机号
                if (!empty($_POST['mobile'])) {
                    $mobile = trim($_POST['mobile']);
                    $mobile = GibberishAESUtil::enc($mobile, Yii::app()->c->idno_key); // 手机号加密
                    $sql = "SELECT id FROM firstp2p_user WHERE mobile = '{$mobile}' ";
                    $user_id = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    if ($user_id) {
                        $where .= " AND deal_load.user_id = {$user_id} ";
                    } else {
                        $where .= " AND deal_load.user_id is NULL ";
                    }
                }
                // 校验项目ID
                if (!empty($_POST['deal_id'])) {
                    $borrow_id = intval($_POST['deal_id']);
                    $where .= " AND deal_load.deal_id = {$borrow_id} ";
                }
                // 校验投资记录ID
                if (!empty($_POST['load_id'])) {
                    $id = intval($_POST['load_id']);
                    $where .= " AND deal_load.id = {$id} ";
                }
                // 校验转让状态
                if (isset($_POST['debt_status']) && $_POST['debt_status'] != 20) {
                    $debt_status = intval($_POST['debt_status']);
                    $where .= " AND deal_load.debt_status = {$debt_status} ";
                }
                // 校验项目名称
                if (!empty($_POST['name'])) {
                    $name = trim($_POST['name']);
                    $dealInfo = Yii::app()->phdb->createCommand("SELECT id FROM firstp2p_deal WHERE name ='{$name}' ")->queryRow();
                    if (!empty($dealInfo)) {
                        $where .= " AND deal_load.deal_id = {$dealInfo['id']}";
                    }else{
                        $where .= " AND deal_load.deal_id = 0";
                    }
                }
                // 投资状态检验
                if (!empty($_POST['deal_src'])) {
                    //wait_capital>0 在途
                    $deal_src = intval($_POST['deal_src']);
                    if ($deal_src == 1) {
                        $where .= " AND deal_load.wait_capital > 0 ";
                        $deal_src = 1;
                    } elseif ($deal_src == 2) {
                        //wait_capital=0 已结清
                        $where .= " AND deal_load.wait_capital = 0 ";
                        $deal_src = 2;
                    }
                }
                // 校验借款人名称
                if (!empty($_POST['company'])) {
                    $company = trim($_POST['company']);
                    $sql     = "SELECT id FROM firstp2p_user WHERE real_name = '{$company}' ";
                    $com_a   = Yii::app()->fdb->createCommand($sql)->queryScalar();
                    $sql     = "SELECT id FROM ag_yj_company WHERE INS_NAME = '{$company}' AND IS_VALID = 1";
                    $com_b   = Yii::app()->yjdb->createCommand($sql)->queryScalar();
                    $com_arr = array();
                    if ($com_a) {
                        $com_arr[] = $com_a;
                    }
                    if ($com_b) {
                        $com_arr[] = $com_b;
                    }
                    if (!empty($com_arr)) {
                        $com_str = implode(',' , $com_arr);
                        $where .= " AND deal.user_id IN ({$com_str}) ";
                    } else {
                        $where .= " AND deal.user_id is NULL ";
                    }
                }
                //是否确权
                if($_POST['is_debt_confirm'] != 2 && isset($_POST['is_debt_confirm'])){
                    $is_debt_confirm = intval($_POST['is_debt_confirm']);
                    $where .= " AND deal_load.is_debt_confirm = {$is_debt_confirm}";
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
                $sql = "SELECT count(deal_load.id) AS count FROM ag_yj_deal_load AS deal_load INNER JOIN ag_yj_deal AS deal ON deal.id = deal_load.deal_id {$where} ";
                $count = Yii::app()->yjdb->createCommand($sql)->queryScalar();
                if ($count == 0) {
                    header ( "Content-type:application/json; charset=utf-8" );
                    $result_data['data']  = array();
                    $result_data['count'] = 0;
                    $result_data['code']  = 0;
                    $result_data['info']  = '查询成功';
                    echo exit(json_encode($result_data));
                }
                $sql = "SELECT deal_load.id , deal_load.is_debt_confirm , deal_load.debt_status , deal_load.user_id , deal_load.deal_id AS deal_id , deal_load.money , deal_load.wait_capital as wait_capital , deal_load.create_time , deal.name
                        FROM ag_yj_deal_load AS deal_load INNER JOIN ag_yj_deal AS deal ON deal.id = deal_load.deal_id {$where} ORDER BY deal_load.id DESC ";
                $page_count = ceil($count / $limit);
                if ($page > $page_count) {
                    $page = $page_count;
                }
                if ($page < 1) {
                    $page = 1;
                }
                $pass = ($page - 1) * $limit;
                $sql .= " LIMIT {$pass} , {$limit} ";
                $list = Yii::app()->yjdb->createCommand($sql)->queryAll();
                //真实姓名user
                $userIds = implode(",", ArrayUtil::array_column($list, "user_id"));
                $userInfo = Yii::app()->fdb->createCommand("select user.real_name,user.id,user.mobile from firstp2p_user user WHERE id IN({$userIds})")->queryAll();
                if (!empty($userInfo)) {
                    $real_names = $this->array_column_low($userInfo, 'real_name', 'id');
                    $mobiles = $this->array_column_low($userInfo, 'mobile', 'id');
                }
                //计划回款时间
                $deal_loan_ids = implode(",", ArrayUtil::array_column($list, "id"));
                $loanRepayInfo = Yii::app()->yjdb->createCommand("select deal_loan_id,id,MAX(time) time from ag_yj_deal_loan_repay WHERE deal_loan_id IN({$deal_loan_ids}) group by deal_loan_id")->queryAll();
                if (!empty($loanRepayInfo)) {
                    $timeInfo = $this->array_column_low($loanRepayInfo, 'time','deal_loan_id');
                }

                $debt_name = array('0' => "未债转", "1" => "新创建转让中", "15" => "全部转让成功");
                $debt_confirm_name = ["未确权","已确权"];
                //获取当前账号所有子权限
                $authList = \Yii::app()->user->getState('_auth');
                $edit_status = 0;
                if (!empty($authList) && strstr($authList,'/user/Loan/EditLoad') || empty($authList)) {
                    $edit_status = 1;
                }
                foreach ($list as $key => $value) {
                    $value['deal_type'] = 3;
                    $value['edit_status'] = $edit_status;
                    $value['mobile'] = GibberishAESUtil::dec($mobiles[$value['user_id']], Yii::app()->c->idno_key); // 手机号解密
                    $value['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                    $value['money'] = number_format($value['money'], 2, '.', ',');//投标金额
                    $value['wait_capital'] = number_format($value['wait_capital'], 2, '.', ',');//在途本金
                    $value['status_name'] = $value['wait_capital'] > 0 ? "在途" : "已结清";//投资记录状态
                    $value['black_status_name'] = $value['black_status'] == 2 ? "是" : "否";//黑名单状态
                    $value['debt_name'] = $debt_name[$value['debt_status']];//债转状态
                    $value['real_name'] = $real_names[$value['user_id']];//真实姓名
                    $value['join_reason'] = $value['join_reason'];//加入理由
                    $value['is_debt_confirm'] = $debt_confirm_name[$value['is_debt_confirm']];//是否确权0:未确权1:已确权
                    $value['time'] = date("Y-m-d",($timeInfo[$value['id']] + 8 * 3600));//计划回款时间(默认添加8小时)
                    $listInfo[] = $value;
                }
            }
            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('GetLoanList', array());
    }

    /**
     * @param $retArr 需要匹配的数组
     * @param $params 请求的数组
     * @return mixed
     */
    public function setGetArr($retArr, $params, $parawhere){
        if(!is_array($retArr) || !is_array($params)){
           return false;
       }
        unset($params['s'],$params['page']);
        $params = ItzUtil::checkGetArr($params);
        $retArr = ItzUtil::checkGetArr($retArr);
        $retGetArr = $retArr;
        $where = '';
        $redis_key = '';
        $diff = ItzUtil::array_diff_assoc2_deep($params, $retArr);
        $model = $retGetArr['deal_type'] == 1 ? Yii::app()->fdb : Yii::app()->phdb;
        if(empty($diff)){
            //指定的参数进行缓存
            unset($retArr['deal_type']);
            foreach($retArr as $key => $val){
                //投资记录状态在途、已结清
                if($key == 'deal_src'){
                    if($val == 1){
                        $where .= " AND dealload.wait_capital> 0";
                    }elseif($val == 2){
                        $where .= " AND dealload.wait_capital = 0";
                    }
                }elseif($key == "is_debt_confirm"){
                    if($val == 2){
                        $where .= "";
                    }else{
                        //已确权未确权
                        $where .= " AND dealload.is_debt_confirm = $val";
                    }
                }elseif($key == "debt_status"){
                    if($val == 20){
                        $where .= "";
                    }else{
                        $where .= " AND dealload.debt_status = $val";
                    }
                }else{
                    $where .= " AND dealload.".$key."=".$val;
                }
                $redis_key .= $key."_".$val."_";
            }
            $redis_key = $retGetArr['deal_type'].trim($redis_key,"_")."_count";
            $countnum = Yii::app()->rcache->get($redis_key);
            if(!$countnum){
                $time = $where == '' ? 86400 : 3600;
                if($retGetArr['deal_type'] == 1){
                    $sql = "select count(*) from firstp2p_deal_load dealload where 1 = 1 {$where}";
                }else{
                    $whereAdd = '';
                    $dealIds = $this->addPhdbWhere();
                    if(!empty($dealIds)){
                        $whereAdd = "and dealload.deal_id in($dealIds)";
                    }
                    $sql = "select count(*) from firstp2p_deal_load dealload  left join firstp2p_deal deal on deal.id = dealload.deal_id where 1 = 1  {$where} $whereAdd";
                }
                //全部投资记录
                $countnum = $model->createCommand($sql)->queryScalar();
                $redisData = Yii::app()->rcache->set($redis_key,$countnum,$time);
                if(!$redisData){
                    Yii::log("{$redis_key} count set error","error");
                }
            }
        }else{
            //非指定的参数返回count
            if($retGetArr['deal_type'] == 1){
                $sql = "select count(*) from firstp2p_deal_load dealload {$parawhere}";
            }else{
                $whereAdd = '';
                $dealIds = $this->addPhdbWhere();
                if(!empty($dealIds)){
                    $whereAdd = "and dealload.deal_id in($dealIds)";
                }
                $sql = "select count(*) from firstp2p_deal_load dealload  left join firstp2p_deal deal on deal.id = dealload.deal_id {$parawhere} $whereAdd";
            }
            $countnum = $model->createCommand($sql)->queryScalar();
        }
        return $countnum;
    }

    /**
     * 普惠限制可查看的deal_id
     */
    public function addPhdbWhere()
    {
        $dealIds = Yii::app()->rcache->get("ph_deal_ids");
        if(!$dealIds){
            $model = Yii::app()->phdb;
            $sql = "select DISTINCT deal.id deal_id from firstp2p_deal deal  left join firstp2p_deal_agency b on deal.advisory_id = b.id
                left join firstp2p_deal_tag dt on deal.id = dt.deal_id
                where b.name not in('杭州大树网络技术有限公司','北京掌众金融信息服务有限公司') and dt.tag_id not in(42,44)";
            $dealinfo = $model->createCommand($sql)->queryAll();
            if(!empty($dealinfo)){
                $dealIds = implode(",",ItzUtil::array_column($dealinfo,"deal_id"));
                $redisData = Yii::app()->rcache->set("ph_deal_ids",$dealIds,86400 * 15);
                if(!$redisData){
                    Yii::log("ph_deal_ids set error","error");
                }
            }
            return '';
        }
        return $dealIds;
    }
    /**
     * 编辑加入还是取消黑名单
     */
    public function actionEditLoad()
    {
        $load_id = $_REQUEST['loan_id'];
        $dealtype = $_REQUEST['deal_type'];
        $black_status = $_REQUEST['status'];
        $join_reason = $_REQUEST['join_reason'];
        if (!in_array($black_status, [1, 2]) || !is_numeric($black_status) || !is_numeric($load_id)) {
            $this->echoJson([], 1, "数据异常");
        }
        //添加黑名单验证是否填写理由
        if($black_status == 2){
            if(empty($join_reason)){
                $this->echoJson([], 1, "请填写加入黑名单理由");
            }
        }
        if ($dealtype == 1) {
            //尊享库
            $model = Yii::app()->fdb;
        } elseif ($dealtype == 2) {
            //普惠库
            $model = Yii::app()->phdb;
        }
        if (!empty($load_id)) {
            $now = time();
            $join_reason = !empty($join_reason) ? $join_reason : '';
            $dealLoadInfo = $model->createCommand("update firstp2p_deal_load set black_status = {$black_status},update_black_time= '{$now}',join_reason = '{$join_reason}' WHERE id={$load_id}")->execute();
            if (!$dealLoadInfo) {
                $this->echoJson([], 1, "更新失败");
            }
        }
        $this->echoJson([], 0, "操作成功");
    }

    /**
     * 编辑取消黑名单
     * @return string
     * @throws CException
     */
    public function actionBlackEditAdd()
    {

        return $this->renderPartial('BlackEditAdd');
    }

    /**
     * 待还款列表撤销操作
     */
    public function actionSetrevoke()
    {
        $ag_wx_repayment_plan_id = $_POST['id'];
        $task_remark = $_POST['task_remark'];
        if(!is_numeric($ag_wx_repayment_plan_id) || empty($ag_wx_repayment_plan_id)){
            $this->echoJson([], 1, "还款计划ID错误");
        }
        if(empty($task_remark)){
            $this->echoJson([], 1, "请填写撤销原因");
        }
        $model = Yii::app()->fdb;
        $repaymentPlanInfo = $model->createCommand("select * from ag_wx_repayment_plan where id = $ag_wx_repayment_plan_id")->queryRow();
        if(empty($repaymentPlanInfo)){
            $this->echoJson([], 1, "还款计划不存在");
        }
        if($repaymentPlanInfo['status'] != 0){
            $this->echoJson([], 1, "只有待审核状态可以进行撤销");
        }
        $sql = ItzUtil::get_update_db_sql("ag_wx_repayment_plan", ['status' => 5,'task_remark' => $task_remark], "id = {$ag_wx_repayment_plan_id} and status = 0");
        $saveret = $model->createCommand($sql)->execute();
        if (!$saveret) {
            $this->echoJson([], 1, "还款计划更新失败，失败ID：{$ag_wx_repayment_plan_id}");
        }
        $this->echoJson([], 0, "撤销成功");
    }

    /**
     * 债转邮件通知记录 列表
     */
    public function actionEmailNoticeList()
    {
        if (!empty($_POST)) {

            // 条件筛选
            $where = " 1 = 1 ";
            // 校验平台ID
            if (!empty($_POST['platform_id'])) {
                $platform = intval($_POST['platform_id']);
                $where   .= " AND platform_id = {$platform} ";
            }
            // 校验担保方名称
            if (!empty($_POST['agency_name'])) {
                $agency = trim($_POST['agency_name']);
                $where .= " AND agency_name = '{$agency}' ";
            }
            // 校验咨询方名称
            if (!empty($_POST['advisory_name'])) {
                $advisory = trim($_POST['advisory_name']);
                $where   .= " AND advisory_name = '{$advisory}' ";
            }
            // 校验债务方名称
            if (!empty($_POST['company_name'])) {
                $company = trim($_POST['company_name']);
                $where  .= " AND company_name = '{$company}' ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                $sta    = intval($_POST['status']);
                $where .= " AND status = {$sta} ";
            }
            // 校验债转起始时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND debt_start_time >= {$start} ";
            }
            // 校验债转结束时间
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND debt_end_time <= {$end} ";
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
            $sql   = "SELECT count(id) AS count FROM ag_wx_email_notice WHERE {$where} ";
            $count = Yii::app()->fdb->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql  = "SELECT * FROM ag_wx_email_notice WHERE {$where} ORDER BY id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = Yii::app()->fdb->createCommand($sql)->queryAll();

            // 获取当前账号所有子权限
            $authList = \Yii::app()->user->getState('_auth');
            $start_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Loan/StartEmailNotice') || empty($authList)) {
                $start_status = 1;
            }
            $info_status = 0;
            if (!empty($authList) && strstr($authList,'/user/Loan/EmailNoticeInfo') || empty($authList)) {
                $info_status = 1;
            }
            $platform_id[1] = '尊享';
            $platform_id[2] = '普惠';
            $status[1] = '待启动';
            $status[2] = '已启动';
            $status[3] = '发送中';
            $status[4] = '发送完成';
            foreach ($list as $key => $value){
                $value['start_status'] = $start_status;
                $value['info_status']  = $info_status;
                $value['platform_id']  = $platform_id[$value['platform_id']];
                $value['status_name']  = $status[$value['status']];
                if ($value['debt_start_time'] != 0) {
                    $value['debt_start_time'] = date('Y-m-d H:i:s' , $value['debt_start_time']);
                } else {
                    $value['debt_start_time'] = '——';
                }
                if ($value['debt_end_time'] != 0) {
                    $value['debt_end_time'] = date('Y-m-d H:i:s' , $value['debt_end_time']);
                } else {
                    $value['debt_end_time'] = '——';
                }
                if ($value['add_time'] != 0) {
                    $value['add_time'] = date('Y-m-d H:i:s' , $value['add_time']);
                } else {
                    $value['add_time'] = '——';
                }
                if ($value['success_time'] != 0) {
                    $value['success_time'] = date('Y-m-d H:i:s' , $value['success_time']);
                } else {
                    $value['success_time'] = '——';
                }
                $listInfo[] = $value;
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        $add_status = 0;
        if (!empty($authList) && strstr($authList,'/user/Loan/AddEmailNotice') || empty($authList)) {
            $add_status = 1;
        }
        return $this->renderPartial('EmailNoticeList' , array('add_status' => $add_status));
    }

    /**
     * 债转邮件通知记录 新增
     */
    public function actionAddEmailNotice()
    {
        if (!empty($_POST)) {
            if (empty($_POST['platform_id']) || !in_array($_POST['platform_id'], array(1 , 2))) {
                $this->echoJson(array() , 1, '请正确选择所属平台');
            }
            if ($_POST['platform_id'] == 1) {
                $model = Yii::app()->fdb;
            } else if ($_POST['platform_id'] == 2) {
                $model = Yii::app()->phdb;
            }
            if (empty($_POST['agency_name']) && empty($_POST['advisory_name']) && empty($_POST['company_name'])) {
                $this->echoJson(array() , 2, '担保方名称、咨询方名称、债务方名称需要至少填写一项');
            }
            $where = '';
            if (!empty($_POST['agency_name'])) {
                $agency_name = trim($_POST['agency_name']);
                $sql         = "SELECT id FROM firstp2p_deal_agency WHERE type = 1 AND name = '{$agency_name}' AND is_effect = 1";
                $agency_id   = $model->createCommand($sql)->queryScalar();
                if (!$agency_id) {
                    $this->echoJson(array() , 3, '担保方名称输入错误');
                }
                $where      .= " AND deal.agency_id = {$agency_id} ";
            } else {
                $agency_name = '';
                $agency_id  = 0;
            }
            if (!empty($_POST['advisory_name'])) {
                $advisory_name = trim($_POST['advisory_name']);
                $sql           = "SELECT id FROM firstp2p_deal_agency WHERE type = 2 AND name = '{$advisory_name}' AND is_effect = 1";
                $advisory_id   = $model->createCommand($sql)->queryScalar();
                if (!$advisory_id) {
                    $this->echoJson(array() , 4, '咨询方名称输入错误');
                }
                $where        .= " AND deal.advisory_id = {$advisory_id} ";
            } else {
                $advisory_name = '';
                $advisory_id   = 0;
            }
            if (!empty($_POST['company_name'])) {
                $company_name = trim($_POST['company_name']);
                $sql = "SELECT c.user_id FROM firstp2p_user_company AS c INNER JOIN firstp2p_user AS u ON u.id = c.user_id AND c.name = '{$company_name}' AND c.is_effect = 1 AND c.is_delete = 0 AND u.user_type = 0";
                $com_a = Yii::app()->fdb->createCommand($sql)->queryScalar();
                $sql = "SELECT e.user_id FROM firstp2p_enterprise AS e INNER JOIN firstp2p_user AS u ON u.id = e.user_id AND e.company_name = '{$company_name}' AND e.company_purpose = 2 AND u.user_type = 1";
                $com_b = Yii::app()->fdb->createCommand($sql)->queryScalar();
                if (!$com_a && !$com_b) {
                    $this->echoJson(array() , 5, '债务方名称输入错误');
                }
                if ($com_a && $com_b) {
                    if ($com_a != $com_b) {
                        $this->echoJson(array() , 6, '债务方名称查询到的信息不一致');
                    }
                    $user_id = $com_a;
                } else {
                    if ($com_a) {
                        $user_id = $com_a;
                    }
                    if ($com_b) {
                        $user_id = $com_b;
                    }
                }
                $where .= " AND deal.user_id = {$user_id} ";
            } else {
                $company_name = '';
                $user_id      = 0;
            }
            if (!empty($_POST['start_time'])) {
                $debt_start_time = strtotime($_POST['start_time']);
                $where .= " AND debt.successtime >= {$debt_start_time} ";
            } else {
                $debt_start_time = 0;
            }
            if (!empty($_POST['end_time'])) {
                $debt_end_time = strtotime($_POST['end_time']);
                $where .= " AND debt.successtime <= {$debt_end_time} ";
            } else {
                $debt_end_time = 0;
            }
            if ($debt_end_time < $debt_start_time) {
                $this->echoJson(array() , 7, '债转结束时间不可小于债转起始时间');
            }
            $sql = "SELECT debt.id FROM firstp2p_debt AS debt INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id WHERE debt.status = 2 AND debt.is_mail = 0 AND debt.email_notice_id = 0 {$where} ";
            $debt_id_arr = $model->createCommand($sql)->queryColumn();
            $count = count($debt_id_arr);
            if ($count == 0) {
                $this->echoJson(array() , 8, '未查询到未通知的债转信息');
            }
            
            if (empty($_POST['email_address'])) {
                $this->echoJson(array() , 9, '请输入接收邮件邮箱');
            }
            $email = explode(';', $_POST['email_address']);
            foreach ($email as $key => $value) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->echoJson(array() , 10, '邮箱地址输入错误');
                }
            }
            Yii::app()->fdb->beginTransaction();
            $model->beginTransaction();
            $time = time();
            $sql = "INSERT INTO ag_wx_email_notice (platform_id , agency_name , agency_id , advisory_name , advisory_id , company_name , user_id , status , email_address , debt_start_time , debt_end_time , debt_number , add_time) VALUES ({$_POST['platform_id']} , '{$agency_name}' , {$agency_id} , '{$advisory_name}' , {$advisory_id} , '{$company_name}' , {$user_id} , 1 , '{$_POST['email_address']}' , {$debt_start_time} , {$debt_end_time} , {$count} , {$time}) ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            $email_notice_id = Yii::app()->fdb->getLastInsertID();
            
            $debt_id_str = implode(',' , $debt_id_arr);
            $sql = "UPDATE firstp2p_debt SET email_notice_id = {$email_notice_id} WHERE id IN ({$debt_id_str}) ";
            $update = $model->createCommand($sql)->execute();
            if (!$result || !$update) {
                Yii::app()->fdb->rollback();
                $model->rollback();
                $this->echoJson(array() , 11, '新增失败');
            }
            Yii::app()->fdb->commit();
            $model->commit();
            $this->echoJson(array() , 0, '新增成功');
        }

        return $this->renderPartial('AddEmailNotice');
    }

    /**
     * 债转邮件通知记录 启动
     */
    public function actionStartEmailNotice()
    {
        if (!empty($_POST['id'])) {
            if (!is_numeric($_POST['id'])) {
                $this->echoJson(array() , 1, 'ID格式错误');
            }
            // $sql   = "SELECT * FROM ag_wx_email_notice WHERE status IN (2 , 3)";
            // $check = Yii::app()->fdb->createCommand($sql)->queryRow();
            // if ($check) {
            //     $this->echoJson(array() , 2, '已存在发送中的通知记录，请等待其结束后再启动');
            // }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_email_notice WHERE id = {$id}";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                $this->echoJson(array() , 3, 'ID输入错误');
            }
            if ($res['status'] != 1) {
                $this->echoJson(array() , 4, '此通知记录的状态错误');
            }
            if ($res['platform_id'] == 1) {
                $model = Yii::app()->fdb;
            } else if ($res['platform_id'] == 2) {
                $model = Yii::app()->phdb;
            }
            $where = '';
            if ($res['agency_id'] != 0) {
                $where .= " AND deal.agency_id = {$res['agency_id']} ";
            }
            if ($res['advisory_id'] != 0) {
                $where .= " AND deal.advisory_id = {$res['advisory_id']} ";
            }
            if ($res['user_id'] != 0) {
                $where .= " AND deal.user_id = {$res['user_id']} ";
            }
            if ($res['debt_start_time'] != 0) {
                $where .= " AND debt.successtime >= {$res['debt_start_time']} ";
            }
            if ($res['debt_end_time'] != 0) {
                $where .= " AND debt.successtime <= {$res['debt_end_time']} ";
            }
            $sql = "SELECT count(debt.id) AS count FROM firstp2p_debt AS debt INNER JOIN firstp2p_deal AS deal ON debt.borrow_id = deal.id WHERE debt.status = 2 AND debt.is_mail = 0 AND debt.email_notice_id = {$res['id']} {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                $this->echoJson(array() , 5, '未查询到相关的债转信息');
            }
            if ($count != $res['debt_number']) {
                $this->echoJson(array() , 6, '此通知记录的债转总条数错误');
            }
            $time       = time();
            $op_user_id = Yii::app()->user->id;
            $op_user_id = $op_user_id ? $op_user_id : 0 ;
            $op_ip      = Yii::app()->request->userHostAddress;
            $sql = "UPDATE ag_wx_email_notice SET status = 2 , op_user_id = {$op_user_id} , op_ip = '{$op_ip}' , op_time = {$time} WHERE id = {$res['id']} ";
            $result = Yii::app()->fdb->createCommand($sql)->execute();
            if (!$result) {
                $this->echoJson(array() , 8, '启动失败');
            }
            $this->echoJson(array() , 0, '启动成功');
        }
    }

    /**
     * 债转邮件通知记录 详情
     */
    public function actionEmailNoticeInfo()
    {
        if (!empty($_POST['id'])) {

            if (!is_numeric($_POST['id'])) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 1;
                $result_data['info']  = 'ID格式错误';
                echo exit(json_encode($result_data));
            }
            $id  = intval($_POST['id']);
            $sql = "SELECT * FROM ag_wx_email_notice WHERE id = {$id} ";
            $res = Yii::app()->fdb->createCommand($sql)->queryRow();
            if (!$res) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 2;
                $result_data['info']  = 'ID输入错误';
                echo exit(json_encode($result_data));
            }
            if ($res['platform_id'] == 1) {
                $model = Yii::app()->fdb;
                $platform = '尊享';
            } else if ($res['platform_id'] == 2) {
                $model = Yii::app()->phdb;
                $platform = '普惠';
            }
            // 条件筛选
            $where = " email_notice_id = {$id} ";
            // 校验债转ID
            if (!empty($_POST['debt_id'])) {
                $debt_id = intval($_POST['debt_id']);
                $where  .= " AND id = {$debt_id} ";
            }
            // 校验用户ID
            if (!empty($_POST['user_id'])) {
                $user_id = intval($_POST['user_id']);
                $where  .= " AND user_id = {$user_id} ";
            }
            // 校验债转类型
            if (!empty($_POST['debt_src'])) {
                $d_src  = intval($_POST['debt_src']);
                $where .= " AND debt_src = {$d_src} ";
            }
            // 校验状态
            if (!empty($_POST['status'])) {
                $sta    = intval($_POST['status'])-1;
                $where .= " AND is_mail = {$sta} ";
            }
            // 校验债转时间
            if (!empty($_POST['start'])) {
                $start  = strtotime($_POST['start']);
                $where .= " AND successtime >= {$start} ";
            }
            if (!empty($_POST['end'])) {
                $end    = strtotime($_POST['end']);
                $where .= " AND successtime <= {$end} ";
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
            $sql   = "SELECT count(id) AS count FROM firstp2p_debt WHERE {$where} ";
            $count = $model->createCommand($sql)->queryScalar();
            if ($count == 0) {
                header ( "Content-type:application/json; charset=utf-8" );
                $result_data['data']  = array();
                $result_data['count'] = 0;
                $result_data['code']  = 0;
                $result_data['info']  = '查询成功';
                echo exit(json_encode($result_data));
            }
            // 查询数据
            $sql  = "SELECT id , is_mail , user_id , money , debt_src , successtime FROM firstp2p_debt WHERE {$where} ORDER BY id DESC ";
            $pass = ($page - 1) * $limit;
            $sql .= " LIMIT {$pass} , {$limit} ";
            $list = $model->createCommand($sql)->queryAll();

            $status[0] = '未通知';
            $status[1] = '已通知';

            $debt_src[1] = '权益兑换';
            $debt_src[2] = '债转交易';
            $debt_src[3] = '债权划扣';
            $debt_src[4] = '一键下车';
            $debt_src[5] = '一键下车退回';
            $debt_src[6] = '权益兑换退回';
            foreach ($list as $key => $value){
                $value['platform']    = $platform;
                $value['status']      = $status[$value['is_mail']];
                $value['debt_src']    = $debt_src[$value['debt_src']];
                $value['money']       = number_format($value['money'] , 2 , '.' , ',');
                $value['successtime'] = date('Y-m-d H:i:s' , $value['successtime']);

                $listInfo[] = $value;
            }

            header ( "Content-type:application/json; charset=utf-8" );
            $result_data['data']  = $listInfo;
            $result_data['count'] = $count;
            $result_data['code']  = 0;
            $result_data['info']  = '查询成功';
            echo exit(json_encode($result_data));
        }

        return $this->renderPartial('EmailNoticeInfo');
    }
}