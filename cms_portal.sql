-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Czas generowania: 07 Lis 2025, 13:05
-- Wersja serwera: 10.4.27-MariaDB
-- Wersja PHP: 8.1.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Baza danych: `cms_portal`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `dedykowane_menu`
--

CREATE TABLE `dedykowane_menu` (
  `uzytkownik_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `dedykowane_strony`
--

CREATE TABLE `dedykowane_strony` (
  `uzytkownik_id` int(11) NOT NULL,
  `strona_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(100) NOT NULL,
  `typ` varchar(20) DEFAULT 'glowne',
  `struktura` text DEFAULT NULL,
  `stworzyl_uzytkownik_id` int(11) DEFAULT NULL,
  `data_utworzenia` datetime DEFAULT current_timestamp(),
  `aktywny` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `nazwa` varchar(50) NOT NULL,
  `opis` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `role`
--

INSERT INTO `role` (`id`, `nazwa`, `opis`) VALUES
(1, 'administrator', 'Pełny dostęp do systemu'),
(2, 'autor', 'Może tworzyć i edytować strony'),
(3, 'autor_menu', 'Może tworzyć i zarządzać menu'),
(4, 'autor_dostepow', 'Może zarządzać dostępami użytkowników');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `strony`
--

CREATE TABLE `strony` (
  `id` int(11) NOT NULL,
  `tytul` varchar(255) NOT NULL,
  `tresc` longtext DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `meta_tytul` varchar(255) DEFAULT NULL,
  `meta_opis` text DEFAULT NULL,
  `meta_slowa_kluczowe` text DEFAULT NULL,
  `stworzyl_uzytkownik_id` int(11) DEFAULT NULL,
  `data_utworzenia` datetime DEFAULT current_timestamp(),
  `data_modyfikacji` datetime DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'szkic',
  `poziom_dostepu` varchar(20) DEFAULT 'publiczny'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `uzytkownicy`
--

CREATE TABLE `uzytkownicy` (
  `id` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `haslo` varchar(255) NOT NULL,
  `imie` varchar(50) DEFAULT NULL,
  `nazwisko` varchar(50) DEFAULT NULL,
  `data_rejestracji` datetime DEFAULT current_timestamp(),
  `ostatnie_logowanie` datetime DEFAULT NULL,
  `aktywny` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `uzytkownicy`
--

INSERT INTO `uzytkownicy` (`id`, `login`, `email`, `haslo`, `imie`, `nazwisko`, `data_rejestracji`, `ostatnie_logowanie`, `aktywny`) VALUES
(1, 'admin', 'admin@localhost', 'admin123', 'Administrator', 'Systemowy', '2025-11-06 23:17:44', '2025-11-07 13:00:44', 1),
(2, 'test', 'test@localhost', 'admin123', 'Test', 'User', '2025-11-06 23:52:01', NULL, 1),
(3, 'user', 'user@localhost', '21232f297a57a5a743894a0e4a801fc3', 'User', 'Test', '2025-11-06 23:58:29', NULL, 1),
(4, 'simpleuser', 'simple@localhost', 'simplepass', 'Simple', 'User', '2025-11-07 00:00:35', NULL, 1);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `uzytkownicy_role`
--

CREATE TABLE `uzytkownicy_role` (
  `uzytkownik_id` int(11) NOT NULL,
  `rola_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `uzytkownicy_role`
--

INSERT INTO `uzytkownicy_role` (`uzytkownik_id`, `rola_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1);

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `dedykowane_menu`
--
ALTER TABLE `dedykowane_menu`
  ADD PRIMARY KEY (`uzytkownik_id`,`menu_id`);

--
-- Indeksy dla tabeli `dedykowane_strony`
--
ALTER TABLE `dedykowane_strony`
  ADD PRIMARY KEY (`uzytkownik_id`,`strona_id`);

--
-- Indeksy dla tabeli `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nazwa` (`nazwa`);

--
-- Indeksy dla tabeli `strony`
--
ALTER TABLE `strony`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indeksy dla tabeli `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeksy dla tabeli `uzytkownicy_role`
--
ALTER TABLE `uzytkownicy_role`
  ADD PRIMARY KEY (`uzytkownik_id`,`rola_id`);

--
-- AUTO_INCREMENT dla zrzuconych tabel
--

--
-- AUTO_INCREMENT dla tabeli `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT dla tabeli `strony`
--
ALTER TABLE `strony`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
