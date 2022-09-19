#!/bin/sh
while [ 1 ]
do
    /apps/product/php/bin/php ./scripts/jobs_worker.php $1
done
