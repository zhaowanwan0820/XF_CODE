#!/bin/sh
while [ 1 ]
do
    /apps/product/php/bin/php ./scripts/daemonworkers/withdraw_proxy_notify_worker.php
done
