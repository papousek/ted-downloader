<?php
/**
 * Inspired by the http://estebanordano.com/ted-talks-download-subtitles/
 *
 * @author Jan Papousek (jan.papousek@gmail.com)
 */
class TedDownloader
{

	const END_OF_LINE = "\r\n";

	/**
	 * It returns the subtitles in the given language and for the video specified
	 * by the given parameters.
	 *
	 * Subtitles are string in the SRT format
	 *
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	public function getSubtitles(TedParams $params, $lang) {
		if ($params->getValue(TedParams::VIDEO_ID) == NULL) {
			throw new InvalidArgumentException("There is no TED video ID in the video params.");
		}
		if ($params->getValue(TedParams::INTRO_DURATION) == NULL) {
			throw new InvalidArgumentException("The length of intro part is not defined.");
		}
		if (!in_array($lang, $params->getValue(TedParams::LANGUAGES))) {
			throw new InvalidArgumentException("The language [$lang] is not in the list available languages [" . implode(', ', $params->getValue(TedParams::LANGUAGES)) . "]");
		}
		$url = sprintf("http://www.ted.com/talks/subtitles/id/%s/lang/%s", $params->getValue(TedParams::VIDEO_ID), $lang);
		$output = $this->getUrlContent($url);
		$json = json_decode($output);
		if (empty($json) || empty($json->captions)) {
			throw new RuntimeException("An error has occured during parsing the subtitles.");
		}
		$subtitles = "";
		$timeIntro = $params->getValue(TedParams::INTRO_DURATION);
		$counter = 0;
		foreach($json->captions AS $line) {
			$counter++;
			$subtitles .= sprintf("%d" . self::END_OF_LINE, $counter);
			$subtitles .= sprintf("%s --> %s" . self::END_OF_LINE, $this->getFormattedTime($timeIntro + $line->startTime), $this->getFormattedTime($timeIntro + $line->startTime + $line->duration));
			$subtitles .= sprintf("%s" . self::END_OF_LINE . self::END_OF_LINE, $line->content);
		}
		return $subtitles;
	}

	/**
	 * It saves the subtitles into the given file
	 *
	 * @param TedParams $params TED video parameters
	 * @param string	$lang	language
	 * @param string	$destination filename
	 * @throws RuntimeException if there is a problem to save the subtitles
	 */
	public function saveSubtitles(TedParams $params, $lang, $destination) {
		if (empty($destination)) {
			throw new InvalidArgumentException("The parameter [destination] is empty.");
		}
		if (empty($lang)) {
			throw new InvalidArgumentException("The parameter [lang] is empty.");
		}
		$subtitles = $this->getSubtitles($params, $lang);
		$file = fopen($destination, "w");
		fwrite($file, $subtitles);
		fclose($file);
	}

	/**
	 * It saves the video into the given file
	 *
	 * @param TedParams $params TED video parameters
	 * @param string	$destination filename
	 */
	public function saveVideo(TedParams $params, $destination) {
		if (empty($destination)) {
			throw new InvalidArgumentException("The parameter [destination] is empty.");
		}
		file_put_contents($destination ,file_get_contents($params->getHighResUrl()));
	}

	private function getFormattedTime($intValue) {
		if ($intValue < 0) {
			throw new InvalidArgumentException("The time has to be a positive number.");
		}
		$mils = $intValue % 1000;
		$secs = ((int) ($intValue / 1000)) % 60;
		$mins = ((int) ($intValue / 60000)) % 60;
		$hors = (int) ($intValue / 3600000);
		return sprintf("%02d:%02d:%02d,%03d", $hors, $mins, $secs, $mils);
	}

	private function getUrlContent($url) {
		// setup
		$ch = curl_init($url);
		// The data should be returned (not printed)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		// get output
		$output = curl_exec($ch);
		// close channel
		curl_close($ch);
		return $output;
	}

}
