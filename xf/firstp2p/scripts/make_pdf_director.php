<?php
	error_reporting(E_ALL ^ E_WARNING); ini_set('display_errors',1);
require_once dirname(__FILE__).'/../app/init.php';
require_once dirname(__FILE__).'/../libs/common/app.php';
require_once dirname(__FILE__).'/../libs/common/functions.php';
$db_config = include dirname(__FILE__).'/../conf/db.conf.php';

use core\service\UserService;
$php_path = "/bin/php";
//当前的子进程数量
$child = 0;

//配合pcntl_signal使用
declare(ticks=1);
////最大的子进程数量
$max = 10;

// 找到一共有多少条记录
$user_service  = new UserService();
$total_row = $user_service->getCount();
//$total_row = 100;

$every_num = ($total_row % $max == 0) ? $total_row / $max : intval($total_row / $max) + 1;
$page_size = 500;

$loop_cnt = (($every_num % $page_size) == 0)? ($every_num / $page_size) : intval($every_num / $page_size) + 1;
//	echo "\n  total:$total_row   every_num:$every_num   loop:$loop_cnt";	die;
function sig_handler($sig) {
	global $child;
	switch($sig) {
        case SIGCHLD:
			//echo 'SIGCHLD received'."\n";
			$child--;       
	}                                                                 
}
pcntl_signal(SIGCHLD, "sig_handler");

for($i = 1; $i <= $max;$i++) {
    $child++;
    $pid = pcntl_fork();
	$conn = mysql_connect($db_config['DB_HOST'],$db_config['DB_USER'],$db_config['DB_PWD'],$db_config['DB_NAME']);
	if($conn) {
		mysql_select_db($db_config['DB_NAME'],$conn);
		mysql_query("set names utf8");
	}

   
   //如果是父进程 
    if($pid) {
        // 如果大于最大进程数则等待子进程结束
        if($child >= $max) {   
            pcntl_wait($status);
        }
    }else { //子进程
      //  echo "\n starting new child $child ".getmypid();
        $self_pid = getmypid(); //获取当前进程id
        //  $no = (($self_pid % $max) == 0) ? $max : $self_pid % $max;
        $no = $i; 
		$user_service  = new UserService();
        for($j =1;$j <= $loop_cnt;  $j++ ) {
            // 查找当前子进程需要处理的用户数据
            $offset = $max*$page_size * ($j - 1) + ($no - 1) * $page_size;
			$sql = "SELECT `id` FROM firstp2p_user WHERE `is_delete` = 0 limit $offset,$page_size";
			$rs = mysql_query($sql);
            if(!$rs) {  echo "\n  $sql $self_pid  faild!";  }
            else {
                $ids = array();
                while($row = mysql_fetch_array($rs)) {
                    $ids[] = $row['id'];
                   //echo "\n pid:".getmypid()." name:".$row['name']; 
            	}
	//			echo "\n /bin/php ".dirname(__FILE__)."/make_bill_pdf.php ".implode(',',$ids).' '.$self_pid;
               $rphp =  system($php_path." ".dirname(__FILE__)."/make_bill_pdf.php ".implode(',',$ids));
			}
            //unset($rs,$sql,$row,$start);
        }
        unset($j);
       // sleep(1);
        exit;   //退出子进程
    }
}

