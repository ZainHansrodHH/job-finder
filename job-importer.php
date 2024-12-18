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
        'fields' => 'ids',
    ]);

    $fetched_ids = [];

    //Loop through the existing jobs and store the ids in the fetched_ids array
    foreach($jobs as $job) {

        //Limit the results to only the english ones
        if($job['langCode'] !== 'en_CA') {
            continue;
        }

        $list_id = $job['itemId'];
        $fetched_ids[] = $list_id;

        //Check if a post exists already
        $existing_post_id = array_search($list_id, $existing_jobs);

        //Build out the post data we want to insert or update. We add all the metadat associatd with it too. We set the item_id so that we can cross reference them when checking if they exists when the job runs again 

        //TODO: I would sanitize and check these fields before inserting them

        $post_data = [
            'post_title' => $job['jobTitle'],
            'post_content' => $job['link'],
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

        foreach($existing_jobs as $existing_job) {
            if(!in_array($existing_job, $fetched_ids)) {
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
        import_jobs();
        echo '<div class="updated"><p>Jobs imported successfully!</p></div>';
    }

    echo '<h1>Job Importer</h1>';
    echo '<form method="post"><button name="run_import" class="button button-primary">Run Import</button></form>';
}


//Schedule the job to run everyday

if (!wp_next_scheduled('daily_job_import')) {
    wp_schedule_event(time(), 'daily', 'daily_job_import');
}

add_action('daily_job_import', 'job_import');
