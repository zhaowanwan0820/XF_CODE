<?php
/**
 * 
 * @desc 批量注册用户，即批量导入用户表, 基本要求如下：
 * --------------------------------------------------------------
 * 1、 后台批量生成一批账户；
 * 2、 通过短信通知，发送账户名、密码至用户手机；
 * 3、 用户第一次登陆时进入修改密码界面http://www.firstp2p.com/user/editpwd，修改密码；
 * 4、 当修改成功后，进入firstp2p首页；当不进行操作时，下次登陆重复3；
 * --------------------------------------------------------------
 * 生成规则：
 * 会员名称：为附件中手机号 补充：手机号前面加H
 * 会员邮件：设置为空
 * 手机号：为附件中手机号
 * 会员密码：随机生成，规则同修改密码，大小写数字的组合
 * 会员所属网站：汇源集团员工（后台已新建会员组，编号17）
 * 会员等级：01
 * 会员等级失效时间：生成时间后退20年
 * 返利系数：1.0000
 * 注册站点：firstp2p
 * 是否内部员工：否
 * ---------------------------------------------------------------
 * 1、 短信内容：您在网信理财平台的用户名为{}，密码为{}，为确保账户安全，请尽快登陆网站（www.firstp2p.com）重新设置。【网信金融】
 * 2、 参数规则：用户名：取账户名称字段，密码：取随机生成的密码字段；
 * 3、 账号有效期：无
 * 4、 短信发送时间：账号生成完毕后，统一发送；
 * ----------------------------------------------------------------
 *  执行方式为命令行 php register_user_batch.php > register_user_batch.txt
 */
die('如果使用该脚本请改造为调用db底层插入或查询操作接口.user表手机号和身份证号码会被自动加密保存!');//add by lvbaosong 2016.4.29
$users = array(
    array('刘中良', '18364381555', '370323198910012010', ''),
    array('李辉', '13722808032', '131127198310203615', ''),
    array('崔春宝', '13831805229', '13110219830830361X', ''),
    array('宋晓潘', '15030846243', '133001197910264495', ''),
    array('高铎芮', '18703386682', '131182199106265033', ''),
    array('孟德泉', '15030816111', '133025197205163016', ''),
    array('沈新扣', '13785842063', '133025197609013049', ''),
    array('李美叶', '13403381502', '13302519810909262X', ''),
    array('李向阳', '15028766712', '131181198709261938', ''),
    array('王春平', '15100385680', '131182198302023029', ''),
    array('崔颖春', '15127807118', '133025198503271626', ''),
    array('刘浩', '18730871327', '131124198805093236', ''),
    array('卢佳旭', '15132895210', '131127199310163857', ''),
    array('张浩', '15350800052', '133025198105132612', ''),
    array('杜岩晴', '18731817296', '131102198811161436', ''),
    array('郝晨曦', '13784835599', '131182199505255019', ''),
    array('程洪超', '15127823418', '370323199509182018', ''),
    array('刘倩', '15100386948', '131125199005203469', ''),
    array('董义东', '18731193216', '130530199402162017', ''),
    array('崔云锋', '15033181560', '133022197804040772', ''),
    array('张进', '15175826726', '131182199106303036', ''),
    array('梁亚寒', '18331822618', '131123198608071226', ''),
    array('张卫焕', '18630593631', '13118219931010304X', ''),
    array('班娜娜', '13653189601', '131102198601213621', ''),
    array('张志翠', '15503247337', '131102198411133620', ''),
    array('李莹', '13833843143', '131122199210193221', ''),
    array('赵翠', '15031856830', '131182198504142624', ''),
    array('崔跃宗', '18931815158', '13302519760909263X', ''),
    array('王朋旭', '13273323395', '133025197809041618', '')
                
);

set_time_limit(0);
ini_set('memory_limit', '512M');
require_once(dirname(dirname(__FILE__)).'/app/init.php');

use core\dao\UserModel;

class Register {
	
	/**
	 * 生成密码
	 */
	public static function getPasswd($length = 8) {
	    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $strlen = strlen($chars) - 1;
    	$str = '';
		for ($i=0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, $strlen), 1);
		}
		return $str;
	}

	/**
	 * 验证身份证
	 */
	public static function validateId($id, $name) {
	    if (!preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $form_data['idno'])) {
	       return false;	
	    }
	    return true;
	}

	/**
	 * 验证手机号码
	 */
	public static function validatePhoneNumber($num) {
	    if (!$num) {
	        return false;
	    }
	    return preg_match('#^13[\d]{9}$|14^[0-9]\d{8}|^15[0-9]\d{8}$|^18[0-9]\d{8}$#', $num) ? true : false;
	}
	
	/**
	 * 获取性别信息
	 * @param $id
	 * @return number
	 */
	public static function getSex($id) {
	    switch (strlen ( $id )) {
	    	case 15 :
	    	    $sexCode = substr ( $id, 14, 1 );
	    	    break;
	    	case 18 :
	    	    $sexCode = substr ( $id, 16, 1 );
	    	    break;
	    }
	    if ($sexCode % 2) {
	        return 1;  // 男
	    } else {
	        return 0;  // 女
	    }
	}
	
	/**
	 * 取得生日（由身份证号）
	 * @param int $id 身份证号
	 * @return string
	 */
	public static function getBirthDay($id) {
	    switch (strlen ( $id )) {
	    	case 15 :
	    	    $year = intval("19" . substr ( $id, 6, 2 ) );
	    	    $month = intval( substr ( $id, 8, 2 ) );
                $day = intval ( substr ( $id, 10, 2 ) );
                break;
            case 18 :
                $year = intval ( substr ( $id, 6, 4 ) );
                $month = intval ( substr ( $id, 10, 2 ) );
                $day = intval ( substr ( $id, 12, 2 ) );
                break;
        }
        return array (
                        $year,
                        $month,
                        $day 
        );
    }
}

use core\service\user\BOFactory;
$bo = BOFactory::instance ( 'web' ); // 生成密码
function getRegisterInfo($name, $phoneId, $identifyId, $email, $idPass, $passwd = '12345678') {
    global $bo;
    // 设置出生日期
    list ( $year, $month, $day ) = Register::getBirthDay ( $identifyId );
    
    $userInfo = array (
                    'user_name' => "'H" . $phoneId . "'", // 会员名称，为附件中手机号
                    'user_pwd' => "'" . $bo->compilePassword ( $passwd ) . "'", // 随机生成，规则同修改密码，大小写数字的组合
                    'idno' => "'" . $identifyId . "'",
                    'real_name' => "'" . $name . "'",
                    'group_id' => 17, // 用户组id
                    'is_effect' => 1, // 帐户状态，1为有效果，0为无效
                    'create_time' => get_gmtime (), // 创建时间
                    'updaet_time' => get_gmtime (), // 创建时间
                                                    // 'site_id' => '', //首次登录的分站ID
                    'email' => "'" . (($email != 1 && ! empty ( $email )) ? $email : '') . "'", // 邮箱地址 默认为空
                    'mobile' => $phoneId, // 手机
                    'mobilepassed' => 1, // 手机认证
                                          // 'level_id' => '01', //信用等级
                    'is_staff' => 0, // 是否内部员工
                    'channel_pay_factor' => 1.0000, // 返利系数
                    'coupon_level_id' => "'" . '01' . "'", // 会员等级
                    'coupon_level_valid_end' => get_gmtime () + 20 * 365 * 24 * 60 * 60,
                    'force_new_passwd' => 1,
                    'sex' => Register::getSex ( $identifyId ),
                    'byear' => intval ( $year ),
                    'bmonth' => intval ( $month ),
                    'bday' => intval ( $day ),
                    'idcardpassed' => intval ( $idPass ),
                    'idcardpassed_time' => get_gmtime () 
    );
    return $userInfo;
}

//验证身份证信息
class IdnoVerifySelf {
    private $_wsdlFile;
    private $_license;
    private $_response;
    public function __construct() {
        $this->_license = app_conf ( 'license' );
        $this->_wsdlFile = dirname(dirname (__FILE__)) . '/public/NciicServices.wsdl';
    }
    
    /**
     * 验证姓名和身份证是否合法
     *
     * @param type $name 姓名
     * @param type $idno 身份证号
     * @return type array('code'=>'0','msg'=>'认证成功')
     */
    public function checkIdno($name, $idno) {
        $condition = <<<XML
<?xml version="1.0" encoding="UTF-8" ?><ROWS><INFO><SBM>{$idno}</SBM></INFO><ROW><GMSFHM>公民身份号码</GMSFHM><XM>姓名</XM></ROW><ROW FSD="110000" YWLX="身份证认证"><GMSFHM>{$idno}</GMSFHM><XM>{$name}</XM></ROW></ROWS>
XML;
        /**
         * $xml= '<?xml version="1.0" encoding="UTF-8" ?><RESPONSE errorcode="-80" code="0" countrows="1"><ROWS><ROW><ErrorCode>-80</ErrorCode><ErrorMsg>授权文件格式错误</ErrorMsg></ROW></ROWS></RESPONSE>';
         *
         * $xml = '<?xml version="1.0" encoding="UTF-8" ?><ROWS><ROW no="1"><INPUT><gmsfhm>61052119****</gmsfhm><xm>王路</xm></INPUT><OUTPUT><ITEM><gmsfhm /><result_gmsfhm>一致</result_gmsfhm></ITEM><ITEM><xm /><result_xm>一致</result_xm></ITEM></OUTPUT></ROW></ROWS>';
         */
        $options = array (
                        'connection_timeout' => 10  // 会使连接请求限定在10秒内，但已连接上的慢速传输不受时间限制
                );
        try {
            $client = new \SoapClient ( $this->_wsdlFile, $options );
            $params = array (
                'inLicense' => $this->_license,
                'inConditions' => $condition 
            );
            
            $ret = $client->nciicCheck ( $params );
            $this->_response = $ret;
            if ($ret && ! empty ( $ret->out )) {
                
                $xmlData = simplexml_load_string ( $ret->out );
                if (! empty ( $xmlData )) {
                    $ret = $this->parseXml ( $xmlData );
                    return $ret;
                } else {
                    return array ('code' => '-998', 'msg' => '数据错误', 'response' => $this->_response );
                }
            } else {
                return array ('code' => '-999', 'msg' => '数据错误', 'response' => $this->_response);
            }
        } catch ( SoapFault $e ) {
            return array ('code' => '-810', 'msg' => '系统错误', 'response' => $e->getMessage () 
            );
        } catch ( SoapFault $fault ) {
            $error = "Fault! code:" . $fault->faultcode . ", string: " . $fault->faultstring;
            return array ('code' => '-820', 'msg' => '系统错误', 'response' => $error);
        }
    }
    private function parseXml($xmlData) {
        $xml_root = $xmlData->getName ();
        if ($xml_root == 'RESPONSE') {
            $row = $xmlData->ROWS->ROW;
        } elseif ($xml_root == 'ROWS') {
            $row = $xmlData->ROW;
        } else {
            $return = array ('code' => '-210', 'msg' => '服务异常', 'response' => $this->_response );
        }
        if (isset ( $row->ErrorCode ) === FALSE) {
            $items = $row->OUTPUT->ITEM;
            if (isset ( $items [0]->errormesage ) === FALSE && isset ( $items [1]->errormesage ) === FALSE) {
                if ($items [0]->result_gmsfhm == '一致' && $items [1]->result_xm == '一致') {
                    $return = array ('code' => '0', 'msg' => '认证成功', 'response' => $this->_response);
                } else {
                    $return = array ('code' => '-110', 'msg' => '姓名身份证号不匹配', 'response' => $this->_response);
                }
            } else {
                $return = array ('code' => '-100', 'msg' => '账号没有找到', 'response' => $this->_response);
            }
        } else {
            $return = array ('code' => '-200', 'msg' => '服务异常', 'response' => $this->_response);
        }
        return $return;
    }
}


$host = app_conf('DB_HOST');
$port = app_conf('DB_PORT');
$user = app_conf('DB_USER');
$pass = app_conf('DB_PWD'); 
$dbname = app_conf('DB_NAME');

//$pdo = new PDO("mysql:dbname=$dbname;host=$host;port=$port", $user, $pass);
//$pdo->exec("SET NAMES 'utf8';");
$con = mysql_connect("$host:$port", $user, $pass);
if (!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_query("set names utf8", $con);
mysql_select_db($dbname, $con);

$idno = new IdnoVerifySelf();//\system\id5\IdnoVerify(); //验证身份证信息
foreach ($users as $row) {
    $line = implode("\t", $row); //方便记录
	//查询确认手机号与身份证号不存在
    // 检查身份证号是否在身份证注册表中
    $row[2]= strtoupper(trim($row[2]));
	$query = mysql_query("SELECT `user_name` FROM `firstp2p_user` WHERE `user_name`='H{$row[1]}' OR `mobile`='{$row[1]}' OR `idno`='{$row[2]}'", $con);
	$result = mysql_fetch_assoc($query);
	if (!empty($result)) {
		echo "ERR\t$line\tphone number is already exits.\n";
		continue;
	}
	//验证用户
	$resultId = $idno->checkIdno($row[0], $row[2]);
	$code = 1;
	if ($resultId['code'] != 0) {
	    echo "ERR\t$line\t".json_encode($resultId)."\tid not passed\n";
	    $code = 0;
	    continue;
	} else {
	    echo "INFO\t", $row[1], "\t", $row[2], "\t", json_encode($resultId), "\n";
	}
	$pass = Register::getPasswd();
	$userInfo = getRegisterInfo($row[0], $row[1], $row[2], $row[3], $code, $pass);
	
	$sql = "INSERT INTO `firstp2p_user` (`user_name`, `user_pwd`, `idno`, `real_name`, `group_id`, `is_effect`, `create_time`, `update_time`, `email`, `mobile`, `mobilepassed`, `is_staff`, `channel_pay_factor`, `coupon_level_id`, `coupon_level_valid_end`, `force_new_passwd`,`sex`, `byear`, `bmonth`, `bday`, `idcardpassed`, `idcardpassed_time`) VALUES ";
	$sql .= "(".implode(', ', array_values($userInfo)).");";
    $count = mysql_query($sql, $con);//$pdo->exec($sql);
    if (!$count) {
        echo "ERR\t$line\t$sql\tInsert DB failed.\n";
	} else {
	    $msg= "H{$row[1]},{$pass}";
	    $res = \SiteApp::init()->sms->send($row[1], $msg, $GLOBALS['sys_config']['SMS_TEPLATE_CONFIG']['TPL_SMS_FIRSTP2P_ACCOUNT'], 0);
	    if ($res['status'] != 1) {
            echo "ERR\t", $row[1], "\t", $row[2], "\t", $pass, "\t", json_encode($res), "\t", $msg, "\n";
	    } else {
            echo "INFO\t", $row[1], "\t", $row[2], "\t", $pass, "\t", json_encode($res), "\t", $msg, "\n";
	    }
	}
	usleep(500000);
}
exit("done.\n");


