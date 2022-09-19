<?php

namespace api\processor;

class DealReservecommit extends Processor {

    public function afterInvoke() {
        $result = $this->fetchResult;
        $url = parse_url($result['url']);
        $url['path'] = '/deal/reservesuccess';
        $result['url'] = $url['path'] .'?'. $url['query'];
        $this->setApiRespData($result);
    }

}
