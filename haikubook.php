<?php

/*
 * Script to scrape poeet webpages for haiku and turn into a Leanpub book.
 *
 * Graham Lally
 * butterfliesandsand@exmosis.net
 * Check the book out at http://leanpub.com/butterflies_and_sand
 * Twitter: http://twitter.com/6loss
 */

define('NO_OF_SECTIONS', 5);
define('PAGES_PER_SECTION', 3);
define('HAIKU_PER_PAGE', 3);

define('ENABLE_IMAGES', true);
define('ENABLE_EXAIKSIS', true);

define('MIN_SECTION_TITLE_LENGTH', 5);

// Change this to the manuscript directory for the Leanpub book in Dropbox
define('OUTPUT_DIR', '/Users/graham/Dropbox/butterflies_and_sand/manuscript/');

// Directory to take random images from
define('SRC_IMG_DIR', '/Users/graham/Pictures/Lightoom Exports/Butterflies and Sand/');
// Directory to store images in for Leanpub
define('LEANPUB_IMG_DIR', '/Users/graham/Dropbox/butterflies_and_sand/manuscript/images/');

require_once('_inc/fns__poeet.php');
require_once('_inc/fns__twitter_archive.php');
require_once('_inc/fns__exaiksis.php');

// list of chapters to insert before and after main content
$pre_chapters = array(
	'about_this_book.txt',
	'reading_this_book.txt'
);

$post_chapters = array(
	'on_syllables.txt',
	'thanks.txt'
);

// list of haiku to regexp match and skip if found
$haiku_to_skip = array(
	'Dusty tag chi shoes \/ a re-birth, the Great Tao makes \/ vacuum cleaner noise.',
	'A baby laughing \/ contains more reality \/ than Radio 4.',
	'{ dew like shadow lies \/ on the haven\'s waterfall \/ summer\'s strange disguise }',
	'Catching my pale breath \/ A red leaf swings on the air \/ Caught in a cobweb\.',
	'Last chance to catch the January haiku.*'
);

// Haiku to use as "last of the last" release - all haiku found after this will be listed in
// the "Latest Haiku" section
// $last_haiku = 'Big garden, big house / A violent argument / with broken voices.';
// $last_haiku = 'Sat in the window / a cooling cup of coffee / fills me with stories.';
// $last_haiku = 'Watching the leaves shake / The wind outside the window / far from a sickbed.';
// $last_haiku = 'Carcasses and skin / tumbling from the stock pot / without flavour.';
// $last_haiku = 'Looking for comets / stepping through fallen leaves / among old rain clouds.';
// $last_haiku = 'The hairs on my arm / among the rose garden plants / wanders a greenfly.';
$last_haiku = "After the showers / the hitchhikers' cardboard sign / in the recycling.";

// Pages to scrape content from
$haiku_pages = array(
	'http://poeet.com/e/x/exmosis.html',
	'http://poeet.com/6/l/6loss.html'
);

// source types can be:
// - poeet: scrape a poeet URL page
// - twitter_archive tweet archive
$haiku_sources = array (
	/*array(
		'type' => 'poeet',
		'location' => 'http://poeet.com/e/x/exmosis.html'
	),
	array(
		'type' => 'poeet',
		'location' => 'http://poeet.com/6/l/6loss.html'
	),
	*/
	array(
		'type' => 'twitter_archive_csv',
		'location' => '/Users/graham/Archive/Backups/tweets_2013-12-22/tweets.csv'
	),
);

// set up variables we're using
$haiku = array();
$recent_haiku = array();
$possible_titles = array();
$section_titles = array();

chdir(OUTPUT_DIR);

$page_i = 0;
$in_recent = false;

// Go through list of sources
$src_i = 1;
foreach ($haiku_sources as $source) {

	// Check type is set
	if (! isset($source['type'])) {
		echo "No source type found for source " . $src_i . ":\n";
		print_r($source);
		echo "Skipping.\n\n";
		continue;
	}

	// Final array to store this source's haiku in, ordered in forwards date order (ie. start at earliest)
	$source_haiku_by_date = array();

	switch($source['type']) {

		case 'poeet':
			// get page from poeet
			if (! isset($source['location'])) {
				echo "No location URL found for poeet, for source " . $src_i . " - skipping.\n\n";
				continue;
			}
			$source_haiku_by_date = getPoeetHaiku($source['location']);
			break;

		case 'twitter_archive_csv':
			// Read CSV file in downloaded Twitter archive
			if (! isset($source['location'])) {
				echo "No file location found for Twitter archive CSV, for source " . $src_i . " - skipping.\n\n";
				continue;
			}
			$source_haiku_by_date = getTwitterArchiveCsvHaiku($source['location']);
			break;
	}

	// Skip if we got nothing back
	if (! $source_haiku_by_date) {
		echo "No haiku found for source " . $src_i . ":\n";
		print_r($source);
		continue;
	}

	// Only add non-blank haiku to our list
	foreach ($source_haiku_by_date as $h) {
		if (! trim($h)) {
			$haiku[] = $h;
		}

		// Check which to skip
		$remove = false;
		foreach ($haiku_to_skip as $hs) {

			if (preg_match('/' . $hs . '/', $h)) {
				$remove = true;
			}

		}

		if ($remove) {
			echo "   Removed: " . $h . "\n";
		} else {
		
			// Add to our complete list
			$haiku[] = $h;

			// Are we hitting "recent haiku" yet?
			if ($in_recent) {
				$recent_haiku[] = $h;
			}

			// Check for "latest" haiku now
			if ($h == $last_haiku) {
				$in_recent = true;
			}

		}


	}

	$src_i++;

}


echo "COMPLETE HAIKU LIST:\n";
echo "====================\n\n";
print_r($haiku);

/** Finished with scraping content now - start randomerising everything **/

global $content_files, $sample_files;
$content_files = array();
$sample_files = array();


if ($haiku) {

	// set up random images
	$images = new ImageSet(SRC_IMG_DIR, LEANPUB_IMG_DIR);

	// get possible section titles
	foreach ($haiku as $h) {
		// check length
		if (trim($h)) {
			$h = preg_replace('/\//', ' ', $h);
			$h_words = explode(' ', $h);
			
			foreach ($h_words as $hw) {
				$hw = preg_replace('/[^a-zA-Z\']/', '', $hw);
				if (strlen(trim($hw)) >= MIN_SECTION_TITLE_LENGTH) {
					$possible_titles[] = ucwords(strtolower(trim($hw)));
				}
			}
		}
	}
	shuffle($possible_titles);

	// shuffle haiku
	shuffle($haiku);

	/** Start outputting files **/

	$content_files[] = 'frontmatter:';

	// Insert pre-content files
	foreach ($pre_chapters as $pc) {
		$content_files[] = $pc;
		$sample_files[] = $pc;
	}

	$content_files[] = 'mainmatter:';

	// $images->addRandomImage(true);

	// Put text files together - sections, pages, titles
	for ($section_i = 0; $section_i < NO_OF_SECTIONS; $section_i++) {
		// Check we have more titles than sections
		if (count($possible_titles) > $section_i) {
			$f = fopen($section_i . '_0_title.txt', 'w');
			fwrite($f, '#' . $possible_titles[$section_i] . "\n");
			if (ENABLE_IMAGES) {
				fwrite($f, $images->addRandomImage() . "\n");
			}
			fclose($f);
			$content_files[] = 'section' . $section_i . ':';
			$content_files[] = $section_i . '_0_title.txt';

			if ($section_i == 0) {
				$sample_files[] = $section_i . '_0_title.txt';
			}
		}

		// Updated for version 5 December 2012: Switch to starting with 1 haiku per page,
		// increasing haikus per page up tp HAIKU_PER_PAGE
		$no_of_haiku = 1;

		// get pages
		for ($page_i = 0; $page_i < PAGES_PER_SECTION; $page_i++) {

			$file = $section_i . '_' . ($page_i + 1) . '_page.txt';
			$f = fopen($file, 'w');
			fwrite($f, "\n{::pagebreak /}\n\n");

			for ($haiku_i = 0; $haiku_i < $no_of_haiku && $haiku_i < HAIKU_PER_PAGE; $haiku_i++) {
				// get next haiku, write to this file
				if ($haiku) {
					$h = '';
					while (! trim($h)) {
						$h = array_shift($haiku);
					}
					$h = preg_replace('/\s*\/\s*/', "  \r\n", $h);
					fwrite($f, $h . "\n\n");	
				}
			}
			fclose($f);
			$content_files[] = $file;

			if ($section_i == 0) {
				$sample_files[] = $file;
			}

			$no_of_haiku++;
		}

		// Add section break image
		$sample_img = ($section_i == 0) ? true : false;
		// $images->addRandomImage($sample_img);

	}

	// Output recent haiku
	if (count($recent_haiku) > 0) {

		$rh_page = 1;
		$rh_count = 0;

		$f = fopen('recent_haiku_' . $rh_page . '.txt', 'w');
		$content_files[] = 'recent_haiku_' . $rh_page . '.txt';
		fwrite($f, '#Recent memories' . "\n\n");
		foreach ($recent_haiku as $rh) {
			$h = preg_replace('/\s*\/\s*/', "  \r\n", $rh);

			$rh_count++;
			if ($rh_count == 4) {
				// Open new page after 3 haiku
				fwrite($f, "\n{::pagebreak /}\n\n");
				$rh_count = 1;
				$rh_page++;
				fclose($f);
				$f = fopen('recent_haiku_' . $rh_page . '.txt', 'w');
				$content_files[] = 'recent_haiku_' . $rh_page . '.txt';
			}

                        fwrite($f, $h . "\n\n");

		}
		fclose($f);
	}

	if (ENABLE_IMAGES) {
		$images->addRandomImage();
	}

	// Markov text
	echo "Markov:\n\n";
	
	$markov_url = 'http://projects.haykranen.nl/markov/demo/'; // 'http://www.beetleinabox.com/cgi-bin/mkv_short1.cgi';

// echo "--\n" . implode("\n", $haiku) . "\n--\n";


	$postdata = http_build_query(
	    array(
	        // 'user_text' => implode("\n", $haiku),
	        'input' => implode("\n", $haiku),
	        // 'maxwords' => 100,
		'length' => 500,
		'order' => 5,
		// 'submit' => 'Markov-ize!'
		'submit' => 'GO'
	    )
	);

	$opts = array('http' => array(
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata
	));

	$context  = stream_context_create($opts);
	$result = file_get_contents($markov_url, false, $context);

	$result = preg_replace("/\n/", "!!!", $result);
	$result = preg_replace('/^.*<h2>Output text<\/h2>/', '', $result);
	$result = preg_replace('/^!!![^a-zA-Z0-9]*<textarea [^>]*>/', '', trim($result));
	$result = preg_replace('/<\/textarea>.*$/', '', $result);
	$result = preg_replace('/!!!/', "  \r\n", trim($result));

	// remove start and end "words"
	// $result = preg_replace('/^[a-zA-Z0-9\'\-]*\s*\/?\s*/', '', $result);
	if (! preg_match('/^[a-zA-Z0-9]/', $result)) {
		$result = preg_replace('/^[a-zA-Z0-9]*([^a-zA-Z0-9]+)/', '$1', $result);
	}
	if (! preg_match('/\.$/', $result)) {
		$result = preg_replace('/\.[^\.]*$/', '', $result);
	}

	$result = trim($result);

	echo $result . "\n\n";

	$f = fopen('markov.txt', 'w');
	fwrite($f, '#Markov Mashup' . "\n\n");
	fwrite($f, $result);
	fclose($f);

	$content_files[] = 'markov.txt';

	if (ENABLE_EXAIKSIS) {
		$exaiksis_file = generateExaiksis();

		if ($exaiksis_file) {
			$content_files[] = $exaiksis_file;
		}
	}


	// Insert post-content files
	foreach ($post_chapters as $pc) {
		$content_files[] = $pc;
	}

	// Final image
	if (ENABLE_IMAGES) {
		$images->addRandomImage();
	}

	// Now write out content list
	$f = fopen('Book.txt', 'w');
	fwrite($f, implode("\n", $content_files));
	fclose($f);

	// Output sample book (1st section)
	$f = fopen('Sample.txt', 'w');
	fwrite($f, implode("\n", $sample_files));
	fclose($f);

}

class ImageSet {

	var $all_images = array();
	var $used_images = array();
	var $current_img_i = 0;

	var $src_dir = null;
	var $target_dir = null;

	function ImageSet($src_img_dir, $target_img_dir) {

		$this->src_dir = $src_img_dir;
		$this->target_dir = $target_img_dir;

		if ($handle = opendir($src_img_dir)) {
			echo "Getting images from $src_img_dir\n";
			while (false !== ($entry = readdir($handle))) {
				if (is_file($this->src_dir . $entry)) {
					$this->all_images[] = $entry;
				}
			}
			closedir($handle);
		}

		shuffle($this->all_images);
	}
	
	function addRandomImage($separate_page = false, $add_to_sample = false) {

		global $content_files, $sample_files;

		// get next image
		if ($this->all_images) {

			$next_image = array_shift($this->all_images);

			// work out format
			$ext = 'jpg';
			if (preg_match('/jpe?g$/', strtolower($next_image))) {
			} else if (preg_match('/png$/', strtolower($next_image))) {
				$ext = 'png';
			}

			if (copy($this->src_dir . $next_image, $this->target_dir . 'content_image_' . $this->current_img_i . '.' . $ext)) {

				$img_ref = 'images/content_image_' . $this->current_img_i . '.' . $ext;
				$img_markdown = '![](' . $img_ref . ')';
			
				if ($separate_page) {	
					$f = fopen('content_image_' . $this->current_img_i . '.txt', 'w');
					fwrite($f, $img_markdown);
					// fwrite($f, '![](images/content_image_' . $this->current_img_i . '.' . $ext . ")");
					fwrite($f, "\n{::pagebreak /}\n\n");
					fclose($f);

					$content_files[] = 'content_image_' . $this->current_img_i . '.txt';

					if ($add_to_sample) {
						$sample_files[] = 'content_image_' . $this->current_img_i . '.txt';
					}

					$return = '';
				} else {
					$return = $img_markdown;
				}

				$this->current_img_i++;

			}

		}

		return $return;

	}
}

class clsMarkov {
	var $wordList= array();
	var $termTree = array();

	function makeList($string) {
		$string = strtolower($string);
		$string =  preg_replace("/[^A-z0-9\/\.\-\s]/i", "", $string);
	 	preg_match_all("/[A-z0-9]+\S/", $string, $op);
	 	$this->wordList = $op[0];
	 	return $this->wordList;
	}

	function buildTree() {
		// $searchList = $this->wordList;
		$arraySize = count($this->wordList);
		$ns = 0;
		while ($ns!=$arraySize) {
			$termRoot = current($this->wordList);
			$termKeys = array_keys($this->wordList,$termRoot);
			foreach ($termKeys as $key=>$num) {
				$this->termTree[$termRoot][] = $this->wordList[($num+1)];
			}
			$this->termTree[$termRoot] = array_unique($this->termTree[$termRoot]);
			next($this->wordList);
			$ns++;
		}

	}

	function phraseWriter($seed, $words) {
		$results = $seed = strtolower($seed);
		if($this->termTree[$seed]) {
		$n=0;
		while($nn!=$this->termTree[$seed]){
			if($this->termTree[$seed][$rndseed]) {
				$results .= ' '.$this->termTree[$seed][$rndseed];
				$seed = $this->termTree[$seed][$rndseed];
				$nn++;
			}
			else $nn++;
		}
		return $results;
		} else return 'No seed match';
	}
}

?>
