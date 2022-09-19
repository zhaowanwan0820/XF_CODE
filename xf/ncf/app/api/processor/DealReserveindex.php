<?php

namespace api\processor;

class DealReserveindex extends Processor {

    public function afterInvoke() {
        $result = $this->fetchResult;
        if(isset($result['description'])) {
             $result['description'] = str_replace('./deal/ReserveDetail', '/deal/reservedetail', $result['description']);
        }
        $url = parse_url($result['reserve_list_button']);
        $url['path'] = '/deal/reservemy';
        $result['reserve_list_button'] = $url['path'] .'?'. $url['query'];
        $this->setApiRespData($result);
    }

}
