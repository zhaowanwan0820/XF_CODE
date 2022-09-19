<?php
namespace NCFGroup\Common\Library;

class TextLib
{

    public static function getStringBetween($content, $startPattern, $endPattern, $startIndex = 0)
    {
        if (empty($content)) {
            return array(
                '',
                -1
            );
        }
        $startIndex = strpos($content, $startPattern, $startIndex);
        if ($startIndex !== false) {
            $startIndex += strlen($startPattern);
            $endIndex = strpos($content, $endPattern, $startIndex);
            if ($endIndex !== false) {
                $subContent = trim(substr($content, $startIndex, $endIndex - $startIndex));
                return array(
                    $subContent,
                    $endIndex+strlen($endPattern)
                );
            }
        }
        return array(
            '',
            -1
        );
    }

    public static function getValueFromArray(array $arr, $index, $default_value) {
        if (array_key_exists($index, $arr)) {
            return $arr[$index];
        } else {
            return $default_value;
        }
    }
}

?>