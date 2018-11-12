<?php

namespace com\noodleofdeath\backbone\model\resource;

abstract class BaseMappable implements Mappable {

    /**
     *
     * @param mixed $value
     * @param mixed $default
     * @return array|null */
    protected static function Decode(string $value = null, $default = null,
        $delimiter = '/,/') {
        if ($value !== null) {
            if (is_array($parts = json_decode($value, JSON_NUMERIC_CHECK)) ||
                is_array($parts = preg_split($delimiter, $value))) {
                if ($default !== null && count($parts) == 1)
                    array_push($parts, $default);
                return $parts;
            }
        }
        return null;
    }

    /**
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return bool */
    protected static function Set(array &$array = [], string $key, $value = null) {
        if ($value === null)
            return false;
        $array[$key] = $value;
        return true;
    }

    protected static function ParseDate($date = null) {
        if (!$date)
            return null;
        if (is_numeric($date))
            return $date;
        if (is_string($date))
            return strtotime($date);
        return null;
    }

    protected static function FormatDate($date = null) {
        if (!$date)
            return null;
        if (is_numeric($date))
            return date(self::TimestampFormat, $date);
        if (is_string($date))
            return self::FormatDate(self::ParseDate($date));
        return null;
    }

    public function dataMap() {
        $dataMap = [];
        return $dataMap;
    }

}

