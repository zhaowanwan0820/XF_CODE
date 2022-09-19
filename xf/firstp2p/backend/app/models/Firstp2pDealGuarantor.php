<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealGuarantor extends ModelBaseNoTime
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
    public $deal_id;


    /**
     *
     * @var string
     */
    public $name;


    /**
     *
     * @var string
     */
    public $id_number;


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
    public $create_time;


    /**
     *
     * @var integer
     */
    public $status;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $active_time;


    /**
     *
     * @var integer
     */
    public $relationship;


    /**
     *
     * @var integer
     */
    public $to_user_id;

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
            'deal_id' => 'dealId',
            'name' => 'name',
            'id_number' => 'idNumber',
            'mobile' => 'mobile',
            'email' => 'email',
            'create_time' => 'createTime',
            'status' => 'status',
            'user_id' => 'userId',
            'active_time' => 'activeTime',
            'relationship' => 'relationship',
            'to_user_id' => 'toUserId',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_guarantor";
    }
}