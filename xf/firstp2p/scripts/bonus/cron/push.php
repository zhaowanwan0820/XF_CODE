<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包推送
 *-----------------------------------------------------------------------
 *
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//ini_set('display_errors', 1);
//error_reporting(E_ERROR);

set_time_limit(0);
ini_set('memory_limit', '1024M');
require_once dirname(__FILE__).'/../../../app/init.php';

use core\dao\BonusConfModel;
use core\service\bonus\BonusPush;
use core\service\MsgBoxService;

class AppPush {

    /**
     * 消息对象
     */
    private $msgbox = null;

    /**
     * 消息推送类型
     */
    private $type = 30;

    /**
     * 消息推送成功数
     */
    public $succ_count = 0;

    /**
     * 配置信息
     */
    public $config = array();

    /**
     * 分页数量
     */
    public $count = 5000;

    /**
     * 推送间隔
     */
    public $push_hour = 5;

    public function __construct($push_hour = 5) {

        $this->msgbox = new MsgBoxService();
        $this->config = BonusPush::getConfig(BonusPush::WILL_EXPIRE_BONUS);
        if (!$this->config['switch']) {
            exit("推送已经关闭！\n");
        }

        if (isset($push_hour) && $push_hour > 0) {
            $this->push_hour = $push_hour;
        }

        if ($this->push_hour <= 0) {
            exit("距离下次推送时间间隔必须大于零！\n");
        }

        $this->start_expired = time() + $this->config['pre_min'] * 60;
        $this->end_expired = $this->start_expired + $this->push_hour * 3600;
    }

    public function run() {

        $max_info = \core\dao\BonusModel::instance()->findBySqlViaSlave('SELECT MAX(id) AS id FROM firstp2p_bonus');
        $start_id = intval($max_info['id']) - 2000000;
        if ($start_id <= 0) {
            $start_id = 200000000;
        }
        $count_sql = 'SELECT COUNT(distinct(owner_uid)) FROM %s WHERE id > %s && expired_at BETWEEN %s AND %s AND status=1 && owner_uid > 0';
        $count_sql = sprintf($count_sql, 'firstp2p_bonus', $start_id, $this->start_expired, $this->end_expired);
        $total_count = \core\dao\BonusModel::instance()->countBySql($count_sql, array(), true);
        $pages = intval(ceil($total_count / $this->count));

        for ($page = 0; $page < $pages; $page++) {
            $sql ='SELECT owner_uid, SUM(money) AS total_money, COUNT(owner_uid) AS total_count FROM %s WHERE id > %s && expired_at BETWEEN %s AND %s AND status=1 && owner_uid > 0 GROUP BY owner_uid ASC';
            $sql .= ' LIMIT %s, %s';
            $sql = sprintf($sql, 'firstp2p_bonus', $start_id, $this->start_expired, $this->end_expired, ($page * $this->count), $this->count);
            $users = $GLOBALS['db']->get_slave()->getAll($sql);
            foreach($users as $user) {
                $content = sprintf($this->config['content'], $user['total_count'], $user['total_money'], $this->push_hour);
                if ($user['total_money'] < $this->config['min_money']) {
                    continue;
                }
                $result = $this->msgbox->create($user['owner_uid'], $this->type, $this->config['title'], $content);
                //$result = $this->msgbox->create($user['owner_uid'], $this->type, $this->config['title'], $content);
                //if ($result) {
                //    $this->succ_count;
                //}
            }
        }
    }
}

$push_hour = 5;
if ($argv[1] > 0) {
    $push_hour = $argv[1];
}
$push = new AppPush($push_hour);
$push->run();
echo "done.\n";
//echo "共成功推送消息：", $push->succ_count, "\n";

