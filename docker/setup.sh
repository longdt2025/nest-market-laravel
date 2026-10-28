#!/bin/sh
set -eu
composer install --no-interaction --prefer-dist
php docker/initialize.php
