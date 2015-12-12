<?php
namespace Mia3\Koseki;

/**
 * partially stolen from https://github.com/webmozart/glob
 */
class Gitignore {

	/**
	 * @var array
	 */
	protected $patterns = array();

	/**
	 * @var string
	 */
	protected $compiledPattern;

	/**
	 * @param string $filename
	 */
	public function __construct($filename = NULL) {
		if ($filename !== NULL) {
			$this->addPatternFile($filename);
		}
	}

	/**
	 * @param string $filename
	 */
	public function matches($filename) {
		return preg_match($this->compiledPattern, $filename, $match) === 1;
	}

	/**
	 * @param string $filename file containing patterns like a .gitignore, .gitattributes or similar file
	 * @param string $prefix that gets prepended before any pattern of this file
	 */
	public function addPatternFile($filename, $prefix = NULL) {
		if (!file_exists($filename)) {
			return;
		}
		$this->addPatterns(file_get_contents($filename), $prefix);
	}

	/**
	 * @param string $content string containing one pattern per line
	 * @param string $prefix that gets prepended berfore any pattern
	 */
	public function addPatterns($content, $prefix = NULL) {
		$lines = explode(chr(10), $content);
		foreach ($lines as $line) {
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			if (substr($line, 0, 1) === '#') {
				continue;
			}
			$line = str_replace(' export-ignore', '', $line);
			$this->patterns[] = $this->createReqexFromGlob($prefix . $line);
		}

		$this->compiledPattern = '~^(?:' . implode(' | ', $this->patterns) . ')~x';
	}

	/**
	 * turn an glob expression into a regular expression
	 *
	 * @param string $glob
	 */
	public function createReqexFromGlob($glob) {
		if (false !== strpos($glob, '{')) {
			$glob = preg_replace_callback(
				'~\\{([^\\}]*)\\}~',
				function ($match) {
					return '(' . str_replace(',', '|', $match[1]) . ')';
				},
				$glob
			);
		}
		return str_replace(
			array('/**/', '*'),
			array('/(.+/)?', '[^/]*'),
			$glob
		);
	}
}
