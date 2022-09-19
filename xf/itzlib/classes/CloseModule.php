<?php
/**
 * 关闭某个模块
 * ex: CloseModule::getInstance()->close(['invest',...]);
 */
class CloseModule{

    /**
     * instances of this class
     * @var new Object
     */
    private static $instance;

    /**
     * Get different instance of class with different params
     * @return object
     */
    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * instances of this class
     * @var new Object
     */
    private static $MODULES;

    /**
     * [__construct 初始化时加载数据]
     * @return null
     */
    public function __construct() {
        $_configPath = dirname(dirname (__FILE__)).'/config/CloseModuleConfig.php';
        try {
            self::$MODULES = include($_configPath);
        } catch (Exception $e) {
            self::$MODULES = array();
        }
    }
    public $deviceType = '';

    /**
     * [close 检查当前请求的地址是否被关闭]
     * @param  array  $modules [description]
     * @return [type]          [description]
     */
    public function close($modules = []) {
        $modules = array_filter($modules); // 快速检查是否是空数组 (http://stackoverflow.com/questions/8328983/check-whether-an-array-is-empty)
        if (empty($modules)) {
            return;
        }
        $_module = Yii::app()->controller->module->getId();
        $_controller = Yii::app()->controller->getId();
        $_action = Yii::app()->controller->getAction()->getId();
        $currentUrl = '/' . $_module
                     .'/' . $_controller
                     .'/' . $_action;
        $req_match_action = null;
        $req_match_module = array_filter($modules,
            function($module) use (&$req_match_action, &$currentUrl) {
                return array_filter(self::$MODULES[$module]['actions'], 
                    function($action) use (&$req_match_action, &$currentUrl) {
                        if (trim(strtolower($currentUrl)) === trim(strtolower($action['url']))) {
                            $req_match_action = $action;
                            return true;
                        }
                });
        });
        if (empty($req_match_module) || !$req_match_action) {
            return;
        }
        $req_match_module = self::$MODULES[current($req_match_module)];
        if ($req_match_action['type'] === 'json') { // 当前请求的模块的 response 是 json 还是页面
			if($this->deviceType=='phone' || FunctionUtil::isITZAPP()){
				Yii::app()->controller->echoJson([], 100, $req_match_module['app_error_msg']);
			}else{
				Yii::app()->controller->echoJson([], 1000423, $req_match_module['error_msg']); // 错误 1000423：服务端资源不可用
			}
        } elseif ($req_match_action['type'] === 'jsonp') {
            Yii::app()->controller->echoJson([], 1000423, $req_match_module['error_msg'], false, 'jsonp');
        } elseif ($req_match_action['type'] === 'form') {
            Yii::app()->controller->renderMsg([
                'msg' => $req_match_module['error_msg']
            ]);
        } else {
            return;
        }
        // 记录审计日志
        Yii::app()->controller->AuditLog['status'] = true;
        Yii::app()->controller->AuditLog['action'] = 'CloseModule';
        Yii::app()->controller->AuditLog['resource'] = $currentUrl;
        Yii::app()->controller->auditLogAdd(1000423, $req_match_module['error_msg']);
        Yii::app()->end();
        return;
    }
    
    /**
     *  获取当前请求所属的module（功能块）
     */
    public static function getModuleByCurrentReq(){
        $_module = Yii::app()->controller->module->getId();
        $_controller = Yii::app()->controller->getId();
        $_action = Yii::app()->controller->getAction()->getId();
        $currentUrl = '/' . $_module
                     .'/' . $_controller
                     .'/' . $_action;
 
        $req_match_module = array_filter(self::$MODULES,
            function($module) use (&$currentUrl) {
                return array_filter($module['actions'],
                    function($action) use (&$currentUrl) {
                        if (trim(strtolower($currentUrl)) === trim(strtolower($action['url']))) {
                            return true;
                        }
                });
        });

        return current(array_keys($req_match_module));
    }

    /**
     *  停服了 记录一些信息
     */
    public function recordSomething($user_id="", $close_modules=[], $record_modules=[]){
        if( empty($close_modules) || empty($record_modules) ){
            return;
        }

        // 当前请求所属module
        $current_req_module = self::getModuleByCurrentReq();

        if( in_array($current_req_module, $record_modules) && in_array($current_req_module, $close_modules) ){
            Yii::log ( "cunguan_tingfu_record_".$current_req_module.",user_id:".$user_id, CLogger::LEVEL_INFO, __METHOD__ );
        }
    }

}
