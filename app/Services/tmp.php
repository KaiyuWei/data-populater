<?php

if (function_exists('pcntl_signal')) {
    echo 'pcntl is enabled.';
} else {
    echo 'pcntl is not enabled.';
}