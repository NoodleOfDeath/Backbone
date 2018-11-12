<?php

namespace com\noodleofdeath\backbone\model\resource;

interface Mappable {

    /** Generates a key-value data map of this resource that can be inserted
     * into a database table. <code>validateStructure</code> should always be
     * called before this.
     *
     * @return mixed[] */
    public function dataMap();

}

