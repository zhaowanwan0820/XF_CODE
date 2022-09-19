<?php
/**
 * 存管页面跳转
 * @author weiwei12<weiwei12@ucfgroup.com>
 */

namespace web\controllers\payment;
use libs\web\Form;
use libs\utils\Logger;
use web\controllers\BaseAction;

class Transit extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
    }


    public function invoke() {
        $userInfo = $GLOBALS['user_info'];
        $srv = isset($_GET['srv']) ? addslashes($_GET['srv']) : '';
        if (empty($srv)) {
            return $this->show_error('非法操作', '操作失败', 0, 0, '/', 3);
        }

        // 初始化普惠账户信息
        $accountId = \core\service\ncfph\AccountService::initAccount($userInfo['id'], $userInfo['user_purpose']);
        if (!$accountId) {
            return $this->show_error('初始化账户失败', '操作失败', 0, 0, '/', 3);
        }

        $_GET['srv'] = $srv;
        $params = $this->rpc->local('SupervisionService\changeSrv', array($_GET, $userInfo['id']));

        $srv = trim($params['srv']);
        unset($params['srv']);
        try {
            $result = $this->rpc->local(
                'SupervisionService\formFactory',
                array($srv, $accountId, $params, 'pc')
            );
        } catch (\Exception $e) {
            Logger::error(sprintf('Web::Transit, userId:%d, userPurpose:%d, accountId:%d, Error:%s', $userInfo['id'], $userInfo['user_purpose'], $accountId, $e->getMessage()));
        }

        if (empty($result['status'])) {
            $result['status'] = 0;
        }

        $this->tpl->assign('status', $result['status']);
        if ($result['status']) {
            $this->tpl->assign('form', $result['form']);
            $this->tpl->assign('formId', $result['formId']);
            $this->tpl->assign("title",'正在跳转到存管银行页面');
            $this->tpl->assign("msg",'正在跳转到存管银行页面，请稍等...');
        } else {
            $msg = !empty($result['msg']) ?  $result['msg'] : '服务错误';
            $title = $msg;
            $this->tpl->assign('title', $title);
            $this->tpl->assign('msg', $msg);
        }
    }
}