<?php

namespace com\noodleofdeath\backbone\model\access;

use com\noodleofdeath\backbone\model\resource\Entity;
use com\noodleofdeath\backbone\model\resource\Resource;

/** Data structure that represents an access mapping of provisioned actions
 * permitted by an entity to a resource. */
class AccessMap {

    /** @var number permission to read a resource. */
    public const READ = (1 << 0);

    /** @var number permission to interact with a resource. */
    public const INTERACT = (1 << 1);

    /** @var number permission to make progress and media uploads to a resource. */
    public const APPEND = (1 << 2);

    /** @var number permission to make actual edits to a resouce's metadata. */
    public const UPDATE = (1 << 3);

    /** @var number permission to (soft) delete a resource. */
    public const DELETE = (1 << 4);

    /** @var number permission to (soft) delete a resource. */
    public const RESTORE = (1 << 5);

    /** @var number permission to hard delete a resource. */
    public const DESTROY = (1 << 6);

    /** @var number permissions granted to the creator of a resource. */
    public const CREATOR = self::READ | self::INTERACT | self::APPEND |
        self::UPDATE | self::DELETE | self::RESTORE;

    /** @var number permissions granted to an admin user/role. */
    public const ADMIN = self::READ | self::INTERACT | self::APPEND |
        self::UPDATE | self::DELETE | self::RESTORE | self::DESTROY;

    /** @var number permissions granted to a root user/role. */
    public const ROOT = self::READ | self::INTERACT | self::APPEND | self::UPDATE |
        self::DELETE | self::RESTORE | self::DESTROY;

    /** @var Entity entity associated with this access control instance. */
    public $entity;

    /** @var Resource resource being accessed by an entity. */
    public $resource;

    /** @var integer permissions for this access control. */
    public $permissions;

    /** Constructs a new access map provisioning a specified entity access to a
     * specified resource with specified granted permissions.
     *
     * @param Entity $entity
     *            to be provisioned by this access map.
     * @param Resource $resource
     *            to be accessed.
     * @param number $permissions
     *            to grant. */
    public function __construct(Entity &$entity, Resource &$resource = null,
        $permissions) {
        $this -> entity = $entity;
        $this -> resource = $resource;
        if (is_numeric($permissions))
            $this -> permissions = $permissions;
    }

    /** Returns an access map with all permissions denied for a specified entity
     * to access a specified resource.
     *
     * @param Entity $entity
     *            to be provisioned by this access map.
     * @param Resource $resource
     *            to be accessed.
     * @return AccessMap an access map with all permissions denied for a
     *         specified entity to access a specified resource. */
    public static function DenyAll(Entity $entity, Resource $resource = null) {
        return new AccessMap($entity, $resource, 0);
    }

    /** Returns an access map with read-only permissions granted for a specified
     * entity to access a specified resource.
     *
     * @param Entity $entity
     *            to be provisioned by this access map.
     * @param Resource $resource
     *            to be accessed.
     * @return AccessMap an access map with read-only permissions granted for a
     *         specified entity to access a specified resource. */
    public static function ReadOnly(Entity $entity, Resource $resource) {
        return new AccessMap($entity, $resource, self::READ);
    }

    /** Returns an access map with interact-only permissions granted for a
     * specified entity to access a specified resource (this includes read
     * permissions).
     *
     * @param Entity $entity
     *            to be provisioned by this access map.
     * @param Resource $resource
     *            to be accessed.
     * @return AccessMap an access map with interact-only permissions granted
     *         for a specified entity to access a specified resource. */
    public static function InteractOnly(Entity $entity, Resource $resource) {
        return new AccessMap($entity, $resource, self::READ | self::INTERACT);
    }

    /** Returns an access map with append-only permissions granted for a
     * specified entity to access a specified resource (this includes read
     * permissions).
     *
     * @param Entity $entity
     *            to be provisioned by this access map.
     * @param Resource $resource
     *            to be accessed.
     * @return AccessMap an access map with append-only permissions granted for
     *         a specified entity to access a specified resource. */
    public static function AppendOnly(Entity $entity, Resource $resource) {
        return new AccessMap($entity, $resource, self::READ | self::APPEND);
    }

    /** Returns an access map with update-only permissions granted for a
     * specified entity to access a specified resource (this includes read
     * permissions).
     *
     * @param Entity $entity
     *            to be provisioned by this access map.
     * @param Resource $resource
     *            to be accessed.
     * @return AccessMap an access map with update-only permissions granted for
     *         a specified entity to access a specified resource. */
    public static function UpdateOnly(Entity $entity, Resource $resource) {
        return new AccessMap($entity, $resource, self::READ | self::UPDATE);
    }

    /** Returns an access map with modify-only permissions granted for a
     * specified entity to access a specified resource (this includes read
     * permissions).
     *
     * @param Entity $entity
     *            to be provisioned by this access map.
     * @param Resource $resource
     *            to be accessed.
     * @return AccessMap an access map with modify-only permissions granted for
     *         a specified entity to access a specified resource. */
    public static function DeleteOnly(Entity $entity, Resource $resource) {
        return new AccessMap($entity, $resource, self::READ | self::DELETE);
    }

    /** Returns an access map with creator permissions granted for a specified
     * entity to access a specified resource (this includes all permissions).
     *
     * @param Entity $entity
     *            to be provisioned by this access map.
     * @param Resource $resource
     *            to be accessed.
     * @return AccessMap an access map with creator permissions granted for a
     *         specified entity to access a specified resource. */
    public static function Creator(Entity $entity, Resource $resource) {
        return new AccessMap($entity, $resource, self::CREATOR);
    }

    /** Returns an access map with root permissions granted for a specified
     * entity to access a specified resource (this includes all permissions).
     *
     * @param Entity $entity
     *            to be provisioned by this access map.
     * @param Resource $resource
     *            to be accessed.
     * @return AccessMap an access map with root permissions granted for a
     *         specified entity to access a specified resource. */
    public static function Root(Entity $entity, Resource $resource) {
        return new AccessMap($entity, $resource, self::ROOT);
    }

    /** Intersects permissions with two maps and returns <code>$this</code> for
     * method chaining.
     *
     * @param AccessMap $dataMap
     *            to merge with this access map.
     * @return AccessMap this access map. */
    public function with(AccessMap $dataMap) {
        $this -> permissions &= $dataMap -> permissions;
        return $this;
    }

    /** Returns <code>true</code> if, and only if, <code>$this ->
     * resource</code> is not <code>null</code>; <code>false</code>, otherwise.
     *
     * @return bool <code>true</code> if, and only if, <code>$this ->
     *         resource</code> is not <code>null</code>; <code>false</code>,
     *         otherwise. */
    public function resourceExists() {
        return !is_null($this -> resource);
    }

    /** Returns <code>true</code> if, and only if, this access map grants read
     * permissions; <code>false</code>, otherwise.
     *
     * @return bool <code>true</code> if, and only if, this access map grants
     *         read permissions; <code>false</code>, otherwise. */
    public function canRead() {
        return $this -> permissions & self::READ;
    }

    /** Returns <code>true</code> if, and only if, this access map grants
     * interact permissions; <code>false</code>, otherwise.
     *
     * @return bool <code>true</code> if, and only if, this access map grants
     *         interact permissions; <code>false</code>, otherwise. */
    public function canInteract() {
        return $this -> permissions & self::INTERACT;
    }

    /** Returns <code>true</code> if, and only if, this access map grants modify
     * permissions; <code>false</code>, otherwise.
     *
     * @return bool <code>true</code> if, and only if, this access map grants
     *         modify permissions; <code>false</code>, otherwise. */
    public function canUpdate() {
        return $this -> permissions & self::UPDATE;
    }

    /** Returns <code>true</code> if, and only if, this access map grants modify
     * permissions; <code>false</code>, otherwise.
     *
     * @return bool <code>true</code> if, and only if, this access map grants
     *         modify permissions; <code>false</code>, otherwise. */
    public function canAppend() {
        return $this -> permissions & self::APPEND;
    }

    /** Returns <code>true</code> if, and only if, this access map grants delete
     * permissions; <code>false</code>, otherwise.
     *
     * @return bool <code>true</code> if, and only if, this access map grants
     *         delete permissions; <code>false</code>, otherwise. */
    public function canDelete() {
        return $this -> permissions & self::DELETE;
    }

    /** Returns <code>true</code> if, and only if, this access map grants
     * restore permissions; <code>false</code>, otherwise.
     *
     * @return bool <code>true</code> if, and only if, this access map grants
     *         restore permissions; <code>false</code>, otherwise. */
    public function canRestore() {
        return $this -> permissions & self::RESTORE;
    }

    /** Returns <code>true</code> if, and only if, this access map grants
     * destroy permissions; <code>false</code>, otherwise.
     *
     * @return bool <code>true</code> if, and only if, this access map grants
     *         destroy permissions; <code>false</code>, otherwise. */
    public function canDestroy() {
        return $this -> permissions & self::DESTROY;
    }

    /** Returns <code>true</code> if, and only if, this access map grants
     * creator permissions; <code>false</code>, otherwise.
     *
     * @return bool <code>true</code> if, and only if, this access map grants
     *         creator permissions; <code>false</code>, otherwise. */
    public function isCreator() {
        return $this -> permissions & self::CREATOR;
    }

}

