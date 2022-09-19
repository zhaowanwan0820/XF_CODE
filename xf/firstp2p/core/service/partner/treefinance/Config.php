<?php
/**
 *@author longbo
 */
namespace core\service\partner\treefinance;

use core\service\partner\common\Container;
use core\service\partner\common\ConfigBase;

class Config extends ConfigBase
{
    public $hostList = [
        'online' => [
                'host' => 'https://www.91gfd.com.cn/loan-thirdparty',
                'client_secret' => '980df37b6111fdfebcbd1ae683ea8e77',
                ],

        'test'   => [
                'host' => 'http://clearing-tp41.test.91gfd.cn',
                'client_secret' => 'treefinance',
                ],
        ];

    public $apiList = [

        /*还款计划通知*/
        'repay.notify' => [
            'action' => 'wxlc/schedules',
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

