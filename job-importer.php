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

    //Get the information from the api and store it if something is given otherwise log the error and return the function

    $api  = 'https://www.lifemark.ca/api/json/adp-jobs';
    $response = wp_remote_get($api);


    //Decode the json response
    $jobsDecoded = json_decode(wp_remote_retrieve_body($response), true);
    $jobs = $jobsDecoded['nodes'];


    //Basic wp query for getting the existing jobs
    $existing_jobs = get_posts([
        'post_type' => 'posts',
        'numberposts' => -1,
    ]);

    $fetched_ids = [];

    //Loop through the existing jobs and store the ids in the fetched_ids array
    foreach($jobs as $job) {

        //Limit the results to only the english ones
        if($job['langCode'] !== 'en_CA') {
            continue;
        }

        $fetched_ids[] = $job['jobId'];

        //Check if a post exists already
        $existing_post_id = array_search($job['id'], $existing_jobs);

        
    }


}