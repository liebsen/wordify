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

class Refocus extends \Spot\Entity
{
    protected static $table = "refocuses";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "title" => ["type" => "string"],
            "guid" => ["type" => "string", "length" => 50],
            "putinhall"   => ["type" => "boolean", "value" => false],
            "deleted"   => ["type" => "boolean", "value" => false],
            "complete"   => ["type" => "boolean", "value" => false],
            "enabled"   => ["type" => "boolean", "value" => true],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id'),
            'beliefs' => $mapper->hasMany($entity, 'App\Belief', 'refocus_id')->order(['created' => 'ASC'])
        ];
    }
    
    public function transform(Refocus $entity)
    {
        $beliefs = [];

        /* belief map*/

        if($entity->beliefs){
            foreach($entity->beliefs as $belief){
                if($belief->is_core){
                    if($belief->is_opposite){
                        $beliefs['opposite'] = strtoupper($belief->text);
                    } else {
                        $beliefs['core'] = strtoupper($belief->text);
                    }
                } else {
                    if($belief->parent_id){
                        if($belief->is_selected){
                            if($belief->is_opposite){
                                $beliefs['opposites'][] = strtoupper($belief->text);
                            } else {
                                $beliefs['supports'][] = strtoupper($belief->text);
                            }
                        }
                    }
                }
            }
        }

        return [
            "id" => (int) $entity->id ?: "",
            "guid" => (string) $entity->guid ?: "",
            "code" => (string) $entity->guid ? substr($entity->guid,-8) : "",
            "title" => (string) $entity->title ?: "",
            "guid" => (string) $entity->guid ?: "",
            "created" => (string) $entity->created->format('U'),
            "updated" => (string) $entity->updated->format('U'),
            "beliefs" => (object) $beliefs,
            "author" => (object) [
                "id" => (integer) $entity->user->id ?: null,
                "first_name" => (string) $entity->user->first_name ?: null,
                "last_name" => (string) $entity->user->last_name ?: null,
                "picture" => (string) $entity->user->picture ?: null,
                "email" => (string) $entity->user->email ?: null
            ]
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
