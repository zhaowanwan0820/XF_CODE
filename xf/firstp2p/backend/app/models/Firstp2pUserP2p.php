<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserP2p extends ModelBaseNoTime
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
    public $idno;


    /**
     *
     * @var string
     */
    public $real_name;


    /**
     *
     * @var string
     */
    public $group_name;


    /**
     *
     * @var string
     */
    public $group_agency;


    /**
     *
     * @var string
     */
    public $group_master;

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
            'id' => 'id',
            'idno' => 'idno',
            'real_name' => 'realName',
            'group_name' => 'groupName',
            'group_agency' => 'groupAgency',
            'group_master' => 'groupMaster',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_p2p";
    }
}