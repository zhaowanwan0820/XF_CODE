<?php
namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use core\service\MsgConfigService;

class UsermsgApi extends ApiBackend {
    /**
     * 用户订阅配置是否短信通知
     * @return array
     */
    public function getAllMsgConfig() {
        $res = MsgConfigService::getAllMsgConfig();
        return $this->formatResult($res);
    }

    /**
    * 获取用户短信或者邮件订阅配置
    * @return array
    */
    public function getUserConfig() {
        $userId = $this->getParam('userId');
        $field = $this->getParam('field');
        if(empty($userId) || empty($field)) {
            return $this->formatResult(false, -1, '参数不能为空');
        }

        $msgConfigObj = new MsgConfigService();
        $res = $msgConfigObj->getUserConfig($userId, $field);
        if (!$res) {
            return $this->formatResult(false, -2, '获取用户订阅配置失败');
        }
        return $this->formatResult($res);
    }

    /**
     * 设置开关 支持 插入和更新
     * @return array
     */
    public function setSwitches() {
        $userId = $this->getParam('userId');
        $field = $this->getParam('field');
        $switches = $this->getParam('switches');
        if(empty($userId) || empty($field) || empty($switches)) {
            return $this->formatResult(false, -1, '参数不能为空');
        }

        $msgConfigObj = new MsgConfigService();
        $res = $msgConfigObj->setSwitches($userId, $field, $switches);
        if (!$res) {
            return $this->formatResult(false, -2, '设置用户订阅配置失败');
        }
        return $this->formatResult($res);
    }

    /**
     * 检查配置项
     * @return array
     */
    public function checkMsgConfig() {
        $msgConfig = $this->getParam('msgConfig');
        $type = $this->getParam('type');
        if(empty($msgConfig) || empty($type)) {
            return $this->formatResult(false, -1, '参数不能为空');
        }

        $msgConfigObj = new MsgConfigService();
        $res = $msgConfigObj->checkMsgConfig($msgConfig, $type);
        return $this->formatResult($res);
    }

    /**
     * 用户订阅配置是否短信通知
     * @return array
     */
    public function checkIsSendSms() {
        $userId = $this->getParam('userId');
        $smsTemplateId = $this->getParam('smsTemplateId');
        if(empty($userId) || empty($smsTemplateId)) {
            return $this->formatResult(false, -1, '参数不能为空');
        }

        $msgConfigObj = new MsgConfigService();
        $res = $msgConfigObj->checkIsSendSms($userId, $smsTemplateId);
        return $this->formatResult($res);
    }

    /**
     * 网信普惠是否发短信
     * @return array
     */
    public function checkP2pcnIsSendSms() {
        $siteId = $this->getParam('siteId');
        $tplName = $this->getParam('tplName');
        $checkOption = $this->getParam('checkOption', 0);
        if(empty($siteId) || empty($tplName)) {
            return $this->formatResult(false, -1, '参数不能为空');
        }

        $msgConfigObj = new MsgConfigService();
        $res = $msgConfigObj->checkP2pcnIsSendSms($siteId, $tplName, $checkOption);
        return $this->formatResult($res);
    }

    /**
     * 用户订阅配置是否邮件通知
     * @return array
     */
    public function checkIsSendEmail() {
        $userId = $this->getParam('userId');
        $tplKey = $this->getParam('tplKey');
        if(empty($userId) || empty($tplKey)) {
            return $this->formatResult(false, -1, '参数不能为空');
        }

        $msgConfigObj = new MsgConfigService();
        $res = $msgConfigObj->checkIsSendEmail($userId, $tplKey);
        return $this->formatResult($res);
    }
}