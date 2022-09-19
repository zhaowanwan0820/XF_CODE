<?php


class IndividualShortcutsAction extends CommonAction {
    private static $turnType = array(//跳转类型
        '1' => '我的信宝', '2' => '我的优惠券', '3' => '我的红包', '4' => '我的礼券', '5' => '领券中心', '6' => '邀请好友', '7' => '回款日历',
        '8' => '随心约', '9' => '智多新', '10' => '资金记录', '11' => '月账单', '12' => '我的投资', '13' => '我的网贷', '14' => '我的专享',
        '15' => '我的基金', '16' => '我的保险', '17' => '我的随心约', '18' => '我的自选股', '19' => '我的智多新', '20' => ' 我的黄金', '21' => '帮助中心',
        '22' => '智能客服', '23' => '网页', '24' => '信宝夺宝', '25' => '会员中心', '26' => '签到有礼', '27' => '生活频道', '28' => '勋章', '29' => '风险测评',
        '30' => '借款', '31' => '银行卡', '32' => '意见反馈', '33' => '网信官微', '34' => '客服电话', '36' => '捐赠记录', '37' => '网信通行证 ',
        '38' => '商户', '39' => '我要发红包 ', '40' => 'AR红包', '41' => '我的私募', '42' => '扫一扫', '43' => '服务奖励', '44' => '任务中心', '45' => '充值卡列表'
    );


    private $confModel;
    private $dataObj;

    public function __construct()
    {
        $this->confModel = M('ApiConf');
        $condition['name'] = 'individual_shortcuts_conf';
        $this->dataObj = $this->confModel->where($condition);
        parent::__construct();
    }


    public function index() {

        $_REQUEST['listRows'] = 10;//限定1页10条
        $confList = $this->dataObj->find();//个人中心快捷入口后台配置

        //获取快捷入口列表
        $condition['conf_type'] = 6;
        $condition['site_id'] = 1;
        $condition['is_delete'] = 0;
        $condition['name'] = 'individual_shortcuts';
        $status = $_REQUEST['status'];
        if(!empty($status)){
            $condition['is_effect'] = intval($status)== 1 ? 1 :0;
        }

        //查列表
        $iconList = M('ApiConf')->where($condition)->findAll();


        $apiAdvlist = [];
        foreach ($iconList as $k => $v) {
                $apiAdvlist[] = $v;
        }
        $configList = json_decode($confList['value'],true);
        //将广告内容json串转成数组
        $list = array();
        if (count($apiAdvlist) > 0) {
            foreach ($apiAdvlist as $k => $v){
                $v['value'] = json_decode($v['value'], true);
                $list[] = $v;
            }
        }
        $userService = $configList['userService'];
        $helpService = $configList['helpService'];
        $this->assign('list',$list);
        $this->assign('userService',$userService);
        $this->assign('helpService',$helpService);
        $this->assign('turnType',self::$turnType);
        $this->assign("jumpUrl",u("IndividualShortcuts/index"));
        $this->display('index');
    }

    public function add() {
        //跳转的类型
        $this->assign('turnType',self::$turnType);
        $this->assign("jumpUrl",u("IndividualShortcuts/index"));
        $this->display();
    }

    public function edit() {
        $condition['id'] = $_REQUEST['id'];
        $condition['conf_type'] = 6;
        $advConf = M('ApiConf')->where($condition)->find();
        $advContent = json_decode($advConf['value'], true);
        //跳转的类型
        $this->assign('turnType',self::$turnType);
        $this->assign('id',$advConf['id']);
        $this->assign('advContent',$advContent);
        $this->assign('title',$advConf['title']);
        $this->assign('status',$advConf['is_effect']);
        $this->assign("jumpUrl",u("IndividualShortcuts/index"));
        $this->display();
    }

    public function update() {
        $ajax = intval($_REQUEST['ajax']);
        $condition['id'] = $_REQUEST['id'];
        $data['value'] = $_REQUEST['value'];
        $data['title'] = $_REQUEST['title'];
        $status = $_REQUEST['status'];
        $data['is_effect'] = intval($status)== 1 ? 1 : 0;
        $verifyValue = json_decode($data['value']);

        if (empty($verifyValue)) {
            $this->error("数据为空！");
        }
        $condition['conf_type'] = 6;
        // 保存
        $result = M('ApiConf')->where($condition)->save($data);
        //日志信息
        if (false !== $result) {
            //成功提示
            save_log(L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"),$ajax);
        } else {
            //错误提示
            save_log(self::$advType[$condition['name']].L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),$ajax);
        }
    }

    public function confupdate() {
        $ajax = intval($_REQUEST['ajax']);

        $condition['name'] = 'individual_shortcuts_conf';
        $IndivConf = M('ApiConf')->where($condition)->find();

        $userService = str_replace('，', ',', $_POST['userService']);
        $helpService = str_replace('，', ',', $_POST['helpService']);
        $value = array(
            'userService' => $userService,
            'helpService' => $helpService,
        );
        $data['value'] = json_encode($value);
        $data['is_effect'] = 1;

        $condition['conf_type'] = 6;
        // 保存

        if(!$IndivConf) {
            $data = M('ApiConf')->create();
            $data['conf_type'] = 6;
            $data['site_id'] = 1;
            $data['name'] = 'individual_shortcuts_conf';
            $data['value'] = json_encode($value);
            $data['title'] = '个人中心快捷入口配置';
            $data['is_effect'] = 1;
            $result = M('ApiConf')->add($data);
        }else {
            $result = M('ApiConf')->where($condition)->save($data);
        }
        //日志信息
        if (false !== $result) {
            //成功提示
            save_log('individual_shortcuts_conf'.L("UPDATE_SUCCESS"),1);
            $this->success(L("UPDATE_SUCCESS"),$ajax);
        } else {
            //错误提示
            save_log('individual_shortcuts_conf'.L("UPDATE_FAILED"),0);
            $this->error(L("UPDATE_FAILED"),$ajax);
        }
    }

    public function addShortcuts(){
        $ajax = intval($_REQUEST['ajax']);

        $data = M('ApiConf')->create();
        $data['conf_type'] = 6;
        $data['site_id'] = 1;
        $data['name'] = 'individual_shortcuts';
        $data['value'] = $_REQUEST['value'];
        $data['title'] = $_REQUEST['title'];
        $status = $_REQUEST['status'];
        $data['is_effect'] = intval($status)== 1 ? 1 : 0;
        $verifyValue = json_decode($data['value']);

        if (empty($verifyValue)) {
            $this->error("数据为空！");
        }
        $lastInsertId = M('ApiConf')->add($data);
        if (false !== $lastInsertId) {
            //插入成功
            save_log($data['title'] . L("INSERT_SUCCESS"), 1);
            $this->success(L("INSERT_SUCCESS"),$ajax);
        } else {
            //错误提示
            save_log($data['title'] . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"),$ajax);
        }
    }

    public function delete() {
        //删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST['id'];
        if (isset($id)) {
            $condition['id'] = intval($id);
            $condition['conf_type'] = 6;
            $data = M("ApiConf")->where($condition)->find();
            $info = $data['title'];
            $result = M('ApiConf')->where($condition)->setField('is_delete', 1);
            if ($result!==false) {
                save_log($info.l("DELETE_SUCCESS"),1);
                $this->success(l('DELETE_SUCCESS'),$ajax);
            } else {
                save_log($info.l("DELETE_FAILED"),0);
                $this->error(l("DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }

    //判断图片的大小
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
                'limitSizeInMB' => round(600 / 1024, 2),
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
}
