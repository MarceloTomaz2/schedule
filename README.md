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

### 2. Ponto de Entrada Padrão (Sem Frameworks)

Para projetos em PHP puro, crie um script (ex: `cron.php`) na raiz do projeto que será chamado pelo crontab a cada minuto. Utilize a `TaskFactory` para registrar e orquestrar suas tasks:

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
]);
```

### 3. Integração com Mad Builder / Adianti Framework (`cmd.php`)

Dentro de um projeto gerado pelo **Mad Builder** utilizando o **Adianti Framework**, a recomendação é utilizar o script `cmd.php` (na raiz do projeto) como ponto de entrada do *Scheduler*. 

Como o script `cmd.php` já existe nativamente no Adianti e carrega toda a aplicação, você **não precisa modificá-lo**! Basta criar uma **Classe de Serviço** no seu projeto para abrigar a chamada ao Scheduler.

Exemplo de serviço (ex: `app/service/task/SchedulerService.php`):

```php
<?php

use Schedule\TaskFactory;

class SchedulerService
{
    /**
     * Ponto de entrada chamado via CLI (cmd.php)
     */
    public static function run($param): void
    {
        // (Opcional) Configura caminhos customizados para logs e tmp
        // TaskFactory::setConfig(__DIR__.'/../../../tmp', __DIR__.'/../../../logs');

        // Dispara o Scheduler registrando as suas tarefas
        TaskFactory::run([
            new \App\Service\Task\SelectNewsTask(),
            new \App\Service\Chatwoot\SearchTask(),
        ]);
    }
}
```

### 4. Configurando a Execução Automática (Sistema Operacional)

O *Scheduler* funciona verificando minuto a minuto quais tarefas devem rodar. Por isso, você deve configurar o seu sistema operacional para chamar o comando do Adianti a cada minuto. O framework Adianti exige a passagem dos parâmetros `class` e `method`.

#### 🐧 Linux (Cron)

Adicione a linha abaixo no Crontab (`crontab -e`):

```bash
* * * * * php /caminho/do/projeto/cmd.php "class=SchedulerService&method=run" >> /dev/null 2>&1
```

#### 🪟 Windows (Agendador de Tarefas / Task Scheduler)

No Windows, configure uma tarefa que se repita continuamente:

1. Abra o aplicativo **Agendador de Tarefas** e selecione **Criar Tarefa...**.
2. Na aba **Geral**, defina um nome (ex: `Adianti Scheduler`).
3. Na aba **Disparadores**, clique em **Novo**. Configure como **Diariamente**, marque a opção **Repetir a tarefa a cada:** `1 minuto`, com duração de **Indefinidamente**.
4. Na aba **Ações**, clique em **Novo**:
   - **Ação:** Iniciar um programa.
   - **Programa/script:** `php` (ou o caminho completo, ex: `C:\php\php.exe`).
   - **Adicionar argumentos:** `-f "C:\caminho\do\projeto\cmd.php" "class=SchedulerService&method=run"`
   - **Iniciar em:** `C:\caminho\do\projeto\`
5. Salve e o agendamento iniciará.

## Testes

Este projeto utiliza o **PHPUnit** para cobertura de código. Para rodar a suíte de testes unitários:

```bash
vendor/bin/phpunit
```
