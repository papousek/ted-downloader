<?php
/**
 * Inspired by the http://estebanordano.com/ted-talks-download-subtitles/
 *
 * @author Jan Papousek (jan.papousek@gmail.com)
 */
class TedDownloader
{

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
			$subtitles .= sprintf("%d\n", $counter);
			$subtitles .= sprintf("%s --> %s\n", $this->getFormattedTime($timeIntro + $line->startTime), $this->getFormattedTime($timeIntro + $line->duration));
			$subtitles .= sprintf("%s\n\n", $line->content);
		}
		return $subtitles;
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
