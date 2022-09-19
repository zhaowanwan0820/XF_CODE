<?php

/**
 * @file
 * OAuth2 Library Mysql Implementation.
 */
require_once __DIR__ . "/lib/Oauth2.php";

/**
 * OAuth2 Library PDO DB Implementation.
 */
class PDOOAuth2 extends OAuth2 {

    private $user_id = 0;

    public function __construct($config = array()) {
        parent::__construct($config);

        // 去掉notice
        if (isset($GLOBALS['user_info']) && is_array($GLOBALS['user_info'])) {
            $this->user_id = intval($GLOBALS['user_info']['id']);
        } else {
            $this->user_id = $this->getVariable('user_id', $this->user_id);
        }
    }

    /**
     * Handle exceptional cases.
     */
    private function handleException($e) {
        exit("invalid param!");
    }

    /**
     *
     * @param $client_id Client identifier to be stored.
     * @param $client_secret Client secret to be stored.
     * @param $redirect_uri Redirect URI to be stored.
     */
    public function addClient(&$client_id, &$client_secret, &$redirect_uri) {
        $client_id = $this->generateKey(true);
        $client_secret = $this->generateKey();
        // TODO
    }

    public function checkClientCredentials($client_id, $client_secret = null) {
        return true;
        $result = $GLOBALS['db']->getRow(sprintf("SELECT client_secret FROM oauth_client WHERE client_id='%s'", $this->checkParam($client_id)));
        if ($result ['client_secret'] === $client_secret) {
            return true;
        }
        return false;
    }

    /**
     * Implements OAuth2::getRedirectUri().
     */
    protected function getRedirectUri($client_id) {
        $client_list = $GLOBALS['sys_config']['OAUTH_SERVER_CONF'];
        if (isset($client_list[$client_id]) === true) {
            return $client_list[$client_id]['redirect_uri'];
        } else {
            return false;
        }
        /* try {
          $result = $GLOBALS['db']->getRow(sprintf("SELECT redirect_uri FROM oauth_clients WHERE client_id='%s'", $client_id));

          if ($result === false)
          return false;
          return isset($result ["redirect_uri"]) && $result ["redirect_uri"] ? $result ["redirect_uri"] : null;
          } catch(Exception $e) {
          $this->handleException("can not get redirect uri");
          } */
    }

    /**
     * Implements OAuth2::getAccessToken().
     */
    public function getAccessToken($oauth_token) {
        try {
            $sql = sprintf("SELECT user_id, client_id, expires, scope FROM oauth_token WHERE access_token='%s'", $this->checkParam($oauth_token));
            $result = $GLOBALS['db']->getRow($sql);
            if ($result ['user_id']) {
                return $result ['user_id'];
            }
            return false;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function getInfoByAccessToken($oauth_token) {
        try {
            $sql = sprintf("SELECT user_id, client_id, expires, scope FROM oauth_token WHERE access_token='%s'", $this->checkParam($oauth_token));
            $result = $GLOBALS['db']->getRow($sql);
            if ($result ['user_id']) {
                return $result;
            }
            return false;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * 通过access_token获得client信息
     * @param type $oauth_token
     * @return boolean
     */
    public function getClientInfoByAccessToken($oauth_token) {
        try {
            $time = time();
            $sql = sprintf("SELECT `user_id`, `client_id`, `expires`, `scope` FROM oauth_token WHERE access_token='%s' and expires > %s", $this->checkParam($oauth_token), $this->checkParam($time));
            $result = $GLOBALS['db']->getRow($sql);
            if ($result ['client_id']) {
                return $result;
            }
            return false;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Implements OAuth2::setAccessToken().
     */
    protected function setAccessToken($oauth_token, $client_id, $expires, $scope = NULL) {
        $info = $this->getAuthCode($_REQUEST['code']);
        try {
            $param = array(
                'user_id' => $this->checkParam($info['user_id']),
                'access_token' => $this->checkParam($oauth_token),
                'client_id' => $this->checkParam($client_id),
                'expires' => $this->checkParam($expires),
                'scope' => $this->checkParam($scope)
            );
            $result = $GLOBALS['db']->autoExecute('oauth_token', $param);
            return $result;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    protected function setAccessTokenForOpen($token, $client_id, $user_id, $scope = NULL) {
        try {
            $param = array(
                'user_id' => $this->checkParam($user_id),
                'access_token' => $this->checkParam($token['access_token']),
                'refresh_token' => $this->checkParam($token['refresh_token']),
                'expires' => $this->checkParam($token['expires_in']),
                'expires_refresh' => $this->checkParam($token['expires_refresh']),
                'client_id' => $this->checkParam($client_id),
                'scope' => $this->checkParam($scope)
            );
            $result = $GLOBALS['db']->autoExecute('oauth_token', $param);
            return $result;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Implements OAuth2::setAccessToken().
     */
    protected function setRefreshToken($refresh_token, $client_id, $expires, $scope = NULL) {
        $code = $_REQUEST['code'];
        $info = $this->getAuthCode($code);
        extract($info);
        try {
            $param = array(
                'refresh_token' => $this->checkParam($refresh_token),
                'expires_refresh' => $this->checkParam($expires),
            );
            $condition = "user_id = '{$user_id}' and client_id = '{$client_id}'";
            $result = $GLOBALS['db']->autoExecute('oauth_token', $param, 'update', $condition);
            return $result;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Overrides OAuth2::getSupportedGrantTypes().
     */
    protected function getSupportedGrantTypes() {
        //return array(OAUTH2_GRANT_TYPE_AUTH_CODE);
        return array(OAUTH2_GRANT_TYPE_AUTH_CODE, OAUTH2_GRANT_TYPE_REFRESH_TOKEN);
    }

    /**
     * Overrides OAuth2::getAuthCode().
     */
    protected function getAuthCode($code) {
        try {
            $sql = sprintf("SELECT user_id, code, client_id, redirect_uri, expires, scope FROM oauth_code WHERE code ='%s'", $this->checkParam($code));
            $result = $GLOBALS['db']->getRow($sql);
            return $result !== false ? $result : null;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Overrides OAuth2::setAuthCode().
     */
    protected function setAuthCode($code, $client_id, $redirect_uri, $expires, $scope = NULL) {
        try {
            $param = array(
                'user_id' => intval($this->user_id),
                'code' => $this->checkParam($code),
                'client_id' => $this->checkParam($client_id),
                'redirect_uri' => $this->checkParam($redirect_uri),
                'expires' => $this->checkParam($expires),
                'scope' => $this->checkParam($scope)
            );
            $result = $GLOBALS['db']->autoExecute('oauth_code', $param);
            return $result;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    private function checkParam($param) {
        return addslashes($param);
    }

    private function generateKey($unique = false) {
        $key = md5(base64_encode(uniqid(rand(), true)));
        if ($unique) {
            list($usec, $sec) = explode(' ', microtime());
            $key .= dechex($usec) . dechex($sec);
            $key = substr($key, 3, 24);
        }
        return $key;
    }

    public function grantAccessTokenForOpen() {
        $ret = array('errorCode' => '', 'errorMsg' => '', 'data' => '');

        $filters = array(
            "grant_type" => array("filter" => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => OAUTH2_GRANT_TYPE_REGEXP), "flags" => FILTER_REQUIRE_SCALAR),
            "scope" => array("flags" => FILTER_REQUIRE_SCALAR),
            "code" => array("flags" => FILTER_REQUIRE_SCALAR),
            "redirect_uri" => array("filter" => FILTER_SANITIZE_URL),
            "username" => array("flags" => FILTER_REQUIRE_SCALAR),
            "password" => array("flags" => FILTER_REQUIRE_SCALAR),
            "assertion_type" => array("flags" => FILTER_REQUIRE_SCALAR),
            "assertion" => array("flags" => FILTER_REQUIRE_SCALAR),
            "refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR),
        );

        $inputGet = array();
        $inputPost = array();
        $inputGet = is_array(filter_input_array(INPUT_GET, $filters)) ? filter_input_array(INPUT_GET, $filters) : array();
        $inputPost = is_array(filter_input_array(INPUT_POST, $filters)) ? filter_input_array(INPUT_POST, $filters) : array();
        $input = array_merge($inputGet, $inputPost);
        // Grant Type must be specified.
        if (!$input["grant_type"]) {
            $ret['errorCode'] = '400';
            $ret['errorMsg'] = 'Invalid grant_type parameter or parameter missing';
            return $ret;
            //$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');
        }
        // Make sure we've implemented the requested grant type
        if (!in_array($input["grant_type"], $this->getSupportedGrantTypes())) {
            $ret['errorCode'] = '400';
            $ret['errorMsg'] = OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE;
            return $ret;
            //$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE);
        }
        /*
          // Authorize the client
          $client = $this->getClientCredentials();
          if ($this->checkClientCredentials($client[0], $client[1]) === FALSE) {
          $ret['errorCode'] = '400';
          $ret['errorMsg'] = OAUTH2_ERROR_INVALID_CLIENT;
          return $ret;
          //$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_CLIENT);
          }

          if (!$this->checkRestrictedGrantType($client[0], $input["grant_type"])) {
          $ret['errorCode'] = '400';
          $ret['errorMsg'] = OAUTH2_ERROR_UNAUTHORIZED_CLIENT;
          return $ret;
          //$this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNAUTHORIZED_CLIENT);
          }
         * */
        // Do the granting
        $client[0] = $_REQUEST['client_id'];
        switch ($input["grant_type"]) {
            case OAUTH2_GRANT_TYPE_AUTH_CODE:
                if (!$input["code"] || !$input["redirect_uri"]) {
                    $ret['errorCode'] = '400';
                    $ret['errorMsg'] = OAUTH2_ERROR_INVALID_REQUEST;
                    return $ret;
                }

                $stored = $this->getAuthCode($input["code"]);
                // Ensure that the input uri starts with the stored uri
                if ($stored === NULL || (strcasecmp(substr($input["redirect_uri"], 0, strlen($stored["redirect_uri"])), $stored["redirect_uri"]) !== 0) || $client[0] != $stored["client_id"]) {
                    $ret['errorCode'] = '400';
                    $ret['errorMsg'] = OAUTH2_ERROR_INVALID_GRANT;
                    return $ret;
                }

                if ($stored["expires"] < time()) {
                    $ret['errorCode'] = '400';
                    $ret['errorMsg'] = OAUTH2_ERROR_EXPIRED_TOKEN;
                    return $ret;
                }

                break;
            case OAUTH2_GRANT_TYPE_USER_CREDENTIALS:
                if (!$input["username"] || !$input["password"]) {
                    $ret['errorCode'] = '400';
                    $ret['errorMsg'] = 'Missing parameters. "username" and "password" required';
                    return $ret;
                }
                $stored = $this->checkUserCredentials($client[0], $input["username"], $input["password"]);

                if ($stored === FALSE) {
                    $ret['errorCode'] = '400';
                    $ret['errorMsg'] = OAUTH2_ERROR_INVALID_GRANT;
                    return $ret;
                }

                break;
            case OAUTH2_GRANT_TYPE_ASSERTION:
                if (!$input["assertion_type"] || !$input["assertion"]) {
                    $ret['errorCode'] = '400';
                    $ret['errorMsg'] = OAUTH2_ERROR_INVALID_REQUEST;
                    return $ret;
                }

                $stored = $this->checkAssertion($client[0], $input["assertion_type"], $input["assertion"]);

                if ($stored === FALSE) {
                    $ret['errorCode'] = '400';
                    $ret['errorMsg'] = OAUTH2_ERROR_INVALID_GRANT;
                    return $ret;
                }

                break;
            case OAUTH2_GRANT_TYPE_REFRESH_TOKEN:
                if (!$input["refresh_token"]) {
                    $ret['errorCode'] = '400';
                    $ret['errorMsg'] = 'No "refresh_token" parameter found';
                    return $ret;
                }
                $stored = $this->getRefreshToken($input["refresh_token"]);
                if ($stored === NULL || $client[0] != $stored["client_id"]) {
                    $ret['errorCode'] = '401';
                    $ret['errorMsg'] = OAUTH2_ERROR_INVALID_GRANT;
                    return $ret;
                }

                if ($stored["expires"] < time()) {
                    $ret['errorCode'] = '402';
                    $ret['errorMsg'] = OAUTH2_ERROR_EXPIRED_TOKEN;
                    return $ret;
                }

                // store the refresh token locally so we can delete it when a new refresh token is generated
                $this->setVariable('_old_refresh_token', $stored["refresh_token"]);
                break;
            case OAUTH2_GRANT_TYPE_NONE:
                $stored = $this->checkNoneAccess($client[0]);
                if ($stored === FALSE) {
                    $ret['errorCode'] = '400';
                    $ret['errorMsg'] = OAUTH2_ERROR_INVALID_REQUEST;
                    return $ret;
                }
        }

        // Check scope, if provided
        if ($input["scope"] && (!is_array($stored) || !isset($stored["scope"]) || !$this->checkScope($input["scope"], $stored["scope"]))) {
            $ret['errorCode'] = '405';
            $ret['errorMsg'] = OAUTH2_ERROR_INVALID_SCOPE;
            return $ret;
        }

        if (!$input["scope"]) {
            $input["scope"] = NULL;
        }
        $token = $this->createAccessTokenForOpen($client[0], $stored['user_id'], $input["scope"]);
        return $token;
    }

    protected function createAccessTokenForOpen($client_id, $user_id, $scope = NULL) {
        $token = array(
            "access_token" => $this->genAccessToken(),
            "expires_in" => time() + $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME),
            "scope" => $scope,
            "refresh_token" => $this->genAccessToken(),
            "expires_refresh" => time() + $this->getVariable('refresh_token_lifetime', OAUTH2_DEFAULT_REFRESH_TOKEN_LIFETIME),
        );
        $this->setAccessTokenForOpen($token, $client_id, $user_id, $scope);
        if ($this->getVariable('_old_refresh_token')) {
            $this->unsetRefreshToken($this->getVariable('_old_refresh_token'));
        }
        return $token;
    }

    public function grantAccessTokenForClient($clientId, $scope = null) {

        $token = array(
            "access_token" => $this->genAccessToken(),
            "expires_in" => $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME),
            "scope" => $scope
        );

        $this->setAccessToken($token["access_token"], $clientId, time() + $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME), $scope);
        return $token;
    }

    /**
     *
     * @param type $refresh_token
     * @return user_id
     */
    protected function getRefreshToken($refresh_token) {
        try {
            $sql = sprintf("SELECT user_id, client_id, access_token, refresh_token, expires_refresh as expires , scope FROM oauth_token WHERE refresh_token='%s'", $this->checkParam($refresh_token));
            $result = $GLOBALS['db']->getRow($sql);
            if ($result ['user_id']) {
                return $result;
            }
            return false;
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     *
     * @param type $refresh_token
     */
    protected function unsetRefreshToken($refresh_token) {
        try {
            $sql = sprintf("DELETE FROM oauth_token WHERE refresh_token='%s'", $this->checkParam($refresh_token));
            return $GLOBALS['db']->query($sql);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * openapi access_token验证
     * @param type $scope
     * @param type $exit_not_present
     * @param type $exit_invalid
     * @param type $exit_expired
     * @param type $exit_scope
     * @param type $realm
     * @return boolean
     */
    public function verifyOpenAccessToken($token_param, $scope = NULL, $exit_not_present = TRUE, $exit_invalid = TRUE, $exit_expired = TRUE, $exit_scope = TRUE, $realm = NULL) {
        $ret = array('errorCode' => '', 'errorMsg' => '');
        $token = $this->getInfoByAccessToken($token_param);
        if ($token === NULL) {
            $ret['errorCode'] = '401';
            $ret['errorMsg'] = 'The access token provided is invalid.';
            return $ret;
//            return $exit_invalid ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_INVALID_TOKEN, 'The access token provided is invalid.', NULL, $scope) : FALSE;
        }
        if (isset($token["expires"]) && time() > $token["expires"]) {
            $ret['errorCode'] = '402';
            $ret['errorMsg'] = 'The access token provided has expired.';
            return $ret;
//            return $exit_expired ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_EXPIRED_TOKEN, 'The access token provided has expired.', NULL, $scope) : FALSE;
        }
        if ($scope && (!isset($token["scope"]) || !$token["scope"] || !$this->checkScope($scope, $token["scope"]))) {
            $ret['errorCode'] = '403';
            $ret['errorMsg'] = 'The request requires higher privileges than provided by the access token.';
            return $ret;
//            return $exit_scope ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_FORBIDDEN, $realm, OAUTH2_ERROR_INSUFFICIENT_SCOPE, 'The request requires higher privileges than provided by the access token.', NULL, $scope) : FALSE;
        }
        return TRUE;
    }

}
