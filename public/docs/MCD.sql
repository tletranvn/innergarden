CREATE TABLE `user` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `email` varchar(180) UNIQUE NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) NOT NULL,
  `pseudo` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime,
  `is_verified` boolean DEFAULT false,
  `bio` text,
  `profile_picture_url` varchar(255)
);

CREATE TABLE `category` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(255) UNIQUE NOT NULL,
  `description` text
);

CREATE TABLE `article` (
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

CREATE TABLE `comment` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL,
  `is_approved` boolean NOT NULL DEFAULT false,
  `article_id` int,
  `author_id` int NOT NULL
);

ALTER TABLE `article` ADD FOREIGN KEY (`author_id`) REFERENCES `user` (`id`);

ALTER TABLE `comment` ADD FOREIGN KEY (`author_id`) REFERENCES `user` (`id`);

ALTER TABLE `article` ADD FOREIGN KEY (`category_id`) REFERENCES `category` (`id`);

ALTER TABLE `comment` ADD FOREIGN KEY (`article_id`) REFERENCES `article` (`id`);
