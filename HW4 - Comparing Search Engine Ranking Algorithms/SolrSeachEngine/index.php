<?php
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
$algorithm = isset($_REQUEST['algorithm']) ? $_REQUEST['algorithm'] : 'lucene';
$dict = array();

$luceneParas = array(
  'fl' => 'id,og_description,title,og_url'
);
$pageRankParas = array(
  'fl' => 'id,og_description,title,og_url',
  'sort' => 'pageRankFile desc'
);

// Reads the mapping file
$file = fopen('URLtoHTML_fox_news.csv', 'r');
if ($file) {
  while ($pair = fgetcsv($file, 0, ',')) {
    $dict[$pair['0']] = $pair['1'];
  }
  fclose($file);
}

if ($query) {
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('Apache/Solr/Service.php');

  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/webindex/');

  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1) {
    $query = stripslashes($query);
  }

  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try {
    if (strcmp($algorithm, 'lucene') == 0) $results = $solr->search($query, 0, $limit, $luceneParas); 
		else $results = $solr->search($query, 0, $limit, $pageRankParas);
  }
  catch (Exception $e) {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}
?>

<html>
  <head>
    <title>Solr Search Engine</title>
    <style>
    .table_cell {
      border: 0.25px solid grey;
    }
    .table_a, .table_b {
      text-decoration: none;
    }  
    .table_a {
      color: black;
    }
    </style>
  </head>
  <body>
    <h2>CSCI 572 - Information Retrieval and Web Search Engines</h2>
    <h3>Solr Search Engine</h3>
    <form id="query_form" accept-charset="utf-8" method="get">
      <label for="query_content">Search:</label>
      <input id="query_content" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
      <input type="submit"/>
      <!-- Options for Ranking Algorithm -->
      &nbsp;&nbsp;&nbsp;&nbsp;Ranking Algorithm:&nbsp;&nbsp;
      <input type="radio" name="algorithm" value="lucene"
              <?php if (strcmp($algorithm, "lucene") == 0) echo "checked"; ?>/>Lucene (Solr Default)
      &nbsp;&nbsp;
      <input type="radio" name="algorithm" value="pagerank"
              <?php if (strcmp($algorithm, "pagerank") == 0) echo "checked"; ?>/>External PageRank
    </form>
<?php
// display results
if ($results) {
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    <ol>
<?php
  // iterate result documents
  foreach ($results->response->docs as $doc) {
    // iterate document fields / values
    $docTitle = "N/A";
    $docUrl = "N/A";
    $docId = "N/A";
    $docDescription = "N/A";
    foreach ($doc as $field => $value) {
      if ($field == "title") $docTitle = $value;
      else if ($field == "id") $docId = $value;
      else if ($field == "og_url") $docUrl = $value;
      else $docDescription = $value;
    }
    if (strcmp($docUrl, "N/A") == 0) {
      $docUrl = $dict[$docId];
    }
?>
      <li>
        <table style="border: 1px solid black; text-align: left; width: 100%">
          <tr>
            <th class="table_cell" style="width: 50px"><?php echo htmlspecialchars('Title', ENT_NOQUOTES, 'utf-8'); ?></th>
            <td class="table_cell"><a href="<?php echo htmlspecialchars($docUrl, ENT_NOQUOTES, 'utf-8'); ?>" target="_blank" class="table_a">
              <?php echo htmlspecialchars($docTitle, ENT_NOQUOTES, 'utf-8'); ?>
            </a></td>
          </tr>
          <tr>
            <th class="table_cell" style="width: 50px"><?php echo htmlspecialchars('URL', ENT_NOQUOTES, 'utf-8'); ?></th>
            <td class="table_cell"><a href="<?php echo htmlspecialchars($docUrl, ENT_NOQUOTES, 'utf-8'); ?>" target="_blank" class="table_b">
              <?php echo htmlspecialchars($docUrl, ENT_NOQUOTES, 'utf-8'); ?>
            </a></td>
          </tr>
          <tr>
            <th class="table_cell" style="width: 50px"><?php echo htmlspecialchars('ID', ENT_NOQUOTES, 'utf-8'); ?></th>
            <td class="table_cell"><?php echo htmlspecialchars($docId, ENT_NOQUOTES, 'utf-8'); ?></td>
          </tr>
          <tr>
            <th class="table_cell" style="width: 50px"><?php echo htmlspecialchars('Description', ENT_NOQUOTES, 'utf-8'); ?></th>
            <td class="table_cell"><?php echo htmlspecialchars($docDescription, ENT_NOQUOTES, 'utf-8'); ?></td>
          </tr>
        </table>
      </li>
<?php
  }
?>
    </ol>
<?php
}
?>
  </body>
</html>