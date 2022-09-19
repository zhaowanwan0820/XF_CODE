<?php

use libs\utils\PaymentApi;
use core\service\MsgBoxService;
use core\service\PushService;
use core\dao\PushTaskModel;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
set_time_limit(3600);

/**
 * 页面用到的函数
 */
function getParamShow($data) {
    $show = '';
    switch ($data['scope']) {
    case PushTaskModel::SCOPE_ALL:
        $show = '全体用户';
        break;
    case PushTaskModel::SCOPE_USERIDS:
        $show = mb_substr('UID:' . $data['param'], 0, 50);
        break;
    case PushTaskModel::SCOPE_CSV:
        $show = app_conf("STATIC_HOST") .'/' . $data['param'];
        break;
    case PushTaskModel::SCOPE_USER_GROUP:
        $show = M("UserGroup", 'Model', true)->where("id=".$data['param'])->getField("name");
        break;
    default:
        break;
    }
    return $show;
}
//END

class PushToolAction extends CommonAction
{

    /**
     * Code定义
     */
    const CODE_SUCCESS = 0;
    const CODE_ERR_MAX_ROW = -1;
    const CODE_ERR_FORMART = -2;

    /**
     * CSV文件最大行数
     */
    const CSV_MAX_ROW = 200000;

    /**
     * Code Msg
     */
    protected static $errorMsg = [
        self::CODE_SUCCESS => '执行成功',
        self::CODE_ERR_MAX_ROW => '数据应小于200000条，请重新编辑CSV文件',
        self::CODE_ERR_FORMART => 'CSV文件格式错误',
    ];

    /**
     * 任务发送状态映射
     */
    protected static $sendStatusMap = [
        PushTaskModel::SEND_INIT => '待发送',
        PushTaskModel::SEND_PROCESS => '发送中',
        PushTaskModel::SEND_COMPLETE => '已发送'
    ];

    /**
     * 任务类型映射
     */
    protected static $typeMap = [
        PushTaskModel::TASK_MSG => '站内信',
        PushTaskModel::TASK_PUSH => '推送',
    ];

    /**
     * 发送范围参数验证
     */
    protected static $uidTypeField = [
        '_userid' => ['scope' => PushTaskModel::SCOPE_USERIDS, 'field' => 'userId', 'needCheck' => true, 'dataType' => 'string', 'msg' => '用户Id不能为空'], // 用户ID,逗号分隔
        '_user_group_id' => ['scope' => PushTaskModel::SCOPE_USER_GROUP, 'field' => 'groupId', 'needCheck' => true, 'dataType' => 'int', 'msg' => '请选择用户所属网站'], // 用户会员组ID
        '_csv' => ['scope' => PushTaskModel::SCOPE_CSV, 'field' => 'csvUrl', 'needCheck' => true, 'dataType' => 'string', 'msg' => '上传文件失败'], // csv上传
        '_user_all' => ['scope' => PushTaskModel::SCOPE_ALL, 'field' => '', 'needCheck' => false] // 全量用户发送
    ];

    public function __construct()
    {
        parent::__construct();
        $this->model = MI('PushTask');
        // 发送操作时，检查CSV文件上传情况，并解析为uid
        if (in_array(ACTION_NAME, ['sendMsg', 'sendPush'])) {
            if (strpos($_REQUEST['uidType'], 'csv')) {
                list($err, $data) = array_values($this->getCSV());
                if ($err) $this->error('CSV错误');
                $_REQUEST['userId'] = implode(',', $data);
                $uploadFileInfo = [
                    'file' => $_FILES['csv'],
                    'asAttachment' => true,
                    'limitSizeInMB' => 2
                ];
                try {
                    $result = uploadFile($uploadFileInfo);
                } catch (\Exception $e) {
                    $this->error('csv文件上传失败');
                }
                $_REQUEST['csvUrl'] = $result['full_path'];
            }
        }
    }

    public function index()
    {
        $where = 'is_delete = '. PushTaskModel::NO_DELETE;
        $title = trim($_REQUEST['title']);
        if (!empty($title)) {
            $where .= ' AND title LIKE "%'.$title .'%"';
        }
        $content = trim($_REQUEST['content']);
        if (!empty($content)) {
            $where .= ' AND content LIKE "%'.$content .'%"';
        }

        $type = intval($_REQUEST['type']);
        if (!empty($type)) {
            $where .= ' AND type = ' .$type;
        }

        $sendStatus = intval($_REQUEST['send_status']);
        if (!empty($sendStatus)) {
            $where .= ' AND send_status = ' .$sendStatus;
        }

        $timeStart = trim($_REQUEST['time_start']);
        $timeEnd = trim($_REQUEST['time_end']);
        if ($timeStart) {
            $where .= " AND send_time >= '". strtotime($timeStart) ."'";
        }

        if ($timeEnd) {
            $where .= " AND send_time <= '". strtotime($timeEnd) ."'";
        }

        $this->_list($this->model, $where);
        $this->assign('sendStatusMap', self::$sendStatusMap);
        $this->assign('typeMap', self::$typeMap);
        $this->display();
    }

    public function add() {
        $allType = [MsgBoxEnum::TYPE_NOTICE => '通知'];
        $this->assign('allType', $allType);
        $groups = MI('UserGroup')->findAll(['field' => 'id, name']);
        $this->assign('allGroup', $groups);
        $this->display();
    }

    /**
     * 发送站内信
     */
    public function sendMsg()
    {
        $title = trim($_REQUEST['title']);
        $content = trim($_REQUEST['content']);
        $url = trim($_REQUEST['url']);
        $type = intval($_REQUEST['type']);

        if (empty($title) || empty($content)) {
            $this->error('参数错误');
        }
        $data = $this->_validateSendParams();
        $data['type'] = PushTaskModel::TASK_MSG;
        $data['title'] = $title;
        $data['content'] = $content;
        $data['url'] = $url;
        $data['send_time'] = trim($_REQUEST['msg_send_time']);
        $data['msg_type'] = ($data['scope'] == PushTaskModel::SCOPE_ALL) ? 0 : intval($_REQUEST['msg_type']);
        $this->_savePushTask($data);
    }

    /**
     * 发送推送
     */
    public function sendPush()
    {
        \libs\utils\PhalconRPCInject::init();

        $badge = intval($_REQUEST['badge']);
        $content = $_REQUEST['content'];

        if (empty($content)) {
            $this->error('参数错误');
        }
        $data = $this->_validateSendParams();
        $data['title'] = $badge;
        $data['content'] = $content;
        $data['type'] = PushTaskModel::TASK_PUSH;
        $data['send_time'] = trim($_REQUEST['push_send_time']);

        $this->_savePushTask($data);
    }

    /**
     * 检查CSV文件
     * @return [type] [description]
     */
    public function checkCSV()
    {
        list($err, $data) = array_values($this->getCSV());
        if ($err) {
            $this->returnJson($err);
        }

        if (count($data) > self::CSV_MAX_ROW) {
            $this->returnJson(self::CODE_ERR_MAX_ROW);
        }

        $this->returnJson(self::CODE_SUCCESS);
    }

    /**
     * 格式化返回JSON
     * @param  [type] $code [description]
     * @param  string $msg  [description]
     * @return [type]       [description]
     */
    protected function returnJson($code, $msg = '')
    {
        $msg = $msg ?: self::$errorMsg[$code] ?: 'failed';
        $ret = ['errorCode' => $code, 'msg' => $msg];
        exit(json_encode($ret));
    }

    /**
     * 获取CSV文件
     * @return [type] [description]
     */
    public function getCSV()
    {
        $csvFile = $_FILES['csv'];
        $data = [];
        if (($handle = fopen($csvFile['tmp_name'], "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000)) !== FALSE) {
                $num = count($line);
                if ($num > 1) return ['err' => self::CODE_ERR_FORMART];
                $data[] = intval(array_shift($line));
            }
            fclose($handle);
        }
        //@unlink($csvFile['tmp_name']);
        // 去掉title行
        array_shift($data);
        return ['err' => self::CODE_SUCCESS, 'data' => $data];
    }

    /**
     * 输出模板文件
     */
    public function downloadTemplate() {
        Header("Location: static/admin/Common/send_msg_template.csv");
        exit();
    }

    /**
     * 保存日志
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function saveLog($msg)
    {
        save_log($msg, 1);
    }

    /**
     * _validateSendParams
     * 验证发送范围相关参数
     *
     * @access private
     * @return void
     */
    private function _validateSendParams()
    {
        $uidType = trim($_REQUEST['uidType']);
        $typeMatch = str_replace(['msg', 'push'], '', $uidType);
        if (!isset(self::$uidTypeField[$typeMatch])) {
            return $this->error('发送用户范围选择错误');
        }

        $typeConfig = self::$uidTypeField[$typeMatch];
        $value = '';
        if ($typeConfig['needCheck']) {
            if ($typeConfig['dataType'] == 'int') {
                $value = intval($_REQUEST[$typeConfig['field']]);
            } else {
                $value = trim($_REQUEST[$typeConfig['field']]);
            }

            if (empty($value)) {
                return $this->error($typeConfig['msg']);
            }
        }

        $data = ['param' => $value, 'scope' => $typeConfig['scope']];

        return $data;
    }

    /**
     * _savePushTask
     * 保存推送任务
     *
     * @access private
     * @param array $data
     * @return void
     */
    private function _savePushTask($data)
    {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $data['create_time'] = time();
        $data['admin_id'] = intval($adm_session['adm_id']);
        $data['send_status'] = PushTaskModel::SEND_INIT;
        if (!empty($data['send_time'])) {
            $data['send_time'] = strtotime($data['send_time']);
        }
        $id = M('PushTask')->add($data);
        if ($id) {
            $this->saveLog('创建推送任务:'. $id);
            $this->success('创建推送任务成功', 0, '/m.php?m=PushTool&a=index');
        } else {
            $this->error('创建推送任务失败');
        }
    }

    /**
     * removePushTask
     * 删除推送任务
     *
     * @param array $data
     * @access private
     * @return void
     */
    public function removePushTask()
    {
        $id = intval($_POST['id']);
        if (!$id) {
            $this->error('任务ID不能为空');
        }

        $db = \libs\db\Db::getInstance('firstp2p');
        $sql = "UPDATE firstp2p_push_task SET is_delete = " .PushTaskModel::IS_DELETE. " WHERE id = $id AND send_status = " . PushTaskModel::SEND_INIT;
        $res = $db->query($sql);
        if (!$res || $db->affected_rows() == 0) {
            $this->error('删除失败, 请刷新页面');
        } else {
            $this->saveLog('删除推送任务:' . $taskId);
            $this->success('删除成功');
        }
    }

    private function _getScopeDesc($data) {
    }
}
