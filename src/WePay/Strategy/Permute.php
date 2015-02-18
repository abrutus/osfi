<?php
/**
 * Date: 2/12/15
 * Time: 1:45 PM
 * Andre Brutus
 */

namespace WePay\Strategy;


class Permute implements Strategy {
    private $permutations = [];
    private function split($name = "") {
        // split on spaces
        $keywords = preg_split("/\s+/", $name);
        // clean empty results
        $keywords = array_filter($keywords);
        // limit to 6 names
        array_splice($keywords, 7);
        return $keywords;
    }
    // from o'reilly
    private function pc_permute($items, $perms = [], &$return) {
        if (empty($items)) {
            $return[] = $perms;
        }  else {
            for ($i = count($items) - 1; $i >= 0; --$i) {
                $newitems = $items;
                $newperms = $perms;
                list($foo) = array_splice($newitems, $i, 1);
                array_unshift($newperms, $foo);
                $this->pc_permute($newitems, $newperms, $return);
            }
        }
    }

    private function nk_combinations(array $sample) {
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

    private function generate_keys(array $sample) {
        $return = [];
        $combinations = $this->nk_combinations($sample);
        foreach($combinations as $combination) {
            $returnval = [];
            $this->pc_permute($combination, [], $returnval);
            $return = array_merge($return, $returnval);
        }
        $remove_single_names = function($name) {
            return count($name) < 2 ? false : true;
        };
        return array_filter($return, $remove_single_names);
    }
    private function permute(array $names, $current_perm ="") {
        $result = [];
        if(!$names) return $current_perm;
        foreach($names as $k => $name) {
            $name_without_k = $names;
            unset($name_without_k[$k]);
            $result [] = $this->permute($name_without_k, $current_perm . $name);
        }
        return $result;
    }
    public function __construct($name) {// (\ExactName $name) {
        // get cleaned up name
        $this->permutations = $this->generate_keys($this->split($name));
        // get parts of name
    }

    public function getResult() {
        return $this->permutations;
    }
}