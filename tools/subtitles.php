<?php
require_once dirname(__FILE__) . '/../model/TedParams.php';
require_once dirname(__FILE__) . '/../model/TedDownloader.php';

define(VIDEO_OPT, 'h');
define(LANGUAGE_OPT, 'l');
define(FILE_OPT, 'f');
define(DESTINATION_OPT, 'd');
define(POSTFIX_OPT, 'p');

function printLine() {
	echo "--------------------------------------------------------------------------------\n";
}

function message($msg, $tabs = 1, $listype = "* ") {
	$left = "";
	for($i=0; $i<$tabs; $i++) {
		$left.= "\t";
	}
	echo $left . $listype .$msg . "\n";
}

function finish() {
	echo "\n";
	die;
}

function softError($msg) {
	message($msg, 1, "[error]\t");
}

function error($msg)  {
	softError($msg);
	finish();
}

function info($msg) {
	message($msg, 1, "[info]\t");
}

function user($msg) {
	message($msg, 1, "[user]\t");
}

function processUrl($url, array $options, $lang = NULL) {
	info("Processing the url [$url].");
	$params = TedParams::loadParams($url);
	if (empty($lang)) {
		info("Available languages: " . implode($params->getValue(TedParams::LANGUAGE_OPTS), ", "));
	}
	else {
		info("Downloading subtitles in language [$lang].");
		$downloader = new TedDownloader();
		$subtitles = $downloader->getSubtitles($params, $lang);
		$filename = $params->getValue(TedParams::VIDEO_ID) . (isset($options[POSTFIX_OPT]) ? "_" . $lang : ''). ".srt";
		if (!empty($options[DESTINATION_OPT])) {
			$filename = rtrim($options[DESTINATION_OPT], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
		}
		info("Saving subtitles into the file [$filename]");
		$file = fopen($filename, "w");
		fwrite($file, $subtitles);
		fclose($file);
	}
}

printLine();
message("TED DOWLOADER", 1, "");
message('', 0, '');
message("php subtitles.php", 1, '');
message("[-".VIDEO_OPT." <url of the video on the TED server>]", 2, '');
message("[-".LANGUAGE_OPT." <language>]", 2, '');
message("[-".FILE_OPT." <file with url of the vide on the TED server per line>]", 2, '');
message("[-".DESTINATION_OPT." <directory where the subtitles will be saved>]", 2, '');
message("[-".POSTFIX_OPT." turn on the language postfix]", 2, '');
printLine();

$options = getopt(VIDEO_OPT . ':' . LANGUAGE_OPT . ':' . FILE_OPT . ':' . DESTINATION_OPT . ':' . POSTFIX_OPT);

// check params
if (empty($options[VIDEO_OPT]) && empty($options[FILE_OPT])) {
	error("Neither the url nor the file with urls are not specified.");
}
if (!empty($options[VIDEO_OPT]) && !empty($options[FILE_OPT])) {
	error("The url and the file cannot be specified together");
}
if (!empty($options[FILE_OPT]) && !file_exists($options[FILE_OPT])) {
	error("The file [".$options[FILE_OPT]."] with urls does not exist.");
}

//
if (!empty($options[VIDEO_OPT])) {
	try {
		processUrl($options[VIDEO_OPT], $options, empty($options[LANGUAGE_OPT]) ? NULL : $options[LANGUAGE_OPT]);
	}
	catch(Exception $e) {
		softError($e->getMessage());
	}
}
else {
	$filename = $options[FILE_OPT];
	$file = fopen($filename, 'r');
	$content = fread($file, filesize($filename));
	$content = split("\n", $content);
	$counter = 0;
	foreach($content AS $line) {
		if (empty($line)) {
			continue;
		}
		$counter++;
		$splittedLine = split("#", $line);
		try {
			if (count($splittedLine) < 1) {
				throw RuntimeException("The line number $counter in file [$filename] has wrong format.");
			}
			if (count($splittedLine) >= 2) {
				user(trim($splittedLine[1]));
			}
			processUrl(trim($splittedLine[0]), $options, empty($options[LANGUAGE_OPT]) ? NULL : $options[LANGUAGE_OPT]);
		}
		catch(Exception $e) {
			softError($e->getMessage());
		}
		message('', 0, '');
	}
}

finish();