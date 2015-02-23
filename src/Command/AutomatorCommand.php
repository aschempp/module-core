<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Contao\Automator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Runs Automator tasks on the command line.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AutomatorCommand extends ContainerAwareCommand
{
    /**
     * @var array
     */
    protected $commands = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:automator')
            ->setDefinition([
                new InputArgument(
                    'task',
                    InputArgument::OPTIONAL,
                    "The name of the task:\n  - " . implode("\n  - ", $this->getCommands())
                ),
            ])
            ->setDescription('Runs automator tasks on the command line')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = new LockHandler('contao:automator');

        // Set the lock
        if (!$lock->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $task = $this->getTaskFromInput($input, $output);

        // No task given
        if (null === $task) {
            $output->writeln("No task given (see help contao:automator)");

            return 1;
        }

        // Invalid task
        if (!in_array($task, $this->getCommands())) {
            $output->writeln("Invalid task $task");

            return 1;
        }

        // Run
        $automator = new Automator();
        $automator->$task();

        // Release the lock
        $lock->release();

        return 0;
    }

    /**
     * Returns a list of available commands.
     *
     * @return array The commands array
     */
    private function getCommands()
    {
        if (empty($this->commands)) {
            $this->commands = $this->generateCommandMap();
        }

        return $this->commands;
    }

    /**
     * Generates the command map from the Automator class.
     *
     * @return array The commands array
     */
    private function generateCommandMap()
    {
        $commands = [];

        // Find all public methods
        $class   = new \ReflectionClass('Contao\\Automator');
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->class == 'Contao\\Automator' && $method->name != '__construct') {
                $commands[] = $method->name;
            }
        }

        return $commands;
    }

    /**
     * Returns the task name from the argument list or via an interactive dialog.
     *
     * @param InputInterface  $input  The input context
     * @param OutputInterface $output The output context
     *
     * @return string|null The task name or null
     */
    private function getTaskFromInput(InputInterface $input, OutputInterface $output)
    {
        $task = $input->getArgument('task');

        if (null !== $task) {
            return $task;
        }

        if (!$input->isInteractive()) {
            return null;
        }

        $commands  = $this->getCommands();
        $dialog    = $this->getHelper('dialog');
        $selection = $dialog->select($output, 'Please select a task:', $commands, 0);

        return $commands[$selection];
    }
}
