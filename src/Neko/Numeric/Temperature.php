<?php declare(strict_types=1);
namespace Neko\Numeric;

use InvalidArgumentException;
use Neko\Comparable;
use Neko\Equatable;
use Override;

/**
 * Represents a temperature value.
 */
final class Temperature implements Comparable, Equatable
{
    /**
     * @var float The temperature in Kelvin units.
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
     * Compares this object with another Temperature object and returns an integer indicating the order.
     *
     * @param mixed $other The object to compare.
     *
     * @return int Returns a value less than zero if this instance is less than the other value.
     * Returns zero if this instance is equal to the other value.
     * Returns a value greater than zero if this instance is greater than the other value.
     *
     * @throws InvalidArgumentException if $other is not an instance of Temperature.
     */
    #[Override]
    public function compareTo(mixed $other): int
    {
        if ($other instanceof Temperature) {
            return $other->value <=> $this->value;
        }

        throw new InvalidArgumentException('\'$other\' must be of type ' . Temperature::class);
    }

    /**
     * Returns true if this object is equal to another Temperature object.
     *
     * @param mixed $other The object to compare.
     *
     * @return bool
     */
    #[Override]
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
