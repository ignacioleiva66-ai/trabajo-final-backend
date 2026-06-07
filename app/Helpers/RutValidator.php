<?php

namespace App\Helpers;

/**
 * Validador de RUT chileno.
 *
 * Algoritmo oficial del SII (Servicio de Impuestos Internos de Chile).
 * Acepta formatos: "12.345.678-9", "12345678-9", "123456789"
 */
class RutValidator
{
    /**
     * Valida si un RUT chileno es correcto.
     * Retorna true si es válido, false si no.
     */
    public static function validar(string $rut): bool
    {
        try {
            // 1. Limpiar puntos, guiones y espacios
            $rut = strtoupper(str_replace(['.', '-', ' '], '', trim($rut)));

            if (strlen($rut) < 2) {
                return false;
            }

            // 2. Separar cuerpo y dígito verificador
            $digitoVerificador = substr($rut, -1);
            $cuerpo            = substr($rut, 0, -1);

            // 3. El cuerpo debe ser numérico
            if (!ctype_digit($cuerpo)) {
                return false;
            }

            // 4. Calcular dígito verificador esperado
            $dvEsperado = self::calcularDv((int) $cuerpo);

            // 5. Comparar
            return $digitoVerificador === $dvEsperado;

        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Calcula el dígito verificador para un cuerpo numérico dado.
     * Algoritmo módulo 11.
     *
     * @param int $cuerpo  Parte numérica del RUT sin DV
     * @return string      '0'-'9' o 'K'
     */
    public static function calcularDv(int $cuerpo): string
    {
        $suma       = 0;
        $multiplo   = 2;

        // Recorrer dígitos de derecha a izquierda
        while ($cuerpo > 0) {
            $suma    += ($cuerpo % 10) * $multiplo;
            $cuerpo   = (int) floor($cuerpo / 10);
            $multiplo = ($multiplo < 7) ? $multiplo + 1 : 2;
        }

        $resto = $suma % 11;
        $dv    = 11 - $resto;

        if ($dv === 11) return '0';
        if ($dv === 10) return 'K';
        return (string) $dv;
    }

    /**
     * Formatea un RUT al formato estándar: XX.XXX.XXX-D
     * No valida, solo formatea. Usar validar() antes.
     */
    public static function formatear(string $rut): string
    {
        $rut  = strtoupper(str_replace(['.', '-', ' '], '', trim($rut)));
        $dv   = substr($rut, -1);
        $body = substr($rut, 0, -1);

        return number_format((int) $body, 0, ',', '.') . '-' . $dv;
    }
}
