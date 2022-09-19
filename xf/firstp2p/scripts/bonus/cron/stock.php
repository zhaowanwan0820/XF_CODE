<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包发放规则：股票基金红包发送
 *-----------------------------------------------------------------------
 * 接口功能：网信申请AA用户数据，AA返回约定的用户手机号码到网信。
 *-----------------------------------------------------------------------.
 *
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../../app/init.php';

use core\dao\BonusModel;
use libs\lock\LockFactory;
use core\service\MsgBoxService;
use core\service\bonus\BonusPush;
use NCFGroup\Common\Extensions\RPC\RpcClientAdapter;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

$lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
$lock_key = 'bonus_cron_stock';
if (!$lock->getLock($lock_key, 3600)) { //防止重复执行
    exit("已经开始执行，请勿重复执行！");
}

class Stock
{
    /**
     * 分页获取每天的数据.
     */
    private $pages = 100;

    /**
     * 每页的数据.
     */
    private $limit = 1000;

    /**
     * 红包生成时间.
     */
    private $createdAt = 0;

    /**
     * 红包过期时间.
     */
    private $expiredAt = 0;

    /**
     * 获取哪天的数据，AA接口参数.
     */
    private $date = array();

    /**
     * 股票基金发送红包用户列表接口.
     */
    private $api_url = 'http://backend.stockaccount.firstp2p.com:8202';

    /**
     * 调用接口重试次数.
     */
    private $retry_times = 3;

    /**
     * 发送成功个数.
     */
    private $num_success = 0;

    /**
     * 异常手机号.
     */
    private $err_mobiles = array();

    /**
     * 错误信息
     */
    private $error_msg = 0;

    /**
     * 构造函数，初始化发送红包的日期.
     */
    public function __construct($date = '')
    {
        $this->createdAt = time();

        if (!empty($date)) {
            $this->date = $date;
        } else {
            $this->date = date('Ymd', strtotime('-1 day'));
        }
    }

    /**
     * 获取数据.
     */
    public function getDataFromApi($url, $date)
    {
        try {
            $stockRpc = new RpcClientAdapter($url);
            $request = new SimpleRequestBase();
            $request->setParam($date);
            $response = $stockRpc->callByObject([
                "service" => "NCFGroup\\Stock\\Account\\Srv\\Services\\Bonus",
                "method"  => "getData",
                "args" => $request,
            ]);
        } catch(\Exception $e) {
            $this->error_msg = json_decode($e);
            $this->sendMail();
            print_r($e);
            exit("日期:".date('Y-m-d H:i:s')."|信息:接口异常!\n");
        }
        return $response;
    }

    /**
     * 执行业务逻辑.
     */
    public function run()
    {

        for ($i = 0; $i < $this->retry_times; $i++) {
            $result = $this->getDataFromApi($this->api_url,$this->date)->toArray();
            if ($result['errorCode'] == 0) {
                if ($i > 0) {
                    sleep(10);
                }
                break;
            }
            $this->error_msg = $result['errorCode'];
        }
        $day_start = mktime(0, 0, 0);
        $day_end  = $day_start + 86400 - 1;
        $this->expiredAt = time() + $result['expire'] * 86400;
        $config = BonusPush::getConfig(BonusPush::GET_BONUS);

        foreach ($result['records'] as $mobile => $money) {
            if (!preg_match('/^1[0-9]{10}/', $mobile)) {
                array_push($this->err_mobiles, $mobile);
                continue;
            }
            $bonus_info = BonusModel::instance()->findBy("mobile='{$mobile}' AND type=".BonusModel::BONUS_STOCK . " AND created_at between $day_start AND $day_end", 'id', array(), true);
            if (!empty($bonus_info)) {
                $hongbaoText = app_conf('NEW_BONUS_TITLE');
                error_log("{$mobile['phone']}\t该手机号已经发送过{$this->date}{$hongbaoText}\n", 3, __DIR__.'/../../../log/STOCK'.date('Y-m-d').".error.log");
                continue;
            }
            $bonus_result = $this->bonusSend($mobile, $money, $config);
            error_log(sprintf("mobile=%s\tresult=%s\n", $mobile, $bonus_result), 3, __DIR__.'/../../../log/STOCK'.date('Y-m-d').".log");
        }
        $this->sendMail();
        echo '共成功发送'.$this->num_success."\n";
    }

    /**
     * 发送红包.
     */
    public function bonusSend($mobile, $money, $config)
    {
        $sql = "SELECT id FROM `%s` where mobile='%s' AND `is_effect` =1 AND `is_delete` = 0";
        $sql = sprintf($sql, 'firstp2p_user', $mobile);
        $user = \core\dao\UserModel::instance()->findBySql($sql, array(), true);
        $owner_uid = 0;
        if (isset($user['id'])) {
            $owner_uid = intval($user['id']);
        }

        $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `mobile`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, %s, %s, %s, %s, %s, %s)';

        $result = $GLOBALS['db']->query(sprintf($insert_sql, $owner_uid, $mobile, $money, 1, BonusModel::BONUS_STOCK, $this->createdAt, $this->expiredAt));
        if ($result) {
            $this->num_success++;
            $msgbox = new MsgBoxService();
            $content = sprintf($config['content'], 1, number_format($money, 2), 1);
            $msgbox->create($owner_uid, 30, $config['title'], $content);
        }
        return $result;
    }

    /**
     * 发送邮件.
     */
    private function sendMail()
    {
        $summary = array();
        $subject = "【股票】红包发送状态".$this->date;

        $body = '<ul style="font-size:px;color:#1f497d;font-weight:bold;">';
        $body .= '<b style="color:red;">发送信息如下：</b>';
        $body .= "<div><b>成功发送红包个数: {$this->num_success}</b></div>";
        $body .= "<div><b>错误的手机号列表: <br/>".implode("<br/>", $this->err_mobiles)."</b></div>";
        if ($this->error_msg) {
            $body .= "<div><b>错误信息: <br/>".$this->error_msg."</b></div>";
        }
        $body .= '</ul>';

        $send_list = explode(',', \core\dao\BonusConfModel::get('BONUS_STOCK_MAIL_LIST'));
        array_push($send_list, 'wangshijie@ucfgroup.com');

        $msgcenter = new \Msgcenter();
        $msgcenter->setMsg(implode(',', $send_list), 0, $body, false, $subject);
        $msgcenter->save();
    }
}

$date = isset($argv[1]) ? $argv[1] : '';

$stock = new Stock($date);
$stock->run();

$lock->releaseLock($lock_key);
exit("done.\n");
