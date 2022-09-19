<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pPreset extends ModelBaseNoTime
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
    public $real_name;


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
     * @var float
     */
    public $money;


    /**
     *
     * @var string
     */
    public $user_name;


    /**
     *
     * @var string
     */
    public $user_area;


    /**
     *
     * @var integer
     */
    public $create_time;


    /**
     *
     * @var integer
     */
    public $is_staff;


    /**
     *
     * @var integer
     */
    public $program_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->realName = '';
        $this->mobile = '';
        $this->email = '';
        $this->money = '0';
        $this->userName = '';
        $this->userArea = '';
        $this->createTime = '0';
        $this->isStaff = '0';
        $this->programId = '1';
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
            'real_name' => 'realName',
            'mobile' => 'mobile',
            'email' => 'email',
            'money' => 'money',
            'user_name' => 'userName',
            'user_area' => 'userArea',
            'create_time' => 'createTime',
            'is_staff' => 'isStaff',
            'program_id' => 'programId',
        );
    }

    public function getSource()
    {
        return "firstp2p_preset";
    }
}