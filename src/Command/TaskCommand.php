<?php
/**
 * Task Runner
 * Copyright 2021 Jamiel Sharief.
 *
 * Licensed under The Apache License 2.0
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @license     https://opensource.org/licenses/Apache-2.0 Apache License 2.0
 */
declare(strict_types = 1);
namespace TaskRunner\Command;

use Exception;
use Throwable;
use Origin\Yaml\Yaml;
use Origin\DotEnv\DotEnv;
use Origin\Process\Process;
use Origin\Console\Command\Command;
use TaskRunner\DataTransferObject\Task;
use TaskRunner\DataTransferObject\Pipeline;

class TaskCommand extends Command
{
    protected $name = 'task';
    protected $description = 'Task runner';

    private Pipeline $pipeline;

    protected function initialize(): void
    {
        $this->addOption('working-directory', [
            'description' => 'The directory to load the task file from',
            'short' => 'd'
        ]);

        $this->addOption('config', [
            'description' => 'Name of the configuration file to use',
            'default' => 'task.yml'
        ]);

        $this->addArgument('task', [
            'description' => 'The task(s) that you want to run',
            'type' => 'array'
        ]);
    }

    protected function startup():void
    {
        $this->io->out('<yellow>' . $this->banner() . '</yellow>');
        $this->io->out('version <yellow>'  . $this->taskVersion() . '</yellow>');
    }
 
    /**
     * @return void
     */
    protected function execute(): void
    {
        $this->pipeline = $this->loadConfig();

        if (empty($this->arguments('task'))) {
            $this->showList();
            $this->exit();
        }

        foreach ($this->arguments('task') as $task) {
            $this->runTask($this->prepareTask($task));
        }
    }

    /**
     * Find the task in the pipeline do any modification etc
     *
     * @param string $name
     * @return \TaskRunner\DataTransferObject\Task
     */
    private function prepareTask(string $name) : Task
    {
        if (! isset($this->pipeline->tasks[$name])) {
            $this->throwError('Unkown task: ' . $name);
        }
        $task = $this->pipeline->tasks[$name];
        if (! isset($task->name)) {
            $task->name = $name;
        }

        return $task;
    }

    /**
     * @param \TaskRunner\DataTransferObject\Task $task
     * @param string $name
     * @return bool
     */
    private function runTask(Task $task) : bool
    {
        if ($task->directory && ! is_dir($task->directory)) {
            mkdir($task->directory, 0775, true);
        }

        $hasErrors = false;
        foreach ($task->depends as $name) {
            $childTask = $this->prepareTask($name);
            if (! $hasErrors && ! $this->runTask($childTask)) {
                $hasErrors = true;
                continue;
            }
            if ($hasErrors) {
                $this->io->status('skipped', $childTask->name);
            }
        }
       
        if ($hasErrors) {
            $this->io->status('skipped', $task->name);

            return false;
        }

        foreach ($task->commands as $command) {
            $this->debug('$ ' . $command);

            $process = new Process($command, [
                'escape' => false,
                'directory' => $task->directory ?: getcwd(),
                'output' => $task->output,
                'env' => $this->prepareEnvironment($task)
            ]);
            
            $result = $process->execute();

            if (! $task->output) {
                $output = trim($process->output());
                $error = trim($process->error());
    
                $this->debug($output);
                $this->debug($error);
            }
   
            if (! $result) {
                $this->io->status('error', $task->name);

                return false;
            }
        }
        $this->io->status('ok', $task->name);

        return true;
    }

    /**
     * Prepares the environment vars for the task in a specfic order, so local ENVs
     * can overide global settings
     *
     * @param \TaskRunner\DataTransferObject\Task $task
     * @return array
     */
    private function prepareEnvironment(Task $task) : array
    {
        $env = [];

        if ($this->pipeline->dotenv) {
            $env = $this->loadDotEnv($this->pipeline->dotenv);
        }
        if ($this->pipeline->environment) {
            $env = array_merge($env, $this->pipeline->environment);
        }

        if ($task->dotenv) {
            $env = array_merge($env, $this->loadDotEnv($task->dotenv));
        }

        if ($task->environment) {
            $env = array_merge($env, $task->environment);
        }

        return $env;
    }

    /**
     * Loads ENV from a dotenv file relative
     *
     * @param string $path
     * @return array
     */
    private function loadDotEnv(string $path) : array
    {
        if ($path[0] !== '/') {
            $path = $this->workingDirectory() . '/' . $path;
        }

        if (! file_exists($path)) {
            $this->throwError('dotenv file not found', $path);
        }

        $parts = pathinfo($path);

        return (new DotEnv())->load($parts['dirname'], $parts['basename']);
    }

    /**
     * @return void
     */
    private function showList() : void
    {
        $out = [];
        $pipeline = $this->loadConfig();
   
        $out[] = ['task','description'];
        foreach ($pipeline->tasks as $name => $task) {
            $out[] = [$name,$task->description];
        }
        $this->io->table($out);
    }

    /**
     * @return \TaskRunner\DataTransferObject\Pipeline
     */
    protected function loadConfig() : Pipeline
    {
        $config = $this->options('config');

        $path = $this->workingDirectory();
        if (! file_exists($path . '/' .  $config)) {
            $this->throwError($config . ' not found');
        }

        try {
            $array = Yaml::toArray(file_get_contents($path . '/' .  $config));
        } catch (Exception $exception) {
            $this->throwError('Error parsing YAML configuration', 'Check that the config file is using valid synax');
        }

        try {
            $pipeline = Pipeline::fromArray($array);
        } catch (Throwable $exception) {
            $this->throwError('Invalid YAML configuration file', $exception->getMessage());
        }

        return $pipeline;
    }

    private function workingDirectory() : string
    {
        $workingDirectory = $this->options('working-directory') ;

        if ($workingDirectory && ! is_dir($workingDirectory)) {
            $this->throwError('Directory does not exist');
        }

        return $workingDirectory ? realpath($workingDirectory) : getcwd();
    }

    /**
     * @return string
     */
    private function taskVersion(): string
    {
        return file_get_contents(ROOT . '/version.txt');
    }

    /**
     * @return string
     */
    protected function banner(): string
    {
        return <<< EOT
   ______           __      ____                             
  /_  __/___ ______/ /__   / __ \__  ______  ____  ___  _____
   / / / __ `/ ___/ //_/  / /_/ / / / / __ \/ __ \/ _ \/ ___/
  / / / /_/ (__  ) ,<    / _, _/ /_/ / / / / / / /  __/ /    
 /_/  \__,_/____/_/|_|  /_/ |_|\__,_/_/ /_/_/ /_/\___/_/     
                                                               
EOT;
    }
}
