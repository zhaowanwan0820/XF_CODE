<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pUserTag extends ModelBaseNoTime
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
    public $const_name;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var date
     */
    public $created_at;


    /**
     *
     * @var date
     */
    public $updated_at;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->constName = '';
        $this->name = '';
        $this->status = '0';
        $this->createdAt = XDateTime::now();
        $this->updatedAt = '0000-00-00 00:00:00';
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
            'const_name' => 'constName',
            'name' => 'name',
            'status' => 'status',
            'created_at' => 'createdAt',
            'updated_at' => 'updatedAt',
        );
    }

    public function getSource()
    {
        return "firstp2p_user_tag";
    }
}