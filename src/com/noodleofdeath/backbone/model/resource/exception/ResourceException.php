<?php

namespace com\noodleofdeath\backbone\model\resource\exception;

require_once 'CreateResourceException.php';
require_once 'FetchResourceException.php';
require_once 'UpdateResourceException.php';
require_once 'DeleteResourceException.php';
require_once 'RestoreResourceException.php';
require_once 'DestroyResourceException.php';

class ResourceException extends \Exception {

    public $message;

    public $info;

    public function __construct(string $message = null, $info = null) {
        $this -> message = $message;
        $this -> info = $info;
    }

}

