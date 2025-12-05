CREATE TABLE user (
  id int AUTO_INCREMENT,
  email varchar(180) NOT NULL,
  roles json NOT NULL,
  password varchar(255) NOT NULL,
  pseudo varchar(50) NOT NULL,
  created_at datetime NOT NULL,
  updated_at datetime,
  is_verified boolean DEFAULT false,
  bio text,
  profile_picture_url varchar(255),
  CONSTRAINT PK_user PRIMARY KEY (id),
  CONSTRAINT UQ_user_email UNIQUE (email)
);

CREATE TABLE category (
  id int AUTO_INCREMENT,
  name varchar(150) NOT NULL,
  slug varchar(255) NOT NULL,
  description text,
  CONSTRAINT PK_category PRIMARY KEY (id),
  CONSTRAINT UQ_category_slug UNIQUE (slug)
);

CREATE TABLE article (
  id int AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  slug varchar(255) NOT NULL,
  content text NOT NULL,
  excerpt text,
  created_at datetime NOT NULL,
  updated_at datetime,
  published_at datetime,
  is_published boolean NOT NULL DEFAULT false,
  image_url varchar(255),
  view_count int NOT NULL DEFAULT 0,
  category_id int NOT NULL,
  author_id int NOT NULL,
  CONSTRAINT PK_article PRIMARY KEY (id),
  CONSTRAINT UQ_article_slug UNIQUE (slug)
);

CREATE TABLE comment (
  id int AUTO_INCREMENT,
  comment text NOT NULL,
  created_at datetime NOT NULL,
  is_approved boolean NOT NULL DEFAULT false,
  article_id int,
  author_id int NOT NULL,
  CONSTRAINT PK_comment PRIMARY KEY (id)
);

ALTER TABLE article ADD CONSTRAINT FK_article_author FOREIGN KEY (author_id) REFERENCES user (id);
ALTER TABLE article ADD CONSTRAINT FK_article_category FOREIGN KEY (category_id) REFERENCES category (id);

ALTER TABLE comment ADD CONSTRAINT FK_comment_author FOREIGN KEY (author_id) REFERENCES user (id);
ALTER TABLE comment ADD CONSTRAINT FK_comment_article FOREIGN KEY (article_id) REFERENCES article (id);

