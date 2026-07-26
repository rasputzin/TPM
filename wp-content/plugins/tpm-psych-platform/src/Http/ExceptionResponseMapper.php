<?php

declare(strict_types=1);

namespace TpmPsych\Http;

use TpmPsych\Support\DomainException;
use TpmPsych\Validation\ValidationException;

/**
 * Convertește excepțiile de domeniu în răspunsuri REST fără a scurge
 * mesaje de eroare PHP brute către utilizator (cerință de securitate:
 * "nu afișa erori PHP utilizatorilor"). Orice altă excepție neașteptată e
 * jurnalizată server-side și întoarsă ca eroare generică 500.
 */
final class ExceptionResponseMapper
{
    public static function run(callable $callback): \WP_REST_Response
    {
        try {
            $result = $callback();

            return new \WP_REST_Response($result, 200);
        } catch (ValidationException $e) {
            return new \WP_REST_Response([
                'code' => 'tpm_validation_error',
                'message' => 'Datele trimise nu sunt valide.',
                'errors' => $e->errors(),
            ], 422);
        } catch (DomainException $e) {
            return new \WP_REST_Response([
                'code' => 'tpm_domain_error',
                'message' => $e->getMessage(),
            ], 409);
        } catch (\Throwable $e) {
            error_log('[tpm-psych-platform] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            return new \WP_REST_Response([
                'code' => 'tpm_internal_error',
                'message' => 'A apărut o eroare neașteptată. Vezi jurnalul serverului pentru detalii.',
            ], 500);
        }
    }
}
