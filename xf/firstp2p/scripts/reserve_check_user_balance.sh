#!/bin/bash

HOME=/apps/product/nginx/htdocs/firstp2p/
#HOME=/home/dev/git/firstp2p/
cd $HOME

while [ 1 ]
do
    /apps/product/php/bin/php ./scripts/reserve_check_user_balance.php
done
