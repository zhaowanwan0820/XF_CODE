<?php

namespace api\processor;

class DealReservedetail extends Processor {

    public function afterInvoke() {
        $result = $this->fetchResult;
        $url = parse_url($result['reserve_list_button']);
        $url['path'] = '/deal/reservemy';
        $result['reserve_list_button'] = $url['path'] .'?'. $url['query'];
        $this->setApiRespData($result);
    }

}
