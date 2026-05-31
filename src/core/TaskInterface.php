<?php

namespace Schedule\Core;

interface TaskInterface
{
    /**
     * Retorna o nome único da tarefa.
     */
    public function getName(): string;

    /**
     * Retorna a expressão Cron que define quando a tarefa deve ser executada.
     */
    public function getExpression(): string;

    /**
     * A lógica principal da tarefa.
     */
    public function handle(): void;
}
