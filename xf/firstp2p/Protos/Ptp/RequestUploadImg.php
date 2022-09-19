<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 二进制图片上传
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan
 */
class RequestUploadImg extends ProtoBufferBase
{
    /**
     * 图片数组
     *
     * @var array
     * @required
     */
    private $contents;

    /**
     * @return array
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param array $contents
     * @return RequestUploadImg
     */
    public function setContents(array $contents)
    {
        $this->contents = $contents;

        return $this;
    }

}