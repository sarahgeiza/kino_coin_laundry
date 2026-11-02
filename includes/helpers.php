
<?php

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);

    if (!is_array($data)) {
        respond(['error' => 'JSON tidak valid'], 400);
    }

    return $data;
}

function need(array $data, array $keys): void
{
    foreach ($keys as $key) {
        if (
            !array_key_exists($key, $data) ||
            $data[$key] === '' ||
            $data[$key] === null
        ) {
            respond(['error' => "Field $key wajib diisi"], 422);
        }
    }
}

function integer(mixed $value, int $min = 1): int
{
    if (
        filter_var($value, FILTER_VALIDATE_INT) === false ||
        (int) $value < $min
    ) {
        respond(['error' => 'Angka integer tidak valid'], 422);
    }

    return (int) $value;
}

function strval_required(mixed $value, int $max = 100): string
{
    if (
        !is_string($value) ||
        trim($value) === '' ||
        strlen(trim($value)) > $max
    ) {
        respond(['error' => 'Teks tidak valid'], 422);
    }

    return trim($value);
}

function query_db(string $sql, array $params = []): \PgSql\Result
{
    $index = 0;

    $sql = preg_replace_callback(
        '/\?/',
        function () use (&$index) {
            return '$' . (++$index);
        },
        $sql
    );

    $conn = getConnection();

    $result = $params === []
        ? @pg_query($conn, $sql)
        : @pg_query_params($conn, $sql, $params);

    if ($result === false) {
        $dbError = pg_last_error($conn);

        error_log('Query database gagal: ' . $dbError);

        throw new RuntimeException(
            'Query database gagal: ' . $dbError
        );
    }

    return $result;
}

function rows(string $sql, array $params = []): array
{
    return pg_fetch_all(query_db($sql, $params)) ?: [];
}

function one(string $sql, array $params = []): ?array
{
    $result = query_db($sql, $params);
    $row = pg_fetch_assoc($result);

    return $row === false ? null : $row;
}

function execute_sql(string $sql, array $params = []): int
{
    return pg_affected_rows(query_db($sql, $params));
}

function actor(): array
{
    if (!empty($_SESSION['customer_id'])) {
        $customer = one(
            "SELECT
                id_pelanggan AS id_pengguna,
                nama_pelanggan AS nama,
                no_hp AS username,
                'pelanggan' AS role
             FROM pelanggan
             WHERE id_pelanggan = ?",
            [(int) $_SESSION['customer_id']]
        );

        if ($customer !== null) {
            return $customer;
        }
    }

    if (empty($_SESSION['user_id'])) {
        respond(['error' => 'Harus login'], 401);
    }

    $user = one(
        'SELECT id_pengguna, nama, username, role
         FROM pengguna
         WHERE id_pengguna = ?',
        [(int) $_SESSION['user_id']]
    );

    if ($user === null) {
        $_SESSION = [];
        session_destroy();

        respond(['error' => 'Akun tidak ditemukan'], 401);
    }

    return $user;
}

function owner(): array
{
    $user = actor();

    if ($user['role'] !== 'pemilik') {
        respond(['error' => 'Akses hanya untuk pemilik'], 403);
    }

    return $user;
}

function method(string $expected): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $expected) {
        respond(['error' => 'HTTP method tidak sesuai'], 405);
    }
}

function csrf(): void
{
    if (
        empty($_SESSION['csrf']) ||
        empty($_SERVER['HTTP_X_CSRF_TOKEN']) ||
        !hash_equals(
            $_SESSION['csrf'],
            $_SERVER['HTTP_X_CSRF_TOKEN']
        )
    ) {
        respond(['error' => 'CSRF token tidak valid'], 403);
    }
}

function allowed_keys(array $data, array $allowed): array
{
    return array_intersect_key($data, array_flip($allowed));
}

function positive_weight(mixed $value): string
{
    if (
        !is_numeric($value) ||
        !is_finite((float) $value) ||
        (float) $value <= 0 ||
        (float) $value > 999.99
    ) {
        respond([
            'error' => 'Berat harus lebih dari 0 dan maksimal 999,99 kg'
        ], 422);
    }

    return number_format((float) $value, 2, '.', '');
}

function idparam(): int
{
    return integer($_GET['id'] ?? null);
}

function customer_actor(): int
{
    if (empty($_SESSION['customer_id'])) {
        respond(['error' => 'Login pelanggan diperlukan'], 401);
    }

    return (int) $_SESSION['customer_id'];
}
