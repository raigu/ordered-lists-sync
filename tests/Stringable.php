<?php
/**
 * Stringable polyfill for PHP 7.
 */
if (!interface_exists('Stringable')) {
    interface Stringable
    {
        public function __toString(): string;
    }
}