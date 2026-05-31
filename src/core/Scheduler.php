<?php

namespace Schedule\Core;

class Scheduler
{
    /** @var AbstractTask[] */
    protected array $tasks = [];

    /**
     * Registra uma nova tarefa no Scheduler.
     */
    public function register(AbstractTask $task): void
    {
        $this->tasks[] = $task;
    }

    /**
     * Itera sobre as tarefas e inicia o fluxo de execução.
     */
    public function run(): void
    {
        foreach ($this->tasks as $task) {
            $task->execute();
        }
    }
}
