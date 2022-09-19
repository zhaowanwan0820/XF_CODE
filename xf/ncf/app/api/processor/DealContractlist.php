<?php

namespace api\processor;

use libs\utils\Aes;

class DealContractlist extends Processor {

    public function beforeInvoke() {
        if (!is_numeric($this->params['id'])) {
            $this->params['id'] = Aes::decryptForDeal($this->params['id']);
        }
    }

    public function afterInvoke() {
        $result = $this->fetchResult;

        $contract = empty($result['deal']['contract']) ? [] : $result['deal']['contract'];
        $temp = array();
        foreach($contract as $item){
           $url = parse_url(urldecode($item['url']));
           $name = $item['nameSrc'];
           parse_str($url['query'], $query);
           $query['id'] = Aes::encryptForDeal($query['id']);
           $query['name'] = $name;
           unset($query['token']);
           $url['query'] = http_build_query($query);
           $url = $url['path'] . '?' . $url['query'];
           $item['url'] = $url;
           $temp[] =$item;
        }
        $result['deal']['contract'] = $temp;

        $this->setApiRespData($result);
    }

}
