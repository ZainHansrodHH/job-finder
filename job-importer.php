<?php

/**
 * Plugin Name: Job Importer
 * Description: Imports jobs from a JSON feed as WordPress posts.
 * Version: 1.0.2
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

    //TODO: I would add more error handling here to check if the response is valid and if the api is up


    //Decode the json response
    $jobsDecoded = json_decode(wp_remote_retrieve_body($response), true);
    $jobs = $jobsDecoded['nodes'];

    //TODO: I would add more error handling here to check the structure of the JSON and ensure it has the fields we are looking for and also is valid


    //Basic wp query for getting the existing jobs
    $existing_jobs = get_posts([
        'post_type' => 'post',
        'numberposts' => -1,
        'fields' => 'ids',
    ]);

    //TODO: I would add error handling here to check the results of the posts returend from the query 

    $fetched_ids = [];

    //Loop through the existing jobs and store the ids in the fetched_ids array
    foreach($jobs as $job) {

        //Limit the results to only the english ones
        if($job['langCode'] !== 'en_CA') {
            continue;
        }

        $list_id = $job['nid'];
        $fetched_ids[] = $list_id;

        //Check if a post exists already
        $existing_post_id = array_search($list_id, $existing_jobs);

        //Build out the post data we want to insert or update. We add all the metadat associatd with it too. We set the item_id so that we can cross reference them when checking if they exists when the job runs again 

        //TODO: I would sanitize and check these fields before inserting them and also check each field before inserting it to ensure it is in the right format and also is valid

        $post_data = [
            'post_title' => $job['jobTitle'],
            'post_content' => 'Link: ' . $job['link'] . ' City:' . $job['jobCity'] . ' Added Date:' . $job['jobAddedDate'],
            'post_type' => 'post',
            'post_status'  => 'publish',
            'meta_input'   => [
                'item_id'     => $list_id,
                'job_city'    => $job['jobCity'],
                'added_date'  => $job['jobAddedDate'],
                'job_link'    => esc_url($job['link']),
            ],
        ]; 

        //Check if the post exists and update it if it does otherwise insert it

        if($existing_post_id) {
            $post_data['ID'] = $existing_post_id;
            wp_update_post($post_data);
        } else {
            wp_insert_post($post_data);
        }

        //Delete jobs that have not been returned in the api call (meaning they don't exist anymore)

        error_log(print_r($existing_jobs, true));

        foreach($existing_jobs as $existing_job) {
            $metaId = get_post_meta($existing_job, 'nid', true);
            if(!in_array($metaId, $fetched_ids)) {
                wp_delete_post($existing_job, true);
            }
        }

        
    }
}

//Create the admin page 

function job_importer_menu() {
    add_menu_page('Job Importer', 'Job Importer', 'manage_options', 'job-importer', 'job_importer_page', 'dashicons-download', 6);
}

add_action('admin_menu', 'job_importer_menu');

//Create the admin page content
function job_importer_page() {
    //Check if the form has been submitted
    if (isset($_POST['run_import'])) {
        job_import();
        echo '<div class="updated"><p>Jobs imported successfully!</p></div>';
    }

    echo '<h1>Job Importer</h1>';
    echo '<form method="post"><button name="run_import" class="button button-primary">Run Import</button></form>';
}


//Schedule the job to run everyday
//TODO: I would add more error handling here to check if the job is scheduled and ensure it is running correctly as well as ensuring the schedule is running

if (!wp_next_scheduled('daily_job_import')) {
    wp_schedule_event(time(), 'daily', 'daily_job_import');
}

add_action('daily_job_import', 'job_import');

//Clear the job when the plugin is deactivated
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('daily_job_import');
});