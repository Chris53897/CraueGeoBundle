<?php

namespace Craue\GeoBundle\Tests\Doctrine\Fixtures;

use Craue\GeoBundle\Tests\Doctrine\Fixtures\CraueGeo\PuertoRicoGeonamesPostalCodeData;
use Craue\GeoBundle\Tests\IntegrationTestCase;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group integration
 *
 * @author Christian Raue <christian.raue@gmail.com>
 * @copyright 2011-2020 Christian Raue
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FixtureTest extends IntegrationTestCase {

	/**
	 * @dataProvider getPlatformConfigs
	 */
	public function testImportByUsingFixtureDirectly($platform, $config, $requiredExtension) {
		$this->initClient($requiredExtension, ['environment' => $platform, 'config' => $config]);

		// [A] add some data which is meant to be removed by importing new data
		$this->persistGeoPostalCode('DE', '14473', 52.392759, 13.065135);

		// [B] import new data by using the fixture directly
		$fixture = new PuertoRicoGeonamesPostalCodeData();
		ob_start();
		$fixture->load($this->getEntityManager());
		$output = ob_get_clean();
		$this->assertEquals(" 177\n", $output);

		// [A] verify that old data has been removed
		$this->assertCount(0, $this->getRepo()->findBy(['country' => 'DE']));

		// [B] verify that new data was imported as expected
		$this->assertCount(177, $this->getRepo()->findAll());
	}

	/**
	 * @dataProvider getPlatformConfigs
	 */
	public function testImportByDoctrineFixturesBundle2Command($platform, $config, $requiredExtension) {
		if (class_exists(SymfonyFixturesLoader::class)) {
			$this->markTestSkipped('DoctrineFixturesBundle >= 3.0 does not allow loading fixtures from a directory anymore.');
		}

		$this->initClient($requiredExtension, ['environment' => $platform, 'config' => $config]);

		// [A] add some data which is meant to be removed by importing new data
		$this->persistGeoPostalCode('DE', '14473', 52.392759, 13.065135);

		// [B] import new data by using a command to load the fixture
		$application = new Application(static::$kernel);
		$application->setAutoExit(false);
		$output = self::executeCommand($application, 'doctrine:fixtures:load', ['--append' => null, '--fixtures' => 'Tests/Doctrine/Fixtures/CraueGeo']);
		$this->assertEquals(sprintf("  > loading %s\n 177\n", PuertoRicoGeonamesPostalCodeData::class), $output);

		// [A] verify that old data has been removed
		$this->assertCount(0, $this->getRepo()->findBy(['country' => 'DE']));

		// [B] verify that new data was imported as expected
		$this->assertCount(177, $this->getRepo()->findAll());
	}

	/**
	 * @dataProvider dataImportByDoctrineFixturesBundle3Command
	 */
	public function testImportByDoctrineFixturesBundle3Command($platform, $config, $requiredExtension) {
		if (!interface_exists(FixtureGroupInterface::class)) {
			$this->markTestSkipped('DoctrineFixturesBundle < 3.1 does not support fixture groups.');
		}

		$this->initClient($requiredExtension, ['environment' => 'fixtureAsAService_' . $platform, 'config' => $config]);

		// [A] add some data which is meant to be removed by importing new data
		$this->persistGeoPostalCode('DE', '14473', 52.392759, 13.065135);

		// [B] import new data by using a command to load the fixture
		$application = new Application(static::$kernel);
		$application->setAutoExit(false);
		$output = self::executeCommand($application, 'doctrine:fixtures:load', ['--append' => null, '--group' => ['craue_geo_test']]);
		$this->assertEquals(sprintf("\n   > loading %s\n 177\n", PuertoRicoGeonamesPostalCodeData::class), $output);

		// [A] verify that old data has been removed
		$this->assertCount(0, $this->getRepo()->findBy(['country' => 'DE']));

		// [B] verify that new data was imported as expected
		$this->assertCount(177, $this->getRepo()->findAll());
	}

	public function dataImportByDoctrineFixturesBundle3Command() {
		return self::duplicateTestDataForEachPlatform([
			[],
		], 'config_fixtureAsAService.yml');
	}

	/**
	 * @dataProvider dataImportByDoctrineFixturesBundle3CommandWithAutoRegistration
	 */
	public function testImportByDoctrineFixturesBundle3CommandWithAutoRegistration($platform, $config, $requiredExtension) {
		if (!interface_exists(FixtureGroupInterface::class)) {
			$this->markTestSkipped('DoctrineFixturesBundle < 3.1 does not support fixture groups.');
		}

		$this->initClient($requiredExtension, ['environment' => 'fixtureAsAService_autoRegistration_' . $platform, 'config' => $config]);

		// [A] add some data which is meant to be removed by importing new data
		$this->persistGeoPostalCode('DE', '14473', 52.392759, 13.065135);

		// [B] import new data by using a command to load the fixture
		$application = new Application(static::$kernel);
		$application->setAutoExit(false);
		$output = self::executeCommand($application, 'doctrine:fixtures:load', ['--append' => null, '--group' => ['craue_geo_test']]);
		$this->assertEquals(sprintf("\n   > loading %s\n 177\n", PuertoRicoGeonamesPostalCodeData::class), $output);

		// [A] verify that old data has been removed
		$this->assertCount(0, $this->getRepo()->findBy(['country' => 'DE']));

		// [B] verify that new data was imported as expected
		$this->assertCount(177, $this->getRepo()->findAll());
	}

	public function dataImportByDoctrineFixturesBundle3CommandWithAutoRegistration() {
		return self::duplicateTestDataForEachPlatform([
			[],
		], 'config_fixtureAsAService_autoRegistration.yml');
	}

	private static function executeCommand(Application $application, $command, array $options = []) {
		$options = array_merge($options, [
			'--env' => $application->getKernel()->getEnvironment(),
			'--no-debug' => null,
			'--no-interaction' => null,
			'command' => $command,
		]);

		$tester = new CommandTester($application->find($command));

		ob_start();
		$tester->execute($options);
		$output = ob_get_clean();

		return $tester->getDisplay(true) . $output;
	}

}
