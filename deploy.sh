#!/bin/bash

git stash
git pull

npm install
npm run build

php artisan config:cache
php artisan migrate --force
