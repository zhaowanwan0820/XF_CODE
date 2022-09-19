<?php

/**
 * IDVerify class file.
 *
 * @author luzhengshuai<luzhengshuai@ucfgroup.com>
 * */

use core\dao\UserModel;

class IDVerifyAction extends CommonAction
{

    public static $verifyTypes = array( 1 => "上海援金接口", 2 => "榕树接口");

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        if (empty($_POST)) {
            $this->assign("verifyTypes", self::$verifyTypes);
            $this->display();
            exit;
        }

        $name = trim($_POST["name"]);
        $idno = trim($_POST["idno"]);
        $reason = trim($_POST['reason']);
        $verifyType = intval($_POST["verify_type"]);
        if (!$name) {
            $this->error("名字不能为空", 1);
        }

        if (!$idno) {
            $this->error("身份证不能为空", 1);
        }

        if (!$verifyType) {
            $this->error("验证类型不能为空", 1);
        }

        if (!preg_match("/^[\x80-\xff]{6,30}$/",$name)){
            $this->error('姓名只支持中文字符', 1);
        }

        if(strlen($name)>30) {
            $this->error('姓名长度不合法', 1);
        }

        if(!preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $idno)) {
            $this->error('身份证号不合法', 1);
        }

        if (!$reason) {
            $this->error("查询原因不能为空", 1);
        }

        // 上海援金接口
        if ($verifyType === 1) {
            $idnoVerify = new \libs\idno\CommonIdnoVerify('rzb');
        // 榕树
        } elseif ($verifyType === 2) {
            $idnoVerify = new \libs\idno\CommonIdnoVerify('rongshu');
        }
        $ret = $idnoVerify->checkIdno($name, $idno);

        $this->save_log($name, $idno, $reason, 1, $ret['msg'], $verifyType);

        if($ret['code'] != '0') {
            $this->error($ret['msg'], 1);
        } 
        $this->success("验证通过", 1);

    }

    public function photo()
    {
        if (empty($_POST)) {
            //$this->assign("verifyTypes", self::$verifyTypes);
            $this->assign("verifyTypes", [2 => "榕树接口"]);
            $this->display();
            exit;
        }

        $name = trim($_POST["name"]);
        $idno = trim($_POST["idno"]);
        $reason = trim($_POST['reason']);
        $verifyType = intval($_POST["verify_type"]);
        if (!$name) {
            $this->error("名字不能为空", 1);
        }

        if (!$idno) {
            $this->error("身份证不能为空", 1);
        }

        if (!$verifyType) {
            $this->error("验证类型不能为空", 1);
        }

        if (!preg_match("/^[\x80-\xff]{6,30}$/",$name)){
            $this->error('姓名只支持中文字符', 1);
        }

        if(strlen($name)>30) {
            $this->error('姓名长度不合法', 1);
        }

        if(!preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $idno)) {
            $this->error('身份证号不合法', 1);
        }

        if (!$reason) {
            $this->error("查询原因不能为空", 1);
        }

        $image = $_POST['compare'];
        if (empty($image)) {
            $this->error("请上传需要比对的图片", 1);
        }

        $content = file_get_contents($image);
        if ($content === false) {
            $this->error("读取图片失败", 1);
        }

        // 上海援金接口
        if ($verifyType === 1) {
            //Todo
            // 榕树
        } elseif ($verifyType === 2) {
            $ret = \libs\idno\Rongshu::compare($name, $idno, base64_encode($content));
        }

        $this->save_log($name, $idno, $reason, 2, json_encode($ret, JSON_UNESCAPED_UNICODE), $verifyType);

        if($ret['ResultCode'] != 1000) {
            $this->error($ret['ResultCode'], 1);
        }

        $confidence = "相似度:" . $ret['Confidence'];
        $this->ajaxReturn($confidence, '验证通过');

    }

    public function show_logs()
    {
        $map = [];
        if ($_REQUEST['name']) {
            $map['name'] =  array('eq',$_REQUEST['name']);
        }
        $this->_list(M("IdnoLog"), $map);
        $this->display();
    }

    //上传图片
    public function loadFile() {
        $file = current($_FILES);
        if (empty($file) || $file['error'] != 0) {
            $rel = array("code" => 0,"message" => "图片为空");
        }

        if (!empty($file)) {
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'limitSizeInMB' => round(200 / 1024, 2),
            );
            $result = uploadFile($uploadFileInfo);
        }
        if(!empty($result['aid']) && empty($result['errors'])){
            $imgUrl = get_attr($result['aid'],1,false);
            $rel = array("code" => 1,"imgUrl" => $imgUrl);
        }else if(!empty($result['errors'])){
            $rel = array("code" => 0,"message" => end($result['errors']));
        }else{
            $rel = array("code" => 0,"message" => "图片上传失败");
        }
        echo  json_encode($rel);
    }

    public function imageCompare()
    {
        $imgBase64 = $_POST['imgBase64'];
        $imgBase64 = strtr($imgBase64, '-', '+');
        $imgBase64 = preg_replace('/^.*?base64,/', '', $imgBase64);
        $name = $_POST['name'];
        $idno = $_POST['idno'];
        $ret = \libs\idno\Rongshu::compare($name, $idno, $imgBase64);
        $this->save_log($name, $idno, "人工换卡后台审核", 2, json_encode($ret, JSON_UNESCAPED_UNICODE), 2);
        if($ret['ResultCode'] != 1000) {
            $ret = array('code' => 4000, 'data' => $ret['ResultMsg']);
            echo json_encode($ret);
            return;
        }

        $ret = array('code' => 0, 'data' => $ret['Confidence']);
        echo json_encode($ret);
        return;
    }

    private function save_log($name, $idno, $reason, $type, $result, $verify_type)
    {
        $adm_session = es_session::get(md5(conf("AUTH_KEY")));
        $log = [
            'log_time' => get_gmtime(),
            'log_admin' => intval($adm_session['adm_id']),
            'log_ip' => get_client_ip(),
            'type' => $type,
            'idno' => $idno,
            'name' => $name,
            'reason' => $reason,
            'is_p2p_user' => UserModel::instance()->isUserExistsByIdno($idno),
            'result' => $result,
            'verify_type' => $verify_type,
        ];

        $GLOBALS['db']->autoExecute('firstp2p_idno_log', $log, 'INSERT');

    }
}