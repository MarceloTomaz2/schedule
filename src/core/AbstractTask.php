<?php

namespace Schedule\Core;

use Exception;

abstract class AbstractTask implements TaskInterface
{
    /**
     * Retorna o diretório temporário do projeto que está executando a lib.
     */
    protected function getTmpDir(): string
    {
        return \Schedule\TaskFactory::getTmpDir();
    }

    /**
     * Retorna o arquivo de log do projeto que está executando a lib.
     */
    protected function getLogFile(): string
    {
        return \Schedule\TaskFactory::getLogDir() . '/scheduler.log';
    }

    /**
     * Executa a tarefa validando intervalo, garantindo lock e capturando exceções.
     */
    public function execute(): void
    {
        if (!$this->isDue()) {
            return;
        }

        if (!is_dir($this->getTmpDir())) {
            mkdir($this->getTmpDir(), 0777, true);
        }

        $lockFile = $this->getTmpDir() . '/' . md5($this->getName()) . '.lock';
        $fp = fopen($lockFile, 'w+');

        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            $this->log("Concorrência detectada (lock ativo). Pulando execução.");
            if ($fp) {
                fclose($fp);
            }
            return;
        }

        $this->markAsRun();
        $this->log("Iniciando execução.");
        $startTime = microtime(true);

        try {
            $this->handle();
            $duration = round(microtime(true) - $startTime, 4);
            $this->log("Execução finalizada com sucesso. Duração: {$duration}s.");
        } catch (Exception $e) {
            $duration = round(microtime(true) - $startTime, 4);
            $this->log("Falha na execução: " . $e->getMessage() . " Duração: {$duration}s.");
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
            @unlink($lockFile);
        }
    }

    /**
     * Verifica se a tarefa deve ser executada de acordo com a expressão Cron.
     */
    protected function isDue(): bool
    {
        $cron = new \Cron\CronExpression($this->getExpression());
        if (!$cron->isDue()) {
            return false;
        }

        $lastRunFile = $this->getTmpDir() . '/' . md5($this->getName()) . '.last_run';
        
        if (!file_exists($lastRunFile)) {
            return true;
        }

        $lastRunMinute = file_get_contents($lastRunFile);
        $currentMinute = date('Y-m-d H:i');

        // Evita múltiplas execuções no mesmo minuto caso o processo seja chamado várias vezes
        return $lastRunMinute !== $currentMinute;
    }

    /**
     * Registra o momento da execução atual.
     */
    protected function markAsRun(): void
    {
        $lastRunFile = $this->getTmpDir() . '/' . md5($this->getName()) . '.last_run';
        file_put_contents($lastRunFile, date('Y-m-d H:i'));
    }

    /**
     * Escreve no log centralizado.
     */
    protected function log(string $message): void
    {
        if (!is_dir(dirname($this->getLogFile()))) {
            mkdir(dirname($this->getLogFile()), 0777, true);
        }

        $date = date('Y-m-d H:i:s');
        $name = $this->getName();
        $line = "[{$date}] [Task: {$name}] {$message}" . PHP_EOL;
        file_put_contents($this->getLogFile(), $line, FILE_APPEND);
    }
}
