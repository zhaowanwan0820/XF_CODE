<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获取公募基金公告内容
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class ResponseGetAnnouncement extends ResponseBase
{
    /**
     * 公告信息
     *
     * @var array
     * @required
     */
    private $record;

    /**
     * @return array
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * @param array $record
     * @return ResponseGetAnnouncement
     */
    public function setRecord(array $record)
    {
        $this->record = $record;

        return $this;
    }

}