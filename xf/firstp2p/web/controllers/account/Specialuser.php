<?php
/**
 * Created by PhpStorm.
 * User: yinli
 * Date: 2018/5/2
 * Time: 15:45
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
class Specialuser extends BaseAction {
    /*
     * user表中对应的id_type字段,2为护照，3为军官证
     * user_passport表中对应的字段，1为港澳台通行证，2为护照，3为军官证
     */
    const PASSPORT = 2;
    const OFFICERS = 3;
    /**
     * 认证类型(1:军官证2:护照)
     */
    private static $idTypeConfig = array(
        1 => self::OFFICERS, // 军官证
        2 => self::PASSPORT, // 护照
    );

    public function init() {
        $this->check_login();
    }

    public function invoke() {
        RiskServiceFactory::instance(Risk::BC_REAL_NAME_AUTH)->check($GLOBALS['user_info'],Risk::ASYNC,$_POST);
        $uid = $GLOBALS['user_info']['id'];
        $type = getRequestInt("type", 1) > 2 ? 1 : getRequestInt("type", 1);

        if (!$uid) {
            return app_redirect(url("index"));
        }
        $info = $this->rpc->local('UserPassportService\getPassport',array($uid));
        //两个浏览器提交的变态问题修复
        if ($_POST){
            if ($GLOBALS['user_info']['idcardpassed'] == 3) {
                $this->template = '';
                return $this->show_error("通行证资料提交失败！已经提交过审核了,平台將在3個工作日內完成信息審核。審核結果將以短信、站內信或電子郵件等方式通知您。", "", 1);
            }
        }

        if ($GLOBALS['user_info']['idcardpassed'] == 3) {
            return $this->show_error('認證信息提交成功,平台將在3個工作日內完成信息審核。審核結果將以短信、站內信或電子郵件等方式通知您。', "", 0);
        }
        if ($info && $info['status'] == 1) {
            return app_redirect(url("index"));
        }

        if ($_POST) {//存数据
            $data = array();
            $id = getRequestInt('id');
            $idType = isset(self::$idTypeConfig[$type]) ? (int)self::$idTypeConfig[$type] : 2;
            $data['uid'] = $uid;
            $data['name'] = addslashes(trim($_POST['name']));
            $data['sex'] = addslashes($_POST['sex']);
            $data['idno'] = addslashes($_POST['idno']);
            $data['passportid'] = addslashes($_POST['passportid']);
            $data['type'] = $idType;

            //校验身份证号或护照是否存在,author:liuzhenpeng,date:2016-01-14
            if ($idType == self::OFFICERS) {
                $res = $this->rpc->local('UserService\isIdCardExist', array($data['idno']));
                if ($res === true) {
                    $this->template = '';
                    return $this->show_error("身份验证失败，如需帮助请联系客服。", "", 1, 0, '', 3, 2);
                }
            } else if ($idType == self::PASSPORT) {
                // 护照用户验证护照号码是否存在
                $res = $this->rpc->local('UserPassportService\isPassportExists', array($data['passportid']));
                if ($res === true)
                {
                    $this->template = '';
                    return $this->show_error("身份验证失败，如需帮助请联系客服。", "", 1, 0, '', 3, 2);
                }
            }

            if ($idType == self::PASSPORT) {
                $data['region'] = "护照";
            } else {
                $data['region'] = "军官证";
            }

            $data['file'] = serialize($_POST['path']);

            //证件转换为大写处理
            $data['idno'] = strtoupper($data['idno']);
            $data['passportid'] = strtoupper($data['passportid']);

            if ($id) {//修改
                $data['utime'] = get_gmtime();
                // 更新的时候，同步更新创建时间， 因为拒绝之后并没有删除此条记录，所以再次进来显示的审核时间不正确 jira-6177
                $data['ctime'] = get_gmtime();
                $re = $this->rpc->local('UserPassportService\updateByIdAndUid', array($data,$id,$uid));
            } else {
                $data['ctime'] = get_gmtime();
                $re = $this->rpc->local('UserPassportService\addInfo', array($data));
            }

            //修改用户表状态
            $this->rpc->local('UserService\updateInfo', array(array('id'=>$uid,'idcardpassed'=>3, 'id_type'=>$idType)));

            if ($re) {
                RiskServiceFactory::instance(Risk::BC_REAL_NAME_AUTH)->notify();
                $this->template = '';
                return $this->show_success($GLOBALS['lang']['SAVE_USER_SUCCESS'],"",1);
            }
            $this->template = '';
            return $this->show_error("通行证资料提交失败！", "", 1);
        } else {//显示界面
            $this->tpl->assign("info", $info);
            $this->tpl->assign("type", $type);
        }
    }
}
