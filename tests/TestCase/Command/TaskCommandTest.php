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
declare(strict_types = 1);
namespace TaskRunner\Test\TestCase\Command;

use RuntimeException;
use Origin\Filesystem\Folder;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class TaskCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public static function setUpBeforeClass(): void
    {
        if (Folder::exists(ROOT . '/tmp')) {
            Folder::delete(ROOT . '/tmp', ['recursive' => true]);
        }
        Folder::create(ROOT . '/tmp');

        if (! Folder::copy(ROOT . '/tests/Fixture', ROOT . '/tmp/test')) {
            throw new RuntimeException('Error creating fixture');
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (Folder::exists(ROOT . '/tmp')) {
            Folder::delete(ROOT . '/tmp', ['recursive' => true]);
        }
    }

    public function testWorkingDirectory()
    {
        $this->exec('task path -v -d ' . ROOT . '/tmp/test');
        $this->assertExitSuccess();

        $this->assertOutputContains('<white>[</white> <green>OK</green> <white>] path</white>');
        $this->assertOutputContains(ROOT);
    }

    public function testENV()
    {
        $this->exec('task echo -v -d ' . ROOT . '/tmp/test');

        $this->assertExitSuccess();
        $this->assertOutputContains('<white>[</white> <green>OK</green> <white>] echo</white>');
        $home = getenv('HOME');
        $this->assertOutputContains("ECHO='{$home}'");
    }

    public function testEnvironment()
    {
        $this->exec('task env -v -d ' . ROOT . '/tmp/test');

        $this->assertExitSuccess();
        $this->assertOutputContains('<white>[</white> <green>OK</green> <white>] env</white>');
   
        $this->assertOutputContains("FOOBAR='1234'");
        $this->assertOutputContains("FOO='bar'");
        $this->assertOutputContains("BAR='foo'");
    }

    public function testDepends()
    {
        $this->exec('task install -d ' . ROOT . '/tmp/test');

        $this->assertOutputCOntains('<green>OK</green> <white>] download source code</white>');
        $this->assertOutputCOntains('<green>OK</green> <white>] install</white>');
    }

    public function testDependsFailure()
    {
        $this->exec('task install-failure -d ' . ROOT . '/tmp/test');
        $this->assertOutputCOntains('<red>ERROR</red> <white>] directory exists</white>');
        $this->assertOutputCOntains('<cyan>SKIPPED</cyan> <white>] install</white>');
    }

    public function testList()
    {
        $this->exec('task');
        $this->assertOutputContains('| coverage  | Generates the code coverage                |');
    }

    public function testUnkownTask()
    {
        $this->exec('task foo -d ' . ROOT . '/tmp/test');

        $this->assertExitError();
        $this->assertErrorContains('<exception> ERROR </exception> <heading>Unkown task: foo</heading>');
    }

    public function testDirectoryDoesNotExist()
    {
        $this->exec('task foo -d /nowhere');
        $this->assertExitError();
        $this->assertErrorContains('<exception> ERROR </exception> <heading>Directory does not exist</heading>');
    }

    public function testNoConfig()
    {
        $this->exec('task foo -d tests');
        $this->assertExitError();
        $this->assertErrorContains('<exception> ERROR </exception> <heading>task.yml not found</heading>');
    }

    public function testEnvDoesNotExist()
    {
        $this->exec('task  env-does-not-exist -d ' . ROOT . '/tmp/test');
        $this->assertExitError();
        $this->assertErrorContains('<exception> ERROR </exception> <heading>dotenv file not found</heading>');
    }
  
    public function testInvalidYaml()
    {
        $tmp = sys_get_temp_dir() .'/'. uniqid();
        mkdir($tmp, 0775, true);
        file_put_contents($tmp .'/task.yml', "\t Yaml does not contain tabs");
        $this->exec('task -d ' . $tmp);
        $this->assertExitError();
        $this->assertErrorContains('<exception> ERROR </exception> <heading>Error parsing YAML configuration</heading>');
    }

    public function testInvalidPipeline()
    {
        $tmp = sys_get_temp_dir() .'/'. uniqid();
        mkdir($tmp, 0775, true);
        file_put_contents($tmp .'/task.yml', 'tasks: foo');
        $this->exec('task -d ' . $tmp);
        $this->assertExitError();
        $this->assertErrorContains('<exception> ERROR </exception> <heading>Invalid YAML configuration file</heading>');
    }
}
