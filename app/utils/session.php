<?php
// utils/Session.php

namespace Utils;

class Session {
    
    /**
     * Iniciar sesión si no está iniciada
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Guardar un valor en sesión
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Obtener un valor de sesión
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Verificar si existe una clave en sesión
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Eliminar una clave de sesión
     */
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Destruir toda la sesión (logout)
     */
    public static function destroy() {
        self::start();
        session_unset();
        session_destroy();
        session_write_close();
    }
    
    /**
     * Guardar mensaje temporal (se elimina después de leerlo)
     */
    public static function setFlash($key, $message) {
        self::set("flash_$key", $message);
    }
    
    /**
     * Obtener y eliminar mensaje temporal
     */
    public static function getFlash($key) {
        $message = self::get("flash_$key");
        self::remove("flash_$key");
        return $message;
    }
    
    /**
     * Verificar si el usuario está logueado
     */
    public static function isLoggedIn() {
        return self::has('user_id');
    }
    
    /**
     * Obtener el rol del usuario
     */
    public static function getUserRole() {
        return self::get('user_role');
    }
    
    /**
     * Requerir que el usuario esté logueado
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            self::setFlash('error', 'Debes iniciar sesión para acceder a esta página');
            header('Location: index.php?page=login');
            exit();
        }
    }
    
    /**
     * Requerir un rol específico
     */
    public static function requireRole($role) {
        self::requireLogin();
        
        $userRole = self::getUserRole();
        if ($userRole !== $role) {
            self::setFlash('error', 'No tienes permisos para acceder a esta página');
            header('Location: index.php?page=' . ($userRole === 'admin' ? 'admin' : 'estudiante'));
            exit();
        }
    }
    
    /**
     * Obtener ID del usuario
     */
    public static function getUserId() {
        return self::get('user_id');
    }
    
    /**
     * Obtener nombre del usuario
     */
    public static function getUserName() {
        return self::get('user_name');
    }
    
    /**
     * Obtener email del usuario
     */
    public static function getUserEmail() {
        return self::get('user_email');
    }
    
    /**
     * Verificar si tiene un permiso específico
     */
    public static function hasPermission($permission) {
        $userRole = self::getUserRole();
        $permissions = self::getRolePermissions($userRole);
        return in_array($permission, $permissions);
    }
    
    /**
     * Permisos por rol (puedes ampliar esto)
     */
    private static function getRolePermissions($role) {
        $permissions = [
            'admin' => [
                'manage_users', 'manage_careers', 'manage_subjects',
                'manage_schedules', 'view_reports', 'view_audit'
            ],
            'estudiante' => [
                'view_subjects', 'enroll_subjects', 'view_grades'
            ]
        ];
        
        return $permissions[$role] ?? [];
    }
}
?>