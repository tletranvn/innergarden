CREATE TABLE `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `email` varchar(180) UNIQUE NOT NULL,
  `roles` json NOT NULL COMMENT 'Stores user roles as JSON array',
  `password` varchar(255) NOT NULL,
  `pseudo` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime,
  `is_verified` boolean DEFAULT false,
  `bio` text,
  `profile_picture_url` varchar(255)
);

CREATE TABLE `categories` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(255) UNIQUE NOT NULL,
  `description` text
);

CREATE TABLE `articles` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) UNIQUE NOT NULL,
  `content` text NOT NULL,
  `excerpt` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime,
  `published_at` datetime,
  `is_published` boolean NOT NULL DEFAULT false,
  `image_url` varchar(255),
  `view_count` int NOT NULL DEFAULT 0,
  `author_id` int NOT NULL,
  `category_id` int NOT NULL
);

CREATE TABLE `comments` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL,
  `is_approved` boolean NOT NULL DEFAULT false,
  `article_id` int,
  `author_id` int NOT NULL,
  `parent_comment_id` int COMMENT 'Self-referencing for replies'
);

ALTER TABLE `articles` ADD FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

ALTER TABLE `comments` ADD FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

ALTER TABLE `articles` ADD FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

ALTER TABLE `comments` ADD FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

ALTER TABLE `comments` ADD FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`id`);
