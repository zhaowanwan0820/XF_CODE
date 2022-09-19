<?php

/**
 * MonthMailService class file.
 * @author pengchanglu <pengchanglu@ucfgroup.com>
 * */

namespace core\service;

use core\service\UserService;
use core\service\DealLoadService;
use core\service\UserLogService;
use core\service\DealLoanRepayService;
use core\service\UserThirdBalanceService;
use core\dao\UserModel;
use libs\utils\Logger;
use libs\utils\Finance;

class MonthlyMailService extends BaseService {

    public $user_service;
    public $deal_load_service;
    public $user_log_service;
    public $deal_loan_repay_service;
    public $user_third_balance_service;
    public $page_size = 500;
    public $day_size = 100000;
    public $page = 0;
    //过期时间 默认为28天
    public $expire_time = 0;

    public $site; //站点
    public $version; //版本
    public $main_site; //url 地址
    public $title; // 标题

    function __construct() {
        $this->user_service = new UserService();
        $this->deal_load_service = new DealLoadService();
        $this->user_log_service = new UserLogService();
        $this->deal_loan_repay_service = new DealLoanRepayService();
        $this->user_third_balance_service = new UserThirdBalanceService();
        $this->expire_time = 86400 * 28;
        $bill_user_day_size = app_conf('BILL_USER_DAY_SIZE');
        if (!empty($bill_user_day_size)) {
            $this->day_size = $bill_user_day_size;
        }

        $arr_time = getTimeStartEnd('last_month');
        $last_month = date('m', $arr_time['end']);

        $this->site = 'http://' . $GLOBALS['sys_config']['STATIC_DOMAIN_NAME'] . $GLOBALS['sys_config']["STATIC_DOMAIN_ROOT"];
        $this->version = app_conf('APP_SUB_VER');
        $this->main_site = 'http://' . $GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p'];
        $this->title = '网信电子对账单 - ' . date('Y', $arr_time['end']) . '年' . ($last_month - 0) . '月份';
    }

    function run($argv = array()) {
        $arr_time = getTimeStartEnd('last_month');
        $year = date('Y', $arr_time['end']);
        $last_month = date('m', $arr_time['end']);
        $redis_key = "mb_" . $year . '_' . $last_month;
        $log_info = array(__CLASS__, __FUNCTION__, APP, json_encode($arr_time), $year, $last_month);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        $cache = \SiteApp::init()->cache;
        // 每天处理量
        $date_j = empty($argv[1]) ? date('j') : $argv[1];
        $start_id = (($date_j - 1) * $this->day_size);
        $end_id = ($date_j == 1) ? $this->day_size : $date_j * $this->day_size;
        $log_info = array_merge($log_info, array("date:{$date_j}", "start_id:{$start_id}", "end_id:{$end_id}"));;
        do {
            $offset = $this->page * $this->page_size;
            $list_uid = $this->user_service->getUserId($offset, $this->page_size, $start_id, $end_id);
            $uids = array();
            foreach ($list_uid as $val) {
                $user_id = $val['id'];
                $uids[] = $user_id;
                if ($cache->get($redis_key . '_' . $user_id)) { //如果当前用户id在redis里代表已经处理过。
                    continue;
                }
                if($this->sendMailByUserid($user_id,$year.$last_month,true)){
                    $cache->set($redis_key . '_' . $user_id, 1, $this->expire_time);
                }
            }
            //记录跑到哪一页了
            Logger::info(implode(" | ", array_merge($log_info, array("offset:{$offset}", 'done', json_encode($uids)))));
            $this->page++;

        } while (!(count($list_uid) < $this->page_size));

        // 记录日志
        Logger::info(implode(" | ", array_merge($log_info, array("done"))));
    }

    /**
     * 组装页面
     * @param string $tpl
     * @param array $data
     * @return string
     */
    function makePage($tpl, $data) {
        if (is_array($data)) {
            extract($data);
        }
        ob_start();
        include($tpl);
        $page = ob_get_contents();
        ob_end_clean();
        return $page;
    }

    function mergeData($wxData, $phData) {
        $data = ['list' => []];

        $data['status'] = $wxData['status'];
        $data['type'] = $wxData['type'];
        $data['counts'] = intval($wxData['counts']) + intval($phData['counts']);

        foreach ($wxData['list'] as $item) {
            $data['list'][] = $item;
        }

        foreach ($phData['list'] as $item) {
            $data['list'][] = $item;
        }

        return $data;
    }

    /**
     * 生成对账单数据
     *
     * @param $user_id 用户id
     * @param $month 年月，201502
     * @return bool
     */
    function getData($user_id, $month) {
        $log_info = array(__CLASS__, __FUNCTION__, APP, $user_id, $month);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        if (empty($user_id) || empty($month)) {
            return false;
        }
        $user_info = $this->user_service->getByFieldUser($user_id, 'lock_money,money,email,real_name,user_purpose');
        //用户统计
        $user_statics = user_statics($user_id);
        //---合并普惠数据
        $user_statics_ncfph = (new \core\service\ncfph\AccountService())->getUserStat($user_id);
        $user_statics = \core\service\ncfph\AccountService::mergeP2P($user_statics, $user_statics_ncfph);

        //存管金额
        //$balanceResult = $this->user_third_balance_service->getUserSupervisionMoney($user_id);
        $accountInfo = (new \core\service\ncfph\AccountService())->getInfoByUserIdAndType($user_id, $user_info['user_purpose']);
        $balanceResult['supervisionBalance'] = $accountInfo['money'];
        $balanceResult['supervisionLockMoney'] = $accountInfo['lockMoney'];
        $balanceResult['supervisionMoney'] = $accountInfo['totalMoney'];

        //资产总额(去掉待收收益)
        $user_statics['money_all'] = Finance::addition(array($user_info['money'], $user_info['lock_money'], $user_statics['new_stay']), 2);
        $referrals = $this->user_log_service->getReferrals($user_id);
        //累计收益 = 已赚利息 + 违约金 + (邀请返利 + 注册返利)
        //$data['earning_all'] = $user_statics['load_earnings'] + $user_statics['load_tq_impose'] + $referrals + $user_statics['load_yq_impose'];
        $data['earning_all'] = $user_statics['earning_all'];
        //上月收支明细
        $last = $this->user_log_service->getLastMonthAll($user_id, $month);
        $data['last'] = $last;
        $data['user_statics'] = $user_statics;
        $data['user_info'] = $user_info;

        //加上存管金额
        $data['moneyInfo'] = [
            'allMoney' => Finance::addition(array($user_statics['money_all'], $balanceResult['supervisionMoney'])),//资产总额
            'usableMoney' => Finance::addition(array($user_info['money'], $balanceResult['supervisionBalance'])),//可用金额
            'freezeMoney' => Finance::addition(array($user_info['lock_money'], $balanceResult['supervisionLockMoney'])),//冻结金额
            'principalMoney' => $user_statics['principal'],//待收本金
            'interestMoney' => $user_statics['interest'],//待收收益
        ];

        $arr_time = getMonthStartEnd($month . '01'); //转为时间戳
        $data['prev_month_start'] = $arr_time['start'];
        $data['prev_month_end'] = $arr_time['end'];
        //上月投资总额
        $loan_sum = $this->deal_load_service->getTotalLoanMoneyByUserId($user_id, $arr_time['start'], $arr_time['end'], array(4, 5));
        //---合并普惠数据
        $loan_sum_ncfph = (new \core\service\ncfph\AccountService())->getTotalLoanMoneyByUserId($user_id, $arr_time['start'],$arr_time['end'],array(4,5));
        $loan_sum = bcadd($loan_sum, $loan_sum_ncfph, 2);

        // 上个月收支明细
        $data['detail'] = $last['detail'];

        //当月回款计划
        //$arr_time = getTimeStartEnd('cur_month');
        $next_month = strtotime("+1 Month",strtotime($month.'01'));
        $arr_time = getMonthStartEnd(date("Ymd",$next_month));
        $start = $arr_time['start'];
        $end = $arr_time['end'];
        $repay_list = $this->deal_loan_repay_service->getRepayList($user_id, $start, $end, array(0, 5), 'api');
        //---合并普惠数据
        $repay_list_ncfph = (new \core\service\ncfph\AccountService())->getLoan($user_id,$start,$end,array(0,5),'api');
        $repay_list = $this->mergeData($repay_list, $repay_list_ncfph);
        $data['repay_list'] = $repay_list['list'];
        /* if(count($repay_list['list']) > 0 ) {
            $content .= $this->make_repay($repay_list['list']);
        } */
        $data['prev_month'] = date('m', $data['prev_month_end']) - 0;
        $data['cur_month'] = date('m', $end) - 0;
        $data['loan_sum'] = $loan_sum; // 投资总额
        Logger::info(implode(" | ", array_merge($log_info, array('done'))));
        return $data;
    }

    /**
     * 根据参数获取用户当月对账单内容
     *
     * @param $user_id 用户id
     * @param $month 年月，201502
     * @return bool
     */
    public function getBillContent($user_id, $month) {
        $log_info = array(__CLASS__, __FUNCTION__, APP, $user_id, $month);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        if (empty($user_id) || empty($month)) {
            Logger::info(implode(" | ", array_merge($log_info, array('error params'))));
            return false;
        }

        //判断是否借款人
        if (!$this->isInvestor($user_id)) {
            Logger::info(implode(" | ", array_merge($log_info, array('is investor, not pass'))));
            return false;
        }

        $data = $this->getData($user_id, $month);
        $log['email'] = $email = $data['user_info']['email'];
        $data['site'] = $this->site;
        $data['version'] = $this->version;
        $data['main_site'] = $this->main_site;
        $data['asset'] = \SiteApp::init()->asset;
        $content = $this->makePage(APP_ROOT_PATH . 'web/views/email/every_month_template.php', $data);
        return array('email' => $email, 'content' => $content);
    }

    /**
     * 根据user_id和年月 发送当月对账邮件
     * @param $user_id 用户id
     * @param $month 年月，201502
     */
    public function sendMailByUserid($user_id, $month,$is_every_month=false) {
        $this->title = '网信电子对账单 - ' . date('Y', strtotime($month.'01')) . '年' . date('n', strtotime($month.'01')) . '月份';
        $log_info = array(__CLASS__, __FUNCTION__, APP, $user_id, $month);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        if (empty($user_id) || empty($month)) {
            Logger::info(implode(" | ", array_merge($log_info, array('error params'))));
            return false;
        }

        if($is_every_month){
            try {
                // 检查用户email订阅设置
                $msg_config_service = new \core\service\MsgConfigService();
                $not_send_sms = $msg_config_service->checkIsSendEmail($user_id, 'content_monthlyMail');
                if ($not_send_sms) {
                    return true;
                }
            }catch (\Exception $e){
                Logger::info(implode(" | ", array_merge($log_info, array('check user email msg config fail'))));
            }
        }
        $rs = $this->getBillContent($user_id, $month);
        if ($rs) {
            \libs\utils\Monitor::add("EMAIL_BILL_START",1);//开始邮件
            try{
                $msgcenter = new \msgcenter();
                $msgcenter->setMsg($rs['email'], $user_id, $rs['content'], false, $this->title);
                if ($msgcenter->save()) {
                    \libs\utils\Monitor::add("EMAIL_BILL_SUCCEED",1);//成功邮件
                    return true;
                }else{
                    \libs\utils\Monitor::add("EMAIL_BILL_FAIL",1);//失败邮件
                    return false;
                }
            }catch (\Exception $e) {
                \libs\utils\Monitor::add("EMAIL_BILL_FAIL",1);//失败邮件
                return false;
            }
            unset($msgcenter);
        } else {
            Logger::info(implode(" | ", array_merge($log_info, array($user_id, 'getBillContent fail'))));
            return false;
        }
    }

    /**
     * 判断是否为投资人
     * @param int $user_id
     * @return number
     */
    function isInvestor($user_id) {
        $cnt = $this->deal_load_service->countByUserId($user_id);
        $cnt = intval($cnt);
        if ($cnt > 0) {
            return $cnt;
        }
        $cnt = (new \core\service\ncfph\AccountService())->getDealLoadCount($user_id);
        $cnt = intval($cnt);
        return $cnt;
    }
}
