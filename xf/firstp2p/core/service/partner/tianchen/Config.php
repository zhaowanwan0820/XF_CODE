<?php
/**
 *@author longbo
 */
namespace core\service\partner\tianchen;

use core\service\partner\common\Container;
use core\service\partner\common\ConfigBase;

class Config extends ConfigBase
{
    public $hostList = [
        'online' => [
                'host' => 'https://www.baofengwd.com',
                'name' => '天辰智投',
                'client_secret' => '35c6b8dadsf3251b52136acd9665841c',
                ],

        'test'   => [
                'host' => 'http://47.94.115.242',
                'name' => '天辰智投',
                'client_secret' => '10d5a6dfasb2154a56695bca6995891a',
                ],
        ];

    public $apiList = [

        /*获取标的列表*/
        'deals.list' => [
            'action' => 'open/api/v1/wangxin/targetInfoList',
            'method' => 'post',
            'post'   => ['count' => ''],
        ],

        /*获取用户资产*/
        'user.asset' => [
            'action' => 'open/api/v1/wangxin/getAccountInfo',
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

