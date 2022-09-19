<?php

namespace api\processor;

class DealReservecontractlist extends Processor {

    public function afterInvoke() {
        $result = $this->fetchResult;
        $result['list'] = array_values($result['list']);
        $this->setApiRespData($result);
    }

}
