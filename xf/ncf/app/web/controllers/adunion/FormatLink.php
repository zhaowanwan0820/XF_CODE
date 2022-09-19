<?php
namespace web\controllers\adunion;

class FormatLink {
    public static function formatLink($cn, $pubId, $ref, $link) {
        return sprintf('http://u.firstp2p.com/click?cn=%s&pubId=%d&ref=%s&redirect=%s', $cn, $pubId, $ref, $link);
    }
}
