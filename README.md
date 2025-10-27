# Country Currency & Exchange API

A RESTful API that fetches country data from external APIs, stores it in a MySQL database, and provides CRUD operations with currency exchange calculations and GDP estimates.

## Features

- Fetch country data from RestCountries API
- Fetch exchange rates from Open Exchange Rates API
- Compute estimated GDP: population × random(1000–2000) ÷ exchange_rate
- Store data in MySQL database
- API endpoints for data retrieval, refresh, and deletion
- Generate summary image with top GDP countries

## API Endpoints

### POST /countries/refresh
Fetch all countries and exchange rates, cache in database, generate summary image.

**Response:**
```json
{
  "message": "Refresh successful"
}
```

**Error (503):**
```json
{
  "error": "External data source unavailable",
  "details": "Could not fetch data from [API name]"
}
```

### GET /countries
Get all countries with optional filters and sorting.

**Query Parameters:**
- `region`: Filter by region (e.g., Africa)
- `currency`: Filter by currency code (e.g., NGN)
- `sort`: `gdp_desc` or `gdp_asc` (sort by estimated GDP, nulls handled properly)

**Example:**
GET /countries?region=Africa&sort=gdp_desc

**Response (Sample):**
```json
[
  {
    "id": 163,
    "name": "Nigeria",
    "capital": "Abuja",
    "region": "Africa",
    "population": 206139587,
    "currency_code": "NGN",
    "exchange_rate": 1460.867333,
    "estimated_gdp": 168905882.18732,
    "flag_url": "https://flagcdn.com/ng.svg",
    "last_refreshed_at": "2025-10-27 07:24:18"
  }
]
```

### GET /countries/{name}
Get a specific country by name.

**Response (200):**
Country JSON object.

**Error (404):**
```json
{
  "error": "Country not found"
}
```

### DELETE /countries/{name}
Delete a country record.

**Response (200):**
```json
{
  "message": "Country deleted"
}
```

**Error (404):**
```json
{
  "error": "Country not found"
}
```

### GET /status
Show total countries and last refresh timestamp.

**Response:**
```json
{
  "total_countries": 250,
  "last_refreshed_at": "2025-10-27 07:24:18"
}
```

### GET /countries/image
Serve the generated summary image (PNG).

**Response (200):** Image data (Content-Type: image/png)

**Error (404):**
```json
{
  "error": "Summary image not found"
}
```

## Setup Instructions

### Prerequisites
- PHP 8.4+
- Composer
- MySQL server (local or cloud)

### Installation
1. Clone the repository:
   ```bash
   git clone <repo-url>
   cd stage3
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Setup the database:
   - Create a MySQL database named `country_api`.
   - Update `.env` file with your MySQL credentials (host, username, password).
   - The table schema will be created automatically on first refresh.

### Running Locally
Start the PHP development server:
```bash
composer run serve
# or
php -S localhost:8000 -t public/
```

Access at: http://localhost:8000

### API Usage
1. **Refresh Data:** `POST /countries/refresh`
2. **View Status:** `GET /status`
3. **Get Countries:** `GET /countries`
4. **Filter Examples:**
   - `GET /countries?region=Africa`
   - `GET /countries?currency=NGN`

### Hosting
This project can be deployed to PHP-supported cloud platforms that support PHP and MySQL.

#### For Railway (Recommended):
1. Create a Railway project and connect to GitHub.
2. Add a MySQL data source (Railway provides managed MySQL, free tier available).
3. Set environment variables for DB connection.
4. Deploy. The root directory is public/ for web server.
5. Visit the deployed URL.

#### For Heroku:
Heroku has ended free tiers, but you can use their paid dynos or alternatives like Render/Paused.

#### Free MySQL Hosting Options:
For testing/deploying with free MySQL:
- PlanetScale: Free Hobby plan with MySQL-compatible database.
- Aiven: Free credits for MySQL databases.
- Neon: Free PostgreSQL tiers.

Use the MySQL database URL in .env DB_HOST.

## Dependencies
- slim/slim: ^4.0 (Slim Framework)
- slim/psr7: ^1.0 (PSR-7 implementation)
- guzzlehttp/guzzle: ^7.0 (HTTP client for API calls)
- vlucas/phpdotenv: ^5.0 (Environment variable loader)
- ext-pdo: * (PHP PDO extension, including MySQL driver)
- ext-json: * (PHP JSON extension)
- ext-gd: * (PHP GD extension for image generation)
- ext-mysqli: * or ext-pdo_mysql: * (PHP MySQL extensions)

## Environment Variables
- `DB_CONNECTION=mysql`
- `DB_HOST=localhost`
- `DB_NAME=country_api`
- `DB_USER=root`
- `DB_PASS=` (your MySQL root password, if set)
- `DB_CHARSET=utf8mb4`
- `APP_ENV=development`
- `APP_DEBUG=true`

## Notes
- Database: Uses MySQL for data persistence.
- Image Generation: Automatically creates `cache/summary.png` during /countries/refresh.
- Error Handling: Returns appropriate HTTP status codes (404, 400, 500, 503).
- Validation: Name, population required; handled in data insertion.
- Refresh Logic: Updates existing countries (case-insensitive name match), inserts new.
- External API Handling: Handles failures with 503 status.

## Table Schema
If needed, create the table manually:

```sql
CREATE TABLE countries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) UNIQUE NOT NULL,
  capital VARCHAR(255),
  region VARCHAR(100),
  population BIGINT NOT NULL,
  currency_code VARCHAR(10),
  exchange_rate DOUBLE,
  estimated_gdp DECIMAL(20,2),
  flag_url VARCHAR(500),
  last_refreshed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## License
This project is for educational purposes.
