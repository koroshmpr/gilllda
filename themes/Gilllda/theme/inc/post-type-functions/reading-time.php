<?php
function reading_time()
{
    ob_start();
    the_content();
    $content = ob_get_clean();
    $readingtime = ceil(sizeof(explode(" ", utf8_decode($content))) / 200);
    return $readingtime;
}

function reading_time_shortcode()
{
    $time = reading_time();
    return $time;
}

add_shortcode('reading_time', 'reading_time_shortcode');
