<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealLoadAdviser extends ModelBaseNoTime
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
    public $deal_load_id;


    /**
     *
     * @var integer
     */
    public $deal_id;


    /**
     *
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var integer
     */
    public $recommend_id;


    /**
     *
     * @var integer
     */
    public $create_time;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->dealLoadId = '0';
        $this->dealId = '0';
        $this->userId = '0';
        $this->recommendId = '0';
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
            'deal_load_id' => 'dealLoadId',
            'deal_id' => 'dealId',
            'user_id' => 'userId',
            'recommend_id' => 'recommendId',
            'create_time' => 'createTime',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_load_adviser";
    }
}