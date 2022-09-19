<?php

namespace core\service\partner\gongdan;

use core\service\partner\common\Container;
use core\service\partner\common\ConfigBase;

class Config extends ConfigBase
{
    public $hostList = [
        'online' => [
                'host' => 'http://gongdan.corp.ncfgroup.com',
                'client_id' => 'gongdan',
                'client_secret' => '32ff94f0d9fb9a30fa7baf252586be53',
                ],

        'test'   => [
                'host' => 'http://schedule.org.kcdns.net',
                'client_id' => 'gongdan',
                'client_secret' => '101532d668a3c90633dfea684a813444',
                ],
        ];

    public $apiList = [

        /*项目状态变更通知*/
        'status.notify' => [
            'action' => 'sysworkflow/zh/neoclassic/ncfpms_open/scheduleProject',
            'method' => 'post',
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

