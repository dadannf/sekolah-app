<?php
require 'vendor/autoload.php';
\ = '4 april 2020 20:32';
try {
    echo \Carbon\Carbon::parse(\)->format('Y-m-d H:i:s');
} catch (Exception \) {
    echo 'Error: ' . \->getMessage();
}
