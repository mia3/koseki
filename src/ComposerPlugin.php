<?php
namespace Mia3\Koseki;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Go\ParserReflection\ReflectionClass;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Promise\Promise;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

use PhpParser\ParserFactory;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var Composer
     */
    protected $composer;
    /**
     * @var IOInterface
     */
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'pre-autoload-dump' => 'preAutoloadDumpEvent',
            'post-autoload-dump' => 'postAutoloadDumpEvent',
        );
    }

    public function preAutoloadDumpEvent()
    {
        $rootPackage = $this->composer->getPackage();
        $autoloadDefinition = $rootPackage->getAutoload();
        $autoloadDefinition['psr-4']['Mia3\\Koseki\\'] = 'vendor/mia3';
        $rootPackage->setAutoload($autoloadDefinition);
    }

    public function postAutoloadDumpEvent()
    {
        $this->io->writeError('<info>mia3/koseki: generating class inheritence registry</info>');
        $classRegister = $this->generateClassRegister();
        file_put_contents(__DIR__ . '/../../ClassRegister.php', '<?php
namespace Mia3\Koseki;

class ClassRegister {

	/**
	 * @var array
	 */
	protected static $classesRegister = ' . var_export($classRegister, true) . ';

	/**
	 * find implementations for a specific interface or classes extending from base class
	 * @param string $interfaceName full class/interface name to look implementations up for
	 */
	public static function getImplementations($interfaceName) {
		if (isset(static::$classesRegister[$interfaceName])) {
			return static::$classesRegister[$interfaceName];
		}
		return array();
	}

}');
    }

    /**
     * locate all "active" composer pathis in this php execution
     */
    public function locateComposerPaths()
    {
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
     * Generate a complete class register
     * @return void
     */
    public function generateClassRegister()
    {
        $classRegister = array();
        require_once(__DIR__ . '/../../../autoload.php');
        $files = array();
        foreach ($this->locateComposerPaths() as $autoloaderClassName => $composerPath) {
            if (substr($composerPath, 0, 5) == 'phar:') {
                continue;
            }
            foreach ($autoloaderClassName::getLoader()->getPrefixesPsr4() as $psr4Prefix => $classDirectories) {
                if ($psr4Prefix == 'Mia3\Koseki\\') {
                    continue;
                }
                foreach ($classDirectories as $classDirectory) {
                    if (!file_exists($classDirectory)) {
                        continue;
                    }
                    $classFiles = new \RegexIterator(
                        new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($classDirectory)
                        ),
                        '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH
                    );
                    foreach ($classFiles as $classFile) {
                        $files[] = current($classFile);
                    }
                }
            }
        }
        $progress = new ProgressBar(new ConsoleOutput(), count($files));
        $progress->start();
        $cacheDir = sys_get_temp_dir() . '/koseki/';
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $start = microtime(true);
        $parser = new ClassReflectionParser();
        $classRegister = [];
        foreach ($files as $file) {
            $progress->advance();
            #echo 'inspecting' . $file . chr(10);
            $classes = $parser->parse(file_get_contents($file));
            $classRegister = array_replace($classRegister, $classes);
        }
        $progress->finish();

        $this->io->writeError(chr(10) . '<info>mia3/koseki: generated registry in ' . number_format(microtime(true) - $start, 2). ' seconds</info>');

        return $classRegister;
    }
}