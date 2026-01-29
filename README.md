# ImageX

Laravel 12 приложение на RoadRunner (Octane) с PostgreSQL и MinIO, развёрнутое через Laravel Sail.

## Требования

- Docker и Docker Compose

## Установка

1. Клонировать репозиторий:

```bash
git clone <repo-url> imageX
cd imageX
```

2. Установить PHP-зависимости (без локального PHP — через Docker):

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs
```

3. Настроить окружение:

```bash
cp .env.example .env
./vendor/bin/sail artisan key:generate
```

4. Поднять контейнеры:

```bash
./vendor/bin/sail up -d
```

Будут запущены:
- **laravel.test** — PHP 8.5 + Laravel Octane (RoadRunner) на порту `8000`
- **pgsql** — PostgreSQL 18 на порту `5432`
- **minio** — S3-совместимое хранилище, API на порту `9000`, консоль на `8900`

5. Выполнить миграции:

```bash
./vendor/bin/sail artisan migrate
```

Приложение доступно по адресу: http://localhost:8000

## API Документация

Swagger/OpenAPI документация (Scramble): http://localhost:8000/docs/api

## RoadRunner (Octane)

Приложение обслуживается через **Laravel Octane** с сервером **RoadRunner**.

RoadRunner держит приложение в памяти между запросами, что значительно ускоряет обработку. Учитывайте это при разработке: избегайте хранения состояния в статических свойствах и синглтонах.

### Запуск с watch режимом (для разработки)

```bash
./vendor/bin/sail up -d && ./vendor/bin/sail artisan octane:start --watch --host=0.0.0.0
```

Сервер будет автоматически перезагружаться при изменении файлов.

## Полезные команды

```bash
# Остановить контейнеры
./vendor/bin/sail down

# Логи
./vendor/bin/sail logs -f

# Artisan
./vendor/bin/sail artisan <command>

# Composer
./vendor/bin/sail composer <command>

# Тесты
./vendor/bin/sail test
```

## MinIO (S3)

- API: http://localhost:9000
- Консоль: http://localhost:8900
- Логин: `sail` / Пароль: `password`
