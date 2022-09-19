<?php

namespace web\controllers\questionnaire;

use libs\web\Form;
use web\controllers\questionnaire\QuestionBaseAction;
use libs\utils\Logger;
use core\service\marketing\QuestioinnaireService;

/**
 * 问卷调查
 */
class Index extends QuestionBaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array_merge($this->generalFormRule, []);

        if (!$this->form->validate()) {
            $this->echoJson(10001, $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {

        $userId = $this->getUserId();

        $code = $this->form->data['c'];
        $res = (new QuestioinnaireService)->getQuestion($code, $userId);

        $isError = false;
        if (empty($res) || $res['question']['status'] == 0) {
            $isError = true;
        }

        $this->tpl->assign('isError', intval($isError));
        $this->tpl->assign("question", $this->formatQ($res['question']));
        $this->tpl->assign('answer', $res['answer']);
        $this->tpl->assign('isAnswered', $res['answer']['endTime'] > 0 ? 1 : 0);

        $this->tpl->assign('token', $this->form->data['token']);

        $csrfToken = $this->getCSRFToken();
        $this->tpl->assign('tokenId', $csrfToken['tokenId']);
        $this->tpl->assign('tokenCSRF', $csrfToken['tokenCSRF']);

        $this->tpl->assign('isApp', isset($_SERVER['HTTP_VERSION']) ? 1 : 0);
        if ($this->isPC()) {
            $this->template = "web/views/questionnaire/index.html";
        } else {
            $this->template = "web/views/questionnaire/index_mobile.html";
        }

    }

    private function formatQ($question)
    {

        if (empty($question)) return [];
        foreach ($question['question'] as &$item) {
            foreach ($item['options'] as &$one) {
                $one['text'] = strtoupper(chr(65 + $one['opid'])) . '.' . $one['text'];
            }
        }
        return $question;
    }

}
