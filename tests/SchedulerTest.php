<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Schedule\Core\AbstractTask;
use Schedule\TaskFactory;

class DummyTask extends AbstractTask
{
    public static bool $executed = false;

    public function getName(): string
    {
        return 'dummy_test_task';
    }

    public function getExpression(): string
    {
        return '* * * * *'; // Always due (every minute)
    }

    public function handle(): void
    {
        self::$executed = true;
    }
}

class NeverTask extends AbstractTask
{
    public static bool $executed = false;

    public function getName(): string
    {
        return 'never_test_task';
    }

    public function getExpression(): string
    {
        return '59 23 31 12 *'; // Almost never (New Year's Eve at 23:59)
    }

    public function handle(): void
    {
        self::$executed = true;
    }
}

class SchedulerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DummyTask::$executed = false;
        NeverTask::$executed = false;
        
        $tmpDir = __DIR__ . '/../tmp';
        $logDir = __DIR__ . '/../logs';
        
        \Schedule\TaskFactory::setConfig($tmpDir, $logDir);

        // Clean tmp and logs for tests
        if (is_dir($tmpDir)) {
            $files = glob($tmpDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    public function testTaskFactoryExecutesDueTasks()
    {
        TaskFactory::run([
            new DummyTask()
        ]);

        $this->assertTrue(DummyTask::$executed, "A tarefa 'DummyTask' deveria ter sido executada pois sua expressão Cron '* * * * *' sempre avalia como verdadeira.");
    }

    public function testTaskFactoryDoesNotExecuteTasksNotDue()
    {
        TaskFactory::run([
            new NeverTask()
        ]);

        $this->assertFalse(NeverTask::$executed, "A tarefa 'NeverTask' não deveria ser executada pois sua expressão Cron está no futuro.");
    }

    public function testConcurrecyIsPreventedWithinSameMinute()
    {
        // First run should execute
        TaskFactory::run([
            new DummyTask()
        ]);
        
        $this->assertTrue(DummyTask::$executed);

        // Reset the flag
        DummyTask::$executed = false;

        // Second run within the same minute should be blocked by .last_run logic
        TaskFactory::run([
            new DummyTask()
        ]);

        $this->assertFalse(DummyTask::$executed, "A tarefa 'DummyTask' não deveria executar duas vezes no mesmo minuto.");
    }
}
