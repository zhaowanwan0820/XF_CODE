<?php
/**
 * Created by PhpStorm.
 * User: wangjiantong
 * Date: 2019/4/29
 * Time: 4:39 PM
 */

return array(
    'IFA' => array(
        'AGENCY_NAME' => "北京东方联合投资管理有限公司",
        'UNIFORM_SOCIAL_CREDIT_CODE' => "91110105576865680R",
        'PUBLIC_KEY_X' => "dfc02454297822849f5a32e0db6c282cfea9168963560e2f4d0ff5f503ae462e",
        'PUBLIC_KEY_Y' => "684b7a0752198469faa795d61165f33f476bbec082c1bcdfb9dca3fb0108ad0d",
        'PRIVATE_KEY' => "509b38a01aafccaa650e597fdc73fffb8e8d0d2a0e3c4d3ee12e77d126ccd971",
        'REPORT_URL' => "http://61.181.59.72/NifaServer/context/ficsdata/json/loanlist",
    ),

    //信贷接口配置
    'RCMS' => array(
        //对公
        'BUSINESS_PROJECT' => array(
            'URL' => 'http://phcms.corp.ncfgroup.com/stableCredit/api/order/templateParamReport',
            'CLIENT_NUMBER' => 'JG015',
            'CLIENT_SECRET' => 'ba23f8554f358c7a33e6c0cfbaa19e13',
        ),

        //零售
        'RETAIL_PROJECT' => array(
            'URL' =>'https://api-rcms.wangxinlicai.com/api/order/templateParamReport',
            'CLIENT_NUMBER' => 'JG010',
            'CLIENT_SECRET' => 'eedb8291b63fdc8527d6650c93f05c5f',
        ),
    ),
    'BAIHANG' => array(
        'AGENCY_NAME' => "北京东方联合投资管理有限公司",
        'PUBLIC_KEY_FILE_PATH' =>ROOT_PATH.'core/service/report/config/rsa/baihang_rsa_public_key.pem',
        'ADMIN' => 'DFLH_A0001',
        'PASSWORD' => 'moglek2#2ot',
        'REPORT_URL' => array(
            'D2' => 'https://zxpt.baihangcredit.com:8443/api/v1/credit/loan/issue ',
            'D3' => 'https://zxpt.baihangcredit.com:8443/api/v1/credit/loan/track ',
        ),
        'AES_KEY' => '84J48VCFN4E9DNEM39',
        'FTP' => array(
            'host'=> 'ftp.baihangcredit.com',
            'port'=> '990',
            'username' => 'DFLH_A0001',
            'password' => 'moglek2#2ot',
            'uploaddir' => '/creditdatafile/data/history/',
            'downloaddir' => '/creditdatafile/log/file/',
        ),
    )
);