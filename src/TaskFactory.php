<?php

namespace Schedule;

use Schedule\Core\Scheduler;
use Schedule\Core\AbstractTask;

class TaskFactory
{
    private static ?string $tmpDir = null;
    private static ?string $logDir = null;

    /**
     * Configura manualmente o diretório de arquivos temporários e logs.
     */
    public static function setConfig(string $tmpDir, string $logDir): void
    {
        self::$tmpDir = rtrim($tmpDir, '/\\');
        self::$logDir = rtrim($logDir, '/\\');
    }

    /**
     * Retorna o diretório temporário configurado ou assume 'tmp' na raiz de quem chamou o script.
     */
    public static function getTmpDir(): string
    {
        return self::$tmpDir ?? (dirname($_SERVER['SCRIPT_FILENAME']) . '/tmp');
    }

    /**
     * Retorna o diretório de logs configurado ou assume 'logs' na raiz de quem chamou o script.
     */
    public static function getLogDir(): string
    {
        return self::$logDir ?? (dirname($_SERVER['SCRIPT_FILENAME']) . '/logs');
    }

    /**
     * Inicializa o Scheduler, registra as tarefas fornecidas e inicia a execução.
     *
     * @param AbstractTask[]|string[] $tasks Lista de instâncias ou nomes de classes que estendem AbstractTask.
     * @return void
     */
    public static function run(array $tasks): void
    {
        $scheduler = new Scheduler();

        foreach ($tasks as $task) {
            // Se foi passado o nome da classe em string, tentamos instanciar
            if (is_string($task) && class_exists($task)) {
                $task = new $task();
            }

            // Apenas registra a tarefa se ela for do tipo válido
            if ($task instanceof AbstractTask) {
                $scheduler->register($task);
            }
        }

        $scheduler->run();
    }
}
