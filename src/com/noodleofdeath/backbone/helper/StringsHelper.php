<?php

namespace com\noodleofdeath\backbone\helper;

/** Simple string helper with convenience string manipulation methods. */
abstract class StringsHelper {

    public const kLengthIsBetween = 'LengthIsBetween';

    public const kRegex = 'RegularExpression';

    /** Returns the domain and domain suffix of a specified url.
     *
     * @param string $url
     * @return string root domain substring of $url that contains only the
     *         domain name and the domain suffix. */
    public static function DomainRoot($url) {
        $matches = [];
        preg_match('/[\w-]+\.\w+(?=[^\.]*$)/', $url, $matches);
        return $matches[0];
    }

    /**
     *
     * @param string $lhs
     * @param string $rhs */
    public static function JoinPathComponents($lhs, $rhs) {
    }

    /**
     *
     * @param int $length
     * @param string $keyspace
     * @return string */
    public static function RandomString(int $length,
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i)
            $pieces[] = $keyspace[random_int(0, $max)];
        return implode('', $pieces);
    }

}

