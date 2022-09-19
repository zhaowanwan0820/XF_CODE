<?php

namespace web\controllers\user;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\UserService;
use core\service\AccountService;
use libs\db\Db;
use NCFGroup\Common\Library\ApiService;

/**
 * @todo 客户接待临时使用代码，只需要部署到灰度就行，后期需要删除
 */
class Query extends BaseAction {
    public function init() {
        if (!$this->check_login()) {
            return false;
        }

        $this->form = new Form();
        $this->form->rules = array(
            'phone' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            return $this->show_error($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $GLOBALS['user_info'];

        // 李志坚 13501113093
        // 刘建江 18618153235
        // 邓小雨 18339266973
        // 鄢丙荣 15911068431
        // 李志坚 13903838377
        // 梁强 18600207300
        // $allowUserMobiles = array('13501113093', '18618153235', '18339266973', '15911068431', '13903838377', '14551851800');
        $allowUserMobiles = array('13501113093', '18618153235', '18339266973', '15911068431', '13903838377', '18600207300');
        if (!in_array($loginUser['mobile'], $allowUserMobiles)) {
            return $this->show_error('您没有权限访问', '非法请求', 0);
        }

        $db = Db::getInstance('firstp2p', 'slave');
        // 验证token
        if ($_SERVER['REQUEST_METHOD'] =='POST') {
            if (!$this->check_token()) {
                return $this->show_error('表单令牌不正确', '非法请求', 0);
            }

            if (empty($data['phone'])) {
                return $this->show_error('手机号不能为空', '参数不正确', 0);
            }

            $user = (new UserService())->getByMobile($data['phone'], 'id,real_name,idno,sex');
            if (empty($user)) {
                return $this->show_error('用户不存在', '请求非法', 0);
            }

            $user = $user->getRow();
            $userMoneyInfo = (new AccountService())->getUserSummaryNew($user['id']);

            // 网信余额显示
            $wxCashString = $wxFreezeString = $phCashString = $phFreezeString = 0;
            if (!empty($userMoneyInfo['wx_cash']) && bccomp($userMoneyInfo['wx_cash'], 0, 2) >= 0) {
                $wxCashString = $userMoneyInfo['wx_cash'];
            }

            if (!empty($userMoneyInfo['wx_freeze']) && bccomp($userMoneyInfo['wx_freeze'], 0, 2) >= 0) {
                $wxFreezeString = $userMoneyInfo['wx_freeze'];
            }

            if (!empty($userMoneyInfo['ph_cash']) && (int)$userMoneyInfo['ph_cash'] >= 0) {
                $phCashString = $userMoneyInfo['ph_cash'];
            }

            if (!empty($userMoneyInfo['ph_freeze']) && (int)$userMoneyInfo['ph_freeze'] >= 0) {
                $phFreezeString = $userMoneyInfo['ph_freeze'];
            }

            $corpusString = $incomeString = 0;
            if (!empty($userMoneyInfo['corpus'])) {
                if (bccomp($userMoneyInfo['corpus'], 10000, 2) >= 0) {
                    $corpusString = number_format(floatval(bcdiv($userMoneyInfo['corpus'], 10000, 2))) . '万';
                } else {
                    $corpusString = number_format(floatval($userMoneyInfo['corpus'])) . '&nbsp元';
                }
            }

            if (!empty($userMoneyInfo['income'])) {
                if (bccomp($userMoneyInfo['income'], 10000, 2) >= 0) {
                    $incomeString = number_format(floatval(bcdiv($userMoneyInfo['income'], 10000, 2))) . '万';
                } else {
                    $incomeString = number_format(floatval($userMoneyInfo['income'])) . '&nbsp元';
                }
            }

            $user['wx_cash_init'] = $wxCashString;
            $user['wx_freeze_init'] = $wxFreezeString;
            $user['ph_cash_init'] = $phCashString;
            $user['ph_freeze_init'] = $phFreezeString;
            $user['corpus'] = $corpusString;
            $user['income'] = $incomeString;
            $user['tmidno'] = mb_substr($user['idno'],-3,null,'utf-8');
            $sex = $user['sex']?'先生':'女士';
            $user['tmname'] = mb_substr($user['real_name'],0,1,'utf-8').$sex;

            $loanUserId = intval($user['id']);
            $repayTime = time() + 30 * 86400 - 8 * 3600;
            $repayMoneySql = "SELECT sum(`money`) AS repay_money FROM `firstp2p_deal_loan_repay` WHERE `loan_user_id`={$loanUserId} AND `status`=0 AND `time`<={$repayTime}";
            $repayMoneyRes = $db->getRow($repayMoneySql);
            $repayMoney = 0;
            if ($repayMoneyRes && !empty($repayMoneyRes['repay_money'])) {
                if (bccomp($repayMoneyRes['repay_money'], 10000, 2) >= 0) {
                    $repayMoney = number_format(floatval(bcdiv($repayMoneyRes['repay_money'], 10000, 2))) . '万';
                } else {
                    $repayMoney = number_format(floatval($repayMoneyRes['repay_money']));
                }
            }
            $user['wx_repay_money'] = $repayMoney.'&nbsp元';

            $repayCount = 0;
            $repayCountSql = "SELECT COUNT(DISTINCT `deal_id`) AS repay_count FROM `firstp2p_deal_loan_repay` WHERE `loan_user_id`={$loanUserId} AND `status`=0 AND `time`<={$repayTime}";
            $repayCountRes = $db->getRow($repayCountSql);
            if ($repayCountRes) {
                $repayCount = $repayCountRes['repay_count'];
            }
            $user['wx_repay_count'] = $repayCount;

            $phInvestInfo = ApiService::rpc("ncfph", "account/InvestInfo", ['userId'=>$loanUserId]);
            if (!$phInvestInfo) {
                return $this->show_error('请求用户普惠数据异常', '服务器异常', 0);
            }

            $phRepayMoney = 0;
            if (!empty($phInvestInfo['repay_money'])) {
                if (bccomp($phInvestInfo['repay_money'], 10000, 2) >= 0) {
                    $phRepayMoney = number_format(floatval(bcdiv($phInvestInfo['repay_money'], 10000, 2))) . '万';
                } else {
                    $phRepayMoney = number_format(floatval($phInvestInfo['repay_money']));
                }
            }
            $user['ph_repay_money'] = $phRepayMoney.'&nbsp元';
            $user['ph_repay_count'] = $phInvestInfo['repay_count'];

            $queryTimesSql = "SELECT COUNT(*) AS query_times FROM `firstp2p_vip_log` WHERE `user_id`={$loanUserId}";
            $queryTimesRes = $db->getRow($queryTimesSql);
            $user['query_times'] = $queryTimesRes ? $queryTimesRes['query_times'] + 1 : 1;

            $queryDateSql = "SELECT `create_time` FROM `firstp2p_vip_log` WHERE `user_id`={$loanUserId}";
            $queryDates = array();
            $queryDatesRes = $db->getAll($queryDateSql);
            if ($queryDatesRes) {
                foreach ($queryDatesRes as $item) {
                    $key = date('Y/m/d', $item['create_time']);
                    if (isset($queryDates[$key])) {
                        $queryDates[$key] = $queryDates[$key] + 1;
                    } else {
                        $queryDates[$key] = 1;
                    }
                }
            }

            $today = date('Y/m/d');
            if (isset($queryDates[$today])) {
                $queryDates[$today] = $queryDates[$today] + 1;
            } else {
                $queryDates[$today] = 1;
            }

            $queryDateString = '';
            foreach ($queryDates as $dateKey=>$dateTimes) {
                $queryDateString .= $dateKey.'['.$dateTimes.'], ';
            }
            $user['query_dates'] = rtrim($queryDateString, ', ');

            // 查询的用户需要落库备查
            Db::getInstance('firstp2p', 'master')->insert('firstp2p_vip_log', array(
                'user_id'=>$loanUserId,
                'log_type'=>10, // 接待查询
                'investing_money'=>$userMoneyInfo['corpus'],
                'investing_wx_money'=>bcadd($userMoneyInfo['wx_cash'], $userMoneyInfo['wx_freeze'], 2),
                'investing_fund_money'=>bcadd($userMoneyInfo['ph_cash'], $userMoneyInfo['wx_freeze'], 2),
                'investing_gold_money'=>$userMoneyInfo['income'],
                'create_time'=>time(),
                'note'=>$data['phone'].'|'.$loginUser['id']
            ));
        }

        $todayQueryTimesSql = "SELECT COUNT(distinct `user_id`) AS `today_query_times` FROM firstp2p_vip_log WHERE create_time>unix_timestamp(CURRENT_DATE())";
        $todayQueryTimesRes = $db->getRow($todayQueryTimesSql);
        $todayQueryTimes = $todayQueryTimesRes ? $todayQueryTimesRes['today_query_times'] : 0;
        $this->tpl->assign('today_query_times', $todayQueryTimes);
        $this->tpl->assign('user', empty($user) ? '' : $user);
        $this->tpl->assign('phone', $data['phone']);
    }

    /**
     * 验证表单令牌
     * 为了重新发送不调用app中的check_token方法
     * @author yutao
     * @param string $token_id
     * @return number 返回1为通过，0为失败
     */
    private function check_token($token_id = '') {
        $_REQUEST['token_id'] =  isset($_REQUEST['token_id']) ? $_REQUEST['token_id'] : false;
        $token_id = empty($token_id) ? $_REQUEST['token_id'] : $token_id;
        $token = isset($_REQUEST['token']) ? $_REQUEST['token'] : false;
        if (empty($token_id) || empty($token)) {
            return 0;
        }

        $k = 'ql_token_' . $token_id;
        if (isset($_SESSION[$k]) && $token == $_SESSION[$k]) {
            return 1;
        } else {
            return 0;
        }
    }
}
