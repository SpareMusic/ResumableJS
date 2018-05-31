<?php

return [
    /**
     * Directories where chunks are stored (using local filesystem driver)
     */
    "chunks"    => [
        'chunks',
    ],

    /**
     * How old the chunks should be before delete
     */
    "timestamp" => "-1 HOUR",

    /**
     * Config for how often the clear chunks command will be run
     */
    "schedule"  => [
        "enabled" => false, // if the cron to clear chunks should be run
        "cron"    => "25 * * * *" // run every hour on the 25th minute
    ]
];