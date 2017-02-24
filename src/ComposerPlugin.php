<?php
namespace Mia3\Koseki;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ComposerPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        // $installer = new TemplateInstaller($io, $composer);
        // $composer->getInstallationManager()->addInstaller($installer);
        var_dump('woot woot');
    }

    public static function getSubscribedEvents() {
	    return array(
	        'post-autoload-dump' => 'generateClassRegister',
	        // ^ event name ^         ^ method name ^
	    );
	}

	public function generateClassRegister() {
		var_dump('woot');
	}
}