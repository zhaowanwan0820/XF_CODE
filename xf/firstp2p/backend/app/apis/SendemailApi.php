<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use libs\utils\Logger;

class SendemailApi extends ApiBackend{

    public function sendEmail() {
        $userEmail = $this->getParam('userEmail');
        $userId = $this->getParam('userId');
        $title = $this->getParam('title');
        if( empty($userEmail) || empty($title)){
            return $this->formatResult(false, 1,'参数不能为空');
        }
        $contentData = $this->getParam('contentData');
        $tplName = $this->getParam('tplName');
        $tplName = empty($tplName) ? false : $tplName;
        $attachment = '';
        $site = $this->getParam('site');
        $data = $this->getParam('data');
        $msgcenter = new \MsgCenter();
        $msgcenter->setMsg($userEmail, $userId, $contentData, $tplName, $title, $attachment, $site ,$data);
        $result = $msgcenter->save();
        return $this->formatResult($result);
    }

    public function batchSendEmail(){
        $batchData = $this->getParam('batchData');
        $msgcenter = new \MsgCenter();
        foreach ($batchData as $value){
            if( empty($value['userEmail']) || empty($value['title'])){
                continue;
            }
            $userEmail = $value['userEmail'];
            $userId = $value['userId'];
            $contentData = $value['contentData'];
            $tplName = $value['tplName'];
            $tplName = empty($tplName) ? false : $tplName;
            $title = $value['title'];
            $attachment = '';
            $site = $value['site'];
            $data = $value['data'];
            $msgcenter->setMsg($userEmail, $userId, $contentData, $tplName, $title, $attachment, $site ,$data);
        }

        $result = $msgcenter->save();
        return $this->formatResult($result);
    }

}
