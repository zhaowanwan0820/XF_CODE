<?php
/**
 *-------------------------------------------------------
 * 按照用户组信息以及标签信息批量给用户发送红包
 *-------------------------------------------------------
 * 2014-12-29 17:05:35
 *-------------------------------------------------------
 */

namespace core\event\Bonus;

use NCFGroup\Task\Events\AsyncEvent;
use core\dao\MsgBoxModel;
use core\dao\BonusJobsModel;
use core\dao\UserModel;
use core\dao\BonusConfModel;
use core\service\BonusJobsService;
use core\service\BonusService;
use libs\utils\Logger;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use NCFGroup\Task\Models\Task;
use core\event\BaseEvent;
use core\event\Bonus\BonusSingleEvent;
use core\service\bonus\BonusUser;
use core\event\Bonus\DiscountEvent;

/**
 * BonusBatchEvent
 * 批量发红包
 *
 * @uses AsyncEvent
 * @package default
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
class BonusTaskEvent extends BaseEvent
{

    private $pages = 1;
    private $count = 10000;
    public  $start_uid = 0;
    public  $start_id = 0;

    private $site_blacklist = '';

    public function __construct($id, $start_uid = 0) {
        $this->id = $id;
        $this->start_uid = intval($start_uid);
    }

    public function execute() {

        $id = intval($this->id);
        $task = \core\dao\BonusTaskModel::instance()->find($id);
        if (!$task) {
            throw new \Exception("任务不存在。");
        }

        if ($task['const_name'] == 1) {
            $bonus_task_limit_count = intval(BonusConfModel::get('BONUS_TASK_LIMIT_COUNT'));
            if ($bonus_task_limit_count <= 0) {
                $bonus_task_limit_count = 10;
            }
            $bonus_task_limit_money = intval(BonusConfModel::get('BONUS_TASK_LIMIT_MONEY'));
            if ($bonus_task_limit_money <= 0) {
                $bonus_task_limit_money = 5;
            }
            $bonus_task_limit_day = intval(BonusConfModel::get('BONUS_TASK_LIMIT_DAY'));
            if ($bonus_task_limit_day <= 0) {
                $bonus_task_limit_day = 7;
            }
            $bonus_task_limit_time = strtotime(date('Y-m-d', strtotime("-{$bonus_task_limit_day} days")));
            $bonus_task_limit_rule = trim(BonusConfModel::get('BONUS_TASK_LIMIT_RULE'));

            $this->site_blacklist = trim(BonusConfModel::get('BONUS_SUBSITE_SIGN_BLACKLIST'));
        }

        $log_file = APP_ROOT_PATH.'/log/logger/bonus_task_'.$id.'_'.date('Y-m-d').'.log';
        if($task['is_effect'] == 0) {
            Logger::wLog("msg=任务为无效状态\ttask=".json_encode($task), Logger::INFO, Logger::FILE, $log_file);
            throw new \Exception("无效的任务。");
        }

        $list = array();
        $file_type = 1;
        $data = array(
            'id' => $task['id'],
            'times' => $task['times'],
            'type' => $task['type'],
            'send_limit_day' => $task['send_limit_day'],
            'use_limit_day' => $task['use_limit_day'],
            'count' => $task['count'],
            'money' => $task['money'],
            'is_sms' => $task['is_sms'],
            'sms_temp_id' => $task['sms_temp_id'],
            'discount_group_id' => $task['extra']
        );

        switch ($task['type']) {
            case 1:
                $bonus_single_event = new BonusGroupEvent($data);
                break;
            case 2:
                $bonus_single_event = new BonusSingleEvent($data);
                break;
            case 3:
                $serialNo = 0;
                $bonus_single_event = new DiscountEvent($data);
                break;
            default:
                return true;
        }

        $gtask_service = new GTaskService();
        $task_count = 0;
        $date = date('Ymd', strtotime("-1 day"));
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        switch ($task['send_way']) {//根据配置的不同方式发送红包
            case 1 :
                $list = explode(',', $task['send_condition']);
                break;
            case 2 :
                $list = UserModel::instance()->getUserListByJob($task['send_condition'], '');
                break;
            case 3 :
                $tag_relation = substr($task['send_condition'], 0, 1) == 1 ? 1 : 0;
                $list = UserModel::instance()->getUserListByJob('', substr($task['send_condition'], 1), $tag_relation);
                break;
            case 4 :
                $file_type = substr($task['send_condition'], 0, 1);
                $static_host = app_conf('STATIC_HOST');
                $url = (substr($static_host, 0, 4) == 'http' ? '' : 'http:') . $static_host .'/' . substr($task['send_condition'], 1);
                list($url, $is_include_money) = explode('&', $url);
                $is_include_money = intval(substr($is_include_money, -1));
                $file = fopen($url, "r");
                $list = array();
                if (fgetcsv($file) != false) {// 去标题
                    while (!feof($file)) {
                        list($mobile_or_uid, $money) = fgetcsv($file);
                        $mobile_or_uid = trim($mobile_or_uid);
                        $money = trim($money);
                        if (is_numeric($mobile_or_uid)) {
                            if ($is_include_money && $money == '') {
                                continue;
                            }
                            //$list[] = array('user' => trim($mobile_or_uid), 'money' => trim($money));

                            //=======================大于一百万条数据直接发送Start=========
                            $send_user_money = $money;
                            if ($file_type == 1) {
                                $user_id = trim($mobile_or_uid);
                            } else {
                                $mobile = trim($mobile_or_uid);
                            }
                            $mobile = !empty($mobile) ? $mobile : '';
                            $bonus_single_event->setSendUser($user_id, $mobile);
                            if ($task['const_name'] == 1) {
                                if (BonusUser::checkRules($user_id, $bonus_task_limit_rule)) {
                                    if (!$redis->sIsMember('O2OCACHE_FIRST_DAY_DEAL_'.$date, $user_id)) {
                                        Logger::wLog("该用户被拦截uid=".$user_id, Logger::INFO, Logger::FILE, $log_file);
                                        continue;
                                    }
                                }
                                $bonus_single_event->is_sign = true;
                                $bonus_single_event->limit_count   = $bonus_task_limit_count;
                                $bonus_single_event->limit_money   = $bonus_task_limit_money;
                                $bonus_single_event->limit_start_time    = $bonus_task_limit_time;
                            }
                            if ($send_user_money && $is_include_money) {
                                $bonus_single_event->money = $send_user_money;
                            }
                            if ($task['type'] == 3) {
                                $serialNo = $serialNo + 1;
                                $bonus_single_event->serial_no = $serialNo;
                            }

                            $result = $gtask_service->doBackground($bonus_single_event, 1, TASK::PRIORITY_NORMAL, null, 'domq_bonus');
                            if ($result) {
                                $task_count++;
                                if ($task_count % 10000 == 0) {
                                    usleep(100000);
                                }
                            }
                            //=======================大于一百万条数据直接发送End=========
                        }
                    }
                }

                $list = array();
                fclose($file);
                break;
            case 5 :
            case 6 :
            case 7 :
                //$this->pages = 500;
                parse_str($task['send_condition'], $condition);
                break;
            default :
                throw new \Exception("发送类型错误。");
                return false;
        }
        for($page = 0; $page < $this->pages; $page++) {
            if ($task['send_way'] == 4) {
                break;
            }
            if ($task['send_way'] == 5) {
                $list = $this->getDealInfoUsers($condition, $page, $this->count);
            }
            if ($task['send_way'] == 6) {
                $list = $this->getRegUndealUsers($condition, $page, $this->count);
            }
            if ($task['send_way'] == 7) {
                $list = $this->getUndealUsers($condition, $page, $this->count);
            }
            if (empty($list)) {
                break;
            }
            foreach ($list as $user) {
                switch ($task['send_way']) {
                    case 1 :
                        $user_id = $user;
                        break;
                    case 2 :
                    case 3 :
                        $user_id = $user['id'];
                        $mobile  = $user['mobile'];
                        break;
                    case 4 :
                        if ($file_type == 1) {
                            $user_id = $user['user'];
                        } else {
                            $mobile = $user['user'];
                        }
                        $send_user_money = $user['money'];
                        break;
                    case 5:
                        $user_id = $user['user_id'];
                        break;
                    case 6:
                        $user_id = $user['id'];
                        break;
                    case 7:
                        $user_id = $user['user_id'];
                        break;
                    default :
                        continue;
                }

                $mobile = !empty($mobile) ? $mobile : '';
                $bonus_single_event->setSendUser($user_id, $mobile);
                if ($task['const_name'] == 1) {
                    if (BonusUser::checkRules($user_id, $bonus_task_limit_rule)) {
                        if (!$redis->sIsMember('O2OCACHE_FIRST_DAY_DEAL_'.$date, $user_id)) {
                            Logger::wLog("该用户被拦截uid=".$user_id, Logger::INFO, Logger::FILE, $log_file);
                            continue;
                        }
                    }
                    $bonus_single_event->is_sign = true;
                    $bonus_single_event->limit_count   = $bonus_task_limit_count;
                    $bonus_single_event->limit_money   = $bonus_task_limit_money;
                    $bonus_single_event->limit_start_time    = $bonus_task_limit_time;
                }
                if ($send_user_money && $is_include_money) {
                    $bonus_single_event->money = $send_user_money;
                }
                if ($task['type'] == 3) {
                    $serialNo = $serialNo + 1;
                    $bonus_single_event->serial_no = $serialNo;
                }
                $result = $gtask_service->doBackground($bonus_single_event, 1, TASK::PRIORITY_NORMAL, null, 'domq_bonus');
                if($result) {
                    $task_count++;
                }
            }
        }
        try {
            $errmsg = '';
            $status = \core\dao\BonusTaskModel::instance()->updateRows('UPDATE `firstp2p_bonus_task` SET status=2 WHERE id='. intval($task['id']));
        } catch (\Exception $e) {
            $errmsg = json_encode($e);
        }
        Logger::wLog("msg=任务执行成功\tstatus=$status\tcount=$task_count\ttask=".json_encode($task)."\terrmsg=$errmsg", Logger::INFO, Logger::FILE, $log_file);
        $this->noticeMail($task, $task_count);
        return true;
    }

    /**
     * 获取自定义红包数据，
     */
    private function getDealInfoUsers($condition, $page, $count) {
        $where = array();
        if ($condition['deal_time_end']) {
            $where[] = sprintf('`create_time` BETWEEN %s AND %s', intval($condition['deal_time_start']), intval($condition['deal_time_end']));
        }
        /*if ($condition['deal_money_end']) {
            $where[] = sprintf('money between %s and %s', intval($condition['deal_money_start']), intval($condition['deal_money_end']));
        }*/
        if (!empty($this->site_blacklist)) {
            $where[] = "site_id NOT IN ({$this->site_blacklist})";
        }
        if (!empty($where)) {
            $where = ' AND ' . implode(' AND ', $where);
        }
        if ($condition['deal_times_end']) {
            $where .= sprintf(' GROUP BY `user_id` HAVING COUNT(`user_id`) BETWEEN %s AND %s',
                     intval($condition['deal_times_start']), intval($condition['deal_times_end']));
        }
        if (empty($where)) {
            $where = '';
        }
        if ($page == 0) { //获取页数
            $max_info = \core\dao\DealLoadModel::instance()->findBySqlViaSlave('SELECT id FROM firstp2p_deal_load order by id desc LIMIT 1');
            $this->start_id = intval($max_info['id']) - 2000000;
            if ($this->start_id <= 0) {
                $this->start_id = 30000000;
            }
            $sql_count = sprintf('SELECT COUNT(DISTINCT(`user_id`)) FROM `firstp2p_deal_load` WHERE id > %s && user_id > %s %s', $this->start_id, $this->start_uid, $where);
            $pages = ceil(intval(\core\dao\DealLoadModel::instance()->countBySql($sql_count, array(), true)) / $this->count);
            if ($pages > 0) {
                $this->pages = $pages;
            }

        }
        $sql = sprintf('SELECT DISTINCT(`user_id`) FROM `firstp2p_deal_load` WHERE id > %s && user_id > %s %s ORDER BY user_id ASC LIMIT %s, %s', $this->start_id, $this->start_uid, $where, $page * $count, $count);
        return  \core\dao\DealLoadModel::instance()->findAllBySql($sql, true, array(), true);
    }

    /**
     * 注册未投资的用户
     *
     * @param mixed $condition
     * @param int $page
     * @param int $count
     * @access private
     * @return mixed
     */
    private function getRegUndealUsers($condition, $page, $count) {
        $start_time = $condition['reg_time_start'];
        $end_time   = $condition['reg_time_end'];
        if ($page == 0) {
            $sql_count = sprintf('SELECT COUNT(DISTINCT(U.id)) FROM `firstp2p_user` U LEFT JOIN `firstp2p_deal_load` D ON U.id = D.user_id WHERE D.id is null AND U.id > %s AND U.create_time BETWEEN %s AND %s', $this->start_uid, $start_time, $end_time);
            $pages = ceil(intval(\core\dao\DealLoadModel::instance()->countBySql($sql_count, array(), true)) / $this->count);
            if ($pages > 0) {
                $this->pages = $pages;
            }
        }
        $sql = 'SELECT DISTINCT(U.id) FROM `firstp2p_user` U LEFT JOIN `firstp2p_deal_load` D ON U.id = D.user_id WHERE D.id is null AND U.id > %s AND U.create_time BETWEEN %s AND %s ORDER BY U.id ASC LIMIT %s, %s';
        $sql = sprintf($sql, $this->start_uid, $start_time, $end_time, $page * $count, $count);

        return \core\dao\DealLoadModel::instance()->findAllBySql($sql, true, array(), true);
    }

    /**
     * 时间段内未投资用户
     *
     * @param mixed $condition
     * @param int $page
     * @param int $count
     * @access private
     * @return mixed
     */
    private function getUndealUsers($condition, $page, $count) {
        $start_time = $condition['not_deal_time_start'];
        $end_time   = $condition['not_deal_time_end'];
        if ($page == 0) {
            $sql_count = sprintf('SELECT COUNT(DISTINCT(A.user_id)) FROM firstp2p_deal_load A LEFT JOIN (select DISTINCT(user_id) FROM firstp2p_deal_load WHERE create_time BETWEEN %s AND %s) B ON A.user_id = B.user_id WHERE A.user_id > %s AND B.user_id is null', $start_time, $end_time, $this->start_uid);
            $pages = ceil(intval(\core\dao\DealLoadModel::instance()->countBySql($sql_count, array(), true)) / $this->count);
            if ($pages > 0) {
                $this->pages = $pages;
            }
        }
        $sql = 'SELECT DISTINCT(A.user_id) FROM firstp2p_deal_load A LEFT JOIN (SELECT DISTINCT(user_id) FROM firstp2p_deal_load WHERE create_time BETWEEN %s AND %s) B ON A.user_id = B.user_id WHERE B.user_id is null AND A.user_id > %s ORDER BY A.user_id ASC LIMIT %s, %s';
        $sql = sprintf($sql, $start_time, $end_time, $this->start_uid, $page * $count, $count);

        return \core\dao\DealLoadModel::instance()->findAllBySql($sql, true, array(), true);
    }

    public function noticeMail($task, $task_count) {

        $send_list = explode(',', \core\dao\BonusConfModel::get('BONUS_TASK_MAIL_LIST'));
        array_push($send_list, 'wangshijie@ucfgroup.com');
        array_push($send_list, 'zhangzhuyan@ucfgroup.com');
        $subject = "【红包TASK】" . $task['name'];

        $body = '<ul style="font-size:px;color:#1f497d;font-weight:bold;">';
        $body .= '<b style="color:red;">发送信息如下：</b>';
        $body .= "<div><b>任务ID: {$task['id']}</b></div>";
        if ($task['type'] == 1) {
            $body .= "<div><b>红包类型: 分享红包</b></div>";
            $body .= "<div><b>红包发送有效期: {$task['send_limit_day']}天</b></div>";
            $body .= "<div><b>红包个数: {$task['count']}个</b></div>";
        } elseif ($task['type'] == 2) {
            $body .= "<div><b>红包类型: 投资红包</b></div>";
        } else {
            $body .= "<div><b>红包类型: 投资劵</b></div>";
        }
        $body .= "<div><b>红包金额: {$task['money']}元</b></div>";
        $body .= "<div><b>红包使用有效期: {$task['use_limit_day']}天</b></div>";
        $body .= "<div><b>成功放入队列个数: {$task_count}个</b></div>";
        $body .= '</ul>';

        $msgcenter = new \Msgcenter();
        $msgcenter->setMsg(implode(',', $send_list), 0, $body, false, $subject);
        $msgcenter->save();

        //签到红包加入短信通知
        if ($task['const_name'] == 1) {
            $content = strip_tags($body);
            $mobiles = array('13601013563','18500132164');
            \libs\sms\SmsServer::sendAlertSms($mobiles,$content);
        }

    }

    public function alertMails() {
        return array('wangshijie@ucfgroup.com');
    }
}
