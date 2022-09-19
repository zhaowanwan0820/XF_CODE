<?php
/**
 * 保级&降级提醒
 * 1.每月1号９点，对进入保级的用户发送站内信提醒
 * 2.还有７天降级时，站内信＆短信提醒用户
 * 3.降级站内信提醒
 */

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';

use core\dao\vip\VipAccountModel;
use core\dao\vip\VipPointLogModel;
use core\service\vip\VipService;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use core\service\MsgBoxService;
use libs\sms\SmsServer;
use core\dao\UserModel;

class VipNoticeTask {
    // 任务类型
    const VIP_NOTICE_RELAGATED      = 1; //保级
    const VIP_NOTICE_WILL_DEGRADE   = 2; //还有７天即将降级
    const VIP_NOTICE_DEGRADE        = 3; //降级

    // 推送内容
    const VIP_NOTICE_RELAGATED_MSG     = "您上月有%s经验值过期，已不满足%s条件。现将为您保级1个月，本月可继续享受%s特权，并只需于月底前补足所缺经验值前即可免于降级。快去投资攒经验值吧。";
    const VIP_NOTICE_WILL_DEGRADE_MSG  = "您还有7天结束保级，请尽快补足经验值，否则下月将要降级为%s了，快去投资攒经验值吧。";
    const VIP_NOTICE_DEGRADE_MSG       = "很遗憾，您已从%s降为%s，还差%s经验值即可再次升级为“%s”，快去投资攒经验值吧。";
    /**
     * 用户ID
     * @var int
     */
    private $userId = 0;

    private $taskType = 1;

    private $vipConf = array();

    private $typeDesc = array(
        self::VIP_NOTICE_RELAGATED => '保级',
        self::VIP_NOTICE_WILL_DEGRADE => '即将降级',
        self::VIP_NOTICE_DEGRADE => '降级'
    );


    public function __construct($taskType,$userId=0) {
        $vipService = new VipService();
        $this->taskType = intval($taskType);
        $this->userId = intval($userId);
        $this->vipConf = $vipService->getLevelVipConf();
    }

    // 脚本执行
    public function run() {
        $params = array('taskType' => $this->taskType, 'userId' => $this->userId);

        echo 'VipNoticeTask begin, params: '.json_encode($params).PHP_EOL;
        PaymentApi::log('VipNoticeTask begin, params: '.json_encode($params));
        switch ($this->taskType) {
        case self::VIP_NOTICE_RELAGATED :
            $count = $this->relegatedNotice($this->userId);
            break;
        case self::VIP_NOTICE_WILL_DEGRADE:
            $count = $this->willDegradeNotice($this->userId);
            break;
        case self::VIP_NOTICE_DEGRADE:
            $count = $this->degradeNotice($this->userId);
            break;
        }
        $this->sendReport($count, $this->taskType);

        PaymentApi::log('VipAnniversaryTask success, count: '.$count.' taskType:'.$this->taskType);
    }

    private function relegatedNotice($userId = 0) {
        $count = 0;
        //站内信提醒
        $relegateTime = strtotime(date('Y-m-01'));
        $where = ' WHERE 1=1';
        if ($userId) {
            $where .= ' AND user_id='.$userId;
        } else {
            $where .= ' AND is_relegated=1 AND relegate_time>='. $relegateTime;
        }

        $sql = 'SELECT user_id,service_grade FROM firstp2p_vip_account'.$where;
        $p2pdb = \libs\db\Db::getInstance('vip','slave','utf8',1);
        $result = $p2pdb->query($sql);

        while($result && ($data = $p2pdb->fetchRow($result))) {
            if ($data) {
                $this->processRelegate($data);
                $count++;
            }
        }
        return $count;
    }

    private function processRelegate($data) {
        $sql = 'SELECT point FROM firstp2p_vip_point_log WHERE user_id='.intval($data['user_id']).' AND status=2 ORDER BY id DESC LIMIT 1';
        $pointLog = VipPointLogModel::instance()->findBySql($sql);
        $expirePoint = $pointLog['point'];
        $gradeInfo = $this->vipConf[VipEnum::$vipGradeNoToAlias[$data['service_grade']]];
        $gradeName = $gradeInfo['name'];
        $msg = sprintf(self::VIP_NOTICE_RELAGATED_MSG, $expirePoint, $gradeName, $gradeName);
        PaymentApi::log('VipNoticeTask.pushMsg , userId|'.$data['user_id'].' data|pushMsg'.$msg);
        $msgBoxService = new MsgBoxService();
        $msgTitle = MsgBoxEnum::$allType[MsgBoxEnum::TYPE_VIP_RELEGATED];
        $msgType = MsgBoxEnum::TYPE_VIP_RELEGATED;
        $extraContent = array(
            'turn_type' => MsgBoxEnum::TURN_TYPE_CONTINUE_INVEST//app跳转类型标识
        );
        $msgBoxService->create($data['user_id'], $msgType, $msgTitle, $msg, $extraContent);
        return true;
    }

    private function willDegradeNotice($userId = 0) {
        //站内信&短信提醒
        $sevenDaysLater = strtotime("+7 days");
        $day = date("d", $sevenDaysLater);
        if ($day != "01") {
            PaymentApi::log('VipAnniversaryTask , time not reached');
            return false;
        }

        $count = 0;
        $where = ' WHERE 1=1';
        if ($userId) {
            $where .= ' AND user_id='.$userId;
        } else {
            $where .= ' AND is_relegated=1';
        }

        $sql = 'SELECT user_id, service_grade, actual_grade, point FROM firstp2p_vip_account '.$where;
        $p2pdb = \libs\db\Db::getInstance('vip','slave','utf8',1);
        $result = $p2pdb->query($sql);

        while($result && ($data = $p2pdb->fetchRow($result))) {
            if ($data) {
                $this->processWillDegrade($data);
                $count++;
            }
        }
        return $count;
    }

    private function processWillDegrade($data) {
        $gradeInfo = $this->vipConf[VipEnum::$vipGradeNoToAlias[$data['service_grade']]];
        $actualGradeInfo = $this->vipConf[VipEnum::$vipGradeNoToAlias[$data['actual_grade']]];
        $pointNeeds = $gradeInfo['minInvest'] - $data['point'];
        $pointNeeds = $pointNeeds ?: 0;
        if ($pointNeeds >0 ) {
            // app推送
            $gradeName = $actualGradeInfo['name'];
            $msg = sprintf(self::VIP_NOTICE_WILL_DEGRADE_MSG, $gradeName);
            PaymentApi::log('VipNoticeTask.pushMsg , userId|'.$data['user_id'].' data|pushMsg'.$msg);
            $msgBoxService = new MsgBoxService();
            $msgTitle = MsgBoxEnum::$allType[MsgBoxEnum::TYPE_VIP_WILL_DEGRADE];
            $msgType = MsgBoxEnum::TYPE_VIP_WILL_DEGRADE;
            $extraContent = array(
                'turn_type' => MsgBoxEnum::TURN_TYPE_CONTINUE_INVEST//app跳转类型标识
            );
            $msgBoxService->create($data['user_id'], $msgType, $msgTitle, $msg, $extraContent);

            // 发送短信
            $contentData = array(
                'gradeName' => $gradeName,
            );
            $userInfo = UserModel::instance()->findViaSlave($data['user_id'], 'mobile');
            SmsServer::instance()->send($userInfo['mobile'], 'TPL_VIP_DEGRADE', $contentData, $data['user_id']);
        }
        return true;
    }

    private function degradeNotice($userId = 0) {
        //站内信提醒
        $degradeTime = strtotime(date('Y-m-01'));
        $sql = 'SELECT user_id, point, actual_grade, service_grade FROM firstp2p_vip_log';
        $where = ' WHERE 1=1';
        if ($userId) {
            $where .= ' AND user_id='.$userId;
        } else {
            $where .= ' AND log_type='. VipEnum::VIP_ACTION_DEGRADE. ' AND create_time>'. $degradeTime.' ORDER BY id ASC';
        }

        $count = 0;
        $vipsql = $sql. $where;
        $p2pdb = \libs\db\Db::getInstance('vip','slave','utf8',1);
        $result = $p2pdb->query($vipsql);

        while($result && ($data = $p2pdb->fetchRow($result))) {
            if ($data) {
                $this->processDegrade($data);
                $count++;
            }
        }
        return $count;
    }

    private function processDegrade($data) {
        $sql = 'SELECT point FROM firstp2p_vip_point_log WHERE user_id='.intval($data['user_id']).' AND status=2 ORDER BY id DESC LIMIT 1';
        $pointLog = VipPointLogModel::instance()->findBySql($sql);
        $expirePoint = $pointLog['point'];
        $originPoint = $expirePoint + $data['point'];
        $vipService = new VipService();
        $originGrade = $vipService->computeVipGrade($originPoint);
        $originGradeInfo = $this->vipConf[VipEnum::$vipGradeNoToAlias[$originGrade]];
        $degradeInfo = $this->vipConf[VipEnum::$vipGradeNoToAlias[$data['service_grade']]];
        // 计算恢复升级的经验值
        $pointSql = 'SELECT point FROM firstp2p_vip_account WHERE user_id='.intval($data['user_id']);
        $userData = VipAccountModel::instance()->findBySql($pointSql);
        $pointNeeds = $originGradeInfo['minInvest'] - $userData['point'];
        $pointNeeds = $pointNeeds ?: 0;
        $msg = sprintf(self::VIP_NOTICE_DEGRADE_MSG, $originGradeInfo['name'], $degradeInfo['name'], $pointNeeds,$originGradeInfo['name']);
        PaymentApi::log('VipNoticeTask.pushMsg , userId|'.$data['user_id'].' data|pushMsg'.$msg);
        $msgBoxService = new MsgBoxService();
        $msgTitle = MsgBoxEnum::$allType[MsgBoxEnum::TYPE_VIP_DEGRADE];
        $msgType = MsgBoxEnum::TYPE_VIP_DEGRADE;
        $extraContent = array(
            'turn_type' => MsgBoxEnum::TURN_TYPE_CONTINUE_INVEST//app跳转类型标识
        );
        $msgBoxService->create($data['user_id'], $msgType, $msgTitle, $msg, $extraContent);
        return true;
    }

    public function sendReport($count, $taskType = self::VIP_NOTICE_RELAGATED) {
        $currentDate = date('Y-m-d');
        $subject = $currentDate.'vip降级提醒通知';
        $content = "<h3>$subject</h3>";
        $content .= "<table border=1 style='text-align: center'>";
        $content .= "<tr><th>日期</th><th>VIP定期提醒:".$this->typeDesc[$taskType]."</th></tr>";
        $content .= "<tr><td> {$currentDate} </td><td>". $count. "</td></tr>";
        $content .= "</table>";
        $mail = new \NCFGroup\Common\Library\MailSendCloud();
        $mailAddress = ['liguizhi@ucfgroup.com'];
        $ret = $mail->send($subject, $content, $mailAddress);
    }
}

$shortopts = "";
$shortopts .= "u:"; // 用户ID

$longopts = array(
    "taskType::", // 通知类型
    "help",
);

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php vip_degrade_notice.php [args...]
    -u 用户ID
    --taskType=1 保级|2即将降级|3降级
    --help 帮助
HELP;
    exit($str.PHP_EOL);
}

$userId     = isset($opts['u']) ? intval($opts['u']) : 0;
$taskType = isset($opts['taskType']) ? $opts['taskType'] : '';

try {
    $vip = new VipNoticeTask($taskType, $userId);
    $vip->run();
} catch (\Exception $ex) {
    $params = array('taskType' => $taskType, 'userId' =>$userId);

    echo 'VipNoticeTask: '.$ex->getMessage().', params: '.json_encode($params).PHP_EOL;
    PaymentApi::log('VipNoticeTask: '.$ex->getMessage().', params: '.json_encode($params), Logger::ERR);
}
