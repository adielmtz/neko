<?php declare(strict_types=1);
namespace Neko\Numeric;

use InvalidArgumentException;
use Neko\Comparable;
use Neko\Equatable;

/**
 * Represents a temperature value.
 */
final class Temperature implements Comparable, Equatable
{
    /**
     * @var float The temperature in Kelvin.
     */
    private float $value;

    /**
     * Returns a new Temperature instance from a Celsius value.
     *
     * @param float $celsius The temperature in Celsius.
     *
     * @return Temperature
     */
    public static function fromCelsius(float $celsius): Temperature
    {
        return new Temperature($celsius + 273.15);
    }

    /**
     * Returns a new Temperature instance from a Fahrenheit value.
     *
     * @param float $fahrenheit The temperature in Fahrenheit.
     *
     * @return Temperature
     */
    public static function fromFahrenheit(float $fahrenheit): Temperature
    {
        return new Temperature((($fahrenheit - 32) * 5 / 9) + 273.15);
    }

    /**
     * Returns a new Temperature instance from a Kelvin value.
     *
     * @param float $kelvin The temperature in Kelvin.
     *
     * @return Temperature
     */
    public static function fromKelvin(float $kelvin): Temperature
    {
        return new Temperature($kelvin);
    }

    /**
     * Temperature constructor.
     *
     * @param float $kelvin The temperature in Kelvin.
     */
    private function __construct(float $kelvin)
    {
        $this->value = $kelvin;
    }

    /**
     * Compares this temperature with another Temperature instance.
     *
     * @param mixed $other The temperature to compare with.
     *
     * @return int
     * @throws InvalidArgumentException If $other is not an instance of Temperature.
     */
    public function compareTo(mixed $other): int
    {
        if ($other instanceof Temperature) {
            return $other->value <=> $this->value;
        }

        throw new InvalidArgumentException('\'$other\' must be of type ' . Temperature::class);
    }

    /**
     * Returns true if this temperature equals the other Temperature instance.
     *
     * @param mixed $other The temperature object to compare with.
     *
     * @return bool
     */
    public function equals(mixed $other): bool
    {
        return $other instanceof Temperature
            && $other->value === $this->value;
    }

    /**
     * Returns the temperature in Celsius.
     *
     * @return float
     */
    public function toCelsius(): float
    {
        return $this->value - 273.15;
    }

    /**
     * Returns the temperature in Fahrenheit.
     *
     * @return float
     */
    public function toFahrenheit(): float
    {
        return (($this->value - 273.15) * 9 / 5) + 32;
    }

    /**
     * Returns the temperature in Kelvin.
     *
     * @return float
     */
    public function toKelvin(): float
    {
        return $this->value;
    }
}
