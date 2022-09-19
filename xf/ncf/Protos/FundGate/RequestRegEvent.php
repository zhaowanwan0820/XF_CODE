<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Fund\Backend\Instrument\Event\AsyncEvent;

/**
 * 注册事件
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestRegEvent extends AbstractRequestBase
{
    /**
     * 事件内容
     *
     * @var AsyncEvent
     * @required
     */
    private $event;

    /**
     * 最大尝试次数
     *
     * @var int
     * @optional
     */
    private $maxTry = 1;

    /**
     * 优先级
     *
     * @var string
     * @optional
     */
    private $priority = 'low';

    /**
     * 执行时间
     *
     * @var XDateTime
     * @optional
     */
    private $executeTime = NULL;

    /**
     * @return AsyncEvent
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param AsyncEvent $event
     * @return RequestRegEvent
     */
    public function setEvent(AsyncEvent $event)
    {
        $this->event = $event;

        return $this;
    }
    /**
     * @return int
     */
    public function getMaxTry()
    {
        return $this->maxTry;
    }

    /**
     * @param int $maxTry
     * @return RequestRegEvent
     */
    public function setMaxTry($maxTry = 1)
    {
        $this->maxTry = $maxTry;

        return $this;
    }
    /**
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $priority
     * @return RequestRegEvent
     */
    public function setPriority($priority = 'low')
    {
        $this->priority = $priority;

        return $this;
    }
    /**
     * @return XDateTime
     */
    public function getExecuteTime()
    {
        return $this->executeTime;
    }

    /**
     * @param XDateTime $executeTime
     * @return RequestRegEvent
     */
    public function setExecuteTime(XDateTime $executeTime = NULL)
    {
        $this->executeTime = $executeTime;

        return $this;
    }

}