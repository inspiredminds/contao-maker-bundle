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
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class MakeContaoManagerPlugin extends AbstractMaker
{
    /**
     * Return the command name for your maker (e.g. make:report).
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
     * If necessary, you can use this method to interactively ask the user for input.
     */
    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($io->askQuestion(new ConfirmationQuestion('Do you want to load a bundle?', true))) {
            $command->addArgument('load-bundle', InputArgument::REQUIRED);
            $input->setArgument(
                'load-bundle',
                $io->ask(
                    'Choose a bundle class to be loaded in the plugin (e.g. <fg=yellow>App\\AppBundle</>)',
                    'App\\AppBundle',
                    [Validator::class, 'validateClassName']
                )
            );
        }

        $command->addArgument('load-routes', InputArgument::REQUIRED);
        $input->setArgument('load-routes', $io->askQuestion(new ConfirmationQuestion('Do you want to load routes?', true)));
    }

    /**
     * Called after normal code generation: allows you to do anything.
     */
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        if ('App' !== $generator->getRootNamespace()) {
            throw new RuntimeCommandException('The contao/manager-plugin only supports an App\\ContaoManager\\Plugin class.');
        }

        $bundleName = preg_replace('/Bundle$/', '', $generator->getRootNamespace()).'Bundle';

        $targetPath = $generator->generateClass(
            $generator->getRootNamespace().'\ContaoManager\Plugin',
            __DIR__.'/../Resources/skeleton/contao-manager-plugin/ContaoManagerPlugin.tpl.php',
            [
                'load_bundle' => $input->hasArgument('load-bundle') ? $input->getArgument('load-bundle') : false,
                'load_routes' => $input->hasArgument('load-routes') ? $input->getArgument('load-routes') : false,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: adjust <fg=green>'.$targetPath.'</> to your needs',
        ]);
    }
}
