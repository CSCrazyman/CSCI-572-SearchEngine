<?php
require_once('Apache/Solr/Service.php');

function sortSuggestions($a, $b) {
    return $b->weight - $a->weight;
}

header('Content-Type: application/json');

$query = $_GET["term"];
$solr = new Apache_Solr_Service('localhost', 8983, '/solr/webindex/');

$prefix = "";
$lastSpace = strripos($query, " ");
if ($lastSpace) {
    $prefix = substr($query, 0, $lastSpace + 1);
    $query = substr($query, $lastSpace + 1);
}

try {
    $results = $solr->search($query, 0, 10, array(), "GET", "suggest");
    if ($results) {
        $results = current($results->suggest->suggest);
        $suggestions = $results->suggestions;
        usort($suggestions, "sortSuggestions");
        $list = array();
        $count = 0;
        foreach ($suggestions as $suggestion) {
            $count++;
            $whole = $prefix . (string)$suggestion->term;
            // if (preg_match("/[:\._-]/", $whole) != 1) {
            $list[] = $whole;
            if ($count == 5) break;
            // }
        }
        $list = json_encode($list);
        echo $list;
    }
}
catch (Exception $e) {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
}
?>