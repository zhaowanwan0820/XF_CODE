<?php
/**
 * oss
 */
return [
    'class' => 'iOss',
    'accessKeyId' => ConfUtil::get('OSS-wj.accessKeyId'),
    'accessKeySecret' => ConfUtil::get('OSS-wj.accessKeySecret'),
    'endpoint' => ConfUtil::get('OSS-wj.endpoint'),
    'bucket' => ConfUtil::get('OSS-wj.bucket'),
    'bucket_attachment' => 'wj-data-contract',
    'bucket_attachment_domain' => 'wj-data-contract.oss-cn-beijing.aliyuncs.com',
];