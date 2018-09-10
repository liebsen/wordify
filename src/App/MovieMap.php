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

class MovieMap extends \Spot\Entity
{
    protected static $table = "movies_map";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "movie_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "parent_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "map_key" => ["type" => "string", "length" => 50],
            "map_value" => ["type" => "text"]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'movie' => $mapper->belongsTo($entity, 'App\Movie', 'movie_id')
        ];
    }
    
    public function transform(MovieMap $entity)
    {
        return [
            "id" => (int) $entity->id ?: "",
            "parent_id" => (string) $entity->parent_id ?: "",
            "map_key" => (string) $entity->map_key ?: "",
            "map_value" => (string) $entity->map_value ?: ""
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
