<?php
/**
 *@author longbo
 */
namespace core\service\partner\qianduola;

use core\service\partner\common\Container;
use core\service\partner\common\ConfigBase;

class Config extends ConfigBase
{
    public $hostList = [
        'online' => [
                'host' => 'https://mapi.socian.com.cn',
                'name' => '钱哆拉',
                'client_secret' => 'c58d10303aa8e2ad364bc1b2bb607d3a',
                'wx_client_id' => 'c06edcc954cb8f0a0adbdd0a',
                ],

        'test'   => [
                'host' => 'http://test3.h5api.mantoulicai.com',
                'name' => '钱哆拉',
                'client_secret' => 'af7cfb7c934f2ed355cbb340655935cb',
                'wx_client_id' => 'ba04ad9a6da6894588fe55fb',
                ],
        ];

    public $apiList = [

        /*获取标的列表*/
        'deals.list' => [
            'action' => 'wangxin/borrowList',
            'method' => 'post',
            'post'   => ['count' => ''],
        ],

        /*获取用户资产*/
        'user.asset' => [
            'action' => 'wangxin/userAccount',
            'method' => 'post',
            'post'   => [
                    'open_id' => ['required' => true],
                    ],
        ],
    ];

    protected function setRequestService()
    {
        Container::register('requestService', function(){
                return new Request();
            }
        );
    }

}

