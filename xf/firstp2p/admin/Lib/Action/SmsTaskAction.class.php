<?php
/**
 * 批量发手机短信管理
 *
 * @date 2017-04-20
 * @author chaizuxue
 */
use libs\utils\Curl;

class SmsTaskAction extends CommonAction {

    const USER_ID_STR = '用户ID';
    const DEAL_BATCH_NUM = 2000;

    private $_adminId;   //登录后台的管理员id
    private $_adminName; //登录后台的管理员name
    private $_smsCheckers;
    private $_isChecker;

    public function __construct() {
        parent::__construct();

        $adminSess = es_session::get(md5(conf('AUTH_KEY'))); //管理员的SESSION
        $this->_adminId = intval($adminSess['adm_id']);
        $this->_adminName = trim($adminSess['adm_name']);
        $this->_smsCheckers = explode(',', app_conf('SMS_TASK_CHECKERS'));
        $this->_isChecker = in_array($this->_adminId, $this->_smsCheckers);

        $this->assign('_adminId', $this->_adminId);
        $this->assign('_adminName', $this->_adminName);
        $this->assign('_smsCheckers', $this->_smsCheckers);
        $this->assign('_isChecker', $this->_isChecker);
    }

    /**
     * 新增短信任务页面显示
     */
    public function add() {
        $this->display('add');
    }

    /**
     * 输出json
     */
    public static function sendJson($errorCode = 200, $errorMsg = null, $data = []) {
        $respon = [
            'errorCode' => $errorCode,
            'errorMsg'  => $errorMsg,
            'data'      => $data
        ];
        exit(json_encode($respon));
    }

    /**
     * 字符串转码
     */
    public static function dataIconv($data, $from = 'GBK', $to = 'UTF-8') {
        if (!is_array($data)) {
            return mb_convert_encoding(trim($data), $to, $from);
        }

        foreach ($data as $key => $val) {
            $data[$key] = self::dataIconv($val, $from, $to);
        }

        return $data;
    }

    /**
     * 获取字段数目
     */
    public static function getFieldCount($data) {
        $count = 0;
        foreach ($data as $key => $val) {
            $val = trim($val);
            strlen($val) && $count ++;
        }
        return $count;
    }

    /**
     * 检查一行的每个字段
     */
    public static function checkField($data, $firstLine) {
        $fieldCount = self::getFieldCount($firstLine);
        if (self::getFieldCount($data) != $fieldCount) {
            return '字段数和表头数不匹配';
        }

        if (!preg_match('~^\d+$~', $data[0])) {
            return self::USER_ID_STR . '格式错误';
        }

        for ($index = 1; $index < $fieldCount; $index ++) {
            $value = self::dataIconv($data[$index]);
            if(preg_match("/[\@\#\$\^\&\*\=\\\|]/", $value)) {
                return "字段{$firstLine[$index]}, 含有特殊符号";
            }
        }
        return "";
    }

    /**
     * 获取csv文件内容
     */
    private function checkCsvFileData($file) {
        $handle = fopen($file['tmp_name'], 'r');
        if($handle === false) {
            self::sendJson(203, '打开导入文件失败');
        }

        $firstLine = self::dataIconv(fgetcsv($handle));
        if (empty($firstLine) || self::USER_ID_STR != trim($firstLine[0])) {
            self::sendJson(204, '模板文件错误');
        }

        $rowNum = 2;
        $tmpUserIds = $userIds = $errorMsg = $retData = array();
        while (($data = fgetcsv($handle)) !== false) {
            $error = self::checkField($data, $firstLine);
            if (!empty($error)) {
                $errorMsg[$rowNum ++] = $error;
                continue;
            }

            if (isset($userIds[$data[0]])) {
                $errorMsg[$rowNum ++] = self::USER_ID_STR . "和第{$userIds[$data[0]]}行重复";
                continue;
            }

            $userIds[$data[0]] = $rowNum ++;
            $tmpUserIds[] = $data[0];
            $extInfo = array_combine($firstLine, self::dataIconv($data));
            unset($extInfo[SmsTaskAction::USER_ID_STR]);
            $retData[$data[0]] = $extInfo;

            if (count($tmpUserIds) % self::DEAL_BATCH_NUM == 0) {
                $users = SmsTaskModel::getMobileByUserIds($tmpUserIds);
                $diff = array_diff($tmpUserIds, array_keys($users));
                foreach ($diff as $item) {
                    $errorMsg[$userIds[$item]] = "用户{$item}不存在或手机号错误";
                    unset($retData[$item]);
                }
                $tmpUserIds = array();
            }
        }

        $users = SmsTaskModel::getMobileByUserIds($tmpUserIds);
        $diff = array_diff($tmpUserIds, array_keys($users));
        foreach ($diff as $item) {
            $errorMsg[$userIds[$item]] = "用户{$item}不存在或手机号错误";
            unset($retData[$item]);
        }

        fclose($handle);
        if (empty($retData)) {
            self::sendJson(205, '文件有效内容为空');
        }

        return array("errorMsg" => $errorMsg, "fileData"=>$retData);
    }

    public static function getStaticPrefix() {
        return app_conf('ENV_FLAG') != 'online' ? '//' . $GLOBALS['sys_config']['vfs_ftp']['ftp_host'] : $GLOBALS['sys_config']['STATIC_HOST'];
    }

    /**
     * 上传文件操作
     */
    public function upload() {
        $this->setLimit();

        $file = $_FILES['file'];
        if(empty($file['tmp_name'])) {
            self::sendJson(201, '导入的文件不能为空');
        }

        $fileExten = pathinfo($file['name'], PATHINFO_EXTENSION);
        if($fileExten !== 'csv') {
            self::sendJson(202, '导入的文件不是csv格式');
        }

        $checkRet = $this->checkCsvFileData($file);
        $errorMsg = "";
        if (!empty($checkRet['errorMsg'])) {
            ksort($checkRet['errorMsg'], SORT_NUMERIC);
            $errorsInfo = array();
            foreach ($checkRet['errorMsg'] as $line => $error) {
                $errorsInfo[] = "第{$line}行 => {$error}";
            }
            $errorMsg = implode(';', $errorsInfo);
        }

        $fileInfo = [
            'file'          => $file,     //文件域信息数组
            'isImage'       => 0,         //是否是图片
            'asAttachment'  => 1,         //是否作为附件保存
            'limitSizeInKB' => 5 * 1024,  // 上传文件需要限制大小，单位KB,限制为5MB
        ];

        $upload = uploadFile($fileInfo);
        if($upload['status'] == 1) {
            $upload['full_path'] = self::getStaticPrefix() . '/' . $upload['full_path'];
            $upload['check_error_msg'] = $errorMsg;
            $_SESSION['file_'.$upload['aid']."_userlist"] = $checkRet['fileData'];
            self::sendJson(200, '导入成功', $upload);
        } else {
            self::sendJson(209, '导入失败', $upload);
        }
    }

    /**
     * 数据保存或提交审核的校验
     */
    public function validate()
    {
        $data['content'] = isset($_REQUEST['content']) ? trim($_REQUEST['content']) : '';
        if(empty($data['content'])) {
            return $this->error('短信内容不能为空');
        }
        if(substr_count($data['content'], '{') !== substr_count($data['content'], '}')) {
            return $this->error('{}没有成对出现');
        }

        if (preg_match('/\{\s*\}/', $data['content'])) {
            return $this->error('不能存在{}空变量');
        }

        $iSendType = intval($_REQUEST['send_type']);
        if(!in_array($iSendType, array(0,1,2,3))){
            return $this->error("发送方式未知");
        }
        $data['send_type'] = $iSendType;

        switch ($data['send_type']) {
            case 0:
                $data['attachment_id'] = isset($_REQUEST['attachment_id']) ? intval($_REQUEST['attachment_id']) : 0;
                if (empty($data['attachment_id'])) {
                    return $this->error('短信发放对象不能为空');
                }

                $attach = get_attr($data['attachment_id'], '', false, true);
                $prefix = strtolower(self::getStaticPrefix());
                $fileUrl = (preg_match('/^https?:/', $prefix) ? '' : 'http:') . $prefix . '/' . $attach['attachment'];

                $handle = fopen($fileUrl, 'r');
                $firstLine = array_filter(self::dataIconv(fgetcsv($handle)), function ($item) { return $item != self::USER_ID_STR; });
                fclose($handle);

                preg_match_all('/\{([^\}]+)\}/', $data['content'], $matches);
                $firstLine = array_unique($firstLine);
                $matches = array_unique($matches[1]);
                if (count($firstLine) != count($matches) || count(array_intersect($firstLine, $matches)) != count($matches)) {
                    return $this->error('短信内容中变量和上传文件列头不匹配');
                }
                break;
            case 1 :
                $data['attachment_id'] = intval($_REQUEST['send_type_1_value']);
                break;
            case 2 :
                $data['attachment_id'] = intval($_REQUEST['send_type_2_value']);
                break;
            case 3 :
                $data['attachment_id'] = trim($_REQUEST['send_type_3_value']);
                if(is_mobile($data['attachment_id'])){
                    $info = SmsTaskModel::getUserByMobile($data['attachment_id']);
                    $data['extinfo'] = json_encode(array("mobile"=>$data['attachment_id']));
                    $data['attachment_id'] = $info['id'];
                }
                if(empty($info['id'])){
                    return $this->error('请确认手机号');
                }
                break;
        }

        $data['work_num'] = trim($_REQUEST['work_num']);
        $data['expect_send_time'] = empty($_REQUEST['expect_send_time']) ? 0 : strtotime($_REQUEST['expect_send_time']);
        return $data;
    }

    /**
     * 保存短信任务
     */
    public function save()
    {
        $this->setLimit();

        $data = $this->validate();
        if(empty($_REQUEST['id']) || !isset($_REQUEST['id'])) {
            $data['creator'] = $this->_adminId;
            $data['create_time'] = time(); //get_gmtime();
            $data['task_status'] = $_REQUEST['check'] == 1 ? 2 : 1;

            switch ($data['send_type']) {
                case 0 :
                    $aUserList = $_SESSION['file_'.$data['attachment_id']."_userlist"];
                    break;
                case 1 :
                    $userIds = $_SESSION['baize_'.$data['attachment_id']."_data"]['user'];
                    $aUserList = array_fill_keys($userIds, array());
                    $data['extinfo'] = json_encode(array("create_user"=>$_SESSION['baize_'.$data['attachment_id']."_data"]['info']['create_user'], 
                        "spark_count" => $_SESSION['baize_'.$data['attachment_id']."_data"]['info']['spark_count']));
                    break;
                case 2 :
                case 3 :
                    $aUserList = array($data['attachment_id']=>array());
                    break;
            }

            if(empty($aUserList)){
                return $this->error("操作失败");
            }

            $model = new SmsTaskModel();
            $model->startTrans();
            $iTaskId = $model->add($data);
            if (empty($iTaskId)) {
                $model->rollback();
                return $this->error("操作失败");
            }

            $offset = 0;
            do{
                $doList = array_slice($aUserList, $offset, self::DEAL_BATCH_NUM, true);
                $result = SmsTaskModel::batchAddSmsTaskUsers($iTaskId, $doList);
                $offset += self::DEAL_BATCH_NUM;
                if(!$result){
                    break;
                }
            }while ($offset < count($aUserList));

            if (empty($result)) {
                $model->rollback();
                return $this->error("操作失败");
            }

            $model->commit();
            $this->success('操作成功');
        } else {
            $id = intval($_REQUEST['id']);
            $model = new SmsTaskModel();
            //任务信息
            $taskInfo =$model->find($id);
            if (empty($taskInfo) || !in_array($taskInfo['task_status'], array(1, 4))) {
                return $this->error('操作失败');
            }

            $aUserList = array();
            $bIsUpdateUser = false;
            if($taskInfo['send_type'] != $data['send_type'] || $taskInfo['attachment_id'] != $data['attachment_id']){
                $bIsUpdateUser = true;
                switch ($data['send_type']) {
                    case 0 :
                        $aUserList = $_SESSION['file_'.$data['attachment_id']."_userlist"];
                        break;
                    case 1 :
                        $userIds = $_SESSION['baize_'.$data['attachment_id']."_data"]['user'];
                        $aUserList = array_fill_keys($userIds, array());
                        $data['extinfo'] = json_encode(array("create_user"=>$_SESSION['baize_'.$data['attachment_id']."_data"]['info']['create_user'], 
                            "spark_count" => $_SESSION['baize_'.$data['attachment_id']."_data"]['info']['spark_count']));
                        break;
                    case 2 :
                    case 3 :
                        $aUserList = array($data['attachment_id']=>array());
                        break;
                }
            }
            if($bIsUpdateUser && empty($aUserList)){
                return $this->error("操作失败");
            }

            $model->startTrans();
            if ($bIsUpdateUser) {
                //删除附件内容
                $taskUserModel = new SmsTaskUserModel();
                $table = 'firstp2p_sms_task_user';
                $sql = "UPDATE {$table} SET status = 2 WHERE sms_task_id = {$id}";
                $result = $taskUserModel->execute($sql);
                if (!$result) {
                    $model->rollback();
                    return $this->error('操作失败');
                }
                //更新内容
                $offset = 0;
                do{
                    $doList = array_slice($aUserList, $offset, self::DEAL_BATCH_NUM, true);
                    $result = SmsTaskModel::batchAddSmsTaskUsers($id, $doList);
                    $offset += self::DEAL_BATCH_NUM;
                    if(!$result){
                        break;
                    }
                }while ($offset < count($aUserList));

                if (!$result) {
                    $model->rollback();
                    return $this->error('操作失败');
                }
            }

            if ($_REQUEST['check'] == 1) {
                $taskInfo['task_status'] = 2; 
            } elseif ($taskInfo['task_status'] == 4) {
                $taskInfo['task_status'] = 1; //退回修改
            }

            $taskInfo = array_merge($taskInfo, $data);
            $result = $model->save($taskInfo);
            if (!$result) {
                $model->rollback();
                return $this->error('操作失败');
            }

            $model->commit();
            $this->success('操作成功');
        }
    }

    /**
     * 短信任务列表页
     */
    public function index()
    {
        /*
        if ($this->_isChecker) {
            $where = 'task_status != 7 AND task_status != 1';
        } else {
            $where = 'task_status != 7 AND creator = ' . $this->_adminId;
        }
        */
        $where = ' task_status != 7 ';

        $content = !empty($_REQUEST['content']) ? addslashes(trim($_REQUEST['content'])) : '';
        if (!empty($content)) {
            $where .= " AND content LIKE '%{$content}%' ";
        }
        $workNum = empty($_REQUEST['work_num']) ? '' : addslashes(trim($_REQUEST['work_num']));
        if(!empty($workNum)){
            $where .= " AND work_num = '$workNum' ";
        }
        if (!empty($_REQUEST['begin'])) {
            $where .= ' AND send_time >= ' . strtotime(trim($_REQUEST['begin']));
        }
        if(!empty($_REQUEST['end'])) {
            $where .= ' AND send_time <  ' . strtotime(trim($_REQUEST['end']));
        }

        $taskStatus = !empty($_REQUEST['task_status']) ? intval($_REQUEST['task_status']) : 0;
        if($taskStatus !== 0) {
            $where .= ' AND task_status = ' . $taskStatus;
        }

        $this->model = new SmsTaskModel();
        $list = (array) $this->_list($this->model, $where);
        foreach($list as &$val){
            if(!empty($val['extinfo'])){
                $val['extinfo'] = json_decode($val['extinfo'], true);
            }
        }
        $this->assign('list', $list);

        $adminIds = [];
        foreach ($list as $row) {
            $adminIds[] = $row['creator'];
            $adminIds[] = $row['checker'];
        }

        $adminInfo = SmsTaskModel::getAdmNamesByAdminIds(array_unique($adminIds));
        $this->assign('adminInfo', $adminInfo);
        $this->display();
    }

    /**
     * 删除短信任务
     */
    public function del()
    {
        $id = intval(filter_input(INPUT_GET, 'id'));
        $model = new SmsTaskModel();
        $model->startTrans();

        $table = DB_PREFIX . 'sms_task';
        $result1 = $model->execute("UPDATE {$table} SET task_status = 7 WHERE task_status IN(1, 4) AND id = {$id}");
        if (!$result1) {
            $model->rollback();
            $this->error('删除失败');
        }

        $table = DB_PREFIX . 'sms_task_user';
        $result2 = $model->execute("UPDATE {$table} SET status = 2 WHERE sms_task_id = {$id}");
        if (!$result2) {
            $model->rollback();
            $this->error('删除失败');
        }

        $model->commit();
        $this->success('删除成功');
    }

    /**
     * 编辑显示
     */
    private function getOne() {
        $id = intval(filter_input(INPUT_GET, 'id'));
        $info = (new SmsTaskModel())->find($id);
        if($info['send_type'] == 0){
            $attach = get_attr($info['attachment_id'], '', false, true);
            $this->assign('attach', $attach);
            $this->assign('prefix', self::getStaticPrefix() . '/' . $attach['attachment']);
        }
        if(!empty($info['extinfo'])){
            $info['extinfo'] = json_decode($info['extinfo'], true);
        }
        $this->assign('info', $info);
        return $info;
    }

    /**
     * 编辑短信任务
     */
    public function edit() {
        $this->getOne();
        $this->display();
    }

    /**
     * 编辑短信任务
     */
    public function show() {
        $this->getOne();
        $this->display();
    }

    /**
     * 发送短信任务
     */
    public function send() {
        $action = $_REQUEST['doSend'];
        if (empty($action)) {
            $this->getOne();
            $this->display();
            return true;
        }

        $model = new SmsTaskModel();
        $id = intval($_REQUEST['id']);
        $info = $model->find($id);
        $info['task_status'] = 8; //待发送
        $info['update_time'] = time();
        $result = $model->save($info);
        return $result ? $this->success('操作成功') : $this->error('操作失败');
    }

    /**
     * 审核短信任务
     */
    public function check()
    {
        $model = new SmsTaskModel();
        $info = $this->getOne();
        if ($info['task_status'] != 2) {
            $this->error('操作失败');
        }

        $attach = get_attr($info['attachment_id'], '', false, true);
        $this->assign('attach', $attach);
        $this->assign('prefix', self::getStaticPrefix() . '/' . $attach['attachment']);

        $info['checker'] = $this->_adminId;
        if(isset($_REQUEST['check_pass'])) {
            $info['task_status'] = 8;//取消发送按钮，审核后直接等待发送
            $info['check_pass_time'] = time(); //get_gmtime();
            $result = $model->save($info);
            return $result ? $this->success('操作成功') : $this->error('操作失败');
        }

        if(isset($_REQUEST['reback'])) {
            $info['task_status'] = 4;
            $result = $model->save($info);
            return $result ? $this->success('操作成功') : $this->error('操作失败');
        }

        $this->display();
    }

    public function error($message) {
        $_SERVER["HTTP_REFERER"] = '/m.php?m=SmsTask&a=index';
        return parent::error($message);
    }

    public function success($message) {
        return parent::success($message, false, '/m.php?m=SmsTask&a=index');
    }

    public function setLimit() {
        set_time_limit(600); //300s超时
        ini_set('memory_limit', '4096M');
    }

    public function checkUserOrMobile(){
        $iSendType = intval($_POST['send_type']);
        $sVal = trim($_POST['val']);
        $r;
        if($iSendType == 2){
            $sVal = intval($sVal);
            $r = SmsTaskModel::getMobileByUserIds(array($sVal));
        }elseif($iSendType == 3){
            if(is_mobile($sVal)) {
                $r = SmsTaskModel::getUserByMobile($sVal);
            }
        }else{
            echo json_encode(array("code"=>-1));
        }
        if(empty($r)){
            echo json_encode(array("code"=>-2));
        }else{
            echo json_encode(array("code"=>0));
        }
        exit();
    }

    public function importbaize(){
        $iVal = intval($_POST['val']);
        if(empty($iVal)){
            echo json_encode(array("code"=>-1));
            exit;
        }
        $baizeDomain = app_conf('BAIZE_DOMAIN');
        $baizeKey = app_conf("BAIZE_KEY");
        $param = array("client"=>"P2P", "spark_id"=>$iVal, "time"=>time());
        $param['sign'] = md5("client=".$param['client']."&spark_id=".$param['spark_id']."&time=".$param['time'].$baizeKey);
        $statusRet = Curl::post($baizeDomain."/api/taskstatus", $param);
        $aSR = json_decode($statusRet, true);
        if($aSR['status'] != 0 || $aSR['data']['spark_status'] != 2){
            echo json_encode(array("code"=>-2, "data"=>$aSR));
            exit;
        }
        $resultRet = Curl::post($baizeDomain."/api/taskresult", $param);
        $aRR = json_decode($resultRet, true);
        if($aRR['status'] != 0 || $aRR['data']['spark_status'] != 2){
            echo json_encode(array("code"=>-3, "data"=>$aRR));
            exit;
        }
        $num = 0;
        $error = "";
        $userid = array();
        $tmpUserIds = array();
        foreach($aRR['data']['spark_result'] as $item){
            $num++;
            $item = intval($item);
            if($item <= 0){
                $error .= "第{$num}行数据无效;";
                continue;
            }
            $tmpUserIds[$item] = $num;
            if (count($tmpUserIds) % self::DEAL_BATCH_NUM == 0) {
                $users = SmsTaskModel::getMobileByUserIds(array_keys($tmpUserIds));
                $diff = array_diff(array_keys($tmpUserIds), array_keys($users));
                foreach ($diff as $item) {
                    $error .= "第".$tmpUserIds[$item]."行数据无效;";
                }
                $userid = array_merge($userid, array_keys($users));
                $tmpUserIds = array();
            }
        }
        $users = SmsTaskModel::getMobileByUserIds(array_keys($tmpUserIds));
        $diff = array_diff(array_keys($tmpUserIds), array_keys($users));
        foreach ($diff as $item) {
            $error .= "第".$tmpUserIds[$item]."行数据无效;";
        }
        $userid = array_merge($userid, array_keys($users));

        if(empty($userid)){
            echo json_encode(array("code"=>-4, "data"=>$aRR));;
            exit;
        }

        $_SESSION['baize_'.$iVal."_data"] = array("info"=>array("create_user"=>$aSR['data']['create_user'], "spark_count"=>$aSR['data']['spark_count']), "user"=>$userid);

        echo json_encode(array("code"=>0, "error"=>$error, "userid"=>$userid, "data"=>$aRR['data'], "sta"=>$aSR['data']));
        exit;
    }

    public function retser(){
        $iVal = trim($_POST['val']);
        $id = intval($_REQUEST['id']);

        if(empty($id) || empty($iVal) || !is_numeric($iVal)){
            echo json_encode(array("code"=>-1));
            exit;
        }

        $taskUserModel = new SmsTaskUserModel();
        $table = 'firstp2p_sms_task_user';
        $sql = "select * from {$table} where sms_task_id = {$id} and status = 1";
        if(is_mobile($sVal)) {
            $sql .= " and mobile = '$iVal' ";
        }else{
            $sql .= " and user_id = '$iVal' ";
        }

        $result = $taskUserModel->execute($sql);
        if(empty($result)){
            echo json_encode(array("code"=>-2));
        }else{
            echo json_encode(array("code"=>0));
        }
        exit;
    }
}
