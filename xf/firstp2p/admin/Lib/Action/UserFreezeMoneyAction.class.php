<?php

use core\service\UserFreezeMoneyService;
ini_set('display_errors', 1);
error_reporting(E_ALL);
use core\service\MoneyApplyService;

/**
 * 批量冻结/解冻金额
 * 时间用的time()
 * @author Zhang Ruoshi
 */
class UserFreezeMoneyAction extends CommonAction{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        //定义条件
        $map = [];

        if (!empty($_REQUEST['apply_admin_name']))
        {
            $map['apply_admin_name'] = trim($_REQUEST['apply_admin_name']);
        }

        if (!empty($_REQUEST['username']))
        {
            $map['user_name'] = trim($_REQUEST['username']);
        }

        if (isset($_REQUEST['status']) && intval($_REQUEST['status']) >= 0)
        {
            $map['status'] = intval($_REQUEST['status']);
        }
        else
        {
            $_REQUEST['status'] = -1;
        }

        $_REQUEST ['_order'] = 'id';//按时间排序
        $apply_start = $apply_end = 0;
        if (!empty($_REQUEST['apply_start'])) {
            $apply_start = strtotime($_REQUEST['apply_start']);
            $map['create_time'] = array('egt', $apply_start);
        }

        if (!empty($_REQUEST['apply_end'])) {
            $apply_end = strtotime($_REQUEST['apply_end']);
            $map['create_time'] = array('between', sprintf('%s,%s', $apply_start, $apply_end));
        }

        //取列表数据
        $userFreezeMoney= MI ('UserFreezeMoney');
        $this->_list ($userFreezeMoney, $map );
        $status_list =[
            ['status' => 0, 'statusCn' => '待审核'],
            ['status' => 1, 'statusCn' => '审核通过'],
            ['status' => 2, 'statusCn' => '审核未通过'],
            ];

        $this->assign('statusCn',$status_list);
        $this->display ();
    }

    /**
     * 导出数据
     */
    public function export_csv() {
        $status_list = array(
            '0'=>'审核中',
            '1'=>'审核通过',
            '2'=>'审核未通过',
        );

        $cond = ' 1 ';
        if (!empty($_REQUEST['apply_admin_name']))
        {
            $adminname = addslashes(trim($_REQUEST['apply_admin_name']));
            $cond .= " AND apply_admin_name = '{$adminname}' ";
        }

        if (!empty($_REQUEST['username']))
        {
            $username = addslashes(trim($_REQUEST['username']));
            $cond .= " AND user_name = '{$username}' ";
        }

        if (isset($_REQUEST['status']) && intval($_REQUEST['status']) >= 0)
        {
            $status = intval($_REQUEST['status']);
            $cond .= " AND status = '{$status}'";
        }

        $apply_start = $apply_end = 0;
        if (!empty($_REQUEST['apply_start'])) {
            $apply_start = strtotime($_REQUEST['apply_start']);
            $cond .= " AND create_time >= '{$apply_start}' ";
        }

        if (!empty($_REQUEST['apply_end'])) {
            $apply_end = strtotime($_REQUEST['apply_end']);
            $cond .= " AND create_time BETWEEN '{$apply_start}' AND '{$apply_end}' ";
        }


        $list = M('UserFreezeMoney')->where($cond)->order('id desc')->select();

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportuser',
                'analyze' => M('UserFreezeMoney')->getLastSql()
                )
        );



        $title = '操作人,金额,会员名称,姓名,状态,审批记录,申请时间,备注';
        $content = iconv('utf-8', 'gbk', $title) . "\n";
        foreach ($list as $k => $v) {
            $temp = '';
            $userInfo = M("User")->where("id=".intval($v['user_id'])." and is_delete = 0")->find();
            $userInfo['user_name'] = !empty($userInfo['user_name']) ? $userInfo['user_name'] : '没有该会员';
            $row = '';
            $row .= $v['apply_admin_name'];
            $row .= ",\"" . format_price($v['money']) . "\"";
            $row .= ','.$userInfo['user_name'];
            $row .= ','.$userInfo['real_name'];
            $row .= ','.$status_list[$v['status']];
            $row .= ','.$v['memo'];
            $row .= ",\"" . date('Y-m-d H:i:s', $v['create_time']) . "\"";
            $row .= ','.$v['note'];
            $row = strip_tags($row);
            $content .= iconv('utf-8', 'gbk', $row) . "\n";
        }
        $datatime = date("YmdHis", get_gmtime());
        header("Content-Disposition: attachment; filename=moneyapply_log_list_{$datatime}.csv");
        echo $content;
    }

    /**
     * 获取修改金额申请记录
     * @param integer $id
     *
     * @return array
     */
    private function get_apply_list($id){
        $apply_list = M("UserFreezeMoney")->where("parent_id=".$id)->findAll();
        foreach($apply_list as $k=>$v){
            $date_str = to_date($v['time']);
            $adm_name = $v['apply_admin_name'];
            $type_name = $GLOBALS['dict']['MONEY_APPLY_TYPE'][$v['type']];
            $apply_list_str .= $date_str.' '.$type_name.' '.$adm_name.'<br/>';
        }
        return $apply_list_str;
    }

    /**
     * 增加余额修改申请页面
     */
    public function add()
    {
        save_log('进入余额修改申请', 1);
        $user_id = intval($_REQUEST['user_id']);
        $user_info = M("User")->where(array('id'=>$user_id))->find();

        $this->assign("user_info",$user_info);
        $this->display();
    }

    /**
     * 重写 edit
     * (non-PHPdoc)
     * @see CommonAction::edit()
     */
    public function edit()
    {
        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if (empty($id)) {
            $this->error(l("INVALID_OPERATION"));
        }
        $condition[$this->pk_name] = $id;
        $vo = $this->model->where($condition)->find();
        if($vo['status'] != 0){
            $this->error("此状态不允许修改！");
        }
        $user_id = $vo['user_id'];
        $user_info = M("User")->where(array('id'=>$user_id))->find();
        if(empty($user_info)) $this->error(L("USER_MOBILE_FORMAT_TIP"));
        $this->assign("user_info",$user_info);
        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     * 处理增加余额申请
     */
    public function doadd(){
        $money  = floatval($_REQUEST['money']);
        $userNo = addslashes(trim($_REQUEST['userno']));
        if($money == 0) $this->error("请输入金额");
        $userInfo = M("User")->where("id=".de32Tonum($userNo)." and is_delete = 0")->find();
        if(empty($userInfo)) $this->error("用户不存在");
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $error = '';
        do
        {
            if (bccomp($money, '0.00', 2) > 0 && bccomp($money, $userInfo['money'], 2) > 0)
            {
                $error = "{$userName}用户账户可用余额小于冻结金额";
                break;
            }

            if (bccomp($money, '0.00', 2) < 0 && bccomp(bcadd($money, $userInfo['lock_money'], 2), '0.00', 2) < 0)
            {
                $error = "{$userName}用户账户冻结金额小于解冻金额";
                break;
            }
        } while(false);

        if (!empty($error))
        {
            $this->error($error);
            exit;
        }

        $url = u("UserFreezeMoney/index");
        $add_data = array(
            'user_id'=>$userInfo['id'],
            'user_name'=>$userName,
            'money'=>$money,
            'apply_admin_name'=>$adm_session['adm_name'],
            'note'=>getRequestString('note'),
            'create_time' => time(),
        );
        $GLOBALS['db']->startTrans();
        try {
            $GLOBALS['db']->autoExecute('firstp2p_user_freeze_money', $add_data, 'INSERT');
            $affectRows = $GLOBALS['db']->affected_rows();
            if ($affectRows <= 0) {
                throw new \Exception('添加记录失败');
            }
            $log = $adm_session['adm_name']."申请冻结/解冻".$userInfo['user_name']."账户资金".format_price($money);
            $_saveResult = save_log($log,1);
            if (!$_saveResult) {
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
    const CSV_USERNAME = 0;
    const CSV_MONEY = 1;
    const CSV_NOTE = 2;

    /**
     * 批量导入充值申请
     */
    public function import()
    {
        $this->display();
    }

    /**
     * 处理导入充值申请
     */
    public function doimport()
    {
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

        // 行数检查
        if (count($contentArray))
        {
            $err[] = '导入的数据超过1000条';
        }

        foreach ($contentArray as $key => $item) {
            $line = $key + 2;
            $row = explode(',', $item);

            $money = trim($row[self::CSV_MONEY]);
            if ($money == 0 || !preg_match('/^\-?\d{1,10}(\.\d{1,2})?$/', $money)) {
                $error[] = "第{$line}行: 金额{$money}有误";
                continue;
            }

            $username = trim($row[self::CSV_USERNAME]);
            $userInfo = M('User')->where(array('user_name' => $username))->find();
            if (empty($userInfo)) {
                $error[] = "第{$line}行: 用户{$username}不存在";
                continue;
            }

            if (bccomp($money, '0.00', 2) > 0 && bccomp($money, $userInfo['money'], 2) > 0)
            {
                $error[] = "第{$line}行：{$username}用户账户可用余额小于冻结金额";
                continue;
            }

            if (bccomp($money, '0.00', 2) < 0 && bccomp(abs($money), $userInfo['lock_money'], 2) > 0)
            {
                $error[] = "第{$line}行：{$username}用户账户冻结金额小于解冻金额";
                continue;
            }

            $userData[] = array(
                'user_id' => $userInfo['id'],
                'user_name' => $userInfo['user_name'],
                'money' => $money,
                'apply_admin_name' => $adminSession['adm_name'],
                'note' => trim($row[self::CSV_NOTE]),
                'create_time' => time(),
            );
        }

        if (!empty($error)) {
            $this->assign('waitSecond', 600);
            $this->error(implode('<br />', $error));
        }

        //保存到数据库
        $GLOBALS['db']->startTrans();
        try {
            foreach ($userData as $item) {
                $GLOBALS['db']->autoExecute('firstp2p_user_freeze_money', $item, 'INSERT');
                $affectRows = $GLOBALS['db']->affected_rows();
                if ($affectRows <= 0) {
                    throw new \Exception('添加记录失败');
                }

                $log = $adminSession['adm_name'].'通过批量导入申请修改'.$item['user_id'].'账户余额'.format_price($item['money']);
                if (!save_log($log, 1)) {
                    throw new \Exception('保存日志失败');
                }
            }
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error($e->getMessage(), 0, $url);
        }

        $GLOBALS['db']->commit();

        $this->success('批量导入成功', 0, u('UserFreezeMoney/index'));
    }

    /**
     * 批准或拒绝申请
     */
    public function doverify()
    {
        $applyId = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : '';
        $type = isset($_REQUEST['id']) ? intval($_REQUEST['type']) : 0; //1批准，2不批准

        $applyIdArray = explode(',', $applyId);
        $passed = $type === 1 ? true : false;

        $userFreezeMoneyService = new UserFreezeMoneyService();

        //批量处理
        $error = array();
        foreach ($applyIdArray as $id) {
            $result = $userFreezeMoneyService->verifyFreezeMoney($id, $passed);
            if (!$result['res']) {
                $error[] = "Id:{$id}, {$result['msg']}";
            }
        }

        //返回结果
        if (!empty($error)) {
            $this->assign('waitSecond', 600);
            $this->error(implode('<br />', $error));
        }

        $this->success($result['msg']);
    }

    /**
     * 新增申请
     */
    public function addlist(){
        require_once APP_ROOT_PATH."/system/libs/user.php";
        $group_list = M("UserGroup")->findAll();
        $this->assign("group_list",$group_list);
        //定义条件
        $map[DB_PREFIX.'user.is_delete'] = 0;

        if(intval($_REQUEST['group_id'])>0)
        {
            $map[DB_PREFIX.'user.group_id'] = intval($_REQUEST['group_id']);
        }
        if(intval($_REQUEST['user_id'])>0)
        {
            $map[DB_PREFIX.'user.id'] = intval($_REQUEST['user_id']);
        }
        if(trim($_REQUEST['user_name'])!='')
        {
            $map[DB_PREFIX.'user.user_name'] = array('like',trim($_REQUEST['user_name']).'%');
        }
        if(trim($_REQUEST['real_name'])!='')
        {
            $map[DB_PREFIX.'user.real_name'] = array('like',trim($_REQUEST['real_name']).'%');
        }
        if(trim($_REQUEST['email'])!='')
        {
            $map[DB_PREFIX.'user.email'] = array('like',trim($_REQUEST['email']).'%');
        }
        if(trim($_REQUEST['mobile'])!='')
        {
            $map[DB_PREFIX.'user.mobile'] = array('like',trim($_REQUEST['mobile']).'%');
        }
        if(trim($_REQUEST['pid_name'])!='')
        {
            $pid = MI("User")->where("user_name='".trim($_REQUEST['pid_name'])."'")->getField("id");
            $map[DB_PREFIX.'user.pid'] = $pid;
        }
        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }

        if (isset($_REQUEST['canLockNegative'])) {
            setcookie('canLockNegative', 1);
        }

        $model = DI ("user");
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        save_log('进入充值申请页面', 1);
        $this->display ();
    }

    /**
     * 删除申请
     */
    public function delete(){
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (!empty($id)) {
            $GLOBALS['db']->query("DELETE FROM ".DB_PREFIX."money_apply WHERE id IN ({$id})");
            clear_auto_cache("consignee_info");
            save_log('批量删除充值申请', 1, $id);
            $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
        }
        else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
}
?>
