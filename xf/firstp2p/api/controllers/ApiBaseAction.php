<?php

namespace api\controllers;

use libs\rpc\Rpc;
use api\conf\Error;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Aes;

/**
 * AppBaseAction
 * APP api，安全验证部分
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @author Pine wangjiansong@ucfgroup.com
 */
class ApiBaseAction extends AppBaseAction {
    // 是否自动寻找view模板文件目录
    protected $isAutoViewDir = true;

    protected $useSession = true;

    public function _before_invoke() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datas = $_POST;
        } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $datas = $_GET;
            $datas = array_diff_key($datas, array('act' => '', 'city' => '', 'ctl' => '', '1' => '', '2' => ''));
        } else {
            $this->setErr('ERR_SIGNATURE_FAIL'); // 签名不正确
            return false;
        }
        $apiVersion = isset($_SERVER['HTTP_APIVERSION']) ? $_SERVER['HTTP_APIVERSION'] : 0;

        $this->app_version = isset($_SERVER['HTTP_VERSION']) ? intval($_SERVER['HTTP_VERSION']) : 100;
        $this->setAutoViewDir();

        $userInfo = $this->getUserByToken();
        $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;

        //特殊用户处理
        if (\libs\utils\Block::isSpecialUser($userId)) {
            define('SPECIAL_USER_ACCESS', true);
            if (\libs\utils\Block::checkAccessLimit($userId) === false) {
                throw new \Exception('刷新过于频繁，请稍后再试', SHOW_EXCEPTION_MESSAGE_CODE);
            }
        }

        return true;
    }

    public function _after_invoke() {
        $this->afterInvoke();
        // 先判断template是否是绝对路径
        if ($this->isAutoViewDir) {
            $this->setAutoViewDir();
        }

        if ($this->errno != 0) {
            $this->template = $this->getTemplate('exchange_fail');
            $this->tpl->assign('errMsg', $this->error);
        }
        $o2oViewAccess = \es_session::get('o2oViewAccess');//获取来源页面的标志返回给前端
        if ($o2oViewAccess == 'pick') {
            $this->tpl->assign('dataTitle', '领取礼券');
        } else {
            $this->tpl->assign('dataTitle', '礼券详情');
        }
        $this->tpl->assign('o2oViewAccess', $o2oViewAccess);
        $this->tpl->assign('appVersion', $this->app_version);
        $this->tpl->display($this->template);
    }
}
