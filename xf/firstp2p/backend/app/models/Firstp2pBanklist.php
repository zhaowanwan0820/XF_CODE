<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pBanklist extends ModelBaseNoTime
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
     * @var string
     */
    public $bank_id;


    /**
     *
     * @var string
     */
    public $branch;


    /**
     *
     * @var string
     */
    public $city;


    /**
     *
     * @var string
     */
    public $province;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->name = '';
        $this->bankId = '';
        $this->branch = '';
        $this->city = '';
        $this->province = '';
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
            'bank_id' => 'bankId',
            'branch' => 'branch',
            'city' => 'city',
            'province' => 'province',
        );
    }

    public function getSource()
    {
        return "firstp2p_banklist";
    }
}