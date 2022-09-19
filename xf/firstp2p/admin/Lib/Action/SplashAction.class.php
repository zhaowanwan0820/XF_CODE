<?php

/**
 * app 3.0 新增闪屏功能
 * @author yutao
 * @date 2015-05-09
 */
class SplashAction extends CommonAction {

    private static $turnType = array(//跳转类型
        '0' => '网页', '1' => '理财', '2' => 'p2p理财列表', '3' => '专享列表', '5' => '交易中心列表',
        '6' => '基金', '7' => '证券', '8' => '借款','9' => '钱包','10' => '发现', '11' => '资讯',
        '12' => '媒体报道', '13' => '走进我们', '14' => '精彩活动','15' => '邀请好友', '16' => '红包',
        '17' => '礼券', '18' => '投资券', '19' => '勋章', '4' => '智多鑫', '26' => '健步','29' => '网信出行',
        '32' => '信宝',
        '-1' => '无',
    );

    private static $site_list = null;

    public function __construct() {
        self::$site_list = array_flip($GLOBALS['sys_config']['TEMPLATE_LIST']);
        ksort(self::$site_list);
        parent::__construct();
    }

    /**
     * 闪屏列表
     */
    public function index() {
        $name = $this->getActionName();
        $model = DI($name);
        if (!empty($model)) {
            $splashList = $this->_list($model, $where, 'id', false);
        }
        foreach ($splashList as $key => &$value) {
            $value['site_name'] = self::$site_list[$value['site_id']];
        }
        $this->assign("list", $splashList);
        $this->display();
    }

    public function add() {
        $this->assign("site_list", self::$site_list);
        $this->assign("turnType", self::$turnType);
        $this->display();
    }

    /**
     * app3.0 闪屏图片上传
     * 上传图片 并将图片信息插入attachment表
     */
    public function uploadSplashImg() {
        $ajaxData = array('code' => '0000', 'message' => '操作成功');
        $file = $_FILES['fileToUpload'];
        // ImageSizeLimit 闪屏图片上传 ios限制400KB以下，android限制350KB以下
        $limitSizeInMB = round(($_GET['platform'] == 'ios' ? 400 : 350) / 1024, 2);
        if (!empty($file)) {
            $uploadFileInfo = array(
                'file' => $file,
                'isImage' => 1,
                'asAttachment' => 1,
                'limitSizeInMB' => $limitSizeInMB,
            );
            $result = uploadFile($uploadFileInfo);
            if (!empty($result['aid']) && $result['filename']) {
                $data['image_id'] = $result['aid'];
                $data['filename'] = get_attr($result['aid'], 1, false);
                $ajaxData['message'] = $data;
            } else {
                $ajaxData = array('code' => '4001', 'message' => '上传失败，请重新上传图片');
            }
        } else {
            $ajaxData = array('code' => '4000', 'message' => '图片格式仅限JPG、PNG，请重新上传图片');
        }
        echo json_encode($ajaxData);
    }

    public function addSplashInfo() {
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $data = M(MODULE_NAME)->create();
        $white_list=$_POST['white_list'];
        $androidIds = array();
        $androidIds['android_480_800'] = $_POST['android_480_800'];
        $androidIds['android_720_1080'] = $_POST['android_720_1080'];
        $androidIds['android_1080_1920'] = $_POST['android_1080_1920'];

        $iosIds = array();
        $iosIds['ios_640_960'] = $_POST['ios_640_960'];
        $iosIds['ios_640_1136'] = $_POST['ios_640_1136'];
        $iosIds['ios_750_1334'] = $_POST['ios_750_1334'];
        $iosIds['ios_1242_2208'] = $_POST['ios_1242_2208'];
        $iosIds['ios_1125_2346'] = $_POST['ios_1125_2346'];

        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/add"));
        if (!check_empty($data['title'])) {
            $this->error(L("请填写标题!"));
        }
//        if (!check_empty($data['link'])) {
//            $this->error(L("SPLASH_LINK_EMPTY_TIP"));
//        }
        foreach ($androidIds as $key => $value) {
            if (!check_empty($value)) {
                //$s=var_dump($data);
                //$this->error(L("$white_list"));
                $this->error(L("android系统闪屏图片为空"));
            }
        }
        foreach ($iosIds as $key => $value) {
            if (!check_empty($value)) {
                $this->error(L("ios系统闪屏图片为空"));
            }
        }

        // 构建数据
        //登陆用户跳转
        if($_POST['jumpType1'] == 0 ) {
            $login_jump = $_POST['first_url'];
            $login_type = 'h5url';
        }
        else{
            $login_jump = '';
            $login_type = 'schema';
        }
        $login_type_id=$_POST['jumpType1'];
        //未登录用户跳转
        if($_POST['jumpType2'] == 0) {
            $unlogin_jump = $_POST['second_url'];
            $unlogin_type = 'h5url';
        }
        else{
            $unlogin_jump = '';
            $unlogin_type = 'schema';
        }
        $unlogin_type_id=$_POST['jumpType2'];
        //生成link
        $link=array('login' => array('type' => $login_type,'url' => $login_jump,'white_list' => $_POST['white_list'],'type_id' => $login_type_id),
            'unlogin' => array('type' => $unlogin_type, 'url' => $unlogin_jump,'type_id' => $unlogin_type_id),
            'valid_time' => array('start_time' => $_POST['startTime'], 'end_time' => $_POST['endTime'])
        );
        $link_json = json_encode($link);

        $data['link']=$link_json;

        foreach ($androidIds as $key => $value) {
            $data['attachment_ids_android'] .= $key . ':' . $value . ',';
        }
        foreach ($iosIds as $key => $value) {
            $data['attachment_ids_ios'] .= $key . ':' . $value . ',';
        }
        $data['attachment_ids_android'] = trim($data['attachment_ids_android'], ',');
        $data['attachment_ids_ios'] = trim($data['attachment_ids_ios'], ',');
        $data['create_time'] = time();
        $data['last_changed_time'] = time();
        // 更新数据
        $log_info = $data['title'];
        $lastInsertId = M(MODULE_NAME)->add($data);
        if (false !== $lastInsertId) {
            //插入成功
            save_log($log_info . L("INSERT_SUCCESS"), 1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"));
        }
    }

    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();

        $idQueryString = '';
        $androidIdArray = explode(',', $vo['attachment_ids_android']);
        foreach ($androidIdArray as $key => $value) {
            $size = explode(':', $value);
            $attachment[$size[0]]['id'] = $size[1];
            if (!empty($size[1])) {
                $idQueryString .= $size[1] . ',';
            }
        }
        $iosIdArray = explode(',', $vo['attachment_ids_ios']);
        foreach ($iosIdArray as $key => $value) {
            $size = explode(':', $value);
            $attachment[$size[0]]['id'] = $size[1];
            if (!empty($size[1])) {
                $idQueryString .= $size[1] . ',';
            }
        }

        //查询attachmentId相关信息
        $attachmentModel = M('Attachment');
        $idQueryString = trim($idQueryString, ',');
        $attRet = $attachmentModel->where("id IN ($idQueryString)")->findAll();
        foreach ($attachment as $key => &$value) {
            foreach ($attRet as $k => $v) {
                if ($value['id'] == $v['id']) {
                    $value['img_url'] = 'http:' . \libs\vfs\Vfs::$staticHost . '/' . $v['attachment'];
                    unset($attRet[$k]);
                    continue;
                }
            }
        }

        $url = json_decode($vo['link'],true);
        $this->assign('vo', $vo);
        $this->assign('unloginType',$url['unlogin']['type_id']);//未登录用户跳转链接类型和链接
        $this->assign('unloginUrl',$url['unlogin']['url']);
        $this->assign('loginType',$url['login']['type_id']);//登录用户跳转链接类型和链接以及白名单
        $this->assign('loginUrl',$url['login']['url']);
        $this->assign('whiteList',$url['login']['white_list']);
        $this->assign('startTime',$url['valid_time']['start_time']);
        $this->assign('endTime',$url['valid_time']['end_time']);
        $this->assign('attachment', $attachment);
        $this->assign("site_list", self::$site_list);
        $this->assign("turnType", self::$turnType);
        $this->display();
    }

    public function update() {
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $data = M(MODULE_NAME)->create();
        $androidIds = array();
        $androidIds['android_480_800'] = $_POST['android_480_800'];
        $androidIds['android_720_1080'] = $_POST['android_720_1080'];
        $androidIds['android_1080_1920'] = $_POST['android_1080_1920'];

        $iosIds = array();
        $iosIds['ios_640_960'] = $_POST['ios_640_960'];
        $iosIds['ios_640_1136'] = $_POST['ios_640_1136'];
        $iosIds['ios_750_1334'] = $_POST['ios_750_1334'];
        $iosIds['ios_1242_2208'] = $_POST['ios_1242_2208'];
        $iosIds['ios_1125_2346'] = $_POST['ios_1125_2346'];
        $log_info = M(MODULE_NAME)->where("id=" . intval($data['id']))->getField("title");
        //开始验证有效性
        if (!check_empty($data['title'])) {
            $this->error(L("标题不能为空"));
        }
//        if (!check_empty($data['link'])) {
//            $this->error(L("SPLASH_LINK_EMPTY_TIP"));
//        }
        foreach ($androidIds as $key => $value) {
            if (!check_empty($value)) {
                $this->error(L("android系统闪屏图片为空"));
            }
        }
        foreach ($iosIds as $key => $value) {
            if (!check_empty($value)) {
                $this->error(L("ios系统闪屏图片为空"));
            }
        }
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));

        // 构建数据
        //登陆用户跳转
        if($_POST['jumpType1'] == 0 ) {
            $login_jump = $_POST['first_url'];
            $login_type = 'h5url';
        }
        else{
            $login_jump = '';
            $login_type = 'schema';
        }
        $login_type_id=$_POST['jumpType1'];
        //未登录用户跳转
        if($_POST['jumpType2'] == 0 ) {
            $unlogin_jump = $_POST['second_url'];
            $unlogin_type = 'h5url';
        }
        else{
            $unlogin_jump = '';
            $unlogin_type = 'schema';
        }
        $unlogin_type_id=$_POST['jumpType2'];
        //生成link
        $link=array('login' => array('type' => $login_type,'url' => $login_jump,'white_list' => $_POST['white_list'],'type_id' => $login_type_id),
            'unlogin' => array('type' => $unlogin_type, 'url' => $unlogin_jump,'type_id' => $unlogin_type_id),
            'valid_time' => array('start_time' => $_POST['startTime'], 'end_time' => $_POST['endTime'])
        );
        $link_json = json_encode($link);

        $data['link']=$link_json;

        foreach ($androidIds as $key => $value) {
            $data['attachment_ids_android'] .= $key . ':' . $value . ',';
        }
        foreach ($iosIds as $key => $value) {
            $data['attachment_ids_ios'] .= $key . ':' . $value . ',';
        }
        $data['attachment_ids_android'] = trim($data['attachment_ids_android'], ',');
        $data['attachment_ids_ios'] = trim($data['attachment_ids_ios'], ',');
        $data['last_changed_time'] = time();
        // 更新数据
        $list = M(MODULE_NAME)->save($data);
        if (false !== $list) {
            //成功提示
            save_log($log_info . L("UPDATE_SUCCESS"), 1);
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("UPDATE_FAILED"), 0);
            $this->error(L("UPDATE_FAILED"), 0, $log_info . L("UPDATE_FAILED"));
        }
    }

    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $attachmentModel = M('Attachment');
        if (isset($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
//            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
//            $attId = array();
//            foreach ($rel_data as $data) {
//                $scr = explode(',', $data['attachment_ids_ios']);
//                foreach ($scr as $key => $value) {
//                    $tmp = explode(':', $value);
//                    if ($tmp[1] > 0) {
//                        $attId[] = $tmp[1];
//                    }
//                }
//
//                $scr = explode(',', $data['attachment_ids_android']);
//                foreach ($scr as $key => $value) {
//                    $tmp = explode(':', $value);
//                    if ($tmp[1] > 0) {
//                        $attId[] = $tmp[1];
//                    }
//                }
//            }
            $list = M(MODULE_NAME)->where($condition)->delete();
            if ($list !== false) {
                save_log($info . l("FOREVER_DELETE_SUCCESS"), 1);
//                if (count($attId) >= 1) {
//                    $condition = array('id' => array('in', $attId));
//                    $delAttRet = $attachmentModel->where($condition)->delete();
//                }
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

?>
