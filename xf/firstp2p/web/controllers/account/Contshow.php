<?php
/**
 * 合同查看和下载
 * @author wenyanlei <wenyanlei@ucfgroup.com>
 **/
namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;

use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

class Contshow extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'tag' => array('filter' => 'string'),
            'ajax' => array('filter' => 'int'),
            'number' => array('filter' => 'string'),
            'dealId' => array('filter' => 'int'),
            'projectId' => array('filter' => 'int'),
            'id' => array('filter' => 'int'),
            'type' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $tag = $data ['tag'];
        $id = intval($data ['id']);
        $ajax = intval($data['ajax']);
        $number = $data['number'];
        $dealId = $data['dealId'];
        $projectId = $data['projectId'];
        $user_info = $GLOBALS['user_info'];
        $type = intval($data['type']);

        if($id <= 0 || !in_array($tag, array('show','download','download_tsa','download_new','shownew'))){
            return self::download_return($ajax);
        }

        if (1 == $type) { // 项目合同
            $service_id = $data['projectId'];
            $service_type = ContractServiceEnum::SERVICE_TYPE_PROJECT;
        } else { // 标的合同
            $service_id = $data['dealId'];
            $service_type = ContractServiceEnum::SERVICE_TYPE_DEAL;
        }

        // else 后 为历史逻辑
        if (!empty($service_id)) {
            if($tag == 'download_tsa'){
                $ret = $this->rpc->local('ContractInvokerService\downloadTsa',array('filer', $id, $service_id, $service_type, $GLOBALS['user_info']));
                if( $ret == false ){
                    //时间戳还没打出来。直接下载老合同,避免前台修改链接走错逻辑
                    $this->rpc->local('ContractInvokerService\download',array('filer', $id, $service_id, $service_type, $GLOBALS['user_info']));
                    return true;
                }
            }elseif($tag == 'download'){
                $this->rpc->local('ContractInvokerService\download',array('filer', $id, $service_id, $service_type, $GLOBALS['user_info']));
                return true;
            }
            $contract = $this->rpc->local('ContractInvokerService\getOneFetchedContract',array('viewer', $id, $service_id, $service_type, $GLOBALS['user_info']));

            echo hide_message($contract['content']);
            exit();
        } else {
            //获取合同信息 并验证合同权限
            $contract = $this->rpc->local('ContractService\showContract',array($id));
            $checkown = $this->checkOwn($contract,$user_info);
            if(empty($contract) || !$checkown){
                return self::download_return($ajax);
            }
        }

        switch ($tag){
            case "download":
            //--------------------临时摘掉时间戳展现--------------------
            /*
            case "download_tsa":
            case "download_new":
                // 当合同补发时触发的逻辑.
                $ret = $this->rpc->local('ContractService\contractDownloadRenew',array($id));
                if($ret == false){
                    //补发的合同还没有。默认下载原先的合同
                    $this->rpc->local('ContractService\contractDownload',array($id));
                }
            //--------------------临时摘掉时间戳展现--------------------
            */
                $this->rpc->local('ContractService\contractDownload',array($id));
                break;
            case "download_tsa":
                // 下载最老的tsa
                $ret = $this->rpc->local('ContractSignService\readSignedPdf',array($id));
                if( $ret == false ){
                    //时间戳还没打出来。直接下载老合同,避免前台修改链接走错逻辑
                    $this->rpc->local('ContractService\contractDownload',array($id));
                    return true;
                }
                break;
            case "download_new":
                // 当合同补发时触发的逻辑.
                $ret = $this->rpc->local('ContractService\contractDownloadRenew',array($id));
                if($ret == false){
                    //补发的合同还没有。默认下载原先的合同
                    $this->rpc->local('ContractService\contractDownload',array($id));
                }
                break;
            case "shownew":
                $ret = $this->rpc->local('ContractService\contractDownloadRenew',array($id,true));
                if(!empty($ret)){
                    echo hide_message($ret);
                }else{
                    echo hide_message($contract['content']);
                }
                break;
            default:
                //--------------------临时摘掉时间戳展现--------------------
                /*
                $ret = $this->rpc->local('ContractService\contractDownloadRenew',array($id,true));
                if(!empty($ret)){
                    echo hide_message($ret);
                }else{
                    echo hide_message($contract['content']);
                }
                */
                //--------------------临时摘掉时间戳展现--------------------
                $contract = $this->rpc->local('ContractService\showContract',array($id,true));
                echo hide_message($contract['content']);
        }
        return true;
    }

    public static function download_return($ajax = 0){
        if($ajax == 0){
            echo '<script>window.parent.location.href="/404.html"</script>';
        }else{
            return app_redirect ('/404.html');
        }
        return false;
    }

    private function checkOwn($contract,$user){
        $params = array($contract, array('id' => $user['id'], 'user_name' => $user['user_name']));
        $checkown = $this->rpc->local('ContractService\checkContractNew', $params);
        return $checkown;
    }
}

