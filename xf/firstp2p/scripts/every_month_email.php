<?php
/**
 * 每月1号8点  发送 月结单邮件
 * 0 8 1 * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php every_month_email.php
 * @author zhanglei5 2014-6-1
 */

require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../libs/common/app.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';
require_once dirname(__FILE__).'/../system/libs/msgcenter.php';

/*
use core\service\UserService;
use core\service\DealLoadService;
use core\service\UserLogService;
use core\service\DealLoanRepayService;
use core\service\MonthlyMailService;
use libs\utils\Logger;
*/
use core\service\MonthlyMailService;
set_time_limit(0);

$mail = new MonthlyMailService();
$mail->run($argv);

exit;



class every_month_email {
    public $user_service;
    public $deal_load_service;
    public $user_log_service;
    public $deal_loan_repay_service;
    public $page_size = 500;
    public $day_size = 100000;
    public $page = 0;
    //过期时间 默认为28天
    public $expire_time = 0;


    function every_month_email() {
        $this->user_service  = new UserService();
        $this->deal_load_service = new DealLoadService();
        $this->user_log_service = new UserLogService();
        $this->deal_loan_repay_service = new DealLoanRepayService();
        $this->expire_time = 86400 * 28;
        $bill_user_day_size = app_conf('BILL_USER_DAY_SIZE');
        if (!empty($bill_user_day_size)){
            $this->day_size = $bill_user_day_size;
        }
    }

    function run($argv=array()) {
        $offset = 0;
        $arr_time = getTimeStartEnd('last_month');
        $year = date('Y',$arr_time['end']);
        $last_month = date('m',$arr_time['end']);
        $cache = \SiteApp::init()->cache;

        $site = 'http://'.$GLOBALS['sys_config']['STATIC_DOMAIN_NAME'].$GLOBALS['sys_config']["STATIC_DOMAIN_ROOT"];
        $version = app_conf('APP_SUB_VER');
        $main_site = 'http://'.$GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p'];

        $title = '网信电子对账单 - '.date('Y',$arr_time['end']).'年'.($last_month-0).'月份';
        $redis_key = "mb_".$year.'_'.$last_month;
        $cnt = 0;
        // 每天处理量
        $date_j = empty($argv[1]) ? date('j') : $argv[1];
        $start_id = (($date_j-1)*$this->day_size);
        $end_id = ($date_j == 1) ? $this->day_size : $date_j*$this->day_size;
        $logInfo = array();
        do {
            $offset = $this->page * $this->page_size;
            $list_uid = $this->user_service->getUserId($offset,$this->page_size,$start_id,$end_id);
            $msgcenter = new msgcenter();
            $uids = array();
            foreach ($list_uid as $val) {
                try {
                    $user_id = $val['id'];
                    $ruid = $cache->get($redis_key.'_'.$user_id);
                    $uids[] = $user_id;
                    if($ruid > 0) { //如果当前用户id在redis里代表已经处理过。
                        continue;
                    }
                    $data = array();
                    //判断如果是借款人
                    if($this->is_investor( $user_id)){  //  if($cnt > 0) { $msgcenter->save(); die;}
                        $data = $this->getData($user_id);
                        $log['email'] = $email = $data['user_info']['email'];
                        $data['site'] = $site;
                        $data['version'] = $version;
                        $data['main_site'] = $main_site;
                        $data['asset'] = \SiteApp::init()->asset;

                        $content = $this->makePage(APP_ROOT_PATH.'web/views/email/every_month_template.php', $data);
                        $msgcenter->setMsg($email, $user_id, $content, false, $title);
                        $cnt++;
                    }
                }catch (Exception $e) {
                    $logInfo[] = array(
                            'scripts/every_month_email',
                            'try_catch' => $e->getMessage()
                    );
                }
            }
            //记录跑到哪一页了
            if($msgcenter->save()){
                foreach($uids as $id) {
                    $rs = $cache->set($redis_key.'_'.$id,1,$this->expire_time);
                }
                if (!empty($uids)){
                    $logUids = array(
                        'uids' => json_encode($uids),
                        'scripts/every_month_email',
                    );
                    // 记录处理ids
                    Logger::wLog($logUids);
                    unset($logUids);
                }
                
            }
            $this->page++;
            unset($msgcenter);
        } while (!(count($list_uid) < $this->page_size));
        
        // 记录日志
        $logInfo[] = array(
                   'scripts/every_month_email',
                   'users_range_ids' => $start_id.','.$end_id,
        );
        Logger::wLog($logInfo);
    }

    /**
     * 组装页面
     * @param string $tpl
     * @param array $data
     * @return string
     */
    function makePage($tpl,$data){
        if(is_array($data)) {
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

    function getData($user_id) {
        $user_info = $this->user_service->getByFieldUser($user_id,'lock_money,money,email,real_name');
        //用户统计
        $user_statics = user_statics($user_id);
        //---合并普惠数据
        $user_statics_ncfph = (new \core\service\ncfph\AccountService())->getUserStat($user_id);
        $user_statics = \core\service\ncfph\AccountService::mergeP2P($user_statics, $user_statics_ncfph);
        //资产总额
        $user_statics['money_all'] = $user_info['money'] + $user_info['lock_money'] + $user_statics['stay'];
        $referrals = $this->user_log_service->getReferrals($user_id);
        //累计收益 = 已赚利息 + 违约金 + (邀请返利 + 注册返利)
        $data['earning_all'] = $user_statics['load_earnings'] + $user_statics['load_tq_impose'] + $referrals + $user_statics['load_yq_impose'] ;
        //上月收支明细
        $last = $this->user_log_service->getLastMonthAll($user_id);
        $data['last'] = $last;
        $data['user_statics'] = $user_statics;
        $data['user_info'] = $user_info;

        $arr_time = getTimeStartEnd('last_month');
        $data['prev_month_start'] = $arr_time['start'];
        $data['prev_month_end'] = $arr_time['end'];
        //上月投资总额
        $loan_sum = $this->deal_load_service->getTotalLoanMoneyByUserId($user_id,$arr_time['start'],$arr_time['end'],array(4,5));
        //---合并普惠数据
        $loan_sum_ncfph = (new \core\service\ncfph\AccountService())->getTotalLoanMoneyByUserId($user_id, $arr_time['start'],$arr_time['end'],array(4,5));
        $loan_sum = bcadd($loan_sum, $loan_sum_ncfph, 2);

        // 上个月收支明细
        $data['detail'] = $last['detail'];

        //当月回款计划
        $arr_time = getTimeStartEnd('cur_month');
        $start = $arr_time['start'];
        $end = $arr_time['end'];
        $repay_list = $this->deal_loan_repay_service->getRepayList($user_id,$start,$end,array(0,5),'api');
        //---合并普惠数据
        $repay_list_ncfph = (new \core\service\ncfph\AccountService())->getLoan($user_id,$start,$end,array(0,5),'api');
        $repay_list = $this->mergeData($repay_list, $repay_list_ncfph);
        $data['repay_list'] = $repay_list['list'];
        /* if(count($repay_list['list']) > 0 ) {
            $content .= $this->make_repay($repay_list['list']);
        } */
        $data['prev_month'] = date('m',$data['prev_month_end']) - 0;
        $data['cur_month'] = date('m',$end) - 0;
        $data['loan_sum'] = $loan_sum;    // 投资总额
        return $data;
    }


    /**
     * 判断是否为投资人
     * @param int $user_id
     * @return number
     */
    function is_investor($user_id) {
        $cnt = $this->deal_load_service->countByUserId($user_id);
        $cnt = intval($cnt);
        return $cnt;
    }


}
$obj = new every_month_email();
$obj->run($argv);






