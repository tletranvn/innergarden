// MongoDB Initialization Script for innergarden project

// Switch to admin database and create root user
db = db.getSiblingDB('admin');
db.createUser({
  user: 'root',
  pwd: 'root',
  roles: ['root']
});

// Create the innergarden_mongodb database and a user for it
db = db.getSiblingDB('innergarden_mongodb');
db.createUser({
  user: 'innergarden_user',
  pwd: 'innergarden_password',
  roles: [
    {
      role: 'readWrite',
      db: 'innergarden_mongodb'
    }
  ]
});

// Create a test collection with some sample data
db.articles.insertMany([
  {
    title: "Article de test 1",
    content: "Contenu de l'article de test 1",
    author: "Auteur Test",
    createdAt: new Date(),
    category: "test"
  },
  {
    title: "Article de test 2", 
    content: "Contenu de l'article de test 2",
    author: "Auteur Test 2",
    createdAt: new Date(),
    category: "development"
  }
]);

db.comments.insertMany([
  {
    articleId: "test-article-1",
    author: "Commentateur 1",
    content: "Ceci est un commentaire de test",
    createdAt: new Date()
  },
  {
    articleId: "test-article-1", 
    author: "Commentateur 2",
    content: "Un autre commentaire de test",
    createdAt: new Date()
  }
]);

print('MongoDB initialization completed for innergarden project');
