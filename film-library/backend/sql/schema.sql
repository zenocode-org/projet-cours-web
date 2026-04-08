-- Film Library Database Schema

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TYPE film_status AS ENUM ('to_watch', 'seen');

CREATE TABLE films (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    omdb_id VARCHAR(20),
    title VARCHAR(255) NOT NULL,
    year VARCHAR(10),
    poster_url VARCHAR(500),
    plot TEXT,
    genre VARCHAR(255),
    status film_status DEFAULT 'to_watch',
    rating INTEGER CHECK (rating >= 0 AND rating <= 10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, omdb_id)
);

CREATE TABLE film_favorites (
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    film_id INTEGER NOT NULL REFERENCES films(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, film_id)
);

CREATE TABLE film_comments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    film_id INTEGER NOT NULL REFERENCES films(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_films_user_id ON films(user_id);
CREATE INDEX idx_film_favorites_user_id ON film_favorites(user_id);
CREATE INDEX idx_film_comments_film_id ON film_comments(film_id);
