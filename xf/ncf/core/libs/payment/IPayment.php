<?php
namespace libs\payment;

interface IPayment
{
    public function request($apiName, $params);

    public function requestMobile($apiName, $params);

    public function getSignature($params);

    public function verifySignature($params, $signature);

    public function getConfig($key, $subKey);

    public function getParams($config, $params);

}
