<?php

define('EXAIKSIS_OUTPUT_FILE', 'exaiksis.txt');

/**
 * Creates Barnoid-based mash-up from webapge.
**/
function generateExaiksis() {
	// Barnoid's Ex/Aik/Sis
	echo "Ex / Aik / Sis:\n\n";

	$exaiksis_url = 'http://subli.me.uk/cgi-bin/exaiksis';

	$result = file_get_contents($exaiksis_url);

	$result = preg_replace("/\n/", '', $result);
	$result = preg_replace('/^.*by exmosis<\/a><\/div>/', '', $result);
	$result = preg_replace('/^<div style="font-size: 30pt; margin-top: 50px;">/', '', trim($result));
	$result = preg_replace('/<.*$/', '', $result);

	$result = trim(preg_replace('/\s*\/\s*/', "  \r\n", $result));

	echo $result . "\n\n";
	$f = fopen(EXAIKSIS_OUTPUT_FILE, 'w');
	fwrite($f, '#Exaiksis' . "\n\n");
	fwrite($f, $result);
	fclose($f);

	return EXAIKSIS_OUTPUT_FILE;

}