<?php

declare(strict_types=1);

/*
 * This file is part of the ContaoMakerBundle.
 *
 * (c) inspiredminds
 *
 * @license LGPL-3.0-or-later
 */

namespace InspiredMinds\ContaoMakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;

final class MakeContaoKickstart extends AbstractMaker
{
    protected $fileManager;
    protected $autoloaderUtil;

    public function __construct(FileManager $fileManager, AutoloaderUtil $autoloaderUtil)
    {
        $this->fileManager = $fileManager;
        $this->autoloaderUtil = $autoloaderUtil;
    }

    /**
     * Return the command name for your maker (e.g. make:report).
     */
    public static function getCommandName(): string
    {
        return 'make:contao-kickstart';
    }

    /**
     * Configure the command: set description, input arguments, options, etc.
     *
     * By default, all arguments will be asked interactively. If you want
     * to avoid that, use the $inputConfig->setArgumentAsNonInteractive() method.
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Kickstart local application development within a Contao Managed Edition')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeContaoKickstart.txt'))
        ;
    }

    /**
     * Configure any library dependencies that your maker requires.
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    /**
     * If necessary, you can use this method to interactively ask the user for input.
     */
    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('use-controllers', InputArgument::REQUIRED);
        $input->setArgument('use-controllers', $io->askQuestion(new ConfirmationQuestion('Do you need controllers?', false)));
    }

    /**
     * Called after normal code generation: allows you to do anything.
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        // get the root namespace
        $namespace = $generator->getRootNamespace();

        // manipulate composer.json
        $composer = json_decode($this->fileManager->getFileContents('composer.json'));
        $needsAutoloadDump = false;
        if (!isset($composer->autoload)) {
            $composer->autoload = new \stdclass();
            $needsAutoloadDump = true;
        }
        if (!isset($composer->autoload->{'psr-4'})) {
            $composer->autoload->{'psr-4'} = new \stdclass();
            $needsAutoloadDump = true;
        }
        if (!isset($composer->autoload->{'psr-4'}->{$namespace.'\\'})) {
            $composer->autoload->{'psr-4'}->{$namespace.'\\'} = new \stdclass();
            $composer->autoload->{'psr-4'}->{$namespace.'\\'} = 'src/';
            $needsAutoloadDump = true;
        }

        if ($needsAutoloadDump) {
            $generator->dumpFile('composer.json', json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $generator->writeChanges();

            $io->warning([
                'composer.json has been changed!',
                'Run composer dump-autoload, then execute make:contao-kickstart again.'
            ]);

            return;
        }

        // determine namespace path
        $path = $this->autoloaderUtil->getPathForFutureClass($namespace . '\\Test');
        
        if (null !== $path) {
            $path = $this->fileManager->relativizePath($path);
            $path = str_replace('Test.php', '', $path);
        }
        else {
            $io->error([
                'Could not determine namespace path.',
                'Did you run composer dump-autoload yet?'
            ]);
            
            return;
        }

        $absolutePath = $this->fileManager->absolutizePath($path);

        // create or manipulate services.yml
        $servicesPath = 'app/config/services.yml';

        if (!$this->fileManager->fileExists($servicesPath)) {
            $generator->generateFile(
                'app/config/services.yml',
                __DIR__.'/../Resources/skeleton/autowiring/services.tpl.yml',
                [
                    'root_namespace' => $namespace,
                ]
            );
        } else {
            $services = Yaml::parse($this->fileManager->getFileContents($servicesPath));

            if (!isset($services['services'])) {
                $services['services'] = [];
            }

            if (!isset($services['services']['_defaults'])) {
                $services['services'] = array_merge(['_defaults' => []], $services['services']);
            }

            $services['services']['_defaults'] = array_merge($services['services']['_defaults'], [
                'autowire' => true,
                'autoconfigure' => true,
                'public' => true,
            ]);

            if (!isset($services['services'][$namespace.'\\'])) {
                $services['services'][$namespace.'\\'] = [];
            }

            $services['services'][$namespace.'\\'] = array_merge($services['services'][$namespace.'\\'], [
                'resource' => '../../' . $path . '*',
                'exclude' => '../../' . $path . '{Entity,Tests,Kernel.php}',
            ]);

            $generator->dumpFile($servicesPath, Yaml::dump($services, 4));
        }

        // create or manipulate config.yml
        $configPath = 'app/config/config.yml';

        $config = Yaml::parse($this->fileManager->fileExists($configPath) ? $this->fileManager->getFileContents($configPath) : '');

        if (!\is_array($config)) {
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

        // create or manipulate routing.yml
        $useControllers = $input->getArgument('use-controllers');

        if ($useControllers) {
            $routingPath = 'app/config/routing.yml';

            $routing = Yaml::parse($this->fileManager->fileExists($routingPath) ? $this->fileManager->getFileContents($routingPath) : '');

            if (!\is_array($routing)) {
                $routing = [];
            }

            if (!isset($routing['controllers'])) {
                $routing['controllers'] = [];
            }

            $routing['controllers']['resource'] = '../../' . $path . 'Controller/';
            $routing['controllers']['type'] = 'annotation';

            // create folder for controller in namespace
            if (!file_exists($absolutePath . 'Controller/')) {
                mkdir($absolutePath . 'Controller/', 0777, true);
            }

            $generator->dumpFile($routingPath, Yaml::dump($routing, 4));
        }

        // write changes
        $generator->writeChanges();

        // create folder for namespace
        if (!file_exists($absolutePath)) {
            mkdir($absolutePath);
        }

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: run <fg=green>composer install</> to finalize the setup!',
        ]);

        if ($useControllers) {
            $io->text([
                'If you are using Contao 4.4 and you need to use controllers, use',
                'the <fg=green>make:contao-manager-plugin</> command to load the routing.yml.'
            ]);
        }
    }
}
