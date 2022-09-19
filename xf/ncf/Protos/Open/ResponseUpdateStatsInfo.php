<?php
namespace NCFGroup\Protos\Open;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * open:更新开放平台的统计信息 的返回结果Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author YanJun
 */
class ResponseUpdateStatsInfo extends ProtoBufferBase
{
    /**
     * 分站ID
     *
     * @var string
     * @optional
     */
    private $resMsg = '';

    /**
     * 更新统计信息时间
     *
     * @var int
     * @optional
     */
    private $resCode = 0;

    /**
     * @return string
     */
    public function getResMsg()
    {
        return $this->resMsg;
    }

    /**
     * @param string $resMsg
     * @return ResponseUpdateStatsInfo
     */
    public function setResMsg($resMsg = '')
    {
        $this->resMsg = $resMsg;

        return $this;
    }
    /**
     * @return int
     */
    public function getResCode()
    {
        return $this->resCode;
    }

    /**
     * @param int $resCode
     * @return ResponseUpdateStatsInfo
     */
    public function setResCode($resCode = 0)
    {
        $this->resCode = $resCode;

        return $this;
    }

}