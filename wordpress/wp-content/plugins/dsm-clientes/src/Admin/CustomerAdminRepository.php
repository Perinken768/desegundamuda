<?php

declare(strict_types=1);

namespace DSM\Clientes\Admin;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerAdminRepository
{
    private string $customersTable;
    private string $profilesTable;
    private string $sessionsTable;

    public function __construct()
    {
        global $wpdb;

        $this->customersTable =
            $wpdb->prefix . 'dsm_customers';

        $this->profilesTable =
            $wpdb->prefix . 'dsm_customer_profiles';

        $this->sessionsTable =
            $wpdb->prefix . 'dsm_customer_sessions';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAll(
        int $page,
        int $perPage,
        string $search = '',
        string $status = ''
    ): array {
        global $wpdb;

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $parameters = [];

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';

            $where[] = '(
                c.email LIKE %s
                OR p.display_name LIKE %s
                OR p.phone LIKE %s
                OR p.whatsapp_phone LIKE %s
            )';

            $parameters[] = $like;
            $parameters[] = $like;
            $parameters[] = $like;
            $parameters[] = $like;
        }

        if ($status !== '') {
            $where[] = 'c.status = %s';
            $parameters[] = $status;
        }

        $whereSql = $where !== []
            ? 'WHERE ' . implode(' AND ', $where)
            : '';

        $sql = "
            SELECT
                c.id,
                c.email,
                c.status,
                c.email_verified_at,
                c.last_login_at,
                c.created_at,
                c.updated_at,
                p.display_name,
                p.phone,
                p.whatsapp_phone,
                p.island_id,
                p.municipality_id,
                (
                    SELECT MAX(s.last_activity_at)
                    FROM {$this->sessionsTable} s
                    WHERE s.customer_id = c.id
                ) AS last_session_activity,
                (
                    SELECT COUNT(*)
                    FROM {$this->sessionsTable} s2
                    WHERE s2.customer_id = c.id
                      AND s2.revoked_at IS NULL
                      AND s2.expires_at > UTC_TIMESTAMP()
                ) AS active_sessions
            FROM {$this->customersTable} c
            LEFT JOIN {$this->profilesTable} p
                ON p.customer_id = c.id
            {$whereSql}
            ORDER BY c.id DESC
            LIMIT %d
            OFFSET %d
        ";

        $parameters[] = $perPage;
        $parameters[] = $offset;

        $preparedSql = $wpdb->prepare(
            $sql,
            ...$parameters
        );

        $rows = $wpdb->get_results(
            $preparedSql,
            ARRAY_A
        );

        return is_array($rows)
            ? $rows
            : [];
    }

    public function count(
        string $search = '',
        string $status = ''
    ): int {
        global $wpdb;

        $where = [];
        $parameters = [];

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';

            $where[] = '(
                c.email LIKE %s
                OR p.display_name LIKE %s
                OR p.phone LIKE %s
                OR p.whatsapp_phone LIKE %s
            )';

            $parameters[] = $like;
            $parameters[] = $like;
            $parameters[] = $like;
            $parameters[] = $like;
        }

        if ($status !== '') {
            $where[] = 'c.status = %s';
            $parameters[] = $status;
        }

        $whereSql = $where !== []
            ? 'WHERE ' . implode(' AND ', $where)
            : '';

        $sql = "
            SELECT COUNT(*)
            FROM {$this->customersTable} c
            LEFT JOIN {$this->profilesTable} p
                ON p.customer_id = c.id
            {$whereSql}
        ";

        if ($parameters !== []) {
            $sql = $wpdb->prepare(
                $sql,
                ...$parameters
            );
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * @return array<string, int>
     */
    public function getCounters(): array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            "
            SELECT
                COUNT(*) AS total,

                SUM(
                    CASE
                        WHEN status = 'pending'
                        THEN 1
                        ELSE 0
                    END
                ) AS pending,

                SUM(
                    CASE
                        WHEN status = 'active'
                        THEN 1
                        ELSE 0
                    END
                ) AS active,

                SUM(
                    CASE
                        WHEN status = 'inactive'
                        THEN 1
                        ELSE 0
                    END
                ) AS inactive,

                SUM(
                    CASE
                        WHEN status = 'suspended'
                        THEN 1
                        ELSE 0
                    END
                ) AS suspended,

                SUM(
                    CASE
                        WHEN status = 'blocked'
                        THEN 1
                        ELSE 0
                    END
                ) AS blocked,

                SUM(
                    CASE
                        WHEN status = 'deletion_pending'
                        THEN 1
                        ELSE 0
                    END
                ) AS deletion_pending,

                SUM(
                    CASE
                        WHEN email_verified_at IS NOT NULL
                        THEN 1
                        ELSE 0
                    END
                ) AS verified

            FROM {$this->customersTable}
            ",
            ARRAY_A
        );

        if (!is_array($row)) {
            return [
                'total' => 0,
                'pending' => 0,
                'active' => 0,
                'inactive' => 0,
                'suspended' => 0,
                'blocked' => 0,
                'deletion_pending' => 0,
                'verified' => 0,
            ];
        }

        return [
            'total' => (int) ($row['total'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
            'suspended' => (int) ($row['suspended'] ?? 0),
            'blocked' => (int) ($row['blocked'] ?? 0),
            'deletion_pending' =>
                (int) ($row['deletion_pending'] ?? 0),
            'verified' => (int) ($row['verified'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDetailById(
        int $customerId
    ): ?array {
        global $wpdb;

        if ($customerId <= 0) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    c.id,
                    c.email,
                    c.status,
                    c.email_verified_at,
                    c.last_login_at,
                    c.created_at,
                    c.updated_at,
                    p.display_name,
                    p.phone,
                    p.whatsapp_phone,
                    p.avatar_attachment_id,
                    p.bio,
                    p.island_id,
                    p.municipality_id
                FROM {$this->customersTable} c
                LEFT JOIN {$this->profilesTable} p
                    ON p.customer_id = c.id
                WHERE c.id = %d
                LIMIT 1",
                $customerId
            ),
            ARRAY_A
        );

        return is_array($row)
            ? $row
            : null;
    }
}