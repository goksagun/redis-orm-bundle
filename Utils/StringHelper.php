<?php

namespace Goksagun\RedisOrmBundle\Utils;

final class StringHelper
{
    public static function slug($text, $delimiter = '-')
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', $delimiter, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $delimiter);

        // remove duplicate -
        $text = preg_replace('~-+~', $delimiter, $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n'.$delimiter.'a';
        }

        return $text;
    }
}