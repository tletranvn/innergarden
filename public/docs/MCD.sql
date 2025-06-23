CREATE TABLE `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `pseudo` varchar(255) NOT NULL,
  `email` varchar(255) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `roles` json NOT NULL DEFAULT '["ROLE_USER"]',
  `created_at` datetime NOT NULL DEFAULT 'now()',
  `updated_at` datetime,
  `is_verified` boolean DEFAULT false,
  `bio` text,
  `profile_picture_url` varchar(255)
);

CREATE TABLE `articles` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) UNIQUE NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text,
  `author_id` int NOT NULL,
  `category_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT 'now()',
  `updated_at` datetime,
  `published_at` datetime,
  `is_published` boolean NOT NULL DEFAULT false,
  `image_url` varchar(255),
  `views_count` int DEFAULT 0
);

CREATE TABLE `categories` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255) UNIQUE NOT NULL,
  `slug` varchar(255) UNIQUE NOT NULL,
  `description` text
);

CREATE TABLE `commentaires` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT 'now()',
  `is_approved` boolean DEFAULT false,
  `parent_comment_id` int
);

ALTER TABLE `articles` ADD FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

ALTER TABLE `articles` ADD FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

ALTER TABLE `commentaires` ADD FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

ALTER TABLE `commentaires` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `commentaires` ADD FOREIGN KEY (`parent_comment_id`) REFERENCES `commentaires` (`id`);
