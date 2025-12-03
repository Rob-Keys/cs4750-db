# cs4750-db
Final project database for CS 4750 at UVA

## Hosted locally
This is being run on XAMPP

All files should be put in htdocs, and the apache conf httpd.conf should be edited to set DocumentRoot to cs4750/public

## Repo structure
Public directory contains root of web server. Majority of web application lives here as html and js files. If JS makes a fetch request, it gets redirected by .htaccess to api.php, which redirects to private/database.php.

Database.php:
This file handles the DB interactions. We can split it up because its already a huge file. Maybe a controller php file that directs the request to a more specific php file. Right now, it connects to the DB, parses the request, handles DB interactions (just for searchLocations and createLocation), and then returns back to the JS for display update.