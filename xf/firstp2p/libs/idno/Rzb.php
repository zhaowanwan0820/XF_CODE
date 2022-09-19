<?php
/**
 *  上海援金身份认证接口实现类
 *  @author liaoyebin
 *  @since 2016-04-26
 */

namespace libs\idno;

use libs\utils\Logger;
use libs\utils\Curl;

class Rzb {
    
    const RZB_ACCOUNT = 'dflh_admin';
    
    const RZB_KEY = '0s3pzH1Y4R';

    const RZB_ACCOUNT_P = 'dflh_ph';

    const RZB_KEY_P = 'fSLgr5sk';
    
    const RZB_URL = 'https://service.sfxxrz.com/simpleCheck.ashx';

    const REQUEST_TIMEOUT = 3;

    public static function verify($name, $idno) {
        return self::_verify($name, $idno, self::RZB_ACCOUNT, self::RZB_KEY);
    }

    /*
     * 认证返回信息包含照片信息
     */
    public static function verifyPhoto($name, $idno) {
        return self::_verify($name, $idno, self::RZB_ACCOUNT_P, self::RZB_KEY_P);
    }
    /**
     * 验证姓名和身份证是否合法
     * @param type $name 姓名
     * @param type $idno 身份证号
     * @param string $accout 服务方接口账户
     * @param string  $key 服务方接口密码
     * @return type array('code'=>'0','msg'=>'认证成功')
     */
    private static function _verify($name, $idno, $accout, $key) {
        $param = $idno . $accout;
        $sign= strtoupper(md5(strtoupper(md5($param)) . $key));
        $name = urlencode($name);
        $url = self::RZB_URL . "?idNumber={$idno}&name={$name}&account=" . $accout . "&pwd=". $key. "&sign={$sign}";
        $start = microtime(true);
        $result = Curl::get($url, false, self::REQUEST_TIMEOUT);
        $cost = round(microtime(true) - $start, 3);
        Logger::info("RzbResponse. cost:{$cost}, error:". Curl::$error .", code:" . Curl::$httpCode . ", result:{$result}");

        if(false === $result) {
            return array('code' => '-110', 'msg' => '连接失败', 'Identifier' => '');
        }
        $ret = json_decode($result, true);
        if(false === $ret || !isset($ret['ResponseCode']) || !isset($ret['ResponseText'])) {
            return array('code' => '-111', 'msg' => '数据格式错误', 'Identifier' => '');
        }
        //成功，转换为0
        if($ret['ResponseCode'] == 100) {
            switch($ret['Identifier']['Result']) {
                case '一致':
                    return array('code' => '0', 'msg' => '身份证号码匹配', 'Identifier' => $ret['Identifier']);
                case '不一致':
                    return array('code' => '-200', 'msg' => '身份证号码不匹配', 'Identifier' => $ret['Identifier']);
                case '库中无此号':
                    return array('code' => '-300', 'msg' => '身份证号码错误', 'Identifier' => $ret['Identifier']);
                default:
                    return array('code' => '-111', 'msg' => '数据格式错误', 'Identifier' => '');
            }
        } else {
            return array('code' => $ret['ResponseCode'], 'msg' => $ret['ResponseText'], 'Identifier' => '');
        }
    }
}
