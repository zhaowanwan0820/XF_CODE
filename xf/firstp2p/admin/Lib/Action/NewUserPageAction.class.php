<?php
/**
 * @desc 新手专区
 * @date 2017-6-11 10:46:05
 * Class NewUserPageAction
 */
use core\service\NewUserPageService;
class NewUserPageAction extends CommonAction{
    public function index()
    {
        $name = $this->getActionName();
        $model = DI($name);
        if (!empty($model)) {
            $list = $this->_list($model, array(), 'id', false);
        }

        $this->assign("list", $list);
        $this->display();
    }

    public function add(){
        $this->display();
    }

    public function insert() {
        B('FilterString');

        $data = M(MODULE_NAME)->create ();
        $conf_reg_wap_app = $_POST['conf_reg_wap_app'];
        $conf_reg_pc = $_POST['conf_reg_pc'];
        $conf_reg = $conf_reg_wap_app . "," . $conf_reg_pc;

        $conf_bid_wap_app = $_POST['conf_bid_wap_app'];
        $conf_bid_pc = $_POST['conf_bid_pc'];
        $conf_bid = $conf_bid_wap_app . "," . $conf_bid_pc;

        $conf_invite_wap_app = $_POST['conf_invite_wap_app'];
        $conf_invite_pc = $_POST['conf_invite_pc'];
        $conf_invite = $conf_invite_wap_app . "," . $conf_invite_pc;

        $conf_platform_wap_app = $_POST['conf_platform_wap_app'];
        $conf_platform_pc = $_POST['conf_platform_pc'];
        $conf_platform = $conf_platform_wap_app . "," . $conf_platform_pc;


        $linkMoreH5 = trim($_POST['link_more_h5']);
        $linkMorePC = trim($_POST['link_more_pc']);

        $data['title'] = trim($_POST['title']);
        $data['invite_codes'] = trim($_POST['invite_codes']);
        $data['remark'] = trim($_POST['remark']);
        $data['link_more'] = $linkMoreH5 . "," . $linkMorePC;
        $data['conf_reg'] = $conf_reg;
        $data['conf_bid'] = $conf_bid;
        $data['conf_invite'] = $conf_invite;
        $data['conf_platform'] = $conf_platform;
        $data['create_time'] = $data['update_time'] = time();

        $inviteCodes= explode(",",$data['invite_codes']);
        $service = new NewUserPageService();
        $existCodes = $service->checkExistsInviteCodes(false,$inviteCodes);
        if(!empty($existCodes)){
            $this->error("邀请码:".implode(",",$existCodes) . " 已经在其它渠道配置，请勿重复配置");
        }

        $lastId=M(MODULE_NAME)->add($data);
        if (false !== $lastId) {
            NewUserPageService::updateInvitePageCache($inviteCodes,$lastId);
            $this->success(L("INSERT_SUCCESS"),0,u("NewUserPage/index"));
        } else {
            $this->error(L("INSERT_SUCCESS"),0,u("NewUserPage/index"));
        }
    }

    public function edit() {
        $id = intval($_REQUEST ['id']);
        $s = new NewUserPageService();
        $vo = $s->getPageInfoById($id);
        $this->assign('vo', $vo);

        $this->display();
    }

    public function update() {
        B('FilterString');
        $id = trim($_POST['id']);

        $data = M(MODULE_NAME)->create ();
        $conf_reg_wap_app = $_POST['conf_reg_wap_app'];
        $conf_reg_pc = $_POST['conf_reg_pc'];
        $conf_reg = $conf_reg_wap_app . "," . $conf_reg_pc;

        $conf_bid_wap_app = $_POST['conf_bid_wap_app'];
        $conf_bid_pc = $_POST['conf_bid_pc'];
        $conf_bid = $conf_bid_wap_app . "," . $conf_bid_pc;

        $conf_invite_wap_app = $_POST['conf_invite_wap_app'];
        $conf_invite_pc = $_POST['conf_invite_pc'];
        $conf_invite = $conf_invite_wap_app . "," . $conf_invite_pc;

        $conf_platform_wap_app = $_POST['conf_platform_wap_app'];
        $conf_platform_pc = $_POST['conf_platform_pc'];
        $conf_platform = $conf_platform_wap_app . "," . $conf_platform_pc;

        $linkMoreH5 = trim($_POST['link_more_h5']);
        $linkMorePC = trim($_POST['link_more_pc']);


        $data['title'] = trim($_POST['title']);
        $data['invite_codes'] = trim($_POST['invite_codes']);
        $data['remark'] = trim($_POST['remark']);
        $data['link_more'] = $linkMoreH5 . "," . $linkMorePC;
        $data['conf_reg'] = $conf_reg;
        $data['conf_bid'] = $conf_bid;
        $data['conf_invite'] = $conf_invite;
        $data['conf_platform'] = $conf_platform;
        $data['update_time'] = time();

        $pageService = new NewUserPageService();
        $pageInfo = $pageService->getPageInfoById($id);
        $oldInviteCodes = explode(",",$pageInfo['invite_codes']);
        $newInviteCodes = explode(",",$data['invite_codes']);

        $service = new NewUserPageService();
        $existCodes = $service->checkExistsInviteCodes($id,$newInviteCodes);
        if(!empty($existCodes)){
            $this->error("邀请码:".implode(",",$existCodes) . " 已经在其它渠道配置，请勿重复配置");
        }


        $list=M(MODULE_NAME)->save($data,array('id'=>$id));
        if (false !== $list) {
            //成功提示
            $inviteCodes = explode(",",$data['invite_codes']);
            $pageService->updateInvitePageCache($inviteCodes,$id);

            $filterInviteCodes = array_diff($oldInviteCodes,$newInviteCodes);
            if(!empty($filterInviteCodes)){
                $pageService->flashPages($filterInviteCodes);
            }
            $this->success(L("UPDATE_SUCCESS"));
        } else {
            //错误提示
            $this->error(L("UPDATE_FAILED"),0,L("UPDATE_FAILED"));
        }
    }


    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        $pageService = new \core\service\NewUserPageService();
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();

            foreach($rel_data as $data)
            {
                $info[] = $data['title'];
                $inviteCodes = explode(",",$data['invite_codes']);
                $pageService->flashPages($inviteCodes);
            }
            if($info) $info = implode(",",$info);
            $list = M(MODULE_NAME)->where ( $condition )->delete();
            if ($list!==false) {
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
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

    public function checkInvite(){
        $data = array('errno' => 0, 'msg' => '恭喜您，邀请码填写正确！');
        $pageId=trim($_REQUEST['page_id']) ? trim($_REQUEST['page_id']) : false;
        $inviteCodes = trim($_REQUEST['invite']);
        $inviteCodes = explode(",",$inviteCodes);
        $service = new NewUserPageService();
        $res = $service->checkExistsInviteCodes($pageId,$inviteCodes);

        if(!empty($res)){
            $data['errno'] = 1;
            $data['msg'] = '邀请码 '.implode(",",$res).' 在其它渠道存在重复，请仔细检查';
        }else{
            $invalidCodes = $service->isSiteInviteCodes($inviteCodes);
            if(!empty($invalidCodes)){
                $invalidStr = implode(",",$invalidCodes);

                $data['errno'] = 1;
                $data['msg'] = "存在无效邀请码:".$invalidStr;
            }
        }
        ajax_return($data);
    }
}
?>