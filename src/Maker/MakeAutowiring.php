<?php

namespace InspiredMinds\ContaoMakerBundle\Maker;

use Composer\Json\JsonManipulator;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputAwareMakerInterface;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

final class MakeAutowiring extends AbstractMaker
{
    protected $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

	/**
     * Return the command name for your maker (e.g. make:report).
     *
     * @return string
     */
	public static function getCommandName(): string
	{
		return 'make:autowiring';
	}

	/**
     * Configure the command: set description, input arguments, options, etc.
     *
     * By default, all arguments will be asked interactively. If you want
     * to avoid that, use the $inputConfig->setArgumentAsNonInteractive() method.
     *
     * @param Command            $command
     * @param InputConfiguration $inputConfig
     */
	public function configureCommand(Command $command, InputConfiguration $inputConfig)
	{
		$command
            ->setDescription('Create default autowiring for root namespace')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeAutowiring.txt'))
        ;
	}

	/**
     * Configure any library dependencies that your maker requires.
     *
     * @param DependencyBuilder $dependencies
     */
	public function configureDependencies(DependencyBuilder $dependencies)
	{
	}

	/**
     * Called after normal code generation: allows you to do anything.
     *
     * @param InputInterface $input
     * @param ConsoleStyle   $io
     * @param Generator      $generator
     */
	public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
	{
        // create or manipulate services.yml
        $servicesPath = 'app/config/services.yml';

        if (!$this->fileManager->fileExists($servicesPath)) {
            $generator->generateFile(
                'app/config/services.yml',
                __DIR__.'/../Resources/skeleton/autowiring/services.tpl.yml',
                [
                    'root_namespace' => $generator->getRootNamespace()
                ]
            );
        }
        else {
            $services = Yaml::parse($this->fileManager->getFileContents($servicesPath));

            if (!isset($services['services'])) {
                $services['services'] = [];
            }

            if (!isset($services['services']['_defaults'])) {
                $services['services'] = array_merge(['_defaults' => []], $services['services']);
            }

            $services['services']['_defaults'] = array_merge([
                'autowire' => true,
                'autoconfigure' => true,
                'public' => false,
            ], $services['services']['_defaults']);

            if (!isset($services['services'][$generator->getRootNamespace() . '\\'])) {
                $services['services'][$generator->getRootNamespace() . '\\'] = [];
            }

            $services['services'][$generator->getRootNamespace() . '\\'] = array_merge([
                'resource' => '../../src/*',
                'exclude' => '../../src/{Entity,Tests,Kernel.php}',
            ], $services['services'][$generator->getRootNamespace() . '\\']);

            $generator->dumpFile($servicesPath, Yaml::dump($services, 4));
        }

        // create or manipulate config.yml
        $configPath = 'app/config/config.yml';

        $config = Yaml::parse($this->fileManager->fileExists($configPath) ? $this->fileManager->getFileContents($configPath) : '');

        if (!is_array($config)) {
            $config = [];
        }

        if (!isset($config['imports'])) {
            $config = array_merge(['imports' => []], $config);
        }

        $hasServices = false;

        foreach ($config['imports'] as $import) {
            if (isset($import['resource']) && 'services.yml' === $import['resource']) {
                $hasServices = true;
                break;
            }
        }

        if (!$hasServices) {
            $config['imports'][] = ['resource' => 'services.yml'];
        }

        $generator->dumpFile($configPath, Yaml::dump($config, 2));

        // create or manipulate composer.json
        $composer = json_decode($this->fileManager->getFileContents('composer.json'));
        $composer->autoload->{'psr-4'}->{$generator->getRootNamespace() . '\\'} = 'src/';
        $generator->dumpFile('composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // write changes
        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: run <fg=green>composer install</> to finalize the setup!',
        ]);
	}
}
