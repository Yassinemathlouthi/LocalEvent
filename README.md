# LocalEvent - Local Event Management Platform

A Symfony-based web application for managing local events, RSVPs, and community engagement.

## 🚀 Quick Start

1. **Clone the repository**
   ```
   git clone [repository-url]
   cd localevent
   ```

2. **Install dependencies**
   ```
   composer install
   npm install
   ```

3. **Configure database**
   - Make sure PostgreSQL is running
   - Create a database named `localevent`
   - Database connection is already configured in `.env.local`

4. **Run security setup**
   ```
   php setup-security.php
   ```

5. **Start the application**
   ```
   php -S localhost:8000 -t public/
   ```

6. **Build assets**
   ```
   npm run build
   ```

## 🔒 Security Setup

### Development Environment

For development, database credentials can be stored in `.env.local` (which is not committed to Git).

### Production Environment

For production:

1. Use Symfony Secrets for credentials:
   ```
   APP_ENV=prod php bin/console secrets:set DATABASE_URL
   ```

2. HTTPS is enforced automatically in production.

3. Create a dedicated database user:
   ```sql
   CREATE USER localevent_user WITH PASSWORD 'secure_password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO localevent_user;
   ```

4. Update your secrets with the new database user:
   ```
   APP_ENV=prod php bin/console secrets:set DATABASE_URL
   ```
   Enter: `postgresql://localevent_user:secure_password@127.0.0.1:5432/localevent`

## 📋 Project Structure

- `src/Controller/` - Application controllers
- `src/Entity/` - Doctrine entities
- `src/Repository/` - Database repositories
- `src/Form/` - Form types
- `templates/` - Twig templates
- `config/` - Application configuration

## 🧪 Testing

```
php bin/phpunit
```

## 🛠️ Troubleshooting

### Database Connection Issues

1. Ensure PostgreSQL is running:
   ```
   pg_isready -h localhost -p 5432
   ```

2. Check database exists:
   ```
   psql -U postgres -c "SELECT 1 FROM pg_database WHERE datname='localevent'"
   ```

### PostgreSQL Array Type Issue

If you encounter the error `Unknown database type _text requested`, this is related to PostgreSQL array types.
The fix is in `config/packages/doctrine.yaml`:

```yaml
doctrine:
    dbal:
        mapping_types:
            _text: text
```

### Server Not Starting

1. Check PHP version (requires PHP 8.1+):
   ```
   php -v
   ```

2. Verify Symfony requirements:
   ```
   php bin/console about
   ``` 