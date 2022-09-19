<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------
//
use core\service\BonusService;
require_once(dirname(__FILE__) . "/../../../system/utils/phpqrcode.php");

class BonusActivityAction extends CommonAction {

    public $type_arr;
    public $load_limit_arr;
    public $status_arr;
    protected $model;
    private $url = '';

    public function __construct() {
        $this->model = new \core\dao\BonusActivityModel();
        $this->type_arr = \core\dao\BonusActivityModel::$type_arr;
        $this->load_limit_arr = \core\dao\BonusActivityModel::$load_limit_arr;
        $this->status_arr = \core\dao\BonusActivityModel::$status_arr;
        $this->url = app_conf('API_BONUS_SHARE_HOST');
        parent::__construct();
    }

    public function index() {
        //$this->error('该功能已经下线，请使用自定义红包任务进行发送!');
        $model = M(MODULE_NAME);
        if (!empty ($model)) {
            $this->_list($model);
        }
        $list = $this->get('list');

        foreach ($list as $k => &$item) {

            if ($item['is_diff_new_old_user'] == 1) {
                $item['is_diff_new_old_user'] = '是';
            } else {
                $item['is_diff_new_old_user'] = '否';
            }

            $item['type'] = $this->type_arr[$item['type']];
            $item['load_limit'] = $this->load_limit_arr[$item['load_limit']];
            $item['status'] = $this->status_arr[$item['status']];
            $item['range_money'] = "{$item['range_money_start']} - {$item['range_money_end']}";
            if ($item['is_fixed'] == 1) {
                $item['is_fixed'] = "是<br> {$item['multiple_money']}";
            } else {
                $item['is_fixed'] = "否<br>";
            }
            $item['link_invalid_date'] = date('Y-m-d H:i:s', $item['link_invalid_date']);
            $item['create_time'] = to_date($item['create_time']);
            $new_list[] = $item;
        }
        $this->assign('list', $new_list);
        $this->display();
       // parent::index();
    }

    public function add() {

        $bonus_service = new \core\service\BonusService();
        $temp_arr = $bonus_service->getBonusTemplates();
        $this->assign("type_arr", $this->type_arr);
        $this->assign("load_limit_arr", $this->load_limit_arr);
        $this->assign("temp_arr", $temp_arr);
        $this->display();
    }

    public function check() {
        if (empty($_REQUEST['name'])) {
            $this->error('规则名必填！');
        }
        if (empty($_REQUEST['link_invalid_date'])) {
            $this->error('红包有效期结束日期必填！');
        }

        if (empty($_REQUEST['valid_day'])) {
            $this->error('红包使用有效期必填！');
        }

        /*if (empty($_REQUEST['subject'])) {
            $this->error('活动标题必填!');
        }

        if (empty($_REQUEST['desc'])) {
            $this->error('活动描述必填!');
        }*/

        if ($_REQUEST['is_fixed'] == 1) {
            if (empty($_REQUEST['multiple_money'])) {
                $this->error("红包固定金额不能为空！");
            }
        } else {
            if (empty($_REQUEST['range_money_start']) || empty($_REQUEST['range_money_end'])) {
                $this->error("红包区间金额不能为空！");
            }
        }
    }

    public function insert() {

        $this->check();
        $file = $_FILES['icon'];
        /*if(empty($file) || $file['error'] != 0){
            $this->error('ICON上传失败！');
        }
        $result = uploadFile($file, 0, 1, '', false, '');
        if(empty($result['aid'])) {
            $this->error('上传失败！');
        } else {
            //$_POST['icon'] = $result['aid'];
            $_POST['icon'] = $result['full_path'];
        }*/
        $_POST['create_time'] = get_gmtime();

        $_POST['link_invalid_date'] = to_timespan(trim($_POST['link_invalid_date']));
        $count = intval($_REQUEST['count']);
        $bonus_service = new \core\service\BonusService();
        $group_id = $bonus_service->generation(0, 0, 0, 0.25, 0, $bonus_service::TYPE_ACTIVITY, 0, $count);
        M('BonusGroup')->where("id=$group_id")->save(array('expired_at' => $_POST['link_invalid_date'] + 28800));
        $_POST['group_id'] = intval($group_id);
        unset($_POST['count']);
        parent::insert();
        $this->redirect(u(MODULE_NAME."/index"));
    }

    public function update() {
        $this->check();
        $_POST['link_invalid_date'] = $expired_at = to_timespan(trim($_POST['link_invalid_date']));
        $count = intval($_REQUEST['count']);
        $group_id = intval($_REQUEST['group_id']);
        $result = M('BonusGroup')->where("id=$group_id")->save(array('count' => $count, 'expired_at' => $expired_at + 28800));
        if ($result) {
            $bonus_service = new BonusService();
            \SiteApp::init()->cache->delete(BonusService::CACHE_PREFIX_BONUS_GROUP . $group_id);
            \SiteApp::init()->dataCache->getRedisInstance()->del($bonus_service->encrypt($group_id, 'E'));
        }
        unset($_POST['group_id'], $_POST['count']);
        /*if (!empty($_FILES['icon']['tmp_name'])) {//修改图片
            $result = uploadFile($_FILES['icon'], 0, 1, '', false, '');
            if(empty($result['full_path'])) {
                $this->error('上传失败！');
            } else {
                $_POST['icon'] = $result['full_path'];
            }
        }*/
        parent::update();
    }

    public function edit() {
        $bonus_service = new \core\service\BonusService();
        $temp_arr = $bonus_service->getBonusTemplates();
        $bonus_group = $bonus_service->getGroupInfoById(M('BonusActivity')->where("id=".intval($_REQUEST['id']))->getField('group_id'));
        $item['count'] = $bonus_group['count'];
        $this->assign("link_invalid_date", $bonus_group['expired_at'] - 28800);
        $this->assign("group_id", $bonus_group['id']);
        $this->assign("bonus_count", $bonus_group['count']);
        $this->assign("type_arr", $this->type_arr);
        $this->assign("load_limit_arr", $this->load_limit_arr);
        $this->assign("temp_arr", $temp_arr);
        parent::edit();
    }

    protected function form_index_list(&$list) {

        $bonus_service = new BonusService();

        foreach ($list as &$item) {
            $bonus_group = $bonus_service->getGroupInfoById($item['group_id']);
            $item['link_invalid_date'] = $bonus_group['expired_at'];
            $item['count'] = $bonus_group['count'];
            $item['code'] = $bonus_group['encrypt'];
            $item['link'] = $this->url.'/hongbao/GetHongbao?sn='.$bonus_group['encrypt'];
            if ($item['cn'] != '') {
                $item['link'] .= "&cn=".$item['cn'];
            }
            if ($item['source'] != '') {
                $item['link'] .= "&from_platform=".$item['source'];
            }
        }
    }

    public function qrcode() {
        $data = $this->url.'/hongbao/GetHongbao?sn='.urlencode($_REQUEST['code']);
        if ($_REQUEST['cn'] != '') {
            $data .= "&cn=".$_REQUEST['cn'];
        }
        if ($_REQUEST['from_platform'] != '') {
            $data .= "&from_platform=".$_REQUEST['from_platform'];
        }
        $errorCorrectionLevel = 'H'; //L、M、Q、H
        $matrixPointSize = 5;
        \QRcode::png($data,false,$errorCorrectionLevel,$matrixPointSize, 1);
    }
}

?>
