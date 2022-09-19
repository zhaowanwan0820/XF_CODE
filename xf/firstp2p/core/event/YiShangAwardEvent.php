<?php
namespace core\event;

use core\event\BaseEvent;
use NCFGroup\Task\Events\AsyncEvent;
use core\dao\BdActivityOrderModel;
use libs\utils\Logger;

class YiShangAwardEvent extends BaseEvent
{
    private $_orderSn;
    private $_params;

    public function __construct(
        $orderSn,
        $params
    ) {
        $this->_orderSn = $orderSn;
        $this->_params = $params;
    }

    public function execute()
    {
        $config = $GLOBALS['sys_config']['bd_activity']['YiShang'];
        Logger::info('YiShangEventStart.');

        if($config){
            $url = $config['url_getAward'];
            $response = \libs\utils\Curl::post($url, $this->_params);
            Logger::info("YiShangRequest. url:{$url}, params:".json_encode($this->_params).", ret:{$response}");
            //$response = '{"customOrderCode":"85930274","code":"HF5XH5CABMQMT;HF5XH5CABMQMB","couponCode":"","result":"10000"}';
            if($response){
                $ret_ar = json_decode($response, true);
                if($ret_ar && is_array($ret_ar)){
                    $model = BdActivityOrderModel::instance();
                    if($ret_ar['result'] == '10000'){
                        $data = array('code'=>$ret_ar['code'], 'status'=>1, 'result'=>$response);
                        $ret = $model->updateOrder($this->_orderSn, $data);
                        if($ret){
                            $log = array('response'=>$response,'key'=>'YiShang_SUCCESS','params'=>$this->_params);
                            Logger::wlog(array('BD_ACT'=>$log));
                            return true;
                        }else{
                            $log = array('ret'=>$ret,'key'=>'YiShang_FAIL','params'=>$this->_params);
                            Logger::wlog(array('BD_ACT'=>$log));
                            throw new \Exception('YiShang Model Update Fail');
                        }
                    }else{
                        //格式错误，不再重复请求
                        $data = array('code'=>'', 'status'=>-1, 'result'=>$response);
                        $ret = $model->updateOrder($this->orderSn, $data);
                        $log = array('response'=>$response,'key'=>'YiShang_FAIL','params'=>$this->_params);
                        Logger::wlog(array('BD_ACT'=>$log));
                        return true;
                    }
                }else{
                    $log = array('response'=>$response,'key'=>'YiShang_FAIL','params'=>$this->_params);
                    Logger::wlog(array('BD_ACT'=>$log));
                    throw new \Exception('YiShang Api Formart Error');
                }
            }else{
                $log = array('ret'=>'null','key'=>'YiShang_FAIL','params'=>$this->_params);
                Logger::wlog(array('BD_ACT'=>$log));
                throw new \Exception('YiShang Api Not Content');
            }
        }else{
            throw new \Exception('YiShang Config Not Found');
        }
        return true;
    }

    public function alertMails()
    {
        return array('yangqing@ucfgroup.com', 'wangqunqiang@ucfgroup.com');
    }
}
