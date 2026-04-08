# Personal Film Library

A web application to manage your personal movie collection with OMDB search integration.

## Requirements

- Docker and Docker Compose
- OMDB API key (free at https://www.omdbapi.com/apikey.aspx)

## Setup

1. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` and add your OMDB API key:
   ```
   OMDB_API_KEY=your_key_here
   ```

3. Start the application:
   ```bash
   docker compose up -d
   ```

4. Open http://localhost:8080 in your browser.

## Features

- User registration and login
- Profile settings (display name, password change)
- Search movies via OMDB API
- Add films to your collection (from search or manually)
- Rate and comment on films
- Mark films as favorites
- Filter by status (to watch / seen) and favorites
- Responsive grid layout

## API Endpoints

- `POST /api/auth/register.php` - Register
- `POST /api/auth/login.php` - Login
- `POST /api/auth/logout.php` - Logout
- `GET /api/auth/me.php` - Current user
- `GET /api/profile.php` - Get profile
- `PUT /api/profile.php` - Update profile
- `GET /api/films/index.php` - List films
- `POST /api/films/index.php` - Add film
- `GET /api/films/search.php?q=...` - Search OMDB
- `GET /api/films/film.php?id=...` - Get film
- `PUT /api/films/film.php?id=...` - Update film
- `DELETE /api/films/film.php?id=...` - Remove film
- `GET /api/favorites/index.php` - List favorites
- `POST /api/favorites/index.php` - Add favorite
- `DELETE /api/favorites/index.php` - Remove favorite
