<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pAdviser extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    /**
     *
     * @var integer
     */
    public $id;


    /**
     *
     * @var integer
     */
    public $adviser_id;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var string
     */
    public $user_name;


    /**
     *
     * @var string
     */
    public $mobile;


    /**
     *
     * @var string
     */
    public $email;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->adviserId = '0';
        $this->name = '0';
        $this->userName = '0';
        $this->mobile = '';
        $this->email = '';
        $this->status = '0';
        $this->createTime = '0';
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
            'adviser_id' => 'adviserId',
            'name' => 'name',
            'user_name' => 'userName',
            'mobile' => 'mobile',
            'email' => 'email',
            'status' => 'status',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_adviser";
    }
}