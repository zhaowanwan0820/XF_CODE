<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 上传返回
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ResponseUploadIdCardImage extends ResponseBase
{
    /**
     * 身份证path
     *
     * @var string
     * @required
     */
    private $filePath;

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     * @return ResponseUploadIdCardImage
     */
    public function setFilePath($filePath)
    {
        \Assert\Assertion::string($filePath);

        $this->filePath = $filePath;

        return $this;
    }

}