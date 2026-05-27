<?php
$timer = $args['timer'] ?? '00:00:00';
// Pass the target time as a string to JS
$boxClass = 'min-w-10 aspect-square bg-icon text-black border font-bold text-lg border-white/10 rounded-lg p-1 flex flex-col justify-center items-center';
?>

<div class="flex justify-center items-center gap-x-1 text-center"
     x-data="{
        targetTime: '<?= $timer; ?>',
        hours: '00',
        minutes: '00',
        seconds: '00',
        countdownSeconds() {
            // Get current time in Iran (Asia/Tehran)
            let now = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Tehran' }));

            // Create target date object for today
            let target = new Date(now);
            let [tH, tM, tS] = this.targetTime.split(':');
            target.setHours(tH, tM, tS, 0);

            // Calculate distance in milliseconds
            let distance = target.getTime() - now.getTime();

            if (distance <= 0) {
                this.hours = '00'; this.minutes = '00'; this.seconds = '00';
                return;
            }

            this.hours = String(Math.floor(distance / (1000 * 60 * 60))).padStart(2, '0');
            this.minutes = String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
            this.seconds = String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, '0');
        }
     }"
     x-init="countdownSeconds(); setInterval(() => countdownSeconds(), 1000)">

    <div x-text="seconds" class="<?= $boxClass; ?>">00</div>
    <span class="animate-pulse">:</span>
    <div x-text="minutes" class="<?= $boxClass; ?>">00</div>
    <span class="animate-pulse">:</span>
    <div x-text="hours" class="<?= $boxClass; ?>">00</div>
</div>