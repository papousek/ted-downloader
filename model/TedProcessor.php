<?php
/**
 * @author Jan Papousek (jan.papousek@gmail.com)
 * @link https://github.com/papousek/ted-downloader
 */
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
}
