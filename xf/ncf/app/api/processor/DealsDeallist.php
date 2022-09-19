<?php

namespace api\processor;

use libs\utils\Aes;

class DealsDeallist extends Processor {

    public function beforeInvoke() {
        if('zxp2p' != $this->params['dealListType']){
            $value = $this->setPage($this->params['page']);
            $this->params['offset'] = isset($value['pageNo']) ? $value['pageNo'] : 0;
            $this->params['count'] = isset($value['pageSize']) ? $value['pageSize'] : 10;
        }
    }

    public function afterInvoke() {
        $result = $this->fetchResult;

        $this->params['isp2pindex'] = isset($this->params['isp2pindex']) ? $this->params['isp2pindex'] : 0;

        if (isset($result['deal_type'])) {

            if(intval($this->params['isp2pindex']) == 1) {
                $result = $result['deal_list'];
                $dealList = isset($result['list']) ? $result['list'] : array();
                $result['list'] = $this->_formatDealList($dealList);
            }else {
                $dealList = isset($result['deal_list']) ? $result['deal_list'] : array();
                $result['deal_list'] = $this->_formatDealList($dealList);
            }

        } else {
            $data = array();
            foreach ($result as $dealType => $dealList){
                $data[$dealType] = array('deal_type' => $dealType, 'deal_list' => $this->_formatDealList($dealList));
            }
            $result = $data;
        }

        $this->setApiRespData($result);
    }

    private function _formatDealList($dealList) {
        if(empty($dealList)) {
            return array();
        }

        foreach ($dealList as $key => $val) {
            $dealList[$key]['timelimit'] = preg_replace('/\D+/', '', $val['timelimit']);
            $dealList[$key]['timeunit'] = preg_replace('/\d+/', '', $val['timelimit']);
            $dealList[$key]['productID'] = Aes::encryptForDeal($dealList[$key]['productID']);
        }
        return $dealList;
    }

}
