<?php

use core\service\MoneyApplyService;

/**
 * 账户余额申请
 * @author Zhang Ruoshi
 */
class MoneyApplyAction extends CommonAction{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        //定义条件
        $map[DB_PREFIX.'money_apply.type'] = 1;
        $_REQUEST ['_order'] = 'time';//按时间排序
        $apply_start = $apply_end = 0;
        if (!empty($_REQUEST['apply_start'])) {
            $apply_start = to_timespan($_REQUEST['apply_start']);
            $map['create_time'] = array('egt', $apply_start);
        }

        if (!empty($_REQUEST['apply_end'])) {
            $apply_end = to_timespan($_REQUEST['apply_end']);
            $map['create_time'] = array('between', sprintf('%s,%s', $apply_start, $apply_end));
        }

        //取列表数据
        $MoneyApply = M ("MoneyApply");
        $this->_list ($MoneyApply, $map );
        $this->display ();
    }

    /**
     * 导出数据
     * @userLock
     */
    public function export_csv() {
        $status_list = array(
            '0'=>'审核中',
            '1'=>'审核未通过',
            '2'=>'审核通过，用户账户已充值',
        );

        $cond = '';
        $apply_start = $apply_end = 0;
        if (!empty($_REQUEST['apply_start'])) {
            $apply_start = to_timespan($_REQUEST['apply_start']);
            $cond = " AND create_time >= '{$apply_start}' ";
        }

        if (!empty($_REQUEST['apply_end'])) {
            $apply_end = to_timespan($_REQUEST['apply_end']);
            $cond = " AND create_time BETWEEN '{$apply_start}' AND '{$apply_end}' ";
        }


        $list = M("MoneyApply")->where('type=1 '.$cond)->order('time desc')->select();

        //记录导出日志
        setLog(
            array(
                'sensitive' => 'exportuser',
                'analyze' => M("MoneyApply")->getLastSql()
                )
        );



        $title = '操作人,金额,会员名称,姓名,状态,审批记录,申请时间,流水单,备注';
        $content = iconv('utf-8', 'gbk', $title) . "\n";
        foreach ($list as $k => $v) {
            $temp = '';
            $userInfo = M("User")->where("id=".intval($v['user_id'])." and is_delete = 0")->find();
            $userInfo['user_name'] = !empty($userInfo['user_name']) ? $userInfo['user_name'] : '没有该会员';
            $row = '';
            $row .= get_admin_name($v['admin_id']);
            $row .= ",\"" . format_price($v['money']) . "\"";
            $row .= ','.$userInfo['user_name'];
            $row .= ','.$userInfo['real_name'];
            //$row .= ",\"" . adminMobileFormat($userInfo['mobile']) . "\"";
            $row .= ','.$status_list[$v['status']];
            $row .= ','.$this->get_apply_list($v['id']);
            $row .= ",\"" . to_date($v['time']) . "\"";
            $row .= ','.$v['orderid'];
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
        $apply_list = M("MoneyApply")->where("parent_id=".$id)->findAll();
        foreach($apply_list as $k=>$v){
            $date_str = to_date($v['time']);
            $adm_name = get_admin_name($v['admin_id']);
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
        if(empty($user_info)) $this->error(L("USER_MOBILE_FORMAT_TIP"));
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
     * @actionLock
     */
    public function doadd(){
        $money  = floatval($_REQUEST['money']);
        $user_id = intval($_REQUEST['user_id']);
        if($money == 0) $this->error("请输入金额");
        $user_info = M("User")->where(array('id'=>$user_id))->find();
        if(empty($user_info)) $this->error("用户不存在");
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $url = u("MoneyApply/index");
        $add_data = array(
            'user_id'=>$user_id,
            'money'=>$money,
            'admin_id'=>$adm_session['adm_id'],
            'type'=>1,
            'time'=>  get_gmtime(),
            'parent_id'=>0,
            'orderid'=>getRequestString('orderid'),
            'note'=>getRequestString('note'),
            'create_time' => get_gmtime(),
        );
        $GLOBALS['db']->startTrans();
        try {
            $GLOBALS['db']->autoExecute('firstp2p_money_apply', $add_data, 'INSERT');
            $affectRows = $GLOBALS['db']->affected_rows();
            if ($affectRows <= 0) {
                throw new \Exception('添加记录失败');
            }
            $log = $adm_session['adm_name']."申请修改".$user_info['name']."账户余额".format_price($money);
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
    const CSV_ORDERID = 2;
    const CSV_NOTE = 3;

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

        foreach ($contentArray as $key => $item) {
            $line = $key + 2;
            $row = explode(',', $item);
            if (count($row) !== 4) {
                $error[] = "第{$line}行: 数据有误";
                continue;
            }

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

            $userData[] = array(
                'user_id' => $userInfo['id'],
                'money' => $money,
                'admin_id' => $adminSession['adm_id'],
                'type' => 1,
                'time' => get_gmtime(),
                'parent_id' => 0,
                'orderid' => trim($row[self::CSV_ORDERID]),
                'note' => trim($row[self::CSV_NOTE]),
                'create_time' => get_gmtime(),
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
                $GLOBALS['db']->autoExecute('firstp2p_money_apply', $item, 'INSERT');
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

        $this->success('批量导入成功', 0, u('MoneyApply/index'));
    }

    /**
     * 批准或拒绝申请
     * @actionLock
     */
    public function doverify()
    {
        $applyId = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : '';
        $type = isset($_REQUEST['id']) ? intval($_REQUEST['type']) : 0; //2批准，3不批准

        $applyIdArray = explode(',', $applyId);
        $passed = $type === 2 ? true : false;

        $moneyApplyService = new MoneyApplyService();

        //批量处理
        $error = array();
        foreach ($applyIdArray as $id) {
            $result = $moneyApplyService->verifyMoneyApply($id, $passed);
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
        $user_num = trim($_REQUEST['user_num']);
        if($user_num){
            $map[DB_PREFIX.'user.id'] = de32Tonum($user_num);
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
            $map[DB_PREFIX.'user.mobile'] = trim($_REQUEST['mobile']);
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
     * @actionLock
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
