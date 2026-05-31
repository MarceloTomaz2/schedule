# Schedule Framework

Uma biblioteca PHP robusta e desacoplada para gerenciamento e execução de tarefas assíncronas (Cron Jobs). O `Schedule` facilita a orquestração de rotinas, garantindo controle de concorrência, execução baseada em expressões Cron padrão e logs centralizados.

## Funcionalidades

- **Expressões Cron Padrão:** Integração com `dragonmantank/cron-expression` para flexibilidade na definição de agendamentos (`* * * * *`).
- **Prevenção de Concorrência:** Uso de locks exclusivos (`flock`) e tracking de execuções (`.last_run`) para garantir que uma mesma tarefa não atropele outra execução idêntica em andamento.
- **Isolamento e Logs:** Captura de exceções por tarefa sem interromper a fila principal, medindo a duração da execução e guardando o histórico no arquivo `logs/scheduler.log`.
- **Factory Simplificada:** Execução das tarefas abstraída em uma interface limpa, onde o projeto cliente apenas injeta as suas regras de negócio.

## Requisitos

- PHP >= 8.1
- Composer

## Instalação

Você pode incluir este projeto no seu aplicativo usando o Composer:

```bash
composer require mftsoft/schedule
```

## Como Usar

### 1. Criando uma Tarefa (Task)

Crie as suas tarefas estendendo a classe abstrata `Schedule\Core\AbstractTask`. Você deve implementar três métodos obrigatórios:

```php
<?php

namespace App\Jobs;

use Schedule\Core\AbstractTask;

class LimparLogsTask extends AbstractTask
{
    /**
     * Define um nome único para a tarefa.
     */
    public function getName(): string
    {
        return 'limpar_logs_antigos';
    }

    /**
     * Define a expressão Cron de quando a tarefa deve rodar.
     * Exemplo: Todo dia à meia-noite
     */
    public function getExpression(): string
    {
        return '0 0 * * *';
    }

    /**
     * A lógica da sua regra de negócio.
     */
    public function handle(): void
    {
        // ... Sua lógica de limpeza aqui
    }
}
```

### 2. Ponto de Entrada (Ex: `cron.php`)

No seu projeto, crie o script que será chamado pelo crontab do servidor operacional a cada minuto. Utilize a `TaskFactory` para registrar e orquestrar suas tasks.

Por padrão, a lib criará as pastas `tmp/` (para arquivos de lock) e `logs/` (para o histórico) na mesma raiz de onde o script estiver rodando. Caso você prefira usar diretórios personalizados (ex: `/var/log`), você pode configurar o `TaskFactory` antes de invocá-lo:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Schedule\TaskFactory;
use App\Jobs\LimparLogsTask;

// (Opcional) Configura caminhos customizados para os diretórios tmp e logs
TaskFactory::setConfig('/caminho/absoluto/tmp', '/caminho/absoluto/logs');

// Passe as instâncias das tarefas (ou o nome da classe em string)
TaskFactory::run([
    new LimparLogsTask(),
    // OutraTask::class
]);
```

### 3. Configurando no Servidor (Linux)

Adicione o seu script principal no Crontab do sistema operacional para rodar a cada minuto. O Scheduler cuidará de decidir qual tarefa será invocada com base na expressão definida:

```bash
* * * * * php /caminho/do/seu/projeto/cron.php >> /dev/null 2>&1
```

## Testes

Este projeto utiliza o **PHPUnit** para cobertura de código. Para rodar a suíte de testes unitários:

```bash
vendor/bin/phpunit
```
