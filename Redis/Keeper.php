<?php

namespace Dark\RedisKeeperBundle\Redis;

use Dark\RedisKeeperBundle\Redis\KeeperException;

class Keeper
{
    private $client;
    
    public function __consturct(Client $client)
    {
        $this->client = $client;
    }
    
    public function pull($entities)
    {
        $entities = $this->prepareEntities($entities);
        
        foreach ($entities as $entity) {
            if (!method_exists($entity, 'setRedisFields')) {
                throw new KeeperException(sprintf("Entity %s must have setRedisFields() method.", get_class($entity)));
            }
            
            $hashName = sprintf("%s:%d", end(explode("\\", get_class($entity))), $entity->getId());
            $redisData = $this->client->hgetall($hashName);
            
            $entity->setRedisFields($redisData);
        }
    }
    
    public function persist($entities)
    {
        $entities = $this->prepareEntities($entities);
        
        foreach ($entities as $entity) {
            if (!method_exists($entity, 'getRedisFields')) {
                throw new KeeperException(sprintf("Entity %s must have getRedisFields() method.", get_class($entity)));
            }
            
            $hashName = sprintf("%s:%d", end(explode("\\", get_class($entity))), $entity->getId());
            $fields = $entity->getRedisFields();
            
            $this->client->hmset($hashName, $fields);
        }
    }
    
    private function prepareEntities($entities)
    {
        if (!is_array($entities)) {
            $entities = array($entities);
        }
        if (null === $entities) {
            throw new KeeperException("You should pass valid data.");
        }
        
        return $entities;
    }
}