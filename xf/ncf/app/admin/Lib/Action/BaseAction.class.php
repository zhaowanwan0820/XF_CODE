<?php

class BaseAction extends Action{
    //后台基础类构造
    protected $lang_pack;
    protected $log;
    public function __construct()
    {
        parent::__construct();
        check_install();

        header('LogId:'.\libs\utils\Logger::getLogId());

        //验证IP
        if(!$this->preg_ip() && trim(strtolower($_REQUEST['m']))!='file'){
            exit("操作错误");
        }

        //重新处理后台的语言加载机制，后台语言环境配置于后台config.php文件
        $langSet = conf('DEFAULT_LANG');
        // 定义当前语言
        define('LANG_SET',strtolower($langSet));
         // 读取项目公共语言包
        if (is_file(LANG_PATH.$langSet.'/common.php'))
        {
            L(include LANG_PATH.$langSet.'/common.php');
            $this->lang_pack = require LANG_PATH.$langSet.'/common.php';

            if(!file_exists(APP_STATIC_PATH."lang.js"))
            {
                $str = "var LANG = {";
                foreach($this->lang_pack as $k=>$lang)
                {
                    $str .= "\"".$k."\":\"".$lang."\",";
                }
                $str = substr($str,0,-1);
                $str .="};";
                file_put_contents(APP_STATIC_PATH."lang.js",$str);
            }
        }
        $this->logInit();
    }


    protected function error($message, $ajax = 0) {
        if (!$this->get("jumpUrl")) {
            if ($_SERVER["HTTP_REFERER"]) {
                $default_jump = $_SERVER["HTTP_REFERER"];
            } else {
                $default_jump = u("Index/main");
            }
            $this->assign("jumpUrl", $default_jump);
        }
        parent::error($message, $ajax);
    }

    /**
     * @param $message 信息
     * @param $ajax 是否ajax
     * @param $default_jump 跳转指定url
     * @see Action::success()
     */
    protected function success($message, $ajax = 0, $default_jump = '') {
        if (!$this->get("jumpUrl")) {
            if ($default_jump == '') {
                if ($_SERVER["HTTP_REFERER"]) {
                    $default_jump = $_SERVER["HTTP_REFERER"];
                } else {
                    $default_jump = u("Index/main");
                }
            }
            $this->assign("jumpUrl", $default_jump);
        }
        parent::success($message, $ajax);
    }

    /**
     * 验证ip
     * @return bool
     */
    private function preg_ip(){
        $ip = get_client_ip();
        FP::import("libs.common.dict");
        $arr_ip_access = dict::get('IP_ACCESS');
        if (empty($arr_ip_access)) {
            return true;
        }
        if (!in_array($ip, $arr_ip_access)){
            $ip_arr = explode('.', $ip);
            $ip_two = $ip_arr[0].'.'.$ip_arr[1].'.'.$ip_arr[2];
            if (!in_array($ip_two, $arr_ip_access)){
                $ip_two = $ip_arr[0].'.'.$ip_arr[1];
                if (!in_array($ip_two, $arr_ip_access)){
                    return false;
                }
            }
        }
        return true;
    }
    protected function logInit(){

        //print_r(debug_backtrace());
        if (!isset($GLOBALS['requestId'])) {
            $GLOBALS['requestId'] = intval((ip2long($_SERVER['REMOTE_ADDR'])+time())/rand(1,100));
        }

        $post = $_POST;
        unset($post['password']);

        $adm_session = es_session::get(md5(app_conf("AUTH_KEY")));
        $this->log = array(
            'level' => 'STATS',
            'platform' => 'admin',
            'errno' => '',
            'errmsg' => '',
            'ip' =>  get_client_ip(),
            'requestId' => $GLOBALS['requestId'],
            'host' => $_SERVER['HTTP_HOST'],
            'uri' => $_SERVER['REQUEST_URI'],
            'query' => $_SERVER['QUERY_STRING'],
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'cookie' => $_COOKIE,
            'method' => $_SERVER['REQUEST_METHOD'],
            'process' => microtime(1),
            'params' => $post,
            'output' => '',
            'adminId' => $adm_session['adm_id'],
            'analyze' => '',
        );

        
    }

   protected function adminLog(){
        $this->log['process'] = sprintf("%d", (microtime(1) - $this->log['process'])*1000);
        $level = $this->log['level'];
        unset($this->log['level']);
        $_log = getLog();
        if(is_array($_log)){
            $this->log = array_merge($this->log, $_log);
        }
        $jsonLog = json_encode($this->log, JSON_UNESCAPED_UNICODE );
        $jsonLog = str_replace('\/', '/', $jsonLog);

        \libs\utils\Logger::wLog($jsonLog,\libs\utils\Logger::STATS);
    }

    public function setLog($k, $v){
        if(!empty($k)){
            $this->log[$k] = $v;
        }
    }


    public function __destruct(){
        $this->adminLog(); 
    }

    public function afterInvoke() {
        if ($this->isOpenCall()) {
            $data = $this->view->tVar;
            if ($_REQUEST['afterInvoke']) {
                $method = $_REQUEST['afterInvoke'];
                $data = \core\service\AdminProxyService::$method($data);
            }

            $arrResult = array();
            if ($this->errno == 0) {
                $arrResult["errno"] = 0;
                $arrResult["error"] = "";
                $arrResult["data"] = $data;
            } else {
                $arrResult["errno"] = $this->errno;
                $arrResult["error"] = $this->error;
                $arrResult["timestamp"] = time();
                $arrResult["data"] = "";
            }

            header('Content-type: application/json;charset=UTF-8');
            echo json_encode($arrResult, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    //来源自呼叫中心的请求
    public function isOpenCall() {
        return ('callCenter' == $_REQUEST['from']);
    }


    protected function display($templateFile='',$charset='',$contentType='text/html') {
        $this->afterInvoke();
        parent::display($templateFile,$charset,$contentType);
    }

}
?>
