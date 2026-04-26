<?php
declare(strict_types=1);

final class AuthController extends Controller
{
    private function authModel(): AuthModel
    {
        return new AuthModel(Database::getInstance(db_config()));
    }

    private function backupService(): BackupService
    {
        return new BackupService(Database::getInstance(db_config()));
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect((string) auth_config('redirect_after_login'));
        }

        $this->view('auth/views/login', [
            'title' => 'Login',
            'errorMessage' => get_flash('error'),
            'successMessage' => get_flash('success'),
            'mfaEnabled' => (bool) (auth_config('mfa')['enabled'] ?? false),
        ], 'auth');
    }

    public function login(): void
    {
        $username = trim((string) post('username'));
        $password = (string) post('password');
        $otpCode = trim((string) post('otp_code', ''));
        $token = (string) post('_token');

        with_old_input(['username' => $username]);
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan muat ulang halaman dan coba lagi.');
            return;
        }

        $errors = [];
        if ($username === '') {
            $errors[] = 'Username wajib diisi.';
        }
        if ($password === '') {
            $errors[] = 'Password wajib diisi.';
        }
        if ($errors !== []) {
            flash('error', implode(' ', $errors));
            $this->redirect('/login');
        }

        $ipAddress = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        try {
            AuthRateLimiter::assertCanAttempt($username, $ipAddress);
        } catch (Throwable $e) {
            audit_log('Autentikasi', 'login_blocked', 'Percobaan login diblokir oleh throttle keamanan.', [
                'severity' => 'warning',
                'entity_type' => 'auth',
                'username' => $username,
                'context' => ['reason' => 'rate_limited', 'message' => $e->getMessage()],
            ]);
            flash('error', $e->getMessage());
            $this->redirect('/login');
        }

        if (!Database::isConnected(db_config())) {
            flash('error', 'Koneksi ke database belum tersedia. Periksa konfigurasi database Anda terlebih dahulu.');
            $this->redirect('/login');
        }

        $user = $this->authModel()->findByUsername($username);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $throttle = AuthRateLimiter::recordFailure($username, $ipAddress);
            audit_log('Autentikasi', 'login_failed', 'Percobaan login gagal.', [
                'severity' => 'warning',
                'entity_type' => 'auth',
                'username' => $username,
                'context' => [
                    'reason' => 'invalid_credentials',
                    'failed_attempts' => $throttle['failed_attempts'] ?? 0,
                    'lockout_seconds' => $throttle['lockout_seconds'] ?? 0,
                ],
            ]);
            $message = 'Login gagal. Periksa kembali username dan password Anda.';
            if ((int) ($throttle['lockout_seconds'] ?? 0) > 0) {
                $message .= ' Sistem mengunci percobaan login sementara karena terlalu banyak percobaan gagal.';
            }
            flash('error', $message);
            $this->redirect('/login');
        }

        if ((int) $user['is_active'] !== 1) {
            audit_log('Autentikasi', 'login_blocked', 'Percobaan login ditolak karena akun tidak aktif.', [
                'severity' => 'warning',
                'entity_type' => 'user',
                'entity_id' => (string) ($user['id'] ?? ''),
                'user_id' => (int) ($user['id'] ?? 0),
                'username' => (string) ($user['username'] ?? $username),
                'full_name' => (string) ($user['full_name'] ?? ''),
                'context' => ['reason' => 'inactive_account'],
            ]);
            flash('error', 'Akun Anda sedang tidak aktif. Silakan hubungi administrator.');
            $this->redirect('/login');
        }

        if (AuthMfa::isEnabledForUser($user) && !AuthMfa::verifyForUser($user, $otpCode)) {
            $throttle = AuthRateLimiter::recordFailure($username, $ipAddress);
            audit_log('Autentikasi', 'login_failed', 'Percobaan login gagal karena OTP MFA tidak valid.', [
                'severity' => 'warning',
                'entity_type' => 'auth',
                'username' => $username,
                'user_id' => (int) ($user['id'] ?? 0),
                'context' => [
                    'reason' => 'invalid_mfa',
                    'failed_attempts' => $throttle['failed_attempts'] ?? 0,
                    'lockout_seconds' => $throttle['lockout_seconds'] ?? 0,
                ],
            ]);
            flash('error', 'Kode OTP MFA tidak valid. Silakan periksa aplikasi authenticator Anda.');
            $this->redirect('/login');
        }

        clear_old_input();
        AuthRateLimiter::clear($username, $ipAddress);
        $this->authModel()->updateLastLogin((int) $user['id']);
        Auth::login($user);
        initialize_working_year_session();
        $loginMessage = 'Login berhasil. Selamat datang, ' . $user['full_name'] . '.';
        if ((string) ($user['role_code'] ?? '') === 'admin') {
            try {
                $profile = app_profile();
                $dailyBackup = $this->backupService()->createDailySafetyBackupIfMissing([
                    'app_name' => (string) app_config('name'),
                    'bumdes_name' => (string) ($profile['bumdes_name'] ?? ''),
                    'actor_name' => (string) ($user['full_name'] ?? ''),
                ]);
                if (!empty($dailyBackup['created'])) {
                    audit_log('backup_database', 'auto_daily_create', 'Backup harian otomatis dibuat saat login admin.', [
                        'severity' => 'info',
                        'entity_type' => 'backup_file',
                        'entity_id' => (string) (($dailyBackup['file']['name'] ?? '') ?: ($dailyBackup['file']['file_name'] ?? '')),
                        'after' => $dailyBackup,
                    ]);
                    $loginMessage .= ' Backup harian otomatis juga dibuat untuk menjaga baseline recovery.';
                }
            } catch (Throwable $backupError) {
                log_error($backupError);
                audit_log('backup_database', 'auto_daily_failed', 'Backup harian otomatis gagal dibuat saat login admin.', [
                    'severity' => 'warning',
                    'entity_type' => 'backup_file',
                    'context' => ['message' => $backupError->getMessage()],
                ]);
            }
        }
        audit_log('Autentikasi', 'login_success', 'Pengguna berhasil login ke aplikasi.', [
            'entity_type' => 'user',
            'entity_id' => (string) ($user['id'] ?? ''),
            'user_id' => (int) ($user['id'] ?? 0),
            'username' => (string) ($user['username'] ?? ''),
            'full_name' => (string) ($user['full_name'] ?? ''),
            'context' => ['working_year' => current_working_year()],
        ]);
        flash('success', $loginMessage);
        if (count(working_year_options()) > 1) {
            $this->redirect('/periods/select-working');
        }
        $this->redirect((string) auth_config('redirect_after_login'));
    }

    public function logout(): void
    {
        $token = (string) post('_token');
        if (!verify_csrf($token)) {
            http_response_code(419);
            render_error_page(419, 'Sesi keamanan formulir telah berakhir. Silakan login kembali.');
            return;
        }

        $currentUser = Auth::user();
        audit_log('Autentikasi', 'logout', 'Pengguna logout dari aplikasi.', [
            'entity_type' => 'user',
            'entity_id' => (string) ($currentUser['id'] ?? ''),
        ]);
        Auth::logout();
        Session::destroy();
        Session::start(app_config());
        flash('success', 'Anda sudah logout dengan aman.');
        $this->redirect((string) auth_config('redirect_after_logout'));
    }
}
