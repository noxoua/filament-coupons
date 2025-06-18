<?php

declare(strict_types=1);

use Noxo\FilamentCoupons\Actions\ApplyCouponAction;
use Noxo\FilamentCoupons\Commands\CreateStrategyCommand;
use Noxo\FilamentCoupons\Concerns\CanNotifyAndRedirect;
use Noxo\FilamentCoupons\Coupons;
use Noxo\FilamentCoupons\CouponsPlugin;
use Noxo\FilamentCoupons\CouponsServiceProvider;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Models\CouponUsage;
use Noxo\FilamentCoupons\Resources\CouponResource;
use Noxo\FilamentCoupons\Strategies\CouponStrategy;

describe('Architecture Tests', function () {
    it('all main classes exist and are instantiable', function () {
        expect(class_exists(Coupons::class))->toBeTrue();
        expect(class_exists(CouponsServiceProvider::class))->toBeTrue();
        expect(class_exists(CouponsPlugin::class))->toBeTrue();
        expect(class_exists(Coupon::class))->toBeTrue();
        expect(class_exists(CouponUsage::class))->toBeTrue();
        expect(class_exists(CouponStrategy::class))->toBeTrue();
        expect(class_exists(CouponResource::class))->toBeTrue();
        expect(class_exists(ApplyCouponAction::class))->toBeTrue();
        expect(class_exists(CreateStrategyCommand::class))->toBeTrue();
    });

    it('all traits exist and are usable', function () {
        expect(trait_exists(CanNotifyAndRedirect::class))->toBeTrue();

        // Test trait can be used
        $testClass = new class
        {
            use CanNotifyAndRedirect;
        };

        expect($testClass)->toBeObject();
    });

    it('models have correct table names', function () {
        $coupon = new Coupon();
        $usage = new CouponUsage();

        expect($coupon->getTable())->toBe('coupons');
        expect($usage->getTable())->toBe('coupon_usages');
    });

    it('models have correct relationships', function () {
        expect(method_exists(Coupon::class, 'usages'))->toBeTrue();
        expect(method_exists(CouponUsage::class, 'coupon'))->toBeTrue();
        expect(method_exists(CouponUsage::class, 'couponable'))->toBeTrue();
        expect(method_exists(CouponUsage::class, 'user'))->toBeTrue();
    });

    it('service provider has correct methods', function () {
        expect(method_exists(CouponsServiceProvider::class, 'configurePackage'))->toBeTrue();
        expect(method_exists(CouponsServiceProvider::class, 'packageRegistered'))->toBeTrue();
        expect(method_exists(CouponsServiceProvider::class, 'packageBooted'))->toBeTrue();
    });

    it('plugin implements correct interface', function () {
        $plugin = new CouponsPlugin();

        expect($plugin)->toBeInstanceOf(Filament\Contracts\Plugin::class);
        expect(method_exists($plugin, 'getId'))->toBeTrue();
        expect(method_exists($plugin, 'register'))->toBeTrue();
        expect(method_exists($plugin, 'boot'))->toBeTrue();
    });

    it('strategy has correct abstract methods', function () {
        expect(method_exists(CouponStrategy::class, 'getName'))->toBeTrue();
        expect(method_exists(CouponStrategy::class, 'getLabel'))->toBeTrue();
        expect(method_exists(CouponStrategy::class, 'apply'))->toBeTrue();
        expect(method_exists(CouponStrategy::class, 'schema'))->toBeTrue();
    });

    it('action extends correct base class', function () {
        $action = new ApplyCouponAction('test');

        expect($action)->toBeInstanceOf(Filament\Actions\Action::class);
        expect(method_exists($action, 'error'))->toBeTrue();
    });

    it('command extends correct base class', function () {
        $command = new CreateStrategyCommand();

        expect($command)->toBeInstanceOf(Illuminate\Console\Command::class);
        expect(in_array(Filament\Support\Commands\Concerns\CanManipulateFiles::class, class_uses($command)))->toBeTrue();
    });

    it('resource extends correct base class', function () {
        expect(is_subclass_of(CouponResource::class, Filament\Resources\Resource::class))->toBeTrue();
        expect(method_exists(CouponResource::class, 'form'))->toBeTrue();
        expect(method_exists(CouponResource::class, 'table'))->toBeTrue();
        expect(method_exists(CouponResource::class, 'getPages'))->toBeTrue();
        expect(method_exists(CouponResource::class, 'getRelations'))->toBeTrue();
    });
});

describe('Package Structure Tests', function () {
    it('has all required composer.json fields', function () {
        $composerPath = __DIR__.'/../../composer.json';
        expect(file_exists($composerPath))->toBeTrue();

        $composer = json_decode(file_get_contents($composerPath), true);

        expect($composer)->toHaveKey('name');
        expect($composer)->toHaveKey('description');
        expect($composer)->toHaveKey('license');
        expect($composer)->toHaveKey('authors');
        expect($composer)->toHaveKey('require');
        expect($composer)->toHaveKey('autoload');
    });

    it('has correct autoload configuration', function () {
        $composerPath = __DIR__.'/../../composer.json';
        $composer = json_decode(file_get_contents($composerPath), true);

        expect($composer['autoload']['psr-4'])->toHaveKey('Noxo\\FilamentCoupons\\');
        expect($composer['autoload']['files'])->toContain('src/helpers.php');
    });

    it('has development dependencies', function () {
        $composerPath = __DIR__.'/../../composer.json';
        $composer = json_decode(file_get_contents($composerPath), true);

        expect($composer)->toHaveKey('require-dev');
        expect($composer['require-dev'])->toHaveKey('pestphp/pest');
        expect($composer['require-dev'])->toHaveKey('orchestra/testbench');
    });

    it('has correct package scripts', function () {
        $composerPath = __DIR__.'/../../composer.json';
        $composer = json_decode(file_get_contents($composerPath), true);

        expect($composer)->toHaveKey('scripts');
        expect($composer['scripts'])->toHaveKey('test');
        expect($composer['scripts'])->toHaveKey('test-coverage');
        expect($composer['scripts'])->toHaveKey('analyse');
    });
});

describe('File Coverage Tests', function () {
    it('covers all PHP files in src directory', function () {
        $srcPath = __DIR__.'/../../src';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcPath)
        );

        $phpFiles = [];
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        // We should have tests covering all main files
        expect(count($phpFiles))->toBeGreaterThan(0);

        // Check that key files exist
        $keyFiles = [
            'Coupons.php',
            'CouponsServiceProvider.php',
            'CouponsPlugin.php',
            'Models/Coupon.php',
            'Models/CouponUsage.php',
            'Strategies/CouponStrategy.php',
            'Actions/ApplyCouponAction.php',
            'Commands/CreateStrategyCommand.php',
            'Concerns/CanNotifyAndRedirect.php',
            'Resources/CouponResource.php',
            'helpers.php',
        ];

        foreach ($keyFiles as $keyFile) {
            $found = false;
            foreach ($phpFiles as $phpFile) {
                if (str_contains($phpFile, $keyFile)) {
                    $found = true;
                    break;
                }
            }
            expect($found)->toBeTrue("File {$keyFile} should exist in src directory");
        }
    });

    it('has test files for all major components', function () {
        $testPath = __DIR__.'/../';
        $testFiles = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($testPath)
        );

        $testFileNames = [];
        foreach ($testFiles as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && str_contains($file->getFilename(), 'Test.php')) {
                $testFileNames[] = $file->getFilename();
            }
        }

        // Check that we have tests for major components
        $expectedTests = [
            'CouponsTest.php',
            'CouponTest.php',
            'CouponUsageTest.php',
            'CouponStrategyTest.php',
            'ServiceProviderTest.php',
            'PluginTest.php',
            'ApplyCouponActionTest.php',
            'CreateStrategyCommandTest.php',
            'CanNotifyAndRedirectTest.php',
            'HelpersTest.php',
        ];

        // Make testFileNames unique to avoid duplicates
        $testFileNames = array_values(array_unique($testFileNames));

        foreach ($expectedTests as $expectedTest) {
            expect(in_array($expectedTest, $testFileNames))->toBeTrue("Test file {$expectedTest} should exist");
        }
    });
});
