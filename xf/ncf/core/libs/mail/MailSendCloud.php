<?php
/**
 * MailSendCloud.php
 *
 * @date 2014-08-01
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace libs\mail;
use libs\utils\Logger;

/**
 * Class MailSendCloud
 * @package libs\mail
 *
 * SendCloud邮件服务
 * 参考 http://sendcloud.sohu.com/sendcloud/api-doc/web-api-ref
 */
class MailSendCloud {

    /**
     * 发信子账号
     */
    private $api_user = "emailposter";

    /**
     * 发信api_key
     */
    private $api_key = "Crwo3PBkwYD1sqWc";

    /**
     * 发件人地址，s.firstp2p.com须与sendcloud实际sender一致，不然邮件列表的发件人不显示from而是实际sender
     */
    protected $from = "noreply@s1.firstp2p.com";

    /**
     * 发件人名称
     */
    protected $from_name = "网信理财";
    
    public function __construct(){
        $this->setFrom();
        $api_key = app_conf('MAIL_SENDCLOUD_API_KEY');
        if (!empty($api_key)) {
            $this->api_key = $api_key;
        }
    }

    /**
     * 设置发件人信息
     * 如果不设置则采用默认值
     * sendcloud显示用
     *
     * @param $from 发件人地址
     * @param $from_name 发件人名称
     * @return mixed
     */
    public function setFrom($from = false, $from_name = false) {
        $config = array(
                's.firstp2p.com' => array('api_user' => 'postmaster@firstp2p.sendcloud.org', 'from' => 'noreply@s.firstp2p.com'),
                's1.firstp2p.com' => array('api_user' => 'emailposter', 'from' => 'noreply@s1.firstp2p.com')
            );
        if (empty($from)) {
            $from_domain = $this->getFromByWeight();
            $this->api_user = $config[$from_domain]['api_user'];
            $this->from = $config[$from_domain]['from'];
        } else {
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
        $log_info = array(__CLASS__, __FUNCTION__, $this->api_user, $this->from, $subject, json_encode($to));
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));
        if (empty($to) || empty($subject) || empty($content) || empty($this->from)) {
            Logger::warn(implode(" | ", array_merge($log_info, array('empty params'))));
            return false;
        }

        // 过滤退信列表名单，增加到达成功率
        if (!is_array($to)) {
            $to = explode(';', $to);
        }
        foreach ($to as $k => $email_address) {
            if (!$this->checkSpecialList($email_address)) {
                \libs\utils\Monitor::add("EMAIL_FAIL_BOUNCE",1);//邮件退信打点
                unset($to[$k]);
            }
        }
        if (empty($to)) {
            Logger::info(implode(" | ", array_merge($log_info, array('empty to'))));
            return false;
        } else {
            $to = implode(';', $to);
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
            Logger::warn(implode(" | ", array_merge($log_info, array('error', json_encode($result)))));
        } else {
            $result_send = json_decode($result_send);
            Logger::info(implode(" | ", array_merge($log_info, array(json_encode($result_send)))));
            if ($result_send->message != 'success') {
                $result = array('message' => 'error', 'error' => implode('|', $result_send->errors));
            } elseif (count($result_send->email_id_list) == 1) { //return emailId if have
                $result['emailId'] = $result_send->email_id_list[0];
            } elseif (count($result_send->email_id_list) > 1) { // 发送多人的邮件
                $result['emailId'] = '-';
            }
        }
        if (empty($result['error'])) {
            Logger::info(implode(" | ", array_merge($log_info, array('success', json_encode($result)))));
        } else {
            Logger::warn(implode(" | ", array_merge($log_info, array('fail', json_encode($result)))));
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
     * 获取退信、取消订阅、垃圾举报等列表成员
     *
     * @param string $query_type 查询类型 bounces:退信列表; unsubscribes:取消订阅; spamReported:垃圾举报;
     * @param string $email 需要查询的邮件地址
     * @param int $start 获取的退信列表的偏移， 默认为0
     * @param int $limit 获取的最大结果个数
     * @param int $days 过去days天内的统计数据（包含今天）
     * @return array|bool|\mix|mixed|\stdClass|string
     */
    public function getSpecialList($query_type, $email = '', $start = 0, $limit = 100, $days = 1000) {
        if(empty($query_type)){
            return false;
        }
        $email = trim($email);
        $url = "https://sendcloud.sohu.com/webapi/{$query_type}.get.json";
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

        $result_field_name  = $query_type == 'unsubscribes' ? 'unsubscribes' : 'bounces';
        if (!empty($result) && $result->message == 'success' && !empty($result->$result_field_name)) {
            return $result->$result_field_name;
        }
        return array();
    }

    /**
     * 校验邮件地址是否有效，非退信垃圾等邮件地址
     *
     * @param string $email 需要查询的邮件地址
     * @return bool ture:合法地址；false:非法地址
     */
    public function checkSpecialList($email){
        $log_info = array(__CLASS__, __FUNCTION__, $email);
        if (empty($email)) {
            return false;
        }
        $check_type_list = array ('bounces', 'unsubscribes', 'spamReported');
        foreach ($check_type_list as $type) {
            $check_result = $this->getSpecialList($type, $email);
            if (!empty($check_result)){
                Logger::info(implode(" | ", array_merge($log_info, array('bad email address', $type, json_encode($check_result)))));
                return false;
            }
        }
        return true;
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
     * 根据权重配置设定发信域名, 3:7 代表3/10的概率用s.firstp2p.com域名发
     */
    private function getFromByWeight(){
        $weight_config = app_conf('MAIL_SENDCLOUD_FROM_WEIGHT');
        if (!empty($weight_config)) {
            $weight_config = explode(':', $weight_config);
        }
        $weight_config = (empty($weight_config) || !is_array($weight_config)) ? array(0, 10) : $weight_config;
        $weight_array = array();
        $weight_from_list = array('s', 's1');
        foreach($weight_from_list as $index => $from){
            for($i = 1; $i <= $weight_config[$index]; $i++){
                $weight_array[] = $from;
            }
        }
        $weight_array_count = count($weight_array);
        if ($weight_array_count < 1) {
            return false;
        }
        $rand_pos = rand(0, $weight_array_count - 1);
        return $weight_array[$rand_pos] . '.firstp2p.com';
    }

}
