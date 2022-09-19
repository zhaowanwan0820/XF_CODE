<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserCreditFile extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var string
     */
    public $type;


    /**
     *
     * @var string
     */
    public $file;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE

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
            'user_id' => 'userId',
            'type' => 'type',
            'file' => 'file',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_credit_file";
    }
}