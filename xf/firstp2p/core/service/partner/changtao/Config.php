<?php
/**
 *@author longbo
 */
namespace core\service\partner\changtao;

use core\service\partner\common\Container;
use core\service\partner\common\ConfigBase;

class Config extends ConfigBase
{
    public $hostList = [
        'online' => [
                'host' => 'https://www.changtaojinrong.com/qydwx',
                'name' => '长涛金融',
                'client_secret' => '9317f50526830b0ea0e5d48f3cfc170d',
                ],

        'test'   => [
                'host' => 'https://zsc.qydgroup.com/qydwx',
                'name' => '长涛金融',
                'client_secret' => '8ec470599c1112d5a5a116fda7f25736',
                ],
        ];

    public $apiList = [

        /*获取标的列表*/
        'deals.list' => [
            'action' => 'wangxin/wxProjectList',
            'method' => 'post',
            'post'   => ['count' => ''],
        ],

        /*获取用户资产*/
        'user.asset' => [
            'action' => 'wangxin/wxUserAccount',
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

