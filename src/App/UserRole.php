<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */

namespace App;

use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;
use Spot\EventEmitter;
use Tuupola\Base62;
use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class UserRole extends \Spot\Entity
{
    protected static $table = "users_roles";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "name" => ["type" => "string", "length" => 20],
            "description" => ["type" => "text"],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'users' => $mapper->hasMany($entity, 'App\User', 'user_id')
        ];
    }

    public function transform(UserRole $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "name" => (integer) $entity->name ?: "",
            "description" => (integer) $entity->description ?: "",
            "timespan" => \human_timespan_short($entity->created->format('U'))
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "name" => null,
            "description" => null
        ]);
    }
}
