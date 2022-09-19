<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class OauthToken extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var string
     */
    public $client_id;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var string
     */
    public $access_token;


    /**
     *
     * @var string
     */
    public $refresh_token;


    /**
     *
     * @var integer
     */
    public $expires;


    /**
     *
     * @var integer
     */
    public $expires_refresh;


    /**
     *
     * @var string
     */
    public $scope;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->clientId = '';
        $this->userId = '0';
        $this->accessToken = '';
        $this->refreshToken = '';
        $this->expires = '0';
        $this->expiresRefresh = '0';
        //END DEFAULT_VALUE
    }

    public function initialize()
    {
        parent::initialize();
        $this->setReadConnectionService('firstp2p_r');
        $this->setWriteConnectionService('firstp2p');
    }

    public function columnMap()
    {
        return array(
            'id' => 'id',
            'client_id' => 'clientId',
            'user_id' => 'userId',
            'access_token' => 'accessToken',
            'refresh_token' => 'refreshToken',
            'expires' => 'expires',
            'expires_refresh' => 'expiresRefresh',
            'scope' => 'scope',
        );
    }

    public function getSource()
    {
        return "oauth_token";
    }
}