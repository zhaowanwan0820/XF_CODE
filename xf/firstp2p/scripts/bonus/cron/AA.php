<?php
/**
 *-----------------------------------------------------------------------
 * 1、红包发放规则：给AA租车活跃用户发送红包
 *-----------------------------------------------------------------------
 * 应用级参数：针对API的不同用途，应用级参数各不相同，详细参见API文档。
 *    •  签名方法
 * 调用API时，需要将所有参数名称以及参数值加入签名，即：系统级参数（除去sign）名称、系统级参数值、应用级参数名称、应用级参数值全部加入签名。
 * I．签名参数串排序
 * 签名时，根据参数名称，将除签名（sign）外所有请求参数按照字母先后顺序排序:
 * key + value .... key + value 。
 * 注：
 * 1、排序若首字母相同，则对第二个字母进行排序，以此类推。
 * 2、value无需编码。
 * 3、对于非必选参数，如果没有value值，也需要参与签名。（说明：非必选参数没有value值时，将参数名放到字符串中，即参数名要参加签名）
 * 例如：将“foo=1,bar=2,baz=三”排序为“bar=2,baz=三,foo=1”参数名和参数值链接后，得到拼装字符串bar2baz三foo1。
 * II．签名算法
 * 将分配的得到的密钥（vendorkey）同时拼接到参数字符串头、尾部进行md5加密，再转化成大写，格式是：md5(vendorkeykey1value1key2value2...vendorkey)。
 * 业务流程说明
 *
 * 用户信息接口
 * 接口说明
 * 发起方：网信p2p
 * 接收方：AA租车
 * 接口功能：网信申请AA用户数据，AA返回约定的用户手机号码到网信。
 *-----------------------------------------------------------------------
 * @version 1.0 Wang Shi Jie <wangshijie@ucfgroup.com>
 *-----------------------------------------------------------------------
 */
//ini_set('display_errors', 1);
//error_reporting(E_ERROR);
set_time_limit(0);
require_once dirname(__FILE__).'/../../../app/init.php';
require_once dirname(__FILE__).'/../../../system/libs/msgcenter.php';


class AA
{
    /**
     * 分页获取每天的数据
     */
    private $pages = 100;

    /**
     * 每页的数据
     */
    private $limit = 1000;

    /**
     * AA加密KEY
     */
    private $vendorKey = 'AA20150413UCF';

    /**
     * AA分配的ID
     */
    private $vendorId = 'AA20150413001';

    /**
     * 红包生成时间
     */
    private $createdAt = 0;

    /**
     * 红包过期时间
     */
    private $expiredAt = 0;

    /**
     * 获取哪天的数据，AA接口参数
     */
    private $date = array();

    /**
     * AA接口
     */
    private $aa_api_url = 'http://crmbeta.aayongche.com/ucf/passenger/getData';

    /**
     * 调用接口重试次数
     */
    private $retry_times = 0;

    /**
     * 发送成功个数
     */
    private $num_success = 0;

    /**
     * 异常手机号
     */
    private $err_mobiles = array();

    /**
     * 构造函数，初始化发送红包的日期
     */
    public function __construct($date = '') {
        $this->createdAt = time();

        if (!empty($date)) {
            $this->date = explode(',', $date);
        } else {
            $this->date = array(date('Y-m-d', strtotime('-1 day')));
        }
    }

    /**
     * 获取AA租车的数据
     */
    public function getDataFromApi($url, $data) {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_HEADER, 0);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);

        $return = json_decode(curl_exec($handle), true);
        curl_close($handle);

        return $return;
    }

    /**
     * 获取签名
     */
    public function getSign($params = array()) {
        ksort($params);
        $sign = '';
        foreach ($params as $k => $v) {
            $sign .= $k.$v;
        }
        return strtoupper(md5($this->vendorKey . $sign . $this->vendorKey));
    }

    /**
     * 执行业务逻辑
     */
    public function run() {
        $switch = \core\dao\BonusConfModel::get('AA_BONUS_SWITCH'); //开关
        $use_limit_day = intval(\core\dao\BonusConfModel::get('AA_BONUS_USE_LIMIT_DAY')); //红包有效期
        $money = \core\dao\BonusConfModel::get('AA_BONUS_MONEY'); //红包金额
        $is_sms = \core\dao\BonusConfModel::get('AA_BONUS_SMS_SWITCH'); //是否发送短信
        if (!$switch) {
            exit("活动已经结束。\n");
        }
        if ($use_limit_day <= 0) {
            exit("有效期配置错误。\n");
        }
        $this->expiredAt = $this->createdAt + $use_limit_day * 86400;

        foreach ($this->date as $date) {
            for ($page = 0; $page < $this->pages; $page++) {
                $params = array(
                    'date' => $date,
                    'limit' => $this->limit,
                    'offset' => ($this->limit * $page),
                    'vendorId' => $this->vendorId
                );
                $result = $this->getDataFromApi($this->aa_api_url, array_merge(array('sign' => $this->getSign($params)), $params));
                /*$result = array('result' => array('dataList' => array(
                                        array('id' => 1, 'phone' => '13601013563'),
                                        array('id' => 2, 'phone' => '13521855616'),
                                        array('id' => 3, 'phone' => '13810048744'),
                                    )));*/
                if (empty($result['result']['dataList'])) {
                    break;
                }

                foreach ($result['result']['dataList'] as $mobile) {
                    if (!preg_match('/^1[0-9]{10}/', $mobile['phone'])) {
                        array_push($this->err_mobiles, $mobile['phone']);
                        continue;
                    }
                    /*if (SiteApp::init()->cache->get('bonus_for_aa_user_' . $mobile['phone'])) {
                        error_log("{$mobile['phone']}\t该手机号已经发送过红包\n", 3, __DIR__ .'/../../../log/AA'.$date.".error.log");
                        continue;
                    }*/
                    $bonus_info = \core\dao\BonusModel::instance()->findBy("mobile='{$mobile['phone']}' AND type=10", 'id', array(), true);
                    if (!empty($bonus_info)) {
                        error_log("{$mobile['phone']}\t该手机号已经发送过红包\n", 3, __DIR__ .'/../../../log/AA'.$date.".error.log");
                        continue;
                    }
                    $bonus_result = $this->bonusSend($mobile['phone'], $money);
                    $log = sprintf("id=%s\tmobile=%s\tresult=%s\tpage=%s\tlimit=%s\toffset=%s\n", $mobile['id'], $mobile['phone'], $bonus_result, $page, $this->limit, $params['offset']);
                    error_log($log, 3, __DIR__ .'/../../../log/AA'.$date.".log");
                    if ($bonus_result) {
                        //SiteApp::init()->cache->set('bonus_for_aa_user_' . $mobile['phone'], 1, 86400);
                        $this->num_success++;
                        if ($is_sms) {
                            \SiteApp::init()->sms->send($mobile['phone'], "{$money},150", $GLOBALS['sys_config']['SMS_TEPLATE_CONFIG']['TPL_SMS_BONUS_FOR_AA_USER'], 0);
                        }
                    }
                }
            }
        }
        $this->sendMail();
        echo '共成功发送' . $this->num_success . "\n";
    }

    /**
     * 发送红包
     */
    public function bonusSend($mobile, $money) {
        $sql = "SELECT id FROM `%s` where mobile='%s' AND `is_effect` =1 AND `is_delete` = 0";
        $sql = sprintf($sql, 'firstp2p_user', $mobile);
        $user = \core\dao\UserModel::instance()->findBySql($sql, array(), true);
        $owner_uid = 0;
        if (isset($user['id'])) {
            $owner_uid = intval($user['id']);
        }

        $insert_sql = 'INSERT INTO `firstp2p_bonus` (`owner_uid`, `mobile`, `money`, `status`, `type`, `created_at`, `expired_at`) VALUES (%s, %s, %s, %s, %s, %s, %s)';
        return $GLOBALS['db']->query(sprintf($insert_sql, $owner_uid, $mobile, $money, 1, 10, $this->createdAt, $this->expiredAt));
    }

    /**
     * 发送邮件
     */
    private function sendMail() {
        $summary = array();
        $subject = "【AA租车红包】红包发送状态邮件";

        $body = '<ul style="font-size:px;color:#1f497d;font-weight:bold;">';
        $body .= '<b style="color:red;">发送信息如下：</b>';
        $body .= "<div><b>成功发送红包个数: {$this->num_success}</b></div>";
        $body .= "<div><b>错误的手机号列表: <br/>" . implode("<br/>", $this->err_mobiles) . "</b></div>";
        $body .= '</ul>';

        $msgcenter = new Msgcenter();
        $msgcenter->setMsg(implode(',', array('wangshijie@ucfgroup.com')), 0, $body, false, $subject);
        $msgcenter->save();
    }

}

$date = $argv[1];

$aa = new AA($date);
$aa->run();

exit("done.\n");
