<?php
declare(strict_types=1);

namespace App;

/**
 * Validación sencilla con mensajes en español. Guarda el primer error de cada campo.
 */
final class Validator
{
    private array $errors = [];

    public function __construct(private readonly array $data)
    {
    }

    private function value(string $field): string
    {
        $value = $this->data[$field] ?? '';
        return is_scalar($value) ? (string) $value : '';
    }

    public function required(string $field, string $label): self
    {
        if (trim($this->value($field)) === '') {
            $this->addError($field, "El campo «{$label}» es obligatorio.");
        }
        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, "Introduce un correo electrónico válido en «{$label}», por ejemplo nombre@dominio.es.");
        }
        return $this;
    }

    public function maxLength(string $field, string $label, int $max): self
    {
        if (mb_strlen($this->value($field)) > $max) {
            $this->addError($field, "El campo «{$label}» no puede superar los {$max} caracteres.");
        }
        return $this;
    }

    public function in(string $field, string $label, array $allowed): self
    {
        if (!in_array($this->value($field), $allowed, true)) {
            $this->addError($field, "Selecciona una opción válida en «{$label}».");
        }
        return $this;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
