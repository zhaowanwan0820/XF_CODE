<?php
namespace NCFGroup\Ptp\models;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class Firstp2pArticle extends ModelBaseNoTime
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
    public $title;


    /**
     *
     * @var string
     */
    public $content;


    /**
     *
     * @var integer
     */
    public $cate_id;


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
    public $add_admin_id;


    /**
     *
     * @var integer
     */
    public $is_effect;


    /**
     *
     * @var string
     */
    public $rel_url;


    /**
     *
     * @var integer
     */
    public $update_admin_id;


    /**
     *
     * @var integer
     */
    public $is_delete;


    /**
     *
     * @var integer
     */
    public $click_count;


    /**
     *
     * @var integer
     */
    public $sort;


    /**
     *
     * @var string
     */
    public $seo_title;


    /**
     *
     * @var string
     */
    public $seo_keyword;


    /**
     *
     * @var string
     */
    public $seo_description;


    /**
     *
     * @var string
     */
    public $uname;


    /**
     *
     * @var string
     */
    public $sub_title;


    /**
     *
     * @var string
     */
    public $brief;


    /**
     *
     * @var integer
     */
    public $site_id;

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE
        $this->siteId = '0';
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
            'title' => 'title',
            'content' => 'content',
            'cate_id' => 'cateId',
            'create_time' => 'createTime',
            'update_time' => 'updateTime',
            'add_admin_id' => 'addAdminId',
            'is_effect' => 'isEffect',
            'rel_url' => 'relUrl',
            'update_admin_id' => 'updateAdminId',
            'is_delete' => 'isDelete',
            'click_count' => 'clickCount',
            'sort' => 'sort',
            'seo_title' => 'seoTitle',
            'seo_keyword' => 'seoKeyword',
            'seo_description' => 'seoDescription',
            'uname' => 'uname',
            'sub_title' => 'subTitle',
            'brief' => 'brief',
            'site_id' => 'siteId',
        );
    }

    public function getSource()
    {
        return "firstp2p_article";
    }
}