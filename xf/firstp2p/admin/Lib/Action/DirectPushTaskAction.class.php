<?php

use core\service\DirectPushTaskService;
use libs\utils\Curl;

class DirectPushTaskAction extends CommonAction
{
    public static $sendTypeList = array(
        '10' => '用户uid',
        '11' => '用户组',
        '12' => '用户标签',
        '13' => '导入csv'
    );

    public static $sendTypeCondition = array(
        '20' => '用户满足条件',
        '21' => '账户满足条件',
        '22' => '邀请满足条件'
    );

    public static $sendTypeUser = array(
        0 => '请选择',
        1 => '生日'
    );

    public static $sendTypeDeal = array(
        0 => '请选择条件',
        1 => '投资',
        2 => '投资未复投'
    );

    public static $scopeSiteGroup = array(
        0 => '请选择用户范围',
        1 => '所属会员组id包含',
        2 => '所属会员组id不包含',
        3 => '所属站点id包含',
        4 => '所属站点id不包含'
    );

    public static $scopeTime = array(
        0 => '请选择统计时段',
        1 => '过去',
        2 => '指定时间段'
    );

    public static $inviteList = array(
        0 => '请选择',
        1 => '邀请投资人数',
        2 => '邀请投资金额'
    );

    public $directPushTaskService = null;

    public function __construct()
    {
        parent::__construct();
        $this->assign('sendTypeList', self::$sendTypeList);
        $this->assign('sendTypeCondition', self::$sendTypeCondition);
        $this->assign('sendTypeUser', self::$sendTypeUser);
        $this->assign('inviteList', self::$inviteList);
        $this->assign('scopeSiteGroup', self::$scopeSiteGroup);
        $this->assign('scopeTime', self::$scopeTime);
        $this->assign('sendTypeDeal', self::$sendTypeDeal);

        $this->assign('role', $this->getRole());

        $this->directPushTaskService = new DirectPushTaskService();
    }

    public function index()
    {
        $data = $this->queryServiceAudit();
        $list = $data['data_list'];

        $list = $this->formatList($list);
        $this->assign('list', $list);
        //$model = M(MODULE_NAME);
        //if (!empty($model)) {
            //$this->_list($model);
        //}
        //$list = $this->get('list');
        //$list = $this->formatList($list);
        //$this->assign('list', $list);
        $this->display();

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

        if ($this->getRole() == 'b') {
            if (!isset($_REQUEST['range_start'])) {
                $_REQUEST['range_start'] = date('Y-m-d 00:00:00');
            }
            if (!isset($_REQUEST['range_end'])) {
                $_REQUEST['range_end'] = date('Y-m-d 23:59:59');
            }
        }

        $conds = array('service_type' => $this->getServiceType());

        $auditStatus = intval($_REQUEST['audit_status']);
        if ($auditStatus > 0) {
            $conds['status'] = $auditStatus;
        }

        $rangeStart = trim($_REQUEST['range_start']);
        if (!empty($rangeStart)) {
            $conds['standby_2'][] = array('egt', to_timespan($rangeStart));
        }

        $rangeEnd = trim($_REQUEST['range_end']);
        if (!empty($rangeEnd)) {
            $conds['standby_2'][] = array('elt', to_timespan($rangeEnd));
        }

        $ruleName = trim($_REQUEST['rule_name']);
        if (!empty($ruleName)) {
            $ruleName = mysql_escape_string($ruleName);
            $conds['standby_1'] = array('LIKE', "%$ruleName%");
        }

        return $conds;
    }

    public function getServiceType()
    {
        return ServiceAuditModel::SERVICE_TYPE_COUPON;
    }

    public function add()
    {
        $this->display();
    }

    public function insert()
    {
        $this->makeParam();
        $directPushTaskService = new DirectPushTaskService();
        $directPushTaskService->createTask($_POST);
        $this->success('创建成功');
        $this->redirect(u(MODULE_NAME."/index"));
    }

    public function update()
    {
        $this->makeParam();
        $this->directPushTaskService->updateTask($_POST);
        $this->success('更新成功');
        $this->redirect(u(MODULE_NAME."/index"));
    }

    public function edit()
    {
        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if (empty($id)) {
            $this->error(l("INVALID_OPERATION"));
        }
        $condition[$this->pk_name] = $id;
        $vo = $this->model->where($condition)->find();
        parse_str($vo['conditions'], $conditions);
        parse_str($vo['msg_params'], $params_msg);
        parse_str($vo['continuous_params'], $params_continuous);
        $this->assign('conditions', $conditions);
        $this->assign('params_msg', $params_msg);
        $this->assign('params_continuous', $params_continuous);
        $this->assign('vo', $vo);
        $this->display();
    }

    /**
     * makeParam
     *
     * @param mixed $isEdit
     * @access private
     * @return void
     */
    private function makeParam($isEdit = false) {
        if ($_POST['is_continuous']) {
            $_POST['continuous_params'] = "params_interval=".$_POST['params_interval']."&params_count=".$_POST['params_count'];
        }

        if ($_POST['msg_type'] == 1) {
            $_POST['msg_params'] = "params_sms_id=".$_POST['params_sms_id']."&params_sms_money=".$_POST['params_sms_money']."&params_sms_expire=".$_POST['params_sms_expire'];
        } elseif ($_POST['msg_type'] == 2) {
            $_POST['msg_params'] = "params_push_money=".$_POST['params_push_money']."&params_push_expire=".$_POST['params_push_expire'];
        }
        $_POST['start_time']  = to_timespan($_POST['start_time']);
        if ($isEdit) {
            $_POST['update_time'] = get_gmtime();
        } else {
            $_POST['create_time'] = $_POST['update_time'] = get_gmtime();
        }
        if (isset($_POST['time_start'])) {
            $_POST['time_start'] = to_timespan($_POST['time_start']);
        }
        if (isset($_POST['time_end'])) {
            $_POST['time_end'] = to_timespan($_POST['time_end']);
        }
        if ($_POST['send_way'] == 1) {
            $_POST['type'] = $_POST['send_type_list'];
        } else {
            $_POST['type'] = $_POST['send_type_condition'];
        }
        unset($_POST['send_type_list'], $_POST['send_type_condition']);

    }

    public function formatList(&$list)
    {
        $time = get_gmtime();
        foreach ($list as &$item) {
            if ($item['msg_type'] == 2) {
                $item['msg_type'] = '推送';
            } elseif ($item['msg_type'] == 1)  {
                $item['msg_type'] = '短信';
            } else {
                $item['msg_type'] = '无';
            }
            if ($item['send_way'] == 1) {
                $item['send_way'] = '按名单发送';
            } else if ($item['send_way'] == 2) {
                $item['send_way'] = '按条件发送';
            }
            parse_str($item['conditions'], $conditions);
            parse_str($item['msg_params'], $params_msg);
            parse_str($item['continuous_params'], $params_continuous);
            $item = array_merge($item, $conditions, $params_msg, $params_continuous);

            if ($item['send_way'] == '按条件发送') {
                $item['conditions'] = $this->directPushTaskService->getStrategy($item['type'])->buildInfo($conditions);
            }

            if ($item['count'] > 0) {
                $item['send_count'] = $item['count'];
            } elseif ($item['start_time'] < ($time + 3600)) {
                if ($item['queue_id'] > 0) {
                    $spark = $this->directPushTaskService->getStatusFromBaize($item['queue_id']);
                    if ($spark['data']['spark_status'] == 2) {
                        $item['send_count'] = $spark['data']['spark_count'];
                        M("DirectPushTask")->where("id=".$item['id'])->save(array('count' => intval($spark['data']['spark_count'])));
                        $item['status'] = '待审核';
                    } else {
                        $item['send_count'] = '计算中...';
                        $item['status'] = '进行中';
                    }
                } else {
                    $item['send_count'] = '-';
                }
            } else {
                $item['send_count'] = '-';
            }

            if ($item['status'] != '进行中' && $item['status'] != '待审核') {
                if ($item['status'] == 4) {
                    $item['status'] = '已失效';
                } elseif ($item['status'] == 3) {
                    $item['status'] = '已驳回';
                } elseif ($item['status'] == 2) {
                    $item['status'] = '已完成';
                } elseif ($item['status'] == 1) {
                    $item['status'] = '发送中';
                } else {
                    if ($item['count'] > 0) {
                        $item['status'] = '待审核';
                    } else {
                        $item['status'] = '未开始';
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 完成ab审核的审核动作,并执行用户回调
     */
    public function doServiceAudit() {

        $id = intval($_REQUEST['id']);
        if ($id <= 0) {
            $this->error("请指定需要审核的任务");
        }
        $serviceAuditModel = D('ServiceAudit');
        $conds = array('service_type' => $this->getServiceType(),'service_id' => $id);
        $task  = $serviceAuditModel->queryTaskByServiceId($conds);
        if ($task['status'] != ServiceAuditModel::NOT_AUDIT) {
            $this->error("数据非待审状态!");
        }

        $directPushTask = MI('DirectPushTask')->find($id);
        $result = $this->directPushTaskService->getStatusFromBaize($directPushTask['queue_id']);

        if ($result['data']['spark_status'] != 2 && $_REQUEST['is_pass']) {
            $this->error('数据未准备完毕，不能审核');
        }

        if ($_REQUEST['is_pass']) {
            $task['status'] = ServiceAuditModel::AUDIT_SUCC;
            $status = 0;
        } else {
            $task['status'] = ServiceAuditModel::AUDIT_FAIL;
            $status = 3;
        }

        $serviceAuditModel->startTrans();
        if (!$serviceAuditModel->save($task)) {
            $this->error(L("UPDATE_FAILED"));
        }

        $data = array('id' => $id, 'is_effect' => $_REQUEST['is_pass'], 'count' => $result['data']['spark_count'], 'status' => $status);
        $audit = M("DirectPushTask")->where('id=' . $id)->data($data)->save();
        if (!$audit) {
            $this->error(L("UPDATE_FAILED"));
        }

        $serviceAuditModel->commit();
        save_log("AB角审核业务", $_REQUEST['is_pass'], '', $task);

        $this->assign("jumpUrl", u(MODULE_NAME . "/index", array('role' => $this->getRole())));
        $this->success(L("UPDATE_SUCCESS"));
    }

    public function submitAudit()
    {
        $id = intval($_REQUEST['id']);
        if ($id <= 0) {
            $this->error("请指定需要审核的任务");
        }
        $serviceAuditModel = D('ServiceAudit');
        $serviceAuditModel->startTrans();
        $data = array();
        $data['status'] = ServiceAuditModel::NOT_AUDIT;
        $res = M("ServiceAudit")->where("service_id=$id && service_type=".$this->getServiceType())->save($data);
        if (!$res) {
            $this->error("提交审核失败");
        }
        $data = array('status' => 0);
        $audit = M("DirectPushTask")->where('id=' . $id)->save($data);
        if (!$audit) {
            $this->error("提交审核失败!");
        }
        $serviceAuditModel->commit();
        $this->assign("jumpUrl", u(MODULE_NAME . "/index", array('role' => $this->getRole())));
        $this->success("提交审核成功");
    }

    public function giveup()
    {
        $id = intval($_REQUEST['id']);
        if ($id <= 0) {
            $this->error("请指定需要审核的任务");
        }
        $directPushTask = MI('DirectPushTask')->find($id);
        if ($directPushTask['start_time'] < get_gmtime() && ($directPushTask['status'] == 1 || $directPushTask['status'] == 2)) {
            $this->error("该任务不能置为无效");
        }
        $data = array('status' => 4, 'is_effect' => 0);
        $audit = M("DirectPushTask")->where('id=' . $id)->data($data)->save();
        if (!$audit) {
            $this->error("置为无效失败");
        }
        $this->assign("jumpUrl", u(MODULE_NAME . "/index", array('role' => $this->getRole())));
        $this->success("置为无效成功");
    }

    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST['id'];
        if (isset($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach ($rel_data as $data) {
                if ($data['start_time'] < get_gmtime() && ($data['status'] == 1 || $data['status'] == 2)) {
                    $this->error('该任务不可以删除', $ajax);
                }
                $info[] = $data['id'];
            }
            if ($info) {
                $info = implode(",", $info);
            }
            $list = M(MODULE_NAME)->where($condition)->delete();
            if ($list !== false) {
                save_log($info . l("FOREVER_DELETE_SUCCESS"), 1);
                $this->success(l("FOREVER_DELETE_SUCCESS"), $ajax);
            } else {
                save_log($info . l("FOREVER_DELETE_FAILED"), 0);
                $this->error(l("FOREVER_DELETE_FAILED"), $ajax);
            }
        } else {
            $this->error(l("INVALID_OPERATION"), $ajax);
        }
    }
}
