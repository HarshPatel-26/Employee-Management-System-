<?php
require_once __DIR__ . '/lib/env.php';

// Database configuration
return [
	'db_host' => getenv('APP_DB_HOST') ?: '127.0.0.1',
	'db_name' => getenv('APP_DB_NAME') ?: 'company_portal',
	'db_user' => getenv('APP_DB_USER') ?: 'root',
	'db_pass' => getenv('APP_DB_PASS') ?: '',
	'db_port' => getenv('APP_DB_PORT') ?: '3306',
	'app_name' => 'Company Portal',
	// Firebase Auth (Google sign-in) settings (read from .env)
	'firebase_api_key' => getenv('VITE_FIREBASE_API_KEY') ?: '',
	'firebase_auth_domain' => getenv('VITE_FIREBASE_AUTH_DOMAIN') ?: '',
	'firebase_project_id' => getenv('VITE_FIREBASE_PROJECT_ID') ?: '',
	'firebase_storage_bucket' => getenv('VITE_FIREBASE_STORAGE_BUCKET') ?: '',
	'firebase_messaging_sender_id' => getenv('VITE_FIREBASE_MESSAGING_SENDER_ID') ?: '',
	'firebase_app_id' => getenv('VITE_FIREBASE_APP_ID') ?: '',
	'coingecko_api_key' => getenv('VITE_COINGECKO_API_KEY') ?: '',
	'admin_default_email' => 'admin@example.com',
	'admin_default_password' => 'Admin@123',
	'boss_default_email' => 'boss@example.com',
	'boss_default_password' => 'Boss@123',
	'version' => '2.0.0',
	'timezone' => 'America/New_York',
];
