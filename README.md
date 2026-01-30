# ImageX

Сервис для загрузки и хранения изображений с дедупликацией.

## Стек

- **Laravel 12** + Octane (RoadRunner)
- **Laravel Horizon** — управление очередями
- **PostgreSQL 18**
- **Redis 7** — очереди, кэш
- **MinIO** — S3-совместимое хранилище
- **JWT** — аутентификация

## Возможности

- Загрузка PNG/JPEG (до 5MB)
- Автоматическое сжатие в WebP
- Генерация thumbnails (200x200)
- Дедупликация по SHA256
- Асинхронная обработка через очереди

## Требования

- Docker и Docker Compose

## Установка

```bash
# 1. Клонировать репозиторий
git clone <repo-url> imageX
cd imageX

# 2. Установить зависимости (без локального PHP)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs

# 3. Настроить окружение
cp .env.example .env
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan jwt:secret

# 4. Запустить контейнеры
./vendor/bin/sail up -d

# 5. Создать bucket в MinIO
# Открыть http://localhost:8900
# Логин: sail / Пароль: password
# Создать bucket: images

# 6. Выполнить миграции
./vendor/bin/sail artisan migrate

# 7. Запустить Horizon (воркеры очередей)
./vendor/bin/sail artisan horizon
```

## Сервисы

| Сервис | URL | Описание |
|--------|-----|----------|
| API | http://localhost:8000 | Основное приложение |
| Horizon | http://localhost:8000/horizon | Мониторинг очередей |
| Документация | http://localhost:8000/docs/api | Swagger UI (Scramble) |
| MinIO Console | http://localhost:8900 | Управление хранилищем |
| PostgreSQL | localhost:5432 | База данных |
| Redis | localhost:6379 | Очереди и кэш |

## API

### Аутентификация

```
POST /api/auth/register   — Регистрация
POST /api/auth/login      — Вход (получение JWT токена)
GET  /api/auth/me         — Текущий пользователь
```

### Изображения

```
POST   /api/images              — Загрузить изображение
GET    /api/images              — Список своих изображений
GET    /api/images/{id}         — Скачать изображение
GET    /api/images/{id}/thumbnail — Скачать thumbnail
DELETE /api/images/{id}         — Удалить изображение
```

Все эндпоинты изображений требуют JWT токен в заголовке:
```
Authorization: Bearer <token>
```

## Разработка

```bash
# Запуск с watch режимом (автоперезагрузка)
./vendor/bin/sail up -d && ./vendor/bin/sail artisan octane:start --watch --host=0.0.0.0

# Horizon (в отдельном терминале)
./vendor/bin/sail artisan horizon

# PHPStan
./vendor/bin/sail composer phpstan

# Тесты
./vendor/bin/sail test

# Логи
./vendor/bin/sail logs -f
```

## Архитектура

### Обработка изображений

1. Пользователь загружает PNG/JPEG
2. Файл сохраняется во временное хранилище (MinIO `temp/`)
3. Job в очереди обрабатывает файл:
   - Вычисляет SHA256 хэш
   - Проверяет дедупликацию
   - Сжимает в WebP (85% качества)
   - Создаёт thumbnail 200x200
   - Сохраняет в MinIO
4. Статус меняется `pending` → `ready`

### Дедупликация

Если файл с таким же содержимым уже загружен — создаётся только ссылка, физический файл не дублируется.

### Структура хранилища (MinIO)

```
images/
├── temp/           — временные файлы
├── images/         — сжатые изображения
│   └── ab/         — подпапки по первым символам хэша
│       └── abc123...webp
└── thumbnails/     — превью
    └── ab/
        └── abc123...webp
```
