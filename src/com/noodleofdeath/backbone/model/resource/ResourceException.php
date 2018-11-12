<?

namespace com\noodleofdeath\backbone\model\resource;

class ResourceException extends \Exception {

    public const CreateException = 1;

    public const FetchException = 2;

    public const UpdateException = 3;

    public const DeleteException = 4;

    public const RestoreException = 5;

    public const DestroyException = 6;

    public $type;

    public $info;

    public function __construct(int $type, $info = null) {
        $this -> type = $type;
        $this -> info = $info;
    }

}

