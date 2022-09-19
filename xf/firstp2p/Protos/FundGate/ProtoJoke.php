<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

class ProtoJoke extends ProtoBufferBase {

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var integer
     */
    public $author_id;

    /**
     *
     * @var string
     */
    public $author_name;

    /**
     *
     * @var string
     */
    public $author_pic_url;

    /**
     *
     * @var string
     */
    public $content;

    /**
     *
     * @var string
     */
    public $image_url;

    /**
     *
     * @var string
     */
    public $media_url;

    /**
     *
     * @var string
     */
    public $pub_time;

    /**
     *
     * @var integer
     */
    public $support_count;

    /**
     *
     * @var integer
     */
    public $deny_count;

    /**
     *
     * @var integer
     */
    public $feedback_count;

    /**
     *
     * @var integer
     */
    public $category;

    /**
     *
     * @var integer
     */
    public $source_id;
}
