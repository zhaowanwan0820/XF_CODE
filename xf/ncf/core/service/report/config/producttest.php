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
            'URL' => 'http://phcmstest.corp.ncfgroup.com/stableCredit/api/order/templateParamReport',
            'CLIENT_NUMBER' => 'JG015',
            'CLIENT_SECRET' => 'be06a3e0d613b65716aa4b1e46e3aa42',
        ),

        //零售
        'RETAIL_PROJECT' => array(
            'URL' =>'http://producttest-api.rcms.corp.ncfgroup.com/api/order/templateParamReport',
            'CLIENT_NUMBER' => 'JG009',
            'CLIENT_SECRET' => '9bf30e8e1a2a99092c264b12611ebdfa',
        ),
    ),
);