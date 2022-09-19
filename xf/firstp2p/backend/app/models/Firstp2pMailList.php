<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pMailList extends ModelBaseNoTime
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
    public $mail_address;


    /**
     *
     * @var integer
     */
    public $city_id;


    /**
     *
     * @var string
     */
    public $code;


    /**
     *
     * @var integer
     */
    public $is_effect;

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
            'mail_address' => 'mailAddress',
            'city_id' => 'cityId',
            'code' => 'code',
            'is_effect' => 'isEffect',
        );
    }

    public function getSource()
    {
        return "firstp2p_mail_list";
    }
}