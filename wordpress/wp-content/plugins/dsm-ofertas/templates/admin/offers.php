<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Variables disponibles:
 *
 * $offers
 * $offer
 * $rules
 * $plans
 * $notice
 * $error
 */

$rulesByPlan = [];

foreach ($rules as $rule) {
    $rulesByPlan[
        (int) $rule->plan_id
    ] = $rule;
}

$currentOfferId =
    is_object($offer)
        ? (int) $offer->id
        : 0;

$currentCode =
    is_object($offer)
        ? (string) $offer->code
        : '';

$currentName =
    is_object($offer)
        ? (string) $offer->name
        : '';

$currentDescription =
    is_object($offer)
        ? (string) (
            $offer->description
            ?? ''
        )
        : '';

$currentScope =
    is_object($offer)
        ? (string) $offer->scope
        : 'general';

$currentStatus =
    is_object($offer)
        ? (string) $offer->status
        : 'draft';

$currentPriority =
    is_object($offer)
        ? (int) $offer->priority
        : 0;

$currentStackable =
    is_object($offer)
        && (int) $offer->stackable === 1;

$formatInputDateTime =
    static function (
        mixed $value
    ): string {
        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return '';
        }

        $timestamp =
            strtotime(
                $value . ' UTC'
            );

        if ($timestamp === false) {
            return '';
        }

        return wp_date(
            'Y-m-d\TH:i',
            $timestamp
        );
    };

$currentStartsAt =
    is_object($offer)
        ? $formatInputDateTime(
            $offer->starts_at
        )
        : '';

$currentEndsAt =
    is_object($offer)
        ? $formatInputDateTime(
            $offer->ends_at
        )
        : '';

$statusLabels = [
    'draft' =>
        __('Borrador', 'dsm-ofertas'),

    'active' =>
        __('Activa', 'dsm-ofertas'),

    'paused' =>
        __('Pausada', 'dsm-ofertas'),

    'archived' =>
        __('Archivada', 'dsm-ofertas'),
];

$scopeLabels = [
    'general' =>
        __('General', 'dsm-ofertas'),

    'customers' =>
        __('Clientes concretos', 'dsm-ofertas'),
];

$benefitLabels = [
    'free_months' =>
        __('Meses gratis', 'dsm-ofertas'),

    'percentage_discount' =>
        __('Descuento porcentual', 'dsm-ofertas'),

    'fixed_discount' =>
        __('Descuento fijo', 'dsm-ofertas'),

    'special_price' =>
        __('Precio especial', 'dsm-ofertas'),
];

$appliesToLabels = [
    'any_subscription' =>
        __('Cualquier contratación', 'dsm-ofertas'),

    'new_subscription' =>
        __('Solo nuevas suscripciones', 'dsm-ofertas'),

    'renewal' =>
        __('Solo renovaciones', 'dsm-ofertas'),

    'retention' =>
        __('Retención / recuperación', 'dsm-ofertas'),
];
?>

<div class="wrap dsm-offers-admin">

    <h1>
        <?php
        echo esc_html__(
            'DSM Ofertas',
            'dsm-ofertas'
        );
        ?>
    </h1>

    <p class="description">
        <?php
        echo esc_html__(
            'Gestiona meses gratuitos, descuentos y condiciones comerciales especiales para las suscripciones de DeSegundaMuda.',
            'dsm-ofertas'
        );
        ?>
    </p>

    <?php if ($notice === 'saved') : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                echo esc_html__(
                    'La oferta se ha guardado correctamente.',
                    'dsm-ofertas'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($notice === 'customer_assigned') : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                Cliente asignado correctamente a la oferta.
            </p>
        </div>
    <?php endif; ?>

    <?php if ($notice === 'customer_revoked') : ?>
        <div class="notice notice-success is-dismissible">
            <p>
                La asignación del cliente se ha revocado correctamente.
            </p>
        </div>
    <?php endif; ?>

    <?php if ($error !== '') : ?>
        <div class="notice notice-error">
            <p>
                <?php echo esc_html($error); ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="dsm-offers-layout">

        <section class="dsm-offers-main">

            <div class="dsm-offers-card">

                <div class="dsm-offers-card__header">

                    <div>
                        <h2>
                            <?php
                            echo esc_html(
                                $currentOfferId > 0
                                    ? __(
                                        'Editar oferta',
                                        'dsm-ofertas'
                                    )
                                    : __(
                                        'Nueva oferta',
                                        'dsm-ofertas'
                                    )
                            );
                            ?>
                        </h2>

                        <p>
                            <?php
                            echo esc_html__(
                                'Define la campaña y después configura qué ventaja recibe cada plan.',
                                'dsm-ofertas'
                            );
                            ?>
                        </p>
                    </div>

                    <?php if ($currentOfferId > 0) : ?>
                        <a
                            class="button"
                            href="<?php
                                echo esc_url(
                                    admin_url(
                                        'admin.php?page=dsm-ofertas'
                                    )
                                );
                            ?>"
                        >
                            <?php
                            echo esc_html__(
                                'Nueva oferta',
                                'dsm-ofertas'
                            );
                            ?>
                        </a>
                    <?php endif; ?>

                </div>

                <form
                    method="post"
                    action="<?php
                        echo esc_url(
                            admin_url(
                                'admin-post.php'
                            )
                        );
                    ?>"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="dsm_offer_save"
                    >

                    <input
                        type="hidden"
                        name="offer_id"
                        value="<?php
                            echo esc_attr(
                                (string) $currentOfferId
                            );
                        ?>"
                    >

                    <?php
                    wp_nonce_field(
                        'dsm_offer_save',
                        'dsm_offer_nonce'
                    );
                    ?>

                    <div class="dsm-offers-form-grid">

                        <label class="dsm-offers-field">
                            <span>
                                <?php
                                echo esc_html__(
                                    'Nombre',
                                    'dsm-ofertas'
                                );
                                ?>
                            </span>

                            <input
                                type="text"
                                name="name"
                                value="<?php
                                    echo esc_attr(
                                        $currentName
                                    );
                                ?>"
                                required
                            >
                        </label>

                        <label class="dsm-offers-field">
                            <span>
                                <?php
                                echo esc_html__(
                                    'Código',
                                    'dsm-ofertas'
                                );
                                ?>
                            </span>

                            <input
                                type="text"
                                name="code"
                                value="<?php
                                    echo esc_attr(
                                        $currentCode
                                    );
                                ?>"
                                placeholder="lanzamiento_2026"
                            >

                            <small>
                                <?php
                                echo esc_html__(
                                    'Si se deja vacío se genera automáticamente.',
                                    'dsm-ofertas'
                                );
                                ?>
                            </small>
                        </label>

                        <label class="dsm-offers-field">
                            <span>
                                <?php
                                echo esc_html__(
                                    'Ámbito',
                                    'dsm-ofertas'
                                );
                                ?>
                            </span>

                            <select name="scope">
                                <?php
                                foreach (
                                    $scopeLabels
                                    as $value => $label
                                ) :
                                    ?>
                                    <option
                                        value="<?php
                                            echo esc_attr(
                                                $value
                                            );
                                        ?>"
                                        <?php
                                        selected(
                                            $currentScope,
                                            $value
                                        );
                                        ?>
                                    >
                                        <?php
                                        echo esc_html(
                                            $label
                                        );
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="dsm-offers-field">
                            <span>
                                <?php
                                echo esc_html__(
                                    'Estado',
                                    'dsm-ofertas'
                                );
                                ?>
                            </span>

                            <select name="status">
                                <?php
                                foreach (
                                    $statusLabels
                                    as $value => $label
                                ) :
                                    ?>
                                    <option
                                        value="<?php
                                            echo esc_attr(
                                                $value
                                            );
                                        ?>"
                                        <?php
                                        selected(
                                            $currentStatus,
                                            $value
                                        );
                                        ?>
                                    >
                                        <?php
                                        echo esc_html(
                                            $label
                                        );
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="dsm-offers-field">
                            <span>
                                <?php
                                echo esc_html__(
                                    'Inicio',
                                    'dsm-ofertas'
                                );
                                ?>
                            </span>

                            <input
                                type="datetime-local"
                                name="starts_at"
                                value="<?php
                                    echo esc_attr(
                                        $currentStartsAt
                                    );
                                ?>"
                            >
                        </label>

                        <label class="dsm-offers-field">
                            <span>
                                <?php
                                echo esc_html__(
                                    'Finalización',
                                    'dsm-ofertas'
                                );
                                ?>
                            </span>

                            <input
                                type="datetime-local"
                                name="ends_at"
                                value="<?php
                                    echo esc_attr(
                                        $currentEndsAt
                                    );
                                ?>"
                            >
                        </label>

                        <label class="dsm-offers-field">
                            <span>
                                <?php
                                echo esc_html__(
                                    'Prioridad',
                                    'dsm-ofertas'
                                );
                                ?>
                            </span>

                            <input
                                type="number"
                                min="0"
                                step="1"
                                name="priority"
                                value="<?php
                                    echo esc_attr(
                                        (string)
                                        $currentPriority
                                    );
                                ?>"
                            >

                            <small>
                                <?php
                                echo esc_html__(
                                    'Una prioridad mayor tendrá preferencia cuando coincidan varias ofertas.',
                                    'dsm-ofertas'
                                );
                                ?>
                            </small>
                        </label>

                        <label class="dsm-offers-checkbox">
                            <input
                                type="checkbox"
                                name="stackable"
                                value="1"
                                <?php
                                checked(
                                    $currentStackable
                                );
                                ?>
                            >

                            <span>
                                <?php
                                echo esc_html__(
                                    'Permitir acumulación con otras ofertas',
                                    'dsm-ofertas'
                                );
                                ?>
                            </span>
                        </label>

                    </div>

                    <label class="dsm-offers-field dsm-offers-field--full">
                        <span>
                            <?php
                            echo esc_html__(
                                'Descripción interna',
                                'dsm-ofertas'
                            );
                            ?>
                        </span>

                        <textarea
                            name="description"
                            rows="4"
                        ><?php
                            echo esc_textarea(
                                $currentDescription
                            );
                        ?></textarea>
                    </label>

                    <hr>

                    <div class="dsm-offers-rules-header">
                        <h2>
                            <?php
                            echo esc_html__(
                                'Condiciones por plan',
                                'dsm-ofertas'
                            );
                            ?>
                        </h2>

                        <p>
                            <?php
                            echo esc_html__(
                                'Activa únicamente los planes que formen parte de esta oferta.',
                                'dsm-ofertas'
                            );
                            ?>
                        </p>
                    </div>

                    <div class="dsm-offer-plans">

                        <?php foreach ($plans as $plan) : ?>

                            <?php
                            $planId =
                                $plan->getId();

                            $rule =
                                $rulesByPlan[
                                    $planId
                                ]
                                ?? null;

                            $ruleEnabled =
                                is_object(
                                    $rule
                                );

                            $benefitType =
                                $ruleEnabled
                                    ? (string)
                                        $rule
                                            ->benefit_type
                                    : 'free_months';

                            $benefitValue =
                                $ruleEnabled
                                    ? (string)
                                        $rule
                                            ->benefit_value
                                    : '1';

                            $durationMonths =
                                $ruleEnabled
                                    ? (
                                        $rule
                                            ->duration_months
                                        !== null
                                            ? (string)
                                                $rule
                                                    ->duration_months
                                            : ''
                                    )
                                    : '';

                            $appliesTo =
                                $ruleEnabled
                                    ? (string)
                                        $rule
                                            ->applies_to
                                    : 'any_subscription';
                            ?>

                            <article class="dsm-offer-plan">

                                <div class="dsm-offer-plan__header">

                                    <label class="dsm-offers-checkbox">
                                        <input
                                            type="checkbox"
                                            name="rules[<?php
                                                echo esc_attr(
                                                    (string)
                                                    $planId
                                                );
                                            ?>][enabled]"
                                            value="1"
                                            <?php
                                            checked(
                                                $ruleEnabled
                                            );
                                            ?>
                                        >

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $plan->getName()
                                            );
                                            ?>
                                        </strong>
                                    </label>

                                    <span class="dsm-offer-plan__price">
                                        <?php
                                        echo esc_html(
                                            number_format_i18n(
                                                $plan->getPrice(),
                                                2
                                            )
                                            . ' '
                                            . $plan->getCurrency()
                                            . ' / '
                                            . $plan->getBillingInterval()
                                        );
                                        ?>
                                    </span>

                                </div>

                                <input
                                    type="hidden"
                                    name="rules[<?php
                                        echo esc_attr(
                                            (string)
                                            $planId
                                        );
                                    ?>][plan_id]"
                                    value="<?php
                                        echo esc_attr(
                                            (string)
                                            $planId
                                        );
                                    ?>"
                                >

                                <div class="dsm-offer-plan__grid">

                                    <label class="dsm-offers-field">
                                        <span>
                                            <?php
                                            echo esc_html__(
                                                'Tipo de ventaja',
                                                'dsm-ofertas'
                                            );
                                            ?>
                                        </span>

                                        <select
                                            name="rules[<?php
                                                echo esc_attr(
                                                    (string)
                                                    $planId
                                                );
                                            ?>][benefit_type]"
                                        >
                                            <?php
                                            foreach (
                                                $benefitLabels
                                                as $value => $label
                                            ) :
                                                ?>
                                                <option
                                                    value="<?php
                                                        echo esc_attr(
                                                            $value
                                                        );
                                                    ?>"
                                                    <?php
                                                    selected(
                                                        $benefitType,
                                                        $value
                                                    );
                                                    ?>
                                                >
                                                    <?php
                                                    echo esc_html(
                                                        $label
                                                    );
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <label class="dsm-offers-field">
                                        <span>
                                            <?php
                                            echo esc_html__(
                                                'Valor',
                                                'dsm-ofertas'
                                            );
                                            ?>
                                        </span>

                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            name="rules[<?php
                                                echo esc_attr(
                                                    (string)
                                                    $planId
                                                );
                                            ?>][benefit_value]"
                                            value="<?php
                                                echo esc_attr(
                                                    $benefitValue
                                                );
                                            ?>"
                                        >

                                        <small>
                                            <?php
                                            echo esc_html__(
                                                'Ej.: 2 meses, 10 %, 5 € o precio 9,99 €.',
                                                'dsm-ofertas'
                                            );
                                            ?>
                                        </small>
                                    </label>

                                    <label class="dsm-offers-field">
                                        <span>
                                            <?php
                                            echo esc_html__(
                                                'Duración del beneficio',
                                                'dsm-ofertas'
                                            );
                                            ?>
                                        </span>

                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            name="rules[<?php
                                                echo esc_attr(
                                                    (string)
                                                    $planId
                                                );
                                            ?>][duration_months]"
                                            value="<?php
                                                echo esc_attr(
                                                    $durationMonths
                                                );
                                            ?>"
                                            placeholder="12"
                                        >

                                        <small>
                                            <?php
                                            echo esc_html__(
                                                'Meses. En "meses gratis" se calcula automáticamente.',
                                                'dsm-ofertas'
                                            );
                                            ?>
                                        </small>
                                    </label>

                                    <label class="dsm-offers-field">
                                        <span>
                                            <?php
                                            echo esc_html__(
                                                'Aplicable a',
                                                'dsm-ofertas'
                                            );
                                            ?>
                                        </span>

                                        <select
                                            name="rules[<?php
                                                echo esc_attr(
                                                    (string)
                                                    $planId
                                                );
                                            ?>][applies_to]"
                                        >
                                            <?php
                                            foreach (
                                                $appliesToLabels
                                                as $value => $label
                                            ) :
                                                ?>
                                                <option
                                                    value="<?php
                                                        echo esc_attr(
                                                            $value
                                                        );
                                                    ?>"
                                                    <?php
                                                    selected(
                                                        $appliesTo,
                                                        $value
                                                    );
                                                    ?>
                                                >
                                                    <?php
                                                    echo esc_html(
                                                        $label
                                                    );
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                    <p class="submit">
                        <button
                            type="submit"
                            class="button button-primary"
                        >
                            <?php
                            echo esc_html(
                                $currentOfferId > 0
                                    ? __(
                                        'Guardar cambios',
                                        'dsm-ofertas'
                                    )
                                    : __(
                                        'Crear oferta',
                                        'dsm-ofertas'
                                    )
                            );
                            ?>
                        </button>
                    </p>

                </form>

            </div>

            <?php if (
                $currentOfferId > 0
                && $currentScope === 'customers'
            ) : ?>

                <?php
                $customersTemplate =
                    DSM_OFERTAS_PATH
                    . 'templates/admin/offer-customers.php';

                if (is_file($customersTemplate)) {
                    require $customersTemplate;
                }
                ?>

            <?php endif; ?>

        </section>

        <section class="dsm-offers-history">

            <div class="dsm-offers-card">

                <div class="dsm-offers-card__header">
                    <div>
                        <h2>
                            Historial de utilizaciones
                        </h2>

                        <p>
                            Últimas ofertas aceptadas por clientes.
                        </p>
                    </div>
                </div>

                <?php if ($redemptions === []) : ?>

                    <p class="dsm-offers-empty">
                        Todavía no hay ofertas utilizadas.
                    </p>

                <?php else : ?>

                    <div class="dsm-offers-table-wrap">

                        <table class="widefat striped">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Oferta</th>
                                    <th>Cliente</th>
                                    <th>Plan</th>
                                    <th>Beneficio</th>
                                    <th>Precio</th>
                                    <th>Periodo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach (
                                    $redemptions
                                    as $redemption
                                ) : ?>

                                    <tr>

                                        <td>
                                            #<?php
                                            echo esc_html(
                                                (string)
                                                $redemption->id
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?php
                                                echo esc_html(
                                                    (string) (
                                                        $redemption
                                                            ->offer_name
                                                        ?? 'Oferta eliminada'
                                                    )
                                                );
                                                ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                (string) (
                                                    $redemption
                                                        ->customer_email
                                                    ?? (
                                                        'Cliente #'
                                                        . $redemption
                                                            ->customer_id
                                                    )
                                                )
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                (string) (
                                                    $redemption
                                                        ->plan_name
                                                    ?? (
                                                        'Plan #'
                                                        . $redemption
                                                            ->plan_id
                                                    )
                                                )
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            $benefitType =
                                                (string)
                                                $redemption
                                                    ->benefit_type;

                                            if (
                                                $benefitType
                                                === 'free_months'
                                            ) {
                                                echo esc_html(
                                                    (string)
                                                    $redemption
                                                        ->duration_months
                                                    . ' meses gratis'
                                                );
                                            } elseif (
                                                $benefitType
                                                === 'percentage_discount'
                                            ) {
                                                echo esc_html(
                                                    number_format_i18n(
                                                        (float)
                                                        $redemption
                                                            ->benefit_value,
                                                        2
                                                    )
                                                    . ' %'
                                                );
                                            } elseif (
                                                $benefitType
                                                === 'fixed_discount'
                                            ) {
                                                echo esc_html(
                                                    number_format_i18n(
                                                        (float)
                                                        $redemption
                                                            ->benefit_value,
                                                        2
                                                    )
                                                    . ' '
                                                    . $redemption
                                                        ->currency
                                                );
                                            } else {
                                                echo esc_html(
                                                    number_format_i18n(
                                                        (float)
                                                        $redemption
                                                            ->final_price,
                                                        2
                                                    )
                                                    . ' '
                                                    . $redemption
                                                        ->currency
                                                );
                                            }
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                number_format_i18n(
                                                    (float)
                                                    $redemption
                                                        ->original_price,
                                                    2
                                                )
                                                . ' → '
                                                . number_format_i18n(
                                                    (float)
                                                    $redemption
                                                        ->final_price,
                                                    2
                                                )
                                                . ' '
                                                . $redemption
                                                    ->currency
                                            );
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                (string)
                                                $redemption
                                                    ->benefit_starts_at
                                            );

                                            if (
                                                $redemption
                                                    ->benefit_ends_at
                                                !== null
                                            ) {
                                                echo '<br>';

                                                echo esc_html(
                                                    'hasta '
                                                    . (
                                                        (string)
                                                        $redemption
                                                            ->benefit_ends_at
                                                    )
                                                );
                                            }
                                            ?>
                                        </td>

                                        <td>
                                            <?php
                                            echo esc_html(
                                                (string)
                                                $redemption->status
                                            );
                                            ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </section>

        <aside class="dsm-offers-sidebar">

            <div class="dsm-offers-card">

                <div class="dsm-offers-card__header">
                    <div>
                        <h2>
                            <?php
                            echo esc_html__(
                                'Ofertas existentes',
                                'dsm-ofertas'
                            );
                            ?>
                        </h2>

                        <p>
                            <?php
                            echo esc_html__(
                                'Selecciona una oferta para editarla.',
                                'dsm-ofertas'
                            );
                            ?>
                        </p>
                    </div>
                </div>

                <?php if ($offers === []) : ?>

                    <p class="dsm-offers-empty">
                        <?php
                        echo esc_html__(
                            'Todavía no hay ofertas creadas.',
                            'dsm-ofertas'
                        );
                        ?>
                    </p>

                <?php else : ?>

                    <div class="dsm-offers-list">

                        <?php foreach ($offers as $existingOffer) : ?>

                            <?php
                            $existingStatus =
                                (string)
                                $existingOffer
                                    ->status;

                            $existingScope =
                                (string)
                                $existingOffer
                                    ->scope;
                            ?>

                            <a
                                class="dsm-offers-list__item <?php
                                    echo (int) $existingOffer->id
                                        === $currentOfferId
                                            ? 'is-current'
                                            : '';
                                ?>"
                                href="<?php
                                    echo esc_url(
                                        add_query_arg(
                                            [
                                                'page' =>
                                                    'dsm-ofertas',

                                                'offer_id' =>
                                                    (int)
                                                    $existingOffer
                                                        ->id,
                                            ],
                                            admin_url(
                                                'admin.php'
                                            )
                                        )
                                    );
                                ?>"
                            >

                                <strong>
                                    <?php
                                    echo esc_html(
                                        (string)
                                        $existingOffer
                                            ->name
                                    );
                                    ?>
                                </strong>

                                <span>
                                    <?php
                                    echo esc_html(
                                        $statusLabels[
                                            $existingStatus
                                        ]
                                        ?? $existingStatus
                                    );
                                    ?>
                                    ·
                                    <?php
                                    echo esc_html(
                                        $scopeLabels[
                                            $existingScope
                                        ]
                                        ?? $existingScope
                                    );
                                    ?>
                                </span>

                                <small>
                                    <?php
                                    echo esc_html(
                                        (string)
                                        $existingOffer
                                            ->code
                                    );
                                    ?>
                                </small>

                            </a>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        </aside>

    </div>

</div>
