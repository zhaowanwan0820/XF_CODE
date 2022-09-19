<?php

namespace api\processor;

class CommonAdv extends Processor {

    public function afterInvoke() {
        $result = $this->fetchResult;
        foreach ($result as $index => $item) {
            if (isset($item['imageUrl'])) {
                $item['imageUrl'] = preg_replace('/^http(s)?:/i', '', $item['imageUrl']);
            }
            $result[$index] = $item;
        }
        $this->setApiRespData($result);
    }

}
