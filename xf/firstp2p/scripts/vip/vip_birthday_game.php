<?php
/**
 * 生日福利券
 * 网信会为生日的VIP用户发放生日专属福利；
 * 不同等级的VIP会员，享受不同的生日福利。
 */

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';

use core\dao\vip\VipAccountModel;
use core\dao\vip\VipGiftLogModel;
use core\service\vip\VipService;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use core\service\MsgBoxService;
use core\service\UserService;
use core\dao\UserModel;
use libs\sms\SmsServer;

class VipBirthdayGameTask
{
    /**
     * 用户ID
     * @var int
     */
    private $userId = 0;

    /**
     * 是否推送
     * @var int
     */
    private $withPush = false;

    /**
     * 生日日期，默认去当天的值
     */
    private $birthday = 0;
    private $byear = 0;
    private $bmonth = 0;
    private $bday = 0;

    public function __construct($userId, $birthday, $withPush)
    {
        $this->userId = $userId;
        $this->birthday = $birthday;
        $items = explode('-', $this->birthday);
        if (count($items) != 3 || !is_numeric($items[0]) || !is_numeric($items[1]) || !is_numeric($items[2])) {
            throw new \Exception('生日格式错误，样例格式2017-07-31');
        }

        $this->byear = intval($items[0]);
        $this->bmonth = intval($items[1]);
        $this->bday = intval($items[2]);
        $this->withPush = $withPush;
    }

    public function getUserAccount()
    {
        $sql = "SELECT * FROM `firstp2p_vip_account`
            WHERE `bmonth`={$this->bmonth} AND `bday`={$this->bday} AND `service_grade` > 0
            ORDER BY `id` ASC";
        return VipAccountModel::instance()->findAllBySql($sql);
    }

    // 脚本执行
    public function run()
    {

        Logger::info(implode('|', [__METHOD__, 'START', $this->userId, $this->birthday, $this->withPush]));

        $isCheckBirthday = false;
        if ($this->userId > 0) {
            $users = [['user_id' => $this->userId]];
            $isCheckBirthday = true;
        } else {
            $users = $this->getUserAccount();
        }

        $msgBoxService = new MsgBoxService();
        $userService = new UserService();
        foreach ($users as $user) {
            $userId = $user['user_id'];
            if ($isCheckBirthday) {
                $userAccount = VipAccountModel::instance()->getVipAccountByUserId($this->userId);
                if (empty($userAccount)) continue;
                if ($userAccount['bmonth'] != $this->bmonth
                    || $userAccount['bday'] != $this->bday) {
                    continue;
                }
            }
            $userInfo = $userService->getUserViaSlave($userId);
            $pushMsg = sprintf("亲爱的VIP会员%s，信仔为您为准备了一份生日大礼，点击礼盒领取吧！", $userInfo['real_name']);
            $extraContent = [
                'turn_type' => MsgBoxEnum::TURN_TYPE_XINZAI_BLESS,
                'url' => \core\dao\BonusConfModel::get("XINZAI_BLESS_GAME_URL"),
            ];
            $msgBoxService->create($userId, MsgBoxEnum::TYPE_VIP_BIRTHDAY, '信仔生日祝福', $pushMsg, $extraContent);
            Logger::info(implode('|', [__METHOD__, $userId]));
            //生日祝福短信
            $birthdayTpl = app_conf('VIP_BIRTHDAY_SMS');
            if ($birthdayTpl) {
                $contentData = array(
                );
                $userInfo = UserModel::instance()->findViaSlave($userId, 'mobile, real_name');
                SmsServer::instance()->send($userInfo['mobile'], $birthdayTpl, $contentData, $userId);
                Logger::info(implode('|', [__METHOD__, $userId, 'pushBirthdaySms', $userInfo['mobile'], $birthdayTpl]));
            }
        }

        Logger::info(implode('|', [__METHOD__, 'END', count($users)]));

    }

    // public function sendReport($count) {
    //     $currentDate = date('Y-m-d');
    //     $subject = $currentDate.'vip生日礼券发送统计';
    //     $content = "<h3>$subject</h3>";
    //     $content .= "<table border=1 style='text-align: center'>";
    //     $content .= "<tr><th>日期</th><th>VIP生日用户数</th></tr>";
    //     $content .= "<tr><td> {$currentDate} </td><td>". $count. "</td></tr>";
    //     $content .= "</table>";
    //     $mail = new \NCFGroup\Common\Library\MailSendCloud();
    //     $mailAddress = ['liguizhi@ucfgroup.com'];
    //     $ret = $mail->send($subject, $content, $mailAddress);
    // }
}

$shortopts = "";
$shortopts .= "u::"; // 用户ID

$longopts = array(
    // "with-sms",     // 是否短信
    "with-push",    // 是否推送
    // "with-report",  // 是否邮件
    "birthday::",   // 指定运行的生日日期
    "help",         // 帮助
);

// 获取参数
$opts = getopt($shortopts, $longopts);

if (isset($opts['help'])) {
    $str = <<<HELP
Usage: php vip_birthday_game [args...]
    -u 用户ID
    --with-push 推送
    --birthday=2017-07-31 指定运行的生日日期,例如2017-07-31
    --help 帮助
HELP;
    exit($str.PHP_EOL);
}

$userId     = isset($opts['u']) ? intval($opts['u']) : 0;
// 默认取当前时间
$birthday   = isset($opts['birthday']) ? $opts['birthday'] : date('Y-m-d', strtotime("+1 days"));
// $withSms    = isset($opts['with-sms']) ? true : false;
$withPush   = isset($opts['with-push']) ? true : false;
// $withReport   = isset($opts['with-report']) ? true : false;

try {

    $vip = new VipBirthdayGameTask($userId, $birthday, $withPush);
    $vip->run();

} catch (\Exception $ex) {

    Logger::info(implode('|', [__METHOD__, $ex->getMessage()]));

}
