<?php
/**
 *----------------------------------------------
 * 手动给用户发送红包（发送单个红包与红包组）
 * 1、抽奖后给中奖用户发送红包
 * 2、运营反馈，给用户补发红包
 *----------------------------------------------
 * @auther wangshijie<wangshijie@ucfgroup.com>
 *----------------------------------------------
 */
use libs\utils\Logger;
class BonusSendAction extends CommonAction {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
    }

    /**
     * 新增红包
     */
    public function add() {

        $this->error('该功能已经下线，请使用自定义红包任务进行发送!');
        /*$op_user = es_session::get(md5(conf("AUTH_KEY")));
        if (!empty($_POST)) {
            $now = time();
            $data = array();
            $data['owner_uid']   = intval($_POST['owner_uid']);
            $data['created_at']  = $now;
            $data['expired_at']  = $now + intval($_POST['use_limit_day']) * 86400;
            $data['status']      = 1;
            //$data['group_id']    = -1;
            $data['money']       = floatval($_POST['money']);
            if ($data['money'] > 1001) {
                $this->error('发送红包金额超过限制.');
            }
            if ($data['owner_uid'] <= 0 || $data['expired_at'] <= $now) {
                $this->error('数据错误');
            }
            $result = M('Bonus')->add($data);
            $log = array(
                'type' => 'BonusSend',
                'op_user_info' => sprintf('adm_id=%s|adm_name=%s', $op_user['adm_id'], $op_user['adm_name']),
                'send_info' => sprintf("owner_uid=%s|money=%s", $data['owner_uid'], $data['money']),
                'msg' => "后台操作红包发送",
                'result' => $result,
                'time' => time(),
            );
            Logger::wlog($log);
            Logger::wLog($log, Logger::INFO, Logger::FILE, LOG_PATH ."bonus_send_by_admin_" . date('Ymd') .'.log');
            $this->assign('waitSecond', 6);
            $this->success("成功发送红包。");
        }
        $this->display('add');*/
    }
}
