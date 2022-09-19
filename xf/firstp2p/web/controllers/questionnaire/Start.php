<?php

namespace web\controllers\questionnaire;

use libs\web\Form;
use web\controllers\questionnaire\QuestionBaseAction;
use libs\utils\Logger;
use core\service\marketing\QuestioinnaireService;

/**
 * 问卷调查
 */
class Start extends QuestionBaseAction
{

    protected $isAjax = true;

    public function init()
    {
        $this->form = new Form('post');
        $this->form->rules = array_merge($this->generalFormRule, []);

        if (!$this->form->validate()) {
            $this->echoJson(10001, $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if (!$this->checkCSRFToken()) {
            return $this->echoJson(10002, '非法操作');
        }
        $userId = $this->getUserId();
        $code = $this->form->data['c'];
        $rsp = (new QuestioinnaireService)->startAnswer($code, $userId, $this->getUserAgent());
        return $this->echoJson($rsp['code'], $rsp['msg']);

    }
}
