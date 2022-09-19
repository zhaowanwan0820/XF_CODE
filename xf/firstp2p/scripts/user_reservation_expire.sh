#!/bin/bash

HOME=/apps/product/nginx/htdocs/firstp2p/
#HOME=/home/dev/git/firstp2p/
cd $HOME

while [ 1 ]
do
    /apps/product/php/bin/php ./scripts/user_reservation_expire.php
done
