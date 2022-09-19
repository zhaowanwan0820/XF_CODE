<?php
/**
 * Mail.php
 *
 * @date 2014-08-01
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace libs\mail;

\FP::import("libs.common.dict");

/**
 * Class Mail
 * @package libs\mail
 *
 * 邮件发送服务
 */
class Mail
{

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
     * 邮件服务提供者
     */
    protected $mail_sender;

    /**
     * 邮件服务提供者-163企业邮箱
     */
    protected $mail_sender_163;

    /**
     * 收件人白名单
     * 该白名单为空，或者收件人在内名单里，才发送
     */
    protected $to_white_list;

    /**
     * 使用sendcloud发送的收件人白名单
     * 该白名单不为空，且收件人不在名单里，则使用原有163企业邮箱发送；否则使用sendcloud发送
     * 供sendcloud过渡期分发流量使用
     */
    protected $sendcloud_test_white_list;

    /**
     * 单日单地址最大接收量
     */
    protected static $send_count_max = 500;

    public function __construct()
    {
        $this->to_white_list = \dict::get('EMAIL_WHITELIST');
        //$this->sendcloud_test_white_list = \dict::get('SENDCLOUD_TEST_WHITELIST');

        //sendcloud平稳过渡后，停用163
        $this->mail_sender = new MailSendCloud();
        /*
        $this->mail_sender_163 = new MailSender();
        if (app_conf('MAIL_SERVICE_SWITCH') == self::MAIL_SERVICE_PROVIDER_163) {
            $this->mail_sender = new MailSender();
        }
        */

    }

    /**
     * 设置发件人
     * @param string $from 邮件地址
     * @param string $sender 发件人名称
     * @return bool
     */
    public function setFrom($from, $sender = false) {
        $this->mail_sender->setFrom($from, $sender);
        return true;
    }

    /**
     * 邮件发送
     * @param $subject 标题
     * @param $content 正文内容
     * @param $to 收件人email地址或者多个收件人email地址数组
     * @param $files 附件数组，{{'path'=>'xxx', 'name'=>'xxx'},{'path'=>'xxx', 'name'=>'xxx'}}
     * @return mixed 发送结果 {'message'=>'success'} {'message'=>'error', 'error'=>'xxx'}
     */
    public function send($subject, $content, $to, $files = false)
    {
        if (empty($subject) || empty($content) || empty($to)) {
            return array('message' => 'error', 'error' => 'empty params');
        }

        //过渡期，根据sendcloud白名单判断是否使用sendcloud发送
        /*
        if (!empty($this->sendcloud_test_white_list)) {
            $use_sendcloud = false;
            if (is_array($to)) {
                $use_sendcloud = count(array_intersect($to, $this->sendcloud_test_white_list)) == count($to);
            } else {
                $use_sendcloud = in_array($to, $this->sendcloud_test_white_list);
            }
            if (!$use_sendcloud) {
                $this->mail_sender = $this->mail_sender_163;
            }
        }*/
        foreach ($to as $k => $email_address) {
            if (!$this->verifyEmail($email_address)) {
                unset($to[$k]);
            }
        }

        if (empty($to)) {
            return array('message' => 'error', 'error' => 'empty tos after white list');
        }
        return $this->mail_sender->send($subject, $content, $to, $files);
    }

    /**
     * 校验收件人地址。须符合email规则，并且在白名单内
     * @param $email_address 收件人地址
     * @return bool
     */
    private function verifyEmail($email_address)
    {
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
        if (!empty($this->to_white_list) && !in_array($email_address, $this->to_white_list)) {
            return false;
        }

        //限制单日发送量
        try {
            $key = 'mail_max_count_' . date('Ymd') . '_' . $email_address;
            $cache = \SiteApp::init()->cache;
            $count = $cache->incrValue($key);
            $cache->setExpire($key, (86400*3));
            if ($count > self::$send_count_max) {
                return false;
            }

        } catch (\Exception $e) {
        }
        return $email_address;
    }
}
