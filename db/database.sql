DROP DATABASE IF EXISTS lamp_db;
CREATE DATABASE lamp_db CHARSET utf8mb4;
USE lamp_db;

CREATE TABLE users (
  id int(11) NOT NULL auto_increment,
  name varchar(100) NOT NULL,
  age int(3) NOT NULL,
  email varchar(100) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE weather_locations (
  id int(11) NOT NULL AUTO_INCREMENT,
  city varchar(100) NOT NULL,
  country_code char(2) DEFAULT NULL,
  state varchar(100) DEFAULT NULL,
  lat decimal(10,7) NOT NULL,
  lon decimal(10,7) NOT NULL,
  normalized_query varchar(150) NOT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_weather_locations_normalized_query (normalized_query),
  KEY idx_weather_locations_city (city),
  KEY idx_weather_locations_country_state (country_code, state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE weather_current (
  location_id int(11) NOT NULL,
  temperature decimal(5,2) NOT NULL,
  feels_like decimal(5,2) NOT NULL,
  humidity int(3) NOT NULL,
  pressure int(5) NOT NULL,
  description varchar(255) NOT NULL,
  icon varchar(10) NOT NULL,
  wind_speed decimal(5,2) NOT NULL,
  observed_at datetime NOT NULL,
  fetched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (location_id),
  CONSTRAINT fk_weather_current_location
    FOREIGN KEY (location_id) REFERENCES weather_locations (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE weather_hourly (
  location_id int(11) NOT NULL,
  forecast_at datetime NOT NULL,
  temperature decimal(5,2) NOT NULL,
  description varchar(255) NOT NULL,
  icon varchar(10) NOT NULL,
  humidity int(3) NOT NULL,
  wind_speed decimal(5,2) NOT NULL,
  fetched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (location_id, forecast_at),
  KEY idx_weather_hourly_fetched_at (fetched_at),
  CONSTRAINT fk_weather_hourly_location
    FOREIGN KEY (location_id) REFERENCES weather_locations (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE weather_daily (
  location_id int(11) NOT NULL,
  forecast_date date NOT NULL,
  temp_min decimal(5,2) NOT NULL,
  temp_max decimal(5,2) NOT NULL,
  description varchar(255) NOT NULL,
  icon varchar(10) NOT NULL,
  fetched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (location_id, forecast_date),
  KEY idx_weather_daily_fetched_at (fetched_at),
  CONSTRAINT fk_weather_daily_location
    FOREIGN KEY (location_id) REFERENCES weather_locations (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE weather_search_history (
  id int(11) NOT NULL AUTO_INCREMENT,
  city_query varchar(150) NOT NULL,
  view_type varchar(20) NOT NULL,
  resolved_city varchar(200) NOT NULL,
  searched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_weather_search_history_searched_at (searched_at),
  KEY idx_weather_search_history_view_type (view_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;