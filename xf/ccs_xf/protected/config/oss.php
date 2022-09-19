<?php
/**
 * oss
 */
return [
    'class' => 'iOss',
    'accessKeyId' => ConfUtil::get('OSS-ccs-xf.accessKeyId'),
    'accessKeySecret' => ConfUtil::get('OSS-ccs-xf.accessKeySecret'),
    'endpoint' => ConfUtil::get('OSS-ccs-xf.endpoint'),
    'bucket' => ConfUtil::get('OSS-ccs-xf-dashboard.bucket'),
    'bucket_attachment' => 'xf-debt-contract',
    'bucket_attachment_domain' => 'xf-debt-contract.oss-cn-beijing.aliyuncs.com',
];