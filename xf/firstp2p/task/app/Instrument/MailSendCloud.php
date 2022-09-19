<?php
/**
 * MailSendCloud.php
 *
 * @date 2014-08-01
 * @author liangqiang <liangqiang@ucfgroup.com>
 * @author jingxu
 */

namespace NCFGroup\Task\Instrument;


/**
 * Class MailSendCloud
 * @package libs\mail
 *
 * SendCloud邮件服务
 * 参考 http://sendcloud.sohu.com/sendcloud/api-doc/web-api-ref
 */
class MailSendCloud {

    const MAIL_SERVICE_PROVIDER_SENDCLOUD = 1;

    const MAIL_SERVICE_PROVIDER_163 = 2;

    /**
     * 正确邮箱后缀
     * @var array
     */
    static protected $mail_suffix = array('com', 'edu', 'gov', 'int', 'mil', 'net', 'org', 'ad', 'ae', 'af', 'ag', 'ai',
        'al', 'am', 'an', 'ao', 'aq', 'ar', 'as', 'at', 'au', 'aw', 'az', 'ba', 'bb', 'bd', 'be', 'bf', 'bg', 'bh',
        'bi', 'bj', 'bm', 'bn', 'bo', 'br', 'bs', 'bt', 'bv', 'bw', 'by', 'bz', 'ca', 'cc', 'cf', 'cg', 'ch', 'ci',
        'ck', 'cl', 'cm', 'cn', 'co', 'cq', 'cr', 'cu', 'cv', 'cx', 'cy', 'cz', 'de', 'dj', 'dk', 'dm', 'do', 'dz',
        'ec', 'ee', 'eg', 'eh', 'es', 'et', 'ev', 'fi', 'fj', 'fk', 'fm', 'fo', 'fr', 'ga', 'gb', 'gd', 'ge', 'gf',
        'gh', 'gi', 'gl', 'gm', 'gn', 'gp', 'gr', 'gt', 'gu', 'gw', 'gy', 'hk', 'hm', 'hn', 'hr', 'ht', 'hu', 'id',
        'ie', 'il', 'in', 'io', 'iq', 'ir', 'is', 'it', 'jm', 'jo', 'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kp',
        'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk', 'lr', 'ls', 'lt', 'lu', 'lv', 'ly', 'ma', 'mc', 'md',
        'mg', 'mh', 'ml', 'mm', 'mn', 'mo', 'mp', 'mq', 'mr', 'ms', 'mt', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'nc',
        'ne', 'nf', 'ng', 'ni', 'nl', 'no', 'np', 'nr', 'nt', 'nu', 'nz', 'om', 'qa', 'pa', 'pe', 'pf', 'pg', 'ph',
        'pk', 'pl', 'pm', 'pn', 'pr', 'pt', 'pw', 'py', 're', 'ro', 'ru', 'rw', 'sa', 'sc', 'sd', 'se', 'sg', 'sh',
        'si', 'sj', 'sk', 'sl', 'sm', 'sn', 'so', 'sr', 'st', 'su', 'sy', 'sz', 'tc', 'td', 'tf', 'tg', 'th', 'tj',
        'tk', 'tm', 'tn', 'to', 'tp', 'tr', 'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'uk', 'us', 'uy', 'va', 'vc', 've',
        'vg', 'vn', 'vu', 'wf', 'ws', 'ye', 'yu', 'za', 'zm', 'zr', 'zw',);

    /**
     * 单日单地址最大接收量
     */
    protected static $send_count_max = 100;

    /**
     * 发信子账号
     */
    private $api_user = "emailposter";

    /**
     * 发信api_key
     */
    private $api_key = "Crwo3PBkwYD1sqWc";

    /**
     * 发件人地址
     */
    protected $from = "noreply@s1.firstp2p.com";

    /**
     * 发件人名称
     */
    protected $from_name = "网信理财";

    /**
     * 设置发件人信息
     * 如果不设置则采用默认值
     * sendcloud显示用
     *
     * @param $from 发件人地址
     * @param $from_name 发件人名称
     * @return mixed
     */
    public function setFrom($from, $from_name = false) {
        if (!empty($from)) {
            $this->from = $from;
        }
        if (!empty($from_name)) {
            $this->from_name = $from_name;
        }
    }

    /**
     * 邮件发送
     *
     * @param $subject 标题
     * @param $content 正文内容
     * @param $to 收件人email地址或者多个收件人email地址数组
     * @param $files 附件数组，{{'path'=>'xxx', 'name'=>'xxx'},{'path'=>'xxx', 'name'=>'xxx'}}
     * @return mixed 发送结果 {'message'=>'success'} {'message'=>'error', 'error'=>'xxx'}
     */
    public function send($subject, $content, $to, $files = false) {
        //$log_info = array(__CLASS__, __FUNCTION__, APP_ENV, $subject, json_encode($to));
        //Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        if (empty($to) || empty($subject) || empty($content)) {
            //Logger::info(implode(" | ", array_merge($log_info, array('empty params'))));
            return false;
        }

        // 过滤退信列表名单，增加到达成功率
        if (!is_array($to)) {
            $to = explode(';', $to);
        }
        $realTo = array();
        foreach ($to as $email_address) {
            if(!$this->verifyEmailAddress($email_address)) {
                continue;
            }
            $rs_bounces = $this->getBounces($email_address, 0, 10);
            if (!empty($rs_bounces) && $rs_bounces->message == 'success' && !empty($rs_bounces->bounces)) {
                //Logger::info(implode(" | ", array_merge($log_info, array('bad email address', $email_address))));
//                unset($email_address); // bug 如果其他还引用$to，则unset不起作用。
                continue;
            }
            $realTo[] = $email_address;
        }
        if (empty($realTo)) {
            //Logger::info(implode(" | ", array_merge($log_info, array('empty to'))));
            return false;
        } else {
            $to = implode(';', $realTo);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'https://sendcloud.sohu.com/webapi/mail.send.json');
        //不同于登录SendCloud站点的帐号，您需要登录后台创建发信子帐号，使用子帐号和密码才可以进行邮件的发送。
        $post_fields = array('api_user' => $this->api_user,
                             'api_key' => $this->api_key,
                             'resp_email_id' => 'true',
                             'from' => $this->from,
                             'fromname' => $this->from_name,
                             'to' => $to,
                             'subject' => $subject,
                             'html' => $content,
        );
        self::postFieldsAddFiles($files, $post_fields); //附件
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

        //执行发送
        $result_send = curl_exec($ch);
        $result = array('message' => 'success');
        if ($result_send === false) { //请求失败
            $result = array('message' => 'error', 'error' => curl_error($ch));
            //Logger::warn(implode(" | ", array_merge($log_info, array('error', json_encode($result)))));
        } else {
            $result_send = json_decode($result_send);
            //Logger::info(implode(" | ", array_merge($log_info, array(json_encode($result_send)))));
            if ($result_send->message != 'success') {
                $result = array('message' => 'error', 'error' => implode('|', $result_send->errors));
            } elseif (count($result_send->email_id_list) == 1) { //return emailId if have
                $result['emailId'] = $result_send->email_id_list[0];
            } elseif (count($result_send->email_id_list) > 1) { // 发送多人的邮件
                $result['emailId'] = '-';
            }
        }
        if (empty($result['error'])) {
            //Logger::warn(implode(" | ", array_merge($log_info, array('success', json_encode($result)))));
        } else {
            //Logger::warn(implode(" | ", array_merge($log_info, array('fail', json_encode($result)))));
        }

        curl_close($ch);
        return $result;
    }


    /**
     * 邮件模板发送
     *
     * @param $subject
     * @param $tpl_name
     * @param substitution_vars参数可以用来指定替换变量，格式为json字符串，里面主要包含to和sub两类属性，其含义如下
     *          $to 收件人地址数组, 注意数组长度不能超过100个，to示例：{ "to": ["to1@sendcloud.org", "to2@sendcloud.org"] }
     *          $sub 替换变量的关联数组。每一个变量对应一组替换值，数组的下标与to数组的下标一一对应，即每一个收件人按其在to(收件人)数组中出现的位置使用sub(替换值)数组中相应位置的值进行替换，
     *          如substitution_vars=‘{"to": ["to1@sendcloud.org", "to2@sendcloud.org"],"sub" : { "%name%" : ["约翰", "林肯"], "%money%" : ["1000", "200"]} }’，
     *          则"to1@sendcloud.org"、"约翰"、"1000"是一组对应值，数组下标都为为0，"to2@sendcloud.org"、"林肯"、"200"是一组对应值，数组下标都为为1。
     *          sub示例：{ "sub": { "%name%" : ["约翰", "林肯"], "%money%" : ["1000", "200"] } }
     * @param bool $files 文件数组，如：{"@/Users/liang/downloads/image001.jpg;filename=1.jpg","@/Users/liang/downloads/upload_tpl_finance_audit.xlsx;filename=2.xlsx"}
     * @return array|bool|mix|mixed|stdClass|string
     */
    public function sendTemplate($subject, $tpl_name, $substitution_vars, $files = false) {
        if (empty($subject) || empty($tpl_name) || empty($substitution_vars)) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'https://sendcloud.sohu.com/webapi/mail.send_template.json');
        //不同于登录SendCloud站点的帐号，您需要登录后台创建发信子帐号，使用子帐号和密码才可以进行邮件的发送。
        $post_fields = array('api_user' => $this->api_user,
                             'api_key' => $this->api_key,
                             'resp_email_id' => true,
                             'from' => $this->from,
                             'fromname' => $this->from_name,
                             'subject' => $subject,
                             'template_invoke_name' => $tpl_name,
                             'substitution_vars' => json_encode($substitution_vars),
        );
        self::postFieldsAddFiles($files, $post_fields); //附件
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

        $result = curl_exec($ch);

        if ($result === false) { //请求失败
            $result = array('error' => curl_error($ch));
        } else {
            $result = json_decode($result);
        }
        curl_close($ch);
        return $result;
    }

    /**
     * 获取退信列表成员
     *
     * @param string $email 需要查询的邮件地址
     * @param int $start 获取的退信列表的偏移， 默认为0
     * @param int $limit 获取的最大结果个数
     * @param int $days 过去days天内的统计数据（包含今天）
     * @return array|bool|\mix|mixed|\stdClass|string
     */
    public function getBounces($email = '', $start = 0, $limit = 100, $days = 1000) {
        $url = "https://sendcloud.sohu.com/webapi/bounces.get.json";
        $url .= "?api_user={$this->api_user}&api_key={$this->api_key}&start={$start}&limit={$limit}&email={$email}&days={$days}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);

        if ($result === false) { //请求失败
            $result = array('error' => curl_error($ch));
        } else {
            $result = json_decode($result);
        }
        curl_close($ch);
        return $result;
    }

    /**
     * 添加附件
     *
     * @param $files
     * @param $post_fields
     */
    protected static function postFieldsAddFiles($files, &$post_fields) {
        if ($files) {
            $files = array_values($files);
            foreach ($files as $k => $file) {
                $post_fields['file' . ($k + 1)] = "@{$file['path']}";
                if (!empty($file['name'])) {
                    $post_fields['file' . ($k + 1)] .= ";filename={$file['name']}";
                }
            }
        }
        return $post_fields;
    }

    /**
     * 校验收件人地址。须符合email规则，后面加白名单过滤。
     * @param $email_address 收件人地址
     * @return bool
     */
    private function verifyEmailAddress($email_address) {
        $email_address = trim($email_address);
        //email 里面是否有空格
        if(strpos($email_address, " ") !== false){
            return false;
        }
        $reg = "/([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z0-9]{2,3}([\.][a-z]{2})?/i";
        $arr_temp = explode(".",$email_address);
        if(!in_array(strtolower($arr_temp[count($arr_temp)-1]),self::$mail_suffix)){
            return false;
        }
        if (!preg_match($reg, $email_address)) {
            return false;
        }

//        if (!empty($this->to_white_list) && !in_array($email_address, $this->to_white_list)) {
//            return false;
//        }

        //限制单日发送量
        try {
            $key = 'mail_max_count_' . date('Ymd') . '_' . $email_address;
            $redis = getDI()->get('taskRedis');
            $count = $redis->incr($key);
            if ($count > self::$send_count_max) {
                return false;
            }
            $redis->expire($key, (86400*3));
        } catch (\Exception $e) {
        }
        return $email_address;
    }
}
