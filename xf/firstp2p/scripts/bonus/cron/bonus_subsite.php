<?php
/**
 *-----------------------------------------------------------------------
 * 1、分站签到红包发送脚本
 *-----------------------------------------------------------------------.
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */

//ini_set('display_errors', 1);
//error_reporting(E_ERROR);

set_time_limit(0);
ini_set('memory_limit', '512M');
require_once dirname(__FILE__).'/../../../app/init.php';

use NCFGroup\Task\Models\Task;
use core\event\Bonus\BonusTaskEvent;
use libs\lock\LockFactory;
use libs\utils\Logger;
use core\dao\BonusModel;
use core\dao\BonusConfModel;
use core\service\BonusService;

$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
$lock_key = 'bonus_subsite_script';
$lock->releaseLock($lock_key); //解锁
if (!$lock->getLock($lock_key, 3600)) {
    return false;
}

class BonusSubsite
{
    public $startTime = 0;
    public $endTime   = 0;
    public $money     = 0;
    public $whiteList = '';
    public $errMsg    = '';
    public $date      = '';

    public $successCount = 0;
    public $pageCount    = 10000;

    public $bonusService = null;
    public $bonusModel   = null;


    public function __construct($date = '')
    {
        $this->money     = floatval(BonusConfModel::get('BONUS_SUBSITE_SIGN_MONEY'));
        $this->whiteList = explode(',', BonusConfModel::get('BONUS_SUBSITE_SIGN_WHITELIST'));

        $this->date = $date != '' ? $date : date('Ymd');

        $this->bonusModel   = new BonusModel();
        $this->bonusService = new BonusService();
    }

    public function checkEventActive()
    {
        if ($this->money === false) {
            $this->errMsg = '活动不再有效期';
            return false;
        }
        if ($this->money > 100 || $this->money <= 0) {
            $this->errMsg = '签到红包金额错误';
            return false;
        }
        return true;
    }

    public function checkIdempotence($uid, $start, $end, $type = BonusModel::BONUS_SUBSITE_SIGN)
    {
        $result = $this->bonusModel->findBy(sprintf("owner_uid=%s && type=%s && created_at between %s and %s", $uid, $type, $start, $end),
            'id', array(), true);
        if (empty($result)) {
            return false;
        }
        return true;
    }

    public function getStartId()
    {
        $maxInfo = \core\dao\DealLoadModel::instance()->findBySqlViaSlave('SELECT MAX(id) as max_id FROM firstp2p_deal_load');
        $startId = intval($maxInfo['max_id']) - 1000000;
        if ($startId <= 0) {
            $startId = 30000000;
        }
        return $startId;
    }

    public function getPages($startId, $dayStart, $dayEnd)
    {
        $sqlCount = sprintf('SELECT COUNT(DISTINCT(`user_id`)) FROM `firstp2p_deal_load` WHERE id > %s && site_id in (%s) && create_time between %s and %s',
            $startId, implode(',', $this->whiteList), ($dayStart - 86400 - 28800), ($dayEnd - 86400 - 28800));
        return ceil(intval(\core\dao\DealLoadModel::instance()->countBySql($sqlCount, array(), true)) / $this->pageCount);
    }

    public function run()
    {
        if (!$this->checkEventActive()) {
            echo $this->errMsg, "\n";
            exit();
        }
        $dayStart = strtotime($this->date);
        $dayEnd   = $dayStart + 86400 - 1;

        $startId = $this->getStartId();
        $pages = $this->getPages($startId, $dayStart, $dayEnd);
        for ($page = 0; $page < $pages; $page++) {
            $sql = sprintf('SELECT DISTINCT(user_id) FROM firstp2p_deal_load WHERE id > %s && site_id in (%s) && create_time between %s and %s ORDER BY user_id ASC LIMIT %s, %s',
                $startId, implode(',', $this->whiteList), ($dayStart - 86400 - 28800), ($dayEnd - 86400 - 28800), $page * $this->pageCount, $this->pageCount);
            $list =  \core\dao\DealLoadModel::instance()->findAllBySql($sql, true, array(), true);
            foreach ($list as $user) {
                if ($user['user_id'] <= 0) {
                    Logger::wLog('用户ID错误：'.$user['user_id'], Logger::INFO, Logger::FILE, LOG_PATH."bonus_subsite_sign".date('Ymd').'.log');
                    continue;
                }
                if ($this->checkIdempotence($user['user_id'], $dayStart, $dayEnd)) {
                    Logger::wLog('重复发送：'.$user['user_id'], Logger::INFO, Logger::FILE, LOG_PATH."bonus_subsite_sign".date('Ymd').'.log');
                    continue;
                }
                $result = $this->bonusService->generateConsumeBonus($user['user_id'], $this->money, 1, BonusModel::BONUS_SUBSITE_SIGN);
                if ($result) {
                    $this->successCount++;
                    Logger::wLog('发送成功：'.$user['user_id'], Logger::INFO, Logger::FILE, LOG_PATH."bonus_subsite_sign".date('Ymd').'.log');
                } else {
                    Logger::wLog('发送失败：'.$user['user_id'], Logger::INFO, Logger::FILE, LOG_PATH."bonus_subsite_sign".date('Ymd').'.log');
                }
            }
        }
    }

    public function noticeSms()
    {

    }

}

$date = isset($argv[1]) ? $argv[1] : '';

$bonusSubsite = new BonusSubsite($date);
$bonusSubsite->run();
echo "共成功发送", $bonusSubsite->successCount, "\n";
$lock->releaseLock($lock_key); //解锁
exit("Done.\n");
