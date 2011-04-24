<?php
class TedProcessor
{

	/**
	 * @var TedDownloader
	 */
	private $downloader;

	private $destDir;

	private $lang;

	/**
	 * @var ILogger
	 */
	private $logger;

	private $postfix;

	private $video;

	public function __construct(TedDownloader $downloader, ITedLogger $logger, $destDir, $lang, $postfix, $video) {
		if (empty($downloader)) {
			throw new InvalidArgumentException("The parameter [downloader] is empty.");
		}
		$this->downloader = $downloader;
		$this->destDir = empty($destDir) ? "" : (rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
		$this->lang = $lang;
		$this->postfix = $postfix;
		$this->logger = $logger;
		$this->video = $video;

	}

	public function processFile($filename) {
		if (empty($filename)) {
			$this->logger->error("The given filename is empty.");
		}
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
					$this->logger->user(trim($splittedLine[1]));
				}
				$this->processUrl(trim($splittedLine[0]));
			}
			catch(Exception $e) {
				$this->logger->error($e->getMessage());
			}
			$this->logger->log('', '', 0);
		}
	}

	public function processUrl($url) {
		if (empty($url)) {
			$this->logger->error("The given url is empty.");
		}
		$this->logger->info("Processing the url [$url].");
		try {
			$params = TedParams::loadParams($url);
			// downloads
			$filename = $this->destDir . $params->getValue(TedParams::VIDEO_ID) . "_" . $params->getCanonicalName();

			// get available languages
			if (empty($this->lang)) {
				$this->logger->info("Available languages: " . implode($params->getValue(TedParams::LANGUAGES), ", "));
			}
			else {
				// get subtitles
				try {
					$srtFile = $filename . (empty($this->postfix) ? "" : "[" . $this->lang . "]") . ".srt";
					$this->logger->info("Downloading subtitles in language [$this->lang].");
					$this->logger->info("Saving subtitles into the file [$srtFile]");
					$this->downloader->saveSubtitles($params, $this->lang, $srtFile);
				}
				catch(Exception $e) {
					$this->logger->error($e->getMessage());
				}
			}
			// get video
			if (!empty($this->video)) {
				$videoFile = $filename . ".mp4";
				$this->logger->info("Downloading video in high resolution.");
				$this->downloader->saveVideo($params, $videoFile);
				$this->logger->info("Vide saved into the file [$videoFile].");
			}
		}
		catch(Exception $e) {
			$this->logger->error($e->getMessage());
		}
	}

	private static function loadOptions() {
		$options = getopt(self::HOST_OPT . ':' . self::LANGUAGE_OPT . ':' . self::FILE_OPT . ':' . self::DESTINATION_OPT . ':' . self::POSTFIX_OPT . self::VIDEO_OPT);
		// check the options
		if (empty($options[self::HOST_OPT]) && empty($options[self::FILE_OPT])) {
			throw RuntimeException("Neither the url nor the file with urls are not specified.");
		}
		if (!empty($options[self::HOST_OPT]) && !empty($options[self::FILE_OPT])) {
			throw RuntimeException("The url and the file cannot be specified together");
		}
		if (!empty($options[self::FILE_OPT]) && !file_exists($options[self::FILE_OPT])) {
			throw RuntimeException("The file [".$options[self::FILE_OPT]."] with urls does not exist.");
		}
	}

	private static function printHeader(TedLogger $logger) {
		self::printLine($logger);
		$logger->log("TED DOWLOADER", "", 1);
		$logger->log('', '', 0);
		$logger->log("subtitles", '', 1);
		$logger->log("[-".self::HOST_OPT." <url of the video on the TED server>]", '', 2);
		$logger->log("[-".self::LANGUAGE_OPT." <language>]", '', 2);
		$logger->log("[-".self::FILE_OPT." <file with url of the vide on the TED server per line>]", '', 2);
		$logger->log("[-".self::DESTINATION_OPT." <directory where the subtitles will be saved>]", '', 2);
		$logger->log("[-".self::POSTFIX_OPT." turn on the language postfix]", '', 2);
		$logger->log("[-".self::VIDEO_OPT." turn on downloading video in high resolution]", '', 2);
		self::printLine($logger);
	}

	private static function printLine(TedLogger $logger) {
		$logger->log("--------------------------------------------------------------------------------", "", 0);
	}

	public static function run() {
		$logger = new TedLogger();
		self::printHeader($logger);
		try {
			$options = self::loadOptions();
			$tool = new TedProcessor(
				new TedDownloader(),
				$logger,
				empty($options[self::DESTINATION_OPT]) ? NULL : $options[self::DESTINATION_OPT],
				empty($options[self::LANGUAGE_OPT]) ? NULL : $options[self::LANGUAGE_OPT],
				empty($options[self::POSTFIX_OPT]) ? NULL : $options[self::POSTFIX_OPT],
				empty($options[self::VIDEO_OPT]) ? NULL : $options[self::VIDEO_OPT]
			);
			// process the given URL
			if (!empty($options[self::HOST_OPT])) {
				$tool->processUrl($options[self::HOST_OPT]);
			}
			// process the given file
			else {
				$tool->processFile($options[self::FILE_OPT]);
			}

		}
		catch(Exception $e) {
			$logger->error($e->getMessage());
			self::terminate();
		}
	}

	public static function terminate() {
		die;
	}
}
