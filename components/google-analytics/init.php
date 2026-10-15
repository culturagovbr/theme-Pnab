<?php

$this->jsObject['config']['googleAnalytics'] = [
    'trackingId' => trim((string) env('PNAB_GOOGLE_ANALYTICS_ID', '')),
];
