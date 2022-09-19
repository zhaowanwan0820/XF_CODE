<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBank extends ModelBaseNoTime
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
    public $name;


    /**
     *
     * @var integer
     */
    public $img;


    /**
     *
     * @var integer
     */
    public $is_rec;


    /**
     *
     * @var integer
     */
    public $day;


    /**
     *
     * @var integer
     */
    public $sort;


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


    /**
     *
     * @var integer
     */
    public $update_time;


    /**
     *
     * @var integer
     */
    public $admin_id;


    /**
     *
     * @var string
     */
    public $short_name;


    /**
     *
     * @var integer
     */
    public $deposit;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->img = '0';
        $this->status = '0';
        $this->updateTime = '0';
        $this->adminId = '0';
        $this->shortName = '';
        $this->deposit = '0';
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
            'name' => 'name',
            'img' => 'img',
            'is_rec' => 'isRec',
            'day' => 'day',
            'sort' => 'sort',
            'status' => 'status',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'admin_id' => 'adminId',
            'short_name' => 'shortName',
            'deposit' => 'deposit',
        );
    }

    public function getSource()
    {
        return "firstp2p_bank";
    }
}