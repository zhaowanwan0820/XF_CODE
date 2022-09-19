<?php
/**
 *@author longbo
 */
namespace core\service\partner\xianghua;

use core\service\partner\common\Container;
use core\service\partner\common\ConfigBase;

class Config extends ConfigBase
{
    public $hostList = [
        'online' => [
                'host' => 'https://xhapp.wintruelife.com',
                'client_id' => 'ytxhncf',
                'client_secret' => '61b4c659d37684e53a122fa74e8b45c4',
                ],

        'test'   => [
                'host' => 'https://testxhapp.wintruelife.com',
                'client_id' => 'ncftest',
                'client_secret' => '61b4c659d37684e53a122fa74e8b45c4',
                ],
        ];

    public $apiList = [

        /*查询用户是否授权*/
        'user.auth' => [
            'action' => 'api/ncfOpen/userAuthQuery',
            'method' => 'post',
            'post'   => [
                    'open_id' => ['required' => true],
                    ],
        ],

        /*投资成功通知*/
        'invest.notify' => [
            'action' => 'api/ncfOpen/investSuccessBackNotify',
            'method' => 'post',
            'post'   => [
                    'open_id' => ['required' => true],
                    'deal_id' => ['required' => true],
                    'load_id' => ['required' => true],
                    'deal_name' => [],
                    'money' => [],
                    'income' => [],
                    'create_time' => [],
                    ],
        ],

        /*回款通知*/
        'refund.notify' => [
            'action' => 'api/ncfOpen/investExpire',
            'method' => 'post',
            'post'   => [
                    'open_id' => ['required' => true],
                    'deal_id' => ['required' => true],
                    'load_id' => ['required' => true],
                    'repay_id' => [],
                    'principal' => [],
                    'interest' => [],
                    'real_time' => [],
                    'is_last' => [],
                    'out_order_id' => [],
                    ],
        ],

        /*划扣通知*/
        'transfer.notify' => [
            'action' => 'api/ncfOpen/investExpirePaySuccessNotify',
            'method' => 'post',
            'post'   => [
                    'out_order_id' => ['required' => true],
                    'status' => ['required' => true],
                    ],
        ],

        /*四要素变动通知*/
        'fourEle.notify' => [
            'action' => 'api/ncfOpen/fourElementChangedNotify',
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

