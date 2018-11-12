<?php

namespace com\noodleofdeath\backbone\model\resource;

class Record extends BaseResource {

    public const kRecordID = 'record_id';

    public $record_id;

    public function __construct($data = []) {
        parent::__construct($data);
        $this -> record_id = $data[self::kRecordID];
    }

    public static function PrimaryKey() {
        return self::kRecordID;
    }

    public function id() {
        return $this -> record_id;
    }

    public function validateStructure(int $directive, array $conditions = []) {
        return parent::validateStructure($directive, $conditions);
    }

    public function dataMap() {
        $dataMap = parent::dataMap();
        return $dataMap;
    }

}

