<?php
namespace Mia3\Koseki;

class ClassRegister {

	/**
	 * @var array
	 */
	protected static $cache;

	/**
	 * @var string
	 */
	protected static $filePointer;

	/**
	 * @var string
	 */
	protected static $rootDirectory;

	/**
	 * @var string
	 */
	protected static $ignoreFile;

	/**
	 * @var string
	 */
	protected static $ignore;

	/**
	 * find implementations for a specific interface or classes extending from base class
	 * @param string $interfaceName full class/interface name to look implementations up for
	 * @param boolean $forceRecache
	 */
	public static function getImplementations($interfaceName, $forceRecache = FALSE) {
		$vendorDir = realpath(__DIR__ . '/../../../');
		static::$rootDirectory = realpath(__DIR__ . '/../../../../') . '/';

		static::locateComposerPaths();

		if (static::$cache === NULL) {
			$md5 = static::generateCacheHash();
			$cacheFile = sys_get_temp_dir() . '/class_register_' . $md5 . '.php';

			if (file_exists($cacheFile) && $forceRecache === FALSE) {
				static::$cache = include($cacheFile);
			} else {
				static::$cache = static::generateCache($cacheFile);
			}
		}

		return isset(static::$cache[$interfaceName]) ? static::$cache[$interfaceName] : array();
	}

	/**
	 * generate a cache md5 hash based on all located composer autoload_psr4 files
	 */
	public static function generateCacheHash() {
		$hashes = array();
		foreach (static::locateComposerPaths() as $className => $composerPath) {
			if (file_exists($composerPath . '/autoload_psr4.php')) {
				$hashes[] = md5_file($composerPath . '/autoload_psr4.php');
			}
		}
		return md5(implode('', $hashes));
	}

	/**
	 * locate all "active" composer pathis in this php execution
	 */
	public static function locateComposerPaths() {
		$composerPaths = array();
		foreach (get_declared_classes() as $className) {
			if (!preg_match('/^ComposerAutoloader[A-z0-9]*$/', $className)) {
				continue;
			}
			$reflector = new \ReflectionClass($className);
			$composerPaths[$className] = dirname($reflector->getFileName());
		}
		return $composerPaths;
	}

	/**
	 * @param $cacheFile
	 */
	protected static function generateCache($cacheFile) {
		register_shutdown_function('\Mia3\Koseki\ClassRegister::catchFatalError');

		foreach (static::locateComposerPaths() as $className => $composerPath) {
			static::loadAllPsr4Classes($className::getLoader()->getPrefixesPsr4(), realpath($composerPath . '/../../'));
		}

		$cache = array();
		foreach (get_declared_classes() as $className) {
			foreach (class_implements($className) as $interface) {
				if (!isset($cache[$interface])) {
					$cache[$interface] = array();
				}
				$cache[$interface][] = $className;
			}
			foreach (class_parents($className) as $parentClassName) {
				if (!isset($cache[$parentClassName])) {
					$cache[$parentClassName] = array();
				}
				$cache[$parentClassName][] = $className;
			}
		}
		file_put_contents($cacheFile, '<?php
		return ' . var_export($cache, TRUE) . ';');

		return $cache;
	}

	/**
	 * loads all classes based on a comoser psr4Prefix array
	 * @param array $psr4Prefix
	 * @return void
	 */
	public static function loadAllPsr4Classes($psr4Prefixes, $rootDirectory) {
		$gitignore = new Gitignore();
		$gitignore->addPatternFile(static::$rootDirectory . '.koseki-ignore');
		foreach ($psr4Prefixes as $psr4Prefix => $classDirectories) {
			foreach ($classDirectories as $classDirectory) {
				$relativeClassDirectory = ltrim(str_replace($rootDirectory, '', $classDirectory) . '/', '/');
				$gitignore->addPatternFile($classDirectory . '/.koseki-ignore', $relativeClassDirectory);
				$gitignore->addPatternFile($classDirectory . '/.gitattributes', $relativeClassDirectory);
				$classFiles = new \RegexIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($classDirectory)), '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
				foreach ($classFiles as $classFile) {
					static::$filePointer = current($classFile);
					if ($gitignore->matches(str_replace(static::$rootDirectory, '', static::$filePointer)) === TRUE) {
						continue;
					}
					require_once(static::$filePointer);
				}
			}
		}
		static::$filePointer = NULL;
	}

	/**
	 * add a ignore entry if a fatal error occured
	 */
	public static function catchFatalError() {
		return;

		if (static::$filePointer !== NULL) {
			$ignore = '';
			$ignoreFile = static::$rootDirectory . '.koseki-ignore';
			if (file_exists(static::$ignoreFile)) {
				$ignore = file_get_contents(static::$ignoreFile);
			}
			$culpritFile = str_replace(static::$rootDirectory, '', static::$filePointer);
			$ignore.= chr(10) . $culpritFile;
			file_put_contents(static::$ignoreFile, trim($ignore));

			echo "\n\nKoseki/ClassRegister: \n\n The file \"" . $culpritFile . "\" failed to be included without fatal errors. \n it has been added to the .koseki-ignore file to be ignored from now on. Please just reload/reexecute this script to continue.\n\n";
		}
	}

}
