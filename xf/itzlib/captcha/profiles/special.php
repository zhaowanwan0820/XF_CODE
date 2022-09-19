<?php
$length = 6; # random 5 or 6 or 7
$width = 160;
$height = 80;
# symbol's vertical fluctuation amplitude
$fluctuation_amplitude = 8;
#noise
//$white_noise_density=0; // no white noise
$white_noise_density=1/30;
//$black_noise_density=0; // no black noise
$black_noise_density=1/30;
# increase safety by prevention of spaces between symbols
$no_spaces = true;
# show credits
$show_credits = false; # set to false to remove credits line. Credits adds 12 pixels to image height
$credits = 'www.xxx.com'; # if empty, HTTP_HOST will be shown

$foreground_color = array(mt_rand(0,80), mt_rand(0,80), mt_rand(0,80));
$background_color = array(mt_rand(220,255), mt_rand(220,255), mt_rand(220,255));
# JPEG quality of CAPTCHA image (bigger is better quality, but larger file size)
$jpeg_quality = 90;
