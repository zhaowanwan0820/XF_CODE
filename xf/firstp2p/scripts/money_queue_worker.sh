#!/bin/sh
while [ 1 ]
do
    /apps/product/php/bin/php ./scripts/money_queue_worker.php $1
done
