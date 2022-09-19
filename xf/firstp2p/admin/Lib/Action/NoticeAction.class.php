<?php
use core\service\PushService;
use core\service\MsgBoxService;
use libs\utils\Logger;

class NoticeAction extends CommonAction
{
    private $noticeModel = null;

    public function __construct()
    {
        $this->noticeModel = M('Notice', 'Model', false, 'msg_box', 'master');
        parent::__construct();
    }

    public function index()
    {
        if (isset($_REQUEST['type'])) {
            $condition['type'] = intval($_REQUEST['type']);
        }
        $condition['status'] = 1;
        if (isset($_REQUEST['status'])) {
            $condition['status'] = intval($_REQUEST['status']);
        }
        if (!empty($this->noticeModel)) {
            $this->_list($this->noticeModel, $condition, 'create_time');
        }
        $this->display();
        return;
    }
    public function trash()
    {
        $condition['status'] = 0;
        if (!empty($this->noticeModel)) {
            $this->_list($this->noticeModel, $condition, 'create_time');
        }
        $this->display();
        return;
    }

    public function add()
    {
        C('TOKEN_ON',true);
        $this->assign("default_send_time",to_date(get_gmtime()+3600*24*7));
        $this->display();
    }

    public function insert() {
        \libs\utils\PhalconRPCInject::init();
        C('TOKEN_ON',true);
        if(!isset($_SESSION[C('TOKEN_NAME')])) {
            $this->redirect(u(MODULE_NAME."/index"));
        }
        $data = $this->noticeModel->create();
        if (empty($data['title'])) {
            $this->error("title不能为空");
            return;
        }
        if (empty($data['content'])) {
            $this->error("content不能为空");
            return;
        }
        if (!empty($data['url'])) {
            $data['url'] = trim($data['url']);
        }
        $type = intval($data['type']) ?: 0;
        if ($type == 1) {
            if (!empty($data['target'])) {
                $userIds = str_replace(
                    array('，','|',' ',"\t","\n","\r\n"),
                    ',',
                    trim($data['target'])
                );
                $userIdsArr = explode(',', $userIds);
            }
            if (empty($userIdsArr) || (count($userIdsArr) > 5000)) {
                $this->error("user ids 不能为空或者大于5000个");
                return;
            } else {
                $data['target'] = $userIds;
            }
        } else {
            $data['target'] = '';
            $data['exclude_site'] = trim($data['exclude_site']);
        }
        $data['create_time'] = time();
        if ($this->noticeModel->add($data)) {
            save_log("Add_Notice:".implode('|',$data), 1);
            if ($type == 1) {
                $msgBoxService = new MsgBoxService();
                $msgBoxService->create($userIdsArr, 40, $data['title'], $data['content']);
            } else {
                $pushService = new PushService();
                $pushService->toAll($data['title'], $data['content']);
            }
        }
        $this->redirect(u(MODULE_NAME."/index"));
    }

    public function delete() {
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST['id'];
        if (!empty($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            if ($rel_data = $this->noticeModel->where($condition)->findAll()) {
                $list = $this->noticeModel->where($condition)->setField('status', 0);
            }
            if (!empty($list)) {
                save_log($id.l("DELETE_SUCCESS"), 1);
                $this->success(l("DELETE_SUCCESS"), $ajax);
            } else {
                save_log($id.l("DELETE_FAILED"), 0);
                $this->error(l("DELETE_FAILED"), $ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"), $ajax);
        }
    }
}
