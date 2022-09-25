<?php

require __DIR__ . '/vendor/autoload.php';

// this would extend class Dictionary
class DictionarySearch {
    public $mysqli;

    public function __construct() {
        $this->mysqli = new mysqli("db", "root", "", "dictionary");
    }

    public function listUndefinedTerms()
    {
        return $this->mysqli->query("SELECT distinct term.* FROM term left join definition on term.id=definition.term_id where definition.id is null order by search_count DESC, last_search_tstamp desc limit 10");
    }

    public function listRecentDefinitions()
    {
        return $this->mysqli->query("SELECT term.* FROM term join definition on term.id=definition.term_id ORDER BY last_search_tstamp desc limit 10");
    }

    public function lookupDefinition($term)
    {
        $this->mysqli->query("INSERT INTO term (term) VALUES ('$term') ON DUPLICATE KEY update last_search_tstamp=now(), search_count = search_count + 1");
        $result = $this->mysqli->query("SELECT definition.* from definition join term on term.id=definition.term_id where term.term='$term'");
        return $result->fetch_all();
    }

}
?>