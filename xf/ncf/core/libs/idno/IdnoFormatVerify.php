<?php
/**
 * 身份证号码格式验证
 * from o2o-crm/web/app/plugins/Utils.php
 */

namespace libs\idno;

class IdnoFormatVerify {

    /**
     * 加权因子
     */
    public static $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

    /**
     * 校验码对应值
     */
    public static $verifyNumberList = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');

    /**
     * 检证身份证号码格式是否正确
     */
    public static function checkFormat($card) {
        if (empty($card)) {
            return false;
        }

        $card = self::to18Card($card);
        if (strlen($card) != 18) {
            return false;
        }

        $cardBase = substr($card, 0, 17);
        return (self::getVerifyNum($cardBase) == strtoupper(substr($card, 17, 1)));
    }

    /**
     * 格式化15位身份证号码为18位
     */
    public static function to18Card($card) {
        $card = trim($card);

        if (strlen($card) == 18) {
            return $card;
        }

        if (strlen($card) != 15) {
            return false;
        }

        // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
        if (array_search(substr($card, 12, 3), array('996', '997', '998', '999')) !== false) {
            $card = substr($card, 0, 6) . '18' . substr($card, 6, 9);
        } else {
            $card = substr($card, 0, 6) . '19' . substr($card, 6, 9);
        }
        $card = $card . self::getVerifyNum($card);
        return $card;
    }

    /**
     * 计算身份证校验码，根据国家标准gb 11643-1999
     */
    private static function getVerifyNum($cardBase) {
        if (strlen($cardBase) != 17) {
            return false;
        }

        $checksum = 0;
        for ($i = 0; $i < strlen($cardBase); $i++) {
            $checksum += substr($cardBase, $i, 1) * self::$factor[$i];
        }

        $mod = $checksum % 11;
        $verifyNumber = self::$verifyNumberList[$mod];

        return $verifyNumber;
    }
}
