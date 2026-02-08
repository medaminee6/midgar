<?php
$s = file_get_contents(__DIR__ . '/../src/Controller/OeuvreController.php');
echo "count { = " . substr_count($s, '{') . PHP_EOL;
echo "count } = " . substr_count($s, '}') . PHP_EOL;
$lines = explode("\n", $s);
echo "Total lines = " . count($lines) . PHP_EOL;
for ($i=1;$i<=count($lines);$i++) {
    $line = $lines[$i-1];
    if (strpos($line, '{') !== false || strpos($line, '}') !== false) {
        echo str_pad($i,4,' ',STR_PAD_LEFT) . ' ' . $line . PHP_EOL;
    }
}
