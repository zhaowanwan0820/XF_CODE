<?php

// KCAPTCHA PROJECT VERSION 2.0

// Automatic test to tell computers and humans apart

// Copyright by Kruglov Sergei, 2006, 2007, 2008, 2011
// www.captcha.ru, www.kruglov.ru

// System requirements: PHP 4.0.6+ w/ GD

// KCAPTCHA is a free software. You can freely use it for developing own site or software.
// If you use this software as a part of own sofware, you must leave copyright notices intact or add KCAPTCHA copyright notices to own.
// As a default configuration, KCAPTCHA has a small credits text at bottom of CAPTCHA image.
// You can remove it, but I would be pleased if you left it. ;)

// See kcaptcha_config.php for customization

class KCaptcha
{

    // generates keystring and image
    public function __construct($new_config = null)
    {
        $this->new_config = $new_config;
        $config = require(dirname(__FILE__) . '/kcaptcha_config.php');
        $this->keystring = "";

    }

    public function Generate()
    {
        $config = require(dirname(__FILE__) . '/kcaptcha_config.php');

        if ($this->new_config) {
            foreach ($this->new_config as $key => $value) {
                $config[$key] = $value;
            }
        }

        $fonts = array();
        $fontsdir_absolute = dirname(__FILE__) . '/' . $config['fontsdir'];
        if ($handle = opendir($fontsdir_absolute)) {
            while (false !== ($file = readdir($handle))) {
                if (preg_match('/\.png$/i', $file)) {
                    $fonts[] = $fontsdir_absolute . '/' . $file;
                }
            }
            closedir($handle);
        }

        $alphabet_length = strlen($config['alphabet']);

        do {
            // generating random keystring
            while (true) {
                $this->keystring = '';
                for ($i = 0; $i < $config['length']; $i++) {
                    $this->keystring .= $config['allowed_symbols']{mt_rand(0, strlen($config['allowed_symbols']) - 1)};
                }
                if (!preg_match('/cp|cb|ck|c6|c9|rn|rm|mm|co|do|cl|db|qp|qb|dp|ww/', $this->keystring))
                    break;
            }
            print $this->keystring;
            return;

            $font_file = $fonts[mt_rand(0, count($fonts) - 1)];
            $font = imagecreatefrompng($font_file);
            imagealphablending($font, true);

            $fontfile_width = imagesx($font);
            $fontfile_height = imagesy($font) - 1;

            $font_metrics = array();
            $symbol = 0;
            $reading_symbol = false;

            // loading font
            for ($i = 0; $i < $fontfile_width && $symbol < $alphabet_length; $i++) {
                $transparent = (imagecolorat($font, $i, 0) >> 24) == 127;

                if (!$reading_symbol && !$transparent) {
                    $font_metrics[$config['alphabet']{$symbol}] = array(
                        'start' => $i
                    );
                    $reading_symbol = true;
                    continue;
                }

                if ($reading_symbol && $transparent) {
                    $font_metrics[$config['alphabet']{$symbol}]['end'] = $i;
                    $reading_symbol = false;
                    $symbol++;
                    continue;
                }
            }

            $img = imagecreatetruecolor($config['width'], $config['height']);
            imagealphablending($img, true);
            $white = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);

            imagefilledrectangle($img, 0, 0, $config['width'] - 1, $config['height'] - 1, $white);

            // draw text
            $x = 1;
            $odd = mt_rand(0, 1);
            if ($odd == 0)
                $odd = -1;
            for ($i = 0; $i < $config['length']; $i++) {
                $m = $font_metrics[$this->keystring{$i}];

                $y = (($i % 2) * $config['fluctuation_amplitude'] - $config['fluctuation_amplitude'] / 2) * $odd + mt_rand(-round($config['fluctuation_amplitude'] / 3), round($config['fluctuation_amplitude'] / 3)) + ($config['height'] - $fontfile_height) / 2;

                if ($config['no_spaces']) {
                    $shift = 0;
                    if ($i > 0) {
                        $shift = 10000;
                        for ($sy = 3; $sy < $fontfile_height - 10; $sy += 1) {
                            for ($sx = $m['start'] - 1; $sx < $m['end']; $sx += 1) {
                                $rgb = imagecolorat($font, $sx, $sy);
                                $opacity = $rgb >> 24;
                                if ($opacity < 127) {
                                    $left = $sx - $m['start'] + $x;
                                    $py = $sy + $y;
                                    if ($py > $config['height'])
                                        break;
                                    for ($px = min($left, $config['width'] - 1); $px > $left - 200 && $px >= 0; $px -= 1) {
                                        $color = imagecolorat($img, $px, $py) & 0xff;
                                        if ($color + $opacity < 170) { // 170 - threshold
                                            if ($shift > $left - $px) {
                                                $shift = $left - $px;
                                            }
                                            break;
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                        if ($shift == 10000) {
                            $shift = mt_rand(4, 6);
                        }
                    }
                } else {
                    $shift = 1;
                }
                imagecopy($img, $font, $x - $shift, $y, $m['start'], 1, $m['end'] - $m['start'], $fontfile_height);
                $x += $m['end'] - $m['start'] - $shift;
            }
        } while ($x >= $config['width'] - 10); // while not fit in canvas

        // noise
        $white = imagecolorallocate($font, 255, 255, 255);
        $black = imagecolorallocate($font, 0, 0, 0);
        for ($i = 0; $i < (($config['height'] - 30) * $x) * $config['white_noise_density']; $i++) {
            imagesetpixel($img, mt_rand(0, $x - 1), mt_rand(10, $config['height'] - 15), $white);
        }
        for ($i = 0; $i < (($config['height'] - 30) * $x) * $config['black_noise_density']; $i++) {
            imagesetpixel($img, mt_rand(0, $x - 1), mt_rand(10, $config['height'] - 15), $black);
        }

        $center = $x / 2;

        // credits. To remove, see configuration file
        $img2 = imagecreatetruecolor($config['width'], $config['height'] + ($config['show_credits'] ? 12 : 0));
        $foreground = imagecolorallocate($img2, $config['foreground_color'][0], $config['foreground_color'][1], $config['foreground_color'][2]);
        $background = imagecolorallocate($img2, $config['background_color'][0], $config['background_color'][1], $config['background_color'][2]);
        imagefilledrectangle($img2, 0, 0, $config['width'] - 1, $config['height'] - 1, $background);
        imagefilledrectangle($img2, 0, $config['height'], $config['width'] - 1, $config['height'] + 12, $foreground);
        $config['credits'] = empty($config['credits']) ? $_SERVER['HTTP_HOST'] : $config['credits'];
        imagestring($img2, 2, $config['width'] / 2 - imagefontwidth(2) * strlen($config['credits']) / 2, $config['height'] - 2, $config['credits'], $background);

        // periods
        $rand1 = mt_rand(750000, 1200000) / 10000000;
        $rand2 = mt_rand(750000, 1200000) / 10000000;
        $rand3 = mt_rand(750000, 1200000) / 10000000;
        $rand4 = mt_rand(750000, 1200000) / 10000000;
        // phases
        $rand5 = mt_rand(0, 31415926) / 10000000;
        $rand6 = mt_rand(0, 31415926) / 10000000;
        $rand7 = mt_rand(0, 31415926) / 10000000;
        $rand8 = mt_rand(0, 31415926) / 10000000;
        // amplitudes
        $rand9 = mt_rand(330, 420) / 110;
        $rand10 = mt_rand(330, 450) / 100;

        // wave distortion

        for ($x = 0; $x < $config['width']; $x++) {
            for ($y = 0; $y < $config['height']; $y++) {
                $sx = $x + (sin($x * $rand1 + $rand5) + sin($y * $rand3 + $rand6)) * $rand9 - $config['width'] / 2 + $center + 1;
                $sy = $y + (sin($x * $rand2 + $rand7) + sin($y * $rand4 + $rand8)) * $rand10;

                if ($sx < 0 || $sy < 0 || $sx >= $config['width'] - 1 || $sy >= $config['height'] - 1) {
                    continue;
                } else {
                    $color = imagecolorat($img, $sx, $sy) & 0xFF;
                    $color_x = imagecolorat($img, $sx + 1, $sy) & 0xFF;
                    $color_y = imagecolorat($img, $sx, $sy + 1) & 0xFF;
                    $color_xy = imagecolorat($img, $sx + 1, $sy + 1) & 0xFF;
                }

                if ($color == 255 && $color_x == 255 && $color_y == 255 && $color_xy == 255) {
                    continue;
                } else
                    if ($color == 0 && $color_x == 0 && $color_y == 0 && $color_xy == 0) {
                        $newred = $config['foreground_color'][0];
                        $newgreen = $config['foreground_color'][1];
                        $newblue = $config['foreground_color'][2];
                    } else {
                        $frsx = $sx - floor($sx);
                        $frsy = $sy - floor($sy);
                        $frsx1 = 1 - $frsx;
                        $frsy1 = 1 - $frsy;

                        $newcolor = ($color * $frsx1 * $frsy1 + $color_x * $frsx * $frsy1 + $color_y * $frsx1 * $frsy + $color_xy * $frsx * $frsy);

                        if ($newcolor > 255)
                            $newcolor = 255;
                        $newcolor = $newcolor / 255;
                        $newcolor0 = 1 - $newcolor;

                        $newred = $newcolor0 * $config['foreground_color'][0] + $newcolor * $config['background_color'][0];
                        $newgreen = $newcolor0 * $config['foreground_color'][1] + $newcolor * $config['background_color'][1];
                        $newblue = $newcolor0 * $config['foreground_color'][2] + $newcolor * $config['background_color'][2];
                    }

                imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newred, $newgreen, $newblue));
            }
        }

        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', FALSE);
        header('Pragma: no-cache');
        if (function_exists("imagejpeg")) {
            header("Content-Type: image/jpeg");
            imagejpeg($img2, null, $config['jpeg_quality']);
        } else
            if (function_exists("imagegif")) {
                header("Content-Type: image/gif");
                imagegif($img2);
            } else
                if (function_exists("imagepng")) {
                    header("Content-Type: image/x-png");
                    imagepng($img2);
                }
    }

    // returns keystring
    function getKeyString()
    {
        return '' . $this->keystring;
    }
}

?>