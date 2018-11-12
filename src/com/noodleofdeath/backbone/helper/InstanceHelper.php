<?

namespace com\noodleofdeath\backbone\helper;

class InstanceHelper {

    public const OptSetOnlyIfValueIsNotNull = 1 << 0;

    /**
     *
     * @param string $type
     * @param mixed[] $args
     * @return object */
    public static function newInstance(string $type, $args = []) {
        return (new \ReflectionClass($type)) -> newInstance($args);
    }

    /**
     *
     * @param mixed $var
     * @param mixed $value
     * @param int $opts
     * @return bool */
    public static function set(&$var, $value,
        int $opts = self::OptSetOnlyIfValueIsNotNull) {
        if (($opts & self::OptSetOnlyIfValueIsNotNull) && is_null($value))
            return false;
        $var = $value;
        return true;
    }

    /**
     *
     * @param array $array
     * @param mixed $key
     * @param mixed $value
     * @param int $opts
     * @return bool */
    public static function array_set(array &$array, $key, $value,
        int $opts = self::OptSetOnlyIfValueIsNotNull) {
        if (($opts & self::OptSetOnlyIfValueIsNotNull) && is_null($value))
            return false;
        $array[$key] = $value;
        return true;
    }

}

