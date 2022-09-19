<?php
/**
 * 功能：
 * 过期经验值脚本
 * 必须参数：
 * 无
 * 可选参数
 *  -u 用户ID
 *-----------------------------------------------------------------------
 * @version 1.0 wangshijie@ucfgroup.com
 *-----------------------------------------------------------------------
 */

// ini_set('display_errors', 1);
// error_reporting(E_ERROR);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';
use core\service\vip\VipService;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\VipEnum;

class VipPoint
{
    const COUNT = 100;
    public $date = '';
    public $vipdb = null;
    public $expireTime = 0;

    public function __construct()
    {
        $this->vipdb = \libs\db\Db::getInstance('vip','slave','utf8',1);
        $this->date = date('Ym').'01';
        $this->expireTime = strtotime($this->date)-1;
    }

    private function getMaxId() {
        $sql = "SELECT id FROM `firstp2p_vip_account` ORDER BY id DESC limit 1";
        return $this->vipdb->getOne($sql);
    }

    private function getMinId() {
        $sql = "SELECT id FROM `firstp2p_vip_account` ORDER BY id ASC limit 1";
        return $this->vipdb->getOne($sql);
    }

    private function getVipList($startId, $endId) {
        $sql = "SELECT user_id FROM `firstp2p_vip_account`
            WHERE id>= $startId AND id < $endId ";
        return $this->vipdb->getCol($sql);
    }

    private function getExpiredList($userIds) {
        $idStr = '';
        if ($userIds) {
            $idStr = implode(',', $userIds);
        }
        if (empty($idStr)) {
            return array();
        }
        $sql = 'SELECT user_id, SUM(point) as point FROM firstp2p_vip_point_log WHERE user_id in ('. $idStr.')  AND expire_time ='.$this->expireTime.' AND status = 1 GROUP BY user_id';
        return $this->vipdb->getAll($sql);
    }

    private function sendReport($count) {
        $currentDate = date('Y-m-d');
        $subject = $currentDate.'经验值过期用户,总数'.$count;
        $content = "<h3>$subject</h3>";
        $mail = new \NCFGroup\Common\Library\MailSendCloud();
        $mailAddress = ['liguizhi@ucfgroup.com'];
        $ret = $mail->send($subject, $content, $mailAddress);
    }

    public function run()
    {
        $total = $this->getMaxId();
        $pages = (int)ceil($total / self::COUNT);

        $successCount = 0;
        $startId = $this->getMinId();
        $vipService = new VipService();
        for ($page = 1; $page <= $pages; $page++) {
            $endId = $startId + self::COUNT;
            $userList = $this->getVipList($startId, $endId);
            if (!empty($userList)) {
                $data = $this->getExpiredList($userList);
                foreach ($data as $row) {
                    try {
                        $token = sprintf('expire_%s_%s', $row['user_id'], date('Ym',$this->expireTime));
                        $result = $vipService->updateVipPoint($row['user_id'], -$row['point'], VipEnum::VIP_SOURCE_EXPIRE, $token, '过期扣除');
                        Logger::info(implode(' | ', ['VipPointExpiredScript', 'SUCCESS', __CLASS__, $result, json_encode($row)]));
                    } catch (Exception $e) {
                        Logger::info(implode(' | ', ['VipPointExpiredScript', 'FAILED', __CLASS__, $result, json_encode($row)]));
                        continue;
                    }
                    if ($result == true) {
                        $successCount++;
                    }
                }
            }

            $startId += self::COUNT;
        }
        $this->sendReport($successCount);
        echo "成功：", $successCount, "个。";
        exit(0);
    }
}

$vip = new VipPoint();
$vip->run();
