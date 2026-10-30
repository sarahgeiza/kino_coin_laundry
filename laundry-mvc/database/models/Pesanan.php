<?php

declare(strict_types=1);

namespace LaundryMvc\Models;

use PDO;

final class Pesanan
{
    public function __construct(private PDO $pdo)
    {
    }

    public function todaySummary(): array
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('Asia/Jakarta'));
        $tomorrow = $today->modify('+1 day');
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS jumlah, COALESCE(SUM(total), 0) AS omzet
             FROM pesanan
             WHERE created_at >= :start AND created_at < :end'
        );
        $statement->execute([
            'start' => $today->format('Y-m-d H:i:sP'),
            'end' => $tomorrow->format('Y-m-d H:i:sP'),
        ]);

        return $statement->fetch();
    }

    public function countInProgress(): int
    {
        $statement = $this->pdo->query(
            "SELECT COUNT(*) FROM pesanan WHERE status = 'Proses'"
        );

        return (int) $statement->fetchColumn();
    }

    public function latest(int $limit = 5): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.*, c.nama_pelanggan, c.kode_pelanggan
             FROM pesanan p
             JOIN pelanggan c ON c.id_pelanggan = p.id_pelanggan
             ORDER BY p.id_pesanan DESC
             LIMIT :limit'
        );
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(
        int $customerId,
        string $service,
        float $beratKg,
        int $quantity,
        int $total,
        string $note
    ): void {
        $statement = $this->pdo->prepare(
            "INSERT INTO pesanan
                (id_pelanggan, layanan, berat_kg, jumlah, total, catatan, status)
             VALUES
                (:customer_id, :service, :berat_kg, :quantity, :total, :note, 'Proses')"
        );
        $statement->execute([
            'customer_id' => $customerId,
            'service' => $service,
            'berat_kg' => $beratKg,
            'quantity' => $quantity,
            'total' => $total,
            'note' => $note === '' ? null : $note,
        ]);
    }

    public function updateStatus(int $orderId, string $status, ?int $customerId = null): bool
    {
        $sql = 'UPDATE pesanan SET status = :status WHERE id_pesanan = :id';
        $params = ['status' => $status, 'id' => $orderId];

        if ($customerId !== null) {
            $sql .= ' AND id_pelanggan = :customer_id';
            $params['customer_id'] = $customerId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() > 0;
    }

    public function history(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM pesanan
             WHERE id_pelanggan = :customer_id
             ORDER BY id_pesanan DESC
             LIMIT 50'
        );
        $statement->execute(['customer_id' => $customerId]);

        return $statement->fetchAll();
    }

    public function customerSummary(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS jumlah, COALESCE(SUM(total), 0) AS total_belanja
             FROM pesanan
             WHERE id_pelanggan = :customer_id'
        );
        $statement->execute(['customer_id' => $customerId]);

        return $statement->fetch();
    }
}
