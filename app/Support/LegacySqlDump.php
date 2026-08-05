<?php

namespace App\Support;

use RuntimeException;

final class LegacySqlDump
{
    public static function rows(string $path, string $table): array
    {
        $sql = file_get_contents($path);
        $pattern = '/INSERT INTO `'.preg_quote($table, '/').'` \((.*?)\) VALUES\s*/s';
        if ($sql === false || ! preg_match($pattern, $sql, $match, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException("No se encontraron datos de {$table}.");
        }
        $columns = array_map(
            fn ($column) => trim($column, " `\r\n\t"),
            explode(',', $match[1][0])
        );
        $offset = $match[0][1] + strlen($match[0][0]);

        return array_map(function (array $values) use ($columns, $table): array {
            if (count($values) !== count($columns)) {
                throw new RuntimeException("Columnas inválidas en {$table}.");
            }
            return array_combine($columns, $values);
        }, self::tuples(substr($sql, $offset)));
    }

    private static function tuples(string $sql): array
    {
        $rows = [];
        $row = [];
        $token = '';
        $depth = 0;
        $quoted = false;
        $escaped = false;
        $tokenQuoted = false;
        $length = strlen($sql);
        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            if ($quoted) {
                if ($escaped) {
                    $token .= ['n' => "\n", 'r' => "\r", 't' => "\t"][$char] ?? $char;
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === "'") {
                    $quoted = false;
                } else {
                    $token .= $char;
                }
                continue;
            }
            if ($char === "'") {
                $quoted = true;
                $tokenQuoted = true;
            } elseif ($char === '(' && $depth === 0) {
                $depth = 1;
            } elseif ($char === ',' && $depth === 1) {
                $row[] = self::value($token, $tokenQuoted);
                $token = '';
                $tokenQuoted = false;
            } elseif ($char === ')' && $depth === 1) {
                $row[] = self::value($token, $tokenQuoted);
                $rows[] = $row;
                $row = [];
                $token = '';
                $tokenQuoted = false;
                $depth = 0;
            } elseif ($char === ';' && $depth === 0) {
                break;
            } elseif ($depth === 1) {
                $token .= $char;
            }
        }

        return $rows;
    }

    private static function value(string $value, bool $quoted): mixed
    {
        $value = $quoted ? $value : trim($value);
        if ($quoted) return $value;
        if (strtoupper($value) === 'NULL') return null;
        if (is_numeric($value)) return str_contains($value, '.') ? (float) $value : (int) $value;

        return $value;
    }
}
