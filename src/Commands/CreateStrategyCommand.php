<?php

namespace Noxo\FilamentCoupons\Commands;

use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Illuminate\Console\Command;
use Noxo\FilamentCoupons\CouponsServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\text;

#[AsCommand(name: 'make:coupons-strategy')]
class CreateStrategyCommand extends Command
{
    use CanManipulateFiles;

    public $description = 'Create a new coupon strategy';

    protected $signature = 'make:coupons-strategy {name?}';

    public function handle(): int
    {
        $strategy = (string) str($this->argument('name') ?? text(
            label: 'What is the strategy name?',
            placeholder: 'FreeSubscription',
            required: true,
        ))
            ->studly()
            ->beforeLast('Strategy');

        $strategyPath = app_path("Coupons/{$strategy}.php");

        $this->copyStubToApp('Strategy', $strategyPath, [
            'strategyClass' => $strategy,
            'strategyLabel' => str($strategy)->headline()->toString(),
        ]);

        $this->components->info("Coupon strategy [{$strategyPath}] created successfully.");
        $this->components->info("Don't forget to register it in the config [app/config/filament-coupons.php] file");

        if (! file_exists(config_path('filament-coupons.php'))) {
            $this->callSilently('vendor:publish', [
                '--tag' => CouponsServiceProvider::$name . '-config',
            ]);
        }

        return self::SUCCESS;
    }
}
