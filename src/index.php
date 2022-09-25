<?php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$logger = new Logger('dictionary-app');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
$logger->info("Logger Initialized");

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
<html lang="html">
<head>
    <title>Dictionary!</title>
    <link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

    <link rel="stylesheet/less" type="text/css" href="assets/less/main.less" />
    <script src="https://cdn.jsdelivr.net/npm/less" ></script>
</head>
<body>
    <nav>
        <h1>
            <svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M33 9.42989C33 4.78135 29.2187 1 24.5704 1C21.3114 1 18.3906 2.89803 17.0001 5.7244C15.6094 2.89803 12.6883 1 9.42965 1C4.78134 1 1 4.78135 1 9.42989C1 12.6863 2.83332 15.5579 5.63867 16.9585C2.83332 18.3589 1 21.2305 1 24.487C1 29.1355 4.78134 32.9169 9.42965 32.9169C12.6883 32.9169 15.6094 31.0188 17.0001 28.1925C18.3906 31.0188 21.3114 32.9169 24.5704 32.9169C29.2187 32.9169 33 29.1355 33 24.487C33 21.2305 31.1667 18.3589 28.3613 16.9585C31.1667 15.5579 33 12.6863 33 9.42989Z" fill="#33CCCC" stroke="white" stroke-width="2"/>
            </svg>
            Acme Company
        </h1>
    </nav>
<div>
    <div>
        <div class="searchBox">
            <form method="POST" action="/">
                <input id="term" type="text" placeholder="Look up a term" name="term">


                <button type="submit">
                    <svg type="submit" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M16.0686 15.0943H17.1527L24 21.9554L21.9554 24L15.0943 17.1527V16.0686L14.7238 15.6844C13.1595 17.0292 11.1286 17.8388 8.91938 17.8388C3.99314 17.8388 0 13.8456 0 8.91938C0 3.99314 3.99314 0 8.91938 0C13.8456 0 17.8388 3.99314 17.8388 8.91938C17.8388 11.1286 17.0292 13.1595 15.6844 14.7238L16.0686 15.0943ZM2.74447 8.91938C2.74447 12.3362 5.50261 15.0943 8.91942 15.0943C12.3362 15.0943 15.0944 12.3362 15.0944 8.91938C15.0944 5.50257 12.3362 2.74443 8.91942 2.74443C5.50261 2.74443 2.74447 5.50257 2.74447 8.91938Z" fill="#757575"/>
                    </svg>
                </button>
            </form>
        </div>

        <?php if(isset($_REQUEST['term'])): ?>
        <div class="searchResults">
            <h4>Term</h4>
            <?php

            $term = isset($_REQUEST['term']) ? $_REQUEST['term'] : null;
            $definitions = array();
            $lookup = new DictionarySearch();

            if ($term) {
                $definitions = $lookup->lookupDefinition($term);
                $logger->info(print_r($definitions, true));
                echo "<div><h1>$term</h1>";

                if (count($definitions) === 0) {
                    echo "<p>No definitions found, add one?</p>";
                } else {
                    echo "<ul>";
                    foreach ($definitions as $row) {
                        echo "<li>$row[2]</li>";
                    }
                    echo "</ul>";
                }
                echo "<form method='POST' action='/define.php'>
                    <label for='definition'>Definition</label> <br />   
                    <input type='hidden' name='term' value='$term'>            
                    <textarea id='definition' name='definition' placeholder='Enter new definition'></textarea>
                    <button type='submit'>Submit</button>
                </form>";
                echo "</div>";
            }
            ?>

            <div class="clear">&nbsp;</div>
            <hr />
            <div class="clear">&nbsp;</div>

            <h4>Recent Searches</h4>
            <ul class="recent-search-list">
                <?php foreach ($lookup->listRecentDefinitions() as $row) {
                    $logger->info(print_r($row, true)); ?>
                    <li><a href="?term=<?= $row['term'] ?>"><?= $row['term'] ?></a></li>
                <?php } ?>
            </ul>

            <h4>Terms Needing Definitions</h4>
            <ul class="terms-needing-definitions">
                <?php foreach ($lookup->listUndefinedTerms() as $row) {
                    $logger->info(print_r($row, true)); ?>
                    <li><a href="?term=<?= $row['term'] ?>"><?= $row['term'] ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <footer>
    2021 Acme |	Terms and Conditions
</footer>
</div>
</body>
</html>
