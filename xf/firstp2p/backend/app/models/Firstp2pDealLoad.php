<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pDealLoad extends ModelBaseNoTime
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
     * @var integer
     */
    public $user_id;


    /**
     *
     * @var string
     */
    public $user_name;


    /**
     *
     * @var string
     */
    public $user_deal_name;


    /**
     *
     * @var float
     */
    public $money;


    /**
     *
     * @var string
     */
    public $short_alias;


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
    public $is_repay;


    /**
     *
     * @var integer
     */
    public $from_deal_id;


    /**
     *
     * @var integer
     */
    public $deal_parent_id;


    /**
     *
     * @var integer
     */
    public $site_id;


    /**
     *
     * @var integer
     */
    public $source_type;


    /**
     *
     * @var integer
     */
    public $is_admin;


    /**
     *
     * @var string
     */
    public $ip;


    /**
     *
     * @var integer
     */
    public $type;


    /**
     *
     * @var integer
     */
    public $assignment_id;


    /**
     *
     * @var integer
     */
    public $assignment_status;


    /**
     *
     * @var integer
     */
    public $deal_type;


    /**
     *
     * @var string
     */
    public $bonus_mobile;


    /**
     *
     * @var integer
     */
    public $order_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->shortAlias = '';
        $this->updateTime = '0';
        $this->fromDealId = '0';
        $this->dealParentId = '-1';
        $this->siteId = '1';
        $this->isAdmin = '0';
        $this->dealType = '0';
        $this->bonusMobile = '';
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
            'user_id' => 'userId',
            'user_name' => 'userName',
            'user_deal_name' => 'userDealName',
            'money' => 'money',
            'short_alias' => 'shortAlias',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'is_repay' => 'isRepay',
            'from_deal_id' => 'fromDealId',
            'deal_parent_id' => 'dealParentId',
            'site_id' => 'siteId',
            'source_type' => 'sourceType',
            'is_admin' => 'isAdmin',
            'ip' => 'ip',
            'type' => 'type',
            'assignment_id' => 'assignmentId',
            'assignment_status' => 'assignmentStatus',
            'deal_type' => 'dealType',
            'bonus_mobile' => 'bonusMobile',
            'order_id' => 'orderId',
        );
    }

    public function getSource()
    {
        return "firstp2p_deal_load";
    }
}