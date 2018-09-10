<?php

/*
 * This file is part of the Slim API skeleton packagesdfsdf
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

class Movie extends \Spot\Entity
{
    protected static $table = "movies";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "title" => ["type" => "string", "length" => 250],
            "title_slug" => ["type" => "string", "length" => 250],
            "intro" => ["type" => "text"],
            "pic1_url" => ["type" => "string", "length" => 255],
            "youtube" => ["type" => "string", "length" => 50],
            "bg_url" => ["type" => "string", "length" => 255],
            "content_html" => ["type" => "text"],
            "from_datetime" => ["type" => "datetime", "value" => new \DateTime()],
            "to_datetime" => ["type" => "datetime", "value" => new \DateTime()],
            "deleted" => ["type" => "boolean", "value" => false, "notnull" => true],
            "enabled" => ["type" => "boolean", "default" => true, "value" => true],
            "created" => ["type" => "datetime", "value" => new \DateTime()],
            "updated" => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'map' => $mapper->hasMany($entity, 'App\MovieMap', 'movie_id')
                ->order(["id" => "ASC"]),
            'actions' => $mapper->hasMany($entity, 'App\MovieAction', 'movie_id')
                ->order(["id" => "ASC"])
        ];
    }
    
    public function transform(Movie $entity)
    {

        $map = [];
        foreach($entity->map as $item){
            $map[]= (object) [
                "id" => (int) $item->id,
                "parent_id" => (int) $item->parent_id,
                "key" => (string) $item->map_key,
                "value" => (string) $item->map_value
            ];
        }

        return [
            "id" => (integer) $entity->id ?: null,
            "title" => (string) $entity->title ?: "",
            "intro" => (string) $entity->intro ?: "",
            "slug" => (string) $entity->title_slug ?: "",
            "content" => (string) $entity->content_html ?: "",
            "picture" => (string) $entity->pic1_url ?: "",
            "position" => (string) $entity->position->slug ?: "",
            "map" => (object) $map
            //"created" => (string) $entity->created->format('U') ?: "",
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "fullname" => null
        ]);
    }
}
