<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class BaseAction extends Action{
    //后台基础类构造
    protected $lang_pack;
    protected $log;
    public $is_cn = false;

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
        if(strchr(strtolower($_SERVER['HTTP_HOST']),".cn") == ".cn") {
            $this->is_cn = true;
        }
        $this->assign('is_cn', $this->is_cn);
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
     *  等待确认的订单
     *
     * @Title: wait_confirm_factory_data
     * @Description: todo(这里用一句话描述这个方法的作用)
     * @param
     * @return $deal_arr
     * @author Liwei
     * @throws
     *
     */
    protected function wait_confirm_factory_data(){
            //取出所有订单
            $deal_list = MI("Deal")->where("deal_status = 0 AND parent_id <= 0 AND is_delete != 1 AND publish_wait != 1")->select();
            $deal_arr = array();

            foreach ($deal_list as $deal){

                $parent_deal_info = NULL;

                $guarantor_status = $this->get_guarantor_status($deal['id']);

                if($deal['is_update'] == 1 || ($guarantor_status != 2 && $guarantor_status != "")){
                    $deal_arr[] = $deal;
                }
            }
            return $deal_arr;
    }


    /**
     * 已经确认的单子
     *
     * @Title: confirm_factory_data
     * @Description:
     * @param
     * @return $deal_arr
     * @author Liwei
     * @throws
     *
     */
    protected function confirm_factory_data(){
        //取出所有订单
        $deal_list = M("Deal")->where("deal_status = 0 AND parent_id <= 0 AND is_delete != 1 AND publish_wait != 1")->select();

        $deal_arr = array();
        foreach ($deal_list as $deal){

            $parent_deal_info = NULL;

            $guarantor_status = $this->get_guarantor_status($deal['id']);

            if($deal['is_update'] == 0 && ($guarantor_status == 2 || $guarantor_status == "")){
                $deal_arr[] = $deal;
            }
        }
        return $deal_arr;
    }

    /**
     * 获取状态担保人是否确认
     *
     * @Title: get_guarantor_status
     * @Description:
     * @param $deal_id 订单ID
     * @return return_type
     * @author Liwei
     * @throws
     *
     */
    protected function get_guarantor_status($deal_id){

        if(empty($deal_id)) return false;

        $status = MI("Deal_guarantor")->field("status")->where("deal_id = ".intval($deal_id))->find();

        return $status['status'];
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

    // 根据 domain 或者 _REQUEST 获取 deal_type 值
    public function getDealType($deal_type = 0)
    {
        if ($this->is_cn) {
            $deal_type = (0 === $deal_type) ? \core\dao\DealModel::DEAL_TYPE_GENERAL : $deal_type;
        } else {
            $deal_type = isset($_REQUEST['deal_type']) ? $_REQUEST['deal_type'] : $deal_type;
        }
        return $deal_type;
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
        parent::display($templateFile, $charset, $contentType);
    }
}
?>
