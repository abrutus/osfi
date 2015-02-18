<?php
/**
 * Date: 2/18/15
 * Time: 9:09 AM
 * Andre Brutus
 */

namespace WePay\Service;


class Osfi {
    public $query_string;
    const TYPE_EXACT = 'exactname';
    public static function query($type = self::TYPE_EXACT, $client, $table, $name) {
        switch ($type) {
            default:
            $filter = "PartitionKey eq '" . $name . "' ";
            $filter .= "and RowKey eq 'exact-name:" . $name . "' ";
        }
        $result = $client->queryEntities($table, $filter);

        $entities = $result->getEntities();
        $result_array = [];
        // multiple results per key, remove duplicates by hashing to the unique id
        foreach ($entities as $entity) {
            $parsed = json_decode(utf8_decode($entity->getPropertyValue("match")));
            $id = current(explode(".", current($parsed)));
            $result_array[$id] = $parsed;
        }
        return $result_array;
    }
}