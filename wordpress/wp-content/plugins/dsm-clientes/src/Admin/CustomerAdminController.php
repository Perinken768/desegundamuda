<?php

declare(strict_types=1);

namespace DSM\Clientes\Admin;

use DSM\Clientes\Application\SendCustomerVerificationEmail;
use DSM\Clientes\Authentication\CustomerSessionRepository;
use DSM\Clientes\Authentication\EmailVerificationRepository;
use DSM\Clientes\Customer\CustomerRepository;
use DSM\Clientes\Customer\CustomerStatus;
use RuntimeException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class CustomerAdminController
{
    private const BASE_PAGE = 'dsm-clientes';

    public function register(): void
    {
        add_action(
            'admin_post_dsm_customer_admin_reactivate',
            [$this, 'handleReactivate']
        );

        add_action(
            'admin_post_dsm_customer_admin_update_status',
            [$this, 'handleUpdateStatus']
        );

        add_action(
            'admin_post_dsm_customer_admin_verify_email',
            [$this, 'handleVerifyEmail']
        );

        add_action(
            'admin_post_dsm_customer_admin_resend_verification',
            [$this, 'handleResendVerification']
        );

        add_action(
            'admin_post_dsm_customer_admin_revoke_sessions',
            [$this, 'handleRevokeSessions']
        );

        add_action(
            'admin_post_dsm_customer_admin_update_password',
            [$this, 'handleUpdatePassword']
        );
    }

    public function handleReactivate(): void
    {
        $this->assertAdministrator();

        check_admin_referer(
            'dsm_customer_admin_reactivate',
            'dsm_customer_admin_nonce'
        );

        $customerId = $this->getCustomerId();

        try {
            $repository = new CustomerRepository();

            $customer = $repository->findById(
                $customerId
            );

            if ($customer === null) {
                throw new RuntimeException(
                    'No se encontró el cliente.'
                );
            }

            if (
                $customer->getStatus()
                !== CustomerStatus::INACTIVE
            ) {
                throw new RuntimeException(
                    'La cuenta no está inactiva.'
                );
            }

            $repository->updateStatus(
                $customerId,
                CustomerStatus::ACTIVE
            );

            do_action(
                'dsm_customer_account_reactivated',
                $customerId
            );

            do_action(
                'dsm_audit_event',
                'customer.reactivated',
                [
                    'customer_id' => $customerId,
                    'actor_type' => 'wordpress_user',
                    'actor_id' => get_current_user_id(),
                ]
            );

            $this->redirectToCustomer(
                $customerId,
                'account_reactivated'
            );
        } catch (Throwable $exception) {
            $this->logError(
                'reactivando la cuenta',
                $exception
            );

            $this->redirectToCustomer(
                $customerId,
                'action_error'
            );
        }
    }

    public function handleUpdateStatus(): void
    {
        $this->assertAdministrator();

        check_admin_referer(
            'dsm_customer_admin_update_status',
            'dsm_customer_admin_nonce'
        );

        $customerId = $this->getCustomerId();

        $status = isset($_POST['status'])
            ? sanitize_key(
                wp_unslash($_POST['status'])
            )
            : '';

        try {
            if (!CustomerStatus::isValid($status)) {
                throw new RuntimeException(
                    'El estado seleccionado no es válido.'
                );
            }

            $repository = new CustomerRepository();

            $repository->updateStatus(
                $customerId,
                $status
            );

            if (
                in_array(
                    $status,
                    [
                        CustomerStatus::BLOCKED,
                        CustomerStatus::SUSPENDED,
                        CustomerStatus::INACTIVE,
                        CustomerStatus::DELETION_PENDING,
                    ],
                    true
                )
            ) {
                $sessionRepository =
                    new CustomerSessionRepository();

                $sessionRepository->revokeAllForCustomer(
                    $customerId
                );
            }

            do_action(
                'dsm_audit_event',
                'customer.status_updated',
                [
                    'customer_id' => $customerId,
                    'status' => $status,
                    'actor_type' => 'wordpress_user',
                    'actor_id' => get_current_user_id(),
                ]
            );

            $this->redirectToCustomer(
                $customerId,
                'status_updated'
            );
        } catch (Throwable $exception) {
            $this->logError(
                'actualizando el estado',
                $exception
            );

            $this->redirectToCustomer(
                $customerId,
                'action_error'
            );
        }
    }

    public function handleVerifyEmail(): void
    {
        $this->assertAdministrator();

        check_admin_referer(
            'dsm_customer_admin_verify_email',
            'dsm_customer_admin_nonce'
        );

        $customerId = $this->getCustomerId();

        try {
            $repository = new CustomerRepository();

            $customer = $repository->findById(
                $customerId
            );

            if ($customer === null) {
                throw new RuntimeException(
                    'No se encontró el cliente.'
                );
            }

            if ($customer->getEmailVerifiedAt() === null) {
                $repository->markEmailAsVerified(
                    $customerId
                );
            }

            $this->redirectToCustomer(
                $customerId,
                'email_verified'
            );
        } catch (Throwable $exception) {
            $this->logError(
                'verificando el correo',
                $exception
            );

            $this->redirectToCustomer(
                $customerId,
                'action_error'
            );
        }
    }

    public function handleResendVerification(): void
    {
        $this->assertAdministrator();

        check_admin_referer(
            'dsm_customer_admin_resend_verification',
            'dsm_customer_admin_nonce'
        );

        $customerId = $this->getCustomerId();

        try {
            $customerRepository =
                new CustomerRepository();

            $customer = $customerRepository->findById(
                $customerId
            );

            if ($customer === null) {
                throw new RuntimeException(
                    'No se encontró el cliente.'
                );
            }

            $service =
                new SendCustomerVerificationEmail(
                    new EmailVerificationRepository()
                );

            $service->execute($customer);

            $this->redirectToCustomer(
                $customerId,
                'verification_resent'
            );
        } catch (Throwable $exception) {
            $this->logError(
                'reenviando la verificación',
                $exception
            );

            $this->redirectToCustomer(
                $customerId,
                'action_error'
            );
        }
    }

    public function handleRevokeSessions(): void
    {
        $this->assertAdministrator();

        check_admin_referer(
            'dsm_customer_admin_revoke_sessions',
            'dsm_customer_admin_nonce'
        );

        $customerId = $this->getCustomerId();

        try {
            $repository =
                new CustomerSessionRepository();

            $repository->revokeAllForCustomer(
                $customerId
            );

            $this->redirectToCustomer(
                $customerId,
                'sessions_revoked'
            );
        } catch (Throwable $exception) {
            $this->logError(
                'revocando las sesiones',
                $exception
            );

            $this->redirectToCustomer(
                $customerId,
                'action_error'
            );
        }
    }

    public function handleUpdatePassword(): void
    {
        $this->assertAdministrator();

        check_admin_referer(
            'dsm_customer_admin_update_password',
            'dsm_customer_admin_nonce'
        );

        $customerId = $this->getCustomerId();

        $password = isset($_POST['temporary_password'])
            ? (string) wp_unslash(
                $_POST['temporary_password']
            )
            : '';

        try {
            $customerRepository =
                new CustomerRepository();

            $customerRepository->updatePassword(
                $customerId,
                $password
            );

            $sessionRepository =
                new CustomerSessionRepository();

            $sessionRepository->revokeAllForCustomer(
                $customerId
            );

            $this->redirectToCustomer(
                $customerId,
                'password_updated'
            );
        } catch (Throwable $exception) {
            $this->logError(
                'actualizando la contraseña',
                $exception
            );

            $this->redirectToCustomer(
                $customerId,
                'password_error'
            );
        }
    }

    private function assertAdministrator(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'No tienes permisos para realizar esta acción.',
                    'dsm-clientes'
                )
            );
        }
    }

    private function getCustomerId(): int
    {
        $customerId = isset($_POST['customer_id'])
            ? absint($_POST['customer_id'])
            : 0;

        if ($customerId <= 0) {
            wp_die(
                esc_html__(
                    'El identificador del cliente no es válido.',
                    'dsm-clientes'
                )
            );
        }

        return $customerId;
    }

    private function redirectToCustomer(
        int $customerId,
        string $status
    ): void {
        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => self::BASE_PAGE,
                    'action' => 'view',
                    'customer_id' => $customerId,
                    'admin_status' => $status,
                ],
                admin_url('admin.php')
            )
        );

        exit;
    }

    private function logError(
        string $action,
        Throwable $exception
    ): void {
        error_log(
            sprintf(
                '[DSM Clientes] Error %s: %s',
                $action,
                $exception->getMessage()
            )
        );
    }
}