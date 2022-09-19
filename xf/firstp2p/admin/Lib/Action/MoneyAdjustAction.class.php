<?php

use core\dao\MoneyAdjustModel;
use core\dao\FinanceQueueModel;
/**
 * 调账管理列表
 * @author Liao Yebin
 */
class MoneyAdjustAction extends CommonAction{

    private static $money_adjust_type = array(
        '1' => '投资返利',
        '2' => '邀请返利',
        '3' => '机构返利',
        '4' => '平台手续费',
        '5' => '咨询费',
        '6' => '担保费',
        '7' => '支付服务费',
        '8' => '还本',
        '9' => '付息',
        '10' => '提前还款本金',
        '11' => '提前还款利息',
        '12' => '提前还款补偿金',
        '13' => '提现失败',
        '14' => '平台贴息',

    );
    private static $money_adjust_status = array(
        '-1' => '审核未通过',
        '1'  => '待运营审核',
        '2'  => '待财务审核',
        '3'  => '审核通过'
    );
    public function __construct()
    {
        parent::__construct();
        require_once APP_ROOT_PATH . "/system/libs/user.php";
    }

    public function index()
    {
        $is_auth = $this->is_have_action_auth(MODULE_NAME, 'verify1');
        $auth_action = array('p' => 'verify2', 'r' => 'refuse2');
        if ($is_auth) {
            $auth_action = array('p' => 'verify1', 'r' => 'refuse1');
        }
        $this->assign('auth_action', $auth_action);
        //定义条件
        $adjust_start = $adjust_end = 0;
        if (!empty($_REQUEST['adjust_start'])) {
            $adjust_start = to_timespan($_REQUEST['adjust_start']);
            $map['create_time'] = array('egt', $adjust_start);
        }

        if (!empty($_REQUEST['adjust_end'])) {
            $adjust_end = to_timespan($_REQUEST['adjust_end']);
            $map['create_time'] = array('between', sprintf('%s,%s', $adjust_start, $adjust_end));
        }

        $status = intval($_REQUEST['status']);
        $type = intval($_REQUEST['type']);
        if ($status === 0) {
            unset($_REQUEST['status']);
        }
        if ($type === 0) {
            unset($_REQUEST['type']);
        }

        //取列表数据
        $this->assign("default_map", $map);
        $this->assign("money_adjust_type", self::$money_adjust_type);
        $this->assign("money_adjust_status", self::$money_adjust_status);
        parent::index();
    }

    /**
     * 导出数据
     * @userLock
     */
    public function export_csv() {

        $id = $_REQUEST['id'];
        if(!empty($id)) {
            $cond = " id IN({$id})";
            $list = M("MoneyAdjust")->where($cond)->order('id desc')->select();
        } else {
            $map = array();
            $adjust_start = $adjust_end = 0;
            if (!empty($_REQUEST['adjust_start'])) {
                $adjust_start = to_timespan($_REQUEST['adjust_start']);
                $map['create_time'] = array('egt', $adjust_start);
            }

            if (!empty($_REQUEST['adjust_end'])) {
                $adjust_end = to_timespan($_REQUEST['adjust_end']);
                $map['create_time'] = array('between', sprintf('%s,%s', $adjust_start, $adjust_end));
            }

            $map = array_merge($this->_search(), $map);
            if (intval($map['status']) === 0) {
                unset($map['status']);
            }
            if (intval($map['type']) === 0) {
                unset($map['type']);
            }
            $list = M("MoneyAdjust")->where($map)->order('id desc')->findAll();
        }


        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportMoneyAdjust',
                'analyze' => M("MoneyAdjust")->getLastSql()
                )
        );



        $title = '编号,批次号,申请时间,申请人,类型,金额,调减账户会员名,调减账户备注,调增账户会员名,调增账户备注,审核状态,审批记录';
        $content = iconv('utf-8', 'gbk', $title) . "\n";
        foreach ($list as $k => $v) {
            $temp = '';
            $userInfo = M("User")->where("id=".intval($v['user_id'])." and is_delete = 0")->find();
            $userInfo['user_name'] = !empty($userInfo['user_name']) ? $userInfo['user_name'] : '没有该会员';
            $row = '';
            $row .= $v['id'];
            $row .= ','.$v['batch_number'];
            $row .= ",\"" . to_date($v['create_time']) . "\"";
            $row .= ','.$v['apply_user'];
            $row .= ','.self::$money_adjust_type[$v['type']];
            $row .= ",\"" . format_price($v['money']) . "\"";
            $row .= ','.$v['decr_name'];
            $row .= ','.$v['decr_note'];
            $row .= ','.$v['incr_name'];
            $row .= ','.$v['incr_note'];
            $row .= ','.self::$money_adjust_status[$v['status']];
            $row .= ','.str_replace("\n", ';', $v['log']);
            $row = strip_tags($row);
            $content .= iconv('utf-8', 'gbk', $row) . "\n";
        }
        $data_time = date("YmdHis", get_gmtime());
        header("Content-Disposition: attachment; filename=money_adjust_list_{$data_time}.csv");
        echo $content;
    }

    private function checkAdjustAuth($auth_type='add', $ajax = 0){
        $auth_add1 = $this->is_have_action_auth(MODULE_NAME, 'add1');
        $auth_add2 = $this->is_have_action_auth(MODULE_NAME, 'add2');
        $auth_verify1 = $this->is_have_action_auth(MODULE_NAME, 'verify1');
        $auth_verify2 = $this->is_have_action_auth(MODULE_NAME, 'verify2');
        if (($auth_add1 && ($auth_verify1 || $auth_verify2)) || ($auth_add2 && ($auth_verify2 || $auth_verify1))) {
            $this->error("权限错误,同一用户不能同时具有新增和审核权限", $ajax);
        }
        if ($auth_type == 'add') {
            if (!$auth_add1 && !$auth_add2) {
                $this->error("权限不足", $ajax);
            } elseif ($auth_add1 && $auth_add2) {
                $this->error("权限错误,不能同时具有运营新增和财务新增权限", $ajax);
            } elseif ($auth_add1 && !$auth_add2) {
                return 1;
            } elseif (!$auth_add1 && $auth_add2) {
                return 2;
            }
        } elseif ($auth_type == 'verify') {
            if (!$auth_verify1 && !$auth_verify2) {
                $this->error("权限不足", $ajax);
            } elseif ($auth_verify1 && $auth_verify2) {
                $this->error("权限错误,不能同时具有运营审核和财务审核权限", $ajax);
            } elseif ($auth_verify1 && !$auth_verify2) {
                return 1;
            } elseif (!$auth_verify1 && $auth_verify2) {
                return 2;
            }
        }
        return false;
    }
    /**
     * 增加调账申请页面
     */
    public function add()
    {
       $this->checkAdjustAuth('add');
        save_log('进入调账申请', 1);
        $this->assign("money_adjust_type",self::$money_adjust_type);
        $this->display();
    }

    /**
     * 处理增加调账申请
     * @actionLock
     */
    public function doadd(){
        $status = $this->checkAdjustAuth('add');
        $money  = floatval($_REQUEST['money']);
        if ($money == 0 || !preg_match('/^\d{1,10}(\.\d{1,2})?$/', $money)) {
            $this->error("金额{$money}有误");
        }
        $decr_idno = trim($_REQUEST['decr_idno']);
        $decr_note = trim($_REQUEST['decr_note']);
        $incr_idno = trim($_REQUEST['incr_idno']);
        $incr_note = trim($_REQUEST['incr_note']);

        if ($decr_idno == $incr_idno) {
            $this->error("调减用户{$decr_idno}与调增用户{$incr_idno}不能相同");
        }
        $decr_name = userNumToUserName($decr_idno);
        if (empty($decr_name)) {
            $this->error("调减用户{$decr_idno}不存在");
        }
        $incr_name = userNumToUserName($incr_idno);
        if (empty($incr_name)) {
            $this->error("调增用户{$incr_idno}不存在");
        }
        $type = trim($_REQUEST['type']);
        if (!in_array($type, array_keys(self::$money_adjust_type))) {
            $this->error("类型{$type}有误");
        }
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $url = u("MoneyAdjust/index");
        $add_data = array(
            'money' => $money,
            'apply_user' => $adm_session['adm_name'],
            'type' => $type,
            'decr_name' => $decr_name,
            'decr_note' => $decr_note,
            'incr_name' => $incr_name,
            'incr_note' => $incr_note,
            'create_time' => get_gmtime(),
            'status' => $status
        );
        $GLOBALS['db']->startTrans();
        try {
            $GLOBALS['db']->autoExecute('firstp2p_money_adjust', $add_data, 'INSERT');
            $affectRows = $GLOBALS['db']->affected_rows();
            if ($affectRows <= 0) {
                throw new \Exception('添加记录失败');
            }
            $log = $adm_session['adm_name'].'通过批量导入申请调账,调减账户:'.$decr_name.',调增账户:'.$incr_name.',金额'.format_price($money);
            if (!save_log($log, 1)) {
                throw new \Exception('保存日志失败');
            }
            $GLOBALS['db']->commit();
        }
        catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error($e->getMessage(), 0, $url);
        }
        $this->success(L("INSERT_SUCCESS"), 0, $url);
    }

    /**
     * CSV列定义
     */
    const CSV_TYPE = 0;
    const CSV_MONEY = 1;
    const CSV_DECREASE_NAME = 2;
    const CSV_DECREASE_NOTE = 3;
    const CSV_INCREASE_NAME = 4;
    const CSV_INCREASE_NOTE = 5;

    /**
     * 批量导入调账申请
     */
    public function import()
    {
        $this->checkAdjustAuth('add');
        $this->display();
    }

    /**
     * 处理批量导入调账申请
     */
    public function doimport()
    {
        $status = $this->checkAdjustAuth('add');
        $adminSession = es_session::get(md5(conf('AUTH_KEY')));

        //参数检查
        $filename = isset($_FILES['upfile']['name']) ? trim($_FILES['upfile']['name']) : '';
        if (empty($filename)) {
            $this->error('请选择要上传的文件');
        }

        if (strtolower(substr($filename, -4)) !== '.csv') {
            $this->error('只支持上传csv格式的文件');
        }

        $content = file_get_contents($_FILES['upfile']['tmp_name']);
        $content = trim($content);
        if (iconv('gbk', 'utf-8', $content) !== false) {
            $content = iconv('gbk', 'utf-8', $content);
        }

        //解析文件
        $contentArray = explode("\n", $content);
        if (count($contentArray) < 2) {
            $this->error('上传的文件没有内容');
        }

        array_shift($contentArray);

        //检查数据
        $userData = array();
        $error = array();

        foreach ($contentArray as $key => $item) {
            $line = $key + 2;
            $row = explode(',', $item);
            if (count($row) !== 6) {
                $error[] = "第{$line}行: 数据有误";
                continue;
            }

            $money = trim($row[self::CSV_MONEY]);
            if ($money == 0 || !preg_match('/^\d{1,10}(\.\d{1,2})?$/', $money)) {
                $error[] = "第{$line}行: 金额{$money}有误";
                continue;
            }

            $type = trim($row[self::CSV_TYPE]);
            if (!in_array($type, self::$money_adjust_type)) {
                $error[] = "第{$line}行: 类型{$type}有误";
                continue;
            }
            $type = array_flip(self::$money_adjust_type)[$type];

            $decrease_name = trim($row[self::CSV_DECREASE_NAME]);
            $userInfo = M('User')->where(array('user_name' => $decrease_name))->find();
            if (empty($userInfo)) {
                $error[] = "第{$line}行: 用户{$decrease_name}不存在";
                continue;
            }

            $increase_name = trim($row[self::CSV_INCREASE_NAME]);
            $userInfo = M('User')->where(array('user_name' => $increase_name))->find();
            if (empty($userInfo)) {
                $error[] = "第{$line}行: 用户{$increase_name}不存在";
                continue;
            }

            if($decrease_name == $increase_name) {
                $error[] = "第{$line}行: 调减用户{$decrease_name}与调增用户{$increase_name}相同";
                continue;
            }

            $userData[] = array(
                'money' => $money,
                'apply_user' => $adminSession['adm_name'],
                'type' => $type,
                'status' => $status,
                'decr_name' => trim($row[self::CSV_DECREASE_NAME]),
                'decr_note' => trim($row[self::CSV_DECREASE_NOTE]),
                'incr_name' => trim($row[self::CSV_INCREASE_NAME]),
                'incr_note' => trim($row[self::CSV_INCREASE_NOTE]),
                'create_time' => get_gmtime(),
            );
        }

        if (!empty($error)) {
            $this->assign('waitSecond', 600);
            $this->error(implode('<br />', $error));
        }

        //批次号生成
        $date = date('Ymd');
        $incrementId = \SiteApp::init()->dataCache->getRedisInstance()->incr('MONEY_ADJUST_NO_'.$date);
        $batchNo = 'TZ'.$date.sprintf('%03d', $incrementId);

        //保存到数据库
        $GLOBALS['db']->startTrans();
        try {
            foreach ($userData as $item) {
                $item['batch_number'] = $batchNo;
                $GLOBALS['db']->autoExecute('firstp2p_money_adjust', $item, 'INSERT');
                $affectRows = $GLOBALS['db']->affected_rows();
                if ($affectRows <= 0) {
                    throw new \Exception('添加记录失败');
                }

                $log = $adminSession['adm_name'].'通过批量导入申请调账,调减账户:'.$item['decr_name'].',调增账户:'.$item['incr_name'].',金额'.format_price($item['money']);
                if (!save_log($log, 1)) {
                    throw new \Exception('保存日志失败');
                }
            }
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error($e->getMessage());
        }

        $GLOBALS['db']->commit();

        $this->success('批量导入成功', 0, u('MoneyAdjust/index'));
    }

    /**
     * 运营审核
     * @actionlock
     */
    public function verify1()
    {

        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        $this->checkAdjustAuth('verify', $ajax);
        foreach ($ids as $k => $v) {
            $id = $v;
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $info = M("MoneyAdjust")->where("id = $id")->find();
            if ($info['status'] != 1) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $data = array();
            $data['update_time'] = get_gmtime();
            $data['admin'] = $adm_session['adm_name'];
            $data['log'] = date("Y-m-d H:i:s") . " 运营批准:" . $adm_session['adm_name'];
            $data['status'] = 2;
            $rs = M("MoneyAdjust")->where('id=' . $id)->data($data)->save();
            if ($rs) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('批准成功！', '', 1);
            }
        }

        $this->ajaxReturn('操作成功！', '', 1);
    }

    /**
     * 财务审核
     * @actionLock
     */
    public function verify2()
    {

        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        $this->checkAdjustAuth('verify', $ajax);
        foreach ($ids as $k => $v) {
            $id = $v;
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $info = M("MoneyAdjust")->where("id = $id")->find();
            if ($info['status'] != 2) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            if ($info['admin'] == $adm_session['adm_name']) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('同一个管理员不能进行操作！', '', 1);
            }
            $adjust = new MoneyAdjustModel();
            if(empty($info['log'])) {
                $log_info = date("Y-m-d H:i:s") . " 财务批准:" . $adm_session['adm_name'];
            } else {
                $log_info = $info['log'] . "\n" . date("Y-m-d H:i:s") . " 财务批准:" . $adm_session['adm_name'];
            }
            $sql = "UPDATE firstp2p_money_adjust SET `status` = '3', `log` = '{$log_info}', `update_time` = '".get_gmtime()."', `admin` = '".$adm_session[adm_name]."' WHERE id={$id} AND `status` = 2 ";

            //开始事务
            $GLOBALS['db']->startTrans();
            try {
                $rrs = $adjust->updateRows($sql);
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
            if ($re) {
                $this->ajaxReturn($re, '', 1);
            }
        }
        //foreach
        $this->ajaxReturn('批准成功！', '', 1);
    }

    /**
     * 更新用户资金
     */
    private function update_money($info, $is_ajax = 0)
    {
        //只能财务待审核的状态 才可以继续
        if($info['status'] != 2){
            return false;
        }
        //转账
        $money = $info['money'];
        if (!$money) {
            return false;
        }
        $send_user = M("User")->where("user_name = '$info[incr_name]'")->find();
        if (!$send_user) {
            return false;
        }
        $user_name = $info['incr_name'];
        $out_user = M("User")->where("user_name = '$info[decr_name]'")->find();
        $id = $out_user['id'];

        // TODO finance 后台 转出资金 扣减当前用户余额 | 通过转账接口同步
        $syncRemoteData = array();
        if (bccomp($info['money'], '0.00', 2) > 0) {
            $syncRemoteData[] = array(
                'outOrderId' => 'MONEY_ADJUST|' . $info['id'],
                'payerId' => $out_user['id'],
                'receiverId' => $send_user['id'],
                'repaymentAmount' => bcmul($info['money'], 100), // 以分为单位
                'curType' => 'CNY',
                'bizType' => 5,
                'batchId' => '',
            );
        }
        modify_account(array('money' => -$money), $id, self::$money_adjust_type[$info['type']], true, $info['decr_note']);
        // TODO finance 后台 转入资金  增加被转入用户余额  | 通过转账接口同步
        modify_account(array('money' => $money), $send_user['id'], self::$money_adjust_type[$info['type']], true, $info['incr_note']);
        // push 增加外层数组
        $rs = FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer');
        if ($rs == false) {
            return false;
        }
        if (!$is_ajax) {
            return "已成功向 $user_name [" . $send_user['real_name'] . "] 转入" . $money . "元";
        }
        return true;
    }

    /**
     * 拒绝逻辑
     * @actionlock
     */
    private function refuse($operator = '运营')
    {

        $ids = $this->get_id_list();
        $ajax = intval($_REQUEST['ajax']);
        $status = $this->checkAdjustAuth('verify', $ajax);
        foreach ($ids as $k => $v) {
            $id = $v;
            if (!$id) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('参数错误！', '', 1);
            }
            $info = M("MoneyAdjust")->where("id = $id")->find();
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            if ($info['status'] != $status) {
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
            $adm_session = es_session::get(md5(conf("AUTH_KEY")));
            $data = array();
            $data['update_time'] = get_gmtime();
            $data['admin'] = $adm_session['adm_name'];
            if(empty($info['log'])) {
                $data['log'] = date("Y-m-d H:i:s") . " {$operator}拒绝:" . $adm_session['adm_name'];
            }else {
                $data['log'] = $info['log'] . "\n" . date("Y-m-d H:i:s") . " {$operator}拒绝:" . $adm_session['adm_name'];
            }
            $data['status'] = -1;
            $rs = M("MoneyAdjust")->where('id=' . $id)->data($data)->save();
            if ($rs) {
                if ($ajax) {
                    continue;
                }
                $this->ajaxReturn('拒绝成功！', '', 1);
            }
        }
        $this->ajaxReturn('操作成功！', '', 1);
    }

    /**
     *  运营拒绝
     * @actionlock
     */
    public function refuse1()
    {
        $this->refuse('运营');
    }

    /**
     *  财务拒绝
     * @actionlock
     */
    public function refuse2()
    {
        $this->refuse('财务');
    }

    /**
     * 删除调账申请
     * @actionLock
     */
    public function delete(){

        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $status = $this->checkAdjustAuth('add', $ajax);
        $id = $_REQUEST ['id'];
        if (!empty($id)) {
            $list = M("MoneyAdjust")->where(" id IN({$id})")->findAll();
            foreach ($list as $info) {
                $adm_session = es_session::get(md5(conf("AUTH_KEY")));
                if ($info['status'] != $status || $adm_session['adm_name'] != $info['apply_user']) {
                    $this->error('没有权限删除', $ajax);
                }
            }
            $GLOBALS['db']->query("DELETE FROM ".DB_PREFIX."money_adjust WHERE id IN ({$id})");
            clear_auto_cache("consignee_info");
            save_log('批量删除调账申请', 1, $id);
            $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
        }
        else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
}
?>
