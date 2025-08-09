#!/bin/bash

echo "Deploying Raptor..."

git stash
git pull

npm install
npm run build

php artisan config:cache
php artisan migrate --force

php artisan queue:restart

echo "Raptor deployed successfully!"
