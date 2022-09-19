<?php

/**
 * Class FinanceAuditAction
 * pengchanglu
 * 2014年5月20日11:08:57
 */
use core\service\CouponService;
use core\service\UserCouponLevelService;
use core\dao\CouponLogModel;
use core\dao\FinanceQueueModel;
use core\dao\FinanceAuditModel;
use core\dao\CounterModel;

class FinanceAuditAction extends CommonAction
{

    public $level_list = array();

    public function __construct()
    {
        parent::__construct();
        require_once APP_ROOT_PATH . "/system/libs/user.php";
    }

    public function index()
    {
        $is_auth = $this->is_have_action_auth(MODULE_NAME, 'step1');
        $auth_action = array('p' => 'step2', 'r' => 'refuse2');
        if ($is_auth) {
            $auth_action = array('p' => 'step1', 'r' => 'refuse1');
        }
        $this->assign('auth_action', $auth_action);
        set_time_limit(0);
        $_REQUEST['listRows'] = isset($_REQUEST['listRows']) ? intval($_REQUEST['listRows']) : 200;
        $status = intval($_REQUEST['status']);
        $type = intval($_REQUEST['type']);
        $deal_s = intval($_REQUEST['deal_id_s']);
        $deal_e = intval($_REQUEST['deal_id_e']);
        if ($status === 0) {
            unset($_REQUEST['status']);
        }
        if ($type === 0) {
            $_REQUEST['type'] = FinanceAuditModel::TYPE_TRANSFER;
        }

        if ($deal_s && $deal_e) {
            $map[DB_PREFIX . 'finance_audit.deal_load_id'] = array('BETWEEN', "{$deal_s},{$deal_e}");
        } else {
            if ($deal_e) {
                $map[DB_PREFIX . 'finance_audit.deal_load_id'] = $deal_e;
            }
            if ($deal_s) {
                $map[DB_PREFIX . 'finance_audit.deal_load_id'] = $deal_s;
            }
        }
        $apply_time_start = $apply_time_end = 0;
        if (!empty($_REQUEST['apply_time_start'])) {
            $apply_time_start = to_timespan($_REQUEST['apply_time_start']);
            $map['create_time'] = array('egt', $apply_time);
        }
        $into_num = trim($_REQUEST['into_num']);
        if($into_num){
            $map[DB_PREFIX.'finance_audit.into_name'] = userNumToUserName($into_num);
        }
        $out_num = trim($_REQUEST['out_num']);
        if($out_num){
            $map[DB_PREFIX.'finance_audit.out_name'] = userNumToUserName($out_num);
        }
        if (!empty($_REQUEST['apply_time_end'])) {
            $apply_time_end = to_timespan($_REQUEST['apply_time_end']);
            $map['create_time'] = array('between', sprintf('%s,%s', $apply_time_start, $apply_time_end));
        }
        if (!empty($_REQUEST['type'])) {
            if ($_REQUEST['type'] == FinanceAuditModel::TYPE_ENTERPRISE_TRANSFER) {
                $map['type'] = FinanceAuditModel::TYPE_ENTERPRISE_TRANSFER;
            } else {
                $map['type'] = array('in' , [FinanceAuditModel::TYPE_TRANSFER,FinanceAuditModel::TYPE_COUPON]);
            }
        }

        //是否从备份库
        $from_backup = intval($_REQUEST['backup']);
        $this->assign("from_backup", $from_backup);
        if($from_backup) {
            $this->model = DI("FinanceAudit", '', 'firstp2p_deleted', 'slave');
        }
        // 优惠码结算（状态未结算的）不再复核列表中显示
        //$query = '( ('.DB_PREFIX . 'finance_audit.type=2 and '.DB_PREFIX . 'finance_audit.status in (-1,3) ) or
        //       '.DB_PREFIX . 'finance_audit.type in (1,3) )';
        //$map['_string'] = $query;
        $this->assign("default_map", $map);
        parent::index();
    }


    public function enterpriseTransfer()
    {
        $_REQUEST['type'] = FinanceAuditModel::TYPE_ENTERPRISE_TRANSFER;
        $this->index();
    }

    /**
     * A角色审核 第一步
     * @actionlock
     */
    public function step1()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');
        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        foreach ($ids as $k => $v) {
            $id = $v;
//            $id = getRequestInt("id");
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $info = M("FinanceAudit")->where("id = $id")->find();
            if ($info['status'] != 1) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $send_user = M("User")->where("user_name = '$info[into_name]'")->find();
            if (!$send_user) {
                return false;
            }
            $out_user = M("User")->where("user_name = '$info[out_name]'")->find();

            $finance = new FinanceAuditModel();
            $finance_info = $finance->find($id);
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $finance_info->update_time = get_gmtime();
            $finance_info->admin = $adm_session['adm_name'];
            $finance_info->log = date("Y-m-d H:i:s") . " A角色批准:" . $adm_session['adm_name'];
            $finance_info->status = 2;
            //开始事务
            $GLOBALS['db']->startTrans();
            try {
                $rs = $finance_info->save();
                if ($rs == false) {
                    throw new Exception('事务数据更新错误！');
                }
                modify_account(array('lock_money' => $info['money']), $out_user['id'], "转账申请", true, '您的账户向会员' . $send_user['user_name'] . '的账户转入金额' . $info['money'] . '元' . ' ' . $info['info'], false);
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $this->ajaxReturn('参数错误！事务操作', '', 1);
            }
            if ($ajax) {
                continue;
            }
            $this->ajaxReturn('批准成功！', '', 1);
        }
        //foreach
        $this->ajaxReturn('批准成功！', '', 1);
        exit;
    }

    /**
     * B角色审核 第二步 也是审核通过
     * @actionLock
     */
    public function step2()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');
        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        foreach ($ids as $k => $v) {
            $id = $v;
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误11！', '', 1);
            }
            $info = M("FinanceAudit")->where("id = $id")->find();
            if ($info['status'] != 2) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误22！', '', 1);
            }
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            if ($info['admin'] == $adm_session['adm_name']) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('同一个管理员不能进行操作！', '', 1);
            }
            $finance = new FinanceAuditModel();
            $finance_info = $finance->find($id);
//            $finance_info->update_time = get_gmtime();
//            $finance_info->admin = $adm_session['adm_name'];
            $log_info = $info['log'] . "<br>" . date("Y-m-d H:i:s") . " B角色批准:" . $adm_session['adm_name'];
//            $finance_info->log = $log_info;
//            $finance_info->status = 3;
//            $finance_info->save();
            $sql = "UPDATE firstp2p_finance_audit SET `status` = '3', `log` = '{$log_info}', `update_time` = '".get_gmtime()."', `admin` = '".$adm_session[adm_name]."' WHERE id={$id} AND `status` = 2 ";
            //开始事务
            $GLOBALS['db']->startTrans();
            try {
                $rrs = $finance->updateRows($sql);
                if(!$rrs){
                    throw new Exception('禁止重复操作！');
                }
                $re = $this->update_money($info, $ajax);
                if ($re == false) {
                    throw new Exception('事务数据更新错误！');
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $this->ajaxReturn('参数错误！事务操作22', '', 1);
            }
            if ($ajax) {
                continue;
            }
            //转账的返回
            if ($re && $info['type'] == 1) {
                $this->ajaxReturn($re, '', 1);
            }
        }
        //foreach
        $this->ajaxReturn('批准成功！', '', 1);
        exit;
    }

    /**
     * A角色拒绝
     * @actionlock
     */
    public function refuse1()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');
        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        $status = getRequestInt("status");
        foreach ($ids as $k => $v) {
            $id = $v;
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $info = M("FinanceAudit")->where("id = $id")->find();
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            if ($info['status'] != 1) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('没有权限！', '', 1);
            }
            if ($info['admin'] == $adm_session['adm_name']) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('同一个管理员不能进行操作！', '', 1);
            }
            $finance = new FinanceAuditModel();
            $finance_info = $finance->find($id);
            $finance_info->update_time = get_gmtime();
            $finance_info->admin = $adm_session['adm_name'];
            $finance_info->log = date("Y-m-d H:i:s") . " A角色拒绝:" . $adm_session['adm_name'];
            $finance_info->status = -1;
            //开始事务
            $GLOBALS['db']->startTrans();
            try {
                $finance_info->save();
                /*
                $r = $this->Failed($info);
                if ($r == false) {
                    throw new Exception('事务数据更新错误！');
                }
                */
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $this->ajaxReturn('参数错误！事务操作refuse1', '', 1);
            }
            if ($ajax) {
                continue;
            }
            $this->ajaxReturn('操作成功！', '', 1);
        }
        //foreach
        $this->ajaxReturn('操作成功！', '', 1);
        exit;
    }

    /**
     *  B角色拒绝
     * @actionlock
     */
    public function refuse2()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');
        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        foreach ($ids as $k => $v) {
            $id = $v;
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $info = M("FinanceAudit")->where("id = $id")->find();
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            if ($info['status'] != 2) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('没有权限！', '', 1);
            }
            $finance = new FinanceAuditModel();
            $finance_info = $finance->find($id);
            $finance_info->update_time = get_gmtime();
            $finance_info->admin = $adm_session['adm_name'];
            $finance_info->log = date("Y-m-d H:i:s") . " B角色拒绝:" . $adm_session['adm_name'];
            $finance_info->status = -1;
            //开始事务
            $GLOBALS['db']->startTrans();
            try {
                $finance_info->save();
                $r = $this->Failed($info);
                if ($r == false) {
                    throw new Exception('事务数据更新错误！');
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $this->ajaxReturn('参数错误！事务操作refuse2', '', 1);
            }
            if ($ajax) {
                continue;
            }
            $this->ajaxReturn('操作成功！', '', 1);
        }
        //foreach
        $this->ajaxReturn('操作成功！', '', 1);
        exit;
    }

    /**
     * 更新用户资金
     * @param $info
     */
    private function update_money($info, $is_ajax = 0)
    {
        set_time_limit(0);
        //只能同 B角色待审核的状态 才可以继续
        if($info['status'] != 2){
            return false;
        }
        //转账
        if ($info['type'] == FinanceAuditModel::TYPE_TRANSFER || $info['type'] == FinanceAuditModel::TYPE_ENTERPRISE_TRANSFER) {
            $money = $info['money'];
            if (!$money) {
                return false;
                //$this->ajaxReturn('转出金额不能为零！','',0);
            }
            $send_user = M("User")->where("user_name = '$info[into_name]'")->find();
            if (!$send_user) {
                return false;
            }
            $user_name = $info['into_name'];
            $out_user = M("User")->where("user_name = '$info[out_name]'")->find();
            $id = $out_user['id'];
            //解冻
            // TODO finance 后台 转出资金 扣减当前用户余额 | 通过转账接口同步
            $syncRemoteData = array();
            if (bccomp($info['money'], '0.00', 2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'FINANCEAUDIT|' . $info['id'],
                    'payerId' => $out_user['id'],
                    'receiverId' => $send_user['id'],
                    'repaymentAmount' => bcmul($info['money'], 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 5,
                    'batchId' => '',
                );
            }
            modify_account(array('money' => -$money, 'lock_money' => -$money), $id, "转出资金", true, '您的账户向会员' . $user_name . '的账户转入金额' . $money . '元' . ' ' . $info['info']);
            // TODO finance 后台 转入资金  增加被转入用户余额  | 通过转账接口同步
            modify_account(array('money' => $money), $send_user['id'], "转入资金", true, '会员' . $out_user['user_name'] . '的账户向您的账户转入金额' . $money . '元' . ' ' . $info['info']);
            // push 增加外层数组
            $rs = FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
            if ($rs == false) {
                return false;
            }
            if (!$is_ajax) {
                //$this->ajaxReturn("已成功向 $user_name [".$send_user['real_name']."] 转入".$money."元",'',1);
                return "已成功向 $user_name [" . $send_user['real_name'] . "] 转入" . $money . "元";
            }
        }
        //优惠码
        if ($info['type'] == 2) {
            $this->ajaxReturn('已停用，请到优惠码管理结算', '', 1);
            exit('已停用，请到优惠码管理结算');
        }
        return true;
    }
    /**
     * 转账失败
     * @param $info
     */
    protected function Failed($info)
    {
        if ($info['type'] == FinanceAuditModel::TYPE_TRANSFER || $info['type'] == FinanceAuditModel::TYPE_ENTERPRISE_TRANSFER) { //转账或者企业转账
            $send_user = M("User")->where("user_name = '" . $info['out_name'] . "'")->find();
            $money = $info['money'];
            // TODO finance? 后台 转账失败 转出用户账户回款 | 不同步
            modify_account(array('lock_money' => -$money), $send_user['id'], "转账申请失败", true, '您的账户向会员' . $info['into_name'] . '的账户转入金额' . $money . '元' . ' ' . $info['info']);
            return true;
        }
        return true;
    }

    /**
     * 查询相关字段
     */
    protected function form_index_list(&$list)
    {
        foreach ($list as &$item) {
            // 查询优惠券短码
            $item['into_user_names'] = $this->get_username($item['into_name'], $item);
            $item['out_user_names'] = $this->get_username($item['out_name']);
        }
    }

    /**
     * 格式化 输出name
     * @param $user1
     * @param null $row
     * @return string
     */
    private function get_username($user1, $row = null)
    {
        $userCouponLevelService = new UserCouponLevelService();
        if ($row['type'] != 3) {
            if ($level_list == null) {
                $str = '<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $user1 . '">' . $user1 . '</a>';
            } else {
                $str = '投&nbsp;<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $user1 . '">' . $user1 . '</a>';
            }
            if ($row['attach_name']) {
                $userId = M('User')->where("user_name='" . $row['attach_name'] . "'")->getField("id");
                $leve_str = $userCouponLevelService->getGroupAndLevelByUserId($userId);
                $str .= '&nbsp;<br>&nbsp;推&nbsp;<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $row['attach_name'] . '">' . $row['attach_name'] . '</a>（' . $leve_str . '）';
            }
            if ($row['agency_name']) {
                $str .= '&nbsp;<br>&nbsp;机&nbsp;<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $row['agency_name'] . '">' . $row['agency_name'] . '</a>';
            }
            return $str;
        } else {
            $str = '注&nbsp;<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $user1 . '">' . $user1 . '</a>';
            if ($row['attach_name']) {
                $userId = M('User')->where("user_name='" . $row['attach_name'] . "'")->getField("id");
                $leve_str = $userCouponLevelService->getGroupAndLevelByUserId($userId);
                $str .= '&nbsp;<br>&nbsp;推&nbsp;<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $row['attach_name'] . '">' . $row['attach_name'] . '</a>（' . $leve_str . '）';
            }
            if ($row['agency_name']) {
                $str .= '&nbsp;<br>&nbsp;机&nbsp;<a target="_blank" href="/m.php?m=User&a=index&user_name=' . $row['agency_name'] . '">' . $row['agency_name'] . '</a>';
            }
            return $str;
        }
    }

    /**
     * csv数据导入
     */
    public function do_import()
    {
        //文件检查
        if ($_FILES['upfile']['error'] == 4) {
            $this->error("请选择文件！");
            exit;
        }
        if ($_FILES['upfile']['type'] != 'text/csv' && $_FILES['upfile']['type'] != 'application/vnd.ms-excel') {
            $this->error("请上传csv格式的文件！");
            exit;
        }
        set_time_limit(0);
        $max_line_num = 10000;
        ini_set('memory_limit', '2G');
        $file_line_num = count(file($_FILES['upfile']['tmp_name']));
        if ($file_line_num > $max_line_num + 1) {
            $this->error("处理的数据不能超过{$max_line_num}行");
        }

        //读取csv数据
        $row_no = 1;
        $row_head_array = array('转入账户会员', '转入账户姓名', '转出账户会员', '转出账户姓名', '转账金额', '备注');
        $list = array();
        if (($handle = fopen($_FILES['upfile']['tmp_name'], "r")) !== FALSE) {
            while (($row = fgetcsv($handle)) !== FALSE) {
                if ($row_no == 1) { //第一行标题，检查标题行
                    if (count($row) != count($row_head_array)) {
                        $this->error("第一行标题不正确！");
                        exit;
                    }
                    for ($i = 0; $i < count($row_head_array); $i++) {
                        $row[$i] = trim(htmlspecialchars(iconv('GBK', 'UTF-8', $row[$i])));
                        if ($row[$i] != $row_head_array[$i]) {
                            $this->error("第" . ($i + 1) . "列标题不正确！应为'{$row_head_array[$i]}'");
                            exit;
                        }
                    }
                } else { //数据
                    $item = array();
                    $item['type'] = 1; //会员转账
                    $item['into_name'] = trim(htmlspecialchars(iconv('GBK', 'UTF-8', $row[0])));
                    $item['into_realname'] = trim(htmlspecialchars(iconv('GBK', 'UTF-8', $row[1])));
                    $item['out_name'] = trim(htmlspecialchars(iconv('GBK', 'UTF-8', $row[2])));
                    $item['out_realname'] = trim(htmlspecialchars(iconv('GBK', 'UTF-8', $row[3])));
                    $item['money'] = trim(htmlspecialchars(iconv('GBK', 'UTF-8', $row[4])));
                    $item['info'] = trim(htmlspecialchars(iconv('GBK', 'UTF-8', $row[5])));
                    //检查数据
                    if (empty($item['into_name']) || empty($item['out_name']) || empty($item['into_realname']) || empty($item['out_realname'])) {
                        $this->error("第{$row_no}行'{$row_head_array[0]}'或'{$row_head_array[1]}'或'{$row_head_array[2]}'或'{$row_head_array[3]}'不正确！");
                        exit;
                    }
                    if ($item['into_name'] == $item['out_name']) {
                        $this->error("第{$row_no}行'{$row_head_array[0]}'和'{$row_head_array[2]}'不能一样！");
                        exit;
                    }
                    if (!is_numeric($item['money']) || $item['money'] <= 0 || $item['money'] > 99999999.99) {
                        $this->error("第{$row_no}行'{$row_head_array[4]}'不正确！请填写正确数值，不能超过99999999.99");
                        exit;
                    }
                    if (strlen($item['info']) > 450) {
                        $this->error("第{$row_no}行'{$row_head_array[5]}'不正确！不能超过450字节");
                        exit;
                    }
                    $list[] = $item;
                }
                $row_no++;
            }
            fclose($handle);
            @unlink($_FILES['upfile']['tmp_name']);
        }

        if (empty($list)) {
            $this->error("导入数据为空！");
            exit;
        }

        //导入
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $adm_name = $adm_session['adm_name'];
        $adm_id = intval($adm_session['adm_id']);

        $user_model = new \core\dao\UserModel();
        $row_no = 2;
        foreach ($list as $item) {
            //检查用户名
            $out_user = $user_model->getInfoByName($item['out_name'], 'id,user_name,real_name');
            $into_user = $user_model->getInfoByName($item['into_name'], 'id,user_name,real_name');
            if (empty($out_user) || empty($into_user)) {
                $this->error("第{$row_no}行'{$row_head_array[0]}'或'{$row_head_array[2]}'不正确！");
                exit;
            }

            //检查真实姓名
            if ($into_user['real_name'] != $item['into_realname']) {
                $this->error("第{$row_no}行'{$row_head_array[0]}'和'{$row_head_array[1]}'不匹配！");
                exit;
            }
            if ($out_user['real_name'] != $item['out_realname']) {
                $this->error("第{$row_no}行'{$row_head_array[2]}'或'{$row_head_array[3]}'不正确！");
                exit;
            }
            //入记录
            $finance_audit_model = new \core\dao\FinanceAuditModel();
            $finance_audit_model['out_name'] = $item['out_name'];
            $finance_audit_model['into_name'] = $item['into_name'];
            $finance_audit_model['money'] = $item['money'];
            $finance_audit_model['info'] = $item['info'];
            $finance_audit_model['create_time'] = get_gmtime();
            $finance_audit_model['apply_user'] = $adm_name;
            $rs = $finance_audit_model->insert();
            if (empty($rs)) {
                $this->error("导入第{$row_no}行数据失败");
                exit;
            }
            $row_no++;
        }
        if ($rs) {
            $this->success("导入成功，导入数据" . count($list) . "条！");
        } else {
            $this->error("导入失败！");
        }
    }

    /**
     * changlu  2014年7月22日
     * 导出数据
     */
    public function export()
    {
        set_time_limit(0);
        @ini_set('memory_limit', '300M');

        $datatime = date("YmdHis", time());
        $file_name = 'finance_audit_' . $datatime;
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $file_name . '.csv"');
        header('Cache-Control: max-age=0');

        $type = intval($_REQUEST['type']);
        $into_name = $_REQUEST['into_name'];
        $out_name = $_REQUEST['out_name'];
        $apply_user = $_REQUEST['apply_user'];
        $status = $_REQUEST['status'];

        $deal_s = intval($_REQUEST['deal_id_s']);
        $deal_e = intval($_REQUEST['deal_id_e']);

        //是否从备份库
        $db = $GLOBALS['db'];
        $from_backup = intval($_REQUEST['backup']);
        if ($from_backup) {
            $db = $GLOBALS['db']::getInstance('firstp2p_deleted', 'slave');
        }
        if ($type == 2) { //优惠码结算
            $head = array('编号', '审核状态', '审批记录', '申请人', '申请时间', '投资记录ID', '成交时间', '投资人会员名称', '投资金额', '借款标题', '期限', '推荐会员名称', '机构会员名称', '优惠券短码', '投资人返点金额', '投资人返点比例金额', '投资人返点比例', '推荐人返点金额', '推荐人返点比例金额', '推荐人返点比例', '机构返点金额', '机构返点比例金额', '机构返点比例',);
            $sql = 'SELECT f.id as id,f.status as status,f.log as log,apply_user,f.create_time as apply_time,f.deal_load_id as deal_load_id,pay_time,consume_user_id,deal_load_money,project_name,c.deal_id as deal_id,refer_user_id,agency_user_id,short_alias,rebate_amount,rebate_ratio_amount,rebate_ratio,referer_rebate_amount,referer_rebate_ratio_amount,referer_rebate_ratio,agency_rebate_amount,agency_rebate_ratio_amount,agency_rebate_ratio FROM `firstp2p_finance_audit` AS f LEFT JOIN `firstp2p_coupon_log` AS c ON f.`coupon_id` = c.`id` WHERE type = 2 ';
            if ($into_name) {
                $sql .= " AND into_name = '{$into_name}' ";
            }
            if ($out_name) {
                $sql .= " AND out_name = '{$out_name}' ";
            }
            if ($apply_user) {
                $sql .= " AND apply_user = '{$apply_user}' ";
            }
            if ($deal_s && $deal_e) {
                $sql .= " AND f.deal_load_id BETWEEN '{$deal_s}' AND '{$deal_e}' ";
            } else {
                if ($deal_s) {
                    $sql .= " AND f.deal_load_id = '{$deal_s}' ";
                }
                if ($deal_e) {
                    $sql .= " AND f.deal_load_id = '{$deal_e}' ";
                }
            }
            if ($status) {
                $sql .= " AND f.status = '{$status}' ";
            }
            $apply_sql = '';
            $apply_time_start = $apply_time_end = 0;
            if (!empty($_REQUEST['apply_time_start'])) {
                $apply_time_start = to_timespan($_REQUEST['apply_time_start']);
                $apply_sql = " AND creat_time >= '{$apply_time_start}' ";
            }

            if (!empty($_REQUEST['apply_time_end'])) {
                $apply_time_end = to_timespan($_REQUEST['apply_time_end']);
                $apply_sql = " AND create_time BETWEEN '{$apply_time_start}' AND '{$apply_time_end}'";
            }
            if (!empty($apply_sql)) {
                $sql .= $apply_sql;
            }

            //echo $sql;exit;
            $list = $db->getAll($sql);
            $status_arr = array('-1' => '拒绝', '1' => 'a角色待审核', '2' => 'b角色待审核', '3' => '审核通过');
            foreach ($list as $k => $v) {
                $deal_id = $v['deal_id'];
                if ($deal_id) {
                    $deal_info = M('Deal')->where('id=' . $deal_id)->find();
                    $v['project_name'] = $deal_info['name'];
                    $v['deal_id'] = $deal_info['repay_time'];
                }
                $v['status'] = $status_arr[$v['status']];
                $consume_user_id = $v['consume_user_id'];
                if ($consume_user_id) {
                    $consume_user = M("User")->where('id=' . $consume_user_id)->find();
                    $v['consume_user_id'] = $consume_user['user_name'];
                }
                $refer_user_id = $v['refer_user_id'];
                if ($refer_user_id) {
                    $refer_user = M("User")->where('id=' . $refer_user_id)->find();
                    $v['refer_user_id'] = $refer_user['user_name'];
                }
                if ($v['agency_user_id']) {
                    $agency_user = M("User")->where('id=' . $v['agency_user_id'])->find();
                    $v['agency_user_id'] = $agency_user['user_name'];
                }
                if ($v['apply_time']) {
                    $v['apply_time'] = to_date($v['apply_time']);
                }
                if ($v['pay_time']) {
                    $v['pay_time'] = to_date($v['pay_time']);
                }
                $list[$k] = $v;
            }
        } else { //会员转账
            $head = array('转入账户会员', '转出账户会员', '转账金额', '申请人', '申请时间', '备注');
            $sql = "SELECT into_name,out_name,money,apply_user,create_time,info FROM `firstp2p_finance_audit` WHERE TYPE = '{$type}' ";
            if ($into_name) {
                $sql .= " AND into_name = '{$into_name}' ";
            }
            if ($out_name) {
                $sql .= " AND out_name = '{$out_name}' ";
            }
            if ($apply_user) {
                $sql .= " AND apply_user = '{$apply_user}' ";
            }
            if ($status) {
                $sql .= " AND `status` = '{$status}' ";
            }
            $apply_sql = '';
            $apply_time_start = $apply_time_end = 0;
            if (!empty($_REQUEST['apply_time_start'])) {
                $apply_time_start = to_timespan($_REQUEST['apply_time_start']);
                $apply_sql = " AND creat_time >= '{$apply_time_start}' ";
            }

            if (!empty($_REQUEST['apply_time_end'])) {
                $apply_time_end = to_timespan($_REQUEST['apply_time_end']);
                $apply_sql = " AND create_time BETWEEN '{$apply_time_start}' AND '{$apply_time_end}'";
            }
            if (!empty($apply_sql)) {
                $sql .= $apply_sql;
            }

            $list = $db->getAll($sql);
            foreach ($list as $k => $v) {
                $v['create_time'] = to_date($v['create_time']);
                $list[$k] = $v;
            }
        }
        $fp = fopen('php://output', 'a');
        foreach ($head as &$item) {
            $item = iconv("utf-8", "gbk//IGNORE", $item);
        }
        fputcsv($fp, $head);



        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportFinanceAudit',
                'analyze' => $sql
                )
        );




        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        foreach ($list as $arr) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            foreach ($arr as $k => $v) {
                $arr[$k] = iconv("utf-8", "gbk//IGNORE", $v);
            }
            fputcsv($fp, $arr);
        }
        exit;
    }

    /**
     * 修复优惠码结算数据
     */
    public function repair_coupon()
    {
        return true; // 已经修复完成
        $id = intval($_GET['id']);
        $key = 'repairCoupon_key';
        $counter = new CounterModel();
        $now_count = $counter->get($key);
        if ($now_count) {
            exit('已经执行修复程序了！');
        }
        $list = array("55823", "55825", "55826", "55827", "55829", "55830", "55628", "55641", "55645", "55668", "55676", "54119", "53388", "53389", "54759",
            "54778", "56192", "56200", "56201", "56235", "56241", "56248", "56275", "56298", "56322", "56337", "56344", "56347", "56353", "56363",
            "56366", "56373", "56375", "56380", "56402", "56411", "56435", "56438", "56452", "56454", "56464", "56469", "56470", "56610", "56614",
            "56620", "56624", "56625", "56683", "56707", "56773", "56797", "56860", "56870", "57013", "57017", "58564", "57881", "57942", "57968",
            "58046", "58085", "49958", "50009", "50014", "50107", "50118", "50150", "50169", "50192", "50389", "50396", "50408", "50496", "50678",
            "50795", "50825", "50844");

        echo '需要修复' . count($list) . '条</br>';

        foreach ($list as $k => $v) {
            $coupon = new CouponService();
            //开始事务
            $GLOBALS['db']->startTrans();
            try {
                if ($v) {
                    $rs = $coupon->repairCoupon($v);
                    if ($rs == false) {
                        throw new Exception('事务数据更新错误！');
                    }
                }
                $GLOBALS['db']->commit();
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                echo '事务数据更新错误！';
                $now_count = $counter->get($key);
                exit('修复成功！' . $now_count . "条数据！");
                exit;
            }
            $now_count = $counter->incr($key);
        }
        $now_count = $counter->get($key);
        exit('修复成功！' . $now_count . "条数据！");
    }
}
