<?php
/**
 * @author Jan Papousek (jan.papousek@gmail.com)
 * @link https://github.com/papousek/ted-downloader
 */
interface ITedLogger
{

	const IMPORTANCE_HIGH	= 0;

	const IMPORTANCE_MEDIUM = 1;

	const IMPORTANCE_LOW	= 2;

	
	function error($msg);

	function info($msg);

	function log($msg, $type, $importance = self::IMPORTANCE_HIGH);

	function user($msg);

}
