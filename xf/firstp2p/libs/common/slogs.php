<?php


class slogs{
	static $path;
	static $time;
	static $timefrag;
	static $item;
	static $line;
	static $num;

	public static function init($prd, $path=""){
		date_default_timezone_set('Asia/Shanghai');
		is_dir($path) or mkdir($path);
        $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

		self::$path = $path;
		self::$item = array('Prd'=>$prd, 'Time'=>date("Y-m-d H:i:s"), 'Ip'=>self::getip(), 'REQUEST'=>$_SERVER['REQUEST_URI'], 'QUERY'=> $queryString);
		self::$time = array(self::gettime());
		self::$timefrag = array("");
		self::$line = array();
		self::$num = array();
	}

	static function getip(){
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
			list($ip) = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR'].",1");
		} else {
			$ip = isset($_SERVER["HTTP_CLIENT_IP"]) ? ($_SERVER["HTTP_CLIENT_IP"].",2") : (isset($_SERVER["REMOTE_ADDR"]) ? ($_SERVER["REMOTE_ADDR"].",3") : "127.0.0.1,0");
		}
		return $ip;
	}

	static function gettime(){
		list($t1, $t2) = explode(' ', microtime());		
		return (floatval($t1)+intval($t2));	
	}

	static function implode_arr($arr, $split="\t"){
		if (count($arr)==0) return "";
		$res = '';
		foreach ($arr as $k=>$v){
			$res[] = $k."=".$v;
		}
		return implode($split, $res);
	}

	public static function at($frag=""){
		self::$time[] = self::gettime();
		self::$timefrag[] = $frag;
	}

	public static function set($key, $val){
		self::$item[ucfirst($key)] = $val;
	}

	public static function setnum($key){
		if (isset(self::$num[$key])) self::$num[$key]++;
		else self::$num[$key] = 1;
	}

	public static function getnum($key){
		return isset(self::$num[$key]) ? self::$num[$key] : 0;
	}

	public static function setline($key, $val){
		self::$line[strtoupper($key)] = $val;
	}

	public static function write(){
		self::$time[] = self::gettime();
		self::$timefrag[] = "end";

		self::$item['UV'] = isset($_COOKIE['_ncf1']) ? $_COOKIE['_ncf1'] : '';
		$cost = array('all'=>0);
		foreach (self::$time as $k=>$v){
			if (isset(self::$time[$k+1])){
				$frag = self::$timefrag[$k+1] != "" ? self::$timefrag[$k+1] : ('step'.$k);
				$cost[$frag] = round(self::$time[$k+1] - $v, 4);
			} else {
				$cost['all'] = round($v - self::$time[0], 4);
			}
		}
		self::$item['Cost'] = self::implode_arr($cost,",");
		self::$item['Memory'] = memory_get_usage();
		self::$item['PeakMem'] = memory_get_peak_usage();
		$ln = "\n".self::implode_arr(self::$item);
		if (count(self::$num)>0){
			$ln .= "\t".self::implode_arr(self::$num,"\t");
		}
		if (count(self::$line)>0){
			$ln .= "\n".self::implode_arr(self::$line,"\n");
		}

		$logfile = rtrim(self::$path,"/")."/slogs_".date("Ymd").".php";
		if (!file_exists($logfile)){
			file_put_contents($logfile, "<?php"."\n\nexit;\n\n/*\n\n");
		}
		$p_fo = fopen($logfile, "a+");
		fwrite($p_fo,$ln."\n");
		fclose($p_fo);
	}
}

/*
slogs::init("slogs");
slogs::set("t1","t2");
sleep(2);
slogs::write();
*/

