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
        'PUBLIC_KEY_X' => "10679efe5e87e1d4dd3b1f34ec60160f3ec2a9072751b35e12a4a631be723c60",
        'PUBLIC_KEY_Y' => "4566b05546261ca86a15b0da23678a9efa34abb148216a6f81f3d508febd15e2",
        'PRIVATE_KEY' => "bfcb29f15b990acc1fd7a7342caadfcff099913781678e7d9f6722d4753add05",
        'REPORT_URL' => "http://61.181.59.73:10081/NifaServer/context/ficsdata/json/loanlist",
    ),

    //信贷接口配置
    'RCMS' => array(
        //对公
        'BUSINESS_PROJECT' => array(
            'URL' => 'http://10.20.69.167:8080/stableCredit/api/order/templateParamReport',
            'CLIENT_NUMBER' => 'JG015',
            'CLIENT_SECRET' => '85eb7e4524eb5bc8b86aaa8708b50d9a',
        ),

        //零售
        'RETAIL_PROJECT' => array(
            'URL' =>'http://10.20.69.167:8083/api/order/templateParamReport',
            'CLIENT_NUMBER' => 'JG015',
            'CLIENT_SECRET' => '9bf30e8e1a2a99092c264b12611ebdfa',
        ),
    ),
    'BAIHANG' => array(
        'AGENCY_NAME' => "北京东方联合投资管理有限公司",
        'PUBLIC_KEY_FILE_PATH' =>ROOT_PATH.'core/service/report/config/rsa/baihang_rsa_public_key.pem',
        'PRIVATE_KEY_FILE_PATH' =>ROOT_PATH.'core/service/report/config/rsa/baihang_rsa_private_key.pem',
        'ADMIN' => 'DFLH_A0001',
        'PASSWORD' => 'moglek2#2ot',
        'REPORT_URL' => array(
            'D2' => 'https://test-zxpt.baihangcredit.com:8443/api/v1/credit/loan/issue ',
            'D3' => 'https://test-zxpt.baihangcredit.com:8443/api/v1/credit/loan/track ',
        ),
        'AES_KEY' => '84J48VCFN4E9DNEM39',
        'FTP' => array(
            'host'=> 'test-ftp.baihangcredit.com',
            'port'=> '990',
            'username' => 'DFLH_A0001',
            'password' => 'moglek2#2ot',
            'uploaddir' => '/creditdatafile/data/ftp/',
            'downloaddir' => '/creditdatafile/log/file/',
        ),

    )
);