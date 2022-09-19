#!/bin/sh
while [ 1 ]
do
    /apps/product/php/bin/php ./scripts/gtm/gtm_worker.php $1
done
