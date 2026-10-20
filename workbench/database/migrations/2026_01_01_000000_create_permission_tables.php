<?php

use Composer\InstalledVersions;

return require InstalledVersions::getInstallPath('spatie/laravel-permission').'/database/migrations/create_permission_tables.php.stub';
