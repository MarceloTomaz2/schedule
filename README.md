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

### 2. Integração com Mad Builder / Adianti Framework (`cmd.php`)

Dentro de um projeto gerado pelo **Mad Builder** utilizando o **Adianti Framework**, a recomendação é utilizar o script `cmd.php` (na raiz do projeto) como ponto de entrada do *Scheduler*. Como o `cmd.php` pode carregar a inicialização do Adianti (`init.php`), isso garante que as suas Tasks tenham acesso a banco de dados e models nativos.

```php
<?php
// Exemplo de cmd.php na raiz do seu projeto Adianti
require_once 'init.php';
require_once 'vendor/autoload.php';

use Schedule\TaskFactory;
use App\Service\Task\SelectNewsTask;
use App\Service\Chatwoot\SearchTask;

// (Opcional) Configura caminhos customizados para os diretórios tmp e logs na raiz do projeto Adianti
// TaskFactory::setConfig(__DIR__ . '/tmp', __DIR__ . '/logs');

// Passe as instâncias das tarefas (ou o nome da classe em string)
TaskFactory::run([
    new SelectNewsTask(),
    new SearchTask(),
]);
```

### 3. Configurando a Execução Automática (Sistema Operacional)

O *Scheduler* funciona verificando minuto a minuto quais tarefas devem rodar. Por isso, você deve configurar o seu sistema operacional para chamar o `cmd.php` a cada minuto. O pacote fará toda a orquestração internamente (evitando duplicidade e checando expressões Cron).

#### 🐧 Linux (Cron)

Adicione o seu script no Crontab (`crontab -e`) para rodar a cada minuto:

```bash
* * * * * php /caminho/do/seu/projeto/cmd.php >> /dev/null 2>&1
```

#### 🪟 Windows (Agendador de Tarefas / Task Scheduler)

No Windows, configure uma tarefa que se repita continuamente:

1. Abra o aplicativo **Agendador de Tarefas** (Task Scheduler) e selecione **Criar Tarefa...** (Create Task).
2. Na aba **Geral**, defina um nome (ex: `Adianti Scheduler`).
3. Na aba **Disparadores** (Triggers), clique em **Novo**. Configure como **Diariamente**, marque a opção **Repetir a tarefa a cada:** `1 minuto`, com duração de **Indefinidamente**.
4. Na aba **Ações** (Actions), clique em **Novo**:
   - **Ação:** Iniciar um programa.
   - **Programa/script:** Digite `php` (ou o caminho completo para o `php.exe`, ex: `C:\php\php.exe`).
   - **Adicionar argumentos:** `-f "C:\caminho\do\seu\projeto\cmd.php"`
   - **Iniciar em:** `C:\caminho\do\seu\projeto\`
5. Clique em **OK** para salvar e iniciar o seu agendador.

## Testes

Este projeto utiliza o **PHPUnit** para cobertura de código. Para rodar a suíte de testes unitários:

```bash
vendor/bin/phpunit
```
