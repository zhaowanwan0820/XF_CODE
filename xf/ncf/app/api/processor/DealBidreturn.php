<?php

namespace api\processor;

class DealBidreturn extends Processor {

    public function afterInvoke() {
        $result = $this->_getPrizeUrl($this->fetchResult);
        $this->setApiRespData($result);
    }

    //获取领券的URL
    public function _getPrizeUrl($result) {
        $prizeType = $result['prize_type'];
        $prizeUrl = $result['prize_url'];
        if (strcmp($prizeType, 'h5') !== 0 ) {
            if(!empty($prizeUrl)) {
                $prizeUrl = parse_url($prizeUrl);
                parse_str($prizeUrl['query'], $urlArr);
                $url = parse_url(urldecode($urlArr['url']));
                if ($url['path'] == '/gift/pickList') {
                    $path = '/coupon/picklist';
                    $result['isOneCoupon'] = false;
                } else {
                    $path = '/coupon/acquire_detail';
                    $result['isOneCoupon'] = true;
                }
                $url = $path . '?' . $url['query'];
                $result['prize_url'] = $url;
            }
        }
        return $result;
    }
}
