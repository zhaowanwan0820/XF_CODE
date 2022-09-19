<?php
/**
 *@author longbo
 */
namespace core\service\partner\urealsoon;

use core\service\partner\common\Container;
use core\service\partner\common\ConfigBase;

class Config extends ConfigBase
{
    public $hostList = [
        'online' => [
                'host' => 'http://bridge.trustrock.com.cn',
                'client_secret' => '42d8ffebb3962c4394abc45a28a71e1c',
                ],

        'test'   => [
                'host' => 'http://bridgetest.trustrock.com.cn',
                'client_secret' => 'angliadddealtest',
                ],
        ];

    public $apiList = [

        /*还款计划通知*/
        'repay.notify' => [
            'action' => 'ncfonline/repayPlan',
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

