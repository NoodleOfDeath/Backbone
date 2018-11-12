<?php

namespace com\noodleofdeath\backbone\model\primatives;

class Range {

    public $min;

    public $max;

    public function __construct($min, $max) {
        $this -> min = $min;
        $this -> max = $max;
    }

}

