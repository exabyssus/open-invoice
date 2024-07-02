<?php

$generator = new \App\Services\GenerateTextualPrice();

it('generates text for cents and no euros', function () use ($generator) {
    expect($generator(0.1))->toBe('Nulle <i>euro</i> un 10 centi');
    expect($generator(0.01))->toBe('Nulle <i>euro</i> un 1 cents');
    expect($generator(0.0))->toBe('Nulle <i>euro</i> un 0 centi');
});

it('generates text for single digit price', function () use ($generator) {
    expect($generator(1.1))->toBe('Viens <i>euro</i> un 10 centi');
    expect($generator(5.1))->toBe('Pieci <i>euro</i> un 10 centi');
    expect($generator(9.1))->toBe('Deviņi <i>euro</i> un 10 centi');
});

it('generates text for double digit price', function () use ($generator) {
    expect($generator(10.0))->toBe('Desmit <i>euro</i> un 0 centi');
    expect($generator(11.0))->toBe('Vienpadsmit <i>euro</i> un 0 centi');
    expect($generator(75.0))->toBe('Septiņdesmit pieci <i>euro</i> un 0 centi');
});

it('generates text for hundreds digit price', function () use ($generator) {
    expect($generator(550.0))->toBe('Piecsimt piecdesmit <i>euro</i> un 0 centi');
    expect($generator(321.0))->toBe('Trīssimt divdesmit viens <i>euro</i> un 0 centi');
    expect($generator(712.0))->toBe('Septiņsimt divpadsmit <i>euro</i> un 0 centi');
});

it('generates text for thousands digit price', function () use ($generator) {
    expect($generator(10000.0))->toBe('Desmit tūkstoši <i>euro</i> un 0 centi');
    expect($generator(1001.0))->toBe('Tūkstoš viens <i>euro</i> un 0 centi');
    expect($generator(42545.32))->toBe('Četrdesmit divi tūkstoši piecsimt četrdesmit pieci <i>euro</i> un 32 centi');
});
