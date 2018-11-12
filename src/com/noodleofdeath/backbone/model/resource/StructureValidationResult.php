<?php

namespace com\noodleofdeath\backbone\model\resource;

class StructureValidationResult {

    public $passed;

    public $condition;

    public function __construct(bool $passed, Condition $condition = null) {
        $this -> passed = $passed;
        $this -> condition = $condition;
    }

    public static function Success() {
        return new StructureValidationResult(true);
    }

    public static function Failure(Condition $condition = null) {
        return new StructureValidationResult(false, $condition);
    }

}

