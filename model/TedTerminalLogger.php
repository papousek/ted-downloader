<?php
class TedTerminalLogger implements ITedLogger
{
	const END_OF_LINE = "\r\n";

	public function error($msg) {
		$this->log($msg, "[error]\t", ITedLogger::IMPORTANCE_MEDIUM);
	}

	public function info($msg) {
		$this->log($msg, "[info]\t", ITedLogger::IMPORTANCE_MEDIUM);
	}

	public function log($msg, $type, $importance = ITedLogger::IMPORTANCE_HIGH) {
		$left = "";
		for($i=0; $i<$importance; $i++) {
			$left.= "\t";
		}
		echo $left . $type .$msg . self::END_OF_LINE;
	}

	public function user($msg) {
		$this->log($msg, "[user]\t", ITedLogger::IMPORTANCE_MEDIUM);
	}
}
