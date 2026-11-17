<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\LgpdMinors\Enums\AgeBand;
use JeffersonGoncalves\LgpdMinors\Enums\AssuranceMethod;
use JeffersonGoncalves\LgpdMinors\Facades\LgpdMinors;
use JeffersonGoncalves\LgpdMinors\Models\Suspension;
use JeffersonGoncalves\LgpdMinors\Tests\Fixtures\Person;

beforeEach(function () {
    $this->person = Person::create(['name' => 'Ana']);
});

it('maps ages to bands, with 16 still needing a guardian', function () {
    expect(AgeBand::fromAge(11))->toBe(AgeBand::Child)
        ->and(AgeBand::fromAge(12))->toBe(AgeBand::YoungAdolescent)
        ->and(AgeBand::fromAge(16))->toBe(AgeBand::YoungAdolescent)
        ->and(AgeBand::fromAge(17))->toBe(AgeBand::Adolescent)
        ->and(AgeBand::fromAge(18))->toBe(AgeBand::Adult)
        ->and(AgeBand::fromAge(16)->needsGuardian())->toBeTrue()
        ->and(AgeBand::fromAge(17)->needsGuardian())->toBeFalse();
});

it('is unknown without results and treats unknown users as minors in likely-access products', function () {
    expect(LgpdMinors::band($this->person))->toBe(AgeBand::Unknown)
        ->and(LgpdMinors::treatAsMinor($this->person))->toBeTrue()
        ->and(LgpdMinors::canLowerProtection($this->person))->toBeFalse();

    config()->set('lgpd-minors.likely_access', false);

    expect(LgpdMinors::treatAsMinor($this->person))->toBeFalse();
});

it('does not take self-declaration as verification', function () {
    LgpdMinors::assure($this->person, 30, AssuranceMethod::SelfDeclaration);

    expect(LgpdMinors::band($this->person))->toBe(AgeBand::Adult)
        ->and(LgpdMinors::isVerifiedAdult($this->person))->toBeFalse()
        ->and(LgpdMinors::allowsProfiling($this->person))->toBeFalse();

    LgpdMinors::assure($this->person, 30, AssuranceMethod::Document, 'acme-id');

    expect(LgpdMinors::isVerifiedAdult($this->person))->toBeTrue()
        ->and(LgpdMinors::allowsProfiling($this->person))->toBeTrue();
});

it('lets reliable results outweigh self-declaration', function () {
    LgpdMinors::assure($this->person, 10, AssuranceMethod::SelfDeclaration);
    LgpdMinors::assure($this->person, 25, AssuranceMethod::Document);

    expect(LgpdMinors::band($this->person))->toBe(AgeBand::Adult);
});

it('picks the most protective band when reliable results diverge', function () {
    LgpdMinors::assure($this->person, AgeBand::Adult, AssuranceMethod::Document);
    LgpdMinors::assure($this->person, AgeBand::Adolescent, AssuranceMethod::StoreSignal);

    expect(LgpdMinors::band($this->person))->toBe(AgeBand::Adolescent)
        ->and(LgpdMinors::isVerifiedAdult($this->person))->toBeFalse();
});

it('ignores expired results', function () {
    LgpdMinors::assure($this->person, 30, AssuranceMethod::OsSignal);

    $this->travel(31)->days();

    expect(LgpdMinors::band($this->person))->toBe(AgeBand::Unknown);
});

it('requires an active guardian up to 16 and blocks lowering protection without one', function () {
    $mother = Person::create(['name' => 'Maria']);
    LgpdMinors::assure($this->person, 14, AssuranceMethod::StoreSignal);

    expect(LgpdMinors::needsGuardian($this->person))->toBeTrue()
        ->and(LgpdMinors::canLowerProtection($this->person))->toBeFalse()
        ->and(LgpdMinors::missingGuardian()->pluck('id')->all())->toBe([$this->person->id]);

    $link = LgpdMinors::link($this->person, $mother, 'mother');

    expect(LgpdMinors::needsGuardian($this->person))->toBeTrue(); // not verified yet

    $link->verify();

    expect(LgpdMinors::needsGuardian($this->person))->toBeFalse()
        ->and(LgpdMinors::canLowerProtection($this->person))->toBeTrue()
        ->and(LgpdMinors::guardians($this->person)->pluck('name')->all())->toBe(['Maria'])
        ->and(LgpdMinors::missingGuardian())->toBeEmpty();

    $link->revoke();

    expect(LgpdMinors::needsGuardian($this->person))->toBeTrue();
});

it('suspends with an appeal and lifts', function () {
    $suspension = LgpdMinors::suspend($this->person, 'Profile photos suggest a child.');

    expect(LgpdMinors::isSuspended($this->person))->toBeTrue();

    $suspension->appeal('I am 19, see my document.');

    expect($suspension->awaitsDecision())->toBeTrue()
        ->and(Suspension::query()->scopes('awaitingDecision')->count())->toBe(1);

    $suspension->lift('Document checked.');

    expect(LgpdMinors::isSuspended($this->person))->toBeFalse()
        ->and(fn () => $suspension->appeal('again'))->toThrow(LogicException::class);
});

it('keeps an upheld suspension in force', function () {
    LgpdMinors::suspend($this->person, 'Reason')->uphold('Confirmed.');

    expect(LgpdMinors::isSuspended($this->person))->toBeTrue();
});

it('gates adult routes on every request', function () {
    Route::get('/adult', fn () => 'ok')->middleware('lgpd.adult');
    Route::get('/home', fn () => 'home')->middleware('lgpd.suspended');

    $this->get('/adult')->assertForbidden();
    $this->actingAs($this->person)->get('/adult')->assertForbidden();

    LgpdMinors::assure($this->person, 40, AssuranceMethod::VerifiableCredential);
    $this->actingAs($this->person)->get('/adult')->assertOk();

    $this->actingAs($this->person)->get('/home')->assertOk();
    LgpdMinors::suspend($this->person, 'Reason');
    $this->actingAs($this->person)->get('/home')->assertForbidden();
});

it('redirects to the verify route when configured', function () {
    Route::get('/verify', fn () => 'verify')->name('age.verify');
    Route::get('/adult', fn () => 'ok')->middleware('lgpd.adult');
    config()->set('lgpd-minors.verify_route', 'age.verify');

    $this->actingAs($this->person)->get('/adult')->assertRedirect('/verify');
});

it('hides ad tags from users who are not verified adults', function () {
    $view = '@lgpdProfiling pixel @else none @endlgpdProfiling';

    $this->actingAs($this->person);
    expect(trim(Blade::render($view)))->toBe('none');

    LgpdMinors::assure($this->person, 30, AssuranceMethod::Document);
    expect(trim(Blade::render($view)))->toBe('pixel');
});

it('reports gaps from the command', function () {
    LgpdMinors::assure($this->person, 9, AssuranceMethod::Guardian);

    $this->artisan('lgpd:minors')->assertFailed();

    LgpdMinors::link($this->person, Person::create(['name' => 'Pai']), 'father', verified: true);

    $this->artisan('lgpd:minors')->assertSuccessful();
});
