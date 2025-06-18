<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Noxo\FilamentCoupons\Coupons;
use Noxo\FilamentCoupons\Models\Coupon;
use Noxo\FilamentCoupons\Models\CouponUsage;
use Noxo\FilamentCoupons\Strategies\CouponStrategy;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->coupons = new Coupons();

    // Create a concrete test strategy class
    $this->testStrategyClass = new class extends CouponStrategy
    {
        public function getName(): string
        {
            return 'test_strategy';
        }

        public function getLabel(): string
        {
            return 'Test Strategy';
        }

        public function schema(): array
        {
            return [];
        }

        public function apply(Coupon $coupon): bool
        {
            return true;
        }
    };

    Config::set('filament-coupons.strategies', [
        get_class($this->testStrategyClass),
    ]);
});

describe('Strategy Management', function () {
    it('can get all strategies', function () {
        $strategies = $this->coupons->getStrategies();

        expect($strategies)->toBeArray()
            ->and($strategies)->toHaveKey('test_strategy');
    });

    it('can get specific strategy', function () {
        $strategy = $this->coupons->getStrategy('test_strategy');

        expect($strategy)->toBeInstanceOf(CouponStrategy::class);
    });

    it('returns null for non-existent strategy', function () {
        $strategy = $this->coupons->getStrategy('non_existent');

        expect($strategy)->toBeNull();
    });

    it('can get strategy payload schema', function () {
        // Create a strategy with schema
        $strategyWithSchema = new class extends CouponStrategy
        {
            public function getName(): string
            {
                return 'strategy_with_schema';
            }

            public function schema(): array
            {
                return ['field' => 'value'];
            }
        };

        $coupons = new Coupons();
        Config::set('filament-coupons.strategies', [
            get_class($strategyWithSchema),
        ]);

        $schema = $coupons->getStrategyPayloadSchema('strategy_with_schema');

        expect($schema)->toBe(['field' => 'value']);
    });

    it('returns empty array for strategy without schema', function () {
        $schema = $this->coupons->getStrategyPayloadSchema('non_existent');

        expect($schema)->toBe([]);
    });

    it('can check if strategy has payload schema', function () {
        expect($this->coupons->hasStrategyPayloadSchema('test_strategy'))->toBeFalse();
        expect($this->coupons->hasStrategyPayloadSchema('non_existent'))->toBeFalse();
    });
});

describe('Coupon Validation', function () {
    it('considers active coupon with no dates as active', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'starts_at' => null,
            'expires_at' => null,
        ]);

        expect($this->coupons->isActive($coupon))->toBeTrue();
    });

    it('considers inactive coupon as inactive', function () {
        $coupon = Coupon::factory()->create(['active' => false]);

        expect($this->coupons->isActive($coupon))->toBeFalse();
    });

    it('considers coupon not started yet as inactive', function () {
        $future = Carbon::now()->addDays(1);
        $coupon = Coupon::factory()->create([
            'active' => true,
            'starts_at' => $future,
        ]);

        expect($this->coupons->isActive($coupon))->toBeFalse();
    });

    it('considers expired coupon as inactive', function () {
        $past = Carbon::now()->subDays(1);
        $coupon = Coupon::factory()->create([
            'active' => true,
            'expires_at' => $past,
        ]);

        expect($this->coupons->isActive($coupon))->toBeFalse();
    });

    it('considers coupon within date range as active', function () {
        $past = Carbon::now()->subDays(1);
        $future = Carbon::now()->addDays(1);

        $coupon = Coupon::factory()->create([
            'active' => true,
            'starts_at' => $past,
            'expires_at' => $future,
        ]);

        expect($this->coupons->isActive($coupon))->toBeTrue();
    });
});

describe('Coupon Consumption', function () {
    it('can consume coupon without usage limit', function () {
        $coupon = Coupon::factory()->create(['usage_limit' => null]);

        expect($this->coupons->canConsume($coupon))->toBeTrue();
    });

    it('can consume coupon within usage limit', function () {
        $coupon = Coupon::factory()->create(['usage_limit' => 5]);

        // Create 3 usages
        CouponUsage::factory()->count(3)->forCoupon($coupon)->create();

        expect($this->coupons->canConsume($coupon))->toBeTrue();
    });

    it('cannot consume coupon that reached usage limit', function () {
        $coupon = Coupon::factory()->create(['usage_limit' => 3]);

        // Create 3 usages (reaching the limit)
        CouponUsage::factory()->count(3)->forCoupon($coupon)->create();

        expect($this->coupons->canConsume($coupon))->toBeFalse();
    });

    it('considers valid coupon as valid', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => 5,
        ]);

        expect($this->coupons->isValid($coupon))->toBeTrue();
    });

    it('considers invalid coupon as invalid', function () {
        $coupon = Coupon::factory()->create(['active' => false]);

        expect($this->coupons->isValid($coupon))->toBeFalse();
    });
});

describe('Coupon Application', function () {
    it('can apply coupon with valid strategy', function () {
        $coupon = Coupon::factory()->create(['strategy' => 'test_strategy']);

        expect($this->coupons->applyCoupon($coupon))->toBeTrue();
    });

    it('returns false for coupon with invalid strategy', function () {
        $coupon = Coupon::factory()->create(['strategy' => 'invalid_strategy']);

        expect($this->coupons->applyCoupon($coupon))->toBeFalse();
    });
});

describe('Coupon Consumption Process', function () {
    it('can consume valid coupon', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => 5,
        ]);

        $result = $this->coupons->consume($coupon);

        expect($result)->toBeTrue()
            ->and($coupon->usages()->count())->toBe(1);
    });

    it('cannot consume invalid coupon', function () {
        $coupon = Coupon::factory()->create(['active' => false]);

        $result = $this->coupons->consume($coupon);

        expect($result)->toBeFalse()
            ->and($coupon->usages()->count())->toBe(0);
    });

    it('can consume coupon with meta data', function () {
        $coupon = Coupon::factory()->create(['active' => true]);
        $meta = ['user_id' => 123, 'source' => 'web'];

        $result = $this->coupons->consume($coupon, null, $meta);

        expect($result)->toBeTrue()
            ->and($coupon->usages()->first()->meta)->toBe($meta);
    });

    it('can consume coupon with couponable model', function () {
        // For testing purposes, we'll create a mock user model
        $user = new class extends Illuminate\Database\Eloquent\Model
        {
            protected $table = 'users';

            protected $fillable = ['name'];
        };
        $user->id = 1;
        $user->name = 'Test User';

        $coupon = Coupon::factory()->create(['active' => true]);

        $result = $this->coupons->consume($coupon, $user);

        expect($result)->toBeTrue();

        $usage = $coupon->usages()->first();
        expect($usage->couponable_type)->toBe(get_class($user))
            ->and($usage->couponable_id)->toBe($user->id);
    });

    it('deactivates coupon when usage limit is reached', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => 1,
        ]);

        $result = $this->coupons->consume($coupon);

        expect($result)->toBeTrue();

        $coupon->refresh();
        expect($coupon->active)->toBeFalse();
    });

    it('does not deactivate coupon when deactiveIfLimitReached is false', function () {
        $coupon = Coupon::factory()->create([
            'active' => true,
            'usage_limit' => 1,
        ]);

        $result = $this->coupons->consume($coupon, null, [], false);

        expect($result)->toBeTrue();

        $coupon->refresh();
        expect($coupon->active)->toBeTrue();
    });

    it('handles database transaction correctly', function () {
        $coupon = Coupon::factory()->create(['active' => true]);

        // Mock DB transaction to verify it's being used
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $result = $this->coupons->consume($coupon);

        expect($result)->toBeTrue();
    });
});
