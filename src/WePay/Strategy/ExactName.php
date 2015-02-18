<?php
/**
 * Date: 2/12/15
 * Time: 12:06 PM
 * Andre Brutus
 */
namespace WePay\Strategy;

class ExactName implements \WePay\Strategy\Strategy {
    private $result;
    private function normalize($index) {
        // Transliterate utf-8 names to latin representation
        $index = transliterator_transliterate("Any-Latin; Latin-ASCII; Upper()", $index);
        // Remove all non A-z characters
        return preg_replace("/[^A-Za-z0-9 ]/", '', $index);
    }
    public function __construct(\WePay\Interfaces\Indexable $structure) {
       $this->result = $this->normalize($structure->getIndex());
    }

    public function getResult() {
        return $this->result;
    }
}