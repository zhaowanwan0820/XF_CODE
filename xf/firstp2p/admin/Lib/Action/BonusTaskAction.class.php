<?php

/**
* 红包发送任务Action
*
* @author         WangShiJie<wangshijie.ucfgroup.com>
* @since          1.0
*/
use core\dao\JobsModel;

class BonusTaskAction extends CommonAction {

    const TYPE_DISCOUNT = 3;

    private $use_type = array(
       '0' => '仅限投资',
    );

    private $send_way = array(
        '1' => '用户id',
        '2' => '会员组',
        '3' => '标签（tag）',
        '4' => '导入csv',
        /*'5' => '自定义条件发送',*/
        //'6' => '时间段内注册未投资用户',
        /*'7' => '时间段内未投资的用户'*/
    );

    private $send_title = array(
        '1' => '领用人会员id',
        '2' => '领用人会员组号',
        '3' => '领用人tag',
        '4' => '导入csv',
        /*'5' => '自定义条件发送',*/
        //'6' => '时间段内注册未投资用户',
        /*'7' => '时间段内未投资的用户'*/
    );

    private $source = array(
        '1' => '平台奖励',
        '2' => '活动奖励'
    );

    private $types = array(
        '1' => '红包组',
        '2' => '单个红包'
    );

    private $condition = array(
        '6' => array(
            'reg_time_start' => 1,
            'reg_time_end'   => 1
        ),
        '7' => array(
            'not_deal_time_start' => 1,
            'not_deal_time_end'   => 1
        )

    );


    public function __construct() {
        parent::__construct();
        $this->error('该功能已经下线，请使用新版红包任务进行发送!');
        // $this->assign('use_type', $this->use_type);
        // $this->assign('send_way_list', $this->send_way);
        // $this->assign('types', $this->types);
    }

    //首页
    public function index() {
        $data = $this->queryServiceAudit();
        $list = $data['data_list'];

        $list = $this->formatList($list);
        $this->assign('list', $list);
        $this->assign('discount', $_GET['discount']);
        $this->assign('role', $this->getRole());
        $this->display();
    }

    private function formatList($list, $cutStr = true)
    {
        $static_host = app_conf('STATIC_HOST');
        foreach ($list as $k => &$item) {
            if ($item['send_way'] == 3 || $item['send_way'] == 4) {
                $item['send_condition'] = substr($item['send_condition'], 1);
                if ($item['send_way'] == 4) {
                    $item['send_condition'] = (substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/' . $item['send_condition'];
                }
            } elseif ($item['send_way'] == 5) {
                parse_str($item['send_condition'], $condition);
                $send_condition = array();
                if ($condition['deal_time_start'] && $condition['deal_time_end']) {
                    $send_condition[] = sprintf('投资时间段：%s至%s', to_date($condition['deal_time_start']), to_date($condition['deal_time_end']));
                }
                if ($condition['deal_times_start'] && $condition['deal_times_end']) {
                    $send_condition[] = sprintf('投资次数段：%s至%s', intval($condition['deal_times_start']), intval($condition['deal_times_end']));
                }
                if ($condition['deal_money_start'] && $condition['deal_money_end']) {
                    $send_condition[] = sprintf('投资金额段：%s至%s', intval($condition['deal_money_start']), intval($condition['deal_money_end']));
                }
                $item['send_condition'] = implode("<br/>", $send_condition);
            }
            if (isset($this->condition[$item['send_way']])) {
                parse_str($item['send_condition'], $condition);
                $item['send_condition'] = sprintf('时间段：%s至%s', to_date(array_shift($condition)), to_date(array_shift($condition)));
            }

            if ($cutStr && strlen($item['send_condition']) > 25) {
                if ($item['send_way'] == 4) {
                    list($item['send_condition'], $item['is_include_money']) = explode('&', $item['send_condition']);
                    $item['send_condition'] = sprintf('<a href="%s">%s</a>', $item['send_condition'], mb_strcut($item['send_condition'], 0, 25).'...');
                } else {
                    $item['send_condition'] = mb_strcut($item['send_condition'], 0, 25).'...';
                }
            }


            $record =  M('ServiceAudit')->where('service_id="'.$item['id'].'" AND service_type='.ServiceAuditModel::SERVICE_TYPE_BONUS)->field('submit_uid, audit_uid')->find();
            $item['submit_name'] = $record['submit_uid'] ? get_admin_name($record['submit_uid']) : '';
            $item['audit_name'] = $record['audit_uid'] ? get_admin_name($record['audit_uid']) : '';
            $item['consume_type'] = $this->use_type[$item['consume_type']];
            $item['send_way'] = $this->send_way[$item['send_way']];
            $item['is_sms'] = $item['is_sms'] ? '是' : '否';
            $item['get_gmtime'] = get_gmtime();
        }
        return $list;
    }

    //插入
    public function add(){
        $this->assign('role', $this->getRole());
        $this->assign('use_type', $this->use_type);
        $this->assign('discount', $_GET['discount']);
        $this->display();
    }

    //插入
    public function insert() {
        $form = M(MODULE_NAME);
        $data = $form->create();
        if (!$data) {
            $this->error($form->getError());
        }
        $data['create_time'] = get_gmtime();
        $data['start_time'] = trim($data['start_time']) ? to_timespan($data['start_time']) : 0;
        $data['send_condition'] = $_POST['send_condition'];
        if (!empty($_FILES['send_condition']['tmp_name'])) {
            $uploadFileInfo = array(
                'file' => $_FILES['send_condition'],
                'asAttachment' => 1,
            );
            $result = uploadFile($uploadFileInfo);
            if(empty($result['full_path'])) {
                $this->error('上传失败！');
            } else {
                $_POST['send_condition'] = $result['full_path'];
            }
        }
        if ($data['send_way'] == 3 || $data['send_way'] == 4) {
            $data['send_condition'] = $_POST['send_type'] . $_POST['send_condition'];
            if ($data['send_way'] == 4) {
                $data['send_condition'] .= '&is_include_money='.$_POST['is_include_money'];
                unset($data['is_include_money']);
            }
            unset($data['send_type']);
        }
        if ($data['send_way'] == 5) {
            $condition = array(
                'deal_time_start'  => to_timespan($_POST['deal_time_start']),
                'deal_time_end'    => to_timespan($_POST['deal_time_end']),
                'deal_times_start' => $_POST['deal_times_start'],
                'deal_times_end'   => $_POST['deal_times_end'],
                'deal_money_start' => $_POST['deal_money_start'],
                'deal_money_end'   => $_POST['deal_money_end']
            );
            $data['send_condition'] = http_build_query($condition);
        }
        if (isset($this->condition[$data['send_way']])) {
            $condition = array();
            foreach ($this->condition[$data['send_way']] as $key=>$val) {
                $condition[$key] = ($val == true ) ? to_timespan($_REQUEST[$key]) : $_REQUEST[$key];
            }
            $data['send_condition'] = http_build_query($condition);
        }
        if ($data['start_time'] == 0) {
            $this->error('时间不能为空!');
        }

        $form->startTrans();
        $id = $form->add($data);
        if (!$id) {
            $this->error(L("INSERT_FAILED"));
        }

        $data['id'] = $id;
        $res = $this->saveServiceAudit($data, ServiceAuditModel::OPERATION_ADD);
        if (!$res) {
            $form->rollback();
            $this->error(L("INSERT_FAILED"));
        }
        $form->commit();

        $this->assign("jumpUrl", u(MODULE_NAME . "/index". ($data['type'] == self::TYPE_DISCOUNT ? '&discount=1' : '')));
        $this->success(L("INSERT_SUCCESS"));
    }

    public function edit() {
        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if ($id == '') {
            $this->error(l("INVALID_OPERATION"));
        }

        $serviceAuditModel = D('ServiceAudit');
        $task = $serviceAuditModel->queryTaskByServiceId(array('service_type' => $this->getServiceType(), 'service_id' => $id));
        $task['mark'] = $serviceAuditModel->getLastAuditError($task);

        $vo = $this->model->where(array($this->pk_name => $id))->find();
        if ($vo['send_way'] == 3 || $vo['send_way'] == 4) {
            $vo['send_type'] = substr($vo['send_condition'], 0, 1);
            $vo['send_condition'] = substr($vo['send_condition'], 1);
            if ($vo['send_way'] == 4) {
                list($vo['send_condition'], $vo['is_include_money']) = explode('&', $vo['send_condition']);
                $vo['is_include_money'] = substr(strval($vo['is_include_money']), -1);
                $static_host = app_conf('STATIC_HOST');
                $item['send_condition'] = (substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/' . $item['send_condition'];
            }
        }
        if ($vo['send_way'] == 5 || isset($this->condition[$vo['send_way']])) {
            parse_str($vo['send_condition'], $condition);
            $vo = array_merge($vo, $condition);
        }

        $this->assign('task', $task);
        $this->assign('role', $this->getRole());
        $this->assign('send_title', $this->send_title[$vo['send_way']]);
        $this->assign('vo', $vo);
        $this->assign('readonly', $_GET['readonly'] ? true : false);
        $this->assign('discount', $_GET['discount']);
        $this->display('edit');
    }

    //编辑
    public function update(){
        //B('FilterString');
        $form = D(MODULE_NAME);

        // 字段校验
        $data = $form->create();
        if (!$data) {
            $this->error($form->getError());
        }

        $vo = $this->model->where(array($this->pk_name => $data['id']))->find();
        if ($vo['start_time'] < get_gmtime()) {
            $this->error("任务已经执行，不能再编辑");
        }

        $serviceAuditModel = D('ServiceAudit');
        $task = $serviceAuditModel->queryTaskByServiceId(array('service_type' => $this->getServiceType(), 'service_id' => $data['id']));
        if (!$serviceAuditModel->isTaskCanEdit($task)) {
            $this->error("待审数据和已审核数据不能被编辑");
        }

        $data['send_condition'] = $_POST['send_condition'];
        if ($data['send_way'] == 4) {
            if (!empty($_FILES['send_condition']['tmp_name'])) {
                $uploadFileInfo = array(
                    'file' => $_FILES['send_condition'],
                    'asAttachment' => 1,
                );
                $result = uploadFile($uploadFileInfo);
                if(empty($result['full_path'])) {
                    $this->error('上传失败！');
                } else {
                    $_POST['send_condition'] = $result['full_path'];
                }
            } else {
                unset($_POST['send_condition'], $data['send_condition']);
            }
        }

        if ($data['send_way'] == 3 || ($data['send_way'] == 4 && $_POST['send_condition'] != '')) {
            $data['send_condition'] = $_POST['send_type'] . $_POST['send_condition'];
            if ($data['send_way'] == 4) {
                $data['send_condition'] .= '&is_include_money='.$_POST['is_include_money'];
            }
            unset($data['send_type']);
        }
        if ($data['send_way'] == 5) {//自定义条件发送
            $condition = array(
                'deal_time_start'  => to_timespan($_POST['deal_time_start']),
                'deal_time_end'    => to_timespan($_POST['deal_time_end']),
                'deal_times_start' => $_POST['deal_times_start'],
                'deal_times_end'   => $_POST['deal_times_end'],
                'deal_money_start' => $_POST['deal_money_start'],
                'deal_money_end'   => $_POST['deal_money_end']
            );
            $data['send_condition'] = http_build_query($condition);
        }
        if (isset($this->condition[$data['send_way']])) {
            $condition = array();
            foreach ($this->condition[$data['send_way']] as $key=>$val) {
                $condition[$key] = ($val == true ) ? to_timespan($_REQUEST[$key]) : $_REQUEST[$key];
            }
            $data['send_condition'] = http_build_query($condition);
        }
        $data['update_time'] = get_gmtime();
        $data['start_time'] = to_timespan(trim($data['start_time']));

        $form->startTrans();
        $result = $form->save($data);
        if ($result === false) {
            save_log($log_info . L("UPDATE_FAILED"), 0);//错误提示
            $this->error(L("UPDATE_FAILED"));
        }

        $res = $this->saveServiceAudit($data, ServiceAuditModel::OPERATION_SAVE);
        if (!$res) {
            $form->rollback();
            $this->error(L("UPDATE_FAILED"));
        }
        $form->commit();

        \SiteApp::init()->cache->get('task_bonus_item_'.$data['id']);//清除缓存
        save_log($log_info . L("UPDATE_SUCCESS"), 1); //成功提示

        $this->assign("jumpUrl", u(MODULE_NAME . "/index", array('role' => $this->getRole(), 'discount' => $_REQUEST['discount'])));
        $this->success(L("UPDATE_SUCCESS"));
    }

    //导出手机号
    public function mobile_csv(){
        $id = intval($_GET['id']);
        if($id <= 0){
            $this->error('error');
        }

        ini_set('memory_limit', '1024M');

        $bonus_model = new \core\dao\BonusModel();
        $list = $bonus_model->findAll("task_id='$id'", true, 'mobile');
        $task = M('BonusTask')->find($id);
        if (empty($task)) {
            $this->error("不存在的任务。");
        }
        if ($task['type'] == 1) {
            $this->error('红包组不支持导出手机号。');
        }

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename={$task['name']}.csv");
        header('Cache-Control: max-age=0');

        $fp = fopen('php://output', 'a');
        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        foreach ($list as $user) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            fputcsv($fp, array($user['mobile']));
        }
    }

    /**
     * 导出列表CSV文件
     */
    public function export_csv()
    {
        ini_set('memory_limit', '1024M');

        $data = $this->queryServiceAudit();
        $list = $data['data_list'];

        $list = $this->formatList($list, false);

        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if (!empty($id)) {
            $ids = explode(',', $id);
            $list = array_filter($list, function($item) use ($ids) {
                return in_array($item['id'], $ids);
            });
        }

        $list = $this->formatCSV($list);

        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=红包任务列表.csv");
        header('Cache-Control: max-age=0');

        $fp = fopen('php://output', 'a');
        $count = 1; // 计数器
        $limit = 10000; // 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小

        foreach ($list as $line) {
            $count++;
            if ($count % $limit == 0) { //刷新一下输出buffer，防止由于数据过多造成问题
                ob_flush();
                flush();
                $count = 0;
            }
            fputcsv($fp, $line);
        }

    }

    private function formatCSV($list)
    {
        $all_audit_status = ServiceAuditModel::$auditStatus;
        $taskList = $this->get('task_list');

        $csvTitle = array('编号', '规则名', '使用限制', '红包个数/人次',
            '红包金额/个', '红包来源[id]', '是否发送短信', '红包发送方式',
            '领用人信息', '红包使用有效期', '发送开始时间', '连续发送天数',
            '规则添加时间', '有效状态', '操作人', '审核人', '审核状态',);

        $formatList = array($csvTitle);
        foreach ($list as $item) {
            $record =  M('ServiceAudit')->where('service_id="'.$item['id'].'" AND service_type='.ServiceAuditModel::SERVICE_TYPE_BONUS)->field('submit_uid, audit_uid')->find();
            $formatList[] = array(
                $item['id'], $item['name'], $item['consume_type'], $item['times'],
                $item['money'], $this->source[$item['source']] . "[{$item['source']}]",
                $item['is_sms'], $item['send_way'],
                $item['send_condition'], $item['use_limit_day'],
                date('Y-m-d H:i:s', $item['start_time']),
                $item['continue_times'],
                date('Y-m-d H:i:s', $item['create_time']),
                $item['is_effect'] == 1 ? '有效' : '无效',
                $item['submit_name'] = $record['submit_uid'] ? get_admin_name($record['submit_uid']) : '',
                $item['audit_name'] = $record['audit_uid'] ? get_admin_name($record['audit_uid']) : '',
                $all_audit_status[$taskList[$item['id']]['status']],
            );
        }
        array_walk_recursive($formatList, function(&$item) {
            $item = mb_convert_encoding($item, 'gbk', 'utf8');
        });
        return $formatList;
    }

    /**
     * 输出模板文件
     */
    public function download() {
        Header("Location: static/admin/Common/bonus_uid_or_mobile_template.csv");
        exit();
    }

    /**
     * 置为无效
     */
    public function disable() {
        $id = intval($_GET['id']);
        $taskInfo = M(MODULE_NAME)->where(array('id' => $id))->find();
        if ($taskInfo['status'] == 2) {
            $this->error("已经完成的任务不可置为无效！");
        }
        if ($taskInfo['start_time'] <= get_gmtime() && $taskInfo['is_effect'] == 1) {
            $this->error("已经开始的任务不可置为无效！");
        }
        $res  = M(MODULE_NAME)->where(array('id' => $id))->save(array('is_effect' => 0));

        $succ = $res ? 1 : 0;
        save_log("红包置为无效", $succ);

        $this->ajaxReturn($res, null, $succ);
    }

    /**
     * ab角色中获取服务类型
     *
     * @return int 业务类型
     */
    public function getServiceType() {
        return ServiceAuditModel::SERVICE_TYPE_BONUS;
    }

    /**
     * 组织数据保存到ab角的任务表中
     *
     * @param $operation sting 操作类型，新增和更新， 见ServiceAuditModel表的OPERATION_ADD和OPERATION_SAVE
     * @param $data array 要保存的任务数据，格式 array('id' => 1, 'name' => '', 'start_time' => '')
     *
     * @return bool 操作成功还是失败
     */
    public function saveServiceAudit($data, $operation) {
        $adm = es_session::get(md5(conf("AUTH_KEY")));
        $param = array(
           'service_type' => $this->getServiceType(),
           'service_id'   => $data['id'],
           'standby_1'    => $data['name'],
           'standby_2'    => $data['start_time'],
           'status'       => ServiceAuditModel::NOT_AUDIT,
           'submit_uid'   => intval($adm['adm_id']),
        );
        return D('ServiceAudit')->opServiceAudit($param, $operation);
    }

    /**
     *  ab角色中获取首页的查询语句
     *
     *  @return mix 返回string 或者 array
     */
    public function getWhereStmt() {
        if ($this->getRole() == 'b' && !isset($_GET['audit_status'])) {
            $_REQUEST['audit_status'] = $_GET['audit_status'] = 1;
        }

        $conds = array('service_type' => $this->getServiceType());

        $auditStatus = intval($_GET['audit_status']);
        if ($auditStatus > 0) {
            $conds['status'] = $auditStatus;
        }

        $rangeStart = trim($_GET['range_start']);
        if (!empty($rangeStart)) {
            $conds['standby_2'][] = array('egt', to_timespan($rangeStart));
        }

        $rangeEnd = trim($_GET['range_end']);
        if (!empty($rangeEnd)) {
            $conds['standby_2'][] = array('elt', to_timespan($rangeEnd));
        }

        $submitName = trim($_GET['submit_name']);
        if (!empty($submitName)) {
            $submitUid = M('Admin')->where('adm_name="'.addslashes($submitName).'"')->getField('id');
            if ($submitUid > 0) {
                $conds['submit_uid'] = $submitUid;
            } else {
                $conds['submit_uid'] = -1;
            }
        }

        $auditName = trim($_GET['audit_name']);
        if (!empty($auditName)) {
            $auditUid = M('Admin')->where('adm_name="'.addslashes($auditName).'"')->getField('id');
            if ($auditUid > 0) {
                $conds['audit_uid'] = $auditUid;
            } else {
                $conds['audit_uid'] = -1;
            }
        }

        $ruleName = trim($_GET['rule_name']);
        if (!empty($ruleName)) {
            $ruleName = addslashes($ruleName);
            $conds['standby_1'] = array('LIKE', "%$ruleName%");
        }

        return $conds;
    }

    /**
     * ab角色获取审核时的参数
     *
     * @return array 要求返回的格式
     * 例如：array(
     *              'is_pass'    => false,                        //必填, bool类型, 审核是否通过, 通过true
     *              'service_id' => 123,                          //必填, int类型,业务id
     *              'reason'     => '金额错误',                   //选填, string类型, 失败原因
     *              'callback'    => array($this, 'auditSucc'),    //选填, callback类型, 在完成审核之后将回调此方法, 回调中必须返回成功或者失败
     *       );
     */
    public function getAuditParam() {
        $param  = array('service_id' => intval($_REQUEST['id']));

        $isPass = isset($_REQUEST['agree']);
        if ($isPass) {
            $param['callback'] = array($this, 'auditSucc');
        } else {
            $param['reason'] = trim($_REQUEST['back_mark']);
        }

        $param['is_pass'] = $isPass;

        return $param;
    }

    /**
     * 审核通过之后的回调
     *
     * @param array $task 任务表的数据
     *
     * @retrun bool 操作成功或者失败
     */
    public function auditSucc($task) {
        return M(MODULE_NAME)->where(array('id' => $task['service_id']))->save(array('is_effect' => 1));
    }

    public function checkDataFromCsv() {
        $data = array();
        if (empty($_FILES['send_condition']['tmp_name'])) {
            $data['errMsg'] = "请上传文件!";
        } else {
            $data = $this->getDataFromCsv();
        }
        header('Content-type:text/json');
        echo json_encode($data);
        exit;
    }

    private function getDataFromCsv() {
        $csvData = array();

        $errMsg = '';
        $count = $_POST['is_include_money'] == 1 ? 2 : 1;
        $sendCount = 0;
        $maxMoney = 0;
        $sendTotalMoney = 0;
        if (($handle = fopen($_FILES['send_condition']['tmp_name'], "r")) !== false) {
            if(fgetcsv($handle) !== false) { //第一行是标题不放到数据列表里
                while (($rowData = fgetcsv($handle)) !== false) {
                    if (count($rowData) != $count) {
                        $errMsg = "列数错误，应该为{$count}列!";
                        break;
                    }
                    if ($count == 1) {
                        $rowData[1] = $_POST['money'];
                    }

                    if ($rowData[1] > $maxMoney) {
                        $maxMoney = $rowData[1];
                    }

                    if ($rowData[0] == '') {
                        continue;
                    }
                    if ($_POST['is_include_money'] == 1 && $rowData[1] <= 0) {
                        $errMsg = '发送金额错误！';
                    }
                    $sendCount++;
                    $sendTotalMoney += $rowData[1];
                }
            }
            fclose($handle);
            @unlink($_FILES['send_condition']['tmp_name']);
        }

        return array('errMsg' => $errMsg, 'count' => $sendCount, 'total_money' => $sendTotalMoney, 'max' => $maxMoney);
    }

//==========================下面方法为重写父类中AB角色审核方法
    /**
     * ab角中，查询具体的业务数据
     *
     * @return array 返回查询业务数据的结果
     */
    public function queryServiceData($data) {
        $dataList = array();

        $serviceIds = array_keys($data);
        if (!empty($serviceIds)) {

            if ($_REQUEST['m'] == 'BonusTask') {
                if ($_REQUEST['discount'] == 1) {
                    $types = array(3);
                } else {
                    $types = array(1, 2);
                }
                $list = M(MODULE_NAME)->where('id IN ('.implode(',', $serviceIds).') AND type IN ('.implode(',', $types).')')->order('id DESC')->findAll();
                //$list = M(MODULE_NAME)->where(array('id' => array('IN', $serviceIds), 'type' => array('IN', $types), 'AND'))->order('id DESC')->findAll();
            } else {
                $list = M(MODULE_NAME)->where(array('id' => array('IN', $serviceIds)))->order('id DESC')->findAll();
            }
            foreach ($list as $item) {
                $dataList[$item['id']] = $item;
            }
        }

        return $dataList;
    }

    /**
     * 完成AB审核的审核动作,并执行用户回调
     */
    public function doServiceAudit() {
        $param = $this->getAuditParam();

        $serviceAuditModel = D('ServiceAudit');
        $conds = array('service_type' => $this->getServiceType(),'service_id' => $param['service_id']);
        $task  = $serviceAuditModel->queryTaskByServiceId($conds);
        if ($task['status'] != ServiceAuditModel::NOT_AUDIT) {
            $this->error("数据非待审状态!");
        }

        $adm = es_session::get(md5(conf("AUTH_KEY")));
        $task['audit_uid'] = intval($adm['adm_id']);
        if ($param['is_pass']) {
            $task['status'] = ServiceAuditModel::AUDIT_SUCC;
        } else {
            $task['status'] = ServiceAuditModel::AUDIT_FAIL;
            if (empty($param['reason'])) {
                $this->error("请填写退回原因!");
            }
            $task = $serviceAuditModel->addLastAuditError($task, $param['reason']);
        }

        $serviceAuditModel->startTrans();
        if (!$serviceAuditModel->save($task)) {
            $this->error(L("UPDATE_FAILED"));
        }

        if (is_callable($param['callback']) && false === call_user_func_array($param['callback'], array($task))) {
            $serviceAuditModel->rollback();
            $this->error(L("UPDATE_FAILED"));
        }

        $serviceAuditModel->commit();
        save_log("AB角审核业务", $param['is_pass'], '', $task);

        if ($_REQUEST['m'] == 'BonusTask' && $_REQUEST['discount'] == 1) {
            $this->assign("jumpUrl", u(MODULE_NAME . "/index", array('role' => 'b', 'discount' => 1)));
        } else {
            $this->assign("jumpUrl", u(MODULE_NAME . "/index", array('role' => 'b')));
        }
        $this->success(L("UPDATE_SUCCESS"));
    }

}
