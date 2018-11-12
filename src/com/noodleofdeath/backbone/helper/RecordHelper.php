<?

namespace com\noodleofdeath\backbone\helper;

use com\noodleofdeath\backbone\model\resource\Record;

class RecordHelper extends ResourceHelper {

    /**
     *
     * @param SQLHelper $SQLHelper
     * @param string $table
     * @param string $model */
    public function __construct(SQLHelper $SQLHelper, string $table,
        string $model) {
        parent::__construct($SQLHelper, $table, $model, Record::PrimaryKey());
    }

}

