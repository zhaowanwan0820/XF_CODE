<?php

/**
 * Passport.php
 *
 * @date 2014年5月22日
 * @author yangqing <yangqing@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
class Passport extends BaseAction {
    /**
     * 港澳台类型配置(1:香港2:澳门3:台湾)
     */
    private static $idTypeConfig = array(
        1 => 4, // 港澳居民来往内地通行证,对应user表的id_type
        2 => 4, // 港澳居民来往内地通行证,对应user表的id_type
        3 => 6, // 台湾居民往来大陆通行证,对应user表的id_type
    );

    public function init() {
        $this->check_login();
    }

    public function invoke() {
        RiskServiceFactory::instance(Risk::BC_REAL_NAME_AUTH)->check($GLOBALS['user_info'],Risk::ASYNC,$_POST);
        $uid = $GLOBALS['user_info']['id'];
        $type = getRequestInt("type", 1) > 3 ? 1 : getRequestInt("type", 1);
        $type = !empty(self::$idTypeConfig[$type]) ? (int)$type : 1;
        $conf = array(
            '1' => array("h" => 'H', 'pass' => '<b class="passport-type-initial">H</b>12345678&nbsp;00', 'id' => 'A123456(B)', 'name' => '香港', 'type' => $type, 'img' => './static/default/images/hk.jpg'),
            '2' => array("h" => 'M', 'pass' => '<b class="passport-type-initial">M</b>12345678&nbsp;00', 'id' => '1234567(8)', 'name' => '澳門', 'type' => $type, 'img' => './static/default/images/ma.jpg'),
            '3' => array("h" => '', 'pass' => '12345678 01', 'id' => 'A123456789', 'name' => '臺灣', 'type' => $type, 'passname' => '通行證內頁', 'img' => './static/default/images/tw.jpg'),
        );
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

            $data['uid'] = $uid;
            $data['name'] = addslashes(trim($_POST['name']));
            $data['region'] = addslashes($_POST["region"]);
            $data['sex'] = addslashes($_POST['sex']);

            if ($_POST['type']) {
                $data['idno'] = addslashes(trim($_POST['idno'])) . '(' . addslashes(trim($_POST['idno_suffix'])) . ')';
            } else {//臺灣
                $data['idno'] = addslashes(trim($_POST['idno'])) . addslashes(trim($_POST['idno_suffix']));
            }

            //校验身份证号或护照是否存在,author:liuzhenpeng,date:2016-01-14
            $res = $this->rpc->local('UserService\isIdCardExist',array($data['idno']));
            if($res === true){
                $this->template = '';
                return $this->show_error("身份验证失败，如需帮助请联系客服。", "", 1, 0, '', 3, 2);
            }

            $data['passportid'] = addslashes($_POST['type']) . addslashes($_POST['passportid']) . ' ' . addslashes($_POST['passportid_suffix']);
            $data['valid_date'] = addslashes($_POST['valid_date']);
            $data['birthday'] = addslashes($_POST['birthday']);
            $data['file'] = serialize($_POST['path']);

            //证件转换为大写处理
            $data['idno'] = strtoupper($data['idno']);
            $data['passportid'] = strtoupper($data['passportid']);

            if ($id) {//修改
                $data['utime'] = get_gmtime();
                $re = $this->rpc->local('UserPassportService\updateByIdAndUid', array($data,$id,$uid));
            } else {
                $data['ctime'] = get_gmtime();
                $re = $this->rpc->local('UserPassportService\addInfo', array($data));
            }

            //修改用户表状态
            $idType = isset(self::$idTypeConfig[$type]) ? (int)self::$idTypeConfig[$type] : 4;
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
            $this->tpl->assign("type", $conf[$type]);
        }
    }
}
