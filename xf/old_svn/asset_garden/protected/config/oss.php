<?php
/**
 * oss
 */
return [
    'class' => 'iOss',
    'accessKeyId' => ConfUtil::get('OSS-ccs-yj.accessKeyId'),
    'accessKeySecret' => ConfUtil::get('OSS-ccs-yj.accessKeySecret'),
    'endpoint' => ConfUtil::get('OSS-ccs-yj.endpoint'),
    'bucket' => ConfUtil::get('OSS-ccs-yj-dashboard.bucket'),
    'bucket_attachment' => 'youjieshangcheng-zhaizhuan',
    'bucket_attachment_domain' => 'youjieshangcheng-zhaizhuan.oss-cn-beijing.aliyuncs.com',
];