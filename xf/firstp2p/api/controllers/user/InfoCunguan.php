<?php
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

class InfoCunguan extends AppBaseAction
{

    const IS_H5 = true;
    static $filter = array(
        '基金申购',
        '基金赎回',
        '基金到账',
        '基金申购成功',
        '基金申购失败',
        '基金扣款失败',
        '私募分红',
        '私募还本及分红',
    );
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
            'type' => array('filter'=>'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $request = $this->form->data;
        $loginUser = $this->getUserByToken();
        $this->tpl->assign("token", $request['token']);
        if (empty($loginUser)) {
            app_redirect('/user/login_cunguan');
            return false;
        }
        $stime = time() % 1800;
        if ($stime == 0) {
            $time = time();
        } else {
            $time = time() - $stime;
        }

        $moneylog = $this->rpc->local(
                    'UserLogService\get_user_log',
                    array(
                        array(0, 100),
                        $loginUser['id'],
                        'money',
                        false,
                        '',
                        0,
                        $time - 86400 - 8*3600
                        )
                    );
        $list = $moneylog['list'];
        $result = array();
        $money = $lockmoney = 0;
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if ($k == 0) {
                    $money = $v['remaining_money'];
                    $lockmoney = $v['remaining_total_money'] - $v['remaining_money'];
                }
                if (in_array($v['log_info'], self::$filter)) {
                    continue;
                }
                $result[$k]['id'] = $v['id'];
                $result[$k]['time'] = to_date($v['log_time']);
                $result[$k]['type'] = $v['log_info'];
                $result[$k]['money'] = format_price($v['showmoney'], false);
                $result[$k]['remark'] = $v['note'];
            }
        }
        $moneylist = $result;

        $data = array(
            "uid" => $loginUser['id'],
            "username" => $loginUser['user_name'],
            "name" => $loginUser['real_name'] ? $loginUser['real_name'] : "无",
            "money" => number_format($money, 2),
            "balance" => number_format($lockmoney, 2),
            "idno" => idnoFormat($loginUser['idno']),
            "idcard_passed" => $loginUser['idcardpassed'],
            "photo_passed" => $loginUser['photo_passed'],
            "mobile" => !empty($loginUser['mobile']) ? moblieFormat($loginUser['mobile']) : '无',
        );
        $this->tpl->assign("idno", $data['idno']);
        $this->tpl->assign("name", $data['name']);
        $this->tpl->assign("money", $data['money']);
        $this->tpl->assign("balance", $data['balance']);
        $this->tpl->assign("mobile", $data['mobile']);
        $this->tpl->assign("moneylist", $moneylist);
        $this->tpl->assign("time", date("Y-m-d H:i", $time));
        if ($request['type'] == 'pc') {
            $this->template = $this->getTemplate('info_cunguan_pc');
        }
    }
}
