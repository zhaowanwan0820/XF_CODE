<?php
/**
 *  身份认证接口实现类
 */

namespace libs\idno;

use \libs\utils\Monitor;
use \libs\utils\Logger;
use \libs\utils\ABControl;
use \libs\utils\Alarm;

class CommonIdnoVerify
{
    const CODE_SUCCESS = '0'; //成功
    const CODE_PARAMETER_ERROR = '-111'; //数据格式错误
    const CODE_IDNO_DISMATCH = '-200'; //身份证号码不匹配（不一致）
    const CODE_IDNO_ERROR = '-300'; //身份证错误（库中无此号）

    private $_types = [];

    /**
     * 不传type使用系统配置的通道，传type为使用指定的通道
     */
    public function __construct($type = '')
    {
        //不指定具体类型，则取后台的配置
        if (empty($type)){
            $conf = app_conf('ID5_VALID');
            if(!empty($conf) && !is_numeric($conf)) {
                $this->_types = explode(',', $conf);
            }
        } else {
            $this->_types = [$type];
        }

        //榕树身份认证添加abTesting
        if ( ABControl::getInstance()->hit('rongshu')) {
            $this->_types = ['rongshu','rzb'];
            Logger::info("CommonIdnoVerify init, hit abTesting.");
        } else {
            Logger::info("CommonIdnoVerify init, do not hit abTesting.");
        }
    }

    public function checkIdno($name, $idno)
    {
        Logger::info("CommonIdnoVerify START. name:$name, idno:" . formatBankcard($idno));
        if (empty($name) || empty($idno)) {
            Logger::info("CommonIdnoVerify ERROR. parameter empty");
            return ['code'=> '-500', 'msg'=>'参数缺失'];
        }
        foreach($this->_types as $type) {
            $class = '\libs\idno\\' . ucfirst($type);
            if (!class_exists($class)) {
                Monitor::add("IdnoVerify_CLASS_NOT_EXIST");
                continue;
            }
            $result = $class::verify($name, $idno);

            $msg = "type:$type, code:{$result['code']}, msg:{$result['msg']}";
            if ($result['code'] == self::CODE_SUCCESS) {
                Monitor::add(strtoupper($type) . '_IdnoVerify_SUCC');
                Logger::info("CommonIdnoVerify SUCCESS. " . $msg);
                return $result;
            } elseif (in_array($result['code'], [self::CODE_PARAMETER_ERROR, self::CODE_IDNO_DISMATCH, self::CODE_IDNO_ERROR])) {
                Monitor::add(strtoupper($type) . '_IdnoVerify_FAIL');
                Alarm::push('IdnoVerify_'.$type, '身份认证失败', $msg);
                Logger::info("CommonIdnoVerify FAIL. " . $msg);
                return $result;
            } else {
                Monitor::add(strtoupper($type) . '_IdnoVerify_ERROR');
                Alarm::push('IdnoVerify_'.$type, '身份认证异常', $msg);
                Logger::info("CommonIdnoVerify ERROR. " . $msg);
            }
        }
        Logger::info("CommonIdnoVerify FINAL FAIL.");
        return ['code'=> '-500', 'msg'=>'验证异常'];
     }

    public function checkIdnoPhoto($name, $idno)
    {
        Logger::info("CommonIdnoVerify Photo START. name:$name, idno:" . formatBankcard($idno));
        if (empty($name) || empty($idno)) {
            Logger::info("CommonIdnoVerify Photo ERROR. parameter empty");
            return ['code'=> '-500', 'msg'=>'参数缺失'];
        }
        foreach($this->_types as $type) {
            $class = '\libs\idno\\' . ucfirst($type);
            if (!class_exists($class)) {
                Monitor::add("IdnoVerify_CLASS_NOT_EXIST");
                continue;
            }
            $result = $class::verifyPhoto($name, $idno);

            $msg = "type:$type, code:{$result['code']}, msg:{$result['msg']}";
            if ($result['code'] == self::CODE_SUCCESS) {
                Monitor::add(strtoupper($type) . '_IdnoVerify_Photo_SUCC');
                Logger::info("CommonIdnoVerify Photo SUCCESS. " . $msg);
                return $result;
            } elseif (in_array($result['code'], [self::CODE_PARAMETER_ERROR, self::CODE_IDNO_DISMATCH, self::CODE_IDNO_ERROR])) {
                Monitor::add(strtoupper($type) . '_IdnoVerify_Photo_FAIL');
                Logger::info("CommonIdnoVerify Photo FAIL. " . $msg);
                return $result;
            } else {
                Monitor::add(strtoupper($type) . '_IdnoVerify_Photo_ERROR');
                Logger::info("CommonIdnoVerify Photo ERROR. " . $msg);
            }
        }
        Logger::info("CommonIdnoVerify Photo FINAL FAIL.");
        return ['code'=> '-500', 'msg'=>'验证异常'];
    }

    /**
     * 取得生日（由身份证号）
     * @param int $id 身份证号
     * @return string
     */
    function getBirthDay($id)
    {
        switch (strlen ( $id )) {
            case 15 :
                $year = intval("19" . substr ( $id, 6, 2 ) );
                $month = intval(substr ( $id, 8, 2 ));
                $day = intval(substr ( $id, 10, 2 ));
                break;
            case 18 :
                $year = intval(substr ( $id, 6, 4 ));
                $month = intval(substr ( $id, 10, 2 ));
                $day = intval(substr ( $id, 12, 2 ));
                break;
        }
        $birthday = array ('year' => $year, 'month' => $month, 'day' => $day );
        return $birthday;
    }

    /**
     * 取得性别（由身份证号）--可能不准
     * @param int $id 身份证号
     * @return string 1 是男 0 是女
     */
    function getSex($id)
    {
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

}
