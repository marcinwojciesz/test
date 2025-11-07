-- Struktura bazy danych dla CMS Portal - COMPATIBLE version
SET FOREIGN_KEY_CHECKS=0;

-- Tabela użytkowników
CREATE TABLE IF NOT EXISTS uzytkownicy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    haslo VARCHAR(255) NOT NULL,
    imie VARCHAR(50),
    nazwisko VARCHAR(50),
    data_rejestracji DATETIME DEFAULT CURRENT_TIMESTAMP,
    ostatnie_logowanie DATETIME NULL,
    aktywny TINYINT DEFAULT 1
);

-- Tabela ról
CREATE TABLE IF NOT EXISTS role (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nazwa VARCHAR(50) UNIQUE NOT NULL,
    opis TEXT
);

-- Tabela przypisania ról do użytkowników
CREATE TABLE IF NOT EXISTS uzytkownicy_role (
    uzytkownik_id INT,
    rola_id INT,
    PRIMARY KEY (uzytkownik_id, rola_id)
);

-- Tabela stron
CREATE TABLE IF NOT EXISTS strony (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tytul VARCHAR(255) NOT NULL,
    tresc LONGTEXT,
    slug VARCHAR(255) UNIQUE,
    meta_tytul VARCHAR(255),
    meta_opis TEXT,
    meta_slowa_kluczowe TEXT,
    stworzyl_uzytkownik_id INT,
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_modyfikacji DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'szkic',
    poziom_dostepu VARCHAR(20) DEFAULT 'publiczny'
);

-- Tabela menu
CREATE TABLE IF NOT EXISTS menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nazwa VARCHAR(100) NOT NULL,
    typ VARCHAR(20) DEFAULT 'glowne',
    struktura TEXT,
    stworzyl_uzytkownik_id INT,
    data_utworzenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    aktywny TINYINT DEFAULT 1
);

-- Tabela dedykowanych stron użytkowników
CREATE TABLE IF NOT EXISTS dedykowane_strony (
    uzytkownik_id INT,
    strona_id INT,
    PRIMARY KEY (uzytkownik_id, strona_id)
);

-- Tabela dedykowanych menu użytkowników
CREATE TABLE IF NOT EXISTS dedykowane_menu (
    uzytkownik_id INT,
    menu_id INT,
    PRIMARY KEY (uzytkownik_id, menu_id)
);

-- Wstawianie podstawowych ról
INSERT IGNORE INTO role (id, nazwa, opis) VALUES 
(1, 'administrator', 'Pełny dostęp do systemu'),
(2, 'autor', 'Może tworzyć i edytować strony'),
(3, 'autor_menu', 'Może tworzyć i zarządzać menu'),
(4, 'autor_dostepow', 'Może zarządzać dostępami użytkowników');

-- Tworzenie konta administratora (hasło: admin123)
INSERT IGNORE INTO uzytkownicy (id, login, email, haslo, imie, nazwisko) VALUES 
(1, 'admin', 'admin@localhost', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'Systemowy');

-- Przypisanie roli administratora
INSERT IGNORE INTO uzytkownicy_role (uzytkownik_id, rola_id) VALUES (1, 1);

SET FOREIGN_KEY_CHECKS=1;