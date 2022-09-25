<?php
require __DIR__ . '/vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

// this would extend class Dictionary
class DictionaryDefinitions {
    public $mysqli;
    public $logger;

    public function __construct() {
        $this->logger = new Logger('define');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        $this->logger->info("Logger Initialized");

        $this->mysqli = new mysqli("db", "root", "", "dictionary");
    }

    public function saveDefinition($term, $definition)
    {
        $termId = $this->mysqli->query("SELECT * FROM term where term='$term'")->fetch_row()[0];
        $this->mysqli->query("INSERT INTO definition (term_id, text) VALUES ($termId, '$definition')");
    }
}

$term = isset($_REQUEST['term']) ? $_REQUEST['term'] : null;
$definition = isset($_REQUEST['definition']) ? $_REQUEST['definition'] : null;

$dictionary = new DictionaryDefinitions();
$dictionary->saveDefinition($term,$definition);
header("Location: /?term=$term");