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
use Symfony\Bundle\MakerBundle\Generator;
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
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Create a Contao Manager Plugin class')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeContaoManagerPlugin.txt'))
        ;
    }

    /**
     * Configure any library dependencies that your maker requires.
     */
    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    /**
     * Called after normal code generation: allows you to do anything.
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $bundleName = preg_replace('/Bundle$/', '', $generator->getRootNamespace()).'Bundle';

        $targetPath = $generator->generateClass(
            $generator->getRootNamespace().'\ContaoManager\Plugin',
            __DIR__.'/../Resources/skeleton/contao-manager-plugin/ContaoManagerPlugin.tpl.php',
            [
                'full_bundle_name' => $generator->getRootNamespace().'\\'.$bundleName,
                'bundle_name' => $bundleName,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: adjust <fg=green>'.$targetPath.'</> to your needs',
        ]);
    }
}
