<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\GenericExecutionEngineBundle\Entity;

use Doctrine\ORM\Mapping\Column;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use ReflectionProperty;

/**
 * The JobRun mapping has to agree with Installer::installJobRunTable(), because a schema
 * built from the mapping instead of the installer is what breaks at runtime: a too-short
 * state column truncates on insert and then fails to map back to the enum, and a
 * non-nullable context column rejects the NULL that JobRunRepository::createFromJob()
 * writes.
 */
class JobRunMappingTest extends TestCase
{
    public function testStateColumnHoldsTheLongestJobRunState(): void
    {
        $longest = max(array_map(
            static fn (JobRunStates $state): int => strlen($state->value),
            JobRunStates::cases()
        ));

        $this->assertGreaterThanOrEqual(
            $longest,
            $this->columnFor('state')->length,
            'The state column is shorter than the longest JobRunStates value, so the state truncates on insert.'
        );
    }

    public function testContextColumnIsNullable(): void
    {
        $this->assertTrue(
            $this->columnFor('context')->nullable,
            'A JobRun is created without a context, so the column has to accept NULL.'
        );
    }

    private function columnFor(string $property): Column
    {
        $attributes = (new ReflectionProperty(JobRun::class, $property))->getAttributes(Column::class);

        $this->assertCount(1, $attributes, sprintf('JobRun::$%s has no ORM Column attribute.', $property));

        return $attributes[0]->newInstance();
    }
}
