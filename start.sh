#!/bin/bash
# Heroku provides the port to bind to via the $PORT environment variable.
# We replace the placeholder port in the Apache config with the one from Heroku.
sed -i "s/\*:80/*:$PORT/g" /etc/apache2/sites-available/000-default.conf

# Start Apache in the foreground
apache2-foreground