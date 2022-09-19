<?php

class BorrowType {

    static public function isIbaoli ( $type ) {
        if ( (int) $type === 6 ) {
            return true;
        }
        return false;
    }

    static public function isIdanbao ( $type ) {
        if ( (int) $type === 2 ) {
            return true;
        }
        return false;
    }

    static public function isIshoucang ( $type ) {
        if ( (int) $type === 7 ) {
            return true;
        }
        return false;
    }

    static public function isIrongzu ( $type ) {
        if ( (int) $type === 5 ) {
            return true;
        }
        return false;
    }

    static public function isShengxin ( $type ) {
        if ( (int) $type >= 100 && (int) $type <= 1999 ) {
            return true;
        }
        return false;
    }

    static public function isLingqian ( $type ) {
        if ( (int) $type >= 2000 && (int) $type <= 2999 ) {
            return true;
        }
        return false;
    }

}
