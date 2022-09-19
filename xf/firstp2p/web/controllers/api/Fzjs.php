<?php
/**
 * 拉取分站js接口
 * @author <daiyuxin@ucfgroup.com>
 **/

namespace web\controllers\api;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Logger;

class Fzjs extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            //'refresh'=>array("filter"=>'string'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $key = sprintf('Static_Js_Content_%s_%s', $_GET['m'], $_GET['t']);
        if (!empty($_GET['m']) && !empty($_GET['t'])) {
            $cacheData = $redis->get($key);
            if (!empty($cacheData)) {
                Logger::info('Key:'.$key.', Content:'.substr(base64_encode($cacheData),-100));
                echo $cacheData;
                return;
            }
        }

        $isContents = false;
        do {
            if(isset($_GET['m']) && $_GET['m'] == 'fangdai'){
                //m站特殊情况,解决https问题
                if($_GET['t']=='register'){
                    $fzjs = 'http://weixin.diyifangdai.com/Static/firstp2p/register.js';
                }else if($_GET['t']=='login'){
                    $fzjs = 'http://weixin.diyifangdai.com/Static/firstp2p/login.js';
                }
                $context = stream_context_create(
                    array(
                        'http' => array(
                            'timeout' => 3 //超时时间，单位为秒
                        ) 
                    ));         
                $contents = file_get_contents($fzjs, 0, $context);
                $isContents = true;
                break;
            }

            if(isset($_GET['m']) && $_GET['m'] == 'caiyitong'){

                //m站特殊情况,解决https问题
                if($_GET['t']=='register'){
                    $fzjs = 'http://static.kcdns.net/caiyitong.register.js';
                }else if($_GET['t']=='login'){
                    $fzjs = 'http://static.kcdns.net/caiyitong.login.js';
                }else if($_GET['t']=='combineRegist'){
                    $fzjs = 'http://static.kcdns.net/caiyitong.combineRegist.js';
                }else if($_GET['t']=='modifyBank'){
                    $fzjs = 'http://static.kcdns.net/caiyitong.modifyBank.js';
                }
                $context = stream_context_create(
                    array(
                        'http' => array(
                            'timeout' => 10, //超时时间，单位为秒
                            'user_agent' => 'WANGXIN HTTPS BRIGE',
                        )
                    ));
                $contents = file_get_contents($fzjs, 0, $context);
                $isContents = true;
                break;
            }

            if(isset($_GET['m']) && $_GET['m'] == 'rxh'){
                //m站特殊情况,解决https问题
                if($_GET['t']=='register'){
                    $fzjs = 'http://rongxh.diyifangdai.com/Static/firstp2p/register.js';
                }else if($_GET['t']=='login'){
                    $fzjs = 'http://rongxh.diyifangdai.com/Static/firstp2p/login.js';
                }
                $context = stream_context_create(
                    array(
                        'http' => array(
                            'timeout' => 3 //超时时间，单位为秒
                        )
                    ));
                $contents = file_get_contents($fzjs, 0, $context);
                $isContents = true;
                break;
            }

            if(isset($_GET['m']) && $_GET['m'] == 'yijinrong'){
                //m站特殊情况,解决https问题
                $fzjs = 'http://m.yijinrong.com/Public/wap_fenzhan/yijinrong/fenzhan.js';  
                $context = stream_context_create(
                    array(
                        'http' => array(
                            'timeout' => 3 //超时时间，单位为秒
                        )
                    ));
                $contents = file_get_contents($fzjs, 0, $context);
                $isContents = true;
                break;
            }
        } while (false);

        if (true === $isContents) {
            $redis->set($key, $contents, 'ex', 600);
            echo $contents;
            return;
        }

        $data = $this->form->data;
        $domain = $_SERVER['HTTP_HOST'];

        $context = stream_context_create($opts);
        $rootDomain = implode('.', array_slice((explode('.', get_host())), -3));
        $shortName = substr($rootDomain, 0, strpos($rootDomain, "."));
        if($shortName == 'fangdai'){
            $url = sprintf("http://www.diyifangdai.com/%s.js", $shortName);
        }else if($shortName == 'esp2p'){
            $url = sprintf("http://www.esp2p.com/js/%s.js", $shortName);
        }else{
            $url = sprintf("http://static.kcdns.net/%s.js", $shortName);
        }
        header(sprintf("Location:%s?%s", $url, date("mdHi")));
    }

}
