<?php

return [
    // All students and lecturers using this platform are at a single
    // institution in Malaysia. HTML `datetime-local` inputs (used for the
    // case "Opens at"/"Closes at" scheduling fields) submit timezone-naive
    // local wall-clock time — with the app defaulting to UTC, a lecturer
    // picking "now" in the browser's local time got stored ~8 hours ahead
    // of the real time, making freshly-published cases stay hidden from
    // students for hours. Running the whole app on local time instead
    // (rather than converting every date field individually) fixes that
    // at the source and keeps every other date/time in the app consistent
    // with what users actually typed and expect to see.
    'timezone' => env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),
];
