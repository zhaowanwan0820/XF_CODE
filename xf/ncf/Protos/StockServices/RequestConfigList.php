<?php
namespace NCFGroup\Protos\StockServices;

use NCFGroup\Common\Extensions\Base\Pageable;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 配置列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author shiyan
 */
class RequestConfigList extends AbstractRequestBase
{
    /**
     * 分页
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * 配置名称
     *
     * @var string
     * @optional
     */
    private $key = '';

    /**
     * 配置的值
     *
     * @var string
     * @optional
     */
    private $value = '';

    /**
     * 配置的描述
     *
     * @var string
     * @optional
     */
    private $message = '';

    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestConfigList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }
    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return RequestConfigList
     */
    public function setKey($key = '')
    {
        $this->key = $key;

        return $this;
    }
    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return RequestConfigList
     */
    public function setValue($value = '')
    {
        $this->value = $value;

        return $this;
    }
    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return RequestConfigList
     */
    public function setMessage($message = '')
    {
        $this->message = $message;

        return $this;
    }

}