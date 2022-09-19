<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * oauth认证
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class RequestOauth extends ProtoBufferBase
{
    /**
     * 第三方应用ID
     *
     * @var string
     * @required
     */
    private $clientId;

    /**
     * 返回类型
     *
     * @var string
     * @optional
     */
    private $responseType = '';

    /**
     * 回调URL
     *
     * @var string
     * @required
     */
    private $redirectUri;

    /**
     * state
     *
     * @var string
     * @optional
     */
    private $state = '';

    /**
     * 权限范围
     *
     * @var string
     * @optional
     */
    private $scope = '';

    /**
     * grant类型
     *
     * @var string
     * @optional
     */
    private $grantType = '';

    /**
     * code
     *
     * @var string
     * @optional
     */
    private $code = '';

    /**
     * 刷新码
     *
     * @var string
     * @optional
     */
    private $refreshToken = '';

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @return RequestOauth
     */
    public function setClientId($clientId)
    {
        \Assert\Assertion::string($clientId);

        $this->clientId = $clientId;

        return $this;
    }
    /**
     * @return string
     */
    public function getResponseType()
    {
        return $this->responseType;
    }

    /**
     * @param string $responseType
     * @return RequestOauth
     */
    public function setResponseType($responseType = '')
    {
        $this->responseType = $responseType;

        return $this;
    }
    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @param string $redirectUri
     * @return RequestOauth
     */
    public function setRedirectUri($redirectUri)
    {
        \Assert\Assertion::string($redirectUri);

        $this->redirectUri = $redirectUri;

        return $this;
    }
    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return RequestOauth
     */
    public function setState($state = '')
    {
        $this->state = $state;

        return $this;
    }
    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     * @return RequestOauth
     */
    public function setScope($scope = '')
    {
        $this->scope = $scope;

        return $this;
    }
    /**
     * @return string
     */
    public function getGrantType()
    {
        return $this->grantType;
    }

    /**
     * @param string $grantType
     * @return RequestOauth
     */
    public function setGrantType($grantType = '')
    {
        $this->grantType = $grantType;

        return $this;
    }
    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return RequestOauth
     */
    public function setCode($code = '')
    {
        $this->code = $code;

        return $this;
    }
    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     * @return RequestOauth
     */
    public function setRefreshToken($refreshToken = '')
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

}