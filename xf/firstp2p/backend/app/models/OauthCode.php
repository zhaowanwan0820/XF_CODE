<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class OauthCode extends ModelBaseNoTime
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
    public $code;


    /**
     *
     * @var string
     */
    public $redirect_uri;


    /**
     *
     * @var integer
     */
    public $expires;


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
        $this->code = '';
        $this->redirectUri = '';
        $this->expires = '0';
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
            'code' => 'code',
            'redirect_uri' => 'redirectUri',
            'expires' => 'expires',
            'scope' => 'scope',
        );
    }

    public function getSource()
    {
        return "oauth_code";
    }
}