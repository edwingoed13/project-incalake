<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Permissions Configuration
    |--------------------------------------------------------------------------
    |
    | Define what each role can access in the admin panel.
    | Permissions are organized by resource and action.
    |
    */

    'roles' => [
        'admin' => [
            'label' => 'Administrador',
            'description' => 'Acceso completo al sistema',
            'permissions' => [
                // Dashboard
                'dashboard.view' => true,
                'dashboard.analytics' => true,

                // Tours
                'tours.view' => true,
                'tours.create' => true,
                'tours.edit' => true,
                'tours.delete' => true,
                'tours.clone' => true,

                // Bookings
                'bookings.view' => true,
                'bookings.create' => true,
                'bookings.edit' => true,
                'bookings.delete' => true,
                'bookings.confirm' => true,
                'bookings.cancel' => true,

                // Categories
                'categories.view' => true,
                'categories.create' => true,
                'categories.edit' => true,
                'categories.delete' => true,

                // Languages
                'languages.view' => true,
                'languages.create' => true,
                'languages.edit' => true,
                'languages.delete' => true,

                // Users
                'users.view' => true,
                'users.create' => true,
                'users.edit' => true,
                'users.delete' => true,

                // Settings
                'settings.view' => true,
                'settings.edit' => true,
                'settings.payment' => true,
                'settings.ai' => true,
            ],
        ],

        'staff' => [
            'label' => 'Staff',
            'description' => 'Gestión de tours y reservas',
            'permissions' => [
                // Dashboard
                'dashboard.view' => true,
                'dashboard.analytics' => false,

                // Tours
                'tours.view' => true,
                'tours.create' => true,
                'tours.edit' => true,
                'tours.delete' => false, // Solo admin puede eliminar
                'tours.clone' => true,

                // Bookings
                'bookings.view' => true,
                'bookings.create' => true,
                'bookings.edit' => true,
                'bookings.delete' => false,
                'bookings.confirm' => true,
                'bookings.cancel' => true,

                // Categories
                'categories.view' => true,
                'categories.create' => false,
                'categories.edit' => false,
                'categories.delete' => false,

                // Languages
                'languages.view' => true,
                'languages.create' => false,
                'languages.edit' => false,
                'languages.delete' => false,

                // Users
                'users.view' => false,
                'users.create' => false,
                'users.edit' => false,
                'users.delete' => false,

                // Settings
                'settings.view' => false,
                'settings.edit' => false,
                'settings.payment' => false,
                'settings.ai' => false,
            ],
        ],

        'customer' => [
            'label' => 'Cliente',
            'description' => 'Solo ver sus propias reservas',
            'permissions' => [
                // Dashboard
                'dashboard.view' => false,
                'dashboard.analytics' => false,

                // All admin features disabled for customers
                'tours.view' => false,
                'bookings.view' => false, // Solo sus propias reservas en frontend
                'categories.view' => false,
                'languages.view' => false,
                'users.view' => false,
                'settings.view' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Define which routes are accessible by which roles.
    | Routes not listed here are public or require authentication only.
    |
    */

    'routes' => [
        'admin.dashboard' => ['admin', 'staff'],
        'admin.tours.*' => ['admin', 'staff'],
        'admin.bookings.*' => ['admin', 'staff'],
        'admin.categories.*' => ['admin'],
        'admin.languages.*' => ['admin'],
        'admin.users.*' => ['admin'],
        'admin.settings.*' => ['admin'],
    ],
];
