<?php

namespace api\processor;

class DuotouDuotoubid extends Processor {

    public function afterInvoke() {
        $result = $this->fetchResult;

        //领券的url
        $prizeUrl = $result['prize_url'];
        if(!empty($prizeUrl)) {
            $prizeUrl = parse_url($prizeUrl);
            parse_str($prizeUrl['query'], $urlArr);
            $url = parse_url(urldecode($urlArr['url']));
            if($url['path'] == '/gift/pickList') {
                $path = '/coupon/picklist';
                $result['isOneCoupon'] = false;
            }else {
                $path = '/coupon/acquire_detail';
                $result['isOneCoupon'] = true;
            }
            $url = $path . '?' . $url['query'];
            $result['prize_url'] = $url;
        }

        $this->setApiRespData($result);
    }

}
