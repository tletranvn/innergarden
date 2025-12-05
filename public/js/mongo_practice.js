
/*
 * MONGODB PRACTICE QUERIES FOR 'activity_logs' COLLECTION
 * 1. Connect to MongoDB:
 *    docker exec -it innergarden_mongodb_new mongosh --username root --password rootpassword --authenticationDatabase admin
 * 
 * 2. Switch to the database:
 *    use innergarden_mongodb
 */

// Create a collection :
db.createCollection("activity_logs")

// Insert a single activity log manually
db.activity_logs.insertOne({
    "userId": "1",
    "userEmail": "tenten@email.com",
    "userRoles": ["ROLE_USER", "ROLE_ADMIN"],
    "articleId": 101,
    "articleTitle": "How to Grow Tomatoes",
    "action": "view",
    "timestamp": new Date(),
    "metadata": {
        "ip": "192.168.1.1",
        "browser": "Chrome"
    }
});

// Insert multiple logs at once
db.activity_logs.insertMany([
    {
        "userId": "2",
        "userEmail": "alice@email.com",
        "action": "create",
        "articleTitle": "My First Garden",
        "timestamp": new Date("2023-10-01T10:00:00Z")
    },
    {
        "userId": "3",
        "userEmail": "bob@email.com",
        "action": "view",
        "articleTitle": "How to Grow Tomatoes",
        "timestamp": new Date("2023-10-01T11:30:00Z")
    }
]);

// Find ALL logs (formatted nicely)
db.activity_logs.find().pretty();

// Find logs for a specific user
db.activity_logs.find({ "userEmail": "tenten@email.com" });

// Find logs with a specific action ('view')
db.activity_logs.find({ "action": "view" });

// Find logs where articleId is greater than 100
db.activity_logs.find({ "articleId": { $gt: 100 } });

// Find logs AND sort by timestamp (newest first)
db.activity_logs.find().sort({ "timestamp": -1 });

// Find logs but ONLY show email and action (hide id)
db.activity_logs.find({}, { "userEmail": 1, "action": 1, "_id": 0 });



// Update a specific log to add more metadata
db.activity_logs.updateOne(
    { "userEmail": "tenten@email.com" }, // Filter
    { $set: { "metadata.device": "MacBook Pro" } } // Update
);

// Update ALL logs with action 'view' to have a new field 'viewed_at_home' = true
db.activity_logs.updateMany(
    { "action": "view" },
    { $set: { "viewed_at_home": true } }
);


// Delete a specific log by email
db.activity_logs.deleteOne({ "userEmail": "bob@email.com" });

// Delete ALL logs that are older than a specific date
db.activity_logs.deleteMany({
    "timestamp": { $lt: new Date("2023-01-01") }
});


// Count how many logs exist for each action type (view, create, edit...)
db.activity_logs.aggregate([
    { $group: { _id: "$action", count: { $sum: 1 } } }
]);

// Find the most active user (by email)
db.activity_logs.aggregate([
    { $group: { _id: "$userEmail", total_activities: { $sum: 1 } } },
    { $sort: { total_activities: -1 } },
    { $limit: 1 }
]);



