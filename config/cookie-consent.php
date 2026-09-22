<?php

return [

    'cookieName' => env('COOKIE_CONSENT_NAME', 'cookie_consent'),
    'cookieMaxAge' => 15552000,

    'version' => 1,

    'policyUrl' => env('COOKIE_CONSENT_POLICY_URL', '/privacy-policy'),

    'defaultLanguage' => 'en',

    'autoInject' => true,

    'colorScheme' => 'auto',    
    'backdropStyle' => 'blur',  
    'displayMode' => 'full',    
    'reopenButton' => true,
    'reopenPosition' => 'auto', 
    'gpcHidesBanner' => true,

    'analyticsCategory' => 'statistics',
    'marketingCategory' => 'marketing',

    'videoFacade' => true,
    'videoThumbnails' => true,
    'videoConsentCategory' => '',

    'inventoryFramework' => '',
    'inventoryClasses' => [],

    'categories' => null,
];
