<?php

/* 
 * Function to take a CSV file as exported and downloaded from Twitter, find haiku in it,
 * apply same transformations that poeet does, and return a list in chronological order.
 * Caution: Only matches "#haiku" in tweet, whereas poeet matched other tags too.
 **/
function getTwitterArchiveCsvHaiku($file) {

	$haiku = array();

	if (! file_exists($file)) {
		echo "Can't find file '" . $file . "' - skipping.\n\n";
		return null;
	}

	if (($handle = fopen($file, "r")) !== FALSE) {
    	while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        	if (count($data) >= 8) {

        		$h = $data[5]; // column F, updated as of 2013-09-25

        		if (preg_match('/#haiku/', $h)) {

        			// apply poeet-like transforms to tweet
        			$h = poeeterise($h);
        			if ($h) {
        				$haiku[] = $h;
        			}

        		}
        	} else {
                echo "No data returned from Twitter CSV file? - skipping.\n\n";
            }
        }

        // Reverse haiku
        $haiku = array_reverse($haiku);

    } else {
    	// Couldn't open file
    	echo "Can't open file '" . $file . "' - skipping.\n\n";
		return null;
    }

    return $haiku;

}

// Performs a series of search and replace regexps as poeet did/does, to make tweets consistent 
function poeeterise($tweet) {

	// Checks from poeet scraper
	if (preg_match("/^(\@|RT)/", $tweet) ||			// skip directed tweets and RTs
		preg_match("/(\@|http:\/\/)/", $tweet) 	// skip tweets with @ or haiku in
	    // preg_match("/[\/\~].*[\/\~]/", $tweet)		// ?
	) {
		return null;
	}

    $tweet = preg_replace("/[\/\~]+/", ' / ', $tweet);;
    $tweet = preg_replace("/^[\s\/]+/", '', $tweet);
    $tweet = preg_replace("/(\/.*?\/.*?)\/.*/", "$1", $tweet);
    $tweet = preg_replace("/^.*\#haiku:\s*(.*)$/", "$1", $tweet);
    $tweet = preg_replace("/^\#haiku /", '', $tweet);
    $tweet = preg_replace("/^\#haikutherapy /", '', $tweet);
    $tweet = preg_replace("/\s*\-{3}.*$/", '', $tweet);
    $tweet = preg_replace("/\#.*/", '', $tweet);
    $tweet = preg_replace("/\s+/", ' ', $tweet);
    $tweet = preg_replace("/\s+$/", '', $tweet);
    $tweet = preg_replace("/^\s+/", '', $tweet);

    // Extras
    $tweet = preg_replace("/^[\.:]\s*/", '', $tweet);
    $tweet = preg_replace("/\s*\/+$/", '', $tweet);

    $parts = explode("/", $tweet);

    if (count($parts) < 3) {
    	return null;
    }

    // Could/should add in more checking here
    return $tweet;
}