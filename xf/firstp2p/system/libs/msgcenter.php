<?php
// for gearman
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\Msg\EmailEvent;
use core\dao\DealMsgListModel;
use core\dao\UserModel;
use libs\utils\Logger;

/**
 * 系统消息中心，邮件和短信的处理
 * 多条消息一次入库提高效率
 * @author Zhang Ruoshi
 * $Msgcenter = new Msgcenter();
 * $notice_sms = array(...);//与模板内容对应
 * $Msgcenter->setMsg($mobile,$user_id, $notice_sms, 'TPL_DEAL_GUARANTOR_VERIFY_SMS');
 * $Msgcenter->setMsg($email,$user_id, $notice_sms, 'TPL_DEAL_GUARANTOR_VERIFY_MAIL', $title);
 * $Msgcenter->save();
 */
FP::import('libs.common.site');
FP::import("libs.common.dict");

class msgcenter
{
    private $_db = null;
    private $_tplTable = null; //模板表
    private $_queueTable = null; //消息存储表
    private $_msgList = array(); //存储消息内容
    private $_tplList = array(); //存储消息模板
    private $_tplEngin = array(); //模板引擎
    private $_cache_key = 'msgcenter_msg_template_list';
    private $_tpl_alone_prefix_cache_key = 'msgcenter_msg_template_one_';
    private $_cache_time = 600; // 10分钟 必须整型

    private $_sms_white_list;
    private $_email_white_list;

    public function __construct($isReadWhitelist=true) {
        $this->_tplTable = DB_PREFIX . 'msg_template';
        $this->_queueTable = DB_PREFIX . 'deal_msg_list';
        $this->_db = $GLOBALS['db'];
        // 不再一次性加载所有改为单条读取
        //$this->_loadTpl(); //一次性加载全部模板
        if ($isReadWhitelist) {
            // 获取短信和邮件的白名单
            $this->_sms_white_list = dict::get('SMS_WHITELIST');
            $this->_email_white_list = dict::get('EMAIL_WHITELIST');
        }
    }

    /**
     * 用模板格式化消息内容
     * @param  mix    $contentData
     * @param  int    $user_id        收取消息用户id
     * @param  string $tplName        消息模板名称
     * @param  string $title          邮件标题
     * @param  string $attachment     附件文件路径
     * @param  array  $data['is_vfs'] 是否从vfs上取数据
     * @param  array  $fromEmailData  发件人信息 ['from' => 邮件地址, 'sender' => 发件人名称]
     * @return int    暂存的消息数量
     */
    public function setMsg($dest, $user_id, $contentData, $tplName, $title = '', $attachment = '', $site = "",$data=array(), $fromEmailData = array())
    {

        if ($tplName === false) {
            $tpl = array( "type" => 1,"is_html" => 1,);  // type:0为短信  1为邮件
            $msgData    = array(
                    'dest'        => trim($dest),
                    'send_type'   => $tpl['type'],
                    'is_send'     => 0,
                    'create_time' => get_gmtime(),
                    'content'     => addslashes($contentData),
                    'site'        => empty($site) ? "网信理财" :$site,
                    'user_id'     => $user_id,
                    'title'       => $title,
                    'is_html'     => $tpl['is_html'],
                    'attachment'  => $attachment,
                    'sms_template_id'=>0,
                    'sms_content' =>'',
            );
        } else {
            if (empty($site)) {
               $site = \libs\utils\Site::getTitleById(\libs\utils\Site::getFenzhanId($user_id));
            }
            $route = $this->_getRoute($site);

            //$tpl = $this->_tplList[$tplName];
            $tpl = $this->getOneTpl($tplName);
            if($tpl['type'] == 0){//短信内容

            }
            else//邮件内容
            {
                $msgContent = $this->_fetchEmailContent($tpl['content'], $contentData, $site);
            }

            $tplConfig  = $GLOBALS['sys_config']['SMS_TEPLATE_CONFIG'];
            if (!empty($tplConfig[$tplName])) {
                 $tpl = array(
                        'name' => $tplName,
                        'is_html' => 0,
                        'send_type' => 0,
                        'type' => 0,
                        );
                $content = $msgContent['content'];

            } else {
                $content = $msgContent['content'];
                $tpl['name'] = $tplName;
            }
            $msgData    = array(
                    'dest'        => $dest,
                    'send_type'   => $tpl['type'],
                    'is_send'     => 0,
                    'create_time' => get_gmtime(),
                    'content'     => addslashes($content),
                    'site'        => $site,
                    'user_id'     => $user_id,
                    'title'       => $title,
                    'is_html'     => $tpl['is_html'],
                    'attachment'  => $attachment,
                    'sms_template_id'=> @$tplConfig[$tpl['name']],
                    'sms_content' =>$msgContent['smsContent'],
                    'route' => $route,
            );

        }
        //add by zhanglei5    判断是否从vfs读取 附件
        if (isset($data['is_vfs']) && $data['is_vfs'] == 1) {
            $msgData['is_vfs'] = $data['is_vfs'];
        }
        // email
        if ($msgData['send_type'] == 1){
            if (!empty($tplName)) {
                // 检查用户email订阅设置
                $msg_config_service = new \core\service\MsgConfigService();
                $not_send_sms = $msg_config_service->checkIsSendEmail($user_id,$tplName);
                if ($not_send_sms) {
                    return count($this->_msgList);
                }
            }

            //是否需要添加发件人信息
            if(!empty($fromEmailData)){
                $msgData['fromEmailData'] = $fromEmailData;
            }

            //文件名为文件路径的basename，不做其他处理
            if(isset($data['is_basename']) && $data['is_basename'] == 1){
                $msgData['is_basename'] = $data['is_basename'];
            }

        }
        array_push($this->_msgList, $msgData);

        return sizeof($this->_msgList);
    }

    /**
     * 变量值写入模板，生成完整的邮件消息内容
     * @param  string $tpl         类似 您好，{$notice.user_name}已经{$notice.verify}成为“{$notice.deal_name}”的借款保证人【{$notice.site_name}】
     * @param  mix    $contentData 模板中对应字段的数据
     * @return string 合并模板与数据的完整内容
     */
    private function _fetchEmailContent($tpl, $contentData, $site)
    {
        $smsContent = array();
        $content = preg_replace('/\{\$.*?\./', '{', $tpl);
        foreach ($contentData as $k => $v) {
            //if ($k != 'site_name') {
            $smsContent[] = $v;
            //}
            $content = str_replace('{' . $k . '}', $v, $content);
            $content = str_replace("admin.", "", $content);
        }
        // 消息内容替换规则 以网信理财优先级最高
        $content = \libs\utils\Site::replaceDealSiteTitleAndUrl($site,$content);
        return array('content'=>$content,'smsContent'=>$this->jsonDeal($smsContent));
    }

    /**
     * json数据 处理 中午编码问题
     * @author  caolong
     * @param unknown $data
     */
    private function jsonDeal($data = array())
    {
        $content = '';
        if (!empty($data)) {
            $content = json_encode($data);
            //iconv 的第一个参数编码  和操作系统相关.. linux 是 UCS-2BE
            $content = preg_replace("#\\\u([0-9a-f]{4})#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $content);
            $content = preg_replace("/\\\\/", '', $content);
        }

        return $content;
    }

    /**
     * 一次性取出所有模板,并格式化
     */
    private function _loadTpl()
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis === NULL){
            $tpls = $this->_db->getAll("select * from " . $this->_tplTable);
        }else {
            $tpls = unserialize($redis->get($this->_cache_key));
            if (empty($tpls)) {
                $tpls = $this->_db->getAll("select * from " . $this->_tplTable);
                $redis->setex($this->_cache_key, intval($this->_cache_time), serialize($tpls));
            }
        }
        foreach ($tpls as $k => $v) {
            $this->_tplList[$v['name']] = $v; //用模板名称做key
        }
        if (empty($this->_tplList)) {
            return false;
        }

        return true;
    }
    /**
     * 根据模板key获取单条数据
     * @param string $template_key
     */
    public  function getOneTpl($template_key){
        if (empty($template_key)){
            return false;
        }
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $sql = 'SELECT * FROM '. $this->_tplTable." WHERE name='$template_key'";
        $cache_key = $this->_tpl_alone_prefix_cache_key.$template_key;
        if ($redis !== NULL){
                $info = unserialize($redis->get($cache_key));
        }
        if (empty($info)){
            $info = $this->_db->get_slave()->getRow($sql, true);
            $redis->setex($cache_key, intval($this->_cache_time), serialize($info));
        }
        if (empty($info)){
            return false;
        }

        return $info;
    }
    /**
     * 一次性写入所有消息内容
     * @return int 成功写入的消息数量
     */
    public function save() {
        $log_info = array(__CLASS__, __FUNCTION__, APP);
        if (empty($this->_msgList)) {
            return 0;
        }
        foreach ($this->_msgList as $msg) {
            try{
                if ($msg['send_type'] == 0) {

                } else if ($msg['send_type'] == 1) {
                    $this->save_email($msg);
            }
            } catch (Exception $e) {
                Logger::error(implode(" | ", array_merge($log_info, array('exception', $e->getMessage()))));
                return false;
            }
        }
        return true;
    }

    /**
     * 邮件保存并发送
     */
    private function save_email($msg) {
        $log_info = array(__CLASS__, __FUNCTION__, APP);
        if (empty($msg['dest']) || empty($msg['content']) || empty($msg['title'])) {
            Logger::info(implode(" | ", array_merge($log_info, array(json_encode($msg), 'error params'))));
            return false;
        }

        //检查信息内容大小
        if(!$this->checkMsgSize($msg['content'],'email')){
            Logger::info(implode(" | ", array_merge($log_info, array(json_encode($msg), 'error emailSize not alow'))));
            return false;
        }

        $msgData = new \core\dao\EmailModel();
        $msgData['dest'] = $msg['dest'];
        $msgData['send_type'] = $msg['send_type'];
        $msgData['title'] = $msg['title'];
        $msgData['content'] = $msg['content'];
        $msgData['user_id'] = $msg['user_id'];
        $msgData['create_time'] = $msg['create_time'];
        $msgData['send_time'] = 0;
        $msgData['is_success'] = 2;
        $msgData['result'] = '';
        $msgData['is_send'] = 0;
        $msgData['route'] = !empty($msg['route']) ? $msg['route'] : 'firstp2p';

        $msgData['is_html'] = $msg['is_html'];
        $msgData['attachment'] = $msg['attachment'];
        $msgData['is_vfs'] = !empty($msg['is_vfs']) ? $msg['is_vfs'] : 0;

        //入库
        $rs = $msgData->save();
        if (!empty($rs)) {
            $insertId = $msgData->getId();
        }
        /*
        $insert_id_mysql = $this->save_mysql($msg); //双写mysql

        if (empty($insertId)) {
            Logger::info(implode(" | ", array_merge($log_info, array(json_encode($msg), 'insert error'))));
            $insertId = $insert_id_mysql;
        }
        */
        if (empty($insertId)) {
            \libs\utils\Monitor::add("EMAIL_SAVE_MONG_FAIL",1);//没有id 插入mongo失败
            Logger::info(implode(" | ", array_merge($log_info, array(json_encode($msg), 'completely insert error'))));
        }

        $data = array(
            'id' => $insertId,
            'address' => $msgData['dest'],
            'title' => $msgData['title'],
            'content' => stripslashes($msgData['content']),
            'is_html' => true,
            'attachment' => $msgData['attachment'],
        );
        if (isset($msgData['is_vfs'])) {
            $data['is_vfs'] = $msgData['is_vfs'];
        }

        if(!empty($msg['fromEmailData'])){
            $data['fromEmailData'] = $msg['fromEmailData'];
        }

        if(isset($msg['is_basename']) && $msg['is_basename'] == 1){
            $data['is_basename'] = $msg['is_basename'];
        }

        // add by wangyiming 20140404 发送邮件需要通过白名单
        if (empty($this->_email_white_list) || in_array($msgData['dest'], $this->_email_white_list)) {
            $event = new EmailEvent($data);
            $task_service = new GTaskService();
            $rs = $task_service->doBackground($event, 1);
            if (empty($rs)) {
                Logger::info(implode(" | ", array_merge($log_info, array(json_encode($msg), 'doBackground fail'))));
                return false;
            }
        }
        Logger::info(implode(" | ", array_merge($log_info, array($msg['dest'], $msg['user_id'], $insertId, 'done'))));
        return $insertId;
    }

    /**
     * 短信邮件保存mysql
     */
    public function save_mysql($msg) {
        $log_info = array(__CLASS__, __FUNCTION__, APP);
        $sql = "INSERT INTO " . $this->_queueTable . " (`id`, `dest`, `send_type`, `content`, `send_time`,
                         `is_send`, `create_time`, `user_id`, `result`, `is_success`, `is_html`, `title`, `is_youhui`,
                         `youhui_id` , `attachment`,`sms_template_id`,`sms_content`) VALUES ";
        $sql .= "(null,'{$msg['dest']}','{$msg['send_type']}','{$msg['content']}',0,
                         0,'{$msg['create_time']}','{$msg['user_id']}','',2,
                         '{$msg['is_html']}','{$msg['title']}',0,0,'{$msg['attachment']}','{$msg['sms_template_id']}','{$msg['sms_content']}')";

        $rs = $this->_db->query($sql);
        if (empty($rs)) {
            Logger::info(implode(" | ", array_merge($log_info, array(json_encode($msg), 'insert fail'))));
            return false;
        } else {
            $insertId = $this->_db->getOne('select @@IDENTITY');
            Logger::info(implode(" | ", array_merge($log_info, array($msg['dest'], $msg['user_id'], $insertId, 'done'))));
            return $insertId;
        }
    }

    public function _getRoute($site)
    {
         $site_list = $GLOBALS['sys_config']['SITE_LIST_TITLE'];
         $route = 'firstp2p';
         $search_rs = array_search($site, $site_list);
         if ($search_rs !== false) {
             $route_key = $search_rs;
             $route = isset($GLOBALS['sys_config']['SMS_ROUTE'][$route_key])?$GLOBALS['sys_config']['SMS_ROUTE'][$route_key]:'firstp2p';
         }

         /*
         if ( in_array($site, $site_list) ) {
             $site_list = array_flip($site_list);
             $site_key = $site_list[$site];
            //在env 配置文件中配置路由
            $route = isset ($GLOBALS['sys_config']['SMS_ROUTE'][$site_key]) ? $GLOBALS['sys_config']['SMS_ROUTE'][$site_key] : 'firstp2p';
         }
var_dump($site, $site_list, $route);
          */


         return $route;
     }

     /**
      * 检查要发送的内容是否满足最大设置，超过最大设置返回false，否则返回true
      * @param string $content
      * @param string $msgType
      * @return boolean
      */
     private function checkMsgSize($content ='',$msgType = 'msg')
     {
         switch ($msgType) {

             case 'msg':
                 //短信字数最多默认是700个
                 $msg_sms_max_size = intval(app_conf('MSG_SMS_MAX_SIZE'))? intval(app_conf('MSG_SMS_MAX_SIZE')):700;
                 return mb_strlen($content,'utf8') <= $msg_sms_max_size;
                 break;

             case 'email':
                 //邮件大小默认为10M; 1KB=1024B;1MB=1024KB=1024×1024B; 1B(byte,字节)= 8 bit;
                 $msg_email_max_size = intval(app_conf('MSG_EMAIL_MAX_SIZE'))? intval(app_conf('MSG_EMAIL_MAX_SIZE')):10;
                 return round(strlen($content)/(1024*1024),2) <= $msg_email_max_size;
                 break;

             default:
                 return true;
                 break;
         }
     }

}
