<?

namespace com\noodleofdeath\backbone\model\resource;

/** */
interface Resource extends Mappable {

    public const TimestampFormat = 'Y-m-d H:i:s';

    public const CreateStructure = 1;

    public const UpdateStructure = 2;

    /** @var string */
    public const kCreatorID = 'creator_id';

    /** @var string */
    public const kCreationDate = 'creation_date';

    /** @var string */
    public const kModifiedDate = 'modified_date';

    /** @var string */
    public const kActivityDate = 'activity_date';

    /** @var string */
    public const kDeleted = 'deleted';

    /**
     *
     * @var string */
    public static function PrimaryKey();


    /** Returns the unique id of this resource.
     *
     * @return number unique id of this resource. */
    public function id();

    /** Returns the creator id of this resourc.
     *
     * @return number creator id of this resource. */
    public function creator_id();

    /** Returns <code>true</code> if, and only if. the specified entity is the
     * creator of this resource; <code>false</code>, otherwise.
     *
     * @param Entity $entity
     *            to check for ownership.
     * @return bool <code>true</code> if, and only if. the specified entity is
     *         the creator of this resource; <code>false</code>, otherwise. */
    public function createdBy(Entity $entity = null);

    /** Returns <code>true</code> if all fields of this instance pass validation
     * necessary for creation serverside.
     *
     * @param int $directive
     *            either to create or update.
     * @param Condition[] $conditions
     *            set of boolean values and/or callable anonymous functions that
     *            return boolean values.
     * @return StructureValidationResult collection of fields that did not pass
     *         validation. */
    public function validateStructure(int $directive, array $conditions = []);

}

