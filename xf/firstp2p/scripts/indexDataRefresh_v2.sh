#!/bin/sh
siteId=$1
while [ 1 ]
do
    /apps/product/php/bin/php ./scripts/indexDataRefresh_v2.php $siteId
done
