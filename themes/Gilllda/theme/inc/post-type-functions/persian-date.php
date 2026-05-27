<?php
function gregorian_to_jalali($gy, $gm, $gd) {
	$g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	$j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

	if ($gy > 1600) {
		$jy = 979;
		$gy -= 1600;
	} else {
		$jy = 0;
		$gy -= 621;
	}

	$gy2 = ($gm > 2) ? ($gy + 1) : $gy;
	$days = (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) - 80 + $gd;

	for ($i = 0; $i < ($gm - 1); ++$i) {
		$days += $g_days_in_month[$i];
	}

	$jy += 33 * (int)($days / 12053);
	$days %= 12053;
	$jy += 4 * (int)($days / 1461);
	$days %= 1461;

	if ($days > 365) {
		$jy += (int)(($days - 1) / 365);
		$days = ($days - 1) % 365;
	}

	for ($i = 0; ($i < 11) && ($days >= $j_days_in_month[$i]); ++$i) {
		$days -= $j_days_in_month[$i];
	}

	$jm = $i + 1;
	$jd = $days + 1;

	return array($jy, $jm, $jd);
}

function shamsi_date($format, $timestamp = null) {
	if (!$timestamp) {
		$timestamp = time();
	}

	$g_date = date("Y-m-d", $timestamp);
	list($gy, $gm, $gd) = explode('-', $g_date);
	list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);

	// Array of Persian month names
	$persian_months = [
		'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
		'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
	];

	// Replace year, month, and day in the format string
	$formatted_date = str_replace('Y', $jy, $format);
	$formatted_date = str_replace('m', str_pad($jm, 2, '0', STR_PAD_LEFT), $formatted_date);
	$formatted_date = str_replace('d', str_pad($jd, 2, '0', STR_PAD_LEFT), $formatted_date);

	// Replace 'F' with the Persian month name if specified in the format
	if (str_contains($format, 'F')) {
		$formatted_date = str_replace('F', $persian_months[$jm - 1], $formatted_date);
	}

	return $formatted_date;
}
