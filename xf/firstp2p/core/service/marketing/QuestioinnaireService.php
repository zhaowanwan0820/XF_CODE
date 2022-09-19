<?php

namespace core\service\marketing;

use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use core\service\marketing\BaseService;

class QuestioinnaireService extends BaseService
{

    const FROM_APP = 0;
    const FROM_PC = 1;
    const FROM_WAP = 2;
    const FROM_WX = 3;

    public function getQuestion($code, $userId)
    {
        $req = new SimpleRequestBase();
        $req->code = $code;
        $req->userId = $userId;
        $res = self::requestMarketing('NCFGroup\Marketing\Services\Questionnaire', 'getQuestionRPC', $req);
        if ($res['code']) return false;
        return $res['data'];
    }

    /**
     * from = \web\controllers\BaseAction::getUserAgent()
     */
    public function startAnswer($code, $userId, $uaInfo)
    {
        $req = new SimpleRequestBase;
        $req->code = $code;
        $req->userId = $userId;
        $req->from = self::getFrom($uaInfo);
        return self::requestMarketing('NCFGroup\Marketing\Services\Questionnaire', 'startAnswerRPC', $req);
    }

    public function answer($code, $userId, $answer, $uaInfo)
    {
        foreach ($answer as &$one) {
            if (is_string($one)) {
                $one = htmlentities($one);
                // var_dump($one);
                // $one = mysql_real_escape_string($one);
                // var_dump($one);
            }
        }
        $req = new SimpleRequestBase;
        $req->code = $code;
        $req->userId = $userId;
        $req->answer = $answer;
        $req->from = self::getFrom($uaInfo);
        return self::requestMarketing('NCFGroup\Marketing\Services\Questionnaire', 'answerRPC', $req);
    }

    public static function getFrom($uaInfo)
    {
        $f = self::FROM_APP;
        if ($uaInfo['from'] == 'web') $f = self::FROM_PC;
        elseif ($uaInfo['from'] == 'weixin') $f = self::FROM_WX;
        elseif ($uaInfo['from'] == 'mobile') {
            if (in_array($uaInfo['os'], ['ios', 'android'])) {
                $f = self::FROM_APP;
            } else {
                $f = self::FROM_WAP;
            }
        }
        return $f;
    }
}
