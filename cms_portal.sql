-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Czas generowania: 11 Lis 2025, 17:26
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
-- Struktura tabeli dla tabeli `admin_roles`
--

CREATE TABLE `admin_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `admin_roles`
--

INSERT INTO `admin_roles` (`id`, `name`, `description`, `permissions`) VALUES
(1, 'administrator', 'Pełny dostęp do systemu', '{\"all\": true}'),
(2, 'autor', 'Tworzenie i edycja stron', '{\"pages\": {\"create\": true, \"edit\": true, \"delete\": true}}'),
(3, 'autor_menu', 'Tworzenie i zarządzanie menu', '{\"menus\": {\"create\": true, \"edit\": true, \"delete\": true}}'),
(4, 'autor_dostepow', 'Przydzielanie dostępu do stron i menu', '{\"access\": {\"assign_pages\": true, \"assign_menus\": true}}');

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
-- Struktura tabeli dla tabeli `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `menu_type` enum('horizontal','vertical','dropdown') DEFAULT 'horizontal',
  `has_dropdown` tinyint(1) DEFAULT 0,
  `access_level` enum('public','logged_in','private') DEFAULT 'public',
  `structure` longtext DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `menus`
--

INSERT INTO `menus` (`id`, `name`, `menu_type`, `has_dropdown`, `access_level`, `structure`, `created_by`, `created_at`, `is_active`) VALUES
(2, 'Menu Główne', 'dropdown', 1, 'public', '[{\"id\":\"4\",\"title\":\"test3\",\"url\":\"test3\",\"custom\":false,\"children\":[{\"id\":\"sub-1762530278297\",\"title\":\"Nowa podstrona\",\"url\":\"podstrona\",\"custom\":true}]},{\"title\":\"Strona główna\",\"url\":\"\",\"custom\":\"true\",\"children\":[]},{\"id\":\"2\",\"title\":\"test\",\"url\":\"test\",\"children\":[{\"id\":\"sub-1762529556976\",\"title\":\"Nowa podstrona\",\"url\":\"podstrona\",\"custom\":true},{\"id\":\"sub-1762529577910\",\"title\":\"Nowa podstrona\",\"url\":\"podstrona\",\"custom\":true}]}]', 1, '2025-11-07 15:38:21', 1);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `access_level` enum('public','logged_in','private') DEFAULT 'public',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_published` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `meta_description`, `author_id`, `access_level`, `created_at`, `updated_at`, `is_published`) VALUES
(2, 'test', 'test', '<p>Zasady gildii REVESA :)))</p>\r\n<p><img src=\"https://revesa.pl/wp-content/uploads/2024/12/setki.png\"></p>\r\n<p>Zasady gildii są proste: Uczciwość, pomoc innym, wysoka kultura osobista w gildii, na czacie global oraz handlu &ndash; najlepiej wszędzie. Wykonywanie zadań gildyjnych w miarę sił i możliwości, co oznacza, że staramy się zawsze zrobić ZG dla dobra całej gildii, jak jesteśmy w grze i czas nam na to pozwala stawiając to za priorytet. Nazwa domeny została uzgodniona z Szefową Gildi &ndash; Dorisday</p>', '', 1, 'public', '2025-11-07 14:51:40', '2025-11-07 14:52:45', 1),
(3, 'test2', 'test2', '<p>test 2</p>', '', 1, 'public', '2025-11-07 15:39:40', '2025-11-07 15:39:40', 1),
(4, 'test3', 'test3', '<p>test3</p>', '', 1, 'public', '2025-11-07 15:39:54', '2025-11-07 15:39:54', 1),
(5, 'test4', 'test4', '<p>test4</p>', '', 1, 'public', '2025-11-07 15:40:06', '2025-11-07 15:40:06', 1);

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
-- Struktura tabeli dla tabeli `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `user_type` enum('admin','site_user') DEFAULT 'site_user',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `user_type`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$ExampleHashedPassword', 'admin@example.com', 'admin', 1, '2025-11-07 13:45:37', NULL),
(2, 'testowy', '$2y$10$WsQh9CukYldVzMjJUR4jBe6Yynd/L7.WtlJCtFlIH7HjFn/FRvnHW', 'test@o2.pl', 'admin', 1, '2025-11-07 13:55:44', NULL),
(3, 'test', '$2y$10$1ATyGKrTs7KljIGO0j07relOyeWDqzdc8lIiJUNtbPu89ElzFK4VW', 'test@l2.pl', 'site_user', 1, '2025-11-07 14:12:10', NULL);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `user_admin_roles`
--

CREATE TABLE `user_admin_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Zrzut danych tabeli `user_admin_roles`
--

INSERT INTO `user_admin_roles` (`id`, `user_id`, `role_id`, `assigned_at`) VALUES
(1, 1, 1, '2025-11-07 13:45:37'),
(2, 2, 2, '2025-11-07 14:11:39'),
(3, 2, 3, '2025-11-07 14:11:39'),
(4, 2, 4, '2025-11-07 14:11:39');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `user_menus`
--

CREATE TABLE `user_menus` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `user_pages`
--

CREATE TABLE `user_pages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp()
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
-- Indeksy dla tabeli `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD PRIMARY KEY (`id`);

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
-- Indeksy dla tabeli `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeksy dla tabeli `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_access_level` (`access_level`),
  ADD KEY `idx_is_published` (`is_published`);

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
-- Indeksy dla tabeli `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeksy dla tabeli `user_admin_roles`
--
ALTER TABLE `user_admin_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indeksy dla tabeli `user_menus`
--
ALTER TABLE `user_menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_menu` (`user_id`,`menu_id`),
  ADD KEY `menu_id` (`menu_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indeksy dla tabeli `user_pages`
--
ALTER TABLE `user_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_page` (`user_id`,`page_id`),
  ADD KEY `page_id` (`page_id`),
  ADD KEY `assigned_by` (`assigned_by`);

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
-- AUTO_INCREMENT dla tabeli `admin_roles`
--
ALTER TABLE `admin_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT dla tabeli `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT dla tabeli `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT dla tabeli `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT dla tabeli `user_admin_roles`
--
ALTER TABLE `user_admin_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT dla tabeli `user_menus`
--
ALTER TABLE `user_menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `user_pages`
--
ALTER TABLE `user_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ograniczenia dla zrzutów tabel
--

--
-- Ograniczenia dla tabeli `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ograniczenia dla tabeli `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ograniczenia dla tabeli `user_admin_roles`
--
ALTER TABLE `user_admin_roles`
  ADD CONSTRAINT `user_admin_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_admin_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE CASCADE;

--
-- Ograniczenia dla tabeli `user_menus`
--
ALTER TABLE `user_menus`
  ADD CONSTRAINT `user_menus_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_menus_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_menus_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ograniczenia dla tabeli `user_pages`
--
ALTER TABLE `user_pages`
  ADD CONSTRAINT `user_pages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_pages_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_pages_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
