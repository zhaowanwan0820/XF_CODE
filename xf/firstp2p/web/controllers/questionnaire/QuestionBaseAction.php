<?php

namespace web\controllers\questionnaire;

use libs\web\Form;
use web\controllers\BaseAction;

class QuestionBaseAction extends BaseAction
{

    protected $isAjax = false;

    protected $generalFormRule = [
        'token' => ['filter' => 'string'],
        'c' => ['filter' => 'required', 'message' => '参数错误'],
    ];

    public function getUserId()
    {
        $this->checkLogin();
        return $GLOBALS['user_info']['id'];
    }

    public function echoJson($code, $msg, $data = null)
    {
        header("Content-type: application/json; charset=utf-8");
        $csrfToken = $this->getCSRFToken();
        $data['tokenId'] = $csrfToken['tokenId'];
        $data['tokenCSRF'] = $csrfToken['tokenCSRF'];
        echo json_encode([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
        die;
    }

    public function isAjax()
    {
        return $this->isAjax;
    }

    public function isPC()
    {
        $ua = $this->getUserAgent();
        if ($ua['from'] == 'web') return true;
        return false;
    }

    protected function getCSRFToken()
    {
        $tokenId = round(microtime(true) * 1000);
        return [
            'tokenId' => $tokenId,
            'tokenCSRF' => mktoken($tokenId),
        ];
    }

    protected function checkCSRFToken()
    {
        $tokenId = empty($tokenId) ? $_REQUEST['tokenId'] : $tokenId;
        $token = $_REQUEST['tokenCSRF'];
        if( empty($tokenId) || empty($token) )
            return 0;

        $k = 'ql_token_' . $tokenId;

        if($token == $_SESSION[$k])
        {
            $_SESSION[$k] = "";
            unset($_SESSION[$k]);
            return 1;
        }
        else
            return 0;
    }

    protected function checkLogin()
    {
        if (empty($GLOBALS['user_info'])) {

            $token = $this->form->data['token'];
            if ($token) {
                $token_info = $this->rpc->local('UserService\getUserByCode', [$token]);
                if ($token_info['user']) {
                    $GLOBALS['user_info'] = $token_info['user'];
                    return true;
                }
            }

            $url = 'http://';
            if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
                || $_SERVER['SERVER_PORT'] == '443') {
                $url = 'https://';
            }
            if ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
                $url .= $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
            } else {
                $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            }

            $current_url = urlencode($url);
            $location_url = !empty($current_url) ? "user/login?backurl=" . $current_url : "user/login";
            set_gopreview();
            if (!$this->isPC()) {
                $location_url .= strpos($location_url, '?') ? '&tpl=questionnaire' : '?tpl=questionnaire';
            }
            if ($this->isAjax()) {
                return $this->echoJson(10000, '请登录', ['loginUrl' => $location_url]);
            } else {
                return app_redirect(url($location_url));
            }
        }
        return true;
    }

}
