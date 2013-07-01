<?php

function getPoeetHaiku($page) {

	global $haiku_to_skip;

	$poeet = file_get_contents($page);

	// Keep backup of scraped page
	$f = fopen('last_poeet_content_' . $page_i++ . '.html', 'w');
	fwrite($f, $poeet);
	fclose($f);

	// some dodgy split function
	$poeet = preg_replace('/<\/div><div class="tweet">/', '±±±', $poeet);

	// Find all haiku among markup and put into $haiku_raw array
	if (preg_match('/<div class="tweet">([^<]+)<\/div>/', $poeet, $haiku_raw)) {

		$haiku_all = explode('±±±', $haiku_raw[1]);

		return $haiku_all;

	}

	return null;

}
