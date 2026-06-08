<?php
preg_match('/(?:rp|idr|rp\.|rp\s)\s*([\d\.,\s]+)/i', 'Nominal Rp190.000', $matches);
var_dump($matches);
