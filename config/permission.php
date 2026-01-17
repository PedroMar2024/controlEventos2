<?php

return [
    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,  // Modelo de permisos
        'role' => Spatie\Permission\Models\Role::class,              // Modelo de roles
    ],

    'table_names' => [
        'roles' => 'roles',                                 // Nombre de tabla de roles
        'permissions' => 'permissions',                     // Nombre de tabla de permisos
        'model_has_permissions' => 'model_has_permissions', // Tabla pivot usuario-permiso
        'model_has_roles' => 'model_has_roles',            // Tabla pivot usuario-rol
        'role_has_permissions' => 'role_has_permissions',   // Tabla pivot rol-permiso
    ],

    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'store' => 'default',
    ],

    'teams' => false,  // Para multi-tenancy (false = desactivado)
    
    'display_permission_in_exception' => false,  // Mostrar permisos en errores
    'display_role_in_exception' => false,        // Mostrar roles en errores
];