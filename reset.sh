#!/bin/bash


rm -rf storage/app/public/thumbnails/*

# Remove all files in the public/hls directory
rm -rf storage/app/public/videos/hls/*

# Remove all files in the public/chunks directory

rm -rf storage/app/public/videos/chunks/*

#Remove all original videos
rm -rf storage/app/public/videos/original/*

# Remove the database file
rm database/database.sqlite

# Run the migrations
php artisan migrate
php artisan app:create-user