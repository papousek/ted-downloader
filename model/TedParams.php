<?php
/**
 * @author Jan Papousek (jan.papousek@gmail.com)
 */
class TedParams
{

	const AD_DURATION		= 'adDuration';

	const INTRO_DURATION	= 'introDuration';

	const LANGUAGES			= 'languages';

	const LANGUAGE_CODE		= 'languageCode';

	const POST_AD_DURATION	= 'postAdDuration';

	const VIDEO_ID			= 'ti';

	private $languages;

	private $params;

	private static $paramsByUrl = array();

	private function __construct($url) {
		// setup
		$ch = curl_init($url);
		// The data should be returned (not printed)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		// get output
		$output = curl_exec($ch);
		// close channel
		curl_close($ch);

		// parse output
		preg_match("/flashVars = {\n([^}]+)}/", $output, $matches);
		if (count($matches) < 1) {
			throw new RuntimeException("An error has occured during connecting TED server [$url].");
		}
		$paramsString = strtr(
			$matches[0],
			array(
				"flashVars = "	=> '',
				"\t"			=> '',
				"\n"			=> '',
				"{"				=> '',
				"}"				=> '',
				"\""			=> ''
			)
		);
		$paramsLines = preg_split('/,/', $paramsString);
		$this->params = array();
		foreach($paramsLines AS $line) {
			$splittedLine = preg_split('/:/', $line);
			if (count($splittedLine) != 2) continue;
			$this->params[$splittedLine[0]] = $splittedLine[1];
		}
	}

	/**
	 * It returns a value of the parameter specified by the given name
	 * or NULL if the parameter does not exist.
	 */
	public function getValue($key) {
		if (empty($key)) {
			throw new InvalidArgumentException("The given argument [url] is empty.");
		}
		if ($key == 'languages') {
			return $this->getLanguages();
		}
		return isset($this->params[$key]) ? $this->params[$key] : NULL;
	}

	/**
	 * It loads new TED parameters from TED server
	 *
	 * @return TedParams
	 */
	public static function loadParams($url) {
		if (empty($url)) {
			throw new InvalidArgumentException("The given argument [url] is empty.");
		}
		if (!isset(self::$paramsByUrl[$url])) {
			self::$paramsByUrl[$url] = new TedParams($url);
		}
		return self::$paramsByUrl[$url];
	}

	/**
	 * It returns languages of the available subtitles of the video specified
	 * by the given parameters
	 */
	private function getLanguages() {
		if (!isset($this->languages)) {
			if (empty($this->params['languages'])) {
				$this->languages = array();
			}
			preg_match_all("/%22([^A-Z]+)%22/", $this->params['languages'], $matches);
			$this->languages = $matches[1];
		}
		return $this->languages;
	}

}
