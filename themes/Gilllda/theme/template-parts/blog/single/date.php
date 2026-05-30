<?php
// 1. Get the post/comment time using WordPress's synchronized timestamp
$postDate = $args['time'] ?? get_the_time('U');

// 2. Use current_time('timestamp') instead of date('U')
// This ensures 'now' is in the same timezone as your WordPress settings
$now = current_time('timestamp');

$gap = $now - $postDate;
$showingDate = '';

// Handle cases where gap might be negative due to slight server sync delays
if ($gap < 0) {
    $gap = 0;
}
if ($gap < 100) {
    $showingDate = ' <span class="text-xs">همین الان</span>';
}
elseif ($gap < 3600) {
    // Less than 1 hour: show minutes
    $minutes = floor($gap / 60);
    $showingDate = $minutes . ' <span class="text-xs">دقیقه پیش</span>';
}
elseif ($gap < 86400) {
    // Less than 24 hours: show hours
    $hour = floor($gap / 3600);
    $showingDate = $hour . ' <span class="text-xs">ساعت پیش</span>';
}
elseif ($gap < 604800) {
    // Less than 7 days: show days
    $day = floor($gap / 86400);
    $showingDate = $day . ' <span class="text-xs">روز پیش</span>';
}
else {
    // Older than a week: show Shamsi date
    // Note: we echo here directly because $showingDate won't be used
    echo shamsi_date('d F, Y', $postDate);
    return; // Stop execution so we don't echo $showingDate below
}

echo $showingDate;