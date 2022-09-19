#!/bin/sh
while [ 1 ]
do
    /apps/product/php/bin/php ./scripts/paymentapi_worker.php
done
