<?php
/**
 * Global configuration settings for the University Networking Portal.
 * Adjust these values to match your environment.
 */

declare(strict_types=1);

// Database connection details.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'university_networking_portal';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

// Application settings.
const APP_NAME = 'University Networking Portal';
const BASE_PATH = __DIR__ . '/../public';
const BASE_URL = '/unp/public';
const SESSION_COOKIE_NAME = 'unp_session';

// Security settings.
const PASSWORD_HASH_ALGO = PASSWORD_DEFAULT;
const CSRF_TOKEN_TTL = 7200; // 2 hours
