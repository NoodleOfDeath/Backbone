<?php

namespace com\noodleofdeath\backbone\model\resource;

class Condition {

    public const OptNullPassesValidation = 1 << 0;

    public $field;

    public $value;

    private $condition;

    private $opts;

    private $failure;

    public function __construct(string $field, $value, $condition, int $opts = 0,
        string $failure = null) {
        $this -> field = $field;
        $this -> value = $value;
        $this -> condition = $condition;
        $this -> opts = $opts;
        $this -> failure = $failure;
    }

    public function evaluate() {
        return (bool) $this -> condition;
    }

    public function nullPassesValidation() {
        return $this -> opts & self::OptNullPassesValidation;
    }

}

