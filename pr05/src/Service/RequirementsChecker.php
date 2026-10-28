<?php

declare(strict_types=1);

/**
 * Сверяет классы с class_requirements.csv: есть ли нужное свойство и совпадает ли его тип.
 */
final class RequirementsChecker
{
    /**
     * @return list<array{class:string, property:string, type:string, required:bool, exists:bool, actualType:string, ok:bool}>
     */
    public static function check(string $csvPath): array
    {
        if (!is_readable($csvPath) || ($handle = fopen($csvPath, 'rb')) === false) {
            return [];
        }

        $header = fgetcsv($handle, null, ';', '"', '\\') ?: [];
        $header = array_map(static fn ($h): string => trim((string) preg_replace('/^\x{FEFF}/u', '', (string) $h)), $header);

        $result = [];
        while (($line = fgetcsv($handle, null, ';', '"', '\\')) !== false) {
            if ($line === [null] || count($line) !== count($header)) {
                continue;
            }
            $row = array_combine($header, array_map('trim', $line));
            $class = $row['class'];
            $property = $row['property'];
            $exists = class_exists($class) && property_exists($class, $property);
            $actualType = '';
            if ($exists) {
                $type = (new ReflectionProperty($class, $property))->getType();
                $actualType = $type instanceof ReflectionNamedType ? $type->getName() : (string) $type;
            }
            $result[] = [
                'class' => $class,
                'property' => $property,
                'type' => $row['type'],
                'required' => $row['required'] === 'yes',
                'exists' => $exists,
                'actualType' => $actualType,
                'ok' => $exists && $actualType === $row['type'],
            ];
        }
        fclose($handle);

        return $result;
    }
}
