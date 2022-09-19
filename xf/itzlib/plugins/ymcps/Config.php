<?php	
	/**
	 * Config类加载配置文件"yiqifa-config.php",并读取其中相应的参数值。<p>
	 * 静态方法{@link #getString(String key)}根据传递"key"获取配置文件"yiqifa-config.properties"的对应的值,没有对应的参数返回空串。
	 * 
	 * @author zhangxing 
	 * @version 1.0.0
	 * @see com.emar.yiqifa.api.advertiser.AdEnter
	 * @since 0.1.0
	 */		
	class Config{
		function Config(){
			$file = WWW_DIR . '/itzlib/plugins/ymcps/yiqifa-config.php';
			if(file_exists($file)){
				include_once $file;
			}else{
				throw new Exception("Not found yiqifa-config file!", 16, "");
				trigger_error();
			}
		}
		
		function getString($paramname){
			return constant($paramname);
		}
	}
?>
