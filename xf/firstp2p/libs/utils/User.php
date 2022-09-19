<?php

namespace libs\utils;

class User
{
    public static function birthdayWishesCheck($userInfo)
    {
        $bmonth = $userInfo['bmonth'];
        $bday = $userInfo['bday'];
        $length = strlen($userInfo['idno']);

        $birthMd = false;
        if ($bmonth && $bday) {
            $birthMd = sprintf('%02s%02s', $bmonth, $bday);
        } else if ($length > 0) {
            $birthYmd = $length == 15 ? ('19' . substr($userInfo['idno'], 6, 6)) : substr($userInfo['idno'], 6, 8);
            $birthMd = substr($birthYmd, 4);
        }

        if ($birthMd) {

            $md = date('md');

            if ($birthMd == $md) { //生日当天
                return true;
            }

            $tomorrow = strtotime("+1 day");
            $tomorrowMd = date('md', $tomorrow);
            $tomorrowH = date('H', $tomorrow);

            if ($tomorrowH >= 10 && $birthMd == $tomorrowMd) { //生日前一天
                return true;
            }
        }

        return false;
    }

}
