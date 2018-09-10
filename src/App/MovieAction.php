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

class MovieAction extends \Spot\Entity
{
    protected static $table = "movies_actions";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "length" => 10, "value" => 0],
            "parent_id" => ["type" => "integer", "length" => 10, "value" => 0],
            "movie_id" => ["type" => "integer", "length" => 10, "value" => 0],
            "text" => ["type" => "text"],
            "is_opposite" => ["type" => "boolean", "value" => false, "default" => false],
            "is_selected" => ["type" => "boolean", "value" => false],
            "is_core"   => ["type" => "boolean", "value" => false],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'movie' => $mapper->belongsTo($entity, 'App\Movie', 'movie_id'),
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id')
        ];
    }
    
    public function transform(MovieAction $entity)
    {
        return [
            "id" => (string) $entity->id ?: "0",
            "text" => (string) $entity->text ?: "",
            "parent_id" => (string) $entity->parent_id ?: "0",
            "refocus_id" => (string) $entity->refocus_id ?: "0",
            "is_opposite" => (boolean) $entity->is_opposite,
            "is_selected" => (boolean) $entity->is_selected,
            "is_core" => (boolean) $entity->is_core,
            "created" => (string) $entity->created->format('Y-m-d H:i:s'),
            "updated" => (string) $entity->updated->format('Y-m-d H:i:s')
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "title" => null,
            "image" => null,
            "enabled" => null
        ]);
    }
}
