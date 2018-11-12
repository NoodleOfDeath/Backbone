<?

namespace com\noodleofdeath\backbone\model\resource;

/** */
abstract class BaseResource extends BaseMappable implements Resource {

    /** @var number */
    public $creator_id;

    /** @var number */
    public $creation_date;

    /** @var number */
    public $modified_date;

    /** @var number */
    public $activity_date;

    /** @var bool */
    public $deleted;

    /** Constructs a new resource.
     *
     * @param mixed[] $data
     *            associative array containing resource data. */
    public function __construct($data = []) {
        $this -> creator_id = $data[self::kCreatorID];
        $this -> creation_date = self::ParseDate($data[self::kCreationDate]);
        $this -> modified_date = self::ParseDate($data[self::kModifiedDate]);
        $this -> activity_date = self::ParseDate($data[self::kActivityDate]);
        $this -> deleted = $data[self::kDeleted];
    }

    public function creator_id() {
        return $this -> creator_id;
    }

    public function createdBy(Entity $entity = null) {
        return $entity ? $this -> creator_id() == $entity -> id() : false;
    }

    public function validateStructure(int $directive, array $conditions = []) {
        $optional = $directive == self::UpdateStructure;
        foreach ($conditions as $condition) {
            if (!(($optional || $condition -> nullPassesValidation() ? $condition -> value ===
                null : false) || ($condition -> value !== null &&
                $condition -> evaluate())))
                return StructureValidationResult::Failure($condition);
        }
        return StructureValidationResult::Success();
    }

    public function dataMap() {
        $dataMap = parent::dataMap();
        self::Set($dataMap, self::kCreatorID, $this -> creator_id);
        self::Set($dataMap, self::kCreationDate,
            self::FormatDate($this -> creation_date));
        self::Set($dataMap, self::kModifiedDate,
            self::FormatDate($this -> modified_date));
        self::Set($dataMap, self::kActivityDate,
            self::FormatDate($this -> activity_date));
        self::Set($dataMap, self::kDeleted, $this -> deleted);
        return $dataMap;
    }

}

