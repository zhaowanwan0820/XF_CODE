<?php
/**
 *----------------------------------------------
 * 超级红包配置信息
 *----------------------------------------------
 * @auther wangshijie<wangshijie@ucfgroup.com>
 *----------------------------------------------
 */

class BonusSuperAction extends CommonAction {

    private $consume_types = array('1' => '仅限投资');
    private $super_templetes = array('xql' => '超级红包');

    public function __construct() {
        parent::__construct();
    }

    /**
     * 超级红包配置列表页面
     */
    public function index() {
        $this->error('该功能已经下线，请使用自定义红包任务进行发送!');
        $model = M(MODULE_NAME);
        if (!empty ($model)) {
            $this->_list($model);
        }
        $list = $this->get('list');

        foreach ($list as $k => &$item) {
            $item['consume_type'] = $this->consume_types[$item['consume_type']];
            $item['temp_id'] = $this->super_templetes[$item['temp_id']];
            $item['date_section'] = date('Y-m-d H:i:s', $item['start_time']) . "<br />" . date('Y-m-d H:i:s', $item['end_time']);
            $new_list[] = $item;
        }
        $this->assign('list', $new_list);
        $this->display();
    }

    /**
     * 超级红包新增配置页面
     */
    public function add() {
        if ($_POST) {
            if (!empty($_FILES['retweet_icon'])) {
                $file = $_FILES['retweet_icon'];
                if(empty($file) || $file['error'] != 0){
                    $this->error('ICON上传失败！');
                }
                $uploadFileInfo = array(
                    'file' => $file,
                    'isImage' => 1,
                    'asAttachment' => 1,
                );
                $result = uploadFile($uploadFileInfo);
                if(empty($result['aid'])) {
                    $this->error('上传失败！');
                } else {
                    $_POST['retweet_icon'] = $result['full_path'];
                }
            }
            $_POST['start_time'] = strtotime($_POST['start_time']);
            $_POST['end_time'] = strtotime($_POST['end_time']);
            $_POST['hour_section'] = $_POST['hour_start'] . "|" . $_POST['hour_end'];
            $result = parent::insert();
            \SiteApp::init()->cache->delete('BONUS_XQL_CONFIG_LIST');
            $this->redirect(u(MODULE_NAME."/index"));
        } else {
            $this->assign("consume_types", $this->consume_types);
            $this->assign("temp_arr", $this->super_templetes);
            $this->display('add');
        }
    }

    /**
     * 超级红包更新页面
     */
    public function update() {
        \SiteApp::init()->cache->delete('BONUS_XQL_CONFIG_LIST');
        $_POST['start_time'] = strtotime($_POST['start_time']);
        $_POST['end_time'] = strtotime($_POST['end_time']);
        $_POST['hour_section'] = $_POST['hour_start'] . "|" . $_POST['hour_end'];
        unset($_POST['retweet_icon'], $_POST['hour_start'], $_POST['hour_end']);
        if (!empty($_FILES['retweet_icon']['tmp_name'])) {//修改图片
            $uploadFileInfo = array(
                'file' => $_FILES['retweet_icon'],
                'isImage' => 1,
                'asAttachment' => 1,
            );
            $result = uploadFile($uploadFileInfo);
            if(empty($result['full_path'])) {
                $this->error('上传失败！');
            } else {
                $_POST['retweet_icon'] = $result['full_path'];
            }
        }
        parent::update();
    }

    /**
     * 超级红包编辑页面
     */
    public function edit() {
        $id = isset($_REQUEST [$this->pk_name]) ? trim($_REQUEST [$this->pk_name]) : "";
        if (empty($id)) {
            $this->error(l("INVALID_OPERATION"));
        }
        $condition[$this->pk_name] = $id;
        $vo = $this->model->where($condition)->find();
        $vo['start_time'] = date('Y-m-d H:i:s',$vo['start_time']);
        $vo['end_time'] = date('Y-m-d H:i:s',$vo['end_time']);
        $static_host = app_conf('STATIC_HOST');
        $vo['retweet_icon'] = (substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/'.$vo['retweet_icon'];
        $this->assign('vo', $vo);
        list($hour_start, $hour_end) = explode("|", $vo['hour_section']);
        $this->assign('hour_start', $hour_start);
        $this->assign('hour_end', $hour_end);
        $this->assign("consume_types", $this->consume_types);
        $item['count'] = $bonus_group['count'];
        $this->assign("temp_arr", $this->super_templetes);
        $this->display();
    }

    /**
     * 红包输出列表页面数据处理
     */
    protected function form_index_list(&$list) {
    }

}
