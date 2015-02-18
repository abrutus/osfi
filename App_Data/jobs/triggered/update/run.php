<?php
/**
 * The following file will download two files, one for entities and one for persons
 * And then proceed to insert them.
 *
 * There is some logic with the names, it generates the nk-combinations and then gets the permutations of those
 * which meeans that there are many writes per name, Combinations = 2^n -1 and and for each combination there is
 * a permutation which is n!
 */
require_once 'D:/site/site/wwwroot/vendor/autoload.php';
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Table\Models\Entity;
use WindowsAzure\Table\Models\EdmType;

// Connection String
$CONN_STRING = getenv('CUSTOMCONNSTR_OSFI_CONN_STRING');
$TABLE_NAME = "osfi";

// Endpoints
$ORG_LIST = "http://www.osfi-bsif.gc.ca/Eng/Docs/entstld.txt";
$ENTITIY_LIST = "http://www.osfi-bsif.gc.ca/Eng/Docs/indstld.txt";

// Toggle for turning off features
$UPDATE_ORGS = TRUE;
$UPDATE_NAMES = TRUE;

// Closest thing to db object
$tableRestProxy = ServicesBuilder::getInstance()->createTableService($CONN_STRING);
/*
 * Accessory functions for name logic
 */
// from o'reilly
function pc_permute($items, $perms = [], &$return) {
    if (empty($items)) {
        $return[] = $perms;
    }  else {
        for ($i = count($items) - 1; $i >= 0; --$i) {
            $newitems = $items;
            $newperms = $perms;
            list($foo) = array_splice($newitems, $i, 1);
            array_unshift($newperms, $foo);
            pc_permute($newitems, $newperms, $return);
        }
    }
}

function nk_combinations(array $sample) {
    $result = [];
    $n  = count($sample);
    $upto = pow(2, $n);
    $count = 0;
    while($count < $upto) {
        $current_result = [];
        for($i=0; $i<$n; $i++) {
            $bmask = 1<< $i;
            if(($bmask & $count) == $bmask) {
                $current_result[] = $sample[$i];
            }
        }
        if($current_result) {
            $result[] = $current_result;
        }
        $count++;
    }
    return $result;
}

function generate_keys(array $sample) {
    $return = [];
    $combinations = nk_combinations($sample);
    foreach($combinations as $combination) {
        $returnval = [];
        pc_permute($combination, [], $returnval);
        $return = array_merge($return, $returnval);
    }
    return $return;
}
/*
 * End accessory functions, start WebJob logic
 */

$org_inserts = $name_inserts = 0;

if($UPDATE_ORGS) {
    $fd = fopen($ORG_LIST, "r");
    $organizations = [];
    while(($line = fgets($fd)) !== false) {
        // The file is tab delimited
        $row = explode("\t", $line);
        // Contains accents
        $row = array_map('utf8_encode', $row);
        // Skip useless lines like metadata and blank footer lines
        if(count($row) == 4 && $row[0] && $row[1]) {
            $row[] = metaphone($row[1]);
            $organizations[] = $row;
        }
    }
    fclose($fd);
    // First element in array are the headers
    $headers = array_shift($organizations);
    array_pop($headers);
    // Custom columns
    $headers[] = "metaphone";

    // Create table and swallow exception if already exists
    try {
        $tableRestProxy->createTable($TABLE_NAME);
    }
    catch(Exception $e){
        $code = $e->getCode();
        echo $error_message = $e->getMessage();
    }
    // Persist organizations
    foreach($organizations as $org) {
        $entity = new Entity;
        // set metaphone of the organization name as the partition key (index).
        $entity->setPartitionKey($org[4]);
        $entity->setRowKey("org:" . $org[4]);
        $entity->addProperty("match", EdmType::STRING, json_encode(array_combine($headers, $org)));
        try {
            $tableRestProxy->insertOrReplaceEntity($TABLE_NAME, $entity);
            $org_inserts++;
        }
        catch(Exception $e){
            echo $e;
            print_r($entity);
        }
    }
}

$organizations = null;

/****
 * NAMES
 */
if($UPDATE_NAMES) {
    $tableRestProxy = ServicesBuilder::getInstance()->createTableService($CONN_STRING);
    $fd = fopen($ENTITIY_LIST, "r");
    $persons = [];
    while(($line = fgets($fd)) !== false) {
        // The file is tab delimited
        $row = explode("\t", $line);
        // Contains accents
        $row = array_map('utf8_encode', $row);
        // Skip useless lines like metadata and blank footer lines
        if(count($row) == 17 && $row[0] && $row[1]) {
            $persons[] = $row;
        }
    }

    fclose($fd);
    $headers = array_shift($persons);

    foreach($persons as $person) {
        // A person can have 1 to 5 names
        $names = array_filter(array_slice($person, 1, 5));
        if(count($names) > 1) {
            // generate the combinations and the permutations of the combinations
            $keys = generate_keys($names);
            // filter out keys that only have a single name
            foreach($keys as $k => $v) {
                if(count($v) < 2) unset($keys[$k]);
            }
            // sort by count, then name to help out with repeated entries
            $custom_cmp = function($a1, $a2) {
                if (count($a1) == count($a2)) {
                    for($i = 0; $i< count($a1); $i++) {
                        $comparison = strcmp($a1[$i], $a2[$i]);
                        if($comparison == 0) {
                            continue;
                        }
                        else return $comparison;
                    }
                    return 0;
                }
                return (count($a1) < count($a2)) ? -1 : 1;
            };

            usort($keys, $custom_cmp);
        }
        else {
            $keys = [$names];
        }
        $strkey = "";
        foreach($keys as $key) {
            print_r($key);
            $glued = join(" ", $key);
            if($strkey == metaphone($glued)) {
                // skip duplicate metaphones, they still match
                // this is why we sorted
                continue;
            }
            $strkey = metaphone($glued);
            $entity = new Entity;
            $entity->setPartitionKey($strkey);
            $entity->setRowKey("name:" . str_replace(["/","\\","#","?"], "", $strkey));
            $entity->addProperty("match", EdmType::STRING, json_encode(array_combine($headers, $person)));
            # exact name match
            $name = preg_replace("/[^A-Za-z0-9 ]/", '', transliterator_transliterate("Any-Latin; Latin-ASCII; Upper()", $glued));
            $separated_by_lines = ltrim(join("-", array_filter(explode(" ", $name))), "-");
            $entity2 = new Entity;
            $entity2->setPartitionKey($separated_by_lines);
            $entity2->setRowKey("exact-name:" . str_replace(["/","\\","#","?"], "", $separated_by_lines));
            $entity2->addProperty("match", EdmType::STRING, json_encode(array_combine($headers, $person)));

            try{
                $tableRestProxy->insertOrReplaceEntity($TABLE_NAME, $entity);
                $tableRestProxy->insertOrReplaceEntity($TABLE_NAME, $entity2);
                $name_inserts++;
            }
            catch(Exception $e){
                echo $e;
                print_r($entity);
            }
        }
        echo "Name: " . join(" ", $names) . " Inserts: " . $name_inserts . PHP_EOL;
    }
}
echo "Total Inserts:" . $name_inserts + $org_inserts;
