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
namespace TaskRunner\DataTransferObject;

use DataTransferObject\DataTransferObject;

class Task extends DataTransferObject
{
    /**
     * Name displayed when running the task
     */
    public ?string $name;
    /**
     * A description of the task when listing the tasks
     */
    public ?string $description = null;

    /**
     * Directory where to run the task, if it does not exist it will be
     * created
     */
    public ?string $directory = null;
    
    /**
     * Set to true if you want the output of the command send to the screen directly
     */
    public bool $output = false;

    /**
     * An array of commands to execute
     */
    public array $commands = [];
   
    /**
     * Tasks here will be run
     */
    public array $depends = [];

    /**
     * Path to .env to which load vars from
     */
    public ?string $dotenv = null;

    /**
     * An array of environment variables to set
     */
    public array $environment = [];
}
