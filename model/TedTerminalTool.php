<?php
/**
 * @author Jan Papousek (jan.papousek@gmail.com)
 * @link https://github.com/papousek/ted-downloader
 */
class TedTerminalTool
{

	const HOST_OPT = 'h';
	const LANGUAGE_OPT =  'l';
	const FILE_OPT = 'f';
	const DESTINATION_OPT = 'd';
	const POSTFIX_OPT = 'p';
	const VIDEO_OPT = 'v';

	private $logger;

	public function __construct() {
		$this->logger = new TedTerminalLogger();
	}

	public function run() {
		$this->printHeader();
		try {
			$options = $this->loadOptions();
			$tool = new TedProcessor(
				new TedDownloader(),
				$this->logger,
				empty($options[self::DESTINATION_OPT]) ? NULL : $options[self::DESTINATION_OPT],
				empty($options[self::LANGUAGE_OPT]) ? NULL : $options[self::LANGUAGE_OPT],
				isset($options[self::POSTFIX_OPT]) ? TRUE : FALSE,
				isset($options[self::VIDEO_OPT]) ? TRUE : FALSE
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
			$this->logger->error($e->getMessage());
			self::terminate();
		}
	}

	public function terminate() {
		die;
	}

	// -------- PRIVATE METHODS

	private function loadOptions() {
		$options = getopt(self::HOST_OPT . ':' . self::LANGUAGE_OPT . ':' . self::FILE_OPT . ':' . self::DESTINATION_OPT . ':' . self::POSTFIX_OPT . self::VIDEO_OPT);
		// check the options
		if (empty($options[self::HOST_OPT]) && empty($options[self::FILE_OPT])) {
			throw new RuntimeException("Neither the url nor the file with urls are not specified.");
		}
		if (!empty($options[self::HOST_OPT]) && !empty($options[self::FILE_OPT])) {
			throw new RuntimeException("The url and the file cannot be specified together");
		}
		if (!empty($options[self::FILE_OPT]) && !file_exists($options[self::FILE_OPT])) {
			throw new RuntimeException("The file [".$options[self::FILE_OPT]."] with urls does not exist.");
		}
		return $options;
	}

	private function printHeader() {
		self::printLine();
		$this->logger->log("TED DOWLOADER", "", ITedLogger::IMPORTANCE_MEDIUM);
		$this->logger->log('', '', ITedLogger::IMPORTANCE_HIGH);
		$this->logger->log("ted-downloader", '', ITedLogger::IMPORTANCE_MEDIUM);
		$this->logger->log("[-".self::HOST_OPT." <url of the video on the TED server>]", '', ITedLogger::IMPORTANCE_LOW);
		$this->logger->log("[-".self::LANGUAGE_OPT." <language>]", '', ITedLogger::IMPORTANCE_LOW);
		$this->logger->log("[-".self::FILE_OPT." <file with url of the vide on the TED server per line>]", '', ITedLogger::IMPORTANCE_LOW);
		$this->logger->log("[-".self::DESTINATION_OPT." <directory where the subtitles will be saved>]", '', ITedLogger::IMPORTANCE_LOW);
		$this->logger->log("[-".self::POSTFIX_OPT." turn on the language postfix]", '', ITedLogger::IMPORTANCE_LOW);
		$this->logger->log("[-".self::VIDEO_OPT." turn on downloading video in high resolution]", '', ITedLogger::IMPORTANCE_LOW);
		self::printLine();
	}

	private function printLine() {
		$this->logger->log("--------------------------------------------------------------------------------", "", ITedLogger::IMPORTANCE_HIGH);
	}


}
