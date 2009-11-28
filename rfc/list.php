<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(dirname( __FILE__ ) . '/../includes/db.php');

function wikilink($text, $target)
{
    $text = str_replace('_', ' ', $text);
    if (mb_strlen($text, 'utf-8') > 55)
    {
        $text = mb_substr($text, 0, 50, 'utf-8') . '…';
    }
    return '<a href="http://cs.wikipedia.org/wiki/' . htmlspecialchars($target) . '">' . htmlspecialchars($text) . '</a>';
}

function oldrevlink($text, $revid)
{
    return "<a href='http://cs.wikipedia.org/w/index.php?oldid=$revid'>" . $text . '</a>';
}

function difflink($text, $revid)
{
    return "<a href='http://cs.wikipedia.org/w/index.php?diff=$revid&diffonly=1'>" . $text . '</a>';
}

function format_timestamp($ts, $sortable = true, $withtime = false)
{
    $tss = $ts . '';
    $result = substr($tss, 6, 2) . '. ' . substr($tss, 4, 2) . '. ' . substr($ts, 0, 4);
    if ($sortable) $result = "<span style='display:none'>$ts</span>" . $result;
    if ($withtime) $result .= ', ' . substr($ts, 8, 2) . ':' . substr($ts, 10, 2) . ':' . substr($ts, 12, 2);
    return $result;
}

$PAGE_PREFIX = 'Žádost_o_komentář/';
$PAGE_PREFIX_LEN = strlen($PAGE_PREFIX);

$db = connect_to_db('cswiki');
if (!$db)
{
	header('Status: 500');
	echo 'Error connecting to database';
	return;
}

$rfcfrontpageid = get_pageid($db, 4, 'Žádost_o_komentář');
if (!$rfcfrontpageid)
{
	header('Status: 500');
	echo 'Could not find RFC front page';
	return;
}

$queryresult = mysql_query("SELECT page_title, page_id FROM page LEFT JOIN pagelinks ON pl_from=$rfcfrontpageid AND pl_namespace=4 AND pl_title=page_title WHERE page_namespace=4 AND page_title LIKE '$PAGE_PREFIX%' AND page_is_redirect=0 AND pl_from IS NULL", $db);
if (!$queryresult)
{
	header('Status: 500');
	echo 'Error executing query';
	return;
}

?><!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Seznam ukončených žádostí o komentář na české Wikipedii</title>
  <link rel="stylesheet" href="http://cs.wikipedia.org/skins-1.5/common/main-ltr.css" media="screen" />
  <link rel="stylesheet" href="http://cs.wikipedia.org/skins-1.5/common/shared.css" media="screen" />
  <script type="text/javascript">
var skin="none",
wgUserLanguage="cs",
wgContentLanguage="cs",
stylepath="http://cs.wikipedia.org/skins-1.5",
wgBreakFrames=false;</script>
  <script src="http://cs.wikipedia.org/skins-1.5/common/wikibits.js" type="text/javascript"></script>
  <style type="text/css">
a { text-decoration: none; color: black; }
a:hover { text-decoration: underline; color: #00e; }
a img { border: none; }
  </style>
</head>
<body class="mediawiki ltr">
    <h1>Seznam ukončených žádostí o komentář na české Wikipedii</h1>

    <table class="wikitable sortable">
        <tr><th>Název</th><th>Založena</th><th>Kým</th><th>Poslední editace</th><th>Kdo</th></tr>
<?php

$count = 0;
while ($row = mysql_fetch_row($queryresult))
{
    $pagetitle = $row[0];
    $pageid = $row[1];

    $oldestres = mysql_query("SELECT rev_user_text, rev_timestamp, rev_id FROM revision WHERE rev_page=$pageid ORDER BY rev_id ASC LIMIT 1");
    $oldestrev = mysql_fetch_row($oldestres);

    $newestres = mysql_query("SELECT rev_user_text, rev_timestamp, rev_id FROM revision LEFT JOIN user_groups ON ug_user=rev_user AND ug_group='bot' WHERE rev_page=$pageid AND ug_user IS NULL ORDER BY rev_id DESC LIMIT 1");
    $newestrev = mysql_fetch_row($newestres);

    $oldestauthor = $oldestrev[0];
    $oldesttimestamp = $oldestrev[1];
    $oldestrevid = $oldestrev[2];
    $newestauthor = $newestrev[0];
    $newesttimestamp = $newestrev[1];
    $newestrevid = $newestrev[2];

	echo "\t<tr>";
    echo "\t\t<td>" . wikilink(substr($pagetitle, $PAGE_PREFIX_LEN), 'Wikipedie:' . $pagetitle) . "</td>\n\t\t<td>" . oldrevlink(format_timestamp($oldesttimestamp), $oldestrevid) . "</td>\n\t\t<td>" . wikilink($oldestauthor, 'Wikipedista:' . $oldestauthor) . "</td>\n\t\t<td>" . difflink(format_timestamp($newesttimestamp), $newestrevid) . "</td>\n\t\t<td>" . wikilink($newestauthor, 'Wikipedista:' . $newestauthor) . "</td>\n\t</tr>\n";
	++$count;
}

    echo "\t</table>\n";

    echo '<p>Informace aktuální k ' . format_timestamp(get_last_edit_timestamp($db), false, true) . ' (UTC).</p>';

?>
</body>
</html>
