<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

use core\service\MonthlyMailService;
use core\dao\UserModel;

class MailListAction extends CommonAction {
    public function index() {
        //输出团购城市
        $city_list = M("DealCity")->where('is_delete = 0')->findAll();
        $city_list = D("DealCity")->toFormatTree($city_list, 'name');
        $this->assign("city_list", $city_list);

        parent::index();
    }

    public function add() {
        //输出团购城市
        $city_list = M("DealCity")->where('is_delete = 0')->findAll();
        $city_list = D("DealCity")->toFormatTree($city_list, 'name');
        $this->assign("city_list", $city_list);
        $this->display();
    }

    public function edit() {
        $id = intval($_REQUEST ['id']);
        $condition['id'] = $id;
        $vo = M(MODULE_NAME)->where($condition)->find();
        $this->assign('vo', $vo);

        //输出团购城市
        $city_list = M("DealCity")->where('is_delete = 0')->findAll();
        $city_list = D("DealCity")->toFormatTree($city_list, 'name');
        $this->assign("city_list", $city_list);
        $this->display();
    }


    public function foreverdelete() {
        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = $_REQUEST ['id'];
        if (isset ($id)) {
            $condition = array('id' => array('in', explode(',', $id)));
            $rel_data = M(MODULE_NAME)->where($condition)->findAll();
            foreach ($rel_data as $data) {
                $info[] = $data['mail_address'];
            }
            if ($info) $info = implode(",", $info);
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

    public function insert() {
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $data = M(MODULE_NAME)->create();

        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/add"));
        if (!check_empty($data['mail_address'])) {
            $this->error(L("MAIL_ADDRESS_EMPTY_TIP"));
        }
        if (!check_email($data['mail_address'])) {
            $this->error(L("MAIL_ADDRESS_ERROR_FORMAT_TIP"));
        }
        if ($data['city_id'] == 0) {
            $this->error(L("MAIL_CITY_EMPTY_TIP"));
        }
        if (M("MailList")->where("mail_address='" . $data['mail_address'] . "'")->count() > 0) {
            $this->error(L("MAIL_ADDRESS_EXIST_TIP"));
        }
        // 更新数据
        $log_info = $data['mail_address'];
        $list = M(MODULE_NAME)->add($data);
        if (false !== $list) {
            //成功提示
            save_log($log_info . L("INSERT_SUCCESS"), 1);
            $this->success(L("INSERT_SUCCESS"));
        } else {
            //错误提示
            save_log($log_info . L("INSERT_FAILED"), 0);
            $this->error(L("INSERT_FAILED"));
        }
    }

    public function update() {
        B('FilterString');
        $data = M(MODULE_NAME)->create();

        $log_info = M(MODULE_NAME)->where("id=" . intval($data['id']))->getField("mail_address");
        //开始验证有效性
        $this->assign("jumpUrl", u(MODULE_NAME . "/edit", array("id" => $data['id'])));
        if (!check_empty($data['mail_address'])) {
            $this->error(L("MAIL_ADDRESS_EMPTY_TIP"));
        }
        if (!check_email($data['mail_address'])) {
            $this->error(L("MAIL_ADDRESS_ERROR_FORMAT_TIP"));
        }
        if ($data['city_id'] == 0) {
            $this->error(L("MAIL_CITY_EMPTY_TIP"));
        }
        if (M("MailList")->where("mail_address='" . $data['mail_address'] . "' and id<>" . $data['id'])->count() > 0) {
            $this->error(L("MAIL_ADDRESS_EXIST_TIP"));
        }
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


    public function set_effect() {
        $id = intval($_REQUEST['id']);
        $ajax = intval($_REQUEST['ajax']);
        $info = M(MODULE_NAME)->where("id=" . $id)->getField("mail_address");
        $c_is_effect = M(MODULE_NAME)->where("id=" . $id)->getField("is_effect"); //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        M(MODULE_NAME)->where("id=" . $id)->setField("is_effect", $n_is_effect);
        save_log($info . l("SET_EFFECT_" . $n_is_effect), 1);
        $this->ajaxReturn($n_is_effect, l("SET_EFFECT_" . $n_is_effect), 1);
    }


    public function export_csv($page = 1) {
        set_time_limit(0);
        $limit = (($page - 1) * intval(app_conf("BATCH_PAGE_SIZE"))) . "," . (intval(app_conf("BATCH_PAGE_SIZE")));

        $city_id = intval($_REQUEST['city_id']);

        if ($city_id > 0)
            $list = M(MODULE_NAME)->where("city_id = " . $city_id)->limit($limit)->findAll();
        else
            $list = M(MODULE_NAME)->limit($limit)->findAll();

        if ($list) {
            register_shutdown_function(array(&$this, 'export_csv'), $page + 1);

            $list_value = array('mail_address' => '""');
            if ($page == 1) $content = "";

            foreach ($list as $k => $v) {

                $list_value['mail_address'] = iconv('utf-8', 'gbk', $v['mail_address']);


                $content .= implode(",", $list_value) . "\n";
            }


            header("Content-Disposition: attachment; filename=mail_list.csv");
            echo $content;
        } else {
            if ($page == 1)
                $this->error(L("NO_RESULT"));
        }
    }

    public function import_csv() {
        //输出团购城市
        $city_list = M("DealCity")->where('is_delete = 0')->findAll();
        $city_list = D("DealCity")->toFormatTree($city_list, 'name');
        $this->assign("city_list", $city_list);
        $this->display();
    }

    public function do_import_csv() {
        $file = $_FILES['file'];
        $city_id = intval($_REQUEST["city_id"]);
        $content = @file_get_contents($file['tmp_name']);
        $content = explode("\n", $content);

        $count = 0;
        foreach ($content as $k => $v) {
            if ($v != '') {
                $data = array();
                $data['mail_address'] = $v;
                $data['city_id'] = $city_id;
                if (!M(MODULE_NAME)->where($data)->find()) {
                    $data['is_effect'] = 1;
                    $res = M(MODULE_NAME)->add($data);
                    if ($res) {
                        $count++;
                    }
                }
            }
        }
        save_log(sprintf(L("IMPORT_MAIL_SUCCESS"), $count), 1);
        $this->success(sprintf(L("IMPORT_MAIL_SUCCESS"), $count));
    }

    /**
     * 补发月对账单
     */
    function sendMonthMail() {
        //$min_month = mktime(-8, 0, 0, date('m'),date('d')-93,date("Y"));
       // $this->assign("min_month", date('Y-m' , $min_month));
        $this->display();
    }

    /**
     * 补发月对账单
     */
    function doSendMonthMail() {
        $user_name = trim($_POST['user_name']);
        $month = ltrim(trim($_POST['month']), '0');
        if (!$user_name) {
            $this->error('用户名不正确！');
        }
        if (strlen($month) != 6 || !preg_match("#\d{6}#", $month)) {
            $this->error('输入时间格式不合法！');
        }
        //当月第一天凌晨
        $current_month = mktime(-8, 0, 0, date("m"), 1, date("Y"));
        //输入的月份
        $stat_time = to_timespan($month . '01');
        if (!$stat_time || $stat_time >= $current_month) {
            $this->error('输入时间不合法！');
        }
        //由于 user_log 表迁移了数据，导致不能补发三个月之前的对账单
      /*  $min_month = mktime(-8, 0, 0, date('m'),date('d')-93,date("Y"));
        $min_month = strtotime(date('Y-m',$min_month).'-01');
        if ($stat_time <= $min_month) {
            $this->error('不能补发'.date('Y',$min_month).'年'.date('m',$min_month).'月之前的对账单！');
        }*/
        $user = new UserModel();
        $user_info = $user->getInfoByName($user_name);
        if (!$user_info) {
            $this->error('用户名不存在！');
        }
        $month_mail = new MonthlyMailService();
        $rs = $month_mail->sendMailByUserId($user_info->id, $month);
        if ($rs) {
            $this->success('发送对账单成功！');
        }
        $this->error('发送对账单失败！');
    }
}

?>