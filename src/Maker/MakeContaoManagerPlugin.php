<?php

namespace InspiredMinds\ContaoMakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputAwareMakerInterface;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

final class MakeContaoManagerPlugin extends AbstractMaker
{
	/**
     * Return the command name for your maker (e.g. make:report).
     *
     * @return string
     */
	public static function getCommandName(): string
	{
		return 'make:contao-manager-plugin';
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
            ->setDescription('Create a Contao Manager Plugin class')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeContaoManagerPlugin.txt'))
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
        $bundleName = preg_replace('/Bundle$/', '', $generator->getRootNamespace()) . 'Bundle';

        $targetPath = $generator->generateClass(
            $generator->getRootNamespace() . '\ContaoManager\Plugin',
            __DIR__.'/../Resources/skeleton/contao-manager-plugin/ContaoManagerPlugin.tpl.php',
            [
                'full_bundle_name' => $generator->getRootNamespace() . '\\' . $bundleName,
                'bundle_name' => $bundleName,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: adjust <fg=green>' . $targetPath . '</> to your needs',
        ]);
	}
}
