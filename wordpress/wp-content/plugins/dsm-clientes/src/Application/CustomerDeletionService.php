<?php

declare(strict_types=1);

namespace DSM\Clientes\Application;

use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Customer\Customer;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use DSM\Clientes\Deletion\CustomerDeletionRequestRepository;
use DSM\Core\Mail\MailerRegistry;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerDeletionService
{
    private const GRACE_PERIOD_SECONDS =
        30 * DAY_IN_SECONDS;

    public function __construct(
        private readonly CustomerDeletionRequestRepository $deletionRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerSessionRepository $sessionRepository
    ) {
    }

    public function request(
        Customer $customer,
        string $password
    ): void {
        if (
            $customer->getStatus()
            !== CustomerStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'La cuenta no puede solicitar la eliminación.'
            );
        }

        $credentials =
            $this->customerRepository
                ->findCredentialsByEmail(
                    $customer->getEmail()
                );

        if (
            $credentials === null
            || !wp_check_password(
                $password,
                $credentials['password_hash']
            )
        ) {
            throw new RuntimeException(
                'La contraseña introducida no es correcta.'
            );
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $requestId =
            $this->deletionRepository
                ->createPending(
                    $customer->getId(),
                    $tokenHash
                );

        try {
            $confirmationUrl = add_query_arg(
                [
                    'action' =>
                        'dsm_customer_confirm_deletion',
                    'token' => $token,
                ],
                admin_url('admin-post.php')
            );

            $subject = sprintf(
                '[%s] Confirma la eliminación de tu cuenta',
                wp_specialchars_decode(
                    get_bloginfo('name'),
                    ENT_QUOTES
                )
            );

            $message = sprintf(
                "Hola,\n\n"
                . "Has solicitado eliminar definitivamente "
                . "tu cuenta de DeSegundaMuda.\n\n"
                . "Para confirmar la solicitud, pulsa:\n%s\n\n"
                . "Después de confirmar tendrás 30 días "
                . "para cancelar la eliminación.\n\n"
                . "Si no has solicitado esta eliminación, "
                . "ignora este mensaje.",
                $confirmationUrl
            );

            MailerRegistry::get()->send(
                $customer->getEmail(),
                $subject,
                $message
            );

            do_action(
                'dsm_audit_event',
                'customer.deletion_requested',
                [
                    'customer_id' =>
                        $customer->getId(),
                    'actor_type' => 'customer',
                ]
            );
        } catch (Throwable $exception) {
            try {
                $this->deletionRepository
                    ->deleteById($requestId);
            } catch (Throwable) {
            }

            throw $exception;
        }
    }

    public function confirm(string $token): string
    {
        $request =
            $this->getRequestByToken($token);

        if (
            $request['status']
            !== CustomerDeletionRequestRepository::
                STATUS_PENDING_CONFIRMATION
        ) {
            throw new RuntimeException(
                'La solicitud ya no puede confirmarse.'
            );
        }

        $customer =
            $this->customerRepository->findById(
                (int) $request['customer_id']
            );

        if ($customer === null) {
            throw new RuntimeException(
                'No se encontró la cuenta.'
            );
        }

        if (
            $customer->getStatus()
            !== CustomerStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'La cuenta no puede eliminarse.'
            );
        }

        $scheduledAt =
            $this->deletionRepository->confirm(
                (int) $request['id'],
                self::GRACE_PERIOD_SECONDS
            );

        $this->customerRepository->updateStatus(
            $customer->getId(),
            CustomerStatus::DELETION_PENDING
        );

        $this->sessionRepository
            ->revokeAllForCustomer(
                $customer->getId()
            );

        $cancelUrl = add_query_arg(
            [
                'action' =>
                    'dsm_customer_cancel_deletion',
                'token' => $token,
            ],
            admin_url('admin-post.php')
        );

        try {
            MailerRegistry::get()->send(
                $customer->getEmail(),
                sprintf(
                    '[%s] Eliminación programada',
                    wp_specialchars_decode(
                        get_bloginfo('name'),
                        ENT_QUOTES
                    )
                ),
                sprintf(
                    "Hola,\n\n"
                    . "La eliminación de tu cuenta ha sido "
                    . "programada para el %s.\n\n"
                    . "Puedes cancelarla antes de esa fecha:\n%s\n\n"
                    . "Después de la eliminación no podrás "
                    . "recuperar la cuenta.",
                    get_date_from_gmt(
                        $scheduledAt,
                        'd/m/Y H:i'
                    ),
                    $cancelUrl
                )
            );
        } catch (Throwable $exception) {
            error_log(
                '[DSM Clientes] No se pudo enviar '
                . 'el correo de eliminación programada: '
                . $exception->getMessage()
            );
        }

        do_action(
            'dsm_customer_deletion_scheduled',
            $customer->getId(),
            $scheduledAt
        );

        do_action(
            'dsm_audit_event',
            'customer.deletion_scheduled',
            [
                'customer_id' => $customer->getId(),
                'actor_type' => 'customer',
                'scheduled_at' => $scheduledAt,
            ]
        );

        return $scheduledAt;
    }

    public function cancel(string $token): Customer
    {
        $request =
            $this->getRequestByToken($token);

        if (
            !in_array(
                $request['status'],
                [
                    CustomerDeletionRequestRepository::
                        STATUS_PENDING_CONFIRMATION,

                    CustomerDeletionRequestRepository::
                        STATUS_SCHEDULED,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'La solicitud ya no puede cancelarse.'
            );
        }

        $customer =
            $this->customerRepository->findById(
                (int) $request['customer_id']
            );

        if ($customer === null) {
            throw new RuntimeException(
                'No se encontró la cuenta.'
            );
        }

        $this->deletionRepository->cancel(
            (int) $request['id']
        );

        if (
            $customer->getStatus()
            === CustomerStatus::DELETION_PENDING
        ) {
            $customer =
                $this->customerRepository->updateStatus(
                    $customer->getId(),
                    CustomerStatus::ACTIVE
                );
        }

        do_action(
            'dsm_customer_deletion_cancelled',
            $customer->getId()
        );

        do_action(
            'dsm_audit_event',
            'customer.deletion_cancelled',
            [
                'customer_id' => $customer->getId(),
                'actor_type' => 'customer',
            ]
        );

        return $customer;
    }

    public function executeDueDeletions(): int
    {
        $processed = 0;

        $requests =
            $this->deletionRepository
                ->findDueRequests();

        foreach ($requests as $request) {
            $customerId =
                (int) $request['customer_id'];

            $requestId =
                (int) $request['id'];

            $customer =
                $this->customerRepository
                    ->findById($customerId);

            if ($customer === null) {
                continue;
            }

            $anonymousReference = hash_hmac(
                'sha256',
                $customerId
                    . '|'
                    . $customer->getEmail(),
                wp_salt('auth')
            );

            try {
                do_action(
                    'dsm_customer_before_permanent_deletion',
                    $customerId,
                    $anonymousReference
                );

                $this->deletionRepository
                    ->permanentlyDeleteCustomerData(
                        $requestId,
                        $customerId
                    );

                do_action(
                    'dsm_customer_deleted',
                    $anonymousReference
                );

                do_action(
                    'dsm_audit_event',
                    'customer.deleted',
                    [
                        'customer_reference' =>
                            $anonymousReference,
                        'actor_type' => 'system',
                    ]
                );

                $processed++;
            } catch (Throwable $exception) {
                error_log(
                    sprintf(
                        '[DSM Clientes] Error eliminando '
                        . 'el cliente %d: %s',
                        $customerId,
                        $exception->getMessage()
                    )
                );
            }
        }

        return $processed;
    }

    /**
     * @return array<string, mixed>
     */
    private function getRequestByToken(
        string $token
    ): array {
        $token = trim($token);

        if (
            preg_match(
                '/^[a-f0-9]{64}$/',
                $token
            ) !== 1
        ) {
            throw new RuntimeException(
                'El token no es válido.'
            );
        }

        $request =
            $this->deletionRepository
                ->findByTokenHash(
                    hash('sha256', $token)
                );

        if ($request === null) {
            throw new RuntimeException(
                'El enlace no es válido.'
            );
        }

        return $request;
    }
}