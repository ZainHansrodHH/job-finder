<?php

/**
 * Plugin Name: Job Importer
 * Description: Imports jobs from a JSON feed as WordPress posts.
 * Version: 1.0.0
 * Author: Zain Hansrod
 * Text Domain: job-importer
 */

 if (!defined('ABSPATH')) {
    exit; 
}

function job_import() {
    $api  = 'https://www.lifemark.ca/api/json/adp-jobs';
    $response = wp_remote_get($api);

    if(is_wp_error($response)) {
        error_log($response->get_error_message());
        return;
    }

    
}