<?php
/**
 * oss
 */
return [
    'class' => 'iOss',
    'accessKeyId' => ConfUtil::get('OSS-ccs-1.accessKeyId'),
    'accessKeySecret' => ConfUtil::get('OSS-ccs-1.accessKeySecret'),
    'endpoint' => ConfUtil::get('OSS-ccs-1.endpoint'),
    'bucket' => ConfUtil::get('OSS-ccs-1-dashboard.bucket'),
    'bucket_attachment' => 'itzattachment',
    'bucket_attachment_domain' => 'https://itzattachment.oss.aliyuncs.com',
];