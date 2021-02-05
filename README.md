# Task Runner

Between working on various projects, sometimes months between checking them and I wanted a way to standardize
the processes for each project, which made it easy to setup, manage and use. 

## Installation

```bash
$ composer require jamielsharief/task-runner
```

## Usage

It looks for `task.yml` file in the working directory, or you can use the `working-directory` option to get it to look in a different folder.

```bash
$ bin/task --working-directory /somewhere/else
```

If your tasks not running as expected, use the `verbose` option to force the output.

```bash
$ bin/task --verbose # show output and debug info
```

To see the available tasks for each project

```bash
$ bin/task
   ______           __      ____                             
  /_  __/___ ______/ /__   / __ \__  ______  ____  ___  _____
   / / / __ `/ ___/ //_/  / /_/ / / / / __ \/ __ \/ _ \/ ___/
  / / / /_/ (__  ) ,<    / _, _/ /_/ / / / / / / /  __/ /    
 /_/  \__,_/____/_/|_|  /_/ |_|\__,_/_/ /_/_/ /_/\___/_/     
                                                                             

version dev
+-----------+----------------------------------------------------+
| task      | description                                        |
+-----------+----------------------------------------------------+
| test      | runs PHPUnit tests                                 |
| coverage  | Generates the code coverage                        |
| release   | Creates a release and updates the version number.  |
| build     | Builds the PHAR                                    |
| deploy    | Deploys the PHAR file                              |
+-----------+----------------------------------------------------+
```

To run the `deploy` task which depends on `build` which depends on `test`.

```bash
$ bin/task deploy
   ______           __      ____                             
  /_  __/___ ______/ /__   / __ \__  ______  ____  ___  _____
   / / / __ `/ ___/ //_/  / /_/ / / / / __ \/ __ \/ _ \/ ___/
  / / / /_/ (__  ) ,<    / _, _/ /_/ / / / / / / /  __/ /    
 /_/  \__,_/____/_/|_|  /_/ |_|\__,_/_/ /_/_/ /_/\___/_/     
                                                               
version dev
[ OK ] Run PHPUnit
[ OK ] Run PHPStan
[ OK ] Build PHAR archive
[ OK ] Copy task.phar to local/bin
```

An example configuration

```yaml
dotenv: config/.env
tasks:
  test:
    name: Run PHPUnit
    description: runs PHPUnit tests
    commands:
      - vendor/bin/phpunit
    output: true
    environment:
      XDEBUG_MODE: "off"
  coverage:
    name: Generate code coverage
    description: Generates the code coverage
    commands:
      - vendor/bin/phpunit --coverage-html coverage
    environment:
      XDEBUG_MODE: "coverage"
  release:
    name: Create release
    description: Creates a release and updates version.txt
    commands:
      - bin/release
  build:
    name: Build PHAR archive
    description: Builds the PHAR
    commands:
      - php -d phar.readonly=Off bin/build
    depends:
      - test
  deploy:
    name: Copy task.phar to local/bin
    description: Deploys the PHAR file
    depends:
      - build
    commands:
      - cp bin/task.phar /usr/local/bin/task
```

### Keys

#### Global Keys

##### dotenv (string)

A path a .env file this can be used gobally or for a specific task only.

```
dotenv: config/.env
```

##### environment

An key value array of environment variables

```
environment:
  XDEBUG_MODE: "coverage"
```

#### Tasks Specific Keys

This are for setting task specific settings within the task configuration

##### dotenv (string)

A path a .env file

```yaml
dotenv: config/.env
```

##### environment

An key value array of environment variables

```yaml
environment:
  XDEBUG_MODE: "coverage"
```

##### directory (string)

You can set the working directory of task, if the directory does not exist, it will create it.

```yaml
directory: /tmp/build
```

##### output (bool)

Sends output from the command process directly to the screen, default is `false`

```yaml
output: true
```
##### commands (array)

An list of commands to run

```yaml
commands:
  - mkdir foo
```

##### depends (array)

An a array of `tasks` that this `task` depends on, these will be run first

```yaml
depends:
  - build
```
##### name (string)

If you want to display a different name of the task when running, set this, this does not change the command line
name to call the task.

##### description (string)

This shows up on the list screen