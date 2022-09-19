<?php
        require_once dirname(__FILE__) . '/config.php';
        require_once dirname(__FILE__) . '/SynPlat.php';
        
        
        /* 写日志 */
        function saveLog($msgArr, $destination = '')
        {
            if(empty($destination))
            {
                $path = dirname(__FILE__);
            	if(!is_dir( $path . "/logger/"))
            	{
            	    if(!mkdir($path ."/logger/"))
            	        return false;
            	}
            	
                $destination = $path . "/logger/".date('Y_m_d').".log";
            }
            
            $now = date('[ c ]');
            $message = str_replace("\n", "", print_r($msgArr, true) );
            error_log("{$now} : {$message}\r\n", 3, $destination);
        }
        
        /*
	       返回 1 姓名与身份证号一致
	       2 姓名与身份证号不一致
	       3 姓名与身份证号库中无此号
	       4 姓名与身份证号 未查到数据
	       5 姓名与身份证号 查询失败
	       6 姓名与身份证号 处理异常
	       7 id5服务器连接失败 
	*/
        if($_GET['type'] == 'idno' && $_GET['name'] && $_GET['idno'])
        {
               $id5 = new SynPlatAPI(ID5_URL, ID5_USER, ID5_PASSWD, ID5_KEY, ID5_IV);
               $re = $id5->checkIdno($user_data['real_name'],  $user_data['idno']);
               
               $msg = array(
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'type' => 'idno',
                    'name' => $_GET['name'],
                    'idno' => $_GET['idno'],
                    'result' => $re,
               );
               saveLog($msg);
               
               echo '{"return":' . $re . '}';
               exit;
        }
        else if($_GET['type'] == 'sex' && $_GET['idno'])
        {
                /*
                      返回 1 是男 0 是女
                */
                
                $id5 = new SynPlatAPI(ID5_URL, ID5_USER, ID5_PASSWD, ID5_KEY, ID5_IV);
                $re = $id5 ->getSex($_GET['idno']);
                
                $msg = array(
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'type' => 'sex',
                    'idno' => $_GET['idno'],
                    'result' => $re,
                );
                saveLog($msg);
                
                echo '{"return":' . $re . '}';
                exit;
        }
?>