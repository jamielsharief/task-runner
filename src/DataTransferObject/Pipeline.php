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

class Pipeline extends DataTransferObject
{
    /**
     * Path to .env to which load vars from
     */
    public ?string $dotenv = null;

    /**
     * Environment vars to be used globally
     */
    public array $environment = [];

    /**
     * @var \TaskRunner\DataTransferObject\Task[] $tasks
     */
    public array $tasks = [];
}
