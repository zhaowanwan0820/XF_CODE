<?php
class ItzSmartyView extends CApplicationComponent implements IViewRenderer {
    public $globalVal = array ();
    /**
     * @var string the file-extension for viewFiles this renderer should handle
     * for smarty templates this usually is .tpl
     */
    public $fileExtension = '.tpl';
    
    /**
     * @var int dir permissions for smarty compiled templates directory
     */
    public $directoryPermission = 0771;
    
    /**
     * @var int file permissions for smarty compiled template files
     * NOTE: BEHAVIOR CHANGED AFTER VERSION 0.9.8
     */
    public $filePermission = 0644;
    
    /**
     * @var null|string yii alias of the directory where your smarty plugins are located
     * application.extensions.Smarty.plugins is always added
     */
    public $pluginsDir = null;
    
    /**
     * @var null|string yii alias of the directory where your smarty template-configs are located
     */
    public $configDir = null;
    protected static $baseUrl;
    
    /**
     * @var array smarty configuration values
     * this array is used to configure smarty at initialization you can set all
     * public properties of the Smarty class e.g. error_reporting
     *
     * please note:
     * compile_dir will be created if it does not exist, default is <app-runtime-path>/smarty/compiled/
     *
     * @since 0.9.9
     */
    public $config = array ();
    
    /**
     * @var Smarty smarty instance for rendering
     */
    private $smarty = null;
    /**
     * autoescape variable output
     *
     * @var boolean
     */
    public $escape_html = false;
         
    
    /**
     * smarty注册函数
     * @var array
     */
    public $modifiers = array ();
    
    /**
     * Component initialization
     */
    public function init() {
        
        parent::init ();
        
        Yii::import ( 'application.vendors.*' );
        
        // need this to avoid Smarty rely on spl autoload function,
        // this has to be done since we need the Yii autoload handler
        if (! defined ( 'SMARTY_SPL_AUTOLOAD' )) {
            define ( 'SMARTY_SPL_AUTOLOAD', 0 );
        } elseif (SMARTY_SPL_AUTOLOAD !== 0) {
            throw new CException ( 'ESmartyViewRenderer cannot work with SMARTY_SPL_AUTOLOAD enabled. Set SMARTY_SPL_AUTOLOAD to 0.' );
        }
        
        // including Smarty class and registering autoload handler
        require_once ('Smarty/sysplugins/smarty_internal_data.php');
        require_once ('Smarty/Smarty.class.php');
        
        // need this since Yii autoload handler raises an error if class is not found
        // Yii autoloader needs to be the last in the autoload chain
        spl_autoload_unregister ( 'smartyAutoload' );
        Yii::registerAutoloader ( 'smartyAutoload' );
        
        $this->smarty = new Smarty ();
        $this->smarty->setEscapeHtml($this->escape_html);
        // configure smarty
        if (is_array ( $this->config )) {
            foreach ( $this->config as $key => $value ) {
                if ($key {0} != '_') { // not setting semi-private properties
                    $this->smarty->$key = $value;
                }
            }
        }
        $this->smarty->_file_perms = $this->filePermission;
        $this->smarty->_dir_perms = $this->directoryPermission;
        
        if (! $this->smarty->template_dir) {
            $this->smarty->template_dir = '';
        }
        $compileDir = isset ( $this->config ['compile_dir'] ) ? $this->config ['compile_dir'] : Yii::app ()->getRuntimePath () . '/smarty/compiled/';
        
        // create compiled directory if not exists
        if (! file_exists ( $compileDir )) {
            mkdir ( $compileDir, $this->directoryPermission, true );
        }
        $this->smarty->compile_dir = $compileDir; // no check for trailing /, smarty does this for us
        

        $this->smarty->plugins_dir [] = Yii::getPathOfAlias ( 'application.extensions.Smarty.plugins' );
        $this->smarty->plugins_dir [] = WWW_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'smartyplugins';
        if (! empty ( $this->pluginsDir )) {
            $this->smarty->plugins_dir [] = Yii::getPathOfAlias ( $this->pluginsDir );
        }
        
        if (! empty ( $this->configDir )) {
            $this->smarty->config_dir = Yii::getPathOfAlias ( $this->configDir );
        }
        
        $this->smarty->registerPlugin ( "block", "action", array (
            &$this, 
            "block" 
        ) );
        $this->smarty->registerPlugin ( 'function', 'getUrl', array (
            &$this, 
            "getUrl" 
        ) );
        $this->smarty->registerPlugin ( 'function', 'getStaticUrl', array (
            &$this, 
            "getStaticUrl" 
        ) );
	    $this->smarty->registerPlugin ( 'modifier', 'money', array (
            &$this, 
            "money" 
        ) );
        $this->smarty->registerPlugin ( 'modifier', 'maskphone', array (
            &$this, 
            "maskphone" 
        ) );
        $this->smarty->registerPlugin ( 'modifier', 'bankcard', array (
            &$this, 
            "bankcard" 
        ) );
        $this->smarty->registerPlugin ( 'modifier', 'card', array (
            &$this, 
            "card" 
        ) );
        $this->smarty->registerPlugin ( 'modifier', 'email', array (
            &$this, 
            "email" 
        ) );
        $this->smarty->registerPlugin ( 'modifier', 'money_format_strong', array (
            &$this, 
            "money_format_strong" 
        ) ); 
        $this->smarty->registerPlugin ( 'modifier', 'bank_card_new', array (
            &$this, 
            "bank_card_new" 
        ) );
        $this->smarty->registerPlugin ( 'modifier', 'remaining_time', array (
            &$this, 
            "remaining_time" 
        ) );
        foreach ( $this->modifiers as $name => $modifier ) {
            $this->smarty->registerPlugin ( "modifier", $name, $modifier );
        }
    }
    
    public function getBaseUrl() {
        if (self::$baseUrl === null) {
            self::$baseUrl = Yii::app ()->getRequest ()->getBaseUrl ();
        }
        return self::$baseUrl;
    }
    
    /**
     * Renders a view file.
     * This method is required by {@link IViewRenderer}.
     * @param CBaseController the controller or widget who is rendering the view file.
     * @param string the view file path
     * @param mixed the data to be passed to the view
     * @param boolean whether the rendering result should be returned
     * @return mixed the rendering result, or null if the rendering result is not needed.
     */
    public function renderFile($context, $sourceFile, $data, $return,$source="",$compileId=null) {
        // current controller properties will be accessible as {$this.property}
        $data ['this'] = $context;
        // Yii::app()->... is available as {Yii->...} (deprecated, use {Yii::app()->...} instead, Smarty3 supports this.)
        $data ['Yii'] = Yii::app ();
        // time and memory information
        $data ['TIME'] = sprintf ( '%0.5f', Yii::getLogger ()->getExecutionTime () );
        $data ['MEMORY'] = round ( Yii::getLogger ()->getMemoryUsage () / (1024 * 1024), 2 ) . ' MB';
        $data ['BASE_URL'] = $this->getBaseUrl ();
        $data ['_GET'] = $_GET;
        $data ['_POST'] = $_POST;
        $data ['_REQUEST'] = $_REQUEST;
        
        foreach ( $this->globalVal as $key => $val ) {
            $data ['CONST'] [$key] = $val;
        }
        $data ['CONST']['wwwPath']=WWW_DIR;
        // check if view file exists
        if ((! is_file ( $sourceFile ) || ($file = realpath ( $sourceFile )) === false) && empty($source)) {
            throw new CException ( Yii::t ( 'yiiext', 'View file "{file}" does not exist.', array (
                '{file}' => $sourceFile 
            ) ) );
        }
        
        //changed by simeng in order to use smarty debug
        foreach ( $data as $key => $value ) {
            $this->smarty->assign ( $key, $value );
        }
        
        //render or return
        if ($return) {
            ob_start ();
        }

	if(!empty($source)){
		$template = $this->smarty->createTemplate ($sourceFile, null, $compileId, $this->smarty, false);
		$template->template_source=$source;
        	$this->smarty->display ( $template );
	}else{
        	$this->smarty->display ( $sourceFile );
	}
        if ($return) {
            $res = ob_get_contents ();
            ob_end_clean ();
            return $res;
        }
    }
    
    function block($params, $content, $smarty, &$repeat, $template = 1) {
        $m = isset ( $params ['module'] ) ? $params ['module'] : "default";
        $c = isset ( $params ['controller'] ) ? $params ['controller'] : "index";
        $a = isset ( $params ['action'] ) ? $params ['action'] : "index";
        
        ob_start ();
	$_tmp = $_GET;
	$_GET=$params;
	Yii::app()->params['blockFlag'] = 1;
        Yii::app ()->runController ( "$m/$c/$a" );
	Yii::app()->params['blockFlag'] = 0;
	$_GET =$_tmp;
        return ob_get_clean ();
    }

    /**
     * getUrl
     * 获取页面的URL
     * 主要用于分类页中不同分类的组合切换、分类列表页和直接查询结果的分页URL
     * 
     * @param mixed $params
     *     array(
     *          arrBaseUrl  =>  原始的url参数
     *          arrUnset =>  要删除的URL参数
     *          arrSet   =>  要新设置的URL参数
     *          router      =>  可选参数，路由 createUrl用，没设置就不调用
     *          domain      =>  可选参数，没传情况下检查main.php的StaticDomain 只在静态化时候有效 并设置router时候有效
     *            
     *          sortIndex   =>  URL各个参数的排序依据
     *          separator   =>  URL中分隔符 
     *          hash        =>  有的url有特殊的hash参数
     *          eType       =>  编码类型 默认0  0：英文url  4：加密url 必填字段
     *          eError      =>  eType=2有效 拼音转换失败后 逻辑
     *      )
     * @access public
     * @return url build之后的url
     */
    public function getUrl($params) {
        extract ( $params );
        if(!isset($domain)){
            $domain = isset(Yii::app()->getViewRenderer()->globalVal["StaticUrlDomain"])?Yii::app()->getViewRenderer()->globalVal["StaticUrlDomain"]:"";
        }
        if (isset ( $arrUnset )) {
            if (! is_array ( $arrUnset )) {
                $arrUnset = array (
                    $arrUnset
                );
            }
            foreach ( $arrUnset as $value ) {
                if (isset ( $arrBaseUrl [$value] )) {
                    unset ( $arrBaseUrl [$value] );
                }
            }
        }
        if (isset ( $arrSet )) {
            if (! is_array ( $arrSet )) {
                throw new exception ( 'arrSet must be array: ' . print_r ( $arrSet, true ) );
            }
            foreach ( $arrSet as $key => $value ) {
                $arrBaseUrl[$key] = $value;
            }
        }


        if(isset($eType)){
            if($eType=="2") $InitialFlag = true;
            else $InitialFlag = false;

            if(!isset($separator) || !is_array($separator)){
                $separator = array('_','_');
            }

            $inSeparator = $separator[0];
            $outSeparator = (isset($separator[1]))?$separator[1]:'_';

            $url='';
            foreach($arrBaseUrl as $k=>$v){
               $arrBaseUrl[$k]=str_replace("-","--",$v);
            }

            if(isset($sortIndex)&& is_array($sortIndex)){
                $tmpBaseUrl = array();
                for($i=0;$i<count($sortIndex);$i++){
                    if(isset($arrBaseUrl[$sortIndex[$i]]) && !empty($arrBaseUrl[$sortIndex[$i]])){
                        $tmpBaseUrl[$sortIndex[$i]] = $arrBaseUrl[$sortIndex[$i]];
                    }
                }
                $arrBaseUrl = $tmpBaseUrl;
            }

            foreach($arrBaseUrl as $k=>$v){
                $url.= ((empty($url))?urlencode($k).$inSeparator.urlencode($v):$outSeparator.urlencode($k).$inSeparator.urlencode($v));
            }

            $sourceUrl = $url;

            switch ($eType) {
                // case '0': //普通静态url
                    // $url = $url;
                    // break;
                case '4': //加解密url
                    $url = self::$encodeType[4].McryptUtil::encrypt($url);
                    break;
                default:
                    $url = $sourceUrl;
                    break;
            }

            $url = (isset($router)?$domain.Yii::app()->getController()->createUrl($router,array('staticParams'=>$url)):$url.".html").(isset($hash)?'#'.$hash:"");
            return $url;

        }else{
            if(isset($router))  {
                return Yii::app()->getController()->createUrl($router,$arrBaseUrl).(isset($hash)?'#'.$hash:"");
            }else{
                return http_build_query ( $arrBaseUrl ).(isset($hash)?'#'.$hash:"");
            }
        }
    }
    
    public function money($string){
        $output = sprintf('%.2f', $string);
        if ($output != '0.00' && ceil($output) % 10000 == 0) {
            $output = intval($output/10000).'万';
	    }else{
            $output .= '元';
        }
        return $output;
    }
    public function maskphone($string){
        $pattern = "/(1\d{1,4})\d\d\d\d(\d{4,4})/";
        $replacement = "\$1****\$2";
        return preg_replace($pattern, $replacement, $string);
    }
    public function bankcard($string){
        $length = strlen($string);
        $mask = str_pad('', $length-4, '*');
    
        return substr_replace($string, $mask, 0, -4);
    }
    public function card($string){
        $length = strlen($string);
        $mask = str_pad('', $length-3, '*');
        return substr_replace($string, $mask, 3);
    }
    public function email($string){
        $email_array = explode("@", $string);
        if(count($email_array)!=2){
            return $string;
        }else{
            $length = strlen($email_array[0]);
            $mask = str_pad('', $length-2, '*');
            return substr_replace($email_array[0], $mask, 2)."@".$email_array[1];
        }
    }
    
    public function money_format_strong($string) {
        $return = '';
        $return = number_format(round($string,2),2);
        list($first,$last) = explode('.', $return);
        $return = "<strong>{$first}</strong>. <i>{$last}</i>";
        return $return;
    }
    
    public function bank_card_new($string){
        $length = strlen($string);
        $first = substr($string,0,4);
        $mask = str_pad('', $length-4, '*');
        $mask = $first . substr($mask,4);
        return substr_replace($string, $mask, 0, -4);
    }
    
    public function remaining_time($string){
        $day = floor($string/86400);
        $hour = floor(($string%86400)/3600);
        $min = floor((($string%86400)%3600)/60);
        return $day . '天' . $hour . '时' . $min . '分';
    }

}
